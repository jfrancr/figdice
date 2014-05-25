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

class TagFigInclude extends TagFig {
	const TAGNAME = 'include';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function validate()
	{
	  // "file" attribute is mandatory.
	  if (! $this->hasAttribute('file')) {
	    //TODO: validate() should be invoked with a filename ?
	    throw new RequiredAttributeException(self::TAGNAME, ''/*$filename*/, $this->getLineNumber(), '"file" attribute is missing in include tag.');
	  }
	}
	
	public function render(Renderer $renderer)
	{
	  $currentFilename = $renderer->getView()->getFilename();
	  $requestedFilename = $this->getAttribute('file');
	   
	  $dirname = dirname($currentFilename) | '.';
	  $fqname = dirname($currentFilename).'/'.$requestedFilename;

	  // An included file will be represented in our model
	  // by a new View object, with its own filename.
	  // However, it will be bound to a parent View,
	  // and all the Render stuff related to Plugs,
	  // Slots, Universe, Functions, etc. will operate
	  // on the topmost View.
	  $view = new View();
	  // Compiled target folder is taken from parent view.
	  $view->setTempPath($renderer->getView()->getTempPath());
	  $view->loadFile($fqname);

	  return $view->renderSubview($renderer);
	}
	
}