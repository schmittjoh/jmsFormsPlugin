<?php
/*
 * Copyright 2010 Johannes M. Schmitt
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This object complements the FakeObjectForm, and is required if it is
 * used as a top-level form (which is not embedded somewhere else).
 * 
 * @package jmsFormsPlugin
 * @subpackage model
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class jmsFakeRecordObject extends Doctrine_Record
{
  public function save(Doctrine_Connection $con = null)
  {
  }
}