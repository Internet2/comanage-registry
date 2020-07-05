<?php
/**
 * COmanage Registry Changelog Behavior
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
    $malias = $model->alias;
    $parentfk = Inflector::underscore($mname) . "_id";
    $dataSource = $model->getDataSource();
    
    if(isset($this->settings[$malias]['expunge'])
       && $this->settings[$malias]['expunge']) {
      // We're in the middle of an expunge, so don't do anything
      return true;
    }
    
    if(!$created
       && !empty($options['fieldList'])
       && !in_array('revision', $options['fieldList'])) {
      // On edit, if a fieldList was specified and revision wasn't in it (which
      // it usually won't be), we weren't able to increment it. Increment it here.
      
      // Get the current revision. We can't disable callbacks using field(), but
      // that's OK since we should always be asking for the most recent (valid)
      // value here.
      
      $curRevision = $model->field('revision');
      
      // Save the incremented revision
      if(!$model->saveField('revision', $curRevision+1, array('callbacks' => false))) {
        $dataSource->rollback();
      }
    }
    
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
    $malias = $model->alias;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    if(isset($this->settings[$malias]['expunge'])
       && $this->settings[$malias]['expunge']) {
      // We're in the process of expunging physical records. Instead of the normal
      // behavior, clear any internal foreign keys to our parent record so cake doesn't
      // throw database errors when it tries to delete archived rows. (There's no
      // guarantee of delete order within a model, so it may try to delete the parent
      // record before the children. Using model associations causes infinite recursion.)
      
      $model->updateAll(array($malias.'.'.$parentfk => null),
                        array($malias.'.'.$parentfk => $model->id));
      
      return true;
    }
    
    // Start a transaction. This may result in nested transactions if associated
    // data is being deleted, but that's OK.
    
    $dataSource = $model->getDataSource();
    $dataSource->begin();
    
    if($this->isDeleted($model, $model->id, true)) {
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
      // We need to manually cascade the delete. For related models that don't
      // implement changelog behavior, we still cascade the delete. An example
      // would be a plugin that doesn't implement changelog even though the
      // parent model does.
      
      // For dependent related models that do implement changelog, we call delete on them
      // as well, unless they are set for $relinkToArchive, in which case we leave
      // them pointing to the deleted attribute.
      
      foreach(array_merge($model->hasOne, $model->hasMany) as $rmodel => $roptions) {
        if(isset($roptions['dependent'])
           && $roptions['dependent']
           && (!$model->relinkToArchive
               || !in_array($rmodel, $model->relinkToArchive))) {
          if(!$model->$rmodel->deleteAll(array($rmodel . '.' . $roptions['foreignKey'] => $model->id), true, true)) {
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
  
  public function beforeFind(Model $model, $query) {
    $mname = $model->name;
    $malias = $model->alias;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    $ret = $query;

    // Inspect query conditions to see if we need to modify the query
    
    if((isset($query['changelog']['archived'])
        && $query['changelog']['archived'])
       ||
       // We're in the process of expunging physical records, so do no magic
       (isset($this->settings[$malias]['expunge'])
        && $this->settings[$malias]['expunge'])) {
      // Don't modify the query, we've specifically been asked for archived attribtues.
      
      return $ret;
    }
    
    if(!empty($query['changelog']['revision'])
       && !empty($query['conditions'][$malias . '.id'])) {
      // We've been asked for a prior revision of a parent attribute. We have to
      // rewrite the query a bit since we were asked for a specific ID, but the
      // older revision will have a different ID.
      
      $ret['conditions'][$malias . '.' . $parentfk] = $query['conditions'][$malias . '.id'];
      $ret['conditions'][$malias . '.revision'] = $query['changelog']['revision'];
      unset($ret['conditions'][$malias . '.id']);
      
      // Don't further modify the query (not clear yet what we would do for contain...)
      return $ret;
    }
    
    if(isset($query['conditions'][$malias . '.id'])
       || isset($query['conditions']['id'])) {
      if(!empty($query['contain'])
         && (!isset($query['contain'][0]) || $query['contain'][0] != false)) {
        // Whether or not we modify the related models depends on whether the ID
        // we're retrieving is deleted.
        
        if(!$this->isDeleted($model,
                             isset($query['conditions'][$malias . '.id'])
                             ? $query['conditions'][$malias . '.id']
                             : $query['conditions']['id'])) {
          // Current record is not deleted, so don't pull deleted related attributes
          $ret['contain'] = $this->modifyContain($model, $query['contain']);
        }
      }
    } else {
      // No id set, so we're probably doing a search of some sort. Filter out
      // any deleted or archived attributes.
      
      $ret['conditions'][$malias . '.' . $parentfk] = null;
      // Careful with checking deleted. We need to check for NOT true (ie: both
      // null and false are OK to return). This seems to be the only way to
      // get that query without throwing errors or returning incorrect results.
      $ret['conditions'][] = $malias . '.deleted IS NOT true';
      
      if(!empty($query['contain'])
         && (!isset($query['contain'][0]) || $query['contain'][0] != false)) {
        $ret['contain'] = $this->modifyContain($model, $query['contain']);
      }
      
      if(!empty($query['joins'])) {
        // We might have joined tables in the query conditions. If so, insert
        // the appropriate filters
        
        foreach($query['joins'] as $i => $j) {
          // The model being joined in the query may not have a direct
          // relationship to the model on which the find is being done
          // so we load it here directly and if it fails to load throw
          // an exception.
          
          $jmodel = ClassRegistry::init($j['alias'], true);
          
          if(!$jmodel) {
            // Try to inflect the table name to get the model
            $jmodel = ClassRegistry::init(Inflector::classify($j['table']), true);
          }
          
          if($jmodel) {
            if($jmodel->Behaviors->enabled('Changelog')
               && (!$jmodel->relinkToArchive
                   || !in_array($mname, $jmodel->relinkToArchive))) {
              // If the model being joined has changelog behavior add conditions
              // on the join so that rows from earlier versions or soft deleted
              // rows are not included after the join. However we don't do this
              // if the model is relinkToArchived... we want the archived record
              // in that case.
              $cparentfk = Inflector::underscore($jmodel->name) . "_id";
              
              // Add condition to the join condition that the joined model
              // model_id column is null.
              $ret['joins'][$i]['conditions'][] = $j['alias'].'.'.$cparentfk.' IS NULL';
              
              // Add condition to the join condition that the joined model
              // deleted column is not true.
              $ret['joins'][$i]['conditions'][] = $j['alias'].'.deleted IS NOT true';
            }
          } else {
            throw new RuntimeException(_txt('er.changelog.model.load', array($j['alias'])));
          }
        }
      }
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
    $malias = $model->alias;
    $parentfk = Inflector::underscore($mname) . "_id";
    
    if(isset($this->settings[$malias]['expunge'])
       && $this->settings[$malias]['expunge']) {
      // We're in the middle of an expunge, so don't do anything
      return true;
    }
    
    if(!empty($model->data[$malias]['id'])) {
      // Before we do anything, make sure we're operating on the latest version of
      // the record. We don't allow editing of already archived attributes.
      
      if($this->isDeleted($model, $model->data[$malias]['id'], true)) {
        throw new RuntimeException(_txt('er.archived'));
      }
    }
    // else it's an add, nothing to check
    
    // Start a transaction. This may result in nested transactions if associated
    // data is being saved, but that's OK.
    
    $dataSource = $model->getDataSource();
    $dataSource->begin();
    
    // A save can actually have multiple model's worth of data (saveAssociated, saveAll),
    // so we walk through the entire set of data and apply the appropriate munging to
    // any model for which Changelog is enabled.
    
    if(!empty($model->data[$malias])) {
      if(empty($model->data[$malias]['id'])) {
        // Add operation, so this is a new instance of this model. Set the revision
        // to 0 (for compatibility with records that existed before the model was
        // enabled for ChangelogBehavior) and make sure the parent key is null.
        
        $model->data[$malias][$parentfk] = null;
        $model->data[$malias]['revision'] = 0;
        
        // For an add, we don't need to rekey related models
      } else {
        // Edit operation. We operate a bit counterintuitively here... we let the
        // edit go through but first create a *new* record for the archived value.
        // We do this to avoid having to re-key most related records (the default
        // behavior is that related models want the most recent value), and to avoid
        // having to trick cake into converting an edit to an add.
        
        // We do the copy here (vs afterSave) so we can abort on error.
        
        // We use the full record from the database. We do this for a couple
        // of reasons: on a saveField(), $model->data will only have the field being
        // saved, not the full record. Similarly, the form posted by the user will be
        // missing some fields (revision, actor_identifier).
        
        // The current record as pulled from the database
        $args = array();
        $args['conditions'][$malias.'.id'] = $model->data[$malias]['id'];
        // Make sure to disable callbacks so we don't screw up the find
        $args['callbacks'] = false;
        // Callbacks are disabled, so contain is ignored -- use recursive instead
        $args['recursive'] = -1;
        
        $curRecord = $model->find('first', $args);
        
        if(!$curRecord) {
          $dataSource->rollback();
          
          throw new RuntimeException(_txt('er.notfound',
                                          array($malias, $model->data[$malias]['id'])));
        }
        
        $curRevision = $curRecord[$malias]['revision'];
        
        // Make sure we don't pull related models into the archive
        $archiveData[$malias] = $curRecord[$malias];
        
        // Fix links and attributes
        unset($archiveData[$malias]['id']);
        $archiveData[$malias][$parentfk] = $model->data[$malias]['id'];
        if(!isset($archiveData[$malias]['deleted'])) {
          $archiveData[$malias]['deleted'] = false;
        }
        
        // We need to jump through some hoops related to how Cake 2 is semi-object oriented
        $origId = $model->id;
        $origData = $model->data;
        // On a saveField, whitelist will be set to just the field being updated, but for
        // archive we need to copy the entire record.
        $origWhitelist = $model->whitelist;
        
        // Reset model state for new save
        $model->whitelist = array();
        $model->create();
      
        // Disable callbacks so we don't loop indefinitely. Also disable validation because
        // we're copying old data, which presumably validated once (though it might not now).
        
        if(!$model->save($archiveData, array('callbacks' => false,
                                             'validate' => false))) {
          $dataSource->rollback();
          throw new RuntimeException(_txt('er.db.save-a', array('Changelog aftersave archive')));
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
              $aparentfk = Inflector::underscore($model->$amodel->name) . "_id";
              
              // We have the original foreign key, but the parent model got rekeyed
              // as part of this save and we need to look up the new (archive) ID.
              // We could probably consolidate these two queries into one, but it's
              // a bit easier to understand this way, and this isn't commonly
              // executed code.
              
              // The current value of this model's foreign key to the parent
              $currentfkid = $origData[$malias][$aparentfk];
              
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
                  throw new RuntimeException(_txt('er.db.save-a', array('Changelog aftersave parentfk')));
                }
              }
            }
          }
        }
        
        // Restore original data for the intended save
        $model->create($origData);
        $model->id = $origId;
        $model->whitelist = $origWhitelist;
        // For use in afterSave
        $model->archiveId = $archiveId;
        
        // Bump the revision number for the current attribute. If we're doing a saveField
        // or some other transaction
        $model->data[$malias]['revision'] = $curRevision + 1;
      }
      
      // Set common attributes for add and edit
      $model->data[$malias]['deleted'] = false;
      
      if(session_status() == PHP_SESSION_ACTIVE) {
        // Forcing a read of the CakeSession is sub-optimal, but consistent with what we do elsewhere
        $model->data[$malias]['actor_identifier'] = CakeSession::read('Auth.User.username');
      } else {
        // We're probably at the command line
        $user = posix_getpwuid(posix_getuid());
        $model->data[$malias]['actor_identifier'] = _txt('fd.actor.shell', array($user['name']));
      }
    }
    
    return true;
  }
  
  /**
   * Determine if a record is flagged as deleted.
   *
   * @since  COmanage Registry v0.9.4
   * @param  Model $model Model instance
   * @param  Integer $id Record ID to check
   * @param  Boolean $archived If true, consider archived records as deleted as well
   * @return boolean true if record is flagged as deleted, false otherwise
   * @throws InvalidArgumentException
   */
  
  protected function isDeleted($model, $id, $archived=false) {
    $mname = $model->name;
    $parentfk = Inflector::underscore($mname) . "_id";
    $malias = $model->alias;
    
    $args = array();
    $args['conditions'][$malias.'.id'] = $id;
    // Make sure to disable callbacks so we don't screw up the find
    $args['callbacks'] = false;
    // Callbacks are disabled, so contain is ignored -- use recursive instead
    $args['recursive'] = -1;
    
    $curRecord = $model->find('first', $args);
    
    if(empty($curRecord)) {
      throw new InvalidArgumentException(_txt('er.notfound', array($malias, $id)));
    }

    return ((isset($curRecord[$malias]['deleted'])
             && $curRecord[$malias]['deleted'])
            ||
            ($archived
             && !empty($curRecord[$malias][$parentfk])
             && $curRecord[$malias][$parentfk] != 0));
  }

  /**
   * modifyContain will walk the array of $query['contain'] and append
   * conditions to constrain the contain to eliminate deleted or archived
   * attributes.
   *
   * @todo Could this be rewritten to mimic (eg) LinkableBehavior's iterator?
   */
  
  protected function modifyContain($model, $contain) {
    // If we get a simple string, convert it to a simple array
    if(is_string($contain)) {
      $contain = array(0 => $contain);
    }
    
    $ret = $contain;
    
    foreach($contain as $k => $v) {
      if(is_int($k)) {
        // eg: $query['contain'] = array('Model1', 'Model2');
        // eg: $query['contain'] = array('Model1.Model2');
        // For now, we don't support this second use case. Rewrite your contains
        // statement to use a different format.
        // eg: $query['contain'] = array('Model1.field = "value"');
        
        $vmodel = $v;
        $bits = array();
        
        if(strchr($v, '.')) {
          // Third example
          
          $bits = explode('.', $v, 2);
          $vmodel = $bits[0];
        }
        
        if($model->$vmodel->Behaviors->enabled('Changelog')) {
          // Check to see if the contain'd model is set to relinkToArchive the
          // parent model. If so, we need to pull archived/deleted records.
          // XXX We probably need to do this below, too.
          
          if(empty($model->$vmodel->relinkToArchive)
             || !in_array($model->name, $model->$vmodel->relinkToArchive)) {
            $cparentfk = Inflector::underscore($model->$vmodel->name) . "_id";
            
            $ret[$vmodel]['conditions'] = array(
              $vmodel.'.'.$cparentfk => null,
              $vmodel.'.deleted IS NOT true'
            );
            
            if(!empty($bits[1]) && strchr($bits[1], '=')) {
              // Insert the originally requested condition
              
              $bits2 = explode('=', $bits[1], 2);
              $ret[$vmodel]['conditions'][ $vmodel.'.'.trim($bits2[0]) ] = trim($bits2[1]);
            }
            
            // Unset the original, which is keyed on an index number ($k)
            unset($ret[$k]);
          }
        }
      } else {
        // eg: $query['contain'] = array('Model1' => array('Model2'));
        // eg: $query['contain'] = array('Model1' => 'Model2');
        // eg: $query['contain'] = array('conditions' => array('Model1.foo =' => 'value'));
        // eg: $query['contain'] = array('Model1' => array('conditions' => array('Model1.foo' => 'value'),
        //                                                 'Model2' => array('conditions' => array('Model2.foo' => 'value'))
        // eg: $query['contain'] = array('Model1' => array('Model2' => 'Model3'));
        // eg: $query['contain'] = array('Model1' => array('order' => 'Model1.field DESC'));
        
        if(is_array($v)) {
          // First handle Model1
          
          if($k == 'conditions') {
            // Example 4. We haven't yet handled $model (this is Model2, not Model1),
            // so merge it with the conditions in $v
            
            if($model->Behaviors->enabled('Changelog')) {
              $parentfk = Inflector::underscore($model->name) . "_id";
              
              $ret['conditions'] = array(
                $model->name.'.'.$parentfk => null,
                $model->name.'.deleted IS NOT true'
              );
              
              $ret['conditions'] = array_merge($ret['conditions'], $v);
            }
            
            // $v is an array of conditions, not models, so we want to jump to the
            // next entry in $contain
            continue;
          } else {
            if($model->$k->Behaviors->enabled('Changelog')) {
              $cparentfk = Inflector::underscore($model->$k->name) . "_id";
              
              $ret[$k]['conditions'] = array(
                $k.'.'.$cparentfk => null,
                $k.'.deleted IS NOT true'
              );
            }
          }
          
          // Now walk the value array
          
          foreach($contain[$k] as $k2 => $v2) {
            // Cast $k2 to a string since (int)0 == 'conditions' somehow evaluates to true
            if((string)$k2 == 'conditions') {
              // Third example, nothing to do but copy (merge) the conditions
              
              $ret[$k]['conditions'] = array_merge($ret[$k]['conditions'], $v2);
            } elseif((string)$k2 == 'order') {
              // Sixth example
              
              $ret[$k]['order'] = $v2;
            } elseif(is_array($v2)) {
              $ret[$k][$k2] = $this->modifyContain($model->$k->$k2, $v2);
              
              if(is_string($k2) && !is_integer($k2)) {
                $cparentfk = Inflector::underscore($model->$k->$k2->name) . "_id";
                
                $ret[$k][$k2]['conditions'] = array(
                  $k2.'.'.$cparentfk => null,
                  $k2.'.deleted IS NOT true'
                );
              }
            } elseif(isset($model->$k->$k2)) {
              // Fifth example
              $m = $this->modifyContain($model->$k, array($k2 => $v2));
              $ret[$k][$k2] = $m[$k2];
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
          // First handle Model1. We have to unset the value first because $v
          // is a single value, not an array. This requires handling quite a few
          // scenarios given Cake's, uh, flexibility in specifiying contains.
          
          unset($ret[$k]);
          
          if($model->$k->$v->Behaviors->enabled('Changelog')) {
            $cparentfk = Inflector::underscore($model->$k->$v->name) . "_id";
            
            $ret[$k][$v]['conditions'] = array(
              $v.'.'.$cparentfk => null,
              $v.'.deleted IS NOT true'
            );
          } else {
            $ret[$k][] = $v;
          }
          
          if($model->$k->Behaviors->enabled('Changelog')) {
            $cparentfk = Inflector::underscore($model->$k->name) . "_id";
            
            $ret[$k]['conditions'] = array(
              $k.'.'.$cparentfk => null,
              $k.'.deleted IS NOT true'
            );
          }
          // else we shouldn't have to do anything since handling of $v should
          // have re-created $ret[$k]
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
