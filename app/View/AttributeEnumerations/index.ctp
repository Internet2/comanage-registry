<?php
/**
 * COmanage Registry Attribute Enumeration Index View
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
  $this->Html->addCrumb(_txt('ct.attribute_enumerations.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $args = array(
      'controller' => 'attribute_enumerations',
      'action' => 'add'
    );
    
    // We expect a CO if org identities are not pooled, or if we're editing CO-specific enumerations
    if(!empty($cur_co['Co']['id'])) {
      $args['co'] = $cur_co['Co']['id'];
    }
    
    $params['topLinks'][] =  $this->Html->link(
      _txt('op.add-a',array(_txt('ct.attribute_enumerations.1'))),
      $args,
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>
<div class="table-container">
  <table id="attribute_enumerations">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
        <th><?php print $this->Paginator->sort('dictionary', _txt('ct.dictionaries.1')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($attribute_enumerations as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if(isset($vv_supported_attrs[ $c['AttributeEnumeration']['attribute'] ])) {
              print $this->Html->link($vv_supported_attrs[ $c['AttributeEnumeration']['attribute'] ],
                                      array('controller' => 'attribute_enumerations',
                                            'action' => 'edit',
                                            $c['AttributeEnumeration']['id']));
            } else {
              print filter_var($c['AttributeEnumeration']['attribute'],FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </td>
        <td>
          <?php
            print $this->Html->link($vv_available_dictionaries[ $c['AttributeEnumeration']['dictionary_id'] ],
                                    array('controller' => 'dictionaries',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['AttributeEnumeration']['dictionary_id']));
          ?>
        </td>
        <td><?php print _txt('en.status', null, $c['AttributeEnumeration']['status']); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                                      array('controller' => 'attribute_enumerations',
                                            'action' => 'edit',
                                            $c['AttributeEnumeration']['id']),
                                      array('class' => 'editbutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'attribute_enumerations',
                    'action' => 'delete',
                    $c['AttributeEnumeration']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['AttributeEnumeration']['optvalue']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
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
  
<?php print $this->element("pagination");
