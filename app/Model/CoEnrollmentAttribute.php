<?php
/**
 * COmanage Registry CO Enrollment Attribute Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoEnrollmentAttribute extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("CoEnrollmentFlow");     // A CO Enrollment Attribute is part of a CO Enrollment Flow
  
  public $hasMany = array(
    "CoEnrollmentAttributeDefault" => array('dependent' => true),
    // A CO Petition Attribute is defined by a CO Enrollment Attribute
    "CoPetitionAttribute" => array('dependent' => true)
  );
  
  // Associated models that should be relinked to the archived attribute during Changelog archiving
  public $relinkToArchive = array('CoPetitionAttribute');
  
  // Default display field for cake generated views
  public $displayField = "label";
  
  // Default ordering for find operations
//  public $order = array("label");
  
  // Validation rules for table elements
  public $validate = array(
    'label' => array(
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A label must be provided'
    ),
    'attribute' => array(
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'An attribute must be provided'
    ),
    'required' => array(
      'rule' => array('range', -2, 2)
    ),
    'required_fields' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'description' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'copy_to_coperson' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'ignore_authoritative' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'default_env' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'language' => array(
      'rule'       => array('validateLanguage'),
      'required'   => false,
      'allowEmpty' => true
    ),
    'login' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_enrollment_attribute_id' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
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
    
    // COU ID is only available if at least one COU is defined
    $cous = $this->CoEnrollmentFlow->Co->Cou->allCous($coid);
    if(!empty($cous)) {
      $ret['r:cou_id'] = _txt('fd.cou') . " (" . _txt('ct.co_person_roles.1') . ")";
    }
    $ret['r:affiliation'] = _txt('fd.affiliation') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:sponsor_co_person_id'] = _txt('fd.sponsor') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:title'] = _txt('fd.title') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:o'] = _txt('fd.o') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:ou'] = _txt('fd.ou') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:valid_from'] = _txt('fd.valid_from') . " (" . _txt('ct.co_person_roles.1') . ")";
    $ret['r:valid_through'] = _txt('fd.valid_through') . " (" . _txt('ct.co_person_roles.1') . ")";
    
    // (2) Multi valued CO Person attributes (code=p)
    
    // Several types can be extended, and so require a bit of work to assemble
    $Name = ClassRegistry::init('Name');
    $nameTypes = $Name->types($coid, 'type');
    
    foreach(array_keys($nameTypes) as $k)
      $ret['p:name:'.$k] = _txt('fd.name') . " (" . $nameTypes[$k] . ", " . _txt('ct.co_people.1') . ")";
    
    $Identifier = ClassRegistry::init('Identifier');
    $identifierTypes = $Identifier->types($coid, 'type');
    
    foreach(array_keys($identifierTypes) as $k)
      $ret['p:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $identifierTypes[$k] . ", " . _txt('ct.co_people.1') . ")";
    
    $EmailAddress = ClassRegistry::init('EmailAddress');
    $emailAddressTypes = $EmailAddress->types($coid, 'type');
    
    foreach(array_keys($emailAddressTypes) as $k)
      $ret['p:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $emailAddressTypes[$k] . ", " . _txt('ct.co_people.1') . ")";
    
    $Url = ClassRegistry::init('Url');
    $urlTypes = $Url->types($coid, 'type');
    
    foreach(array_keys($urlTypes) as $k)
      $ret['p:url:'.$k] = _txt('fd.url.url') . " (" . $urlTypes[$k] . ", " . _txt('ct.co_people.1') . ")";
    
    // (2a) Group Memberships are Multi valued CO Person attributes, but have all sorts
    // of special logic around them so they get their own code (code=g)
    
    $ret['g:co_group_member'] = _txt('fd.group.grmem') . " (" . _txt('ct.co_people.1') . ")";
    $ret['g:co_group_member_owner'] = _txt('fd.group.grmemown') . " (" . _txt('ct.co_people.1') . ")";
    
    // (3) Multi valued CO Person Role attributes (code=m)
    
    $TelephoneNumber = ClassRegistry::init('TelephoneNumber');
    $telephoneNumberTypes = $TelephoneNumber->types($coid, 'type');
    
    foreach(array_keys($telephoneNumberTypes) as $k)
      $ret['m:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $telephoneNumberTypes[$k] . ", " . _txt('ct.co_person_roles.1') . ")";
    
    $Address = ClassRegistry::init('Address');
    $addressTypes = $Address->types($coid, 'type');
    
    foreach(array_keys($addressTypes) as $k)
      $ret['m:address:'.$k] = _txt('fd.address') . " (" . $addressTypes[$k] . ", " . _txt('ct.co_person_roles.1') . ")";
    
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
      // Note that since org identities don't support extended types, we use default values here.
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.name.type']) as $k)
        $ret['i:name:'.$k] = _txt('fd.name') . " (" . $cm_texts[ $cm_lang ]['en.name.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.identifier.type']) as $k)
        $ret['i:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $cm_texts[ $cm_lang ]['en.identifier.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.address.type']) as $k)
        $ret['i:address:'.$k] = _txt('fd.address') . " (" . $cm_texts[ $cm_lang ]['en.address.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
      
      foreach(array_keys($cm_texts[ $cm_lang ]['en.email_address.type']) as $k)
        $ret['i:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $cm_texts[ $cm_lang ]['en.email_address.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
        
      foreach(array_keys($cm_texts[ $cm_lang ]['en.telephone_number.type']) as $k)
        $ret['i:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $cm_texts[ $cm_lang ]['en.telephone_number.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
        
      foreach(array_keys($cm_texts[ $cm_lang ]['en.url.type']) as $k)
        $ret['i:url:'.$k] = _txt('fd.url.url') . " (" . $cm_texts[ $cm_lang ]['en.url.type'][$k] . ", " . _txt('ct.org_identities.1') . ")";
    }
    
    // (7) Enrollment Flow specific attributes -- these don't get copied out of the petition (code=e)
    $ret['e:textfield'] = _txt('fd.pt.textfield');
    
    return($ret);
  }
  
  /**
   * Obtain the configured attributes for a particular Enrollment Flow.
   *
   * @since  COmanage Registry 0.5
   * @param  integer CO Enrollment Flow ID
   * @param  Array Default values, keyed on Model name
   * @param  Boolean Whether to include archived (historical) attributes as well
   * @return Array Configured attributes and metadata
   */
  
  public function enrollmentFlowAttributes($coef, $defaultValues=array(), $archived=false) {
    $attrs = array();
    $permNameFields = array();
    
    // CO Petitions get relinked to archived CO Enrollment Flows (ie: if the definition of
    // the flow changes after the petition is created), but Enrollment Attributes stay
    // with the current flow definition. So we need to check if there is a parent id.
    $actualEfId = $this->CoEnrollmentFlow->field('co_enrollment_flow_id', array('CoEnrollmentFlow.id' => $coef));
    
    if(!$actualEfId) {
      $actualEfId = $coef;
    }
    
    $coId = $this->CoEnrollmentFlow->field('co_id', array("CoEnrollmentFlow.id" => $coef));
    
    // First, retrieve the configured attributes
    
    $args = array();
    $args['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $actualEfId;
    $args['conditions']['CoEnrollmentAttribute.required !='] = RequiredEnum::NotPermitted;
    $args['order']['CoEnrollmentAttribute.ordr'] = 'asc';
    $args['contain'][] = 'CoEnrollmentAttributeDefault';
    $args['contain'][] = 'CoEnrollmentFlow';
    if($archived) {
      $args['changelog']['archived'] = true;
    }
    
    $efAttrs = $this->find('all', $args);
    
    // We may need some global settings
    if(!empty($efAttrs[0]['CoEnrollmentFlow']['co_id'])) {
      // Pull the CO ID from the first EF attribute. If we don't have any attributes
      // there won't be much for us to do, anyway.
      $permNameFields = $this->CoEnrollmentFlow->Co->CoSetting->getPermittedNameFields($efAttrs[0]['CoEnrollmentFlow']['co_id']);
    }
    
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
        
        // Make sure the model has updated its validation rules to account for
        // (eg) attribute enumerations.
        $attrModel->updateValidationRules($coId);
        
        // XXX We could rewrite a bunch of stuff to reference this link to the parent
        // model rather than manually copy fields like 'id' and others that won't
        // vary even when a single logical attribute is expanded into multiple physical ones.
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
        
        // The attribute ID
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        
        // The attribute key/shorthand
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        
        // Required? We're using required when perhaps we should be using allowEmpty.
        // An attribute is required if the enrollment flow requires it OR if it is
        // type 'o' or 'r' and is required by the data model.
        $attr['required'] = $efAttr['CoEnrollmentAttribute']['required'];
        $attr['mvpa_required'] = false; // does not apply
        
        if(($attrCode == 'o' || $attrCode == 'r')
           && $attrModel->validate[$attrName]['content']['required'])
          $attr['required'] = true;
        
        // Label
        $attr['label'] = $efAttr['CoEnrollmentAttribute']['label'];
        
        // Description
        $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
        
        // If configured as hidden, hide it
        $attr['hidden'] = (isset($efAttr['CoEnrollmentAttribute']['hidden']) && $efAttr['CoEnrollmentAttribute']['hidden']);
        
        // Org attributes can ignore authoritative values
        $attr['ignore_authoritative'] =
          ($attrCode == 'o'
           && isset($efAttr['CoEnrollmentAttribute']['ignore_authoritative'])
           && $efAttr['CoEnrollmentAttribute']['ignore_authoritative']);
        
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
        
        // See if there is a default value for this field. If so, determine if it
        // is modifiable.
        
        if(isset($defaultValues[ $attr['model'] ][ $attr['field'] ])) {
          // These are default values created by the Controller, eg for prepopulating Name.
          $attr['default'] = $defaultValues[ $attr['model'] ][ $attr['field'] ];
        } else if(!empty($efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
          // These are the default values configured per-enrollment flow attribute
          
          if(($attrCode == 'r'
              && ($attrName == 'valid_from' || $attrName == 'valid_through'))
             ||
             // Extended attribute of type Timestamp?
             ($attrCode == 'x'
              && ($attrModel->field('type',
                                    array('co_id' => $efAttr['CoEnrollmentFlow']['co_id'],
                                          'name' => $attrName)) == ExtendedAttributeEnum::Timestamp))) {
            // For date types, convert to an actual date
            
            if(preg_match("/^[0-2][0-9]\-[0-9]{2}$/",
                          $efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
              // MM-DD indicates next MM-DD. Rather than muck around with PHP date parsing,
              // we'll see if {THISYEAR}-MM-DD is before now(). If it is, we'll increment
              // the year.
              
              $curyear = date("Y");
              $mmdd = explode("-", $efAttr['CoEnrollmentAttributeDefault'][0]['value'], 2);
              
              if(mktime(0, 0, 0, $mmdd[0], $mmdd[1], $curyear) < time()) {
                $curyear++;
              }
              
              $attr['default'] = $curyear . "-" . $efAttr['CoEnrollmentAttributeDefault'][0]['value'];
            } elseif(preg_match("/^\+[0-9]+$/",
                                $efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
              // Format +## indicates days from today
              
              $attr['default'] = strftime("%F",
                                          strtotime($efAttr['CoEnrollmentAttributeDefault'][0]['value'] . " days"));
            } else {
              // Just copy the string
              $attr['default'] = $efAttr['CoEnrollmentAttributeDefault'][0]['value'];
            }
          } else {
            $attr['default'] = $efAttr['CoEnrollmentAttributeDefault'][0]['value'];
          }
        }
        $attr['modifiable'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])
                               ? $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable']
                               : true);
        $attr['org_identity'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['org_identity'])
                               ? $efAttr['CoEnrollmentAttributeDefault'][0]['org_identity']
                               : false);

        // Attach the validation rules so the form knows how to render the field.
        if($attrCode == 'o') {
          $attr['validate'] = $attrModel->validate[$attrName];
          
          if(isset($attr['validate']['content']['rule'][0])
             && $attr['validate']['content']['rule'][0] == 'inList') {
            // If this is a select field, get the set of options
            $attr['select'] = $attrModel->validEnumsForSelect($attrName);
          }
        } elseif($attrCode == 'r') {
          if($attrName == 'affiliation') {
            // Affiliation needs a select based on available affiliations
            
            $attr['select'] = $attrModel->types($efAttr['CoEnrollmentFlow']['co_id'], 'affiliation');
            $attr['validate']['content']['rule'][0] = 'inList';
            $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
          } elseif($attrName == 'cou_id') {
            // We have to set up a select based on the available COUs
            
            $args = array();
            $args['fields'] = array('Cou.id', 'Cou.name');
            $args['conditions'] = array('CoEnrollmentFlow.id' => $actualEfId);
            $args['joins'][0]['table'] = 'co_enrollment_flows';
            $args['joins'][0]['alias'] = 'CoEnrollmentFlow';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'Cou.co_id=CoEnrollmentFlow.co_id';
            $args['order'] = 'Cou.name ASC';
            
            $attr['select'] = $this->CoEnrollmentFlow->CoPetition->Cou->find('list', $args);
            $attr['validate']['content']['rule'][0] = 'inList';
            $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
            // As of Cake 2.1, inList doesn't work for integers unless you set strict to false
            // https://cakephp.lighthouseapp.com/projects/42648/tickets/2770-inlist-doesnt-work-more-in-21
            $attr['validate']['content']['rule'][2] = false;
          } elseif($attrName == 'sponsor_co_person_id') {
            // Like COU ID, we need to set up a select
            
            $attr['select'] = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson->sponsorList($efAttr['CoEnrollmentFlow']['co_id']);
            $attr['validate']['content']['rule'][0] = 'inList';
            $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
            // As of Cake 2.1, inList doesn't work for integers unless you set strict to false
            // https://cakephp.lighthouseapp.com/projects/42648/tickets/2770-inlist-doesnt-work-more-in-21
            $attr['validate']['content']['rule'][2] = false;
          } else {
            // Default behavior for all other attributes
            
            $attr['validate'] = $attrModel->validate[$attrName];
            
            if(isset($attr['validate']['content']['rule'][0])
               && $attr['validate']['content']['rule'][0] == 'inList') {
              // If this is a select field, get the set of options
              $attr['select'] = $attrModel->validEnumsForSelect($attrName);
            }
          }
        } else {
          // Extended attributes
          
          $attr['validate']['content'] = $attrModel->validationRules($efAttr['CoEnrollmentFlow']['co_id'], $attrName);
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
        
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
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
          if($k != 'co_department_id'
             && $k != 'co_person_id'
             && $k != 'co_person_role_id'
             && $k != 'org_identity_id'
             && $k != 'source_' . $attrName . '_id'
             // For now, we skip description (introduced to MVPAs in 3.1.0)
             // because it will generally be too confusing to people.
             // "What's my email description?" This could become configurable, though.
             && $k != 'description') {
            $attr = array();
              
            // The attribute ID and attribute key will be the same for all components
            // of a multi-valued attribute
            $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
            $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
            $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
            
            // Track if the mvpa itself is required
            $attr['mvpa_required'] = $efAttr['CoEnrollmentAttribute']['required'];
            
            // For certain MVPAs, check if this field is even permitted (currently only names)
            // XXX this is kind of clunky -- rewrite if this expands to more attributes
            if(strstr($efAttr['CoEnrollmentAttribute']['attribute'], ':name:')
               && in_array($k, array('honorific','given','middle','family','suffix'))
               && !in_array($k, $permNameFields)) {
              // skip this field
              continue;
            }
            
            // Is this individual attribute required?
            if(!empty($efAttr['CoEnrollmentAttribute']['required_fields'])) {
              // See if the field is specified in the fields list. It would be slightly
              // more efficient to not split the string for each field each time through
              // the foreach loop.
              // XXX should honor CO Settings if not set
              
              $rfields = explode(",", $efAttr['CoEnrollmentAttribute']['required_fields']);
              
              $attr['required'] = in_array($k, $rfields);
            } else {
              // We use allowEmpty to check, which is more accurate than $validate->required.
              // Required is true if the attribute is required by the enrollment flow configuration,
              // AND if the MVPA's element is also required/allowEmpty (eg: Email requires mail to be set).
              
              $attr['required'] = ($attr['mvpa_required']
                                   &&
  // XXX need to look for other places where ['content'] needs to be added
                                   isset($attrModel->validate[$k]['content']['allowEmpty'])
                                   &&
                                   !$attrModel->validate[$k]['content']['allowEmpty']);
            }
            
            // Org attributes can ignore authoritative values
            $attr['ignore_authoritative'] =
              ($attrCode == 'i'
               && isset($efAttr['CoEnrollmentAttribute']['ignore_authoritative'])
               && $efAttr['CoEnrollmentAttribute']['ignore_authoritative']);
            
            // if configured hidden, hide it
            $attr['hidden'] = (isset($efAttr['CoEnrollmentAttribute']['hidden']) && $efAttr['CoEnrollmentAttribute']['hidden']);

            // We hide language, primary_name, type, status, and verified
            $attr['hidden'] = ($k == 'language'
                               || $k == 'login'
                               || $k == 'primary_name'
                               || $k == 'type'
                               || $k == 'status'
                               || $k == 'verified' ? true : $attr['hidden']);
            
            if($attr['hidden']) {
              // Populate a default value.
              
              switch($k) {
                case 'language':
                  if(!empty($efAttr['CoEnrollmentAttribute']['language'])) {
                    $attr['default'] = $efAttr['CoEnrollmentAttribute']['language'];
                  } else {
                    $attr['default'] = "";
                  }
                  break;
                case 'login':
                  // For Identifiers, set the default value based on the Enrollment Attribute configuration
                  $attr['default'] = isset($efAttr['CoEnrollmentAttribute']['login'])
                                     && $efAttr['CoEnrollmentAttribute']['login'];
                  break;
                case 'primary_name':
                  // Official names are considered primary names, at least for now
                  if($attr['attribute'] == 'i:name:official' || $attr['attribute'] == 'p:name:official') {
                    $attr['default'] = 1;
                  } else {
                    $attr['default'] = 0;
                  }
                  break;
                case 'type':
                  // Just use $attrType
                  $attr['default'] = $attrType;
                  break;
                case 'status':
                  // For now, status is always set to Active
                  $attr['default'] = StatusEnum::Active;
                  break;
                case 'verified':
                  // Verified defaults to false
                  $attr['default'] = 0;
                  break;
                default:
                  $attr['default'] = isset($efAttr['CoEnrollmentAttributeDefault'][0]['value']) ? $efAttr['CoEnrollmentAttributeDefault'][0]['value'] : null;
              }
            } else {
              // Label
              $attr['group'] = $efAttr['CoEnrollmentAttribute']['label'];
              $attr['label'] = _txt('fd.' . $attrName . '.' . $k);
              
              // Description
              $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
            }
            
            // Model, in cake's Model.field
            $attr['model'] = $m;
            
            // Field, in cake's Model.field
            $attr['field'] = $k;
            
            // See if there is a default value for this field
            if(isset($defaultValues[$m][$k])) {
              $attr['default'] = $defaultValues[$m][$k];
            }
            
            // Attach the validation rules so the form knows how to render the field.
            $attr['validate'] = $attrModel->validate[$k];
            
            if(!empty($attr['validate']['content']['rule'][0])
               && $attr['validate']['content']['rule'][0] == 'validateExtendedType') {
              // Insert the current CO ID
              $attr['validate']['content']['rule'][1]['coid'] = $efAttr['CoEnrollmentFlow']['co_id'];
            }
            
            if($k != 'type'
               && isset($attr['validate']['content']['rule'][0])
               && $attr['validate']['content']['rule'][0] == 'inList') {
              // If this is a select field, get the set of options
              $attr['select'] = $attrModel->validEnumsForSelect($attrName);
            }

            // copy modifiable and org_identity-default-source settings
            $attr['modifiable'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])
                                   ? $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable']
                                   : true);
            $attr['org_identity'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['org_identity'])
                                   ? $efAttr['CoEnrollmentAttributeDefault'][0]['org_identity']
                                   : false);

            $attrs[] = $attr;
          }
        }
      } elseif($attrCode == 'g') {
        // This is a bit obscure. In order for FormHelper to determine that
        // CoGroupMember->member is boolean, it needs access to the CoGroupMember
        // class. Since FormHelper runs under CoPetition, and there is no direct
        // relationship between CoPetition and CoGroupMember, we need to
        // initialize the class manually so FormHelper can see it later (see
        // FormHelper::_getModel). We don't need to do this for (eg) Identifier
        // because Identifier is loaded as part of the login (beforeFilter) process.
        
        ClassRegistry::init('CoGroupMember');
        
        // Group Membership requires a bit of specialness. Basically, we'll manually
        // contruct the $attrs entry.
        
        $attr = array();
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        $attr['required'] = $efAttr['CoEnrollmentAttribute']['required'];
        $attr['mvpa_required'] = false; // does not apply
        $attr['group'] = $efAttr['CoEnrollmentAttribute']['label'];
        $attr['label'] = _txt('ct.co_groups.1');
        $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
        $attr['model'] = "EnrolleeCoPerson.CoGroupMember." . $efAttr['CoEnrollmentAttribute']['id'];
        $attr['field'] = "co_group_id";
        $attr['hidden'] = false;
        if(!empty($efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
          $attr['default'] = $efAttr['CoEnrollmentAttributeDefault'][0]['value'];
          // If there's a default value, then the attribute can be hidden
          $attr['hidden'] = (isset($efAttr['CoEnrollmentAttribute']['hidden'])
                             && $efAttr['CoEnrollmentAttribute']['hidden']);
        }
        $attr['modifiable'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])
                               ? $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable']
                               : true);
        $attr['org_identity'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['org_identity'])
                               ? $efAttr['CoEnrollmentAttributeDefault'][0]['org_identity']
                               : false);
        $attr['validate']['content']['rule'][0] = 'inList';
        
        // Pull the set of groups for the select
        $args = array();
        $args['conditions']['co_id'] = $efAttr['CoEnrollmentFlow']['co_id'];
        $args['fields'] = array('CoGroup.id', 'CoGroup.name');
        $args['contain'] = false;
        
        $attr['select'] = $this->CoEnrollmentFlow->Co->CoGroup->find('list', $args);
        
        $attrs[] = $attr;
        
        // We allow a petitioner to opt-in (ie: tick the box) for membership when
        // the following are all true:
        //  (1) A default group is specified
        //  (2) Modifiable is false
        //  (3) Hidden is false
        //  (4) Attribute is optional, not required
        // Figure this out before we reset $attr.
        
        $optin = (!empty($attr['default'])
                  && !$attr['modifiable']
                  && !$attr['hidden']
                  && $attr['required'] == RequiredEnum::Optional);
        
        // Inject hidden attributes to specify membership
        
        $attr = array();
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        $attr['group'] = $efAttr['CoEnrollmentAttribute']['label'];
        $attr['label'] = _txt('fd.group.mem');
        $attr['hidden'] = !$optin;
        $attr['modifiable'] = $optin;
        $attr['required'] = false;
        $attr['mvpa_required'] = false;
        $attr['default'] = true;
        $attr['model'] = "EnrolleeCoPerson.CoGroupMember." . $efAttr['CoEnrollmentAttribute']['id'];
        $attr['field'] = "member";
        
        $attrs[] = $attr;
        
        // ... and ownership
        
        if($attrName == 'co_group_member_owner') {
          // Repurpose most prior settings
          $attr['label'] = _txt('fd.group.own');
          $attr['field'] = "owner";
        } else {
          $attr['default'] = 0;
          $attr['field'] = "owner";
          // Explicitly set in case $optin is true
          $attr['hidden'] = true;
          $attr['modifiable'] = false;
          $attr['required'] = true;
        }
        
        $attrs[] = $attr;
        
        // Inject a hidden attribute to link this attribute back to its definition.
        
        $attr = array();
        
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        $attr['hidden'] = true;
        $attr['default'] = $attr['id'];
        $attr['model'] = "EnrolleeCoPerson.CoGroupMember." . $efAttr['CoEnrollmentAttribute']['id'];
        $attr['field'] = "co_enrollment_attribute_id";
        
        $attrs[] = $attr;
      } elseif($attrCode == 'e') {
        // Attributes for the enrollment flow only -- these do not get copied
        // outside of the petition
        
        $attr = array();
        
        $attr['CoEnrollmentAttribute'] = $efAttr['CoEnrollmentAttribute'];
        $attr['id'] = $efAttr['CoEnrollmentAttribute']['id'];
        $attr['attribute'] = $efAttr['CoEnrollmentAttribute']['attribute'];
        $attr['label'] = $efAttr['CoEnrollmentAttribute']['label'];
        $attr['hidden'] = false;
        $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];
        $attr['required'] = $efAttr['CoEnrollmentAttribute']['required'];
        // Create a pseudo model and field
        $attr['model'] = "CoPetitionAttribute";
        $attr['field'] = $attrName;
        
        $attrs[] = $attr;
      } else {
        throw new RuntimeException("Unknown attribute code: " . $attrCode);
      }
    }
    
    return $attrs;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v0.9
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {
    // Override the parent version since we need to retrieve via the co enrollment flow
    
    // First get the enrollment flow
    $coef = $this->field('co_enrollment_flow_id', array('CoEnrollmentAttribute.id' => $id));
    
    if(!$coef) {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.co_enrollment_attributes.1', $id)));
    }
    
    $coId = $this->CoEnrollmentFlow->field('co_id', array("CoEnrollmentFlow.id" => $coef));
    
    if($coId) {
      return $coId;
    } else {
      throw new InvalidArgumentException(_txt('er.notfound', array('ct.co_enrollment_flows.1', $coef)));
    }
  }
  
  /**
   * Map environment variables into enrollment attribute default values.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array Array of CO enrollment attributes, as returned by enrollmentFlowAttributes()
   * @param  Array Array of CMP enrollment attributes, as returned by CmpEnrollmentConfiguration::enrollmentAttributesFromEnv()
   * @param  Integer ID of the current petition
   * @return Array Array of CO enrollment attributes
   */
  
  public function mapEnvAttributes($enrollmentAttributes, $envValues, $petition_id) {
    // First, map the enrollment attributes by model+field, but only for those
    // that we might actually populate (ie: org attributes). We partly have to
    // do this because CO Enrollment Attributes and CMP Enrollment Attributes
    // use different formats in their attribute column (the former does not
    // include field names while the latter does).
    
    if(!empty($envValues)) {
      $eaMap = array();
      
      for($i = 0;$i < count($enrollmentAttributes);$i++) {
        $model = explode('.', $enrollmentAttributes[$i]['model'], 2);
        
        // Only track org identity attributes
        if($model[0] == "EnrolleeOrgIdentity"
           // that aren't hidden
           && !$enrollmentAttributes[$i]['hidden']
           // and that are modifiable
           && (!isset($enrollmentAttributes[$i]['modifiable'])
               || $enrollmentAttributes[$i]['modifiable'])
           // and that aren't set to ignore authoritative values
           && (!isset($enrollmentAttributes[$i]['ignore_authoritative'])
               || !$enrollmentAttributes[$i]['ignore_authoritative'])) {
          $key = "";
          
          if(!empty($model[1])) {
            // Inflect the associated model name, minus any model ID
            // (ie: we want "EmailAddress", not "EmailAddress.3")
            
            $m = explode(".", $model[1], 2);
            $key = Inflector::pluralize(Inflector::tableize($m[0])) . ":";
          }
          
          $key .= $enrollmentAttributes[$i]['field'];
          
          $eaMap[$key] = $i;
        }
      }
      
      // Now walk through the CMP Enrollment Attributes. If an env_name is defined,
      // look for the corresponding CO Enrollment Attribute.
      
      foreach($envValues as $e) {
        if(!empty($e['env_name']) && isset($eaMap[ $e['attribute'] ])) {
          $i = $eaMap[ $e['attribute'] ];
          
          if(!empty($e['type'])) {
            // Check the type. The enrollment attribute is of the form i:name:official.
            
            $xeattr = explode(':', $enrollmentAttributes[$i]['attribute'], 3);
            
            if(empty($xeattr[2]) || ($e['type'] != $xeattr[2])) {
              // This is not the right type of attribute, move on
              continue;
            }
          }
          // If no type specified, match any instance of this attribute, regardless of type
          
          $enrollmentAttributes[$i]['default'] = getenv($e['env_name']);
          
          // Make sure the modifiable value is set. If a value was found, we will
          // make it not-modifiable.
          
          $enrollmentAttributes[$i]['modifiable'] = !(boolean)$enrollmentAttributes[$i]['default'];
        }
      }
    }

    $org_identities=array();
    if(!empty($petition_id)) {

      // create a list of OrgIdentities we can use to take defaults from
      // get all possible fields for default values as well
      // We do not take the EnrolleeOrgIdentity, because that is the destination
      // identity we might want to copy values to
      $args = array();
      $args['conditions']['CoPetition.id'] = intval($petition_id);
      $args['contain'] = array(
        'OrgIdentitySourceRecord' => array(
          'OrgIdentity' => array ('PrimaryName','Address','EmailAddress','Identifier','Name','TelephoneNumber'))
      );

      $petition = $this->CoEnrollmentFlow->CoPetition->find('first', $args);

      if(!empty($petition) && isset($petition['OrgIdentitySourceRecord'])) {
        foreach($petition['OrgIdentitySourceRecord'] as $ois) {
          $org_identities[]=$ois['OrgIdentity'];
        }
      }
    }

    // Check for default values from env variables.

    for($i = 0;$i < count($enrollmentAttributes);$i++) {

      if(!empty($enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env'])) {
        if(strstr($enrollmentAttributes[$i]['attribute'], ':name:')) {
          // Handle name specially
          $envVar = $enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env']
                  . "_"
                  . strtoupper($enrollmentAttributes[$i]['field']);
        } else {
          $envVar = $enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env'];
        }
        
        $enrollmentAttributes[$i]['default'] = getenv($envVar);
      }

      if( empty($enrollmentAttributes[$i]['default']) && $enrollmentAttributes[$i]['org_identity']) {

        // if we have an OrgIdentity attached to this EF, try to pick a default from that
        // This solves the use case where we have an OIS plugin in authenticate mode that
        // reads a source for us and allows us to prepopulate the petitioner attributes
        //
        // We match in two loops: one for a very specific match (home address => home address only)
        // and one for a loose match (email address => any email address)

        foreach($org_identities as $oi) {
          $enrollmentAttributes[$i]['default'] = $this->mapOrgIdentityToDefault($enrollmentAttributes[$i], $oi, true);
          if(!empty($enrollmentAttributes[$i]['default'])) {
            break;
          }
        }

        if(empty($enrollmentAttributes[$i]['default'])) {
          foreach($org_identities as $oi) {
            $enrollmentAttributes[$i]['default'] = $this->mapOrgIdentityToDefault($enrollmentAttributes[$i], $oi, false);
            if(!empty($enrollmentAttributes[$i]['default'])) {
              break;
            }
          }
        }
      }
    }
    
    return $enrollmentAttributes;
  }
  
  /**
   * Try to match an enrollment attribute to a field on one of the attached OrgIdentities
   *
   * @since  COmanage Registry vTODO
   * @param  Array ea enrollment attribute definition
   * @param  Array oi OrgIdentity object
   * @param  Bool strict_match if true, only match exact address/emailaddress/name types
   * @return String default value, empty if none found
   */
  private function mapOrgIdentityToDefault($ea, $oi, $strict_match) {
    $retval = "";

    $a = explode(':', $ea['attribute'], 4);

    // See availableAttributes() for the various codes
    $attrCode = array_shift($a);

    // attribute name (as per availableAttributes)
    $attrName = array_shift($a);
    $attrModelName = Inflector::camelize($attrName);

    // optional constraining type, for multi-valued attributes
    $attrType = array_shift($a);

    switch($attrCode) {
    case 'r': // CoPersonRole related fields
    case 'o': // OrgIdentity related fields
      if(!empty($oi[$attrName])) {
        $retval = $oi[$attrName];
      }
      break;
    case 'p':       // CoPerson related fields
    case 'm':       // multi-valued CoPersonRole related fields
    case 'i':       // multi-valued OrgIdentity related fields
      if(isset($oi[$attrModelName])) {
        $field = $ea['field'];
        foreach($oi[$attrModelName] as $data) {
          // if it is set and of the same type, or we do not check types
          if(isset($data[$field]) && (!$strict_match || $data["type"]==$attrType)) {
            $retval = $data[$field];
          }
        }
      }
      break;
    default:
      break;
    }

    return $retval;
  }

  /**
   * Check if a given extended type is in use by any Enrollment Attribute.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String Attribute, of the form Model.field
   * @param  String Name of attribute (any default or extended type may be specified)
   * @param  Integer CO ID
   * @return Boolean True if the extended type is in use, false otherwise
   */
  
  public function typeInUse($attribute, $attributeName, $coId) {
    // Note we are effectively overriding AppModel::typeInUse().
    
    // Inflect the model names
    $attr = explode('.', $attribute, 2);
    
    $mname = Inflector::underscore($attr[0]);
    
    if($attr[0] == 'CoPersonRole') {
      // We might have a default value in use.
      
      $rattr = "r:" . $attr[1];
      
      $args = array();
      $args['conditions']['CoEnrollmentAttributeDefault.value'] = $attributeName;
      $args['conditions']['CoEnrollmentAttribute.attribute'] = $rattr;
      $args['conditions']['CoEnrollmentFlow.co_id'] = $coId;
      $args['joins'][0]['table'] = 'co_enrollment_attribute_defaults';
      $args['joins'][0]['alias'] = 'CoEnrollmentAttributeDefault';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoEnrollmentAttributeDefault.co_enrollment_attribute_id=CoEnrollmentAttribute.id';
      $args['joins'][1]['table'] = 'co_enrollment_flows';
      $args['joins'][1]['alias'] = 'CoEnrollmentFlow';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'CoEnrollmentFlow.id=CoEnrollmentAttribute.co_enrollment_flow_id';
      $args['contain'] = false;
      
      return (boolean)$this->find('count', $args);
    } elseif($attr[1] == 'type') {
      // For MVPA attribute, we need to see if the type is specified as part of the
      // attribute name.
      
      // We're only concerned about code 'p' and 'm' (CO Person and CO Person Role
      // multi valued). Rather than try to guess or hardcode which we're dealing with,
      // we'll simply check for both.
      
      $mattr = "m:" . $mname . ":" . $attributeName;
      $pattr = "p:" . $mname . ":" . $attributeName;
      
      $args = array();
      $args['conditions']['CoEnrollmentAttribute.attribute'] = array($mattr, $pattr);
      $args['conditions']['CoEnrollmentFlow.co_id'] = $coId;
      $args['joins'][0]['table'] = 'co_enrollment_flows';
      $args['joins'][0]['alias'] = 'CoEnrollmentFlow';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoEnrollmentFlow.id=CoEnrollmentAttribute.co_enrollment_flow_id';
      $args['contain'] = false;
      
      return (boolean)$this->find('count', $args);
    }
    // else nothing to do
    
    return false;
  }
}
