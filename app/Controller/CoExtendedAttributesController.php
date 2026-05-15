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
   * @param  integer $coid ID of CO to check for
   * @param  string $name Requested name
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
   * @param  Array $curdata Current data
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

    // Start a transaction (unless DDL auto-commits for this datasource, eg MySQL)

    $this->_beginIfSupported($dbc);
    
    // Drop the specified column. This will also drop any index.
    
    $sql = "ALTER TABLE " . $cotable . " DROP COLUMN "
         . filter_var($curdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES);
    
    if($this->CoExtendedAttribute->query($sql) === false) {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(500, "Database Error");
      } else {
        $this->Flash->set(_txt('er.ea.alter'), array('key' => 'error'));
      }
      
      $this->_rollbackIfInTransaction($dbc);
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
        
        $this->_rollbackIfInTransaction($dbc);
        return false;
      }
    }
    
    // Commit any changes
    $this->_commitIfInTransaction($dbc);

    // Since the database representation of the models has been
    // manipulated directly we need to clear the models in the cache.
    // See https://bugs.internet2.edu/jira/browse/CO-171
    clearCache(null, 'models');

    return true;
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array $reqdata Request data
   * @param  Array $curdata Current data
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

    if(!$this->coExtendedAttributesValidateOrError($reqdata)) {
      return false;
    }

    $dbc = $this->CoExtendedAttribute->getDataSource();

    // Construct dynamic names. We are leveraging CakePHP's dynamic models,
    // so table names need to be inflectable.

    $names = $this->coExtendedAttributesBuildDynamicNames($reqdata);
    $cotable = $names['cotable'];
    $coindex = $names['coindex'];

    // Start a transaction (unless DDL auto-commits for this datasource, eg MySQL)

    $this->_beginIfSupported($dbc);

    $ok = (!$curdata)
      ? $this->coExtendedAttributesApplyAddDependencies($reqdata, $dbc, $cotable, $coindex)
      : $this->coExtendedAttributesApplyEditDependencies($reqdata, $curdata, $dbc, $cotable, $coindex);

    if(!$ok) {
      return false;
    }

    // Commit any changes
    $this->_commitIfInTransaction($dbc);

    // Since the database representation of the models has been
    // manipulated directly we need to clear the models in the cache.
    // See https://bugs.internet2.edu/jira/browse/CO-171
    clearCache(null, 'models');

    return true;
  }

  /**
   * Validates extended attribute request data and handles errors.
   *
   * This method verifies the validity of the request data for an extended attribute,
   * ensuring that it adheres to the model's validation rules. On validation failure,
   * it delivers appropriate error responses based on the request type, either as
   * REST API headers or user-visible Flash messages.
   *
   * @param Array $reqdata Request data containing extended attribute details.
   * @return Boolean True if valid, false otherwise.
   * @since  COmanage Registry v4.6.1
   */
  protected function coExtendedAttributesValidateOrError($reqdata) {
    $this->CoExtendedAttribute->set($reqdata);

    // Valid request data, proceed with dependency checks
    if($this->CoExtendedAttribute->validates()) {
      return true;
    }

    // Invalid request data, emit the original REST/Flash error responses and stop
    if($this->request->is('restful')) {
      $fs = $this->CoExtendedAttribute->invalidFields();

      // Invalid fields available, return 400 and include invalid_fields payload
      if(!empty($fs)) {
        $this->Api->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', $fs);
        return false;
      }

      // No invalid fields detail available, return generic 500 error
      $this->Api->restResultHeader(500, "Other Error");
      return false;
    }

    // Non-REST request, set the original Flash error message and stop
    $this->Flash->set(
      $this->fieldsErrorToString($this->CoExtendedAttribute->invalidFields()),
      array('key' => 'error')
    );

    return false;
  }

  /**
   * Builds dynamic names for the table and index based on the extended attribute request data.
   *
   * This method constructs the database table name and index name for the provided
   * CO (Collaborative Organization) extended attribute. The names are sanitized
   * to ensure security and prevent SQL injection.
   *
   * @param Array $reqdata Request data containing 'co_id' and 'name' keys.
   * @return Array ['cotable' => string Table name, 'coindex' => string Index name]
   * @since  COmanage Registry v4.6.1
   */
  protected function coExtendedAttributesBuildDynamicNames($reqdata) {
    $cotable = $this->CoExtendedAttribute->tablePrefix . "co"
      . filter_var(
        $reqdata['CoExtendedAttribute']['co_id'],
        FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK
      )
      . "_person_extended_attributes";

    $coindex = $cotable . "_"
      . filter_var(
        $reqdata['CoExtendedAttribute']['name'],
        FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK
      ) . "_i";

    return array(
      'cotable' => $cotable,
      'coindex' => $coindex
    );
  }

  /**
   * Handle the add operation for dependency checks and creation of CO Extended Attributes.
   *
   * This method ensures that the necessary table and columns are created for a new extended attribute
   * by performing the following steps:
   * - Creates the extended attribute table if it does not already exist.
   * - Checks the validity and availability of the attribute name.
   * - Adds a new column to the extended attribute table corresponding to the specified name and type.
   * - Creates an index for the new column if requested.
   *
   * @param Array $reqdata Request data containing details about the extended attribute to be added.
   * @param Object $dbc Database connection object for executing transactions and queries.
   * @param String $cotable The table name where the extended attribute will be added.
   * @param String $coindex The index name corresponding to the new extended attribute.
   * @return Boolean True if all operations succeed, false otherwise.
   * @since  COmanage Registry v4.6.1
   */
  protected function coExtendedAttributesApplyAddDependencies($reqdata, $dbc, $cotable, $coindex) {
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

        $this->_rollbackIfInTransaction($dbc);
        return false;
      }
    }

    // Alter the table to add the new column -- make sure name doesn't exist

    if(!$this->checkAttributeName($reqdata['CoExtendedAttribute']['co_id'],
      $reqdata['CoExtendedAttribute']['name']))
    {
      $this->_rollbackIfInTransaction($dbc);
      return false;
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

      $this->_rollbackIfInTransaction($dbc);
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

        $this->_rollbackIfInTransaction($dbc);
        return false;
      }
    }

    return true;
  }

  /**
   * Handle the edit operation for dependency checks and modifications of CO Extended Attributes.
   *
   * This method edits dependencies related to an extended attribute by performing the following:
   * - Checks for valid attributes and whether the name conflicts with existing attributes.
   * - Renames existing columns in the database if necessary.
   * - Handles changes to indexing, including creating or dropping indexes on the specified table.
   *
   * @param Array $reqdata Request data containing new attribute settings.
   * @param Array $curdata Current data of the attribute before editing.
   * @param Object $dbc Database connection object for executing transactions and queries.
   * @param String $cotable The table name where the attribute is stored.
   * @param String $coindex The index name corresponding to the attribute.
   * @return Boolean True if all operations succeed, otherwise false on failure.
   * @since  COmanage Registry v4.6.1
   */
  protected function coExtendedAttributesApplyEditDependencies($reqdata, $curdata, $dbc, $cotable, $coindex) {
    // Alter the existing column -- make sure name doesn't exist

    if($reqdata['CoExtendedAttribute']['name'] != $curdata['CoExtendedAttribute']['name'])
    {
      if(!$this->checkAttributeName($reqdata['CoExtendedAttribute']['co_id'],
        $reqdata['CoExtendedAttribute']['name']))
      {
        $this->_rollbackIfInTransaction($dbc);
        return false;
      }

      if ($dbc->config['datasource'] === 'Database/Mysql') {
        $sql = "ALTER TABLE " . $cotable . " CHANGE "
          . filter_var($curdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES)
          . " " . $reqdata['CoExtendedAttribute']['name']
          . " " . $reqdata['CoExtendedAttribute']['type'];
      } else {
        $sql = "ALTER TABLE " . $cotable . " RENAME COLUMN "
          . filter_var($curdata['CoExtendedAttribute']['name'], FILTER_SANITIZE_MAGIC_QUOTES)
          . " TO " . $reqdata['CoExtendedAttribute']['name'];
      }

      if($this->CoExtendedAttribute->query($sql) === false) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(500, "Database Error");
        } else {
          $this->Flash->set(_txt('er.ea.alter'), array('key' => 'error'));
        }

        $this->_rollbackIfInTransaction($dbc);
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

          $this->_rollbackIfInTransaction($dbc);
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Commit only if a transaction is currently active.
   *
   * This is a defensive wrapper for databases/drivers that may end a transaction
   * implicitly (eg via DDL on some platforms).
   *
   * @param Object $dbc DataSource (DboSource)
   * @return mixed Result of commit(), or true if there was no active transaction
   * @since  COmanage Registry v4.6.1
   */
  private function _commitIfInTransaction($dbc) {
    if($dbc && $dbc->inTransaction()) {
      return $dbc->commit($this);
    }

    return true;
  }

  /**
   * Rollback only if a transaction is currently active.
   *
   * This is a defensive wrapper for databases/drivers that may end a transaction
   * implicitly (eg via DDL on some platforms).
   *
   * @param Object $dbc DataSource (DboSource)
   * @return mixed Result of rollback(), or true if there was no active transaction
   * @since  COmanage Registry v4.6.1
   */
  private function _rollbackIfInTransaction($dbc) {
    if($dbc && $dbc->inTransaction()) {
      return $dbc->rollback($this);
    }

    return true;
  }

  /**
   * Begin only when transactions are meaningful for the underlying DDL execution.
   *
   *  Note: MySQL (5.x and 8.x) implicitly commits most DDL (eg CREATE/ALTER/DROP).
   *  Wrapping these statements in an explicit transaction does not make them rollbackable
   *  and may commit any pending work automatically.
   *  For MySQL datasources we therefore avoid starting a transaction around DDL.
   *
   * @param Object $dbc DataSource (DboSource)
   * @return bool True if begin was called successfully or skipped, false otherwise
   * @since  COmanage Registry v4.6.1
   */
  private function _beginIfSupported($dbc) {
    if($dbc->config['datasource'] === 'Database/Mysql') {
      return true;
    }

    return $dbc->begin($this);
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
