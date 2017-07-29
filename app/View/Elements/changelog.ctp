<?php
/**
 * COmanage Registry Changelog Fields
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
    <em class="material-icons">add_box</em>
    <?php print _txt('fd.changelog'); ?>
  </a>
  <ul id="tabs-changelog" class="fields data-list data-table" style="display: none;">
    <li>
      <div class="table-container">
        <table id="<?php print $this->action . "_" . $modelu . "_changelog"; ?>">
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

                $mkey = $modelu . '_id';

                if(!empty(${$modelpl}[0][$req][$mkey])) {
                  print "&nbsp;(" . _txt('er.archived') . ") " .
                        $this->Html->link(_txt('op.view.current'),
                                          array('controller' => $modelpl,
                                                'action' => $this->action,
                                                ${$modelpl}[0][$req][$mkey]));
                }
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
                print filter_var(${$modelpl}[0][$req]['actor_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
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

    <?php if(!empty(${$modelpl}[0][$req][$mkey])): ?>
    // Add "Archived" text next to page title, if we're looking at an archived entity
    $(".pageTitle h1").append('<span class="archived"><?php print _txt('fd.archived'); ?></span>');
    <?php endif ?>
    <?php if(${$modelpl}[0][$req]['deleted']): ?>
      // Add "Deleted" text next to page title, if we're looking at a deleted entity
      $(".pageTitle h1").append('<span class="deleted"><?php print _txt('fd.deleted'); ?></span>');
    <?php endif ?>
  });
</script>