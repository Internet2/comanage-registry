<?php
/**
 * COmanage Registry Utilities
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Group hierarchy separator. XXX This should probably be moved elsewhere and made a constant
global $group_sep;
$group_sep = ":";

// Default invitation validity, in minutes (used in various places, should probably be moved elsewhere)
define("DEF_INV_VALIDITY", 1440);

// Default window for reprovisioning on group validity change
define("DEF_GROUP_SYNC_WINDOW", 1440);

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
 * Format an address into a single string.
 *
 * @since  COmanage Registry v2.0.0
 * @param  Array $addr Array of Address attributes
 * @return string The formatted address
 * @todo This is an incredibly simplistic initial implementation that is not locale aware
 */

function formatAddress($addr) {
  $a = "";
  
  if(!empty($addr['street'])) {
    $a = $addr['street'];
  }
  
  if(!empty($addr['room'])) {
    if($a != "") { $a .= ", "; }
    $a .= $addr['room'];
  }
  
  if(!empty($addr['locality'])) {
    if($a != "") { $a .= ", "; }
    $a .= $addr['locality'];
  }
  
  if(!empty($addr['state'])) {
    if($a != "") { $a .= ", "; }
    $a .= $addr['state'];
  }
  
  if(!empty($addr['postal_code'])) {
    if($a != "") { $a .= ", "; }
    $a .= $addr['postal_code'];
  }
  
  if(!empty($addr['country'])) {
    if($a != "") { $a .= ", "; }
    $a .= $addr['country'];
  }
  
  return $a;
}

/**
 * Render a telephone number in E.123 format
 *
 * @since  COmanage Registry v0.9.4
 * @param  Array $phone Array of TelephoneNumber attributes
 * @return string The formatted telephone number
 */

function formatTelephone($phone) {
  $n = "";
  
  if(!empty($phone['country_code'])) {
    // We'll only output + style if a country code was provided
    $n = "+" . $phone['country_code'];
  }
  
  if(!empty($phone['area_code'])) {
    if($n != "") {
      $n .= " ";
    }
    
    $n .= $phone['area_code'];
  }
  
  if(!empty($phone['number'])) {
    if($n != "") {
      $n .= " ";
    }
    
    $n .= $phone['number'];
  }
  
  if(!empty($phone['extension'])) {
    if($n != "") {
      $n .= " " . _txt('fd.telephone.ext');
    }
    
    $n .= $phone['extension'];
  }
  
  return $n;
}

/**
 * Assemble a common name from the array $name.
 *
 * @since  COmanage Registry v0.1
 * @param  array An array containing the attributes of a Name object
 * @param  boolean If true return honorific as part of name
 * @return string The assembled name
 */

function generateCn($name, $showHonorific = false) {
  // Name order is a bit tricky. We'll use the language encoding as our hint, although
  // it isn't perfect. This could be replaced with a more sophisticated test as
  // requirements evolve.
  
  $cn = "";
  
  if(empty($name['language'])
     || !in_array($name['language'], array('hu', 'ja', 'ko', 'za-Hans', 'za-Hant'))) {
    // Western order. Do not show honorific by default.
    
    if($showHonorific && !empty($name['honorific'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['honorific'];
    }
    
    if(!empty($name['given'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['given'];
    }
    
    if(!empty($name['middle'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['middle'];
    }
    
    if(!empty($name['family'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['family'];
    }
    
    if(!empty($name['suffix'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['suffix'];
    }
  } else {
    // Switch to Eastern order. It's not clear what to do with some components.
    
    if(!empty($name['family'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['family'];
    }
    
    if(!empty($name['given'])) {
      $cn .= ($cn != "" ? ' ' : '') . $name['given'];
    }
  }
  
  return $cn;
}

/**
 * Obtain the preferred language requested by the browser, if supported.
 *
 * @since  COmanage Registry v0.8.2
 * @return string Language code, or an empty string
 */

function getPreferredLanguage() {
  $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  
  if($lang == 'zh') {
    // For the Chinese scripts, determine traditional vs simplified.
    // First map old style notation to new style.
    
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);
    
    if($lang == 'zh-CN') {
      return 'zh-Hans';
    }
    if($lang == 'zh-TW') {
      return 'zh-Hant';
    }
    
    // Still here? Maybe it's new style.
    
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 7);
    
    if($lang == 'zh-Hans' || $lang == 'zh-Hant') {
      return $lang;
    }
    
    // Else we don't know what to do with this Chinese variant. Go with simplified.
    
    return 'zh-Hans';
  }
  
  // See if this is a defined language.
  
  global $cm_lang, $cm_texts;

  if(isset($cm_texts[ $cm_lang ]['en.lang'][ $lang ])) {
    return $lang;
  }
  
  // We don't recognize this language
  
  return "";
}

/**
 * Process a message template, replacing parameters with respective values.
 * Note this function is for configured templates (ie: those loaded from the
 * database) and not for Cake templates (ie: those loaded from View/Emails).
 *
 * The substitutions array should include key/value pairs for each value
 * to replace. For example array('CO_NAME' => 'MyCO') will replace @CO_NAME
 * with "MyCO".
 *
 * @since  COmanage Registry v0.9
 * @param  String Template text
 * @param  Array Array of substitution parameters
 * @param  Array Array of identifiers (complex format, as returned by find) to use for identifier substitutions
 * @return String Processed template
 */

function processTemplate($template, $substitutions, $identifiers=array()) {
  $searchKeys = array();
  $replaceVals = array();
  
  $subs = $substitutions;
  
  // If any identifiers were provided, process the appropriate substitutions
  if($identifiers) {
    // Identifier substitutions are of the form (@IDENTIFER:type)
    foreach($identifiers as $i) {
      if($i['status'] == SuspendableStatusEnum::Active) {
        $t = 'IDENTIFIER:'.$i['type'];
        
        if(!empty($subs[$t])) {
          $subs[$t] .= "," . $i['identifier'];
        } else {
          $subs[$t] = $i['identifier'];
        }
      }
    }
  }
  
  foreach(array_keys($subs) as $k) {
    $searchKeys[] = "(@" . $k . ")";
    $replaceVals[] = $subs[$k];
  }
  
  return str_replace($searchKeys, $replaceVals, $template);
}

/**
 * Retrieve menu links for plugin-defined menu items.
 * - postcondition: HTML emitted
 *
 * @since  COmanage Registry v3.2.0
 * @param  Array   $plugins Array of plugins as created by AppController
 * @param  String  $context Which menu items to render
 * @param  Integer $coId    CO ID
 * @return Array            Array of menu labels and their URL information
 */

function retrieve_plugin_menus($plugins, $menu, $coId=null) {
  $ret = array();
 
  if(!empty($plugins)) {
    foreach(array_keys($plugins) as $plugin) {
      if(isset($plugins[$plugin][$menu])) {
        foreach(array_keys($plugins[$plugin][$menu]) as $label) {
          $args = $plugins[$plugin][$menu][$label];
          
          if(is_array($args)) {
            $args['plugin'] = Inflector::underscore($plugin);
            
            if(!empty($coId)){
              $args['co'] = $coId;
            }
          }
          
          // Migrate 'icon' to its own key
          if(!empty($args['icon'])) {
            $ret[$label]['icon'] = $args['icon'];
            unset($args['icon']);
          }
          
          $ret[$label]['url'] = $args;
        }
      }
    }
  }
  
  return $ret;
}

/**
 * Escape a string so it is suitable for echoing into Javascript function parameters.
 * Specifically, quotes are double escaped for correct round-trip rendering.
 *
 * @since  COmanage Registry v0.1
 * @param  string String to be escaped
 * @return string Escaped string
 */

function _jtxt($str) {
  return(str_replace(array("'", '"'), array('\\x27', '\\x22'), $str));
}
