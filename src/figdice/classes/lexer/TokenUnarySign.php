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

class TokenUnarySign extends TokenOperator {
	/**
	 * @var string
	 */
	private $sign;

	/**
	 * @param string $sign
	 */
	public function __construct($sign) {
		parent::__construct(self::PRIORITY_MINUS);
		$this->sign = $sign;
	}

	public function getNumOperands() {
		return 1;
	}

	/**
	 * @param ViewElement $viewElement
	 * @return mixed
	 */
	public function evaluate(ViewElementTag $viewElement) {
		if($this->sign == '-')
			return (- $this->operands[0]->evaluate($viewElement));
		return $this->operands[0]->evaluate($viewElement);
	}
}
