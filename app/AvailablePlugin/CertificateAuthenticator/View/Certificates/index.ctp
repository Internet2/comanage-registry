<?php
/**
 * COmanage Registry Certificates Index View
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.certificates.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.certificates.1'))),
      array(
        'plugin' => 'certificate_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
        'controller' => 'certificates',
        'action' => 'add',
        'authenticatorid' => $vv_authenticator['Authenticator']['id'],
        'copersonid' => $vv_co_person['CoPerson']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<table id="certificates">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('valid_fram', _txt('fd.valid_from')); ?></th>
      <th><?php print $this->Paginator->sort('valid_through', _txt('fd.valid_through')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($certificates as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(
            $c['Certificate']['description'],
            array(
              'plugin' => 'certificate_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
              'controller' => 'certificates',
              'action' => (($permissions['edit'])
                           ? 'edit'
                           : ($permissions['view'] ? 'view' : '')),
              $c['Certificate']['id']
            )
          );
        ?>
      </td>
      <td>
        <?php
          if($c['Certificate']['valid_from'] > 0)
            print $this->Time->format($c['Certificate']['valid_from'], "%c $vv_tz", false, $vv_tz);
        ?>
      </td>
      <td>
        <?php
          if($c['Certificate']['valid_through'] > 0)
            print $this->Time->format($c['Certificate']['valid_through'], "%c $vv_tz", false, $vv_tz);
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit']) {
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'plugin' => 'certificate_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
                'controller' => 'certificates',
                'action' => 'edit',
                $c['Certificate']['id']
              ),
              array('class' => 'editbutton')
            ) . "\n";
          }
          
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                'plugin' => 'certificate_authenticator', // XXX can inflect from $vv_authenticator['Authenticator']['plugin']
                'controller' => 'certificates',
                'action' => 'delete',
                $c['Certificate']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['Certificate']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.delete')
              . '</button>';
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
</table>
  
<?php print $this->element("pagination");