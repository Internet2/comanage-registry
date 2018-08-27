<?php
  /**
   * COmanage Registry CO Search View
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
  $this->Html->addCrumb(_txt('op.search'));

  // Add page title
  $params = array();
  $params['title'] = _txt('op.search');

  print $this->element("pageTitleAndButtons", $params);
?>

<section class="inner-content">
  <?php
    $options = array(
      'type' => 'get',
      'url' => array('action' => 'search')
    );
  
    print $this->Form->create('CoDashboard', $options);
    
    $index = 0;
    
    $options = array(
      'label' => '<span class="visuallyhidden">' . _txt('op.search.global') . '</span>'
    );
    
    if(!empty($this->request->query['q'])) {
      $options['default'] = $this->request->query['q'];
    }
  ?>
  <div id="search">
    <?php
      print $this->Form->input('q', $options);
      print $this->Form->submit(_txt('op.search'));
    ?>
  </div>
  <?php
    // We need co after q in the URL so queries like "q=foo@aol.com" don't get truncated
    // (since .com looks like a file extensions)
    print $this->Form->hidden('co', array('default' => $cur_co['Co']['id']));
    print $this->Form->end();
  ?>
  
  <div id="search-results">
  <?php
    if(!empty($this->request->query['q'])
      && (empty($vv_results) || count(Hash::extract($vv_results, '{s}.{n}')) == 0)) {
      print _txt('rs.search.none');
    }

    if(empty($this->request->query['q'])) {
      print _txt('rs.search.noquery');
    }
    
    // Models associated with CoPerson
    
    foreach(array('Name' => 'generateCn',
                  'EmailAddress' => 'mail',
                  'Identifier' => 'identifier',
                  'CoPersonRole' => 'title')
            as $m => $k) {
      if(!empty($vv_results[$m])) {
        print '<div class="co-card">';
        print "<h2>" . _txt('ct.'.Inflector::tableize($m).'.pl') . "</h2>";
        print "<ul>";
        
        foreach($vv_results[$m] as $r) {
          $args = array(
            'plugin'     => null,
            'controller' => 'co_people',
            'action'     => 'canvas',
            $r[$m]['co_person_id']
          );
          
          $linkLabel = $r[$m]['id'];
          
          if($m == 'Name') {
            $linkLabel = generateCn($r['Name']);
            
            // There might be more than one role, but for now we'll only look at the first
            if(!empty($r['CoPerson']['CoPersonRole'][0]['title'])) {
              $linkLabel .= " (" . $r['CoPerson']['CoPersonRole'][0]['title'] . ")";
            }
          } elseif(!empty($r[$m][$k])) {
            $linkLabel = $r[$m][$k];
            
            if(!empty($r['CoPerson']['PrimaryName']['id'])) {
              $linkLabel .= " (" . generateCn($r['CoPerson']['PrimaryName']) . ")";
            }
          }
          
          print "<li>" . $this->Html->link($linkLabel, $args). "</li>\n";
        }
        
        print "</ul>\n";
        print "</div>\n";
      }
    }
    
    // Models associated with CoPersonRole
    
    foreach(array('Address' => 'formatAddress',
                  'TelephoneNumber' => 'formatTelephone')
            as $m => $fn) {
      if(!empty($vv_results[$m])) {
        print '<div class="co-card">';
        print "<h2>" . _txt('ct.'.Inflector::tableize($m).'.pl') . "</h2>";
        print "<ul>";
        
        foreach($vv_results[$m] as $r) {
          $args = array(
            'plugin'     => null,
            'controller' => 'co_person_roles',
            'action'     => 'edit',
            $r[$m]['co_person_role_id']
          );
          
          $linkLabel = $fn($r[$m]);
          
          if(!empty($r['CoPersonRole']['CoPerson']['PrimaryName']['id'])) {
            $linkLabel .= " (" . generateCn($r['CoPersonRole']['CoPerson']['PrimaryName']) . ")";
          }
          
          print "<li>" . $this->Html->link($linkLabel, $args). "</li>\n";
        }
        
        print "</ul>\n";
        print "</div>\n";
      }
    }
    
    // Models to render directly
    
    foreach(array('CoGroup' => 'name',
                  'CoService' => 'name',
                  'CoDepartment' => 'name',
                  'CoEmailList' => 'name',
                  'CoEnrollmentFlow' => 'name')
            as $m => $k) {
      if(!empty($vv_results[$m])) {
        $mpl = Inflector::tableize($m);
        print '<div class="co-card">';
        print "<h2>" . _txt('ct.'.$mpl.'.pl') . "</h2>";
        print "<ul>";
        
        foreach($vv_results[$m] as $r) {
          $args = array(
            'plugin'     => null,
            'controller' => $mpl,
            'action'     => 'view',
            $r[$m]['id']
          );
          
          print "<li>" . $this->Html->link($r[$m][$k], $args). "</li>\n";
        }
        
        print "</ul>\n";
        print "</div>\n";
      }
    }
    
    // Plugins: We walk the results for models of the form Plugin.Model, then
    // use the model name to determine a heading
    foreach(array_keys($vv_results) as $m) {
      if(!empty($vv_results[$m]) && strstr($m, '.')) {
        list($plugin, $pmodel) = explode('.', $m, 2);
        
        $mpl = Inflector::tableize($pmodel);
        print '<div class="co-card">';
        print "<h2>" . _txt('ct.'.$mpl.'.pl') . "</h2>";
        print "<ul>";
        
        foreach($vv_results[$m] as $r) {
          $field = $vv_plugin_display_fields[$m];
          
          $args = array(
            'plugin'     => $plugin,
            'controller' => $mpl,
            'action'     => 'view',
            $r[$pmodel]['id']
          );
          
          print "<li>" . $this->Html->link($r[$pmodel][$field], $args). "</li>\n";
        }
        
        print "</ul>\n";
        print "</div>\n";
      }
    }
  ?>
  </div>
</section>
