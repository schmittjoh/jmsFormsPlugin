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
 * This class is useful, if you want to change the saving/update process of
 * objects from a non-object form (e.g. a collection form). This can also 
 * be used in complex form hierarchies where the uppermost form must be a
 * non-object form.
 * 
 * Use Cases: 
 * 1. You want to implement a custom collection class which must do something
 *    special during the save/update process.
 *    
 * 2. The top-object in a hierarchy cannot be determined. For example, a form
 *    where the user can either sign-up, or login, and additionally he can
 *    create another object which is owned by the user object coming either from
 *    the sign-up form, or the login form. In this case, you end up embedding
 *    three object forms in a non-object form which you want to be able to call
 *    <code>save()</code> upon. 
 * 
 * @package jmsFormsPlugin
 * @subpackage form
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class jmsFakeObjectForm extends BaseFormDoctrine
{
  /**
   * Make sure to skip the constructor of all object forms
   * 
   * @param array $defaults
   * @param array $options
   * @param mixed $CSRFSecret
   * @return void
   */
  public function __construct($defaults = array(), $options = array(), 
                              $CSRFSecret = null)
  {
    BaseForm::__construct($defaults, $options, $CSRFSecret);
    $this->object = new jmsFakeRecordObject;        
  }
  
  /**
   * This method must be implemented since it is declared abstract; it serves
   * no purpose though.
   * 
   * @return void
   */
  public function getModelName() { }
  
  /**
   * Returns the current connection; this is the best guess we can make.
   * If you need a specific connection, simply override this method.
   * 
   * @return Doctrine_Connection
   */
  public function getConnection() 
  { 
    return Doctrine_Manager::getInstance()->getCurrentConnection();
  }

  /**
   * There is no actual object to update, so we can skip this.
   * 
   * @return void
   */
  protected function doUpdateObject($values) { }
  
  /**
   * Simply return the passed values.
   * 
   * @param array $values
   * @return array
   */
  public function processValues($values)
  {
    return $values;
  }
  
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException always when called.
   */
  public function embedI18n($cultures = array(), $decorator = null)
  {
    throw new LogicException(
      'embedI18n() is not supported on instances of jmsFakeObjectForm.');
  }
  
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  public function embedRelation($relationName, $formClass = null, 
               $formArgs = array(), $innerDecorator = null, $decorator = null)
  {
    throw new LogicException(
      'embedRelation() is not supported on instances of jmsFakeObjectForm.');
  }
  
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  public function isI18n()
  {
    throw new LogicException(
      'isI18n() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  public function getI18nModelName()
  {
    throw new LogicException(
      'getI18nModelName() is not supported on instances of jmsFakeObjectForm.');
  }
   
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  public function getI18nFormClass()
  {
    throw new LogicException(
      'getI18nFormClass() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object. However, we cannot
   * throw an exception since it is from symfony internally.
   */
  protected function updateDefaultsFromObject()
  {
  }

  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  protected function processUploadedFile($field, $filename = null, $values = null)
  {
    throw new LogicException(
      'processUploadedFile() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  protected function removeFile($field)
  {
    throw new LogicException(
      'removeFile() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  protected function saveFile($field, $filename = null, 
                              sfValidatedFile $file = null)
  {
    throw new LogicException(
      'saveFile() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  protected function setupInheritance()
  {
    throw new LogicException(
      'setupInheritance() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  protected function getRelatedModelName($alias)
  {
    throw new LogicException(
      'getRelatedModelName() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * This is not supported since there is no actual object.
   * @throws LogicException Always if called
   */
  public function isNew()
  {
    throw new LogicException(
      'isNew() is not supported on instances of jmsFakeObjectForm.');
  }
    
  /**
   * Directly, use the BaseForm method here.
   * @see lib/vendor/symfony/form/addon/sfForm#renderFormTag($url, $attributes)
   */
  public function renderFormTag($url, array $attributes = array())
  {
    return BaseForm::renderFormTag($url, $attributes);
  }
    
  /**
   * Updates all embedded objects and saves them in order of
   * embedding. If you want to change this process overwrite
   * the saveEmbeddedForms method.
   *
   * @param mixed $con An optional connection object
   */
  protected function doSave($con = null)
  {
    if (null === $con)
    {
      $con = $this->getConnection();
    }

    $this->updateObject();

    // embedded forms
    $this->saveEmbeddedForms($con);
  }    
}