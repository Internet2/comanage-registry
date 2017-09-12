<?php
/**
 * COmanage Registry Default Normalizer Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class DefaultNormalizer extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "normalizer";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v0.9.2
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform normalizations on the specified data
   *
   * @since  COmanage Registry v0.9.2
   * @param  Array Data to be saved, in typical Cake format
   * @return Array Data in the same format
   */
  
  public function normalize($data) {
    $ret = $data;
    
    $normalizations = array(
      'Address' => array(
        'mixCase' => array('street', 'locality', 'state', 'country'),
        'trimWhitespace' => array('street', 'locality', 'state', 'postal_code', 'country')
      ),
      'CoPersonRole' => array(
        'mixCase' => array('title', 'o', 'ou'),
        'trimWhitespace' => array('title', 'o', 'ou')
      ),
      'EmailAddress' => array(
        // Note cake validation will likely prevent this from being called
        'trimWhitespace' => array('mail')
      ),
      'Identifier' => array(
        'trimWhiteSpace' => array('identifier')
      ),
      'Name' => array(
        // For now, we don't mix case to avoid dealing with issues like people who
        // go by lowercase names, or McPherson-style capitalization
        'trimWhitespace' => array('honorific', 'given', 'middle', 'family', 'suffix')
      ),
      'TelephoneNumber' => array(
        // Following E.123 format, we only use spaces in telephone numbers
        // (the + and extension label get added by formatTelephone at rendering time)
        'punctuationToSpace' => array('country_code', 'area_code', 'number', 'extension'),
        'trimWhitespace' => array('country_code', 'area_code', 'number', 'extension')
      ),
      'Url' => array(
        // We don't normalize an http:// prefix because cake validation will prevent
        // a URL from being submitted without a prefix (and we wouldn't know the
        // protocol anyway).
        'trimWhitespace' => array('url')
      )
    );
    
    // In order for this to work, Co#PersonExtendedAttribute has to be after CoPersonRole
    // in $data (which it currently is, but not guaranteed). However, due to the hack in
    // CoPersonRole::checkWriteFollowups, we'll get called a second time for extended
    // attributes, so this should generally be fine.
    
    foreach(array_keys($ret) as $model) {
      // Also check for Extended Attributes
      
      if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $model)) {
        // Extract the CO ID
        
        $coId = preg_replace(array('/^Co/', '/PersonExtendedAttribute$/'),
                             array('', ''),
                             $model);
        
        // Figure out which extended attributes are of type string
        
        $args = array();
        $args['conditions']['co_id'] = $coId;
        $args['conditions']['type'] = ExtendedAttributeEnum::Varchar32;
        $args['fields'] = array('CoExtendedAttribute.name', 'CoExtendedAttribute.type');
        $args['contain'] = false;
        
        $CoExtendedAttribute = ClassRegistry::init('CoExtendedAttribute');
        
        $extattrs = $CoExtendedAttribute->find('list', $args);
        
        if(!empty($extattrs)) {
          foreach($extattrs as $name => $type) {
            // Add these attributes to the normalization hash.
            
            // We only trim whitespace since we can't say too much about the contents
            // of the extended attribute.
            
            $normalizations[$model]['trimWhitespace'][] = $name;
          }
        }
      }
      
      // Other than Extended Attributes, make sure we're working with a known model
      if(!isset($normalizations[$model])) {
        continue;
      }
      
      // Run the appropriate normalizations for each field within the model
      
      foreach(array_keys($normalizations[$model]) as $normalization) {
        foreach($normalizations[$model][$normalization] as $field) {
          if(!empty($ret[$model][$field])) {
            $ret[$model][$field] = $this->$normalization($ret[$model][$field], $field);
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Mix Case (generally: upper case the first letter of each word) the provided
   * string. State and Country fields are handled specially.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String $str   String to mix case for
   * @param  String $field Field name $str is for
   * @return String Normalized string
   */
  
  protected function mixCase($str, $field) {
    // If $field is state or country and the length is 3 or shorter, convert to
    // upper case, since we're almost certainly dealing with an abbreviation.
    // (As of this writing, there do not appear to be any countries or US states
    // with English names of less than 4 characters. This may not hold true for
    // non-US states.)
    
    if(($field == 'state' || $field == 'country')
       && strlen($str) <= 3) {
      if(function_exists('mb_strtoupper')) {
        // Use multi-byte functions when available (build php with --enable-mbstring)
        return mb_strtoupper($str);
      } else {
        return strtoupper($str);
      }
    }
    
    if(function_exists('mb_convert_case')) {
      return mb_convert_case($str, MB_CASE_TITLE);
    } else {
      return ucwords(strtolower($str));
    }
  }
  
  /**
   * Punctuation To Space.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $str   String to mix case for
   * @param  String $field Field name $str is for
   * @return String Normalized string
   */
  
  protected function punctuationToSpace($str, $field) {
    return preg_replace("/[^[:alnum:]]+/", " ", $str);
  }
  
  /**
   * Trim Whitespace.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $str   String to mix case for
   * @param  String $field Field name $str is for
   * @return String Normalized string
   */
  
  protected function trimWhiteSpace($str, $field) {
    return trim($str);
  }
}
