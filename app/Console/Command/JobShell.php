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
                    'CoJob',
                    'CoLocalization',
                    'CmpEnrollmentConfiguration',
                    'Lock');
  
  /**
   * Dispatch the specified command.
   *
   * @since  COmanage Registry v3.3.0
   * @param  string  $command Command (plugin) to run
   * @param  array   $params  Parameters to pass to the job
   * @param  integer $coJobId If specified, the already queued job to process
   */
  
  public function dispatch($command, $params, $coJobId=null) {
    // In v3.3.0, the job name "Foo" implied a plugin named "FooJob" and a
    // model named "FooJob". However, this prevents other types of plugins from
    // defining jobs, and also prevents multiple jobs from being defined in the
    // same plugin.
    
    // As of v4.0.0, commands are of the form "Plugin.Foo" corresponding to the
    // plugin model implementing the job (without "Job" suffixed).
    
    $pluginModelName = $command . "Job";
    $pluginModel = ClassRegistry::init($pluginModelName);
    
    try {
      $this->CoJob->clear();
      
      // Pull current user info (maybe move into a utility call?, also used again below)
      $pwent = posix_getpwuid(posix_getuid());
        
      if($coJobId) {
        // Processing an already queued job. In this case, if parameter validation
        // fails we want to terminate the job so it can be re-queued. (So we make
        // sure CoJob->id is set before validation.)
        
        $this->CoJob->id = $coJobId;
        
        $this->validateParameters($pluginModel->parameterFormat(), $params);
      } else {
        // Register a new job. In this case, if parameter validation fails we want
        // to just emit the error and not register the job. (So we make sure
        // CoJob->id is *not* set.)
        
        $this->validateParameters($pluginModel->parameterFormat(), $params);
        
        // Register a new CoJob. This will throw an exception if a job is already in progress.
        
        $jobId = $this->CoJob->register($params['coid'],
                                        $command,
                                        null,
                                        null,
                                        _txt('rs.jb.started', array($pwent['name'], $pwent['uid'])));
        
        $this->out(_txt('rs.jb.registered', array($jobId)), 1, Shell::NORMAL);
        
        // Note actual passing of object here!
        $this->CoJob->id = $jobId;
      }
      
      if($this->CoJob->id 
         || (isset($params['synchronous']) && $params['synchronous'])) {
        $this->CoJob->start($this->CoJob->id, _txt('rs.jb.started', array($pwent['name'], $pwent['uid'])));
        
        // We have to look at $this->params for coid since we won't have it when we
        // were passed the Job ID.
        $pluginModel->execute($this->params['coid'], $this->CoJob, $params);
      }
    }
    catch(Exception $e) {
      $this->out($e->getMessage(), 1, Shell::NORMAL);
      
      if(!empty($this->CoJob->id)) {
        $this->CoJob->finish($this->CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
      }
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
    
    foreach($this->Co->loadAvailablePlugins('job') as $jPlugin) {
      $pluginModel = ClassRegistry::init($jPlugin->name . "." . $jPlugin->name);
        
      $models = $pluginModel->getAvailableJobs();

      foreach($models as $jModel => $helpTxt) {
        $command = $jPlugin->name . "." . $jModel;
        $jobModel = ClassRegistry::init($command . "Job", true);
        
        // Add a subcommand
        $subparser = $jobModel->getOptionParser();
        
        $parser->addSubcommand($command, array(
          'help' => $helpTxt,
          'parser' => $subparser
        ));
      }
    }
    
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
      'unlock',
      array(
        'short'   => 'U',
        'help'    => _txt('sh.job.arg.unlock'),
        'boolean' => false,
        'default' => false
      )
    )->addOption(
      'cancel',
      array(
        'short'   => 'X',
        'help'    => _txt('sh.job.arg.cancel'),
        'boolean' => false,
        'default' => false
      )
    );
    
    return $parser;
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

    // Set App.base configuration
    $app_base = $this->CmpEnrollmentConfiguration->getAppBase();
    Configure::write('App.base', $app_base);
    
    // We need to run this in getOptionParser since that runs before main()
    //_bootstrap_plugin_txt();
    
    if(isset($this->params['cancel']) && $this->params['cancel']) {
      $pwent = posix_getpwuid(posix_getuid());
      $this->CoJob->cancel($this->params['cancel'], $pwent['name']);
    } elseif(isset($this->params['runqueue']) && $this->params['runqueue']) {
      // Obtain a run lock
      
      try {
        $lockid = $this->Lock->obtain($this->params['coid'], 'jobshell');
      }
      catch(Exception $e) {
        $this->out(_txt('er.lock', array($e->getMessage())), 1, Shell::QUIET);
        return;
      }
      
      $this->out(_txt('sh.job.lock.obt', array($lockid)), 1, Shell::NORMAL);

      // Load localizations
      $this->CoLocalization->load($this->params['coid']);

      // Pull all jobs where status is queued and whose start time isn't deferred.
      // We do this in a loop rather than pull all at once for two reasons:
      // (1) to work with very large queues (this is effectively keyset pagination
      // with a single entry page size) and (2) for future compatibility with
      // support for multiple queue runners.
      
      $args = array();
      $args['conditions']['CoJob.co_id'] = $this->params['coid'];
      $args['conditions']['CoJob.status'] = JobStatusEnum::Queued;
      $args['conditions']['OR'] = array(
        'CoJob.start_after_time IS NULL',
        'CoJob.start_after_time < ' => date('Y-m-d H:i:s', time())
      );
      $args['order'] = 'CoJob.id ASC';
      $args['limit'] = 1;
      $args['contain'] = false;
      
      $count = $this->CoJob->find('count', $args);
      
      $this->out(_txt('sh.job.count', array($count)), 1, Shell::NORMAL);
      
      // In order to prevent resource exhaustion, we'll cap the number of jobs
      // we run to 100 at which point we'll exit and another process can be started.
      $maxtodo = 100;

      while($maxtodo > 0) {
        $maxtodo--;
        
        // We sort by id ASC so we always get the oldest job ready to process.
        // XXX When we support multiple queue runners we'll need a read lock,
        // at least until we change the job status.
        
        $job = $this->CoJob->find('first', $args);
        
        if(!empty($job)) {
          $this->out(_txt('sh.job.proc', array($job['CoJob']['id'])), 1, Shell::NORMAL);
          
          // XXX CO-1729 pass in actor_co_person_id of registerer
          $this->dispatch($job['CoJob']['job_type'], json_decode($job['CoJob']['job_params'], true), $job['CoJob']['id']);
        } else {
          break;
        }
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
    }
    
    $this->out(_txt('sh.job.done'));
  }
}
