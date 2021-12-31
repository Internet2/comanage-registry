<?php
/**
 * COmanage Registry Data Scrubber Filter Attribute Rule Model
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

class DataScrubberFilterAttribute extends AppModel {
  // Define class name for cake
  public $name = "DataScrubberFilterAttribute";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("DataScrubberFilter.DataScrubberFilter");
  
  // Default display field for cake generated views
  public $displayField = "name_pattern";
  
  // Validation rules for table elements
  public $validate = array(
    'data_scrubber_filter_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'type' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'required' => array(
      'rule' => array('range', -2, 2),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // We need to get the co id via the parent Filter

    $args = array();
    $args['conditions']['DataScrubberFilterAttribute.id'] = $id;
    $args['contain'] = array('DataScrubberFilter');
    
    $attr = $this->find('first', $args);
    
    if(empty($attr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.data_scrubber_filter_attributes.1'), $id)));
    }
    
    // Relations aren't autobinding DataFilter...
    $DataFilter = ClassRegistry::init('DataFilter');
    
    $coId = $DataFilter->field('co_id', array('DataFilter.id' => $attr['DataScrubberFilter']['data_filter_id']));

    if($coId) {
      return $coId;
    }
    
    return parent::findCoForRecord($id);
  }
  
  /**
   * Obtain the set of attributes supported for Data Scrubber Filters.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer $coId  CO ID
   * @return array          Array of supported attributes
   */
  
  public function supportedAttributes($coId) {
    $ret = array(
      'OrgIdentity.affiliation' => _txt('fd.affiliation'),
      'OrgIdentity.date_of_birth' => _txt('fd.date_of_birth'),
      'OrgIdentity.o' => _txt('fd.o'),
      'OrgIdentity.ou' => _txt('fd.ou'),
      'OrgIdentity.title' => _txt('fd.title')
    );
    
    // Build a list of MVPAs and their types
    foreach(array('Address', 'EmailAddress', 'Identifier', 'Name', 'TelephoneNumber', 'Url') as $m) {
      $ret[$m] = $this->DataScrubberFilter->DataFilter->Co->CoExtendedType->active($coId, $m.".type");
    }
    
    return $ret;
  }
}
