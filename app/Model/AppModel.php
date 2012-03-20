<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       registry
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * This is a placeholder class.
 * Create the same file in app/Model/AppModel.php
 * Add your application-wide methods to the class, your models will inherit them.
 *
 * @package       registry
 */
class AppModel extends Model {
  /**
   * Filter a model's related data according, removing optional related models that
   * weren't provided, and pulling the model up a level to prepare it for save().
   *
   * @since  COmanage Registry v0.5
   * @param  array Data to filter, as provided from a form submission
   * @param  array List of attributes and required status, as obtained by CoEnrollmentAttribute->find("list", (id, required))
   * @return array Filtered data
   */
  
  function filterRelatedModel($data, $reqAttrs) {
    $primaryModel = $this->alias;
    
    // hasone relations do not have an index ($data[$primarymodel][$model][$attribute])
    foreach(array_keys($this->hasOne) as $eAttrName) {
      if(isset($data[$primaryModel][$eAttrName])) {
        if(isset($data[$primaryModel][$eAttrName]['co_enrollment_attribute_id'])) {
          // Extended Attributes won't have an enrollment attribute id set. Even though
          // they show up as hasOne, they really behave like part of the primary model,
          // so we don't need to do this check.
          
          $allRequiredEmpty = true;
          $eAttrId = $data[$primaryModel][$eAttrName]['co_enrollment_attribute_id'];
          
          if(!$reqAttrs[$eAttrId]) {
            // Optional attribute according to the enrollment flow configuration.
            // Walk through and see if required attributes present. If all required
            // attributes are empty, consider the attribute to be not provided for
            // validation purposes. (If any was provided, consider the attribute to
            // be provided, and let validation determine if everything is OK.)
            
            foreach(array_keys($this->$eAttrName->validate) as $eAttrField) {
              // We check both required and notEmpty, since we're currently transitioning from the former
              // to the latter. We skip 'type' since it's a required field and the petition-generated
              // forms generally provide it, but it's not an indication if a field is actually being
              // filled in.
              
              if($eAttrField == 'type')
                continue;
              
              if((isset($this->$eAttrName->validate[$eAttrField]['required'])
                  && $this->$eAttrName->validate[$eAttrField]['required'])
                 ||
                 (isset($this->$eAttrName->validate[$eAttrField]['allowEmpty'])
                  && !$this->$eAttrName->validate[$eAttrField]['allowEmpty'])) {
                // $eAttrName:$eAttrField is required according to the model
                
                if(isset($data[$primaryModel][$eAttrName][$eAttrField])
                   && $data[$primaryModel][$eAttrName][$eAttrField] != "") {
                  $allRequiredEmpty = false;
                  
                  // Our work here is done
                  break;
                }
              }
            }
            
            if($allRequiredEmpty) {
              // Pretend this attribute wasn't provided
              
              unset($data[$primaryModel][$eAttrName]);
            }
          }
        }
        
        if(isset($data[$primaryModel][$eAttrName])) {
          // Promote
          $data[$eAttrName] = $data[$primaryModel][$eAttrName];
          unset($data[$primaryModel][$eAttrName]);
        }
      }
    }
    
    // hasmany relations are keyed with an index ($data[$primarymodel][$model][$index][$attribute])
    foreach(array_keys($this->hasMany) as $eAttrName) {
      if(isset($data[$primaryModel][$eAttrName])) {
        foreach($data[$primaryModel][$eAttrName] as $eAttrData) {
          $allRequiredEmpty = true;
          $eAttrId = $eAttrData['co_enrollment_attribute_id'];
          
          if(!$reqAttrs[$eAttrId]) {
            // Optional attribute according to the enrollment flow configuration.
            // Walk through and see if required attributes present. If all required
            // attributes are empty, consider the attribute to be not provided for
            // validation purposes. (If any was provided, consider the attribute to
            // be provided, and let validation determine if everything is OK.)
            
            foreach(array_keys($this->$eAttrName->validate) as $eAttrField) {
              // We check both required and notEmpty, since we're currently transitioning from the former
              // to the latter. We skip 'type' since it's a required field and the petition-generated
              // forms generally provide it, but it's not an indication if a field is actually being
              // filled in.
              
              if($eAttrField == 'type')
                continue;
              
              if((isset($this->$eAttrName->validate[$eAttrField]['required'])
                  && $this->$eAttrName->validate[$eAttrField]['required'])
                 ||
                 (isset($this->$eAttrName->validate[$eAttrField]['allowEmpty'])
                  && !$this->$eAttrName->validate[$eAttrField]['allowEmpty'])) {
                // $eAttrName:$eAttrField is required according to the model
                
                if(isset($data[$primaryModel][$eAttrName][$eAttrId][$eAttrField])
                   && $data[$primaryModel][$eAttrName][$eAttrId][$eAttrField] != "") {
                  $allRequiredEmpty = false;
                  
                  // Our work here is done
                  break;
                }
              }
            }
            
            if($allRequiredEmpty) {
              // Pretend this attribute wasn't provided
              
              unset($data[$primaryModel][$eAttrName][$eAttrId]);
            }
          }
        }
        
        // If there are any remaining attribute IDs, pull them up a level
        
        if(count(array_keys($data[$primaryModel][$eAttrName])) > 0) {
          $data[$eAttrName] = $data[$primaryModel][$eAttrName];
        }
        
        // Clean up
        unset($data[$primaryModel][$eAttrName]);
      }
    }
    
    return($data);
  }
  
  /**
   * Determine if a string represents a valid timestamp. This function is intended
   * to be used as a validation rule.
   *
   * @since  COmanage Registry v0.5
   * @param  array Array of fields to validate
   * @return boolean True if all field strings parses to a valid timestamp, false otherwise
   */
  
  function validateTimestamp($a) {
    // Note we are assuming the >= PHP 5.1 behavior of strtotime here, which is
    // reasonable since we require >= PHP 5.2.
    
    foreach(array_keys($a) as $f) {
      if(strtotime($a[$f]) === false)
        return false;
    }
    
    return true;
  }
  
  /**
   * Generate an array mapping the valid enums for a field to their language-specific
   * strings, in a form suitable for an HTML select.
   *
   * @since  COmanage Registry v0.5
   * @param  string Name of field within model, as known to $validates
   * @return array Array suitable for generating a select via FormHelper
   */
  
  function validEnumsForSelect($field) {
    $ret = array();
    
    if(isset($this->validate[$field]['rule'])
       && $this->validate[$field]['rule'][0] == 'inList'
       && isset($this->validate[$field]['rule'][1])) {
      // This is the list of valid values for this field. Map these to their
      // translated names.
      
      foreach($this->validate[$field]['rule'][1] as $key) {
        $ret[$key] = _txt($this->cm_enum_txt[$field], NULL, $key);
      }
    }
    
    return $ret;
  }
}
