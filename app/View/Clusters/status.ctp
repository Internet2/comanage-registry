<?php
/**
 * COmanage Registry Cluster Status View
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
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);

  $this->Html->addCrumb(_txt('ct.clusters.pl'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();
  
  if(!empty($vv_cluster_status)) {
    // We use cluster status as a proxy for knowing that there are any Clusters defined
    
    $params['topLinks'][] = $this->Html->link(
      _txt('op.cluster.acct.auto'),
      'javascript:js_confirm_autogenerate();',
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<script type="text/javascript">
  <!-- /* JS specific to these fields */ -->

  function js_confirm_autogenerate() {
    // Open the dialog to confirm autogeneration of cluster accounts
    $('#autogenerate-dialog').dialog('open');
  }

  $(function() {
    // Autogenerate dialog
    $("#autogenerate-dialog").dialog({
      autoOpen: false,
      buttons: [
        {
          text : "<?php print _txt('op.cancel'); ?>",
          click : function() {
            $(this).dialog("close");
          }
        },
        {
          text : "<?php print _txt('op.cluster.acct.auto'); ?>",
          click: function () {
            $(this).dialog("close");
            displaySpinner();
            window.location.href = "<?php print $this->Html->url(array('controller' => 'clusters',
            'action' => 'assign',
            'copersonid' => $vv_co_person['CoPerson']['id'])); ?>";
          }
        }
      ],
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

<table id="clusters">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print _txt('fd.status'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($vv_cluster_status as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print filter_var($c['description'],FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </td>
      <td>
        <?php
          print filter_var($c['status']['comment'], FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </td>
      <td>
        <?php
          // $plugin = "UnixCluster"
          $plugin = filter_var($c['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          // $pl = "unix_cluster"
          $pl = Inflector::underscore($plugin);
          // Cluster Store Model, "unix_cluster_accounts"
          $plcmodel = Inflector::pluralize($pl . "_account");
          
          if(!empty($vv_co_person['CoPerson']['id'])) {
            if($permissions['manage']) {
              print $this->Html->link(
                _txt('op.manage'),
                array(
                  'plugin' => $pl,
                  'controller' => $plcmodel,
                  'action' => 'index',
                  'clusterid' => $c['id'],
                  'copersonid' => $vv_co_person['CoPerson']['id']
                ),
                array('class' => 'editbutton')
              );
            }
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
</table>

<div id="autogenerate-dialog" class="co-dialog" title="<?php print _txt('op.cluster.acct.auto'); ?>">
  <?php print _txt('op.cluster.acct.auto.confirm'); ?>
</div>