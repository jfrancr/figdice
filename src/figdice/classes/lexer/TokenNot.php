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
use \figdice\classes\Tag;
use \figdice\classes\Renderer;

class TokenNot extends TokenOperator {
	/**
	 * @param string $sign
	 */
	public function __construct() {
		parent::__construct(self::PRIORITY_MINUS);
	}

	public function getNumOperands() {
		return 1;
	}

	public function evaluate(Tag $tag, Renderer $renderer)
	{
		$operand = $this->operands[0]->evaluate($tag, $renderer);
		return ! $operand;
	}
	
}
