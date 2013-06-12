<!--
/**
 * COmanage Registry CO LDAP Provisioning Target Fields
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script type="text/javascript">
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
    ?>
  }
  
  function toggle_div(div) {
    $("#" + div).toggle("slide", { "direction" : "up" });
  }
</script>
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
  // Find the ID of our parent
  $ptid = -1;
  
  if(isset($this->params->named['ptid'])) {
    $ptid = Sanitize::html($this->params->named['ptid']);
  } else {
    $ptid = $co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['co_provisioning_target_id'];
  }
  
  if($e) {
    print $this->Html->link(_txt('op.cancel'),
                           array('plugin' => null, 'controller' => 'co_provisioning_targets', 'action' => 'edit', $ptid, 'co' => $cur_co['Co']['id']),
                           array('class' => 'cancelbutton'));
  } else {
    print $this->Html->link(_txt('op.back'),
                           array('plugin' => null, 'controller' => 'co_provisioning_targets', 'action' => 'edit', $ptid, 'co' => $cur_co['Co']['id']),
                           array('class' => 'backbutton'));
  }
  
  print '
    <br />
    <br />
    ';
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $ptid)) . "\n";
  
  // Determine the starting index for newly emitted attributes and groupings
  $newattri = count($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute']);
  $newgri = count($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttrGrouping']);
  
  // Rekey the attributes array on attribute name
  $currentAttributes = array();
  
  for($i = 0;$i < $newattri;$i++) {
    if(!empty($co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i]['attribute'])) {
      $attr = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i]['attribute'];
      $currentAttributes[$attr] = $co_ldap_provisioner_targets[0]['CoLdapProvisionerAttribute'][$i];
      $currentAttributes[$attr]['_index'] = $i;
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
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.ldapprovisioner.info'); ?></strong>
  </p>
</div>
<br />
<table id="<?php print $this->action; ?>_co_ldap_provisioner_target" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('pl.ldapprovisioner.serverurl'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.serverurl.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : Sanitize::html($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['serverurl'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.ldapprovisioner.binddn'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.binddn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : Sanitize::html($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['binddn'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.ldapprovisioner.password'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.password.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('password') : Sanitize::html($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['password'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.ldapprovisioner.basedn'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.basedn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : Sanitize::html($co_ldap_provisioner_targets[0]['CoLdapProvisionerTarget']['basedn'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('pl.ldapprovisioner.opts'); ?><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.opts.desc'); ?></font>
      </td>
      <td>
        <?php
          if($e) {
            // Disabled because not yet implemented -- need CO-115
            print $this->Form->input('opt_lang', array('label' => _txt('pl.ldapprovisioner.opt.lang'),
                                                       'disabled' => true)) . "<br />";
            
            // Disabled because not yet implemented
            print $this->Form->input('opt_role', array('label' => _txt('pl.ldapprovisioner.opt.role'),
                                                       'disabled' => true)) . "<br />";
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('pl.ldapprovisioner.attrs'); ?><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapprovisioner.attrs.desc'); ?></font>
      </td>
      <td>
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
              
              print '<div id="oc_' . $oc . '_div">';
              
              foreach(array_keys($supportedAttributes[$oc]['attributes']) as $attr) {
                // Make sure the attributes map to the indexing created by Cake
                $xindex = -1;
                
                if(isset($currentAttributes[$attr]['_index'])) {
                  $xindex = $currentAttributes[$attr]['_index'];
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
                    
                    if(isset($currentGroupings[$grouping]['type'])) {
                      $xval = $currentGroupings[$grouping]['type'];
                    } elseif(isset($supportedAttributes[$oc]['groupings'][$grouping]['defaulttype'])) {
                      $xval = $supportedAttributes[$oc]['groupings'][$grouping]['defaulttype'];
                    }
                    
                    print " " . $this->Form->select($xgprefix . '.type',
                                                    $xtypeopts,
                                                    array('value' => $xval,
                                                          'empty' => $xempty));
                  }
                  
                  print "<br />\n";
                  
                  // Mark this grouping as having been seen
                  $seenGroupings[ $supportedAttributes[$oc]['attributes'][$attr]['grouping'] ] = true;
                }
                
                // If we're editing an existing attribute, emit the cross reference
                
                if(!empty($currentAttributes[$attr]['id'])) {
                  print $this->Form->hidden($xprefix . '.id' /*,array('default' => $currentAttributes[$attr]['id'])*/) . "\n";
                }
                
                // Emit the attribute name
                
                print $this->Form->hidden($xprefix . '.attribute', array('default' => $attr)) . "\n";
                
                // Set up for the checkbox
                
                $xname = 'CoLdapProvisionerAttribute.' . $xindex . '.export';
                $xlabel = '<font style="font-family:monospace">' . $attr . '</font>';
                // Default to checked if this is a new entry, or if previously set
                $xchecked = (!isset($currentAttributes[$attr]['export'])
                             || $currentAttributes[$attr]['export']);
                $xdisabled = false;
                
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
                    
                    if(isset($currentAttributes[$attr]['type'])) {
                      $xval = $currentAttributes[$attr]['type'];
                    } elseif(isset($supportedAttributes[$oc]['attributes'][$attr]['defaulttype'])) {
                      $xval = $supportedAttributes[$oc]['attributes'][$attr]['defaulttype'];
                    }
                    
                    print " " . $this->Form->select($xprefix . '.type',
                                                    $xtypeopts,
                                                    array('value' => $xval,
                                                          'empty' => $xempty));
                  }
                }
                
                print "<br />\n";
                
                if(isset($supportedAttributes[$oc]['attributes'][$attr]['alloworgvalue'])
                   && $supportedAttributes[$oc]['attributes'][$attr]['alloworgvalue']) {
                  // Set up for the special use_org_value checkbox
                  
                  $xname = 'CoLdapProvisionerAttribute.' . $xindex . '.use_org_value';
                  // Default to unchecked if this is a new entry, or if previously set
                  $xchecked = isset($currentAttributes[$attr]['use_org_value'])
                              && $currentAttributes[$attr]['use_org_value'];
                  
                  print "&nbsp;&nbsp;&nbsp;&nbsp;"
                        . $this->Form->input($xname,
                                             array('label' => _txt('pl.ldapprovisioner.fd.useorgval'),
                                                   'checked' => $xchecked))
                        . "<br />\n";
                }
              }
              
              print "</div>";
            }
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e)
            print $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>