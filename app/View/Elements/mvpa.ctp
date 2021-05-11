<?php
/*
 * COmanage Registry MVPA Elements
 * Used in various "canvas" views
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

 // Vars passed in from the calling view:
 // $edit if record is editable
 // $model = CoDepartment, CoPerson, CoPersonRole, OrgIdentity
 //  Note: Name not yet supported
 // $model_param = codeptid, copersonid, copersonroleid, orgidentityid
 // $mvpa_field = identifier, mail, etc
 // $mvpa_format = function to generate a display string (eg: formatTelephone)
 // $mvpa_model = Identifier, EmailAddress, etc
 
 // eg identifier
 $lmvpa = strtolower($mvpa_model);
 
 // eg identifiers
 $lmvpapl = Inflector::tableize($mvpa_model);
 
 // eg co_people
 $lmpl = Inflector::tableize($model);
 
 // eg SourceIdentifier
 $smodel = "Source" . $mvpa_model;
 
 // Dictionary lookup for extended types
 $vv_dictionary = 'vv_' . $lmvpapl . '_types';
 
 $action = ($edit ? 'edit' : 'view');
 $lorder = ($edit ? $this->Menu->getMenuOrder('Edit') : $this->Menu->getMenuOrder('View'));
 $action_icon = ($edit ? $this->Menu->getMenuIcon('Edit') : $this->Menu->getMenuIcon('View'));
?>

  <li id="fields-<?php print $lmvpa; ?>" class="fieldGroup">
    <?php
      // Determine if permission is present for add button
      if(($self_service
          && ($this->Permission->selfService($permissions, $edit, $mvpa_model) == PermissionEnum::ReadWrite))
         || (!$self_service && $edit)):
    ?>
      <div class="coAddEditButtons">
        <?php
          // Render the add button
          $linktarget = array(
            'controller'    => $lmvpapl,
            'action'        => 'add',
            $model_param    => ${$lmpl}[0][$model]['id']
          );
          $linkparams = array('class' => 'addbutton');

          print $this->Html->link(_txt('op.add'), $linktarget, $linkparams);
        ?>
      </div>
    <?php endif; // edit ?>
    <a href="#tabs-<?php print $lmvpa; ?>" class="fieldGroupName">
      <em class="material-icons">indeterminate_check_box</em>
      <?php print _txt('ct.'.$lmvpapl.'.pl'); ?>
    </a>
    <ul id="tabs-<?php print $lmvpa; ?>" class="fields data-list">
      <?php
        // Loop through each record and render
        if(!empty(${$lmpl}[0][$mvpa_model])) {
          foreach(${$lmpl}[0][$mvpa_model] as $m) {
            $editable = ($action == 'edit');
            $removetxt = _txt('js.remove');
            $displaystr = (!empty($mvpa_field) ? $m[$mvpa_field] : "");
            $laction = $action;
            // Store the action list
            $action_args = array();
            $action_args['vv_attr_mdl'] = $mvpa_model;
            $action_args['vv_attr_id'] = $m["id"];
            // Store the Bagde list
            $badge_list = array();

            if(!empty($m[$smodel]['id'])) {
              // Records attached to a SourceModel are read only
              $editable = false;
              $laction = 'view';
              $lorder = $this->Menu->getMenuOrder('View');
              $action_icon = $this->Menu->getMenuIcon('View');
            } elseif($self_service) {
              // If self service, check appropriate permissions
              $perm = $this->Permission->selfService($permissions,
                                                     $edit,
                                                     $mvpa_model,
                                                     (!empty($m['type']) ? $m['type'] : null));
              
              switch($perm) {
                case PermissionEnum::ReadWrite:
                  $editable = true;
                  $laction = 'edit';
                  $lorder = $this->Menu->getMenuOrder('Edit');
                  $action_icon = $this->Menu->getMenuIcon('Edit');
                  break;
                case PermissionEnum::ReadOnly:
                  $editable = false;
                  $laction = 'view';
                  $lorder = $this->Menu->getMenuOrder('View');
                  $action_icon = $this->Menu->getMenuIcon('View');
                  break;
                default:
                  // No permission, skip this entry entirely
                  continue 2;
                  break;
              }
            }

            // Lookup the extended type friendly name, if set
            if(!empty($m['type']) && isset(${$vv_dictionary}[ $m['type'] ])) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Type'),
                'text' => ${$vv_dictionary}[ $m['type'] ],
                'color' => $this->Badge->getBadgeColor('Light'),
              );
            } elseif (!empty($m['type'])) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Type'),
                'text' => $m['type'],
                'color' => $this->Badge->getBadgeColor('Light'),
              );
            }
            
            // Adjust text strings for identifiers associated with provisioning targets specially
            if($mvpa_model == 'Identifier' && $model == 'CoPerson') {
              if(!empty($m['type'])) {
                if($m['type'] == IdentifierEnum::ProvisioningTarget) {
                  $removetxt = _txt('js.remove.id.prov');
                  
                  if(!empty($m['CoProvisioningTarget']['description'])) {
                    // Render a link to the target, instead, though only for admins.
                    // We'll use link permission as a proxy (even though it really
                    // means something else).

                    if($permissions['link']) {
                      $action_args['vv_actions'][] = array(
                        'order' => $this->Menu->getMenuOrder('Provision'),
                        'icon' => $this->Menu->getMenuIcon('Provision'),
                        'url' => $this->Html->link(
                          array(
                            'controller' => 'co_provisioning_targets',
                            'action' => 'edit',
                            $m['CoProvisioningTarget']['id'],
                            )
                        ),
                        'label' => $m['CoProvisioningTarget']['description'],
                      );
                    } else {
                      $badge_list[] = array(
                        'order' => $this->Badge->getBadgeOrder('Other'),
                        'text' => $m['CoProvisioningTarget']['description'],
                        'color' => $this->Badge->getBadgeColor('Light'),
                        'outline' => true,
                      );
                    }
                  }
                }
              }
            }
            
            // Prepend the attribute source to the type string, if there is one
            if(!empty($m[$smodel]['id'])) {
                $badge_list[] = array(
                  'order' => $this->Badge->getBadgeOrder('Source'),
                  'text' => filter_var($m[$smodel]['OrgIdentity']['OrgIdentitySourceRecord']['OrgIdentitySource']['description'],FILTER_SANITIZE_SPECIAL_CHARS),
                  'color' => $this->Badge->getBadgeColor('Light'),
                  'outline' => true,
                );
            }
            
            // Add a suspended badge, if appropriate
            if(isset($m['status']) && $m['status'] == SuspendableStatusEnum::Suspended) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Status'),
                'text' => _txt('en.status.susp', null, SuspendableStatusEnum::Suspended),
                'color' => $this->Badge->getBadgeColor('Danger'),
              );
            }
            
            // If this is an Email Address and is verified, add that to the type string
            if($mvpa_model === 'EmailAddress'
               && isset($m['verified'])
               && !$m['verified']) {
              // Action
              $dg_url = array(
                'controller' => 'co_invites',
                'action' => 'verifyEmailAddress',
                'email_address_id' => $m['id'],
              );
              // Verify button
              $action_args['vv_actions'][] = array(
                'order' => $this->Menu->getMenuOrder('EmailVerify'),
                'icon' => $this->Menu->getMenuIcon('EmailVerify'),
                'url' => 'javascript:void(0);',
                'label' => _txt('op.verify'),
                'onclick' => array(
                  'dg_bd_txt' => _txt('js.confirm.verify'),
                  'dg_url' => $this->Html->url($dg_url),
                  'dg_conf_btn' => _txt('op.verify'),
                  'dg_cancel_btn' => _txt('op.cancel'),
                  'dg_title' => _txt('op.verify'),
                  'db_bd_txt_repl_str' => filter_var(_jtxt($m['mail']), FILTER_SANITIZE_STRING),
                ),
              );

              // Badge email Unverified
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Status'),
                'text' => _txt('fd.email_address.unverified'),
                'color' => $this->Badge->getBadgeColor('Warning'),
              );
            }
            
            // If $mvpa_format is a defined function, use that to render the display string
            if(!empty($mvpa_format) && function_exists($mvpa_format)) {
              $displaystr = $mvpa_format($m);
            }

            print '<li class="field-data-container">';
            print '<div class="field-data force-wrap">';
            if (($mvpa_model == 'Identifier') && !$permissions['identifiers'] ) {
              print $displaystr;
            } else {
              // Render the text link
              print $this->Html->link($displaystr,
                                      array('controller' => $lmvpapl,
                                            'action' => $laction,
                                            $m['id']));
            }
            print '</div>';
            print '<div class="field-data data-label">';
            if(!empty($badge_list)) {
              print $this->element('badgeList', array('vv_badge_list' => $badge_list));
            }
            print '</div>';
            print '<div class="field-actions">';
            // Render specific buttons
            if($mvpa_model == 'Identifier') {
              // Login identifiers link to Authentication Events
              if(isset($m['login']) && $m['login']) {
                $action_args['vv_actions'][] = array(
                  'order' => $this->Menu->getMenuOrder('AuthEvent'),
                  'icon' => $this->Menu->getMenuIcon('AuthEvent'),
                  'url' => $this->Html->url(
                    array(
                      'controller' => 'authentication_events',
                      'action' => 'index',
                      'identifier' => rawurlencode($m['identifier']),
                    )
                  ),
                  'label' => _txt('ct.authentication_events.pl'),
                );
              }
            }
            
            if (($mvpa_model != 'Identifier') || 
               (($mvpa_model == 'Identifier') && $permissions['identifiers'])) {
              // This renders the View or Edit button, as appropriate
              $action_args['vv_actions'][] = array(
                'order' => $lorder,
                'icon' => $action_icon,
                'url' => $this->Html->url(
                  array(
                    'controller' => $lmvpapl,
                    'action' => $laction,
                    $m['id'])
                ),
                'label' => _txt('op.'.$laction),
              );
            }
            
            // Possibly render a delete button
            if($laction == 'edit' && $editable) {
              // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
              $dg_url = array(
                'controller' => $lmvpapl,
                'action' => 'delete',
                $m['id'],
              );
              // Delete button
              $action_args['vv_actions'][] = array(
                'order' => $this->Menu->getMenuOrder('Delete'),
                'icon' => $this->Menu->getMenuIcon('Delete'),
                'url' => 'javascript:void(0);',
                'label' => _txt('op.delete'),
                'onclick' => array(
                  'dg_bd_txt' => $removetxt,
                  'dg_url' => $this->Html->url($dg_url),
                  'dg_conf_btn' => _txt('op.remove'),
                  'dg_cancel_btn' => _txt('op.cancel'),
                  'dg_title' => _txt('op.remove'),
                  'db_bd_txt_repl_str' => filter_var(_jtxt($displaystr),FILTER_SANITIZE_STRING),
                ),
              );
            }

            if(!empty($action_args['vv_actions'])) {
              print $this->element('menuAction', $action_args);
            }
            print '</div>';
            print '</li>';
          }
        }
      ?>
    </ul><!-- tabs-<?php print $lmvpa; ?> -->
  </li><!-- fields-<?php print $lmvpa; ?> -->
