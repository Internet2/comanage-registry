<?php
/**
 * COmanage Registry CO Job Backend Parent Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class CoJobBackend extends AppModel {
  public $useTable = false;
  
  /**
   * Build an option parser based on the plugin's parameters.
   * 
   * @since  COmanage Registry v3.3.0
   * @return ConsoleOptionParser
   */
  
  public function getOptionParser() {
    $parser = new ConsoleOptionParser();
    
    $parser->addOption(
      'asynchronous',
      array(
        'short'    => 'a',
        'help'     => _txt('sh.job.arg.asynchronous'),
        'boolean'  => true,
        'default'  => false
      )
    )->addOption(
      'coid',
      array(
        'short'    => 'c',
        'help'     => _txt('sh.job.arg.coid'),
        'boolean'  => false,
        'default'  => false,
        'required' => true
      )
    )->addOption(
      'synchronous',
      array(
        'short'   => 's',
        'help'    => _txt('sh.job.arg.synchronous'),
        'boolean' => true,
        'default' => false
      )
    );
    
    foreach($this->parameterFormat() as $p => $cfg) {
      if($cfg['type'] == 'bool') {
        // Set cake flag
        $cfg['boolean'] = true;
      }
      
      $parser->addOption($p, $cfg);
    }
    
    return $parser;
  }
}
