<?php
/**
 * COmanage Registry Dictionary Entries Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class DictionaryEntriesController extends StandardController {
  public $requires_co = true;
  
  // Class name, used by Cake
  public $name = "DictionaryEntries";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc',
      'value' => 'asc'
    )
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   * @todo   This is copied from SIVController
   */

  public function beforeRender() {
    parent::beforeRender();

    if(!$this->request->is('restful')) {
      // Figure out our Dictionary

      $dictid = null;

      if($this->action == 'add' || $this->action == 'index' || $this->action == 'populate' || $this->action == 'upload') {
        // Accept dictionary id from the url or the form

        if(!empty($this->request->params['named']['dictionary'])) {
          $dictid = filter_var($this->request->params['named']['dictionary'],FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(!empty($this->request->data['DictionaryEntry']['dictionary_id'])) {
          $dictid = filter_var($this->request->data['DictionaryEntry']['dictionary_id'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Map the dictionary from the requested object

        $dictid = $this->DictionaryEntry->field('dictionary_id',
                                                array('id' => $this->request->params['pass'][0]));
      }

      $dict = $this->DictionaryEntry->Dictionary->field('description', array('Dictionary.id' => $dictid));

      // Override page title
      $this->set('title_for_layout', $this->viewVars['title_for_layout'] . " (" . $dict . ")");
      $this->set('vv_dict_name', $dict);
      $this->set('vv_dict_id', $dictid);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // If a dictionary is specified, use it to get to the CO ID

    $dict = null;

    if(($this->action == 'add' || $this->action == 'index' || $this->action == 'populate' || $this->action == 'upload')
       && !empty($this->params->named['dictionary'])) {
      $dict = $this->params->named['dictionary'];
    } elseif(!empty($this->request->data['DictionaryEntry']['dictionary_id'])) {
      $dict = $this->request->data['DictionaryEntry']['dictionary_id'];
    }

    if($dict) {
      // While we're here, look up the mode of the requested dictionary.
      // We only allow Dictionary Entries to be managed for Standard Dictionaries.
      // (We could do this check in isAuthorized, but we're already processing $dict.)
      
      $mode = $this->DictionaryEntry->Dictionary->field('mode', array('Dictionary.id' => $dict));
      
      if($mode != DictionaryModeEnum::Standard) {
        // Suboptimally, this will manifest as "No CO Specified"
        throw new RuntimeException(_txt('er.dict.entry.mode'));
      }
      
      // Map Dictionary to CO

      $coId = $this->DictionaryEntry->Dictionary->field('co_id', array('Dictionary.id' => $dict));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.dictionaries.1'), $dict)));
      }
    }

    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Dictionary Entry?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Dictionary Entry?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Dictionary Entry?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Dictionary Entry?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Populate from a pre-defined Dictionary Entry set?
    $p['populate'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Upload Dictionary Entries?
    $p['upload'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Dictionary Entry?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current dictionary

    $ret = array();

    $ret['conditions']['DictionaryEntry.dictionary_id'] = $this->request->params['named']['dictionary'];

    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */

  function performRedirect() {
    if(!empty($this->request->data['DictionaryEntry']['dictionary_id'])) {
      // Redirect to the index of dictionary entries

      $target = array();
      $target['controller'] = 'dictionary_entries';
      $target['action'] = 'index';
      $target['dictionary'] = filter_var($this->request->data['DictionaryEntry']['dictionary_id'],FILTER_SANITIZE_SPECIAL_CHARS);

      $this->redirect($target);
    } elseif(isset($this->cur_co)) {
      // This is probably a delete, where we lost the context for which dictionary
      // we were working with. Not ideal, how do other nested models (CoEnrollmentAttribute)
      // deal with this?
      
      $target = array();
      $target['controller'] = 'dictionaries';
      $target['action'] = 'index';
      $target['co'] = $this->cur_co['Co']['id'];

      $this->redirect($target);
    }

    parent::performRedirect();
  }
  
  /**
   * Populate a Dictionary from a pre-defined source.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function populate() {
    // Pull the set of available dictionaries
    $dicts = $this->DictionaryEntry->predefinedDictionaries();

    if($this->request->is('get')) {
      asort($dicts, SORT_STRING);
      
      $this->set('vv_available_dictionaries', $dicts);
      $this->set('title_for_layout', _txt('op.populate'));
    } else {
      // The file name does not exist in the predefined list then return
      if(empty($dicts[$this->request->data['DictionaryEntry']['file']])) {
        throw new BadRequestException(_txt('er.invalid.params'));
      }

      try {
        // SecurityComponent should prevent the injection of arbitrary file paths,
        // but even then the specified file has to parse correctly as a Dictionary.
        $this->DictionaryEntry->uploadFromFile($this->request->data['DictionaryEntry']['dictionary_id'],
                                               // Same path is in DictionaryEntry.php, maybe define in bootstrap?
                                               APP . DS . 'Lib' . DS . 'Dictionaries' . DS . $this->request->data['DictionaryEntry']['file'],
                                               (bool)$this->request->data['DictionaryEntry']['replace']);
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
        
        $this->performRedirect();
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
  }
  
  /**
   * Upload a Dictionary.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function upload() {
    if($this->request->is('get')) {
      // Render the upload form
      
      $this->set('title_for_layout', _txt('op.upload.file'));
    } else {
      try {
        $this->DictionaryEntry->uploadFromFile($this->request->data['DictionaryEntry']['dictionary_id'],
                                               $this->request->data['DictionaryEntry']['file']['tmp_name'],
                                               (bool)$this->request->data['DictionaryEntry']['replace']);
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      
      $this->performRedirect();
    }
  }
}
