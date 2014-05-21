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

use figdice\exceptions\LexerArrayToStringConversionException;

class Tag extends Node
{

  private $xmlLineNumber;
  private $name;
  private $figAttributes;
  /**
   * @var array
   */
  private $attributes;
  protected $children;

	public function __construct($name, $xmlLineNumber)
	{
	  $this->name = $name;
	  $this->xmlLineNumber = $xmlLineNumber;
	}
	
	/**
	 * The tag name. Example: img
	 * @return string
	 */
	public function getName()
	{
	  return $this->name;
	}
	/**
	 * The line where the tag appears in the source document.
	 * @return integer
	 */
	public function getLineNumber()
	{
	  return $this->xmlLineNumber;
	}

	protected function getAttribute($name)
	{
	  if (! $this->hasAttribute($name)) {
	    return null;
	  }
	  return $this->attributes[$name];
	}
	protected function getFigAttribute($name)
	{
	  if (! $this->hasFigAttribute($name)) {
	    return null;
	  }
	  return $this->figAttributes[$name];
	}
	/**
	 * @param string $name
	 * @return boolean
	 */
	protected function hasAttribute($name)
	{
	  return isset($this->attributes[$name]);
	}
	/**
	 * @return array
	 */
	protected function getAttributes()
	{
	  return $this->attributes;
	}
	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasFigAttribute($name)
	{
	  return isset($this->figAttributes[$name]);
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 */
	public function putAttribute($name, $value)
	{
	  if (null == $this->attributes) {
	    $this->attributes = array();
	  }
	  $this->attributes[$name] = $value;
	}
	/**
	 * @param string $name
	 * @param string $value
	 */
	public function putFigAttribute($name, $value)
	{
	  if (null == $this->figAttributes) {
	    $this->figAttributes = array();
	  }
	  $this->figAttributes[$name] = $value;
	}
	/**
	 * @param Node $node
	 */
	public function addChild(Node $node)
	{
	  if (null == $this->children) {
	    $this->children = array();
	  }
	  $this->children []= $node;
	}

	/**
	 * @param string $name
	 * @param Renderer $renderer
	 * @return boolean
	 */
	private function isBooleanFigAttr($name, Renderer $renderer)
	{
	  $expr = $this->getFigAttribute($name);
	  if (! $expr) {
	    return false;
	  }
	  $bool = $renderer->evaluate($expr, $this);
	  return $bool;
	}
	
	/**
	 * @param Renderer $renderer
	 * @return boolean
	 */
	private function isAutoClose(Renderer $renderer)
	{
	  return $this->isBooleanFigAttr('auto', $renderer);
	}
	/**
	 * @param Renderer $renderer
	 * @return boolean
	 */
	private function isVoid(Renderer $renderer)
	{
	  return $this->isBooleanFigAttr('void', $renderer);
	}
	
	private function hasText()
	{
	  return isset($this->figAttributes['text']);
	}
	
	/**
	 * @param Renderer $renderer
	 * @return boolean
	 */
	protected function isMute(Renderer $renderer)
	{
	  // For non-fig: tags, check the fig:mute property.
	  return $this->isBooleanFigAttr('mute', $renderer);
	}
	
	
	protected function checkFigCond(Renderer $renderer)
	{
	  if (! $this->hasFigAttribute('cond'))
	    return true;
	  
	  $expr = $this->getFigAttribute('cond');
	  $bool = $renderer->evaluate($expr, $this);
	  
	  if ($bool)
	    return true;
	  return false;
	}

	public function render(Renderer $renderer)
	{
	  
	  $appender = '';
	  
	  // TODO: Check fig:case.

	  
	  
	  //================================================================
	  // fig:walk
	  // We compute the collection, switch the view data context,
	  // and temporarily remove the fig:walk attribute artificially,
	  // so as to loop over self and do the rendering.
	  // After that, we restore the fig:walk attr.
	  if ($this->hasFigAttribute('walk')) {
	    return $this->fig_walk($renderer);
	  }
	  
	  
	  
	  //================================================================
	  // fig:cond
	  // Check if there is an unsatisfied fig:cond condition.
	  if (! $this->checkFigCond($renderer)) {
	    return null;
	  }


	  //================================================================
	  // fig:macro
	  // Check if tag is defining a macro
	  // A macro definition does not produce output.
	  // CAUTION: You cannot define a macro on the same tag as a :walk.
	  // It also leads to undetermined behavior, to nest a macro def
	  // anywhere inside a walk loop.
	  if ($this->hasFigAttribute('macro')) {
	    $renderer->defineMacro($this->getFigAttribute('macro'), $this);
	    return '';
	  }

	  //================================================================
	  // fig:call
	  // Check if tag is invoking a macro
	  if ($this->hasFigAttribute('call')) {
	    return $this->invokeMacro($renderer);
	  }


	  // Check if the tag is defining a slot:
	  if ($this->hasFigAttribute('slot')) {
	    //Extract name of slot
	    $slotName = $this->getFigAttribute('slot');
	    //Store a reference to current node, into the View's map of slots
	    
	    $renderer->defineSlot($slotName);
	    $appender .= $renderer->makeBeginSlotMarker($slotName);
	    
	    // Next, we will render the tag as usual,
	    // and finish with an ending marker.
	  }


	  // Check mute
	  $isMute = $this->isMute($renderer);
	  
	  if (! $isMute) {
  	  $appender .= '<'.$this->name;
  	  
  	  //
  	  // The attributes
  	  //
  	  
  	  // The attributes are:
  	  // direct attributes in the tag,
  	  // and direct fig:attr children.
  	  
  	  // We will collect the named attributes' values 
  	  // in an array, so that the calculated ones (fig:attr)
  	  // will override the direct ones.
  	  $attributesToRender = array();
  	  
  	  // Let's take care of the direct attributes first.
  	  if (count($this->attributes)) {
    	  foreach ($this->attributes as $attrName => $attrValue) {
    	    // Ad Hoc
    	    $attrValue = $this->processAdHocs($renderer, $attrName, $attrValue);

    	    $attributesToRender[$attrName] = $attrValue;
    	  }
  	  }
  	  
  	  // Now let's see if the tag has fig:attr immediate children:
  	  if (count($this->children)) {
  	    foreach ($this->children as $childNode) {
  	      if ($childNode instanceof TagFigAttr) {
  	        $value = $childNode->render($renderer);
  	        if (is_array($value)) {
  	          $value = implode('', $value);
  	        }
  	        $attributesToRender[$childNode->getAttributeName()] = $value;
  	      }
  	    }
  	  }

  	  // Finally let's print all this
  	  if (count($attributesToRender)) {
    	  foreach ($attributesToRender as $attrName => $attrValue) {
      	  $appender .= ' ' . $attrName.'="' . $attrValue . '"';
  	    }
  	  }
	  }
	  
	  
	  $childrenAppender = '';
	  
		//================================================================
		// fig:text
		// The whole subtree is replaced with the specified expr.
	  if ($this->hasText()) {
	    $textValue = $renderer->evaluate($this->figAttributes['text'], $this);
	    //If we tried to resolve an XPath query, and we got a DOMNode,
	    //use its inherent value instead of the object.
	    if ($textValue instanceof \DOMNode) {
	      $textValue = $textValue->nodeValue;
	    }
	    $childrenAppender .= $textValue;
	  }
	  
	  // If the tag does not carry a fig:text directive,
	  // we will render its children, recursively.
	  else if (count($this->children)) {
	    
	    $previousCDataSibling = null;
  	  foreach ($this->children as $child) {

  	    // Nothing to render for a fig:attr, because we have already taken care of them
  	    // in the attributes section.
  	    if ($child instanceof TagFigAttr)
  	      continue;

  	    if ($child instanceof Tag)
    	    $child->setPreviousCDataSibling($previousCDataSibling);
  	    
  	    $childAppender = $child->render($renderer);
 	      $childrenAppender .= $childAppender;
 	      
 	      $previousCDataSibling = ($child instanceof CData ? $child : null);
  	  }
	  }

		//================================================================
		// fig:filter
		// If the tag carries a fig:filter attribute, we will pass the
		// subtree through the Filter (or the outcome of the fig:text
		// attr, which in a way can be considered as the subtree of the tag).
		if ($this->hasFigAttribute('filter')) {
		  $filtername = $this->getFigAttribute('filter');
  	  $childrenAppender = $renderer->applyOutputFilter($filtername, $childrenAppender, $this);
		}
	   
  
	  
	  // Now take care of the closing tag.
	  if ($isMute) {
	    $appender .= $childrenAppender;
	  }
	  else {
	    // If there is no child content, let's see if we were AutoClose
	    // or Void.
	    
	    if ($this->isVoid($renderer) && ('' == trim($childrenAppender))) {
	      $appender .= '>';
	    }
	    else if ($this->isAutoClose($renderer) && ('' == trim($childrenAppender))) {
	      $appender .= ' />';
	    }
	    
	    else if (empty($childrenAppender) && ! (is_numeric($childrenAppender) && ($childrenAppender == 0)) ) {
        $appender .= '></' . $this->name . '>';
	    }
	    else {
	      $appender .= '>';
	      $appender .= $childrenAppender;
	      $appender .= '</' . $this->name . '>';
	    }
	    
	  }
	  
	  
		//================================================================
		// fig:slot
	  // Put an ending marker for a slot tag:
	  if ($this->hasFigAttribute('slot')) {
	    $appender .= $renderer->makeEndSlotMarker($slotName);
	  }
		//================================================================
		// fig:plug
	  // Check if the tag is plugging some contents into a slot:
	  else if ($this->hasFigAttribute('plug')) {
	    $slotName = $this->getFigAttribute('plug');
	    // Next, we will calculate the rendering of this tag,
	    // but without printing it. Instead, it will be stored
	    // into the Renderer, and in the end of the full rendering
	    // process, the renderer will wire plugs into slots.
	    $renderer->plug($slotName, $appender, $renderer->evaluate($this->getFigAttribute('append'), $this));
	    return null;
	  }
	  
	  return $appender;
	}
	
	/**
	 * @param Renderer $renderer
	 * @param string $attributeName
	 * @param string $attributeValueString
	 * @throws LexerArrayToStringConversionException
	 * @return string
	 */
	private function processAdHocs(Renderer $renderer, $attributeName, $attributeValueString)
	{
	  $value = $attributeValueString;

	  if(preg_match_all('/\{([^\{]+)\}/', $value, $matches, PREG_OFFSET_CAPTURE)) {
	    for($i = 0; $i < count($matches[0]); ++ $i) {
	      $expression = $matches[1][$i][0];
	      $outerExpressionPosition = $matches[0][$i][1];
	      $outerExpressionLength = strlen($matches[0][$i][0]);
	  
	      //Evaluate expression now:
	  
	      $evaluatedValue = $renderer->evaluate($expression, $this);
	      if($evaluatedValue instanceof Tag) {
	        $evaluatedValue = $evaluatedValue->render($renderer);
	      }
	      if(is_array($evaluatedValue)) {
	        if(empty($evaluatedValue)) {
	          $evaluatedValue = '';
	        }
	        else {
	          $message = 'Attribute ' . $attributeName . '="' . $attributeValueString . '" in tag "' . $this->name . '" evaluated to array.';
	          throw new LexerArrayToStringConversionException($message, $renderer->getView()->getFilename(), $this->xmlLineNumber);
	        }
	      }
	  
	      //The outcome of the evaluatedValue, coming from DB or other, might contain non-standard HTML characters.
	      //We assume that the FIG library targets HTML rendering.
	      //Therefore, let's have the outcome comply with HTML.
	      if(is_object($evaluatedValue)) {
	        //TODO: Log some warning!
	        $evaluatedValue = '### Object of class: ' . get_class($evaluatedValue) . ' ###';
	      }
	      else {
	        $evaluatedValue = htmlspecialchars($evaluatedValue);
	      }
	  
	      //Store evaluated value in $matches structure:
	      $matches[0][$i][2] = $evaluatedValue;
	    }
	  
	    //Now replace expressions right-to-left:
	    for($i = count($matches[0]) - 1; $i >= 0; -- $i) {
	      $evaluatedValue = $matches[0][$i][2];
	      $outerExpressionPosition = $matches[0][$i][1];
	      $outerExpressionLength = strlen($matches[0][$i][0]);
	      $value = substr_replace($value, $evaluatedValue, $outerExpressionPosition, $outerExpressionLength);
	    }
	  }
	   
	  return $value;
	}
	
  /**
   * Iterates the rendering of the current element,
   * over the data specified in the fig:walk attribute.
   *
   * @return string
   */
  private function fig_walk(Renderer $renderer) {
    $figIterateAttribute = $this->getFigAttribute('walk');
    $this->clearFigAttribute('walk');
    $dataset = $renderer->evaluate($figIterateAttribute, $this);
  
    //Walking on nothing gives no ouptut.
    if(null === $dataset) {
      return '';
    }
  
    $outputBuffer = '';
  
    if(is_object($dataset) && ($dataset instanceof Iterable) ) {
      $datasetCount = $dataset->count();
    }
    else if(is_array($dataset)) {
      $datasetCount = count($dataset);
    }
    else {
      //When requested to walk on a scalar or a single object,
      //do as if walking on an array containing this single element.
      $dataset = array($dataset);
      $datasetCount = 1;
    }
  
    $newIteration = new Iteration($datasetCount);
    $renderer->pushIteration($newIteration);
  
    if(is_array($dataset) || (is_object($dataset) && ($dataset instanceof Iterable)) ) {
      
      //Keep track of the first round.
      $bFirstIteration = true;

      foreach($dataset as $key => $data) {
        $renderer->getRootView()->pushStackData($data);
        $newIteration->iterate($key);
        $nextContent = $this->render($renderer);

        //Each rendered iteration start again
        //with the blank part on the right of the preceding CDATA,
        //so that the proper indenting is kept, and carriage returns
        //between each iteration, if applies.
        //CAUTION: the property previousCDataSibling is optionally defined
        //on the fly during a parent loop-on-children: is is not a persistable
        //attribute of Tag objects.
        if(! $bFirstIteration) {
          if(isset($this->previousCDataSibling) && (null != $this->previousCDataSibling)) {
            if(($rtrim = rtrim($this->previousCDataSibling->getText())) < $this->previousCDataSibling->getText()) {
              $blankRPart = substr($this->previousCDataSibling->getText(), strlen($rtrim));
              $precedingBlank = strrchr($blankRPart, "\n");
              if($precedingBlank === false) {
                $precedingBlank = $blankRPart;
              }
              $nextContent = $precedingBlank . $nextContent;
              $bFirstIteration = false;
            }
          }
        }
        //But only do this for subsequent iterations, not the first one,
        //since its preceding sibling was already rendered beforehand.
        else {
          $bFirstIteration = false;
        }


        
        $outputBuffer .= $nextContent;
        $renderer->getRootView()->popStackData();
      }
    }
    $renderer->popIteration();
    
    
    // Restore fig:walk property
    $this->putFigAttribute('walk', $figIterateAttribute);
    return $outputBuffer;
  }

	public function validate()
	{
	  // TODO: parse expressions and adhocs
	  
	  // TODO: check required attributes
	  
	  // TODO: check incompatible combinations of attributes
	  //  for example: 
	  //  - fig:walk and fig:slot together

	  return true;
	}
	
	private function clearFigAttribute($attrName) {
	  unset($this->figAttributes[$attrName]);
	}

	/**
	 * Renders the call to a macro.
	 * No need to mute the tag that carries the fig:call attribute,
	 * because the output of the macro call replaces completely the
	 * whole caller tag.
	 * @param Renderer $renderer
	 * @return string
	 */
	private function invokeMacro(Renderer $renderer)
	{
	  //Retrieve the name of the macro to call.
	  $macroName = $this->getFigAttribute('call');
	  
	  //Prepare the arguments to pass to the macro:
	  //all the non-fig: attributes, evaluated.
	  $arguments = array();
	  foreach($this->attributes as $attribName => $attribValue) {
      $value = $renderer->evaluate($attribValue, $this);
      $arguments[$attribName] = $value;
	  }
	  
	  
	  //Fetch the parameters specified as immediate children
	  //of the macro call : <fig:param name="" value=""/>
	  $paramTags = array();
	  if (count($this->children)) {
	    foreach ($this->children as $childNode) {
	      if ($childNode instanceof TagFigParam) {
	        $value = $childNode->render($renderer);
	        if (is_array($value)) {
	          $value = implode('', $value);
	        }
	        $attributesToRender[$childNode->getParamName()] = $value;
	      }
	    }
	  }
	   
	  
	  $arguments = array_merge($arguments, $paramTags);
	  
	  //Retrieve the macro contents.
	  $macroTag = $renderer->getMacro($macroName);
	  if(null != $macroTag) {
	    $renderer->getRootView()->pushStackData($arguments);
	    //Now render the macro contents, but do not take into account the fig:macro
	    //that its root tag carries.
	    $macroTag->clearFigAttribute('macro');
	    $renderer->blockIterations();
	    $result = $macroTag->render($renderer);
	    $renderer->unblockIterations();
	    $renderer->getRootView()->popStackData();
	    $macroTag->putFigAttribute('macro', $macroName);
	    return $result;
	  }
	  
	  return '';
	}

	private function setPreviousCDataSibling(CData $previousSibling = null)
	{
	  //This property explicitly not defined as a member,
	  //because I don't want it serialized.
	  $this->previousCDataSibling = $previousSibling;
	}
}
