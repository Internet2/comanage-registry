<?php
/**
 * COmanage Registry Shibboleth Embedded Discovery Service View
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  $params = array('title' => _txt('eds.title'));
  print $this->element("pageTitle", $params);
  
  print $this->Html->css('eds-idpselect', array('inline' => false));
?>

<p><?php print _txt('eds.layout.preamble'); ?></p>

<div id="idpSelect"></div>

<script type="text/javascript" src="<?php print $this->Html->url(array('controller' => 'pages', 'action' => 'eds', 'idpselect_config')); ?>"></script>

<?php
  print $this->Html->script('eds/idpselect');
