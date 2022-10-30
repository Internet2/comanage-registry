<?php
/**
 * COmanage Registry Kafka Server Fields
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;

  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'index';
  $args['co'] = $kafka_servers[0]['Server']['co_id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'edit';
  $args[] = $kafka_servers[0]['Server']['id'];
  $this->Html->addCrumb($kafka_servers[0]['Server']['description'], $args);
  
  $this->Html->addCrumb(_txt('op.config-a', array($kafka_servers[0]['Server']['description'])));
  
  print $this->Form->hidden('server_id', array('default' => $kafka_servers[0]['Server']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_kafka_server" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('brokers',_txt('fd.server.kafka.brokers')); ?><span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.server.kafka.brokers.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('brokers', array('class' => 'focusFirst')) : filter_var($kafka_servers[0]['KafkaServer']['brokers'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('security_protocol', _txt('fd.server.kafka.security_protocol')) : _txt('fd.server.kafka.security_protocol')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        // Cake will correctly autopopulate this
        //  $attrs['value'] = (isset($kafka_servers) ? $kafka_servers[0]['KafkaServer']['security_protocol'] : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('security_protocol',
                                    array_combine($vv_security_protocols, $vv_security_protocols),
                                    $attrs);

          if($this->Form->isFieldError('security_protocol')) {
            print $this->Form->error('security_protocol');
          }
        } else {
          print $kafka_servers[0]['Server']['security_protocol'];
        }
      ?>
    </div>
  </li>
  <li>
    <!-- XXX could hide this if security_protocol is not sasl_ssl? -->
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('sasl_mechanism', _txt('fd.server.kafka.sasl_mechanism')) : _txt('fd.server.kafka.sasl_mechanism')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        // Cake will correctly autopopulate this
        //  $attrs['value'] = (isset($kafka_servers) ? $kafka_servers[0]['KafkaServer']['sasl_mechanism'] : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('sasl_mechanism',
                                    array_combine($vv_sasl_mechanisms, $vv_sasl_mechanisms),
                                    $attrs);

          if($this->Form->isFieldError('sasl_mechanism')) {
            print $this->Form->error('sasl_mechanism');
          }
        } else {
          print $kafka_servers[0]['Server']['sasl_mechanism'];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('username',_txt('fd.server.username')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('username') : filter_var($kafka_servers[0]['KafkaServer']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('password',_txt('fd.password')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($kafka_servers[0]['KafkaServer']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.kafka.groupid'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('groupid', array('size' => 50)) : filter_var($kafka_servers[0]['KafkaServer']['groupid'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.kafka.topic'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('topic', array('size' => 50)) : filter_var($kafka_servers[0]['KafkaServer']['topic'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.kafka.batch_size'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('batch_size', array('default' => 10)) : $kafka_servers[0]['KafkaServer']['batch_size']); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.kafka.partition'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('partition', array('default' => 0)) : $kafka_servers[0]['KafkaServer']['partition']); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.kafka.timeout'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('timeout', array('default' => 30)) : $kafka_servers[0]['KafkaServer']['timeout']); ?>
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