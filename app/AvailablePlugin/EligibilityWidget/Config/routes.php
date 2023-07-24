<?php

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/active',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'active',
    '[method]' => 'GET'
  )
);

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/all',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'all',
    '[method]' => 'GET'
  )
);

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/assign',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'assign',
    '[method]' => 'POST'
  )
);

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/eligibility',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'eligibility',
    '[method]' => 'POST'
  )
);

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/personroles/:id',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'personroles',
    '[method]' => 'GET'
  ),
  array(
    'pass' => array('id'),
    'id' => '[0-9]+'
  )
);

Router::connect(
  '/eligibility_widget/co_eligibility_widgets/sync/:id',
  array(
    'plugin' => 'eligibility_widget',
    'controller' => 'co_eligibility_widgets',
    'action' => 'sync',
    '[method]' => 'PUT'
  ),
  array(
    'pass' => array('id'),
    'id' => '[0-9]+'
  )
);