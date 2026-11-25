<?php
/**
 * COmanage Registry SLIP Attachment Model
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SlipAttachment extends AppModel {
  // Add behaviors
  // Note we are not Changelog behaviors because these documents are intended to
  // be transient and easy to purge
  public $actsAs = array('Containable');  //, 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SlipPlugin.SlipProofing"
  );

  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'slip_proofing_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'mime_type' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'file_contents' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => true,
        'allowEmpty' => false
      )
    )
  );

  // Image types we allow (and the only ones the vouch view will render)
  const ALLOWED_MIME_TYPES = array(
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
  );

  /**
   * Add a SLIP Attachment from a file upload.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  int    SLIP Proofing ID
   * @param  string Description
   * @param  array  Array of file upload info (as provided by Cake/PHP)
   * @return int    SLIP Attachment ID
   * @throws RuntimeException
   */

  public function addFromUpload($slipProofingId, $description, $uploadData) {
    if(empty($uploadData['tmp_name'])
      || (isset($uploadData['error']) && $uploadData['error'] === UPLOAD_ERR_NO_FILE)) {
      throw new RuntimeException(_txt('er.file.none.size',array(ini_get('upload_max_filesize'))));
    }

    // Check for upload errors. Size is enforced by PHP's upload_max_filesize.
    if(isset($uploadData['error']) && $uploadData['error'] !== UPLOAD_ERR_OK) {
      if(in_array($uploadData['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)) {
        throw new InvalidArgumentException(_txt('er.slipplugin.file.size', array(ini_get('upload_max_filesize'))));
      }

      throw new RuntimeException(_txt('er.file.read', array($uploadData['name'])));
    }

    // Reject empty files
    if(empty($uploadData['size'])) {
      throw new InvalidArgumentException(_txt('er.slipplugin.file.empty', array($uploadData['name'])));
    }

    // Determine the MIME type from the file contents, not the browser-supplied type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($uploadData['tmp_name']);

    if(!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
      throw new InvalidArgumentException(_txt('er.slipplugin.file.type', array($mimeType)));
    }

    $contents = file_get_contents($uploadData['tmp_name']);

    if(!$contents) {
      throw new InvalidArgumentException(_txt('er.file.read', array($uploadData['name'])));
    }

    $attachment = array(
      'slip_proofing_id'  => $slipProofingId,
      'description'       => $description,
      'mime_type'         => $mimeType,
      'file_contents'     => $contents
    );

    $this->clear();

    if(!$this->save($attachment)) {
      throw new RuntimeException(_txt('er.db.save'));
    }

    return $this->id;
  }

  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v4.7.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // SlipBindings refer to SlipProofings which refer to SlipPlugins

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'] = array('SlipProofing' => array('SlipPlugin'));

    $sid = $this->find('first', $args);

    if(!empty($sid['SlipProofing']['SlipPlugin']['co_id'])) {
      return $sid['SlipProofing']['SlipPlugin']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
}
