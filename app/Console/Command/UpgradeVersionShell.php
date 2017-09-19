<?php
/**
 * COmanage Upgrade Shell (not called "UpgradeShell" to avoid conflict with Cake's Upgrade shell)
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class UpgradeVersionShell extends AppShell {
  var $uses = array('Meta',
                    'Address',
                    'CmpEnrollmentConfiguration',
                    'Co',
                    'CoEnrollmentAttributeDefault',
                    'CoEnrollmentFlow',
                    'CoGroup',
                    'GrouperProvisioner.CoGrouperProvisionerTarget',
                    'Identifier');
  
  // A list of known versions, must be semantic versioning compliant. The value
  // is a "blocker" if it is a version that prevents an upgrade from happening.
  // For example, if a user attempts to upgrade from 1.0.0 to 1.2.0, and 1.1.0
  // is flagged as a blocker, then the upgrade must be performed in two steps
  // (1.0.0 -> 1.1.0, then 1.1.0 -> 1.2.0). Without the blocker, an upgrade from
  // 1.0.0 to 1.2.0 is permitted.
  
  // A typical scenario for blocking is when a pre### step must run after an
  // earlier version's post### step. Because we don't (yet) have the capability
  // to run database updates on a per-release basis, we run all relevant pre
  // steps, then the database update, then all relevant post update steps.
  // So if (eg) the admin is upgrading from 1.0.0 past 1.1.0 to 1.2.0 and there
  // are no blockers, the order of operations is 1.1.0-pre, 1.2.0-pre, database,
  // 1.1.0-post, 1.2.0-post.

  // Make sure to keep this list in order so we can walk the array rather than compare version strings.
  // You must specify the 'block' parameter. If you flag a version as blocking, be sure to document why.
  protected $versions = array(
    "0.9.3" => array('block' => false),
    // 0.9.4 blocks because it's the first version to use UpgradeVersionShell.
    // Also, see notes in Address::_ug094().
    "0.9.4" => array('block' => true, /* 'pre' => 'pre094', */ 'post' => 'post094'),
    // 1.0.0 blocks because of the introduction of /local
    "1.0.0" => array('block' => true, 'post' => 'post100'),
    "1.0.1" => array('block' => false),
    "1.0.2" => array('block' => false),
    "1.0.3" => array('block' => false),
    "1.0.4" => array('block' => false),
    "1.0.5" => array('block' => false, 'post' => 'post105'),
    "1.0.6" => array('block' => false),
    "1.0.7" => array('block' => false),
    "2.0.0" => array('block' => false, 'post' => 'post110'),
    "2.0.1" => array('block' => false),
    "2.0.2" => array('block' => false),
    "3.0.0" => array('block' => false)
  );
  
  public function getOptionParser() {
    $parser = parent::getOptionParser();
    
    $parser->addArgument(
      'version',
      array(
        'help'     => _txt('sh.ug.arg.version'),
        'required' => false
      )
    )->addOption(
      'forcecurrent',
      array(
        'short' => 'f',
        'help' => _txt('sh.ug.arg.forcecurrent'),
        'boolean' => false,
        'default' => false
      )
    )->addOption(
      'skipdatabase',
      array(
        'short' => 'D',
        'help' => _txt('sh.ug.arg.skipdatabase'),
        'boolean' => true,
        'default' => false
      )
    )->addOption(
      'skipvalidation',
      array(
        'short' => 'X',
        'help' => _txt('sh.ug.arg.skipvalidation'),
        'boolean' => true,
        'default' => false
      )
    )->description(_txt('sh.ug.arg.desc'));
    
    return $parser;
  }
  
  /**
   * Validate the requested from and to versions.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $from "From" version (current database)
   * @param  String $to "To" version (current codebase)
   * @return Boolean True if the requested range is valid
   * @throws InvalidArgumentException
   */
  
  protected function validateVersions($from, $to) {
    // First make sure these are valid versions
    
    if(!array_key_exists($from, $this->versions)) {
      throw new InvalidArgumentException(_txt('er.ug.version', array($from)));
    }
    
    if(!array_key_exists($to, $this->versions)) {
      throw new InvalidArgumentException(_txt('er.ug.version', array($to)));
    }
    
    // If $from and $to are the same, nothing to do.
    
    if($from == $to) {
      throw new InvalidArgumentException(_txt('er.ug.same'));
    }
    
    // Walk through the version array and check our version path
    
    $fromFound = false;
    
    foreach($this->versions as $version => $params) {
      $blocks = $params['block'];
      
      if($version == $from) {
        $fromFound = true;
      } elseif($version == $to) {
        if(!$fromFound) {
          // Can't downgrade ($from must preceed $to)
          throw new InvalidArgumentException(_txt('er.ug.order'));
        } else {
          // We're good to go
          break;
        }
      } else {
        if($fromFound && $blocks) {
          // We can't pass a blocker version
          throw new InvalidArgumentException(_txt('er.ug.blocked', array($version)));
        }
      }
    }
    
    return true;
  }
  
  function main() {
    // Merge plugin texts, in case any plugins end up being called
    _bootstrap_plugin_txt();
    
    // Pull current (PHP code) version
    $targetVersion = null;
    
    if(!empty($this->args[0])) {
      // Use requested target version
      $targetVersion = $this->args[0];
    } else {
      // Read the current release from the VERSION file
      $versionFile = APP . DS . 'Config' . DS . "VERSION";
      
      $targetVersion = rtrim(file_get_contents($versionFile));
    }
    
    // Pull current database version
    
    $currentVersion = $this->params['forcecurrent'];
    
    if(!$currentVersion) {
      $currentVersion = $this->Meta->getUpgradeVersion();
    }
    
    $this->out(_txt('sh.ug.current', array($currentVersion)));
    $this->out(_txt('sh.ug.target', array($targetVersion)));
    
    $skipValidation = $this->params['skipvalidation'];
    
    if(!$skipValidation) {
      // Validate the version path
      try {
        $this->validateVersions($currentVersion, $targetVersion);
      }
      catch(Exception $e) {
        $this->out($e->getMessage());
        $this->out(_txt('er.ug.fail'));
        exit;
      }
    }
    
    // Run appropriate pre-database steps
    
    $fromFound = false;
    
    foreach($this->versions as $version => $params) {
      if($version == $currentVersion) {
        // Note we don't actually want to run the steps for $currentVersion
        $fromFound = true;
        continue;
      }
      
      if(!$fromFound) {
        // We haven't reached the from version yet
        continue;
      }
      
      if(isset($params['pre'])) {
        $fn = $params['pre'];
        
        $this->out(_txt('sh.ug.pre', array($fn)));
        $this->$fn();
      }
      
      if($version == $targetVersion) {
        // We're done
        break;
      }
    }
    
    $skipDatabase = $this->params['skipdatabase'];
    
    if(!$skipDatabase) {
      // Call database shell
      $this->dispatchShell('database');
    }
    
    // Run appropriate post-database steps
    
    $fromFound = false;
    
    foreach($this->versions as $version => $params) {
      if($version == $currentVersion) {
        // Note we don't actually want to run the steps for $currentVersion
        $fromFound = true;
        continue;
      }
      
      if(!$fromFound) {
        // We haven't reached the from version yet
        continue;
      }
      
      if(isset($params['post'])) {
        $fn = $params['post'];
        
        $this->out(_txt('sh.ug.post', array($fn)));
        $this->$fn();
      }
      
      if($version == $targetVersion) {
        // We're done
        break;
      }
    }
    
    // Now that we're done, update the current version
    $this->Meta->setUpgradeVersion($targetVersion,
                                   // If we're upgrading from 0.9.3, we need to
                                   // create the row in the database
                                   $currentVersion == '0.9.3');
  }
  
  // Version specific pre/post functions
  
  public function post094() {
    // 0.9.4 consolidates cm_addresses:line1 and line2 into street (CO-539)
    $this->out(_txt('sh.ug.094.address'));
    $this->Address->_ug094();
  }
  
  public function post100() {
    // 1.0.0 migrates org identity pooling to setup (CO-1160), so we check to make
    // sure the default CMP enrollment configuration is set.
    $this->out(_txt('sh.ug.100.cmpdefault'));
    
    if(!$this->CmpEnrollmentConfiguration->findDefault()) {
      // If no default entry is found, create one. This will force org identities
      // to be unpooled (which is the default).
      $this->CmpEnrollmentConfiguration->createDefault();
    }
  }

  public function post105() {
    // 1.0.5 fixes a bug (CO-1287) that created superfluous attribute default entries.
    // This will clean them out.
    // 1.0.0 migrates org identity pooling to setup (CO-1160), so we check to make
    // sure the default CMP enrollment configuration is set.
    $this->out(_txt('sh.ug.105.attrdefault'));
    $this->CoEnrollmentAttributeDefault->_ug105();
  }
  
  public function post110() {
    // 2.0.0 was originally going to be 1.1.0. Rather than rename all this internal
    // stuff and risk breaking something, we'll leave the 1.1.0 references in place.
    
    // 2.0.0 replaces CoEnrollmentFlow::verify_email with email_verification_mode.
    $this->out(_txt('sh.ug.110.ef'));
    $this->CoEnrollmentFlow->_ug110();

    // 2.0.0 deprecates using a database view as the source of subjectes for Grouper.
    // Mark existing GrouperProvisioner targets as using the legacy view.
    $this->out(_txt('sh.grouperprovisioner.ug.110.gp'));
    $this->CoGrouperProvisionerTarget->_ug110();
    
    // 2.0.0 changes how automatic groups are populated.
    $this->out(_txt('sh.ug.110.gr'));
    
    // Start by pulling the list of COs and its COUs.
    
    $args = array();
    // Because CO is not Changelog but COU is, we have to pull COUs separately
    $args['contain'] = false;
    
    $cos = $this->Co->find('all', $args);
    
    // We update inactive COs as well, in case they become active again
    foreach($cos as $co) {
      $this->out('- ' . $co['Co']['name']);

      $this->CoGroup->_ug110($co['Co']['id'], $co['Co']['name']);
      
      $args = array();
      $args['conditions']['Cou.co_id'] = $co['Co']['id'];
      $args['contain'] = false;
      
      $cous = $this->Co->Cou->find('all', $args);
      
      foreach($cous as $cou) {
        $this->out('-- ' . $cou['Cou']['name']);
        
        $this->CoGroup->_ug110($co['Co']['id'],  $co['Co']['name'], $cou['Cou']['id'], $cou['Cou']['name']);
      }
    }
    
    // 2.0.0 uses SuspendableStatusEnum for Identifier::status
    $this->out(_txt('sh.ug.110.is'));
    $this->Identifier->_ug110();
  }
}
