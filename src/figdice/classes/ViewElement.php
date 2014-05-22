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

/**
 * Node element attached to a View object.
 *
 */
abstract class ViewElement {
	public $outputBuffer;
	public $autoclose;
	/**
	 * @var ViewElementTag
	 */
	public $parent;
	/**
	 * Indicates the index of this object
	 * in the $children array of $parent object.
	 *
	 * @var integer
	 */
	public $childOffset;

	/**
	 * @var ViewElement
	 */
	public $previousSibling;
	/**
	 * @var ViewElement
	 */
	public $nextSibling;

	/**
	 * The View object which this ViewElement
	 * is attached to.
	 * @var View
	 */
	public $view;
	public $data;
	public $logger;

	/**
	 * The line in XML file where this element begins.
	 * @var int
	 */
	public $xmlLineNumber;


	/**
	 * Constructor
	 *
	 * @param View $view The View to which this node is attached.
	 */
	public function __construct(View &$view) {
		$this->outputBuffer = null;
		$this->autoclose = true;
		$this->parent = null;
		$this->previousSibling = null;
		$this->view = &$view;
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
	public function getData($name) {
		//Treat plain names
		return $this->view->fetchData($name);
	}

	/**
	 * The line on which the element is found.
	 * @return int
	 */
	public function getLineNumber() {
		return $this->xmlLineNumber;
	}
}