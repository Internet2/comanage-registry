<?php
/**
 * COmanage Registry CO Identifier Assignment Model
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoIdentifierAssignment extends AppModel {
  // Define class name for cake
  public $name = "CoIdentifierAssignment";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoGroup"
  );
  
  public $hasMany = array(
    "CoSequentialIdentifierAssignment" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                        SuspendableStatusEnum::Suspended)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'context' => array(
      'rule' => array(
        'inList',
        array(
          IdentifierAssignmentContextEnum::CoDepartment,
          IdentifierAssignmentContextEnum::CoGroup,
          IdentifierAssignmentContextEnum::CoPerson
        )
      ),
      'required' => true,
      'allowEmpty' => false
    ),
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::UID))),
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'email_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'EmailAddress.type',
                              'default' => array(EmailAddressEnum::Delivery,
                                                 EmailAddressEnum::Forwarding,
                                                 EmailAddressEnum::Official,
                                                 EmailAddressEnum::Personal))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'login' => array(
      'rule' => array('boolean'),
    ),
    'algorithm' => array(
      'rule' => array(
        'inList',
        array(
          IdentifierAssignmentEnum::Random,
          IdentifierAssignmentEnum::Sequential,
          IdentifierAssignmentEnum::Plugin
        )
      ),
      'required' => true
    ),
    'plugin' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'format' => array(
      'rule' => '/.*/',
      // This should be required, but because we're stapling Plugins on rather
      // than moving existing logic into its own plugin, we can't require it
      'required' => false,
      'allowEmpty' => true
    ),
    'minimum' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'maximum' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'permitted' => array(
      'rule' => array(
        'inList',
        array(
          PermittedCharacterEnum::AlphaNumeric,
          PermittedCharacterEnum::AlphaNumDotDashUS,
          PermittedCharacterEnum::AlphaNumDDUSQuote,
          PermittedCharacterEnum::Any
        )
      )
    ),
    'collision_resolution' => array(
      'rule' => '/.*/'
    ),
    'exclusions' => array(
      'rule' => array(
        'inList',
        array(
          IdentifierAssignmentExclusionEnum::Confusing,
          IdentifierAssignmentExclusionEnum::Offensive,
          IdentifierAssignmentExclusionEnum::Superstitious
        )
      )
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Auto-assign an identifier to a CO Person or CO Group if one does not already exist.
   * Note: This method is atomic. Multiple concurrent runs will not result in multiple assignments.
   * Note: This method will not trigger provisioning. Manually trigger provisioning if required.
   *
   * @since  COmanage Registry v0.6
   * @param  Array CoIdentifierAssignment data, as returned by find
   * @param  String  Object Type ("CoDepartment", "CoGroup", "CoPerson")
   * @param  Integer Object ID
   * @param  Integer Actor CO Person ID
   * @param  Boolean Whether to run provisioners on save
   * @param  Integer Actor API User ID
   * @return Integer ID of newly created Identifier
   * @throws InvalidArgumentException
   * @throws OverflowException (identifier already exists)
   * @throws UnderflowException (subject not eligible)
   * @throws RuntimeException
   */
  
  public function assign($coIdentifierAssignment, $objType, $objId, $actorCoPersonId, $provision=true, $actorApiUserId=null) {
    $ret = null;

    // Determine if we are actually assigning an email address instead of an identifier.
    $assignEmail = false;
    
    if($coIdentifierAssignment['CoIdentifierAssignment']['identifier_type'] == 'mail'
       && !empty($coIdentifierAssignment['CoIdentifierAssignment']['email_type'])
       // CoGroups do not currently have email addresses
       && $objType != 'CoGroup') {
      $assignEmail = true;
    }
    
    if($objType == 'CoPerson' 
       && !empty($coIdentifierAssignment['CoIdentifierAssignment']['co_group_id'])) {
      // Check to see if the subject is in the configured group.
      
      if(!$this->CoGroup->CoGroupMember->isMember($coIdentifierAssignment['CoIdentifierAssignment']['co_group_id'], $objId)) {
        throw new UnderflowException(_txt(_txt('er.ia.gr.mem')));
      }
    } 
    
    // Begin a transaction. This is more because we need to ensure the integrity of
    // data between SELECT and INSERT/UPDATE than that we expect to rollback.
    
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Find the requested object
    
    $args = array();
    $args['conditions'][$objType.'.id'] = $objId;
    $args['contain'] = array();
    $args['contain']['Identifier'] = array(
      'conditions' => array(
        'Identifier.deleted IS NOT TRUE',
        'Identifier.identifier_id IS NULL'
      )
    );
    if($objType == 'CoPerson') {
      $args['contain']['PrimaryName'] = array(
          'conditions' => array(
            'PrimaryName.deleted IS NOT TRUE',
            'PrimaryName.primary_name IS TRUE',
            'PrimaryName.name_id IS NULL'
        )
      );
    }
    
    $obj = $this->Co->$objType->find('first', $args);

    if(empty($obj)) {
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.notfound',
                                         array(_txt('ct.'.Inflector::tableize($objType).'.1'),
                                               $objId)));
    }
    
    // For now, we throw an error if the CoGroup is an automatic group, since
    // Identifiers are not currently supported there. (CO-1829)
    
    if($objType == 'CoGroup' && $obj['CoGroup']['auto']) {
      $dbc->rollback();
      throw new InvalidArgumentException(_txt('er.ia.gr.auto'));
    }
    
    // Check for the Identifier. If the person already has one of this sort,
    // don't generate a new one.
    
    if($assignEmail) {
      if($this->Co->CoPerson->EmailAddress->assigned($objType,
                                                     $objId,
                                                     $coIdentifierAssignment['CoIdentifierAssignment']['email_type'])) {
        $dbc->commit();
        throw new OverflowException(_txt('er.ia.already'));
      }
    } else {
      if($this->Co->CoPerson->Identifier->assigned($objType,
                                                   $objId,
                                                   $coIdentifierAssignment['CoIdentifierAssignment']['identifier_type'])) {
        $dbc->commit();
        throw new OverflowException(_txt('er.ia.already'));
      }
    }
    
    // If we are invoking a plugin, do it here
    
    if($coIdentifierAssignment['CoIdentifierAssignment']['algorithm'] == IdentifierAssignmentEnum::Plugin) {
      if(empty($coIdentifierAssignment['CoIdentifierAssignment']['plugin'])) {
        $dbc->commit();
        throw new InvalidArgumentException(_txt('er.ia.plugin'));
      }
      
      $pModel = ClassRegistry::init($coIdentifierAssignment['CoIdentifierAssignment']['plugin'] . "." . $coIdentifierAssignment['CoIdentifierAssignment']['plugin']);
      
      try {
        // Unlike our native implementation, we do NOT retry if the returned
        // identifier is already in use. We simply through an error.
        
        $candidate = $pModel->assign(
          $coIdentifierAssignment['CoIdentifierAssignment']['co_id'],
          $coIdentifierAssignment['CoIdentifierAssignment']['context'],
          $objId,
          $coIdentifierAssignment['CoIdentifierAssignment']['identifier_type'],
          $coIdentifierAssignment['CoIdentifierAssignment']['email_type']
        );
        
        if(empty($candidate)) {
          $dbc->commit();
          throw new InvalidArgumentException("No identifier received"); // XXX I18n
        }
        
        // We only try once, pretty much any failure should throw an Exception.
        // If checkInsert() returns null we'll catch it below.
        
        $ret = $this->checkInsert($coIdentifierAssignment,
                                  $objType,
                                  $assignEmail,
                                  $obj,
                                  $candidate,
                                  $actorCoPersonId,
                                  $provision,
                                  $actorApiUserId);
      }
      catch(Exception $e) {
        $dbc->rollback();
        throw new InvalidArgumentException($e->getMessage());
      }
    } else {
      // Generate the new identifier. This requires several steps. First, substitute
      // non-collision number parameters. If no format is specified, default to "(#)".
      
      $iaFormat = "(#)";
      
      if(isset($coIdentifierAssignment['CoIdentifierAssignment']['format'])
         && $coIdentifierAssignment['CoIdentifierAssignment']['format'] != '') {
        $iaFormat = $coIdentifierAssignment['CoIdentifierAssignment']['format'];
      }

      try {
        $base = $this->substituteParameters($iaFormat,
                                            $obj['PrimaryName'] ?? $obj[$objType]['name'],
                                            $obj['Identifier'],
                                            $coIdentifierAssignment['CoIdentifierAssignment']['permitted']);
      }
      catch(Exception $e) {
        // Some sort of substitution error, such as dependent identifier not existing
        $dbc->rollback();
        throw new InvalidArgumentException($e->getMessage());
      }
      
      // Now that we've got our base, loop until we get a unique identifier.
      // We try a maximum of 10 (0 through 9) times, and track identifiers we've
      // seen already.
      
      $tested = array();
      
      for($i = 0;$i < 10;$i++) {
        $sequenced = $this->selectSequences($base,
                                            $i,
                                            $coIdentifierAssignment['CoIdentifierAssignment']['permitted']);
        
        // There may or may not be a collision number format. If so, we should end
        // up with a unique candidate (though for random it's possible we won't).
        $candidate = $this->assignCollisionNumber($coIdentifierAssignment['CoIdentifierAssignment']['id'],
                                                  $sequenced,
                                                  $coIdentifierAssignment['CoIdentifierAssignment']['algorithm'],
                                                  $coIdentifierAssignment['CoIdentifierAssignment']['minimum'],
                                                  $coIdentifierAssignment['CoIdentifierAssignment']['maximum']);
        
        if(!in_array($candidate, $tested)
           // Also check that we didn't get an empty string
           && trim($candidate) != false) {
          // We have a new candidate (ie: one that wasn't generated on a previous loop),
          // so let's see if it is already in use.
          
          $ret = $this->checkInsert($coIdentifierAssignment,
                                    $objType,
                                    $assignEmail,
                                    $obj,
                                    $candidate,
                                    $actorCoPersonId,
                                    $provision,
                                    $actorApiUserId);
        }
        
        if($ret)
          break;
        
        // else try the next one
        $tested[] = $candidate;
      }
    }
    
    $dbc->commit();
    
    // Return the new ID (or throw an error if we don't have one)
    
    if(!$ret) {
      throw new RuntimeException(_txt('er.ia.failed'));
    }
    
    return $ret;
  }
  
  /**
   * Assign a collision number if the current identifier segment accepts one.
   *
   * @since  COmanage Registry v0.6
   * @param  String Sequenced string as returned by selectSequences
   * @param  Integer CO Identifier Assignment ID
   * @param  IdentifierAssignmentEnum Algorithm to assign collision number
   * @param  Integer Minimum number to assign
   * @param  Integer Maximum number to assign (for Random only)
   * @return String Candidate string, possibly with a collision number assigned
   * @throws InvalidArgumentException
   */
  
  private function assignCollisionNumber($coIdentifierAssignmentID, $sequenced, $algorithm, $min, $max) {
    // We expect $sequenced to be %s and not %d in order to be able to ensure
    // a specific width (ie: padded and/or truncated). This also makes sense in that
    // identifiers are really strings, not numbers.
    
    $matches = array();
    
    if(preg_match('/\%[0-9.]*s/', $sequenced, $matches)) {
      switch($algorithm) {
        case IdentifierAssignmentEnum::Random:
          // Simply pick a number between $min and $max.

          $lmax = $max;
          
          if(!$max) {
            // We have to be a bit careful with min and max vs mt_rand(). substituteParameters()
            // will generate something like (%05.5s). If no explicit $max is configured by the
            // admin, we used mt_getrandmax. However, that could generate a string like 172500398.
            // We take the first (eg) 5 digits, which are "17250". If $min is 20000, we'll
            // incorrectly assign a collision number outside the permitted range (CO-1933).
            
            // Pull the width out of the string
            $width = (int)rtrim(ltrim(strstr($matches[0], '.'), "."), "s");
            
            // And calculate a new max
            $lmax = (10 ** $width) - 1;
          }

          // XXX should switch to random_bytes() with PE
          $n = mt_rand($min, $lmax);
          return sprintf($sequenced, $n);
          break;
        case IdentifierAssignmentEnum::Sequential:
          return sprintf($sequenced, $this->CoSequentialIdentifierAssignment->next($coIdentifierAssignmentID, $sequenced, $min));
          break;
        default:
          throw new InvalidArgumentException(_txt('er.unknown', array($algorithm)));
          break;
      }
    } else {
      # Nothing to do, just return the same string
      
      return $sequenced;
    }
  }

  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['CoIdentifierAssignment']['algorithm'])
       && $this->data['CoIdentifierAssignment']['algorithm'] == IdentifierAssignmentEnum::Plugin
       && empty($this->data['CoIdentifierAssignment']['plugin'])) {
      throw new InvalidArgumentException(_txt('er.ia.plugin'));
    }
    
    if(!empty($this->data['CoIdentifierAssignment']['co_id'])
       && (empty($this->data['CoIdentifierAssignment']['ordr'])
           || $this->data['CoIdentifierAssignment']['ordr'] == '')) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoIdentifierAssignment.co_id'] = $this->data['CoIdentifierAssignment']['co_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoIdentifierAssignment']['ordr'] = $n;
    }
    
    return true;
  }
  
  /**
   * Check the requested identifier for availability, and if available insert it.
   *
   * @since  COmanage Registry v4.1.0
   * @param  array  $coIdentifierAssignment CoIdentifierAssignment configuration
   * @param  string $objType                Object type (eg: 'CoPeople') to check identifier for
   * @param  bool   $assignEmail            Whether to check an email address instead of an identifier
   * @param  array  $obj                    Object to check identifier for
   * @param  string $candidate              Candidate identifier
   * @param  int    $actorCoPersonId        Actor CoPerson ID
   * @param  bool   $provision              Whether to trigger provisioning
   * @param  int    $actorApiUserId         Actor API User ID
   * @return int                            Identifier ID, or false
   */
  
  private function checkInsert($coIdentifierAssignment,
                               $objType,
                               $assignEmail,
                               $obj,
                               $candidate,
                               $actorCoPersonId,
                               $provision,
                               $actorApiUserId=null) {
    $ret = null;
    
    try {
      if($assignEmail) {
        $this->Co->CoPerson->EmailAddress->checkAvailability($candidate,
                                                             $coIdentifierAssignment['CoIdentifierAssignment']['email_type'],
                                                             $coIdentifierAssignment['CoIdentifierAssignment']['co_id'],
                                                             true,
                                                             $objType);
      } else {
        $this->Co->CoPerson->Identifier->checkAvailability($candidate,
                                                           $coIdentifierAssignment['CoIdentifierAssignment']['identifier_type'],
                                                           $coIdentifierAssignment['CoIdentifierAssignment']['co_id'],
                                                           false,
                                                           $objType);
      }
    }
    catch(Exception $e) {
      // OverflowException = Identifier in use
      // InvalidArgumentException = Bad format
      // For now, we ignore the details and return false
      
      return false;
    }
    
    // This one's good... insert it into the table
    
    // We need to update the appropriate validation rule with the current CO ID
    // so that extended types can validate correctly. In order to do that, we need
    // the CO ID. We'll pick it out of the CO Identifier Assignment data.
    
    $coId = $coIdentifierAssignment['CoIdentifierAssignment']['co_id'];
    $fk = Inflector::underscore($objType) . "_id";

    if($assignEmail) {
      $emailAddressData = array();
      $emailAddressData['EmailAddress']['mail'] = $candidate;
      $emailAddressData['EmailAddress']['type'] = $coIdentifierAssignment['CoIdentifierAssignment']['email_type'];
      $emailAddressData['EmailAddress'][$fk] = $obj[$objType]['id'];
      
      // We need to update the Email Address validation rule
      $this->Co->CoPerson->EmailAddress->validate['type']['content']['rule'][1]['coid'] = $coId;
      
      // We need to call create to reset the model state since we're (possibly) doing multiple distinct
      // saves against the same model.
      $this->Co->CoPerson->EmailAddress->create($emailAddressData);
      
      if($this->Co->CoPerson->EmailAddress->save($emailAddressData, array('provision' => $provision))) {
        $ret = $this->Co->CoPerson->EmailAddress->id;
      }
    } else {
      $identifierData = array();
      $identifierData['Identifier']['identifier'] = $candidate;
      $identifierData['Identifier']['type'] = $coIdentifierAssignment['CoIdentifierAssignment']['identifier_type'];
      $identifierData['Identifier']['login'] = $coIdentifierAssignment['CoIdentifierAssignment']['login'];
      $identifierData['Identifier'][$fk] = $obj[$objType]['id'];
      $identifierData['Identifier']['status'] = StatusEnum::Active;
      
      // We need to update the Identifier validation rule
      $this->Co->CoPerson->Identifier->validate['type']['content']['rule'][1]['coid'] = $coId;
      
      // We need to call create to reset the model state since we're (possibly) doing multiple distinct
      // saves against the same model.
      $this->Co->CoPerson->Identifier->create($identifierData);
      
      // Because we're in a transaction (at least for local checks, not necessarily
      // for plugin checks), we can advise beforeSave to skip the availability checks
      // (that we just ran).
      if($this->Co->CoPerson->Identifier->save($identifierData,
                                               array('provision' => $provision,
                                                     'skipAvailability' => true))) {
        $ret = $this->Co->CoPerson->Identifier->id;
      }
    }
    
    if($ret) {
      // Create a history record, for CoPerson and CoGroup
      if($objType == 'CoGroup' || $objType == 'CoPerson') {
        $coGroupId = !empty($obj['CoGroup']['id']) ? $obj['CoGroup']['id'] : null;
        $coPersonId = !empty($obj['CoPerson']['id']) ? $obj['CoPerson']['id'] : null;
        
        $txt =  _txt('en.action', null, ActionEnum::IdentifierAutoAssigned) . ': '
         . $candidate . ' (' . $coIdentifierAssignment['CoIdentifierAssignment']['identifier_type']
         . ($assignEmail ? ':'.$coIdentifierAssignment['CoIdentifierAssignment']['email_type'] : '')
         . ')';
        
        try {
          $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $actorCoPersonId,
                                                     ActionEnum::IdentifierAutoAssigned,
                                                     $txt,
                                                     $coGroupId,
                                                     null, null,
                                                     $actorApiUserId);
        }
        catch(Exception $e) {
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    } else {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $ret;
  }
  
  /**
   * Select the sequenced segments to be processed for the given iteration.
   *
   * @since  COmanage Registry v0.6
   * @param  String  Base string as returned by substituteParameters
   * @param  Integer Iteration number (between 0 and 9)
   * @param  Enum    Acceptable characters for substituted parameters (PermittedCharacterEnum)
   * @return String Identifier with sequenced segments selected
   */
  
  private function selectSequences($base, $iteration, $permitted) {
    $sequenced = "";
    
    // Loop through the string
    for($j = 0;$j < strlen($base);$j++) {
      switch($base[$j]) {
        case '\\':
          // Copy the next character directly
          if($j+1 < strlen($base)) {
            $j++;
            $sequenced .= $base[$j];
          }
          break;
        case '[':
          // Sequenced segment
          
          // Single Use segments are only incorporated into the specified iteration,
          // vs Additive segmens that are incorporated into all subsequent ones as well.
          $singleuse = false;
          
          if($j+1 < strlen($base) && $base[$j+1] == '=') {
            $singleuse = true;
            $j++;
          }
          
          if($j+3 < strlen($base)) {
            $j++;
            
            if(($singleuse && ($base[$j] == $iteration))
                ||
                (!$singleuse && ($base[$j] <= $iteration))) {
              // This segment is now in effect, copy until we see a close bracket
              // (and jump past the ':')
              $j += 2;
              
              // Assemble the text for this segment. If after parameter substitution
              // we end up with no permitted characters, skip this segment
              
              $segtext = "";
              
              while($base[$j] != ']') {
                $segtext .= $base[$j];
                $j++;
              }
              
              if(strlen($segtext) > 0
                 && preg_match('/'. _txt('en.chars.permitted.re', null, $permitted) . '/', $segtext)) {
                $sequenced .= $segtext;
              }
            } else {
              // Move to end of segment, we're not using this one yet
              
              while($base[$j] != ']') {
                $j++;
              }
            }
          }
          break;
        default:
          // Just copy this character
          $sequenced .= $base[$j];
          break;
      }
    }
    
    return $sequenced;
  }
  
  /**
   * Perform parameter substitution on an identifier format to generate the base
   * string used in identifier assignment.
   *
   * @since  COmanage Registry v0.6
   * @param  String CoIdentifierAssignment format
   * @param  Mixed  Name array (for CO Person) or string (for CO Group / CO Department)
   * @param  Array  Identifiers array
   * @param  Enum   Acceptable characters for substituted parameters (PermittedCharacterEnum)
   * @return String Identifier with paramaters substituted
   * @throws RuntimeException
   */
  
  private function substituteParameters($format, $name, $identifiers, $permitted) {
    $base = "";
    
    // For random letter generation ('h', 'r', 'R')
    $randomCharSet = array(
      'h' => "0123456789abcdef",
      'l' => "abcdefghijkmnopqrstuvwxyz",  // Note no "l"
      'L' => "ABCDEFGHIJKLMNPQPSTUVWXYZ"   // Note no "O"
    );
    
    // Loop through the format string
    for($i = 0;$i < strlen($format);$i++) {
      switch($format[$i]) {
        case '\\':
          // Copy the next character directly
          if($i+1 < strlen($format)) {
            $i++;
            $base .= $format[$i];
          }
          break;
        case '(':
          // Parameter to substitute
          if($i+2 < strlen($format)) {
            // Move past '('
            $i++;
            
            $width = "";
            
            // Check if the next character is a width specifier
            if($format[$i+1] == ':') {
              // Don't advance $i yet since we still need it, so use $j instead
              for($j = $i+2;$j < strlen($format);$j++) {
                if($format[$j] != ')') {
                  $width .= $format[$j];
                } else {
                  break;
                }
              }
            }
            
            // Do the actual parameter replacement, blocking out characters that aren't permitted
            
            if($permitted) {
              // $permitted is generally expected to be provided, except in some edge upgrade cases
              $charregex = '/'. _txt('en.chars.permitted.re.not', null, $permitted) . '/';
            }
            
            switch($format[$i]) {
              case 'f':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', strtolower($name['family'])));
                break;
              case 'F':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', $name['family']));
                break;
              case 'g':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', strtolower($name['given'])));
                break;
              case 'G':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', $name['given']));
                break;
              // Note 'h' is defined with 'l', below
              // case 'h':
              case 'I':
                // We skip the next character (a slash) and then continue reading
                // until we get to a close parenthesis
                $identifierType = "";
                
                $i+=2;
                
                while($format[$i] != ')' && $i < strlen($format)) {
                  $identifierType .= $format[$i];
                  $i++;
                }
                
                // Rewind one character because we're going to advance past it
                // again below.
                $i--;
                
                if($identifierType == "") {
                  throw new RuntimeException(_txt('er.ia.id.type.none'));
                }
                
                // If we find more than one identifier of the same type, we
                // arbitrarily pick the first.
                
                $id = Hash::extract($identifiers, '{n}[type='.$identifierType.']');
                
                if(empty($id)) {
                  throw new RuntimeException(_txt('er.ia.id.type', array($identifierType)));
                }
                
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', $id[0]['identifier']));
                break;
              case 'h':
              case 'l':
              case 'L':
                for($j = 0;$j < ($width != "" ? $width : 1);$j++) {
                  $base .= $randomCharSet[ $format[$i] ][ mt_rand(0, strlen($randomCharSet[ $format[$i] ])-1) ];
                }
                break;
              case 'm':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', strtolower($name['middle'])));
                break;
              case 'M':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', $name['middle']));
                break;
              case 'n':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', strtolower($name)));
                break;
              case 'N':
                $base .= sprintf("%.".$width."s",
                                 preg_replace($charregex, '', $name));
                break;
              case '#':
                // Convert the collision number parameter to a sprintf style specification,
                // left padded with 0s. Note that assignCollisionNumber expects %s, not %d.
                $base .= "%" . ($width != "" ? ("0" . $width . "." . $width) : "") . "s";
                break;
            }
            
            // Move past the width specifier
            if($width != "") {
              $i += strlen($width) + 1;
            }
            
            // Move past the ')'
            $i++;
          }
          break;
        default:
          // Just copy this character
          $base .= $format[$i];
          break;
      }
    }
    
    return $base;
  }
  
  /**
   * Check if a given extended type is in use by any CO Identifier Assignment.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String Attribute, of the form Model.field
   * @param  String Name of attribute (any default or extended type may be specified)
   * @param  Integer CO ID
   * @return Boolean True if the extended type is in use, false otherwise
   */
  
  public function typeInUse($attribute, $attributeName, $coId) {
    // Note we are effectively overriding AppModel::typeInUse().
    
    // Inflect the model names
    $attr = explode('.', $attribute, 2);
    
    if($attr[0] == 'Identifier' && $attr[1] == 'type') {
      // For MVPA attribute, we need to see if the type is specified as part of the
      // attribute name.
      
      $args = array();
      $args['conditions']['CoIdentifierAssignment.identifier_type'] = $attributeName;
      $args['conditions']['CoIdentifierAssignment.co_id'] = $coId;
      $args['contain'] = false;
      
      return (boolean)$this->find('count', $args);
    }
    // else nothing to do
    
    return false;
  }
}
