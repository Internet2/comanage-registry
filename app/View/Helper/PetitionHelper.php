<?php
/**
 * COmanage Registry Petition Helper
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('AppHelper', 'View/Helper');

class PetitionHelper extends AppHelper {
  /**
   * Get Record from the Id
   *
   * @param integer $id
   * @return int|null
   *
   * @since  COmanage Registry        v4.4.0
   */
  public function getAttributeRecord($id) {
    $args['conditions']['CoEnrollmentAttribute.id'] = $id;
    $args['contain'] = false;

    $CoEnrollmentAttribute = ClassRegistry::init('CoEnrollmentAttribute');
    return $CoEnrollmentAttribute->find('first', $args);
  }

  /**
   * Check if recA and recB according to assumptions
   *
   *
   *
   * @param array $recA
   * @param array $recB
   *
   * @return boolean
   */
  public function attributeRecordsMatching($recA, $recB) {
    // Fields that have to match
    $fieldToSkipMatching = array(
      'id',
      'ordr',
      'revision',
      'co_enrollment_attribute_id',
      'actor_identifier',
      'created',
      'modified'
    );

    foreach ($recA as $key => $value) {
      if($recB[$key] != $value
         && !in_array($key, $fieldToSkipMatching)) {
        return false;
      }
    }

    return true;
  }

}