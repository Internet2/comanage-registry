<?php
  /*
   * COmanage Registry CoNsfDemographic Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
  
  class CoNsfDemographic extends AppModel {
    // Define class name for cake
    var $name = "CoNsfDemographic";
    
    // Association rules from this model to other models
    var $belongsTo = array("CoPerson");
    
    // Default display field for cake generated views
    var $displayField = "co_nsf_demographic";
    
    // Default ordering for find operations
    var $order = array("CoNsfDemographic.id");
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'status' => 'status_t'
    );

    function encodeOptions($d)
    {
      // Perform translation of encoded attributes for edit
      //
      // Parameters:
      //     d - demographics data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      //     encoded - array with encoded values for race and disability

      $encoded['race'] = implode($d['race']);

      $encoded['disability'] = implode($d['disability']);

      return $encoded;
    }

    function extractOptions($d, $full = false)
    {
      // Perform translation of encoded attributes for edit
      //
      // Parameters:
      //     d - demographics data
      //     full - if true, returns full names in array; by default and if false, returns enum number
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      //     An array containing selected options that were stored in the database.  If $full is true, it will
      //           be an array of the full names.  Otherwise, it will return an array of the single character 
      //           encoded values.

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
?>
