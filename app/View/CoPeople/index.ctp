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

<style type="text/css">
  /* Listing Sorter */
  #sorter {
    color: #1D5987;
    margin: -2em 0 1em 0;
    float: right;
  }
  #sorter ul,
  #sorter li {
    display: inline;
  }
  #sorter ul {
    margin: 0;
    padding: 0;
  }
  #sorter li {
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
    width: 80%;
    padding: 5px;
    border-radius: 5px;
  }
  .role {
    width: 80%;
    border: 1px solid #d0e5f5;
    border-radius: 5px;
    padding: 2px;
    margin-bottom: 5px;
  }
  .roleinfo{
    width: 50%;
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
    background: #d0e5f5 url(jquery/css/comanage-theme/images/ui-bg_glass_75_d0e5f5_1x400.png) 50% 50% repeat-x;
    color: #1d5987;
  }
  #co_people .ui-state-active,
  #co_people .ui-widget-content .ui-state-active,
  #co_people .ui-widget-header .ui-state-active {
    border: 1px solid #79b7e7;
    background: #f5f8f9 url(images/ui-bg_inset-hard_100_f5f8f9_1x100.png) 50% 50% repeat-x;
    color: #333;
  }
</style>

<script>
  $(function() {
    $( "#advancedSearch" ).accordion({
      collapsible: true,
      active     : false
    });

    $( ".line1, .line2" ).accordion({
      collapsible: true,
      active     : false
    });

  });

</script>

<?php
  $params = array('title' => _txt('fd.people', array($cur_co['Co']['name'])));
  print $this->element("pageTitle", $params);

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
?>

<div id = "sorter">
  Sort By:
  <ul>
    <li><?php print $this->Paginator->sort('PrimaryName.family', _txt('fd.name')); ?></li>
    <li><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></li>
    <li><?php print $this->Paginator->sort('created', _txt('fd.created')); ?></li>
    <li><?php print $this->Paginator->sort('modified', _txt('fd.modified')); ?></li>
  </ul>
</div>

<div id="co_people">
  <?php $i = 0; ?>
  <?php foreach ($co_people as $p): ?>
    <div class="line<?php print ($i % 2)+1; ?>">
      <div class = "panel1">
        <div class="name">
          <?php
            print $this->Html->link(generateCn($p['PrimaryName']),
                                    array(
                                      'controller' => 'co_people',
                                      'action' => ($permissions['edit']
                                                   ? 'edit'
                                                   : ($permissions['view'] ? 'view' : '')),
                                      $p['CoPerson']['id'],
                                      'co' => $cur_co['Co']['id'])
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
            if($permissions['compare'])
              print $this->Html->link(_txt('op.compare'),
                                      array('controller' => 'co_people', 
                                            'action'     => 'compare',
                                            $p['CoPerson']['id'], 
                                            'co'         => $cur_co['Co']['id']),
                                      array('class'   => 'comparebutton',
                                            'onclick' => 'noprop(event);')) 
                . "\n";
            if(true || $myPerson) {
              // XXX for now, cou admins get all the actions, but see CO-505
              // Edit actions are unavailable if not
              
              if($permissions['edit'])
                print $this->Html->link(_txt('op.edit'),
                                        array('controller' => 'co_people',
                                              'action'     => 'edit',
                                              $p['CoPerson']['id'],
                                              'co'         => $cur_co['Co']['id']),
                                        array('class'   => 'editbutton',
                                              'onclick' => 'noprop(event);')) 
                . "\n";
              
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
                  . '\')";>' 
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
                    . '\')";>' 
                    . _txt('op.inv.resend') 
                    . '</button>'
                    . "\n";
                }
              }
            }
          ?>
        </div>
      </div>
      <div class = "panel2">
        <div class="roles">
          <span> 
            <?php print _txt('fd.roles') . ':'; ?>
          </span>
          <br>
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
                        print $this->Html->link(_txt('op.edit'),
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
                    
                    if(isset($pr['Cou']['name']))
                      print " (" . $pr['Cou']['name'] . ")";
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