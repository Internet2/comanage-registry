<?php
/**
 * COmanage Registry CO People Controller
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoPersonRolesController extends StandardController {
  public $name = "CoPersonRoles";
  
  public $helpers = array('Time');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'Name.family' => 'asc',
      'Name.given' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // This controller allows a COU to be set
  public $allows_cou = true;

  // For CO Person group renderings, we need all CoGroup data, so we need more recursion
  public $edit_recursion = 2;
  public $view_recursion = 2;
  
  /**
   * Add a CO Person Role Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.2
   */
  
  function add() {
    if(!$this->restful && $this->request->is('get'))
    {
      // Create a stub person role. It's unclear that title should
      // autopopulate, and if it need not it's further unclear that we
      // really need to set this variable.
      
      $cop = $this->viewVars['co_people'];
      $copr['CoPersonRole']['title'] = $cop[0]['CoOrgIdentityLink'][0]['OrgIdentity']['title'];
      $copr['CoPersonRole']['co_person_id'] = $cop[0]['CoPerson']['id'];
      
      $this->set('co_person_roles', array(0 => $copr));
    }
    
    parent::add();
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $pool_org_identities set
   * - postcondition: $sponsors set
   *
   * @since  COmanage Registry v0.2
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    if(!$this->restful)
    {
      // We need CO Person information for the view as well. We also want Name,
      // so we increase recursion.
      
      $copid = -1;
      
      // Might be passed in the URL (as per add)
      if(!empty($this->request->params['named']['copersonid']))
        $copid = $this->request->params['named']['copersonid'];
      // Might be determined from the CO Person Role (as per edit/view)
      elseif(!empty($this->request->data['CoPersonRole']['co_person_id']))
        $copid = $this->request->data['CoPersonRole']['co_person_id'];
      // Might need to look it up from the person role
      elseif(!empty($this->request->params['pass'][0]))
        $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->request->params['pass'][0]));
      
      $this->CoPersonRole->CoPerson->recursive = 2;
      $cop = $this->CoPersonRole->CoPerson->findById($copid);
      
      if($cop)
        $this->set('co_people', array(0 => $cop));
      else
      {
        $this->Session->setFlash(_txt('er.cop.unk-a', array($copid)), '', array(), 'error');
        $this->redirect(array('controller' => 'co_people', 'action' => 'index', 'co' => $this->cur_co['Co']['id']));
      }
    }
    
    // If there are any extended attributes defined for this CO,
    // dynamically bind the CO table of attributes to the model.
    
    if($this->restful && !isset($this->cur_co))
    {
      // Calls to co_person_roles via the REST controller won't have a CO set (except
      // when retrieving all members of a CO) so we have to figure out the CO
      // from the person requested.
      
      if(isset($this->request->params['id']))
      {
        // Request for an individual
        
        $args['joins'][0]['table'] = 'co_people';
        $args['joins'][0]['alias'] = 'CoPerson';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'Co.id=CoPerson.co_id';
        $args['joins'][1]['table'] = 'co_person_roles';
        $args['joins'][1]['alias'] = 'CoPersonRole';
        $args['joins'][1]['type'] = 'INNER';
        $args['joins'][1]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
        $args['conditions']['CoPersonRole.id'] = $this->request->params['id'];
        
        $this->cur_co = $this->CoPersonRole->CoPerson->Co->find('first', $args);
      }
      elseif(isset($this->request->params['url']['coid']))
      {
        // Request for all members of a CO
        
        $this->cur_co = $this->CoPersonRole->CoPerson->Co->findById($this->request->params['url']['coid']);
      }
      // We don't currently support requests for all CO people (regardless of CO).
      // To do so, we'd have to extract the CO ID on a per-CO person basis, which
      // wouldn't be terribly efficient.
    }
    
    $c = $this->CoPersonRole->CoPerson->Co->CoExtendedAttribute->find('count',
                                                                      array('conditions' =>
                                                                            array('co_id' => $this->cur_co['Co']['id'])));
    
    if($c > 0)
    {
      $cl = 'Co' . $this->cur_co['Co']['id'] . 'PersonExtendedAttribute';
      
      $this->CoPersonRole->bindModel(array('hasOne' =>
                                           array($cl => array('className' => $cl,
                                                              'dependent' => true))),
                                     false);
    }

    // generate list of sponsors
    $this->set('sponsors',$this->CoPersonRole->CoPerson->sponsorList($this->cur_co['Co']['id']));
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if($this->restful && !empty($this->viewVars['permissions']['cous'])) {
      // Check that the COU ID provided points to an existing COU.
      
      if(empty($reqdata['CoPersonRole']['cou_id'])) {
        $this->restResultHeader(403, "COU Does Not Exist");
        return false;
      }      
      
      $a = $this->CoPersonRole->Cou->findById($reqdata['CoPersonRole']['cou_id']);

      if(empty($a)) {
        $this->restResultHeader(403, "COU Does Not Exist");
        return false;
      }
    }

    return true;
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.2
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($c[$req][$model->displayField]))
      return($c[$req][$model->displayField]);
    if(isset($this->request->data['Name']))
      return(generateCn($this->request->data['Name']));
    if(isset($this->viewVars['co_people'][0]['Name']))
      return(generateCn($this->viewVars['co_people'][0]['Name']));
    else
      return("(?)");
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Is this our own record?
    $self = false;
    
    if($cmr['comember'] && $cmr['copersonid'] && isset($this->request->params['pass'][0]))
    {
      // We need to see if the person role ID passed in maps to the authenticated CO person
      
      $copid = $this->CoPersonRole->field('co_person_id', array('id' => $this->request->params['pass'][0]));
      
      if($copid && $copid == $cmr['copersonid'])
        $self = true;
    }

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Person Role?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
    
    // Delete an existing CO Person Role?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing CO Person Role?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);

    // Are we trying to edit our own record? 
    // If we're an admin, we act as an admin, not self.
    $p['editself'] = $self && !$cmr['cmadmin'] && !$cmr['coadmin'] && !$cmr['subadmin'];
    
    // View all existing CO Person Roles (or a COU's worth)?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $cmr['subadmin']);
    
    // View an existing CO Person Role?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $self);
    
    // Determine which COUs a person can manage.
    if($cmr['cmadmin'] || $cmr['coadmin'])
      $p['cous'] = $this->CoPersonRole->Cou->find("list",
                                                  array("conditions" =>
                                                        array("co_id" => $this->cur_co['Co']['id'])));      
    elseif($cmr['subadmin'])
      $p['cous'] = $this->CoPersonRole->Cou->find("list",
                                                  array("conditions" =>
                                                        array("co_id" => $this->cur_co['Co']['id'],
                                                              "name" => $cmr['couadmin'])));
    else
      $p['cous'] = array();
    
    // COUs are handled a bit differently. We need to authorize operations that
    // operate on a per-person basis accordingly.
    
    if($cmr['subadmin'] && !empty($p['cous']))
    {
      if(!empty($this->request->params['pass'][0]))
      {
        // If the target person is in a COU managed by the COU admin, grant permission
        
        $tcous = $this->CoPersonRole->Cou->find("list",
                                                array("joins" =>
                                                      array(array('table' => 'co_person_roles',
                                                                  'alias' => 'CoPersonRole',
                                                                  'type' => 'INNER',
                                                                  'conditions' => array('Cou.id=CoPersonRole.cou_id'))),
                                                      "conditions" =>
                                                      array('CoPersonRole.id' => $this->request->params['pass'][0])));
        
        $a = array_intersect($tcous, $p['cous']);

        if(!empty($a))
        {
          // CO Person is a member of at least one COU that the COU admin manages
          
          $p['compare'] = true;
          $p['delete'] = true;
          $p['edit'] = true;
          $p['view'] = true;
        }
      }
      else
      {
        if($p['index'])
        {
          // We grant additional permissions so the appropriate buttons render
          // on the assumption that any row that renders is for an individual
          // that this COU admin can manage, and that anyway we'll check the
          // authz on a per-person basis (the above portion of this if/else)
          // when an individual is selected. This probably isn't ideal -- it
          // might be better to have separate render and action permissions --
          // but it'll do.
          
          $p['compare'] = true;
          $p['delete'] = true;
          $p['edit'] = true;
          $p['view'] = true;
        }
      }
    }
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.2
   */
  
  function performRedirect() {
    // On add, redirect to edit view again so MVPAs are available.
    // For everything else, return to co_people
   
    if($this->action == 'add')
      $this->redirect(array('action' => 'edit', $this->CoPersonRole->id, 'co' => $this->cur_co['Co']['id']));
    else
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'edit',
                            $this->viewVars['co_people'][0]['CoPerson']['id'],
                            'co' => $this->cur_co['Co']['id']));
  }
}
