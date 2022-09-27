<?php

// Get a pointer to our model
$req = "EmailAddress";
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
  