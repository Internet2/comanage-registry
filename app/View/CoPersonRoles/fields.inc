<?php
/**
 * COmanage Registry CO Person Role Fields
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
  $es = false;

  if(($this->action == "add" && $permissions['add'])
    || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  if($this->action == "edit" && $permissions['editself'])
    $es = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return(false);

  if($e)
  {
    // This is for beforeFilter
    print $this->Form->hidden('Co.id', array('default' => $cur_co['Co']['id'])). "\n";
    // And this is to link to the co person
    print $this->Form->hidden('co_person_id', array('default' => $co_people[0]['CoPerson']['id'])). "\n";
    // Make sure ID gets carried over
    if(isset($co_person_roles[0]['CoPersonRole']['id']))
      print $this->Form->hidden('id', array('default' => $co_person_roles[0]['CoPersonRole']['id']));
  }

  // Line number, for rendering
  $l = 1;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'controller' => 'co_people',
    'action' => 'canvas',
    $co_people[0]['CoPerson']['id']);
  $this->Html->addCrumb(generateCn($co_people[0]['PrimaryName']), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_person_roles.1')));
  $this->Html->addCrumb($crumbTxt);

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

  <div id="<?php print $this->action; ?>_role_attributes" class="explorerContainer">
    <div id="role-attributes" class="personExplorer">
      <ul>

        <li id="fields-role" class="fieldGroup">

          <a href="#tabs-role" class="fieldGroupName">
            <em class="material-icons">indeterminate_check_box</em>
            <?php print _txt('fd.attrs.copr'); ?>
          </a>

          <ul id="<?php print $this->action; ?>_co_person_role" class="fields form-list">
            <?php if(!empty($co_person_roles[0]['CoPersonRole']['source_org_identity_id'])): ?>
              <li>
                <div class="field-name">
                  <?php print _txt('fd.source'); ?>
                </div>
                <div class="field-info">
                  <?php
                    $args = array(
                      'controller' => 'org_identities',
                      'action'     => 'view',
                      $co_person_roles[0]['CoPersonRole']['source_org_identity_id']
                    );

                    print $this->Html->link(_txt('ct.org_identities.1'), $args);
                  ?>
                </div>
              </li>
            <?php endif; ?>
            <?php if(!empty($permissions['cous'])): ?>
              <li>
                <div class="field-name">
                  <?php
                    $label = _txt('fd.cou-a', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));

                    if($e && !$es) {
                      print $this->Form->label('cou_id', $label);
                      print ' <span class="required">*</span>';
                    } else {
                      print $label;
                    }
                  ?>
                </div>
                <div class="field-info">
                  <?php
                    $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['cou_id'])
                      ? $co_person_roles[0]['CoPersonRole']['cou_id']
                      : 0);
                    $attrs['empty'] = false;

                    if($e && !$es) {
                      print $this->Form->select('cou_id',
                        $permissions['cous'],
                        $attrs);

                      if($this->Form->isFieldError('cou_id')) {
                        print $this->Form->error('cou_id');
                      }
                    } else {
                      print filter_var($co_person_roles[0]['Cou']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                  ?>
                </div>
              </li>
            <?php endif; ?>
            <li>
              <div class="field-name">
                <?php
                  $label = _txt('fd.affil-a', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));

                  if($e && !$es) {
                    print $this->Form->label('affiliation', $label);
                    print ' <span class="required">*</span>';
                  } else {
                    print $label;
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                  global $cm_lang, $cm_texts;
                  $attrs = array();
                  $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['affiliation'])
                    ? $co_person_roles[0]['CoPersonRole']['affiliation']
                    : AffiliationEnum::Member);
                  $attrs['empty'] = false;

                  if($e && !$es) {
                    print $this->Form->select('affiliation',
                      $vv_copr_affiliation_types,
                      $attrs);

                    if($this->Form->isFieldError('affiliation')) {
                      print $this->Form->error('affiliation');
                    }
                  } elseif(!empty($co_person_roles[0]['CoPersonRole']['affiliation'])) {
                    print $vv_copr_affiliation_types[ $co_person_roles[0]['CoPersonRole']['affiliation'] ];
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name vtop">
                <?php
                  if($e && !$es) {
                    print $this->Form->label('status', _txt('fd.status'));
                    print '<span class="required">*</span>';
                  } else {
                    print _txt('fd.status');
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                global $cm_lang, $cm_texts;
                $attrs = array();
                $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['status'])
                  ? $co_person_roles[0]['CoPersonRole']['status']
                  : StatusEnum::Active);
                $attrs['empty'] = false;

                if($e && !$es) {
                  print $this->Form->select('status',
                    $cm_texts[ $cm_lang ]['en.status'],
                    $attrs);

                  if($this->Form->isFieldError('status')) {
                    print $this->Form->error('status');
                  }
                } else {
                  if(!empty($co_person_roles[0]['CoPersonRole']['status'])) {
                    print _txt('en.status', null, $co_person_roles[0]['CoPersonRole']['status']);
                  }
                }
                ?>
                <?php if($e && !$es
                  && !empty($co_person_roles[0]['CoPersonRole']['status'])
                  && ($co_person_roles[0]['CoPersonRole']['status'] == StatusEnum::PendingApproval
                    || $co_person_roles[0]['CoPersonRole']['status'] == StatusEnum::PendingConfirmation)): ?>
                  <div class="field-desc">
                    <span class="ui-icon ui-icon-info co-info"></span>
                    <?php print _txt('fd.status.change'); ?>
                  </div>
                <?php endif; ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php
                  if ($e && !$es) {
                    print $this->Form->label('ordr', _txt('fd.order'));
                  } else {
                    print _txt('fd.order');
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    // XXX not sure why model introspection isn't picking up the type...
                    print $this->Form->input('ordr', array('type' => 'numeric'));
                  } else {
                    print filter_var($co_person_roles[0]['CoPersonRole']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php
                  $label = _txt('fd.title-a', array(filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS)));

                  if($e && !$es) {
                    print $this->Form->label('title', $label);
                  } else {
                    print $label;
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    if(!empty($vv_enums_title)) {
                      // Attribute enumerations defined

                      if(!empty($co_person_roles[0]['CoPersonRole']['title'])
                        && !array_search($co_person_roles[0]['CoPersonRole']['title'], $vv_enums_title)) {
                        print filter_var($co_person_roles[0]['CoPersonRole']['title'],FILTER_SANITIZE_SPECIAL_CHARS);

                        print '<div class="field-desc">
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
                      print $this->Form->input('title', array('size' => 60, 'class' => 'focusFirst'));
                    }
                  } else {
                    print filter_var($co_person_roles[0]['CoPersonRole']['title'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php
                  if ($e && !$es) {
                    print $this->Form->label('o', _txt('fd.o'));
                  } else {
                    print _txt('fd.o');
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    if(!empty($vv_enums_o)) {
                      // Attribute enumerations defined

                      if(!empty($co_person_roles[0]['CoPersonRole']['o'])
                        && !array_search($co_person_roles[0]['CoPersonRole']['o'], $vv_enums_o)) {
                        print filter_var($co_person_roles[0]['CoPersonRole']['o'],FILTER_SANITIZE_SPECIAL_CHARS);

                        print '<div class="field-desc">
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
                      $args = array(
                        'size' => 60
                      );

                      if($this->action == 'add') {
                        $args['default'] = filter_var($cur_co['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                      }

                      print $this->Form->input('o', $args);
                    }
                  } else {
                    print filter_var($co_person_roles[0]['CoPersonRole']['o'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php
                  if ($e && !$es) {
                    print $this->Form->label('ou', _txt('fd.ou'));
                  } else {
                    print _txt('fd.ou');
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    if(!empty($vv_enums_ou)) {
                      // Attribute enumerations defined

                      if(!empty($co_person_roles[0]['CoPersonRole']['ou'])
                         && !array_search($co_person_roles[0]['CoPersonRole']['ou'], $vv_enums_ou)) {
                        print filter_var($co_person_roles[0]['CoPersonRole']['ou'],FILTER_SANITIZE_SPECIAL_CHARS);

                        print '<div class="field-desc">
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
                    print filter_var($co_person_roles[0]['CoPersonRole']['ou'],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                ?>
              </div>
            </li>
            <li>
              <div class="field-name">
                <?php
                  if($e && !$es) {
                    print $this->Form->label('sponsor_co_person_id', _txt('fd.sponsor') . " " . _txt('fd.sponsor.desc'));
                  } else {
                    print _txt('fd.sponsor') . " " . _txt('fd.sponsor.desc');
                  }
                ?>
              </div>
              <div class="field-info">
                <?php
                if(!empty($vv_sponsors)) {
                  if(!empty($co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'])
                    && !isset($vv_sponsors[ $co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'] ])) {
                    if(!empty($co_person_roles[0]['SponsorCoPerson']['PrimaryName'])) {
                      print $this->Html->link(generateCn($co_person_roles[0]['SponsorCoPerson']['PrimaryName']),
                        array('controller' => 'co_people',
                          'action' => 'canvas',
                          $co_person_roles[0]['SponsorCoPerson']['id']));
                    }

                    print '<div class="field-desc">
                              <span class="ui-icon ui-icon-info co-info"></span>
                              <em>' . _txt('fd.sponsor.inel') . '</em>
                            </div>';
                  }

                  if($e && !$es) {
                    print $this->Form->select('sponsor_co_person_id', $vv_sponsors);
                  } elseif(!empty($co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'])) {
                    print filter_var($vv_sponsors[ $co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
                  }
                }
                ?>
              </div>
            </li>
            <li class="modelbox-data">
              <div class="field-name">
                <?php
                  if($e && !$es) {
                    print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
                  } else {
                    print _txt('fd.valid_from');
                  }
                ?><br />
                <span class="field-descr"><?php print _txt('fd.valid_from.desc'); ?></span>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    $args = array(
                      'class' => 'datepicker-f'
                    );

                    if(!empty($co_person_roles[0]['CoPersonRole']['valid_from'])) {
                      if(!empty($vv_tz)) {
                        // We need to adjust the UTC value to the user's local time
                        $args['value'] = $this->Time->format($co_person_roles[0]['CoPersonRole']['valid_from'], "%F %T", false, $vv_tz);
                      } else {
                        $args['value'] = $co_person_roles[0]['CoPersonRole']['valid_from'];
                      }
                    }

                    print $this->Form->text('valid_from', $args);
                  } else {
                    if(!empty($co_person_roles[0]['CoPersonRole']['valid_from'])) {
                      print $this->Time->format($co_person_roles[0]['CoPersonRole']['valid_from'], "%c $vv_tz", false, $vv_tz);
                    }
                  }
                ?>
              </div>
            </li>
            <li class="modelbox-data">
              <div class="field-name">
                <?php
                  if($e && !$es) {
                    print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
                  } else {
                    print _txt('fd.valid_through');
                  }
                ?><br />
                <span class="field-descr"><?php print _txt('fd.valid_through.desc'); ?></span>
              </div>
              <div class="field-info">
                <?php
                  if($e && !$es) {
                    $args = array(
                      'class' => 'datepicker-u'
                    );

                    if(!empty($co_person_roles[0]['CoPersonRole']['valid_through'])) {
                      if(!empty($vv_tz)) {
                        // We need to adjust the UTC value to the user's local time
                        $args['value'] = $this->Time->format($co_person_roles[0]['CoPersonRole']['valid_through'], "%F %T", false, $vv_tz);
                      } else {
                        $args['value'] = $co_person_roles[0]['CoPersonRole']['valid_through'];
                      }
                    }

                    print $this->Form->text('valid_through', $args);
                  } else {
                    if(!empty($co_person_roles[0]['CoPersonRole']['valid_through'])) {
                      print $this->Time->format($co_person_roles[0]['CoPersonRole']['valid_through'], "%c $vv_tz", false, $vv_tz);
                    }
                  }
                ?>
              </div>
            </li>
            <?php
            // Check for extended attributes and render
            if(!empty($vv_extended_attributes)) {

              $cl = 'Co' . $cur_co['Co']['id'] . 'PersonExtendedAttribute';
              print '<li class="hidden">' .  $this->Form->hidden($cl . '.id') . '</li>';

              foreach($vv_extended_attributes as $c) {
                $attr = strtolower($c['CoExtendedAttribute']['name']);
                ?>
                <li class="modelbox-data">
                  <div class="field-name">
                    <?php
                      if($e && !$es) {
                        print $this->Form->label($cl.'.'.$attr,
                          filter_var($c['CoExtendedAttribute']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS));
                      } else {
                        print filter_var($c['CoExtendedAttribute']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS);
                      }
                    ?>
                  </div>
                  <div class="field-info">
                    <?php
                      if($c['CoExtendedAttribute']['type'] == 'TIMESTAMP') {
                        print ($e && !$es ? $this->Form->text($cl.'.'.$attr, array('class' => 'datepicker'))
                          : (($co_person_roles[0][$cl][$attr] > 0) ? strftime("%F", strtotime($co_person_roles[0][$cl][$attr])) : ""));
                      } else {
                        print ($e && !$es ? $this->Form->input($cl.'.'.$attr,
                          isset($co_person_roles[0][$cl][$attr]) ? array('default' => $co_person_roles[0][$cl][$attr]) : array())
                          : filter_var($co_person_roles[0][$cl][$attr],FILTER_SANITIZE_SPECIAL_CHARS));
                      }
                    ?>
                  </div>
                </li>
                <?php
              } // foreach
            } // endif extended attributes
            ?>
            <?php if($e && !$es): ?>
              <li class="fields-submit">
                <div class="field-name">
                  <span class="required"><?php print _txt('fd.req'); ?></span>
                </div>
                <div class="field-info">
                  <?php print $this->Form->submit($submit_label); ?>
                </div>
              </li>
            <?php endif; ?>
          </ul><!-- tabs-role -->
        </li><!-- fields-role -->

        <?php
          if($this->action != "add") {
            $args = array(
              'edit'         => $e,
              'self_service' => true,
              'model'        => 'CoPersonRole',
              'model_param'  => 'copersonroleid',
              'mvpa_model'   => 'TelephoneNumber',
              'mvpa_field'   => null,
              'mvpa_format'  => 'formatTelephone'
            );
            
            print $this->element('mvpa', $args);
            
            $args['mvpa_model'] = 'Address';
            $args['mvpa_format'] = 'formatAddress';
            
            print $this->element('mvpa', $args);
          }
        ?>
      </ul>
    </div>
  </div>

<?php
  print $this->element("changelog");
