<?php
/**
 * COmanage Registry Alphabet Search Bar
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Globals
global $cm_lang, $cm_texts;
// Get a pointer to our Controller
$controller = $this->name;
$req = Inflector::singularize($controller);
$controller_route_name = Inflector::underscore($controller);

$search_param = array_keys($vv_alphabet_search_config)[0];
?>

<div id="<?php print $req; ?>Alphabet" class="listControl searchAlphabet" aria-label="<?php print $vv_alphabet_search_config[$search_param]['label']; ?>">
  <ul>
    <?php
      $args = array();
      $args['controller'] = $controller_route_name;
      $args['action'] = $this->action;

      if($this->action == 'index') {
        $args['co'] = $cur_co['Co']['id'];
      } else {
        // A link/relink operation is in progress
        if(!empty($this->request->params['pass'][0])) {
          $args[] = $this->request->params['pass'][0];
        }
      }

      // Merge (propagate) all prior search criteria, except familyNameStart and page
      $args = array_merge($args, $this->request->params['named']);
      unset($args[$search_param], $args['page']);

      $alphaSearch = '';

      if(!empty($this->request->params['named'][$search_param])) {
        $alphaSearch = $this->request->params['named'][$search_param];
      }

      foreach(_txt('me.alpha') as $i) {
        $args[$search_param] = $i;
        $alphaStyle = ' class="spin"';
        if ($alphaSearch == $i) {
          $alphaStyle = ' class="selected spin"';
        }
        print '<li' . $alphaStyle . '>' . $this->html->link($i,$args) . '</li>';
      }

      // Remove the alphabet search param
      unset($args[$search_param]);
      // Clear only Alphabet Bar Search
      $clear_alphabet_link = $this->html->link(
        '<em class="material-icons">block</em>',
        $args,
        array(
          'title' => _txt('op.clear.search'),
          'escape' => false,
        )
      );
      print '<li class="spin">' . $clear_alphabet_link . '</li>';
    ?>
  </ul>
</div>