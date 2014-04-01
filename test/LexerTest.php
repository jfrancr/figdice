<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.2
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
use figdice\exceptions\LexerUnexpectedCharException;
use figdice\View;
use figdice\classes\File;
use figdice\classes\ViewElementTag;

/**
 * Unit Test Class for basic Lexer expressions
 */
class LexerTest extends PHPUnit_Framework_TestCase {

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		// A Lexer object needs to live inside a View,
		// and be bound to a ViewElementTag instance.
		// They both need to be bound to a File object,
		// which must respond to the getCurrentFile method.

		$view = $this->getMock('\\figdice\\View');
		$viewFile = $this->getMock('\\figdice\\classes\\File', null, array('PHPUnit'));
		$viewElement = $this->getMock('\\figdice\\classes\\ViewElementTag', array('getCurrentFile'), array(& $view, 'testtag', 12));
		$viewElement->expects($this->any())
			->method('getCurrentFile')
			->will($this->returnValue($viewFile));

		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($viewElement);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		return $lexer->evaluate($viewElement);
	}


	public function testTrue()
	{
		$this->assertTrue($this->lexExpr( 'true' ));
	}

	public function testEmptyExpressionIsFalse()
	{
		$this->assertFalse($this->lexExpr( '' ));
	}

	/**
	 * @expectedException \figdice\exceptions\LexerUnexpectedCharException
	 */
	public function testParseErrorThrowsException()
	{
		$lexer = new Lexer('2 == true=');
		$this->lexExpr( '2 == true=' );
		$this->assertFalse(true, 'An expected exception was not thrown');
	}

	public function test1Plus1()
	{
		$this->assertEquals(2, $this->lexExpr( '1+1' ));
	}

	public function testMul()
	{
		$this->assertEquals(28, $this->lexExpr( '4*7' ));
	}

	public function testDiv()
	{
		$this->assertEquals(13, $this->lexExpr( '39 div 3' ));
	}

	public function testArithmeticPriority()
	{
		$this->assertEquals(15, $this->lexExpr( '39 div 3 + 2' ));
	}

	public function testParentheses()
	{
		$this->assertEquals(13, $this->lexExpr( '(35 + 4) div (4 - 1)' ));
	}

	public function testLogicalAnd()
	{
		$this->assertEquals(true, $this->lexExpr( '(35 + 4) and true' ));
	}
	public function testLogicalNot()
	{
		$this->assertTrue($this->lexExpr( 'not(false)' ));
	}
	
	public function testLogicalOr()
	{
		$this->assertFalse($this->lexExpr( "false or 0 or '' " ));
		$this->assertTrue($this->lexExpr( "false or 0 or 'a' " ));
		$this->assertTrue($this->lexExpr( "true or 0 or '' " ));
		$this->assertTrue($this->lexExpr( "false or 12 or '' " ));
	}
	
	public function testComparatorEquals()
	{
		$this->assertEquals(true, $this->lexExpr( '(35 + 4) == 39' ));
	}

	public function testComparatorNotEquals()
	{
		$this->assertEquals(true, $this->lexExpr( '4 != 5' ));
	}
	
	public function testExplicitPositiveNumber()
	{
		$this->assertEquals(13, $this->lexExpr(' +12 +1'));
	}
	public function testNegativeLiteral()
	{
		$this->assertEquals(-3, $this->lexExpr(' - 3'));
		$this->assertEquals(-2.86, $this->lexExpr(' - 3 + 0.14'));
		$this->assertEquals(-3.14, $this->lexExpr(' - 3 - 0.14'));
	}
	public function testAlphanumCompare()
	{
		$this->assertTrue($this->lexExpr(" 'abc' == 'abc' "));
	}
	public function testAlphanumIsCaseSensitive()
	{
		$this->assertTrue($this->lexExpr(" 'abc' != 'ABC' "));
	}
	public function testEmptyStringIsNotZero()
	{
		$this->assertFalse($this->lexExpr(" '' == 0 "));		
	}
	public function testChainingComparisons()
	{
		$this->assertEquals(true, $this->lexExpr( '(35 + 4) == 39 and 3 == (4 - 1)' ));
	}

	public function testPriorityOfArithmeticOverComparison()
	{
		$this->assertTrue($this->lexExpr(  ' 3 == 2 + 1' ));
	}

	public function testDecimalMulInt()
	{
		$this->assertEquals(19.47*2, $this->lexExpr(" 19.47 * 2 "));
	}
	public function testDecimalPlusInt()
	{
		$this->assertEquals(19.47+2, $this->lexExpr(" 19.47 + 2 "));
	}
	
	/**
	 * @expectedException \figdice\exceptions\FunctionNotFoundException
	 */
	public function testParserWithUndefinedFunction()
	{
		$this->assertFalse($this->lexExpr( ' somefunc(12) ' ));
	}
	
	/**
	 * @todo Currently the Lexer is not able to identify that
	 * the literal .14 is in fact 0.14 and we are required
	 * to explicity not omit the leading zero.
	 * Thus this test should fail as soon as lexer fixed.
	 */
	public function testDecimalLiteralIsAllowedToStartWithDot()
	{
		$this->assertNotEquals(.14, $this->lexExpr('.14'));
	}
	
	public function testDynamicPathParsing()
	{
		//Null because the said path values don't exist in Universe.
		$this->assertNull($this->lexExpr('/a/b/[c]'));
	}
}
