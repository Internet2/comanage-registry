<?php

# Original source: https://github.com/bbars/utils/tree/master/php-base32-encode-decode
class Base32
{
  const BITS_5_RIGHT = 31;
  const CHARS = 'abcdefghijklmnopqrstuvwxyz234567'; // lower-case
  
  public static function encode($data, $padRight = false)
  {
    $dataSize = strlen($data);
    $res = '';
    $remainder = 0;
    $remainderSize = 0;
    
    for ($i = 0; $i < $dataSize; $i++)
    {
      $b = ord($data[$i]);
      $remainder = ($remainder << 8) | $b;
      $remainderSize += 8;
      while ($remainderSize > 4)
      {
        $remainderSize -= 5;
        $c = $remainder & (self::BITS_5_RIGHT << $remainderSize);
        $c >>= $remainderSize;
        $res .= static::CHARS[$c];
      }
    }
    if ($remainderSize > 0)
    {
      // remainderSize < 5:
      $remainder <<= (5 - $remainderSize);
      $c = $remainder & self::BITS_5_RIGHT;
      $res .= static::CHARS[$c];
    }
    if ($padRight)
    {
      $padSize = (8 - ceil(($dataSize % 5) * 8 / 5)) % 8;
      $res .= str_repeat('=', $padSize);
    }
    
    return $res;
  }
  
  public static function decode($data)
  {
    $data = rtrim($data, "=\x20\t\n\r\0\x0B");
    $dataSize = strlen($data);
    $buf = 0;
    $bufSize = 0;
    $res = '';
    $charMap = array_flip(str_split(static::CHARS)); // char=>value map
    $charMap += array_flip(str_split(strtoupper(static::CHARS))); // add upper-case alternatives
    
    for ($i = 0; $i < $dataSize; $i++)
    {
      $c = $data[$i];
      if (!isset($charMap[$c]))
      {
        if ($c == " " || $c == "\r" || $c == "\n" || $c == "\t")
          continue; // ignore these safe characters
        throw new Exception('Encoded string contains unexpected char #'.ord($c)." at offset $i (using improper alphabet?)");
      }
      $b = $charMap[$c];
      $buf = ($buf << 5) | $b;
      $bufSize += 5;
      if ($bufSize > 7)
      {
        $bufSize -= 8;
        $b = ($buf & (0xff << $bufSize)) >> $bufSize;
        $res .= chr($b);
      }
    }
    
    return $res;
  }
}

class Base32hex extends Base32
{
  const CHARS = '0123456789abcdefghijklmnopqrstuv'; // lower-case
}

/**
 * COmanage Registry CO Service Tokens Generate View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add QRCode Javascript
  echo $this->Html->script('qrcode/qrcode.min.js', array('inline' => false));

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = 'co_service_token';
  $args['controller'] = 'co_service_tokens';
  $args['action'] = 'index';
  $args['copersonid'] = $vv_co_person_id;
  $this->Html->addCrumb(_txt('ct.co_service_tokens.pl'), $args);
?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('pl.coservicetoken.token.info'); ?></strong>
  </p>
</div>
<div class="innerContent">
<table id="<?php print $this->action; ?>_co_service_token" class="ui-widget">
  <tbody>
    <tr class="line1">
      <th class="ui-widget-header">
        <?php print _txt('ct.co_services.1'); ?>
      </th>
      <td>
        <?php print filter_var($vv_co_service['CoService']['name'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </td>
    </tr>
    <tr class="line2">
      <th class="ui-widget-header">
        <?php print _txt('pl.coservicetoken.token'); ?>
      </th>
      <td>
        <div id="qrcode"></div>
      </td>
    </tr>
  </tbody>
</table>
</div>
<script>
  var qrcode = new QRCode(document.getElementById("qrcode"), {
    text: "otpauth://totp/<?php print filter_var($vv_co_service['CoService']['name'], FILTER_SANITIZE_SPECIAL_CHARS); ?>?secret=<?php $secret = new Base32hex; print filter_var($secret->encode($vv_token), FILTER_SANITIZE_SPECIAL_CHARS); ?>",
    width: 128,
    height: 128,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.L
  });
</script>
