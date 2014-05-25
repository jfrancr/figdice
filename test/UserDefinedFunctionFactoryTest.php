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

use figdice\FigFunction;
use figdice\FunctionFactory;
use figdice\View;
use figdice\classes\lexer\Lexer;
use figdice\classes\ViewElementTag;
use figdice\classes\Anchor;
use figdice\classes\Tag;
use figdice\classes\Renderer;
use figdice\exceptions\LexerUnexpectedCharException;


/**
 * Unit Test Class for Function factory and custom Fig functions
 */
class UserDefinedFunctionFactoryTest extends PHPUnit_Framework_TestCase {

	protected $viewElement;

	protected function setUp() {
		$this->viewElement = null;
		$this->view = new View();
	}
	protected function tearDown() {
		$this->viewElement = null;
	}

	private function lexExpr($expression) {
		$lexer = new Lexer($expression);

		$view = $this->view;
		$renderer = $this->getMock('\\figdice\\classes\\Renderer');
		$renderer->expects($this->any())
		->method('getRootView')
		->will($this->returnValue($view));
		$renderer->expects($this->any())
		->method('getView')
		->will($this->returnValue($view));

		
		$anchor = new Anchor($renderer, __FILE__, __LINE__);
		
		// Make sure that the passed expression is successfully parsed,
		// before asserting stuff on its evaluation.
		$parseResult = $lexer->parse($anchor);
		$this->assertTrue($parseResult, 'parsed expression: ' . $lexer->getExpression());

		
		
		return $lexer->evaluate($anchor);
	}


	/**
	 * @expectedException \figdice\exceptions\FunctionNotFoundException
	 */
	public function testCustomFunctionBeforeRegThrowsException()
	{
		$this->lexExpr( "customFunc(12)" );
		$this->assertFalse(true);
	}

	public function testRegisteredCustomFunctionExecutes()
	{
		$view = $this->view;

		//Create an instance of our custom factory
		$functionFactory = new CustomFunctionFactory();

		//Register our nice factory
		$view->registerFunctionFactory($functionFactory);

		//and evaluate an expression wich invokes our function
		$result = $this->lexExpr( "customFunc(12)" );
		$this->assertEquals(12*2, $result);
	}

}

/**
 * This simple function factory handles one function: customFunc
 * and does not bother with caching.
 */
class CustomFunctionFactory extends FunctionFactory {
	public function create($funcName) {
		if ($funcName == 'customFunc') {
			return new MyCustomFigFunc();
		}
		return null;
	}
}

/**
 * This simple function accepts one argument, and
 * returns the argument multiplied by 2.
 */
class MyCustomFigFunc implements FigFunction {
	public function evaluate($arity, $arguments, Anchor $anchor = null) {
		if($arity < 1) {
			return null;
		}
		$firstArgument = $arguments[0];
		return 2 * $firstArgument;
	}
}
