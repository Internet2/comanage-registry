<?php
/**
 * COmanage Registry CoNsfDemographic Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoNsfDemographic extends AppModel {
  // Define class name for cake
  public $name = "CoNsfDemographic";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("CoPerson");
  
  // Default display field for cake generated views
  public $displayField = "co_nsf_demographic";
  
  // Default ordering for find operations
  public $order = array("CoNsfDemographic.id");
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );

  /**
   * Encode demographic attributes for edit.
   *
   * @since  COmanage Registry v0.3
   * @param  Array Demographics data
   * @return Array Encoded values for race and disability
   */
  
  public function encodeOptions($d) {
    if(is_array($d['race']))
      $encoded['race'] = implode($d['race']);
    if(is_array($d['disability']))
      $encoded['disability'] = implode($d['disability']);

    return $encoded;
  }

  /**
   * Extract encoded attributes for edit.
   *
   * @since  COmanage Registry v0.3
   * @param  Array Encoded demographics data
   * @param  boolean If true, returns full names in array; if false, returns enum number
   * @return Array Contains selected options that were stored in the database. If $full is true, it will be an array of the full names. Otherwise, it will return an array of the single character encoded values.
   */
  
  public function extractOptions($d, $full = false) {
    global $cm_lang, $cm_texts;

    // Retrieve all possible options for race
    $raceOptions = $cm_texts[ $cm_lang ]['en.nsf.race'];
    $disabilityOptions = $cm_texts[ $cm_lang ]['en.nsf.disab'];
    
    // Extract selected values for race into array of single characters
    $raceValues = str_split($d['race']);

    // Iterates through string
    if($full)
    {
      foreach($raceValues as $c)
        $val['race'][] = $raceOptions[$c];
    }
    else
    {
      foreach($raceValues as $c)
        $val['race'][] = $c;
    }

    // Extract values for disability into array of single characters
    $disValues = str_split($d['disability']);

    // Iterates through string

    if($full)
      foreach($disValues as $c)
        $val['disability'][] = $disabilityOptions[$c];
    else
      foreach($disValues as $c)
        $val['disability'][] = $c;

    return $val;
  }
}
