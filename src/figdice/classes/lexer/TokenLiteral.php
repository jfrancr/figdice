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

namespace figdice\classes\lexer;

use \figdice\classes\ViewElementTag;

class TokenLiteral extends Token {
	/**
	 * @var mixed
	 */
	public $value;
	/**
	 * @param mixed $value
	 */
	public function __construct($value) {
		parent::__construct();
		$this->value = $value;
	}
	/**
	 * @param ViewElement $viewElement
	 * @return mixed
	 */
	public function evaluate(ViewElementTag $viewElement) {
		return $this->value;
	}/*
	public function export() {
		$result = 'Tokenliteral::restore(';
		if(is_numeric($this->value)) {
			$result .= $this->value;
		}
		else {
			$result .= '\'' . $this->value . '\'';
		}
		return $result . ')';
	}
	public static function restore($value) {
		return new TokenLiteral($value);
	}*/
}
