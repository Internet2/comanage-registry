<?php
/**
 * COmanage Registry CO Extended Attributes Controller
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

App::uses("StandardController", "Controller");

class CoExtendedAttributesController extends StandardController {
 // Class name, used by Cake
  public $name = "CoExtendedAttributes";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoExtendedAttribute.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Determine if an attribute name is available for use as an extended attribute..
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST) on error
   *
   * @since  COmanage Registry v0.2
   * @param  integer ID of CO to check for
   * @param  string Requested name
   * @return boolean true if name is available, false otherwise
   */
  
  function checkAttributeName($coid, $name) {   
    $r = true;
    
    // Reserved names are not permitted. Basically, this is anything in co_people or co_person_role_id.
    
    if($name == 'co_person_role_id')
      $r = false;
    
    if($r) {
      // Check co_person_role schema
      
      if(isset($this->CoExtendedAttribute->Co->CoPerson->CoPersonRole->_schema[$name]))
        $r = false;
    }
    
    if($r) {
      // See if $name is an extended attribute
      $args['conditions']['CoExtendedAttribute.co_id'] = $coid;
      $args['conditions']['CoExtendedAttribute.name'] = $name;
      
      $x = $this->CoExtendedAttribute->find('first', $args);
        
      if(!empty($x))
        $r = false;
    }
      
    if(!$r) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Name In Use");
      } else {
        $this->Flash->set(_txt('er.ea.exists', array($name)), array('key' => 'error'));
      }
      
      return false;
    }
    
    return true;
  }        

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // XXX If this method returns true but the controller delete fails (say, due
    // to data validation error), cm_co_extended_attributes and $cotable will be
    // inconsistent.
    
    $dbc = $this->CoExtendedAttribute->getDataSource();
    
    // Construct dynamic names
    
    $cotable = $this->CoExtendedAttribute->tablePrefix . "co"
             . filter_var($curdata['CoExtendedAttribute']['co_id'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK)
             . "_person_extended_attributes";
    
    // Start a transaction
    
    $dbc->begin($this);
    
    // Drop the specified column. This will also drop any index.
    
    $sql = "ALTER TABLE " . $cotable . " DROP COLUMN "
         . filter_var($curdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES);
    
    if($this->CoExtendedAttribute->query($sql) === false) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(500, "Database Error");
      } else {
        $this->Flash->set(_txt('er.ea.alter'), array('key' => 'error'));
      }
      
      $dbc->rollback($this);
      return false;
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
      
      if($this->CoExtendedAttribute->query($sql) === false) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(500, "Database Error");
        } else {
          $this->Flash->set(_txt('er.ea.table.d'), array('key' => 'error'));
        }
        
        $dbc->rollback($this);
        return false;
      }
    }
    
    // Commit any changes
    $dbc->commit($this);

    // Since the database representation of the models has been
    // manipulated directly we need to clear the models in the cache.
    // See https://bugs.internet2.edu/jira/browse/CO-171
    clearCache(null, 'models');

    return(true);
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
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
    
    $this->CoExtendedAttribute->set($reqdata);
    
    if(!$this->CoExtendedAttribute->validates())
    {
      if($this->request->is('restful')) {
        $fs = $model->invalidFields();
        
        if(!empty($fs)) {
          $this->Api->restResultHeader(400, "Invalid Fields");
          $this->set('invalid_fields', $fs);
        } else {
          $this->Api->restResultHeader(500, "Other Error");
        }
      } else {
        $this->Flash->set($this->fieldsErrorToString($this->CoExtendedAttribute->invalidFields()), array('key' => 'error'));
      }
      
      return false;
    }
    
    $dbc = $this->CoExtendedAttribute->getDataSource();
    
    // Construct dynamic names. We are leveraging CakePHP's dynamic models,
    // so table names need to be inflectable.
    
    $cotable = $this->CoExtendedAttribute->tablePrefix . "co"
             . filter_var($reqdata['CoExtendedAttribute']['co_id'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK)
             . "_person_extended_attributes";
    
    $coindex = $cotable . "_" . filter_var($reqdata['CoExtendedAttribute']['name'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK) . "_i";
    
    // Start a transaction
    
    $dbc->begin($this);
    
    if(!$curdata)
    {
      // Add operation. If this is the first attribute for the CO, create the
      // extended attribute table.
      
      $c = $this->CoExtendedAttribute->find('count',
                                            array('conditions' =>
                                                  array('co_id' => $reqdata['CoExtendedAttribute']['co_id'])));
      
      if($c == 0)
      {
        // No extended attributes for this CO, so create its table

        $sql = "CREATE TABLE " . $cotable . " (
                id SERIAL PRIMARY KEY,
                co_person_role_id INTEGER REFERENCES " . $this->CoExtendedAttribute->tablePrefix . "co_person_roles(id),
                created TIMESTAMP,
                modified TIMESTAMP
              );";
        
        if($this->CoExtendedAttribute->query($sql) === false) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(500, "Database Error");
          } else {
            $this->Flash->set(_txt('er.ea.table'), array('key' => 'error'));
          }
          
          $dbc->rollback($this);
          return false;
        }
      }
      
      // Alter the table to add the new column -- make sure name doesn't exist

      if(!$this->checkAttributeName($reqdata['CoExtendedAttribute']['co_id'],
                                    $reqdata['CoExtendedAttribute']['name']))
      {
        $dbc->rollback($this);
        return(false);
      }
      
      $sql = "ALTER TABLE " . $cotable . "
              ADD COLUMN " . filter_var($reqdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES)
           . " " . $reqdata['CoExtendedAttribute']['type'];
           // Type must match an enumerated value (as defined in the model) and so doesn't need sanitization
      
      if($this->CoExtendedAttribute->query($sql) === false) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(500, "Database Error");
        } else {
          $this->Flash->set(_txt('er.ea.alter'), array('key' => 'error'));
        }
        
        $dbc->rollback($this);
        return false;
      }

      // Set up an index if requested
      
      if(isset($reqdata['CoExtendedAttribute']['indx'])
         && $reqdata['CoExtendedAttribute']['indx'])
      {
        $sql = "CREATE INDEX " . $coindex . " ON " . $cotable
             . " (" . filter_var($reqdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES) . ")";
        
        if($this->CoExtendedAttribute->query($sql) === false) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(500, "Database Error");
          } else {
            $this->Flash->set(_txt('er.ea.index'), array('key' => 'error'));
          }
          
          $dbc->rollback($this);
          return false;
        }
      }
    }
    else
    {
      // Alter the existing column -- make sure name doesn't exist
      
      if($reqdata['CoExtendedAttribute']['name'] != $curdata['CoExtendedAttribute']['name'])
      {
        if(!$this->checkAttributeName($reqdata['CoExtendedAttribute']['co_id'],
                                      $reqdata['CoExtendedAttribute']['name']))
        {
          $dbc->rollback($this);
          return(false);
        }
        
        $sql = "ALTER TABLE "
             . $this->CoExtendedAttribute->tablePrefix . "co_" . $reqdata['CoExtendedAttribute']['co_id'] . "_person_extended_attributes
                RENAME COLUMN " . filter_var($curdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES)
             . " TO " . $reqdata['CoExtendedAttribute']['name'];
      
        if($this->CoExtendedAttribute->query($sql) === false) {
          if($this->request->is('restful')) {
            $this->Api->restResultHeader(500, "Database Error");
          } else {
            $this->Flash->set(_txt('er.ea.alter'), array('key' => 'error'));
          }
          
          $dbc->rollback($this);
          return false;
        }
      }
      
      // We don't currently support changing the type because it may not be easily supported
      // at the database level. (Casting of values may be required.)
      
      // Alter the index setting if requested
      
      if($reqdata['CoExtendedAttribute']['indx'] != $curdata['CoExtendedAttribute']['indx'])
      {
        $sql = "";
        
        if(isset($curdata['CoExtendedAttribute']['indx']) && $curdata['CoExtendedAttribute']['indx'])
        {
          // Drop the current index
          
          $sql = "DROP INDEX " . $coindex;
        }
        elseif(isset($reqdata['CoExtendedAttribute']['indx'])
               && $reqdata['CoExtendedAttribute']['indx'])
        {
          // Create the index

          $sql = "CREATE INDEX " . $coindex . " ON " . $cotable
               . " (" . filter_var($reqdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES) . ")";
        }
        
        if($sql != "")
        {
          if($this->CoExtendedAttribute->query($sql) === false) {
            if($this->request->is('restful')) {
              $this->Api->restResultHeader(500, "Database Error");
            } else {
              $this->Flash->set(_txt('er.ea.index'), array('key' => 'error'));
            }
            
            $dbc->rollback($this);
            return false;
          }
        }
      }
    }
    
    // Commit any changes
    $dbc->commit($this);
    
    // Since the database representation of the models has been
    // manipulated directly we need to clear the models in the cache.
    // See https://bugs.internet2.edu/jira/browse/CO-171
    clearCache(null, 'models');

    return(true);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Extended Attribute?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Extended Attribute?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Extended Attribute?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Extended Attributes?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Extended Attribute?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
