<!--
/**
 * COmanage Registry CO Person Index View
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->

<style type="text/css" scoped>
  /* Listing Sorter */
  #sorter {
    float: right;
  }

  /* Alpha search  */
  #peopleAlphabet {
    margin: 0.5em 0;
    font-size: 1.2em;
    border: 1px solid #eee;
  }
  #peopleAlphabet ul {
    display: table;
    width: 100%;
    margin: 0;
    padding: 0;
  }
  #peopleAlphabet li {
    display: table-cell;
    width: 3.8%;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    text-align: center;
  }
  #peopleAlphabet li:nth-child(odd) {
    background-color: #e5e5e5;
  }
  #peopleAlphabet a {
    display: inline-block;
    text-decoration: none;
    margin: 0;
    width: 100%;
    height: 100%;
    padding: 4px 0;
    margin: 0;
    color: #666;
  }
  #peopleAlphabet li.selected a,
  #peopleAlphabet li.selected a:hover{
    background-color: #888;
    color: #eee;
  }
  #peopleAlphabet a:hover {
    background-color: #ffe;
    color: #333;
  }

  /* Listing controls */
  .listControl {
    color: #1D5987;
    margin: -1em 0 1em 0;
  }
  .listControl a {
    color: #181CF5;
  }
  .listControl a:hover {
    color: #444;
    text-decoration: none;
  }

  .listControl ul,
  .listControl li {
    display: inline;
  }
  .listControl ul {
    margin: 0;
    padding: 0;
  }
  .listControl li {
    margin-left: 0.5em;
  }


  /* People Listing */
  #co_people {
    clear: both;
  }
  #co_people > .ui-accordion {
    margin: 0 0 2px;
    overflow: hidden;
    padding: 5px;
  }
  #co_people > div > .panel1{
    float: left;
    margin: 0 10px 0 0;
  }
  #noResults {
    margin: 1.5em 0 0 0;
    font-size: 1.2em;
    font-weight: bold;
  }
  .panel2 {
    padding: 0 0 0 10px !important;
    margin-left: 25px;
  }
  .panel1 div,
  .panel2 div{
    float: left;
  }
  .created,
  .status,
  .email{
    margin-top: 6px;
  }
  .name {
    width: 325px;
    position: relative;
    top: -4px;
  }
  .nameWithoutEmail {
    top: 6px;
  }
  .email {
    margin: 13px 0 0 0px;
    position: absolute;
    /*color: #7FB7DB;*/
    color: #777;
  }
  .admin {
    float: right !important;
  }
  .status {
    margin-left: 5px;
  }
  .roles {
    width: 98%;
    padding: 5px;
  }
  .role {
    width: 98%;
    /*border: 1px solid #d0e5f5; */
    padding: 2px;
    margin-bottom: 5px;
  }
  .rolestatus{
    float: right !important;
    margin: 5px 9px 5px 5px;
  }
  .roledata{
    width: 100%;
  }
  .roletitle{
    width:100%;
  }

  .roledates{
    text-align: right;
    margin: 0 30px 0 0;
  }

  /* Pagination */
  .pagination {
    padding: 5px
  }
  .outer-center {
    float: right;
    right: 50%;
    position: relative;
  }
  .inner-center {
      float: right;
      right: -50%;
      position: relative;
  }
  .clear {
      clear: both;
  }


  /* jquery ui overrides */
  #co_people .panel1 {
    border: 1px solid transparent; /* to allow our jquery UI hovers not to change the size of the div */
    background: inherit;
    color: #333;
  }
  #co_people .ui-state-hover,
  #co_people .ui-widget-content .ui-state-hover,
  #co_people .ui-widget-header .ui-state-hover,
  #co_people .ui-state-focus,
  #co_people .ui-widget-content .ui-state-focus,
  #co_people .ui-widget-header .ui-state-focus {
    border: 1px solid #79b7e7;
    background: #d0e5f5 url("/registry/css/jquery/ui/css/comanage-theme/images/ui-bg_glass_75_d0e5f5_1x400.png") 50% 50% repeat-x;
    color: #1d5987;
  }
  #co_people .ui-state-active,
  #co_people .ui-widget-content .ui-state-active,
  #co_people .ui-widget-header .ui-state-active {
    border: 1px solid #79b7e7;
    color: #333;
    background-color: #F5F5F5;
  }
  #co_people .ui-widget-content {
    background-color: #FAFAFA;
  }
</style>

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
<br /><?php endif; // relink ?>

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
      $args['action'] = 'index';
      $args['co'] = $cur_co['Co']['id'];
      foreach(range('a','z') as $i) {
        $args['Search.familyNameStart'] = $i;
        
        if(!empty($this->request->params['named']['Search.familyNameStart'])) {
          $curAlphaSearch = Sanitize::html($this->request->params['named']['Search.familyNameStart']);
          
          if($curAlphaSearch == $i) {
            print '<li class="selected">' . $this->html->link($i,$args) . '</li>';
          }  else {
            print '<li>' . $this->html->link($i,$args) . '</li>';
          }
        }
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
                                          || $this->action == 'link')
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
                    if(!empty($pr['cou_id'])) {
                      print " (" . $permissions['cous'][$pr['cou_id']];
                      if(!empty($pr['affiliation'])) {
                        global $cm_lang, $cm_texts;
                        print ", <em>" . $cm_texts[ $cm_lang ]['en.affil'][ $pr['affiliation']] . "</em>";
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