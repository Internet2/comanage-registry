<?php
/**
 * COmanage Registry Normalization Behavior
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
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class NormalizationBehavior extends ModelBehavior {
  /**
   * Handle normalization following (before) save of Model.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Model $model Model instance
   * @return boolean true on success
   * @throws RuntimeException
   */
  
  public function beforeSave(Model $model, $options = array()) {
    $model->data = $this->normalize($model, $model->data);
    
    return true;
  }
  
  /* Run normalizations on requested data. The data can be any supported data for
   * normalization, it need not correlate to $model (unless $coId is false).
   * 
   * @since  COmanage Registry v2.0.0
   * @param  Model $model Model instance
   * @param  Array $data Data to normalize in usual format; need not belong to $model
   * @param  Integer $coId CO ID data belongs to, or null for Org Identity data, or false to determine from $data ($data must then belong to $model)
   * @return boolean true on success
   * @throws RuntimeException
   */
  
  public function normalize(Model $model, $data, $coId = false) {
    $mname = $model->name;
    
    // If $coId is false, look for a CO ID. If we don't find one or if $coId is null,
    // we're dealing with org identity data, which normalizations don't currently support.
    
    if($coId === false) {
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
        return $data;
      }
    }
    
    if($coId) {
      // Try to find the CoSetting model
      
      $CoSetting = null;
      
      if(isset($model->CoPerson->Co->CoSetting)) {
        $CoSetting = $model->CoPerson->Co->CoSetting;
      } elseif(isset($model->CoPersonRole->CoPerson->Co->CoSetting)) {
        $CoSetting = $model->CoPersonRole->CoPerson->Co->CoSetting;
      } elseif(isset($model->Co->CoSetting)) {
        $CoSetting = $model->Co->CoSetting;
      }
      
      // Check to see if normalizations are enabled
      if(!$CoSetting
         || !$CoSetting->normalizationsEnabled($coId)) {
        // Not enabled, just return
        return $data;
      }
    } else {
      // We currently don't support normalizations on OrgIdentity data
      
      return $data;
    }
    
    // Load any plugins and figure out which (if any) have foreign keys to belongTo this model
    
    foreach(App::objects('plugin') as $p) {
      $pluginModel = ClassRegistry::init($p . "." . $p);
      
      if($pluginModel->isPlugin('normalizer')) {
        try {
          $data = $pluginModel->normalize($data);
        }
        catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    return $data;
  }
}