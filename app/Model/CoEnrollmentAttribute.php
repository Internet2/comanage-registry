<?php
/**
 * COmanage Registry CO Enrollment Attribute Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoEnrollmentAttribute extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("CoEnrollmentFlow");     // A CO Enrollment Attribute is part of a CO Enrollment Flow
  
  public $hasMany = array(
    // A CO Petition Attribute is defined by a CO Enrollment Attribute
    "CoPetitionAttribute" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "label";
  
  // Default ordering for find operations
  public $order = array("label");
  
  // Validation rules for table elements
  public $validate = array(
    'label' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A label must be provided'
    ),
    'attribute' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'An attribute must be provided'
    ),
    'required' => array(
      'rule' => array('range', -2, 2)
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'allowEmpty' => true
    ),
    'description' => array(
      'rule' => '/.*/',
      'required' => false
    )
  );
  
  /**
   * Determine the attributes available to be requested as part of an Enrollment Flow.
   *
   * @since  COmanage Registry v0.3
   * @param  integer Identifier of the CO to assemble attributes for
   * @return Array A hash of internal keys and human readable attribute names
   */
  
  public function availableAttributes($coid) {
    global $cm_lang, $cm_texts;
    
    $ret = array();
    
    // There are several types of attributes we need to assemble. We encode information
    // about them into their key to make it easier for the petition process to figure
    // out what's what. The general form is
    //  <code>:<name>:<type>
    // though there are variations described below. <type> is optional, and for
    // multi-valued person attributes to indicate the type of that attribute that is
    // being collected.
    
    // (1) Single valued CO Person Role attributes (code=r)
    
    $ret['r:cou_id'] = _txt('fd.cou') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:affiliation'] = _txt('fd.affiliation') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:title'] = _txt('fd.title') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:o'] = _txt('fd.o') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:ou'] = _txt('fd.ou') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:valid_from'] = _txt('fd.valid.f') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:valid_through'] = _txt('fd.valid.u') . " (" . _txt('ct.co_person_roles.1') . ")";
    
    // (2) Multi valued CO Person attributes (code=p)
    
    foreach(array_keys($cm_texts[ $cm_lang ]['en.name']) as $k)
      $ret['p:name:'.$k] = _txt('fd.name') . " (" . $cm_texts[ $cm_lang ]['en.name'][$k] . ", " . _txt('ct.co_people.1') . ")";
    
    // Identifier types can be extended, and so require a bit of work to assemble
    $coExtendedType = ClassRegistry::init('CoExtendedType');
    $identifierTypes = $coExtendedType->active($coid, 'Identifier', 'list');
    
    foreach(array_keys($identifierTypes) as $k)
      $ret['p:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $identifierTypes[$k] . ", " . _txt('ct.co_people.1') . ")";
    
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.mail']) as $k)
      $ret['p:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $cm_texts[ $cm_lang ]['en.contact.mail'][$k] . ", " . _txt('ct.co_people.1') . ")";
      
    // (3) Multi valued CO Person Role attributes (code=m)
    
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.phone']) as $k)
      $ret['m:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $cm_texts[ $cm_lang ]['en.contact.phone'][$k] . ", " . _txt('ct.co_person_roles.1') . ")";
    
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.address']) as $k)
      $ret['m:address:'.$k] = _txt('fd.address') . " (" . $cm_texts[ $cm_lang ]['en.contact.address'][$k] . ", " . _txt('ct.co_person_roles.1') . ")";
    
    // (4) CO Person Role Extended attributes (code=x)
    
    $extAttrs = $this->CoEnrollmentFlow->Co->CoExtendedAttribute->findAllByCoId($coid);
    
    foreach($extAttrs as $e)
      $ret['x:' . $e['CoExtendedAttribute']['name']] = $e['CoExtendedAttribute']['display_name'];
    
    $cmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($cmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
      // (5) Single valued Org Identity attributes, if enabled (code=o)
      
      $ret['o:affiliation'] = _txt('fd.affiliation') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['o:title'] = _txt('fd.title') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['o:o'] = _txt('fd.o') . " (" . _txt('ct.org_identities.1') . ")";
      $ret['o:ou'] = _txt('fd.ou') . " (" . _txt('ct.org_identities.1') . ")";
      
      // (6) Multi valued Org Identity attributes, if enabled (code=i)
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.name']) as $k)
        $ret['i:name:'.$k] = _txt('fd.name') . " (" . $cm_texts[ $cm_lang ]['en.name'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.identifier']) as $k)
        $ret['i:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $cm_texts[ $cm_lang ]['en.identifier'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.address']) as $k)
        $ret['i:address:'.$k] = _txt('fd.address') . " (" . $cm_texts[ $cm_lang ]['en.contact.address'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.mail']) as $k)
        $ret['i:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $cm_texts[ $cm_lang ]['en.contact.mail'][$k] . ", " . _txt('ct.org_identities.1') . ")";
        
      foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.phone']) as $k)
        $ret['i:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $cm_texts[ $cm_lang ]['en.contact.phone'][$k] . ", " . _txt('ct.org_identities.1') . ")";
    }
    
    return($ret);
  }
  
  /**
   * Obtain the configured attributes for a particular Enrollment Flow.
   *
   * @since  COmanage Registry 0.5
   * @param  integer CO Enrollment Flow ID
   * @return Array Configured attributes and metadata
   */
  
  public function enrollmentFlowAttributes($coef) {
    $attrs = array();
    
    // First, retrieve the configured attributes
    
    $efAttrs = $this->findAllByCoEnrollmentFlowId($coef,
                                                  array(),
                                                  array('CoEnrollmentAttribute.ordr' => 'asc'));
    
    foreach($efAttrs as $efAttr) {
      $attr = array();
      
      // Figure out what we're dealing with
      $a = explode(':', $efAttr['CoEnrollmentAttribute']['attribute'], 4);
      
      // See availableAttributes() for the various codes
      $attrCode = array_shift($a);
      
      // attribute name (as per availableAttributes)
      $attrName = array_shift($a);
      
      // optional constraining type, for multi-valued attributes
      $attrType = array_shift($a);
      
      if($attrCode == 'o' || $attrCode == 'r' || $attrCode == 'x') {
        $attrModel = null;
        
        switch($attrCode) {
        case 'o':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->OrgIdentity;
          break;
        case 'r':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson->CoPersonRole;
          break;
        case 'x':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->CoExtendedAttribute;
          break;
        }
        
        // The attribute ID
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        
        // The attribute key/shorthand
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        
        // Required? We're using required when perhaps we should be using allowEmpty.
        // An attribute is required if the enrollment flow requires it OR if it is
        // type 'o' or 'r' and is required by the data model.
        $attr['required'] = $efAttr['CoEnrollmentAttribute']['required'];
        
        if(($attrCode == 'o' || $attrCode == 'r')
           && $attrModel->validate[$attrName]['required'])
          $attr['required'] = true;
        
        // Label
        $attr['label'] = $efAttr['CoEnrollmentAttribute']['label'];
        
        // Description
        $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
        
        // Single value attributes are never hidden
        $attr['hidden'] = 0;
        
        // Model, in cake's Model.field.
        if($attrCode == 'o') {
          $attr['model'] = 'EnrolleeOrgIdentity';
        } elseif($attrCode == 'r') {
          $attr['model'] = 'EnrolleeCoPersonRole';
        } else {
          // Model is Co#PersonExtendedAttribute
          $attr['model'] = 'EnrolleeCoPersonRole.Co' . $efAttr['CoEnrollmentFlow']['co_id'] . 'PersonExtendedAttribute';
        }
        
        // Field, in cake's Model.field
        $attr['field'] = $attrName;
        
        // Attach the validation rules so the form knows how to render the field.
        if($attrCode == 'o') {
          $attr['validate'] = $attrModel->validate[$attrName];
          
          if(isset($attr['validate']['rule'][0])
             && $attr['validate']['rule'][0] == 'inList') {
            // If this is a select field, get the set of options
            $attr['select'] = $attrModel->validEnumsForSelect($attrName);
          }
        } elseif($attrCode == 'r') {
          if($attrName == 'cou_id') {
            // We have to set up a select based on the available COUs
            
            $args = array();
            $args['fields'] = array('Cou.id', 'Cou.name');
            $args['conditions'] = array('CoEnrollmentFlow.id' => $coef);
            $args['joins'][0]['table'] = 'co_enrollment_flows';
            $args['joins'][0]['alias'] = 'CoEnrollmentFlow';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'Cou.co_id=CoEnrollmentFlow.co_id';
            
            $attr['select'] = $this->CoEnrollmentFlow->CoPetition->Cou->find('list', $args);
            $attr['validate']['rule'][0] = 'inList';
            $attr['validate']['rule'][1] = array_keys($attr['select']);
          } else {
            // Default behavior for all other attributes
            
            $attr['validate'] = $attrModel->validate[$attrName];
            
            if(isset($attr['validate']['rule'][0])
               && $attr['validate']['rule'][0] == 'inList') {
              // If this is a select field, get the set of options
              $attr['select'] = $attrModel->validEnumsForSelect($attrName);
            }
          }
        } else {
          // Extended attributes
          
          $attr['validate'] = $attrModel->validationRules($efAttr['CoEnrollmentFlow']['co_id'], $attrName);
        }
        
        // Single valued attributes don't have types, so we can ignore $attrType
        
        $attrs[] = $attr;
      } elseif($attrCode == 'i' || $attrCode == 'm' || $attrCode == 'p') {
        // For multivalued attributes, we figure out the relevant fields to pass to the view.
        
        // Figure out the model name. $attrName is the lowercased version.
        $attrModelName = Inflector::camelize($attrName);
        $attrIsHasMany = false;
        
        $attrModel = null;
        
        switch($attrCode) {
        case 'i':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->OrgIdentity->$attrModelName;
          if(isset($this->CoEnrollmentFlow->CoPetition->Co->OrgIdentity->hasMany[$attrModelName]))
            $attrIsHasMany = true;
          break;
        case 'm':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson->CoPersonRole->$attrModelName;
          if(isset($this->CoEnrollmentFlow->CoPetition->Co->CoPerson->CoPersonRole->hasMany[$attrModelName]))
            $attrIsHasMany = true;
          break;
        case 'p':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson->$attrModelName;
          if(isset($this->CoEnrollmentFlow->CoPetition->Co->CoPerson->hasMany[$attrModelName]))
            $attrIsHasMany = true;
          break;
        }
        
        if($attrModel == null) {
          throw new RuntimeException("Failed to find attribute model: " . $attrModelName . " (" . $attrCode . ")");
        }
        
        // Model, in cake's Model.field. We prefix it with the parent model so
        // CoPetitionsController can figure out where to map multi-valued attributes.
        // For hasmany relations, we enumerate using the attribute ID in order to
        // permit multiples of an attribute (eg: Foo.23, Foo.29). This is easier than
        // trying to correlate in order to produce Foo.0, Foo.1 etc.
        
        switch($attrCode) {
        case 'i':
          $m = "EnrolleeOrgIdentity." . $attrModelName . ($attrIsHasMany ? "." . $efAttr['CoEnrollmentAttribute']['id'] : "");
          break;
        case 'm':
          $m = "EnrolleeCoPersonRole." . $attrModelName . ($attrIsHasMany ? "." . $efAttr['CoEnrollmentAttribute']['id'] : "");
          break;
        case 'p':
          $m = "EnrolleeCoPerson." . $attrModelName . ($attrIsHasMany ? "." . $efAttr['CoEnrollmentAttribute']['id'] : "");
          break;
        }
        
        // Inject a hidden attribute to link this attribute back to its definition.
        
        $attr = array();
        
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        $attr['hidden'] = true;
        $attr['default'] = $attr['id'];
        $attr['model'] = $m;
        $attr['field'] = "co_enrollment_attribute_id";
        
        $attrs[] = $attr;
        
        // Loop through the fields in the model.
        
        foreach(array_keys($attrModel->validate) as $k) {
          // Skip fields that are autopopulated
          if($k != 'co_person_id'
             && $k != 'co_person_role_id'
             && $k != 'org_identity_id') {
            $attr = array();
            
            // The attribute ID and attribute key will be the same for all components
            // of a multi-valued attribute
            $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
            $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
            
            // Required? We use allowEmpty to check, which is more accurate than $validate->required.
            // Required is true if the attribute is required by the enrollment flow configuration,
            // AND of the MVPA's element is also required/allowEmpty (eg: Email requires mail to be set).
            
            $attr['required'] = ($efAttr['CoEnrollmentAttribute']['required']
                                 &&
                                 isset($attrModel->validate[$k]['allowEmpty'])
                                 &&
                                 !$attrModel->validate[$k]['allowEmpty']);
            
            // We hide type and status
            $attr['hidden'] = ($k == 'type' || $k == 'status' ? 1 : 0);
            
            if($attr['hidden']) {
              // Populate a default value.
              
              switch($k) {
                case 'type':
                  // Just use $attrType
                  $attr['default'] = $attrType;
                  break;
                case 'status':
                  // For now, status is always set to Active
                  $attr['default'] = StatusEnum::Active;
                  break;
              }
              
            } else {
              // Label
              $attr['label'] = $efAttr['CoEnrollmentAttribute']['label'] . ": " . _txt('fd.' . $attrName . '.' . $k);
              
              // Description
              $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
            }
            
            // Model, in cake's Model.field
            $attr['model'] = $m;
            
            // Field, in cake's Model.field
            $attr['field'] = $k;
            
            // Attach the validation rules so the form knows how to render the field.
            $attr['validate'] = $attrModel->validate[$k];
            
            if($k != 'type'
               && isset($attr['validate']['rule'][0])
               && $attr['validate']['rule'][0] == 'inList') {
              // If this is a select field, get the set of options
              $attr['select'] = $attrModel->validEnumsForSelect($attrName);
            }
            
            $attrs[] = $attr;
          }
        }
      } else {
        throw new RuntimeException("Unknown attribute code: " . $attrCode);
      }
    }
    
    return $attrs;
  }
}
