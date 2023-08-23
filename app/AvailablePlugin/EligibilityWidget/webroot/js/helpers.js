/**
 * COmanage Registry Eligibility Widget Helpers
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
 * @package       registry
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

/**
 * @param couId           {integer} COU Id record number
 * @param couActiveList   {array}   List of COU Ids currently the user is a member of
 */
const isCouActive = (couId, couActiveList) => {
  return couActiveList.includes(couId)
}

/**
 * @param allCousList   {array}   List of all COUs
 * @param items         {object}  MVPA object
 */
const constructItems = (allCousList, items) => {
  // The backend is responsible to filter the CoPersonRole memberships to OrgIdentitySource
  // related ones or not
  for (var idx in items) {
    items[idx].Item = allCousList[items[idx].cou_id]
  }

  return items
}


/**
 * @param oisRegistrationId        {integer} OisRegistration Id
 * @param couActiveList   {array}   List of COU Ids currently the user is a member of
 */
const isOISActive = (oisRegistrationId, couActiveList) => {
  return couActiveList.includes(oisRegistrationId)
}

export {
  isCouActive,
  isOISActive,
  constructItems
}