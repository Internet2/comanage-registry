<?php
/**
 * COmanage Registry Unix Cluster Event Listener
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
App::uses('CakeEventListener', 'Event');

class UnixClusterListener implements CakeEventListener {
  /**
   * Define our listener(s)
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of events and associated function names
   */

  public function implementedEvents() {
    return array(
      // For now, we skip all delete operations, and simply leave the UnixClusterAccount
      // object unmodified on delete of upstream objects
      // 'Model.afterDelete' => 'updateUnixClusterAccount',
      'Model.afterSave'   => 'updateUnixClusterAccount'
    );
  }
  
  /**
   * Update Unix Cluster Accounts based on updated attributes, if appropriate.
   *
   * @since  COmanage Registry v3.3.0
   * @param  CakeEvent $event Cake Event
   * @return boolean          True to continue the event flow
   */
  
  public function updateUnixClusterAccount(CakeEvent $event) {
    $subject = $event->subject();
    // We need to cache data from the event, since the above is apparently a
    // pointer or something, and doesn't necessarily persist as long as we need it
    $subjectData = $subject->data;
    $subjectName = $subject->name;
    
    // First see if this is a model we're interested in
    
    if($subjectName == 'Identifier'
       // We only care about the Primary Name
       || ($subjectName == 'Name'
           && isset($subjectData[ $subjectName ]['primary_name'])
           && $subjectData[ $subjectName ]['primary_name'])) {
      // Next look for a CO Person ID in the event data
      if(!empty($subjectData[ $subjectName ]['co_person_id'])) {
        // Now look for UnixClusterAccounts associated with this CO Person
        
        $UnixCluster = ClassRegistry::init('UnixCluster.UnixCluster');
        $UnixClusterAccount = ClassRegistry::init('UnixCluster.UnixClusterAccount');
        $HistoryRecord = ClassRegistry::init('HistoryRecord');
        
        $args = array();
        $args['conditions']['UnixClusterAccount.co_person_id'] = $subjectData[ $subjectName ]['co_person_id'];
        $args['conditions']['UnixClusterAccount.sync_mode'] = UnixClusterSyncEnum::Full;
        // For now, we don't look at UnixClusterAccount.status - we'll update attributes even if the account is suspended
        $args['contain'] = array(
          'UnixCluster' => array('Cluster'),
          'PrimaryCoGroup' => array('Identifier')
        );
        
        $accounts = $UnixClusterAccount->find('all', $args);
        
        if(!empty($accounts)) {
          foreach($accounts as $acct) {
            // We pass the whole UnixClusterAccount object rather than saveField
            // to avoid errors with UnixClusterAccount::beforeSave(). Unset the
            // metadata attributes.
            
            foreach(array('created', 'modified', 'unix_cluster_account_id',
                          'revision', 'deleted', 'actor_identifier') as $a) {
              unset($acct['UnixClusterAccount'][$a]);
            }
            
            $mods = array();
            $gmods = array();
            
            if($subjectName == 'Identifier') {
              // Update username and uid from the appropriate identifier type
              
              if($subjectData[ $subjectName ]['type'] == $acct['UnixCluster']['uid_type']
                 && $subjectData[ $subjectName ]['identifier'] != $acct['UnixClusterAccount']['uid']) {
                $mods['uid'] = array(
                  'old' => $acct['UnixClusterAccount']['uid'],
                  'new' => $subjectData[ $subjectName ]['identifier']
                );
                
                $acct['UnixClusterAccount']['uid'] = $subjectData[ $subjectName ]['identifier'];
                
                // If there is an existing Identifier of gid_type that matches the old uid
                // then we'll also update the Identifier.
                
                foreach($acct['PrimaryCoGroup']['Identifier'] as $id) {
                  if($id['type'] == $acct['UnixCluster']['gid_type']
                     && $id['identifier'] == $mods['uid']['old']) {
                    $gmods['gidIdentifier'] = array(
                      'old' => $mods['uid']['old'],
                      'new' => $mods['uid']['new']
                    );
                    
                    $UnixClusterAccount->PrimaryCoGroup->Identifier->clear();
                    $UnixClusterAccount->PrimaryCoGroup->Identifier->id = $id['id'];
                    
                    $UnixClusterAccount->PrimaryCoGroup->Identifier->saveField('identifier', $mods['uid']['new']);
                    
                    // There should be only one identifier of this type with this value
                    break;
                  }
                }
              }
              
              if($subjectData[ $subjectName ]['type'] == $acct['UnixCluster']['username_type']
                 && $subjectData[ $subjectName ]['identifier'] != $acct['UnixClusterAccount']['username']) {
                $mods['username'] = array(
                  'old' => $acct['UnixClusterAccount']['username'],
                  'new' => $subjectData[ $subjectName ]['identifier']
                );
                
                $acct['UnixClusterAccount']['username'] = $subjectData[ $subjectName ]['identifier'];
                
                // Also reconstruct the home directory.
                
                $acct['UnixClusterAccount']['home_directory'] = $UnixCluster->calculateHomeDirectory(
                  $acct['UnixCluster'],
                  $acct['UnixClusterAccount']['username']
                );
                
                // Rename the primary group, but only if the current name matches
                // the old default name. (ie: If we're using a common default group
                // or if the group has been renamed, we don't rename it here.)
                
                if(!empty($acct['PrimaryCoGroup']['name'])
                   && ($acct['PrimaryCoGroup']['name'] == _txt('pl.unixcluster.fd.co_group_id.new.name', array($mods['username']['old'])))) {
                  $UnixClusterAccount->PrimaryCoGroup->clear();
                  $UnixClusterAccount->PrimaryCoGroup->id = $acct['PrimaryCoGroup']['id'];
                  
                  $UnixClusterAccount->PrimaryCoGroup->saveField('name', _txt('pl.unixcluster.fd.co_group_id.new.name', array($mods['username']['new'])));
                  
                  $gmods['name'] = array(
                    'old' => _txt('pl.unixcluster.fd.co_group_id.new.name', array($mods['username']['old'])),
                    'new' => _txt('pl.unixcluster.fd.co_group_id.new.name', array($mods['username']['new']))
                  );
                  
                  // Maybe also update the Group description
                  
                  if(!empty($acct['PrimaryCoGroup']['description'])
                     && ($acct['PrimaryCoGroup']['description'] == _txt('pl.unixcluster.fd.co_group_id.new.desc', array($mods['username']['old'])))) {
                    $UnixClusterAccount->PrimaryCoGroup->saveField('description', _txt('pl.unixcluster.fd.co_group_id.new.desc', array($mods['username']['new'])));
                    
                    $gmods['description'] = array(
                      'old' => _txt('pl.unixcluster.fd.co_group_id.new.desc', array($mods['username']['old'])),
                      'new' => _txt('pl.unixcluster.fd.co_group_id.new.desc', array($mods['username']['new']))
                    );
                  }
                  
                  // Since we renamed the group, if there is an existing Identifier
                  // of groupname_type that matches the old name, then we'll also
                  // update the Identifier.
                  
                  foreach($acct['PrimaryCoGroup']['Identifier'] as $id) {
                    if($id['type'] == $acct['UnixCluster']['groupname_type']
                       && $id['identifier'] == $mods['username']['old']) {
                      $gmods['groupnameIdentifier'] = array(
                        'old' => $mods['username']['old'],
                        'new' => $mods['username']['new']
                      );
                      
                      $UnixClusterAccount->PrimaryCoGroup->Identifier->clear();
                      $UnixClusterAccount->PrimaryCoGroup->Identifier->id = $id['id'];
                      
                      $UnixClusterAccount->PrimaryCoGroup->Identifier->saveField('identifier', $mods['username']['new']);
                      
                      // There should be only one identifier of this type with this value
                      break;
                    }
                  }
                }
              }
              
              if(!empty($gmods)) {
                $cstr = "";
                
                foreach($gmods as $field => $vs) {
                  $cstr .= ($cstr == "" ? "" : ";") . $field . ": " . $vs['old'] . " > " . $vs['new'];
                }
                
                $HistoryRecord->record($acct['UnixClusterAccount']['co_person_id'],
                                       null,
                                       null,
                                       // Grabbing the $actorCoPersonId is a bit tricky in a listener,
                                       // in PE we have a slightly complicated mechanism for this
                                       // (see eg ChangelogEventListener). For now, we just leave it blank
                                       // on the theory that nearby history context (whoever changed the
                                       // main attribute) will have the actor logged.
                                       null,
                                       ActionEnum::CoGroupEdited,
                                       _txt('rs.edited-a4', array($acct['UnixCluster']['Cluster']['description'], $cstr)),
                                       $acct['PrimaryCoGroup']['id']);
              }
            } elseif($subjectName == 'Name') {
              // Update gecos from primary name
              
              $gecos = $UnixCluster->calculateGecos($subjectData[ $subjectName ]);
              
              if($gecos != $acct['UnixClusterAccount']['gecos']) {
                $mods['gecos'] = array(
                  'old' => $acct['UnixClusterAccount']['gecos'],
                  'new' => $gecos
                );
                
                $acct['UnixClusterAccount']['gecos'] = $gecos;
              }
            }
            
            if(!empty($mods)) {
              $UnixClusterAccount->clear();
              $UnixClusterAccount->save($acct['UnixClusterAccount']);
              
              // Create a history record
              $cstr = "";
              
              foreach($mods as $field => $vs) {
                $cstr .= ($cstr == "" ? "" : ";") . $field . ": " . $vs['old'] . " > " . $vs['new'];
              }
              
              $HistoryRecord->record($subjectData[ $subjectName ]['co_person_id'],
                                     null,
                                     null,
                                     // Grabbing the $actorCoPersonId is a bit tricky in a listener,
                                     // in PE we have a slightly complicated mechanism for this
                                     // (see eg ChangelogEventListener). For now, we just leave it blank
                                     // on the theory that nearby history context (whoever changed the
                                     // main attribute) will have the actor logged.
                                     null,
                                     ActionEnum::ClusterAccountAutoEdited,
                                     _txt('rs.edited-a4', array($acct['UnixCluster']['Cluster']['description'], $cstr)));
            }
          }
        }
        
        // Note if the username and/or user ID are already in use, UnixClusterAccount
        // will reject the save in beforeSave.
        
        // We don't explicitly trigger provisioning since most likely that's going to
        // happen as part o the Identifier or Name saving.
      }
    }
    
    // Return true to keep the event flowing
    return true;
  }
}