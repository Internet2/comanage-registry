<?php
/**
 * COmanage Registry Garbage Collector Job Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");

class GarbageCollectorJob extends CoJobBackend {

  /**
   * @var null
   */
  private $_mdl = null;

  /**
   * @var null
   */
  private $_mdl_inst = null;

  /**
   * Execute the requested Job.
   *
   * @param int $coId CO ID
   * @param CoJob $CoJob CO Job Object, id available at $CoJob->id
   * @param array $params Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @since  COmanage Registry v4.0.0
   */

  public function execute($coId, $CoJob, $params) {
    $CoJob->update($CoJob->id,
      null,
      null,
      _txt('pl.garbagecollectorjob.start'));

    try {
      // Validate the parameters
      $this->validateParams($coId, $params);
      // Construct the table name
      $modelpl = Inflector::tableize($params['object_type']);
      // Get all instances of the Object Type marked as trash
      $mdl_instances = $this->mdlInstances($params);
      // We have nothing to collect
      if(empty($mdl_instances)) {
        $CoJob->finish($CoJob->id, _txt('pl.garbagecollectorjob.none'));
        return;
      }

      foreach($mdl_instances as $instance) {
        $this->trash($CoJob, $params, $instance);

        $comment = ($CoJob->failed($CoJob->id))
          ? $comment = _txt('er.delete-a', array( _txt('ct.' . $modelpl . '.1'), $instance[ $params['object_type'] ]['name']))
          : $comment = _txt('rs.deleted-a2', array( _txt('ct.' . $modelpl . '.1'), $instance[ $params['object_type'] ]['name']));
      }

      $done_job_id = $CoJob->id;
      $CoJob->finish($CoJob->id, _txt('pl.garbagecollectorjob.done'));
      // Send notification
      $this->notify($coId, $CoJob, $params, $done_job_id, $comment);
    }
    catch(Exception $e) {
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);

      // Send notification
      $comment = _txt('er.delete-a', array( _txt('ct.' . $modelpl . '.1'), $instance[ $params['object_type'] ]['name']));
      $this->notify($coId, $CoJob, $params, $done_job_id, $comment);
    }
  }

  /**
   * Register a notification for each recipient
   *
   * @param int    $coId          CO Id
   * @param CoJob  $CoJob         CO Job Object, id available at $CoJob->id
   * @param int    $done_job_id   Id of the concluded Job
   * @param array  $params        CO Job parameters
   * @param string $comment       Notifications comment
   * @param int    $delay         Introduce a delay(seconds) for each email postage
   * @since  COmanage Registry v4.0.0
   */
  protected function notify($coId, $CoJob, $params, $done_job_id, $comment = "", $delay=5) {
    // Load the class
    $CoNotification = ClassRegistry::init('CoNotification');
    $Identifier = ClassRegistry::init('Identifier');

    // Notify the administrators

    $admin_identifiers = $Identifier->findGroupMembersNAdminsMVPA($coId, null, true);
    // We only need one identifier per CO Person.
    $admin_list = array();
    if(!empty($admin_identifiers)) {
      $admin_list = Hash::combine($admin_identifiers,
        '{n}.Identifier.identifier',
        '{n}.Identifier.co_person_id'
      );
      $admin_list = array_unique($admin_list);
    }

    foreach($admin_list as $identifier => $copid) {
      // Register the notification

      $ids = $CoNotification->register($copid,                                 // $subjectCoPersonId
                                       null,
                                       $copid,                                 // $actorCoPersonId
                                       'coperson',                             // $recipientType
                                       $copid,                                 // $recipientId
                                       TemplateableStatusEnum::InTrash,        // action
                                       $comment,                               // comment
                                       array(
                                         'controller' => 'co_jobs',
                                         'action' => 'view',
                                         'id' => $done_job_id
                                       ),                                      // $source
                                       false,                                  // $resolve
                                       null,                                   // $fromAddress
                                       _txt('pl.garbagecollectorjob.done'),    // $subjectTempate
                                       _txt('pl.garbagecollectorjob.body'));   // $bodyTemplate
      // Introduce a delay between transmitions
      sleep($delay);
    }
  }

  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Array of supported parameters.
   */

  public function parameterFormat() {
    $params = array(
      'object_type' => array(
        'help'     => _txt('pl.garbagecollectorjob.arg.object_type'),
        'type'     => 'select',
        'required' => true,
        'choices'  => array('Co')
      ),
    );

    return $params;
  }

  /**
   * Delete RecordType entry.
   *
   * @since  COmanage Registry v4.0.0
   * @param  CoJob   $CoJob       CO Job
   * @param  array   $params      Parameters passed into the Job
   * @param  array   $object      Object to delete
   */

  protected function trash($CoJob, $params, $object) {

    $mdl = $this->mdlObj($params['object_type']);
    $modelpl = Inflector::tableize($params['object_type']);

    if(!empty($object[$params['object_type']]['name'])) {
      $name = $object[$params['object_type']]['name'];
    } else {
      $name = "Unknown";
    }

    // Delete the CO and record the Job into the history
    $mdl->delete($object[ $params['object_type'] ]['id']);
    $CoJob->CoJobHistoryRecord->record(
      $CoJob->id,
      $object[ $params['object_type'] ]['id'],
      _txt('rs.deleted-a2', array(_txt('ct.' . $modelpl . '.1'), $name))
    );

  }

  /**
   * Validate Garbage Collector Job parameters.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId   CO ID
   * @param  array   $params Array of parameters
   * @return boolean         true if parameters are valid
   * @throws InvalidArgumentException
   */

  protected function validateParams($coId, $params) {
    $object_types = array(
      'Co'
    );

    // Do we support this object_type
    if(!in_array($params['object_type'], $object_types)) {
      $this->log(__METHOD__ . "::message " . _txt('er.garbagecollectorjob.object_type.invalid', array($params['object_type'], 'status')), LOG_ERROR);
      throw new InvalidArgumentException(_txt('er.garbagecollectorjob.object_type.unknown', array($params['object_type'])));
    }

    return true;
  }

  /**
   * Singleton Model initiator
   *
   * @since  COmanage Registry v4.0.0
   * @param $object_type
   * @return AppModel|bool|Model|object|null
   */
  private function mdlObj($object_type) {
    if(is_null($this->_mdl)) {
      $this->_mdl = ClassRegistry::init($object_type);
    }
    return $this->_mdl;
  }

  /**
   * @param $params
   * @return null
   */
  protected function mdlInstances($params) {
    if(is_null($this->_mdl_inst)) {
      $mdl = $this->mdlObj($params['object_type']);
      // Check if the Model has a `status` column
      $mdl_columns = array_keys($mdl->schema());
      if(!in_array('status', $mdl_columns)) {
        return null;
      }
      $this->_mdl_inst = $mdl->find('all', array(
        'conditions' => array($params['object_type'] . '.status' => TemplateableStatusEnum::InTrash),
        'contain' => false,
      ));
    }
    return $this->_mdl_inst;
  }

}