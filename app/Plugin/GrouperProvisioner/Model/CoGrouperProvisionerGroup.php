<?php
/**
 * COmanage Registry CO Grouper Provisioner Group Model
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
      'rule' => 'notBlank'
    ),
    'extension' => array(
      'rule' => 'notBlank'
    )
  );
  
  /**
   * Add a mapping between a CO Group, provisioner target, and Grouper group.
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup being provisioned
   * @return array New provisioner group mapping
   * @throws RuntimeException
   */

  public function addProvisionerGroup($coProvisioningTargetData, $coGroup) {
    $newProvisionerGroup = $this->computeProvisionerGroup($coProvisioningTargetData, $coGroup);

    $this->clear();
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
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup to map
   * @return array New provisioner group mapping
   */

  public function computeProvisionerGroup($coProvisioningTargetData, $coGroup) {
    // Replace colon with underscore in COmanage group name to
    // create Grouper group extension (name without stem).
    $groupName = $coGroup['CoGroup']['name'];
    $extension = str_replace(":", "_", $groupName);

    $stem = $coProvisioningTargetData['CoGrouperProvisionerTarget']['stem'];
    
    // If this is a COU members or admin group we need to create the necessary
    // hierarchy of stems that represent the parent child relationships
    // (if any) of the COUs.
    if($this->CoGroup->isCouAdminOrMembersGroup($coGroup)) {
      // Find the corresponding COU.
      $args = array();
      $args['conditions']['Cou.id'] = $coGroup['CoGroup']['cou_id'];
      $args['contain'] = false;
      $cou = $this->CoGroup->Co->Cou->find('first', $args);
      if(empty($cou)) {
        $message = 'Error finding Cou for admin or members group ' . $groupName;
        throw new RuntimeException($message);
      }
      
      // Find the 'parent path', if any, for this COU.
      $parents = $this->CoGroup->Co->Cou->getPath($coGroup['CoGroup']['cou_id']);
      foreach($parents as $cou) {
          $stem = $stem . ':' . $cou['Cou']['name'];
      }
    } 

    $newProvisionerGroup = array();
    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_grouper_provisioner_target_id'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['id'];
    $newProvisionerGroup['CoGrouperProvisionerGroup']['co_group_id'] = $coGroup['CoGroup']['id'];
    $newProvisionerGroup['CoGrouperProvisionerGroup']['stem'] = $stem;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['extension'] = $extension;
    $newProvisionerGroup['CoGrouperProvisionerGroup']['description'] = $coGroup['CoGroup']['description'];

    return $newProvisionerGroup;
  }

  /**
   * Delete a mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  array CoGrouperProvisionerGroup to be deleted
   * @return void
   */

  public function delProvisionerGroup($provisionerGroup) {
    $id = $provisionerGroup['CoGrouperProvisionerGroup']['id'];
    $this->delete($id);
  }

  /**
   * Determine if two CoGrouperProvisionerGroup objects are equal.
   *
   * @since COmanage Registry 1.1.0
   * @param array $pGroup1 first CoGrouperProvisionerGroup for comparison
   * @param array $pGroup2 second CoGrouperProvisionerGroup for comparison
   * @return boolean true if equal otherwise false
   */

  public function equal($pGroup1, $pGroup2) {
    if ($pGroup1['CoGrouperProvisionerGroup']['co_grouper_provisioner_target_id'] 
          != $pGroup2['CoGrouperProvisionerGroup']['co_grouper_provisioner_target_id']) {
            return false;
    }
    if ($pGroup1['CoGrouperProvisionerGroup']['co_group_id'] 
          != $pGroup2['CoGrouperProvisionerGroup']['co_group_id']) {
            return false;
    }
    if ($pGroup1['CoGrouperProvisionerGroup']['stem'] 
          != $pGroup2['CoGrouperProvisionerGroup']['stem']) {
            return false;
    }
    if ($pGroup1['CoGrouperProvisionerGroup']['extension'] 
          != $pGroup2['CoGrouperProvisionerGroup']['extension']) {
            return false;
    }
    if ($pGroup1['CoGrouperProvisionerGroup']['description'] 
          != $pGroup2['CoGrouperProvisionerGroup']['description']) {
            return false;
    }

    return true;
  }

  /**
   * Find current mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup for which to find the mapping
   * @return CoGrouperProvisionerGroup or NULL if mapping not found
   */

  public function findProvisionerGroup($coProvisioningTargetData, $coGroup) {
    $args = array();
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $coGroup['CoGroup']['id'];
    $args['conditions']['CoGrouperProvisionerGroup.co_grouper_provisioner_target_id'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['id'];
    $args['contain'] = false;

    $group = $this->find('first', $args);

    if (!empty($group)) {
      return $group;
    } else {
      return NULL;
    }
  }

  /**
   * Find current mapping between a CO Group, provisioner target, 
   * and Grouper name by CoGroup ID.
   *
   * @since  COmanage Registry v1.1.0
   * @param  integer $id CoGroup id
   * @return CoGrouperProvisionerGroup
   */

  public function findProvisionerGroupByCoGroupId($coGroupId) {
    $args = array();
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $coGroupId;
    $args['contain'] = false;

    $group = $this->find('first', $args);

    return $group;
  }

  /**
   * Construct the Grouper group name by combining stem and extension
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $provisionerGroup CoGrouperProvisionerGroup data
   * @return string
   */

  public function getGrouperGroupName($provisionerGroup) {
    $stem = $provisionerGroup['CoGrouperProvisionerGroup']['stem'];
    $extension = $provisionerGroup['CoGrouperProvisionerGroup']['extension'];

    $name = $stem . ':' . $extension;

    return $name;
  }

  /**
   * Return the Grouper group description
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $provisionerGroup CoGrouperProvisionerGroup data
   * @return string
   */

  public function getGrouperGroupDescription($provisionerGroup) {
    // Grouper group description is just the COmanage group description.
    return $provisionerGroup['CoGrouperProvisionerGroup']['description'];
  }
  
  /**
   * Return the Grouper group stem
   *
   * @since  COmanage Registry v0.9.3
   * @param  array $provisionerGroup CoGrouperProvisionerGroup data
   * @return string
   */

  public function getStem($provisionerGroup) {
    return $provisionerGroup['CoGrouperProvisionerGroup']['stem'];
  }

  /**
   * Return the Grouper group display extension
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $provisionerGroup CoGrouperProvisionerGroup data
   * @return string
   */

  public function getGroupDisplayExtension($provisionerGroup) {
    // Grouper group display extension is the same as the Grouper
    // group extension and so the same as the COmanage group name.
    return $provisionerGroup['CoGrouperProvisionerGroup']['extension'];
  }

  /**
   * Determine if a CoGroup name has changed because of a change in COU name.
   *
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup to examine
   * @return boolean true if group is COU auto managed and COU name changed
   * 
   */

  public function isCouNameChange($coProvisioningTargetData, $coGroup) {
    if(!$this->CoGroup->isCouAdminOrMembersGroup($coGroup)) {
      return false;
    }

    $current = $this->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $new = $this->computeProvisionerGroup($coProvisioningTargetData, $coGroup);
    $currentExtension = $this->getGroupDisplayExtension($current);
    $newExtension = $this->getGroupDisplayExtension($new);
    if ($currentExtension != $newExtension) {
        return true;
    } else {
      return false;
    }
  }

  /**
   * Determine if a Grouper group given by the full name, stem plus extension,
   * is a group managed by this plugin.
   *
   * @param  string $grouperGroupName full name of the Grouper group
   * @return boolean true if group is managed or false if not
   * 
   */

  public function isManaged($grouperGroupName) {
    // The input grouperGroupName is expected to be a full name as
    // returned by Grouper, ie. stem plus extension with colon (:)
    // separating the stems. An example is
    // MESS:Optics Group:CO_COU_Optics Group_members_active.
    
    $nameComponents = explode(':', $grouperGroupName);
    $extension = array_pop($nameComponents);
    $stem = implode(":", $nameComponents);

    $args = array();
    $args['conditions']['CoGrouperProvisionerGroup.stem'] = $stem;
    $args['conditions']['CoGrouperProvisionerGroup.extension'] = $extension;
    $args['contain'] = false;

    $provisionerGroup = $this->find('first', $args);
    if($provisionerGroup) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Update a mapping between a CO Group, provisioner target, and Grouper name.
   *
   * @since  COmanage Registry v0.8.3
   * @param  array $provisionerGroup CoGrouperProvisionerGroup to update
   * @return void
   * @throws RuntimeException
   */

  public function updateProvisionerGroup($provisionerGroup) {
    if(isset($provisionerGroup['CoGrouperProvisionerGroup']['modified'])) {
      unset($provisionerGroup['CoGrouperProvisionerGroup']['modified']);
    }
    
    if(!$this->save($provisionerGroup)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
}
