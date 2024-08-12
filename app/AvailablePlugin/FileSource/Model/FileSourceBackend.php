<?php
/**
 * COmanage Registry File OrgIdentitySource Backend Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("OrgIdentitySourceBackend", "Model");
App::uses("FileSourceBackendCSV", "FileSource.Model");

class FileSourceBackend extends OrgIdentitySourceBackend {
  public $name = "FileSourceBackend";
  
  // Backend model, once loaded
  protected $FSBackend = null;
  
  // Archive files, for caching
  protected $archive1 = null;
  protected $archive2 = null;

  // CO Id
  protected $coId = null;

  /**
   * Obtain a list of records changed since $lastStart, through $curStart.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $lastStart Time of start of last request, or 0 if no previous request
   * @param  Integer $curStart  Time of start of current request
   * @return Mixed              Array of SORIDs, or false if not supported
   * @throws RuntimeException
   */

  public function getChangeList($lastStart, $curStart) {
    if(!empty($this->pluginCfg['archivedir'])) {
      // Maintain a copy of the previous file in order to do a diff and
      // generate a changelist.
      
      $ret = array();
      
      $infile = $this->pluginCfg['filepath'];
      $basename = basename($infile);
      $this->archive1 = $this->pluginCfg['archivedir'] . DS . $basename . ".1";
      $this->archive2 = $this->pluginCfg['archivedir'] . DS . $basename . ".2";
      
      // We could either read the files simultaneously in order (lower memory requirement),
      // or read one and hash it (can read records out of sequence). For now we'll take
      // the second approach.
      
      if(is_readable($this->archive1)) {
        // Start by creating a set of previously known records.
        $knownRecords = array();
        
        $handle = fopen($this->archive1, "r");
        
        if(!$handle) {
          throw new RuntimeException('er.filesource.read', array($this->archive1));
        }
        
        // ignore the header line
        fgetcsv($handle);
        while(($data = fgetcsv($handle)) !== false) {
          // Implode the record back together for string comparison purposes.
          // This may not be the same as the original line due to quotes, etc.
          // $data[0] is the SORID
          $knownRecords[ $data[0] ] = implode(',', $data);
        }
        
        $knownCount = count($knownRecords);
        $newCount = 0;
        
        fclose($handle);
        
        // Now read the new file and look for changes.
        $handle = fopen($infile, "r");
        
        if(!$handle) {
          throw new RuntimeException('er.filesource.read', array($infile));
        }
        
        // ignore the header line
        fgetcsv($handle);
        while(($data = fgetcsv($handle)) !== false) {
          
          // $data[0] is the SORID
          if(array_key_exists($data[0], $knownRecords)) {
            $newData = implode(',', $data);

            if($newData != $knownRecords[ $data[0] ]) {
              // This record changed, push the SORID onto the change list
              $ret[] = $data[0];
            }
            
            // Unset the key so we can see which records were deleted.
            unset($knownRecords[ $data[0] ]);
          } else {
            // This is a new record (ie: in $infile, not in $archive1),
            // so we ignore it, except to count it.
            $newCount++;
          }
        }
        
        fclose($handle);
        
        // Finally, any remaining keys in $knownRecords are delete operations.
        if(!empty($knownRecords)) {
          $ret = array_merge($ret, array_keys($knownRecords));
        }
        
        if(!empty($this->pluginCfg['threshold_warn'])) {
          // Check the number of changed records vs warning threshold. Note this
          // check (correctly) does not run the first time a file is processed
          // since there will be no archive file to compare against.
          
          if(isset($this->pluginCfg['threshold_override'])
             && $this->pluginCfg['threshold_override']) {
            // Ignore thresholds, but unset this configuration for our next run
            
            $FileSource = ClassRegistry::init('FileSource.FileSource');
            
            $FileSource->clear();
            $FileSource->id = $this->pluginCfg['id'];
            
            $FileSource->saveField('threshold_override', false);
          } else {
            $changed = count($ret) + $newCount;
            $pct = floor(($changed * 100) / $knownCount);
            
            if($pct > $this->pluginCfg['threshold_warn']) {
              throw new RuntimeException(_txt('er.filesource.threshold', array($changed,
                                                                               $knownCount,
                                                                               $pct,
                                                                               $this->pluginCfg['threshold_warn'])));
            }
          }
        }
        
        // If no changes, don't archive.
        if(empty($ret)) {
          $ok = false;
        }
      } else {
        // If there is no archive file, return nothing, since there are no changes.
        // We do however want to create an archive file for our next run.
      }
      
      return $ret;
    } else {
      // changelist not supported if no archive dir
      
      return false;
    }
  }
  
  /**
   * Obtain a FileSource Backend according to the configuration.
   *
   * @since  COmanage Registry v4.0.0
   * @return FileSourceBackend File Source Backend
   */
  
  protected function getFSBackend() {
    if(!$this->FSBackend) {
      switch($this->pluginCfg['format']) {
        case FileSourceFormat::CSV1:
        case FileSourceFormat::CSV2:
          $this->FSBackend = new FileSourceBackendCSV($this->pluginCfg);
          break;
        default:
          throw new LogicException('NOT IMPLEMENTED');
          break;
      }
    }
    
    return $this->FSBackend;
  }
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    $FSBackend = $this->getFSBackend();
    
    return $FSBackend->groupableAttributes();
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    return $this->searchFile();
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    $FSBackend = $this->getFSBackend();
    
    return $FSBackend->resultToGroups($raw);
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $FSBackend = $this->getFSBackend();
    
    return $FSBackend->resultToOrgIdentity($result);
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   * @throws RuntimeException on backend specific errors
   */
  
  public function retrieve($id) {
    $ret = array();
    $results = $this->searchFile(array('SORID' => $id));
    
    if(empty($results)) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    if(count($results) > 1) {
      throw new OverflowException(_txt('er.id.unk-a', array($id)));
    }
    
    $ret['raw'] = json_encode($results[0]);
    $ret['orgidentity'] = $this->resultToOrgIdentity($results[0]);
    
    return $ret;
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    $ret = array();
    
    $results = $this->searchFile($attributes);
    
    foreach($results as $r) {
      // Use the file unique ID as the result uniqueId
      $ret[ $r[0] ] = $this->resultToOrgIdentity($r);
    }
    
    return $ret;
  }
  
  /**
   * Generate the set of searchable attributes for the IdentitySource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the IdentitySource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    // With CSV2, we can have multiple names, etc, so we use pseudo-keys to
    // define search attributes, then decipher them in searchFile.
    
    $attrs = array(
      // The label "SORID" is used by retrieve()
      'SORID'            => _txt('en.identifier.type', null, IdentifierEnum::SORID),
      'Given'            => _txt('fd.name.given'),
      'Family'           => _txt('fd.name.family'),
      // We need to use 'mail' because OrgIdentitySource::searchAllByEmail uses it
      // we may want to mandate standard attribute names...
      'mail'             => _txt('fd.email_address.mail')
    );
    
    return $attrs;
  }
  
  /**
   * Search the file.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes()), or null to obtain a list of all SORIDs
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function searchFile($attributes=null) {
    $FSBackend = $this->getFSBackend();
    
    return $FSBackend->searchFile($attributes);
  }
  
  /**
   * Set the plugin configuration for this backend.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($cfg) {
    // We want the parent behavior
    parent::setConfig($cfg);

    $args = array();
    $args['conditions']['OrgIdentitySource.id'] = $cfg['org_identity_source_id'];
    $args['contain'] = false;

    $OrgIdentitySource = ClassRegistry::init('OrgIdentitySource');
    $source_configuration =$OrgIdentitySource->find('first', $args);

    // But then we also need to pass the updated configuration to our backend
    $Backend = $this->getFSBackend();
    
    $Backend->setConfig($cfg);
    $this->coId = $source_configuration['OrgIdentitySource']['co_id'];
    $Backend->setCoId($source_configuration['OrgIdentitySource']['co_id']);
  }

  /**
   * Update any plugin specific cache following the processing of records returned
   * by getChangeList().
   * 
   * @since  COmanage Registry v4.3.0
   * @return boolean  true on success, false otherwise
   */

  public function updateCache() {
    // Update the archive file. updateCache() is called after processing is complete,
    // and only if at least one record changed. It's possible an irregular exist will
    // prevent the cache from being updated, in that case we'll just end up reprocessing
    // some records, which should effectively be a no-op. Historically, we kept two backup
    // copies in case something went wrong, we still do so here, though it's less critical now.
    
    if(is_readable($this->archive1)) {
      if(!copy($this->archive1, $this->archive2)) {
        throw new RuntimeException(_txt('er.filesource.copy', array($this->archive1, $this->archive2)));
      }
    }
    
    if(is_readable($this->pluginCfg['filepath'])) {
      if(!copy($this->pluginCfg['filepath'], $this->archive1)) {
        throw new RuntimeException(_txt('er.filesource.copy', array($this->pluginCfg['filepath'], $this->archive1)));
      }
    }

    return true;
  }
}
