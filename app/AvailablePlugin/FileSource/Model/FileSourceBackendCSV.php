<?php
/**
 * COmanage Registry File OrgIdentitySource CSV Backend Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("FileSourceBackendImpl", "FileSource.Model");

class FileSourceBackendCSV extends FileSourceBackendImpl {
  // We default to CSV v1 format, but this can be overridden by other parsers.
  protected $fieldCfg = null;
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    $this->readFieldConfig();
    
    $attrs = array();
    
    // We look for various groupable attributes that might be available
    
    foreach(array('affiliation', 'title', 'o', 'ou') as $a) {
      if(!empty($this->fieldCfg['OrgIdentity'][$a])) {
        $attrs[ $this->fieldCfg['OrgIdentity'][$a] ] = _txt('fd.'.$a);
      }
    }
    
    if(!empty($this->fieldCfg['AdHocAttribute'])) {
      foreach($this->fieldCfg['AdHocAttribute'] as $tag => $col) {
        $attrs[$col] = $tag;
      }
    }
    
    asort($attrs);
    
    return $attrs;
  }
  
  /**
   * Obtain the file field configuration.
   *
   * @since  COmanage Registry v4.0.0
   * @return array Configuration array
   */
  
  protected function readFieldConfig() {
    if($this->fieldCfg) {
      return $this->fieldCfg;
    }
    
    if($this->pluginCfg['format'] == FileSourceFormat::CSV2) {
      $this->fieldCfg = array();
      
      // The field config is described in the first line of the file
      $handle = fopen($this->pluginCfg['filepath'], "r");

      if(!$handle) {
        throw new RuntimeException(_txt('er.filesource.read', array($this->pluginCfg['filepath'])));
      }
      
      // The first line is our configuration
      $cfg = fgetcsv($handle);
      
      fclose($handle);
      
      if(empty($cfg)) {
        throw new RuntimeException(_txt('er.filesource.header'));
      }
      
      foreach($cfg as $i => $label) {
        // Labels are of the form
        //  SORID (special case)
        //  Model.field
        //  Model.field.type
        //  Identifier.identifier.type+login (special case)
        // Parse them out into the fieldcfg array
        
        $bits = explode('.', $label, 3);
        
        switch(count($bits)) {
          case 1:
            $this->fieldCfg[ $bits[0] ] = $i;
            break;
          case 2:
            $this->fieldCfg[ $bits[0] ][ $bits[1] ] = $i;
            break;
          case 3:
            // Note we flip the order and store model/type/field
            $this->fieldCfg[ $bits[0] ][ $bits[2] ][ $bits[1] ] = $i;
            break;
        }
      }
    } else {
      // Legacy v1 format
      
      $this->fieldCfg = array(
        'SORID' => 0,
        'OrgIdentity' => array(
          'title' => 16,
          'o' => 17,
          'valid_from' => 19,
          'valid_through' => 20,
          'affiliation' => 22,
          'date_of_birth' => 24
        ),
        'Name' => array(
          NameEnum::Official => array(
            'given' => 3,
            'family' => 5
          )
        ),
        'Address' => array(
          ContactEnum::Home => array(
            'street' => 6,
            'locality' => 7,
            'state' => 9,
            'postal_code' => 10,
            'country' => 11
          )
        ),
        'EmailAddress' => array(
          EmailAddressEnum::Official => array(
            'mail' => 12
          )
        ),
        'TelephoneNumber' => array(
          ContactEnum::Office => array(
            'number' => 13,
            'country_code' => 14
          )
        ),
        'Identifier' => array(
          IdentifierEnum::National => array(
            'identifier' => 15
          ),
          IdentifierEnum::Reference => array(
            'identifier' => 18
          )
        ),
        'Url' => array(
          UrlEnum::Personal => array(
            'url' => 21
          )
        ),
        'Legacy' => array(
          'Identifier' => 23,
          'AdHocAttribute' => 25
        )
      );
    }
    
    return $this->fieldCfg;
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v4.0.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    $this->readFieldConfig();
    
    $ret = array();
    
    // Decode the json arrawy
    $attrs = json_decode($raw, true);
    
    // Get the set of groupable attributes
    $grAttrs = $this->groupableAttributes();
    
    foreach($grAttrs as $col => $label) {
      if(!empty($attrs[$col])) {
        // Find the raw value for the specified column
        $ret[$col][] = array('value' => $attrs[$col]);
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  public function resultToOrgIdentity($result) {
    $this->readFieldConfig();
    
    $orgdata = array();
    $orgdata['OrgIdentity'] = array();
    
    // Note if we have any bad values for fixed vocabulary fields (like
    // affiliation) the save will fail
    
    if(!empty($this->fieldCfg['OrgIdentity'])) {
      foreach($this->fieldCfg['OrgIdentity'] as $attr => $col) {
        if(!empty($result[$col])) {
          if($attr == 'valid_from' || $attr == 'valid_through') {
            // Convert the value to database format
            $orgdata['OrgIdentity'][$attr] = strftime("%F %T", strtotime($result[$col]));
          } else {
            // Just copy the value
            $orgdata['OrgIdentity'][$attr] = $result[$col];
          }
        }
      }
    }
    
    if(empty($orgdata['OrgIdentity']['affiliation'])) {
      // Until we have some rules, everyone is a member
      $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    }
    
    // Walk through MVPAs by type
    
    foreach(array('Name', 'Address', 'EmailAddress', 'Identifier', 'TelephoneNumber', 'Url') as $model) {
      $orgdata[$model] = array();
      
      if(!empty($this->fieldCfg[$model])) {
        foreach(array_keys($this->fieldCfg[$model]) as $type) {
          $n = array();
          
          foreach($this->fieldCfg[$model][$type] as $attr => $col) {
            if(!empty($result[$col])) {
              $n[$attr] = $result[$col];
            }
          }
          
          if(!empty($n)) {
            // Insert the type then add this record to the set to save
            $n['type'] = $type;
            
            if($model == 'EmailAddress' && !isset($n['verified'])) {
              // By default we consider Email Addresses verified
              $n['verified'] = true;
            }
            
            if($model == 'Identifier') {
              if(!isset($n['status'])) {
                // By default set the Identifier to active
                $n['status'] = SuspendableStatusEnum::Active;
              }
              
              if(strlen($n['type']) > 6) {
                $p = strpos($n['type'], "+login", -6);
                
                if($p) {
                  $n['login'] = true;
                  $n['type'] = substr($n['type'], 0, $p);
                }
              }
            }
            
            $orgdata[$model][] = $n;
          }
        }
      }
    }
    
    // Make sure we have a Primary Name
    $primaryNameSet = false;
    
    foreach($orgdata['Name'] as $n) {
      if(isset($n['primary_name']) && $n['primary_name']) {
        $primaryNameSet = true;
        break;
      }
    }
    
    if(!$primaryNameSet) {
      $orgdata['Name'][0]['primary_name'] = true;
    }
    
    // Process AdHoc Attributes
    
    if(!empty($this->fieldCfg['AdHocAttribute'])) {
      foreach($this->fieldCfg['AdHocAttribute'] as $tag => $col) {
        $orgdata['AdHocAttribute'][] = array(
          'tag' => $tag,
          'value' => $result[$col]
        );
      }
    }
    
    // Handle legacy v1 fields
    
    if(!empty($this->fieldCfg['Legacy']['AdHocAttribute'])
       && !empty($result[ $this->fieldCfg['Legacy']['AdHocAttribute'] ])) {
      $ahas = json_decode($result[ $this->fieldCfg['Legacy']['AdHocAttribute'] ], true);
      
      $orgdata['AdHocAttribute'] = array();
      
      foreach($ahas as $tag => $col) {
        $orgdata['AdHocAttribute'][] = array(
          'tag' => $tag,
          'value' => $result[$col]
        );
      }
    }
    
    if(!empty($this->fieldCfg['Legacy']['Identifier'])
       && !empty($result[ $this->fieldCfg['Legacy']['Identifier'] ])) {
      // This field allows a semi-colon separated lists of identifiers of the form
      // identifiertype[+login]:identifier
      // where identifiertype is a non-extended type, since Org Identities don't
      // currently support extended types (CO-530).
      
      $ids = explode(';', $result[ $this->fieldCfg['Legacy']['Identifier'] ]);
      
      foreach($ids as $id) {
        $i = explode(':', $id, 2);
        
        $login = false;
        $idtype = $i[0];
        
        if(strlen($i[0]) > 6) {
          $p = strpos($i[0], "+login", -6);
          
          if($p) {
            $login = true;
            $idtype = substr($i[0], 0, $p);
          }
        }
        
        $orgdata['Identifier'][] = array(
          'identifier' => $i[1],
          'login'      => $login,
          'status'     => StatusEnum::Active,
          'type'       => $idtype
        );
      }
    }
    
    return $orgdata;
  }
  
  /**
   * Search a CSV file.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes()), or null to obtain a list of all SORIDs
   * @return Array Search results
   * @throws RuntimeException
   */
  
  public function searchFile($attributes=null) {
    $this->readFieldConfig();
    
    $ret = array();
    
    // The field config is described in the first line of the file
    $handle = fopen($this->pluginCfg['filepath'], "r");

    if(!$handle) {
      throw new RuntimeException(_txt('er.filesource.read', array($this->pluginCfg['filepath'])));
    }
    
    if($this->pluginCfg['format'] == FileSourceFormat::CSV2) {
      // The first line is our configuration (which was already parsed, so just skip it)
      fgetcsv($handle);
    }
    
    while(($data = fgetcsv($handle)) !== false) {
      // For each row, see if any provided search key matches a specified field. In our current
      // test format, we check
      //  givenname = [3], familyname = [5], email = [12]
      
      if(!$attributes) {
        // Just store the SORID (row key)
        $ret[] = $data[0];
      } else {
        // All fields provided must match (AND, not OR)
        $specified = 0;
        $matched = 0;
        
        if(!empty($attributes['SORID'])) {
          $specified++;
          
          if($data[0] == $attributes['SORID']) {
            $matched++;
          }
        }
        
        if(!empty($attributes['Given'])) {
          $specified++;
          
          foreach($this->fieldCfg['Name'] as $type => $fields) {
            if(!empty($fields['given']) && (strtolower($data[ $fields['given'] ]) == strtolower($attributes['Given']))) {
              $matched++;
              break;
            }
          }
        }
        
        if(!empty($attributes['Family'])) {
          $specified++;
          
          foreach($this->fieldCfg['Name'] as $type => $fields) {
            if(!empty($fields['family']) && (strtolower($data[ $fields['family'] ]) == strtolower($attributes['Family']))) {
              $matched++;
              break;
            }
          }
        }
        
        if(!empty($attributes['mail'])) {
          $specified++;
          
          foreach($this->fieldCfg['EmailAddress'] as $type => $fields) {
            if(!empty($fields['mail']) && (strtolower($data[ $fields['mail'] ]) == strtolower($attributes['mail']))) {
              $matched++;
              break;
            }
          }
        }
        
        if($matched > 0 && $matched == $specified) {
          $ret[] = $data;
        }
      }
    }
    
    fclose($handle);
    
    return $ret;
  }

  /**
   * Set the plugin configuration for this backend.
   *
   * @since  COmanage Registry v4.0.2
   * @param  Array $cfg Array of configuration information, as returned by find()
   */

  public function setConfig($pluginCfg) {
    parent::setConfig($pluginCfg);
    
    // We also need to reset the field config
    $this->fieldCfg = null;
  }
}