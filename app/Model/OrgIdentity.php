<?php
/**
 * COmanage Registry OrgIdentity Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class OrgIdentity extends AppModel {
  // Define class name for cake
  public $name = "OrgIdentity";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $hasOne = array(
    // A person can have one (preferred) name per Org.
    // This could change if Name became an MVPA
    "Name" => array('dependent' => true)
  );
  
  public $hasMany = array(
    // A person can have one or more address
    "Address" => array('dependent' => true),
    // An Org Identity can be attached to one or more CO Person
    // The current design requires all links to be dropped manually
    "CoOrgIdentityLink" => array('dependent' => false), 
    // A person can have various roles for a petition
    "CoPetition" => array(
      'dependent' => true,
      'foreignKey' => 'enrollee_org_identity_id'
    ),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // It's probably not right to delete history records, but generally org identities shouldn't be deleted
    "HistoryRecord" => array('dependent' => true),
    // A person can have many identifiers within an organization
    "Identifier" => array('dependent' => true),
    // A person can have one or more telephone numbers
    "TelephoneNumber" => array('dependent' => true)
  );

  public $belongsTo = array(
    // A person may belong to an organization (if pre-defined)
    "Organization",
    // An Org Identity may belong to a CO, if not pooled
    "Co"
  );
  
  // Default display field for cake generated views
  public $displayField = "Name.family";
  
// XXX CO-296
  // Default ordering for find operations
//  public $order = array("Name.family", "Name.given");
  
  // Validation rules for table elements
  public $validate = array(
    'affiliation' => array(
      'rule' => array('inList', array(AffiliationEnum::Faculty,
                                      AffiliationEnum::Student,
                                      AffiliationEnum::Staff,
                                      AffiliationEnum::Alum,
                                      AffiliationEnum::Member,
                                      AffiliationEnum::Affiliate,
                                      AffiliationEnum::Employee,
                                      AffiliationEnum::LibraryWalkIn)),
      'required' => false
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false
    ),
    'o' => array(
      'rule' => '/.*/',
      'required' => false
    ),
    'organization_id' => array(
      'rule' => 'numeric',
      'required' => false
    ),
    'ou' => array(
      'rule' => '/.*/',
      'required' => false
    ),
    'title' => array(
      'rule' => '/.*/',
      'required' => false
    ),
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'affiliation' => 'en.affil',
  );
  
  public $cm_enum_types = array(
    'affiliation' => 'affil_t'
  );
  
  /**
   * Duplicate an Organizational Identity, including all of its related
   * (has one/has many) models.
   * - postcondition: Duplicate identity created.
   *
   * @since  COmanage Registry v0.2
   * @param  integer Identifier of Org Identity to duplicate
   * @param  integer CO to attach duplicate Org Identity to
   * @return integer New Org Identity ID if successful, -1 otherwise
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
    return($this->updateAll(array('OrgIdentity.co_id' => null)));
  }
  
  /**
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
}
