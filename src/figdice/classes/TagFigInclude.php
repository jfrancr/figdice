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

class TagFigInclude extends TagFig {
	const TAGNAME = 'include';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function validate()
	{
	  // "file" attribute is mandatory.
	  if (! $this->hasAttribute('file')) {
	    throw new Exception('TODO: MISSING ATTRIBUTE');
	  }
	}
	
	public function render(Renderer $renderer)
	{
	  $currentFilename = $renderer->getView()->getFilename();
	  $requestedFilename = $this->getAttribute('file');
	   
	  $dirname = dirname($currentFilename) | '.';
	  $fqname = dirname($currentFilename).'/'.$requestedFilename;

	  $view = new View();
	  $view->loadFile($fqname);

	  // TODO: pass reference to data provider, so that Universe is shared,
	  //    and must take care of plugs, macros etc.
	  return $view->renderSubview($renderer);
	}
	
}