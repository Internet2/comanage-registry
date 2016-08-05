<?php
/**
 * COmanage Registry Normalization Behavior
 *
 * Copyright (C) 2014-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class NormalizationBehavior extends ModelBehavior {
  /**
   * Handle provisioning following (before) save of Model.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Model $model Model instance.
   * @return boolean true on success, false on failure
   * @throws RuntimeException
   */
  
  public function beforeSave(Model $model, $options = array()) {
    $mname = $model->name;
    
    // First look for a CO ID. If we don't find one, we're probably dealing with
    // org identity data, which normalizations don't currently support.
    $coId = null;
    
    try {
      if(!empty($model->data[$mname]['id'])) {
        // Edit operation, we can use the model's ID
        $coId = $model->findCoForRecord($model->data[$mname]['id']);
      } else {
        // Add operation, we need to find the CO via the appropriate belongsTo model
        
        if(!empty($model->data[$mname]['co_person_id'])) {
          $coId = $model->CoPerson->findCoForRecord($model->data[$mname]['co_person_id']);
        } elseif(!empty($model->data[$mname]['co_person_role_id'])) {
          $coId = $model->CoPersonRole->findCoForRecord($model->data[$mname]['co_person_role_id']);
        }
      }
    }
    catch(Exception $e) {
      // Ignore any error, which is probably about being unable to find a CO
      return true;
    }
    
    if($coId) {
      // Try to find the CoSetting model
      
      $CoSetting = null;
      
      if(isset($model->CoPerson->Co->CoSetting)) {
        $CoSetting = $model->CoPerson->Co->CoSetting;
      } elseif(isset($model->CoPersonRole->CoPerson->Co->CoSetting)) {
        $CoSetting = $model->CoPersonRole->CoPerson->Co->CoSetting;
      }
      
      // Check to see if normalizations are enabled
      if(!$CoSetting
         || !$CoSetting->normalizationsEnabled($coId)) {
        // Not enabled, just return true
        return true;
      }
    }
    
    // Load any plugins and figure out which (if any) have foreign keys to belongTo this model
    
    foreach(App::objects('plugin') as $p) {
      $pluginModel = ClassRegistry::init($p . "." . $p);
      
      if($pluginModel->isPlugin('normalizer')) {
        try {
          $model->data = $pluginModel->normalize($model->data);
        }
        catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    return true;
  }
}