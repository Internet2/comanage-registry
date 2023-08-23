<?php
/**
 * COmanage Registry CO Petition Duplicate Check View
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// A simple array to hold Match Attribute names for generating unique attribute rows:
$matchAttributeNames = array();

// Generate page title
$params = array();
$params['title'] = _txt('op.match.duplicate.check');
print $this->element("pageTitleAndButtons", $params);
?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('in.ef.match'); ?>
  </div>
</div>

<div id="reconcile-table-container">
  <table id="reconcile-table" class="side-by-side">
    <thead>
    <!-- Table headers: "New" and "Suggestion n" -->
    <tr>
      <th class="attr-title empty"></th>
      <?php $i = 1; ?>
      <?php foreach($vv_matches as $m): ?>
        <th class="col-names" scope="col">
          <?php print ($m->referenceId != 'new') ? _txt('fd.match.suggestion', array($i++)) : _txt('fd.match.new.record');  ?>
        </th>
        <?php
          // Gather up all Match Attribute names for generating unique attribute rows later.
          $hasNonSorIdentifiers = false;
          foreach($m->sorRecords as $s) {
             foreach($s->sorAttributes as $k => $v) {
               // We display SOR indentifiers separately, so see if we have any identifiers that are *NOT* SOR. If so,
               // we need to display an identifier row. Otherwise we can skip it.
               if ($k == 'identifiers') {
                 // We only need to determine this once.
                 if(!$hasNonSorIdentifiers) {
                   foreach ($v as $id) {
                     if ($id->type != 'sor') {
                       $hasNonSorIdentifiers = true;
                       $matchAttributeNames[] = $k;
                     }
                   }
                 }  
               } else {
                 // We only need the names at the top of the sorAttributes
                 $matchAttributeNames[] = $k;
               }
             }
          }
          // Finally, make the Match Attribute names array unique so we can iterate over
          // them and produce one row per attribute.
          $matchAttributeNames = array_unique($matchAttributeNames);
        ?>
      <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <!-- Reference IDs -->
    <tr>
      <th class="attr-title" scope="row">
        <?php print _txt('fd.match.reference.ids'); ?>
      </th>
      <?php foreach($vv_matches as $m): ?>
        <td class="reference-ids">
          <?php print ($m->referenceId != 'new') ? $m->referenceId : _txt('fd.match.new'); ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <!-- Actions -->
    <tr>
      <th class="attr-title" scope="row">
        <?php print _txt('op.action'); ?>
      </th>
      <?php foreach($vv_matches as $m): ?>
        <td class="reconcile-actions">
          <?php
            $targetUrl = array(
              'controller' => 'co_petitions',
              'action'     => 'duplicateCheck',
              $vv_co_petition_id
            );
            $targetOptions = array(
              'class' => 'btn btn-primary',
              'confirm' => ($m->referenceId != 'new') ? _txt('op.match.select.confirm') : _txt('op.match.add.confirm'),
              'data'  => array(
                'referenceId' => $m->referenceId
              )
            );
            if(!empty($vv_petition_token)) {
              $targetUrl['token'] = $vv_petition_token;
            }
            $linkText = ($m->referenceId != 'new') ? _txt('op.match.merge') : _txt('op.match.new');
            print $this->Form->postLink($linkText, $targetUrl, $targetOptions); 
          ?>
        </td>
      <?php endforeach; ?>
    </tr>
    <!-- System of Record (SOR) -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.match.sor'); ?></th>
      <?php $totalRecords = 0; ?>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <?php $totalRecords++; ?>
              <span class="reconcile-sor-label">
                <?php if(!empty($s->meta->sorLabel)) print $s->meta->sorLabel; ?>
              </span>
            <?php endforeach; // $s ?>
          </div>
        </td>
      <?php endforeach; // $m ?>
    </tr>
    <!-- System of Record ID (SorID) -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.sorid'); ?></th>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <span class="reconcile-sor-id">
                <?php if(!empty($s->meta->sorId)) print $s->meta->sorId; ?>
              </span>
            <?php endforeach; // $s ?>
          </div>
        </td>
      <?php endforeach; // $m ?>
    </tr>

    <!-- MATCH ATTRIBUTES -->
    <?php foreach($matchAttributeNames as $n): ?>
      <?php $newAttributes = []; // Keep the list of new attributes for matching comparison. ?>
      <tr class="match-attributes-row">
        <th class="attr-title match-attr-name" scope="row"><?php print $n ?></th>
        <?php foreach($vv_matches as $m): ?>
          <td>
            <div class="reconcile-fields">
              <?php
              // Output the Match Attribute values for the current attribute key.
              foreach($m->sorRecords as $s) {
                foreach($s->sorAttributes as $k => $v) {
                  // Only output values for the current key (derived from $matchAttributeNames).
                  if($k == $n) {
                    print '<div class="match-attr-list-container">';
                    if(is_array($v)) {
                      foreach($v as $obj) {
                        // Skip SOR Identifiers (if Identifiers are being listed at all).
                        if (!($k == 'identifiers' && $obj->type == 'sor')) {
                          print '<ul class="match-attr-list">' . "\n";
                          // Convert the object to associative array and sort by key values.
                          // XXX This assumes that our object will *always* be a simple associative array by this point.
                          $attrsArr = json_decode(json_encode($obj), true);
                          ksort($attrsArr);
                          // Finally, output the key/value pairs:
                          foreach ($attrsArr as $key => $val) {
                            if (!empty($val)) {
                              $matchClass = '';
                              if($m->referenceId == 'new') {
                                // Save the new attribute.
                                $newAttributes[$key] = $val;
                              } else {
                                // Do we have a match? Compare the new attribute with the current attribute.
                                // We will convert both strings to lowercase for a case-insensitive match.
                                if(strtolower($newAttributes[$key]) == strtolower($val)) {
                                  $matchClass = ' class="match"';
                                } elseif(!empty($newAttributes[$key])) {
                                  $matchClass = ' class="no-match"';
                                }
                              }
                              print "<li" . $matchClass . ">$key: <strong>$val</strong></li>\n";
                            }
                          }
                          print "</ul>\n";
                        }
                      }
                    } else {
                      print "<strong>$v</strong>\n";
                    }
                    print "</div>\n";
                  }
                }  
                
              }
              ?>
            </div>
          </td>
        <?php endforeach; // $m ?>
      </tr>
    <?php endforeach; // $n - end of MATCH ATTRIBUTES ?>
    
    <!-- Match ID -->
    <tr>
      <th class="attr-title" scope="row"><?php print _txt('fd.match.id'); ?></th>
      <?php foreach($vv_matches as $m): ?>
        <td>
          <div class="reconcile-fields">
            <?php foreach($m->sorRecords as $s): ?>
              <span class="reconcile-match-id">
                <?php if(!empty($s->meta->matchRequest)) print $s->meta->matchRequest; ?>
              </span>  
            <?php endforeach; // $s ?>
          </div>  
        </td>
      <?php endforeach; // $m ?>
    </tr>
    <!-- Request Times -->
    <tr class="match-attributes-row">
      <th class="attr-title" scope="row">
        <?php print _txt('fd.match.request.time'); ?>
      </th>
      <?php foreach($vv_matches as $m): ?>
        <td class="request-time">
          <div class="reconcile-fields">
            <?php
            // Output the Request Time for each set of attributes
            foreach($m->sorRecords as $s) {
              print '<div class="match-attr-list-container">';
              foreach($s->meta as $k => $v) {
                if($k == 'requestTime') {
                  print '<ul class="match-attr-list">' . "\n";
                  print '<li>';
                  print $this->Time->format($v, "%F %T", false, $vv_tz) . '<br>' . $vv_tz;
                  print "</li>\n";
                  print "</ul>\n";
                }
              }
              print "</div>\n";
            }
            ?>
          </div>
        </td>
      <?php endforeach; ?>
    </tr>
    </tbody>
  </table>
</div>
