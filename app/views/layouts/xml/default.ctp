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
    $m = "";
    
    // Invalid fields

    if(isset($invalid_fields))
    {
      foreach(array_keys($invalid_fields) as $f)
      {
        $m .= $this->Xml->elem("InvalidField",
                                array("Field" => $f),
                                $invalid_fields[$f])
        . "\n";
      }
      
      $m = $this->Xml->elem("InvalidFields",
                            array(),
                            $m) . "\n";
    }

    if(isset($memberships))
    {
      foreach(array_keys($memberships) as $f)
      {
        $m .= $this->Xml->elem("Membership",
                                array("Id" => $f),
                                $memberships[$f])
        . "\n";
      }
      
      $m = $this->Xml->elem("Memberships",
                            array(),
                            $m) . "\n";      
    }

    echo $this->Xml->header() . "\n";
    echo $this->Xml->elem("ErrorResponse",
                          array("Version" => "1.0"),
                          $m) . "\n";
  }
  
  echo $content_for_layout;
?>