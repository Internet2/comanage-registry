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
    ),
    'hidden' => array(
      'validateRequired' => array(
        'rule' => array('boolean'),
        'required' => false,
        'allowEmpty' => true
      ),
      'validateHidden' => array(
        'rule' => array('validateHidden'),
        'required' => false
      )
    )
  );

  /**
   * Validate that the field is allowed to be hidden based on the attribute and default value
   *
   * @param  array            Array of fields to validate
   * @return boolean | string True if allowed, false or error message if not
   */
  public function validateHidden($a) {
    // Hidden attribute is only applicable to
    // CO Person Role attributes (type 'r')
    // CO Person attributes (type 'g'),
    // Organizational Identity attributes (type 'o') or
    // Extended Attributes (type 'x')
    $attrCode = explode(':', $this->data['CoEnrollmentAttribute']['attribute']);
    if(!in_array($attrCode[0], array('r', 'g', 'o', 'x'))) {
      return true;
    }

    // A default value must be provided if field is hidden
    if(isset($a['hidden']) && $a['hidden']) {
      return !empty($this->data['CoEnrollmentAttributeDefault'][0]['value']) ?: _txt('er.field.hidden.req');
    }

    return true;
  }
  
  /**
   * Determine the attributes available to be requested as part of an Enrollment Flow.
   * (1)  (code=r) Single valued CO Person Role attributes
   * (2)  (code=p) Multi valued CO Person attributes
   * (2a) (code=g) Group Memberships are Multi valued CO Person attributes, but have all sorts of special logic around them so they get their own code
   * (3)  (code=m) Multi valued CO Person Role attributes
   * (4)  (code=x) CO Person Role Extended attributes
   * (5)  (code=o) Single valued Org Identity attributes, if enabled
   * (6)  (code=i) Multi valued Org Identity attributes, if enabled.Note that since org identities don't support extended types, we use default values here.
   * (7)  (code=e) Enrollment Flow specific attributes -- these don't get copied out of the petition
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
      $ret[_txt('ct.co_person_roles.1')]['r:cou_id'] = _txt('fd.cou');
    }
    $ret[_txt('ct.co_person_roles.1')]['r:affiliation'] = _txt('fd.affiliation');
    $ret[_txt('ct.co_person_roles.1')]['r:manager_co_person_id'] = _txt('fd.manager');
    $ret[_txt('ct.co_person_roles.1')]['r:sponsor_co_person_id'] = _txt('fd.sponsor');
    $ret[_txt('ct.co_person_roles.1')]['r:title'] = _txt('fd.title');
    $ret[_txt('ct.co_person_roles.1')]['r:o'] = _txt('fd.o');
    $ret[_txt('ct.co_person_roles.1')]['r:ou'] = _txt('fd.ou');
    $ret[_txt('ct.co_person_roles.1')]['r:valid_from'] = _txt('fd.valid_from');
    $ret[_txt('ct.co_person_roles.1')]['r:valid_through'] = _txt('fd.valid_through');
    
    // (2) Multi valued CO Person attributes (code=p)
    
    // Several types can be extended, and so require a bit of work to assemble
    $Name = ClassRegistry::init('Name');
    $nameTypes = $Name->types($coid, 'type');
    
    foreach(array_keys($nameTypes) as $k)
      $ret[_txt('ct.co_people.1')]['p:name:'.$k] = _txt('fd.name') . " (" . $nameTypes[$k] . ")";
    
    $Identifier = ClassRegistry::init('Identifier');
    $identifierTypes = $Identifier->types($coid, 'type');
    
    foreach(array_keys($identifierTypes) as $k)
      $ret[_txt('ct.co_people.1')]['p:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $identifierTypes[$k] . ")";
    
    $EmailAddress = ClassRegistry::init('EmailAddress');
    $emailAddressTypes = $EmailAddress->types($coid, 'type');
    
    foreach(array_keys($emailAddressTypes) as $k)
      $ret[_txt('ct.co_people.1')]['p:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $emailAddressTypes[$k] . ")";
    
    $Url = ClassRegistry::init('Url');
    $urlTypes = $Url->types($coid, 'type');
    
    foreach(array_keys($urlTypes) as $k)
      $ret[_txt('ct.co_people.1')]['p:url:'.$k] = _txt('fd.url.url') . " (" . $urlTypes[$k] . ")";
    
    // (2a) Group Memberships are Multi valued CO Person attributes, but have all sorts
    // of special logic around them so they get their own code (code=g)
    
    $ret[_txt('ct.co_people.1')]['g:co_group_member'] = _txt('fd.group.grmem');
    $ret[_txt('ct.co_people.1')]['g:co_group_member_owner'] = _txt('fd.group.grmemown');
    
    // (3) Multi valued CO Person Role attributes (code=m)
    
    $TelephoneNumber = ClassRegistry::init('TelephoneNumber');
    $telephoneNumberTypes = $TelephoneNumber->types($coid, 'type');
    
    foreach(array_keys($telephoneNumberTypes) as $k)
      $ret[_txt('ct.co_person_roles.1')]['m:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $telephoneNumberTypes[$k] . ")";
    
    $Address = ClassRegistry::init('Address');
    $addressTypes = $Address->types($coid, 'type');
    
    foreach(array_keys($addressTypes) as $k)
      $ret[_txt('ct.co_person_roles.1')]['m:address:'.$k] = _txt('fd.address') . " (" . $addressTypes[$k] . ")";
    
    // (4) CO Person Role Extended attributes (code=x)
    
    $extAttrs = $this->CoEnrollmentFlow->Co->CoExtendedAttribute->findAllByCoId($coid);
    
    foreach($extAttrs as $e)
      $ret[_txt('ct.co_person_roles.1')]['x:' . $e['CoExtendedAttribute']['name']] = $e['CoExtendedAttribute']['display_name'];
    
    $cmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($cmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
      // (5) Single valued Org Identity attributes, if enabled (code=o)
      
      $ret[_txt('ct.org_identities.1')]['o:affiliation'] = _txt('fd.affiliation');
      $ret[_txt('ct.org_identities.1')]['o:title'] = _txt('fd.title');
      $ret[_txt('ct.org_identities.1')]['o:o'] = _txt('fd.o');
      $ret[_txt('ct.org_identities.1')]['o:ou'] = _txt('fd.ou');
      
      // (6) Multi valued Org Identity attributes, if enabled (code=i)
      
      foreach(array_keys($nameTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:name:'.$k] = _txt('fd.name') . " (" . $nameTypes[$k] . ")";
      
      foreach(array_keys($identifierTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:identifier:'.$k] = _txt('fd.identifier.identifier') . " (" . $identifierTypes[$k] . ")";
      
      foreach(array_keys($addressTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:address:'.$k] = _txt('fd.address') . " (" . $addressTypes[$k] . ")";
      
      foreach(array_keys($emailAddressTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:email_address:'.$k] = _txt('fd.email_address.mail') . " (" . $emailAddressTypes[$k] . ")";
        
      foreach(array_keys($telephoneNumberTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:telephone_number:'.$k] = _txt('fd.telephone_number.number') . " (" . $telephoneNumberTypes[$k] . ")";
        
      foreach(array_keys($urlTypes) as $k)
        $ret[_txt('ct.org_identities.1')]['i:url:'.$k] = _txt('fd.url.url') . " (" . $urlTypes[$k] . ")";
    }
    
    // (7) Enrollment Flow specific attributes -- these don't get copied out of the petition (code=e)
    $ret[_txt('ct.petitions.1')]['e:textfield'] = _txt('fd.pt.textfield');
    
    // (8) Single valued CO Person attributes (code=c)
    $ret[_txt('ct.co_people.1')]['c:date_of_birth'] = _txt('fd.date_of_birth');
    
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

    // if there is no parent id then the co_enrollment_flow_id field will always be null
    if(is_null($actualEfId)) {
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
      
      if($attrCode == 'c' || $attrCode == 'o' || $attrCode == 'r' || $attrCode == 'x') {
        $attrModel = null;
        
        switch($attrCode) {
        case 'c':
          $attrModel = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson;
          break;
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

        // Enrollment configuration has to prevail over default model validation
        if($attrCode == 'c' || $attrCode == 'o' || $attrCode == 'r') {
          $attrModel->validate[$attrName]['content']['required'] = (bool)$attr['required'];
          $attr['allow_empty'] = !$attr['required'];
          $attrModel->validate[$attrName]['content']['allowEmpty'] = (bool)$attr['allow_empty'];
        }
        
        // Label
        $attr['label'] = $efAttr['CoEnrollmentAttribute']['label'];
        
        // Description
        $attr['description'] = $efAttr['CoEnrollmentAttribute']['description'];

        // Single value attributes are never hidden, unless there is a non-modifable
        // default value
        $attr['hidden'] =
          (isset($efAttr['CoEnrollmentAttribute']['hidden'])
           && $efAttr['CoEnrollmentAttribute']['hidden']
           && isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])
           && !$efAttr['CoEnrollmentAttributeDefault'][0]['modifiable']);
        
        // Org attributes can ignore authoritative values
        $attr['ignore_authoritative'] =
          ($attrCode == 'o'
           && isset($efAttr['CoEnrollmentAttribute']['ignore_authoritative'])
           && $efAttr['CoEnrollmentAttribute']['ignore_authoritative']);
        
        // Model, in cake's Model.field.
        if($attrCode == 'o') {
          $attr['model'] = 'EnrolleeOrgIdentity';
        } elseif($attrCode == 'c') {
          $attr['model'] = 'EnrolleeCoPerson';
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
          // Currently, they are always modifiable.
          $attr['default'] = $defaultValues[ $attr['model'] ][ $attr['field'] ];
          $attr['modifiable'] = true;
        } elseif(!empty($efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
          // These are the default values configured per-enrollment flow attribute
          
          if(($attrCode == 'r'
              && ($attrName == 'valid_from' || $attrName == 'valid_through'))
             ||
             // Date of Birth
             ($attrCode == 'c' && $attrName == 'date_of_birth')
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
          $attr['modifiable'] = $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'];
        } elseif($efAttr['CoEnrollmentAttribute']['attribute'] == 'r:sponsor_co_person_id') {
          // Special case for sponsor, we want to make sure the modifiable field passes
          // through even if there is no default value
          if(isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])) {
            $attr['modifiable'] = $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'];
          } else {
            $attr['modifiable'] = true;
          }
        }

        // Attach the validation rules so the form knows how to render the field.
        if($attrCode == 'c' || $attrCode == 'o') {
          $attr['validate'] = $attrModel->validate[$attrName];
          
          if(isset($attr['validate']['content']['rule'][0])
             && $attr['validate']['content']['rule'][0] == 'inList') {
            // If this is a select field, get the set of options
            $attr['select'] = $attrModel->validEnumsForSelect($attrName);
          } elseif($attrName == 'affiliation') {
            // Affiliation needs a select based on available affiliations
            
            $attr['select'] = $attrModel->types($efAttr['CoEnrollmentFlow']['co_id'], 'affiliation');
            $attr['validate']['content']['rule'][0] = 'inList';
            $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
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
            if(isset($efAttr['CoEnrollmentAttributeDefault'][0]['value'])
               && !is_null($efAttr['CoEnrollmentAttributeDefault'][0]['value'])) {
              $attr['default'] = $efAttr['CoEnrollmentAttributeDefault'][0]['value'];
              // If there's a default value, then the attribute can be hidden
              $attr['hidden'] = (isset($efAttr['CoEnrollmentAttribute']['hidden'])
                && $efAttr['CoEnrollmentAttribute']['hidden']);
            }
            $attr['modifiable'] = (isset($efAttr['CoEnrollmentAttributeDefault'][0]['modifiable'])
                                    ? $efAttr['CoEnrollmentAttributeDefault'][0]['modifiable']
                                    : false);
          } elseif($attrName == 'sponsor_co_person_id') {
            // Like COU ID, we need to set up a select
            
            try {
              $attr['select'] = $this->CoEnrollmentFlow->CoPetition->Co->CoPerson->sponsorList($efAttr['CoEnrollmentFlow']['co_id']);
              $attr['validate']['content']['rule'][0] = 'inList';
              $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
              // As of Cake 2.1, inList doesn't work for integers unless you set strict to false
              // https://cakephp.lighthouseapp.com/projects/42648/tickets/2770-inlist-doesnt-work-more-in-21
              $attr['validate']['content']['rule'][2] = false;
            }
            catch(OverflowException $e) {
              // Use the people picker instead
              
              // We don't need to inject a validation rule
            }
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
             && $k != 'co_group_id'
             && $k != 'co_person_id'
             && $k != 'co_person_role_id'
             && $k != 'co_provisioning_target_id'
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
            $attr['mvpa_required'] = (bool)$efAttr['CoEnrollmentAttribute']['required'];
            
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
            
            // We hide language, primary_name, type, status, and verified
            $attr['hidden'] = ($k === 'language'
                               || $k === 'login'
                               || $k === 'primary_name'
                               || $k === 'type'
                               || $k === 'status'
                               || $k === 'verified' ? true : false);
            
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
                               : false);
        
        // Pull the set of groups for the select
        $args = array();
        $args['conditions']['co_id'] = $efAttr['CoEnrollmentFlow']['co_id'];
        $args['fields'] = array('CoGroup.id', 'CoGroup.name');
        $args['contain'] = false;
        
        $attr['select'] = $this->CoEnrollmentFlow->Co->CoGroup->find('list', $args);

        $attr['validate']['content']['rule'][0] = 'inList';
        $attr['validate']['content']['rule'][1] = array_keys($attr['select']);
        // As of Cake 2.1, inList doesn't work for integers unless you set strict to false
        // https://cakephp.lighthouseapp.com/projects/42648/tickets/2770-inlist-doesnt-work-more-in-21
        $attr['validate']['content']['rule'][2] = false;

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
                  && $attr['required'] === RequiredEnum::Optional);
        
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
   * @throws RuntimeException
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
   * @return Array Array of CO enrollment attributes
   */
  
  public function mapEnvAttributes($enrollmentAttributes, $envValues) {
    // First, map the enrollment attributes by model+field, but only for those
    // that we might actually populate (ie: org attributes). We partly have to
    // do this because CO Enrollment Attributes and CMP Enrollment Attributes
    // use different formats in their attribute column (the former does not
    // include field names while the latter does).
    
    if(!empty($envValues)) {
      $eaMap = array();
      
      for($i = 0, $iMax = count($enrollmentAttributes); $i < $iMax; $i++) {
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
    
    // Check for default values from env variables.

    for($i = 0, $iMax = count($enrollmentAttributes); $i < $iMax; $i++) {
      // Skip anything that's hidden. This will prevent us from setting a
      // default value for metadata attributes, and will also prevent using
      // default values in hidden attributes (which is probably a feature, not
      // a bug).
      
      if($enrollmentAttributes[$i]['hidden']) {
        continue;
      }
      
      if(!empty($enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env'])) {
        if(strstr($enrollmentAttributes[$i]['attribute'], ':name:')) {
          // Handle name specially
          $envVar = $enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env']
                  . "_"
                  . strtoupper($enrollmentAttributes[$i]['field']);
        } else {
          $envVar = $enrollmentAttributes[$i]['CoEnrollmentAttribute']['default_env'];
        }

        // The default value is either the default envVar or the default hardcoded value if the envVar is not present
        // in the session. For example community Identity Providers might not provide affiliation attributes.
        $enrollmentAttributes[$i]['default'] = !empty(getenv($envVar)) ? getenv($envVar)
          : ( !empty($enrollmentAttributes[$i]['default'])
            ? $enrollmentAttributes[$i]['default'] : '');

        $enrollmentAttributes[$i]['modifiable'] = (!isset($enrollmentAttributes[$i]['modifiable'])) ? true :
          $enrollmentAttributes[$i]['modifiable'];
        // XXX Should we define default value for each env_var in case of complex attributes, e.g. given name
        // We use allowEmpty to check, which is more accurate than $validate->required.
        // Required is true if the attribute is required by the enrollment flow configuration,
        // AND if the MVPA's element is also required/allowEmpty (eg: Email requires mail to be set).
        // In the new style, these are defaults, not canonical values
        if( empty($enrollmentAttributes[$i]['default'])
          && $enrollmentAttributes[$i]['required']) {
          $enrollmentAttributes[$i]['modifiable'] = true;
        }
      }
    }

    return $enrollmentAttributes;
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
