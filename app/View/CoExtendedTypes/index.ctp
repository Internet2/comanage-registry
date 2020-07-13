<?php
/**
 * COmanage Registry CO Extended Type Index View
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_extended_types.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Which attribute are we currently looking at? If not set, we'll default
  // to Identifier.type since that's what was specified in CoExtendedTypesController.
  $attr = 'Identifier.type';

  if(isset($this->request->query['attr'])) {
    $attr = filter_var($this->request->query['attr'],FILTER_SANITIZE_STRING);
  }

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] =  $this->Html->link(
      _txt('op.add-a',array(_txt('ct.co_extended_types.1'))),
      array('controller' => 'co_extended_types',
        'action' => 'add',
        'co' => $cur_co['Co']['id'],
        '?' => array(
          'attr' => $attr
        )),
      array('class' => 'addbutton')
    );

    $params['topLinks'][] =  $this->Html->link(
      _txt('op.restore.types'),
      array('controller' => 'co_extended_types',
        'action' => 'addDefaults',
        'co' => $cur_co['Co']['id'],
        '?' => array(
          // Strictly speaking, we don't need to pass attr as a query string,
          // but it's helpful because it will look like Model.field and if
          // that's at the end of the URL it will be parsed as a doctype (eg: .json).
          'attr' => $attr,
        )),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>
<div id="extendedTypesFilter" class="top-filter">
  <!-- Selector for which Extended Type to manage -->
  <form method="get" action="<?php print $this->Html->url('/');?>co_extended_types/index/co:<?php print $cur_co['Co']['id'] ?>">
    <span class="filters">
      <label class="select-name" for="attr"><?php print _txt('fd.et.forattr'); ?></label>
      <select name="attr" id="attr">
        <?php foreach($vv_supported_attrs as $a => $label): ?>
        <option value="<?php print $a; ?>"<?php if($attr == $a) print " selected"; ?>><?php print $label; ?></option>
        <?php endforeach; ?>
      </select>
    </span>
    <span class="submit-button">
      <input type="submit" value="<?php print _txt('op.filter')?>"/>
    </span>
  </form>
</div>

<div class="table-container">
  <table id="co_extended_types">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
        <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
        <th><?php print $this->Paginator->sort('display_name', _txt('fd.name.d')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_extended_types as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if(isset($vv_supported_attrs[ $c['CoExtendedType']['attribute'] ])) {
              print $vv_supported_attrs[ $c['CoExtendedType']['attribute'] ];
            } else {
              print filter_var($c['CoExtendedType']['attribute'],FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            print $this->Html->link($c['CoExtendedType']['name'],
                                    array('controller' => 'co_extended_types',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoExtendedType']['id']));
          ?>
        </td>
        <td><?php print filter_var($c['CoExtendedType']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print _txt('en.status', null, $c['CoExtendedType']['status']); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'co_extended_types',
                                            'action' => 'edit',
                                            $c['CoExtendedType']['id']),
                                      array('class' => 'editbutton')) . "\n";
            }

            if($c['CoExtendedType']['attribute'] != 'Name.type'
               || $c['CoExtendedType']['name'] != NameEnum::Official) {
              // NameEnum::Official is required and cannot be deleted (CO-955)

              if($permissions['delete']) {
                // We include attr in the request so we know where to redirect to when we're done
                print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('js.remove') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'controller' => 'co_extended_types',
                      'action' => 'delete',
                      $c['CoExtendedType']['id'],
                      '?' => array('attr' => $attr)
                    )
                  ) . '\',\''
                  . _txt('op.remove') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''    // dialog cancel button
                  . _txt('op.remove') . '\',[\''   // dialog title
                  . filter_var(_jtxt($c['CoExtendedType']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\']);">'
                  . _txt('op.delete')
                  . '</button>';
              }
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
  
<?php
  print $this->element("pagination");
