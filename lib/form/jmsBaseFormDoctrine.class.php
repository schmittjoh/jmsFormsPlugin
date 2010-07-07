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
 * This is a mixin for the BaseFormDoctrine class. 
 * 
 * It is not necessary to provide this via inheritance, so we can give the user
 * as much freedom as possible in designing his inheritance structure.
 * 
 * @package jmsFormsPlugin
 * @subpackage form
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class jmsBaseFormDoctrine
{
	/**
	 * Listen to the form.method_not_found event
	 * 
	 * @param sfEvent $event
	 * @return void
	 */
	public static function listenToFormMethodNotFound(sfEvent $event)
	{
		$form = $event->getSubject();
		
		// do not process events of non-object forms
		if (!$form instanceof BaseFormDoctrine || $form instanceof FakeObjectForm)
		  return;
		
		switch (strtolower($event['method']))
		{
			case 'setobject':
				if (count($event['arguments']) !== 1)
				  throw new InvalidArugmentException(
				    'The number of arguments is invalid.');
				
				self::setObject($form, $event['arguments'][0]);
				$event->setProcessed(true);
				
				break;
				
			case 'embedcollection':
				if (count($event['arguments']) < 2 || count($event['arguments']) > 3)
				  throw new InvalidArgumentException(
				    'The number of arguments is invalid.');
				  
				self::embedCollection(
				  $form, 
				  $event['arguments'][0], 
				  $event['arguments'][1],
				  isset($event['arguments'][2])? $event['arguments'][2] : array()
				);
				$event->setProcessed(true);
				
				break;
		}
	}
	
	/**
	 * A convenience method for embedding collections.
	 * 
	 * @param BaseFormDoctrine $form
	 * @param string $formName
	 * @param string $relationAlias
	 * @param array $options
	 * @return void
	 */
	private static function embedCollection(BaseFormDoctrine $form, 
	                     $formName, $relationAlias, array $options)
  {
  	// set required options
    $options['parent_object'] = $form->getObject();
    $options['relation_alias'] = $relationAlias;
    
    // initialize and embed the collection form
    $collectionForm = new jmsBaseCollectionForm(array(), $options);
    $form->embedForm($formName, $collectionForm);
  }
	
	/**
	 * Sets the object of the given form. You should use this method with caution
	 * and only when you know exactly what you are doing.
	 * 
	 * @param BaseFormDoctrine $form
	 * @param Doctrine_Record $object
	 * @return void
	 */
	private static function setObject(BaseFormDoctrine $form, 
	                                  Doctrine_Record $object)
  {
  	// we need to do this via reflection since the property is declared
  	// protected on sfFormObject
    $reflection = new ReflectionObject($form);
    $property = $reflection->getProperty('object');
    $property->setAccessible(true);
    $property->setValue($form, $object);
    $property->setAccessible(false);
  }
}