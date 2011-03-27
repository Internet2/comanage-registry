<?php
  /*
   * COmanage Gears Enumerations
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

  global $affil_t, $affil_ti;
  global $contact_t, $contact_ti;
  global $extattr_t, $extattr_ti;
  global $identifier_t, $identifier_ti;
  global $name_t, $name_ti;
  global $status_t, $status_ti;

  $affil_t = array(
    'faculty' => 'Faculty',
    'student' => 'Student',
    'staff' => 'Staff',
    'alum' => 'Alum',
    'member' => 'Member',
    'affiliate' => 'Affiliate',
    'employee' => 'Employee',
    'library-walk-in' => 'Library Walk-In'    
  );
  
  $affil_ti = array(
    'Faculty' => 'faculty',
    'Student' => 'student',
    'Staff' => 'staff',
    'Alum' => 'alum',
    'Member' => 'member',
    'Affiliate' => 'affiliate',
    'Employee' => 'employee',
    'Library Walk-In' => 'library-walk-in'    
  );
  
  $contact_t = array(
    'F' => 'Fax',
    'H' => 'Home',
    'M' => 'Mobile',
    'O' => 'Office',
    'P' => 'Postal',
    'R' => 'Forwarding'
  );
  
  $contact_ti = array(
    'Fax' => 'F',
    'Home' => 'H',
    'Mobile' => 'M',
    'Office' => 'O',
    'Postal' => 'P',
    'Forwarding' => 'R'
  );
  
  /*
  $extattr_t = array(
    'INTEGER' => 'INTEGER',
    'TIMESTAMP' => 'TIMESTAMP',
    'VARCHAR(32)' => 'VARCHAR(32)'
  );
  
  $extattr_ti = array(
    'INTEGER' => 'I',
    'TIMESTAMP' => 'T',
    'VARCHAR(32)' => 'V3'
  );
  */
  
  $identifier_t = array(
    'eppn' => 'ePPN',
    'eptid' => 'ePTID',
    'mail' => 'Mail',
    'openid' => 'OpenID',
    'uid' => 'UID'
  );
  
  $identifier_ti = array(
    'ePPN' => 'eppn',
    'ePTID' => 'eptid',
    'Mail' => 'mail',
    'OpenID' => 'openid',
    'Username' => 'username'
  );
  
  $name_t = array(
    'A' => 'Author',
    'F' => 'FKA',
    'O' => 'Official',
    'P' => 'Preferred'
  );
  
  $name_ti = array(
    'Author' => 'A',
    'FKA' => 'F',
    'Official' => 'O',
    'Preferred' => 'P'
  );

  $status_t = array(
    'A' => 'Active',
    'D' => 'Deleted',
    'I' => 'Invited',
    'P' => 'Pending',
    'S' => 'Suspended',
    'X' => 'Declined'
  );
  
  $status_ti = array(
    'Active' => 'A',
    'Deleted' => 'D',
    'Invited' => 'I',
    'Pending' => 'P',
    'Suspended' => 'S',
    'Declined' => 'X'
  );
?>