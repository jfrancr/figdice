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

		$result = $lexer->evaluateNEW($this, $tag);
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

	public function defineSlot($slotName)
	{
	  if ($this->parentRenderer) {
	    $this->parentRenderer->defineSlot($slotName);
	    return;
	  }

	  $this->slots[] = $slotName;
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
}
