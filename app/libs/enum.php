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
  
  class AdministratorEnum
  {
    const NoAdmin       = 'N';
    const CoOrCouAdmin  = 'C';
    const CoAdmin       = 'O';
/*    
    public $from_api = array(
      "NoAdmin"       => NoAdmin,
      "CoOrCouAdmin"  => CoOrCouAdmin,
      "CoAdmin"       => CoAdmin
    );

    public $to_api = array(
      NoAdmin       => "NoAdmin",
      CoOrCouAdmin  => "CoOrCouAdmin",
      CoAdmin       => "CoAdmin"
    );*/
  }

  class AffiliationEnum
  {
    const Faculty       = 'F';
    const Student       = 'SU';
    const Staff         = 'SA';
    const Alum          = 'AL';
    const Member        = 'M';
    const Affiliate     = 'AF';
    const Employee      = 'E';
    const LibraryWalkIn = 'L';
  }

  class NSFCitizenshipEnum
  {
    const USCitizen            = 'US';
    const USPermanentResident  = 'P';
    const Other                = 'O';
  }

  class ContactEnum
  {
    const Fax         = 'F';
    const Home        = 'H';
    const Mobile      = 'M';
    const Office      = 'O';
    const Postal      = 'P';
    const Forwarding  = 'R';
/*
    public $from_api = array(
      "Fax"         => Fax,
      "Home"        => Home,
      "Mobile"      => Mobile,
      "Office"      => Office,
      "Postal"      => Postal,
      "Forwarding"  => Forwarding
    );

    public $to_api = array(
      Fax         => "Fax",
      Home        => "Home",
      Mobile      => "Mobile",
      Office      => "Office",
      Postal      => "Postal",
      Forwarding  => "Forwarding"
    );*/
  }

  class NSFDisabilityEnum
  {
    const Hearing     = 'H';
    const Visual      = 'V';
    const Mobility    = 'M';
    const Other       = 'O';

  }

  class NSFEthnicityEnum
  {
    const Hispanic    = 'H';
    const NotHispanic = 'N';
  }

  class NSFGenderEnum
  {
    const Male        = 'M';
    const Female      = 'F';
  }

  class IdentifierEnum
  {
    const ePPN    = 'EP';
    const ePTID   = 'ET';
    const Mail    = 'M';
    const OpenID  = 'OP';
    const UID     = 'U';
    /*
    public $from_api = array(
      "ePPN"    => ePPN,
      "ePTID"   => ePTID,
      "Mail"    => Mail,
      "OpenID"  => OpenID,
      "UID"     => UID
    );
    
    public $to_api = array(
      ePPN    => "ePPN",
      ePTID   => "ePTID",
      Mail    => "Mail",
      OpenID  => "OpenID",
      UID     => "UID"
    );*/
  }
  
  class NameEnum
  {
    const Author    = 'A';
    const FKA       = 'F';
    const Official  = 'O';
    const Preferred = 'P';
    
/*    public $from_api = array(
      'Author'    => Author,
      'FKA'       => FKA,
      'Official'  => Official,
      'Preferred' => Preferred
    );
    
    public $to_api = array(
      Author    => 'Author',
      FKA       => 'FKA',
      Official  => 'Official',
      Preferred => 'Preferred'
    );*/
  }

  class NSFRaceEnum
  {
    const Asian            = 'A';
    const AmericanIndian   = 'I';
    const Black            = 'B';
    const NativeHawaiian   = 'N';
    const White            = 'W';
  }

  class RequiredEnum
  {
    const Required      = 1;
    const Optional      = 0;
    const NotPermitted  = -1;
    /*
    public $from_api = array(
      'Required'      => Required,
      'Optional'      => Optional,
      'NotPermitted'  => NotPermitted
    );
    
    public $to_api = array(
      Required      => 'Required',
      Optional      => 'Optional',
      NotPermitted  => 'NotPermitted'
    );*/
  }
  
  class StatusEnum
  {
    const Active    = 'A';
    const Deleted   = 'D';
    const Invited   = 'I';
    const Pending   = 'P';
    const Suspended = 'S';
    const Declined  = 'X';
    /*
    public $from_api = array(
      "Active"    => Active,
      "Deleted"   => Deleted,
      "Invited"   => Invited,
      "Pending"   => Pending,
      "Suspended" => Suspended,
      "Declined"  => Declined
    );
    
    public $to_api = array(
      Active    => "Active",
      Deleted   => "Deleted",
      Invited   => "Invited",
      Pending   => "Pending",
      Suspended => "Suspended",
      Declined  => "Declined"
    );*/
  }
  
  // old style enums below, deprecated
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
    'EP' => 'ePPN',
    'ET' => 'ePTID',
    'M' => 'Mail',
    'OP' => 'OpenID',
    'U' => 'UID'
  );
  
  $identifier_ti = array(
    'ePPN' => 'EP',
    'ePTID' => 'ET',
    'Mail' => 'M',
    'OpenID' => 'OP',
    'UID' => 'U'
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
