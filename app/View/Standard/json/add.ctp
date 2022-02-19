<?php
/**
 * COmanage Registry Standard JSON Add View
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

// Get a pointer to our model
$model = $this->name;
$req = Inflector::singularize($model);
$modelid = Inflector::underscore($req) . "_id";

if(!empty($$modelid)) {
  print json_encode(array("ResponseType" => "NewObject",
                          "Version" => "1.0",
                          "ObjectType" => $req,
                          "Id" => $$modelid)) . PHP_EOL;
} elseif(!empty($invalid_fields)) {
  print json_encode(array("ResponseType" => "ErrorResponse",
                          "Version" => "1.0",
                          "Id" => "New",
                          "InvalidFields" => $invalid_fields)) . PHP_EOL;
} elseif(!empty($vv_error)) {
  print json_encode(array("ResponseType" => "ErrorResponse",
                          "Version" => "1.0",
                          "Id" => "New",
                          "Error" => $vv_error)) . PHP_EOL;
}
