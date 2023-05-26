<?php
/**
 * COmanage Registry Core API Petition Model
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoreApi", "CoreApi.Model");

class CoreApiPetition extends CoreApi {
  // Define class name for cake
  public $name = "CoreApiPetition";

  public $mapper = "CoPetition";

  public $associated_models = array();

  protected $allowed_query_params = array(
    'limit' => array('integer' => array('range' => array(1, 1001))),
    'direction' => array('string' => array('inList' => array(array('asc' , 'desc')))),
    'page'  => array('integer' => array('comparison' => array('>=', 1))),
    'id' => array('string' => array('custom' => array('/^[0-9]{1,}$/'))),
    'couid' => array('string' => array('custom' => array('/^[0-9]{1,}$/'))),
    'enrollmentflowid' => array('string' => array('custom' => array('/^[0-9]{1,}$/'))),
    'status' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
    'enrollee' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
    'petitioner' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
    'sponsor' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
    'approver' => array('string' => array('custom' => array('/^[A-Za-z]{1,10}$/'))),
  );

  public $index_contains = array(
    'ApproverCoPerson' => array(
      'class' => 'CoPerson',
      'ApproverPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          // Linkable behavior doesn't seem to be able to handle multiple joins
          // against the same table, so we manually specify the join condition for
          // each name. We then have to explicitly filter on primary name so as
          // not to produce multiple rows in the join for alternate names the
          // CO Person might have.
          'exactly' => 'ApproverPrimaryName.co_person_id = ApproverCoPerson.id AND ApproverPrimaryName.primary_name = true'
        )
      )
    ),
    'CoEnrollmentFlow',
    'Cou',
    'EnrolleeCoPerson' => array(
      'class' => 'CoPerson',
      'EnrolleePrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'EnrolleePrimaryName.co_person_id = EnrolleeCoPerson.id AND EnrolleePrimaryName.primary_name = true')
      )
    ),
    'PetitionerCoPerson' => array(
      'class' => 'CoPerson',
      'PetitionerPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'PetitionerPrimaryName.co_person_id = PetitionerCoPerson.id AND PetitionerPrimaryName.primary_name = true')
      )
    ),
    'SponsorCoPerson' => array(
      'class' => 'CoPerson',
      'SponsorPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'SponsorPrimaryName.co_person_id = SponsorCoPerson.id AND SponsorPrimaryName.primary_name = true')
      )
    ),
    'CoInvite',
    'VettingRequest'
  );

  public $related_models = array();

  public $view_contains = array(
    'ApproverCoPerson' => array(
      'class' => 'CoPerson',
      'ApproverPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          // Linkable behavior doesn't seem to be able to handle multiple joins
          // against the same table, so we manually specify the join condition for
          // each name. We then have to explicitly filter on primary name so as
          // not to produce multiple rows in the join for alternate names the
          // CO Person might have.
          'exactly' => 'ApproverPrimaryName.co_person_id = ApproverCoPerson.id AND ApproverPrimaryName.primary_name = true'
        )
      )
    ),
    'CoEnrollmentFlow',
    'Cou',
    'EnrolleeCoPerson' => array(
      'EnrolleePrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'EnrolleePrimaryName.co_person_id = EnrolleeCoPerson.id AND EnrolleePrimaryName.primary_name = true')
      )
    ),
    'PetitionerCoPerson' => array(
      'class' => 'CoPerson',
      'PetitionerPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'PetitionerPrimaryName.co_person_id = PetitionerCoPerson.id AND PetitionerPrimaryName.primary_name = true')
      )
    ),
    'SponsorCoPerson' => array(
      'class' => 'CoPerson',
      'SponsorPrimaryName' => array(
        'class' => 'Name',
        'conditions' => array(
          'exactly' => 'SponsorPrimaryName.co_person_id = SponsorCoPerson.id AND SponsorPrimaryName.primary_name = true')
      )
    ),
    'CoPetitionHistoryRecord'
  );

  /**
   * Query Parameters need now transmogrification here. Return the dataset as is.
   *
   * @since  COmanage Registry v4.1.0
   * @param array   $queryParams  List of query parameters
   * @return array
   */
  public function parseQueryParams($queryParams) {
    return $queryParams;
  }

  /**
   * Pull a CoPetition record, including associated models.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId           CO ID
   * @param  string  $identifier     Identifier to query
   * @param  string  $identifierType Identifier type
   * @return array                   Array of CO Person data
   * @throws InvalidArgumentException
   */

  protected function pullRecord($coId, $identifier, $identifierType) {
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoPetition.co_id'] = $coId;
    $args['joins'][0]['table'] = 'identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_department_id=CoPetition.id';
    // While we're here pull the data we need
    $args['contain'] = $this->view_contains;

    // find('first') won't result in two records, though if identifier is not
    // unique it's non-deterministic as to which record we'll retrieve.

    $org = $this->Co->CoPetition->find('first', $args);

    if(empty($org)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifiers.1'), filter_var($identifier,FILTER_SANITIZE_SPECIAL_CHARS))));
    }
    return $org;
  }
}