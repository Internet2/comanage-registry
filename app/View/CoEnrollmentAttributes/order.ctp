<?php
/**
 * COmanage Registry CO Enrollment Attribute Order View
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
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $vv_coefid;
  $this->Html->addCrumb($vv_ef_name, $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_attributes';
  $args['action'] = 'index';
  $args['coef'] = $vv_coefid;
  $this->Html->addCrumb(_txt('ct.co_enrollment_attributes.pl'), $args);

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
        var jqxhr = $.post("<?php print $this->Html->url(array('controller' => 'co_enrollment_attributes',
                                                               'action'     => 'reorder',
                                                               'ext'        => 'json',
                                                               'coef'       => $vv_ef_id)); ?>", $("#sortable").sortable("serialize"));
        
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
  <table id="enrollment_attributes">
    <thead>
      <tr>
        <th><?php print _txt('fd.ea.order'); ?></th>
        <th><?php print _txt('fd.ea.label'); ?></th>
        <th><?php print _txt('fd.attribute'); ?></th>
      </tr>
    </thead>

    <tbody id="sortable">
      <?php foreach ($co_enrollment_attributes as $c): ?>
        <tr id = "CoEnrollmentAttributeId_<?php print $c['CoEnrollmentAttribute']['id']?>" class="line1">
          <td class = "order">
            <span class="ui-icon ui-icon-arrow-4"></span>
          </td>
          <td>
            <?php
              print $this->Html->link($c['CoEnrollmentAttribute']['label'],
                                      array('controller' => 'co_enrollment_attributes',
                                            'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                            $c['CoEnrollmentAttribute']['id'],
                                            'coef' => $vv_coefid));
            ?>
          </td>
          <td><?php
            $attrProps = $vv_attributes_properties[ $c['CoEnrollmentAttribute']['attribute'] ];
            print $attrProps['attrName'] . ' (' . implode(', ', array_slice(array_filter($attrProps), '1')) . ')';
            ?></td>
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
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_attributes';
  $args['action'] = 'index';
  $args['coef'] = $vv_coefid;
  
  print $this->Html->link(_txt('op.done'),
                          $args,
                          array('class'  => 'checkbutton right'));
?>

<div id="result-dialog" class="co-dialog" title="<?php print _txt('op.reorder'); ?>">
  <p></p>
</div>
