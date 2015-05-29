<?php
/**
 * COmanage Registry Changelog Behavior
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class ChangelogBehavior extends ModelBehavior {
  /**
   * Handle changelog archive following (after) save of Model.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model instance
   */
  
  public function afterSave(Model $model, $created, $options = array()) {
    $mname = $model->name;
    $parentfk = Inflector::underscore($mname) . "_id";
    $dataSource = $model->getDataSource();
    
    if(!empty($model->relinkToArchive)) {
      foreach($model->relinkToArchive as $amodel) {
        if(isset($model->hasMany[$amodel]) || isset($model->hasOne[$amodel])) {
          // Standard approach is to update associated (child) models in afterSave.
          // (Otherwise this is handled in beforeSave.)
          
          $col = $amodel.".".$parentfk;
          
          if(!$model->$amodel->updateAll(array($col => $model->archiveId),
                                         array($col => $model->id))) {
            $dataSource->rollback();
          }
        }
      }
    }
    
    $dataSource = $model->getDataSource();
    $dataSource->commit();
  }
  
  /**
   * Handle changelog delete of Model.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model instance
   * @return Boolean False in order to interrupt the physical delete from happening
   */
  
  public function beforeDelete(Model $model, $cascade = true) {
    $mname = $model->name;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    if(isset($this->settings[$model->alias]['expunge'])
       && $this->settings[$model->alias]['expunge']) {
      // We're in the process of expunging physical records. Instead of the normal
      // behavior, clear any internal foreign keys to our parent record so cake doesn't
      // throw database errors when it tries to delete archived rows. (There's no
      // guarantee of delete order within a model, so it may try to delete the parent
      // record before the children. Using model associations causes infinite recursion.)
      
      $model->updateAll(array($mname.'.'.$parentfk => null),
                        array($mname.'.'.$parentfk => $model->id));
      
      return true;
    }
    
    // Start a transaction. This may result in nested transactions if associated
    // data is being deleted, but that's OK.
    
    $dataSource = $model->getDataSource();
    $dataSource->begin();
    
    $deleteStatus = $model->field('deleted');
    
    if($deleteStatus) {
      // We can't really pass back an error to be nicely rendered, but we can
      // at least force a stack trace.
      throw new RuntimeException(_txt('er.delete.already'));
    }
    
    // Set the attribute to deleted. Disable callbacks so we don't end up in a loop.
    
    if(!$model->saveField('deleted', true, array('callbacks' => false))) {
      $dataSource->rollback();
    }
    
    // We need to return false to prevent the actual delete from happening
    
    if($cascade) {
      // By returning false, we appear to interrupt a cascade from happening.
      // We need to manually cascade the delete. It's not clear what we should
      // do for related models that don't implement changelog behavior, so for
      // now we just ignore them.
      
      // For related models that do implement changelog, we call delete on them
      // as well, unless they are set for $relinkToArchive, in which case we leave
      // them pointing to the deleted attribute.
      
      foreach(array_merge(array_keys($model->hasOne),
                          array_keys($model->hasMany)) as $rmodel) {
        if($model->$rmodel->Behaviors->enabled('Changelog')
           && !in_array($rmodel, $model->relinkToArchive)) {
          if(!$model->$rmodel->deleteAll(array($rmodel . '.' . $parentfk => $model->id), true, true)) {
            $dataSource->rollback();
          }
        }
      }
    }
    
    // If we rolled back, this commit won't do much
    $dataSource->commit();
    
    return false;
  }
  
  /**
   * Adjust find query conditions for Changelog models.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model instance
   * @param  Array $query Query conditions
   * @return Array Modified query conditions
   */
  
  public function beforeFind(Model $model, array $query) {
    $mname = $model->name;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    $ret = $query;
    
    // Inspect query conditions to see if we need to modify the query
    
    if((isset($query['archived']) && $query['archived'])
       ||
       // We're in the process of expunging physical records, so do no magic
       (isset($this->settings[$model->alias]['expunge'])
        && $this->settings[$model->alias]['expunge'])) {
      // Don't modify the query, we've specifically been asked for archived attribtues.
      
      return $ret;
    }
    
    if(!isset($query['conditions'][$mname . '.id'])
       && !isset($query['conditions']['id'])) {
      // No id set, so we're probably doing a search of some sort. Filter out
      // any deleted or archived attributes.
      
      $ret['conditions'][$mname . '.' . $parentfk] = null;
      // Careful with checking deleted. We need to check for NOT true (ie: both
      // null and false are OK to return). This seems to be the only way to
      // get that query without throwing errors or returning incorrect results.
      $ret['conditions'][] = $mname . '.deleted IS NOT true';
    }
    
    if(!empty($query['contain'])
       && (!isset($query['contain'][0]) || $query['contain'][0] != false)) {
      $ret['contain'] = $this->modifyContain($model, $query['contain']);
    }
    
    return $ret;
  }
  
  /**
   * Handle changelog archive during (before) save of Model.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model instance
   * @return boolean true on success, false on failure
   * @throws RuntimeException
   */
  
  public function beforeSave(Model $model, $options = array()) {
    $mname = $model->name;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    // Before we do anything, make sure we're operating on the latest version of
    // the record. We don't allow editing of already archived attributes.
    
    $curParent = $model->field($parentfk);
    
    if($curParent) {
      throw new RuntimeException(_txt('er.archived'));
    }
    
    // Start a transaction. This may result in nested transactions if associated
    // data is being saved, but that's OK.
    
    $dataSource = $model->getDataSource();
    $dataSource->begin();
    
    // A save can actually have multiple model's worth of data (saveAssociated, saveAll),
    // so we walk through the entire set of data and apply the appropriate munging to
    // any model for which Changelog is enabled.
    
    if(!empty($model->data[$mname])) {
      if(empty($model->data[$mname]['id'])) {
        // Add operation, so this is a new instance of this model. Set the revision
        // to 0 (for compatibility with records that existed before the model was
        // enabled for ChangelogBehavior) and make sure the parent key is null.
        
        $model->data[$mname][$parentfk] = null;
        $model->data[$mname]['revision'] = 0;
        
        // For an add, we don't need to rekey related models
      } else {
        // Edit operation. We operate a bit counterintuitively here... we let the
        // edit go through and then create a *new* record for the archived value.
        // We do this to avoid having to re-key most related records (the default
        // behavior is that related models want the most recent value), and to avoid
        // having to trick cake into converting an edit to an add.
        
        // We do the copy here (vs afterSave) so we can abort on error.
        
        // Start by pulling fields that won't get submitted by the form, and so
        // won't be in $model->data.
        
        $curRevision = $model->field('revision');
        $curActor = $model->field('actor_identifier');
        
        // Now create the archive copy
        
        $archiveData = array();
        $archiveData[$mname] = $model->data[$mname];
        
        // Fix links and attributes
        unset($archiveData[$mname]['id']);
        $archiveData[$mname][$parentfk] = $model->data[$mname]['id'];
        $archiveData[$mname]['revision'] = $curRevision;
        $archiveData[$mname]['actor_identifier'] = $curActor;
        if(!isset($archiveData[$mname]['deleted'])) {
          $archiveData[$mname]['deleted'] = false;
        }
        
        // We need to jump through some hoops related to how Cake 2 is semi-object oriented
        $origId = $model->id;
        $origData = $model->data;
        
        // Reset model state for new save
        $model->create();
      
        // Disable callbacks so we don't loop indefinitely
        if(!$model->save($archiveData, array('callbacks' => false))) {
          $dataSource->rollback();
          
          throw new RuntimeException(_txt('er.db.save'));
        }
        
        // Grab a copy of the archive ID before we reset model state back to where it was
        $archiveId = $model->id;
        
        if(!empty($model->relinkToArchive)) {
          // Special case for models that are saved along side other (parent)
          // models. Since Cake 2 doesn't allow a callback to update associated
          // data model during a save, we have to wait for the parent to be
          // saved so we can get the updated identifier. We determine whether to
          // do this if $relinkToArchive model is belongsTo.
          
          foreach($model->relinkToArchive as $amodel) {
            if(isset($model->belongsTo[$amodel])) {
              $aparentfk = Inflector::underscore($amodel->$amodel->name) . "_id";
              
              // We have the original foreign key, but the parent model got rekeyed
              // as part of this save and we need to look up the new (archive) ID.
              // We could probably consolidate these two queries into one, but it's
              // a bit easier to understand this way, and this isn't commonly
              // executed code.
              
              // The current value of this model's foreign key to the parent
              $currentfkid = $origData[$mname][$aparentfk];
              
              // The parent foreign key for the current foreign key
              $parentfkid = null;
              
              $args = array();
              $args['conditions'][$amodel . '.id'] = $currentfkid;
              // Make sure to disable callbacks so we don't screw up the find
              $args['callbacks'] = false;
              $args['contain'] = false;
              
              if(!empty($targetArchive[$amodel][$aparentfk])) {
                $parentfkid = $targetArchive[$amodel][$aparentfk];
              } else {
                // The current is also the parent
                $parentfkid = $currentfkid;
              }
              
              // Now we need to figure out the most recently archived child of
              // $parentfkid. This will be the one with the highest revision where
              // the parent is not null. (parent = null means current, unarchived value)
              
              $args = array();
              $args['conditions'][$amodel.'.'.$aparentfk] = $parentfkid;
              $args['order'][$amodel.'.revision'] = 'DESC';
              // Make sure to disable callbacks so we don't screw up the find
              $args['callbacks'] = false;
              $args['contain'] = false;
              
              $targetArchive = $model->$amodel->find('first', $args);
              
              if(!empty($targetArchive[$amodel]['id'])) {
                // Now that we have the correct identifier update the foreign key
                // in the archive that we saved above.
                
                if(!$model->saveField($aparentfk, $targetArchive[$amodel]['id'], array('callbacks' => false))) {
                  $dataSource->rollback();
                }
              }
            }
          }
        }
        
        // Restore original data for the intended save
        $model->create($origData);
        $model->id = $origId;
        // For use in afterSave
        $model->archiveId = $archiveId;
        
        // Bump the revision number for the current attribute
        $model->data[$mname]['revision'] = $curRevision + 1;
      }
      
      // Set common attributes for add and edit
      $model->data[$mname]['deleted'] = false;
      // Forcing a read of the CakeSession is sub-optimal, but consistent with what we do elsewhere
      $model->data[$mname]['actor_identifier'] = CakeSession::read('Auth.User.username');
    }
    
    return true;
  }

  /**
   * modifyContain will walk the array of $query['contain'] and append
   * conditions to constrain the contain to eliminate deleted or archived
   * attributes.
   */
  
  protected function modifyContain($model, $contain) {
    $ret = $contain;
    
    foreach($contain as $k => $v) {
      if(is_int($k)) {
        // eg: $query['contain'] = array('Model1', 'Model2');
        
        if($model->$v->Behaviors->enabled('Changelog')) {
          $cparentfk = Inflector::underscore($model->$v->name) . "_id";
          
          $ret[$v]['conditions'] = array(
            $v.'.'.$cparentfk => null,
            $v.'.deleted IS NOT true'
          );
          
          // Unset the original, which is keyed on an index number ($k)
          unset($ret[$k]);
        }
      } else {
        // eg: $query['contain'] = array('Model1' => array('Model2'));
        // eg: $query['contain'] = array('Model1' => 'Model2');
        
        // First check the model represented by the key
        
        if($model->$k->Behaviors->enabled('Changelog')) {
          $cparentfk = Inflector::underscore($model->$k->name) . "_id";
          
          $ret[$k]['conditions'] = array(
            $k.'.'.$cparentfk => null,
            $k.'.deleted IS NOT true'
          );
        }
        
        // And now the value
        
        if(is_array($v)) {
          // Now walk the value array
          
          foreach($contain[$k] as $k2 => $v2) {
            if(is_array($v2)) {
              $ret[$k][$k2] = $this->modifyContain($model->$k->$k2, $v2);
            } else {
              if($model->$k->$v2->Behaviors->enabled('Changelog')) {
                $cparentfk = Inflector::underscore($model->$k->$v2->name) . "_id";
                
                $ret[$k][$v2]['conditions'] = array(
                  $v2.'.'.$cparentfk => null,
                  $v2.'.deleted IS NOT true'
                );
              }
            }
          }
        } else {
          if($model->$k->$v->Behaviors->enabled('Changelog')) {
            $cparentfk = Inflector::underscore($model->$k->$v->name) . "_id";
            
            $ret[$k][$v]['conditions'] = array(
              $v.'.'.$cparentfk => null,
              $v.'.deleted IS NOT true'
            );
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Perform initial setup of the Behavior.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model behavior is attached to
   * @param  Array $settings Array of settings (Boolean 'expunge' controls hard vs soft delete)
   */
  
  public function setup(Model $model, $settings = array()) {
    // Behavior instances are shared across models, so store settings on a per alias basis
    
    if(!isset($this->settings[$model->alias])) {
      $this->settings[$model->alias] = array(
        'expunge' => false
      );
    }
    
    $this->settings[$model->alias] = array_merge(
      $this->settings[$model->alias], (array)$settings
    );
  }
}