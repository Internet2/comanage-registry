<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
  
header("Content-Security-Policy: frame-ancestors 'self'");
$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
?>
<!DOCTYPE html>
<html>
<head>
	<?php print $this->Html->charset(); ?>
	<title>
		<?php print $cakeDescription ?>:
		<?php print filter_var($title_for_layout,FILTER_SANITIZE_STRING); ?>
	</title>
	<?php
		print $this->Html->meta('icon');

		print $this->Html->css('cake.generic');

		print $this->fetch('meta');
		print $this->fetch('css');
		print $this->fetch('script');
	?>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1><?php print $this->Html->link($cakeDescription, 'http://cakephp.org'); ?></h1>
		</div>
		<div id="content">

			<?php print $this->Session->flash(); ?>

			<?php print $this->fetch('content'); ?>
		</div>
		<div id="footer">
			<?php print $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt' => $cakeDescription, 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false)
				);
			?>
		</div>
	</div>
	<?php print $this->element('sql_dump'); ?>
</body>
</html>
