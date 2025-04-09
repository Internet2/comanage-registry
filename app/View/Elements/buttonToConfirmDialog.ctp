<?php

/**
 * COmanage Registry Button To Confirmation Dialog
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
 * @since         COmanage Registry v4.5.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

if (empty($data)) {
    print 'No configuration.' . PHP_EOL;
}

?>

<script>
  let jsBodyTxtReplacementArray = [];
  <?php if (!empty($data['bodyTxtReplacementStrings']) && is_array($data['bodyTxtReplacementStrings'])): ?>
  jsBodyTxtReplacementArray = <?= json_encode($data['bodyTxtReplacementStrings'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  <?php endif; ?>

</script>

<button type="button"
        class="deletebutton"
        title="<?= $data['btnTitle'] ?>"
        onclick="javascript:js_confirm_generic(
            '<?= $data['bodyText'] ?>',
            '<?= $this->Html->url($data['confirmUrl']) ?>',
            '<?= $data['action'] ?>',
            '<?= _txt('op.cancel') ?>',
            '<?= $data['dialogTitle'] ?>',
            jsBodyTxtReplacementArray,
            '<?= $data['checkBoxText'] ?? null ?>'
        )">
    <?= $data['action'] ?>
</button>
