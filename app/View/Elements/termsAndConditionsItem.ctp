<strong><?php print filter_var($t['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></strong><br/>
<button class="checkbutton"
        type="button"
        onClick="open_tandc('<?php print addslashes($t['description']); ?>',
                '<?php print addslashes($t['url']); ?>',
                '<?php print addslashes($t['id']); ?>')">
  <?php print _txt('op.tc.review'); ?>
</button>
<?php

  $fieldName = "CoTermsAndConditions." . $t['id'];
  $args = array();
  // By default, this would be "on". For now (as of v4.0.0) the actual value
  // doesn't matter.
  $args['value'] = _txt('fd.tc.agree.yes');

  if($vv_tandc_mode == TAndCEnrollmentModeEnum::ExplicitConsent) {
    // Explicit consent, render a checkbox
    $args['onClick'] = "maybe_enable_submit()";
  } elseif($vv_tandc_mode == TAndCEnrollmentModeEnum::ImpliedConsent) {
    // Implicit consent, render as hidden fields instead
    $args['checked'] = true;
    // Disabling the checkbox prevents the value from POSTing, and there
    // is no READONLY attribute on a checkbox. A hack is to use javascript.
    // A better options would be to use a hidden field here, but that is
    // triggering a blackhole on submit. Processing entirely server side
    // is also problematic in the edge case where a new T&C is added after
    // the form is rendered.
    //$args['disabled'] = true;
    $args['onClick'] = "return false";
  }
  print '<span class="tc-checkbox-and-label">';
  print $this->Form->checkbox($fieldName, $args);
  print $this->Form->label($fieldName, _txt('op.tc.agree.i'));
  print '</span>';
?>