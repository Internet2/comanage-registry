<?php
/**
 * COmanage Registry CO Service Token Setting Index View
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
  
  $this->Html->addCrumb(_txt('ct.co_service_token_settings.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('ct.co_service_token_settings.pl');

  // Add top links
  $params['topLinks'] = array();
  
  print $this->element("pageTitleAndButtons", $params);
  
  print $this->Form->create('CoServiceTokenSetting',
                            array('url' => array('action' => 'configure'),
                                  'inputDefaults' => array('label' => false,
                                                           'div' => false))) . "\n";
  print $this->Form->hidden('CoServiceTokenSetting.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<table id="co_service_token_settings" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('CoService.name', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('CoServiceTokenSetting.enabled', _txt('pl.coservicetoken.enabled')); ?></th>
      <th><?php print $this->Paginator->sort('CoServiceTokenSetting.token_type', _txt('pl.coservicetoken.token.type')); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($vv_co_services as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(
            $c['CoService']['name'],
            array(
              'plugin'     => null,
              'controller' => 'co_services',
              'action'     => 'edit',
              $c['CoService']['id']
            )
          );
        ?>
      </td>
      <td>
        <?php
          $checked = isset($c['CoServiceTokenSetting']['enabled']) && $c['CoServiceTokenSetting']['enabled'];
          
          if(!empty($c['CoServiceTokenSetting']['id'])) {
            print $this->Form->hidden('CoServiceTokenSetting.'.$i.'.id',
                                      array('default' => $c['CoServiceTokenSetting']['id']));
          }
          print $this->Form->hidden('CoServiceTokenSetting.'.$i.'.co_service_id',
                                    array('default' => $c['CoService']['id']));
          print $this->Form->checkbox('CoServiceTokenSetting.'.$i.'.enabled',
                                      array('checked' => $checked));
        ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($c['CoServiceTokenSetting']['token_type'])
                             ? $c['CoServiceTokenSetting']['token_type']
                             : CoServiceTokenTypeEnum::Plain15);
          $attrs['empty'] = false;
          
          print $this->Form->select('CoServiceTokenSetting.'.$i.'.token_type',
                                    $cm_texts[ $cm_lang ]['en.coservicetoken.tokentype'],
                                    $attrs);
          
          if($this->Form->isFieldError('CoServiceTokenSetting.'.$i.'.token_type')) {
            print $this->Form->error('CoServiceTokenSetting.'.$i.'.token_type');
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="3">
      </th>
    </tr>
    <tr>
      <td></td>
      <td colspan="2">
        <?php
          $options = array('style' => 'float:left;');
          
          print $this->Form->submit(_txt('op.save'), $options);
          print $this->Form->end();
        ?>
      </td>
    </tr>
  </tfoot>
</table>
