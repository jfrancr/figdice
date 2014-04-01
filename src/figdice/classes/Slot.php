<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2013, Gabriel Zerbib.
 * @version 2.0.0
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

class Slot {
	/**
	 * @var string
	 */
	private $anchorString;
	private $length;

	public function __construct($anchorString) {
		$this->anchorString = $anchorString;
	}
	/**
	 * @return string
	 */
	function getAnchorString()
	{
		return $this->anchorString;
	}
	/**
	 * @return integer
	 */
	function getLength()
	{
		return $this->length;
	}
	/**
	 * Sets the length of the replacement contents.
	 * @param integer $length
	 */
	function setLength($length)
	{
		$this->length = $length;
	}
}