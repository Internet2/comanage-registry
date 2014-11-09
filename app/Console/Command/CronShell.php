<?php
/**
 * COmanage Cron Shell
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CronShell extends AppShell {
  var $uses = array('Co', 'CoExpirationPolicy', 'CoSetting');
  
  /**
   * Execute expirations for the specified CO
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer  $coId       CO ID
   */
  
  protected function expirations($coId) {
    // First see if expirations are enabled
    
    if($this->CoSetting->expirationEnabled($coId)) {
      $this->CoExpirationPolicy->executePolicies($coId, $this);
    } else {
      $this->out("- " . _txt('sh.cron.xp.disabled'));
    }
  }
  
  function main() {
    // Run background / scheduled tasks. For now, we only run expirations so we don't
    // bother with any command line flags. This might need to change in the future,
    // especially if we want to run things on an other than nightly/daily schedule.
    
    _bootstrap_plugin_txt();
    
    // First, pull a set of COs
    
    $args = array();
    $args['conditions']['Co.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $cos = $this->Co->find('all', $args);
    
    // Now hand off to the various tasks
    foreach($cos as $co) {
      $this->out(_txt('sh.cron.xp', array($co['Co']['name'], $co['Co']['id'])));
      $this->expirations($co['Co']['id']);
    }    
    
    $this->out(_txt('sh.cron.done'));
  }
}
