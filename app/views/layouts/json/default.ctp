<?php
  /*
   * COmanage Gears JSON View Template
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010 University Corporation for Advanced Internet Development, Inc.
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

  if(isset($invalid_fields) || isset($memberships))
  {
    // Invalid fields
    
    $a = array("ResponseType" => "ErrorResponse",
               "Version" => "1.0");
    
    if(isset($invalid_fields))
      $a['InvalidFields'] = $invalid_fields;
    
    if(isset($memberships))
      $a['Memberships'] = $memberships;
    
    echo json_encode($a) . "\n";
  }
  
  echo $content_for_layout;
?>