<?php
// eg identifier
$lmvep = strtolower($mvep_model);
$lmveppl = Inflector::tableize($mvep_model);

// eg co_people
$lmpl = Inflector::tableize($model);

$action = ($edit ? 'edit' : 'view');
$lorder = ($edit ? $this->Menu->getMenuOrder('Edit') : $this->Menu->getMenuOrder('View'));
$action_icon = ($edit ? $this->Menu->getMenuIcon('Edit') : $this->Menu->getMenuIcon('View'));

?>
<li id="fields-<?php print $lmvep; ?>" class="fieldGroup">
  <div class="fieldGroupNameContainer">
    <a href="#tabs-<?php print $lmvep; ?>" class="fieldGroupName" title="<?php print _txt('op.collapse') ?>" aria-expanded="true" aria-controls="tabs-<?php print $lmvep; ?>">
      <em class="material-icons" aria-hidden="true">expand_less</em>
      <h2><?php print _txt('ct.'.$lmveppl.'.pl'); ?></h2>
    </a>
    <div class="coAddEditButtons">
      <?php
        // Render the add button
        $linktarget = array(
          'controller'    => $lmveppl,
          'action'        => 'add',
          $model_param    => ${$lmpl}[0][$model]['id']
        );
        $linkparams = array('class' => 'addbutton');

        print $this->Html->link(_txt('op.add'), $linktarget, $linkparams);
      ?>
    </div>
  </div>
    <ul id="tabs-<?php print $lmvep; ?>" class="fields data-list">
      <?php
        // Loop through each record and render
        if(!empty(${$lmpl}[0][$mvep_model])) {
          // Sort by the ordr column
          $sorted_data = Hash::sort(${$lmpl}[0][$mvep_model], "{n}.ordr", 'asc', 'numeric');
          foreach($sorted_data as $m) {
            $editable = ($action == 'edit');
            $removetxt = _txt('js.remove');
            $displaystr = (!empty($mvep_field) ? $m[$mvep_field] : "");
            // Append the COU Name
            $displaystr .= ' <cite class="text-muted-cmg cm-id-display">'
                            . _txt('pl.eligibilitywidget.fd.name.val', array($m["OrgIdentitySource"]["CoPipeline"]["SyncCou"]["name"]))
                            . '</cite>';
            $laction = $action;
            // Store the action list
            $action_args = array();
            $action_args['vv_attr_mdl'] = $mvep_model;
            $action_args['vv_attr_id'] = $m["id"];
            // Store the Bagde list
            $badge_list = array();

            // Lookup the extended type friendly name, if set
            if(!empty($m['type']) && isset(${$vv_dictionary}[ $m['type'] ])) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Type'),
                'text' => ${$vv_dictionary}[ $m['type'] ],
                'color' => $this->Badge->getBadgeColor('Light'),
              );
            } elseif (!empty($m['type'])) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Type'),
                'text' => $m['type'],
                'color' => $this->Badge->getBadgeColor('Light'),
              );
            }

            // Add a suspended badge, if appropriate
            if(isset($m['status']) && $m['status'] == SuspendableStatusEnum::Suspended) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Status'),
                'text' => _txt('en.status.susp', null, SuspendableStatusEnum::Suspended),
                'color' => $this->Badge->getBadgeColor('Danger'),
              );
            }

            if(isset($m['ordr'])) {
              $badge_list[] = array(
                'order' => $this->Badge->getBadgeOrder('Other'),
                'text' => _txt('pl.elector_data_filter_precedence.order', array($m['ordr'])),
                'color' => $this->Badge->getBadgeColor('Light'),
              );
            }


            // If $mvpa_format is a defined function, use that to render the display string
            if(!empty($mvpa_format) && function_exists($mvpa_format)) {
              $displaystr = $mvpa_format($m);
            }

            print '<li class="field-data-container">';
            print '<div class="field-data force-wrap">';
            // Render the text link
            print $this->Html->link($displaystr,
                                    array('controller' => $lmveppl,
                                          'action' => $laction,
                                      $m['id']),
                                    array(
                                      'escape' => false,
                                      'class' => ($laction == 'view') ? 'lightbox' : ''),
                                    );
            print '</div>';
            print '<div class="field-data data-label">';
            if(!empty($badge_list)) {
              print $this->element('badgeList', array('vv_badge_list' => $badge_list));
            }
            print '</div>';
            print '<div class="field-actions">';
            // Render specific buttons
            $action_args['vv_actions'][] = array(
              'order' => $lorder,
              'icon' => $action_icon,
              'lightbox' => (($laction === "view") ? true : false),
              'url' => $this->Html->url(
                array(
                  'controller' => $lmveppl,
                  'action' => $laction,
                  $m['id'])
              ),
              'label' => _txt('op.'.$laction),
            );

            // Possibly render a delete button
            if($laction == 'edit' && $editable) {
              // XXX we already checked for $permissions['edit'], but not ['delete']... should we?
              $dg_url = array(
                'controller' => $lmveppl,
                'action' => 'delete',
                $m['id'],
                '?' => array(
                  'cewid' => $m['co_eligibility_widget_id']
                )
              );
              // Delete button
              $action_args['vv_actions'][] = array(
                'order' => $this->Menu->getMenuOrder('Delete'),
                'icon' => $this->Menu->getMenuIcon('Delete'),
                'url' => 'javascript:void(0);',
                'label' => _txt('op.delete'),
                'onclick' => array(
                  'dg_bd_txt' => $removetxt,
                  'dg_url' => $this->Html->url($dg_url),
                  'dg_conf_btn' => _txt('op.remove'),
                  'dg_cancel_btn' => _txt('op.cancel'),
                  'dg_title' => _txt('op.remove'),
                  'db_bd_txt_repl_str' => filter_var(_jtxt($displaystr),FILTER_SANITIZE_STRING),
                ),
              );
            }

            if(!empty($action_args['vv_actions'])) {
              print $this->element('menuAction', $action_args);
            }
            print '</div>';
            print '</li>';
          }
        }
      ?>
    </ul><!-- tabs-<?php print $lmvep; ?> -->
  </li><!-- fields-<?php print $lmvep; ?> -->