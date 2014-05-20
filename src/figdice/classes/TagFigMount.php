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
use figdice\exceptions\RequiredAttributeException;

class TagFigMount extends TagFig {
	const TAGNAME = 'mount';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function getAttributeTarget()
	{
	  return $this->getAttribute('target');
	}
	public function getAttributeValue()
	{
	  return $this->getAttribute('value');
	}
	public function render(Renderer $renderer)
	{
	  
	  if (! $this->checkFigCond($renderer)) {
	    return '';
	  }
	  
	  $target = $this->getAttributeTarget('target');

	  //When an explicit value="" attribute exists, 
	  //use its contents as a Lex expression to evaluate.
	  $valueExpression = $this->getAttributeValue();
	  if($valueExpression) {
	    $value = $renderer->evaluate($valueExpression, $this);
	  }
	  //Otherwise, no value attribute: then we render the inner contents of the fig:mount into the target variable.
	  else {
	    $value = $this->renderChildren($renderer);
	  }

	  $renderer->getRootView()->mount($target, $value);
	  
	  return '';
	}

	private function renderChildren(Renderer $renderer)
	{
	  $childrenAppender = '';
	  if (count($this->children)) {
	    foreach ($this->children as $child) {
	      $childAppender = $child->render($renderer);
	      $childrenAppender .= $childAppender;
	    }
	  }
	  return $childrenAppender;
	}

}
