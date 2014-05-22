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

use Psr\Log\LoggerIntergace;
use figdice\View;
use figdice\LoggerFactory;
use figdice\exceptions\RenderingException;
use figdice\exceptions\DictionaryDuplicateKeyException;
use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\FeedClassNotFoundException;
use figdice\exceptions\FileNotFoundException;

class ViewElementTag extends ViewElement {
	/**
	 * Tag name.
	 * @var string
	 */
	private $name;

	private $attributes;

	private $children;


	/**
	 * 
	 * @param View $view
	 * @param string $name
	 * @param integer $xmlLineNumber
	 */
	public function __construct(View &$view, $name, $xmlLineNumber) {
		parent::__construct($view);
		$this->name = $name;
		$this->attributes = array();
		$this->children = array();
		$this->xmlLineNumber = $xmlLineNumber;
	}
	/**
	 * @return string
	 */
	public function getTagName() {
		return $this->name;
	}
	public function setAttributes(array $attributes) {
		$this->attributes = $attributes;
	}
	public function getAttributes() {
		return $this->attributes;
	}
	/**
	 * @return integer
	 */
	public function getChildrenCount() {
		return count($this->children);
	}
	/**
	 * @param integer $index
	 * @return ViewElement
	 */
	public function getChildNode($index) {
		return $this->children[$index];
	}
	public function appendChild(ViewElement & $child) {
		if(0 < count($this->children)) {
			$this->children[count($this->children) - 1]->nextSibling = & $child;
			$child->previousSibling = & $this->children[count($this->children) - 1];
		}
		$this->children[] = $child;
	}

	function appendCDataChild($cdata)
	{
		if (trim($cdata) != '') {
			$this->autoclose = false;
		}
		$lastChild = null;

		//Position, if applies, a reference to element's a previous sibling.
		if( count($this->children) )
			$lastChild = & $this->children[count($this->children) - 1];

		//If lastChild exists append a sibling to it.
		if($lastChild)
		{
			$lastChild->appendCDataSibling($cdata);
			return;
		}

		//Create a brand new node whose parent is the last node in stack.
		//Do not push this new node onto Depth Stack, beacuse CDATA
		//is necessarily autoclose.
		$newElement = new ViewElementCData($this->view);
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this;
		$newElement->previousSibling = null;
		if(count($this->children))
		{
			$newElement->previousSibling = & $this->children[count($this->children) - 1];
			$newElement->previousSibling->nextSibling = & $newElement;
		}
		$this->children[] = & $newElement;
	}

	function appendCDataSibling($cdata)
	{
		//Create a brand new node whose parent is the last node in stack.
		//Do not push this new node onto Depth Stack, beacuse CDATA
		//is necessarily autoclose.
		$newElement = new ViewElementCData($this->view);
		$newElement->outputBuffer .= $cdata;
		$newElement->parent = & $this->parent;
		$newElement->previousSibling = & $this;
		$this->nextSibling = & $newElement;
		$this->parent->children[] = & $newElement;
	}

	/**
	 * Returns the logger instance,
	 * or creates one beforehand, if null.
	 *
	 * @return LoggerInterface
	 */
	private function getLogger() {
		if(! $this->logger) {
			$this->logger = LoggerFactory::getLogger(get_class($this));
		}
		return $this->logger;
	}
	/**
	 * The tag name. 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}
