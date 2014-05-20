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

use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\FeedClassNotFoundException;

/**
 * Process <fig:feed> tag.
 * This tag accepts the following attributes:
 *  - class = the name of the Feed class to instanciate and run.
 *  - target = the mount point in the global universe.
 */
class TagFigFeed extends TagFig {
	const TAGNAME = 'feed';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}

	/**
	 * @return string
	 */
	public function getTarget()
	{
	  return $this->getAttribute('target');
	}

	public function render(Renderer $renderer) 
	{
	  if (! $this->checkFigCond($renderer))
	    return '';


	  $className = $this->getAttribute('class');
	  if(null === $className) {
	    $errormsg = 'Missing "class" attribute for fig:feed tag, in ' . $renderer->getView()->getFilename() . '(' . $this->getLineNumber() . ')';
	    throw new RequiredAttributeException(self::TAGNAME, $renderer->getView()->getFilename(), $this->getLineNumber(), $errormsg);
	  }
	  
	  //Set the parameters for the feed class:
	  //the parameters are an assoc array made of the
	  //scalar attributes of the fig:feed tag other than fig:* and
	  //class and target attributes.
	  $feedParameters = array();
	  foreach($this->getAttributes() as $attribName=>$attribText) {
	    if( ($attribName != 'class') && ($attribName != 'target') ) {
	      $feedParameters[$attribName] = $renderer->evaluate($attribText, $this);
	    }
	  }

	  $feedInstance = $renderer->getRootView()->createFeed($className, $feedParameters);

	  //At this point the feed instance must be created.
	  //If not, there was no factory to handle its loading.
	  if(! $feedInstance) {
	    throw new FeedClassNotFoundException($className, $renderer->getView()->getFilename(), $this->getLineNumber());
	  }
	  
	  //It is possible to simply invoke a Feed class and
	  //discard its result, by not defining a target to the tag.
	  $mountPoint = null;
	  if($this->hasAttribute('target')) {
	    $mountPoint = $this->getAttribute('target');
	  }

	  $feedInstance->setParameters($feedParameters);
	  
	  // The run method of the Feed might throw a FeedRuntimeException...
	  // It means that the problem encountered is severe enough, for the Feed to
	  // request that the View rendering should stop.
	  // In this case, the controller is responsible for treating accordingly.
	  $subUniverse = $feedInstance->run();
	  
	  if($mountPoint !== null) {
	    $renderer->getRootView()->mount($mountPoint, $subUniverse);
	  }
	  
	  return '';
	}
}
