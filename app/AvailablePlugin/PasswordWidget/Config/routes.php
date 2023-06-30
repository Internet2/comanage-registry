<?php

Router::connect(
  '/password_widget/co_password_widgets/password',
  array(
    'plugin' => 'password_widget',
    'controller' => 'co_password_widgets',
    'action' => 'password',
    '[method]' => 'POST'
  )
);