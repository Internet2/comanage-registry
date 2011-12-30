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
  
  // Group hierarchy separator. XXX This should probably be moved elsewhere.
  global $group_sep;
  $group_sep = ":";

  function find_ef_attribute($attrs, $attr, $type=null)
  {
    // Find an attribute within an array, specifically intended for working with
    // Enrollment Flow Attributes.
    //
    // Parameters:
    // - attrs: An indexed array (ie: [0], [1], [2], etc) of CMP Enrollment Flow Attributes
    // - attr: The attribute to search for (ie: $attrs[#]['attribute'])
    // - type: The type to search for (ie: $attrs[#]['type'])
    //
    // Preconditions:
    //     None
    //
    // Postconditions:
    //     None
    //
    // Returns:
    // - An array equivalent to $attrs[#] matching $attr and $type, with an additional
    //   field of '_index' corresponding to the position (#) the match was found at;
    //   or false if not found
    
    foreach(array_keys($attrs) as $k)
    {
      if($attrs[$k]['attribute'] == $attr)
      {
        if(!defined($type)
           || (defined($attrs[$k]['type'])
               && $attrs[$k]['type'] == $type))
        {
          $ret = $attrs[$k];
          $ret['_index'] = $k;
          return($ret);
        }
      }
    }
    
    return(false);
  }
        
  function generateCn($name, $showHonorific = false)
  {
    // Assemble a common name from the array $name.
    //
    // Parameters:
    // - name: An array containing the attributes of a Name object
    // - showHonorific: will return honorific as part of name when true
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
    
    // Does not show honorific by default
    if( $showHonorific && ($name['honorific'] != "") )
      $cn .= ($cn != "" ? ' ' : '') . $name['honorific'];
    
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
  
  function _jtxt($str)
  {
    // Escape a string so it is suitable for echoing into Javascript function parameters.
    // Specifically, quotes are replaced with XML representations.
    //
    // Parameters:
    // - str: String to be escaped
    //
    // Preconditions:
    //     None
    //
    // Postconditions:
    //     None
    //
    // Returns:
    // - The escaped string
    
    return(str_replace(array("'", '"'), array('&apos;', '&quot;'), $str));
  }
?>
