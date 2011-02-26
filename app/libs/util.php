<?php
  /*
   * COmanage Gears Utilities
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

  function generateCn($name)
  {
    // Assemble a common name from the array $name.
    //
    // Parameters:
    // - name: An array containing the attributes of a Name object
    //
    // Preconditions:
    //     None
    //
    // Postconditions:
    //     None
    //
    // Returns:
    // - The assembled name
    
    // XXX need international name order checking (Given FAMILY vs FAMILY Given)

    $cn = "";
    
    // We ignore Honorific, but maybe we should offer an option to include
    
    if($name['given'] != "")
      $cn .= ($cn != "" ? ' ' : '') . $name['given'];
    
    if($name['middle'] != "")
      $cn .= ($cn != "" ? ' ' : '') . $name['middle'];
    
    if($name['family'] != "")
      $cn .= ($cn != "" ? ' ' : '') . $name['family'];
    
    if($name['suffix'] != "")
      $cn .= ($cn != "" ? ' ' : '') . $name['suffix'];
            
    return($cn);
  }    
?>