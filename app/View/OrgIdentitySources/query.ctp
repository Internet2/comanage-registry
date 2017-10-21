<?php
/**
 * COmanage Registry Org Identity Search View
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
?>

<script>
  function clearSearch(formObj) {
    for (var i=0; i<formObj.elements.length; i++) {
      t = formObj.elements[i].type;
      if(t == "text" || t == "select-one") {
        formObj.elements[i].value = "";
      }
    }
    formObj.submit();
  }

  $(function() {
    $( ".clearButton").button();
  });
</script>

<?php
  // Set page title
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);
  
  // Add breadcrumbs
  if(!$pool_org_identities) {
    print $this->element("coCrumb");
  }

  $args = array(
    'controller' => 'org_identity_sources',
    'plugin'     => null,
    'action'     => 'index'
  );
  
  if(!$pool_org_identities) {
    $args['co'] = $cur_co['Co']['id'];
  }
  
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);
  $this->Html->addCrumb($title_for_layout);
  
  $options = array(
    'url' => array(
      'action' => 'query',
      $vv_org_identity_source['id']
    )
  );
  
  print $this->Form->create('OrgIdentitySource', $options);
  
  if(!empty($this->request->params['named']['copetitionid'])) {
    print $this->Form->hidden('copetitionid', array('default' => filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS)));
  }
  
  $index = 1;
?>

<ul id="<?php print $this->action; ?>_ois_query" class="fields form-list">
<?php if(empty($vv_search_attrs)): ?>
    <div class="co-info-topbox">
      <em class="material-icons">info</em>
      <?php print _txt('in.ois.nosearch'); ?>
    </div>
<?php else: // vv_search_attrs ?>
<?php
    foreach($vv_search_attrs as $field => $label) {
      $args = array();
      $args['label'] = false;
      $args['placeholder'] = $label;
      $args['tabindex'] = $index++;
      $args['value'] = (!empty($this->request->params['named']['Search.' . $field])
                        ? filter_var($this->request->params['named']['Search.' . $field],FILTER_SANITIZE_SPECIAL_CHARS) : '');
      
      print '
    <li>
      <div class="field-name">
        <div class="field-title">' . filter_var($label,FILTER_SANITIZE_SPECIAL_CHARS) . '</div>
      </div>
      <div class="field-info">'. $this->Form->input('Search.' . $field, $args) . '</div>
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
        <th><?php print _txt('fd.email_address.mail'); ?></th>
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
              'controller' => 'org_identity_sources',
              'action' => 'retrieve',
              $vv_org_identity_source['id'],
              'key' => $k
            );

            if(!empty($this->request->params['named']['copetitionid'])) {
              $retrieveUrl['copetitionid'] = filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS);
            }

            // We could walk the set of names to look for primary, but it's easier
            // to just pick the first (and that will be sufficient in almost all cases).
            print $this->Html->link(
              generateCn($o['Name'][0]),
              $retrieveUrl
            );
          ?>
        </td>
        <td>
          <?php
            if(!empty($o['EmailAddress'][0]['mail'])) {
              print $o['EmailAddress'][0]['mail'];
            }
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
