<?php
/**
 * COmanage Registry OrgIdentity Fields
 *
 * Copyright (C) 2011-17 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-17 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>

<script>
$(function() {
  // Enumeration for tabs
  var tabid = {};
  tabid.name = 0;
  tabid.id = 1;
  tabid.email = 2;
  tabid.phone = 3;
  tabid.address  = 4;
  tabid.copeople = 5;

  // Turn on Tabs
  var $tabs = $( "#tabs" ).tabs();
  
  // If returning to this page via redirect, open last used tab
  <?php if(isset($this->request->params['named']['tab'])): ?>
    var selectedtab = "<?php print($this->request->params['named']['tab']); ?>";

    $tabs.tabs('option', 'active', tabid[selectedtab] );
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
<?php if($this->action == 'view' && !empty($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['id'])): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php
      print _txt('op.orgid.edit.ois', array($org_identities[0]['OrgIdentitySourceRecord']['OrgIdentitySource']['description']));
    ?></strong>
  </p>
</div>
<br />
<?php endif; // view ?>

<div id="<?php print $this->action; ?>_org_identity" class="coTabsContainer">
  <div id="tabs" class="coTabs">
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
        <li>
          <a href="#tabs-copeople">
            <?php print _txt('ct.co_people.pl'); ?>
          </a>
        </li>
      <?php endif; ?>
    </ul>

    <div id="tabs-name" class="ui-helper-clearfix additionalinfo">
      <table id="<?php print $this->action; ?>_org_identity_names" class="ui-widget">
        <tbody>
          <?php $l = 1; ?>
          <?php if($this->action == 'add'): ?>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('PrimaryName.honorific', _txt('fd.name.honorific') .  " " . _txt('fd.name.h.desc')) : _txt('fd.name.honorific')); ?>
            </td>
            <td>
              <?php
                print $this->Form->hidden('PrimaryName.id');
                print $this->Form->hidden('PrimaryName.type', array('default' => NameEnum::Official));
                print $this->Form->hidden('PrimaryName.primary_name', array('default' => true));
                print ($e ? $this->Form->input('PrimaryName.honorific') : filter_var($org_identities[0]['PrimaryName']['honorific'],FILTER_SANITIZE_SPECIAL_CHARS));
              ?>
             </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
                <?php print ($e ? $this->Form->label('PrimaryName.given', _txt('fd.name.given')) : _txt('fd.name.given')); ?><span class="required">*</span>
            </td>
            <td>
              <?php print ($e ? $this->Form->input('PrimaryName.given') : filter_var($org_identities[0]['PrimaryName']['given'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('PrimaryName.middle', _txt('fd.name.middle')) : _txt('fd.name.middle')); ?>
            </td>
            <td>
              <?php print ($e ? $this->Form->input('PrimaryName.middle') : filter_var($org_identities[0]['PrimaryName']['middle'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </td>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('PrimaryName.family', _txt('fd.name.family')) :  _txt('fd.name.family')); ?>
            </td>
            <td>
              <?php print ($e ? $this->Form->input('PrimaryName.family') : filter_var($org_identities[0]['PrimaryName']['family'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('PrimaryName.suffix', _txt('fd.name.suffix') . " " . _txt('fd.name.s.desc')) :  _txt('fd.name.suffix')); ?>
            </td>
            <td>
              <?php print ($e ? $this->Form->input('PrimaryName.suffix') : filter_var($org_identities[0]['PrimaryName']['suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print _txt('fd.type'); ?>
            </td>
            <td>
              <?php print _txt('en.name.type', null, NameEnum::Official); ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('PrimaryName.language', _txt('fd.language')) : _txt('fd.language')); ?>
            </td>
            <td>
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
            </td>
          </tr>
          <?php else: // add ?>
          <?php if($e): ?>
          <tr class="line1 noborder">
            <td>
              <?php print _txt('fd.name'); ?>
            </td>
            <td>
              <?php
                $linktarget = array('controller'    => 'names',
                                    'action'        => 'add',
                                    'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
                                    'co'            => ($pool_org_identities ? false : $cur_co['Co']['id']));
                $linkparams = array('class'  => 'addbutton');

                print $this->Html->link(_txt('op.add'),
                                        $linktarget,
                                        $linkparams);
              ?>
            </td>
          </tr>
          <?php endif; // e ?>
          <?php foreach($org_identities[0]['Name'] as $n): ?>
          <tr class="noborder">
            <td>
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
            </td>
            <td>
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
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; // add / edit ?>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
            </td>
            <td>
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
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('affiliation', _txt('fd.affiliation')) : _txt('fd.affiliation')); ?>
            </td>
            <td>
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
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('title', _txt('fd.title')) : _txt('fd.title')); ?>
            </td>
            <td>
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
                    print $this->Form->input('title', array('size' => 60, 'class' => 'focusFirst'));
                  }
                } else {
                  print filter_var($org_identities[0]['OrgIdentity']['title'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('o', _txt('fd.o')) : _txt('fd.o')); ?>
            </td>
            <td>
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
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php print ($e ? $this->Form->label('ou', _txt('fd.ou')) : _txt('fd.ou')); ?>
            </td>
            <td>
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
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php
                if($e) {
                  print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
                } else {
                  print _txt('fd.valid_from');
                }
              ?><br />
              <span class="descr"><?php print _txt('fd.valid_from.desc'); ?></span>
            </td>
            <td>
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
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <td>
              <?php
                if($e) {
                  print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
                } else {
                  print _txt('fd.valid_through');
                }
              ?><br />
              <span class="descr"><?php print _txt('fd.valid_from.desc'); ?></span>
            </td>
            <td>
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
            </td>
          </tr> 
          <tr>
            <td>
              <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
            </td>
            <td>
              <?php
                if($e)
                {
                  print '<div class="submitButton">';
                    print $this->Form->submit($submit_label);
                  print '</div>';
                  print $this->Form->button(_txt('op.reset'), 
                                            array('type'=>'reset'));
                }
              ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php if($this->action != "add"): ?>
      <div id="tabs-id" class="additionalinfo">
        <?php
          if(isset($org_identities[0]['Identifier'])) {
            foreach($org_identities[0]['Identifier'] as $id) {
              print '<div>';
                print '<div>';
                  print $this->Html->link($id['identifier'],
                                          array('controller' => 'identifiers',
                                                'action' => ($e ? 'edit' : 'view'),
                                                $id['id']));
                  print "&nbsp;(" . $id['type'] . ")\n";
                print '</div>';
                print '<div>';
                
                if(isset($id['login']) && $id['login']) {
                  print $this->Html->link(_txt('ct.authentication_events.pl'),
                                          array('controller' => 'authentication_events',
                                                'action' => 'index',
                                                'identifier' => $id['identifier']),
                                          array('class' => 'notebutton'));
                }
                
                if($e) {
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'identifiers',
                        'action' => 'delete',
                        $id['id'],
                        'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($id['identifier']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</a>';
                  print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'identifiers',
                                          'action' => 'edit',
                                          $id['id']),
                                    array('class' => 'editbutton')) . "\n";
                } else {
                  print $this->Html->link(_txt('op.view'),
                                    array('controller' => 'identifiers',
                                          'action' => 'view',
                                          $id['id']),
                                    array('class' => 'viewbutton')) . "\n";
                }
                
                print '</div>';
              print '</div>';
            }
          }
          
          if($e) {
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
        ?>
        <br>
      </div>

      <div id="tabs-email"class="additionalinfo">
        <?php
          if(isset($org_identities[0]['EmailAddress'])) {
            foreach($org_identities[0]['EmailAddress'] as $ea) {
              print '<div>';
                print '<div>';
                  print $this->Html->link($ea['mail'], array('controller' => 'email_addresses',
                                                             'action' => ($e ? 'edit' : 'view'),
                                                             $ea['id']));
                  print "&nbsp;(" . _txt('en.email_address.type', null, $ea['type']) . ", "
                    . ($ea['verified'] ? _txt('fd.email_address.verified') : _txt('fd.email_address.unverified')) . ")";
                print '</div>';
                print '<div>';
                
                if($e) {
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'email_addresses',
                        'action' => 'delete',
                        $ea['id']
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($ea['mail']),FILTER_SANITIZE_EMAIL)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</a>';
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'email_addresses',
                                                'action' => 'edit',
                                                $ea['id']),
                                          array('class' => 'editbutton')) . "\n";
                } else {
                  print $this->Html->link(_txt('op.view'),
                                          array('controller' => 'email_addresses',
                                                'action' => 'view',
                                                $ea['id']),
                                          array('class' => 'viewbutton')) . "\n";
                }
                print '</div>';
              print '</div>';
            }
          }
          
          if($e) {
            $linktarget = array('controller'    => 'email_addresses',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id']
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
        ?>
        <br>
      </div>

      <div id="tabs-phone" class="additionalinfo">
        <?php
          if(isset($org_identities[0]['TelephoneNumber'])) {
            foreach($org_identities[0]['TelephoneNumber'] as $t) {
              print '<div>';
                print '<div>';
                  print $this->Html->link(formatTelephone($t),
                                          array('controller' => 'telephone_numbers',
                                                'action' => ($e ? 'edit' : 'view'),
                                                $t['id']));
                  print "&nbsp;(" . _txt('en.telephone_number.type', null, $t['type']) . ")<br />\n";
                print '</div>';
                print '<div>';
                
                if($e) {
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'telephone_numbers',
                        'action' => 'delete',
                        $t['id']
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt(formatTelephone($t)),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</a>';
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'telephone_numbers',
                                                'action' => 'edit',
                                                $t['id']),
                                          array('class' => 'editbutton')) . "\n";
                } else {
                  print $this->Html->link(_txt('op.view'),
                                          array('controller' => 'telephone_numbers',
                                                'action' => 'view',
                                                $t['id']),
                                          array('class' => 'viewbutton')) . "\n";
                }
                
                print '</div>';
              print '</div>';
            }
          }

          if($e) {
            $linktarget = array('controller'    => 'telephone_numbers',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id']
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
        ?>
        <br>
      </div>

      <div id="tabs-address" class="additionalinfo">
        <?php
          if(isset($org_identities[0]['Address'])) {
            foreach($org_identities[0]['Address'] as $addr) {
              print '<div>';
                print '<div>';
                  print $this->Html->link($addr['street'], array('controller' => 'addresses',
                                                                 'action'     => ($e ? 'edit' : 'view'),
                                                                 $addr['id']));
                  print "&nbsp;(" . _txt('en.address.type', null, $addr['type']) . ")<br />\n";
                print '</div>';
                print '<div>';
                
                if($e) {
                  // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
                  print '<a class="deletebutton" title="' . _txt('op.delete')
                    . '" onclick="javascript:js_confirm_generic(\''
                    . _txt('js.remove') . '\',\''    // dialog body text
                    . $this->Html->url(              // dialog confirm URL
                      array(
                        'controller' => 'addresses',
                        'action' => 'delete',
                        $addr['id']
                      )
                    ) . '\',\''
                    . _txt('op.remove') . '\',\''    // dialog confirm button
                    . _txt('op.cancel') . '\',\''    // dialog cancel button
                    . _txt('op.remove') . '\',[\''   // dialog title
                    . filter_var(_jtxt($addr['street']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.delete')
                    . '</a>';
                  print $this->Html->link(_txt('op.edit'),
                                          array('controller' => 'addresses',
                                                'action'     => 'edit',
                                                $addr['id']),
                                          array('class' => 'editbutton')) . "\n";
                } else {
                  print $this->Html->link(_txt('op.view'),
                                          array('controller' => 'addresses',
                                                'action'     => 'view',
                                                $addr['id']),
                                          array('class' => 'viewbutton')) . "\n";
                }
                
                print '</div>';
              print '</div>';
            }
          }

          if($e) {
            $linktarget = array('controller'    => 'addresses',
                                'action'        => 'add',
                                'orgidentityid' => $org_identities[0]['OrgIdentity']['id']
                               );
            $linkparams = array('class'  => 'addbutton');

            print $this->Html->link(_txt('op.add'),
                                    $linktarget,
                                    $linkparams
                                   );
          }
        ?>
        <br>
      </div>
      
      <div id="tabs-copeople" class="additionalinfo">
        <?php
          if(!empty($org_identities[0]['CoOrgIdentityLink'])) {
            foreach($org_identities[0]['CoOrgIdentityLink'] as $o) {
              print '<div>';
                print '<div>';
                  print $this->Html->link(generateCn($o['CoPerson']['PrimaryName']),
                                          array('controller' => 'co_people',
                                                'action'     => 'canvas',
                                                $o['CoPerson']['id']));
                  
                  print "&nbsp;(" . filter_var($o['CoPerson']['Co']['name'],FILTER_SANITIZE_SPECIAL_CHARS) . ")<br />\n";
                print '</div>';
                print '<div>';
                
                // We don't currently offer an unlink button here, that can only (currently)
                // be done from CO Person Canvas, which places some restrictions on that action.
                
                print $this->Html->link(_txt('op.view'),
                                        array('controller' => 'co_people',
                                              'action'     => 'canvas',
                                              $o['CoPerson']['id']),
                                        array('class' => 'editbutton')) . "\n";
                print '</div>';
              print '</div>';
            }
          }
          
          if(!empty($vv_co_person_roles['PipelineCoPersonRole']['id'])) {
            print '<div>';
              print '<div>';
                print _txt('in.orgid.pi.role') . "<br />\n";
              print '</div>';
              print '<div>';
              
              print $this->Html->link(_txt('op.view'),
                                      array('controller' => 'co_person_roles',
                                            'action'     => 'edit',
                                            $vv_co_person_roles['PipelineCoPersonRole']['id']),
                                      array('class' => 'editbutton')) . "\n";
              print '</div>';
            print '</div>';
          }
          
          if(!empty($org_identities[0]['PipelineCoGroupMember'])) {
            foreach($org_identities[0]['PipelineCoGroupMember'] as $g) {
              print '<div>';
                print '<div>';
                  print _txt('in.orgid.pi.group', array(filter_var($g['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS))) . "<br />\n";
                print '</div>';
                print '<div>';
                
                print $this->Html->link(_txt('op.view'),
                                      array('controller' => 'co_group_members',
                                            'action'     => 'edit',
                                            $g['id']),
                                      array('class' => 'editbutton')) . "\n";
                print '</div>';
              print '</div>';
            }
          }
          
          if($e) {
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
          }
        ?>
        <br>
      </div>
    <?php endif; //will not show up if adding ?>
  </div>
  
  <?php print $this->element("changelog"); ?>
</div>
