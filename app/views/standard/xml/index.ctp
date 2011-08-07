<?php
  /*
   * COmanage Gears Standard REST Index XML View
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
  
  // Get a pointer to our model
  $model = $this->name;                     // Unlike in the controllers, this is CamelPlural
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  if(isset($$modelpl))
  {
    $x = "";
    
    foreach($$modelpl as $m)
    {
      $xd = "";
      
      foreach(array_keys($m[$req]) as $k)
      {
        if($m[$req][$k] != null)
        {
          // Some attributes are treated specially
          
          switch($k)
          {
          case 'CoPersonId':
            $xd .= $this->Xml->elem("Person",
                                    array("Type" => "CO",
                                          "Id" => $m[$req][$k]));
            break;
          case 'CoPersonRoleId':
            $xd .= $this->Xml->elem("Person",
                                    array("Type" => "CoRole",
                                          "Id" => $m[$req][$k]));
            break;
          case 'OrgIdentityId':
            $xd .= $this->Xml->elem("Person",
                                    array("Type" => "Org",
                                          "Id" => $m[$req][$k]));
            break;
          default:
            $xd .= $this->Xml->elem($k, null, $m[$req][$k]);
            break;
          }
        }
      }
        
      if(($req == 'CoPerson' || $req == 'OrgIdentity')
         && isset($m['Name']))
      {
        // We treat Name specially and copy it over
        
        $xn = "";
      
        foreach(array_keys($m['Name']) as $k)
        {
          if(in_array($k, array('Honorific', 'Given', 'Middle', 'Family', 'Suffix'))
             && $m['Name'][$k] != null)
            $xn .= $this->Xml->elem($k, null, $m['Name'][$k]);
        }
      
        $xd .= $this->Xml->elem("Name",
                                array("Type" => $m['Name']['Type']),
                                $xn);
      }

      if($req == 'CoPersonRole')
      {
        // For CO Person Roles, we need to check for extended attributes.
        
        foreach(array_keys($m) as $ak)
        {
          if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $ak))
          {
            $xn = "";
            
            foreach(array_keys($m[$ak]) as $ea)
              if(!in_array($ea, array('Id', 'CoPersonRoleId', 'Created', 'Modified'))
                 && $m[$ak][$ea] != null)
                $xn .= $this->Xml->elem($ea, null, $m[$ak][$ea]);
            
            $xd .= $this->Xml->elem("ExtendedAttributes", null, $xn);
            
            break;
          }
        }
      }

      $x .= $this->Xml->elem($req,
                             array("Version" => "1.0",    // XXX this needs to be set by the controller
                                   "Id" => $m[$req]['Id']),
                             $xd) . "\n";
    }
    
    echo $this->Xml->header() . "\n";
    echo $this->Xml->elem($model,
                          array("Version" => "1.0"),    // XXX this needs to be set by the controller
                          $x) . "\n";
  }
?>