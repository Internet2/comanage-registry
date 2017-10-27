<?php
/**
 * COmanage Registry Organizational Identity Source Record Model
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class OrgIdentitySourceRecord extends AppModel {
  // Define class name for cake
  public $name = "OrgIdentitySourceRecord";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array('CoPetition',
                            'OrgIdentity',
                            'OrgIdentitySource');
  
  public $hasMany = array();
  
  // Default display field for cake generated views
  public $displayField = "sorid";
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'sorid' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'source_record' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'last_update' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_petition_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
}