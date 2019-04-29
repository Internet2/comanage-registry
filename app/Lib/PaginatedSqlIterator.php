<?php
/**
 * COmanage Registry Paginated SQL Iterator
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class PaginatedSqlIterator implements Iterator {
  // PaginatedSqlIterator implements Keyset Pagination on a Cake model.
  // This is suitable for iterating large datasets sequentially, but not so
  // good for random pagination (eg: via the UI) of large datasets.
  
  // Number of results to pull at one time, for now this is not configurable
  private $pageSize = 100;
  
  // For now we only iterate over id, which we know is indexed and is an integer
  private $keyField = "id";
  
  // The model we are querying
  private $model = null;
  
  // Conditions for querying
  private $conditions = null;
  
  // Fields to return (or null)
  private $fields = null;
  
  // Models to contain (or null)
  private $contain = null;
  
  // Record count -- used for work estimates only, *not* for pagination
  private $count = null;
  
  // Most recent page of results
  private $results = null;
  
  // Current index (*not* $id) into $results
  private $position = 0;
  
  // The highest ID we've seen so far
  private $maxid = 0;
  
  public function __construct($model, $conditions=null, $fields=null, $contain=null) {
    $this->model = $model;
    $this->conditions = $conditions;
    $this->fields = $fields;
    $this->contain = $contain;
    
    $this->position = 0;
  }
  
  /**
   * Obtain the current element of the iteration.
   *
   * @since  COmanage Registry v3.3.0
   * @return mixed Element at the current position
   */
  
  public function current() {
    return $this->results[$this->position];
  }
  
  /**
   * Obtain the current count of records.
   *
   * @since  COmanage Registry v3.3.0
   * @param  boolean $refresh Refresh the count rather than returning the cached count
   * @return integer          Record count
   */
  
  public function count($refresh=false) {
    if($this->count === null || $refresh) {
      $this->loadCount();
    }
    
    return $this->initialCount;
  }
  
  /**
   * Obtain the current position of the iteration.
   *
   * @since  COmanage Registry v3.3.0
   * @return integer The current position
   */
  
  public function key() {
    return $this->position;
  }
  
  /**
   * Obtain the count of records.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  protected function loadCount() {
    $args = array();
    if($this->conditions) {
      $args['conditions'] = $this->conditions;
    }

    $this->initialCount = $this->model->find('count', $args);
  }
  
  /**
   * Obtain the next page of results (releasing the current page).
   *
   * @since  COmanage Registry v3.3.0
   */
  
  protected function loadPage() {
    unset($this->results);
    $this->results = null;
    
    $this->position = 0;
    
    $args = array();
    if($this->conditions) {
      $args['conditions'] = $this->conditions;
    }
    $args['conditions'][$this->model->alias.'.id >'] = $this->maxid;
    if($this->fields) {
      $args['fields'] = $this->fields;
    }
    $args['order'] = array($this->model->alias.'.id' => 'asc');
    $args['limit'] = $this->pageSize;
    $args['contain'] = false;

    $this->results = $this->model->find('all', $args);
    
    if(!empty($this->results)) {
      // Since we've ORDERed BY id, we know the maximum id value is in the last
      // slot of the results array.
      $this->maxid = $this->results[count($this->results) - 1][$this->model->alias]['id'];
    }
    // else no remaining rows. valid() will return false.
  }
  
  /**
   * Move to the next record in the iteration.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function next() {
    $this->position++;
    
    if($this->position >= count($this->results)) {
      // We've reached the end of the current page, retrieve the next page of results
      $this->loadPage();
    }
  }
  
  /**
   * Rewind to the first record in the iteration. (This is called by PHP on initialization.)
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function rewind() {
    $this->maxid = 0;
    
    $this->loadPage();
  }
  
  /**
   * Determine if the current position is valid.
   *
   * @since  COmanage Registry v3.3.0
   * @return boolean True if the current position is valid, false otherwise
   */
  
  public function valid() {
    return !empty($this->results[$this->position]);
  }
}
