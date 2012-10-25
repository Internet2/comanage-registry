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
App::uses('GrouperCoGroup', 'Grouper.Lib');
App::uses('GrouperRestClient', 'Grouper.Lib');
App::uses('GrouperCoGroupSourceException', 'Grouper.Lib');

class CoGroupSource extends DataSource {

  protected $_schema = array(
    'co_groups' => array(
      'id' => array(
          'type' => 'string',
          'length' => 36,
        'key' => 'primary'
        ),
      'co_id' => array(
        'type' => 'integer',
        'length' => 11,
        'key' => 'index'
      ),
      'name' => array(
        'type' => 'string',
        'null' => true,
        'length' => 128,
        'key' => 'index',
        'collate' => 'latin1_swedish_ci',
        'charset' => 'latin1'
      ),
      'description' => array(
        'type' => 'string',
        'null' => true,
        'length' => 256,
        'key' => 'index',
        'collate' => 'latin1_swedish_ci',
        'charset' => 'latin1'
      ),
      'open' => array(
        'type' => 'boolean',
        'null' => true,
        'length' => 1
      ),
      'status' => array(
        'type' => 'string',
        'null' => true,
        'length' => 2,
        'collate' => 'latin1_swedish_ci',
        'charset' => 'latin1'
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
   * Constructor for CoGroupSource
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
   * Destructor for CoGroupSource
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
    //
    // This approach is suggested in the CakePHP documentation.

    return 'COUNT';
  }

  /**
   * Create group in Grouper representing Model CoGroup.
   * Required by the dataSource API. The 'C' in CRUD.
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

    if ($data['open']) {
      $open = 1;
    }
    else {
      $open = 0;
    }

    try {
      $group = GrouperCoGroup::fromInputs(
                                $data['id'],
                                $data['co_id'],
                                $data['name'],
                                $data['description'],
                                $open,
                                $data['status']);
    } catch (GrouperCoGroupException $e) {
      // TODO log properly
      return false;
    }

    $Model->id = $data['id'];
    return true;
  }

  /**
   * Delete a group in Grouper representing Model CoGroup.
   * Required by the dataSource API. The 'D' in CRUD.
   * - precondition: group exists in grouper
   * - postcondition: group does not exist in grouper
   *
   * @since         COmanage Directory 0.7
   * @Model         Model instance being deleted
   * @condition     Conditions on the delete action
   * @return        boolean insert result
   */
   public function delete(Model $Model, $condition = null) {

    try {
      GrouperCoGroup::deleteById($Model->id);
    } catch (GrouperCoGroupException $e) {
      //TODO better logging
      return false;
    }

    return true;
   }

  /**
   * Required by the dataSource API
   * - precondition:
   * - postcondition:
   *
   * @since         COmanage Directory 0.7
   * @model
   * @return        description of the model schema
   */
  public function describe($model) {
    return $this->_schema['co_groups'];
  }

  /**
   * Required by the dataSource API.
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
   * @since         COmanage Directory 0.7
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
      return $model->find('first', $args);
    }

  }

  /**
   * Required by the dataSource API. The 'R' in CRUD
   * - precondition:
   * - postcondition:
   *
   * @since         COmanage Directory 0.7
   * @model         model being queried
   * @queryData     parameters for the query
   * @recursive     the level of recursing
   */
  public function read($model, $queryData = array(), $recursive = null) {
    // See the comment for the calculate method. Here we do the actual
    // count as instructed by the calculate() method.
    //
    // TODO For now rather then counting the actual number of
    // "rows" or records we are returning 1 so update() and
    // delete() will assume the record exists.

    if (array_key_exists('fields', $queryData)) {
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

    $resultSet = array();

    // If the query condition includes a constraint on the group Id and there
    // is only one group Id then use it to read.
    if (array_key_exists('CoGroup.id', $conditions)) {
        if (count($conditions['CoGroup.id']) == 1 ) {
          try {
            $group = GrouperCoGroup::fromId($conditions['CoGroup.id']);
          } catch (GrouperCoGroupException $e) {
            //TODO better logging
            return false;
          }
          $resultSet[] = array('CoGroup' => $group->serializeFromObject());
        } else {
          throw new LogicException('Found CoGroup.id = ' . print_r($conditions['CoGroup.id'], true));
        }
    } elseif (array_key_exists('CoGroup.co_id', $conditions)) {
      // Get groups by CO Id.
        if (count($conditions['CoGroup.co_id'] == 1)) {
          try {
            $groups = GrouperCoGroup::fromCoId($conditions['CoGroup.co_id']);
          } catch (GrouperCoGroupException $e) {
            //TODO better logging
            return false;
          }

          foreach($groups as $g) {
            $resultSet[] = array('CoGroup' => $g->serializeFromObject());
          }
        } else {
          throw new LogicException('Found CoGroup.co_id = ' . print_r($conditions['CoGroup.co_id'], true));
        }
    }

    // Filter the result set by group name if that query condition
    // is set.
    if (array_key_exists('CoGroup.name', $conditions))  {
      if (count($conditions['CoGroup.name'] == 1)) {
        $name = $conditions['CoGroup.name'];
        foreach($resultSet as $key => $result){
          if ($result['CoGroup']['name'] != $name) {
            unset($resultSet[$key]);
            }
        }
      } else {
        throw new LogicException('Found CoGroup.name = ' . print_r($conditions['CoGroup.name'], true));
      }
    }

    $resultSet = array_values($resultSet);

    if (!is_null($recursive)) {
      $model->recursive = $_recursive;
    }
    return $resultSet;
  }

 /**
   * Required by the dataSource API. The 'U' in CRUD
   * - precondition: CoGroup exists as a group in Grouper
   * - postcondition: CoGroup exists as a group in Grouper with new details
   *
   * @since         COmanage Directory 0.7
   * @model         model being updated
   * @fields        fields on CoGroup
   * @values        values on CoGroup
   */
  public function update($model, $fields = null, $values = null) {

    $group = GrouperCoGroup::fromId($model->id);

    $newValues = array_combine($fields, $values);

    try {
      $group->update($newValues);
    } catch (GrouperCoGroupException $e) {
      //TODO better logging
      return false;
    }

    return true;
  }
}
