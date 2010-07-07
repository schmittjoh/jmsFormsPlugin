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
 * This is the base class used by first-level forms which 
 * embed forms of several interdependent objects where the 
 * order of saving is important, and where the top object
 * in the hierarchy can be returned by one of several embedded
 * forms.
 * 
 * Use Case: 
 * You want to save an Establishment which is owned by a User.
 * The User can either sign up, or login to an existing account.
 * So, you end up with a form that embeds a SignUpForm, 
 * a LoginForm, and an EstablishmentForm.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class jmsFakeObjectForm extends BaseFormDoctrine
{
	/**
	 * We need the constructor from the base form
	 * 
	 * @param array $defaults
	 * @param array $options
	 * @param mixed $CSRFSecret
	 * @return void
	 */
	public function __construct($defaults = array(), $options = array(), $CSRFSecret = null)
	{
		BaseForm::__construct($defaults, $options, $CSRFSecret);
		$this->object = new jmsFakeRecordObject;				
	}
	
	/**
	 * We need to implement this method, it's serving no other purpose
	 * since this form does not actually represent an object.
	 */
	public function getModelName() { }
	
	/**
	 * Overwrite the parent method since we do not retrieve
	 * the connection for a specific model.
	 */
	public function getConnection() 
	{ 
		return Doctrine_Manager::getInstance()->getCurrentConnection();
	}

	/**
	 * Overwrite the parent method since we do not use it.
	 */
	protected function doUpdateObject($values) { }
	
	/**
	 * Overwrite the parent method since we do not use it.
	 * 
	 * @param array $values
	 * @return array
	 */
	public function processValues($values)
	{
		return $values;
	}
	
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
	public function embedI18n($cultures = array(), $decorator = null)
	{
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
	}
	
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
	public function embedRelation($relationName, $formClass = null, $formArgs = array(), $innerDecorator = null, $decorator = null)
	{
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
	}
	
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
	public function isI18n()
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  public function getI18nModelName()
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
   
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  public function getI18nFormClass()
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function updateDefaultsFromObject()
  {
  }

	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function processUploadedFile($field, $filename = null, $values = null)
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function removeFile($field)
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function saveFile($field, $filename = null, sfValidatedFile $file = null)
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function setupInheritance()
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  protected function getRelatedModelName($alias)
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
	/**
	 * This method is not available.
	 * @throws LogicException Always if called
	 */
  public function isNew()
  {
		throw new LogicException('This method is not supported in forms extending FakeObjectForm.');
  }
    
  /**
   * We need the base form behavior here
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