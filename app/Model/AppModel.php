<?php
/**
 * Application level Model
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
  public function beforeDelete($cascade = true) {
    if($cascade) {
      // Load any plugins and figure out which (if any) have foreign keys to belongTo this model
      
      foreach(App::objects('plugin') as $p) {
        $pluginModel = ClassRegistry::init($p . "." . $p);
        
        if(!empty($pluginModel->cmPluginHasMany)
           && !empty($pluginModel->cmPluginHasMany[ $this->name ])) {
          foreach($pluginModel->cmPluginHasMany[ $this->name ] as $fkModel) {
            $assoc = array();
            $assoc['hasMany'][ $fkModel ] = array(
              'className' => $fkModel,
              'dependent' => true
            );
            
            $this->bindModel($assoc, false);
          }
        }
      }
    }
    
    return true;
  }
  
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
    
    if(isset($this->validate['type']['content']['rule'])
       && is_array($this->validate['type']['content']['rule'])
       && $this->validate['type']['content']['rule'][0] == 'validateExtendedType'
       && is_array($this->validate['type']['content']['rule'][1])
       && isset($this->validate['type']['content']['rule'][1]['default'])) {
      // Figure out which language key to use. Note 'en' is the prefix for 'enum'
      // and NOT an abbreviation for 'english'.
      $langKey = 'en.' . Inflector::underscore($this->name);
      
      foreach($this->validate['type']['content']['rule'][1]['default'] as $name) {
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
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v0.8
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {
    if($this->alias == 'CakeError') return;
    
    // We need to find a corresponding CO ID, which may or may not be directly in the model.
    
    if(isset($this->validate['co_id'])) {
      // This model directly references a CO
      
      return $this->field('co_id', array($this->alias.".id" => $id));
    } elseif(isset($this->validate['co_person_id'])) {
      // Find the CO via the CO Person
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoPerson';
    
      $cop = $this->find('first', $args);
      
      if(!empty($cop['CoPerson']['co_id'])) {
        return $cop['CoPerson']['co_id'];
      }
      
      // Is this an MVPA where this is an org identity?
      
      if(empty($cop[ $this->alias ]['co_person_id'])
         && !empty($cop[ $this->alias ]['org_identity_id'])) {
        return null;
      }
    } elseif(isset($this->validate['co_person_role_id'])) {
      // Find the CO via the CO Person via the CO Person Role
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoPersonRole';
    
      $copr = $this->find('first', $args);
      
      if(!empty($copr['CoPersonRole']['co_person_id'])) {
        $args = array();
        $args['conditions']['CoPersonRole.co_person_id'] = $copr['CoPersonRole']['co_person_id'];
        $args['contain'][] = 'CoPerson';
        
        $cop = $this->CoPersonRole->find('first', $args);
        
        if(!empty($cop['CoPerson']['co_id'])) {
          return $cop['CoPerson']['co_id'];
        }
      }
      
      // Is this an MVPA where this is an org identity?
      
      if(empty($copr[ $this->alias ]['co_person_id'])
         && !empty($copr[ $this->alias ]['org_identity_id'])) {
        return null;
      }
    } elseif(isset($this->validate['co_provisioning_target_id'])) {
      // Provisioning plugins will refer to a provisioning target
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoProvisioningTarget';
    
      $copt = $this->find('first', $args);
      
      if(!empty($copt['CoProvisioningTarget']['co_id'])) {
        return $copt['CoProvisioningTarget']['co_id'];
      }
    } elseif(isset($this->validate['subject_co_person_id'])) {
      // Notifications will reference a subject CO Person
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'SubjectCoPerson';
      
      $cop = $this->find('first', $args);
      
      if(!empty($cop['SubjectCoPerson']['co_id'])) {
        return $cop['SubjectCoPerson']['co_id'];
      }
    } else {
      throw new LogicException(_txt('er.co.fail'));
    }
    
    throw new RuntimeException(_txt('er.co.fail'));
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
  
  public function findForUpdate($conditions, $fields, $joins = array()) {
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
   * @param  Integer CO ID
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
  
  public function validateExtendedType($a, $d) {
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
   * Determine if a string represents a defined/supported language. This function
   * is intended to be used as a validation rule.
   *
   * @since  COmanage Registry v0.8.2
   * @param  array Array of fields to validate
   * @return boolean True if all field strings parses to a valid timestamp, false otherwise
   */
  
  public function validateLanguage($a) {
    global $cm_lang, $cm_texts;
    
    foreach(array_keys($a) as $f) {
      if(!isset($cm_texts[ $cm_lang ]['en.language'][ $a[$f] ])) {
        return false;
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
  
  public function validateTimestamp($a) {
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
    
    if(isset($this->validate[$field]['content']['rule'])
       && $this->validate[$field]['content']['rule'][0] == 'inList'
       && isset($this->validate[$field]['content']['rule'][1])) {
      // This is the list of valid values for this field. Map these to their
      // translated names.
      
      foreach($this->validate[$field]['content']['rule'][1] as $key) {
        $ret[$key] = _txt($this->cm_enum_txt[$field], NULL, $key);
      }
    }
    
    return $ret;
  }
}
