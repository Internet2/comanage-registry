<?php
/**
 * COmanage Registry Names Controller
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("MVPAController", "Controller");

class NamesController extends MVPAController {
  // Class name, used by Cake
  public $name = "Names";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'Name.family' => 'asc'
    )
  );

  public $edit_contains = array(
    'CoPerson' => array('PrimaryName'),
    'OrgIdentity' => array('PrimaryName')
  );

  public $view_contains = array(
    'CoPerson' => array('PrimaryName'),
    'OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'),
                           'PrimaryName'),
    'SourceName'
  );
  
  /**
   * Callback to set relevant tab to open when redirecting to another page
   *
   * @since  COmanage Registry v0.8.3
   */

  function beforeFilter() {
    $this->redirectTab = 'name';
    
    parent::beforeFilter();
  }

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Set required and permitted fields according to whether or not this is attached to a CO Person (Role)
      
      $pids = $this->parsePersonID();
      
      if($pids['copersonid']) {
        $this->set('vv_required_fields', $this->Name->CoPerson->Co->CoSetting->getRequiredNameFields($this->cur_co['Co']['id']));
        $this->set('vv_permitted_fields', $this->Name->CoPerson->Co->CoSetting->getPermittedNameFields($this->cur_co['Co']['id']));
      } else {
        // Always use default settings for org identities
        $this->set('vv_required_fields', $this->Name->CoPerson->Co->CoSetting->getRequiredNameFields());
        $this->set('vv_permitted_fields', $this->Name->CoPerson->Co->CoSetting->getPermittedNameFields($this->cur_co['Co']['id']));
      }
    }
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // We can't delete a primary name
    
    if($curdata['Name']['primary_name']) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Primary Name Cannot Be Deleted");
      } else {
        $this->Flash->set(_txt('er.nm.primary',
                               array(generateCn($curdata['Name']))),
                          array('key' => 'error'));
      }
      
      return false;
    }
    
    return true;
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.9
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
 
  public function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($this->request->data['Name']))
      return(generateCn($this->request->data['Name']));
    elseif(isset($c['Name']))
      return(generateCn($c['Name']));
    else
      return("(?)");
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.8.3
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    // Note: We are overriding MVPAContrller::generateHistory here.
    
    $copersonid = null;
    $orgidentityid = null;
    $HistoryRecord = null;
    $cn = "";
    
    // Find some pointers according to the data
    if(isset($newdata['Name']['co_person_id'])) {
      $copersonid = $newdata['Name']['co_person_id'];
      $cn = generateCn($newdata['Name']);
      $HistoryRecord = $this->Name->CoPerson->HistoryRecord;
    } elseif(isset($newdata['Name']['org_identity_id'])) {
      $orgidentityid = $newdata['Name']['org_identity_id'];
      $cn = generateCn($newdata['Name']);
      $HistoryRecord = $this->Name->OrgIdentity->HistoryRecord;
    } elseif(isset($olddata['Name']['co_person_id'])) {
      $copersonid = $olddata['Name']['co_person_id'];
      $cn = generateCn($olddata['Name']);
      $HistoryRecord = $this->Name->CoPerson->HistoryRecord;
    } elseif(isset($olddata['Name']['org_identity_id'])) {
      $orgidentityid = $olddata['Name']['org_identity_id'];
      $cn = generateCn($olddata['Name']);
      $HistoryRecord = $this->Name->OrgIdentity->HistoryRecord;
    }
    
    switch($action) {
      case 'add':
        $HistoryRecord->record($copersonid,
                               null,
                               $orgidentityid,
                               $this->Session->read('Auth.User.co_person_id'),
                               ActionEnum::NameAdded,
                               _txt('rs.added-a2', array(_txt('ct.names.1'), $cn)));
        break;
      case 'delete':
        $HistoryRecord->record($copersonid,
                               null,
                               $orgidentityid,
                               $this->Session->read('Auth.User.co_person_id'),
                               ActionEnum::NameDeleted,
                               _txt('rs.deleted-a2', array(_txt('ct.names.1'), $cn)));
        break;
      case 'edit':
        $HistoryRecord->record($copersonid,
                               null,
                               $orgidentityid,
                               $this->Session->read('Auth.User.co_person_id'),
                               ActionEnum::NameEdited,
                               _txt('rs.updated-a2', array(_txt('ct.names.1'), $cn)));
        break;
      case 'primary':
        $HistoryRecord->record($copersonid,
                               null,
                               $orgidentityid,
                               $this->Session->read('Auth.User.co_person_id'),
                               ActionEnum::NamePrimary,
                               _txt('rs.nm.primary-a', array($cn)));
        break;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    
    $readOnly = false;
    
    // Is this a read only record? True if it belongs to an Org Identity that has
    // an OrgIdentity Source Record. As of the initial implementation, not even
    // CMP admins can edit such a record.
    
    if($this->action == 'edit' && !empty($this->request->params['pass'][0])) {
      $sourceAttributeId = $this->Name->field('source_name_id', array('id' => $this->request->params['pass'][0]));

      if($sourceAttributeId) {
        $readOnly = true;
      } else {
        $orgIdentityId = $this->Name->field('org_identity_id', array('id' => $this->request->params['pass'][0]));
        
        if($orgIdentityId) {
          $readOnly = $this->Name->OrgIdentity->readOnly($orgIdentityId);
        }
      }
    }
    
    if($readOnly) {
      // Proactively redirect to view. This will also prevent (eg) the REST API
      // from editing a read only record.
      $args = array(
        'controller' => 'names',
        'action'     => 'view',
        filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
      );
      
      $this->redirect($args);
    }
    
    // In order to manipulate an email address, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    $self = false;
    $name = null;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
          
          if($pids['copersonid'] == $roles['copersonid']) {
            $self = true;
          }
        } elseif(!empty($pids['orgidentityid'])) {
          $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                               $pids['orgidentityid']);
        }
        break;
      case 'delete':
      case 'edit':
      case 'view':
        if(!empty($this->request->params['pass'][0])) {
          // look up $this->request->params['pass'][0] and find the appropriate co person id or org identity id
          // then pass that to $this->Role->isXXX
          $args = array();
          $args['conditions']['Name.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $name = $this->Name->find('first', $args);
          
          if(!empty($name['Name']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $name['Name']['co_person_id']);
            
            if($name['Name']['co_person_id'] == $roles['copersonid']) {
              $self = true;
            }
          } elseif(!empty($name['Name']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $name['Name']['org_identity_id']);
          }
        }
        break;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Self service is a bit complicated because permission can vary by type.
    // Self service only applies to CO Person-attached attributes.
    
    $selfperms = array(
      'add'    => false,
      'delete' => false,
      'edit'   => false,
      'view'   => false
    );
    
    if($self) {
      foreach(array_keys($selfperms) as $a) {
        $selfperms[$a] = $this->Name
                              ->CoPerson
                              ->Co
                              ->CoSelfServicePermission
                              ->calculatePermission($this->cur_co['Co']['id'],
                                                    'Name',
                                                    $a,
                                                    ($a != 'add' && !empty($name['Name']['type']))
                                                     ? $name['Name']['type'] : null);
      }
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = null;
    }
    
    // Add a new Name?
    $p['add'] = ($roles['cmadmin']
                 || $roles['coadmin']
                 || ($managed && $roles['couadmin'])
                 || $selfperms['add']);
    
    // Delete an existing Name?
    $p['delete'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || ($managed && $roles['couadmin'])
                    || $selfperms['delete']);
    
    // Edit an existing Name?
    $p['edit'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || ($managed && $roles['couadmin'])
                  || $selfperms['edit']);
    // Making a name primary is the same as editing
    $p['primary'] = $p['edit'];
    
    // View all existing Names?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Name?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || ($managed && $roles['couadmin'])
                  || $selfperms['view']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Make a name primary for the associated Org Identity or CO Person.
   * - precondition: <id> must exist
   * - precondition: copersonid or orgidentityid must be provided in the URL
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On success, all related data (any table with an <object>_id column) is deleted
   *
   * @since  COmanage Registry v0.8.3
   * @param  integer Name identifier
   * @throws InvalidArgumentException
   */
  
  public function primary($id) {
    $ret = false;
    
    // We create a transaction here rather than in Name->beforeSave because the model
    // can't guarantee a rollbock on any error.
    $dbc = $this->Name->getDataSource();
    $dbc->begin($this);
    
    // Set the new primary name
    
    // Read the current data for this name
    $args = array();
    $args['conditions']['Name.id'] = $id;
    $args['contain'] = false;
    
    $curdata = $this->Name->find('first', $args);
    
    $this->Name->id = $id;
    
    if($this->Name->saveField('primary_name', true)) {
      // Reread the current data for this name
      
      $newdata = $this->Name->find('first', $args);
      
      if($this->recordHistory('primary', $newdata, $curdata)) {
        $ret = true;
      }
    }
    
    if($ret) {
      $dbc->commit($this);
      
      $this->Flash->set(_txt('rs.nm.primary'), array('key' => 'success'));
    } else {
      $dbc->rollback($this);
      
      $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
    }
    
    if(!$this->request->is('restful')) {
      // Redirect
      
      if(!empty($this->Name->OrgIdentity->data)) {
        $this->checkPersonId("force", $this->Name->OrgIdentity->data);
      } else {
        $this->checkPersonId("force", $this->Name->CoPerson->data);
      }
    }
  }
}
