<?php
/**
 * COmanage Registry CoOrgIdentityLink Model
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

class CoOrgIdentityLink extends AppModel {
  // Define class name for cake
  public $name = "CoOrgIdentityLink";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Org Identity Link is attached to one CO Person
    "CoPerson",
    // A CO Org Identity Link is attached to one Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoOrgIdentityLink.id";
  
  // Default ordering for find operations
  public $order = array("CoOrgIdentityLink.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
// Note that over the API the link must be deleted and recreated (there are
// other checks to prevent reassignment of links), but via the UI we do permit
// a guided reassignment of an org identity to a different CO Person.
        'unfreeze' => 'CO'
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
//        'unfreeze' => 'CO'
      )
    )
  );
  
  // If we're relinking an Org Identity, track the relevant info across callbacks
  private $relinkingOrgIdentity = null;
  
  /**
   * Execute logic after a save operation.
   *
   * @since  COmanage Registry v3.3.0
   * @param  boolean true if a new record was created (rather than update)
   * @param  array, the same passed into Model::save()
   * @return none
   */

  public function afterSave($created, $options = array()) {
    // If we are relinking an Org Identity associated with an OIS and a Pipeline,
    // we now need to execute the Pipeline on the target CO Person ID.
    if($this->relinkingOrgIdentity) {
      // Run the pipeline for the target CO Person ID. (CoPipeline::execute will
      // figure out which it is based on the CoOrgIdentityLink.)
      
      $orgIdentity = $this->relinkingOrgIdentity;
      $this->relinkingOrgIdentity = null;  // Clear this out so it doesn't somehow cause problems later
      
      try {
        $this->OrgIdentity
             ->Co
             ->CoPipeline
             ->execute($orgIdentity['OrgIdentitySourceRecord']['OrgIdentitySource']['co_pipeline_id'],
                       $orgIdentity['OrgIdentity']['id'],
                       SyncActionEnum::Relink,
                       // In PE we can do something similar to ChangelogBehavior to get the username
                       CakeSession::read('Auth.User.co_person_id'),
                       true,
                       $orgIdentity['OrgIdentitySourceRecord']['source_record'],
                       $orgIdentity['OrgIdentitySourceRecord']['id']);
        
        $this->_commit();
      }
      catch(Exception $e) {
        $this->_rollback();
        throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeSave($options = array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }
    
    // Make sure we don't already have a link for the specified targets.
    
    if(empty($this->data['CoOrgIdentityLink']['id'])) {
      $args = array();
      $args['conditions']['CoOrgIdentityLink.co_person_id'] = $this->data['CoOrgIdentityLink']['co_person_id'];
      $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $this->data['CoOrgIdentityLink']['org_identity_id'];
      $args['contain'] = false;
      
      $cnt = $this->find('count', $args);
      
      if($cnt > 0) {
        throw new RuntimeException(_txt('er.linked'));
      }
    } else {
      // If we're updating an existing link we need to check to see if the Org
      // Identity was created from an Org Identity Source attached to a Pipeline.
      // If so, we tell the Pipeline to unlink the CO Person here, and then in
      // afterSave we will link the new target CO Person.
      
      // Note if Org Identities are pooled then Org Identity Sources aren't
      // supported, so we won't need to do any of this.
      
      $args = array();
      $args['conditions']['OrgIdentity.id'] = $this->data['CoOrgIdentityLink']['org_identity_id'];
      $args['contain'] = array(
        // Until pooling goes away, an OrgIdentity could point to multiple CO People,
        // but Org Identity Sources are not supported when pooling is enabled so
        // we don't need to worry about it here.
        'CoOrgIdentityLink',
        'OrgIdentitySourceRecord' => array('OrgIdentitySource')
      );
      
      $orgIdentity = $this->OrgIdentity->find('first', $args);
      
      if(!empty($orgIdentity['OrgIdentitySourceRecord']['id'])
         // There must be an associated pipeline
         && !empty($orgIdentity['OrgIdentitySourceRecord']['OrgIdentitySource']['co_pipeline_id'])
         // and we must be changing the CO Person ID
         && ($this->data['CoOrgIdentityLink']['co_person_id']
             != $orgIdentity['CoOrgIdentityLink'][0]['co_person_id'])
         // but not the Org Identity
         && ($this->data['CoOrgIdentityLink']['org_identity_id']
             == $orgIdentity['OrgIdentity']['id'])) {
        $this->_begin();
        
        try {
          $this->OrgIdentity
               ->Co
               ->CoPipeline
               ->execute($orgIdentity['OrgIdentitySourceRecord']['OrgIdentitySource']['co_pipeline_id'],
                         $orgIdentity['OrgIdentity']['id'],
                         SyncActionEnum::Unlink,
                         // In PE we can do something similar to ChangelogBehavior to get the username
                         CakeSession::read('Auth.User.co_person_id'),
                         true,
                         $orgIdentity['OrgIdentitySourceRecord']['source_record'],
                         $orgIdentity['OrgIdentitySourceRecord']['id']);
          
          $this->relinkingOrgIdentity = $orgIdentity;
        }
        catch(Exception $e) {
          $this->_rollback();
          throw new RuntimeException($e->getMessage());
        }
      }
    }
    
    return true;
  }
}
