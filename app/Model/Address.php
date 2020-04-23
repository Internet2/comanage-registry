<?php
/**
 * COmanage Registry Address Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Address extends AppModel {
  // Define class name for cake
  public $name = "Address";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An email address may be attached to a CO Department
    "CoDepartment",
    // An address may be attached to a CO person role
    "CoPersonRole",
    // An address may be attached to an Org identity
    "OrgIdentity",
    // An address created from a Pipeline has a Source Address
    "SourceAddress" => array(
      'className' => 'Address',
      'foreignKey' => 'source_address_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "street";
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require any element since $belongsTo saves won't validate if they're empty
    'street' => array(
      'content' => array(
        'rule' => array('maxLength', 400),
        'required' => false,
        'allowEmpty' => false
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'room' => array(
      'content' => array(
        'rule' => array('maxLength', 64),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'locality' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'state' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'postal_code' => array(
      'content' => array(
        'rule' => array('maxLength', 16),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'country' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'description' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Address.type',
                              'default' => array(ContactEnum::Campus,
                                                 ContactEnum::Home,
                                                 ContactEnum::Office,
                                                 ContactEnum::Postal))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'language' => array(
      'content' => array(
        'rule'       => array('validateLanguage'),
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'co_person_role_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_address_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function beforeValidate($options = array()) {
    // Update validation rules according to CO Settings, but only for records attached
    // to a CO Person Role
    
    if(!empty($this->data['Address']['co_person_role_id'])) {
      // Map to the CO ID
      
      $args = array();
      $args['joins'][0]['table'] = 'cm_co_person_roles';
      $args['joins'][0]['alias'] = 'CoPersonRole';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
      $args['conditions']['CoPersonRole.id'] = $this->data['Address']['co_person_role_id'];
      $args['contain'] = false;
      
      $cop = $this->CoPersonRole->CoPerson->find('first', $args);
      
      if($cop) {
        $fields = $this->CoPersonRole->CoPerson->Co->CoSetting->getRequiredAddressFields($cop['CoPerson']['co_id']);
        
        foreach($fields as $f) {
          // Make this field required
          $this->validator()->getField($f)->getRule('content')->required = true;
          $this->validator()->getField($f)->getRule('content')->allowEmpty = false;
          $this->validator()->getField($f)->getRule('content')->message = _txt('fd.required');
        }
      } else {
        // If for some reason we can't find the CO, fall back to the defaults
      }
    }
    
    return true;
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    $args['joins'][1]['table'] = 'co_people';
    $args['joins'][1]['alias'] = 'CoPerson';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
    
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(Address.street) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }
    
    $args['conditions']['LOWER(Address.street) LIKE'] = '%' . strtolower($q) . '%';
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['order'] = array('Address.street');
    $args['contain']['CoPersonRole']['CoPerson'] = 'PrimaryName';
    
    return $this->find('all', $args);
  }
  
  /**
   * Perform Address model upgrade steps for version 0.9.4.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v0.9.4
   */
  
  public function _ug094() {
    // If anything fails here we'll just let the exception bubble up the stack.
    
    // 0.9.4 consolidates cm_addresses:line1 and line2 into street (CO-539).
    
    // First we need to migrate line1+line2 into street. We'll add a newline if
    // line2 is populated, but not otherwise. We do this manually because
    // line1/line2 are no longer valid in the Cake model, and also to bypass
    // ChangelogBehavior.
    
    // Note that line1+line2 are preserved in the database for v0.9.4 (even though
    // they're already gone from the model), but will be tossed in v1.0.0. This allows
    // for the transition, but also prevents an upgrade from pre-v0.9.4 directly
    // to v1.0.0.
    
    // Since MySQL returns NULL for CONCAT if any value is NULL (why??? why???),
    // we have to be a bit verbose in the SQL we execute.
    
    $dbc = $this->getDataSource();
    $dbprefix = $dbc->config['prefix'];
    
    $sql = "UPDATE " . $dbprefix . "addresses
SET street=CONCAT(
CASE WHEN line1 IS NOT NULL THEN line1
ELSE ''
END
,
CASE WHEN line2 IS NOT NULL AND line2 <> '' THEN CONCAT('
',line2)
ELSE ''
END
);";
    
    $r = $this->query($sql);
    
    // Next we need to update CO Petition Attributes. This is a little tricky because
    // petitions are artifacts, indicating what was originally submitted, and we're
    // sort of rewriting history here. It's also a bit tricky because we need to
    // consolidate records from multiple rows. Oh, and also there are duplicate rows
    // if ("Copy to CO Person") is ticket (see CO-1097).
    
    // Our solution is a set of nested subselects, which groups the data from line1
    // and line2 together, and then concatenates them into a new "street" value. To
    // preserve history, we leave the line1/line2 attributes in place. (Unlike
    // Address, where we're adding a new attribute and dropping line1/lune2.) This
    // won't actually be visible in the UI (since the line1/line2 attributes are
    // gone), but it will be queryable from the database if needed.
    
    // We have to use subselects here because MySQL doesn't support the more readable
    // WITH syntax.
    
    $sql = "INSERT INTO " . $dbprefix . "co_petition_attributes
            (co_petition_id, co_enrollment_attribute_id, attribute, value, created, modified)
SELECT c.co_petition_id,
       c.co_enrollment_attribute_id,
       'street',
       CONCAT(
        CASE WHEN c.line1 IS NOT NULL THEN c.line1
        ELSE ''
        END,
        CASE WHEN c.line2 IS NOT NULL AND c.line2 <> '' THEN CONCAT('
',c.line2)
        ELSE ''
        END
       ),
       now(),
       now()
FROM
-- select C collapses rows together by pulling the non-NULL strings
(SELECT b.co_petition_id,
        MAX(b.co_enrollment_attribute_id) as co_enrollment_attribute_id,
        MAX(b.line1) as line1,
        MAX(b.line2) as line2
FROM
-- select B removes duplicate attributes (see CO-1097)
(SELECT DISTINCT a.co_petition_id, a.co_enrollment_attribute_id, a.line1, a.line2
FROM
-- select A pulls line1 and line2 attributes from the underlying table and pivots them
(SELECT id,
        co_petition_id,
        co_enrollment_attribute_id,
        CASE WHEN attribute='line1' THEN value END AS line1,
        CASE WHEN attribute='line2' THEN value END AS line2
FROM   " . $dbprefix . "co_petition_attributes
WHERE  attribute IN ('line1','line2')
ORDER BY id DESC) AS a
) AS b
GROUP BY b.co_petition_id) AS c;";
    
    $r = $this->query($sql);
    
    // Update CMP Enrollment Attributes (which may or may not be enabled)
    
    $sql = "UPDATE " . $dbprefix . "cmp_enrollment_attributes
SET   attribute='addresses:street'
WHERE attribute='addresses:line1';";
    
    $r = $this->query($sql);
    
    // Update CO Enrollment Attributes, for required fields
    
    $sql = "UPDATE " . $dbprefix . "co_enrollment_attributes
SET   required_fields=CONCAT('street', SUBSTRING(required_fields, 6, LENGTH(required_fields)))
WHERE required_fields LIKE 'line1%';";
    
    $r = $this->query($sql);
    
    // Update CO Settings, for required fields
    
    $sql = "UPDATE " . $dbprefix . "co_settings
SET   required_fields_addr=CONCAT('street', SUBSTRING(required_fields_addr, 6, LENGTH(required_fields_addr)))
WHERE required_fields_addr LIKE 'line1%';";
    
    $r = $this->query($sql);
  }
}