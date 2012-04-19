<?php
/**
 * COmanage Registry Standard Edit View
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  // Get a pointer to our data
  $d = $$modelpl;

  // Figure out a heading
  // XXX this should probably get moved into the controllers (duped in view.ctp)
  $h = "";
  
  if(isset($d[0]['Name']))
    $h = _txt('op.edit-a', array(Sanitize::html(generateCn($d[0]['Name']))));
  elseif(isset($d[0]['CoPerson']['Name']))
    $h = _txt('op.edit-a', array(Sanitize::html(generateCn($d[0]['CoPerson']['Name'])))) . " (" . _txt('co') . ")";
  elseif(isset($d[0]['OrgIdentity']['Name']))
    $h = _txt('op.edit-a', array(Sanitize::html(generateCn($d[0]['OrgIdentity']['Name'])))) . " (" . _txt('org') . ")";
  // CO Person Role rendering gets some info from co_people
  elseif(isset($co_people[0]['Name']))
    $h = _txt('op.edit-a', array(Sanitize::html(generateCn($co_people[0]['Name']))));
  elseif(isset($d[0][$req]['line1']))
    $h = _txt('op.edit-a', array(Sanitize::html($d[0][$req]['line1'])));
  elseif(isset($d[0][$req]['label']))
    $h = _txt('op.edit-a', array(Sanitize::html($d[0][$req]['label'])));
  elseif(isset($d[0][$req]['name']))
    $h = _txt('op.edit-a', array(Sanitize::html($d[0][$req]['name'])));
  else
    $h = _txt('op.edit');

  $params = array('title' => $h);
  print $this->element("pageTitle", $params);

  $submit_label = _txt('op.save');
  echo $this->Form->create($req,
                           array('action' => 'edit',
                                 'inputDefaults' => array('label' => false, 'div' => false)));
  include(APP . "View/" . $model . "/fields.inc");
  echo $this->Form->end();
?>
