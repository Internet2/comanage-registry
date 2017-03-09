<?php
/**
 * COmanage Registry CO Expiration Policy Model
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
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class CoExpirationPolicy extends AppModel {
  // Define class name for cake
  public $name = "CoExpirationPolicy";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ActCou" => array(
      'className' => 'Cou',
      'foreignKey' => 'act_cou_id'
    ),
    "ActNotifyCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'act_notify_co_group_id'
    ),
    "ActNotifyMessageTemplate" => array(
      'className' => 'CoMessageTemplate',
      'foreignKey' => 'act_notification_template_id'
    ),
    "Co",
    "CondCou" => array(
      'className' => 'Cou',
      'foreignKey' => 'cond_cou_id'
    )
  );
  
  public $hasMany = array(
    "CoExpirationCount" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'cond_cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cond_affiliation' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'CoPersonRole.affiliation',
                              'default' => array(AffiliationEnum::Faculty,
                                                 AffiliationEnum::Student,
                                                 AffiliationEnum::Staff,
                                                 AffiliationEnum::Alum,
                                                 AffiliationEnum::Member,
                                                 AffiliationEnum::Affiliate,
                                                 AffiliationEnum::Employee,
                                                 AffiliationEnum::LibraryWalkIn))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'cond_before_expirty' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cond_after_expirty' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cond_count' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cond_status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Approved,
                                        StatusEnum::Declined,
                                        StatusEnum::Deleted,
                                        StatusEnum::Denied,
                                        StatusEnum::Duplicate,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::Expired,
                                        StatusEnum::Invited,
                                        StatusEnum::Pending,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::PendingConfirmation,
                                        StatusEnum::Suspended)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'cond_sponsor_invalid' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_affiliation' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'CoPersonRole.affiliation',
                              'default' => array(AffiliationEnum::Faculty,
                                                 AffiliationEnum::Student,
                                                 AffiliationEnum::Staff,
                                                 AffiliationEnum::Alum,
                                                 AffiliationEnum::Member,
                                                 AffiliationEnum::Affiliate,
                                                 AffiliationEnum::Employee,
                                                 AffiliationEnum::LibraryWalkIn))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'act_clear_expiry' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notify_co_admin' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notify_cou_admin' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notify_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notify_co_person' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notify_sponsor' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notification_subject' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notification_body' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_notification_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'act_status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Approved,
                                        StatusEnum::Declined,
                                        StatusEnum::Deleted,
                                        StatusEnum::Denied,
                                        StatusEnum::Duplicate,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::Expired,
                                        StatusEnum::Invited,
                                        StatusEnum::Pending,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::PendingConfirmation,
                                        StatusEnum::Suspended)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  /**
   * Execute expiration policies for the specified CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  integer $coId CO ID
   * @param  AppShell $appShell If set, log progress via this provided AppShell
   * @return boolean True on success
   */
  
  public function executePolicies($coId, $appShell=null) {
    // Select all policies where status=active
    
    $args = array();
    $args['conditions']['CoExpirationPolicy.co_id'] = $coId;
    $args['conditions']['CoExpirationPolicy.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('ActCou', 'ActNotifyMessageTemplate');
    
    $policies = $this->find('all', $args);
    
    if(!empty($policies)) {
      foreach($policies as $p) {
        // First, retrieve matching CO Person Role records
        
        $args = array();
        if(!empty($p['CoExpirationPolicy']['cond_affiliation'])) {
          $args['conditions']['CoPersonRole.affiliation'] = $p['CoExpirationPolicy']['cond_affiliation'];
        }
        // Note only one of after and before is permitted
        // We use strlen because we want the literal string 0 but not an empty string.
        if(strlen($p['CoExpirationPolicy']['cond_after_expiry']) > 0) {
          // Imagine today is June 10 and cond_after_expiry is 7 days (ie: give someone a week grace
          // period after expiration). What we want are valid through dates from June 3 and earlier.
          $args['conditions']['CoPersonRole.valid_through <'] =
            date('Y-m-d H:i:s', strtotime("-" . $p['CoExpirationPolicy']['cond_after_expiry'] . " days"));
        } elseif(strlen($p['CoExpirationPolicy']['cond_before_expiry']) > 0) {
          // Imagine today is June 5 and cond_before_expiry is 3 days (ie: notify someone 3 days
          // before expiration). What we want are valid_through dates between now (6/5) and 3 days
          // from now (6/8).
          $args['conditions']['CoPersonRole.valid_through BETWEEN ? AND ?'] =
            array(date('Y-m-d H:i:s', time()),
                  date('Y-m-d H:i:s', strtotime("+" . $p['CoExpirationPolicy']['cond_before_expiry'] . " days")));
        }
        if(!empty($p['CoExpirationPolicy']['cond_cou_id'])) {
          $args['conditions']['CoPersonRole.cou_id'] = $p['CoExpirationPolicy']['cond_cou_id'];
        }
        if(!empty($p['CoExpirationPolicy']['cond_status'])) {
          $args['conditions']['CoPersonRole.status'] = $p['CoExpirationPolicy']['cond_status'];
        }
        if(isset($p['CoExpirationPolicy']['cond_sponsor_invalid'])
           && $p['CoExpirationPolicy']['cond_sponsor_invalid']) {
          // Here we make sure a sponsor was specified and then check to see if the
          // sponsor is not active
          $args['conditions'][] = 'CoPersonRole.sponsor_co_person_id IS NOT NULL';
          $args['joins'][0]['table'] = 'co_people';
          $args['joins'][0]['alias'] = 'SponsorCoPerson';
          $args['joins'][0]['type'] = 'INNER';
          $args['joins'][0]['conditions'][0] = 'SponsorCoPerson.id=CoPersonRole.sponsor_co_person_id';
          $args['conditions']['SponsorCoPerson.status !='] = StatusEnum::Active;
        }
        // Restrict matching records to the requested CO
        $args['conditions']['CoPerson.co_id'] = $coId;
        $args['contain']['CoPerson'] = array('PrimaryName', 'Identifier');
        $args['contain']['SponsorCoPerson'] = 'PrimaryName';
        $args['contain'][] = 'Cou';
        
        $roles = $this->Co->CoPerson->CoPersonRole->find('all', $args);
        
        if(!empty($roles)) {
          foreach($roles as $role) {
            if(!empty($p['CoExpirationPolicy']['cond_count'])) {
              // Make sure we haven't already sent the specified number of notifications.
              // It's a bit tricky to do this as part of the find, so we do it here.
              
              $cnt = $this->CoExpirationCount->count($p['CoExpirationPolicy']['id'],
                                                     $role['CoPersonRole']['id']);
              
              if($cnt >= $p['CoExpirationPolicy']['cond_count']) {
                // Count reached, just move on to the next role
                continue;
              } else {
                // While we're here increment the count
                $this->CoExpirationCount->increment($p['CoExpirationPolicy']['id'],
                                                    $role['CoPersonRole']['id']);
              }
            }
            
            // Log that this expiration policy matched
            
            if($appShell) {
              $appShell->out(generateCn($role['CoPerson']['PrimaryName'])
                             . " (" . $role['CoPersonRole']['co_person_id']
                             . "/" . $role['CoPersonRole']['id'] . "): "
                             . _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                         $p['CoExpirationPolicy']['id'])));
            }
            
            try {
              $this->Co->CoPerson->HistoryRecord->record($role['CoPersonRole']['co_person_id'],
                                                         $role['CoPersonRole']['id'],
                                                         null,
                                                         null,
                                                         ActionEnum::ExpirationPolicyMatched,
                                                         _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                                                   $p['CoExpirationPolicy']['id'])));
            }
            catch(Exception $e) {
              if($appShell) {
                $appShell->out($e->getMessage(), 1, Shell::QUIET);
              }
            }
            
            // Execute all defined actions
            
            $this->Co->CoPerson->CoPersonRole->id = $role['CoPersonRole']['id'];
            
            // Track changes we make for purposes of constructing history records
            $newRoleData = array();
            $oldRoleData = array();
            $fieldList = array();
            
            if(!empty($p['CoExpirationPolicy']['act_affiliation'])) {
              // Update affiliation
              
              $newRoleData['CoPersonRole']['affiliation'] = $p['CoExpirationPolicy']['act_affiliation'];
              $oldRoleData['CoPersonRole']['affiliation'] = $role['CoPersonRole']['affiliation'];
              $fieldList[] = 'affiliation';
            }
            
            if(isset($p['CoExpirationPolicy']['act_clear_expiry'])
               && $p['CoExpirationPolicy']['act_clear_expiry']) {
              // Clear expiration
              
              $newRoleData['CoPersonRole']['valid_through'] = null;
              $oldRoleData['CoPersonRole']['valid_through'] = $role['CoPersonRole']['valid_through'];
              $fieldList[] = 'valid_through';
            }
            
            if(!empty($p['CoExpirationPolicy']['act_cou_id'])) {
              // Update COU
              
              $newRoleData['CoPersonRole']['cou_id'] = $p['CoExpirationPolicy']['act_cou_id'];
              $oldRoleData['CoPersonRole']['cou_id'] = $role['CoPersonRole']['cou_id'];
              $fieldList[] = 'cou_id';
            }
            
            if(!empty($p['CoExpirationPolicy']['act_status'])) {
              // Update status
              
              $newRoleData['CoPersonRole']['status'] = $p['CoExpirationPolicy']['act_status'];
              $oldRoleData['CoPersonRole']['status'] = $role['CoPersonRole']['status'];
              $fieldList[] = 'status';
              
              // Note recalculation of person status happens as part of CoPersonRole::afterSave.
            }
            
            // Save changes, if any
            
            if(!empty($fieldList)) {
              $this->Co->CoPerson->CoPersonRole->save($newRoleData, true, $fieldList);
            }
            
            // Before we go on to notifications, record history if appropriate
            
            if(!empty($newRoleData) || !empty($oldRoleData)) {
              try {
                $ctxt = $this->Co->CoPerson->CoPersonRole->changesToString($newRoleData,
                                                                           $oldRoleData,
                                                                           $coId);
                
                if($appShell) {
                  $appShell->out('+ ' . $ctxt);
                }
                
                $this->Co->CoPerson->HistoryRecord->record($role['CoPersonRole']['co_person_id'],
                                                           $role['CoPersonRole']['id'],
                                                           null,
                                                           null,
                                                           ActionEnum::CoPersonRoleEditedExpiration,
                                                           _txt('rs.xp.action', array($p['CoExpirationPolicy']['description'],
                                                                                      $p['CoExpirationPolicy']['id'],
                                                                                      $ctxt)));
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
            
            // We have a bunch of substitutions to support, so process the template
            // here. In addition, we'll also support the standard substitutions (though ACTOR_NAME
            // probably doesn't make much sense) when processTemplate is called a second
            // time by CoNotification::register().
            
            // Prep date
            
            $expiryDays = null;
            
            if(!empty($role['CoPersonRole']['valid_through'])) {
              $expdate = new DateTime($role['CoPersonRole']['valid_through']);
              $nowdate = new DateTime();
              
              $timediff = $expdate->diff($nowdate);
              $expiryDays = $timediff->days;   // On PHP < 5.4, this could be -99999 on error
              // $expiryDays seems to always be positive regardless of order
            }
            
            $substitutions = array(
              'ORIG_AFFIL'        => $role['CoPersonRole']['affiliation'],
              'NEW_AFFIL'         => (!empty($newRoleData['CoPersonRole']['affiliation'])
                                      ? $newRoleData['CoPersonRole']['affiliation']
                                      // No change, use original value
                                      : $role['CoPersonRole']['affiliation']),
              'CO_PERSON'         => (!empty($role['CoPerson']['PrimaryName'])
                                      ? generateCn($role['CoPerson']['PrimaryName'])
                                      : null),
              'ORIG_COU'          => (!empty($role['Cou']['name'])
                                      ? $role['Cou']['name']
                                      : null),
              'NEW_COU'           => (!empty($newRoleData['CoPersonRole']['cou_id'])
                                      && !empty($p['ActCou']['name'])
                                      ? $p['ActCou']['name']
                                      // No change, use original value
                                      : (!empty($role['Cou']['name'])
                                         ? $role['Cou']['name']
                                         : null)),
              'ORIG_STATUS'       => _txt('en.status', null, $role['CoPersonRole']['status']),
              'NEW_STATUS'        => (!empty($newRoleData['CoPersonRole']['status'])
                                      ? _txt('en.status', null, $newRoleData['CoPersonRole']['status'])
                                      // No change, use original value
                                      : _txt('en.status', null, $role['CoPersonRole']['status'])),
              'DAYS_SINCE_EXPIRY' => ($expiryDays !== null && ($nowdate >= $expdate)
                                      ? $expiryDays
                                      : null),
              'DAYS_TO_EXPIRY'    => ($expiryDays !== null && ($expdate > $nowdate)
                                      ? $expiryDays
                                      : null),
              'POLICY_DESC'       => $p['CoExpirationPolicy']['description'],
              'SPONSOR'           => (!empty($role['SponsorCoPerson']['PrimaryName'])
                                      ? generateCn($role['SponsorCoPerson']['PrimaryName'])
                                      : null),
              'TITLE'             => $role['CoPersonRole']['title'],
              'VALID_FROM'        => $role['CoPersonRole']['valid_from'],
              'VALID_THROUGH'     => $role['CoPersonRole']['valid_through'],
            );
            
            $subject = null;
            $body = null;
            
            if(!empty($p['ActNotifyMessageTemplate']['id'])) {
              $subject = processTemplate($p['ActNotifyMessageTemplate']['message_subject'],
                                         $substitutions,
                                         $role['CoPerson']['Identifier']);
              $body = processTemplate($p['ActNotifyMessageTemplate']['message_body'],
                                      $substitutions,
                                      $role['CoPerson']['Identifier']);
            } else {
              $subject = processTemplate($p['CoExpirationPolicy']['act_notification_subject'],
                                         $substitutions,
                                         $role['CoPerson']['Identifier']);
              $body = processTemplate($p['CoExpirationPolicy']['act_notification_body'],
                                      $substitutions,
                                      $role['CoPerson']['Identifier']);
            }
            
            if(isset($p['CoExpirationPolicy']['act_notify_co_admin'])
               && $p['CoExpirationPolicy']['act_notify_co_admin']) {
              // Just pull group ID and register notification
              
              try {
                $cogroupid = $this->Co->CoGroup->adminCoGroupId($coId);
                
                $this->Co
                     ->CoGroup
                     ->CoNotificationRecipientGroup
                     ->register($role['CoPersonRole']['co_person_id'],
                                null,
                                null,
                                'cogroup',
                                $cogroupid,
                                ActionEnum::ExpirationPolicyMatched,
                                _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                          $p['CoExpirationPolicy']['id'])),
                                array(
                                  // XXX Not really clear this is the right source, but there's not a clear alternate
                                  // Should we create a log of expirations that are fired off? (seems redundant vs history_records)
                                  'controller' => 'co_person_roles',
                                  'action'     => 'edit',
                                  'id'         => $role['CoPersonRole']['id']
                                ),
                                false,
                                null,
                                $subject,
                                $body);
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
            
            if(isset($p['CoExpirationPolicy']['act_notify_cou_admin'])
               && $p['CoExpirationPolicy']['act_notify_cou_admin']
               && !empty($role['CoPersonRole']['cou_id'])) {
              try {
                $cogroupid = $this->Co->CoGroup->adminCoGroupId($coId, $role['CoPersonRole']['cou_id']);
                
                $this->Co
                     ->CoGroup
                     ->CoNotificationRecipientGroup
                     ->register($role['CoPersonRole']['co_person_id'],
                                null,
                                null,
                                'cogroup',
                                $cogroupid,
                                ActionEnum::ExpirationPolicyMatched,
                                _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                          $p['CoExpirationPolicy']['id'])),
                                array(
                                  // XXX Not really clear this is the right source, but there's not a clear alternate
                                  // Should we create a log of expirations that are fired off? (seems redundant vs history_records)
                                  'controller' => 'co_person_roles',
                                  'action'     => 'edit',
                                  'id'         => $role['CoPersonRole']['id']
                                ),
                                false,
                                null,
                                $subject,
                                $body);
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
            
            if(!empty($p['CoExpirationPolicy']['act_notify_co_group_id'])) {
              try {
                $this->Co
                     ->CoGroup
                     ->CoNotificationRecipientGroup
                     ->register($role['CoPersonRole']['co_person_id'],
                                null,
                                null,
                                'cogroup',
                                $p['CoExpirationPolicy']['act_notify_co_group_id'],
                                ActionEnum::ExpirationPolicyMatched,
                                _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                          $p['CoExpirationPolicy']['id'])),
                                array(
                                  // XXX Not really clear this is the right source, but there's not a clear alternate
                                  // Should we create a log of expirations that are fired off? (seems redundant vs history_records)
                                  'controller' => 'co_person_roles',
                                  'action'     => 'edit',
                                  'id'         => $role['CoPersonRole']['id']
                                ),
                                false,
                                null,
                                $subject,
                                $body);
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
            
            if(isset($p['CoExpirationPolicy']['act_notify_co_person'])
               && $p['CoExpirationPolicy']['act_notify_co_person']) {
              try {
                $this->Co
                     ->CoGroup
                     ->CoNotificationRecipientGroup
                     ->register($role['CoPersonRole']['co_person_id'],
                                null,
                                null,
                                'coperson',
                                $role['CoPersonRole']['co_person_id'],
                                ActionEnum::ExpirationPolicyMatched,
                                _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                          $p['CoExpirationPolicy']['id'])),
                                array(
                                  // XXX Not really clear this is the right source, but there's not a clear alternate
                                  // Should we create a log of expirations that are fired off? (seems redundant vs history_records)
                                  'controller' => 'co_person_roles',
                                  'action'     => 'edit',
                                  'id'         => $role['CoPersonRole']['id']
                                ),
                                false,
                                null,
                                $subject,
                                $body);
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
            
            if(isset($p['CoExpirationPolicy']['act_notify_sponsor'])
               && $p['CoExpirationPolicy']['act_notify_sponsor']
               && !empty($role['CoPersonRole']['sponsor_co_person_id'])) {
              try {
                $this->Co
                     ->CoGroup
                     ->CoNotificationRecipientGroup
                     ->register($role['CoPersonRole']['sponsor_co_person_id'],
                                null,
                                null,
                                'coperson',
                                $role['CoPersonRole']['sponsor_co_person_id'],
                                ActionEnum::ExpirationPolicyMatched,
                                _txt('rs.xp.match', array($p['CoExpirationPolicy']['description'],
                                                          $p['CoExpirationPolicy']['id'])),
                                array(
                                  // XXX Not really clear this is the right source, but there's not a clear alternate
                                  // Should we create a log of expirations that are fired off? (seems redundant vs history_records)
                                  'controller' => 'co_person_roles',
                                  'action'     => 'edit',
                                  'id'         => $role['CoPersonRole']['id']
                                ),
                                false,
                                null,
                                $subject,
                                $body);
              }
              catch(Exception $e) {
                if($appShell) {
                  $appShell->out($e->getMessage(), 1, Shell::QUIET);
                }
              }
            }
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Check if a given extended type is in use by any Expiration Policy.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String Attribute, of the form Model.field
   * @param  String Name of attribute (any default or extended type may be specified)
   * @param  Integer CO ID
   * @return Boolean True if the extended type is in use, false otherwise
   */
  
  public function typeInUse($attribute, $attributeName, $coId) {
    // Note we are effectively overriding AppModel::typeInUse().
    
    // Inflect the model names
    $attr = explode('.', $attribute, 2);
    
    $mname = Inflector::underscore($attr[0]);
    
    if($attr[0] == 'CoPersonRole' && $attr[1] == 'affiliation') {
      // We need to check both conditions and actions
      
      $args = array();
      $args['conditions']['OR']['CoExpirationPolicy.act_affiliation'] = $attributeName;
      $args['conditions']['OR']['CoExpirationPolicy.cond_affiliation'] = $attributeName;
      $args['conditions']['CoExpirationPolicy.co_id'] = $coId;
      $args['contain'] = false;
      
      return (boolean)$this->find('count', $args);
    }
    // else nothing to do
    
    return false;
  }
}
