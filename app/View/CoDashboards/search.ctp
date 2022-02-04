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
      'label' => '<span class="visuallyhidden mr-2">' . _txt('op.search.global') . ':</span>'
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
    
    // Models associated with CoPerson or CoGroup
    $supportedModels = array('Name' => 'generateCn',
                             'EmailAddress' => 'mail',
                             'Identifier' => 'identifier',
                             'CoPersonRole' => 'title');

    foreach( $supportedModels as $m => $k) {
      if(!empty($vv_results[$m])) {
        print '<div class="co-card">';
        print "<h2>" . _txt('ct.'.Inflector::tableize($m).'.pl') . "</h2>";
        print "<ul>";

        $link_associative_array = array();

        foreach($vv_results[$m] as $r) {
          $href = array();
          $label_name = "";

          $href_id = -1;
          if (!empty($r[$m]['co_person_id'])) {
            $href_id = $r[$m]['co_person_id'];
            $href = array(
              'plugin' => null,
              'controller' => 'co_people',
              'action' => 'canvas',
              $r[$m]['co_person_id']
            );
          } elseif (!empty($r[$m]['co_group_id'])) {
            $href = array(
              'plugin' => null,
              'controller' => 'co_groups',
              'action' => 'edit',
              $r[$m]['co_group_id']
            );
            $href_id = $r[$m]['co_group_id'];
          }
          $href_url = json_encode($href);

          $link_associative_array[$href_url][$r[$m]['id']] = array();
          if ($m == 'Name') {
            $name_url = $this->Html->link(
              generateCn($r['Name']) . " (" . $r[$m]['id'] . ")",
              array(
                'plugin' => null,
                'controller' => 'names',
                'action' => 'edit',
                $r[$m]['id']
              )
            );
            $link_associative_array[$href_url]['Name'][] = $name_url;
            $link_associative_array[$href_url]['Name'] = array_unique($link_associative_array[$href_url]['Name']);
            // Use the name as the title of the card we will create
            $link_associative_array[$href_url]['linklabel'] = generateCn($r['Name']) . " (" . $href_id . ")";
            if (!empty($r['CoPerson']['CoPersonRole'])) {
              foreach ($r['CoPerson']['CoPersonRole'] as $role) {
                // XXX The query fetches the archived ones as well. I will filter them here
                if(!is_null($role['co_person_role_id'])
                   || $role['deleted']) {
                  continue;
                }
                $title = !empty($role['title']) ? $role['title'] . " " : $this->Html->tag('cite', _txt('fd.title.none'), array('class' => 'mr-1 text-lowercase'));
                $role_title = $this->Html->link(
                  $title . "(" . $role['id'] . ")",
                  array(
                    'plugin' => null,
                    'controller' => 'co_person_roles',
                    'action' => 'edit',
                    $role['id']
                  ),
                  array('escape' => false)
                );
                $role_title = empty($role['id']) ? $title : $role_title;
                $link_associative_array[$href_url]['Title'][] = $role_title;
                $link_associative_array[$href_url]['Title'] = array_unique($link_associative_array[$href_url]['Title']);
              }
            }
          } elseif (!empty($r['CoGroup']['name'])) {
            $link_associative_array[$href_url][$r[$m]['id']]['Name'] = $r['CoGroup']['name'] . " (" . $href_id . ")";
          } elseif (!empty($r[$m][$k])) {
            $mfk = Inflector::singularize(Inflector::tableize($m));
            if(!is_null($r[$m][$mfk . '_id'])
               || $r[$m]['deleted']) {
              continue;
            }
            $value = $this->Html->link(
              $r[$m][$k] . " (" . $r[$m]['id'] . ")",
              array(
                'plugin' => null,
                'controller' => Inflector::tableize($m),
                'action' => 'edit',
                $r[$m]['id']
              )
            );
            $link_associative_array[$href_url]['Value'][] = $value;
            $link_associative_array[$href_url]['Value'] = array_unique($link_associative_array[$href_url]['Value']);
            if (!empty($r['CoPerson']['PrimaryName']['id'])) {
              $primary_name = $this->Html->link(
                generateCn($r['CoPerson']['PrimaryName']) . " (" . $r['CoPerson']['PrimaryName']['id'] . ")",
                array(
                  'plugin' => null,
                  'controller' => 'names',
                  'action' => 'edit',
                  $r[$m]['id']
                )
              );
              $label_name = generateCn($r['CoPerson']['PrimaryName']);
              $link_associative_array[$href_url]['Name'][] = $primary_name;
              $link_associative_array[$href_url]['Name'] = array_unique($link_associative_array[$href_url]['Name']);
            }
            $link_associative_array[$href_url]['linklabel'] = $label_name . " (" . $href_id . ")";
          }
        }
        ?>
        <?php foreach ($link_associative_array as $a_href => $meta): ?>
          <li class="co-card">
            <div class="field-name">
              <div class="field-title"><?php
                $a_href = json_decode($a_href, true);
                print $this->Html->link($meta['linklabel'], $a_href);
                ?></div>
            </div>
            <div class="field-info">
            </div>
            <ul class="field-children">
            <?php foreach ($meta as $key => $values): ?>
            <?php if(is_array($values) && !is_int($key) && !empty($values)): ?>
              <li class="li-global-search">
                <span class="meta-global-search">
                  <div class="field-name">
                    <div class="field-title font-weight-bold"><?php print $key; ?>:</div>
                  </div>
                  <div class="field-info ml-2"><?php print implode(', ', $values)?></div>
                </span>
              </li>
            <?php endif; ?>
            <?php endforeach; ?>
            </ul>
          </li>
        <?php endforeach;?>
        <?php
        print "</ul>" . PHP_EOL;
        print "</div>" . PHP_EOL;
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
            'action'     => 'edit',
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
            'plugin'     => Inflector::underscore($plugin),
            'controller' => $mpl,
            'action'     => 'edit',
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
