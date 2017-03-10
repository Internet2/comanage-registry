<?php
/**
 * COmanage Notification Shell
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class NotificationShell extends AppShell {
  var $uses = array('Co', 'CoGroup', 'CoNotification', 'CoPerson');
  
  public function getOptionParser() {
    $parser = parent::getOptionParser();
    
    $parser->addArgument(
      'coName',
      array(
        'help'     => _txt('sh.nt.arg.coname'),
        'required' => true
      )
    )->AddArgument(
      'subjectIdentifier',
      array(
        'help'     => _txt('sh.nt.arg.subjectidentifier'),
        'required' => true
      )
    )->AddArgument(
      'actorIdentifier',
      array(
        'help'     => _txt('sh.nt.arg.actoridentifier'),
        'required' => true
      )
    )->AddArgument(
      'recipientIdentifier',
      array(
        'help'     => _txt('sh.nt.arg.recipientidentifier'),
        'required' => true
      )
    )->AddArgument(
      'action',
      array(
        'help'     => _txt('sh.nt.arg.action'),
        'required' => true
      )
    )->AddArgument(
      'comment',
      array(
        'help'     => _txt('sh.nt.arg.comment'),
        'required' => true
      )
    )->AddArgument(
      'source',
      array(
        'help'     => _txt('sh.nt.arg.source'),
        'required' => true
      )
    )->addOption(
      'resolve',
      array(
        'short' => 'r',
        'help' => _txt('sh.nt.arg.resolve'),
        'boolean' => true,
      )
    )->description(_txt('sh.nt.arg.desc'))
    ->epilog(_txt('sh.nt.arg.epilog'));
    
    return $parser;
  }
  
  public function main() {
    // First try to find the CO ID
    
    $args = array();
    $args['name'] = $this->args[0];
    $args['status'] = SuspendableStatusEnum::Active;
    
    $coId = $this->Co->field('id', $args);
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.unk-a', array($this->args[0])));
    }
    
    $this->out($this->args[0] . " => " . $coId, 1, Shell::VERBOSE);
    
    // Subject
    
    $subjectCoPersonId = $this->mapIdentifier($this->args[1], $coId);
    
    $this->out($this->args[1] . " => " . $subjectCoPersonId, 1, Shell::VERBOSE);
    
    // Actor
    
    $actorCoPersonId = $this->mapIdentifier($this->args[2], $coId);
    
    $this->out($this->args[2] . " => " . $actorCoPersonId, 1, Shell::VERBOSE);
    
    // Recipient -- we use : to tell co group from co person identifier. This means we can't
    // send to groups with a colon in the name, which given the limited scope of this shell
    // shouldn't be an issue.
    
    $recipientType = null;
    $recipientId = null;
    
    if(strchr($this->args[3], ":")) {
      $recipientType = 'coperson';
      $recipientId = $this->mapIdentifier($this->args[3], $coId);
    } else {
      $args = array();
      $args['name'] = $this->args[3];
      $args['co_id'] = $coId;
      $args['status'] = SuspendableStatusEnum::Active;
      
      $recipientType = 'cogroup';
      $recipientId = $this->CoGroup->field('id', $args);
      
      if(!$recipientId) {
        throw new InvalidArgumentException(_txt('er.gr.nf', array($this->args[3])));
      }
    }
    
    $this->out($this->args[3] . " => " . $recipientType . ":" . $recipientId, 1, Shell::VERBOSE);
    
    // Action
    
    $action = $this->args[4];
    
    // Comment
    
    $comment = $this->args[5];
    
    // Source
    
    $source = null;
    
    if(strncmp('http://', $this->args[6], 7)==0
       || strncmp('https://', $this->args[6], 8)==0) {
      $source = $this->args[6];
    } else {
      $s = explode(",", $this->args[6], 5);
      
      $source['controller'] = $s[0];
      $source['action'] = $s[1];
      $source['id'] = $s[2];
      
      if(isset($s[3])) {
        $source['arg0'] = $s[3];
        
        if(isset($s[4])) {
          $source['val0'] = $s[4];
        }
      }
    }
    
    // Resolution Required
    
    $resolve = $this->params['resolve'];
    
    $ids = $this->CoNotification->register($subjectCoPersonId,
                                           null, // subject group not currently supported
                                           $actorCoPersonId,
                                           $recipientType,
                                           $recipientId,
                                           $action,
                                           $comment,
                                           $source,
                                           $resolve);
    
    foreach($ids as $id) {
      $this->out(_txt('rs.nt.delivered', array($id)));
    }
  }
  
  /**
   * Map an identifier and type to a CO Person
   *
   * @since  COmanage Registry v0.8.4
   * @param  String $identifier Identifier, of the form type:value
   * @param  Integer $coId CO ID to search within
   * @return Integer CO Person ID
   * @throws Invalid Argument Exception
   */
  
  protected function mapIdentifier($identifier, $coId) {
    $i = explode(":", $identifier, 2);
    
    $args = array();
    $args['joins'][0]['table'] = 'cm_identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
    $args['conditions']['Identifier.type'] = $i[0];
    $args['conditions']['Identifier.identifier'] = $i[1];
    $args['conditions']['Identifier.status'] = StatusEnum::Active;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['contain'] = false;
    
    $coPerson = $this->CoPerson->find('first', $args);
    
    if(empty($coPerson)) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($identifier)));
    }
    
    return $coPerson['CoPerson']['id'];
  }
}
