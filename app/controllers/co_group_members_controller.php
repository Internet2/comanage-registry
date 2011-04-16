<?php
  /*
   * COmanage Gears CoGroupMember Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  include APP."controllers/standard_controller.php";

  class CoGroupMembersController extends StandardController {
    // Class name, used by Cake
    var $name = "CoGroupMembers";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'Name.family' => 'asc',
        'Name.given' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;

    // Edit and view need recursion so we get Name for rendering view
    var $edit_recursion = 2;
    var $view_recursion = 2;

    function add()
    {
      // Add one or more CO Group Members.
      //
      // Parameters (in $this->data):
      // - Model specific attributes
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) On success, new Group Member(s) created
      // (2) Session flash message updated (HTML)
      //
      // Returns:
      //   Nothing
      
      if(!$this->restful)
      {
        // We can't just saveAll because Cake will create rows in the database for all
        // non-member non-owners, which is silly.  Create a version without those
        // entries (and properly structured).
        
        // Set page title
        $this->set('title_for_layout', _txt('op.add-a', array(_txt('ct.co_group_members.1'))));

        // We may get here via co_groups, in which case co_group_id is static, or
        // via co_people, in which case co_person_id is static
        
        $a = array('CoGroupMember' => array());

        foreach($this->data['CoGroupMember'] as $g)
        {
          // Must be a member or an owner to get a row created
          
          if(is_array($g)
             && ((isset($g['member']) && $g['member'])
                 || (isset($g['owner']) && $g['owner'])))
          {
            $a['CoGroupMember'][] = array(
              'co_group_id' => (!empty($g['co_group_id'])
                                ? $g['co_group_id']
                                : $this->data['CoGroupMember']['co_group_id']),
              'co_person_id' => (!empty($g['co_person_id'])
                                 ? $g['co_person_id']
                                 : $this->data['CoGroupMember']['co_person_id']),
              'member' => $g['member'],
              'owner' => $g['owner']
            );
          }
        }
        
        if(count($a['CoGroupMember']) > 0)
        {
          if($this->CoGroupMember->saveAll($a['CoGroupMember']))
            $this->Session->setFlash(_txt('rs.added'), '', array(), 'success');
          else
            $this->Session->setFlash($this->fieldsErrorToString($this->CoGroupMember->invalidFields()), '', array(), 'error');
        }
        else
          $this->Session->setFlash(_txt('er.grm.none'), '', array(), 'info');
          
        $this->performRedirect();
      }
      else
        parent::add();
    }

    function beforeFilter()
    {
      // Callback before other controller methods are invoked or views are rendered.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Parent called
      //
      // Returns:
      //   Nothing
      
      // Strictly speaking, this controller doesn't require a CO except to
      // redirect/render views.  Since the REST API doesn't specify CO ID
      // we unset requires_co.  $this->restful gets set in beforeFilter, so
      // call the parent first.  (While requires_co is checked there for
      // non-REST, it is checked in checkPost for REST.)

      parent::beforeFilter();

      if($this->restful)
        $this->requires_co = false;
    }
    
    function checkWriteDependencies()
    {
      // Perform any dependency checks required prior to a write (add/update) operation.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succed, false otherwise.
      
      // Make sure the Group exists
      
      $g = $this->CoGroupMember->CoGroup->findById($this->data['CoGroupMember']['co_group_id']);
      
      if(empty($g))
      {
        if($this->restful)
          $this->restResultHeader(403, "CoGroup Does Not Exist");
        else
        {
          $this->Session->setFlash(_txt('er.gr.nf', array($this->data['CoGroupMember']['co_group_id'])), '', array(), 'error');
          $this->performRedirect();
        }

        return(false);
      }

      // Make sure the CO Person exists
      
      $p = $this->CoGroupMember->CoPerson->findById($this->data['CoGroupMember']['co_person_id']);
      
      if(empty($p))
      {
        if($this->restful)
          $this->restResultHeader(403, "CoPerson Does Not Exist");
        else
        {
          $this->Session->setFlash(_txt('er.cop.nf', array($this->data['CoGroupMember']['co_person_id'])), '', array(), 'error');
          $this->performRedirect();
        }

        return(false);
      }
      
      if($this->action == 'add')
      {
        // Make sure the CO Person isn't already in the Group
        
        $x = $this->CoGroupMember->find('all', array('conditions' =>
                                                     array('CoGroupMember.co_group_id' => $this->data['CoGroupMember']['co_group_id'],
                                                           'CoGroupMember.co_person_id' => $this->data['CoGroupMember']['co_person_id'])));
        
        if(!empty($x))
        {
          if($this->restful)
            $this->restResultHeader(403, "CoPerson Already Member");
          else
          {
            $this->Session->setFlash(_txt('er.grm.already', array($this->data['CoGroupMember']['co_person_id'],
                                                                  $this->data['CoGroupMember']['co_group_id'])),
                                     '', array(), 'error');
            $this->performRedirect();
          }
  
          return(false);
        }
      }
      
      // XXX We don't check that the CO Person is actually in the CO... should we?
      // And what if they're later removed from the CO?

      return(true);
    }
    
    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions

      $cmr = $this->calculateCMRoles();             // What was authenticated

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine if the authenticated person is an owner or member of the associated group

      $owner = false;
      $member = false;
      $gid = null;
      
      if($this->action == 'add' && isset($this->data['CoGroupMember']['co_group_id']))
        $gid = $this->data['CoGroupMember']['co_group_id'];
      elseif(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
             && isset($this->params['pass'][0]))
        $gid = $this->CoGroupMember->field('co_group_id', array('CoGroupMember.id' => $this->params['pass'][0]));
      elseif($this->action == 'select' && isset($this->params['named']['cogroup']))
        $gid = $this->params['named']['cogroup'];

      if(isset($gid) && !empty($cmr['copersonid']))
      {
        $gm = $this->CoGroupMember->find('all', array('conditions' =>
                                                      array('CoGroupMember.co_group_id' => $gid,
                                                            'CoGroupMember.co_person_id' => $cmr['copersonid'])));
        
        if(isset($gm[0]['CoGroupMember']['owner']) && $gm[0]['CoGroupMember']['owner'])
          $owner = true;
        
        if(isset($gm[0]['CoGroupMember']['member']) && $gm[0]['CoGroupMember']['member'])
          $member = true;
      }
      
      // Determine what operations this user can perform

      // Add a new member to a group?
      // XXX probably need to check if group is open here and in delete
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $owner);
      
      // Delete a member from a group?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $owner);
      
      // Edit members of a group?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $owner);
      
      // View a list of members of a group?
      // This is for REST
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      // Select from a list of potential members to add?
      $p['select'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $owner);
      
      // View members of a group?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || $owner || $member);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
    
    function performRedirect()
    {
      // Perform a redirect back to the controller's default view.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Redirect generated
      //
      // Returns:
      //   Nothing
      
      // Figure out where to redirect back to based on how we were called
      
      $cop = null;  
      
      if($this->action == 'add' && isset($this->data['CoGroupMember']['co_person_id']))
        $cop = $this->data['CoGroupMember']['co_person_id'];
      elseif($this->action == 'delete' && isset($this->params['named']['copersonid']))
        $cop = $this->params['named']['copersonid'];
        
      if(isset($cop))
      {
        $this->redirect(array('controller' => 'co_people',
                              'action' => 'edit',
                              $cop,
                              'co' => $this->cur_co['Co']['id']));
      }
      else
      {
        $this->redirect(array('controller' => 'co_groups',
                              'action' => 'edit',
                              $this->data['CoGroupMember']['co_group_id'],
                              'co' => $this->cur_co['Co']['id']));
      }
    }

    function select()
    {
      // Select from a list of potential new group members
      //
      // Parameters (in $this->params):
      // - cogroup: CO Group to add a member to
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) $co_people set with potential new members
      // (2) Session flash message updated on error (HTML)
      //
      // Returns:
      //   Nothing

      // Set page title
      $this->set('title_for_layout', _txt('op.select-a', array(_txt('ct.co_group_members.1'))));

      // Find available people
      // XXX remove people already members
      /* Use server side pagination
      $coppl = $this->CoGroupMember->CoPerson->find('all',
                                                    array('joins' => array(0 => array('table' => 'cm_co_person_sources',
                                                                                      'alias' => 'CoPersonSource',
                                                                                      'type' => 'INNER',
                                                                                      'conditions' => array('CoPerson.id=CoPersonSource.co_person_id'))),
                                                          'conditions' => array('CoPersonSource.co_id' => $this->cur_co['Co']['id'])));
      $this->set('co_people', $coppl);
      */
      $dbo = $this->CoGroupMember->getDataSource();
      
      $this->paginate['joins'][] = array(
        'table' => $dbo->fullTableName($this->CoGroupMember->CoPerson->CoPersonSource),
        'alias' => 'CoPersonSource',
        'type' => 'INNER',
        'conditions' => array('CoPerson.id=CoPersonSource.co_person_id')
      );
      $this->paginate['conditions'] = array(
        'CoPersonSource.co_id' => $this->cur_co['Co']['id']
      );
      
      $this->set('co_people', $this->paginate('CoPerson'));;
      
      if(isset($this->params['named']['cogroup']))
        $this->set('co_group', $this->CoGroupMember->CoGroup->findById($this->params['named']['cogroup']));
    }
  }
?>