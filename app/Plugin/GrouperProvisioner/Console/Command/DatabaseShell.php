<?php
/**
 * COmanage Registry Grouper Provisioner Database Shell
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  App::import('Model', 'ConnectionManager');

  class DatabaseShell extends AppShell {
  	var $uses = array('Identifier');
    
    function main() {
      // Create a view for Grouper to use as a JDBC source in the Grouper 
      // sources.xml configuration.
         
      // Determine which database is being used.
      $prefix = "";
      $db =& ConnectionManager::getDataSource('default');
      $db_driver = split("/", $db->config['datasource'], 2);
      
      if(isset($db->config['prefix'])) {
        $prefix = $db->config['prefix'];
      }        

      // The view syntax is different for each database product.
      if ($db_driver[1] == 'Mysql') {
      	$this->Identifier->query("CREATE VIEW " . $prefix . "grouper_subjects AS
SELECT
    cm_co_people.id AS 'id',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.given),' ',GROUP_CONCAT(DISTINCT cm_names.family)) AS 'name',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.family),',',GROUP_CONCAT(DISTINCT cm_names.given)) AS 'lfname',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.given),' ',GROUP_CONCAT(DISTINCT cm_names.family),' (',GROUP_CONCAT(DISTINCT cm_cos.description),')') AS 'description',
    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),',',1) AS 'loginid1',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',2),',',-1) AS 'loginid2',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',3),',',-1) AS 'loginid3',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',4),',',-1) AS 'loginid4',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',5),',',-1) AS 'loginid5',
    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),',',1) AS 'email1',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',2),',',-1) AS 'email2',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',3),',',-1) AS 'email3',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',4),',',-1) AS 'email4',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',5),',',-1) AS 'email5'
FROM
    cm_co_people
    LEFT JOIN cm_names ON cm_co_people.id = cm_names.co_person_id
    LEFT JOIN cm_identifiers ON cm_co_people.id = cm_identifiers.co_person_id
    LEFT JOIN cm_email_addresses ON cm_co_people.id = cm_email_addresses.co_person_id
    LEFT JOIN cm_cos ON cm_co_people.co_id = cm_cos.id
GROUP BY 
    cm_co_people.id
");
        } else {
          // Only support MySQL for now so throw exception.
          throw new RuntimeException('Grouper Provisioner requires MySQL at this time');
        }
      
      
      $this->out(_txt('se.done'));
    }
  }
