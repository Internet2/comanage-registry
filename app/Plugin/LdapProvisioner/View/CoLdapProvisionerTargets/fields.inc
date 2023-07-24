<!--
/**
 * COmanage Registry CO LDAP Provisioning Target Fields
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<script type="text/javascript">
  function js_check_group_config() {
    // If groupOfNames objectclass is defined but Base Group DN is empty, throw an error
    
    if(document.getElementById('CoLdapProvisionerTargetOcGroupofnames').checked) {
      if(document.getElementById('CoLdapProvisionerTargetGroupBasedn').value == "") {
        $("#error-dialog").dialog("open");
        return false;
      }
    }
    
    return true;
  }
  
  function js_local_onload() {
    <?php
      // Emit javascript for each objectclass div
      
      foreach(array_keys($supportedAttributes) as $oc) {
        if(!$supportedAttributes[$oc]['objectclass']['required']) {
          if(isset($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['oc_'.strtolower($oc)])
             && $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['oc_'.strtolower($oc)]) {
            print '$("#oc_' . $oc . '_div").show();';
          } else {
            print '$("#oc_' . $oc . '_div").hide();';
          }
        }
      }
      
      // And set the DN attribute name if empty (we have to do it this way since
      // we're referencing an extended type and we won't know what the default value is)
      
      if(empty($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['dn_attribute_name'])) {
        print "set_dn_attribute_name()";
      }
    ?>
  }
  
  function set_dn_attribute_name() {
    // Populate the DN attribute name field based on the current value of the DN identifier type
    document.getElementById('dnIdentifierName').value = document.getElementById('dnIdentifierType').value;
  }
  
  function toggle_div(div) {
    $("#" + div).toggle("slide", { "direction" : "up" });
  }
    
  $(function() {
    // Error dialog
    
    $("#error-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        },
      },
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });
  });
</script>
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
  
  // Determine the starting index for newly emitted attributes and groupings
  $newattri = count($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute']);
  $newgri = count($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttrGrouping']);
  
  // Rekey the attributes array on object class and attribute name
  $currentAttributes = array();
  
  for($i = 0;$i < $newattri;$i++) {
    if(!empty($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i]['attribute'])) {
      $attr = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i]['attribute'];
      $oc = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i]['objectclass'];
      $currentAttributes[$oc][$attr] = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i];
      $currentAttributes[$oc][$attr]['_index'] = $i;
    }
  }
  
  // Rekey groupings as well
  $currentGroupings = array();
  
  for($i = 0;$i < $newgri;$i++) {
    if(!empty($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttrGrouping'][$i]['grouping'])) {
      $grouping = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttrGrouping'][$i]['grouping'];
      $currentGroupings[$grouping] = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttrGrouping'][$i];
      $currentGroupings[$grouping]['_index'] = $i;
    }
  }
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.ldapprovisioner.info'); ?>
  </div>
</div>

<ul id="<?php print $this->action; ?>_co_ldap_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.serverurl'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.serverurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.binddn'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.binddn.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['binddn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.password'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.password.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.dntype'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.dntype.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $xval = "";
        
        if(isset($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['dn_identifier_type'])) {
          $xval = $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['dn_identifier_type'];
        }
        
        print ($e ? $this->Form->select('dn_identifier_type',
                                        $identifier_types,
                                        array('id' => 'dnIdentifierType',
                                              'value' => $xval,
                                              'empty' => false,
                                              'onchange' => 'set_dn_attribute_name()'))
          : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['dn_identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.dnattr'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.dnattr.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('dn_attribute_name',
                                           array('id' => 'dnIdentifierName'))
        : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['dn_attribute_name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.basedn'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.basedn.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['basedn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.basedn.gr'); ?></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.basedn.gr.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('group_basedn', array('size' => 50)) : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['group_basedn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.scope'); ?></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.scope.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('scope_suffix', array('size' => 50)) : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['scope_suffix'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.uam'); ?><span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.uam.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = (isset($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['unconf_attr_mode'])
          ? $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['unconf_attr_mode']
          : LdapProvUnconfAttrEnum::Remove);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('unconf_attr_mode',
            $cm_texts[ $cm_lang ]['en.ldapprovisioner.unconfattr'],
            $attrs);

          if($this->Form->isFieldError('unconf_attr_mode')) {
            print $this->Form->error('unconf_attr_mode');
          }
        } else {
          print _txt('en.ldapprovisioner.unconfattr', null, $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['unconf_attr_mode']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.opts'); ?></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.opts.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        if($e) {
          print $this->Form->input('attr_opts', array('label' => _txt('pl.ldapprovisioner.opts')));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.attrs'); ?> <span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.attrs.desc'); ?></div>
    </div>
    <div class="field-info"><!-- do not apply class "checkbox" here -->
      <?php
        if($e) {
          global $cm_lang, $cm_texts;
          $seenGroupings = array();

          // Iterate through the supported object classes

          foreach(array_keys($supportedAttributes) as $oc) {
            // Checkbox to enable this objectclass

            $ocopts = array();
            $ocopts['label'] = _txt('pl.ldapprovisioner.oc.enable', array($oc));
            $ocopts['onClick'] = 'toggle_div("oc_' . $oc . '_div")';

            if($supportedAttributes[$oc]['objectclass']['required']) {
              $ocopts['label'] .= " (" . _txt('fd.required') . ")";
              $ocopts['checked'] = true;
              $ocopts['disabled'] = true;
            }

            print $this->Form->input('oc_' . strtolower($oc), $ocopts) . "<br />";

            print '<div id="oc_' . $oc . '_div" class="nested">';
            
            if($oc == 'posixAccount') {
              // Special case for handling UnixClusters
              
              if(empty($vv_clusters)) {
                // No (Unix) Clusters defined, print a warning and close the div
                print _txt('er.ldapprovisioner.unix.none') . '</div>';
                continue;
              }
              
              $attrs = array();
              $attrs['value'] = (isset($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['cluster_id'])
                                 ? $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['cluster_id']
                                 : null);
              $attrs['empty'] = false;
              
              print _txt('ct.clusters.1');
              
              print $this->Form->select('cluster_id',
                                        $vv_clusters,
                                        $attrs);
              
              if($this->Form->isFieldError('cluster_id')) {
                print $this->Form->error('cluster_id');
              }
              
              print "<br />";
            }

            foreach(array_keys($supportedAttributes[$oc]['attributes']) as $attr) {
              // Make sure the attributes map to the indexing created by Cake
              $xindex = -1;

              if(isset($currentAttributes[$oc][$attr]['_index'])) {
                $xindex = $currentAttributes[$oc][$attr]['_index'];
              } else {
                // New attribute, get the next index number
                $xindex = $newattri;
                $newattri++;
              }

              $xprefix = 'CoLdapProvisionerAttribute.' . $xindex;

              // If this is a grouping we haven't seen yet, emit the appropriate elements

              if(!empty($supportedAttributes[$oc]['attributes'][$attr]['grouping'])
                 && !isset($seenGroupings[ $supportedAttributes[$oc]['attributes'][$attr]['grouping'] ])) {
                $grouping = $supportedAttributes[$oc]['attributes'][$attr]['grouping'];
                $xgindex = -1;

                if(isset($currentGroupings[$grouping]['_index'])) {
                  $xgindex = $currentGroupings[$grouping]['_index'];
                } else {
                  // New grouping, get the next index number
                  $xgindex = $newgri;
                  $newgri++;
                }

                $xgprefix = 'CoLdapProvisionerAttrGrouping.' . $xgindex;

                // If we're editing an existing grouping, emit the cross reference

                if(!empty($currentGroupings[$grouping]['id'])) {
                  print $this->Form->hidden($xgprefix . '.id') . "\n";
                }

                // Grouping name for form submission
                print $this->Form->hidden($xgprefix . '.grouping', array('default' => $grouping)) . "\n";

                // Grouping label for display
                print "&nbsp;&nbsp;" . $supportedAttributes[$oc]['groupings'][$grouping]['label'];

                // See if Grouping is typed
                $xtypeopts = null;

                if(isset($supportedAttributes[$oc]['groupings'][$grouping]['extendedtype'])) {
                  $xtypevar = $supportedAttributes[$oc]['groupings'][$grouping]['extendedtype'];
                  $xtypeopts = $$xtypevar;  // Set by the controller
                } elseif(isset($supportedAttributes[$oc]['groupings'][$grouping]['typekey'])) {
                  $xtypekey = $supportedAttributes[$oc]['groupings'][$grouping]['typekey'];
                  $xtypeopts = $cm_texts[ $cm_lang ][ $xtypekey ];
                }

                if($xtypeopts) {
                  $xval = "";
                  $xempty = $supportedAttributes[$oc]['groupings'][$grouping]['multiple']
                            ? _txt('pl.ldapprovisioner.types.all') : false;
                  $xrequired = !(boolean)$xempty;

                  if(isset($currentGroupings[$grouping]['type'])) {
                    $xval = $currentGroupings[$grouping]['type'];
                  } elseif(isset($supportedAttributes[$oc]['groupings'][$grouping]['defaulttype'])) {
                    $xval = $supportedAttributes[$oc]['groupings'][$grouping]['defaulttype'];
                  }

                  print " " . $this->Form->select($xgprefix . '.type',
                                                  $xtypeopts,
                                                  array('value'    => $xval,
                                                        'empty'    => $xempty,
                                                        'required' => $xrequired));
                }

                print "<br />\n";

                // Mark this grouping as having been seen
                $seenGroupings[ $supportedAttributes[$oc]['attributes'][$attr]['grouping'] ] = true;
              }

              // If we're editing an existing attribute, emit the cross reference

              if(!empty($currentAttributes[$oc][$attr]['id'])) {
                print $this->Form->hidden($xprefix . '.id' /*,array('default' => $currentAttributes[$oc][$attr]['id'])*/) . "\n";
              }

              // Emit the attribute name

              print $this->Form->hidden($xprefix . '.attribute', array('default' => $attr)) . "\n";

              // Emit the associated object class

              print $this->Form->hidden($xprefix . '.objectclass', array('default' => $oc)) . "\n";

              // Set up for the checkbox

              $xname = 'CoLdapProvisionerAttribute.' . $xindex . '.export';
              $xlabel = '<code>' . $attr . '</code>';
              // Prior to v2.0.0, default was to check for new entries, but that
              // probably isn't desirable for stable deployments (and is also
              // unintuitive from the UI), so new default is unchecked.
              $xchecked = (isset($currentAttributes[$oc][$attr]['export'])
                           && $currentAttributes[$oc][$attr]['export']);
              $xdisabled = false;

              if(!empty($supportedAttributes[$oc]['attributes'][$attr]['description'])) {
                $xlabel .= " (" . $supportedAttributes[$oc]['attributes'][$attr]['description'] . ")";
              }

              if($supportedAttributes[$oc]['attributes'][$attr]['required']) {
                // If this attribute is required, make sure the export field gets set
                // to true when saving
                print $this->Form->hidden($xname, array('default' => true)) . "\n";

                // For required checkboxes, the hidden attribute is what we actually save
                $xname .= "-required";
                $xlabel .= " (" . _txt('fd.required') . ")";
                $xchecked = true;
                $xdisabled = true;
              }

              // Emit the checkbox for exporting the attribute

              print "&nbsp;&nbsp;" . $this->Form->input($xname,
                                                        array('label' => $xlabel,
                                                              'checked' => $xchecked,
                                                              'disabled' => $xdisabled));

              // If this attribute supports multiple types, emit the select box.

              if(!isset($supportedAttributes[$oc]['attributes'][$attr]['grouping'])) {
                $xtypeopts = null;

                if(isset($supportedAttributes[$oc]['attributes'][$attr]['extendedtype'])) {
                  $xtypevar = $supportedAttributes[$oc]['attributes'][$attr]['extendedtype'];
                  $xtypeopts = $$xtypevar;  // Set by the controller
                } elseif(isset($supportedAttributes[$oc]['attributes'][$attr]['typekey'])) {
                  $xtypekey = $supportedAttributes[$oc]['attributes'][$attr]['typekey'];
                  $xtypeopts = $cm_texts[ $cm_lang ][ $xtypekey ];
                }

                if($xtypeopts) {
                  $xval = "";
                  $xempty = $supportedAttributes[$oc]['attributes'][$attr]['multiple']
                            ? _txt('pl.ldapprovisioner.types.all') : false;

                  if(isset($currentAttributes[$oc][$attr]['type'])) {
                    $xval = $currentAttributes[$oc][$attr]['type'];
                  } elseif(isset($supportedAttributes[$oc]['attributes'][$attr]['defaulttype'])) {
                    $xval = $supportedAttributes[$oc]['attributes'][$attr]['defaulttype'];
                  }

                  print " " . $this->Form->select($xprefix . '.type',
                                                  $xtypeopts,
                                                  array('value' => $xval,
                                                        'empty' => $xempty,
                                                        'required' => false));
                }
              }

              print "<br />\n";

              if(isset($supportedAttributes[$oc]['attributes'][$attr]['alloworgvalue'])
                 && $supportedAttributes[$oc]['attributes'][$attr]['alloworgvalue']) {
                // Set up for the special use_org_value checkbox

                $xname = 'CoLdapProvisionerAttribute.' . $xindex . '.use_org_value';
                // Default to unchecked if this is a new entry, or if previously set
                $xchecked = isset($currentAttributes[$oc][$attr]['use_org_value'])
                            && $currentAttributes[$oc][$attr]['use_org_value'];

                print '<div class="nested">'
                      . $this->Form->input($xname,
                                           array('label' => _txt('pl.ldapprovisioner.fd.useorgval'),
                                                 'checked' => $xchecked))
                      . "</div>\n";
              }
            }
            
            print "</div>\n";
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.person_ocs'); ?></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.person_ocs.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('person_ocs')
        : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['person_ocs'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.ldapprovisioner.group_ocs'); ?></div>
      <div class="field-desc"><?php print _txt('pl.ldapprovisioner.group_ocs.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('group_ocs')
        : filter_var($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['group_ocs'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php
          print $this->Form->submit($submit_label,
            // We could also do this by adding onsubmit to the form open tag
            array('onclick' => 'return js_check_group_config()'));
        ?>
      </div>
    </li>
  <?php endif; ?>
</ul>

<div id="error-dialog" title="<?php print _txt('pl.ldapprovisioner.basedn.gr'); ?>">
  <p><?php print _txt('er.ldapprovisioner.basedn.gr.none'); ?></p>
</div>