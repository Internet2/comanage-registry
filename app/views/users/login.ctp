<?php
  /*
   * COmanage Gears XXX Placeholder Login Layout
   * Used by Auth component
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  // XXX this page isn't used anymore

  echo $session->flash('auth');
  echo $form->create('User', array('action' => 'login'));
  echo $form->input('username');
  echo $form->input('password');
  echo $form->end('Login');
?>
If you see this page, your password isn't * in the database.