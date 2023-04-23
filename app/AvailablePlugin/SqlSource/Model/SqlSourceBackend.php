<?php
/**
 * COmanage Registry SQL OrgIdentitySource Backend Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("OrgIdentitySourceBackend", "Model");
App::uses("SqlServer", "Model");

class SqlSourceBackend extends OrgIdentitySourceBackend {
  public $name = "SqlSourceBackend";
  
  // Cached SqlServer connection and Cake Auto-Model
  protected $SqlServer = null;
  protected $SourceRecord = null;
  protected $server_id = null;
  
  /**
   * Get the available set of AdHoc Attributes.
   *
   * @since  COmanage Registry v4.1.0
   * @return array  Array of Ad Hoc Attribute tags
   */
  
  protected function getAdHocAttributes() {
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Flat) {
      // The "standard" attributes
      $standardAttrs = array(
        'id',
        'sorid',
        'honorific',
        'given',
        'middle',
        'family',
        'suffix',
        'affiliation',
        'date_of_birth',
        'valid_from',
        'valid_through',
        'title',
        'o',
        'ou',
        'manager_identifier',
        'sponsor_identifier',
        'address',
        'mail',
        'identifier',
        'telephone_number',
        'url',
        'modified'
      );
      
      // Introspect the inbound attributes
      $SourceRecord = $this->getRecordModel();
      
      $columnTypes = $SourceRecord->getColumnTypes();
      
      return array_diff(array_keys($columnTypes), $standardAttrs);
    } else {
      // In Relational mode, we pull the unique tags
      
      $AdHoc = $this->getRecordModel('AdHocAttribute');
      
      $args = array();
      $args['fields'] = 'DISTINCT '.$AdHoc->alias.'.tag';
      $args['contain'] = false;
      
      try {
        // find('list') would make more sense but because of the nature of our
        // query doesn't work so well
        $tags = $AdHoc->find('all', $args);
        
        return Hash::extract($tags, '{n}.'.$AdHoc->alias.'.tag');
      }
      catch(MissingTableException $e) {
        // If there is no AdHocAttribute table just return an empty array
        return array();
      }
    }
  }
  
  /**
   * Obtain a list of records changed since $lastStart, through $curStart.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $lastStart Time of start of last request, or 0 if no previous request
   * @param  Integer $curStart  Time of start of current request
   * @return Mixed              Array of SORIDs, or false if not supported
   * @throws RuntimeException
   */

  public function getChangeList($lastStart, $curStart) {
    $SourceRecord = $this->getRecordModel();
    
    $columnTypes = $SourceRecord->getColumnTypes();
  
    if(!isset($columnTypes['modified'])) {
      return false;
    }
    
    $args = array();
    $args['conditions'][$SourceRecord->alias.'.modified >'] = date('Y-m-d H:i:s', $lastStart);
    $args['conditions'][$SourceRecord->alias.'.modified <='] = date('Y-m-d H:i:s', $curStart);
    $args['fields'] = array('sorid', 'modified');
    $args['contain'] = false;
    
    $changeList = $SourceRecord->find('list', $args);
    
    return array_keys($changeList);
  }
  
  /**
   * Obtain a Model object for Source Records.
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $relatedModel If specified, obtain a Model for this related model
   * @return Model                Cake Model object
   */
  
  protected function getRecordModel(?string $relatedModel=null) {
    // We only cache the core model (if relational) since that might get
    // used multiple times in a single action. We check the server_id
    // since we might be called for different configurations eg during
    // an OIS sync.
    
    if(!$relatedModel 
       && $this->SourceRecord 
       && $this->server_id == $this->pluginCfg['server_id']) {
      return $this->SourceRecord;
    }
    
    if(!$this->SqlServer) {
      $this->SqlServer = ClassRegistry::init('SqlServer');

      $this->SqlServer->connect($this->pluginCfg['server_id'], "sourcedb");
    }
    
    $tableName = $this->pluginCfg['source_table'];
    
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Relational
       && $relatedModel) {
      $tableName .= "_" . Inflector::tableize($relatedModel);
    }
    
    $SourceRecord = new Model(array(
      'table'  => $tableName,
      'name'   => 'SourceRecord' . $relatedModel,
      'ds'     => 'sourcedb'
    ));
    
    if(!$relatedModel) {
      // Cache the core model
      $this->SourceRecord = $SourceRecord;
      $this->server_id = $this->pluginCfg['server_id'];
    }
    
    return $SourceRecord;
  }
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    $adhoc = $this->getAdHocAttributes();
    
    return array_combine(array_values($adhoc), array_values($adhoc));
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    $SourceRecord = $this->getRecordModel();
    
    $args = array();
    $args['fields'] = array('id', 'sorid');
    $args['contain'] = false;
    
    $inventory = $SourceRecord->find('list', $args);
    
    return array_values($inventory);
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v4.1.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    $ret = array();
    
    // Hash wants to work with an array, not an object
    $result = json_decode($raw, true);
    
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Flat) {
      foreach($this->groupableAttributes() as $g) {
        if(isset($result['SourceRecord'][$g])) {
          $ret[$g][] = array('value' => $result['SourceRecord'][$g]);
        }
      }
    } else {
      foreach($this->groupableAttributes() as $g) {
        $adhoc = Hash::extract($result['AdHocAttribute'], '{n}[tag='.$g.']');
        
        if(!empty($adhoc)) {
          foreach($adhoc as $a) {
            $ret[$g][] = array('value' => $a['value']);
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array $result Sql Search Result
   * @return Array Org Identity and related models, in the usual format
   * @throws InvalidArgumentException
   */
  
  protected function resultToOrgIdentity($result) {
    // For flat mode, any unexpected column becomes an ad hoc attribute.
    
    $orgdata = array();
    
    // Start with attributes common to flat and relational modes
    
    if(!empty($result['SourceRecord']['affiliation'])) {
      $orgdata['OrgIdentity']['affiliation'] = $result['SourceRecord']['affiliation'];
    } else {
      $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    }
    
    foreach(array('date_of_birth', 
                  'manager_identifier',
                  'o',
                  'ou',
                  'sponsor_identifier',
                  'title',
                  'valid_from',
                  'valid_through') as $attr) {
      if(!empty($result['SourceRecord'][$attr])) {
        $orgdata['OrgIdentity'][$attr] = $result['SourceRecord'][$attr];
      }
    }
    
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Flat) {
      $orgdata['Name'][] = array(
        'honorific'     => (!empty($result['SourceRecord']['honorific']) ? $result['SourceRecord']['honorific'] : null),
        'given'         => (!empty($result['SourceRecord']['given']) ? $result['SourceRecord']['given'] : null),
        'middle'        => (!empty($result['SourceRecord']['middle']) ? $result['SourceRecord']['middle'] : null),
        'family'        => (!empty($result['SourceRecord']['family']) ? $result['SourceRecord']['family'] : null),
        'suffix'        => (!empty($result['SourceRecord']['suffix']) ? $result['SourceRecord']['suffix'] : null),
        'type'          => $this->pluginCfg['name_type'],
        'primary_name'  => true
      );
      
      if(!empty($result['SourceRecord']['address'])) {
        $bits = explode(',', $result['SourceRecord']['address']);
        
        $orgdata['Address'][] = array(
          'street'      => $bits[0],
          'locality'    => $bits[1],
          'state'       => $bits[2],
          'postal_code' => $bits[3],
          'type'        => $this->pluginCfg['address_type']
        );
      }
      
      if(!empty($result['SourceRecord']['mail'])) {
        $orgdata['EmailAddress'][] = array(
          'mail'      => $result['SourceRecord']['mail'],
          'type'      => $this->pluginCfg['email_address_type'],
          'verified'  => true
        );
      }
      
      if(!empty($result['SourceRecord']['identifier'])) {
        $orgdata['Identifier'][] = array(
          'identifier'  => $result['SourceRecord']['identifier'],
          'type'        => $this->pluginCfg['identifier_type'],
          'status'      => SuspendableStatusEnum::Active
        );
      }
      
      if(!empty($result['SourceRecord']['telephone_number'])) {
        $orgdata['TelephoneNumber'][] = array(
          'number'  => $result['SourceRecord']['telephone_number'],
          'type'    => $this->pluginCfg['telephone_number_type']
        );
      }
      
      if(!empty($result['SourceRecord']['url'])) {
        $orgdata['Url'][] = array(
          'url'     => $result['SourceRecord']['url'],
          'type'    => $this->pluginCfg['url_type']
        );
      }
      
      // Process remaining columns as ad hoc attributes
      foreach($this->getAdHocAttributes() as $attr) {
        $orgdata['AdHocAttribute'][] = array(
          'tag'   => $attr,
          'value' => $result['SourceRecord'][$attr]
        );
      }
    } else {
      // Relational Mode
      
      // We want exactly one Primary Name. The first name we see becomes primary
      // if none are flagged primary.
      $primaryNameSet = false;
      
      foreach($result['Name'] as $name) {
        $n = array(
          // This might be overridden
          'primary_name' => false
        );
        
        foreach(array('honorific', 'given', 'middle', 'family', 'suffix', 'type', 'language') as $k) {
          if(!empty($name[$k])) {
            $n[$k] = $name[$k];
          }
        }
        
        if(!$primaryNameSet && isset($name['primary_name']) && $name['primary_name']) {
          $n['primary_name'] = true;
          $primaryNameSet = true;
        }
        
        $orgdata['Name'][] = $n;
      }
      
      if(!$primaryNameSet) {
        $orgdata['Name'][0]['primary_name'] = true;
      }
      
      if(!empty($result['Address'])) {
        foreach($result['Address'] as $addr) {
          $a = array();
          
          foreach(array('street', 'room', 'locality', 'state', 'postal_code', 'country', 'type', 'language', 'description') as $k) {
            if(!empty($addr[$k])) {
              $a[$k] = $addr[$k];
            }
          }
          
          $orgdata['Address'][] = $a;
        }
      }
      
      if(!empty($result['AdHocAttribute'])) {
        foreach($result['AdHocAttribute'] as $adhoc) {
          $a = array();
          
          foreach(array('tag', 'value') as $k) {
            if(!empty($adhoc[$k])) {
              $a[$k] = $adhoc[$k];
            }
          }
          
          $orgdata['AdHocAttribute'][] = $a;
        }
      }
      
      if(!empty($result['EmailAddress'])) {
        foreach($result['EmailAddress'] as $email) {
          $m = array(
            // This might be overridden
            'verified' => false
          );
          
          foreach(array('mail', 'type', 'verified', 'description') as $k) {
            if(!empty($email[$k])) {
              $m[$k] = $email[$k];
            }
          }
          
          $orgdata['EmailAddress'][] = $m;
        }
      }
      
      if(!empty($result['Identifier'])) {
        foreach($result['Identifier'] as $id) {
          $i = array(
            'status' => SuspendableStatusEnum::Active,
            // This might be overridden
            'login' => false
          );
          
          foreach(array('identifier', 'type', 'login') as $k) {
            if(!empty($id[$k])) {
              $i[$k] = $id[$k];
            }
          }
          
          $orgdata['Identifier'][] = $i;
        }
      }
      
      if(!empty($result['TelephoneNumber'])) {
        foreach($result['TelephoneNumber'] as $number) {
          $n = array();
          
          foreach(array('country_code', 'area_code', 'number', 'extension', 'type', 'description') as $k) {
            if(!empty($number[$k])) {
              $n[$k] = $number[$k];
            }
          }
          
          $orgdata['TelephoneNumber'][] = $n;
        }
      }
      
      if(!empty($result['Url'])) {
        foreach($result['Url'] as $url) {
          $u = array();
          
          foreach(array('url', 'type', 'description') as $k) {
            if(!empty($url[$k])) {
              $u[$k] = $url[$k];
            }
          }
          
          $orgdata['Url'][] = $u;
        }
      }
    }  
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v4.1.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   * @throws RuntimeException on backend specific errors
   */
  
  public function retrieve($id) {
    $SourceRecord = $this->getRecordModel();
    
    $args = array();
    $args['conditions']['SourceRecord.sorid'] = $id;
    
    // Because we find('first') we shouldn't get more than one record.
    $record = $SourceRecord->find('first', $args);
    
    if(empty($record)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('fd.sorid'), $id)));
    }
    
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Relational) {
      // Pull data from the associated models. Cake doesn't really want to
      // operate containable over dynamic models, so we just pull the related
      // data ourselves.
      
      foreach(array('Address',
                    'AdHocAttribute',
                    'EmailAddress',
                    'Identifier',
                    'Name',
                    'TelephoneNumber',
                    'Url') as $rmodel) {
        $RelatedModel = $this->getRecordModel($rmodel);
        
        $args = array();
        $args['conditions'][$RelatedModel->alias.'.sorid'] = $id;
        
        try {
          $relatedRecords = $RelatedModel->find('all', $args);
        }
        catch(MissingTableException $e) {
          // If any related table other than Name is missing, we'll skip it
          
          if($rmodel == 'Name') {
            throw new RuntimeException($e->getMessage());
          }
          
          continue;
        }
        // Let all other exceptions bubble up

        // The result is returned as 0.Name.foo, but we really need Name.0.foo
        foreach($relatedRecords as $rr) {
          $record[$rmodel][] = $rr[$RelatedModel->alias];
        }
      }
    }

    $ret = array(
      'raw'         => json_encode($record),
      'orgidentity' => $this->resultToOrgIdentity($record)
    );
    
    return($ret);
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    $ret = array();
    
    if($this->pluginCfg['table_mode'] == SqlSourceTableModeEnum::Flat) {
      $SourceRecord = $this->getRecordModel();
      
      $args = array();
      foreach($attributes as $field => $q) {
        // We use OR searches for consistency with Relational mode
        if($field == 'given' || $field == 'family') {
          $args['conditions']['OR']['LOWER(SourceRecord.'.$field.') LIKE'] = strtolower($q) . '%';
        } else {
          $args['conditions']['OR'][$field] = $q;
        }
      }
      $args['contain'] = false;
      
      $results = $SourceRecord->find('all', $args);
      
      foreach($results as $r) {
        $ret[ $r['SourceRecord']['sorid'] ] = $this->resultToOrgIdentity($r);
      }
    } else {
      // Relational searches are a bit more complicated. Basically, we'll
      // perform a search on each provided attribute and then OR the results
      // together. (AND would be more complicated, we'd have to do the same
      // searches but only return SORIDs that were present in all the results.)
      
      $results = array();
      
      if(!empty($attributes['sorid'])) {
        // We can simply retrieve() this, ignoring not found errors
        
        try {
          $rec = $this->retrieve($attributes['sorid']);
        }
        catch(InvalidArgumentException $e) {
        }
        
        if(!empty($rec)) {
          $ret[ $attributes['sorid'] ] = $rec['orgidentity'];
        }
      }
      
      foreach(array(
        // We do mail and identifier first since they'll most likely return
        // at most exactly one record
        'mail'        => 'EmailAddress',
        'identifier'  => 'Identifier',
        // We could probably try to merge these into a single query...
        'given'       => 'Name',
        'family'      => 'Name'
      ) as $field => $model) {
        if(!empty($attributes[$field])) {
          $RelatedModel = $this->getRecordModel($model);
          
          $args = array();
          if($field == 'given' || $field == 'family') {
            $args['conditions']['LOWER('.$RelatedModel->alias . '.' . $field . ') LIKE'] = strtolower($attributes[$field]) . '%';
          } else {
            $args['conditions'][$RelatedModel->alias . '.' . $field] = $attributes[$field];
          }
          $args['fields'] = array($field, 'sorid');
          $args['contain'] = false;
          
          $matches = $RelatedModel->find('list', $args);
          
          if(!empty($matches)) {
            foreach($matches as $v => $sorid) {
              // This shouldn't throw an error, but there could be an invalid row
              $rec = $this->retrieve($sorid);
              
              if(!empty($rec)) {
                $ret[$sorid] = $rec['orgidentity'];
              }
            }
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Generate the set of searchable attributes for the IdentitySource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the IdentitySource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    return array(
      'sorid'         => _txt('en.identifier.type', null, IdentifierEnum::SORID),
      'given'         => _txt('fd.name.given'),
      'family'        => _txt('fd.name.family'),
      'mail'          => _txt('fd.email_address.mail'),
      'identifier'    => _txt('fd.identifier.identifier')
    );
  }
}
