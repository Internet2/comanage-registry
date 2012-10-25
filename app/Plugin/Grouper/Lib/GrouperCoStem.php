<?php
/**
 * COmanage Registry Grouper CO Stem
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('GrouperRestClient', 'Grouper.Lib');
App::uses('GrouperRestClientException', 'Grouper.Lib');
App::uses('GrouperCoStemException', 'Grouper.Lib');

/**
 * An instance represents a translation between a COmanage CO
 * and a Grouper stem under which will be stored group information
 * for groups in the CO.
 *
 * @since COmanage Registry 0.7
 */
class GrouperCoStem {
  // These are the details about the CO.
  public $coId = null;
  public $coName = null;
  public $coDescription = null;

  // These are the details about the Grouper stemp.
  public $description = null;
  public $displayExtension = null;
  public $displayName = null;
  public $extension = null;
  public $name = null;

  // The base stem in Grouper under which all COmanage details are stored.
  private $comanageBaseStem = null;

  // The stem delineator being used by Grouper, so that we can normalize
  // it away for some Grouper fields if found as input into COmanage,
  // for example as the group name.
  private $grouperStemDelineator = null;

  // A replacement to use if delineator is found.
  private $grouperStemDelineatorReplacement = null;

  // Holds the GrouperRestClient instance.
  private $_connection = null;

  /**
   * Constructor for GrouperCoStem
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  public function __construct() {
    // Read configuration details.
    $this->comanageBaseStem = Configure::read('Grouper.COmanage.baseStem');
    $this->grouperStemDelineator = Configure::read('Grouper.COmanage.grouperStemDelineator');
    $this->grouperStemDelineatorReplacement = Configure::read('Grouper.COmanage.grouperStemDelineatorReplacement');

    // Create instance of rest client to Grouper.
    $this->_connection = new GrouperRestClient();
  }

  /**
   * Destructor for GrouperCoStem
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  public function __destruct() {
  }

  private function coNameCanonicalize($coName) {
    $name = str_replace(' ', '', $coName);
    return str_replace($this->grouperStemDelineator, $this->grouperStemDelineatorReplacement, $name);
  }

  /**
   * Factory function to create a stem if necessary to
   * represent the CO.
   *
   * @since         COmanage Registry 0.7
   * @coId          COmanage CO id
   * @return        instance
   * @throws        GrouperCoStemException 
   */
  public static function fromCoId($coId) {
    $instance = new self();

    $instance->coId = $coId;

    $instance->makeCOProperties();
    $instance->makeDescription();
    $instance->makeDisplayExtension();
    $instance->makeDisplayName();
    $instance->makeExtension();
    $instance->makeName();

    try {
      // We do not need to check for existence since
      // the stem save operation is idempotent.
      $result = $instance->_connection->stemSave(
                                          $instance->name, 
                                          $instance->description, 
                                          $instance->displayExtension);
    } catch (Exception $e) {
      throw new GrouperCoStemException("Error creating stem for co_id = $coId: " . $e->getMessage());
    }

    $instance->uuid = $result->uuid;

    return $instance;
  }

  /**
   * Query database to find CO properties 
   *
   * @since         COmanage Registry 0.7
   * @return        void
   */
  private function makeCOProperties() {
    $Co = ClassRegistry::init('Co');
    $Co->Behaviors->attach('Containable');
    $Co->contain();
    $co = $Co->findById($this->coId);
    $this->coName = $co['Co']['name'];
    $this->coDescription = $co['Co']['description'];
  }

  /**
   * Create stem description from CO description
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoStemException
   */
  private function makeDescription() {
    if(empty($this->coDescription)){
      throw new GrouperCoStemException('CO description not set so cannot make stem description');
    }

    // The stem description is the CO description.
    $this->description = $this->coDescription;
  }

  /**
   * Create display extension from CO name
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoStemException
   */
  private function makeDisplayExtension() {
    if(empty($this->coName)){
      throw new GrouperCoStemException('CO name not set so cannot make stem display extension');
    }

    // The display extension is the unmangled CO name.
    $this->displayExtension = $this->coName;
  }

  /**
   * Create display name from CO name
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoStemException
   */
  private function makeDisplayName() {
    if(empty($this->coName)){
      throw new GrouperCoStemException('CO name not set so cannot make stem display name');
    }

    // The display name is the concatenation of the COmanage base stem,
    // a stem delineator, and the unmangled CO name.
    $this->displayName = $this->comanageBaseStem . $this->grouperStemDelineator . $this->coName;
  }

  /**
   * Create extension from canonicalized CO name
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoStemException
   */
  private function makeExtension() {
    if(empty($this->coName)){
      throw new GrouperCoStemException('CO name not set so cannot make stem extension');
    }

    // The extension is the canonicalized CO name.
    $this->extension = $this->coNameCanonicalize($this->coName);
  }

  /**
   * Create name from canonicalized CO name
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoStemException
   */
  private function makeName() {
    if(empty($this->coName)){
      throw new GrouperCoStemException('CO name not set so cannot make stem name');
    }

    // The name is the concatenation of the COmanage base stem,
    // a stem delineator, and the canonicalized CO name
    $this->name = $this->comanageBaseStem . $this->grouperStemDelineator . $this->coNameCanonicalize($this->coName);
  }
}
