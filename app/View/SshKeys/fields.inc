<?php
/**
 * COmanage Registry SSH Keys Fields
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  $idForBreadcrumbs = null;

  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonid'])) {
    print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
  } elseif(isset($this->request->data['SshKey']['co_person_id'])) {
    print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->data['SshKey']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = $this->request->data['SshKey']['co_person_id'];
  }

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);

  if($idForBreadcrumbs) {
    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $idForBreadcrumbs
    );
    $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
  }
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.ssh_keys.1')));
  $this->Html->addCrumb($crumbTxt);

?>
<?php if($e): ?>
<script>
  $(document).ready(function() {
    $('#edit_ssh_key .ssh_key-skey').on('change',function() {      
        // check to see if the pasted text by chance contains a full SSH authorized key,
        // including a type and a comment. If so, parse it and set the comment and type
        // fields instead
        var value = $.trim($(this).val());

        var typeToCO = {
          "ssh-dss": <?php echo json_encode(SshKeyTypeEnum::DSA); ?>,
          "ssh-rsa": <?php echo json_encode(SshKeyTypeEnum::RSA); ?>,
          "ssh-ed25519": <?php echo json_encode(SshKeyTypeEnum::ED25519); ?>,
          "ecdsa-sha2-nistp256": <?php echo json_encode(SshKeyTypeEnum::ECDSA); ?>
        };

        if(value.indexOf(' ') !== -1) {

          var values=value.split(' ');
          var type = values[0];
          var key = values.length > 1 ? values[1] : '';
          var comment = values.length > 2 ? values[2] : '';
          
          // check the type and key
          // type should be one of the ssh-<keytype> fields and key should
          // only contain base64 characters
          if(typeToCO[type]) {
            $('#edit_ssh_key .ssh_key-comment').val(comment);
            $('#edit_ssh_key .ssh_key-type').val(typeToCO[type]);
            $('#edit_ssh_key .ssh_key-skey').val(key);
          }          
        }
        else if(value.match(/^[A-Za-z0-9\+\/]+=*$/) !== null) {

          // no spaces, but we can determine the type on the base64 encoded key
          var type=null;
          if(value.indexOf('AAAAB3NzaC1kc3M') === 0) {
            // ssh-dss => DSA
            type='ssh-dss';
          }
          else if(value.indexOf('AAAAB3NzaC1yc2E') === 0) {
            // ssh-rsa => RSA
            type='ssh-rsa';
          } 
          else if(value.indexOf('AAAAC3NzaC1lZDI1NTE5') === 0) {
            // ssh-ed25519
            type='ssh-ed25519';
          } 
          else if(value.indexOf('AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYA') === 0) {
            // ecdsa-sha2-nistp256
            type='ecdsa-sha2-nistp256';
          }
          if(type !== null) {
            $('#edit_ssh_key ssh_key-type').val(typeToCO[type]);
          }
        }
    });
  });
</script>
<?php endif; ?>
<ul id="<?php print $this->action; ?>_ssh_key" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.sshkey.comment'); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('comment', array('class' => 'focusFirst ssh_key-comment')) : filter_var($ssh_keys[0]['SshKey']['comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.sshkey.type'); ?>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs['value'] = (isset($ssh_keys) ? $ssh_keys[0]['SshKey']['type'] : SshKeyTypeEnum::DSA);
        $attrs['empty'] = false;
        $attrs['class']='ssh_key-type';

        if($e) {
          print $this->Form->select('type',
                                    $cm_texts[ $cm_lang ]['en.sshkey.type'],
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print filter_var($contact_t[ $ssh_keys[0]['SshKey']['type'] ],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('fd.sshkey.skey'); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs=array();
        $attrs['class']='ssh_key-skey';
 
        print ($e
                   ? $this->Form->input('skey',$attrs)
                   : filter_var($ssh_keys[0]['SshKey']['skey'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php if($this->action == 'add' && $e): ?>
  <hr />
  <h2><?php print _txt('op.upload.new', array(_txt('ct.ssh_keys.1'))); ?></h2>

  <ul id="<?php print $this->action; ?>_ssh_key_upload" class="fields form-list">
    <li>
      <div class="field-name">
        <?php print _txt('fd.sshkey.skey'); ?>
      </div>
      <div class="field-info">
        <?php
          // Close the previous form
          // XXX Shouldn't do this; instead, pass this page through canvas to avoid a global form wrapper and
          // then define each form separately in this file
          print $this->Form->end();

          // Start a new form (this will get closed by Standard/add/edit.ctp)
          print $this->Form->create('SshKey', array('type' => 'file',
                                                    'url' => array('action' => 'addKeyFile')));
          
          // Since we started a new form, we need to re-insert the coperson id

          if(isset($this->request->params['named']['copersonid']))
            print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
          elseif(isset($this->request->data['SshKey']['co_person_id']))
            print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->data['SshKey']['co_person_id'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
          
          print $this->Form->file('SshKey.keyFile');
        ?>
      </div>
    </li>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit(_txt('op.upload')); ?>
      </div>
    </li>
  </ul>
<?php endif; // add ?>

<?php
  print $this->element("changelog");
