<?php
/**
 * COmanage Registry Standard JSON Add View
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Get a pointer to our model
$model = $this->name;
$req = Inflector::singularize($model);
$modelid = Inflector::underscore($req) . "_id";

if(!empty($$modelid)) {
  print json_encode(array("ResponseType" => "NewObject",
                          "Version" => "1.0",
                          "ObjectType" => $req,
                          "Id" => $$modelid)) . "\n";
} elseif(!empty($invalid_fields)) {
  print json_encode(array("ResponseType" => "ErrorResponse",
                          "Version" => "1.0",
                          "Id" => "New",
                          "InvalidFields" => $invalid_fields)) . "\n";
}
