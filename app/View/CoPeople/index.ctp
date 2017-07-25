<?php
/**
 * COmanage Registry CO Person Index View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script>
  $(function() {
    
    $( ".co-person" ).accordion({
      collapsible: true,
      active     : false,
      heightStyle: "content"
    });

    // allow names to link to the person canvas
    $(".name a").click(function() {
      window.location = $(this).attr('href');
      return false;
    });

    $("#clearSearchButton").button();

  });

  function togglePeople(state) {
    if (state == 'open') {
      $(".co-person" ).accordion( "option", "active", 0 );
    } else {
      $(".co-person" ).accordion( "option", "active", false );
    }
  }
</script>

<?php
  $params = array('title' => _txt('fd.people', array($cur_co['Co']['name'])));
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($this->action == 'link') {
    $this->Html->addCrumb(_txt('op.link'));
  } elseif($this->action == 'relink') {
    $this->Html->addCrumb(_txt('op.relink'));
  } elseif($this->action == 'select') {
    $this->Html->addCrumb(filter_var($title_for_layout,FILTER_SANITIZE_SPECIAL_CHARS));
  } else {
    $this->Html->addCrumb(_txt('me.population'));
  }
?>

<?php if($this->action == 'link'): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('op.link.select', array(filter_var(generateCn($vv_org_identity['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS),
      filter_var($vv_org_identity['OrgIdentity']['id'],FILTER_SANITIZE_SPECIAL_CHARS))); ?>
  </div>
<?php elseif($this->action == 'relink' && !empty($vv_co_org_identity_link['OrgIdentity'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('op.relink.select', array(filter_var(generateCn($vv_co_org_identity_link['OrgIdentity']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS),
      filter_var($vv_co_org_identity_link['OrgIdentity']['id'],FILTER_SANITIZE_SPECIAL_CHARS))); ?>
  </div>
<?php elseif($this->action == 'relink' && !empty($vv_co_person_role['CoPersonRole'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('op.relink.role.select', array(filter_var($vv_co_person_role['CoPersonRole']['title'],FILTER_SANITIZE_SPECIAL_CHARS),
      filter_var($vv_co_person_role['CoPersonRole']['id'],FILTER_SANITIZE_SPECIAL_CHARS))); ?>
  </div>
<?php elseif($this->action == 'select'): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('op.select.select'); ?>
  </div>
<?php endif; // link ?>

<div id="sorter" class="listControl">
  <?php print _txt('fd.sort.by'); ?>:
  <ul>
    <li class="spin"><?php print $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></li>
    <li class="spin"><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></li>
    <li class="spin"><?php print $this->Paginator->sort('created', _txt('fd.created')); ?></li>
    <li class="spin"><?php print $this->Paginator->sort('modified', _txt('fd.modified')); ?></li>
  </ul>
</div>

<div id="peopleToggle" class="listControl">
  <?php print _txt('fd.toggle.all'); ?>:
  <ul>
    <li><?php print $this->html->link(_txt('fd.open'),'javascript:togglePeople(\'open\');'); ?></li>
    <li><?php print $this->html->link(_txt('fd.closed'),'javascript:togglePeople(\'closed\');'); ?></li>
  </ul>
</div>

<?php // Load the top search bar
if(isset($permissions['search']) && $permissions['search'] ) {
  if(!empty($this->plugin)) {
    $fileLocation = APP . "Plugin/" . $this->plugin . "/View/CoPeople/search.inc";
    if(file_exists($fileLocation))
      include($fileLocation);
  } else {
    $fileLocation = APP . "View/CoPeople/search.inc";
    if(file_exists($fileLocation))
      include($fileLocation);
  }
}
?>

<div id="peopleAlphabet" class="listControl" aria-label="<?php print _txt('me.alpha.label'); ?>">
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
      
      // Merge (propagate) all prior search criteria, except familyNameStart and page
      $args = array_merge($args, $this->request->params['named']);
      unset($args['Search.familyNameStart']);
      unset($args['page']);
      
      $alphaSearch = '';

      if(!empty($this->request->params['named']['Search.familyNameStart'])) {
        $alphaSearch = $this->request->params['named']['Search.familyNameStart'];
      }

      foreach(_txt('me.alpha') as $i) {
        $args['Search.familyNameStart'] = $i;
        $alphaStyle = ' class="spin"';
        if ($alphaSearch == $i) {
          $alphaStyle = ' class="selected spin"';
        }
        print '<li' . $alphaStyle . '>' . $this->html->link($i,$args) . '</li>';
      }
    ?>
    <li class="spin">
      <a href="javascript:clearSearch(document.getElementById('CoPersonSearchForm'));"
         title="<?php print _txt('op.clear.search'); ?>">
        <em class="material-icons">block</em>
      </a>
    </li>
  </ul>
</div>

<div id="co_people">
  <?php $i = 0; ?>
  <?php foreach ($co_people as $p): ?>
    <div class="co-person line<?php print ($i % 2)+1; ?>">
      <div class="person-panel">
        <?php
          $nameWithoutEmailClass = 'nameWithEmail';
          if(!isset($p['EmailAddress'][0]['mail'])) {
            $nameWithoutEmailClass = 'nameWithoutEmail';
          }
        ?>
        <div class="person-info">
          <div class="person-info-inner">
            <span class="person-name <?php print $nameWithoutEmailClass; ?>">
              <?php
                print $this->Html->link(generateCn($p['PrimaryName']),
                  array(
                    'controller' => 'co_people',
                    'action' => ($permissions['edit'] ? 'canvas' : ($permissions['view'] ? 'view' : '')),
                    $p['CoPerson']['id'])
                );
              ?>
            </span>

            <span class="person-email">
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
            </span>
          </div>

          <span class="person-status">
            <?php
              global $status_t;
              if(!empty($p['CoPerson']['status']) ) print _txt('en.status', null, $p['CoPerson']['status']);
            ?>
          </span>
        </div>
        
        <div class="person-admin">
          <?php
            if(true || $myPerson) {
              // XXX for now, cou admins get all the actions, but see CO-505
              // Edit actions are unavailable if not
              
              if($this->action == 'index') {
                // Resend invitation button
                // This is largely duplicated in fields.inc
                if($permissions['invite']
                   && ($p['CoPerson']['status'] == StatusEnum::Pending
                       || $p['CoPerson']['status'] == StatusEnum::Invited)) {
                  print '<button class="invitebutton"'
                    . 'title="' . _txt('op.inv.resend.to',array(generateCn($p['PrimaryName']))) . '" '
                    . 'aria-label="' . _txt('op.inv.resend.to',array(generateCn($p['PrimaryName']))) . '" '
                    . 'onclick="javascript:noprop(event);js_confirm_generic(\''
                    . _txt('js.reinvite') . '\',\''   // dialog body text
                    . $this->Html->url(array(         // dialog confirm URL
                        'controller' => 'co_invites',
                        'action'     => 'send',
                        'copersonid' => $p['CoPerson']['id'])
                     ) . '\',\''
                    . _txt('op.inv.resend') . '\',\''   // dialog confirm button
                    . _txt('op.cancel') . '\',\''       // dialog cancel button
                    . _txt('op.inv.resend') . '\',[\''  // dialog title
                    . filter_var(_jtxt(generateCn($p['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                    . '\']);">'
                    . _txt('op.inv.resend') 
                    . '</button>'
                    . "\n";
                } elseif($permissions['enroll']
                         && $p['CoPerson']['status'] == StatusEnum::PendingConfirmation) {
                  if(!empty($p['CoInvite']['CoPetition']['id'])) {
                    print '<button class="invitebutton" '
                      . 'title="' . _txt('op.inv.resend.to',array(generateCn($p['PrimaryName']))) . '" '
                      . 'aria-label="' . _txt('op.inv.resend.to',array(generateCn($p['PrimaryName']))) . '" '
                      . 'onclick="javascript:noprop(event);js_confirm_generic(\''
                      . _txt('js.reinvite') . '\',\''   // dialog body text
                      . $this->Html->url(array(         // dialog confirm URL
                          'controller' => 'co_petitions',
                          'action'     => 'resend',
                          $p['CoInvite']['CoPetition']['id'])
                      ) . '\',\''
                      . _txt('op.inv.resend') . '\',\''   // dialog confirm button
                      . _txt('op.cancel') . '\',\''       // dialog cancel button
                      . _txt('op.inv.resend') . '\',[\''  // dialog title
                      . filter_var(_jtxt(generateCn($p['PrimaryName'])),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                      . '\']);">'
                      . _txt('op.inv.resend') 
                      . '</button>'
                      . "\n";
                  }
                }
              }
              
              // Edit button
              if($permissions['edit']) {
                print $this->Html->link((($this->action == 'relink'
                      || $this->action == 'link'
                      || $this->action == 'select')
                      ? _txt('op.view')
                      : _txt('op.edit')),
                    array(
                      'controller' => 'co_people',
                      'action' => 'canvas',
                      $p['CoPerson']['id']
                    ),
                    array(
                      'class' => 'editbutton spin',
                      'onclick' => 'noprop(event);',
                      'title' => _txt('op.edit-a',array(generateCn($p['PrimaryName']))),
                      'aria-label' => _txt('op.edit-a',array(generateCn($p['PrimaryName'])))
                    ))
                  . "\n";
              }
              
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
        <span class="clearfix"></span>
      </div>
      <div class = "role-panel">
        <div class="roles-title"><?php print _txt('fd.roles'); ?></div>
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
                        print '<span class="roleTitleLinks">';
                        print $this->Html->link(_txt('op.petition'),
                                                array('controller' => 'co_petitions',
                                                      'action' => 'view',
                                                      $pr['CoPetition'][0]['id'],
                                                      'co' => $pr['CoPetition'][0]['co_id'],
                                                      'coef' => $pr['CoPetition'][0]['co_enrollment_flow_id']),
                                                array('class' => 'petitionbutton'));
                        print '</span>';
                        if(!empty($pr['title'])) {
                          print '<span class="roleTitleText">';
                          print $this->Html->link($pr['title'],
                            array(
                              'controller' => 'co_petitions',
                              'action' => 'view',
                              $pr['CoPetition'][0]['id'],
                              'co' => $pr['CoPetition'][0]['co_id'],
                              'coef' => $pr['CoPetition'][0]['co_enrollment_flow_id']
                            ));
                          print '</span>';
                        }
                      } else {
                        print '<span class="roleTitleLinks">';
                        print $this->Html->link(($this->action == 'relink'
                           ? _txt('op.view')
                           : _txt('op.edit')),
                          array('controller' => 'co_person_roles',
                                'action' => ($permissions['edit'] ? "edit" : "view"),
                                $pr['id'],
                                'co' => $cur_co['Co']['id']),
                          array(
                            'class' => 'editbutton spin',
                            'onclick' => 'noprop(event);',
                            'title' => _txt('op.edit-a',array(_txt('ct.co_person_roles.1') . ' ' . $pr['title'])),
                            'aria-label' => _txt('op.edit-a',array(_txt('ct.co_person_roles.1') . ' ' . $pr['title']))
                          ));
                        print '</span>';
                        if(!empty($pr['title'])) {
                          print '<span class="roleTitleText">';
                          print $this->Html->link($pr['title'],
                            array(
                              'controller' => 'co_person_roles',
                              'action' => ($permissions['edit'] ? "edit" : "view"),
                              $pr['id'],
                              'co' => $cur_co['Co']['id']
                            ));
                          print '</span>';
                        }
                      }
                    } else{
                      print '<span class="roleTitleText">';
                      print $pr['title'];
                      print '</span>';
                    }

                    // Insert placeholder when no title exists for display
                    if(empty($pr['title'])) {
                      print '<span class="roleTitleText">';
                      print _txt('fd.title.none');
                      print '</span>';
                    }

                    // Display COU information if present
                    if(!empty($pr['Cou']['name'])) {
                      print " (" . $pr['Cou']['name'];
                      if(!empty($pr['affiliation'])) {
                        print ", <em>" . $vv_copr_affiliation_types[ $pr['affiliation'] ] . "</em>";
                      }
                      print ")";
                    }
                  print '<span class="clearfix"></span>';
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
      print('<div id="noResults">' . _txt('rs.search.none') . '</div>');
      print('<div id="restoreLink">');
      $args = array();
      $args['plugin'] = null;
      $args['controller'] = 'co_people';
      $args['action'] = 'index';
      $args['co'] = $cur_co['Co']['id'];
      print $this->Html->link(_txt('op.search.restore'), $args);
      print('</div>');
    }
  ?>

  <?php print $this->element("pagination"); ?>
  <div class="clearfix"></div>

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