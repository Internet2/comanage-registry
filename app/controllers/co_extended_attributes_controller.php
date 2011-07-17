<?php
  /*
   * COmanage Gears Per-CO Extended Attributes Controller
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

  include APP."controllers/standard_controller.php";

  class CoExtendedAttributesController extends StandardController {
   // Class name, used by Cake
    var $name = "CoExtendedAttributes";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'CoExtendedAttribute.name' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;

    function checkAttributeName($coid, $name)
    {
      // Determine if an attribute name is available for use as an extended attribute.
      //
      // Parameters:
      // - coid: ID of CO to check for
      // - name: Requested name
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if name is available, false otherwise.
      
      $r = true;
      
      // Reserved names are not permitted. Basically, this is anything in co_people or co_person_role_id.
      
      if($name == 'co_person_role_id')
        $r = false;
      
      if($r)
      {
        // Check co_people schema
        
        if(isset($this->CoExtendedAttribute->Co->CoPersonSource->CoPersonRole->_schema[$name]))
          $r = false;
      }
      
      if($r)
      {
        // See if $name is an extended attribute
        $x = $this->CoExtendedAttribute->find('first',
                                              array('conditions' =>
                                                    array('CoExtendedAttribute.co_id' => $this->data['CoExtendedAttribute']['co_id'],
                                                          'CoExtendedAttribute.name' => $this->data['CoExtendedAttribute']['name'])));
          
        if(!empty($x))
          $r = false;
      }
        
      if(!$r)
      {
        if($this->restful)
          $this->restResultHeader(403, "Name In Use");
        else
          $this->Session->setFlash(_txt('er.ea.exists', array($this->data['CoExtendedAttribute']['name'])), '', array(), 'error');          

        return(false);
      }
      
      return(true);
    }        

    function checkDeleteDependencies($curdata)
    {
      // Perform any dependency checks required prior to a delete operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - curdata: Current data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
            
      // XXX If this method returns true but the controller delete fails (say, due
      // to data validation error), cm_co_extended_attributes and $cotable will be
      // inconsistent.
      
      $dbc = $this->CoExtendedAttribute->getDataSource();
      
      // Construct dynamic names
      
      $cotable = $this->CoExtendedAttribute->tablePrefix . "co"
               . Sanitize::paranoid($curdata['CoExtendedAttribute']['co_id'])
               . "_person_extended_attributes";
      
      // Start a transaction
      
      $dbc->begin($this);
      
      // Drop the specified column. This will also drop any index.
      
      $sql = "ALTER TABLE " . $cotable . " DROP COLUMN "
           . Sanitize::escape($curdata['CoExtendedAttribute']['name'], $dbc->configKeyName);
      
      if($this->CoExtendedAttribute->query($sql) === false)
      {
        if($this->restful)
          $this->restResultHeader(500, "Database Error");
        else
          $this->Session->setFlash(_txt('er.ea.alter'), '', array(), 'error');
        
        $dbc->rollback($this);
        return(false);
      }
      
      // If there are no columns left, drop the dynamic table.
      
      $c = $this->CoExtendedAttribute->find('count',
                                            array('conditions' =>
                                                  array('co_id' => $curdata['CoExtendedAttribute']['co_id'])));
      
      if($c == 1)
      {
        // No extended attributes for this CO, so create its table.
        // (We test for 1 because the cm_co_extended_attribute row hasn't
        // been removed yet.)

        $sql = "DROP TABLE " . $cotable;
        
        if($this->CoExtendedAttribute->query($sql) === false)
        {
          if($this->restful)
            $this->restResultHeader(500, "Database Error");
          else
            $this->Session->setFlash(_txt('er.ea.table.d'), '', array(), 'error');
          
          $dbc->rollback($this);
          return(false);
        }
      }
      
      // Commit any changes
      $dbc->commit($this);

      return(true);
    }
    
    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - For edit operations, $curdata will hold current data
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      // Extended Attributes are "indexed" in the co_extended_attributes table
      // (ie: a table of contents of defined extended attributes), but the actual
      // attribute values are stored in per-CO tables that are dynamically created
      // here.
      
      // XXX If this method returns true but the controller add/edit fails (say, due
      // to data validation error), cm_co_extended_attributes and $cotable will be
      // inconsistent.
      
      // Before proceeding, manually invoke validation. This will catch errors like
      // invalid field names and types before we try to set up the tables.
      // (Normally, validation happens at save(), which happens after checkWriteDependencies.)
      
      $this->CoExtendedAttribute->set($this->data);
      
      if(!$this->CoExtendedAttribute->validates())
      {
        if($this->restful)
        {
          $fs = $model->invalidFields();
          
          if(!empty($fs))
          {
            $this->restResultHeader(400, "Invalid Fields");
            $this->set('invalid_fields', $fs);
          }
          else
          {
            $this->restResultHeader(500, "Other Error");
          }
        }
        else
          $this->Session->setFlash($this->fieldsErrorToString($this->CoExtendedAttribute->invalidFields()), '', array(), 'error');
        
        return(false);
      }
      
      $dbc = $this->CoExtendedAttribute->getDataSource();
      
      // Construct dynamic names. We are leveraging CakePHP's dynamic models,
      // so table names need to be inflectable.
      
      $cotable = $this->CoExtendedAttribute->tablePrefix . "co"
               . Sanitize::paranoid($this->data['CoExtendedAttribute']['co_id'])
               . "_person_extended_attributes";
      
      $coindex = $cotable . "_" . Sanitize::paranoid($this->data['CoExtendedAttribute']['name']) . "_i";
      
      // Start a transaction
      
      $dbc->begin($this);
      
      if(!$curdata)
      {
        // Add operation. If this is the first attribute for the CO, create the
        // extended attribute table.
        
        $c = $this->CoExtendedAttribute->find('count',
                                              array('conditions' =>
                                                    array('co_id' => $this->data['CoExtendedAttribute']['co_id'])));
        
        if($c == 0)
        {
          // No extended attributes for this CO, so create its table

          $sql = "CREATE TABLE " . $cotable . " (
                  id SERIAL PRIMARY KEY,
                  co_person_role_id INTEGER REFERENCES " . $this->CoExtendedAttribute->tablePrefix . "co_person_roles(id),
                  created TIMESTAMP,
                  modified TIMESTAMP
                );";
          
          if($this->CoExtendedAttribute->query($sql) === false)
          {
            if($this->restful)
              $this->restResultHeader(500, "Database Error");
            else
              $this->Session->setFlash(_txt('er.ea.table'), '', array(), 'error');
            
            $dbc->rollback($this);
            return(false);
          }
        }
        
        // Alter the table to add the new column -- make sure name doesn't exist

        if(!$this->checkAttributeName($this->data['CoExtendedAttribute']['co_id'],
                                      $this->data['CoExtendedAttribute']['name']))
        {
          $dbc->rollback($this);
          return(false);
        }
        
        $sql = "ALTER TABLE " . $cotable . "
                ADD COLUMN " . Sanitize::escape($this->data['CoExtendedAttribute']['name'], $dbc->configKeyName)
             . " " . $this->data['CoExtendedAttribute']['type'];
             // Type must match an enumerated value (as defined in the model) and so doesn't need sanitization
        
        if($this->CoExtendedAttribute->query($sql) === false)
        {
          if($this->restful)
            $this->restResultHeader(500, "Database Error");
          else
            $this->Session->setFlash(_txt('er.ea.alter'), '', array(), 'error');
          
          $dbc->rollback($this);
          return(false);
        }

        // Set up an index if requested
        
        if(isset($this->data['CoExtendedAttribute']['indx'])
           && $this->data['CoExtendedAttribute']['indx'])
        {
          $sql = "CREATE INDEX " . $coindex . " ON " . $cotable
               . " (" . Sanitize::escape($this->data['CoExtendedAttribute']['name'], $dbc->configKeyName) . ")";
          
          if($this->CoExtendedAttribute->query($sql) === false)
          {
            if($this->restful)
              $this->restResultHeader(500, "Database Error");
            else
              $this->Session->setFlash(_txt('er.ea.index'), '', array(), 'error');
            
            $dbc->rollback($this);
            return(false);
          }
        }
      }
      else
      {
        // Alter the existing column -- make sure name doesn't exist
        
        if($this->data['CoExtendedAttribute']['name'] != $curdata['CoExtendedAttribute']['name'])
        {
          if(!$this->checkAttributeName($this->data['CoExtendedAttribute']['co_id'],
                                        $this->data['CoExtendedAttribute']['name']))
          {
            $dbc->rollback($this);
            return(false);
          }
          
          $sql = "ALTER TABLE "
               . $this->CoExtendedAttribute->tablePrefix . "co_" . $this->data['CoExtendedAttribute']['co_id'] . "_person_extended_attributes
                  RENAME COLUMN " . Sanitize::escape($curdata['CoExtendedAttribute']['name'], $dbc->configKeyName)
               . " TO " . $this->data['CoExtendedAttribute']['name'];
        
          if($this->CoExtendedAttribute->query($sql) === false)
          {
            if($this->restful)
              $this->restResultHeader(500, "Database Error");
            else
              $this->Session->setFlash(_txt('er.ea.alter'), '', array(), 'error');
            
            $dbc->rollback($this);
            return(false);
          }
        }
        
        // We don't currently support changing the type because it may not be easily supported
        // at the database level. (Casting of values may be required.)
        
        // Alter the index setting if requested
        
        if($this->data['CoExtendedAttribute']['indx'] != $curdata['CoExtendedAttribute']['indx'])
        {
          $sql = "";
          
          if(isset($curdata['CoExtendedAttribute']['indx']) && $curdata['CoExtendedAttribute']['indx'])
          {
            // Drop the current index
            
            $sql = "DROP INDEX " . $coindex;
          }
          elseif(isset($this->data['CoExtendedAttribute']['indx'])
                 && $this->data['CoExtendedAttribute']['indx'])
          {
            // Create the index

            $sql = "CREATE INDEX " . $coindex . " ON " . $cotable
                 . " (" . Sanitize::escape($this->data['CoExtendedAttribute']['name'], $dbc->configKeyName) . ")";
          }
          
          if($sql != "")
          {
            if($this->CoExtendedAttribute->query($sql) === false)
            {
              if($this->restful)
                $this->restResultHeader(500, "Database Error");
              else
                $this->Session->setFlash(_txt('er.ea.index'), '', array(), 'error');
              
              $dbc->rollback($this);
              return(false);
            }
          }
        }
      }
      
      // Commit any changes
      $dbc->commit($this);
      
      return(true);
    }
    
    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions

      $cmr = $this->calculateCMRoles();
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new Extended Attribute?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Delete an existing Extended Attribute?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing Extended Attribute?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View all existing Extended Attributes?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View an existing Extended Attribute?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
  }
?>