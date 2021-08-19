<?php
/**
 * COmanage Registry CO Terms and Conditions Model
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

/*
 * Note: This model looks plural because the phrase "Terms and Conditions" is actually
 * singular. Cake's inflector has been configured
 */  

class CoTermsAndConditions extends AppModel {
  // Define class name for cake
  public $name = "CoTermsAndConditions";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $hasMany = array(
    "CoTAndCAgreement" => array('dependent' => true)
  );

  public $belongsTo = array("Co", "Cou");
  
  // Associated models that should be relinked to the archived attribute during Changelog archiving
  public $relinkToArchive = array('CoTAndCAgreement');
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'url' => array(
      'rule' => array("validateOneOfMany", array("url","body")),
      'required' => false,
      'type' => 'textarea',
      'message' => "" // empty message causes field to get error state without duplicate message
    ),
    'body' => array(
      'rule' => array("validateOneOfMany", array("url","body")),
      'required' => false,
      'type' => 'textarea',
      'message' => 'Either URL or Content must be set for this T&C'
    ),
    'cou_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum'
  );
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   */

  public function afterSave($created, $options = array()) {

    // if we created a new T&C and added a URL pointing to our T&C body,
    // we need to re-save the model with the newly created model id
    if($created && !empty($this->data['CoTermsAndConditions']['body'])) {
      $this->data['CoTermsAndConditions']['url'] = Router::url(array(
                             "controller" => "CoTermsAndConditions",
                             "action" => "display",
                             $this->data['CoTermsAndConditions']['id']
                           ), true);
      $this->save();
    }

    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeSave($options = array()) {

    if (!empty($this->data['CoTermsAndConditions']['co_id'])
      && empty($this->data['CoTermsAndConditions']['ordr'])) {
      // In order to deterministically order TandCs, assign an order.
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoTermsAndConditions.co_id'] = $this->data['CoTermsAndConditions']['co_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if (!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoTermsAndConditions']['ordr'] = $n;
    }

    // if we have a body for our T&C, override any URL with a local
    // URL to display said body
    if (!empty($this->data['CoTermsAndConditions']['body'])) {
      $this->data['CoTermsAndConditions']['url'] = Router::url(array(
                             "controller" => "CoTermsAndConditions",
                             "action" => "display",
                             $this->data['CoTermsAndConditions']['id']
                           ), true);
    }

    return true;
  }

  /**
   * Get all active Terms&Conditions for a CO and its COUs or for a specific COU
   *
   * @param  int   CO identifier
   * @param  int   COU identifier
   * @return array An array of active Terms&Conditions
   */

  public function getTermsAndConditionsByCouId($coId, $couId=null) {
    $args = array();
    $args['conditions']['CoTermsAndConditions.co_id'] = $coId;
    if($couId) {
      $args['conditions']['OR'] = array(
        array('CoTermsAndConditions.cou_id' => $couId),
        array('CoTermsAndConditions.cou_id' => null)
      );
    } else {
      $args['conditions']['CoTermsAndConditions.cou_id'] = null;
    }
    $args['conditions']['CoTermsAndConditions.status'] = SuspendableStatusEnum::Active;
    $args['order'] = array('CoTermsAndConditions.ordr' => 'asc');
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
  
  /**
   * Obtain the set of pending (un-agreed-to) T&C for a CO Person
   *
   * @since  COmanage Registry v0.9.1
   * @param  Integer CO Person identifier
   * @return Array An array of Terms&Conditions as returned by find(), including any Agreements for the CO Person
   */
  
  public function pending($copersonid) {
    // Use status to pull the full set then walk through the results
    
    $ret = array();
    
    foreach($this->status($copersonid) as $tc) {
      if(empty($tc['CoTAndCAgreement'])) {
        // This T&C has not yet been agreed to, so push it onto the list
        
        $ret[] = $tc;
      }
    }
    
    return $ret;
  }
  
  /**
   * Determine the T&C Status for a CO Person
   *
   * @since  COmanage Registry v0.8.3
   * @param  Integer CO Person identifier
   * @return Array An array of Terms&Conditions as returned by find(), including any Agreements for the CO Person
   */
  
  public function status($copersonid) {
    // Pull active T&C for the CO/COU $copersonid is a member of.
    
    // Pull the list of COUs separately rather than trying to code up a subselect
    // since writing a subselect in Cake is actually quite annoying.
    
    $args = array();
    $args['fields'] = 'CoPersonRole.cou_id';
    $args['conditions']['CoPersonRole.co_person_id'] = $copersonid;
    
    $cous = array_values($this->Co->CoPerson->CoPersonRole->find('list', $args));
    
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.co_id=CoTermsAndConditions.co_id';
    $args['conditions']['CoTermsAndConditions.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoPerson.id'] = $copersonid;
    $args['conditions']['OR'] = array(
      array('CoTermsAndConditions.cou_id' => NULL),
      array('CoTermsAndConditions.cou_id' => $cous)
    );
    $args['contain'] = false;
    
    $tandc = $this->find('all', $args);
    
    // Pull the list of agreements separately since changelog behavior makes it a bit
    // tricky to figure out that an agreement linked to an archived T&C is actually sufficient.
    
    $args = array();
    $args['conditions']['CoTAndCAgreement.co_person_id'] = $copersonid;
    $args['order'] = array('CoTAndCAgreement.agreement_time DESC');
    $args['contain'][] = 'CoTermsAndConditions';
    
    $agreements = $this->CoTAndCAgreement->find('all', $args);
    
    // Walk through each T&C and merge in any existing agreements. There's probably
    // a more optimal way to handle this, but typically there will only be a handful
    // of agreements.
    
    for($i = 0;$i < count($tandc);$i++) {
      // This is the ID of the current T&C
      $tid = $tandc[$i]['CoTermsAndConditions']['id'];
      
      foreach($agreements as $a) {
        if($a['CoTermsAndConditions']['id'] == $tid) {
          // Agreement is to the current T&C
          $tandc[$i]['CoTAndCAgreement'] = $a['CoTAndCAgreement'];
          break;
        } elseif($a['CoTermsAndConditions']['co_terms_and_conditions_id'] == $tid) {
          // Agreement is to a previous version of the current T&C,
          // which for now at least is considered sufficient
          $tandc[$i]['CoTAndCAgreement'] = $a['CoTAndCAgreement'];
          // Replace with the version actually agreed to
          $tandc[$i]['CoTermsAndConditions'] = $a['CoTermsAndConditions'];
          break;
        }
      }
    }
    
    return $tandc;
  }
}
