<?php
  /*
   * COmanage Gears Multi-Value Person Attribute (MVPA) Controller
   * Parent for Controllers that implement multi-valued person attributes
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

  include APP."controllers/standard_controller.php";

  class MVPAController extends StandardController {
    // MVPAs require a Person ID (CO or Org)
    var $requires_person = true;
    
    // We need to increase recursion because CoPerson/OrgPerson doesn't
    // define name, and we need it to render the columns.
    var $edit_recursion = 2;
    var $view_recursion = 2;
  }
?>