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
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identities';
  $args['action'] = 'index';
  if(!$pool_org_identities) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
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
    // Explorer menu toggles
    $(".fieldGroupName").click(function(event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "indeterminate_check_box") {
        $(this).find(".material-icons").text("add_box");
      } else {
        $(this).find(".material-icons").text("indeterminate_check_box");
      }
    });

  });
</script>

<?php if($this->action == 'view' && !empty($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('op.orgid.edit.ois', array($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['description'])); ?>
  </div>
<?php endif; // view ?>

<?php if($this->action == 'edit'): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('in.orgidentities'); ?>
  </div>
<?php endif; // edit ?>

<div id="<?php print $this->action; ?>_org_identity" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>

      <!-- Names -->
      <li id="fields-name" class="fieldGroup">

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

        <a href="#tabs-name" class="fieldGroupName">
          <em class="material-icons">indeterminate_check_box</em>
          <?php print _txt('fd.name.affil'); ?>
        </a>

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
                <li class="field-data-container">
                  <div class="field-data">
                    <?php
                      print $this->Html->link(generateCn($n),
                          array('controller' => 'names',
                            'action' => ($e ? 'edit' : 'view'),
                            $n['id']))
                        . " ("
                        . ($n['primary_name'] ? (_txt('fd.name.primary_name') . ", ") : "")
                        . _txt('en.name.type', null, $n['type'])
                        . (!empty($n['language']) ? (", " . _txt('en.language', null, $n['language'])) : "")
                        . ")";
                    ?>
                  </div>
                  <div class="field-actions">
                    <?php
                      if($e) {
                        if(!$n['primary_name']) {
                          print '<a class="deletebutton" title="' . _txt('op.delete')
                            . '" onclick="javascript:js_confirm_generic(\''
                            . _txt('js.remove') . '\',\''    // dialog body text
                            . $this->Html->url(              // dialog confirm URL
                              array(
                                'controller' => 'names',
                                'action' => 'delete',
                                $n['id'],
                                'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
                              )
                            ) . '\',\''
                            . _txt('op.remove') . '\',\''    // dialog confirm button
                            . _txt('op.cancel') . '\',\''    // dialog cancel button
                            . _txt('op.remove') . '\',[\''   // dialog title
                            . filter_var(_jtxt(generateCn($n)),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                            . '\']);">'
                            . _txt('op.delete')
                            . '</a>';

                          print $this->Html->link(_txt('op.primary'),
                              array('controller'    => 'names',
                                'action'        => 'primary',
                                $n['id'],
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id']),
                              array('class' => 'primarybutton')) . "\n";
                        }

                        print $this->Html->link(_txt('op.edit'),
                            array('controller' => 'names',
                              'action' => 'edit',
                              $n['id']),
                            array('class' => 'editbutton')) . "\n";
                      } else {
                        print $this->Html->link(_txt('op.view'),
                            array('controller' => 'names',
                              'action' => 'view',
                              $n['id']),
                            array('class' => 'viewbutton')) . "\n";
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
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
            </div>
            <div class="field-info">
              <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($org_identities[0]['OrgIdentity']['affiliation'])
                  ? $org_identities[0]['OrgIdentity']['affiliation']
                  : "M");
                $attrs['empty'] = true;

                if($e) {
                  print $this->Form->select('affiliation',
                    $cm_texts[ $cm_lang ]['en.org_identity.affiliation'],
                    $attrs);

                  if($this->Form->isFieldError('affiliation')) {
                    print $this->Form->error('affiliation');
                  }
                } else {
                  if(!empty($org_identities[0]['OrgIdentity']['affiliation'])) {
                    print $cm_texts[ $cm_lang ]['en.org_identity.affiliation'][ $org_identities[0]['OrgIdentity']['affiliation'] ];
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
                if($e) {
                  if(!empty($vv_enums_title)) {
                    // Attribute enumerations defined

                    if(!empty($org_identities[0]['OrgIdentity']['title'])
                       && !array_search($org_identities[0]['OrgIdentity']['title'], $vv_enums_title)) {
                      print filter_var($org_identities[0]['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS);
                      
                      print '<div>
                               <span class="ui-icon ui-icon-info co-info"></span>
                               <em>' . _txt('er.ae.val.inv') . '</em>
                             </div>';
                    }

                    $attrs = array();
                    $attrs['empty'] = true;

                    print $this->Form->select('title', $vv_enums_title, $attrs);

                    if($this->Form->isFieldError('title')) {
                      print $this->Form->error('title');
                    }
                  } else {
                    // No attribute enumerations defined
                    print $this->Form->input('title', array('size' => 60));
                  }
                } else {
                  print filter_var($org_identities[0]['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('o', _txt('fd.o')) : _txt('fd.o')); ?>
            </div>
            <div class="field-info">
              <?php
                if($e) {
                  if(!empty($vv_enums_o)) {
                    // Attribute enumerations defined

                    if(!empty($org_identities[0]['OrgIdentity']['o'])
                      && !array_search($org_identities[0]['OrgIdentity']['o'], $vv_enums_o)) {
                      print filter_var($org_identities[0]['OrgIdentity']['o'],FILTER_SANITIZE_SPECIAL_CHARS);
                      
                      print '<div>
                         <span class="ui-icon ui-icon-info co-info"></span>
                         <em>' . _txt('er.ae.val.inv') . '</em>
                       </div>';
                    }

                    $attrs = array();
                    $attrs['empty'] = true;

                    print $this->Form->select('o', $vv_enums_o, $attrs);

                    if($this->Form->isFieldError('o')) {
                      print $this->Form->error('o');
                    }
                  } else {
                    // No attribute enumerations defined
                    print $this->Form->input('o', array('size' => 60));
                  }
                } else {
                  print filter_var($org_identities[0]['OrgIdentity']['o'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </div>
          </li>
          <li>
            <div class="field-name">
              <?php print ($e ? $this->Form->label('ou', _txt('fd.ou')) : _txt('fd.ou')); ?>
            </div>
            <div class="field-info">
              <?php
                if($e) {
                  if(!empty($vv_enums_ou)) {
                    // Attribute enumerations defined

                    if(!empty($org_identities[0]['OrgIdentity']['ou'])
                      && !array_search($org_identities[0]['OrgIdentity']['ou'], $vv_enums_ou)) {
                      print filter_var($org_identities[0]['OrgIdentity']['ou'],FILTER_SANITIZE_SPECIAL_CHARS);
                      
                      print '<div>
                         <span class="ui-icon ui-icon-info co-info"></span>
                         <em>' . _txt('er.ae.val.inv') . '</em>
                       </div>';
                    }

                    $attrs = array();
                    $attrs['empty'] = true;

                    print $this->Form->select('ou', $vv_enums_ou, $attrs);

                    if($this->Form->isFieldError('ou')) {
                      print $this->Form->error('ou');
                    }
                  } else {
                    // No attribute enumerations defined
                    print $this->Form->input('ou', array('size' => 60));
                  }
                } else {
                  print filter_var($org_identities[0]['OrgIdentity']['ou'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
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
                <?php print _txt('fd.valid_from.desc'); ?>
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
        }
      ?>

      <?php if($this->action != "add"): ?>       
        <!-- Associated CoPeople listings -->
        <li id="fields-copeople" class="fieldGroup">
          <?php if($e): ?>
            <div class="coAddEditButtons">
              <?php
                if($permissions['link']) {
                  foreach($vv_linkable_cos as $lcoid => $lconame) {
                    print $this->Html->link(_txt('op.link.to.co', array($lconame)),
                      array('controller'    => 'co_people',
                        'action'        => 'link',
                        'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                        'co'            => $lcoid),
                      array('class'  => 'addbutton'));

                  }
                }
              ?>
            </div>
          <?php endif; ?>
          <a href="#tabs-copeople" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('ct.co_people.pl'); ?>
          </a>
          <ul id="tabs-copeople" class="fields data-list">
            <?php
              if(!empty($org_identities[0]['CoOrgIdentityLink'])) {
                foreach($org_identities[0]['CoOrgIdentityLink'] as $o) {
                  print '<li class="field-data-container">';
                  print '<div class="field-data">';
                  print $this->Html->link(generateCn($o['CoPerson']['PrimaryName']),
                    array('controller' => 'co_people',
                      'action'     => 'canvas',
                      $o['CoPerson']['id']));

                  print "&nbsp;(" . filter_var($o['CoPerson']['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS) . ")<br />\n";
                  print '</div>';
                  print '<div class="field-actions">';

                  // We don't currently offer an unlink button here, that can only (currently)
                  // be done from CO Person Canvas, which places some restrictions on that action.

                  print $this->Html->link(_txt('op.view'),
                      array('controller' => 'co_people',
                        'action'     => 'canvas',
                        $o['CoPerson']['id']),
                      array('class' => 'editbutton')) . "\n";
                  print '</div>';
                  print '</li>';
                }
              }

              if(!empty($vv_co_person_roles['PipelineCoPersonRole']['id'])) {
                print '<li class="field-data-container">';
                print '<div class="field-data">';
                print _txt('in.orgid.pi.role') . "<br />\n";
                print '</div>';
                print '<div class="field-actions">';

                print $this->Html->link(_txt('op.view'),
                    array('controller' => 'co_person_roles',
                      'action'     => 'edit',
                      $vv_co_person_roles['PipelineCoPersonRole']['id']),
                    array('class' => 'editbutton')) . "\n";
                print '</div>';
                print '</li>';
              }

              if(!empty($org_identities[0]['PipelineCoGroupMember'])) {
                foreach($org_identities[0]['PipelineCoGroupMember'] as $g) {
                  print '<li class="field-data-container">';
                  print '<div class="field-data">';
                  print _txt('in.orgid.pi.group', array(filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS))) . "<br />\n";
                  print '</div>';
                  print '<div class="field-actions">';

                  print $this->Html->link(_txt('op.view'),
                      array('controller' => 'co_group_members',
                        'action'     => 'edit',
                        $g['id']),
                      array('class' => 'editbutton')) . "\n";
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
