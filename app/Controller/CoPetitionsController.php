<?php
/**
 * COmanage Registry CO Petition Controller
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoPetitionsController extends StandardController {
  public $name = "CoPetitions";
  
  public $helpers = array('Time');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'modified' => 'asc'
    ),
    'contain' => array(
      'ApproverCoPerson' => 'Name',
      'EnrolleeCoPerson' => 'Name',
      'PetitionerCoPerson' => 'Name',
      'SponsorCoPerson' => 'Name'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'ApproverCoPerson' => 'Name',
    'EnrolleeCoPerson' => 'Name',
    'PetitionerCoPerson' => 'Name',
    'SponsorCoPerson' => 'Name',
    'CoPetitionHistoryRecord' => array(
      'ActorCoPerson' => array(
        'Name'
      )
    )
  );
  
  /**
   * Add a CO Petition.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v0.5
   * @throws RuntimeException
   */
  
  function add() {
    if(!$this->restful && $this->request->is('post')) {
      // We have to do a bunch of non-standard work here. We're passed a bunch of data
      // for other models, basically enough to create a Person/Role. We need to use
      // it to create appropriate records, then create a Petition record with the
      // appropriate entries.
      
      $orgIdentityID = null;
      $coPersonID = null;
      
      // Track whether or not our processing was successful.
      
      $fail = false;
      
      // Validate the data presented. Do this manually since enrollment flow rules may not match
      // up with model rules. Start by pulling the enrollment attributes configuration.
      
      $efAttrs = $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($this->enrollmentFlowID());
      
      // Set the view var here, in case the form validation/save fails and the page renders.
      // We do this here for two reasons: (1) we're about to dork with the validation rules
      // attached to each model to fake out validation, and in doing so will screw up the
      // calculations done by enrollmentFlowAttributes(), and (2) there's no reason to pull
      // all this from the database and redo all the calculations again.
      
      $this->set('co_enrollment_attributes', $efAttrs);
      
      // Obtain a list of enrollment flow attributes' required status for use later.
      
      $fArgs = array();
      $fArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $this->enrollmentFlowID();
      $fArgs['fields'] = array('CoEnrollmentAttribute.id', 'CoEnrollmentAttribute.required');
      $reqAttrs = $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $fArgs);
      
      // For each defined attribute, update the loaded model validation definition to match
      // the enrollment flow attribute definition. (The CoEnrollmentAttribute model has already
      // done the logic to figure out what validation should really be.) We do this so that
      // when we try saveAll later, the validation rules Cake checks match what the admin
      // configured.
      
      foreach($efAttrs as $efAttr) {
        // co_enrollment_attribute_id is a special attribute with no validation
        if($efAttr['field'] == 'co_enrollment_attribute_id') continue;
        
        // The model might be something like EnrolleeCoPersonRole or EnrolleeCoPersonRole.Name
        // or EnrolleeCoPersonRole.TelephoneNumber.0. Split and handle accordingly.
        
        $m = explode('.', $efAttr['model'], 3);
        
        if(count($m) == 1) {
          $this->CoPetition->$m[0]->validate[ $efAttr['field'] ] = $efAttr['validate'];
          
          $this->CoPetition->$m[0]->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
          $this->CoPetition->$m[0]->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
        } else {
          if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m[1])) {
            // Extended attributes require a bit more work. First, dynamically bind
            // the extended attribute to the model if we haven't already.
            
            if(!isset($this->CoPetition->EnrolleeCoPersonRole->$m[1])) {
              $bArgs = array();
              $bArgs['hasOne'][ $m[1] ] = array(
                'className' => $m[1],
                'dependent' => true
              );
              
              $this->CoPetition->EnrolleeCoPersonRole->bindModel($bArgs, false);
            }
            
            // Extended attributes generally won't have validate by Cake set since their models are
            // dynamically bound, so grabbing validation rules from $efAttr is a win.
          }
          
          $this->CoPetition->$m[0]->$m[1]->validate[ $efAttr['field'] ] = $efAttr['validate'];
          
          $this->CoPetition->$m[0]->$m[1]->validate[ $efAttr['field'] ]['required'] = $efAttr['required'];
          $this->CoPetition->$m[0]->$m[1]->validate[ $efAttr['field'] ]['allowEmpty'] = !$efAttr['required'];
        }
      }
      
      // Start a transaction
      
      $dbc = $this->CoPetition->getDataSource();
      $dbc->begin();
      
      // We need to manually construct an Org Identity, at least for now until
      // they're populated some other way (eg: SAML/LDAP). We'll need to add a
      // reconciliation hook at some point. Save this prior to saving CO Person
      // so Cake doesn't get confused with attaching Names (to the Org Identity
      // vs the CO Person).
      
      $orgData = array();
      
      $this->loadModel('CmpEnrollmentConfiguration');
      
      if($this->CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
        // Platform is configured to pull org identities from the form.
        
        $orgData['EnrolleeOrgIdentity'] = $this->request->data['EnrolleeOrgIdentity'];
        
        // Attach this org identity to this CO (if appropriate)
        
        if(!$this->CmpEnrollmentConfiguration->orgIdentitiesPooled())
          $orgData['EnrolleeOrgIdentity']['co_id'] = $this->cur_co['Co']['id'];
        
        // Everything we need is in $this->request->data['EnrolleeOrgIdentity'].
        // Filter the data to pull related models up a level and, if optional and
        // not provided, drop the model entirely to avoid validation errors.
        
        $orgData = $this->CoPetition->EnrolleeOrgIdentity->filterRelatedModel($orgData,
                                                                              $reqAttrs);
        
        // We don't fail immediately on error because we want to run validate on all
        // the data we save in the various saveAll() calls so the appropriate fields
        // are highlighted at once.
        
        if($this->CoPetition->EnrolleeOrgIdentity->saveAll($orgData)) {
          $orgIdentityID = $this->CoPetition->EnrolleeOrgIdentity->id;
        } else {
          $fail = true;
        }
      } else {
        // The Org Identity will need to be populated via some other way,
        // such as via attributes pulled during login.
        
        throw new RuntimeException("Not implemented");
      }
      
      // Next, populate a CO Person and CO Person Role, statuses = pending.
      
      $coData = array();
      $coData['EnrolleeCoPerson'] = $this->request->data['EnrolleeCoPerson'];
      $coData['EnrolleeCoPerson']['co_id'] = $this->cur_co['Co']['id'];
      $coData['EnrolleeCoPerson']['status'] = StatusEnum::PendingApproval;
      
      // Filter the data to pull related models up a level and, if optional and
      // not provided, drop the model entirely to avoid validation errors.
      
      $coData = $this->CoPetition->EnrolleeCoPerson->filterRelatedModel($coData,
                                                                        $reqAttrs);
      
      // We don't fail immediately on error because we want to run validate on all
      // the data we save in the various saveAll() calls so the appropriate fields
      // are highlighted at once.
      
      if($this->CoPetition->EnrolleeCoPerson->saveAll($coData)) {
        $coPersonID = $this->CoPetition->EnrolleeCoPerson->id;
      } else {
        $fail = true;
      }
      
      $coRoleData = array();
      $coRoleData['EnrolleeCoPersonRole'] = $this->request->data['EnrolleeCoPersonRole'];
      $coRoleData['EnrolleeCoPersonRole']['status'] = StatusEnum::PendingApproval;
      $coRoleData['EnrolleeCoPersonRole']['co_person_id'] = $coPersonID;
      
      // Filter the data to pull related models up a level and, if optional and
      // not provided, drop the model entirely to avoid validation errors.
      
      $coRoleData = $this->CoPetition->EnrolleeCoPersonRole->filterRelatedModel($coRoleData,
                                                                                $reqAttrs);
      
      foreach(array_keys($this->request->data['EnrolleeCoPersonRole']) as $m) {
        if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
          // Pull the data up a level. We don't do the same filtering here since
          // extended attributes are flat (ie: no related models).
          
          $coRoleData[$m] = $this->request->data['EnrolleeCoPersonRole'][$m];
          unset($coRoleData['EnrolleeCoPersonRole'][$m]);
        }
      }
      
      // We don't fail immediately on error because we want to run validate on all
      // the data we save in the various saveAll() calls so the appropriate fields
      // are highlighted at once.

      if($this->CoPetition->EnrolleeCoPersonRole->saveAll($coRoleData)) {
        $coPersonRoleID = $this->CoPetition->EnrolleeCoPersonRole->id;
      } else {
        // We need to fold any extended attribute validation errors into the CO Person Role
        // validation errors in order for FormHandler to be able to see them.
        
        foreach(array_keys($this->request->data['EnrolleeCoPersonRole']) as $m) {
          if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
            $f = $this->CoPetition->EnrolleeCoPersonRole->$m->invalidFields();
            
            if(!empty($f)) {
              $this->CoPetition->EnrolleeCoPersonRole->validationErrors[$m] = $f;
            }
          }
        }
        
        $fail = true;
      }
      
      // From here, if any save fails it's probably a coding error since there are no
      // form fields that need validation. (We're using data from above, for the most
      // part). As such, if $fail gets set to true at any point, we don't need to
      // continue save()ing data.
      
      // Create a CO Org Identity Link
      
      $coOrgLink = array();
      $coOrgLink['CoOrgIdentityLink']['org_identity_id'] = $orgIdentityID;
      $coOrgLink['CoOrgIdentityLink']['co_person_id'] = $coPersonID;
      
      if(!$this->CoPetition->EnrolleeCoPerson->CoOrgIdentityLink->save($coOrgLink)) {
        $fail = true;
      }
      
      $coPetitionID = null;
      
      if(!$fail) {
        // Assemble the Petition, status = pending. We have most of the identifiers
        // we need from the above saves.
        
        $coPetitionData = array();
        $coPetitionData['CoPetition']['co_enrollment_flow_id'] = $this->enrollmentFlowID();
        $coPetitionData['CoPetition']['co_id'] = $this->cur_co['Co']['id'];
        
        if(isset($coRoleData['EnrolleeCoPersonRole']['cou_id'])) {
          $coPetitionData['CoPetition']['cou_id'] = $coRoleData['EnrolleeCoPersonRole']['cou_id'];
        }
        
        $coPetitionData['CoPetition']['enrollee_org_identity_id'] = $orgIdentityID;
        $coPetitionData['CoPetition']['enrollee_co_person_id'] = $coPersonID;
        $coPetitionData['CoPetition']['enrollee_co_person_role_id'] = $coPersonRoleID;
        
        // Figure out the petitioner person ID. As of now, it is the authenticated
        // person completing the form. This could be NULL if a CMP admin who is not
        // a member of the CO initiates the petition.
        
        $petitioner = $this->Session->read('Auth.User.co_person_id');
        
        $coPetitionData['CoPetition']['petitioner_co_person_id'] = $petitioner;
        $coPetitionData['CoPetition']['status'] = StatusEnum::PendingApproval;
        
        if($this->CoPetition->save($coPetitionData)) {
          $coPetitionID = $this->CoPetition->id;
        } else {
          $fail = true;
        }
      }
      
      // Store a copy of the attributes in co_petition_attributes.
      
      if(!$fail) {
        // In order to do this, we need to walk through the various submitted attributes
        // and "flatten" them into a format suitable for this table. Start with org data.
        
        $petitionAttrs = array();
        
        // Pull a mapping of attributes to attribute IDs
        
        $mArgs = array();
        $mArgs['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $this->enrollmentFlowID();
        $mArgs['fields'] = array('CoEnrollmentAttribute.attribute', 'CoEnrollmentAttribute.id');
        $attrIDs = $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->find("list", $mArgs);
          
        if(isset($orgData['EnrolleeOrgIdentity'])) {
          foreach(array_keys($orgData['EnrolleeOrgIdentity']) as $a) {
            // We need to find the attribute ID for this attribute. If not found, we'll
            // skip it (since it's probably something like co_id that we don't need to
            // store here).
            
            if(isset($attrIDs['o:'.$a])
               && isset($orgData['EnrolleeOrgIdentity'][$a])
               && $orgData['EnrolleeOrgIdentity'][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $attrIDs['o:'.$a],
                'attribute' => $a,
                'value' => $orgData['EnrolleeOrgIdentity'][$a]
              );
            }
          }
          
          foreach(array_keys($orgData) as $m) {
            // Loop through the related models, which may or may not be hasMany.
            
            if($m == 'EnrolleeOrgIdentity')
              continue;
            
            if(isset($orgData[$m]['co_enrollment_attribute_id'])) {
              // hasOne
              
              foreach(array_keys($orgData[$m]) as $a) {
                if($a != 'co_enrollment_attribute_id'
                   && isset($orgData[$m][$a])
                   && $orgData[$m][$a] != '') {
                  $petitionAttrs['CoPetitionAttribute'][] = array(
                    'co_petition_id' => $coPetitionID,
                    'co_enrollment_attribute_id' => $orgData[$m]['co_enrollment_attribute_id'],
                    'attribute' => $a,
                    'value' => $orgData[$m][$a]
                  );                  
                }
              }
            } else {
              // hasMany
              
              foreach(array_keys($orgData[$m]) as $i) {
                foreach(array_keys($orgData[$m][$i]) as $a) {
                  if($a != 'co_enrollment_attribute_id'
                     && isset($orgData[$m][$i][$a])
                     && $orgData[$m][$i][$a] != '') {
                    $petitionAttrs['CoPetitionAttribute'][] = array(
                      'co_petition_id' => $coPetitionID,
                      'co_enrollment_attribute_id' => $orgData[$m][$i]['co_enrollment_attribute_id'],
                      'attribute' => $a,
                      'value' => $orgData[$m][$i][$a]
                    );                  
                  }
                }
              }
            }
          }
        }
        
        // CO Person doesn't currently have any direct attributes that we track.
        // Move on to related model attributes.
        
        foreach(array_keys($coData) as $m) {
          // Loop through the related models, which may or may not be hasMany.
          
          if($m == 'EnrolleeCoPerson')
            continue;
          
          if(isset($coData[$m]['co_enrollment_attribute_id'])) {
            // hasOne
            
            foreach(array_keys($coData[$m]) as $a) {
              if($a != 'co_enrollment_attribute_id'
                 && isset($coData[$m][$a])
                 && $coData[$m][$a] != '') {
                $petitionAttrs['CoPetitionAttribute'][] = array(
                  'co_petition_id' => $coPetitionID,
                  'co_enrollment_attribute_id' => $coData[$m]['co_enrollment_attribute_id'],
                  'attribute' => $a,
                  'value' => $coData[$m][$a]
                );                  
              }
            }
          } else {
            // hasMany
            
            foreach(array_keys($coData[$m]) as $i) {
              foreach(array_keys($coData[$m][$i]) as $a) {
                if($a != 'co_enrollment_attribute_id'
                   && isset($coData[$m][$i][$a])
                   && $coData[$m][$i][$a] != '') {
                  $petitionAttrs['CoPetitionAttribute'][] = array(
                    'co_petition_id' => $coPetitionID,
                    'co_enrollment_attribute_id' => $coData[$m][$i]['co_enrollment_attribute_id'],
                    'attribute' => $a,
                    'value' => $coData[$m][$i][$a]
                  );                  
                }
              }
            }
          }
        }
        
        // Finally, CO Person Role data
        
        if(isset($coRoleData['EnrolleeCoPersonRole'])) {
          foreach(array_keys($coRoleData['EnrolleeCoPersonRole']) as $a) {
            // We need to find the attribute ID for this attribute. If not found, we'll
            // skip it (since it's probably something like co_id that we don't need to
            // store here).
            
            if(isset($attrIDs['r:'.$a])
               && isset($coRoleData['EnrolleeCoPersonRole'][$a])
               && $coRoleData['EnrolleeCoPersonRole'][$a] != '') {
              $petitionAttrs['CoPetitionAttribute'][] = array(
                'co_petition_id' => $coPetitionID,
                'co_enrollment_attribute_id' => $attrIDs['r:'.$a],
                'attribute' => $a,
                'value' => $coRoleData['EnrolleeCoPersonRole'][$a]
              );
            }
          }
          
          foreach(array_keys($coRoleData) as $m) {
            // Loop through the related models, which may or may not be hasMany.
            
            if($m == 'EnrolleeCoPersonRole')
              continue;
            
            if(isset($coRoleData[$m]['co_enrollment_attribute_id'])) {
              // hasOne
              
              foreach(array_keys($coRoleData[$m]) as $a) {
                if($a != 'co_enrollment_attribute_id'
                   && isset($coRoleData[$m][$a])
                   && $coRoleData[$m][$a] != '') {
                  $petitionAttrs['CoPetitionAttribute'][] = array(
                    'co_petition_id' => $coPetitionID,
                    'co_enrollment_attribute_id' => $coRoleData[$m]['co_enrollment_attribute_id'],
                    'attribute' => $a,
                    'value' => $coRoleData[$m][$a]
                  );                  
                }
              }
            } elseif(preg_match('/Co[0-9]+PersonExtendedAttribute/', $m)) {
              // Extended Attribute
              
              foreach(array_keys($coRoleData[$m]) as $a) {
                // We need to find the attribute ID for this attribute.
                
                if(isset($attrIDs['x:'.$a])
                   && isset($coRoleData[$m][$a])
                   && $coRoleData[$m][$a] != '') {
                  $petitionAttrs['CoPetitionAttribute'][] = array(
                    'co_petition_id' => $coPetitionID,
                    'co_enrollment_attribute_id' => $attrIDs['x:'.$a],
                    'attribute' => $a,
                    'value' => $coRoleData[$m][$a]
                  );                  
                }
              }
            } else {
              // hasMany
              
              foreach(array_keys($coRoleData[$m]) as $i) {
                foreach(array_keys($coRoleData[$m][$i]) as $a) {
                  if($a != 'co_enrollment_attribute_id'
                     && isset($coRoleData[$m][$i][$a])
                     && $coRoleData[$m][$i][$a] != '') {
                    $petitionAttrs['CoPetitionAttribute'][] = array(
                      'co_petition_id' => $coPetitionID,
                      'co_enrollment_attribute_id' => $coRoleData[$m][$i]['co_enrollment_attribute_id'],
                      'attribute' => $a,
                      'value' => $coRoleData[$m][$i][$a]
                    );                  
                  }
                }
              }
            }
          }
        }
        
        // Finally, try to save. Note that saveMany doesn't expect the Model name as an array
        // component, unlike all the other saves.
        
        if(!$this->CoPetition->CoPetitionAttribute->saveMany($petitionAttrs['CoPetitionAttribute'])) {
          $fail = true;
        }
      }
      
      // Add a co_petition_history_record.
      
      if(!$fail) {
        try {
          $this->CoPetition->CoPetitionHistoryRecord->record($coPetitionID,
                                                             $petitioner,
                                                             PetitionActionEnum::Created);
        }
        catch(Exception $e) {
          $fail = false;
        }
      }
      
      if(!$fail) {
        $dbc->commit();
        
        $this->Session->setFlash(_txt('rs.pt.create'), '', array(), 'success');
        $this->performRedirect();
      } else {
        // Roll back and allow the form to re-render
        
        $this->Session->setFlash(_txt('er.fields'), '', array(), 'error');
        $dbc->rollback();
      }
    } else {
      // REST API gets standard behavior
      
      parent::add();
    }
  }
  
  /**
   * Approve a petition.
   * - precondition: $id must exist and be in 'Pending Approval' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   * @param  Integer Petition ID
   */
  
  function approve($id) {
    try {
      $this->CoPetition->updatePetition($id,
                                        StatusEnum::Approved,
                                        $this->Session->read('Auth.User.co_person_id'));
      
      $this->Session->setFlash(_txt('rs.pt.approve'), '', array(), 'success');
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->performRedirect();
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeFilter() {
    if(!$this->restful && ($this->action == 'add' || $this->action == 'edit')) {
      // Make sure we were given a valid enrollment flow
      
      $args['conditions']['CoEnrollmentFlow.id'] = $this->enrollmentFlowID();
      $found = $this->CoPetition->CoEnrollmentFlow->find('count', $args);
      
      if($found == 0) {
        $this->Session->setFlash(_txt('er.coef.unk'), '', array(), 'error');
      }
    }
    
    parent::beforeFilter();
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: If a CO must be specifed, a named parameter may be set.
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeRender() {
    if(!$this->restful) {
      // Set the enrollment flow ID to make it easier to carry forward through failed submissions
      $this->set('co_enrollment_flow_id', $this->enrollmentFlowID());
      
      if(($this->action == 'add' || $this->action == 'edit' || $this->action == 'view')
          && $this->request->is('get')) {
        // If we processed a post, this will have already been set.
        $this->set('co_enrollment_attributes',
                   $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($this->enrollmentFlowID()));
      }
      
      if(($this->action == 'edit' || $this->action == 'view')
          && $this->request->is('get')) {
        // This information is already embedded in $co_petitions, but it's easier for the
        // views to access it this way. Also, arguably $co_petitions needs some trimming
        // via containable.
        
        $vArgs = array();
        $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $this->CoPetition->id;
        $vArgs['fields'] = array(
          'CoPetitionAttribute.attribute',
          'CoPetitionAttribute.value',
          'CoPetitionAttribute.co_enrollment_attribute_id'
        );
        $vAttrs = $this->CoPetition->CoPetitionAttribute->find("list", $vArgs);
        
        $this->set('co_petition_attribute_values', $vAttrs);
      }
    }
    
    parent::beforeRender();
  }
  
  /**
   * Deny a petition.
   * - precondition: $id must exist and be in 'Pending Approval' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   * @param  Integer Petition ID
   */
  
  function deny($id) {
    try {
      $this->CoPetition->updatePetition($id,
                                        StatusEnum::Denied,
                                        $this->Session->read('Auth.User.co_person_id'));
      
      $this->Session->setFlash(_txt('rs.pt.deny'), '', array(), 'success');
    }
    catch (Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->performRedirect();
  }
  
  /**
   * Determine the requested Enrollment Flow ID.
   * - precondition: An enrollment flow ID should be specified as a named query parameter or in form data.
   *
   * @since  COmanage Registry v0.5
   * @return Integer CO Enrollment Flow ID if found, or -1 otherwise
   */
  
  function enrollmentFlowID() {
    if(isset($this->request->params['named']['coef']))
      return($this->request->params['named']['coef']);
    elseif(isset($this->request->data['CoPetition']['co_enrollment_flow_id']))
      return($this->request->data['CoPetition']['co_enrollment_flow_id']);
    
    return(-1);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.5
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Petition?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Approve a CO Petition?
    $p['approve'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    $p['deny'] = $p['approve'];
    
    // Delete an existing CO Petition?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Edit an existing CO Petition?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Match against existing CO People?
    // Note this same permission exists in CO People
    $p['match'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // View all existing CO Petitions?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
          
    // View an existing CO Petition?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   */
  
  function performRedirect() {
    if($this->action == 'add') {
      // After submission on add, we go back to CO People
      
      $this->redirect(array(
        'controller' => 'co_people',
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    } else {
      parent::performRedirect();
    }
  }
}
