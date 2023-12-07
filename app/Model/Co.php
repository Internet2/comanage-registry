<?php
/**
 * COmanage Registry CO Model
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
  
class Co extends AppModel {
  // Define class name for cake
  public $name = "Co";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models.
  // Models here should be listed in reverse dependency order. ie: for a delete,
  // the models at the end of the dependency tree need to be deleted first.
  // Note when adding configuration related models they may need to be added
  // to duplicate(), below.
  public $hasMany = array(
    "ApiUser" => array('dependent' => true),
    "AttributeEnumeration" => array('dependent' => true),
    "Authenticator" => array('dependent' => true),
    "Cluster" => array('dependent' => true),
    // A CO can have zero or more provisioning targets
    "CoProvisioningTarget" => array('dependent' => true),
    "CoService" => array('dependent' => true),
    "CoDashboard" => array('dependent' => true),
    "CoEmailList" => array('dependent' => true),
    // A CO has zero or more enrollment flows
    "CoEnrollmentFlow" => array('dependent' => true),
    "CoExpirationPolicy" => array('dependent' => true),
    // A CO has zero or more extended attributes
    "CoExtendedAttribute" => array('dependent' => true),
    "CoExtendedType" => array('dependent' => true),
    // A CO has zero or more groups
    "CoIdentifierAssignment" => array('dependent' => true),
    "CoIdentifierValidator" => array('dependent' => true),
    "CoLocalization" => array('dependent' => true),
    "CoMessageTemplate" => array('dependent' => true),
    "CoNavigationLink" => array('dependent' => true),
    // A CO can have zero or more CO people
    "CoPerson" => array('dependent' => true),
    // A CO can have zero or more petitions
    "CoPetition" => array('dependent' => true),
    "CoSelfServicePermission" => array('dependent' => true),
    "CoTermsAndConditions" => array('dependent' => true),
    "CoTheme" => array('dependent' => true),
    // A CO has zero or more OrgIdentities, depending on if they are pooled.
    // It's OK to make the model dependent, because if they are pooled the
    // link won't be there to delete.
    "DataFilter" => array('dependent' => true),
    "Dictionary" => array('dependent' => true),
    "Organization" => array('dependent' => true),
    "OrgIdentity" => array('dependent' => true),
    "OrgIdentitySource" => array('dependent' => true),
    "CoPipeline" => array('dependent' => true),
    // A CO has zero or more COUs
    // XXX A COU has dependent Groups. Delete the COUs before the Groups
    // XXX A COU has dependent Departments. Delete the COUs before the Departments
    "Cou" => array('dependent' => true),
    "CoDepartment" => array('dependent' => true),
    "CoGroup" => array('dependent' => true),
    "Server" => array('dependent' => true),
    "VettingStep" => array('dependent' => true),
    "Lock" => array('dependent' => true),
    "CoJob" => array('dependent' => true),
  );
  
  public $hasOne = array(
    "CoSetting" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  public $order = array("Co.name");
  
  // Validation rules for table elements
  public $validate = array(
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(TemplateableStatusEnum::Active,
                                      TemplateableStatusEnum::Suspended,
                                      TemplateableStatusEnum::Template,
                                      TemplateableStatusEnum::InTrash)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'TemplateableStatusEnum'
  );
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created && !empty($this->data['Co']['id'])) {
      // Run setup for new CO
      
      $this->setup($this->data['Co']['id']);
    }
    
    return true;
  }
  
  /**
   * Determine the CO ID for the COmanage CO.
   *
   * @since  COmanage Registry v4.0.0
   * @return int The COmanage CO ID
   */
  
  public function getCOmanageCOID() {
    return $this->field('id', array('Co.name' => DEF_COMANAGE_CO_NAME));
  }

  /**
   * Determine if a CO is read only.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer $id CO  ID
   * @return True if the CO is read only, false otherwise
   * @throws InvalidArgumentException
   */

  public function readOnly($id) {
    // A CO is read only if the status field is 'TR'.

    // Refer to CO-2385
    try{
      return (bool)$this->inTrash($id);
    } catch (InvalidArgumentException $e) {
      return false;
    }

  }

  /**
   * Determine if a CO is in Trash
   *
   * @since  COmanage Registry  v4.0.0
   * @param  Integer $id    Job ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */

  public function inTrash($id) {
    $curStatus = $this->field('status', array('Co.id' => $id));

    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.cos.1'), $id)));
    }

    return ($curStatus == TemplateableStatusEnum::InTrash);
  }


  
  /**
   * Delete a CO.
   *
   * @since  COmanage Registry v3.2.0
   * @param  int  $id      CO ID
   * @param  bool $cascade Whether to cascade the delete to related models
   * @return bool          True on success, false otherwise
   */
  
  public function delete($id = NULL, $cascade = true) {
    // Completely wiping a CO requires special handing.
    // (Unclear what we would do if $cascade is false or $id is NULL...)
    
    if(!$id || !$cascade) {
      return false;
    }
    
    // Cake doesn't always successfully figure out our dependencies, and we're
    // constrained in how we can provide hints. (eg: We can order within $hasMany,
    // but not across $hasOne and $hasMany.) As a hack, we clear out things that
    // we know have caused problems.
    
    // Unset foreign keys from CO Settings
    $this->CoSetting->updateAll(
      array(
        'CoSetting.sponsor_co_group_id' => null,
        'CoSetting.default_co_pipeline_id' => null,
        'CoSetting.co_theme_id' => null,
        'CoSetting.co_dashboard_id' => null
      ),
      array('CoSetting.co_id' => $id)
    );
    
    // Trigger the delete of models associated with plugins before we clean up
    // data structures they might point to
    foreach(App::objects('plugin') as $p) {
      $pluginModel = ClassRegistry::init($p . "." . $p);

      // Check if the plugin has explicitly listed relationships
      if (!empty($pluginModel->cmPluginHasMany)
        && !empty($pluginModel->cmPluginHasMany[$this->name])) {
        foreach ($pluginModel->cmPluginHasMany[$this->name] as $fkModel => $acfg) {
          $updateModel = null;
          if (is_array($acfg)) {
            // Use the plugin's association settings

            if ($this->id
              && (!isset($acfg['dependent']) || !$acfg['dependent'])) {
              // Clear foreign keys pointing to the current record. We use
              // updateAll since it won't run callbacks.
              $updateModel = ClassRegistry::init($p . "." . $acfg['className']);

              $field = $acfg['className'] . "." . $acfg['foreignKey'];

              $updateModel->updateAll(
                array($field => null),
                array($field => $this->id)
              );
            }
          } else {
            // The model is actually $acfg because of the way PHP handles
            // singletons in an array (ie: 0 => CoAnnouncementChannel)

            $updateModel = ClassRegistry::init($p . "." . $acfg);
          }

          // Reset the Changelog Behavior foreign keys
          if($updateModel->Behaviors->enabled('Changelog')) {
            $updateModel->reloadBehavior('Changelog', array('expunge' => true));
          }

          $updateModel->deleteAll(
            array($updateModel->name . '.co_id' => $id),
            true,
            // We need AppModel::beforeDelete to run so that model dependencies get
            // updated, at the expense of extra callbacks
            true
          );
        }
      }
    }

    
    // Make sure to update this list in duplicate() as well
    foreach(array("CoDashboard", // triggers CoDashboardWidget
                  "Authenticator",
                  "CoProvisioningTarget",
                  "DataFilter",
                  "OrgIdentitySource") as $m) {
      // If set, we use duplicatableModels as a sort of inverse logic for cleanup

      // Reset the Changelog Behavior foreign keys
      if($this->$m->Behaviors->enabled('Changelog')) {
        $this->$m->reloadBehavior('Changelog', array('expunge' => true));
      }

      // Reset all the Changelog Behaviors from hasMany Relations
      foreach($this->$m->hasMany as $modelName => $options) {
        $modelRelation = ClassRegistry::init($modelName);

        if($modelRelation->Behaviors->enabled('Changelog')) {
          $modelRelation->reloadBehavior('Changelog', array('expunge' => true));
        }
      }

      // Load all the Configured plugins and disable the Changelog Behavior config if any
      // XXX For the case of CoDashboardWidget plugins we will call the beforeDelete callback in the Model itself
      $modelPluginTypes = !empty($this->$m->hasManyPlugins)
                          ? array_keys($this->$m->hasManyPlugins)
                          : array();
      foreach($modelPluginTypes as $pluginType) {
        $plugins = $this->loadAvailablePlugins($pluginType);
        foreach($plugins as $pluginName => $plugin) {
          $pluginClassName = $pluginName;
          if(!empty($this->$m->hasManyPlugins)
             && isset($this->$m->hasManyPlugins[$pluginType]['coreModelFormat'])) {
            $corem = sprintf($this->$m->hasManyPlugins[$pluginType]['coreModelFormat'], $plugin->name);
            $pluginClassName = "{$plugin->name}.{$corem}";
          }

          $relation = array('hasMany' => array( $pluginClassName => array('dependent' => true)));

          // Set reset to false so the bindings don't disappear after the first find
          $this->$m->bindModel($relation, false);
          // Unload the Changelog Behavior for all the descendants
          $this->unloadChangelogBehavior($plugin, $pluginClassName);
        }
      }

      $this->$m->deleteAll(
        array($m.'.co_id' => $id),
        true,
        // We need AppModel::beforeDelete to run so that model dependencies get
        // updated, at the expense of extra callbacks
        true
      );
    }

    // We are in the middle of an expunge so disable Changelog behaviors.
    // Skipping callbacks is not enough for a Shell Job
    foreach(App::objects('Model') as $m) {
      try {
        // Not all Models are associated to CO model
        $modell = ClassRegistry::init($m);
        if($modell->Behaviors->enabled('Changelog')) {
          $modell->reloadBehavior('Changelog', array('expunge' => true));
        }
      } catch(Exception $e) {
        // Do nothing
        // App::objects('Model) will fetch every Model, even the abstract ones which we can not
        // initialize. Catch the error and continue to the next one
      }
    }

    // CoGroupNestings have two parent CoGroup pointers, which makes them hard
    // to delete, so we'll delete them manually. They also have a foreign key
    // into co_group_members that needs to be cleared as well. We'll clean up
    // based on the target_co_group_id since that covers both cases.
    
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $id;
    $args['fields'] = array('id', 'name');
    $args['contain'] = false;
    
    $groups = $this->CoGroup->find('list', $args);
    
    if(count($groups) > 0) {
      // We normally don't use updateAll since callbacks aren't fired, but in this
      // case that's fine.
      
      $this->CoGroup->CoGroupMember->updateAll(array('CoGroupMember.co_group_nesting_id' => null),
                                               array('CoGroupMember.co_group_id' => array_keys($groups)));

      // Disable Changelog behaviors. Skipping callbacks is not enough for a Shell Job
      $this->CoGroup->CoGroupNesting->deleteAll(array('CoGroupNesting.co_group_id' => array_keys($groups)));
    }
    
    // Unload TreeBehavior from Cou, since it throws errors, and we don't need to
    // rebalance a tree that we're about to remove entirely.
    $this->Cou->Behaviors->unload('Tree');
    // Delete all parent_id foreign keys since they will generate errors. We do not
    // need them anymore since we are about to delete everything.
    $this->Cou->updateAll(array('Cou.parent_id' => null),
                          array(
                            "Cou.co_id={$id}",
                            "Cou.parent_id IS NOT null"
                          ));

    return parent::delete($id, $cascade);
  }

  /**
   * Duplicate an existing CO.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id CO ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function duplicate($id) {
    // Based on CoEnrollmentFlow::duplicate, but not really the same.
    
    // Because Co itself is not changelog enabled, and because some Models
    // have a more complex relationship than simple parent/child, we pull
    // models more or less one at a time to process them.
    
    // This maintains a per-Model map of old_id -> new_id
    $idmap = array();
    
    // (0) Start a transcation. If we error out, we can rollback.
    $this->_begin();
    
    // (1) First copy the CO itself
    
    $this->duplicateObjects($this, 'id', $id, $idmap);
    
    if(empty($idmap['Co'])) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.cos.1'), $id)));
    }
    
    try {
      // (2) Copy objects with a direct relation to CO
      
      // A number of models reference CoGroups, which in turn might reference Cous,
      // so we copy those first. Furthermore, COUs are trees, and so require special
      // care when copying.
      
      $this->duplicateObjects($this->Cou, 'co_id', $id, $idmap, true);
      
      foreach(array(
        'CoGroup',
        'Dictionary',
        'AttributeEnumeration',
        'Authenticator',
        'CoDashboard',
        'CoExpirationPolicy',
        'CoExtendedAttribute',
        'CoExtendedType',
        'CoIdentifierAssignment',
        'CoIdentifierValidator',
        'CoLocalization',
        'CoMessageTemplate',
        'CoNavigationLink',
        'CoPipeline',
        'CoSelfServicePermission',
        'CoService',
        'CoTermsAndConditions',
        'CoTheme',
        'CoEnrollmentFlow',
        'Server',
        'DataFilter',
        'OrgIdentitySource',
        'CoProvisioningTarget',
        'CoSetting'
      ) as $m) {
        $this->duplicateObjects($this->$m, 'co_id', $id, $idmap);
      }
      
      // (3) Copy objects with a different parent relation
      
      foreach(array(
        'CoDashboardWidget' => 'CoDashboard',
        'CoEnrollmentAttribute' => 'CoEnrollmentFlow',
        'CoEnrollmentSource' => 'CoEnrollmentFlow',
        'CoGroupOisMapping' => 'OrgIdentitySource',
        'CoGroupNesting' => 'CoGroup',
        'HttpServer' => 'Server',
        'MatchServer' => 'Server',
        'Oauth2Server' => 'Server',
        'SqlServer' => 'Server',
        'KafkaServer' => 'Server',
        'DictionaryEntry' => 'Dictionary'
      ) as $m => $parentm) {
        $fk = Inflector::underscore($parentm) . "_id";
        
        // If we don't have any parent objects, we can't have any objects to copy
        if(!empty($idmap[$parentm])) {
          $this->duplicateObjects($this->$parentm->$m, $fk, array_keys($idmap[$parentm]), $idmap);
        }
      }
      
      // (4) Copy objects three levels deep
      
      foreach(array(
        'CoEnrollmentAttributeDefault' => array('CoEnrollmentAttribute' => 'CoEnrollmentFlow')
      ) as $m => $parents) {
        foreach($parents as $parentm => $grandparentm) {
          $fk = Inflector::underscore($parentm) . "_id";
          
          // If we don't have any parent objects, we can't have any objects to copy
          if(!empty($idmap[$parentm])) {
            $this->duplicateObjects($this->$grandparentm->$parentm->$m, $fk, array_keys($idmap[$parentm]), $idmap);
          }
        }
      }
      
      // (5) Copy plugin objects
      
      // Figure out our set of plugins to make dealing with instantiated objects easier.
      // We'll store them by type.
      
      $plugins = array();
      
      foreach(App::objects('plugin') as $p) {
        $m = ClassRegistry::init($p . "." . $p);

        // XXX As of v2.0.0, $cmPluginType may also be an array.
        //     If it is an array, pick up the first
        $pluginType = is_array($m->cmPluginType) ? $m->cmPluginType[0] : $m->cmPluginType;

        $plugins[$pluginType][$p] = $m;
      }
      
      // Make sure to update this list in delete() as well
      foreach(array(
        'Authenticator' => array(
          'type'   => 'authenticator',
          'fk'     => 'authenticator_id',
          'pmodel' => $this->Authenticator
        ),
        'CoDashboardWidget' => array(
          'type'   => 'dashboardwidget',
          'fk'     => 'co_dashboard_widget_id',
          'pmodel' => $this->CoDashboard->CoDashboardWidget
        ),
        'CoProvisioningTarget' => array(
          'type'   => 'provisioner',
          'fk'     => 'co_provisioning_target_id',
          'pmodel' => $this->CoProvisioningTarget
        ),
        'DataFilter' => array(
          'type'   => 'datafilter',
          'fk'     => 'data_filter_id',
          'pmodel' => $this->DataFilter
        ),
        'OrgIdentitySource' => array(
          'type'   => 'orgidsource',
          'fk'     => 'org_identity_source_id',
          'pmodel' => $this->OrgIdentitySource
        )
      ) as $parentm => $pmcfg) {
        $pmodel = $pmcfg['pmodel'];
        
        if(!empty($idmap[$parentm]) 
           && !empty($plugins[ $pmcfg['type'] ])) {
          foreach($plugins[ $pmcfg['type'] ] as $pluginName => $m) {
            // Some plugin types have a special naming convention for the core model
            // (eg: CoFooPlugin instead of FooPlugin).
            $coreModelName = sprintf($pmodel->hasManyPlugins[ $pmcfg['type'] ]['coreModelFormat'], $pluginName);
            $corem = ClassRegistry::init($pluginName . "." . $coreModelName);
            if(!empty($corem->duplicatableModels)) {
              // Duplicate models as indicated by the plugin
              
              foreach($corem->duplicatableModels as $dupeModelName => $dupecfg) {
                // Probably need to load the model
                $dupem = ClassRegistry::init($pluginName . "." . $dupeModelName);
                // Skip duplication if no records exist
                if(!empty($idmap[$dupecfg['parent']])) {
                  $this->duplicateObjects($dupem, $dupecfg['fk'], array_keys($idmap[$dupecfg['parent']]), $idmap);
                }
              }
            } else {
              // Duplicate the main plugin object
              $this->duplicateObjects($corem, $pmcfg['fk'], array_keys($idmap[$parentm]), $idmap);
            }
          }
        }
      }
      
      $this->_commit();
    }
    catch(Exception $e) {
      $this->_rollback();
      throw new RuntimeException($e->getMessage());
    }
    
    return $idmap['Co'][$id];
  }
  
  /**
   * Duplicate objects associated with a CO.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Model   $model      Model to duplicate
   * @param  String  $foreignKey The name of the foreign key from $model to its parent (or 'id' if no parent)
   * @param  Integer $fkid       The ID of the foreign key in $model
   * @param  Array   $idmap      Reference to an array of old IDs to new IDs (originals to duplicates)
   * @param  Boolean $isTree     True if $model implements TreeBehavior
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function duplicateObjects($model, $foreignKey, $fkid, &$idmap, $isTree=false) {
    // Pull all records from $model with a foreign key $fkid into its parent.
    // eg: All COUs where co_id=$fkid.
    
    $args = array();
    $args['conditions'][$model->name.'.'.$foreignKey] = $fkid;
    $args['contain'] = false;
    if($isTree) {
      // We will order by parent_id using the NULLS FIRST option.
      // PostgreSQL needs the NULLS FIRST in order to put the null at the top
      // We will treat this as the default
      $args['order'] = $model->name . '.parent_id ASC NULLS FIRST';

      // What should we do in the case of MySQL
      $db = $model->getDataSource();
      $db_driver = explode("/", $db->config['datasource'], 2);
      $db_driverName = $db_driver[1];
      if(preg_match("/mysql/i", $db_driverName)) {
        // MySQL, MariaDB treats NULLs as NULLs are treated as less than 0 and
        // places them at the top of an ASC dataset
        $args['order'] = $model->name . '.parent_id ASC';
      }
    }
    
    $objs = $model->find('all', $args);
    
    if(!empty($objs)) {
      foreach($objs as $o) {
        if($model->name == 'Co') {
          // Special case: rename the CO
          
          $o['Co']['name'] = _txt('fd.copy-a', array($o['Co']['name']));
          $o['Co']['description'] = _txt('fd.copy-a', array($o['Co']['description']));
        }
        
        // Cache the id and foreign key, and remove the metadata
        $oldId = $o[$model->name]['id'];
        
        // Don't remove foreign keys (other than for changelog) since we use them
        // below to map to their new values
        foreach(array(
          'id',
          'created',
          'modified',
          'revision',
          'deleted',
          'actor_identifier',
          Inflector::underscore($model->name).'_id',
          // For Trees, we'll let TreeBehavior recalculate lft and rght on save
          'lft',
          'rght'
        ) as $field) {
          if(isset($o[$model->name][$field])) {
            unset($o[$model->name][$field]);
          }
        }
        
        if(!empty($model->belongsTo)) {
          // Use the relations to populate any foreign key. This approach should
          // get the primary relation (eg: co_id) as well as the parent for
          // TreeBehavior.
          
          foreach($model->belongsTo as $alias => $config) {
            if(is_array($config)) {
              $fk = $config['foreignKey'];
              $fkClass = $config['className'];
            } else {
              // Inflect the alias to get the foreign key
              $fk = Inflector::underscore($alias).'_id';
              $fkClass = $alias;
            }
            
            if(strpos($fkClass, '.')) {
              // Model name of the form Plugin.Model, but we only use Model in $idmap
              $fkClass = substr($fkClass, strpos($fkClass, '.')+1);
            }
            
            if(!empty($fk) && !empty($o[$model->name][$fk])
               && !empty($fkClass) && !empty($idmap[$fkClass][ $o[$model->name][$fk] ])) {
              // eg: $o['CoGroup']['cou_id'] = $idmap['Cou'][$old_cou_id]
              $o[$model->name][$fk] = $idmap[$fkClass][ $o[$model->name][$fk] ];
            }
          }
        }
        
        $model->clear();
        
        // Disable validation since we're copying data, which may have been valid
        // at time of save even though it wouldn't validate now. Disable callbacks
        // so eg provisioners, changelog, and random logic don't fire.
        $model->save($o, array('validate' => false, 'callbacks' => false));
        
        if($isTree) {
          // Since we disabled callbacks, we have to manually rebuild the tree
          $model->recover('parent');
        }
        
        $idmap[$model->name][$oldId] = $model->id;
      }
    }
  }
  
  /**
   * Perform initial setup for a CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @return Boolean True on success
   */
  
  public function setup($coId) {
    // Set up the default values for extended types
    $this->CoExtendedType->addDefaults($coId);
    
    // Create the default groups
    $this->CoGroup->addDefaults($coId);

    // Register the garbage Collector
    if($coId == $this->getCOmanageCOID()) {
      $this->CoJob->register(
        $coId,                                                       // $coId
        'CoreJob.GarbageCollector',                                  // $jobType
        null,                                                        // $jobTypeFk
        "",                                                          // $jobMode
        _txt('rs.jb.started.web', array(__FUNCTION__ , -1)),         // $summary
        true,                                                        // $queued
        false,                                                       // $concurrent
        array(                                                       // $params
          'object_type' => 'Co',
        ),
        0,                                                           // $delay (in seconds)
        DEF_GARBAGE_COLLECT_INTERVAL*60                              // $requeueInterval (in seconds)
      );
    }
    
    return true;
  }

  /**
   * Unload Changelog Behavior recursively
   *
   * @since  COmanage Registry v4.1.0
   * @param  Object         $plugin
   * @param  String         $pluginClassName
   */

  public function unloadChangelogBehavior($plugin, $pluginClassName) {
    if($plugin->Behaviors->enabled('Changelog')) {
      $plugin->reloadBehavior('Changelog', array('expunge' => true));
    }

    // Get the prefix by the full Class Name
    list($pluginName, $pluginParentModel) = explode('.', $pluginClassName);

    foreach($plugin->hasMany as $pluginModelName => $options) {
      $pluginModel = ClassRegistry::init($pluginName . '.' . $pluginModelName);
      $this->unloadChangelogBehavior($pluginModel, $pluginClassName);
    }

  }
}
