<?php

/*
 * Cake wrapper for DynamoDB PHP Session Handler
 *
 * CakePHP reference:
 * https://book.cakephp.org/2/en/development/sessions.html
 *
 * AWS SDK for PHP v3 reference:
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/service_dynamodb-session-handler.html
 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.DynamoDb.SessionHandler.html
*/

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

use Aws\DynamoDb\SessionHandler;

class DynamoSession implements CakeSessionHandlerInterface
{
    private $sessionHandler;

    public function __construct()
    {

        $dynamoDb = new \Aws\DynamoDb\DynamoDbClient([
            'region' => getenv('COMANAGE_REGISTRY_DYNAMODB_REGION'),
            'endpoint' => getenv('COMANAGE_REGISTRY_DYNAMODB_ENDPOINT') ?? null,
            'credentials' => [
                'key' => getenv('COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_ACCESSKEY'),
                'secret' => getenv('COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_SECRETACCESSKEY'),
            ],
            //'debug' => true,
        ]);
        $this->sessionHandler = SessionHandler::fromClient($dynamoDb, [
            'table_name' => getenv('COMANAGE_REGISTRY_DYNAMODB_PHPSESSIONS_TABLE'),
            'hash_key' => 'id',
            'data_attribute' => 'data',
            'session_lifetime_attribute' => 'expires',
        ]);

        // do not need to register, as CakePHP routes everything through this class
        // and registers this class as the custom session.save_handler
        /* $this->sessionHandler->register(); */
    }

    public function close(): bool
    {
        return $this->sessionHandler->close();
    }

    public function destroy($sessionId): bool
    {
        return $this->sessionHandler->destroy($sessionId);
    }

    public function gc($expires = null): bool
    {
        return $this->sessionHandler->gc($expires);
    }

    public function open(): bool
    {
        $sessionName = Configure::Read('Session.cookie');
        $savePath = null; // DynamoDB has no file path
        return $this->sessionHandler->open($savePath, $sessionName);
    }

    public function read($sessionId)
    {
        return $this->sessionHandler->read($sessionId);
    }

    public function write($sessionId, $sessionData): bool
    {
        return $this->sessionHandler->write($sessionId, $sessionData);
    }

    /* prevent COmanage/Cake from trying to use this as a generic Datasource */
    public function isPlugin(): bool
    {
        return false;
    }
}