<!--
/**
 * COmanage Registry OrgIdentity Fields
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->

<script>
$(function() {
  // Turn on Tabs
  var $tabs = $( "#tabs" ).tabs();
  
  // If returning to this page via redirect, open last used tab
  <?php if(isset($this->request->params['named']['tab'])): ?>
    var selectedtab = "<?php print($this->request->params['named']['tab']); ?>";
    $tabs.tabs('select', '#tabs-' + selectedtab );
  <?php endif; ?>
});
</script>

<?php

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

  if($e)
  {
    // Populate CO ID if approporiate

    if(!$pool_org_identities)
      echo $this->Form->hidden('OrgIdentity.co_id',
                               array('default' => $cur_co['Co']['id'])). "\n";
  }

  if($this->action != "add") {
    print $this->Html->link(
      _txt('op.history'),
      array(
        'controller' => 'history_records',
        'action' => 'index',
        'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
        'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
      ),
      array('class' => 'historybutton')
    );
  }
?>
<br />
<br />

<?php

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
<div id="<?php print $this->action; ?>_org_identity" style=" float:left; height:auto;">
  <div id="tabs" style="min-height:350px; width:550px">
    <ul>
      <li>
        <a href="#tabs-name">
          <?php print _txt('fd.name.affil'); ?>
        </a>
      </li>

      <?php if($this->action != "add"): ?>
        <li>
          <a href="#tabs-id">
            <?php print _txt('fd.ids'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-email">
            <?php print _txt('fd.email_address.mail'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-phone">
            <?php print _txt('fd.telephone_number.number'); ?>
          </a>
        </li>
        <li>
          <a href="#tabs-address">
            <?php print _txt('fd.address'); ?>
          </a>
        </li>
      <?php endif; ?>
    </ul>

    <div id="tabs-name" class="ui-helper-clearfix">
      <table id="<?php print $this->action; ?>_org_identity" class="ui-widget">
        <tbody>
          <tr class="line1">
            <td>
              <?php
              echo _txt('fd.name.honorific');

              if($e)
                echo " " . _txt('fd.name.h.desc');
              ?>
            </td>
            <td>
              <?php
                echo $this->Form->hidden('Name.id');
                echo $this->Form->hidden('Name.type', array('default' => 'O'));
                echo ($e ? $this->Form->input('Name.honorific') : Sanitize::html($org_identities[0]['Name']['honorific']));
              ?>
             </td>
          </tr>
          <tr class="line2">
            <td>
                <?php echo _txt('fd.name.given'); ?><font class="required">*</font>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('Name.given') : Sanitize::html($org_identities[0]['Name']['given'])); ?>
            </td>
          </tr>
          <tr class="line1">
            <td>
              <?php echo _txt('fd.name.middle'); ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('Name.middle') : Sanitize::html($org_identities[0]['Name']['middle'])); ?>
            </td>
          <tr class="line2">
            <td>
              <?php echo _txt('fd.name.family'); ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('Name.family') : Sanitize::html($org_identities[0]['Name']['family'])); ?>
            </td>
          </tr>
          <tr class="line1">
            <td>
              <?php
                echo _txt('fd.name.suffix');

                if($e)
                  echo " " . _txt('fd.name.s.desc');
              ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('Name.suffix') : Sanitize::html($org_identities[0]['Name']['suffix'])); ?>
            </td>
          </tr>
          <tr class="line2">
            <td>
              <?php echo _txt('fd.affiliation'); ?><font class="required">*</font>
            </td>
            <td>
              <?php
                global $cm_lang, $cm_texts;
                $attrs['value'] = (isset($org_identities[0]['OrgIdentity']['affiliation'])
                                   ? $org_identities[0]['OrgIdentity']['affiliation']
                                   : "M");
                $attrs['empty'] = false;

                if($e) {
                  print $this->Form->select('affiliation',
                                            $cm_texts[ $cm_lang ]['en.affil'],
                                            $attrs);

                  if($this->Form->isFieldError('status')) {
                    print $this->Form->error('status');
                  }
                } else {
                  if(isset($org_identities[0]['OrgIdentity']['affiliation'])) {
                    print $cm_texts[ $cm_lang ]['en.affil'][ $org_identities[0]['OrgIdentity']['affiliation'] ];
                  }
                }
              ?>
            </td>
          </tr>
          <tr class="line1">
            <td>
              <?php echo _txt('fd.title'); ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('title') : Sanitize::html($org_identities[0]['OrgIdentity']['title'])); ?>
            </td>
          </tr>
          <tr class="line2">
            <td>
              <?php echo _txt('fd.o'); ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('o') : Sanitize::html($org_identities[0]['OrgIdentity']['o'])); ?>
            </td>
          </tr>
          <tr class="line1">
            <td>
              <?php echo _txt('fd.ou'); ?>
            </td>
            <td>
              <?php echo ($e ? $this->Form->input('ou') : Sanitize::html($org_identities[0]['OrgIdentity']['ou'])); ?>
            </td>
          </tr>
          <tr>
            <td>
              <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
            </td>
            <td>
              <?php
                if($e)
                {
                  print '<div style="float:left; margin:0 10px 0 0;">';
                    print $this->Form->submit($submit_label);
                  print '</div>';
                  print $this->Html->link(_txt('op.cancel'),
                                          isset($redirect) ? $redirect
                                                           : array('controller' => 'org_identities',
                                                                   'action' => 'index',
                                                                   'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
                                                                  ),
                                          array('class' => 'cancelbutton')
                                         );
                }
              ?>
            </td>
          </tr>
          <?php if(0): ?>
            <?php foreach ($cmp_ef_attribute_order as $f): ?>
            <?php
              // Find this attribute in the CMP enrollment flow configuration
              $a = find_ef_attribute($cmp_ef_attributes['CmpEnrollmentAttribute'], $f['attr'], $f['type']);

              // Skip this one if not found or not permitted
              if(!$a || $a['required'] == RequiredEnum::NotPermitted)
                continue;

              // Convert table:column into usable bits
              if(preg_match('/:/', $f['attr']))
              {
                $attr = preg_split('/:/', $f['attr'], 2);
                $m = Inflector::classify($attr[0]);  // names -> Name

                // Associated models must be referenced as Model.0.field for HABTM,
                // or Model.field for HasOne
                if(isset($f['assoc']) && $f['assoc'] != 'hasone')
                  $d = '.0.';
                else
                  $d = '.';

                $fattr = $m.$d.$attr[1];

                // Create hidden fields for associated models

                if(!isset($emitted[$m]))
                {
                  print $this->Form->hidden($m.$d.'id');
                  print $this->Form->hidden($m.$d.'type', array('default' => $f['type']));

                  $emitted[$m] = true;
                }
              }
              else
              {
                // Set up matching array
                $attr[0] = 'org_identities';
                $attr[1] = $f['attr'];
                $m = Inflector::classify($attr[0]);  // names -> Name
                $fattr = $attr[1];
              }
            ?>
            <tr class="line<?php print ($l % 2); $l++; ?>">
              <td>
                <?php
                  print $f['label'];

                  if($a['required'] == RequiredEnum::Required)
                    print '<font class="required">*</font>';

                  if($e && isset($f['desc']))
                    print " " . $f['desc'];
                ?>
              </td>
              <td>
                <?php
                  if(isset($f['select']))
                  {
                    // Render a select element
                    $attrs['value'] = (isset($org_identities[0][$m][ $attr[1] ])
                                        ? $org_identities[0][$m][ $attr[1] ]
                                        : $f['select']['default']);
                    $attrs['empty'] = false;

                    print ($e ? $this->Form->select($fattr,
                                                    $f['select']['options'],
                                                    $attrs)
                              : Sanitize::html($org_identities[0][$m][ $attr[1] ]));

                    if($this->Form->isFieldError($fattr)) {
                      print $this->Form->error($fattr);
                    }
                  }
                  else
                  {
                    // Use default rendering
                    print ($e ? $this->Form->input($fattr) : Sanitize::html($org_identities[0][$m][ $attr[1] ]));
                  }
                ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if($this->action != "add"): ?>
      <div id="tabs-id" class="additionalinfo">
        <?php
          if($e)
          {
            if(isset($org_identities[0]['Identifier']))
            {
              foreach($org_identities[0]['Identifier'] as $id)
              {
                print '<div>';
                  print '<div>';

                    print $this->Html->link($id['identifier'], array('controller' => 'identifiers',
                                                           'action' => 'edit',
                                                           $id['id'],
                                                           'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])));
                    print "&nbsp;(" . $id['type'] . ")\n";
                  print '</div>';
                  print '<div>';

                    // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                    print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($id['identifier'])) . '\', \'' . $this->Html->url(array('controller' => 'identifiers', 'action' => 'delete', $id['id'], 'co' => ($pool_org_identities ? false : $cur_co['Co']['id']))) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                    print $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'identifiers',
                                            'action' => 'edit',
                                            $id['id'],
                                            'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                                      array('class' => 'editbutton')) . "\n";
                  print '</div>';
                print '</div>';
              }
            }

            $linktarget = array('controller'    => 'identifiers',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                                'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
          else
          {
            if(isset($org_identities[0]['Identifier']))
              foreach($org_identities[0]['Identifier'] as $id)
              {
                print '<div>';
                  print Sanitize::html($id['identifier']) . " (" . $id['type'] . ")<br />\n";
                print '</div>';
              }
          }
        ?>
        <br>
      </div>

      <div id="tabs-email"class="additionalinfo">
        <?php
          if($e)
          {
            if(isset($org_identities[0]['EmailAddress']))
            {
              foreach($org_identities[0]['EmailAddress'] as $ea)
              {
                print '<div>';
                  print '<div>';

                    print $this->Html->link($ea['mail'], array('controller' => 'email_addresses',
                                                               'action' => 'edit',
                                                               $ea['id'],
                                                               'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])));
                    print "&nbsp;(" . _txt('en.contact.mail', null, $ea['type']) . ", "
                      . ($ea['verified'] ? _txt('fd.email_address.verified') : _txt('fd.email_address.unverified')) . ")";
                  print '</div>';
                  print '<div>';
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($ea['mail'])) . '\', \'' . $this->Html->url(array('controller' => 'email_addresses', 'action' => 'delete', $ea['id'], 'co' => ($pool_org_identities ? false : $cur_co['Co']['id']))) . '\')";>' . _txt('op.delete') .'</a>' . "\n";
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'email_addresses',
                                                'action' => 'edit',
                                                $ea['id'],
                                                'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                                          array('class' => 'editbutton')) . "\n";
                  print '</div>';
                print '</div>';
              }
            }

            $linktarget = array('controller'    => 'email_addresses',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                                'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
          else
          {
            if(isset($org_identities[0]['EmailAddress']))
              foreach($org_identities[0]['EmailAddress'] as $ea)
              {
                print '<div>';
                  print Sanitize::html($ea['mail']) . " (" . _txt('en.contact', null, $ea['type']) . ")<br />\n";
                print '</div>';
              }

          }
        ?>
        <br>
      </div>

      <div id="tabs-phone" class="additionalinfo">
        <?php
          if($e)
          {
            if(isset($org_identities[0]['TelephoneNumber']))
            {
              foreach($org_identities[0]['TelephoneNumber'] as $t)
              {
                print '<div>';
                  print '<div>';
                    print $this->Html->link($t['number'], array('controller' => 'telephone_numbers',
                                                      'action' => 'edit',
                                                      $t['id'],
                                                      'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])));
                    print "&nbsp;(" . _txt('en.contact', null, $t['type']) . ")<br />\n";
                  print '</div>';
                  print '<div>';
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($t['number'])) . '\', \'' . $this->Html->url(array('controller' => 'telephone_numbers', 'action' => 'delete', $t['id'], 'co' => ($pool_org_identities ? false : $cur_co['Co']['id']))) . '\')";>' . _txt('op.delete') .'</a>' . "\n";

                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'telephone_numbers',
                                                'action' => 'edit',
                                                $t['id'],
                                                'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                                          array('class' => 'editbutton')) . "\n";
                  print '</div>';
                print '</div>';
              }
            }

            $linktarget = array('controller'    => 'telephone_numbers',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                                'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
          else
          {
            if(isset($org_identities[0]['TelephoneNumber']))
              foreach($org_identities[0]['TelephoneNumber'] as $t)
              {
                print '<div>';
                  print Sanitize::html($t['number']) . " (" . _txt('en.contact', null, $t['type']) . ")<br />\n";
                print '</div>';
              }
          }
        ?>
        <br>
      </div>

      <div id="tabs-address" class="additionalinfo">
        <?php
          if($e)
          {
            if(isset($org_identities[0]['Address']))
            {
              foreach($org_identities[0]['Address'] as $addr)
              {
                print '<div>';
                  print '<div>';
                    print $this->Html->link($addr['line1'], array('controller' => 'addresses',
                                                                  'action'     => 'edit',
                                                                  $addr['id'],
                                                                  'co'         => ($pool_org_identities ? false : $cur_co['Co']['id'])));
                    print "&nbsp;(" . _txt('en.contact', null, $addr['type']) . ")<br />\n";
                  print '</div>';
                  print '<div>';
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete') .'" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($addr['line1'])) . '\', \'' . $this->Html->url(array('controller' => 'addresses', 'action' => 'delete', $addr['id'], 'co' => ($pool_org_identities ? false : $cur_co['Co']['id']))) . '\')";>' . _txt('op.delete') .'</a>' . "\n";

                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'addresses',
                                                'action'     => 'edit',
                                                $addr['id'],
                                                'co'         => ($pool_org_identities ? false : $cur_co['Co']['id'])),
                                          array('class' => 'editbutton')) . "\n";
                  print '</div>';
                print '</div>';
              }
            }

            $linktarget = array('controller'    => 'addresses',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                                'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
          else
          {
            if(isset($org_identities[0]['Address']))
            {
              foreach($org_identities[0]['Address'] as $addr)
              {
                print '<div>';
                  print Sanitize::html($addr['line1']) . " (" . _txt('en.contact', null, $addr['type']) . ")\n";
                print '</div>';
              }
            }
          }
        ?>
        <br>
      </div>
    <?php endif; //will not show up if adding ?>
  </div>
</div>
