<?php
/**
 * COmanage Registry Changelog Fields
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  $modelu = Inflector::underscore($req);

  $l = 0;
  
  if($this->action == 'add' || $this->action == 'invite') {
    // There can't be a changelog yet...
    return;
  }
?>
<div>
  <h2>
    <?php print _txt('fd.changelog'); ?>
  </h2>
</div>

<table id="<?php print $this->action . "_" . $modelu . "_changelog"; ?>" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%">
        <b><?php print _txt('fd.deleted'); ?></b><br />
      </td>
      <td>
        <?php print (${$modelpl}[0][$req]['deleted'] ? _txt('fd.yes') : _txt('fd.no')); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%">
        <b><?php print _txt('fd.revision'); ?></b><br />
      </td>
      <td>
        <?php
          print ${$modelpl}[0][$req]['revision'];
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%">
        <b><?php print _txt('fd.modified'); ?></b><br />
      </td>
      <td>
        <?php
          print $this->Time->niceShort(${$modelpl}[0][$req]['modified']);
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td width="50%">
        <b><?php print _txt('fd.actor'); ?></b><br />
      </td>
      <td>
        <?php
          if(!empty(${$modelpl}[0][$req]['actor_identifier'])) {
            print ${$modelpl}[0][$req]['actor_identifier'];
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
