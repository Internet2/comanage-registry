<?php
/**
 * COmanage Registry Login Component
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
 * @since         COmanage Registry vTODO
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class LoginComponent extends Component {
    public $components = array("Session");

    public function process()
    {
      // At this point, Auth.User.username has been established by the Auth
      // Controller, but nothing else. We now populate the rest of the user's
      // session auth information.
      $u = $this->Session->read('Auth.User.username');

      if(!empty($u)) {
        // This is an Org Identity. Figure out which Org Identities this username
        // (identifier) is associated with. First, pull the identifiers.
        // We use $oargs here instead of $args because we may reuse this below
        $oargs = array();
        $oargs['joins'][0]['table'] = 'identifiers';
        $oargs['joins'][0]['alias'] = 'Identifier';
        $oargs['joins'][0]['type'] = 'INNER';
        $oargs['joins'][0]['conditions'][0] = 'OrgIdentity.id=Identifier.org_identity_id';
        $oargs['conditions']['Identifier.identifier'] = $u;
        $oargs['conditions']['Identifier.login'] = true;
        // Join on identifiers that aren't deleted (including if they have no status)
        $oargs['conditions']['OR'][] = 'Identifier.status IS NULL';
        $oargs['conditions']['OR'][]['Identifier.status <>'] = StatusEnum::Deleted;
        // As of v2.0.0, OrgIdentities have validity dates, so only accept valid dates (if specified)
        // Through the magic of containable behaviors, we can get all the associated
        $oargs['conditions']['AND'][] = array(
          'OR' => array(
            'OrgIdentity.valid_from IS NULL',
            'OrgIdentity.valid_from < ' => date('Y-m-d H:i:s', time())
          )
        );
        $oargs['conditions']['AND'][] = array(
          'OR' => array(
            'OrgIdentity.valid_through IS NULL',
            'OrgIdentity.valid_through > ' => date('Y-m-d H:i:s', time())
          )
        );
        // data we need in one clever find
        $oargs['contain'][] = 'PrimaryName';
        $oargs['contain'][] = 'Identifier';
        $oargs['contain']['CoOrgIdentityLink']['CoPerson'][0] = 'Co';
        $oargs['contain']['CoOrgIdentityLink']['CoPerson'][1] = 'CoPersonRole';
        $oargs['contain']['CoOrgIdentityLink']['CoPerson']['CoGroupMember'] = 'CoGroup';

        $OrgIdentity = ClassRegistry::init("OrgIdentity");
        $orgIdentities = $OrgIdentity->find('all', $oargs);

        // Grab the org IDs and CO information
        $orgs = array();
        $cos = array();

        // Determine if we are collecting authoritative attributes from $ENV
        // (the only support mechanism at the moment). If so, this will be an array
        // of those value. If not, false.
        $CmpEnrollmentConfiguration = ClassRegistry::init("CmpEnrollmentConfiguration");
        $envValues = $CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();

        if(!empty($envValues)) {
          // Walk through the Org Identities and update any configured/collected attributes.
          // Track if we made any changes.
          $orgIdentityChanged = false;

          foreach($orgIdentities as $o) {
            if(!empty($o['Identifier'])) {
              // Does this org identity's identifier match the authenticated identifier?

              foreach($o['Identifier'] as $i) {
                if(isset($i['login']) && $i['login']
                   && !empty($i['status']) && $i['status'] == StatusEnum::Active
                   && !empty($i['identifier'])
                   && $i['identifier'] == $u) {
                  // We have a match, possibly update associated attributes
                  $newOrgIdentity = $OrgIdentity->updateFromEnv($o['OrgIdentity']['id'], $envValues);

                  if(!empty($newOrgIdentity)) {
                    // Update our session store with the new values
                    $orgIdentityChanged = true;
                  }

                  // No need to walk through any other identifiers attached to this org identity
                  break;
                }
              }
            }
          }

          if($orgIdentityChanged) {
            // Simply reread the org identities... this is easier than trying to
            // collate the new identity into the old one. (We don't track all potentially
            // updated attributes in the session.)
            $orgIdentities = $OrgIdentity->find('all', $oargs);
          }
        }

        foreach($orgIdentities as $o) {
          $orgs[] = array(
            'org_id' => $o['OrgIdentity']['id'],
            'co_id' => $o['OrgIdentity']['co_id']
          );

          foreach($o['CoOrgIdentityLink'] as $l)
          {
            // If org identities are pooled, OrgIdentity:co_id will be null, so look at
            // the identity links to get the COs (via CO Person).

            $cos[ $l['CoPerson']['Co']['name'] ] = array(
              'co_id' => $l['CoPerson']['Co']['id'],
              'co_name' => $l['CoPerson']['Co']['name'],
              'co_person_id' => $l['co_person_id'],
              'co_person' => $l['CoPerson']
            );

            // And assemble the Group Memberships
            $params = array(
              'conditions' => array(
                'CoGroupMember.co_person_id' => $l['co_person_id']
              ),
              'contain' => false
            );
            $CoGroupMember = ClassRegistry::init("CoGroupMember");
            $CoGroup = ClassRegistry::init("CoGroup");
            $memberships = $CoGroupMember->find('all', $params);

            foreach($memberships as $m){
              $params = array(
                'conditions' => array(
                  'CoGroup.id' => $m['CoGroupMember']['co_group_id']
                )
              );
              $result = $CoGroup->find('first', $params);

              if(!empty($result)) {
                $group = $result['CoGroup'];

                $cos[ $l['CoPerson']['Co']['name'] ]['groups'][ $group['name'] ] = array(
                  'co_group_id' => $m['CoGroupMember']['co_group_id'],
                  'name' => $group['name'],
                  'member' => $m['CoGroupMember']['member'],
                  'owner' => $m['CoGroupMember']['owner']
                );
              }
            }
          }
        }

        $this->Session->write('Auth.User.org_identities', $orgs);
        $this->Session->write('Auth.User.cos', $cos);

        // Use the primary organizational name as the session name.
        if(isset($orgIdentities[0]['PrimaryName'])) {
          $this->Session->write('Auth.User.name', $orgIdentities[0]['PrimaryName']);
        }

        // Determine if there are any pending T&Cs
        foreach($cos as $co) {
          // First see if T&Cs are enforced at login for this CO
          $CoSetting = ClassRegistry::init("CoSetting");
          $CoTermsAndConditions = ClassRegistry::init("CoTermsAndConditions");
          if($CoSetting->getTAndCLoginMode($co['co_id']) == TAndCLoginModeEnum::RegistryLogin) {
            $pending = $CoTermsAndConditions->pending($co['co_person_id']);

            if(!empty($pending)) {
              // Store the pending T&C in the session so that beforeFilter() can check it.
              // This isn't ideal, but should be preferable to beforeFilter performing the
              // check before every action. It also means T&C are enforced once per login
              // rather than if the T&C change in the middle of a user's session.
              $this->Session->write('Auth.User.tandc.pending.' . $co['co_id'], $pending);
            }
          }
        }
        return true;
      }
      return false;
    }

    public function record()
    {
      $u = $this->Session->read('Auth.User.username');

      if(!empty($u)) {
        $AuthenticationEvent = ClassRegistry::init("AuthenticationEvent");
        $AuthenticationEvent->record($u, AuthenticationEventEnum::RegistryLogin, $_SERVER['REMOTE_ADDR']);
      }
    }
}