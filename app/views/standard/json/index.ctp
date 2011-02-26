<?php
  /*
   * COmanage Gears Standard REST Index JSON View
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
  $modelcc = $model;

  if(isset($$modelpl))
  {
    $ms = array();
    
    foreach($$modelpl as $m)
    {
      $a = array("Version" => "1.0",    // XXX this needs to be set by the controller
                 "Id" => $m[$req]['Id']);
      
      foreach(array_keys($m[$req]) as $k)
      {
        if($m[$req][$k] != null)
        {
          // Some attributes are treated specially
          
          switch($k)
          {
          case 'CoPersonId':
            $a['Person'] = array('Type' => 'CO',
                                 'Id' => $m[$req][$k]);
            break;
          case 'OrgPersonId':
            $a['Person'] = array('Type' => 'Org',
                                 'Id' => $m[$req][$k]);
            break;
          default:
            $a[$k] = $m[$req][$k];
            break;
          }
        }
      }
      
      if(($req == 'CoPerson' || $req == 'OrgPerson')
         && isset($m['Name']))
      {
        // We treat Name specially and copy it over
      
        foreach(array_keys($m['Name']) as $k)
        {
          if(in_array($k, array('Honorific', 'Given', 'Middle', 'Family', 'Suffix', 'Type'))
             && $m['Name'][$k] != null)
            $a['Name'][$k] = $m['Name'][$k];
        }
      
      }
        
      $ms[] = $a;
    }

    echo json_encode(array("ResponseType" => $modelcc,
                           "Version" => "1.0",    // XXX this needs to be set by the controller
                           $modelcc => $ms)) . "\n";
  }
?>