<?php
/**
 * COmanage Registry Utilities
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Group hierarchy separator. XXX This should probably be moved elsewhere.
global $group_sep;
$group_sep = ":";

/**
 * Find an attribute within an array, specifically intended for working with
 * Enrollment Flow Attributes.
 *
 * @since  COmanage Registry v0.1
 * @param  array An indexed array (ie: [0], [1], [2], etc) of CMP Enrollment Flow Attributes
 * @param  string The attribute to search for (ie: $attrs[#]['attribute'])
 * @param  string The type to search for (ie: $attrs[#]['type'])
 * @return array An array equivalent to $attrs[#] matching $attr and $type, with an additional field of '_index' corresponding to the position (#) the match was found at; or false if not found
 */

function find_ef_attribute($attrs, $attr, $type=null)
{
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

/**
 * Assemble a common name from the array $name.
 *
 * @since  COmanage Registry v0.1
 * @param  array An array containing the attributes of a Name object
 * @param  boolean If true return honorific as part of name
 * @return string The assembled name
 */

function generateCn($name, $showHonorific = false)
{
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

/**
 * Escape a string so it is suitable for echoing into Javascript function parameters.
 * Specifically, quotes are replaced with XML representations.
 *
 * @since  COmanage Registry v0.1
 * @param  string String to be escaped
 * @return string Escaped string
 */

function _jtxt($str)
{
  return(str_replace(array("'", '"'), array('&apos;', '&quot;'), $str));
}
