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

  public function __construct($namespace, Renderer $parentRenderer = null)
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
		if(! isset($this->view->lexers[$expression]) ) {
			$lexer = new Lexer($expression);
			$this->view->lexers[$expression] = & $lexer;
			$lexer->parse($this);
		}
		else {
			$lexer = & $this->view->lexers[$expression];
		}

		$result = $lexer->evaluate($this, $tag);
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
	  return $this->view->fetchData($name);
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
}
