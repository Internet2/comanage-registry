<?php
/**
 * COmanage Registry CO Services Portal View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_services.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);
?>

<div id="co-services">
  
  <?php foreach ($co_services as $c): ?>

    <?php
    if(!empty($c['CoService']['co_group_id'])) {
      // Possibly render a join/leave link, depending on whether
      // the group is open and if this person is currently a member.
      $isMember = false;
      $isOpen = false;

      if(isset($vv_member_groups)
        && in_array($c['CoService']['co_group_id'], $vv_member_groups)) {
        $isMember = true;
      }

      if(isset($vv_open_groups)
        && in_array($c['CoService']['co_group_id'], $vv_open_groups)) {
        $isOpen = true;
      }

      $args = array(
        'controller' => 'co_services',
      );
      $action = "";
      $attribs = null;
      $containerClass = "";

      if($isMember) {
        if($isOpen) {
          $action = _txt('op.svc.leave');
          $args['action'] = 'leave';
          $attribs = array(
            'class' => 'deletebutton ui-button ui-corner-all ui-widget',
          );
        } else {
          $action = _txt('op.svc.member');
          $args = null;
        }
        $containerClass = " is-member";
      } else {
        if($isOpen) {
          $action = _txt('op.svc.join');
          $args['action'] = 'join';
          $attribs = array(
            'class' => 'addbutton ui-button ui-corner-all ui-widget',
          );
        } else {
          // XXX CO-1057
          // $action = _txt('op.svc.request');
          $args = null;
        }
      }
    }
    ?>

  <div class="co-card<?php print $containerClass ?>">
    <h2><?php print filter_var($c['CoService']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></h2>
    <div class="co-card-content">
      <?php /* XXX keep the following for future RFE; these improve the portal layout:
      <div class="co-card-image">
        <img src="http://www.npr.org/about/images/press/Logos/npr_logo_rgb.JPG"/>
      </div> */ ?>
      <div class="co-card-description">
        <?php print filter_var($c['CoService']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
      <div class="co-card-join-button">
        <?php
          if(!empty($c['CoService']['co_group_id'])) {
            // Render the join/leave link, depending on the outcome of the code above
            if($args) {
              $args[] = $c['CoService']['id'];
              
              // If we have a cou (ie: cou portal), add it here as advisory for redirect
              if(!empty($this->request->params['named']['cou'])) {
                $args['cou'] = filter_var($this->request->params['named']['cou'],FILTER_SANITIZE_SPECIAL_CHARS);
              }
              print $this->Html->link($action, $args, $attribs);
            } else {
              print $action;
            }
          }
        ?>
      </div>
      <div class="co-card-icons">
      <?php

        if(!empty($c['CoService']['service_url'])) {
          print $this->Html->link('<em class="material-icons" aria-hidden="true">public</em>',
            $c['CoService']['service_url'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => $c['CoService']['service_url']
            ));
        }
        if(!empty($c['CoService']['contact_email'])) {
          print $this->Html->link('<em class="material-icons" aria-hidden="true">email</em>',
            'mailto:'.$c['CoService']['contact_email'],
            array(
              'class' => 'co-card-link',
              'escape' => false,
              'title' => 'mailto:'.$c['CoService']['contact_email']
            ));
        }

      ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<?php if(!empty($co_departments)): ?>
  <div id="co-departments">
    <h2><?php print _txt('ct.co_departments.pl'); ?></h2>
    <?php foreach ($co_departments as $c): ?>
      <div class="co-dept">
        <div class="co-dept-header">
          <h3><?php print filter_var($c['CoDepartment']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></h3>
        </div>
        <div class="co-dept-content">
          <?php if(!empty($c['CoDepartment']['description'])): ?>
            <p class="co-dept-desc">
              <?php print filter_var($c['CoDepartment']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
            </p>
          <?php endif; ?>
          <?php if(!empty($c['CoDepartment']['introduction'])): ?>
            <p class="co-dept-intro">
              <?php print filter_var($c['CoDepartment']['introduction'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
            </p>
          <?php endif; ?>

          <?php if(!empty($c['Address']) or
                   !empty($c['EmailAddress']) or
                   !empty($c['Identifier']) or
                   !empty($c['TelephoneNumber']) or
                   !empty($c['Url'])): ?>
            <ul class="co-dept-mvpas fields form-list">
              <?php if(!empty($c['Address'])) foreach($c['Address'] as $addr): ?>
                <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print _txt('fd.address') . " <em>(" . $addr['type'] . ")</em>"; ?>
                    </div>
                  </div>
                  <div class="field-info">
                    <?php print filter_var(formatAddress($addr),FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </div>
                </li>
              <?php endforeach; // address ?>
              <?php if(!empty($c['EmailAddress'])) foreach($c['EmailAddress'] as $email): ?>
                <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print _txt('ct.email_addresses.1') . " <em>(" . $email['type'] . ")</em>"; ?>
                    </div>
                  </div>
                  <div class="field-info">
                    <?php print $this->Html->link($email['mail'], 'mailto:' . $email['mail']); ?>
                  </div>
                </li>
              <?php endforeach; // email ?>
              <?php if(!empty($c['Identifier'])) foreach($c['Identifier'] as $id): ?>
                <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print _txt('fd.identifier.identifier') . " <em>(" . $id['type'] . ")</em>"; ?>
                    </div>
                  </div>
                  <div class="field-info">
                    <?php print filter_var($id['identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </div>
                </li>
              <?php endforeach; // identifier ?>
              <?php if(!empty($c['TelephoneNumber'])) foreach($c['TelephoneNumber'] as $phone): ?>
                <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print _txt('ct.telephone_numbers.1') . " <em>(" . $phone['type'] . ")</em>"; ?>
                    </div>
                  </div>
                  <div class="field-info">
                    <?php print filter_var(formatTelephone($phone),FILTER_SANITIZE_SPECIAL_CHARS); ?>
                  </div>
                </li>
              <?php endforeach; // telephone ?>
              <?php if(!empty($c['Url'])) foreach($c['Url'] as $url): ?>
                <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print _txt('fd.url.url') . " <em>(" . $url['type'] . ")</em>"; ?>
                    </div>
                  </div>
                  <div class="field-info">
                    <?php print $this->Html->link($url['url']); ?>
                  </div>
                </li>
              <?php endforeach; // url ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
