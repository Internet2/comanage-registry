<?php
/**
 * COmanage Registry Make Attribute Definition Script Shell
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  class MakeAttributeDefScriptShell extends AppShell {

    // The base stem in Grouper under which all COmanage details are stored
    // including attribute definitions and attribute definition names.
    private $comanageBaseStem = null;

    // The delineator used for stems in the Grouper deployment.
    private $stemDelineator = null;

    // Grouper AttributeDefName(s) used for storing COmanage data
    // that would be found in a row in the cm_co_groups table.
    private $groupCoIdAttributeName = null;
    private $groupIdAttributeName = null;
    private $groupStatusAttributeName = null;

    // Grouper AttributeDefName(s) used for storing COmanage data
    // that would be found in a row in the cm_co_group_members table.
    private $groupMembersIdAttributeName = null;
    private $groupMembersCoGroupIdAttributeName = null;
    private $groupMembersCoPersonIdAttributeName = null;
    
    function main() {

      // Read configuration details about COmanage Grouper intergration.
      $this->comanageBaseStem = Configure::read('Grouper.COmanage.baseStem');
      $this->stemDelineator = Configure::read('Grouper.COmanage.grouperStemDelineator');

      $this->groupCoIdAttributeName = Configure::read('Grouper.COmanage.groupCoIdAttributeName');
      $this->groupIdAttributeName = Configure::read('Grouper.COmanage.groupIdAttributeName');
      $this->groupStatusAttributeName = Configure::read('Grouper.COmanage.groupStatusAttributeName');

      $this->groupMembersIdAttributeName = Configure::read('Grouper.COmanage.groupMembersIdAttributeName');
      $this->groupMembersCoGroupIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoGroupIdAttributeName');
      $this->groupMembersCoPersonIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoPersonIdAttributeName');

      // Examine the configured base stem and create array of stem names that
      // need to be created.
      $stems = explode($this->stemDelineator, $this->comanageBaseStem);

      // Begin constructing the Grouper Shell script.
      $script = '';

      // Initiate a Grouper root session.
      $script = $script . <<<EOT
grouperSession = GrouperSession.startRootSession();

EOT;

      // Create the first or root stem.
      $script = $script . <<<EOT
addRootStem("$stems[0]", "$stems[0]");

EOT;

      // Create any necessary stems in the root stem.
      if (count($stems) > 1) {
        $parent = $stems[0];
        for ($i = 1; $i < count($stems); $i++) {
          $child = $stems[$i];
          $script = $script . <<<EOT
addStem("$parent", "$child", "$child");

EOT;
          $parent = $parent . $this->stemDelineator . $stems[$i];
        }
      }
      
      // Grab handle to the COmanage base stem.
      $script = $script . <<<EOT

comanageStem = StemFinder.findByName(grouperSession, "$this->comanageBaseStem");

EOT;

      // Create attribute definitions and attribute definition names.
      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupCoIdAttributeName);
      $script = $script . <<<EOT
groupCoIdAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupCoIdAttributeDef.setAssignToGroup(true);
groupCoIdAttributeDef.setValueTypeDb("integer");
groupCoIdAttributeDef.store();
comanageStem.addChildAttributeDefName(groupCoIdAttributeDef, "$attributeName", "$attributeName");

EOT;

      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupIdAttributeName);
      $script = $script . <<<EOT
groupIdAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupIdAttributeDef.setAssignToGroup(true);
groupIdAttributeDef.setValueTypeDb("string");
groupIdAttributeDef.store();
comanageStem.addChildAttributeDefName(groupIdAttributeDef, "$attributeName", "$attributeName");

EOT;

      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupStatusAttributeName);
      $script = $script . <<<EOT
groupStatusAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupStatusAttributeDef.setAssignToGroup(true);
groupStatusAttributeDef.setValueTypeDb("string");
groupStatusAttributeDef.store();
comanageStem.addChildAttributeDefName(groupStatusAttributeDef, "$attributeName", "$attributeName");

EOT;

      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupMembersIdAttributeName);
      $script = $script . <<<EOT
groupMembersIdAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupMembersIdAttributeDef.setAssignToImmMembership(true);
groupMembersIdAttributeDef.setValueTypeDb("string");
groupMembersIdAttributeDef.store();
comanageStem.addChildAttributeDefName(groupMembersIdAttributeDef, "$attributeName", "$attributeName");

EOT;

      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupMembersCoGroupIdAttributeName);
      $script = $script . <<<EOT
groupMembersCoGroupIdAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupMembersCoGroupIdAttributeDef.setAssignToImmMembership(true);
groupMembersCoGroupIdAttributeDef.setValueTypeDb("string");
groupMembersCoGroupIdAttributeDef.store();
comanageStem.addChildAttributeDefName(groupMembersCoGroupIdAttributeDef, "$attributeName", "$attributeName");

EOT;

      $attributeName = str_replace(array($this->comanageBaseStem, $this->stemDelineator), '', $this->groupMembersCoPersonIdAttributeName);
      $script = $script . <<<EOT
groupMembersCoPersonIdAttributeDef = comanageStem.addChildAttributeDef("$attributeName", AttributeDefType.attr);
groupMembersCoPersonIdAttributeDef.setAssignToImmMembership(true);
groupMembersCoPersonIdAttributeDef.setValueTypeDb("integer");
groupMembersCoPersonIdAttributeDef.store();
comanageStem.addChildAttributeDefName(groupMembersCoPersonIdAttributeDef, "$attributeName", "$attributeName");

EOT;

      file_put_contents($this->args[0], $script);

    }
  }
