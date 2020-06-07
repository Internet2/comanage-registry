<?php
/**
 * COmanage Registry CO Enrollment Cluster Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoEnrollmentCluster extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentCluster";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Cluster",
    "CoEnrollmentFlow"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoEnrollmentCluster.id";
  
  // Default ordering for find operations
  public $order = array("CoEnrollmentCluster.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'cluster_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'required' => array(
      'rule' => array('boolean')
    )
  );
  
  /**
   * Obtain a list of Enrollment Clusters, where the underlying Clusters
   * are active.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $efId Enrollment Flow ID
   * @return Array List of Enrollment Authenticators
   */
  
  public function active($efId) {
    // Similar to CoEnrollmentAuthenticator::active
    $args = array();
    $args['conditions']['CoEnrollmentCluster.co_enrollment_flow_id'] = $efId;
    // What we really want to do is something like this
    //    $args['joins'][0]['table'] = 'clusters';
    //    $args['joins'][0]['alias'] = 'Cluster';
    //    $args['joins'][0]['type'] = 'INNER';
    //    $args['joins'][0]['conditions'][0] = 'Cluster.id=CoEnrollmentCluster.cluster_id';
    // and something like this
    //    $args['contain'][] = 'Cluster.status = "' . SuspendableStatusEnum::Active . '"';
    // but we can't have both a join and a contain (duplicate tables in the SQL query).
    // If we just use the contain, we'll still get matching rows back, it's just that the
    // associated OrgIdentitySource will be empty. So we have to walk the results and clean them up.
    // This is similar to CoEnrollmentSource::activeSources.
    $args['contain'][] = 'Cluster';
    
    $ret = $this->find('all', $args);
    
    for($i = count($ret)-1;$i >= 0;$i--) {
      if(!isset($ret[$i]['Cluster']['status'])
         || $ret[$i]['Cluster']['status'] != SuspendableStatusEnum::Active) {
        // Not an active cluster, pull it from the result set
        
        unset($ret[$i]);
      }
    }
    
    return $ret;
  }
}