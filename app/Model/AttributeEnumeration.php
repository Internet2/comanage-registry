<?php
/**
 * COmanage Registry Attribute Enumeration Model
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
  
class AttributeEnumeration extends AppModel {
  // Define class name for cake
  public $name = "AttributeEnumeration";
  
  // Current schema version for API
  public $version = "1.1";
  
  // Association rules from this model to other models
  public $belongsTo = array('Co', 'Dictionary');
  
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'attribute' => array(
      // This should really track the list from supportedAttrs()
      'content' => array(
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'dictionary_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'allow_other' => array(
      'content' => array(
        'rule' => array('boolean'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                        SuspendableStatusEnum::Suspended)),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   * @throws InvalidArgumentException
   */
  
  public function beforeSave($options = array()) {
    // Make sure there isn't already an entry for co_id+attribute. We only need
    // to do this on add
    
    if(empty($this->data['AttributeEnumeration']['id'])
       && !empty($this->data['AttributeEnumeration']['co_id'])
       && !empty($this->data['AttributeEnumeration']['attribute'])) {
      $args = array();
      $args['conditions']['AttributeEnumeration.co_id'] = $this->data['AttributeEnumeration']['co_id'];
      $args['conditions']['AttributeEnumeration.attribute'] = $this->data['AttributeEnumeration']['attribute'];
      
      if($this->find('count', $args) > 0) {
        throw new InvalidArgumentException(_txt('er.ae.defined', array($this->data['AttributeEnumeration']['attribute'])));
      }
    }
    
    return true;
  }
  
  /**
   * Determine the available enumeration values for a given attribute.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId      CO ID
   * @param  string  $attribute Attribute, of the form Model.attribute
   * @return array              Array of AttributeEnumeration configuration
   */
  
  public function enumerations($coId, $attribute) {
    $args = array();
    $args['conditions']['AttributeEnumeration.co_id'] = $coId;
    $args['conditions']['AttributeEnumeration.attribute'] = $attribute;
    $args['conditions']['AttributeEnumeration.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $cfg = $this->find('first', $args);
    
    if(empty($cfg) || empty($cfg['AttributeEnumeration']['dictionary_id'])) {
      return false;
    }
    
    $ret = $this->Dictionary->entries($cfg['AttributeEnumeration']['dictionary_id']);
    $ret['allow_other'] = $cfg['AttributeEnumeration']['allow_other'];
    
    return $ret;
  }
  
  /**
   * Determine if a given value is valid for the specified attribute.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId      CO ID
   * @param  string  $attribute Attribute, of the form Model.attribute
   * @param  string  $value     Value to test
   * @return boolean            True if $value is valid for $attribute
   * @throws InvalidArgumentException
   */

  public function isValid($coId, $attribute, $value) {
    // First, see if there is an enumeration defined for $coId + $attribute.
    
    $args = array();
    $args['conditions']['AttributeEnumeration.co_id'] = $coId;
    $args['conditions']['AttributeEnumeration.attribute'] = $attribute;
    $args['conditions']['AttributeEnumeration.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $attrEnum = $this->find('first', $args);
    
    // If there is no configuration, or there is no dictionary attached to the
    // configuration, or the configuration allows other values then $value is
    // always accepted.
    
    if(empty($attrEnum)
       || empty($attrEnum['AttributeEnumeration']['dictionary_id'])
       || (isset($attrEnum['AttributeEnumeration']['allow_other']) 
           && $attrEnum['AttributeEnumeration']['allow_other'])) {
      return true;
    }
    
    if($this->Dictionary->isValidEntry($attrEnum['AttributeEnumeration']['dictionary_id'], $value)) {
      return true;
    }
    
    throw new InvalidArgumentException(_txt('er.ae.value', array($attribute)));
  }
  
  /**
   * Assemble a list of supported attributes, suitable for use in generating a select button.
   *
   * @since  COmanage Registry 2.0.0
   * @param  Boolean $pooled Whether or not OrgIdentities are pooled
   * @param  Boolean $orgOnly Whether or not to only return OrgIdentity attributes
   * @return Array Hash of supported attributes, with model name as key and localized text as value
   */
  
  public function supportedAttrs($pooled = false, $orgOnly = false) {
    $ret = array();
    
    // Also need to add to $validate, above
    if(!$orgOnly) {
      $ret['CoPersonRole.title'] = _txt('fd.title') . " (" . _txt('ct.co_person_roles.1') . ")";
      $ret['CoPersonRole.o'] = _txt('fd.o') . " (" . _txt('ct.co_person_roles.1') . ")";
      $ret['CoPersonRole.ou'] = _txt('fd.ou') . " (" . _txt('ct.co_person_roles.1') . ")";
      
      // Create an attribute for each Identity Document Type for each supported field
      $reflectionClass = new ReflectionClass('IdentityDocumentEnum');
      
      foreach($reflectionClass->getConstants() as $label => $key) {
        $ret['IdentityDocument.issuing_authority.'.$key] = _txt('fd.identity_documents.issuing_authority') . " (" . _txt('ct.identity_documents.1') . "," . $label . ")";
      }
    }
    
    if(!$pooled || $orgOnly) {
      // Either org identities are not pooled (in which case we return OrgIdentity
      // attributes with the CO Person attributes) or we have been asked only for
      // org identity attributes (for CMP level configuration)
      $ret['OrgIdentity.title'] = _txt('fd.title') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['OrgIdentity.o'] = _txt('fd.o') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['OrgIdentity.ou'] = _txt('fd.ou') . " (" . _txt('ct.org_identities.1') . ")";
    }
    
    asort($ret);

    return $ret;
  }
  
  /**
   * Perform AttributeEnumeration model upgrade steps for version 4.0.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function _ug400() {
    // Pull the current Attribute Enumerations so they can be converted to use
    // Dictionaries. We'll create one Dictionary for each co_id+attribute
    // combination (but only "Active" records).
    
    $args = array();
    $args['conditions']['AttributeEnumeration.status'] = SuspendableStatusEnum::Active;
    // In general, dictionary_id should always be null, but on testing instances
    // we may have a dictionary already set.
    $args['conditions']['AttributeEnumeration.dictionary_id'] = null;
    $args['contain'] = false;
    
    $attrEnums = $this->find('all', $args); 
    
    // Walk the results (which could be completely empty) and construct a hash
    // for subsequent output purposes.
    
    $todo = array();
    
    if(!empty($attrEnums)) {
      foreach($attrEnums as $ae) {
        // Key on co_id then attribute
        $todo[ $ae['AttributeEnumeration']['co_id'] ][ $ae['AttributeEnumeration']['attribute'] ][] = $ae['AttributeEnumeration']['optvalue'];
      }
    }
    
    if(!empty($todo)) {
      foreach(array_keys($todo) as $coId) {
        foreach(array_keys($todo[$coId]) as $attr) {
          // Create a Dictionary and populate it
          
          $dict = array(
            'co_id'       => $coId,
            'description' => $attr . " Dictionary"
          );
          
          $this->Dictionary->clear();
          $this->Dictionary->save($dict);
          
          $dictId = $this->Dictionary->id;
          $values = array();
          
          if(!empty($todo[$coId][$attr])) {
            foreach($todo[$coId][$attr] as $value) {
              $values[] = array(
                'dictionary_id' => $dictId,
                'value'         => $value
              );
            }
            
            $this->Dictionary->DictionaryEntry->clear();
            $this->Dictionary->DictionaryEntry->saveMany($values);
          }
          
          // Drop any old style Attribute Enumerations
          
          $this->deleteAll(
            array(
              'AttributeEnumeration.co_id' => $coId,
              'AttributeEnumeration.attribute' => $attr
            ),
            false,
            // We run callbacks so we keep the rows via changelog...
            // this will generate extra SQL queries, but we won't do this often
            true
          );
          
          // Insert a new entry attached to this dictionary
          
          $newEnum = array(
            'co_id'         => $coId,
            'attribute'     => $attr,
            'status'        => SuspendableStatusEnum::Active,
            'dictionary_id' => $dictId,
            'allow_other'   => false
          );
          
          $this->clear();
          $this->save($newEnum);
        }
      }
    }
  }  
}
