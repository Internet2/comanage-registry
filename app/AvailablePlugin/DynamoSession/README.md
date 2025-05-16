# AWS DynamoDB SessonHandler Plugin for COmanage Registry 4.x/CakePHP 2.x

The DynamoSession plugin is designed to replace the CakePHP default 'php' session handler as transparently as possible
using the AWS SDK for PHP v3.x Aws\DynamoDb\SessionHandler.

## CloudFormation configuration prerequisites

### AWS::DynamoDB::Table

A DynamoDB table configured with an `id` attribute of the String type as a `HASH` key type, with a TTL attribute with
the name `expires`:

```yaml
DynamoCakePHPSessionsTable:
  Type: AWS::DynamoDB::Table
  Properties:
    AttributeDefinitions:
      - AttributeName: "id"
        AttributeType: "S"
    KeySchema:
      - AttributeName: "id"
        KeyType: "HASH"
    TimeToLiveSpecification:
      AttributeName: "expires"
      Enabled: true
    TableName: dynamo_cakephp_sessions
    BillingMode: "PAY_PER_REQUEST"
```

### AWS::IAM::User

An IAM user with a policy that allows the full set of data updates to the table:

```yaml
DynameCakePHPSessionsUser:
  Type: AWS::IAM::User
  Properties:
    UserName: dynamo_cakephp_sessions_user
    Policies:
      - PolicyName: dynamo_cakephp_sessions_policy
        PolicyDocument:
          Version: '2012-10-17'
          Statement:
            - Effect: 'Allow'
              Action:
                - 'dynamodb:GetItem'
                - 'dynamodb:UpdateItem'
                - 'dynamodb:DeleteItem'
                - 'dynamodb:Scan'
                - 'dynamodb:BatchWriteItem'
              Resource: !GetAtt DynamoCakePHPSessionsTable.Arn
```

### AWS::IAM::AccessKey

An API access key attached to that user to pass into environment variables for the CakePHP web server:

```yaml
DynameCakePHPSessionsAccessKey:
  Type: AWS::IAM::AccessKey
  Properties:
    Serial: 1
    Status: 'Active'
    UserName:
      Ref: DynameCakePHPSessionsUser
```

## Configuration

The following environment variables required to be available to the web server hosting CakePHP:

- `COMANAGE_REGISTRY_DYNAMODB_REGION`: The AWS region for this DynamoDB table.
- `COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_ACCESSKEY`: The API access key for the DynamoDB table user.
- `COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_SECRETACCESSKEY`: The API secret access key for the DynamoDB table user.
- `COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_TABLE`: The DynamoDB table name.

In your COmanage v4.x `Config/core.php` modify your Session section, keeping the `defaults` set to `php` and updating
the `handler` to use this plugin:

```php
 Configure::write('Session', array(
    'cookie' => 'CAKEPHP',
    'timeout' => 480,
    'cookieTimeout' => 480,
    'handler' => array(
      'engine' => 'DynamoSession.DynamoSession',
    ),
    'ini' => array(
      'session.use_trans_sid' => 0,
      'session.use_cookies' => 1,
      'session.serialize_handler' => 'php',
      'session.cookie_httponly' => true,
      // session.gc_maxlifetime in seconds
      // default is 1440 (24 minutes)
      'session.gc_maxlifetime' => 86400,
    )
  ));
```

You may want to update the `ini` element here to set the `session.gc_maxlifetime` here, or take the defaults from your
PHP engine configuration.

## Notes

From
the [AWS SDK for PHP v3 documentation](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/service_dynamodb-session-handler.html#configuration),
session lifetime is set by the PHP session.gc_maxlifetime tunable.

## References

- [Using the DynamoDB session handler with AWS SDK for PHP Version 3](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/service_dynamodb-session-handler.html#)
- [AWS SDK for PHP v3 API - AWS\DynamoDb\SessionHandler](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.DynamoDb.SessionHandler.html)
- [CakePHP v2.x Sessions](https://book.cakephp.org/2/en/development/sessions.html)