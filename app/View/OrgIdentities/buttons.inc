<?php
/**
 * COmanage Registry CO Enrollment Configuration Fields
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Add buttons
if($this->action != "add") {
  if($permissions['viewsource']
     && !empty($org_identities[0]['OrgIdentitySourceRecord']['id'])) {
    // View Source Record button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.view-a', array(_txt('ct.org_identity_source_records.1'))),
      array(
        'controller' => 'org_identity_source_records',
        'action'     => 'view',
        $org_identities[0]['OrgIdentitySourceRecord']['id']
      ),
      array('class' => 'viewbutton')
    );
  }
  
  if($permissions['pipeline']
     && !empty($vv_pipeline['CoPipeline'])) {
    // Re-execute Pipeline button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.pipeline.rerun'),
      'javascript:js_confirm_pipeline()',
      array('class' => 'runbutton')
    );
  }
  
  if($permissions['history']) {
    // History button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.history'),
      array(
        'controller'    => 'history_records',
        'action'        => 'index',
        'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
        'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
      ),
      array('class' => 'notebutton')
    );
  }
  
  if($permissions['petitions']) {
    // Petitions
    $params['topLinks'][] = $this->Html->link(
      _txt('ct.co_petitions.pl'),
      array(
        'controller' => 'co_petitions',
        'action' => 'index',
        // Since this is an index view, we still need the CO ID. paginationConditions
        // will restrict searching to eligible enrollments.
        'co' => ($pool_org_identities ? false : $cur_co['Co']['id']),
        'sort' => 'modified',
        'search.orgidentityid' => $org_identities[0]['OrgIdentity']['id']
      ),
      array('class' => 'petitionbutton')
    );
  }
}
?>
<script>
  function js_confirm_pipeline() {
    pipelinedialog.dialog("open");
    document.getElementById("CoPipelineAction").focus();
  }
  
  $(function() {
    pipelinedialog = $("#pipeline-form").dialog({
      autoOpen: false,
      modal: true
    });
  });
</script>
<div id="pipeline-form" title="<?php print _txt('op.pipeline.rerun'); ?>">
  <?php if(!empty($vv_pipeline)): ?>
  <?php
    // SecurityComponent form tampering token checking doesn't like this as a POST,
    // so the easiest thing is to send it as a GET (which is nominally more correct anyway).
    print $this->Form->create('CoPipeline', array('url' => array('action' => 'execute/'.$vv_pipeline['CoPipeline']['id']),
                                                  'type' => 'get'));
    print $this->Form->hidden('CoPipeline.org_identity_id', array('default' => $org_identities[0]['OrgIdentity']['id']));
  ?>
  <p>
    <?php print _txt('op.pipeline.rerun.form'); ?>
  </p>
  <table>
    <tbody>
      <tr>
        <th class="ui-widget-header">
          <?php print _txt('ct.co_pipelines.1'); ?>
        </th>
        <td class="line1">
          <?php
            if(!empty($vv_pipeline['CoPipeline'])) {
              print $this->Html->link(
                $vv_pipeline['CoPipeline']['name'],
                array(
                  'controller' => 'co_pipelines',
                  'action'     => 'edit',
                  $vv_pipeline['CoPipeline']['id']
                )
              );
            }
          ?>
        </td>
      </tr>
      <tr>
        <th class="ui-widget-header">
          <?php print $this->Form->label('CoPipeline.action', _txt('op.action')); ?>
        </th>
        <td class="line1">
          <?php
            global $cm_lang, $cm_texts;
            
            $attrs = array();
            // Most of the time we want the default value to be update
            $attrs['value'] = SyncActionEnum::Update;
            $attrs['empty'] = false;
            
            // Build a list of actions enabled for the pipeline
            $actions = array();
            
            foreach(
              array('add' => SyncActionEnum::Add,
                    'update' => SyncActionEnum::Update,
                    'delete' => SyncActionEnum::Delete)
              as $k => $e
            ) {
              if(isset($vv_pipeline['CoPipeline']['sync_on_'.$k])
                 && $vv_pipeline['CoPipeline']['sync_on_'.$k]) {
                $actions[$e] = _txt('en.sync.action', null, $e);
              }
            }
            
            print $this->Form->select('CoPipeline.action',
                                      $actions,
                                      $attrs);
          ?>
        </td>
      </tr>
      <tr>
        <td></td>
        <td>
          <?php print $this->Form->submit(_txt('op.run'),
                                          array('onClick' => 'pipelinedialog.dialog("close");')); ?>
        </td>
      </tr>
    </tbody>
  </table>
  <?php print $this->Form->end(); ?>
  <?php endif; // vv_pipeline ?>
</div>
