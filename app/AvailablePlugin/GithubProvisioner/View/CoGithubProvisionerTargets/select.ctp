<?php
/**
 * COmanage Registry CO GitHub Provisioner Target Org Selector View
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  print $this->Form->create('CoGithubProvisionerTarget',
                            array('action' => 'select',
                                  'inputDefaults' => array('label' => false, 'div' => false)));
  
  print $this->Form->hidden('id', array('default' => $vv_co_github_provisioner_target['CoGithubProvisionerTarget']['id'])) . "\n";
  
  $attrs = array();
  $attrs['value'] = (isset($vv_co_github_provisioner_target['CoGithubProvisionerTarget']['github_org'])
                     ? $vv_co_github_provisioner_target['CoGithubProvisionerTarget']['github_org']
                     : "");
  $attrs['empty'] = false;
  
  print $this->Form->select('CoGithubProvisionerTarget.github_org',
                            $vv_owned_github_orgs,
                            $attrs);
  
  if($this->Form->isFieldError('CoGithubProvisionerTarget.github_org')) {
    print $this->Form->error('CoGithubProvisionerTarget.github_org');
  }
  
  print $this->Form->submit(_txt('op.save'));
  
  print $this->Form->end();
?>
