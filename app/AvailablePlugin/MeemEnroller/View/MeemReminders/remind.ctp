<?php
/**
 * COmanage Registry Meem Enroller Remind View
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
  
  $params = array(
    'title' => _txt('pl.meemenroller.remind.title')
  );
  
  print $this->element("pageTitle", $params);
  
  // MFA enrollment flow ID, via controller
  $efid = $vv_efid;
  // How long until MFA exemption expires (if set), via 'countdown' query param
  // null = no deadline, 0 = expired
  $due = null;
  // Where to send the user when finished (if set), via 'return query param',
  // encoded for use with Petition Specific Redirect Targets
  $encreturn = null;
    
  if(!empty($this->request->query['countdown'])) {
    if($this->request->query['countdown'] > 0) {
      $due = $this->Time->nice(time() + $this->request->query['countdown']);
    } elseif($this->request->query['countdown'] == 0) {
      $due = 0;
    }
  }
  
  if(!empty($vv_return_url)) {
    $encreturn = str_replace(array("+", "/", "="), array(".", "_", "-"), base64_encode($vv_return_url));
  }
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
  <?php
    if($due === null) {
      print _txt('pl.meemenroller.remind.message');
    } elseif($due === 0) {
      print _txt('pl.meemenroller.remind.message.req');
    } else {
      print _txt('pl.meemenroller.remind.message.soon', array($due));
    }
  ?>
  </div>
</div>
<?php
  if($due !== 0 && !empty($vv_return_url)) {
    print $this->Html->link(_txt('pl.meemenroller.remind.later'),
                            $vv_return_url,
                            array('class' => 'cancelbutton')) . "\n";
  }
  
  $redirect = array(
    'plugin' => null,
    'controller' => 'co_petitions',
    'action' => 'start',
    'coef' => $efid
  );
  
  if($encreturn) {
    $redirect['return'] = $encreturn;
  }
  
  print $this->Html->link(_txt('pl.meemenroller.remind.enroll'),
                          $redirect,
                          array('class' => 'forwardbutton')) . "\n";
