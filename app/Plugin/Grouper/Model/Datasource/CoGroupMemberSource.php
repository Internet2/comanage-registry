<?php
/**
 * COmanage Registry Grouper CoGroup Data Source
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('DataSource', 'Model/Datasource');
App::uses('GrouperRestClient', 'Grouper.Lib');
App::uses('GrouperCoMembers', 'Grouper.Lib');
App::uses('GrouperCoGroupMemberSourceException', 'Grouper.Lib');

class CoGroupMemberSource extends DataSource {
  protected $_connection = null;

  protected $_schema = array(
    'co_group_members' => array(
      'id' => array(
        'type' => 'string',
        'length' => 36,
        'key' => 'primary'
        ),
      'co_group_id' => array(
        'type' => 'string',
        'length' => 36,
        'key' => 'index'
      ),
      'co_person_id' => array(
        'type' => 'integer',
        'length' => 11,
        'key' => 'index'
      ),
      'member' => array(
        'type' => 'boolean',
        'null' => true,
        'length' => 1
      ),
      'owner' => array(
        'type' => 'boolean',
        'null' => true,
        'length' => 1
      ),
      'created' => array(
        'type' => 'datetime',
        'null' => true
      ),
      'modified' => array(
        'type' => 'datetime',
        'null' => true
      )
    )
  );

  public $columns = array(
    'primary_key' => array('name' => 'primary_key'),
    'boolean' => array('name' => 'boolean'),
    'string' => array('name'  => 'string'),
    'text' => array('name' => 'text'),
    'integer' => array('name' => 'integer'),
    'float' => array('name' => 'float'),
    'datetime' => array('name' => 'datetime'),
    'timestamp' => array('name' => 'timestamp'),
    'time' => array('name' => 'time'),
    'date' => array('name' => 'date'),
  );

  // The endQuote and startQuote definitions are necessary since
  // CakePHP is incorrectly assuming a relational database
  // backend.
  public $endQuote = null;
  public $startQuote = null;

  /**
   * Constructor for CoGroupMemberSource
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  function __construct($config = array(), $autoConnect = false) {
    parent::__construct($config, $autoConnect);
  }

  /**
   * Destructor for CoGroupMemberSource
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   * @return void
   */
  public function __destruct() {
  }

  /**
   * Required by the dataSource API.
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.7
   */
  public function calculate($Model, $func, $params = array()){
    // Calculate() is for determining how we will count the records and is
    // required to get ``update()`` and ``delete()`` to work.
    //
    // We don't count the records here but return a string to be passed to
    // ``read()`` which will do the actual counting. The easiest way is to just
    // return the string 'COUNT' and check for it in ``read()`` where
    // ``$data['fields'] == 'COUNT'``.

    return 'COUNT';
  }

  /**
   * Create group membership in Grouper representing Model CoGroupMembers.
   * The 'C' in CRUD.
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   * @Model         Model instance being created
   * @fields        Field data
   * @values        Save data
   * @return        boolean insert result
   */
  public function create(&$Model, $fields = null, $values = null) {
    if ($fields !== null && $values !== null) {
      $data = array_combine($fields, $values);
    } else {
      $data = $Model->data;
    }

    $coGroupId = $data['co_group_id'];
    $coPersonId = $data['co_person_id'];
    $member = $data['member'];
    $owner  = $data['owner'];

    $group = GrouperCoGroup::fromId($coGroupId);

    try {
      if ($member) {
        $group->addMember($data['id'], $coGroupId, $coPersonId);
      }

      if ($owner) {
          $group->addOwner($data['id'], $coGroupId, $coPersonId);
      }
    } catch (GrouperCoGroupException $e) {
      // TODO better loggin
      return false;
    }

    return true;
  }

  /**
   * Delete an immediate membership in Grouper representing Model CoGroupMember.
   * Required by the dataSource API. The 'D' in CRUD.
   * - precondition: immediate membership exists in grouper
   * - postcondition: immediate membership does not exist in grouper
   *
   * @since         COmanage Directory 0.7
   * @Model         Model instance being deleted
   * @condition     Conditions on the delete action
   * @return        boolean insert result
   */
   public function delete(Model $Model, $condition = null) {
    // At this time deleting a membership is taken to mean
    // deleting both group membership and the owner role.

    try {
      // Find the corresponding membership.
      $membership = GrouperCoMembers::fromId($Model->id);

      // Delete it.
      $membership->delete();
    } catch (GrouperCoMembersException $e) {
      // TODO better logging
      return false;
    }

    return true;
   }

  public function describe($model) {
    return $this->_schema['co_group_members'];
  }

  /**
   * Required by the dataSource API
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   */
  public function listSources() {
    return null;
  }

  /**
   * Required by the dataSource API
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.8
   * @method        type of query such as findById
   * @params        parameters for the query
   * @model         model being queried
   */
  public function query($method, $params, $model){

    if ($method == 'findById'){
      $args = array(
        'conditions' => array(
          'CoGroup.id' => $params[0]
          )
        );
      return $model->find('first', $params);
    }
  }

  /**
   * Required by the dataSource API. The 'R' in CRUD.
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   * @model         Model instance being deleted
   * @queryData     Conditions on the read action
   * @recursive
   * @return        boolean insert result
   */
  public function read(Model $model, $queryData = array(), $recursive = null) {
    // See the comment for the calculate method. Here we do the actual
    // count as instructed by the calculate() method. 
    //
    // TODO For now rather then counting the actual number of
    // "rows" or records we are returning 1 so update() and 
    // delete() will assume the record exists.
    if (array_key_exists('fields', $queryData)){
      if ($queryData['fields'] == 'COUNT') {
        return array(array(array('count' => 1)));
      }
    }

    // Boilerplate taken from other dataSources. 
    // TODO Review if this boilerplate can be removed.
    if ($recursive === null && isset($queryData['recursive'])) {
      $recursive = $queryData['recursive'];
    }

    if (!is_null($recursive)) {
      $_recursive = $model->recursive;
      $model->recursive = $recursive;
    }

    $conditions = array();
    if (!empty($model->conditions)){
      $conditions += $model->conditions;
    }
    if (!empty($queryData['conditions'])){
      $conditions += $queryData['conditions'];
    }

    if (array_key_exists('CoGroupMember.co_group_id', $conditions)) {
      // Fetch possibly many memberships by CO group id.
      try {
        $coGroupId = $conditions['CoGroupMember.co_group_id'];
        $memberships = GrouperCoMembers::fromCoGroupId($coGroupId);
      } catch (GrouperCoMembershipsException $e) {
        //TODO better logging
        return false;
      }

      $resultSet = array();
      foreach($memberships as $m) {
        $resultSet[] = array('CoGroupMember' => $m->serializeFromObject());
      }
    } elseif (array_key_exists('CoGroupMember.id', $conditions)) {
      // Fetch a single membership by id.
      try {
        $id = $conditions['CoGroupMember.id'];
        $membership = GrouperCoMembers::fromId($id);
      } catch (GrouperCoMembershipsException $e) {
        //TODO better logging
        return false;
      }

      $resultSet = array();
      $resultSet[] = array('CoGroupMember' => $membership->serializeFromObject());

    } elseif (array_key_exists('CoGroupMember.co_person_id', $conditions)) {
      // Fetch possibly many memberships by CO person Id.
      try {
        $coPersonId = $conditions['CoGroupMember.co_person_id'];
        $memberships = GrouperCoMembers::fromCoPersonId($coPersonId);
      } catch (GrouperCoMembershipsException $e) {
        //TODO better logging
        return false;
      }

      $resultSet = array();
      foreach($memberships as $m) {
        $resultSet[] = array('CoGroupMember' => $m->serializeFromObject());
      }

    }

    // Filter the result set using conditions from the input model and queryData.

    foreach($conditions as $attr => $value){

      // Filter to only include particular CO person Id.
      if ($attr == 'CoGroupMember.co_person_id'){
        $coPersonId = $value;
        foreach($resultSet as $key => $result){
          if ($result['CoGroupMember']['co_person_id'] != $coPersonId) {
            unset($resultSet[$key]);
          }
        }
      }

      // Filter to only include particular group Id.
      if ($attr == 'CoGroupMember.co_group_id'){
        $coGroupId = $value;
        foreach($resultSet as $key => $result){
          if ($result['CoGroupMember']['co_group_id'] != $coGroupId) {
            unset($resultSet[$key]);
          }
        }
      }
    }

    $resultSet = array_values($resultSet);
    return $resultSet;
  }

  /**
   * Required by the dataSource API. The 'U' in CRUD
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.7
   * @model         model being updated
   * @fields        name of the data fields
   * @values        values of the data fields
   */
  public function update($model, $fields = null, $values = null) {
    // Find the current membership
    $membership = GrouperCoMembers::fromId($model->id);

    $newValues = array_combine($fields, $values);

    try {
      $membership->update($newValues);
    } catch (GrouperCoMembersException $e) {
      //TODO better logging
      return false;
    }

    return true;
  }
}
