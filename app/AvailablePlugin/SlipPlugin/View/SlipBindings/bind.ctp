<?php
/**
 * COmanage Registry SLIP Authenticator Binding Bind View
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

?>
<?php if(empty($slip_binding)
         || empty($slip_binding['SlipBinding']['code'])
         || ($slip_binding['SlipBinding']['status'] != SlipBindingStatusEnum::PendingVerification)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('er.slipplugin.bind', array($this->request->pass[0])); ?>
  </div>
</div>
<?php else: // slip_binding ?>
<?php print _txt('pl.slipplugin.bind.code', array($slip_binding['SlipBinding']['code'])); ?>
<?php endif; // slip_binding ?>
