<?php
/**
 * COmanage Registry CO Group OIS Mapping Model
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
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CakeTime', 'Utility');
  
class CoGroupOisMapping extends AppModel {
  // Define class name for cake
  public $name = "CoGroupOisMapping";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoGroup",
    "OrgIdentitySource"
  );
  
  // Default display field for cake generated views
  public $displayField = "pattern";
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'attribute' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'comparison' => array(
      'rule' => array('inList', array(ComparisonEnum::Contains,
                                      ComparisonEnum::ContainsInsensitive,
                                      ComparisonEnum::Equals,
                                      ComparisonEnum::EqualsInsensitive,
                                      ComparisonEnum::NotContains,
                                      ComparisonEnum::NotContainsInsensitive,
                                      ComparisonEnum::NotEquals,
                                      ComparisonEnum::NotEqualsInsensitive,
                                      ComparisonEnum::Regex)),
      'required' => true,
      'allowEmpty' => false
    ),
    'pattern' => array(
      // XXX We would ideally check for a valid regular expression when ComparisonEnum is Regex
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false,
        'unfreeze' => 'CO'
      )
    )
  );
  
  /**
   * Compare a value against a pattern based on a ComparisonEnum.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $value Value to compare
   * @param  ComparisonEnum $comparison Type of comparison to perform
   * @param  String $pattern Pattern to compare $value against
   * @return Boolean True if $value matches $pattern, false otherwise
   * @todo   This probably belongs in Lib/utils.php
   */
  
  protected function compare($value, $comparison, $pattern) {
    switch($comparison) {
      case ComparisonEnum::Contains:
        return (strpos($value, $pattern) !== false);
        break;
      case ComparisonEnum::ContainsInsensitive:
        return (stripos($value, $pattern) !== false);
        break;
      case ComparisonEnum::Equals:
        return (strcmp($value, $pattern) === 0);
        break;
      case ComparisonEnum::EqualsInsensitive:
        return (strcasecmp($value, $pattern) === 0);
        break;
      case ComparisonEnum::NotContains:
        return (strpos($value, $pattern) === false);
        break;
      case ComparisonEnum::NotContainsInsensitive:
        return (stripos($value, $pattern) === false);
        break;
      case ComparisonEnum::NotEquals:
        return (strcmp($value, $pattern) !== 0);
        break;
      case ComparisonEnum::NotEqualsInsensitive:
        return (strcasecmp($value, $pattern) !== 0);
        break;
      case ComparisonEnum::Regex:
        return (preg_match($pattern, $value ?? ''));
        break;
      default:
        // Ignore anything unexpected
        break;
    }
    
    return false;
  }
  
  /**
   * Map attributes received from an OIS backend to group memberships, as configured.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $orgIdentitySourceId OrgIdentitySource ID
   * @param  Array $attributes Hash of attributes, of the form attribute => array(value, ...)
   * @return Array List of CO Group IDs corresponding to memberships
   */
  
  public function mapGroups($orgIdentitySourceId, $attributes) {
    $ret = array();
    
    // Start by pulling the relevant mappings
    $args = array();
    $args['conditions']['CoGroupOisMapping.org_identity_source_id'] = $orgIdentitySourceId;
    $args['contain'] = false;
    
    $mappings = $this->find('all', $args);
    
    // Walk through the mappings (if found) and process the rules
    foreach($mappings as $m) {
      $attr = $m['CoGroupOisMapping']['attribute'];
      
      if(!empty($attributes[$attr])) {
        foreach($attributes[$attr] as $v) {
          if($this->compare($v['value'],
                            $m['CoGroupOisMapping']['comparison'],
                            $m['CoGroupOisMapping']['pattern'])) {
            // Match found
            
            $r = array(
              'role' => 'member',
              'valid_from' => (isset($v['valid_from']) ? $v['valid_from'] : null),
              'valid_through' => (isset($v['valid_through']) ? $v['valid_through'] : null),
            );
            
            if(!empty($ret[ $m['CoGroupOisMapping']['co_group_id'] ])) {
              // It's possible that there could be more than one mapping with
              // different validity dates (eg: representing memberships in the
              // same committee but for different periods/years), but that's not
              // really supported in the CoGroupMember model. (Technically we
              // could store multiple rows in co_group_members, but nothing
              // really supports that right now.)
              
              // Our order of preference is
              // (1) The "active" membership with the latest valid_through date.
              // (2) The "future" membership with the earlest valid_from date.
              // (3) The "expired" membership with the latest valid_through date.
              // This calculation is a little verbose in order to improve readability.
              
              $f0 = $ret[ $m['CoGroupOisMapping']['co_group_id'] ]['valid_from']
                    ? CakeTime::toUnix($ret[ $m['CoGroupOisMapping']['co_group_id'] ]['valid_from']) : null;
              $t0 = $ret[ $m['CoGroupOisMapping']['co_group_id'] ]['valid_through']
                    ? CakeTime::toUnix($ret[ $m['CoGroupOisMapping']['co_group_id'] ]['valid_through']) : null;
              $f1 = $r['valid_from'] ? CakeTime::toUnix($r['valid_from']) : null;
              $t1 = $r['valid_through'] ? CakeTime::toUnix($r['valid_through']) : null;
              $now = time();
              
              $status0 = ((!$f0 || ($f0 < $now)) && (!$t0 || ($t0 >= $now))) ? "active" :
                          ((($f0 && ($f0 > $now)) && (!$t0 || ($t0 >= $now))) ? "future" : "expired");
              $status1 = ((!$f1 || ($f1 < $now)) && (!$t1 || ($t1 >= $now))) ? "active" :
                          ((($f1 && ($f1 > $now)) && (!$t1 || ($t1 >= $now))) ? "future" : "expired");
              
              if($status1 == "active") {
                // Pick the new record if the current one is not active
                // OR the new one has a later valid_through time
                if($status0 != "active"
                   || $t1 > $t0) {
                  // Use the new record
                  $ret[ $m['CoGroupOisMapping']['co_group_id'] ] = $r;
                }
              } elseif($status0 == "active") {
                // The current record is active and the new one is not, keep the current one
              } elseif($status1 == "future") {
                // Pick the new record is the current one is expired
                // OR the new one has an earlier valid_from date
                if($status0 == "expired"
                   || $f1 < $f0) {
                  // Use the new record
                  $ret[ $m['CoGroupOisMapping']['co_group_id'] ] = $r;
                }
              } else {
                // The new record is expired, pick it if the current record
                // is also expired AND the new one has a later valid_through date
                if($status0 == "expired"
                   && $t1 > $t0) {
                  // Use the new record
                  $ret[ $m['CoGroupOisMapping']['co_group_id'] ] = $r;
                }
              }
            } else {
              $ret[ $m['CoGroupOisMapping']['co_group_id'] ] = $r;
            }
          }
        }
      }
    }
    
    return $ret;
  }
}
