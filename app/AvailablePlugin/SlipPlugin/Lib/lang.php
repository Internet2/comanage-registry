<?php
/**
 * COmanage Registry SLIP Plugin Language File
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
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_slip_plugin_texts['en_US'] = array (
  // Titles, per-controller
  'ct.slip_attachments.1'         => 'Attachment',
  'ct.slip_attachments.pl'        => 'Attachments',
  'ct.slip_bindings.1'            => 'Authenticator Binding',
  'ct.slip_bindings.pl'           => 'Authenticator Bindings',
  'ct.slip_identity_documents.1'  => 'Identity Document',
  'ct.slip_identity_documents.pl' => 'Identity Documents',
  'ct.slip_plugins.1'             => 'Simple Lightweight Identity Proofer',
  'ct.slip_plugins.pl'            => 'Simple Lightweight Identity Proofers',
  'ct.slip_proofings.1'           => 'Identity Proofing Request',
  'ct.slip_proofings.pl'          => 'Identity Proofing Requests',

  // Enumeration language texts
  'pl.slipplugin.en.binding' => array(
    SlipBindingStatusEnum::Canceled             => 'Canceled',
    SlipBindingStatusEnum::Confirmed            => 'Confirmed',
    SlipBindingStatusEnum::NotStarted           => 'Not Started',
    SlipBindingStatusEnum::PendingVerification  => 'Pending Verification',
    SlipBindingStatusEnum::Revoked              => 'Revoked'
  ),

  'pl.slipplugin.en.status' => array(
    SlipProofingStatusEnum::Assigned            => 'Assigned',
    SlipProofingStatusEnum::Canceled            => 'Canceled',
    SlipProofingStatusEnum::Completed           => 'Completed',
    SlipProofingStatusEnum::Initiated           => 'Initiated',
    SlipProofingStatusEnum::PendingVouching     => 'Pending Vouching',
    SlipProofingStatusEnum::Rejected            => 'Rejected',
    SlipProofingStatusEnum::Revoked             => 'Revoked',
    SlipProofingStatusEnum::Scheduled           => 'Scheduled',
    SlipProofingStatusEnum::Vouched             => 'Vouched'
  ),
  
  // Error messages
  'er.slipplugin.accept'              => 'Identity Proofing Request cannot be assigned to self',
  'er.slipplugin.bind'                => 'Requested Binding %1$s is not a valid Binding request',
  'er.slipplugin.bind.code'           => 'Invalid code',
  'er.slipplugin.bind.status'         => 'Binding is not Pending Verification (status: %1$s)',
  'er.slipplugin.calculate'           => 'Identity Proofing Not Complete',
  'er.slipplugin.cxl'                 => 'Identity Proofing Request is not in a cancelable state',
  'er.slipplugin.mail'                => 'There are no Email Addresses associated with this CO Person',
  'er.slipplugin.request.id'          => 'Requested Identifier is not available for Authenticator Binding',
  'er.slipplugin.schedule'            => 'Identity Proofing Request cannot be scheduled',
  'er.slipplugin.start.inprogress'    => 'Request %1$s is in progress, a new request cannot be started',
  'er.slipplugin.vouch.assign'        => 'Identity Proofing Request cannot be assigned for vouching',
  'er.slipplugin.vouch.status'        => 'Identity Proofing Request is not Pending Vouching',
  
  // Plugin texts
  'pl.slipplugin.accept'                            => 'Assign to Self',
  'pl.slipplugin.accepted.history'                  => 'Identity Proofing request %1$s assigned to %2%s',
  'pl.slipplugin.assigned'                          => 'Identity Proofing request assigned',
  'pl.slipplugin.assignment_time'                   => 'Registrar Assignment Time',
  'pl.slipplugin.attach'                            => 'Identity Documents',
  'pl.slipplugin.attached'                          => 'Identity Document attached',
  'pl.slipplugin.authenticated_identifier'          => 'Login Identifier',
  'pl.slipplugin.bind'                              => 'Authenticator Bindings',
  'pl.slipplugin.bind.code'                         => '<p>Please read the following code to your Registrar: %1$s</p> <p>After the Registrar confirms the code, you may close this tab or window.</p>',
  'pl.slipplugin.calculate'                         => 'Validate Proofing Status',
  'pl.slipplugin.calculate.ok'                      => 'Identity Proofing Complete',
  'pl.slipplugin.cancel.confirm'                    => 'Are you sure you want to cancel this Identity Proofing request?',
  'pl.slipplugin.canceled'                          => 'Identity Proofing request canceled',
  'pl.slipplugin.canceled.history'                  => 'Identity Proofing request %1$s canceled',
  'pl.slipplugin.code'                              => 'Code',
  'pl.slipplugin.co_person_id'                      => 'Claimant',
  'pl.slipplugin.completed'                         => 'Identity Proofing request completed',
  'pl.slipplugin.completed.history'                 => 'Identity Proofing request %1$s completed',
  'pl.slipplugin.created'                           => 'Request Initiation Time',
  'pl.slipplugin.identity_documents.desc'           => 'The underlying Identity Document attached directly to the CO Person record. (This link is only available to CO Administrators.)',
  'pl.slipplugin.initiated'                         => 'Identity Proofing request initiated. You will be contacted regarding next steps.',
  'pl.slipplugin.initiated.history'                 => 'Identity Proofing request %1$s initiated',
  'pl.slipplugin.initiator_co_person_id'            => 'Initiator',
  'pl.slipplugin.initiation_template_id'            => 'Initiation Message Template',
  'pl.slipplugin.initiation_template_id.desc'       => 'Message Template used for Registrar notification when Claimant initiates a request',
  'pl.slipplugin.proofing_exempt_co_group_id'       => 'Identity Proofing Exemption CO Group',
  'pl.slipplugin.proofing_valid_co_group_id'        => 'Identity Proofing Valid CO Group',
  'pl.slipplugin.proofing_valid_co_group_id.desc'   => 'CO People successfully proofed will be automatically added to this CO Group',
  'pl.slipplugin.registrar_co_group_id'             => 'Registrar CO Group',
  'pl.slipplugin.registrar_co_person_id'            => 'Registrar',
  'pl.slipplugin.rejected.history'                  => 'Identity Proofing request %1$s rejected',
  'pl.slipplugin.request'                           => 'Request Binding',
  'pl.slipplugin.request.notify'                    => 'Authenticator Binding request sent',
  'pl.slipplugin.request.js'                        => 'Send Authenticator Binding request for \x22{0}\x22?',
  'pl.slipplugin.schedule'                          => 'Schedule',
  'pl.slipplugin.scheduled'                         => 'Identity Proofing request scheduled',
  'pl.slipplugin.scheduled.history'                 => 'Identity Proofing request %1$s scheduled',
  'pl.slipplugin.scheduling_template_id'            => 'Scheduling Message Template',
  'pl.slipplugin.scheduling_template_id.desc'       => 'Message Template used for Claimant notification when Registrar schedules an interview',
  'pl.slipplugin.binding_template_id'               => 'Binding Message Template',
  'pl.slipplugin.binding_template_id.desc'          => 'Message Template used for Claimant credential binding request',
  'pl.slipplugin.vouching_template_id'              => 'Vouching Request Message Template',
  'pl.slipplugin.vouching_template_id.desc'         => 'Message Template used for Trusted Person verification request notification',
  'pl.slipplugin.approval_template_id'              => 'Approval Message Template',
  'pl.slipplugin.approval_template_id.desc'         => 'Message Template used for Claimaint notification on approval',
  'pl.slipplugin.reject'                            => 'Reject',
  'pl.slipplugin.resolution_time'                   => 'Resolution Time',
  'pl.slipplugin.result'                            => 'Overall Result',
  'pl.slipplugin.result.binding.complete'           => 'Authenticator Bindings completed',
  'pl.slipplugin.result.binding.incomplete'         => 'Authenticator Bindings not complete (%1$s done, %2$s not done)',
  'pl.slipplugin.result.binding.none'               => 'No Authenticator Bindings found',
  'pl.slipplugin.result.docs.complete'              => 'Valid Identity Document found',
  'pl.slipplugin.result.docs.none'                  => 'No Identity Documents found',
  'pl.slipplugin.result.proofing.complete'          => 'Identity Proofing request completed',
  'pl.slipplugin.result.proofing.incomplete'        => 'No completed Identity Proofing record found',
  'pl.slipplugin.result.proofing.none'              => 'No Identity Proofing records found',
  'pl.slipplugin.result.status'                     => 'Identity Proofing Status',
  'pl.slipplugin.result.vouching.complete'          => 'Vouching completed',
  'pl.slipplugin.result.vouching.incomplete'        => 'Vouching not completed',
  'pl.slipplugin.start'                             => 'This service will be used to verify your identity. Please click <i>Begin</i> below to start the process.',
  'pl.slipplugin.start.url'                         => 'The URL for a Claimant to start an Identity Proofing request is %1$s',
  'pl.slipplugin.upload'                            => 'Attachments',
  'pl.slipplugin.verification_time'                 => 'Verification Time',
  'pl.slipplugin.vouch'                             => 'Request Vouching',
  'pl.slipplugin.vouch.info'                        => 'Vouching',
  'pl.slipplugin.vouch.search.desc'                 => 'Use the search box to select the Voucher (use a name, email address, or identifier)',
  'pl.slipplugin.vouch.url'                         => 'Vouching URL',
  'pl.slipplugin.vouch_request_time'                => 'Vouching Request Time',
  'pl.slipplugin.vouch_time'                        => 'Vouching Completion Time',
  'pl.slipplugin.voucher_co_person_id'              => 'Voucher'
);
