<?php
  /**
   * COmanage Registry CO Terms and Conditions Inline T&C Display
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
   * @since         COmanage Registry v0.8.3
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */
  
  // The Inline T&Cs can display user-generated HTML output. Use the html-sanitizer library.
  require(APP . '/Vendor/html-sanitizer-1.5/vendor/autoload.php');
  $sanitizer = HtmlSanitizer\Sanitizer::create([
    'extensions' => ['basic', 'code', 'image', 'list', 'table', 'details', 'extra'],
    'tags' => [
      'div' => [
        'allowed_attributes' => ['class'],
      ],
      'p' => [
        'allowed_attributes' => ['class'],
      ]
    ]
  ]);
  print $sanitizer->sanitize($body);