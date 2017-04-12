<?php
/**
 * Application level Model
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
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
  // Track transactions in callbacks so we know whether to commit.
  // (Commiting the wrong number of times confuses saveAssociated.)
  protected $inTxn = false;
  
  // Track timezone for models that need to convert to/from UTC
  protected $tz = null;
  
  /**
   * Wrapper for begin(), primarily intended for use in callbacks.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  protected function _begin() {
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $this->inTxn = true;
  }

  /**
   * Wrapper for commit(), primarily intended for use in callbacks.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  protected function _commit() {
    if($this->inTxn) {
      $dbc = $this->getDataSource();
      $dbc->commit();
      
      $this->inTxn = false;
    }
  }
    
  /**
   * Wrapper for rollback(), primarily intended for use in callbacks.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  protected function _rollback() {
    if($this->inTxn) {
      $dbc = $this->getDataSource();
      $dbc->rollback();
      
      $this->inTxn = false;
    }
  }
  
  /**
   * Actions before deleting a model.
   *
   * @since  COmanage Registry v0.8
   * @param  boolean Whether this is a cascading delete
   * @return true for the actual delete to happen
   * @todo   Rewrite to use loadAvailablePlugins
   */
  
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
   * Compare one model's worth of data and generate a string describing what changed, suitable for
   * including in a history record.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String  $model   Model being examined
   * @param  Array   $newdata New data, in Cake single instance format
   * @param  Array   $olddata Old data, in Cake single instance format
   * @param  Integer $coId    CO ID, if known
   * @param  Array   $attrs   Array of model attributes to examine
   * @return Array Array of string describing changes
   */
  
  protected function changesForModel($model, $newdata, $olddata, $coId, $attrs) {
    global $cm_texts, $cm_lang;
    
    $changes = array();
    
    foreach($attrs as $attr) {
      // Skip some "housekeeping" keys. Don't blanket skip all *_id attributes
      // since some foreign keys should be tracked (eg: cou_id, sponsor_co_person_id).
      if($attr == 'id'
         // We can generally skip CO ID since attributes can't move across COs.
         || $attr == 'co_id'
         // We can generally skip co_person_id and org_identity_id since changing those
         // requires a relink operation that will generate its own history
         || $attr == 'co_person_id'
         || $attr == 'org_identity_id'
         // Skip record metadata
         || $attr == 'created'
         || $attr == 'modified'
         // And changelog metadata
         || $attr == 'revision'
         || $attr == 'deleted'
         || $attr == 'actor_identifier'
         || $attr == Inflector::underscore($model).'_id'
         // And pipeline metadata
         || $attr == 'source_'.Inflector::underscore($model).'_id') {
        continue;
      }
      
      // Skip further nested arrays
      if((isset($newdata[$attr]) && is_array($newdata[$attr]))
          || (isset($olddata[$attr]) && is_array($olddata[$attr]))) {
        continue;
      }
      
      if(preg_match('/.*_id$/', $attr)) {
        // Foreign keys need to be handled specially. Start by figuring out the model.
        
        if(preg_match('/.*_co_person_id$/', $attr)) {
          // This is a foreign key to a CO Person (eg: sponsor_co_person)
          
          // Chop off _co_person_id
          $afield = substr($attr, 0, strlen($attr)-13);
          $amodel = "CoPerson";
        } else {
          // Chop off _id
          $afield = substr($attr, 0, strlen($attr)-3);
          $amodel = Inflector::camelize(rtrim($attr, "_id"));
        }
        
        // Instantiated foreign key model
        $fkmodel = ClassRegistry::init($amodel);
        
        $ftxt = $afield;
        
        // XXX this isn't really an ideal way to see if a language key exists (here or below)
        if(!empty($cm_texts[ $cm_lang ]['fd.' . $afield])) {
          $ftxt = $cm_texts[ $cm_lang ]['fd.' . $afield];
        }
        
        // Get the old and new values
        
        $oldval = (isset($olddata[$attr]) && $olddata[$attr] != "") ? $olddata[$attr] : null;
        $newval = (isset($newdata[$attr]) && $newdata[$attr] != "") ? $newdata[$attr] : null;
        
        // Make sure they're actually different (we may get some foreign keys here that aren't)
        
        if($oldval == $newval) {
          continue;
        }
        
        if($amodel == "CoPerson" || $amodel == "OrgIdentity") {
          // Display field is Primary Name. Pull the old and new CO People/Org Identity in
          // one query, though we won't know which one we'll get back first.
          
          $args = array();
          $args['conditions'][$amodel.'.id'] = array($oldval, $newval);
          $args['contain'][] = 'PrimaryName';
          
          $ppl = $fkmodel->find('all', $args);
          
          if(!empty($ppl)) {
            // Walk through the result set to figure out which one is old and which is new
            
            foreach($ppl as $c) {
              if(!empty($c[$amodel]['id']) && !empty($c['PrimaryName'])) {
                if($c[$amodel]['id'] == $oldval) {
                  $oldval = generateCn($c['PrimaryName']) . " (" . $oldval . ")";
                } elseif($c[$amodel]['id'] == $newval) {
                  $newval = generateCn($c['PrimaryName']) . " (" . $newval . ")";
                }
              }
            }
          }
        } else {
          // Lookup a human readable string (usually name or something) and prepend it to the ID
          
          $oldval = $fkmodel->field($fkmodel->displayField, array('id' => $oldval)) . " (" . $oldval . ")";
          $newval = $fkmodel->field($fkmodel->displayField, array('id' => $newval)) . " (" . $newval . ")";
        }
      } else {
        // Simple field in the model
        
        $oldval = (isset($olddata[$attr]) && $olddata[$attr] != "") ? $olddata[$attr] : null;
        $newval = (isset($newdata[$attr]) && $newdata[$attr] != "") ? $newdata[$attr] : null;
        
        // See if we're working with a type, and if so use the localized string instead
        // (if we can find it)
        
        $fmodel = null;
        
        // Use name (not alias) here so (eg) EnrolleeCoPerson works correctly
        if($model == $this->name) {
          // We are the model we want
          $fmodel = $this;
        } elseif($model == $this->$model->name) {
          // For now we assume there is a direct association possible
          $fmodel = $this->$model;
        }
        
        if($fmodel) {
          if(isset($fmodel->cm_enum_txt[$attr])) {
            // The model defines a key into lang.php texts to use for localization.
            
            if($oldval) {
              $oldval = _txt($fmodel->cm_enum_txt[$attr], null, $oldval) . " (" . $oldval . ")";
            }
            if($newval) {
              $newval = _txt($fmodel->cm_enum_txt[$attr], null, $newval) . " (" . $newval . ")";
            }
          } else {
            // This is possibly a model with an Extended Type. Try looking up the mapping.
            // It would be best if we could pull the CO ID from the data we're passed, but
            // in general MVPAs don't point directly to CO ID. We could look it up, but instead
            // we'll just expect it to be passed in.
            
            if($coId) {
              $mTypes = $fmodel->types($coId, $attr);
              
              if(!empty($mTypes)) {
                if($oldval) {
                  $oldval = $mTypes[$oldval] . " (" . $oldval . ")";
                }
                if($newval) {
                  $newval = $mTypes[$newval] . " (" . $newval . ")";
                }
              }
            }
          }
        }
        
        // Find the localization of the field
        
        $ftxt = "(?)";
        
        if(($model == 'Name' || $model == 'PrimaryName') && $attr != 'type') {
          // Treat name specially
          $ftxt = _txt('fd.name.'.$attr);
        } else {
          // Inflect the model name and see if fd.model.attr exists
          
          $imodel = Inflector::underscore($model);
          
          // XXX this isn't really an ideal way to see if a language key exists (here or above)
          if(!empty($cm_texts[ $cm_lang ]['fd.' . $imodel . '.' . $attr])) {
            $ftxt = _txt('fd.' . $imodel . '.' . $attr);
          } else {
            // Otherwise see if the attribute by itself exists
            $ftxt = _txt('fd.' . $attr);
          }
        }
      }
      
      // Finally, render the change string based on the attributes found above.
      // Notate going to or from NULL only if $newdata or $olddata (as appropriate)
      // was populated, so as to avoid noise when a related object is added or
      // deleted.
      
      if(isset($newval) && !isset($oldval)) {
        $changes[] = $ftxt . ": " . (isset($olddata) ? _txt('fd.null') . " > " : "") . $newval;
      } elseif(!isset($newval) && isset($oldval)) {
        $changes[] = $ftxt . ": " . $oldval . (isset($newdata) ? " > " . _txt('fd.null') : "");
      } elseif(isset($newval) && isset($oldval) && ($newval != $oldval)) {
        $changes[] = $ftxt . ": " . $oldval . " > " . $newval;
      }
    }
    
    return $changes;
  }
  
  /**
   * Compare two arrays and generate a string describing what changed, suitable for
   * including in a history record.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Array New data, in typical Cake format
   * @param  Array Old data, in typical Cake format
   * @param  Integer CO ID, if known
   * @param  Array Additional Models to examine within new and old data
   * @param  Array Array of Extended Attributes, if known
   * @return String String describing changes
   */
    
  public function changesToString($newdata, $olddata, $coId = null, $additionalModels = array(), $extendedAttrs = array()) {
    // We assume $newdata and $olddate are intended to have the same structure, however
    // we require $models to be specified since different controllers may pull different
    // levels of containable or recursion data, and so we don't know how many associated
    // models will appear in $newdata and/or $olddata.
    
    $changes = array();
    
    $models = array($this->name);
    $models = array_merge($models, $additionalModels);
    
    foreach($models as $model) {
      if($model == 'ExtendedAttribute') {
        // Handle extended attributes differently, as usual
        
        if(isset($extendedAttrs)) {
          // First, calculate the real model name
          $eaModel = "Co" . $coId . "PersonExtendedAttribute";
          
          foreach($extendedAttrs as $extAttr) {
            $oldval = null;
            $newval = null;
            
            // Grab the name of this attribute and lowercase it to match the data model
            $eaName = strtolower($extAttr['CoExtendedAttribute']['name']);
            $eaDisplayName = $extAttr['CoExtendedAttribute']['display_name'];
            
            // Try to find the attribute in the data
            
            if(isset($newdata[$eaModel][$eaName]) && ($newdata[$eaModel][$eaName] != "")) {
              $newval = $newdata[$eaModel][$eaName];
            }
            
            if(isset($olddata[$eaModel][$eaName]) && ($olddata[$eaModel][$eaName] != "")) {
              $oldval = $olddata[$eaModel][$eaName];
            }
            
            if(isset($newval) && !isset($oldval)) {
              $changes[] = $eaDisplayName . ": " . _txt('fd.null') . " > " . $newval;
            } elseif(!isset($newval) && isset($oldval)) {
              $changes[] = $eaDisplayName . ": " . $oldval . " > " . _txt('fd.null');
            } elseif(isset($newval) && isset($oldval) && ($newval != $oldval)) {
              $changes[] = $eaDisplayName . ": " . $oldval . " > " . $newval;
            }
          }
        }
      } else {
        // Walk through old and new data to correlate records, and while we're at it
        // assemble the set of attributes to process
        
        $indexedModels = array();
        $attrs = array();
        
        if(isset($newdata[$model][0]) || isset($olddata[$model][0])) {
          // We've got at least one instance of this model to look at (and for now, that's probably
          // all we'll get) (eg: $data['TelephoneNumber'][0])
          
          foreach($olddata[$model] as $o) {
            if(!empty($o['id'])) {
              $indexedModels[ $o['id'] ]['old'] = $o;
              
              // Identify the attributes associated with this model
              $attrs = array_unique(array_merge($attrs, array_keys($o)));
            }
            // else no old data (new record added)
          }
          
          foreach($newdata[$model] as $n) {
            if(!empty($n['id'])) {
              $indexedModels[ $n['id'] ]['new'] = $n;
              
              // Identify the attributes associated with this model
              $attrs = array_unique(array_merge($attrs, array_keys($n)));
            } elseif(!empty($n)) {
              // New record, no ID so use special notation
              
              $indexedModels['new']['new'] = $n;
              
              // Identify the attributes associated with this model
              $attrs = array_unique(array_merge($attrs, array_keys($n)));
            }
            // else no new data (old record deleted)
          }
        } else {
          // Single instance model (eg:$data['CoPerson'])
          
          if(!empty($olddata[$model]['id'])) {
            $indexedModels[ $olddata[$model]['id'] ]['old'] = $olddata[$model];
            
            // Identify the attributes associated with this model
            $attrs = array_unique(array_merge($attrs, array_keys($olddata[$model])));
          }
          // else no old data (new record added)
          
          if(!empty($newdata[$model]['id'])) {
            $indexedModels[ $newdata[$model]['id'] ]['new'] = $newdata[$model];
            
            // Identify the attributes associated with this model
            $attrs = array_unique(array_merge($attrs, array_keys($newdata[$model])));
          } elseif(!empty($newdata[$model])) {
            // New record, no ID so use special notation
            
            $indexedModels['new']['new'] = $newdata[$model];
            
            // Identify the attributes associated with this model
            $attrs = array_unique(array_merge($attrs, array_keys($newdata[$model])));
          }
          // else no new data (old record deleted)
        }
        
        foreach(array_keys($indexedModels) as $mid) {
          // Note $mid is typically an ID, but can also be the literal 'new' (see above)
          $changes = array_merge($changes,
                                 $this->changesForModel($model,
                                                        (isset($indexedModels[$mid]['new']) ? $indexedModels[$mid]['new'] : array()),
                                                        (isset($indexedModels[$mid]['old']) ? $indexedModels[$mid]['old'] : array()),
                                                        $coId,
                                                        $attrs));
        }
      }
    }
    
    return implode(';', $changes);
  }
  
  /**
   * Check if an identifier or email address is available for use, ie
   * if it is not defined (regardless of status) within the same CO.
   *
   * IMPORTANT: This function should be called within a transaction to ensure
   * actions taken based on availability are atomic.
   *
   * @since  COmanage Registry v0.6
   * @param  String  $identifier     Candidate identifier or email address
   * @param  String  $identifierType Type of candidate identifier or email address
   * @param  Integer $coId           CO ID
   * @param  Boolean $emailUnique    If true, email addresses must be unique within the CO
   * @return Boolean True if identifier or email address is not in use
   * @throws InvalidArgumentException If $identifier is not of the correct format
   * @throws OverflowException If $identifier is already in use
   * @throws RuntimeException
   * @todo   Since this only currently supports EmailAddress and Identifer, this could go in an intermediate model instead
   */
  
  public function checkAvailability($identifier, $identifierType, $coId, $emailUnique=false) {
    $mname = $this->name;
    // Currently we support Identifier and EmailAddress
    $mattr = ($this->name == 'Identifier' ? 'identifier' : 'mail');
    
    // In order to allow ensure that another process doesn't perform the same
    // availability check while we're running, we need to lock the appropriate
    // tables/rows at read time. We do this with findForUpdate instead of a normal find.
    
    // Ordinarily we don't require EmailAddress to be unique within the CO,
    // unless (eg) it's being assigned via Identifier Assignment.
    
    if($mname != 'EmailAddress' || $emailUnique) {
      $args = array();
      $args['conditions']['CoPerson.co_id'] = $coId;
      $args['conditions'][$mname.'.'.$mattr] = $identifier;
      if($mname == 'Identifier') {
        // For email address, uniqueness is regardless of type
        $args['conditions'][$mname.'.type'] = $identifierType;
      }
      $args['joins'][0]['table'] = 'co_people';
      $args['joins'][0]['alias'] = 'CoPerson';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id='.$mname.'.co_person_id';
      $args['contain'] = false;
      
      $r = $this->findForUpdate($args['conditions'],
                                array($mattr),
                                $args['joins']);
      
      if(!empty($r)) {
        throw new OverflowException(_txt('er.ia.exists', array($identifier)));
      }
    }
    
    // Our internal availability check is clear.
    // Next see if there are any validators defined.
    
    $args = array();
    $args['conditions']['CoIdentifierValidator.co_id'] = $coId;
    $args['conditions']['CoIdentifierValidator.status'] = SuspendableStatusEnum::Active;
    $args['contain'][] = 'CoExtendedType';
    
    $validators = $this->CoPerson->Co->CoIdentifierValidator->find('all', $args);
    
    if(!empty($validators)) {
      // Load the related plugins in case we need them
      $plugins = $this->loadAvailablePlugins('identifiervalidator');
      
      foreach($validators as $v) {
        // See if this validator is configured for this attribute type.
        // The validator type is actually in the related model pulled via foreign key.
        
        if($v['CoExtendedType']['attribute'] == $mname.'.type'
           && $v['CoExtendedType']['name'] == $identifierType) {
          // Run this plugin. If more than one plugin is configured, we'll run
          // them all but fail on the first one that fails.
          
          try {
            $plugin = $v['CoIdentifierValidator']['plugin'];
            $pname = $plugin . '.' . $plugin;
            $pmodel = $plugins[$pname];
            $pcfg = array();
        
            if($pmodel->cmPluginInstantiate) {
              // Pull the relevant plugin config to pass to the plugin
              
              $args = array();
              $args['conditions'][ $plugin . '.co_identifier_validator_id' ] = $v['CoIdentifierValidator']['id'];
              $args['contain'] = false;
              
              $pcfg = $pmodel->find('first', $args);
            }
            
            $pmodel->validate($identifier,
                              $v['CoIdentifierValidator'],
                              $v['CoExtendedType'],
                              (!empty($pcfg) ? $pcfg[$plugin] : null));
          }
          catch(InvalidArgumentException $e) {
            // Bad format
            throw new InvalidArgumentException(_txt('er.id.format-a', array($identifier, $e->getMessage())));
          }
          catch(OverflowException $e) {
            // In use
            throw new OverflowException(_txt('er.id.exists-a', array($identifier, $e->getMessage())));
          }
          catch(RuntimeException $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Compare changes in a two arrays worth of a model's data.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String  $model   Model being examined
   * @param  Array   $newdata New data, in Cake single instance format
   * @param  Array   $olddata Old data, in Cake single instance format
   * @param  Integer $coId    CO ID, if known
   * @return Array Array of changes, empty if none
   */
  
  public function compareChanges($model, $newdata, $olddata, $coId=null) {
    // We'll use changesForModel since it already does a lot of the work we need
    
    $attrs = array_unique(array_merge(array_keys($newdata), array_keys($olddata)));
    
    return $this->changesForModel($model, $newdata, $olddata, $coId, $attrs);
  }
  
  /**
   * For models that support Extended Types, obtain the default types for the specified attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  String Model attribute to obtain defaults for
   * @return Array Default types as key/value pair of name and localized display_name
   */
  
  public function defaultTypes($attribute) {
    $ret = null;
    
    if(isset($this->validate[$attribute]['content']['rule'])
       && is_array($this->validate[$attribute]['content']['rule'])
       && $this->validate[$attribute]['content']['rule'][0] == 'validateExtendedType'
       && is_array($this->validate[$attribute]['content']['rule'][1])
       && isset($this->validate[$attribute]['content']['rule'][1]['default'])) {
      // Figure out which language key to use. Note 'en' is the prefix for 'enum'
      // and NOT an abbreviation for 'english'.
      $langKey = 'en.' . Inflector::underscore($this->name) . '.' . $attribute;
      
      foreach($this->validate[$attribute]['content']['rule'][1]['default'] as $name) {
        $ret[$name] = _txt($langKey, null, $name);
      }
    }
    
    return $ret;
  }
  
  /**
   * Wrap the standard delete behavior to handle Changelog enabled models.
   * 
   * @since  COmanage Registry v0.9.4
   * @param  Integer $id Model ID to delete
   * @param  Boolean $cascade Whether to cascade the delete to related models
   * @return Boolean True on success, false otherwise
   */
  
  public function delete($id = null, $cascade = true) {
    // Because of limitations in Cake 2, we need to do some extra work here for
    // ChangelogBehavior. For a discussion of why, see this thread:
    // https://groups.google.com/forum/?fromgroups#!topic/cakephp-core/2vIZN8Sq8RE
    // It is likely this code can be refactored into a less hacky implementation
    // with Cake 3.
    
    if($this->Behaviors->enabled('Changelog')) {
      // The first problem is that ChangelogBehavior must implement soft delete in
      // beforeDelete(), and then must return false to prevent Cake from hard
      // deleting the record. This has the unfortunate side effect of preventing
      // any subsequent behaviors from firing. However, we need ProvisionerBehavior
      // to run before ChangelogBehavior in order to cache data prior to the delete
      // (normally the order is the opposite). A more general solution would probably
      // be to invert the order of all Behaviors, but for now we simply ensure that
      // Provisioner runs before Changelog.
      
      $this->reloadBehavior('Provisioner', array('priority' => '1'));
    }
    
    // Now run the actual delete.
    $ret = parent::delete($id, $cascade);
    
    if($ret === false && $this->Behaviors->enabled('Changelog')) {
      // Check that the deleted field was set in lieu of the (incorrect) return
      // code from delete() (which will always be false with Changelog behavior).
      
      if((bool)$this->field('deleted')) {
        // Now that the (soft) delete was successful, manually fire the afterDelete
        // callbacks. These otherwise wouldn't run due to the false return.
        
        $this->getEventManager()->dispatch(new CakeEvent('Model.afterDelete', $this));
        $this->_clearCache();
        
        return true;
      } else {
        return false;
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
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {
    if($this->alias == 'CakeError') return;
    
    // We need to find a corresponding CO ID, which may or may not be directly in the model.
    
    if(isset($this->validate['co_id'])) {
      // This model directly references a CO
      
      $coId = $this->field('co_id', array($this->alias.".id" => $id));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array($this->alias, $id)));
      }
    } elseif(isset($this->validate['co_person_id'])) {
      // Find the CO via the CO Person
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoPerson';
      if(isset($this->validate['org_identity_id'])) {
        // This is an MVPA
        $args['contain'][] = 'OrgIdentity';
      }
      
      $cop = $this->find('first', $args);
      
      if(!empty($cop['CoPerson']['co_id'])) {
        return $cop['CoPerson']['co_id'];
      }
      
      // Is this an MVPA where this is an org identity?
      
      if(!empty($cop['OrgIdentity']['co_id'])) {
        return $cop['OrgIdentity']['co_id'];
      }
      
      // If this is an MVPA, don't fail on no CO ID since that may not be the current configuration
      
      if(empty($cop[ $this->alias ]['co_person_id'])
         && !empty($cop[ $this->alias ]['org_identity_id'])) {
        return null;
      }
    } elseif(isset($this->validate['co_person_role_id'])) {
      // Find the CO via the CO Person via the CO Person Role
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoPersonRole';
      if(isset($this->validate['org_identity_id'])) {
        // This is an MVPA
        $args['contain'][] = 'OrgIdentity';
      }
      
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
      
      if(!empty($copr['OrgIdentity']['co_id'])) {
        return $copr['OrgIdentity']['co_id'];
      }
      
      // If this is an MVPA, don't fail on no CO ID since that may not be the current configuration
      
      if(empty($copr[ $this->alias ]['co_person_id'])
         && !empty($copr[ $this->alias ]['org_identity_id'])) {
        return null;
      }
    } elseif(isset($this->validate['co_identifier_validator_id'])) {
      // XXX this plugin type-specific logic should move elsewhere (CO-1321)
      // Identifier Validator plugins will refer to an identifier validator
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'CoIdentifierValidator';
    
      $copt = $this->find('first', $args);
      
      if(!empty($copt['CoIdentifierValidator']['co_id'])) {
        return $copt['CoIdentifierValidator']['co_id'];
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
    } elseif(isset($this->validate['org_identity_source_id'])) {
      // Org Identity Source plugins will refer to an org identity source
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'OrgIdentitySource';
    
      $copt = $this->find('first', $args);
      
      if(!empty($copt['OrgIdentitySource']['co_id'])) {
        return $copt['OrgIdentitySource']['co_id'];
      }
    } elseif(preg_match('/Co[0-9]+PersonExtendedAttribute/', $this->alias)) {
      // Extended attributes need to be handled specially, as usual, since there
      // are no explicit validation rules. Find the CO via the CO Person Role ID.
      // (ie: We don't trust the Model name in order to follow the general pattern
      // of lookup the CO ID from the relevant record, though probably we could
      // just trust it.)
      
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
   * @param  Integer Maximium number of results to retrieve
   * @param  Integer Offset to start retrieving results from
   * @param  String Field to sort by
   * @return Array Result set as returned by Cake fetchAll() or read(), which isn't necessarily the same format as find()
   */
  
  public function findForUpdate($conditions, $fields, $joins = array(), $limit=null, $offset=null, $order=null) {
    $dbc = $this->getDataSource();
    
    $args['conditions'] = $conditions;
    $args['fields'] = $dbc->fields($this, null, $fields);
    $args['table'] = $dbc->fullTableName($this->useTable);
    $args['alias'] = $this->alias;
    // Don't allow joins to be NULL, make it an empty array if not set
    $args['joins'] = ($joins ? $joins : array());
    
    $args['order'] = $order;
    $args['offset'] = $offset;
    $args['limit'] = $limit;
    
    // Appending to the generated query should be fairly portable.
    // We use buildQuery to ensure callbacks (such as ChangelogBehavior) are
    // invoked, then buildStatement to turn it into SQL.
    
    return $dbc->fetchAll($dbc->buildStatement($this->buildQuery('all', $args), $this) . " FOR UPDATE", array(), array('cache' => false));
  }
  
  /**
   * Determine if the current model represents a plugin of the specified type.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $type Plugin type (eg: "provisioner"), or null for any type
   * @return True if the model is a plugin of the requested type, false otherwise
   */
  
  public function isPlugin($type = null) {
    if(isset($this->cmPluginType)) {
      // cmPluginType can either be an array or a string
      if(is_array($this->cmPluginType)) {
        if(!$type)
          return true;
        
        return in_array($type, $this->cmPluginType);
      } elseif(is_string($this->cmPluginType)) {
        if(!$type)
          return true;
        
        return $this->cmPluginType == $type;
      }
    }
    
    return false;
  }
  
  /**
   * Determine which plugins of a given type are available, and load them if not already loaded.
   *
   * @param  String Plugin type, or 'all' for all available plugins
   * @since  COmanage Registry v2.0.0
   * @return Array Available plugins, ModelName => ModelPointer format
   * @todo   Merge with AppController::loadAvailablePlugins
   */
  
  public function loadAvailablePlugins($pluginType = 'all') {
    $ret = array();
    
    foreach(App::objects('plugin') as $p) {
      $pluginModelName = $p . "." . $p;
      $pluginModel = ClassRegistry::init($pluginModelName);
        
      if($pluginModel->isPlugin($pluginType != 'all' ? $pluginType : null)) {
        $ret[ $pluginModelName ] = $pluginModel;
      }
    }
    
    return $ret;
  }
  
  /**
   * Recursively reload a behavior for a model and it's dependent=true related models.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $behavior Name of behavior to disable
   * @param  Array $params Argumenths to pass to Behavior
   */
  
  public function reloadBehavior($behavior, $params = array()) {
    if($this->Behaviors->enabled($behavior)) {
      // We only want to update the configuration of already loaded behaviors.
      // ie: We don't want to add changelog behavior to a model that isn't set up
      // for it.
      
      $this->Behaviors->load($behavior, $params);
    }
    
    foreach(array_merge($this->hasMany, $this->hasOne) as $assoc => $data) {
      if($data['dependent'] === true) {
        $this->$assoc->reloadBehavior($behavior, $params);
      }
    }
  }
  
  /**
   * Set the current timezone for use within the model.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $tz Timezone, eg as determined by AppController::beforeFilter
   */
  
  public function setTimeZone($tz) {
    // This is initially intended for CO Person Role, Org Identity, and CO Petition to
    // be able to convert valid from/through from browser localtime to UTC on save.
    
    $this->tz = $tz;
  }
  
  /**
   * Check if a given extended type is in use by any members of a CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String Attribute, of the form Model.field
   * @param  String Type of attribute (any default or extended type may be specified)
   * @param  Integer CO ID
   * @return Boolean True if the extended type is in use, false otherwise
   */
  
  public function typeInUse($attribute, $attributeType, $coId) {
    $args = array();
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['conditions'][$attribute] = $attributeType;
    $args['contain'] = false;
    
    // Is this model attached to CO Person or CO Person Role?
    if(!empty($this->validate['co_person_id'])) {
      $args['joins'][0]['table'] = 'co_people';
      $args['joins'][0]['alias'] = 'CoPerson';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=' . $this->alias . '.co_person_id';
    } elseif(!empty($this->validate['co_person_role_id'])) {
      $args['joins'][0]['table'] = 'co_person_roles';
      $args['joins'][0]['alias'] = 'CoPersonRole';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPersonRole.id=' . $this->alias . '.co_person_role_id';
      $args['joins'][1]['table'] = 'co_people';
      $args['joins'][1]['alias'] = 'CoPerson';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'CoPersonRole.co_person_id=CoPerson.id';
    } else {
      throw new RuntimeException(_txt('er.notimpl'));
    }
    
    return (boolean)$this->find('count', $args);
  }
  
  /**
   * For models that support Extended Types, obtain the valid types for the specified CO and attribute.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO ID
   * @param  String Attribute to retrieve
   * @return Array Defined types (including defaults if no extended types) in key/value form suitable for select buttons.
   */
  
  public function types($coId, $attribute) {
    $ret = array();
    
    $CoExtendedType = ClassRegistry::init('CoExtendedType');
    
    return $CoExtendedType->active($coId, $this->name . "." . $attribute, 'list');
  }
  
  /**
   * Update the validation rules for the model based on dynamic configurations.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coId Current CO ID, if known and applicable
   * @return Boolean True on success
   */
  
  public function updateValidationRules($coId = null) {
    $AttributeEnumeration = ClassRegistry::init('AttributeEnumeration');
    
    $enumAttrs = $AttributeEnumeration->supportedAttrs();
    
    // Walk through the list of attributes supported for enumeration to see if any
    // belong to the current model
    foreach($enumAttrs as $attr => $label) {
      $a = explode('.', $attr, 2);
      
      if($a[0] == $this->name) {
        // Model is a match. See if there are any defined enums.
        
        $enums = $AttributeEnumeration->active($coId, $attr, 'validation');
        
        if(!empty($enums)) {
          // Enumerations defined, update the validation rule
          $this->validate[ $a[1] ]['content']['rule'] = $enums;
        }
      }
    }
    
    return true;
  }
  
  /**
   * Determine if a string is a valid extended type.
   *
   * @since  COmanage Registry v0.6
   * @param  array Array of fields to validate
   * @param  array Array with up to three keys: 'attribute' holding the attribute model name, and 'default' holding an Array of default values (for use if no extended types are defined), and 'coid' holding the CO ID (optional)
   * @return boolean True if all field strings parses to a valid timestamp, false otherwise
   * @todo   'attribute' should really be called 'model'
   */
  
  public function validateExtendedType($a, $d) {
    // First obtain active extended types, if any.
    
    $extTypes = array();
    $coId = null;
    
    if(!empty($d['coid'])) {
      $coId = $d['coid'];
    }
    
    if($coId) {
      $CoExtendedType = ClassRegistry::init('CoExtendedType');
      
      $extTypes = $CoExtendedType->active($coId, $d['attribute']);
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
   * Determine if a string is a valid input.
   *
   * @since  COmanage Registry v1.0.6
   * @param  array Array of fields to validate
   * @param  array Array Supported options: 'filter' and 'flags', corresponding to filter_var options, or "invalidchars" as a string of not permitted characters
   * @return mixed True if all field strings validate, an error message otherwise
   */
  
  public function validateInput($a, $d) {
    // By default, we'll accept anything except < and >. Arguably, we should accept
    // anything at all for input (and filter only on output), but this was agreed to
    // as an extra "line of defense" against unsanitized HTML output, since there are
    // currently no known cases where user-entered input should permit angle brackets.
    
    if(!empty($d['filter']) || !empty($d['flags'])) {
      // If requested, we'll use PHP's filter_var() (to match output filters).
      // eg: Used by Email Address to match email format.
      
      $filter = FILTER_SANITIZE_SPECIAL_CHARS;
      $flags = null;
      
      if(!empty($d['filter'])) {
        // Use the requested filter instead
        $filter = $d['filter'];
      }
      
      if(!empty($d['flags'])) {
        $flags = $d['flags'];
      }
      
      foreach($a as $k => $v) {
        // We use filter_var for consistency with the views, and simply check
        // that we end up with the same string we started with.
        
        $filtered = filter_var($v, $filter, $flags);
        
        if($v != $filtered) {
          // Mismatch, implying bad input
          return _txt('er.input.invalid');
        }
      }
    } else {
      // Perform a basic string search.
      
      $invalid = "<>";
      
      // We use isset here rather than !empty because we'll accept an empty string
      // as a way to skip the check.
      if(isset($d['invalidchars'])) {
        $invalid = $d['invalidchars'];
      }
      
      foreach($a as $k => $v) {
        if(strlen($v) != strcspn($v, $invalid)) {
          // Mismatch, implying bad input
          return _txt('er.input.invalid');
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
      // translated names. Note as of v2.0.0 there may not be "translated"
      // names (ie: for attribute enumerations), in which case we just want
      // the original string.
      
      foreach($this->validate[$field]['content']['rule'][1] as $key) {
        if(isset($this->cm_enum_txt[$field])) {
          $ret[$key] = _txt($this->cm_enum_txt[$field], NULL, $key);
        } else {
          $ret[$key] = $key;
        }
      }
    }
    
    return $ret;
  }
}
