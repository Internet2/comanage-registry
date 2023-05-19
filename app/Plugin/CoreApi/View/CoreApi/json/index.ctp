<?php
/**
 * COmanage Registry Core API JSON View
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

if(isset($results)) {
  // 'format' => __('Page {:page} of {:pages}, showing {:current} records out of {:count} total, starting on record {:start}, ending on {:end}')
  // Set pagination metadata
  if(empty($results["error"])
     && !empty($results)) {
    $results['currentPage'] = $this->Paginator->counter('{:page}');
    $results['itemsPerPage'] = $this->Paginator->counter('{:current}');
    $results['pageCount'] = $this->Paginator->counter('{:pages}');
    $results['startIndex'] = $this->Paginator->counter('{:start}');
    $results['totalResults'] = $this->Paginator->counter('{:count}');
  }
  print json_encode($results);
}