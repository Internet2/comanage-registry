<?php
  /**
   * COmanage Registry Group Nestings View
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
   * @since         COmanage Registry v4.0.0
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */

  // Determine if fields are editable or viewable
  $dok = false;
  $e = false;
  $v = false;

  if($permissions['buildnest'])
    $e = true;

  if($permissions['deletenest'])
    $dok = true;

  if(($permissions['view'])
    || (isset($co_groups[0]['CoGroup']['id'])
      && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
    || (isset($co_groups[0]['CoGroup']['open']) 
      && $co_groups[0]['CoGroup']['open']))
    $v = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$v)
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($permissions['index']) {
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_groups';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $args['search.auto']= 'f';
    $args['search.noadmin'] = '1';
    $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);
  }
  if($e) {
    $crumbTxt = _txt('op.edit-a', array(_txt('ct.co_groups.1')));
  } else {
    $crumbTxt = _txt('op.view-a', array(_txt('ct.co_groups.1')));
  }
  $this->Html->addCrumb($crumbTxt);


  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  print $this->element("pageTitleAndButtons", $params);
  if(file_exists(APP . "View/CoGroups/tabs.inc")) {
    include(APP . "View/CoGroups/tabs.inc");
  }

  // Index the nested groups for rendering purposes
  $nGroups = array();

  if(!empty($co_groups[0]['CoGroupNesting'])) {
    foreach($co_groups[0]['CoGroupNesting'] as $n) {
      // We filter_var here since these names are probably going to be printed
      $nGroups[ $n['id'] ] = filter_var($n['CoGroup']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
    }
  }
?>

<div class="table-container">
  <div id="nestings">
    <?php foreach(array('source' => 'CoGroupNesting',
      'target' => 'SourceCoGroupNesting') as $k => $m): ?>

      <h2 class="subtitle"><?php print _txt('fd.co_group.'.$k.'.pl'); ?></h2>
      <p><em><?php print _txt('fd.co_group.'.$k.'.desc', array(filter_var($co_groups[0]['CoGroup']['name'],FILTER_SANITIZE_STRING))); ?></em></p>

      <?php if($e && $k == 'source' && !empty($co_groups[0]['CoGroup']['id'])
        && !$co_groups[0]['CoGroup']['auto']): ?>
        <ul class="widget-actions">
          <li>
            <?php
              // Add a nested group
              print $this->Html->link(
                _txt('op.add-a', array(_txt('ct.co_group_nestings.1'))),
                array(
                  'controller' => 'co_group_nestings',
                  'action'     => 'add',
                  'cogroup'    => $co_groups[0]['CoGroup']['id']
                ),
                array('class' => 'addbutton')
              );
            ?>
          </li>
        </ul>
      <?php endif; ?>

      <?php $tableCols = 1; ?>
      <table class="common-table">
        <thead>
          <tr>
            <th><?php print _txt('fd.name'); ?></th>
            <?php if($dok): // only action is delete/remove ?>
              <th class="thinActionButtonsCol"><?php print _txt('fd.actions'); ?></th>
              <?php $tableCols = 2; ?>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($co_groups[0][$m])): ?>
            <?php foreach($co_groups[0][$m] as $n): ?>
              <tr>
                <td><?php
                    // The model that we want to render, as contain'd by CoGroupNesting
                    $gnm = ($k == 'source' ? "CoGroup" : "TargetCoGroup");

                    if($e) {
                      print $this->Html->link($n[$gnm]['name'],
                        array('controller' => 'co_groups',
                          'action' => $this->action,
                          $n[$gnm]['id']));
                    } else {
                      print filter_var($n[$gnm]['name'],FILTER_SANITIZE_SPECIAL_CHARS);
                    }
                    if($n['negate']) {
                      print " (" . _txt('fd.co_group_nesting.negated') . ")";
                    }
                  ?>
                </td>

                <?php if($dok): ?>
                  <td class="actions">
                    <?php
                      print '<a class="deletebutton" title="' . _txt('op.remove')
                        . '" onclick="javascript:js_confirm_generic(\''
                        . _txt('js.remove.nesting') . '\',\''    // dialog body text
                        . $this->Html->url(              // dialog confirm URL
                          array(
                            'controller' => 'co_group_nestings',
                            'action' => 'delete',
                            $n['id']
                          )
                        ) . '\',\''
                        . _txt('op.remove') . '\',\''    // dialog confirm button
                        . _txt('op.cancel') . '\',\''    // dialog cancel button
                        . _txt('op.remove') . '\',[\''   // dialog title
                        . filter_var(_jtxt($n[$gnm]['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                        . '\']);">'
                        . _txt('op.remove')
                        . '</a>';
                    ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; // $n ?>
          <?php else: ?>
            <tr><td colspan="<?php print $tableCols; ?>"><?php print _txt('in.co_group.'.$k.'.none') ?></td></tr>
          <?php endif; // $m ?>
        </tbody>
      </table>
    <?php endforeach; // $k, $m ?>
  </div>
</div>
