<!--
  /*
   * COmanage Registry CoNsfDemographics Index View
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */
-->
<h1 class="ui-state-default"><?php print $title_for_layout; ?></h1>

<?php
  if($permissions['add'])
  {
    $args =  array('controller' => 'co_nsf_demographics',
                   'action'     => 'add');
    $classArgs = array('class' => 'addbutton');
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.demographics.1'),
                            $args,
                            $classArgs) . '
                                   <br />
                                   <br />
                                   ';
  }
  // Globals
  global $cm_lang, $cm_texts;
?>

<table id="co_nsf_demographics" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort(_txt('fd.de.gender'),  'gender'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.de.citizen'), 'citizenship'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.de.ethnic'),  'ethnicity'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.de.race'),    'race'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.de.disab'),   'disability'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_nsf_demographics as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.gender'][ $c['CoNsfDemographic']['gender'] ]; ?></td>
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.citizen'][ $c['CoNsfDemographic']['citizenship'] ]; ?></td>
      <td><?php print $cm_texts[ $cm_lang ]['en.nsf.ethnic'][ $c['CoNsfDemographic']['ethnicity'] ]; ?></td>
      <td>
        <?php 
          $counter = 0;
          foreach($c['CoNsfDemographic']['race'] as $demo)
          {
            if($counter > 0)
              print "; <br>";
            print Sanitize::html($demo); 
            $counter++;
          }
        ?>
      </td>
      <td>
        <?php 
          $counter = 0;
          foreach($c['CoNsfDemographic']['disability'] as $demo)
          {
            if($counter > 0)
              print "; <br>";
            print Sanitize::html($demo); 
            $counter++;
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit'])
          {
            $args = array('controller' => 'co_nsf_demographics',
                          'action' => 'edit',
                          $c['CoNsfDemographic']['id']
                         );
            $classArgs = array('class' => 'editbutton');
            print $html->link(_txt('op.edit'),
                             $args,
                             $classArgs) . "\n";
          }

          if($permissions['delete'])
          {
            $args = array('controller' => 'co_nsf_demographics',
                          'action' => 'delete',
                          $c['CoNsfDemographic']['id']
                         );
            print '<button class="deletebutton" title="'
                  . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_delete(\''
                  . _txt(Sanitize::html($c['CoNsfDemographic']['name']))
                  . '\', \''
                  . $html->url($args)
                  . '\')";>'
                  . _txt('op.delete')
                  . '</button>';
          }
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="7">
        <?php print $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>
