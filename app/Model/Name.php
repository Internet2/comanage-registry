<?php
/**
 * COmanage Registry Names Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Name extends AppModel {
  // Define class name for cake
  public $name = "Name";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A name is attached to a CO Person
    "CoPerson",
    // A name is attached to an Org Identity
    "OrgIdentity",
    // A name created from a Pipeline has a Source Identifier
    "SourceName" => array(
      'className' => 'Name',
      'foreignKey' => 'source_name_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "family";
  
/*
  // Default ordering for find operations
  public $order = array(
    "family",
    "given"
  );
*/  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'honorific' => array(
      'content' => array(
        'rule' => array('maxLength', 32),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'given' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A given name must be provided'
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'middle' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'family' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'suffix' => array(
      'content' => array(
        'rule' => array('maxLength', 32),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Name.type',
                              'default' => array(NameEnum::Alternate,
                                                 NameEnum::Author,
                                                 NameEnum::FKA,
                                                 NameEnum::Official,
                                                 NameEnum::Preferred))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'language' => array(
      'content' => array(
        'rule'       => array('validateLanguage'),
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'primary_name' => array(
      'content' => array(
        'rule'       => array('boolean'),
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_name_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  public function beforeSave($options = array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }
    
    // Make sure exactly one Primary Name is set
    
    // We don't do transaction management here because we can't guarantee a rollback
    // on error. (afterSave is not called if the save itself fails.) So it's up to
    // the controller (or other calling code) to begin/commit/rollback.
    // If we're handling a saveField() on any field other than primary_name,
    // skip the primary name check
    if(isset($this->data[$this->alias]['primary_name'])) {
      // Are we in an Enrollment Flow? If so, we'll do some things differently...
      $inEF = isset($this->data[$this->alias]['co_enrollment_attribute_id']);
      
      // In order to check if another Name might already be flagged primary,
      // For this, we need either an Org Identity ID or a CO Person ID.
      // However, if we were called via saveField these might not be in $this->data.
      // But we can't just call read() because that will clobber the data we're
      // supposed to save.
      
      $orgIdentityId = null;
      $coPersonId = null;
      
      // First check to see if an identity was provided in the data
      if(!empty($this->data[$this->alias]['org_identity_id'])) {
        $orgIdentityId = $this->data[$this->alias]['org_identity_id'];
      } elseif(!empty($this->data[$this->alias]['co_person_id'])) {
        $coPersonId = $this->data[$this->alias]['co_person_id'];
      } else {
        // No identity, so pull the record and see if we can find one. But we'll do
        // this by field so as not to disrupt $this->data
        
        $orgIdentityId = $this->field('org_identity_id');
        
        if(!$orgIdentityId) {
          // Try for a CO Person ID
          
          $coPersonId = $this->field('co_person_id');
          
          if(!$coPersonId) {
            throw new InvalidArgumentException(_txt('er.person.none'));
          }
        }
      }
      
      // At this point, we must have either an Org ID or a CO Person ID
      
      // If the current record to save is not a primary name, check to see if
      // there is already an existing primary name. If not, make this one primary
      // *unless* we're in an Enrollment Flow, in which case we trust what we're
      // given.
      if(!$inEF && !$this->data[$this->alias]['primary_name']) {
        $args = array();
        $args['conditions'][$this->alias.'.primary_name'] = true;
        if($orgIdentityId) {
          $args['conditions'][$this->alias.'.org_identity_id'] = $orgIdentityId;
        } elseif($coPersonId) {
          $args['conditions'][$this->alias.'.co_person_id'] = $coPersonId;
        }
        
        if($this->find('count', $args) == 0) {
          // No other names, this one must be primary
          $this->data[$this->alias]['primary_name'] = true;
        }
      }
      
      // Unset any existing primary name -- but only if this Name has primary name as true.
      
      if($this->data[$this->alias]['primary_name']) {
        // We can't use updateAll since no callbacks (ie: changelogbehavior) are fired.
        // So instead, pull all relevant names and set them to primary_name = false if
        // not already set.
        
        $args = array();
        if($orgIdentityId) {
          $args['conditions'][$this->alias.'.org_identity_id'] = $orgIdentityId;
        } elseif($coPersonId) {
          $args['conditions'][$this->alias.'.co_person_id'] = $coPersonId;
        }
        $args['conditions'][$this->alias.'.primary_name'] = true;
        $args['contain'] = false;
        
        // Generally there should only be one result...
        $names = $this->find('all', $args);
        
        if(!empty($names)) {
          // We'll need to restore stuff when we're done
          $curId = $this->id;
          $curData = $this->data;
          
          foreach($names as $n) {
            $this->id = $n[$this->alias]['id'];
            $this->saveField('primary_name', false);
          }
          
          $this->id = $curId;
          $this->data = $curData;
        }
      }
    }
    
    return true;
  }
  
  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function beforeValidate($options = array()) {
    // Update validation rules according to CO Settings, but only for records attached
    // to a CO Person
    
    if(!empty($this->data[$this->alias]['co_person_id'])) {
      // Map to the CO ID
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->data[$this->alias]['co_person_id'];
      $args['contain'] = false;
      
      $cop = $this->CoPerson->find('first', $args);
      
      if($cop) {
        $fields = $this->CoPerson->Co->CoSetting->getRequiredNameFields($cop['CoPerson']['co_id']);
        
        foreach($fields as $f) {
          // Make this field required
          $this->validator()->getField($f)->getRule('content')->required = true;
          $this->validator()->getField($f)->getRule('content')->allowEmpty = false;
          $this->validator()->getField($f)->getRule('content')->message = _txt('fd.required');
        }
      } else {
        // If for some reason we can't find the CO, fall back to the defaults
      }
    }
    
    return parent::beforeValidate($options);
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(Name.given) LIKE' => '%' . strtolower($t) . '%',
          'LOWER(Name.middle) LIKE' => '%' . strtolower($t) . '%',
          'LOWER(Name.family) LIKE' => '%' . strtolower($t) . '%',
        )
      );
    }
    
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['order'] = array('Name.family', 'Name.given', 'Name.middle');
    $args['contain']['CoPerson'] = 'CoPersonRole';
    
    return $this->find('all', $args);
  }
}