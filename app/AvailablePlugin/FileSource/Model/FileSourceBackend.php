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

class FileSourceBackend extends OrgIdentitySourceBackend {
  public $name = "FileSourceBackend";
  
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
      $archive1 = $this->pluginCfg['archivedir'] . DS . $basename . ".1";
      $archive2 = $this->pluginCfg['archivedir'] . DS . $basename . ".2";
      
      // ok to archive?
      $ok = true;
      
      // We could either read the files simultaneously in order (lower memory requirement),
      // or read one and hash it (can read records out of sequence). For now we'll take
      // the second approach.
      
      if(is_readable($archive1)) {
        // Start by creating a set of previously known records.
        $knownRecords = array();
        
        $handle = fopen($archive1, "r");
        
        if(!$handle) {
          throw new RuntimeException('er.filesource.read', array($archive1));
        }
        
        // We don't use fgetcsv here because we don't want to parse the full line,
        // we just need the SORID as a key.
        while(($data = fgets($handle)) !== false) {
          $d = explode(',', $data, 2);
          
          $knownRecords[ $d[0] ] = $d[1];
        }
        
        fclose($handle);
        
        // Now read the new file and look for changes.
        $handle = fopen($infile, "r");
        
        if(!$handle) {
          throw new RuntimeException('er.filesource.read', array($infile));
        }
        
        while(($data = fgets($handle)) !== false) {
          $d = explode(',', $data, 2);
          
          if(array_key_exists($d[0], $knownRecords)) {
            if($d[1] != $knownRecords[ $d[0] ]) {
              // This record changed, push the SORID onto the change list
              $ret[] = $d[0];
            }
            
            // Unset the key so we can see which records were deleted.
            unset($knownRecords[ $d[0] ]);
          } else {
            // This is a new record (ie: in $infile, not in $archive1),
            // so we ignore it.
          }
        }
        
        fclose($handle);
        
        // Finally, any remaining keys in $knownRecords are delete operations.
        if(!empty($knownRecords)) {
          $ret = array_merge($ret, array_keys($knownRecords));
        }
        
        // If no changes, don't archive.
        if(empty($ret)) {
          $ok = false;
        }
      } else {
        // If there is no archive file, return nothing, since there are no changes.
        // We do however want to create an archive file for our next run.
      }
      
      // We copy the current file to the archive here, even though we haven't processed it yet
      // since we don't have a good way to otherwise know when we're done processing. Since
      // this could cause problems (ie: if the job is canceled or killed), we'll keep *two*
      // backup copies.
      
      if($ok && is_readable($archive1)) {
        $ok = copy($archive1, $archive2);
        
        if(!$ok) {
          throw new RuntimeException(_txt('er.filesource.copy', array($archive1, $archive2)));
        }
      }
      
      if($ok && is_readable($infile)) {
        $ok = copy($infile, $archive1);
        
        if(!$ok) {
          throw new RuntimeException(_txt('er.filesource.copy', array($infile, $archive1)));
        }
      }
      
      return $ret;
    } else {
      // changelist not supported if no archive dir
      
      return false;
    }
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
    return array(
      '16' => _txt('fd.title'),
      '17' => _txt('fd.o')
    );
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
    // We basically just return the decoded raw record, which will be a superset of groupable attributes.
    // No real need to filter that down.
    
    $ret = array();
    
    $attrs = json_decode($raw, true);
    
    foreach($attrs as $k => $v) {
      $ret[$k][] = array('value' => $v);
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $orgdata = array();
    $orgdata['OrgIdentity'] = array();
    
    if(!empty($result[22])) {
      // We don't currently sanity check the value passed, if it's bad the save will fail
      $orgdata['OrgIdentity']['affiliation'] = $result[22];
    } else {
      // Until we have some rules, everyone is a member
      $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    }
    
    if(!empty($result[17]))
      $orgdata['OrgIdentity']['o'] = $result[17];
    if(!empty($result[16]))
      $orgdata['OrgIdentity']['title'] = $result[16];
    
    $orgdata['Name'] = array();
    
    if(!empty($result[3]))
      $orgdata['Name'][0]['given'] = $result[3];
    if(!empty($result[5]))
      $orgdata['Name'][0]['family'] = $result[5];
    $orgdata['Name'][0]['primary_name'] = true;
    $orgdata['Name'][0]['type'] = NameEnum::Official;
    
    $orgdata['EmailAddress'] = array();
    
    if(!empty($result[12])) {
      $orgdata['EmailAddress'][0]['mail'] = $result[12];
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = true;
    }
    
    $orgdata['Address'] = array();
    
    if(!empty($result[6])) {
      $orgdata['Address'][0]['street'] = $result[6];
      if(!empty($result[7]))
        $orgdata['Address'][0]['locality'] = $result[7];
      if(!empty($result[9]))
        $orgdata['Address'][0]['state'] = $result[9];
      if(!empty($result[10]))
        $orgdata['Address'][0]['postal_code'] = $result[10];
      if(!empty($result[11]))
        $orgdata['Address'][0]['country'] = $result[11];
      $orgdata['Address'][0]['type'] = ContactEnum::Home;
    }
    
    $orgdata['TelephoneNumber'] = array();
    
    if(!empty($result[13])) {
      $orgdata['TelephoneNumber'][0]['number'] = $result[13];
      
      if(!empty($result[14])) {
        $orgdata['TelephoneNumber'][0]['country_code'] = $result[14];
      }
      $orgdata['TelephoneNumber'][0]['type'] = ContactEnum::Office;
    }
    
    // Collect some identifiers
    /* As of Registry v3.3.0, we no longer need to explicitly do this
    if(!empty($result[0])) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result[0],
        'login'      => false,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::SORID
      );
    }*/
    
    if(!empty($result[15])) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result[15],
        'login'      => false,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::National
      );
    }
    
    if(!empty($result[18])) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result[18],
        'login'      => false,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::Reference
      );
    }
    
    if(!empty($result[23])) {
      // This field allows a semi-colon separated lists of identifiers of the form
      // identifiertype[+login]:identifier
      // where identifiertype is a non-extended type, since Org Identities don't
      // currently support extended types (CO-530).
      
      $ids = explode(';', $result[23]);
      
      foreach($ids as $id) {
        $i = explode(':', $id, 2);
        
        $login = false;
        $idtype = $i[0];
        
        if(strlen($i[0]) > 6) {
          $p = strpos($i[0], "+login", -6);
          
          if($p) {
            $login = true;
            $idtype = substr($i[0], 0, $p);
          }
        }
        
        $orgdata['Identifier'][] = array(
          'identifier' => $i[1],
          'login'      => $login,
          'status'     => StatusEnum::Active,
          'type'       => $idtype
        );
      }
    }
    
    if(!empty($result[19]))
      $orgdata['OrgIdentity']['valid_from'] = strftime("%F %T", strtotime($result[19]));
    
    if(!empty($result[20]))
      $orgdata['OrgIdentity']['valid_through'] = strftime("%F %T", strtotime($result[20]));
    
    if(!empty($result[21])) {
      $orgdata['Url'][] = array(
        'url'        => $result[21],
        'type'       => UrlEnum::Personal
      );
    }
    
    if(!empty($result[24])) {
      $orgdata['OrgIdentity']['date_of_birth'] = $result[24];
    }
    
    if(!empty($result[25])) {
      $ahas = json_decode($result[25], true);
      
      $orgdata['AdHocAttribute'] = array();
      
      foreach($ahas as $tag => $value) {
        $orgdata['AdHocAttribute'][] = array(
          'tag' => $tag,
          'value' => $value
        );
      }
    }
    
    return $orgdata;
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
    return array(
      // Currently these keys correlate to fakenamegenerator header labels, but there's
      // no particular reason to keep that model
      'SORID'            => 'SORID', // XXX I18n if we keep this
      'GivenName'        => _txt('fd.name.given'),
      'Surname'          => _txt('fd.name.family'),
      // We need to use 'mail' because OrgIdentitySource::searchAllByEmail uses it
      // we may want to mandate standard attribute names
      'mail'     => _txt('fd.email_address.mail')
    );
  }

  /**
   * Search a CSV file.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes()), or null to obtain a list of all SORIDs
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function searchFile($attributes=null) {
    $ret = array();
    
    $handle = fopen($this->pluginCfg['filepath'], "r");
    
    if(!$handle) {
      throw new RuntimeException(_txt('er.filesource.read', array($this->pluginCfg['filepath'])));
    }
    
    while(($data = fgetcsv($handle)) !== false) {
      // For each row, see if any provided search key matches a specified field. In our current
      // test format, we check
      //  givenname = [3], familyname = [5], email = [12]
      
      if(!$attributes) {
        // Just store the SORID (row key)
        $ret[] = $data[0];
      } else {
        if((!empty($attributes['SORID']) && ($data[0]==$attributes['SORID']))
           ||
           (!empty($attributes['GivenName']) && stristr($data[3], $attributes['GivenName']) !== false)
           ||
           (!empty($attributes['Surname']) && stristr($data[5], $attributes['Surname']) !== false)
           ||
           (!empty($attributes['mail']) && stristr($data[12], $attributes['mail']) !== false)) {
          $ret[] = $data;
        }
      }
    }
    
    fclose($handle);
    
    return $ret;
  }
}
