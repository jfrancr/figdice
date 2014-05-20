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

class TagFigParam extends TagFig {
	const TAGNAME = 'param';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function getParamName()
	{
	  return $this->getAttribute('name');
	}
	
	public function render(Renderer $renderer) 
	{
	  $result = '';

	  if ($this->hasAttribute('value')) {
	    $valueExpr = $this->getAttribute('value');
	    $result = $renderer->evaluate($valueExpr, $this);
	  }
	  else {
  	  $appender = array();
  	  if (count($this->children)) {
  	    foreach($this->children as $childNode) {
  	      $childResult = $childNode->render($renderer);
  	      if (is_array($childResult)) {
  	        $childResult = implode($childResult);
  	      }
  	      $appender []= $childResult;
  	    }
  	    $result = implode($appender);
  	  }
	  }
	  
	  
	  //An XML attribute should not span accross several lines.
	  $result = trim(preg_replace("#[\n\r\t]+#", ' ', $result));
	  
	  
	  return $result;
	}
}