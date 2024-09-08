<?php
/**
 * COmanage Registry Federation Organization Source Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoHttpClient', 'Lib');
App::uses("OrganizationSourceBackend", "Model");
App::uses('Xml', 'Utility');

class FederationSource extends OrganizationSourceBackend {
  // Required by COmanage Plugins
  public $cmPluginType = "orgsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Request HTTP Servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Association rules from this model to other models
  public $belongsTo = array("OrganizationSource");
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Validation rules for table elements
  public $validate = array(
    'organization_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );

  // Metadata cache, for Sync operations.
  protected $mdcache = array();
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v4.4.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  public function cmPluginMenus() {
    return array();
  }

  /**
   * Obtain all available records in the Organization Source, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    if(!empty($this->mdcache)) {
      return array_keys($this->mdcache);
    }

    throw new DomainException("Cache not populated");
  }
  
  /**
   * Map a SAML language tag to a Registry language enumeration.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  string  $lang  SAML language tag
   * @return string|null    Registry language enumeration, or null
   */

  protected function mapLang($lang) {
    // In general, SAML language tags and Registry language enumerations are the same,
    // but there are some exceptions. SAML tags are actually XML tags, which are defined
    // in RFC 3066. In practice, the metadata uses ISO-639 two letter language codes
    // (matching Registry), with some further using ISO-3166 two-letter country codes
    // (eg "en-US", and which match Registry as a substring).

    // As of the initial implementation, this is the complete set of languages found
    // in InCommon metadata:
    // bg, ca, cs, da, de, el, en, en-us, es, eu, fi, fr, gl, is, it, ja,
    // lt, nl, pl, pt, ro, sk, sr, sv, tr, zh

    global $cm_lang, $cm_texts;
    
    $ret = $lang;

    if(strlen($ret)==5 && $ret[2]=='-') {
      // This is probably ll-CC, so just chop off the -CC

      $ret = substr($ret, 0, 2);
    }

    if(strlen($ret)==2) {
      // We just need to verify the language is defined in the enumeration.
      // (For UI purposes not all languages are defined out of the box.)
      // If not found, we simply return null.

      if(!array_key_exists($ret, $cm_texts[$cm_lang]['en.language'])) {
        // Language not known, blank it out so saves validate
        $ret = null;
      }
    } else {
      // We don't know how to handle this string
      $ret = null;
    }

    return $ret;
  }

  /**
   * Make a request to the configured MDQ server.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  string  $entityId  Entity ID to request, or null for all IdPs
   * @return mixed              Result of request, as returned by HttpClient
   */

  protected function mdqRequest($entityID=null) {
    // Start by pulling our server configuration
    $cfg = $this->getConfig();

    if(empty($cfg['FederationSource']['server_id'])) {
      throw new InvalidArgumentException("Server ID not specified"); // XXX I18n
    }

    $HttpServer = ClassRegistry::init('HttpServer');

    $args = array();
    $args['conditions']['HttpServer.server_id'] = $cfg['FederationSource']['server_id'];
    $args['contain'] = false;

    $srvr = $HttpServer->find('first', $args);

    $Http = new CoHttpClient();

    $Http->setBaseUrl($srvr['HttpServer']['serverurl']);
    $Http->setRequestOptions(array(
      'header' => array(
        'Content-Type'  => 'application/samlmetadata+xml'
      )
    ));

    $url = "/entities/";
    
    if($entityID) {
      $url .= urlencode($entityID);
    } else {
      $url .= "idps/all";
    }

    return $Http->get($url);
  }

  /**
   * Perform any tasks prior to beginning a Sync operation.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  int    $coJobId   CO Job ID
   * @throws RuntimeException if processing should not proceed
   */

  public function preRunChecks($coJobId) {
    // MDQ only supports two calls, one to retrieve a single entity and one to retrieve
    // all entities. We use the latter call here to populate a cache for use with Sync,
    // since it's required to do a Full Sync and will make an Update Sync more efficient
    // for more than a handful of entries.

    $CoJobHistoryRecord = ClassRegistry::init('CoJobHistoryRecord');

    // This $response object uses over 100MB of memory(!), whereas the various
    // XML processors below (using XMLReader, which is more efficient) use only a 
    // trivial amount. Each cache entry (array from resultToOrganization) uses about
    // 20kb, which once 100+MB have been allocated can easily push us over the max
    // limit, so we unset the $response as soon as we can dispose of it.
    // In aggregate, we'll use about 30MB to hold the cache.

    // Measurements based on memory_get_usage() reports and the InCommon MDQ aggregate,
    // which is about 55MB when downloaded.

    $response = $this->mdqRequest();

    if($response->code == 200) {
      $XMLReader = XMLReader::XML($response->body);

      // We're done with this, claim back the memory before we populate the cache
      unset($response);

      $count = 0;

      while($XMLReader->read()) {
        if($XMLReader->nodeType == XMLReader::END_ELEMENT) continue;

        if($XMLReader->name == 'EntityDescriptor') {
          $metadata = Xml::toArray(Xml::build($XMLReader->readOuterXML()));

          if(!empty($metadata['EntityDescriptor']['@entityID'])) {
            $entityID = $metadata['EntityDescriptor']['@entityID'];
            
            $this->mdcache[$entityID]['rec'] = $this->resultToOrganization($metadata['EntityDescriptor']);
            // We json_encode the result for consistency with search()
            $this->mdcache[$entityID]['raw'] = json_encode($this->mdcache[$entityID]['rec']);

            if(!empty($this->mdcache[$entityID])) {
              // print "Display Name: " . $this->mdcache[$entityID]['rec']['Organization']['name'] . "\n";
            }
          }

          $count++;
        }
      }

      $CoJobHistoryRecord->record($coJobId,
                                  null,
                                  _txt('pl.federationsource.count', array($count)),
                                  null,
                                  null,
                                  JobStatusEnum::Notice);
    } else {
      // XXX There might be more information in $response->body, we should bubble that up
      // or log it

      throw new RuntimeException($response->reasonPhrase);
    }
  }

  /**
   * Parse a metadata record into an Organization array.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  Array $metadata Metadata, in array format
   * @return Array           Organization, in array format
   */

  protected function resultToOrganization($metadata) {
    $ret = array();

    // Use the entity ID as the source_key
    $ret['Organization']['source_key'] = $metadata['@entityID'];

    // Note that the SAML Metadata UI spec (§2.4.3) says the following order of precedence SHOULD
    // be used for populating a display name:
    // <mdui:DisplayName>
    // <md:ServiceName> (if applicable, which it isn't here)
    // entityID or a hostname associated with the endpoint of the service
    // Implementations MAY support the use of <md:OrganizationDisplayName>, particularly as a 
    // migration strategy, but this is not recommend this as a general practice.
    // Since we might also have OrganizationNames, we'll use that if DisplayName isn't available.

    // We first look for various attributes in the UI metadata. These can either be single valued
    // or multi, which are typically (but not always) differentiated via language tags.

    foreach(array(
      'name' => array('field' => 'mdui:DisplayName', 'maxlen' => 128),
      'description' => array('field' => 'mdui:Description', 'maxlen' => 128),
      'logo_url' => array('field' => 'mdui:Logo', 'maxlen' => 256)
    ) as $field => $src) {
      if(!empty($metadata['IDPSSODescriptor']['Extensions']['mdui:UIInfo'][ $src['field'] ]['@'])) {
        // If there is just a single value, use that

        $ret['Organization'][$field] = substr($metadata['IDPSSODescriptor']['Extensions']['mdui:UIInfo'][ $src['field'] ]['@'], 0, $src['maxlen']);
      } elseif(!empty($metadata['IDPSSODescriptor']['Extensions']['mdui:UIInfo'][ $src['field'] ])) {
        // We have multiple values, most likely flagged by language. We don't really have a good
        // pattern to follow here for a single valued attribute, so we'll start by looking for
        // an English entry, and if we don't find that we'll use the first one.

        foreach($metadata['IDPSSODescriptor']['Extensions']['mdui:UIInfo'][ $src['field'] ] as $xv) {
          if(!empty($xv['@xml:lang']) && $xv['@xml:lang'] == 'en') {
            $ret['Organization'][$field] = substr($xv['@'], 0, $src['maxlen']);
            break;
          }
        }

        if(empty($ret['Organization'][$field])) {
          // No English entry found, use the first one

          $ret['Organization'][$field] = substr($metadata['IDPSSODescriptor']['Extensions']['mdui:UIInfo'][ $src['field'] ][0]['@'], 0, $src['maxlen']);
        }
      }

      if($field == 'logo_url' && !empty($ret['Organization'][$field])) {
        // Some IdP put actual data in (as permitted by the spec), which for now we don't support

        if(strncmp($ret['Organization'][$field], "data:", 4)==0) {
          unset($ret['Organization'][$field]);
        }
      }
    }

    if(!empty($metadata['IDPSSODescriptor']['Extensions']['shibmd:Scope']['@'])) {
      // For now we only support single value scopes since it's not clear what to do with
      // multiple values
      $ret['Organization']['saml_scope'] = $metadata['IDPSSODescriptor']['Extensions']['shibmd:Scope']['@'];

      // Try to guess an organization type from the scope. This isn't going to be perfect.

      if(preg_match('/(\.edu$|\.edu\.|\.ac\.)/', $ret['Organization']['saml_scope'])) {
        $ret['Organization']['type'] = OrganizationEnum::Academic; 
      } elseif(preg_match('/(\.com^|\.com\.|\.co\.)/', $ret['Organization']['saml_scope'])) {
        $ret['Organization']['type'] = OrganizationEnum::Commercial;
      } elseif(preg_match('/(\.gov^|\.gov\.)/', $ret['Organization']['saml_scope'])) {
        $ret['Organization']['type'] = OrganizationEnum::Government;
      }
      // else we don't know what to do, so we don't do anything
    }


    $ret['Identifier'][] = array(
      'identifier'  => $metadata['@entityID'],
      'type'        => IdentifierEnum::EntityID,
      'status'      => SuspendableStatusEnum::Active
    );

    // The SAML Metadata UI spec (§2.4.3) says use OrganizationDisplayName for display purposes,
    // but also that its use isn't recommended. For now we'll use OrganizationName, which
    // appears to be the more "official", though in most cases they're the same. (An example
    // where they differ is Penn State.)

    if(!empty($metadata['Organization']['OrganizationName']['@'])) {
      // There's only one OrganizatonName, push it one level down for compatibility with
      // processing multiple URLs.

      $metadata['Organization']['OrganizationName'] = array($metadata['Organization']['OrganizationName']);
    }

    if(is_array($metadata['Organization']['OrganizationName'])) {
      // Multiple values, probably for different languages

      foreach($metadata['Organization']['OrganizationName'] as $name) {
        $ret['Identifier'][] = array(
          'identifier'  => $name['@'],
          'type'        => IdentifierEnum::Name,
          'status'      => SuspendableStatusEnum::Active,
          'language'    => $this->mapLang(isset($name['@xml:lang']) ? $name['@xml:lang'] : "")
        );

        // If we didn't get a name from the UI metadata, use this one (this will effectively
        // pick the first in the array)
        if(empty($ret['Organization']['name'])) {
          $ret['Organization']['name'] = $name['@'];
        }
      }
    }

    if(!empty($metadata['Organization']['OrganizationURL']['@'])) {
      // There's only one URL, push it one level down for compatibility with
      // processing multiple URLs.

      $metadata['Organization']['OrganizationURL'] = array($metadata['Organization']['OrganizationURL']);
    }
    
    if(is_array($metadata['Organization']['OrganizationURL'])) {
      foreach($metadata['Organization']['OrganizationURL'] as $url) {
        $ret['Url'][] = array(
          'url'         => $url['@'],
          'type'        => UrlEnum::Official,
          'language'    => $this->mapLang(isset($url['@xml:lang']) ? $url['@xml:lang'] : "")
        );
      }
    }

    // Contact People

    if(!empty($metadata['ContactPerson']['@contactType'])) {
      // There's only one Contact, push it one level down for compatibility with
      // processing multiple Contacts.

      $metadata['ContactPerson'] = array($metadata['ContactPerson']);
    }

    if(!empty($metadata['ContactPerson'])) {
      foreach($metadata['ContactPerson'] as $cp) {
        // Convert a SAML ContactPerson to a Registry Contact. EmailAddress and TelephoneNumber
        // are technically multi-valued, but few entities have multiple values, so for now we
        // just use the first one we see.
        $t = array('EmailAddress' => null, 'TelephoneNumber' => null);

        foreach(array_keys($t) as $k) {
          if(!empty($cp[$k])) {
            if(is_array($cp[$k])) {
              $t[$k] = $cp[$k][0];
            } else {
              $t[$k] = $cp[$k];
            }
          }
        }

        // Email Addresses are specified as URIs in the SAML spec
        if(!empty($t['EmailAddress'])) {
          $parsed = parse_url($t['EmailAddress']);

          if(!empty($parsed['scheme']) && $parsed['scheme'] == 'mailto') {
            $t['EmailAddress'] = $parsed['path'];
          } else {
            // While EmailAddress is supposed to be a URI, not everyone correctly populates it
            $t['EmailAddress'] = $cp['EmailAddress'];
          }
        }

        $ret['Contact'][] = array(
          'given'   => !empty($cp['GivenName']) ? $cp['GivenName'] : null,
          'family'  => !empty($cp['SurName']) ? $cp['SurName'] : null,
          'company' => !empty($cp['Company']) ? $cp['Company'] : null,
          'number'  => $t['TelephoneNumber'],
          'mail'    => $t['EmailAddress'],
          'type'    => !empty($cp['@contactType']) ? $cp['@contactType'] : null,
        );
      };
    }

    // If we don't have a name at this point, use the entityID
    if(empty($ret['Organization']['name'])) {
      $ret['Organization']['name'] = $metadata['@entityID'];
    }

    return $ret;
  }

  /**
   * Retrieve a record from the Organization Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   * @param  String $key Record key to retrieve
   * @return Array Raw record and Array in Organization format
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   * @throws RuntimeException on backend specific errors
   */

  public function retrieve($key) {
    // Do we already have the record in the cache?
    if(!empty($this->mdcache[$key])) {
      return $this->mdcache[$key];
    }

    // Otherwise this is basically just search, which will throw InvalidArgumentException
    // on not found

    $rec = $this->search(array('entityID' => $key));

    return $rec[$key];
  }

  /**
   * Perform a search against the Organization Source. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an Organization, including
   * related models.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */

  public function search($attributes) {
    $ret = array();

    // We need to decode the URL because mdqRequest will re-encode it in order
    // to work with the base64 manipulated key strings.
    $response = $this->mdqRequest(urldecode($attributes['entityID']));

    if($response->code == 200) {
      $metadata = Xml::toArray(Xml::build($response->body));

      if(empty($metadata['EntityDescriptor']['@entityID'])) {
        throw new RuntimeException('er.federationsource.notfound.entityid');
      }

      $entityID = $metadata['EntityDescriptor']['@entityID'];

      $ret[$entityID]['rec'] = $this->resultToOrganization($metadata['EntityDescriptor']);

      // Because of differences between how the XML is processed here vs when cached via
      // preRunChecks, we can't use the raw XML here. Instead, we json_encode the
      // post-processed record.

      // Some records (eg https://idp.gbu.edu.in/idp/shibboleth) have invalid UTF-8 characters
      // in them. We could use JSON_INVALID_UTF8_IGNORE here, but then a save will fail
      // somewhere else, possible causing the job to abort.
      $ret[$entityID]['raw'] = json_encode($ret[$entityID]['rec']);
    } elseif($response->code == 404) {
      // Invalid entity ID

      throw new InvalidArgumentException($response->reasonPhrase);
    } else {
      // XXX There might be more information in $response->body, we should bubble that up
      // or log it

      throw new RuntimeException($response->reasonPhrase);
    }
    
    return $ret;
  }

  /**
   * Generate the set of searchable attributes for the OrganizationSource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the OrganizationSource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v4.4.0
   * @return Array As specified
   */

  public function searchableAttributes() {
    // MDQ doesn't have a search interface, the only thing we can do is retrieve by Entity ID.

    $attrs = array(
      'entityID'        => _txt('pl.federationsource.entityid')
    );

    return $attrs;
  }
}
