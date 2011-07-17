<?php
  /*
   * COmanage Gears CO Person Source Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class CoPersonSource extends AppModel {
    // Define class name for cake
    var $name = "CoPersonSource";
    
    // Association rules from this model to other models
    var $belongsTo = array("Co",                     // A CO Person Source is attached to one CO
                           "Cou",                    // A CO Person Source may be attached to a COU
                           "CoPersonRole",           // A CO Person Source is attached to one CO Person Role
                           "OrgIdentity");           // A CO Person Source is attached to one Org Identity
    
    // Default display field for cake generated views
    var $displayField = "CoPersonSource.id";
    
    // Default ordering for find operations
    var $order = array("CoPersonSource.id");
    
    // Validation rules for table elements
    var $validate = array(
    );
  }
?>