<?php
/**
 * COmanage Registry Visual Compliance Vetter Model
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

App::uses('CoHttpClient', 'Lib');

class VisualComplianceVetter extends AppModel {
  // Required by COmanage Plugins
// XXX document plugin type
  public $cmPluginType = "vetter";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "VettingStep",
    "Server"
  );
  
   // Request Http servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Validation rules for table elements
  public $validate = array(
    'vetting_step_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'unfreeze' => 'CO'
      )
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Perform vetting for the requested CO Person ID.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int   $vettingStepId Vetting Step ID
   * @param  int   $coPersonId    CO Person ID
   * @param  int   $coPetitionId  CO Petition ID
   * @return array                Array with three keys: "result" (VettingStatusEnum), "comment" (string), "raw" (string)
   * @throws InvalidArgumentException
   */
  
  public function vet($vettingStepId, $coPersonId, $coPetitionId=null) {
    // Pull our configuration
    $args = array();
    $args['conditions']['VisualComplianceVetter.vetting_step_id'] = $vettingStepId;
    $args['contain'] = array(
      'Server' => 'HttpServer'
    );
    
    $vcvConfig = $this->find('first', $args);

    if(empty($vcvConfig)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.visual_compliance_vetters.1'), $vettingStepId)));
    }
    
    // Pull the CO Person data we want to look at
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    // Note we explicitly do NOT filter on CoPerson status
    $args['contain'] = array(
      'CoPersonRole',
      'PrimaryName',
      'IdentityDocument'
    );
    
    $coPerson = $this->Server->Co->CoPerson->find('first', $args);
    
    if(empty($coPerson)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $coPersonId)));
    }
    
    $org = array();
    
    // As a first implementation, we require the use of the Organization Dictionary.
    // Walk through CoPersonRoles until we find one with a value, use that Org and Address
    foreach($coPerson['CoPersonRole'] as $copr) {
      if(!empty($copr['o']) && (int)$copr['o'] > 0) {
        $args = array();
        $args['conditions']['Organization.id'] = $coPerson['CoPersonRole'][0]['o'];
        $args['contain'] = array('Address');
        
        $org = $this->Server->Co->Organization->find('first', $args);
        
        if(!empty($org)) {
          break;
        }
      }
    }
    
    // The fields we'll send to VC. We don't enforce any particular formatting
    // on (eg) $state or $country, for now we expect the deployer to format
    // the data correctly.
    
    // For our first implementation, we'll make two searches (in the same request):
    // (1) $name + $company (organization) + $city (organization) + $state (organization)
    // (2) $name + $country (from passport identity document)
    // Based on the way VC appears to work, we can always send both requests
    // even if we don't have the full set of attributes for them (but this might
    // not be true under all circumstances?).
    // Note this logic is not generalized, but meets initial requirements.
    // It will probably make sense to make this configurable somehow in the future.
    
    $name = "";
    $company = "";
    $city = "";
    $state = "";
    $country = "";
    
    if(!empty($coPerson['PrimaryName'])) {
      $name = generateCn($coPerson['PrimaryName']);
    }
    
    if(!empty($org['Organization']['name'])) {
      $company = $org['Organization']['name'];
    }
    
    // XXX For now we just look at the first address we have
    if(!empty($org['Address'][0]['locality'])) {
      $city = $org['Address'][0]['locality'];
    }
    
    if(!empty($org['Address'][0]['state'])) {
      $state = $org['Address'][0]['state'];
    }
    
    if(!empty($coPerson['IdentityDocument'])) {
      // Pull the first issuing_authority we find
      foreach($coPerson['IdentityDocument'] as $doc) {
        if(!empty($doc['issuing_authority'])) {
          $country = $doc['issuing_authority'];
          break;
        }
      }
    }
    
    $Http = new CoHttpClient();

    $Http->setBaseUrl($vcvConfig['Server']['HttpServer']['serverurl']);
    $Http->setRequestOptions(array(
      'header' => array(
        'Content-Type' => 'text/json;charset=UTF-8',
        'Accept'       => 'text/json;charset=UTF-8'
      )
    ));
    
    $vettingRequest = array(
      "__type"          => "searchrequest:http://eim.visualcompliance.com/RPSService/2016/11",
      "sguid"           => "",
      "stransid"        => "",
      "ssecno"          => $vcvConfig['Server']['HttpServer']['username'],
      "spassword"       => $vcvConfig['Server']['HttpServer']['password'],
      "smodes"          => "",
      "srpsgroupbypass" => "",
      // By sending multiple searches the overall result appears to be the
      // "worst" possible result.
      "searches"        => array(
        array(
          "__type"      => "search:http://eim.visualcompliance.com/RPSService/2016/11",
          "soptionalid" => "", 
          "sname"       => $name,
          "scompany"    => $company,
          "saddress1"   => "",
          "saddress2"   => "",
          "saddress3"   => "",
          "scity"       => $city,
          "sstate"      => $state,
          "szip"        => "",
          "scountry"    => "",
          "selective1"  => "",
          "selective2"  => "",
          "selective3"  => "",
          "selective4"  => "",
          "selective5"  => "",
          "selective6"  => "",
          "selective7"  => "",
          "selective8"  => ""
        ),
        array(
          "__type"      => "search:http://eim.visualcompliance.com/RPSService/2016/11",
          "soptionalid" => "", 
          "sname"       => $name,
          "scompany"    => "",
          "saddress1"   => "",
          "saddress2"   => "",
          "saddress3"   => "",
          "scity"       => "",
          "sstate"      => "",
          "szip"        => "",
          "scountry"    => $country,
          "selective1"  => "",
          "selective2"  => "",
          "selective3"  => "",
          "selective4"  => "",
          "selective5"  => "",
          "selective6"  => "",
          "selective7"  => "",
          "selective8"  => ""
        )
      )
    );
    
    $response = $Http->post("", json_encode($vettingRequest));
    
    if($response->code != 200) {
      // Structural error of some form, eg Bad Request
      
      return array(
        'comment' => $response->code . " " . $response->reasonPhrase,
        'result'  => VettingStatusEnum::Error,
        'raw'     => !empty($response->body) ? $response->body : ""
      );
    }
    
    // Decode the json response
    $body = json_decode($response->body);
    
    // Since this API isn't really RESTful it may return 200 OK but still have an error
    
    if(!empty($body->serrorstring)) {
      return array(
        'comment' => $body->errorstring,
        'result'  => VettingStatusEnum::Error,
        'raw'     => $response->body
      );
    }
    
    // Finally, a useful result. We can get a fairly complicated response back,
    // depending on which compliance rules failed. To simplify things, we'll
    // look at $body->smaxalert, which should be the highest possible alert.
    // Note this could cover multiple requests if we sent multiple requests, but
    // we only send one request at a time, so the max alert must be for the
    // request we just sent.
    
    // We first check stransstatus, which will be "Passed" if there are no alerts.
    
    if($body->stransstatus == 'Passed') {
      // The subject passed vetting
      
      return array(
        // There's not really anything in the response worth stuffing into the comment
        'comment' => _txt('en.status.vet', null, VettingStatusEnum::Passed),
        'result'  => VettingStatusEnum::Passed,
        'raw'     => $response->body
      );
    }
    
    // Individual rule failures can be found in $body->searches->[*]->results->[*]
    // which includes a fairly length narrative in ->notes. We'll instead use
    // ->list as a shorter indicator of what failed.
    
    $code = VettingStatusEnum::Passed;
    
    switch($body->smaxalert) {
      case 'TR':  // Triple Red
      case 'DR':  // Double Red
      case '_R':  // Red
        $code = VettingStatusEnum::Failed;
        break;
      case '_Y';  // Yellow
        $code = VettingStatusEnum::PendingManual;
        break;
      // This will be empty ("") on pass, but we should have caught that above
      // when we checked stransstatus...
    }
    
    $comments = array();
    
    foreach($body->searches as $s) {
      foreach($s->results as $r) {
        if(!empty($r->list)) {
          // List here is the Visual Compliance alert list, it's not a data structure
          $comments[] = $r->list;
        }
      }
    }
    
    return array(
      'comment' => !empty($comments) ? implode(';', $comments) : _txt('en.status.vet', null, $code),
      'result'  => $code,
      'raw'     => $response->body
    );
  }
}