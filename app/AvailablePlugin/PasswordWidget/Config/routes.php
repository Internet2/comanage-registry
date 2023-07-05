<?php

Router::connect(
  '/password_widget/co_password_widgets/manage',
  array(
    'plugin' => 'password_widget',
    'controller' => 'co_password_widgets',
    'action' => 'manage',
    '[method]' => 'POST'
  )
);

Router::connect(
  '/password_widget/co_password_widgets/passwords/:id',
  array(
    'plugin' => 'password_widget',
    'controller' => 'co_password_widgets',
    'action' => 'passwords',
    '[method]' => 'GET'
  ),
  array(
    'pass' => array('id'),
    'id' => '[0-9]+'
  )
);