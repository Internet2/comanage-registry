<?php
/**
 * COmanage Registry Redirect View
 *
 * Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>
<!DOCTYPE html>
<html>
<head>
	<?php print $this->Html->charset(); ?>
	<title>
		<?php print _txt('op.processing'); ?>
	</title>

	<meta http-equiv="refresh" content="0;URL='<?php print $this->Html->url($vv_meta_redirect_target); ?>'" />   
</head>
<body>
	<div id="container">
		<div id="content">
			<?php print $this->fetch('content'); ?>
		</div>
	</div>
</body>
</html>
