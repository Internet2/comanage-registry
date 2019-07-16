<?php

App::uses('CoMidPointProvisionerTarget', 'Model');
App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');

class CoMidPointProvisionerTargetTest extends CakeTestCase {

  // 'plugin.MidPointProvisioner.CoMidPointProvisionerTarget',

  public $fixtures = array(
    'plugin.MidPointProvisioner.CoMidPointProvisionerTarget',
    'CoExtendedType',
    //'CoIdentifierValidator',
    'CoPerson',
    'Identifier'
  );

  public $coProvisioningTargetData = array(
    'CoMidPointProvisionerTarget' => array(
      'serverurl' => 'https://172.22.0.6:443/midpoint',
      'username' => 'Administrator',
      'password' => '5ecr3t',
      'ssl_allow_self_signed' => 1,
      'ssl_verify_host' => 0,
      'ssl_verify_peer' => 0,
      'ssl_verify_peer_name' => 0,
      'user_name_identifier' => 'uid'
    )
  );

  /**
   * @var array COmanage person with minimal attributes.
   */
  public $comanagePersonMinimal = array(
    'CoPerson' => array(
      'id' => '2',
      'co_id' => 2
    ),
    'PrimaryName' => array(
      'given' => 'Given',
      'family' => 'Family',
    ),
    'Identifier' => array(
      array(
        'identifier' => 'given.family',
        'type' => 'uid',
      )
    )
  );

  public $midPointUserMinimal = array(
    'user' => array(
      'givenName' => 'Given',
      'familyName' => 'Family',
      'fullName' => 'Given Family',
      'name' => 'given1.family1'
    )
  );

  /** @var MidPointRestApiClient $api */
  public $api;

  /** @var CoMidPointProvisionerTarget $target */
  public $target;

  public function setUp() {
    parent::setUp();
    $this->target = ClassRegistry::init('CoMidPointProvisionerTarget');
    $this->api = new MidPointRestApiClient($this->coProvisioningTargetData);
  }

  public function testCalcUserMinimal() {
    $midPointUser = $this->target->calcUser($this->coProvisioningTargetData, $this->comanagePersonMinimal);
    $this->assertEquals($this->midPointUserMinimal['user']['givenName'], $midPointUser['user']['givenName']);
    $this->assertEquals($this->midPointUserMinimal['user']['familyName'], $midPointUser['user']['familyName']);
    $this->assertEquals($this->midPointUserMinimal['user']['fullName'], $midPointUser['user']['fullName']);
    $this->assertEquals($this->midPointUserMinimal['user']['name'], $midPointUser['user']['name']);
  }

  public function testDiffUserAdd() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserAddDeleteReplace() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle'
      ),
      'delete' => array(
        'nickName' => 'Given'
      ),
      'replace' => array(
        'givenName' => 'New Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserAddMultiple() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['additionalName'] = 'Middle';
    $midPointUserAfter['user']['nickName'] = 'Given';

    $expectedMods = array(
      'add' => array(
        'additionalName' => 'Middle',
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserDelete() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;

    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'delete' => array(
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserDeleteMultiple() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;

    $midPointUserBefore['user']['additionalName'] = 'Middle';
    $midPointUserBefore['user']['nickName'] = 'Given';

    $expectedMods = array(
      'delete' => array(
        'additionalName' => 'Middle',
        'nickName' => 'Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserNoChanges() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEmpty($mods);
  }

  public function testDiffUserReplace() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $expectedMods = array(
      'replace' => array(
        'givenName' => 'New Given'
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testDiffUserReplaceMultiple() {
    $midPointUserBefore = $this->midPointUserMinimal;

    $midPointUserAfter = $midPointUserBefore;
    $midPointUserAfter['user']['familyName'] = 'New Family';
    $midPointUserAfter['user']['givenName'] = 'New Given';

    $expectedMods = array(
      'replace' => array(
        'familyName' => 'New Family',
        'givenName' => 'New Given',
      )
    );

    $mods = $this->target->diffUser($midPointUserAfter, $midPointUserBefore);
    $this->assertEquals($expectedMods, $mods);
  }

  public function testGetIdentifier() {
    $midPointOid = $this->target->findIdentifier(2, 2);
    // TODO $this->assertEquals('2162bca7-a6b2-4b24-a1fe-06a5bb2b2977', $midPointOid);
  }

}