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
 * This is the base class to be used when implementing collections within
 * a parent object (the parent has a one-to-many relation). This form can 
 * be instantiated directly, or extended to implement some custom logic. 
 * However, usually that is not necessary.
 * 
 * Required Options:
 * - relation_alias (string): The alias of the relation that this collection 
 *                            represents
 * - parent_object (Doctrine_Record): The parent object of the relation
 * 
 * Optional Options:
 * - child_form_class (string): The form class to use for each child object
 * - min (integer): The minimum number of child objects that must be added;
 *                   defaults to 0 (so no child objects are required).
 * - max (integer): The maximum number of child objects that can be added;
 *                  defaults to 0 (so no child objects are required).
 * - min_message (string): The error message to display when the minimum count
 *                          of child objects is not reached.
 * - max_message (string): The error message to display when the maximum count
 *                          of child objects is exceeded.
 * 
 * @package jmsFormsPlugin
 * @subpackage form
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * TODO: Currently, only a single column primary key is supported.
 */
class jmsBaseCollectionForm extends jmsFakeObjectForm
{
  /**
   * This prefix is prepended to the form name of embedded forms when the form 
   * represents a persistent child object.
   * @var string
   */
  const PERSISTENT_PREFIX = 'persistent_';
  
  /**
   * This prefix is prepended to the form name of embedded forms when the form
   * represents a transient child object which has not yet been persisted.
   * @var string
   */
  const TRANSIENT_PREFIX = 'transient_';
  
  /**
   * The record that this collection belongs to.
   * @var Doctrine_Record
   */
  private $_parentObject;
  
  /**
   * The relation of the parent object that this collection represents 
   * @var Doctrine_Relation
   */
  private $_relation;
  
  /**
   * The table of the parent object
   * @var Doctrine_Table
   */
  private $_table;
  
  /**
   * An array of objects that need to be deleted
   * @var array
   */
  private $_scheduledDeletes;
  
  /**
   * Returns the minimum number of childs that are required
   * @return int
   */
  public function getMinNbChilds()
  {
    return $this->getOption('min', 0);
  }
  
  /**
   * Returns the maximum number of childs that are allowed
   * @return int
   */
  public function getMaxNbChilds()
  {
    return $this->getOption('max', 0);
  }
  
  /**
   * Returns the error message when min child count is not reached
   * @return string
   */
  public function getMinMessage()
  {
    return $this->getOption('min_message', 'Please add at least %min% number '
                                          .'of objects.');
  }
  
  /**
   * Returns the error message when max child count is exceeded
   * @return string
   */
  public function getMaxMessage()
  {
    return $this->getOption('max_message', 'Please add not more than %max% '
                                          .'number of objects.');
  }
  
  /**
   * Returns the table of the parent object
   * @return Doctrine_Table
   */
  public final function getTable()
  {
    if ($this->_table === null)
      $this->_table = $this->getParentObject()->getTable();
      
    return $this->_table;
  }
  
  /**
   * Returns the alias of the relation this collection represents
   * @return string
   */
  public final function getRelationAlias()
  {
    if (($alias = $this->getOption('relation_alias')) === null)
      throw new InvalidArgumentException('You must pass the alias name '
            .'of the relation via options.');
            
    return $alias;
  }
  
  /**
   * Returns the form class to use for each of the related object
   * @return string
   */
  public final function getChildFormClass()
  {
    if ($class = $this->getOption('child_form_class') !== null)
      return $class;

    return $this->getRelation()->getClass().'Form';
  }
  
  /**
   * Returns the relation this collection represents
   * @return Doctrine_Record
   */
  public final function getRelation()
  {
    if ($this->_relation === null)
    {
      $this->_relation = $this->getTable()
                ->getRelation($this->getRelationAlias());
    }

    return $this->_relation;
  }
  
  /**
   * Returns the parent object for this form
   * @return Doctrine_Record
   */
  public final function getParentObject()
  {
    if ($this->_parentObject === null)
    {
      $this->_parentObject = $this->getOption('parent_object');
      if ($this->_parentObject === null)
        throw new InvalidArgumentException('You must pass a parent '
              .'object via options.');
      else if (!$this->_parentObject instanceof Doctrine_Record)
        throw new InvalidArgumentException('The parent object must '
              .'be an instance of Doctrine_Record.');
    }
    
    return $this->_parentObject;
  }
  
  /**
   * Returns an array with child objects that have been scheduled for deletion
   * @return array
   */
  public final function getScheduledDeletes()
  {
    if ($this->_scheduledDeletes === null)
      return array();
    
    return $this->_scheduledDeletes;
  }
  
  /**
   * Called to configure the form with the already persisted child objects
   * @see lib/vendor/symfony/lib/plugins/sfDoctrinePlugin/test/functional/fixtures/lib/form/doctrine/BaseFormDoctrine#setup()
   */
  public function setup()
  {
    parent::setup();
    
    $formClass = $this->getChildFormClass();
    $collection = $this->getParentObject()->{$this->getRelationAlias()};
    $nbChilds = 0;
    $min = $this->getMinNbChilds();
    $max = $this->getMaxNbChilds();
    
    if ($min > $max && $max > 0)
      throw new RuntimeException('min cannot be greater than max.');
    
    // embed forms for each child element that is already persisted
    foreach ($collection as $childObject)
    {
      $form = new $formClass($childObject);
      $pk = $childObject->identifier();
      
      if ($childObject->exists() === false)
        throw new RuntimeException('Transient child objects are not supported.');
      
      if (count($pk) !== 1)
        throw new RuntimeException('Composite primary keys are not supported.');
      
      $this->embedForm(self::PERSISTENT_PREFIX.reset($pk), $form);
      $nbChilds += 1;
    }
    
    // embed as many additional forms as are needed to reach the minimum
    // number of required child objects
    for (; $nbChilds < $min; $nbChilds += 1)
    {
      $form = new $formClass($collection->get(null));
      $this->embedForm(self::TRANSIENT_PREFIX.$nbChilds, $form);
    }
    
    $this->validatorSchema->setPostValidator(new sfValidatorCallback(array(
      'callback' => array($this, 'validateLogic'),
    )));
  }
  
  /**
   * Performs some of the built-in validations that are common to collections 
   * @param sfValidatorBase $validator
   * @param array $values
   * @return mixed
   */
  public function validateLogic(sfValidatorBase $validator, $values)
  {
    // get the number of child objects
    $nbChilds = 0;
    foreach ($values as $formName => $formValues)
    {
      if ($formValues !== null && is_array($formValues))
        $nbChilds += 1;
    }
    
    // check that the minimum number of childs is reached
    $min = $this->getMinNbChilds();
    if ($nbChilds < $min)
      throw new sfValidatorError($validator, $this->getMinMessage(), array(
        'min' => $min,
      ));
    
    // check that the maximum number of childs is not exceeded
    $max = $this->getMaxNbChilds();
    if ($max > 0 && $nbChilds > $max)
      throw new sfValidatorError($validator, $this->getMaxMessage(), array(
        'max' => $max,
      ));
      
    return $values;
  }
  
  /**
   * Returns the persistent child object given the primary key
   * @param mixed $pk
   * @return Doctrine_Record|null
   * TODO: Allow the user to supply a method on the parent object which takes
   *       care of the lookup. Currently, the only way is in extending and over-
   *       writing this method.
   */
  protected function getPersistentChildByPK($pk)
  {
    foreach ($this->getParentObject()->{$this->getRelationAlias()}
              as $childObject)
    { 
      if (reset($childObject->identifier()) == $pk)
        return $childObject; 
    }
    
    return null;
  }
  
  /**
   * Configure the form for the given values. This is relevant when we are 
   * manipulating the form data via Javascript, and only pass the updated
   * values when submitting the entire form.
   * 
   * @param array $values These values have not been validated yet
   * @return void
   */
  public function configureWithValues(array $values = null, array $files = null)
  {
    $this->removeEmbeddedForms();
    
    $formClass = $this->getChildFormClass();
    foreach ($values as $formName => $formValues)
    {
      if (strpos($formName, self::PERSISTENT_PREFIX) === 0)
      {
        $pk = substr($formName, strlen(self::PERSISTENT_PREFIX));
        $childObject = $this->getPersistentChildByPK($pk);
        if ($childObject === null)
          throw new RuntimeException('The child object for the embedded form '
                                    .'"'.$formName.'" does not exist.');
        
        $form = new $formClass($childObject);
        $this->embedForm($formName, $form);
      }
      else
      {
        $childObject = $this->getParentObject()->{$this->getRelationAlias()}
                        ->get(null);
        $form = new $formClass($childObject);
        $this->embedForm($formName, $form);
      }
    }
  }
  
  /**
   * We need to schedule the deletes here, since we do not have the values
   * in the save method.
   * 
   * @see lib/vendor/symfony/lib/form/addon/sfFormObject#updateObject($values)
   */
  public function updateObject($values = null)
  {
    if ($this->isValid() === false)
      throw new RuntimeException('This method is only available on valid forms.');
    
    if ($values === null)
      $values = $this->getValues();
      
    $this->_scheduledDeletes = array();
    foreach ($this->getParentObject()->{$this->getRelationAlias()}
              as $childObject)
    {
      $formName = self::PERSISTENT_PREFIX.reset($childObject->identifier());

      // check if there is an embedded form with these values
      if (!isset($values[$formName]) || !is_array($values[$formName]))
        $this->_scheduledDeletes[] = $childObject;
    }
    
    return parent::updateObject($values);
  }
  
  /**
   * This is the only method which is called on embedded forms during the 
   * save process, so we need to perform our deletes here.
   * 
   * @see lib/vendor/symfony/lib/form/addon/sfFormObject#saveEmbeddedForms($con, $forms)
   */
  public function saveEmbeddedForms($con = null, $forms = null)
  {
    if ($con === null)
      $con = $this->getConnection();
      
    foreach ($this->getScheduledDeletes() as $childObject)
      $childObject->delete($con);
    $this->_scheduledDeletes = array();
    
    parent::saveEmbeddedForms($con, $forms);
  }
}