<?php

/* Plugin used for to address an invitation to a specific email addresss and make sure that same email address will
 * result into a 'unique' identifier for the newly enrolled member of this CO.
 * The scenario is specifically useful when a Service (SP) is expecting theis email address to become
 * the logon UID for the newly enrolled user.
 *
 * Interesting usage;
 * - make enrollent flow (copy form sefl-signup template)
 * - Strip to bare minimum, no approval, no notification, no pettion authorization, etc.
 * - Only enforce:
 * - a) Mail Confirmation Mode = Review !
 * - b) Require Enrollee Authentication = Yes !
 * - Strip enrollment attribute to the minimum, say just NAME and ORGANIZATION
 *
 * Expected result:
 * - Smooth enrollment during which enrollee will have minimal interaction with COmanage. (onjy petition conrfmation)
 *
 * Next step:
 * - Enrollee must LOGIN to COmanage and setup a Service Token as a Password in order for him to succesfully login to the
 *   service using his email addess as UID incombination wth the created token as PASSWORD
 *
 * Author: Harry Kodden, 2018, harry.kodden@surfnet.nl
 */

App::uses('CoPetitionsController', 'Controller');
App::uses('CakeLog', 'Log');

class EmailUidEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "EmailUidEnrollerCoPetitions";
  public $uses = array("CoPetition");
   
  /**
   * Plugin functionality following start step
   *
   * Ths plugin expects a named parameter "invite" holding a base64 encoded emalladress
   * Example usage:
   * https://example.com/registry/email_uid_enroller/email_uid_enroller_co_petitions/start/coef:6/invite:am9vcEB2YW5lbGxlbmRlLm5s
   *
   * This function inspects for this invite URI paramenter and stores the decoded value in a session variable for later retrieval
   *
   * @param Integer $id  = NULL !!!!
   * @param String 'invite' = Base64 encoded email address
   * @param Array $onFinish URL, in Cake format
   */

  protected function execute_plugin_start($id, $onFinish) {

    CakeLog::warning('START');

    if(!empty($this->request->params['named']['invite'])) {
      CakeLog::info(' *** INVITE: ' . $this->request->params['named']['invite']);

      $this->Session->write('invite', base64_decode($this->request->params['named']['invite']));
    }

    $this->redirect($onFinish);
  }

  /**
   * Plugin functionality following petitionerAttributes step
   *
   * This function is taking the earlier stored 'invite' parameter from the session variables. 
   * If available, an Org Identity email address is created. This address will be used during confirmation
   * of this petititon. After confirmation this email adDrEss will become 'verified'
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
   
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {

    $invitation = $this->Session->read('invite');

    if (!empty($invitation)) {
      CakeLog::info("***** SESSION TOKEN :" . $this->Session->read('invite'));

      $args = array();
      $args['conditions']['CoPetition.id'] = $id;
      $args['joins'][0]['table'] = 'co_org_identity_links';
      $args['joins'][0]['alias'] = 'link';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPetition.enrollee_co_person_id=link.co_person_id';
      $args['contain'] = false;
      
      $data = $this->CoPetition->find('first', $args);

      CakeLog::info("[Petition Attributes] Petition Attributes read: " . print_r($data, true));

      // Make sure the invitation email addres is not already 'taken' as UID...
      $this->loadModel('Identifier');
      $args = array();
      $args['conditions']['identifier'] = $invitation;
      $args['condition']['type'] = IdentifierEnum::UID;
      $args['contain'] = false;
      $uid = $this->Identifier->find('first', $args);

      CakeLog::info("[Petition Attributes] UID found: " . print_r($uid, true));

      if (!empty($uid)) {
        throw new OverflowException(_txt('er.ia.already'));
        return;
      } 

      try {
        $this->loadModel('EmailAddress');
        $txn = $this->EmailAddress->getDataSource();
        $txn->begin();

        /* Make sure the OrgIdentity gets this email addres, the petitions will be send to this address...
         */
        $emailAddressData = array();
        $emailAddressData['EmailAddress']['mail'] = $invitation;
        $emailAddressData['EmailAddress']['type'] = EmailAddressEnum::Official;
        $emailAddressData['EmailAddress']['verified'] = false;
        $emailAddressData['EmailAddress']['org_identity_id'] = $data['CoPetition']['enrollee_org_identity_id'];
        
        $this->EmailAddress->create($emailAddressData);
        $this->EmailAddress->save($emailAddressData, array('provision' => false));

        /* Now set the UID to become equal to the MAIL address...
         */
        $identifierData = array();
        $identifierData['Identifier']['identifier'] = $invitation;
        $identifierData['Identifier']['type'] = IdentifierEnum::UID;
        $identifierData['Identifier']['login'] = false;
        $identifierData['Identifier']['status'] = StatusEnum::Active;
        $identifierData['Identifier']['co_person_id'] = $data['CoPetition']['enrollee_co_person_id'];
              
        $this->Identifier->create($identifierData);
        $this->Identifier->save($identifierData, array('provision' => false));

        $txn->commit();

      } catch(Exception $e) {

        $txn->rollback();

        $eclass = get_class($e);
        CakeLog::error("Error assigning Email Address: " . $e->getMessage());

        throw new $eclass($e->getMessage());
      }
    }

    $this->redirect($onFinish);
  }

  /**
   * Plugin functionality following finalizestep
   * 
   * Lookup petition and lookup official email address of org identity of this enrolleee
   * then create a Identifier for this CO Person and make that CO Person 'Active Member'
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
   
  protected function execute_plugin_finalize($id, $onFinish) {

    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['joins'][0]['table'] = 'co_org_identity_links';
    $args['joins'][0]['alias'] = 'link';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPetition.enrollee_co_person_id=link.co_person_id';
    $args['contain'] = false;
    
    $data = $this->CoPetition->find('first', $args);

    if (empty($data)) {
      CakeLog::info("[Finalize] Data is EMPTY so skipping further processing!");
    } else {
      CakeLog::info("[Finalize] Petition Attributes read: " . print_r($data, true));

      $this->loadModel('EmailAddress');

      /* Now for the big conclusion:
       * - If wa have a (now) verified Email Address
       * - That email addres is equal to the Active UID for this CO Person
       * Then we are 'all set' we have enrolled sucessful CO person, make him active !
       */
      $args = array();
      $args['conditions']['EmailAddress.org_identity_id'] = $data['CoPetition']['enrollee_org_identity_id'];
      $args['conditions']['EmailAddress.type'] = EmailAddressEnum::Official;
      $args['conditions']['EmailAddress.verified'] = true;
      $args['conditions']['uid.type'] = IdentifierEnum::UID;
      $args['conditions']['uid.login'] = false;
      $args['conditions']['uid.status'] = StatusEnum::Active;
      $args['conditions']['uid.co_person_id'] = $data['CoPetition']['enrollee_co_person_id'];
      
      $args['joins'][0]['table'] = 'identifiers';
      $args['joins'][0]['alias'] = 'uid';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'EmailAddress.mail=uid.identifier';

      $args['contain'] = false;
      $mail = $this->EmailAddress->find('first', $args);

      if (empty($mail)) {
        CakeLog::info("[Finalize] No matching MAIL+UID entry found, so skipping further processing!");
      } else {

        CakeLog::info("[Finalize] All conditions OK, EMAIL+UID found: " . print_r($mail, true));
        try {
          $txn = $this->Co->CoPerson->getDataSource();
          $txn->begin();

          // Make the person 'active' member of the CO...
          $role = array();
          $role['CoPersonRole']['co_person_id'] = $data['CoPetition']['enrollee_co_person_id'];
          $role['CoPersonRole']['affiliation'] = AffiliationEnum::Member;
          $role['CoPersonRole']['status'] = StatusEnum::Active;

          $this->Co->CoPerson->CoPersonRole->create($role);
          $this->Co->CoPerson->CoPersonRole->save($role, array('provision' => false));

          $txn->commit();

        } catch(Exception $e) {

          $txn->rollback();

          $eclass = get_class($e);
          CakeLog::error("Error finalizing enrollment: " . $e->getMessage());

          throw new $eclass($e->getMessage());
        }
      }
    }

    $this->redirect($onFinish);
  }
}
