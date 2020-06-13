<?php
/**
 * COmanage Registry Group Filter Rules Filters Order View
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'edit';
  $args[] = $vv_datafilter['id'];
  $this->Html->addCrumb($vv_datafilter['description'], $args);
  
  $args = array();
  $args['plugin'] = 'group_filter';
  $args['controller'] = 'group_filter_rules';
  $args['action'] = 'index';
  $args['groupfilter'] = $vv_groupfilter['id'];
  $this->Html->addCrumb(_txt('ct.group_filter_rules.pl'), $args);
  
  $this->Html->addCrumb(_txt('op.order-a', array(_txt('ct.group_filter_rules.pl'))));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  print $this->element("pageTitleAndButtons", $params);
?>
<script type="text/javascript">
  $(function() {
    // Define sortable
    $("#sortable").sortable({
      update: function( event, ui ) {
        // POST to /reorder with the new order serialized
        var jqxhr = $.post("<?php print $this->Html->url(array('plugin'      => 'group_filter',
                                                               'controller'  => 'group_filter_rules',
                                                               'action'      => 'reorder',
                                                               'ext'         => 'json',
                                                               'groupfilter' => $vv_groupfilter['id'])); ?>", $("#sortable").sortable("serialize"));
        
        jqxhr.done(function(data, textStatus, jqXHR) {
        });
        
        jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
          // Note we're getting 200 here but it's actually a success (perhaps because no body returned; CO-984)
          if(jqXHR.status != "200") {
            $("#result-dialog").html("<p><?php print _txt('er.reorder'); ?>" + errorThrown + " (" +  jqXHR.status + ")</p>");
            $("#result-dialog").dialog("open");
          }
        });
      }
    });
    
    // Result dialog
    $("#result-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        },
      },
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });
  });
</script>

<div class="table-container">
  <table id="provisioning_targets">
    <thead>
      <tr>
        <th><?php print _txt('fd.order'); ?></th>
        <th><?php print _txt('pl.groupfilter.name'); ?></th>
        <th><?php print _txt('fd.required'); ?></th>
      </tr>
    </thead>
    
    <tbody id="sortable">
      <?php foreach ($group_filter_rules as $c): ?>
        <tr id = "GroupFilterRuleId_<?php print $c['GroupFilterRule']['id']?>" class="line1">
          <td class = "order">
            <span class="ui-icon ui-icon-arrow-4"></span>
          </td>
          <td>
            <?php
              print $this->Html->link($c['GroupFilterRule']['name_pattern'],
                                      array('plugin' => 'group_filter',
                                            'controller' => 'group_filter_rules',
                                            'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                            $c['GroupFilterRule']['id']));
            ?>
          </td>
          <td><?php print _txt('en.required', null, $c['GroupFilterRule']['required']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>

    <tfoot>
      <tr>
        <th colspan="3">
          <?php print $this->element("pagination"); ?>
        </th>
      </tr>
    </tfoot>
  </table>
</div>

<?php
  $args = array();
  $args['plugin'] = 'group_filter';
  $args['controller'] = 'group_filter_rules';
  $args['action'] = 'index';
  $args['groupfilter'] = $vv_groupfilter['id'];
  
  print $this->Html->link(_txt('op.done'),
                          $args,
                          array('class'  => 'checkbutton right'));
?>

<div id="result-dialog" title="<?php print _txt('op.reorder'); ?>">
  <p></p>
</div>
