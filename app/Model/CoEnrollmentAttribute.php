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
      'rule' => 'numeric'
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
    
    // There are three types of attributes we need to assemble...
    
    // (1) Single valued attributes, generally attached to the Person Role
    
    $ret['s:affiliation'] = _txt('fd.affiliation');
    $ret['s:title'] = _txt('fd.title');
    $ret['s:o'] = _txt('fd.o');
    $ret['s:ou'] = _txt('fd.ou');
    $ret['s:valid_from'] = _txt('fd.valid.f');
    $ret['s:valid_through'] = _txt('fd.valid.u');
    
    // (2) Multi valued attributes, mostly (but not exclusively) attached
    // to the Person Role... add one entry per defined type
    
    foreach(array_keys($cm_texts[ $cm_lang ]['en.name']) as $k)
      $ret['m:name:'.$k] = _txt('fd.name') . " (" . $cm_texts[ $cm_lang ]['en.name'][$k] . ")";
      
    foreach(array_keys($cm_texts[ $cm_lang ]['en.identifier']) as $k)
      $ret['m:identifier:'.$k] = _txt('fd.id') . " (" . $cm_texts[ $cm_lang ]['en.identifier'][$k] . ")";
      
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.address']) as $k)
      $ret['m:address:'.$k] = _txt('fd.address') . " (" . $cm_texts[ $cm_lang ]['en.contact.address'][$k] . ")";
      
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.mail']) as $k)
      $ret['m:mail:'.$k] = _txt('fd.mail') . " (" . $cm_texts[ $cm_lang ]['en.contact.mail'][$k] . ")";
      
    foreach(array_keys($cm_texts[ $cm_lang ]['en.contact.phone']) as $k)
      $ret['m:phone:'.$k] = _txt('fd.phone') . " (" . $cm_texts[ $cm_lang ]['en.contact.phone'][$k] . ")";
      
    // (3) Extended Attributes, which are specific to the CO and single valued
    
    $extAttrs = $this->CoEnrollmentFlow->Co->CoExtendedAttribute->findAllByCoId($coid);
    
    foreach($extAttrs as $e)
      $ret['s:' . $e['CoExtendedAttribute']['id']] = $e['CoExtendedAttribute']['display_name'];
    
    return($ret);
  }
}
