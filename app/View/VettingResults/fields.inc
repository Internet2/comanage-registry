<?php
/**
 * COmanage Registry Vetting Results Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'vetting_requests';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.vetting_requests.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'vetting_requests';
  $args['action'] = 'view';
  $args[] = $vetting_results[0]['VettingResult']['vetting_request_id'];
  $this->Html->addCrumb($vetting_results[0]['VettingResult']['vetting_request_id'], $args);
  
  $this->Html->addCrumb($vetting_results[0]['VettingStep']['description']);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.vetting_results.1')));
  $this->Html->addCrumb($crumbTxt);
?>
<ul id="<?php print $this->action; ?>_vetting_result" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.id.seq'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_results[0]['VettingResult']['id'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.vetting_requests.1'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_results[0]['VettingResult']['vetting_request_id'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.vet.subject'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print $this->Html->link(
          generateCn($vetting_results[0]['VettingRequest']['CoPerson']['PrimaryName']),
          array(
            'controller' => 'co_people',
            'action'     => 'canvas',
            $vetting_results[0]['VettingRequest']['CoPerson']['id']
          )
        );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.vetting_steps.1'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_results[0]['VettingStep']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.status'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print _txt('en.status.vet', null, $vetting_results[0]['VettingResult']['status']); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.comment'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_results[0]['VettingResult']['comment'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.vet.vetter'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if(!empty($vetting_results[0]['VetterCoPerson']['id'])) {
          print $this->Html->link(
            generateCn($vetting_results[0]['VetterCoPerson']['PrimaryName']),
            array(
              'controller' => 'co_people',
              'action'     => 'canvas',
              $vetting_results[0]['VetterCoPerson']['id']
            )
          );
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.vet.raw'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($vetting_results[0]['VettingResult']['raw'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
</ul>
<?php
  print $this->element("changelog");
