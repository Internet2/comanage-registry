<?php
/**
 * COmanage Registry CO Group Member Model
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoGroupMember extends AppModel {
  // Define class name for cake
  public $name = "CoGroupMember";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CoGroupMember is attached to one CoGroup
    "CoGroup",
    // A CoGroupMember is attached to one CoPerson
    "CoPerson");
  
  // Default display field for cake generated views
  public $displayField = "co_person_id";
  
  // Default ordering for find operations
  public $order = array("co_person_id");
  
  public $actsAs = array('Containable', 'Provisioner');

  // If true the data source for the model uses a relational database
  // backend and if false then the data source is something else, perhaps
  // Grouper or similar.
  public $usesSqlDataSource = true;
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'member' => array(
      'rule' => array('boolean')
    ),
    'owner' => array(
      'rule' => array('boolean')
    )
  );

  /**
   * Constructor
   * - precondition:
   * - postcondition:
   *
   * @since COmanage Directory 0.7
   * @return instance
   */
  public function __construct($id = false, $table = null, $ds = null){

    // Depending on the configuration use the Grouper
    // plugin data source or the default data source.
    if(Configure::read('Grouper.COmanage.useGrouperDataSource')) {
      $this->useDbConfig = 'grouperCoGroupMember';
    } 

    // Depending on the configuration signal that we
    // do not use a SQL relational database backend for the
    // data source.
    if (!Configure::read('COmanage.groupSqlDataSource')) {
      $this->usesSqlDataSource = false;
    } 
    
    parent::__construct($id, $table, $ds);
  }
  
  /**
   * Obtain the group roles for a CO person.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Person ID
   * @return Array An array of two array: group IDs for which the person is a member, and group IDs for which the person is an owner
   */
  
  function findCoPersonGroupRoles($coPersonId) {
    $ret = array(
      'member' => array(),
      'owner'  => array()
    );
    
    $args = array();
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $memberships = $this->find('all', $args);
    
    foreach($memberships as $m) {
      if(isset($m['CoGroupMember']['member']) && $m['CoGroupMember']['member']) {
        $ret['member'][] = $m['CoGroupMember']['co_group_id'];
      }
      
      if(isset($m['CoGroupMember']['owner']) && $m['CoGroupMember']['owner']) {
        $ret['owner'][] = $m['CoGroupMember']['co_group_id'];
      }
    }
    
    return $ret;
  }
  
  /**
   * Update the CO Group Memberships for a CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  Array Array of CO Group Member attributes (id, co_group_id, member, owner)
   * @param  Integer CO Person ID of requester
   * @return Boolean True on success, false otherwise
   * @throws LogicException
   */
  
  public function updateMemberships($coPersonId, $memberships, $requesterCoPersonId) {
    if($coPersonId && !empty($memberships)) {
      // First, pull the current group roles.
      
      $curRoles = $this->findCoPersonGroupRoles($coPersonId);
      
      foreach($memberships as $m) {
        // Determine desired roles for this row
        $member = isset($m['member']) && $m['member'];
        $owner = isset($m['owner']) && $m['owner'];
        
        // Pull the related group information
        $args = array();
        $args['conditions']['id'] = $m['co_group_id'];
        $args['contain'] = false;
        
        $grp = $this->CoGroup->find('first', $args);
        
        if(empty($grp)) {
          throw new InvalidArgumentException(_txt('er.gr.nf', array($m['co_group_id'])));
        }
        
        if(!empty($m['id'])) {
          $args = array();
          $args['conditions']['id'] = $m['id'];
          $args['contain'] = false;
          
          $grpMem = $this->find('first', $args);
          
          if(empty($grpMem)) {
            throw new InvalidArgumentException(_txt('er.grm.nf', array($m['id'])));
          }
          
          if(!$member && !$owner) {
            // If a (CO Group Member) id is specified but member and owner are
            // both false, delete the row and cut a history record.
            
            if(!$this->delete($m['id'])) {
              throw new RuntimeException(_txt('er.delete'));
            }
            
            // Cut a history record
            
            try {
              $this->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $requesterCoPersonId,
                                                     ActionEnum::CoGroupMemberDeleted,
                                                     _txt('rs.grm.deleted', array($grp['CoGroup']['name'],
                                                                                  $m['co_group_id'])));
            }
            catch(Exception $e) {
              throw new RuntimeException($e->getMessage());
            }
          } else {
            // Otherwise, update the row if the member or owner are different than current.
            
            $curMember = isset($grpMem['CoGroupMember']['member']) && $grpMem['CoGroupMember']['member'];
            $curOwner = isset($grpMem['CoGroupMember']['owner']) && $grpMem['CoGroupMember']['owner'];
            
            if(($member != $curMember) || ($owner != $curOwner)) {
              $cogm = array();
              $cogm['CoGroupMember']['id'] = $m['id'];
              $cogm['CoGroupMember']['co_group_id'] = $m['co_group_id'];
              $cogm['CoGroupMember']['co_person_id'] = $coPersonId;
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberEdited,
                                                       _txt('rs.grm.edited', array($grp['CoGroup']['name'],
                                                                                   $m['co_group_id'],
                                                                                   _txt($grpMem['CoGroupMember']['member'] ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($grpMem['CoGroupMember']['owner'] ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($owner ? 'fd.yes' : 'fd.no'))));
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            }
          }
        } else {
          // If id is not specified, that a role has been specified, make sure
          // the CO Person is not already in the group, that the group is in the
          // same CO as the CO Person, and add a new row.
          
          if($member || $owner) {
            if(!in_array($m['co_group_id'], $curRoles['member'])
              && !in_array($m['co_group_id'], $curRoles['owner'])) {
              if($grp['CoGroup']['co_id']
                 != $this->CoPerson->field('co_id', array('id' => $coPersonId))) {
                throw new InvalidArgumentException(_txt('er.co.mismatch', array("CoGroup", $m['co_group_id'])));
              }
              
              // We can finally add a new CoGroupMember
              
              $cogm = array();
              $cogm['CoGroupMember']['co_group_id'] = $m['co_group_id'];
              $cogm['CoGroupMember']['co_person_id'] = $coPersonId;
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberAdded,
                                                       _txt('rs.grm.added', array($grp['CoGroup']['name'],
                                                                                  $m['co_group_id'],
                                                                                  _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                  _txt($owner ? 'fd.yes' : 'fd.no'))));
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            } else {
              // We shouldn't get here since $m['id'] should have been set if the CO person
              // already had a role in the group
              
              throw new LogicException(_txt('er.grm.already', array($coPersonId, $m['co_group_id'])));
            }
          }
        }
      }
      
      return true;
    }
    
    return false;
  }
}
