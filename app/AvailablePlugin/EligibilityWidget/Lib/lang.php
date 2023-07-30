<?php
/**
 * COmanage Registry Eligibility Dashboard Widget Plugin Language File
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_eligibility_widget_texts['en_US'] = array(
  'ct.eligibility_widgets.1'                          => 'Eligibility Widget',
  'ct.eligibility_widgets.pl'                         => 'Eligibility Widgets',
  'ct.ois_registrations.1'                            => 'Organization Identity Source',
  'ct.ois_registrations.pl'                           => 'Organization Identity Sources',

  // Error
  // Eligibility Widget
  'pl.er.eligibilitywidget.id.specify'                => 'Id was not found',
  'pl.er.eligibilitywidget.param.notfound'            => '%1$s was not found',
  'pl.er.eligibilitywidget.ois.inappropriate'         => 'Organizational Identity Source does not match the requirements',
  'pl.er.eligibilitywidget.ois.norecord'              => 'No record found',
  'pl.er.eligibilitywidget.remove'                    => 'Removal failed.',
  'pl.er.eligibilitywidget.req.copersonid'            => 'Co Person Id not found',
  'pl.er.eligibilitywidget.no.match-1'                => 'We failed to match your %1$s email.',
  'pl.er.eligibilitywidget.no.match'                  => 'We failed to match some of your email addresses.',
  // OIS Registration
  'pl.er.ois_registration.cewid.specify'              => 'Named parameter cewid was not found',

  // Actions
  'pl.op.eligibilitywidget.membership.add'            => 'Add Membership',
  'pl.op.eligibilitywidget.membership.edit'           => 'Edit Membership',
  'pl.op.eligibilitywidget.membership.add-a'          => 'Add Membership %1$s',
  'pl.op.eligibilitywidget.sync'                      => 'Sync',

  // Fields
  'pl.eligibilitywidget.fd.mode'                       => 'Registration Mode',
  'pl.eligibilitywidget.fd.mode.desc'                  => 'Pick the registration mode',
  'pl.eligibilitywidget.fd.name.val'                   => 'COU Name: %1$s',
  'pl.ois_registration.ois.desc'                       => 'Pick the Organizational Identity Source that will be used to query the user',
  'pl.ois_registration.desc.desc'                      => 'Provide a friendly name to use on widget render',
  'pl.ois_registration.order.desc'                     => 'The order in which this Ogranization Identity Source will be processed',

  //  Modal
  'pl.eligibilitywidget.modal.body.add.success'        => 'The item was added.',
  'pl.eligibilitywidget.modal.body.remove'             => 'Are you sure you want to remove this item?',
  'pl.eligibilitywidget.modal.body.remove.success'     => 'The email address was removed.',
  'pl.eligibilitywidget.modal.title.add.success'       => 'Item added',
  'pl.eligibilitywidget.modal.title.remove'            => 'Remove item?',
  'pl.eligibilitywidget.modal.title.remove.success'    => 'Item removed',
);
