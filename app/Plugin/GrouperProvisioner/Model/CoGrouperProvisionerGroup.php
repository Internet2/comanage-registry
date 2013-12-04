<?php
/**
 * COmanage Registry CO Grouper Provisioner Group Model
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoGrouperProvisionerGroup extends AppModel {
  // Define class name for cake
  public $name = "CoGrouperProvisionerGroup";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "GrouperProvisioner.CoGrouperProvisionerTarget",
    "CoGroup"
  );

  // Default display field for cake generated views
  public $displayField = "extension";
  
  // Validation rules for table elements
  public $validate = array(
    'co_grouper_provisioner_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Grouper Provisioning Target ID must be provided'
    ),
    'co_group_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Group ID must be provided'
    ),
    'stem' => array(
      'rule' => 'notEmpty'
    ),
    'extension' => array(
      'rule' => 'notEmpty'
    )
  );
  
  /**
   * Add a mapping between a CO Group, provisioner target, and Grouper group.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CO Provisioning Target data
   * @param  Array CO Provisioning data
   * @return Array
   * @throws RuntimeException
   */

  public function addProvisionerGroup($coProvisioningTargetData, $provisioningData) {
    $newProvisionerGroup = $this->computeProvisionerGroup($coProvisioningTargetData, $provisioningData);

    if(!$this->save($newProvisionerGroup)) {
      throw new RuntimeException(_txt('er.db.save'));
    }

    $newProvisionerGroup['CoGrouperProvisionerGroup']['id'] = $this->id;

    return $newProvisionerGroup;
  }

  /**
   * Compute a mapping between a CO Group, provisioner target, and Grouper group.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CO Provisioning Target data
   * @param  Array CO Provisioning data
   * @return Array
   */

  public function computeProvisionerGroup($coProvisioningTargetData, $provisioningData) {
    // Replace colon with underscore in COmanage group name to
    // create Grouper group extension (name without stem).
    $extension = str_replace(":", "_", $provisioningData['CoGroup']['name']);

    $newProvisionerGroup = array();
    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_grouper_provisioner_target_id'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['id'];
    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_group_id'] = $provisioningData['CoGroup']['id'];
    $newProvisionerGroup['CoGrouperProvisionerGroup']['stem'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['stem'];
    $newProvisionerGroup['CoGrouperProvisionerGroup']['extension'] = $extension;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['description'] = $provisioningData['CoGroup']['description'];

    return $newProvisionerGroup;
  }

  /**
   * Delete a mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Grouper group name
   * @param  Array CoGrouperProvisionerGroup data
   * @return None
   */

  public function delProvisionerGroup($provisionerGroup) {
    $id = $provisionerGroup['CoGrouperProvisionerGroup']['id'];
    $this->delete($id);
  }

  /**
   * Return an empty CoProvisionerGroup data set
   *
   * @since  COmanage Registry v0.8.3
   * @return Array
   */

  public function emptyProvisionerGroup() {
    $newProvisionerGroup = array();

    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_grouper_provisioner_target_id'] = NULL;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_group_id'] = NULL;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['stem'] = NULL;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['extension'] = NULL;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['description'] = NULL;

    return $newProvisionerGroup;
  }

  /**
   * Find current mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CO Provisioning Target data
   * @param  Array CO Provisioning data
   * @return Group name
   */

  public function findProvisionerGroup($coProvisioningTargetData, $provisioningData) {
    $args = array();
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $provisioningData['CoGroup']['id'];
    $args['conditions']['CoGrouperProvisionerGroup.co_grouper_provisioner_target_id'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['id'];

    $group = $this->find('first', $args);

    if (!empty($group)) {
      return $group;
    } else {
      return NULL;
    }
  }

  /**
   * Construct the Grouper group name by combining stem and extension
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CoGrouperProvisionerGroup data
   * @return String
   */

  public function getGroupName($provisionerGroup) {
    $stem = $provisionerGroup['CoGrouperProvisionerGroup']['stem'];
    $extension = $provisionerGroup['CoGrouperProvisionerGroup']['extension'];

    $name = $stem . ':' . $extension;

    return $name;
  }

  /**
   * Return the Grouper group description
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CoGrouperProvisionerGroup data
   * @return String
   */

  public function getGroupDescription($provisionerGroup) {
    // Grouper group description is just the COmanage group description.
    return $provisionerGroup['CoGrouperProvisionerGroup']['description'];
  }

  /**
   * Return the Grouper group display extension
   *
   * @since  COmanage Registry v0.8.3
   * @param  Array CoGrouperProvisionerGroup data
   * @return String
   */

  public function getGroupDisplayExtension($provisionerGroup) {
    // Grouper group display extension is the same as the Grouper
    // group extension and so the same as the COmanage group name.
    return $provisionerGroup['CoGrouperProvisionerGroup']['extension'];
  }

  /**
   * Update a mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Current mapping
   * @param  New mapping
   * @return None
   * @throws RuntimeException
   */

  public function updateProvisionerGroup($current, &$updated) {
    if (array_key_exists('id', $current['CoGrouperProvisionerGroup'])) {
      $updated['CoGrouperProvisionerGroup']['id'] = $current['CoGrouperProvisionerGroup']['id'];
    }

    if(!$this->save($updated)) {
      throw new RuntimeException(_txt('er.db.save'));
    }

    $updated['CoGrouperProvisionerGroup']['id'] = $this->id;
  }
}
