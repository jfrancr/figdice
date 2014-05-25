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

use figdice\classes\lexer\Lexer;
use figdice\classes\Anchor;
use figdice\exceptions\LexerUnexpectedCharException;

/**
 * Unit Test Class for basic Lexer expressions
 */
class ExpressionsTest extends PHPUnit_Framework_TestCase {

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		
		$anchor = new Anchor();
		$parseResult = $lexer->parse( $anchor );
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate($anchor);
	}


	public function testDivByZeroIsZero()
	{
		$this->assertEquals(0, $this->lexExpr( '39 div 0' ));
	}

	public function testFalseAndShortcircuit()
	{
		$this->assertEquals(false, $this->lexExpr( 'false and 12' ));
	}

	public function testStringConcat()
	{
		$this->assertEquals('a2', $this->lexExpr( "'a' + 2 " ));
	}
}
