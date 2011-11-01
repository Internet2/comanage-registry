<?php
  /*
   * COmanage Registry CO Enrollment Flow Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  class CoEnrollmentFlow extends AppModel {
    // Define class name for cake
    var $name = "CoEnrollmentFlow";
    
    // Association rules from this model to other models
    var $belongsTo = array("Co");     // A CO Enrollment Flow is attached to a CO
    
    var $hasMany = array("CoEnrollmentAttribute" =>   // A CO Enrollment Flow has many CO Enrollment Attributes
                         array('dependent' => true),
                         "CoPetition" =>              // A CO Enrollment Flow may have zero or more CO Petitions
                         array('dependent' => true));
    
    // Default display field for cake generated views
    var $displayField = "name";
    
    // Default ordering for find operations
    var $order = array("CoEnrollmentFlow.name");
    
    // Validation rules for table elements
    var $validate = array(
      'name' => array(
        'rule' => 'notEmpty',
        'required' => true,
        'message' => 'A name must be provided'
      ),
      'self_enroll' => array(
        'rule' => array('boolean')
      ),
      'self_require_authn' => array(
        'rule' => array('boolean')
      ),
      'admin_enroll' => array(
        'rule' => array('inList', array(AdministratorEnum::NoAdmin,
                                        AdministratorEnum::CoOrCouAdmin,
                                        AdministratorEnum::CoAdmin))
      ),
      'admin_confirm_email' => array(
        'rule' => array('boolean')
      ),
      'admin_require_authn' => array(
        'rule' => array('boolean')
      ),
      'attrs_from_ldap' => array(
        'rule' => array('boolean')
      ),
      'attrs_from_saml' => array(
        'rule' => array('boolean')
      ),
      'status' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Suspended))
      )
    );
  }
?>