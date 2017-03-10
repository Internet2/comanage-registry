<?php
/**
 * COmanage Cache Shell
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
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  class CacheShell extends AppShell {
    function main()
    {
      // Clear caches to account for any changes made to models/controllers that Cake
      // doesn't automagically pick up. (See CO-442.)
      
      // Before we start, make sure debugging is set to 2. We do this for two reasons:
      // (1) That level also clears out caches
      // (2) At level 0, errors clearing the cache may not reported
      // (We don't need to set it back to whatever it was before since we'll exit quickly.)
      Configure::write('debug', 2);
      
      foreach(array('models', 'persistent', 'views') as $type) {
        // There's no point in checking the return code since clearCache returns false
        // if the cache is already empty.
        clearCache(null, $type);
        
        // Check if the cache is in fact empty
        $cacheDir = CACHE . $type . DS;
        
        $files = glob($cacheDir . '*');
        
        if(!empty($files)) {
          $this->out(_txt('er.sh.cache', array($cacheDir)));
        }
      }
      
      $this->out(_txt('se.cache.done'));
    }
  }
