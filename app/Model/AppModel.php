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
   * For models that support Extended Types, obtain the default types.
   *
   * @since  COmanage Registry v0.6
   * @return Array Default types as key/value pair of name and localized display_name
   */
  
  public function defaultTypes() {
    // We currently assume there is only one type and it is called "type". This may
    // not always be true.
    
    $ret = null;
    
    if(isset($this->validate['type']['rule'])
       && is_array($this->validate['type']['rule'])
       && $this->validate['type']['rule'][0] == 'validateExtendedType'
       && is_array($this->validate['type']['rule'][1])
       && isset($this->validate['type']['rule'][1]['default'])) {
      // Figure out which language key to use. Note 'en' is the prefix for 'enum'
      // and NOT an abbreviation for 'english'.
      $langKey = 'en.' . Inflector::underscore($this->name);
      
      foreach($this->validate['type']['rule'][1]['default'] as $name) {
        $ret[$name] = _txt($langKey, null, $name);
      }
    }
    
    return $ret;
  }
  
  /**
   * Filter a model's native attributes from its related models.
   *
   * @since  COmanage Registry v0.7
   * @param  array Data to filter, as provided from a form submission
   * @return array Filtered data
   */

  public function filterModelAttributes($data) {
    $ret = array();
    
    foreach(array_keys($data) as $k) {
      if(isset($this->validate[$k])) {
        $ret[$k] = $data[$k];
      }
    }
    
    return $ret;
  }
  
  /**
   * Filter a model's related models from its native attributes.
   *
   * @since  COmanage Registry v0.7
   * @param  array Data to filter, as provided from a form submission
   * @return array Filtered data
   */

  public function filterRelatedModels($data) {
    $ret = array();
    
    foreach(array_keys($data) as $k) {
      if(isset($this->hasOne[$k])) {
        $ret['hasOne'][$k] = $data[$k];
      } elseif(isset($this->hasMany[$k])) {
        $ret['hasMany'][$k] = $data[$k];
      } elseif(preg_match('/Co[0-9]+PersonExtendedAttribute/', $k)) {
        $ret['extended'][$k] = $data[$k];
      }
    }
    
    return $ret;
  }
  
  /**
   * Perform a find, but using SELECT ... FOR UPDATE syntax. This function should
   * be called within a transaction.
   *
   * @since  COmanage Registry v0.6
   * @param  Array Find conditions in the usual Cake format
   * @param  Array List of fields to retrieve
   * @param  Array Join conditions in the usual Cake format
   * @return Array Result set as returned by Cake fetchAll() or read(), which isn't necessarily the same format as find()
   */
  
  function findForUpdate($conditions, $fields, $joins = array()) {
    $dbc = $this->getDataSource();
    
    $args['conditions'] = $conditions;
    $args['fields'] = $dbc->fields($this, null, $fields);
    $args['table'] = $dbc->fullTableName($this->useTable);
    $args['alias'] = $this->alias;
    // Don't allow joins to be NULL, make it an empty array if not set
    $args['joins'] = ($joins ? $joins : array());
    
    // For the moment, we don't support these for no particular reason
    $args['order'] = null;
    $args['limit'] = null;
    $args['group'] = null;
    
    // Appending to the generated query should be fairly portable.
    
    // We should perhaps be using read() and/or buildQuery() instead.
    
    return $dbc->fetchAll($dbc->buildStatement($args, $this) . " FOR UPDATE", array(), array('cache' => false));
  }
  
  /**
   * For models that support Extended Types, obtain the valid types for the specified CO.
   *
   * @since  COmanage Registry v0.6
   * @param  CO ID
   * @return Array Defined types (including defaults if no extended types) in key/value form suitable for select buttons.
   */
  
  function types($coId) {
    $ret = array();
    
    $CoExtendedType = ClassRegistry::init('CoExtendedType');
    
    $extTypes = $CoExtendedType->active($coId, $this->name, 'all');
    
    if(!empty($extTypes)) {
      foreach($extTypes as $t) {
        $ret[ $t['CoExtendedType']['name'] ] = $t['CoExtendedType']['display_name'];
      }
    } else {
      // Use the default set
      
      $ret = $this->defaultTypes();
    }
    
    return $ret;
  }
  
  /**
   * Determine if a string is a valid extended type.
   *
   * @since  COmanage Registry v0.6
   * @param  array Array of fields to validate
   * @param  array Array with two keys: 'attribute' holding the attribute model name, and 'default' holding an Array of default values (for use if no extended types are defined)
   * @return boolean True if all field strings parses to a valid timestamp, false otherwise
   */
  
  function validateExtendedType($a, $d) {
    // First obtain active extended types, if any.
    
    $extTypes = array();
    
    // We need access to the CO ID to know what types are valid, but we don't have it since
    // $cur_co is attached to the controller and not the model (and we don't directly call
    // validation -- that's done in the core of Cake). As an interim hack, beforeFilter
    // or checkRestPost will set the CO ID for us. However, the better approach (possible with
    // Cake 2.2) is to generate a dynamic validation rule is the controller, using the current
    // CO as an argument. See CO-368.
    
    if(isset($this->coId)) {
      $CoExtendedType = ClassRegistry::init('CoExtendedType');
      
      $extTypes = $CoExtendedType->active($this->coId, $d['attribute']);
    }
    // else some models can be used with Org Identities (ie: MVPA controllers). When used
    // with org identities, we currently don't support extended types.
    
    if(empty($extTypes)) {
      // Use the default values
      
      foreach(array_keys($a) as $f) {
        if(!in_array($a[$f], $d['default'])) {
          return false;
        }
      }
    } else {
      // Check the extended types
      
      foreach(array_keys($a) as $f) {
        if(!isset($extTypes[ $a[$f] ])) {
          return false;
        }
      }
    }
    
    return true;
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
