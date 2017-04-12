<?php
/**
 * COmanage Registry CO Petition Attribute Model
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

class CoPetitionAttribute extends AppModel {
  // Define class name for cake
  public $name = "CoPetitionAttribute";
  
  // Add behaviors
  // We add Changelog so that if a petition is deleted (archived), the related
  // attributes are archived as well (and not physically removed from the database)
  public $actsAs = array('Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Petition Attribute is defined by a CO Enrollment Attribute
    "CoEnrollmentAttribute",
    // A CO Petition Attribute is part of a CO Petition
    "CoPetition"
  );
  
  // Default display field for cake generated views
  public $displayField = "value";
  
  // Default ordering for find operations
  public $order = array("value");
  
  // Validation rules for table elements
  public $validate = array(
  );
}
