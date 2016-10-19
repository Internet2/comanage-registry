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
<div id="changeLog">
  <a href="#tabs-changelog" class="fieldGroupNameCl">
    <i class="material-icons">add_box</i>
    <?php print _txt('fd.changelog'); ?>
  </a>
  <ul class="fields" style="display: none;">
    <li>
      <div id="tabs-changelog" class="additionalinfo">

        <table id="<?php print $this->action . "_" . $modelu . "_changelog"; ?>" class="ui-widget">
          <tbody>
          <tr class="line<?php print ($l % 2);
                               print (${$modelpl}[0][$req]['deleted'] ? ' deleted' : '');
                               $l++; ?>">
            <th>
              <?php print _txt('fd.deleted'); ?>
            </th>
            <td>
              <?php print (${$modelpl}[0][$req]['deleted'] ? _txt('fd.yes') : _txt('fd.no')); ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <th>
              <?php print _txt('fd.revision'); ?>
            </th>
            <td>
              <?php
              print ${$modelpl}[0][$req]['revision'];
              ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <th>
              <?php print _txt('fd.modified'); ?>
            </th>
            <td>
              <?php
              print $this->Time->format(${$modelpl}[0][$req]['modified'], "%c $vv_tz", false, $vv_tz);
              ?>
            </td>
          </tr>
          <tr class="line<?php print ($l % 2); $l++; ?>">
            <th>
              <?php print _txt('fd.actor'); ?>
            </th>
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
      </div>
    </li>
  </ul>
</div>

<script type="text/javascript">
  $(function() {
    // Explorer menu toggle for changelog
    $(".fieldGroupNameCl").click(function (event) {
      event.preventDefault();
      $(this).next(".fields").slideToggle("fast");
      // toggle the +/- icon:
      if ($(this).find(".material-icons").text() == "indeterminate_check_box") {
        $(this).find(".material-icons").text("add_box");
      } else {
        $(this).find(".material-icons").text("indeterminate_check_box");
      }
    });

    <?php if(${$modelpl}[0][$req]['deleted']): ?>
      // Add "Deleted" text next to page title, if we're looking at a deleted entity
      $(".pageTitle h2").append('<span class="deleted"><?php print _txt('fd.deleted'); ?></span>');
    <?php endif ?>
  });
</script>