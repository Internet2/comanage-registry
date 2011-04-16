<?php
  /*
   * COmanage Gears Language (L10N) Implementation
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */
  
  global $cm_lang, $cm_texts;

  // XXX move this to a master config
  $cm_lang = "en_US";
  
  // When localizing, the number in format specifications (eg: %1$s) indicates the argument
  // position as passed to _txt.  This can be used to process the arguments in
  // a different order than they were passed.
  
  $cm_texts['en_US'] = array(
    // Application name
    'coordinate' =>     'COmanage COordinate',
    
    // What a CO is called (abbreviated)
    'co' =>             'CO',
    'cos' =>            'COs',
    
    // What an Org is called
    'org' =>            'Organization',
    
    // Authnz
    'au.not' =>         'Not Logged In',
    
    // COs Controllers
    'co.cm.gradmin' =>  'COmanage Platform Administrators',
    'co.cm.desc' =>     'COmanage Gears Internal CO',
    'co.init' =>        'No COs found, initial CO created',
    'co.nomember' =>    'You are not a member of any COs',
    'co.select' =>      'Select the CO you wish to work with.',
    
    // Titles, per-controller
    'ct.addresses.1' =>           'Address',
    'ct.addresses.pl' =>          'Addresses',
    'ct.co_extended_attributes.1'  => 'Extended Attribute',
    'ct.co_extended_attributes.pl' => 'Extended Attributes',
    'ct.co_group_members.1' =>    'Group Member',
    'ct.co_group_members.pl' =>   'Group Members',
    'ct.co_groups.1' =>           'Group',
    'ct.co_groups.pl' =>          'Groups',
    'ct.co_invites.1' =>          'Invite',
    'ct.co_invites.pl' =>         'Invites',
    'ct.co_people.1' =>           'CO Person',
    'ct.co_people.pl' =>          'CO People',
    'ct.cos.1' =>                 'CO',
    'ct.cos.pl' =>                'COs',
    'ct.cous.1' =>                'COU',
    'ct.cous.pl' =>               'COUs',
    'ct.email_addresses.1' =>     'Email Address',
    'ct.email_addresses.pl' =>    'Email Addresses',
    'ct.identifiers.1' =>         'Identifier',
    'ct.identifiers.pl' =>        'Identifiers',
    'ct.org_people.1' =>          'Organizational Person',
    'ct.org_people.pl' =>         'Organizational People',
    'ct.organizations.1' =>       'Organization',
    'ct.organizations.pl' =>      'Organizations',
    'ct.telephone_numbers.1' =>   'Telephone Number',
    'ct.telephone_numbers.pl' =>  'Telephone Numbers',

    // Enumerations, corresponding to enum.php
    'en.affil' =>       array('faculty' => 'Faculty',
                              'student' => 'Student',
                              'staff' => 'Staff',
                              'alum' => 'Alum',
                              'member' => 'Member',
                              'affiliate' => 'Affiliate',
                              'employee' => 'Employee',
                              'library-walk-in' => 'Library Walk-In'),
  
    'en.contact' =>     array('F' => 'Fax',
                              'H' => 'Home',
                              'M' => 'Mobile',
                              'O' => 'Office',
                              'P' => 'Postal',
                              'R' => 'Forwarding'),
    
    'en.extattr' =>     array('INTEGER' => 'Integer',
                              'TIMESTAMP' => 'Timestamp',
                              'VARCHAR(32)' => 'String (32)'),
  
    'en.identifier' =>  array('eppn' => 'ePPN',
                              'eptid' => 'ePTID',
                              'mail' => 'Mail',
                              'openid' => 'OpenID',
                              'uid' => 'UID'),

    'en.status' =>      array('A' => 'Active',
                              'D' => 'Deleted',
                              'I' => 'Invited',
                              'P' => 'Pending',
                              'S' => 'Suspended',
                              'X' => 'Declined'),

    // Errors
    'er.co.cm.edit' =>  'Cannot Edit COmanage CO',
    'er.co.cm.rm' =>    'Cannot Remove COmanage CO',
    'er.co.exists' =>   'A CO named "%1$s" already exists',
    'er.co.unk' =>      'Unknown CO',
    'er.comember' =>    '%1$s is a member of one or more COs (%2$s) and cannot be removed.',
    'er.cop.nf' =>      'CO Person %1$s Not Found',
    'er.cop.none' =>    'CO Person Not Provided',
    'er.cop.unk' =>     'Unknown CO Person',
    'er.cou.cop' =>     'There are still one or more CO people in the COU %1$s, and so it cannot be deleted.',
    'er.delete' =>      'Delete Failed',
    'er.deleted-a' =>   'Deleted "%1$s"',
    'er.ea.alter' =>    'Failed to alter table for attribute',
    'er.ea.exists' =>   'An attribute named "%1$s" already exists within the CO',
    'er.ea.index' =>    'Failed to update index for attribute',
    'er.ea.table' =>    'Failed to create CO Extended Attribute table',
    'er.ea.table.d' =>  'Failed to drop CO Extended Attribute table',
    'er.gr.exists' =>   'A group named "%1$s" already exists within the CO',
    'er.gr.init' =>     'Group created, but failed to set initial owner/member',
    'er.gr.nf' =>       'Graup %1$s Not Found',
    'er.grm.already' => 'CO Person %1$s is already a member of group %2$s',
    'er.grm.none' =>    'No group memberships to add',
    'er.inv.exp' =>     'Invitation Expired',
    'er.inv.nf' =>      'Invitation Not Found',
    'er.notfound' =>    '%1$s "%2$s" Not Found',
    'er.notprov' =>     'Not Provided',
    'er.notprov.id' =>  '%1$s ID Not Provided',
    'er.reply.unk' =>   'Unknown Reply',
    'er.orgp.nomail' => '%1$s (Org Person %2$s) has no known email address.<br />Add an email address and then resend the invitation.',
    'er.orgp.unk-a' =>  'Unknown Org Person "%1$s"',

    // Fields
    'fd.actions' =>     'Actions',
    'fd.address' =>     'Address',
    'fd.address.1' =>   'Address Line 1',
    'fd.address.2' =>   'Address Line 2',
    'fd.affiliation' => 'Affiliation',
    'fd.an.desc' =>     'Alphanumeric characters only',
    'fd.attribute' =>   'Attribute',
    'fd.city' =>        'City',
    'fd.closed' =>      'Closed',
    'fd.cou' =>         'COU',
    'fd.country' =>     'Country',
    'fd.desc' =>        'Description',
    'fd.directory' =>   'Directory',
    'fd.domain' =>      'Domain',
    'fd.false' =>       'False',
    'fd.group.mem' =>   'Member',
    'fd.group.memin' => 'membership in "%1$s"',
    'fd.group.own' =>   'Owner',
    'fd.groups' =>      'Groups',
    'fd.id' =>          'Identifier',
    'fd.ids' =>         'Identifiers',
    'fd.index' =>       'Index',
    'fd.login' =>       'Login',
    'fd.login.desc' =>  'Allow this identifier to login to COordinate',
    'fd.mail' =>        'Email',
    'fd.members' =>     'Members',
    'fd.name' =>        'Name',
    'fd.name.d' =>      'Display Name',
    'fd.name.f' =>      'Family Name',
    'fd.name.g' =>      'Given Name',
    'fd.name.h' =>      'Honorific',
    'fd.name.h.desc' => '(Dr, Hon, etc)',
    'fd.name.m' =>      'Middle Name',
    'fd.name.s' =>      'Suffix',
    'fd.name.s.desc' => '(Jr, III, etc)',
    'fd.no' =>          'No',
    'fd.o' =>           'Organization',
    'fd.open' =>        'Open',
    'fd.orgid' =>       'Organization ID',
    'fd.ou' =>          'Department',
    'fd.perms' =>       'Permissions',
    'fd.phone' =>       'Phone',
    'fd.postal' =>      'ZIP/Postal Code',
    'fd.req' =>         '* denotes required field',
    'fd.searchbase' =>  'Search Base',
    'fd.state' =>       'State',
    'fd.status' =>      'Status',
    'fd.title' =>       'Title',
    'fd.true' =>        'True',
    'fd.type' =>        'Type',
    'fd.type.warn' =>   'After an extended attribute is created, its type may not be changed',
    'fd.valid.f' =>     'Valid From',
    'fd.valid.f.desc' =>  '(leave blank for immediate validity)',
    'fd.valid.u' =>     'Valid Through',
    'fd.valid.u.desc' =>  '(leave blank for indefinite validity)',
    'fd.yes' =>         'Yes',

    // Operations
    'op.add' =>         'Add',
    'op.add-a' =>       'Add "%1$s"',
    'op.add.new' =>     'Add a New %1$s',
    'op.back' =>        'Back',
    'op.cancel' =>      'Cancel',
    'op.compare' =>     'Compare',
    'op.delete' =>      'Delete',
    'op.delete.ok' =>   'Are you sure you wish to remove "%1$s"? This action cannot be undone.',
    'op.edit' =>        'Edit',
    'op.edit-a' =>      'Edit "%1$s"',
    'op.edit-f' =>      'Edit %1$s for %2$s',
    'op.find.inv' =>    'Find a Person to Invite to %1$s',
    'op.gr.memadd' =>   'Add Person %1$s to Group',
    'op.grm.add' =>     'Add Person to %1$s Group %2$s',
    'op.inv' =>         'Invite',
    'op.inv-a' =>       'Invite %1$s',
    'op.inv-t' =>       'Invite %1$s to %2$s',
    'op.inv.reply' =>   'Reply to Invitation',
    'op.inv.resend' =>  'Resend Invite',
    'op.inv.send' =>    'Send Invite',
    'op.menu' =>        'Menu',
    'op.logout' =>      'Logout',
    'op.ok' =>          'OK',
    'op.proceed.ok' =>  'Are you sure you wish to proceed?',
    'op.remove' =>      'Remove',
    'op.save' =>        'Save',
    'op.select' =>      'Select',
    'op.select-a' =>    'Select a %1$s',
    'op.view' =>        'View',
    'op.view-a' =>      'View "%1$s"',
    
    // Results
    'rs.added' =>       'Added',
    'rs.added-a' =>     '"%1$s" Added',
    'rs.inv.conf' =>    'Invitation Confirmed',
    'rs.inv.dec' =>     'Invitation Declined',
    'rs.updated' =>     '"%1$s" Updated',
    
    // Setup
    
    'se.cf.admin.given' =>  'Enter administrator\'s given name',
    'se.cf.admin.sn' =>     'Enter administrator\'s family name',
    'se.cf.admin.user' =>   'Enter administrator\'s login username',
    'se.db.co' =>           'Creating COmanage CO',
    'se.db.cop' =>          'Adding OrgPerson to CO',
    'se.db.group' =>        'Creating COmanage admin group',
    'se.db.op' =>           'Adding initial OrgPerson',
    'se.done' =>            'Setup complete',
    'se.users.drop' =>      'Dropping users table',
    'se.users.view' =>      'Creating users view'
  );
  
  function _txt($key, $vars=null, $index=null)
  {
    // Render localized text.
    //
    // Parameters:
    // - key: Index of message to render
    // - vars: Array of substitutions for variables within localized text
    // - index: If <key> represents an array, the index of the corresponding message
    //
    // Preconditions:
    //     None
    //
    // Postconditions:
    //     None
    //
    // Returns:
    // - The localized text
    
    global $cm_lang, $cm_texts;

    // XXX need to figure out how to pass arbitrary # of args to sprintf
    
    $s = (isset($index) ? $cm_texts[ $cm_lang ][$key][$index] : $cm_texts[ $cm_lang ][$key]);
    
    switch(count($vars))
    {
    case 1:
      return(sprintf($s, $vars[0]));
      break;
    case 2:
      return(sprintf($s, $vars[0], $vars[1]));
      break;
    case 3:
      return(sprintf($s, $vars[0], $vars[1], $vars[2]));
      break;
    case 4:
      return(sprintf($s, $vars[0], $vars[1], $vars[2], $vars[3]));
      break;
    default:
      return($s);
    }
  }
?>