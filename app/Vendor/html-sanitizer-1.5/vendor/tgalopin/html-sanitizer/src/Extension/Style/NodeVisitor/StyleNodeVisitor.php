<?php
  /**
   * COmanage Style Node Extension to HTML sanitizer
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
   * @link          http://www.internet2.edu/comanage COmanage Project
   * @package       registry
   * @since         COmanage Registry v4.3.3
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */

namespace HtmlSanitizer\Extension\Style\NodeVisitor;

use HtmlSanitizer\Model\Cursor;
use HtmlSanitizer\Extension\Style\Node\StyleNode;
use HtmlSanitizer\Node\NodeInterface;
use HtmlSanitizer\Visitor\AbstractNodeVisitor;
use HtmlSanitizer\Visitor\HasChildrenNodeVisitorTrait;
use HtmlSanitizer\Visitor\NamedNodeVisitorInterface;

class StyleNodeVisitor extends AbstractNodeVisitor implements NamedNodeVisitorInterface {
  use HasChildrenNodeVisitorTrait;

  protected function getDomNodeName(): string {
    return 'style';
  }

  protected function createNode(\DOMNode $domNode, Cursor $cursor): NodeInterface {
    return new StyleNode($cursor->node);
  }
}
