<?php
/**
 * COmanage Registry CO Provisioner Plugin Target Parent Model
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

abstract class CoProvisionerPluginTarget extends AppModel {
  // Define class name for cake
  public $name = "CoProvisionerPluginTarget";
  
  /**
   * Determine the provisioning status of this target for a CO Person ID.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Provisioning Target ID
   * @param  Integer CO Person ID
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  abstract public function status($coProvisioningTargetId, $coPersonId);
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array CO Person data
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  abstract public function provision($coProvisioningTargetData, $op, $coPersonData);
}
