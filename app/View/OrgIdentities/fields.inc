<?php
/**
 * COmanage Registry OrgIdentity Fields
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Globals
  global $cm_lang, $cm_texts;

  // Determine if fields are editable
  $e = false;

  if(($this->action == "add" && $permissions['add'])
      || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if (!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
  }
  $this->Html->addCrumb(_txt('ct.org_identities.1'));

  if($e)
  {
    // Populate CO ID if approporiate

    if(!$pool_org_identities)
      print $this->Form->hidden('OrgIdentity.co_id',
                               array('default' => $cur_co['Co']['id'])). "\n";
  }

  // The fields to render are determined by the CMP Enrollment Flow configuration,
  // which is passed to us in $cmp_ef_attributes. The minor gotcha is that MVPAs
  // allow multiple versions their attributes where the enrollment flow configuration
  // interface doesn't currently allow for this (although it should). So we'll
  // follow the config exactly on an add, but on an edit if the fields aren't pulled
  // from LDAP or SAML we'll allow multiple. This will probably need to get rewritten
  // at some point.

  // Track which models we've emitted hidden fields for
  $emitted = array();

  $l = 1;
?>

<script type="text/javascript">
  $(function() {
    validate_date_input('<?php print _txt('er.validation') ?>', '<?php print _txt('er.validation.date') ?>');

    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).parent().next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "expand_less") {
        $(this).find(".material-icons").text("expand_more");
        $(this).attr('title','<?php print _txt('op.expand'); ?>');
        $(this).attr('aria-expanded','false');
      } else {
        $(this).find(".material-icons").text("expand_less");
        $(this).attr('title','<?php print _txt('op.collapse'); ?>');
        $(this).attr('aria-expanded','true');
      }
    });

  });

  function js_local_onload() {
    enum_update_gadgets(false);
  }
</script>
  
<?php 
  $args = array(
    'enumerables' => array(
      'OrgIdentity.o',
      'OrgIdentity.ou',
      'OrgIdentity.title'
    )
  );
  print $this->element('enumerations', $args); 
?>

<?php if($this->action == 'view' && !empty($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('op.orgid.edit.ois', array($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['description'])); ?>
    </div>
  </div>
<?php endif; // view ?>

<?php if($this->action == 'edit'): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.orgidentities'); ?>
    </div>
  </div>
<?php endif; // edit ?>

<div id="<?php print $this->action; ?>_org_identity" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>

      <!-- Names -->
      <li id="fields-name" class="fieldGroup">
        <div class="fieldGroupNameContainer">
          <a href="#tabs-name" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="names-container">
            <em class="material-icons" aria-hidden="true">expand_less</em>
            <h2><?php print _txt('fd.name.affil'); ?></h2>
          </a>
          <?php if($e && $this->action != 'add'): ?>
            <div class="coAddEditButtons">
              <?php
                $linktarget = array('controller'    => 'names',
                  'action'        => 'add',
                  'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                  'co'            => ($pool_org_identities ? false : $cur_co['Co']['id']));
                $linkparams = array('class'  => 'addbutton');
  
                print $this->Html->link(_txt('op.add'), $linktarget, $linkparams);
              ?>
            </div>
          <?php endif; // e ?>
        </div>

        <div id="names-container" class="fields">
          <?php if($this->action == 'add'): ?>
            <ul id="tabs-name" class="fields form-list">
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.honorific', _txt('fd.name.honorific') .  " " . _txt('fd.name.h.desc')) : _txt('fd.name.honorific')); ?>
                </div>
                <div class="field-info">
                  <?php
                    print $this->Form->hidden('PrimaryName.id');
                    print $this->Form->hidden('PrimaryName.type', array('default' => NameEnum::Official));
                    print $this->Form->hidden('PrimaryName.primary_name', array('default' => true));
                    print ($e ? $this->Form->input('PrimaryName.honorific') : filter_var($org_identities[0]['PrimaryName']['honorific'],FILTER_SANITIZE_SPECIAL_CHARS));
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.given', _txt('fd.name.given')) . ' <span class="required">*</span>' : _txt('fd.name.given')); ?>
                </div>
                <div class="field-info">
                  <?php print ($e ? $this->Form->input('PrimaryName.given') : filter_var($org_identities[0]['PrimaryName']['given'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.middle', _txt('fd.name.middle')) : _txt('fd.name.middle')); ?>
                </div>
                <div class="field-info">
                  <?php print ($e ? $this->Form->input('PrimaryName.middle') : filter_var($org_identities[0]['PrimaryName']['middle'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.family', _txt('fd.name.family')) :  _txt('fd.name.family')); ?>
                </div>
                <div class="field-info">
                  <?php print ($e ? $this->Form->input('PrimaryName.family') : filter_var($org_identities[0]['PrimaryName']['family'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.suffix', _txt('fd.name.suffix') . " " . _txt('fd.name.s.desc')) :  _txt('fd.name.suffix')); ?>
                </div>
                <div class="field-info">
                  <?php print ($e ? $this->Form->input('PrimaryName.suffix') : filter_var($org_identities[0]['PrimaryName']['suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print _txt('fd.type'); ?>
                </div>
                <div class="field-info">
                  <?php print _txt('en.name.type', null, NameEnum::Official); ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <?php print ($e ? $this->Form->label('PrimaryName.language', _txt('fd.language')) : _txt('fd.language')); ?>
                </div>
                <div class="field-info">
                  <?php
                    global $cm_lang, $cm_texts;

                    $attrs = array();
                    $attrs['value'] = (isset($org_identities[0]['PrimaryName']['language'])
                      ? $org_identities[0]['PrimaryName']['language']
                      : getPreferredLanguage());
                    $attrs['empty'] = true;

                    if($e) {
                      print $this->Form->select('PrimaryName.language',
                        $cm_texts[ $cm_lang ]['en.language'],
                        $attrs);

                      if($this->Form->isFieldError('PrimaryName.language')) {
                        print $this->Form->error('PrimaryName.language');
                      }
                    } else {
                      print _txt('en.language', null, $org_identities[0]['PrimaryName']['language']);
                    }
                  ?>
                </div>
              </li>
            </ul>
          <?php else: // add ?>
            <ul id="tabs-name" class="fields data-list">
              <?php foreach($org_identities[0]['Name'] as $n): ?>
              <?php
                $action_name_args = array();
                $action_name_args['vv_attr_mdl'] = "Name";
                $action_name_args['vv_attr_id'] = $n['id'];
              ?>
                <li class="field-data-container">
                  <div class="field-data force-wrap">
                    <?php
                      $primary_name_with_bg = $this->Badge->badgeIt(
                        _txt('fd.name.primary_name'),
                        $this->Badge->getBadgeColor('Secondary'),
                        false,
                        true
                      );
                      $cn = $n['primary_name'] ? filter_var(generateCn($n),FILTER_SANITIZE_SPECIAL_CHARS) . " " . $primary_name_with_bg
                                               : filter_var(generateCn($n),FILTER_SANITIZE_SPECIAL_CHARS);
                      print $this->Html->link(
                        $cn,
                        array(
                          'controller' => 'names',
                          'action' => ($e ? 'edit' : 'view'),
                          $n['id']),
                        array(
                          'escape' => false,
                        )
                      );
                    ?>
                  </div>
                  <div class="field-data data-label">
                    <?php
                    $badge_list = array();
                    // Name Type
                    $badge_list[] = array(
                      'order' => $this->Badge->getBadgeOrder('Type'),
                      'text' => _txt('en.name.type', null, $n['type']),
                      'color' => $this->Badge->getBadgeColor('Light'),
                    );
                    // Name Language
                    if(!empty($n['language'])) {
                      $badge_list[] = array(
                        'order' => $this->Badge->getBadgeOrder('Other'),
                        'text' => _txt('en.language', null, $n['language']),
                        'color' => $this->Badge->getBadgeColor('Light'),
                      );
                    }
                    if(!empty($badge_list)) {
                      print $this->element('badgeList', array('vv_badge_list' => $badge_list));
                    }
                    ?>
                  </div>
                  <div class="field-actions">
                    <?php
                      if($e) {
                        if(!$n['primary_name']) {
                          // Delete Action Button
                          $dg_url = array(
                            'controller' => 'names',
                            'action' => 'delete', $n['id'],
                            'co' => $cur_co['Co']['id']
                          );
                          $action_name_args['vv_actions'][] = array(
                            'order' => $this->Menu->getMenuOrder('Delete'),
                            'icon' => $this->Menu->getMenuIcon('Delete'),
                            'url' => 'javascript:void(0);',
                            'label' => _txt('op.delete'),
                            'onclick' => array(
                              'dg_bd_txt' => _txt('js.remove'),
                              'dg_url' => $this->Html->url($dg_url),
                              'dg_conf_btn' => _txt('op.remove'),
                              'dg_cancel_btn' => _txt('op.cancel'),
                              'dg_title' => _txt('op.remove'),
                              'db_bd_txt_repl_str' => filter_var(_jtxt(generateCn($n)), FILTER_SANITIZE_STRING),
                            ),
                          );
                          // Primary Name Action Button
                          $action_name_args['vv_actions'][] = array(
                            'order' => $this->Menu->getMenuOrder('PrimaryName'),
                            'icon' => $this->Menu->getMenuIcon('Icon'),
                            'label' => _txt('op.primary'),
                            'url' => $this->Html->url(
                              array(
                                'controller' => 'names',
                                'action' => 'primary',
                                $n['id'],
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                              )
                            ),
                          );
                        }
                        // Edit Action Button
                        $action_name_args['vv_actions'][] = array(
                          'order' => $this->Menu->getMenuOrder('Edit'),
                          'icon' => $this->Menu->getMenuIcon('Edit'),
                          'url' => $this->Html->url(
                            array(
                              'controller' => 'names',
                              'action' => 'edit',
                              $n['id'],
                            )
                          ),
                          'label' => _txt('op.edit'),
                        );
                      } else {
                        // View Action Button
                        $action_name_args['vv_actions'][] = array(
                          'order' => $this->Menu->getMenuOrder('View'),
                          'icon' => $this->Menu->getMenuIcon('View'),
                          'lightbox' => true,
                          'url' => $this->Html->url(
                            array(
                              'controller' => 'names',
                              'action' => 'view',
                              $n['id'],
                            )
                          ),
                          'label' => _txt('op.view'),
                        );
                    }
                    if(!empty($action_name_args['vv_actions'])) {
                      print $this->element('menuAction', $action_name_args);
                    }
                    ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; // add / edit ?>
          <ul id="tabs-name-fields" class="fields form-list">
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
            </div>
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs['value'] = (isset($org_identities[0]['OrgIdentity']['status'])
                  ? $org_identities[0]['OrgIdentity']['status']
                  : "M");
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('status',
                    $cm_texts[ $cm_lang ]['en.status.org'],
                    $attrs);

                  if($this->Form->isFieldError('status')) {
                    print $this->Form->error('status');
                  }
                } else {
                  if(!empty($org_identities[0]['OrgIdentity']['status'])) {
                    print $cm_texts[ $cm_lang ]['en.status.org'][ $org_identities[0]['OrgIdentity']['status'] ];
                  }
                }
              ?>
            </div>
          </li>
          <li class="modelbox-data">
            <div class="field-name">
              <?php print $this->Form->label('OrgIdentity.date_of_birth', _txt('fd.date_of_birth')); ?>
            </div>
            <div class="field-info">
              <?php
                if($e) {
                  $args = array(
                    'class' => 'datepicker'
                  );

                  if(!empty($org_identities[0]['OrgIdentity']['date_of_birth'])) {
                    $args['value'] = $org_identities[0]['OrgIdentity']['date_of_birth'];
                  }

                  print $this->Form->text('date_of_birth', $args);
                } else {
                  if(!empty($org_identities[0]['OrgIdentity']['date_of_birth'])) {
                    print $this->Time->format($org_identities[0]['OrgIdentity']['date_of_birth'], "%F");
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
            </div>
            <div class="field-info">
              <?php
                $attrs = array();
                $attrs['value'] = (isset($org_identities[0]['OrgIdentity']['affiliation'])
                  ? $org_identities[0]['OrgIdentity']['affiliation']
                  : null);
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('affiliation',
                    $vv_affiliation_types,
                    $attrs);

                  if($this->Form->isFieldError('affiliation')) {
                    print $this->Form->error('affiliation');
                  }
                } else {
                  if(!empty($org_identities[0]['OrgIdentity']['affiliation'])) {
                    print $vv_affiliation_types[ $org_identities[0]['OrgIdentity']['affiliation'] ];
                  }
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('title', _txt('fd.title')) : _txt('fd.title')); ?>
            </div>
            <div class="field-info">
              <?php
                $args = array(
                  'column' => 'title',
                  'editable' => $e
                );
                print $this->element('enumerableField', $args);
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('o', _txt('fd.o')) : _txt('fd.o')); ?>
            </div>
            <div class="field-info">
              <?php
                $args = array(
                  'column' => 'o',
                  'editable' => $e
                );
                print $this->element('enumerableField', $args);
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('ou', _txt('fd.ou')) : _txt('fd.ou')); ?>
            </div>
            <div class="field-info">
              <?php
                $args = array(
                  'column' => 'ou',
                  'editable' => $e
                );
                print $this->element('enumerableField', $args);
              ?>
            </div>
          </li>
          <li class="modelbox-data">
            <div class="field-name">
              <?php
                if($e) {
                  print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
                } else {
                  print _txt('fd.valid_from');
                }
              ?>
              <div class="field-desc">
                <?php print _txt('fd.valid_from.desc'); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
                if($e) {
                  $args = array(
                    'class' => 'datepicker-f'
                  );

                  if(isset($org_identities[0]['OrgIdentity']['valid_from'])
                    && $org_identities[0]['OrgIdentity']['valid_from'] > 0) {
                    if(!empty($vv_tz)) {
                      // We need to adjust the UTC value to the user's local time
                      $args['value'] = $this->Time->format($org_identities[0]['OrgIdentity']['valid_from'], "%F %T", false, $vv_tz);
                    } else {
                      $args['value'] = $org_identities[0]['OrgIdentity']['valid_from'];
                    }
                  }

                  print $this->Form->text('valid_from', $args);
                } else {
                  if(isset($org_identities[0]['OrgIdentity']['valid_from'])
                    && $org_identities[0]['OrgIdentity']['valid_from'] > 0) {
                    print $this->Time->format($org_identities[0]['OrgIdentity']['valid_from'], "%c $vv_tz", false, $vv_tz);
                  }
                }
              ?>
            </div>
          </li>
          <li class="modelbox-data">
            <div class="field-name">
              <?php
                if($e) {
                  print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
                } else {
                  print _txt('fd.valid_through');
                }
              ?>
              <div class="field-desc">
                <?php print _txt('fd.valid_through.desc'); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
                if($e) {
                  $args = array(
                    'class' => 'datepicker-u'
                  );

                  if(isset($org_identities[0]['OrgIdentity']['valid_through'])
                    && $org_identities[0]['OrgIdentity']['valid_through'] > 0) {
                    if(!empty($vv_tz)) {
                      // We need to adjust the UTC value to the user's local time
                      $args['value'] = $this->Time->format($org_identities[0]['OrgIdentity']['valid_through'], "%F %T", false, $vv_tz);
                    } else {
                      $args['value'] = $org_identities[0]['OrgIdentity']['valid_through'];
                    }
                  }

                  print $this->Form->text('valid_through', $args);
                } else {
                  if(isset($org_identities[0]['OrgIdentity']['valid_through'])
                    && $org_identities[0]['OrgIdentity']['valid_through'] > 0) {
                    print $this->Time->format($org_identities[0]['OrgIdentity']['valid_through'], "%c $vv_tz", false, $vv_tz);
                  }
                }
              ?>
            </div>
          </li>
          <!-- Manager and Sponsor fields are read only because we only support
               setting these fields in an OIS context. In Registry v5, Org
               Identities basically become read only to the administrator.
          -->
          <li>
            <div class="field-name">
              <?php print _txt('fd.manager'); ?>
            </div>
            <div class="field-info">
              <?php if(!empty($org_identities[0]['OrgIdentity']['manager_identifier'])) print filter_var($org_identities[0]['OrgIdentity']['manager_identifier'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print _txt('fd.sponsor'); ?>
            </div>
            <div class="field-info">
              <?php if(!empty($org_identities[0]['OrgIdentity']['sponsor_identifier'])) print filter_var($org_identities[0]['OrgIdentity']['sponsor_identifier'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
            </div>
          </li>
          <?php if($e): ?>
            <li class="fields-submit">
              <div class="field-name">
                <span class="required"><?php print _txt('fd.req'); ?></span>
              </div>
              <div class="field-info">
                <?php print $this->Form->submit($submit_label); ?>
              </div>
            </li>
          <?php endif; ?>
        </ul><!-- tabs-name--fields -->
        </div>
      </li><!-- fields-name -->

      <?php
        if($this->action != "add") {
          $args = array(
            'edit'         => $e,
            'self_service' => false,
            'model'        => 'OrgIdentity',
            'model_param'  => 'orgidentityid',
            'mvpa_model'   => 'Identifier',
            'mvpa_field'   => 'identifier'
          );
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'EmailAddress';
          $args['mvpa_field'] = 'mail';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'Url';
          $args['mvpa_field'] = 'url';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'TelephoneNumber';
          $args['mvpa_field'] = null;
          $args['mvpa_format'] = 'formatTelephone';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'Address';
          $args['mvpa_format'] = 'formatAddress';
          
          print $this->element('mvpa', $args);
          
          $args['mvpa_model'] = 'AdHocAttribute';
          $args['self_service'] = false;
          $args['mvpa_format'] = 'formatAdHoc';
          
          print $this->element('mvpa', $args);
        }
      ?>

      <?php if($this->action != "add"): ?>       
        <!-- Associated CoPeople listings -->
        <li id="fields-copeople" class="fieldGroup">
          <div class="fieldGroupNameContainer">
            <a href="#tabs-copeople" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-copeople">
              <em class="material-icons" aria-hidden="true">expand_less</em>
              <h2><?php print _txt('ct.co_people.pl'); ?></h2>
            </a>
            <div class="coAddEditButtons">
              <?php
                if($permissions['link']) {
                  foreach($vv_linkable_cos as $lcoid => $lconame) {
                    print $this->Html->link(_txt('op.link.to.co', array($lconame)),
                      array('controller'    => 'co_people',
                        'action'        => 'link',
                        'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                        'co'            => $lcoid,
                        'render'        => 'norm'),
                      array('class'  => 'addbutton'));
  
                  }
                }
              ?>
            </div>
          </div>
          <ul id="tabs-copeople" class="fields data-list">
            <?php
              if(!empty($org_identities[0]['CoOrgIdentityLink'])) {
                foreach($org_identities[0]['CoOrgIdentityLink'] as $o) {
                  print '<li class="field-data-container">';
                  print '<div class="field-data force-wrap">';
                  print $this->Html->link(generateCn($o['CoPerson']['PrimaryName']),
                    array('controller' => 'co_people',
                      'action'     => 'canvas',
                      $o['CoPerson']['id']));
                  print '</div>';
                  print '<div class="field-data data-label">';
                  print $this->element('badgeList',
                    array('vv_badge_list' =>
                      array(
                        array(
                          'order' => $this->Badge->getBadgeOrder('Other'),
                          'text' => filter_var($o['CoPerson']['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS),
                          'color' => $this->Badge->getBadgeColor('Light'),
                        )
                      )
                    )
                  );
                  print '</div>';
                  $action_coil_args = array();
                  $action_coil_args['vv_attr_mdl'] = "CoOrgIdentityLink";
                  $action_coil_args['vv_attr_id'] = $o['id'];

                  // We don't currently offer an unlink button here, that can only (currently)
                  // be done from CO Person Canvas, which places some restrictions on that action.

                  $action_coil_args['vv_actions'][] = array(
                    'order' => $this->Menu->getMenuOrder('View'),
                    'icon' => $this->Menu->getMenuIcon('View'),
                    'url' => $this->Html->url(
                      array(
                        'controller' => 'co_people',
                        'action' => 'canvas',
                        $o['CoPerson']['id'],
                      )
                    ),
                    'label' => _txt('op.view'),
                  );
                  print '<div class="field-actions">';
                  if(!empty($action_coil_args['vv_actions'])) {
                    print $this->element('menuAction', $action_coil_args);
                  }
                  print '</div>';
                  print '</li>';
                }
              }

              if(!empty($vv_co_person_roles['PipelineCoPersonRole']['id'])) {
                $action_pcpr_args = array();
                $action_pcpr_args['vv_attr_mdl'] = "PipelineCoPersonRole";
                $action_pcpr_args['vv_attr_id'] = $vv_co_person_roles['PipelineCoPersonRole']['id'];
                print '<li class="field-data-container">';
                print '<div class="field-data force-wrap">';
                print _txt('in.orgid.pi.role') . "<br />\n";
                print '</div>';
                print '<div class="field-data data-label"></div>';

                $action_pcpr_args['vv_actions'][] = array(
                  'order' => $this->Menu->getMenuOrder('Edit'),
                  'icon' => $this->Menu->getMenuIcon('Edit'),
                  'url' => $this->Html->url(
                    array(
                      'controller' => 'co_person_roles',
                      'action' => 'edit',
                      $vv_co_person_roles['PipelineCoPersonRole']['id'],
                    )
                  ),
                  'label' => _txt('op.edit'),
                );
                print '<div class="field-actions">';
                if(!empty($action_pcpr_args['vv_actions'])) {
                  print $this->element('menuAction', $action_pcpr_args);
                }
                print '</div>';
                print '</li>';
              }

              if(!empty($org_identities[0]['PipelineCoGroupMember'])) {
                foreach($org_identities[0]['PipelineCoGroupMember'] as $g) {
                  $action_pcgm_args = array();
                  $action_pcgm_args['vv_attr_mdl'] = "PipelineCoGroupMember";
                  $action_pcgm_args['vv_attr_id'] = $g['id'];
                  print '<li class="field-data-container">';
                  print '<div class="field-data force-wrap">';
                  print _txt('in.orgid.pi.group', array(filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS))) . "<br />\n";
                  print '</div>';
                  print '<div class="field-data data-label"></div>';

                  $action_pcgm_args['vv_actions'][] = array(
                    'order' => $this->Menu->getMenuOrder('Edit'),
                    'icon' => $this->Menu->getMenuIcon('Edit'),
                    'url' => $this->Html->url(
                      array(
                        'controller' => 'co_group_members',
                        'action' => 'edit',
                        $g['id'],
                      )
                    ),
                    'label' => _txt('op.edit'),
                  );
                  print '<div class="field-actions">';
                  if(!empty($action_pcgm_args['vv_actions'])) {
                    print $this->element('menuAction', $action_pcgm_args);
                  }
                  print '</div>';
                  print '</li>';
                }
              }

            ?>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div> <!-- personExplorer -->
  <?php print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->
