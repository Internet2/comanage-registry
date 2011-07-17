<?php
  /*
   * COmanage Gears Standard View
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);
  $modelpl = Inflector::tableize($req);
  
  // Get a pointer to our data
  $d = $$modelpl;
  
  // Figure out a heading
  $h = "";

  if(isset($d[0]['Name']))
    $h = _txt('op.view-a', array(Sanitize::html(generateCn($d[0]['Name']))));
  elseif(isset($d[0]['CoPersonRole']['Name']))
    $h = _txt('op.view-a', array(Sanitize::html(generateCn($d[0]['CoPersonRole']['Name'])))) . " (" . _txt('co') . ")";
  elseif(isset($d[0]['OrgIdentity']['Name']))
    $h = _txt('op.view-a', array(Sanitize::html(generateCn($d[0]['OrgIdentity']['Name'])))) . " (" . _txt('co') . ")";
  else
    $h = _txt('op.view-a', array(Sanitize::html($d[0][$req]['name'])));
?>
<h1 class="ui-state-default"><?php echo $h; ?></h1>

<?php
  include("views/" . $modelpl . "/fields.inc");

  // If user has edit permission, offer an edit button

  if($permissions['edit'])
  {
    $a = array('controller' => $modelpl, 'action' => 'edit', $d[0][$req]['id']);
    
    if(isset($this->params['named']['co']))
      $a['co'] = $this->params['named']['co'];
    
    echo $html->link(_txt('op.edit'),
                     $a,
                     array('class' => 'editbutton'));
  }
?>