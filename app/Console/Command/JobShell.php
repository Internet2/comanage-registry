<?php
/**
 * COmanage Job Shell
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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class JobShell extends AppShell {
  var $uses = array('Co',
                    'CoExpirationPolicy',
                    'CoGroupMember',
                    'CoJob',
                    'CoSetting',
                    'Lock',
                    'OrgIdentitySource');
  
  /**
   * Dispatch the specified command.
   *
   * @since  COmanage Registry v3.3.0
   * @param  string  $command Command (plugin) to run
   * @param  array   $params  Parameters to pass to the job
   * @param  integer $coJobId If specified, the already queued job to process
   */
  
  public function dispatch($command, $params, $coJobId=null) {
    $classCore = Inflector::classify($command);
    $pluginName = $classCore . "Job";
    $modelName = $pluginName;
    $pluginModelName = $pluginName . "." . $modelName;
    
    $pluginModel = ClassRegistry::init($pluginModelName);
    
    try {
      $this->CoJob->clear();
      
      // Pull current user info (maybe move into a utility call?)
      $pwent = posix_getpwuid(posix_getuid());
        
      if($coJobId) {
        // Processing an already queued job. In this case, if parameter validation
        // fails we want to terminate the job so it can be re-queued. (So we make
        // sure CoJob->id is set before validation.)
        
        $this->CoJob->id = $coJobId;
        
        $this->validateParameters($pluginModel->parameterFormat(), $params);
        
        $this->CoJob->start($coJobId, _txt('rs.jb.started', array($pwent['name'], $pwent['uid'])));
      } else {
        // Register a new job. In this case, if parameter validation fails we want
        // to just emit the error and not register the job. (So we make sure
        // CoJob->id is *not* set.)
        
        $this->validateParameters($pluginModel->parameterFormat(), $params);
        
        // Register a new CoJob. This will throw an exception if a job is already in progress.
        
        $jobId = $this->CoJob->register($params['coid'],
                                        $classCore,
                                        null,
                                        null,
                                        _txt('rs.jb.started', array($pwent['name'], $pwent['uid'])));
        
        $this->out(_txt('rs.jb.registered', array($jobId)), 1, Shell::NORMAL);
        
        // Note actual passing of object here!
        $this->CoJob->id = $jobId;
      }
      
      // We have to look at $this->params for coid since we won't have it when we
      // were passed the Job ID.
      $pluginModel->execute($this->params['coid'], $this->CoJob, $params);
    }
    catch(Exception $e) {
      $this->out($e->getMessage(), 1, Shell::NORMAL);
      
      if(!empty($this->CoJob->id)) {
        $this->CoJob->finish($this->CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
      }
    }
  }
  
  /**
   * Execute expirations for the specified CO
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer  $coId       CO ID
   */
  
  protected function expirations($coId) {
    // First see if expirations are enabled
    
    if($this->CoSetting->expirationEnabled($coId)) {
      $this->CoExpirationPolicy->executePolicies($coId, $this);
    } else {
      $this->out("- " . _txt('sh.job.xp.disabled'));
    }
  }

  /**
   * Configure the option parser based on the available job plugins.
   * 
   * @since  COmanage Registry v3.3.0
   * @return Cake Option Parser
   */
  
  public function getOptionParser() {
    _bootstrap_plugin_txt();
    
    $parser = parent::getOptionParser();
    
    // Load the set of available jobs
    
    foreach($this->Co->loadAvailablePlugins('job') as $jModel) {
      $j = Inflector::tableize($jModel->name);
      // Toss the _job at the end
      $jobCmd = substr($j, 0, strlen($j)-5);
      
      // Add a subcommand
      $subparser = $jModel->getOptionParser();
      
      $parser->addSubcommand($jobCmd, array(
        'help' => 'Plugin text here', // XXX
        'parser' => $subparser
      ));
    }
    
    // XXX CO-1310 after the other jobs migrate to plugins, this can go away (implemented in CoJobBackend)
    $parser->addOption(
      'coid',
      array(
        'short'   => 'c',
        'help'    => _txt('sh.job.arg.coid'),
        'boolean' => false,
        'default' => false
      )
    )->addOption(
      'runqueue',
      array(
        'short'   => 'r',
        'help'    => _txt('sh.job.arg.runqueue'),
        'boolean' => true,
        'default' => false
      )
    )->addOption(
      'synchronous',
      array(
        'short'   => 's',
        'help'    => _txt('sh.job.arg.synchronous'),
        'boolean' => true,
        'default' => false
      )
    )->addOption(
      'unlock',
      array(
        'short'   => 'U',
        'help'    => _txt('sh.job.arg.unlock'),
        'boolean' => false,
        'default' => false
      )
    )->epilog(_txt('sh.job.arg.epilog'));
    
    return $parser;
  }
  
  /**
   * Execute group validity based reprovisioning for the specified CO
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $coId CO ID
   */
  
  protected function groupValidity($coId) {
    // Pull the current window for reprovisioning
    
    $w = $this->CoSetting->getGroupValiditySyncWindow($coId);
    
    if($w > 0) {
      $this->CoGroupMember->reprovisionByValidity($coId, $w);
    } else {
      $this->out("- " . _txt('sh.job.gv.disabled'));
    }
  }
  
  /**
   * Sync Organizational Identity Sources for the specified CO
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer  $coId       CO ID
   * @param  Boolean  $force      Force sync unchanged records
   */
  
  protected function syncOrgSources($coId, $force=false) {
    // First see if syncing is enabled
    
    if($this->CoSetting->oisSyncEnabled($coId)) {
      try {
        $this->OrgIdentitySource->syncAll($coId, $force);
      }
      catch(Exception $e) {
        $this->out("- " . $e->getMessage());
      }
    } else {
      $this->out("- " . _txt('sh.job.sync.ois.disabled'));
    }
  }
  
  /**
   * Validate Job parameters. This function performs additional checks on top of
   * the core Cake option parser checks.
   *
   * @since  COmanage Registry v3.3.0
   * @param  array $format Parameter format as per parameterFormat()
   * @param  array $args   Command line arguments, in name=value format
   * @return array         Array of validated name => value pairs
   * @throws InvalidArgumentException
   */
  
  protected function validateParameters($format, $args) {
    $ret = array();
    
    if(!empty($args)) {
      foreach($args as $attr => $val) {
        // Most validation is handled by Cake's option parser
        
        if(!isset($format[$attr])) {
          // Probably -s or -c
          continue;
        }
        
        // For attributes of type int, is the value an integer?
        if($format[$attr]['type'] == 'int') {
          if(!preg_match('/^[0-9.+-]*$/', $val)) {
            throw new InvalidArgumentException("Value for " . $attr . " is not an integer"); // XXX I18n
          }
        }
      }
    }
    
    // NOTE: Reassigning $attr below here!
    
    // Check that required values were provided
    foreach($format as $a => $cfg) {
      if($cfg['required'] && !isset($args[$a])) {
        throw new InvalidArgumentException("Required attribute " . $a . " not provided"); // XXX I18n
      }
    }
    
    return $ret;
  }
  
  /**
   * JobShell entry point.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  function main() {
    // Run background / scheduled tasks.
    
    // We need to run this in getOptionParser since that runs before main()
    //_bootstrap_plugin_txt();
    
    if(isset($this->params['runqueue']) && $this->params['runqueue']) {
      // Obtain a run lock
      
      try {
        $lockid = $this->Lock->obtain($this->params['coid'], 'jobshell');
      }
      catch(Exception $e) {
        $this->out(_txt('er.lock', array($e->getMessage())), 1, Shell::QUIET);
        return;
      }
      
      $this->out(_txt('sh.job.lock.obt', array($lockid)), 1, Shell::NORMAL);
      
      // Pull all jobs where status is queued
      $args = array();
      $args['conditions']['CoJob.co_id'] = $this->params['coid'];
      $args['conditions']['CoJob.status'] = JobStatusEnum::Queued;
      $args['order'] = 'CoJob.id ASC';
      $args['contain'] = false;
      
      $jobs = $this->CoJob->find('all', $args);
      
      $this->out(_txt('sh.job.count', array(count($jobs))), 1, Shell::NORMAL);
      
      foreach($jobs as $j) {
        $this->out(_txt('sh.job.proc', array($j['CoJob']['id'])), 1, Shell::NORMAL);
        
        // XXX CO-1729 pass in actor_co_person_id of registerer
        $this->dispatch($j['CoJob']['job_type'], json_decode($j['CoJob']['job_params'], true), $j['CoJob']['id']);
      }
      
      $this->out(_txt('sh.job.lock.rel'), 1, Shell::NORMAL);
      
      $this->Lock->release($lockid);
    } elseif(isset($this->params['synchronous']) && $this->params['synchronous']) {
      $args = $this->args;
      
      $command = array_shift($args);
      
      try {
        $lockid = $this->Lock->obtain($this->params['coid'], 'jobshell');
      }
      catch(Exception $e) {
        $this->out(_txt('er.lock', array($e->getMessage())), 1, Shell::QUIET);
        return;
      }
      
      $this->dispatch($command, $this->params);
      
      $this->Lock->release($lockid);
    } elseif(isset($this->params['unlock']) && $this->params['unlock']) {
      $this->Lock->release($this->params['unlock']);
    } else {
      // First, pull a set of COs
      
      $args = array();
      $args['conditions']['Co.status'] = TemplateableStatusEnum::Active;
      $args['contain'] = false;
      
      $cos = $this->Co->find('all', $args);
      
      // Now hand off to the various tasks
      $runAll = empty($this->args);
      $runCoId = $this->params['coid'];
      
      foreach($cos as $co) {
        if(!$runCoId || $runCoId == $co['Co']['id']) {
          if($runAll || in_array('expirations', $this->args)) {
            $this->out(_txt('sh.job.xp', array($co['Co']['name'], $co['Co']['id'])));
            $this->expirations($co['Co']['id']);
          }
          
          if($runAll || in_array('forcesyncorgsources', $this->args)) {
            $this->out(_txt('sh.job.sync.ois', array($co['Co']['name'], $co['Co']['id'], _txt('fd.yes'))));
            $this->syncOrgSources($co['Co']['id'], true);
          }
          
          if($runAll || in_array('groupvalidity', $this->args)) {
            $this->out(_txt('sh.job.gv', array($co['Co']['name'], $co['Co']['id'])));
            $this->groupValidity($co['Co']['id']);
          }
          
          if($runAll || in_array('syncorgsources', $this->args)) {
            $this->out(_txt('sh.job.sync.ois', array($co['Co']['name'], $co['Co']['id'], _txt('fd.no'))));
            $this->syncOrgSources($co['Co']['id']);
          }
        }
      }
    }    
    
    $this->out(_txt('sh.job.done'));
  }
}
