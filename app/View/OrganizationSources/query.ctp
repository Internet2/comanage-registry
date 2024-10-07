<?php
/**
 * COmanage Registry Organization Source Query View
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<script>
  $(function() {
    $( ".clearButton").button();
  });
</script>

<?php
  // Set page title
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'controller' => 'organization_sources',
    'plugin'     => null,
    'action'     => 'index',
    'co'         => $cur_co['Co']['id']
  );
  
  $this->Html->addCrumb(_txt('ct.organization_sources.pl'), $args);
  $this->Html->addCrumb($title_for_layout);
  
  $options = array(
    'url' => array(
      'action' => 'query',
      $vv_organization_source['OrganizationSource']['id']
    )
  );
  
  print $this->Form->create('OrganizationSource', $options);
  
  $index = 1;
?>

<ul id="<?php print $this->action; ?>_os_query" class="fields form-list">
<?php if(empty($vv_search_attrs)): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.os.nosearch'); ?>
    </div>
  </div>
<?php else: // vv_search_attrs ?>
<?php
    foreach($vv_search_attrs as $field => $label) {
      $args = array();
      $args['label'] = false;
      $args['placeholder'] = $label;
      $args['tabindex'] = $index++;
      $args['value'] = (!empty($this->request->params['named']['search.' . $field])
                        ? filter_var(urldecode($this->request->params['named']['search.' . $field]),FILTER_SANITIZE_SPECIAL_CHARS) : '');
      
      print '
    <li>
      <div class="field-name">
        <div class="field-title">' . filter_var($label,FILTER_SANITIZE_SPECIAL_CHARS) . '</div>
      </div>
      <div class="field-info">'. $this->Form->input('search.' . $field, $args) . '</div>
    </li>';
    }
?>
  <li class="fields-submit">
    <div class="field-name">
    </div>
    <div class="field-info">
      <?php
        $args = array();
        $args['tabindex'] = $index;
        print $this->Form->submit(_txt('op.search'),$args);
      ?>
    </div>
  </li>
</ul>
<?php endif; // vv_search_attrs ?>
<?php print $this->Form->end();?>

<?php if(!empty($vv_search_results)): ?>
<p><b></b><?php print _txt('rs.found.cnt', array(count($vv_search_results))); ?></b></p>

<div class="table-container">
  <table id="org_identity_source_results">
    <thead>
      <tr>
        <th><?php print _txt('fd.name'); ?></th>
        <th><?php print _txt('fd.sorid'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach($vv_search_results as $k => $o): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            $retrieveUrl = array(
              'controller' => 'organization_sources',
              'action' => 'retrieve',
              $vv_organization_source['OrganizationSource']['id'],
              // Keys can be URNs or URLs in some backends (eg: FederationSource),
              // both of which can cause havoc with our MVC addresses, so we base64 encode them
              'key' => cmg_urlencode($k)
            );

            print $this->Html->link(
              $o['rec']['Organization']['name'],
              $retrieveUrl
            );
          ?>
        </td>
        <td>
          <?php
            print $o['rec']['Organization']['source_key'];
          ?>
        </td>
        <td>
          <?php
            if($permissions['retrieve']) {
              print $this->Html->link(
                _txt('op.view'),
                $retrieveUrl,
                array('class' => 'viewbutton')
              ). "\n";
            }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; // $vv_search_results ?>
    </tbody>

    <tfoot>
      <tr>
        <th colspan="3">
          <?php /*print $this->element("pagination");*/ ?>
        </th>
      </tr>
    </tfoot>
    <?php elseif(!empty($vv_search_query)): ?>
      <tbody>
        <tr>
          <td>
            <?php print _txt('rs.search.none'); ?>
          </td>
        </tr>
      </tbody>
    <?php endif; // vv_search_results/query ?>
  </table>
</div>
