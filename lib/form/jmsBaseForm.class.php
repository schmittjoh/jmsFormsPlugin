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
 * This provides some enhancements to symfony's default BaseForm.
 * 
 * Unfortunately, we cannot provide this as a mixin since we need to override
 * some of the built-in methods. So, you need to set-up your inheritance 
 * structure in such a way to extend this form; e.g. you can let your BaseForm
 * extend this form. In fact, we will automatically change the inheritance if
 * it is still the installation's default.
 * 
 * @package jmsFormsPlugin
 * @subpackage form
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class jmsBaseForm extends sfFormSymfony
{
  /**
   * Unsets all fields except the ones specified.
   * A similar method (useFields) has been implemented in sf 1.3/1.4,
   * the difference though is that useFields just hides the passed
   * fields from the user, but does not actually unset them.
   * 
   * @param mixed $fields An array or a list of fields to unset
   * @return void
   */
  public final function unsetAllExcept()
  {
    $fields = is_array(func_get_arg(0))? func_get_arg(0) : func_get_args();
    
    foreach ($this->widgetSchema->getFields() as $name => $field)
    {
      if (!in_array($name, $fields, true))
        unset($this[$name]);  
    }
  }

  /**
   * Whether this form has an embedded form with the given name
   * 
   * @param string $name
   * @return boolean
   */
  public final function hasEmbeddedForm($name)
  {
    return isset($this->embeddedForms[$name]);
  }
  
  /**
   * Replaces the default validator schema class with an own class.
   * All existing validators on the passed schema will be overridden. 
   *  
   * @param mixed $class (accepts an instance of the class or the class name 
   *                      as string)
   */
  public final function replaceValidatorSchema($class)
  {
    if (is_string($class))
      $class = new $class();
      
    if (!$class instanceof sfValidatorSchema)
      throw new InvalidArgumentException('$class must extend sfValidatorSchema.');
      
    foreach ($this->validatorSchema->getFields() as $name => $field)
      $class[$name] = $field;
      
    if (($preValidator = $this->validatorSchema->getPreValidator()) !== null)
    {
    	$class->setPreValidator($preValidator);
    }
    
    if (($postValidator = $this->validatorSchema->getPostValidator()) !== null)
    {
      $class->setPostValidator($postValidator);
    }
    
    $this->validatorSchema = $class;
  }
  
  /**
   * Binds this form with the default values. This is useful if you want to
   * validate an object without having the user actually send the form.
   * 
   * @return void
   */
  public final function bindWithDefaults()
  {
    $this->bind($this->getCleanedDefaults());
  }
  
  /**
   * Cleans up the default values 
   * @param sfWidgetFormSchema $schema
   * @param array $defaults
   * @return array
   */
  private function cleanUpDefaults(sfWidgetFormSchema $schema, array $defaults)
  {
	  foreach ($defaults as $key => $value)
	  {
	    if (!isset($schema[$key]))
	    {
	      unset($defaults[$key]);
	      continue;
	    }
	    
	    if (is_array($value))
	      $defaults[$key] = $this->cleanUpDefaults($schema[$key], $value);
	  }
	  
	  return $defaults;
  }
  
  /**
   * Returns the cleaned-up defaults. This clean-up is needed if not all fields
   * corresponding to the fields of an object are displayed. Consequentially,
   * for non-object forms this equals <code>getDefaults()</code>.
   * 
   * @return array
   */
  public final function getCleanedDefaults()
  {
    return $this->cleanUpDefaults($this->widgetSchema, $this->getDefaults());
  }
  
  /**
   * Removes all embedded forms with corresponding widget schemas and 
   * corresponding validator schemas and finally resets the generated
   * form field schema.
   * 
   * @param mixed A list, or an array of forms to remove. If nothing is passed,
   *              all embedded forms are removed.
   * @return void
   */
  public final function removeEmbeddedForms()
  {
  	$forms = func_num_args() === 1 && is_array(func_get_arg(0))
  	           ? func_get_arg(0) : func_get_args();

  	// if no forms are passed, all embedded forms are removed
  	if (count($forms) === 0)
  	{
	    foreach ($this->embeddedForms as $name => $form)
	    {
	      unset($this->embeddedForms[$name]);
	      unset($this->widgetSchema[$name]);
	      unset($this->validatorSchema[$name]);
	    }
  	}
  	else
  	{
  		foreach ($forms as $form)
  		{
  			if (!$this->hasEmbeddedForm($form))
  			  throw new InvalidArgumentException(
  			    sprintf('The form "%s" does not exist.', $form)
  			  );
  			
  			unset($this->embeddedForms[$form]);
  			unset($this->widgetSchema[$form]);
  			unset($this->validatorSchema[$form]);
  		}
  	}
    
    $this->resetFormFields();
  }
  
  /**
   * Automatically re-configure the form, and all embedded forms when the parent
   * form is bound. This allows you to change values on the client-side via 
   * Javascript, and still use the same form. 
   * 
   * @param array $taintedValues
   * @param array $taintedFiles
   * @see lib/vendor/symfony/lib/form/sfForm#bind($taintedValues, $taintedFiles)
   */
  public function bind(array $taintedValues = null, array $taintedFiles = null)
  {
    $this->configureWithValues($taintedValues, $taintedFiles);
    parent::bind($taintedValues, $taintedFiles);
  }
  
  /**
   * Call the configureWithValues methods of embedded forms, and see if we need
   * to re-generate the form fields.
   * 
   * @param array $taintedValues
   * @param array $taintedFiles
   * @return boolean whether the form has changed or not
   */
  public function configureWithValues(array $taintedValues = null,
                                      array $taintedFiles = null)
  {
    $embeddedForms = $this->getEmbeddedForms();
    $hasChanged = false;
    
    foreach ($embeddedForms as $formName => $form)
    {
      if (!isset($taintedValues[$formName]))
        $taintedValues[$formName] = array();
        
      if (!isset($taintedFiles[$formName]))
        $taintedFiles[$formName] = array();
        
      if (!is_array($taintedValues[$formName])
          || !is_array($taintedFiles[$formName])
          || 
          (
            count($taintedFiles[$formName]) === 0 
            && count($taintedValues[$formName]) === 0
          ))
          continue;
          
      if ($form->configureWithValues($taintedValues[$formName], 
                                     $taintedFiles[$formName]) === true)
      {
        $this->embedForm($formName, $form);
        $hasChanged = true;
      }
    }
    
    return $hasChanged;
  }
}