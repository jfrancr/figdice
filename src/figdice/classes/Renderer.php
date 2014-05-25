<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
* @copyright 2004-2014, Gabriel Zerbib.
* @version 2.1.0
* @package FigDice
*
* This file is part of FigDice.
*
* FigDice is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* FigDice is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with FigDice.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace figdice\classes;

use figdice\View;
use figdice\classes\lexer\Lexer;
use figdice\exceptions\FilterNotFoundException;

class Renderer
{
  private $namespace = 'fig:';
  /**
   * @var View
   */
  private $view;
  
  /**
   * @var Renderer
   */
  private $parentRenderer;

  /**
   * Associative array (string)name=>Dictionary
	 * Stacked model of dictionaries :
	 * a dictionary is attached to a specific fig file. 
	 * An anonymous dictionary cannot be overridable
	 * and is always added to the current file.
	 * A named dictionary replaces (overrides) an already defined one in same file if exists,
	 * or is added to the highest ancestor of the current file which does not define a
	 * dictionary by same name (ie. we never override a parent dictionary). 
   * 
   * @var array
   */
  private $dictionaries = array();
 
  /**
   * Map of the defined slots. Easy to
   * lookup whether a slot is defined, by
   * its name.
   * @var array
   */
  private $slots = array();
  
  /**
   * Map of the contents provided by the plug tags.
   * @var array
   */
  private $plugs = array();

  /**
   * Acts as a stack for nested Iterations.
   * They are maintained at the Root Renderer only.
   * Sub-renderers do not deal with them.
   * @var array of Iteration
   */
  private $iterations = array();

  /**
   * Only available for Root Renderer:
   * a map of macroName => array(Tag, View)
   * The Tag is the one carrying the macro attribute, and the View indicates the file.
   * @var array
   */
  private $macros = array();

  public function __construct($namespace = 'fig:', Renderer $parentRenderer = null)
  {
    $this->parentRenderer = $parentRenderer;
    $this->namespace = $namespace;
  }
  
  /**
   * @return \figdice\View
   */
  public function getView()
  {
    return $this->view;
  }
  public function getRootView()
  {
    if ($this->parentRenderer)
      return $this->parentRenderer->getRootView();
    return $this->view;
  }

  public function render(View $view)
  {
    $this->view = $view;
    $compiledView = $view->getCompiled();
    if ($compiledView->getFigNamespace()) {
      $this->namespace = $compiledView->getFigNamespace();
    }
    $output = $compiledView->getRootTag()->render($this);
    if (is_array($output))
      $output = implode('', $output);

    // Wire plug contents only at Root Renderer level.
    // The subviews do not take care of this.
    if (null == $this->parentRenderer) {
      $this->wirePlugs($output);
    }
    
    return $output;
  }
  
	/**
	 * Checks whether specified attribute (or tag) name is in the fig namespace
	 * (whose prefix can be overriden by xmlns declaration).
	 * @param string $string
	 * @return boolean
	 */
	public function checkNsPrefix($string)
  {
    return (substr($string, 0, strlen($this->namespace)) == $this->namespace);
  }
  

	/**
	 * Evaluate the XPath-like expression
	 * on the data object associated to the view.
	 *
	 * @param string $expression
	 * @return string
	 */
	public function evaluate($expression, Tag $tag) {
	  if (null == $expression) {
	    return null;
	  }

		if(is_numeric($expression)) {
			$expression = (string)$expression;
		}

		$anchor = new Anchor($this, $this->getView()->getFilename(), $tag->getLineNumber());
		if(! isset($this->view->lexers[$expression]) ) {
			$lexer = new Lexer($expression);
			$this->view->lexers[$expression] = & $lexer;
			$lexer->parse($anchor);
		}
		else {
		  //TODO: centralize the instances of Lexer at RootView level.
		  //TODO: also pre-compile the expressions and save them in the View's compiled binary.
			$lexer = & $this->view->lexers[$expression];
		}

		$result = $lexer->evaluate($anchor);
		return $result;
	}

	/**
	 * Returns the data structure
	 * behind the specified name.
	 * Looks first in the local variables,
	 * then in the data context of the element.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getData($name)
	{
	  return $this->getRootView()->fetchData($name);
	}

	/**
	 * @param string $slotName
	 */
	public function defineSlot($slotName)
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->defineSlot($slotName);
	    return;
	  }

	  $this->slots[] = $slotName;
	}

	private function defineMacroInSubview($macroName, Tag $tag, View $view)
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->defineMacroInSubview($macroName, $tag, $view);
	    return;
	  }
	  
	  $this->macros[$macroName] = array('tag' => $tag, 'view' => $view);
	}

	public function defineMacro($macroName, Tag $tag)
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->defineMacroInSubview($macroName, $tag, $this->getView());
	    return;
	  }

    $this->defineMacroInSubview($macroName, $tag, $this->getView());
	}

	public function plug($slotName, $contents, $append)
	{
	  
	  // Only the root renderer takes care of the slot/plug
	  // mechanism, in a centralized manner.
	  if ($this->parentRenderer) {
	    $this->parentRenderer->plug($slotName, $contents, $append);
	    return;
	  }
	  
	  if (is_array($contents)) {
	    $contents = implode($contents);
	  }
	  
	  if ($append && isset($this->plugs[$slotName])) {
	    $this->plugs[$slotName] .= $contents;
	  }
	  else {
	    $this->plugs[$slotName] = $contents;
	  }
	}
	
	/**
	 * Write plug contents into slots, and clean up
	 * the slot markers.
	 */
	private function wirePlugs(& $documentString)
	{
	  
	  foreach ($this->slots as $slotName) {
	    $beginMarkerString = $this->makeBeginSlotMarker($slotName);
	    $endMarkerString = $this->makeEndSlotMarker($slotName);
	    
	    
	    if (isset($this->plugs[$slotName])) {
	      $beginPosition = strpos($documentString, $beginMarkerString);
	      
	      // If we're plugging content into a slot that does not exist,
	      // simply ignore.
	      if (false === $beginPosition) continue;

	      $endPosition = strpos($documentString, $endMarkerString) + strlen($endMarkerString);
	      
	      $documentString = substr($documentString, 0, $beginPosition)
	        . $this->plugs[$slotName]
	        . substr($documentString, $endPosition);
	      
	    }

	    else {
  	    // Erase the markers
	      $documentString = str_replace($beginMarkerString, '', $documentString);
 	      $documentString = str_replace($endMarkerString, '', $documentString);
	    }
	  }
	}

	public function makeEndSlotMarker($slotName)
	{
	  return '/==SLOT==' . $slotName . '==END/';
	}
	public function makeBeginSlotMarker($slotName)
	{
	  return '/==SLOT==' . $slotName . '==BEGIN/';
	}
	
	public function blockIterations()
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->blockIterations();
	    return;
	  }
	   
	  //TODO: this logic forbids a macro call inside a loop, where the macro itself
	  // runs a loop and calls a macro... :)
	  // The block/unblock mechanism should be implemented as a stack, too.
	  $this->iterationStopper = $this->iterations;
	  $this->iterations = array();
	}
	public function unblockIterations()
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->unblockIterations();
	    return;
	  }
	  
	  $this->iterations = $this->iterationStopper;
	  $this->iterationStopper = null;
	}
	
	public function pushIteration(Iteration $iteration)
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->pushIteration($iteration);
	    return;
	  }
	  
	  array_push($this->iterations, $iteration);
	}

	/**
	 * @return Iteration
	 */
	public function popIteration()
	{
	  if ($this->parentRenderer) {
	    return $this->parentRenderer->popIteration();
	  }
	  
	  $iteration = array_pop($this->iterations);
	  return $iteration;
	}
	
	/**
	 * @return Iteration
	 */
	public function getIteration()
	{
	  if ($this->parentRenderer) {
	    return $this->parentRenderer->getIteration();
	  }
	   
	  
	  if (empty($this->iterations)) {
	    return new Iteration(0);
	  }
	  $iteration = $this->iterations[count($this->iterations) - 1];
	  return $iteration;
	}
	
	/**
	 * @param string $macroName
	 * @return Tag
	 */
	public function getMacro($macroName)
	{
	  if ($this->parentRenderer) {
	    return $this->parentRenderer->getMacro($macroName);
	  }

	  if (isset($this->macros[$macroName])) {
	    return $this->macros[$macroName]['tag'];
	  }
	  
	  return null;
	}

	/**
	 * Attaches the specified dictionary to the current file, under specified name.
	 * If name is the empty string, then the dictionary is not named,
	 * and cannot override an already loaded dictionary,
	 * and cannot be made available to parent files.
	 * To the contrary, attaching an explicitly named dictionary tries to
	 * hook it first to the parent (recursively), if the parent exists and if it does not
	 * already have a dictionary by same name. Then, if immediate parent has a dictionary
	 * by same name, then we attach it to the file itself (thus potentially overwriting
	 * another dictionary by same name in current file).
	 * @param Dictionary & $dictionary
	 * @param string $name
	 */
	public function addDictionary(Dictionary & $dictionary, $name) {
	  if($name) {
	    //If I already have a dictionary by this name,
	    //I am only requested to overwrite.
	    if(array_key_exists($name, $this->dictionaries)) {
	      $this->dictionaries[$name] = & $dictionary;
	      return;
	    }
	
	    //Root file: store in place.
	    if(! $this->parentRenderer) {
	      $this->dictionaries[$name] = & $dictionary;
	      return;
	    }
	
	    //Otherwise, try to bubble up the event, but then do not overwrite
	    //the dictionary anywhere in the parent hierarchy.
	    if($this->parentRenderer->tentativeAddDictionary($dictionary, $name)) {
	      return;
	    }
	    $this->dictionaries[$name] = & $dictionary;
	    return;
	  }
	
	  //Anonymus dictionary:
	  else {
	    //Prepend the array of dictionaries with the new one,
	    //so that it's quicker to search during the translating phase.
	    array_unshift($this->dictionaries, $dictionary);
	    return;
	  }
	}


	/**
	 * In this method, we try to add the named dictionary to current file,
	 * but do not overwrite. If dictionary by same name exists, we return false.
	 * @param Dictionary & $dictionary
	 * @param string $name
	 * @return boolean
	 */
	private function tentativeAddDictionary(Dictionary & $dictionary, $name) {
	  //Do not overwrite! Stop the recusrion as soon as a dictionary by same name exists up in the hierarchy.
	  if(array_key_exists($name, $this->dictionaries)) {
	    //Returning false will cause the previous call in the recursion to store it in place.
	    return false;
	  }
	  //Root file: store in place.
	  if(! $this->parentRenderer) {
	    $this->dictionaries[$name] = & $dictionary;
	    return true;
	  }
	  if(! $this->parentRenderer->tentativeAddDictionary($dictionary, $name)) {
	    $this->dictionaries[$name] = & $dictionary;
	    return true;
	  }
	}

	/**
	 * If a dictionary name is specified, performs the lookup only in this one.
	 * If the dictionary name is not in the perimeter of the current file,
	 * bubbles up the translation request if a parent file exists.
	 * Finally throws DictionaryNotFoundException if the dictionary name is not
	 * found anywhere in the hierarchy.
	 *
	 * If the entry is not found in the current file's named dictionary,
	 * throws DictionaryEntryNotFoundException.
	 *
	 * If no dictionary name parameter is specified, performs the lookup in every dictionary attached
	 * to the current FIG file, and only if not found in any, bubbles up the request.
	 *
	 * @param $key
	 * @param string $dictionaryName
	 * @return string
	 * @throws DictionaryEntryNotFoundException, DictionaryNotFoundException
	 */
	public function translate($key, $dictionaryName = null) {
	  //If a dictionary name is specified,
	  if(null !== $dictionaryName) {
	    //and there is no dictionary by that name in the dictionaries attached to the current file,
	    if((0 == count($this->dictionaries)) || (! array_key_exists($dictionaryName, $this->dictionaries)) ) {
	      //if this file is root file (no parent), error!
	      if(null == $this->parentFile) {
	        throw new DictionaryNotFoundException($dictionaryName);
	      }
	      //otherwise, search the parent file's dictionaries for the dictionary with specified name.
	      return $this->parentFile->translate($key, $dictionaryName);
	    }
	    //This will throw an exception if the entry is not found
	    //in the current file's named dictionary, instead of searching parent's hierarchy for
	    //dictionaries with the same name.
	    return $this->dictionaries[$dictionaryName]->translate($key);
	  }
	
	  //Walk the array of dictionaries, to try the lookup in all of them.
	  if(count($this->dictionaries)) {
	    foreach($this->dictionaries as $dictionary) {
	      try {
	        return $dictionary->translate($key);
	      } catch(DictionaryEntryNotFoundException $ex) {
	      }
	    }
	  }
	  if(null == $this->parentFile) {
	    throw new DictionaryEntryNotFoundException();
	  }
	  return $this->parentFile->translate($key, $dictionaryName);
	}

	/**
	 * Applies a filter to the inner contents of an element.
	 * Returns the filtered output.
	 *
	 * @param string $filtername The name of the filter to invoke
	 * @param string $buffer the inner contents of the element, after rendering.
	 * @return string
	 */
	public function applyOutputFilter($filtername, $content, Tag $tag)
	{
	  //TODO: Currently the filtering works only on non-slot tags.
	  //If applied on a slot tag, the transform is made on the special placeholder /==SLOT=.../
	  //rather than the future contents of the slot.
	  
	  $filter = $this->getRootView()->instanciateFilter($filtername);
	  
	  if (null == $filter) {
	    throw new FilterNotFoundException($filtername, $this->getView()->getFilename(), $tag->getLineNumber());
	  }
	  
	  return $filter->transform($content);
	}
}
