<!--
  /*
   * COmanage Gears Menu Page
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
-->
<h1 class="ui-state-default">COmanage Registry</h1>

<table id="mainmenu" width="100%">
  <tbody>
    <tr>
      <!-- Person Operations -->
      <td width="33%" valign="top">
        <?php
          if(isset($permissions['menu']['orgprofile']) && $permissions['menu']['orgprofile'])
          {
            echo $html->link("View My Home Identity",
                             array('controller' => 'org_identities', 'action' => 'view', $this->Session->read('Auth.User.org_identity_id')),
                             array('class' => 'menuitembutton'));
          }

          if(isset($permissions['menu']['coprofile']) && $permissions['menu']['coprofile'])
          {
            echo $html->link("Manage My CO Identity",
                             array('controller' => 'co_person_roles', 'action' => 'editself'),
                             array('class' => 'menuitembutton'));
          }

          if(isset($permissions['menu']['cogroups']) && $permissions['menu']['cogroups'])
          {
            echo $html->link("View/Edit Groups",
                             array('controller' => 'co_groups', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
        ?>
      </td>

      <!-- CO Operations -->
      <td width="33%" valign="top">
        <?php
          if(isset($permissions['menu']['orgidentities']) && $permissions['menu']['orgidentities'])
          {
            echo $html->link("Organizational Identities",
                             array('controller' => 'org_identities', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
          
          if(isset($permissions['menu']['cos']) && $permissions['menu']['cos'])
          {
            echo $html->link("My Population",
                             array('controller' => 'co_person_roles', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
          
          if(isset($permissions['menu']['extattrs']) && $permissions['menu']['extattrs'])
          {
            echo $html->link("Extended Attributes",
                             array('controller' => 'co_extended_attributes', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
          
          if(isset($permissions['menu']['cous']) && $permissions['menu']['cous'])
          {
            echo $html->link("COUs",
                             array('controller' => 'cous', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
        ?>
      </td>

      <!-- Platform Operations -->
      <td width="33%" valign="top">
        <?php
          if(isset($permissions['menu']['admin']) && $permissions['menu']['admin'])
          {
            echo $html->link("COs",
                             array('controller' => 'cos', 'action' => 'index'),
                             array('class' => 'menuitembutton'));

            echo $html->link("Organizations",
                             array('controller' => 'organizations', 'action' => 'index'),
                             array('class' => 'menuitembutton'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>