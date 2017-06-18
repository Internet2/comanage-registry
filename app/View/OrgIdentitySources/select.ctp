<?php
/**
 * COmanage Registry Org Identity Source Select View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $crumbTxt = _txt('op.select-a',array(_txt('ct.org_identity_sources.1')));
  $this->Html->addCrumb($crumbTxt);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="org_identity_sources">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach($vv_org_id_sources as $id => $desc): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print filter_var($desc,FILTER_SANITIZE_SPECIAL_CHARS);
          ?>
        </td>
        <td>
          <?php
            if($permissions['select']) {
              $args = array(
                'controller' => 'org_identity_sources',
                'action' => 'query',
                $id
              );

              if(!empty($this->request->params['named']['copetitionid'])) {
                $args['copetitionid'] = filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS);
              }

              print $this->Html->link(_txt('op.select'),
                                      $args,
                                      array('class' => 'forwardbutton')) . "\n";
            }
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>

  </table>
</div>
