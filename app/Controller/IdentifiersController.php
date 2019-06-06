<?php
/**
 * COmanage Registry Identifiers Controller
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

App::uses("MVPAController", "Controller");

class IdentifiersController extends MVPAController {
  // Class name, used by Cake
  public $name = "Identifiers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'identifier' => 'asc'
    )
  );
  
  public $edit_contains = array(
    'CoDepartment',
    'CoPerson' => array('PrimaryName'),
    'CoProvisioningTarget',
    'OrgIdentity' => array('PrimaryName')
  );

  public $view_contains = array(
    'CoDepartment',
    'CoPerson' => array('PrimaryName'),
    'CoProvisioningTarget',
    'OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource'),
                           'PrimaryName'),
    'SourceIdentifier'
  );
  
  /**
   * Autoassign identifiers for a CO Person.
   * - precondition: $this->request->params holds CO ID and CO Person ID
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: Redirect issued (HTML)
   *
   * @since  COmanage Registry v0.6
   */  
  
  function assign() {
    if($this->request->is('restful')) {
      $this->Api->parseRestRequestDocument();
      
      $reqdata = $this->Api->getData();
      
      if(!empty($reqdata['co_person_id'])) {
        $copersonid = $reqdata['co_person_id'];
      } else {
        $this->Api->restResultHeader(403, "No Person Specified");
        return;
      }
      
      // Determine the CO ID from the CO Person ID
      
      $coid = $this->Identifier->CoPerson->field('co_id', array('CoPerson.id' => $copersonid));
    } else {
      // While the controller doesn't require_co, this method does.
      
      $coid = $this->parseCOID($this->request->data);
      $copersonid = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
    }
    
    if($coid != -1) {
      // Assign the identifiers, then walk through the result array and generate a flash message
      $res = $this->Identifier->assign($coid, $copersonid, $this->Session->read('Auth.User.co_person_id'));
      
      if(!empty($res)) {
        // Loop through the results and build result messages
        
        $errs = "";             // Unexpected errors
        $assigned = array();    // Identifiers that were assigned
        $existed = array();     // Identifiers that already existed
        
        foreach(array_keys($res) as $type) {
          if($res[$type] == 2) {
            $existed[] = $type;
          } elseif($res[$type] == 1) {
            $assigned[] = $type;
          } else {
            $errs .= $type . ": " . $res[$type] . "<br />\n";
          }
        }
        
        if($this->request->is('restful')) {
          if($errs != "") {
            $this->Api->restResultHeader(500, $errs);
          } else {
            $this->Api->restResultHeader(200, "OK");
          }
        } else {
          if($errs != "") {
            $this->Flash->set($errs, array('key' => 'error'));
          }
          
          if(!empty($assigned)) {
            $this->Flash->set(_txt('rs.ia.ok') . " (" . implode(',', $assigned) . ")",
                              array('key' => 'success'));
          }
          
          if(!empty($existed)) {
            $this->Flash->set(_txt('er.ia.already') . " (" . implode(',', $existed) . ")",
                              array('key' => 'information'));
          }
        }
      } else {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(200, "OK");
        } else {
          $this->Flash->set(_txt('er.ia.none'), array('key' => 'information'));
        }
      }
    } else {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CO Does Not Exist");
      } else {
        $this->Flash->set(_txt('er.co.unk'), array('key' => 'error'));
      }
    }
    
    if(!$this->request->is('restful')) {
      // Redirect to CO Person view
      $rargs['controller'] = 'co_people';
      $rargs['action'] = 'canvas';
      $rargs[] = $copersonid;
      
      $this->redirect($rargs);
    }
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Sets tab to open for redirects
    $this->redirectTab = 'id';
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    if(isset($this->viewVars['pool_org_identities'])
       && $this->viewVars['pool_org_identities']
       && isset($this->request->params['named']['copersonid'])) {
      // If org identities are pooled, we need to manually map from copersonid
      // since otherwise AppController won't
      
      $coId = $this->Identifier->CoPerson->field('co_id', array('id' => $this->request->params['named']['copersonid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    // Is this a read only record? True if it belongs to an Org Identity that has
    // an OrgIdentity Source Record, or if it has a source identity.
    // As of the initial implementation, not even CMP admins can edit such a record.
    
    if($this->action == 'edit' && !empty($this->request->params['pass'][0])) {
      $readOnly = false;
      
      $orgIdentityId = $this->Identifier->field('org_identity_id', array('id' => $this->request->params['pass'][0]));
      
      if($orgIdentityId) {
        $readOnly = $this->Identifier->OrgIdentity->readOnly($orgIdentityId);
      } else {
        $readOnly = (bool)$this->Identifier->field('source_identifier_id', array('id' => $this->request->params['pass'][0]));
      }
      
      if($readOnly) {
        // Proactively redirect to view. This will also prevent (eg) the REST API
        // from editing a read only record.
        $args = array(
          'controller' => 'identifiers',
          'action'     => 'view',
          filter_var($this->request->params['pass'][0])
        );
        
        $this->redirect($args);
      }
    }
    
    // In order to manipulate an identifier, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
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
          $args['conditions']['Identifier.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $identifier = $this->Identifier->find('first', $args);
          
          if(!empty($identifier['Identifier']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $identifier['Identifier']['co_person_id']);
          } elseif(!empty($identifier['Identifier']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $identifier['Identifier']['org_identity_id']);
          }
        }
        break;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Identifier?
    $p['add'] = ($roles['cmadmin']
                 || $roles['coadmin'] 
                 || ($managed && $roles['couadmin']));
    
    // Assign (autogenerate) Identifiers? (Same logic is in CoPeopleController)
    $p['assign'] = ($roles['cmadmin']
                    || $roles['coadmin'] 
                    || ($managed && $roles['couadmin']));
    
    // Delete an existing Identifier?
    $p['delete'] = ($roles['cmadmin']
                    || $roles['coadmin'] 
                    || ($managed && $roles['couadmin']));
    
    // Edit an existing Identifier?
    $p['edit'] = ($roles['cmadmin']
                  || $roles['coadmin'] 
                  || ($managed && $roles['couadmin']));
    
    // View all existing Identifier?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Identifier?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin'] 
                  || ($managed && $roles['couadmin']));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $data Array of data for calculating implied CO ID
   * @return Integer The CO ID if found, or -1 if not
   */

  function parseCOID($data = null) {
    if ($this->action == 'assign') {
      // API call, includes a CoPerson ID
      if(isset($data['co_person_id'])) {

        $args=array();
        $args['contain']=false;
        $args['conditions']['CoPerson.id'] = $data['co_person_id'];
        $coperson = $this->Identifier->CoPerson->find('first',$args);

        if(!empty($coperson)) {
          return $coperson['CoPerson']['co_id'];
        }
      }
    }

    return parent::parseCOID();
  }
}
