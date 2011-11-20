<?php
  /*
   * COmanage Gears Organizational Person Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class OrgIdentity extends AppModel {
    // Define class name for cake
    var $name = "OrgIdentity";
    
    // Association rules from this model to other models
    var $hasOne = array("Name" =>                     // A person can have one (preferred) name per Org
                        array('dependent' => true));  // This could change if Name became an MVPA
    
    var $hasMany = array("Address" =>                 // A person can have one or more address
                         array('dependent' => true),
                         "CoOrgIdentityLink" =>       // An Org Identity can be attached to one or more CO Person
                         array('dependent' => false), // Current design requires all links to be dropped manually
                         "CoPetition" =>              // A person can have various roles for a petition
                         array('dependent' => true,
                               'foreignKey' => 'enrollee_org_identity_id'),
                         "EmailAddress" =>            // A person can have one or more email address
                         array('dependent' => true),
                         "Identifier" =>              // A person can have many identifiers within an organization
                         array('dependent' => true),
                         "TelephoneNumber" =>         // A person can have one or more telephone numbers
                         array('dependent' => true));

    var $belongsTo = array("Organization");       // A person may belong to an organization (if pre-defined)
    
    // Default display field for cake generated views
    var $displayField = "Name.family";
    
    // Default ordering for find operations
    var $order = array("Name.family", "Name.given");
    
    // Validation rules for table elements
    var $validate = array(
      'edu_person_affiliation' => array(
        'rule' => array('inList', array('faculty', 'student', 'staff', 'alum', 'member', 'affiliate', 'employee', 'library-walk-in')),
        'required' => true,
        'message' => 'A valid affiliation must be selected'
      )
      // 'o'
      // 'ou'
      // 'title'
    );
    
    function duplicate($orgId, $coId)
    {
      // Duplicate an Organizational Identity, including all of its related
      // (has one/has many) models.
      //
      // Parameters:
      // - orgId: Identifier of Org Identity to duplicate.
      // - coId: CO to attach duplicate Org Identity to.
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Duplicate identity created.
      //
      // Returns:
      // - New Org Identity ID if successful, -1 otherwise.
      
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
  
    function pool()
    {
      // Pool Organizational Identities. This will delete all links from Org Identities
      // to COs. No attempt is made to delete duplicate identities that may result from
      // this operation. This operation cannot be undone.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Organizational Identities are not pooled.
      //
      // Postconditions:
      // (1) co_id values for Org Identities are deleted.
      //
      // Returns:
      // - True if successful, false otherwise.
      
      return($this->updateAll(array('OrgIdentity.co_id' => null)));
    }
    
    function unpool()
    {
      // Unpool Organizational Identities. This will link organizational identities
      // to the COs which use them. If an Org Identity is referenced by more than
      // one CO, it will be duplicated.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Organizational Identities are pooled.
      //
      // Postconditions:
      // (1) co_id values for Org Identities are assigned. If necessary, org
      //     identities will be duplicated.
      //
      // Returns:
      // - True if successful, false otherwise.
      
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
?>