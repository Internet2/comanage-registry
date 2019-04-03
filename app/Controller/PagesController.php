<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
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
 * @package       registry
 * @since         COmanage Registry v0.1, CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       Cake.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
  public $name = 'Pages';

/**
 * Default helper
 *
 * @var array
 */
  public $helpers = array('Html', 'Session');

/**
 * This controller does not use a model
 *
 * @var array
 */
  public $uses = array('CmpEnrollmentConfiguration');

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v0.8.4
   */
  
  function beforeFilter() {
    if($this->name == 'Pages') {
      if($this->request->params['pass'][0] == 'home') {
        if(!$this->Session->check('Auth.User')) {
          // Allow the front page to render without authentication. If there is an
          // authenticated user, we want Auth to run to set up authorizations.
          $this->Auth->allow();
        }
      } elseif($this->request->params['pass'][0] == 'public') {
        // Allow public pages to render without authentication.
        $this->Auth->allow();
      } elseif($this->request->params['pass'][0] == 'eds') {
        // EDS pages need to render without authentication.
        $this->Auth->allow();
        
        if($this->request->params['pass'][1] == 'idpselect_config') {
          // The configuration javascript doesn't need formatting.
          $this->layout = 'ajax';
        }
        
        $edsConfig = $this->CmpEnrollmentConfiguration->edsConfiguration();
        
        // And we need to set some parameters
        $this->set('vv_eds_help_url',
                   !empty($edsConfig['CmpEnrollmentConfiguration']['eds_help_url'])
                   ? "'" . $edsConfig['CmpEnrollmentConfiguration']['eds_help_url'] . "'"
                   : "null");
        
        $this->set('vv_eds_preferred_idps',
                   !empty($edsConfig['CmpEnrollmentConfiguration']['eds_preferred_idps'])
                   ? "['" . join("','", preg_split('/\R/', rtrim($edsConfig['CmpEnrollmentConfiguration']['eds_preferred_idps']))) . "']"
                   : "null");
                   
        $this->set('vv_eds_hidden_idps',
                   !empty($edsConfig['CmpEnrollmentConfiguration']['eds_hidden_idps'])
                   ? "['" . join("','", preg_split('/\R/', rtrim($edsConfig['CmpEnrollmentConfiguration']['eds_hidden_idps']))) . "']"
                   : "null");
      }
    }

    parent::beforeFilter();
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.2.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    if($this->Session->check('Auth.User')
       && $this->request->params['pass'][0] == 'home') {
      // This isn't really the best place to do this (in PagesController),
      // but the only other option is to do something in JavaScript in
      // home.ctp, which seems worse. Something to clean up at some point...
      
      // If the user is a member of exactly one CO, redirect to that CO's dashboard
      if(count($this->viewVars['menuContent']['cos']) == 1) {
        $co = reset($this->viewVars['menuContent']['cos']);
        
        if(isset($co['co_person']['status']) 
           // If the person is not active or graceperiod, they'll get stuck in an
           // infinite loop on login
           && ($co['co_person']['status'] == StatusEnum::Active
               || $co['co_person']['status'] == StatusEnum::GracePeriod)) {
          $this->redirect(array(
            'controller' => 'co_dashboards',
            'action'     => 'dashboard',
            'co'         => $co['co_id']
          ));
        }
      }
    }
  }

/**
 * Displays a view
 *
 * @param mixed What page to display
 * @return void
 */
  public function display() {
    $path = func_get_args();

    $count = count($path);
    if (!$count) {
      $this->redirect('/');
    }
    $page = $subpage = $title_for_layout = null;

    if (!empty($path[0])) {
      $page = $path[0];
    }
    if (!empty($path[1])) {
      $subpage = $path[1];
    }
    if (!empty($path[$count - 1])) {
      $title_for_layout = Inflector::humanize($path[$count - 1]);
    }
    $this->set(compact('page', 'subpage', 'title_for_layout'));
    $this->render(implode('/', $path));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return boolean True if user is authorized for the current operation, false otherwise
   */

  public function isAuthorized() {
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
          
    // Permission to render this page
    // We currently only route the welcome page through here, so always allow display.
    $p['display'] = true; 
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
