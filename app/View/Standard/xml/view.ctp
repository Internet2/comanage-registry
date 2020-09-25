<?php
/**
 * COmanage Registry Standard XML Index View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  // Get a pointer to our model
  $model = $this->name;                     // Unlike in the controllers, this is CamelPlural
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  if(isset($$modelpl))
  {
    $ms = array();
    
    foreach($$modelpl as $m)
    {
      $a = array("@Version" => $vv_model_version,
                 "@Id" => $m[$req]['Id']);
      
      foreach(array_keys($m[$req]) as $k)
      {
        if($m[$req][$k] !== null)
        {
          // Some attributes are treated specially
          
          if($req == 'CoOrgIdentityLink') {
            $a[$k] = $m[$req][$k];
          } else {
            switch($k)
            {
            case 'CoDepartmentId':
              $a['Person'] = array('Type' => 'Dept',
                                   'Id' => $m[$req][$k]);
              break;
            case 'CoGroupId':
              $a['Person'] = array('Type' => 'Group',
                                   'Id' => $m[$req][$k]);
              break;
            case 'CoPersonId':
              $a['Person'] = array('Type' => 'CO',
                                   'Id' => $m[$req][$k]);
              break;
            case 'CoPersonRoleId':
              $a['Person'] = array('Type' => 'CoRole',
                                   'Id' => $m[$req][$k]);
              break;
            case 'OrganizationId':
              $a['Person'] = array('Type' => 'Organization',
                                   'Id' => $m[$req][$k]);
              break;
            case 'OrgIdentityId':
              $a['Person'] = array('Type' => 'Org',
                                   'Id' => $m[$req][$k]);
              break;
            default:
              $a[$k] = $m[$req][$k];
              break;
            }
          }
        }
      }
      
      if($req == 'CoPersonRole') {
        // For CO Person Roles, we need to check for extended attributes.
        
        foreach(array_keys($m) as $ak) {
          if(preg_match('/Co[0-9]+PersonExtendedAttribute/', $ak)) {
            foreach(array_keys($m[$ak]) as $ea) {
              $a['ExtendedAttributes'][$ea] = $m[$ak][$ea];
            }
            
            break;
          }
        }
      }
      
      $ms[] = $a;
    }
    
    $xa = array(
      $model => array(
        '@Version' => '1.0',
        $req => $ms
      )
    );
    $xobj = Xml::fromArray($xa, array('format' => 'tags'));
    print $xobj->asXML();
  }
