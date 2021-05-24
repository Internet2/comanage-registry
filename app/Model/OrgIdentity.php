<?php
/**
 * COmanage Registry OrgIdentity Model
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

class OrgIdentity extends AppModel {
  // Define class name for cake
  public $name = "OrgIdentity";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $hasOne = array(
    // An Org Identity may have one related source record
    "OrgIdentitySourceRecord" => array(
      'dependent'  => true
    ),
    // An Org Identity has one Primary Name, which is a pointer to a Name
    "PrimaryName" => array(
      'className'  => 'Name',
      'conditions' => array('PrimaryName.primary_name' => true),
      'dependent'  => false,
      'foreignKey' => 'org_identity_id'
    ),
    // A CO Person and associated models created from a Pipeline have Source Org Identities
    "PipelineCoPersonRole" => array(
      'className'  => 'CoPersonRole',
      'foreignKey' => 'source_org_identity_id'
    )
  );
  
  public $hasMany = array(
    // A person can have one or more address
    "Address" => array('dependent' => true),
    // An Org Identity can be attached to one or more CO Person
    // The current design requires all links to be dropped manually
    "AdHocAttribute" => array('dependent' => true),
    "CoOrgIdentityLink" => array('dependent' => false), 
    // A person can have various roles for a petition
    "CoPetition" => array(
      // Because a CO Petition is primarily designed to create a CO Person,
      // we only allow CoPerson to cascade delete to a CO Petition.
      'dependent' => false,
      'foreignKey' => 'enrollee_org_identity_id'
    ),
    "ArchivedCoPetition" => array(
      'className' => 'CoPetition',
      'foreignKey' => 'archived_org_identity_id'
    ),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // It's probably not right to delete history records, but generally org identities shouldn't be deleted
    "HistoryRecord" => array('dependent' => true),
    // A person can have many identifiers within an organization
    "Identifier" => array('dependent' => true),
    "Name" => array('dependent' => true),
    "PipelineCoGroupMember" => array(
      'className'  => 'CoGroupMember',
      'foreignKey' => 'source_org_identity_id'
    ),
    // A person can have one or more telephone numbers
    "TelephoneNumber" => array('dependent' => true),
    // A person can have one or more URL
    "Url" => array('dependent' => true)
  );

  public $belongsTo = array(
    // An Org Identity may belong to a CO, if not pooled
    "Co"
  );
  
  // Default display field for cake generated views
  public $displayField = "PrimaryName.family";
  
// XXX CO-296
  // Default ordering for find operations
//  public $order = array("Name.family", "Name.given");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(OrgIdentityStatusEnum::Removed,
                                        OrgIdentityStatusEnum::Synced)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'date_of_birth' => array(
      'content' => array(
        'rule' => array('date'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'affiliation' => array(
      'content' => array(
        'rule' => array('inList', array(AffiliationEnum::Faculty,
                                        AffiliationEnum::Student,
                                        AffiliationEnum::Staff,
                                        AffiliationEnum::Alum,
                                        AffiliationEnum::Member,
                                        AffiliationEnum::Affiliate,
                                        AffiliationEnum::Employee,
                                        AffiliationEnum::LibraryWalkIn)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'o' => array(
      'content' => array(
        // Note we perform additional checks in beforeSave, see that function for details
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
/*    'organization_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),*/
    'ou' => array(
      'content' => array(
        // Note we perform additional checks in beforeSave, see that function for details
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'title' => array(
      'content' => array(
        // Note we perform additional checks in beforeSave, see that function for details
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'precedes' => array(
        'rule' => array('validateTimestampRange', "valid_through", "<"),
      ),
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'follows' => array(
        'rule' => array("validateTimestampRange", "valid_from", ">"),
      ),
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'affiliation' => 'en.co_person_role.affiliation'
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function beforeSave($options = array()) {
    // Verify the Attribute Enumeration values for issuing_authority, if any.
    // Because the logic is more complicated than the Cake 2 validation framework
    // can handle, we do it here where we (generally) have full access to the record.
    // Mostly this is a sanity check in case someone tries to bypass the UI, since
    // ordinarily it shouldn't be possible to send an unpermitted value.
    
    // On saveField, we'll only have id. On all other actions, we'll have co_id.
    $coId = null;
    
    if(!empty($this->data[$this->alias]['co_id'])) {
      $coId = $this->data[$this->alias]['co_id'];
    } elseif(!empty($this->data[$this->alias]['id'])) {
      $coId = $this->findCoForRecord($this->data[$this->alias]['id']);
    }
    
    if($coId) {
      foreach(array('o', 'ou', 'title') as $a) {
        if(!empty($this->data[$this->alias][$a])) {
          $this->validateEnumeration($coId,
                                     'OrgIdentity.'.$a, 
                                     $this->data[$this->alias][$a]);
        }
      }
    }
    
    // Possibly convert the requested timestamps to UTC from browser time.
    // Do this before the strtotime/time calls below, both of which use UTC.
    
    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);
      
      if(!empty($this->data['OrgIdentity']['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['OrgIdentity']['valid_from'], $localTZ);
        
        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['OrgIdentity']['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
      
      if(!empty($this->data['OrgIdentity']['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['OrgIdentity']['valid_through'], $localTZ);
        
        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['OrgIdentity']['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
    }
    
    return true;
  }
  
  /**
   * Duplicate an Organizational Identity, including all of its related
   * (has one/has many) models.
   * - postcondition: Duplicate identity created.
   *
   * @since  COmanage Registry v0.2
   * @param  integer Identifier of Org Identity to duplicate
   * @param  integer CO to attach duplicate Org Identity to
   * @return integer New Org Identity ID if successful, -1 otherwise
   * @todo   Remove this in v5
   */
  
  public function duplicate($orgId, $coId)
  {
    $ret = -1;
    
    // We need deep recursion to pull the various related models. Track the previous
    // value so we can reset it after the find.
    $oldRecursive = $this->recursive;
    $this->recursive = 2;
    
    $src = $this->findById($orgId);
    
    $this->recursive = $oldRecursive;
    
    // Construct a new OrgIdentity explicitly copying the pieces we want (so as to
    // avoid any random cruft that recursive=2 happens to pull with it).
    
    $new = array();
    
    foreach(array_keys($src['OrgIdentity']) as $k)
    {
      // Copy most fields
      
      if($k != 'id' && $k != 'co_id' && $k != 'created' && $k != 'modified')
        $new['OrgIdentity'][$k] = $src['OrgIdentity'][$k];
    }
    
    // Set the CO ID
    $new['OrgIdentity']['co_id'] = $coId;
    
    // Copy most fields from most dependent models.
    
    foreach(array_keys($this->hasOne) as $m)
    {
      if($this->hasOne[$m]['dependent'])
      {
        foreach(array_keys($src[$m]) as $k)
        {
          if($k != 'id' && $k != 'created' && $k != 'modified')
            $new[$m][$k] = $src[$m][$k];
        }
      }
    }
    
    foreach(array_keys($this->hasMany) as $m)
    {
      if($this->hasMany[$m]['dependent'] && $m != 'CoPetition')
      {
        foreach(array_keys($src[$m]) as $k)
        {
          if($k != 'id' && $k != 'created' && $k != 'modified')
            $new[$m][$k] = $src[$m][$k];
        }
      }
    }
    
    $this->create();
    $this->saveAll($new);
    $ret = $this->id;
    
    return($ret);
  }
  
  /**
   * Determine which COs $id is eligible to be linked into. ie: Return the set of
   * COs $id is not a member of.
   *
   * @since  COmanage Registry v0.9.1
   * @param  Integer Org Identity ID
   * @return Array Array of CO IDs and CO name
   */
  
  public function linkableCos($id) {
    $cos = array();
    
    $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    
    if($CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // First pull the set of COs that $id is in
      
      $args = array();
      $args['joins'][0]['table'] = 'co_org_identity_links';
      $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
      $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $id;
      $args['fields'] = array('CoPerson.co_id', 'CoPerson.id');
      $args['contain'] = false;
      
      $inCos = $this->Co->CoPerson->find('list', $args);
      
      // Then pull the set of COs that aren't listed above
      
      $args = array();
      $args['conditions']['NOT']['Co.id'] = array_keys($inCos);
      $args['fields'] = array('Co.id', 'Co.name');
      $args['contain'] = false;
      
      $cos = $this->Co->find('list', $args);
    } else {
      // Pull the Org Identity, it's CO, and it's Link via containable. If no link,
      // then this CO is linkable.
      
      $args = array();
      $args['conditions']['OrgIdentity.id'] = $id;
      $args['contain'][] = 'Co';
      $args['contain'][] = 'CoOrgIdentityLink';
      
      $orgid = $this->find('first', $args);
      
      if(empty($orgid['CoOrgIdentityLink'])) {
        $cos[ $orgid['Co']['id'] ] = $orgid['Co']['name'];
      }
    }
    // determine if pooled
    // if not, pull list of cos
    // for each CO, see if a link exists from $id to a co person in that CO
    
    return $cos;
  }

  /**
   * Determine the Pipeline this OrgIdentity should be processed using, if any.
   * The priority for selecting a Pipeline when multiple are available is the Pipeline
   *  (1) Attached to the Enrollment Flow that created the OrgIdentity
   *  (2) Attached to the Org Identity Source the OrgIdentity was created from
   *  (3) The CO Setting
   * Note this implies the Pipeline an OrgIdentity is processed by can change over time.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer Org Identity ID
   * @return Integer CO Pipeline ID, or null
   */
  
  public function pipeline($id) {
    $args = array();
    $args['conditions']['CoPetition.enrollee_org_identity_id'] = $id;
    $args['conditions']['CoPetition.status'] = PetitionStatusEnum::Finalized;
    $args['order'][] = 'CoPetition.created ASC';
    $args['contain'][] = 'CoEnrollmentFlow';
    
    // First see if this Org Identity is attached to a Petition, and if so if that
    // Petition's Enrollment Flow in turn has a pipeline. Since an Org Identity
    // can be attached to multiple Petitions (eg: account linking), we pick the
    // oldest with a pipeline attached. (eg: Account linking enrollment flows
    // shouldn't have pipelines attached.)
    
    // XXX implement and test when pipelines are attached to EFs
    // $this->Co->CoPetition->find('all', $args);
    // return id if found;
    
    // Next check for an org identity source
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_id'] = $id;
    $args['contain'][] = 'OrgIdentitySource';
    
    $oisRecord = $this->OrgIdentitySourceRecord->find('first', $args);
    
    if(!empty($oisRecord['OrgIdentitySource']['co_pipeline_id']))
      return $oisRecord['OrgIdentitySource']['co_pipeline_id'];
    
    // If none found, check CO Settings.
    $coId = $this->field('co_id', array('OrgIdentity.id' => $id));
    
    if($coId) {
      $pipelineId = $this->Co->CoSetting->field('default_co_pipeline_id', array('CoSetting.co_id' => $coId));
      
      if($pipelineId) {
        return $pipelineId;
      }
    }
    
    return null;
  }
  
  /**
   * As of v1.0.0, this operation is no longer supported.
   *
   * Pool Organizational Identities. This will delete all links from Org Identities
   * to COs. No attempt is made to delete duplicate identities that may result from
   * this operation. This operation cannot be undone.
   * - precondition: Organizational Identities are not pooled
   * - postcondition: co_id values for Org Identities are deleted
   *
   * @since  COmanage Registry v0.2
   * @return boolean True if successful, false otherwise
   */
  
  public function pool()
  {
    return false;
    
    // If we ever need to restore this code, we shouldn't use updateAll since it
    // bypasses callbacks (including ChangelogBehavior).
    
    return($this->updateAll(array('OrgIdentity.co_id' => null)));
  }
  
  /**
   * Determine if an Org Identity is read only.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id Org Identity ID
   * @return True if the Org Identity is read only, false otherwise
   */
  
  public function readOnly($id) {
    // An Org Identity is read only if it is attached to an Org Identity Source.
    
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_id'] = $id;
    $args['contain'] = false;
    
    return (bool)$this->OrgIdentitySourceRecord->find('count', $args);
  }
  
  /**
   * As of v1.0.0, this operation is no longer supported.
   *
   * Unpool Organizational Identities. This will link organizational identities
   * to the COs which use them. If an Org Identity is referenced by more than
   * one CO, it will be duplicated.
   * - precondition: Organizational Identities are pooled
   * - postcondition: co_id values for Org Identities are assigned. If necessary, org identities will be duplicated
   *
   * @since  COmanage Registry v0.2
   * @return boolean True if successful, false otherwise
   */
  
  function unpool()
  {
    return false;
    
    // Retrieve all CO/Org Identity Links.
    
    $links = $this->CoOrgIdentityLink->find('all');
    
    // For each retrieved record, find the CO ID for the CO Identity and
    // attach it to the Org Identity.
    
    foreach($links as $l)
    {
      $coId = $l['CoPerson']['co_id'];
      $orgId = $l['CoOrgIdentityLink']['org_identity_id'];
      
      // Get the latest version of the Org Identity, even though it's available
      // in $links
      
      $o = $this->findById($orgId);
      
      if(!isset($o['OrgIdentity']['co_id']) || !$o['OrgIdentity']['co_id'])
      {
        // co_id not yet set (ie: this org_identity is not yet linked to a CO),
        // so we can just update this record
        
        $this->id = $orgId;
        // Use co_id here and NOT OrgIdentity.co_id (per the docs)
        $this->saveField('co_id', $coId);
      }
      else
      {
        // We've previously seen this org identity. First check to see if we've
        // attached it to the same CO. (This shouldn't really happen since it
        // implies the same person was added twice to the same CO.) If so, there's
        // nothing to do.
        
        if($o['OrgIdentity']['co_id'] != $coId)
        {
          // Not the same CO. We need to duplicate the OrgIdentity (including all
          // of it's dependent attributes like identifiers) and relink to the newly
          // created identity.
          
          $newOrgId = $this->duplicate($orgId, $coId);
          
          if($newOrgId != -1)
          {
            // Update CoOrgIdentityLink
            
            $this->CoOrgIdentityLink->id = $l['CoOrgIdentityLink']['id'];
            $this->CoOrgIdentityLink->saveField('org_identity_id', $newOrgId);
          }
          else
          {
            return(false);
          }
        }
      }
    }
    
    return(true);
  }
  
  /**
   * Check for authoritative attributes in the environment and update the specified
   * Organizational ID.
   *
   * @param  Integer $id       Organizational Identity ID
   * @param  Array   $envAttrs Array of environment variables holding authoritative data
   * @return Array Updated Organizational Identity
   * @throws RuntimeException
   */
  
  public function updateFromEnv($id, $envAttrs) {
    // Start by examining what attributes we collected from the environment.
    // For now, we assume only one of each thing can be provided (ie: only one
    // name can be extracted from $ENV). Since we only support (eg) official name
    // at the moment this is OK, though perhaps it might change in the future.
    
    $curOrgIdentity = null;  // What we currently know
    $envOrgIdentity = null;  // What we got from the IdP
    $newOrgIdentity = null;  // What we decided to save as the updated record (or portion thereof)
    $newModels = array();    // List of associated models we decided to save
    
    foreach($envAttrs as $ea) {
      // First see if there is an env variable identified, and if so if it's populated
      
      if(!empty($ea['env_name']) && getenv($ea['env_name'])) {
        $newval = getenv($ea['env_name']);
        
        // This attribute was populated, next figure out the associated model and type.
        // $ea['attribute'] is of the form [model:]field, so it may need to be split and inflected.
        
        if(strchr($ea['attribute'], ':')) {
          $xeattr = explode(':', $ea['attribute'], 2);
          
          $model = Inflector::classify($xeattr[0]);
          $field = $xeattr[1];
          
          $envOrgIdentity[$model][0][$field] = $newval;
          
          if(!empty($ea['type'])) {
            // We're assuming all fields of the same model are of the same type, which
            // for now they must be. (ie: we can't currently handle both an official and
            // preferred name.)
            
            $envOrgIdentity[$model][0]['type'] = $ea['type'];
          }
        } else {
          // This field goes into the OrgIdentity Model
          
          $envOrgIdentity['OrgIdentity'][ $ea['attribute'] ] = $newval;
        }
      }
    }
    
    if(!empty($envOrgIdentity)) {
      // Now pull the current data associated with this OrgIdentity
      $args = array();
      $args['conditions']['OrgIdentity.id'] = $id;
      $args['contain']['Address']['conditions']['Address.type ='] = ContactEnum::Office;
      $args['contain']['EmailAddress']['conditions']['EmailAddress.type ='] = EmailAddressEnum::Official;
      $args['contain']['Name']['conditions']['Name.type ='] = NameEnum::Official;
      $args['contain']['TelephoneNumber']['conditions']['TelephoneNumber.type ='] = ContactEnum::Office;
      
      $curOrgIdentity = $this->find('first', $args);
      
      // Walk through the models. Start with OrgIdentity, which has to be handled specially.
      // Start by copying all existing values in this model, since we will not get them all
      // via the environment, and we may not get any (in which case we need at least id
      // in order for SaveAssociated to know what to do).
      
      $newOrgIdentity['OrgIdentity'] = $curOrgIdentity['OrgIdentity'];
      
      if(!empty($envOrgIdentity['OrgIdentity'])) {
        // Copy each defined field to the record to be saved
        
        foreach(array_keys($envOrgIdentity['OrgIdentity']) as $f) {
          if($f == 'affiliation') {
            // Affiliation has to be a valid edupersonaffiliation. If it's not, we'll
            // just drop it on the floor.
            
            global $affil_t;
            
            if(isset($affil_t[ $envOrgIdentity['OrgIdentity'][$f] ])) {
              $newOrgIdentity['OrgIdentity'][$f] = $envOrgIdentity['OrgIdentity'][$f];
            }
          } else {
            // Just copy other attributes
            
            $newOrgIdentity['OrgIdentity'][$f] = $envOrgIdentity['OrgIdentity'][$f];
          }
        }
      }
      
      // We don't want to save models which have no associated env data, so iterate
      // through the models defined by the contain and check.
      
      // Note that EmailAddress::beforeSave() will correctly decide what to do about verified.
      // Similarly, Name::beforeSave() will determine what to do about primary_name.
      
      foreach(array('Address', 'EmailAddress', 'Name', 'TelephoneNumber') as $m) {
        if(!empty($envOrgIdentity[$m][0])) {
          $newModels[] = $m;
          
          if(!empty($curOrgIdentity[$m])) {
            // Update any record we find, but we need to preserve id
            
            foreach(array_keys($curOrgIdentity[$m]) as $instance) {
              $id = $curOrgIdentity[$m][$instance]['id'];
              
              $newOrgIdentity[$m][$instance] = $envOrgIdentity[$m][0];
              $newOrgIdentity[$m][$instance]['id'] = $id;
            }
          } else {
            // Create a new instance... simple copy
            $newOrgIdentity[$m] = $envOrgIdentity[$m];
          }
        }
      }
      
      // Use changesToString to determine if anything actually changed
      
      $changes = $this->changesToString($newOrgIdentity,
                                        $curOrgIdentity,
                                        (!empty($curOrgIdentity['OrgIdentity']['co_id'])
                                         ? $curOrgIdentity['OrgIdentity']['co_id']
                                         : null),
                                        $newModels);
      
      if($changes != "") {
        // Run changesToString again for each associated model, this time to clear
        // models that had no changes (to avoid updating their modified times)
        
        try {
          foreach($newModels as $m) {
            $mchanges = $this->$m->changesToString($newOrgIdentity,
                                                   $curOrgIdentity,
                                                   (!empty($curOrgIdentity['OrgIdentity']['co_id'])
                                                    ? $curOrgIdentity['OrgIdentity']['co_id']
                                                    : null));
            
            if($mchanges == "") {
              // No changes, so don't try to save this model
              unset($newOrgIdentity[$m]);
            }
          }
          
          $this->saveAssociated($newOrgIdentity);
          
          $this->HistoryRecord->record(null,
                                       null,
                                       $curOrgIdentity['OrgIdentity']['id'],
                                       null,
                                       ActionEnum::OrgIdEditedLoginEnv,
                                       _txt('en.action', null, ActionEnum::OrgIdEditedLoginEnv) . ": " .
                                       $changes);
        }
        catch(Exception $e) {
          throw new RuntimeException($e);
        }
      }
    }
    
    // Return updated OrgIdentity & associated values
    
    return $newOrgIdentity;
  }
}
