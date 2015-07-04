<!--
/**
 * COmanage Registry CO Person Index View
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->

<script>
  $(function() {
    
    $( ".line1, .line2" ).accordion({
      collapsible: true,
      active     : false
    });

    // allow names to link to the person canvas
    $(".name a").click(function() {
      window.location = $(this).attr('href');
      return false;
    });

  });

  function togglePeople(state) {
    if (state == 'open') {
      $(".line1, .line2" ).accordion( "option", "active", 0 );
    } else {
      $(".line1, .line2" ).accordion( "option", "active", false );
    }
  }
</script>

<?php
  $params = array('title' => _txt('fd.people', array($cur_co['Co']['name'])));
  print $this->element("pageTitle", $params);

  if($this->action == 'link') {
    // Add breadcrumbs
    $this->Html->addCrumb(_txt('op.link'));
  } elseif($this->action == 'relink') {
    // Add breadcrumbs
    $this->Html->addCrumb(_txt('op.relink'));
  } elseif($this->action == 'select') {
    print $this->element("enrollmentCrumbs");
  } else {
    // Add breadcrumbs
    $this->Html->addCrumb(_txt('me.population'));
    
    // Add buttons to sidebar
    $sidebarButtons = $this->get('sidebarButtons');
    
    if($permissions['enroll'] && !empty($co_enrollment_flows)) {
      $sidebarButtons[] = array(
        'icon'    => 'circle-plus',
        'title'   => _txt('op.enroll'),
        'url'     => array(
          'controller' => 'co_enrollment_flows',
          'action'     => 'select',
          'co'         => $cur_co['Co']['id']
        )
      );    
    } elseif($permissions['add']) {
      $sidebarButtons[] = array(
        'icon'    => 'circle-plus',
        'title'   => _txt('op.inv'),
        'url'     => array(
          'controller' => 'org_identities', 
          'action'     => 'find', 
          'co'         => $cur_co['Co']['id']
        )
      );
    }  
    
    $this->set('sidebarButtons', $sidebarButtons);
  }
?>

<?php if($this->action == 'link'): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.link.select', array(generateCn($vv_org_identity['PrimaryName']),
                                                     $vv_org_identity['OrgIdentity']['id'])); ?></strong>
  </p>
</div>
<br />
<?php elseif($this->action == 'relink' && !empty($vv_co_org_identity_link['OrgIdentity'])): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.relink.select', array(generateCn($vv_co_org_identity_link['OrgIdentity']['PrimaryName']),
                                                       $vv_co_org_identity_link['OrgIdentity']['id'])); ?></strong>
  </p>
</div>
<br />
<?php elseif($this->action == 'relink' && !empty($vv_co_person_role['CoPersonRole'])): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.relink.role.select', array($vv_co_person_role['CoPersonRole']['title'],
                                                       $vv_co_person_role['CoPersonRole']['id'])); ?></strong>
  </p>
</div>
<br />
<?php elseif($this->action == 'select'): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('op.select.select'); ?></strong>
  </p>
</div>
<br />
<?php endif; // link ?>

<div id="sorter" class="listControl">
  <?php print _txt('fd.sort.by'); ?>:
  <ul>
    <li><?php print $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></li>
    <li><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></li>
    <li><?php print $this->Paginator->sort('created', _txt('fd.created')); ?></li>
    <li><?php print $this->Paginator->sort('modified', _txt('fd.modified')); ?></li>
  </ul>
</div>

<div id="peopleToggle" class="listControl">
  <?php print _txt('fd.toggle.all'); ?>:
  <ul>
    <li><?php print $this->html->link(_txt('fd.open'),'javascript:togglePeople(\'open\');'); ?></li>
    <li><?php print $this->html->link(_txt('fd.closed'),'javascript:togglePeople(\'closed\');'); ?></li>
  </ul>
</div>

<div id="peopleAlphabet" class="listControl">
  <ul>
    <?php
      $args = array();
      $args['controller'] = 'co_people';
      $args['action'] = $this->action;
      
      if($this->action == 'index') {
        $args['co'] = $cur_co['Co']['id'];
      } else {
        // A link/relink operation is in progress
        if(!empty($this->request->params['pass'][0])) {
          $args[] = $this->request->params['pass'][0];
        }
      }
      
      // Merge (propagate) all prior search criteria, except familyNameStart
      $args = array_merge($args, $this->request->params['named']);
      unset($args['Search.familyNameStart']);
      
      $alphaSearch = '';

      if(!empty($this->request->params['named']['Search.familyNameStart'])) {
        $alphaSearch = Sanitize::html($this->request->params['named']['Search.familyNameStart']);
      }

      foreach(range('a','z') as $i) {
        $args['Search.familyNameStart'] = $i;
        $alphaStyle = '';
        if ($alphaSearch == $i) {
          $alphaStyle = ' class="selected"';
        }
        print '<li' . $alphaStyle . '>' . $this->html->link($i,$args) . '</li>';
      }
    ?>
  </ul>
</div>

<div id="co_people">
  <?php $i = 0; ?>
  <?php foreach ($co_people as $p): ?>
    <div class="line<?php print ($i % 2)+1; ?>">
      <div class = "panel1">
        <?php
          $nameWithoutEmailClass = 'nameWithEmail';
          if(!isset($p['EmailAddress'][0]['mail'])) {
            $nameWithoutEmailClass = 'nameWithoutEmail';
          }
        ?>
        <div class="name <?php print $nameWithoutEmailClass; ?>">
          <?php
            print $this->Html->link(generateCn($p['PrimaryName']),
              array(
                'controller' => 'co_people',
                'action' => ($permissions['edit'] ? 'canvas' : ($permissions['view'] ? 'view' : '')),
                $p['CoPerson']['id'])
            );
          ?>
        </div>

        <div class = "email">
          <?php
              if(isset($p['EmailAddress'][0]['mail'])) { 
                print '(' ;

                $email = $p['EmailAddress'][0]['mail'];
                if(strlen($email) > 36)
                  print substr($email, 0, 35) . "...";
                else
                  print $email;
                
                print ')';
              }
          ?>
        </div>

        <div class="status">
          <?php
            global $status_t;

            if(!empty($p['CoPerson']['status']) ) echo _txt('en.status', null, $p['CoPerson']['status']);
          ?>
        </div>
        
        <div class="admin">
          <?php
            if(true || $myPerson) {
              // XXX for now, cou admins get all the actions, but see CO-505
              // Edit actions are unavailable if not
              
              if($this->action == 'index') {
                // Resend invitation button
                if($permissions['invite']
                   && ($p['CoPerson']['status'] == StatusEnum::Pending
                       || $p['CoPerson']['status'] == StatusEnum::Invited)) {
                  print '<button class="invitebutton" title="' 
                    . _txt('op.inv.resend') 
                    . '" onclick="javascript:noprop(event);js_confirm_reinvite(\'' 
                    . _jtxt(Sanitize::html(generateCn($p['PrimaryName']))) 
                    . '\', \'' 
                    . $this->Html->url(array('controller' => 'co_invites',
                                             'action'     => 'send', 
                                             'copersonid' => $p['CoPerson']['id'], 
                                             'co'         => $cur_co['Co']['id'])) 
                    . '\');">'
                    . _txt('op.inv.resend') 
                    . '</button>'
                    . "\n";
                } elseif($permissions['enroll']
                         && $p['CoPerson']['status'] == StatusEnum::PendingConfirmation) {
                  if(!empty($p['CoInvite']['CoPetition']['id'])) {
                    print '<button class="invitebutton" title="' 
                      . _txt('op.inv.resend') 
                      . '" onclick="javascript:noprop(event);js_confirm_reinvite(\'' 
                      . _jtxt(Sanitize::html(generateCn($p['PrimaryName']))) 
                      . '\', \'' 
                      . $this->Html->url(array('controller' => 'co_petitions',
                                               'action'     => 'resend',
                                               $p['CoInvite']['CoPetition']['id'],
                                               'co'         => $cur_co['Co']['id'])) 
                      . '\');">'
                      . _txt('op.inv.resend') 
                      . '</button>'
                      . "\n";
                  }
                }
              }
              
              // Edit button
              if($permissions['edit'])
                print $this->Html->link((($this->action == 'relink'
                                          || $this->action == 'link'
                                          || $this->action == 'select')
                                         ? _txt('op.view')
                                         : _txt('op.edit')),
                    array('controller' => 'co_people',
                      'action'     => 'canvas',
                      $p['CoPerson']['id']),
                    array('class'   => 'editbutton',
                      'onclick' => 'noprop(event);'))
                  . "\n";
              
              if($this->action == 'link') {
                print $this->Html->link(_txt('op.link'),
                          array('controller'    => 'co_people',
                                'action'        => 'link',
                                $p['CoPerson']['id'],
                                'orgidentityid'        => $vv_org_identity['OrgIdentity']['id']),
                          array('class'   => 'relinkbutton',
                                'onclick' => 'noprop(event);'))
                      . "\n";  
              } elseif($this->action == 'relink'
                       && !empty($vv_co_org_identity_link['CoOrgIdentityLink'])
                       // Don't allow linking back to the current CO Person
                       && $vv_co_org_identity_link['CoOrgIdentityLink']['co_person_id'] != $p['CoPerson']['id']) {
                print $this->Html->link(_txt('op.relink'),
                                        array('controller'    => 'co_people',
                                              'action'        => 'relink',
                                              $vv_co_org_identity_link['CoOrgIdentityLink']['co_person_id'],
                                              'linkid'        => $vv_co_org_identity_link['CoOrgIdentityLink']['id'],
                                              'tocopersonid'  => $p['CoPerson']['id']),
                                        array('class'   => 'relinkbutton',
                                              'onclick' => 'noprop(event);'))
                      . "\n";
              } elseif($this->action == 'relink'
                       && !empty($vv_co_person_role['CoPersonRole'])
                       // Don't allow linking back to the current CO Person
                       && $vv_co_person_role['CoPersonRole']['co_person_id'] != $p['CoPerson']['id']) {
                print $this->Html->link(_txt('op.relink'),
                                        array('controller'     => 'co_people',
                                              'action'         => 'relink',
                                              $vv_co_person_role['CoPersonRole']['co_person_id'],
                                              'copersonroleid' => $vv_co_person_role['CoPersonRole']['id'],
                                              'tocopersonid' => $p['CoPerson']['id']),
                                        array('class'   => 'relinkbutton',
                                              'onclick' => 'noprop(event);'))
                      . "\n";
              } elseif($this->action == 'select') {
                print $this->Html->link(_txt('op.select'),
                          array('controller'    => 'co_petitions',
                                'action'        => 'selectEnrollee',
                                $this->request->params['named']['copetitionid'],
                                'copersonid'    => $p['CoPerson']['id']),
                          array('class'   => 'relinkbutton',
                                'onclick' => 'noprop(event);'))
                      . "\n"; 
              }
            }
          ?>
        </div>
      </div>
      <div class = "panel2">
        <div class="roles">
          <?php
            foreach ($p['CoPersonRole'] as $pr) {
              print '<div class = "role">';
                print '<div class = "rolestatus">';
                  // Print Status
                  if(!empty($pr['status']) ) {
                    print _txt('en.status', null, $pr['status']);
                  }
                print '</div>';

                print '<div class = "roleinfo">';
                  print '<div class = "roletitle">';
                    // The current user can edit this role if they have general edit
                    // permission and (1) there is no COU defined or (2) there is a COU
                    // defined and the user can manage that COU.
                    $myPersonRole = false;
                    
                    if($permissions['edit']) {
                      if(empty($pr['cou_id']) || isset($permissions['cous'][ $pr['cou_id'] ])) {
                        $myPersonRole = true;
                      }
                    }
                    
                    if($myPersonRole) {
                      if($permissions['enroll']
                         && $pr['status'] == StatusEnum::PendingApproval
                         && !empty($pr['CoPetition'])) {
                        print $this->Html->link(_txt('op.petition'),
                                                array('controller' => 'co_petitions',
                                                      'action' => 'view',
                                                      $pr['CoPetition'][0]['id'],
                                                      'co' => $pr['CoPetition'][0]['co_id'],
                                                      'coef' => $pr['CoPetition'][0]['co_enrollment_flow_id']),
                                                array('class' => 'petitionbutton'));
                        
                        print $this->Html->link($pr['title'],
                                                array('controller' => 'co_petitions',
                                                      'action' => 'view',
                                                      $pr['CoPetition'][0]['id'],
                                                      'co' => $pr['CoPetition'][0]['co_id'],
                                                      'coef' => $pr['CoPetition'][0]['co_enrollment_flow_id']));
                      } else {
                        print $this->Html->link(($this->action == 'relink'
                                                 ? _txt('op.view')
                                                 : _txt('op.edit')),
                                                array('controller' => 'co_person_roles',
                                                      'action' => ($permissions['edit'] ? "edit" : "view"),
                                                      $pr['id'],
                                                      'co' => $cur_co['Co']['id']),
                                                array('class' => 'editbutton'));
                        
                        print $this->Html->link($pr['title'],
                                                array('controller' => 'co_person_roles',
                                                      'action' => ($permissions['edit'] ? "edit" : "view"),
                                                      $pr['id'],
                                                      'co' => $cur_co['Co']['id']));
                      }
                    } else{
                      print $pr['title'];
                    }

                    // Insert placeholder when no title exists for display
                    if(empty($pr['title'])) {
                      print _txt('fd.title.none');
                    }

                    // Display COU information if present
                    if(!empty($pr['Cou']['name'])) {
                      print " (" . $pr['Cou']['name'];
                      if(!empty($pr['affiliation'])) {
                        print ", <em>" . $vv_copr_affiliation_types[ $pr['affiliation'] ] . "</em>";
                      }
                      print ")";
                    }

                  print "</div>";  // roletitle
                print "</div>";  // roleinfo
              print "</div>";  // role
            }
          ?>
        </div>
      </div>
    </div>
    <?php $i++; ?>
  <?php endforeach; // $co_people ?>

  <?php
    if(empty($co_people)) {
      // No search results, or there are no people in this CO
      print('<div id="noResults">' . _txt('ct.co_people.se.no_results') . '</div>');
      print('<div id="restoreLink">');
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'co_people';
      $args['action'] = 'index';
      $args['co'] = $cur_co['Co']['id'];
      print $this->Html->link(_txt('ct.co_people.se.restore'), $args);
      print('</div>');
    }
  ?>

  <div class="pagination">
    <div class="outer-center">
      <div class="product inner-center">
        <?php print $this->Paginator->numbers(); ?>
      </div>
    </div>
    <div class="clear"></div>
  </div>
</div>

<script>
// Prevents propagations of event handling up to containers
function noprop(e)
{
    if (!e)
      e = window.event;

    //IE9 & Other Browsers
    if (e.stopPropagation) {
      e.stopPropagation();
    }
    //IE8 and Lower
    else {
      e.cancelBubble = true;
    }
}
</script>