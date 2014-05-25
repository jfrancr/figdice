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

namespace figdice\classes\lexer;

use \figdice\classes\Anchor;

class TokenLParen extends TokenOperator {
	public function __construct() {
		parent::__construct(self::PRIORITY_LEFT_PAREN);
	}

	/**
	 * @return integer
	 */
	public function getNumOperands() {
		return 0;
	}

	/**
	 * @param Anchor $anchor
	 * @return mixed
	 */
	public function evaluate(Anchor $anchor) {
		throw new \Exception('Abnormal evaluation of left parenthesis token.');
	}
}
