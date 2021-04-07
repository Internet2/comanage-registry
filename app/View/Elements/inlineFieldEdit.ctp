<?php
?>
<div class="inline-edit-form" style="display: none;">
  <?php
  $mdl = Inflector::singularize($this->name);
  $mdl_tbl = Inflector::tableize($mdl);
  $mdl_pt = $$mdl_tbl;
  global $cm_lang, $cm_texts;


  // Create a PUT Form
  print $this->Form->create(
    $mdl,
    array(
      'default' => false,
      'inputDefaults' => array('div' => false),
      'url' => array(
        'action' => 'edit',
        $mdl_pt[0][$mdl]['id'],   // todo: Make the id optional
      ),
    )
  );
  print $this->Form->hidden($mdl . '.co_id', array('default' => $cur_co['Co']['id'])). PHP_EOL;

  $input_options = array(
    'label' => $label,
    'type' => $type,
    'class' => 'ml-1 vmiddle',
    'value' => $mdl_pt[0][$mdl][$field],
  );
  if ($type === "select") {
    $input_options['options'] = $cm_texts[$cm_lang]['en.' . $field];
    $input_options['value'] = $mdl_pt[0][$mdl][$field];
    $input_options['label'] = $label . ":";
    $input_options['aria-label'] = $label;
    if(!$empty) {
      $input_options['empty'] = $label . ' ' . _txt('op.select.empty');
    }
  }
  print $this->Form->input($field, $input_options);

  if($this->Form->isFieldError($field)) {
    print $this->Form->error($field);
  }
  // Submit
  $sr_edit = $this->Html->tag('span', 'edit', array('class' => 'sr-only'));
  print $this->Form->button(
    $this->Html->tag('i', $sr_edit, array('class' => 'fa fa-check')),
    array(
      'name'    => 'ok',
      'type'    => 'submit',
      'class'   => 'btn btn-primary ml-1 btn-sz9',
      'escape'  => false,
      'onclick' => 'javascript:update_field(this,'
        . '{'
        . '\'model\': \'' . $mdl . '\''
        . ', \'field\': \'' . $field . '\''
        . '});'
    )
  );
  // Cancel
  $sr_cancel = $this->Html->tag('span', 'cancel', array('class' => 'sr-only'));
  print $this->Form->button(
    $this->Html->tag('i', $sr_cancel, array('class' => 'fa fa-times')),
    array(
      'name'    => 'cancel',
      'type'    => 'submit',
      'class'   => 'btn btn-warning ml-1 btn-sz9',
      'escape'  => false,
      'onclick' => 'javascript:inline_edit(false);'
    )
  );
  print $this->Form->end();
  ?>
</div>