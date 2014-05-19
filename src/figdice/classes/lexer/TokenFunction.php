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

use \figdice\FunctionFactory;
use \figdice\LoggerFactory;
use \figdice\classes\ViewElement;
use \figdice\classes\ViewElementTag;
use \figdice\classes\Tag;
use \figdice\classes\Renderer;
use \figdice\exceptions\FunctionNotFoundException;

class TokenFunction extends TokenOperator {
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var integer
	 */
	private $arity;

	/**
	 * @var Function
	 */
	private $function;
	/**
	 * Becomes true when the closing parenthesis is found.
	 * Indicates that the function accepts no more arguments.
	 * @var boolean
	 */
	private $closed;
	/**
	 * @param string $name
	 * @param integer $arity
	 */
	public function __construct($name, $arity) {
		parent::__construct(self::PRIORITY_FUNCTION);
		$this->name = $name;
		$this->arity = $arity;
		$this->function = null;
		$this->closed = false;
	}

	public function incrementArity() {
		++ $this->arity;
	}

	/**
	 * @return integer
	 */
	public function getNumOperands() {
		return $this->arity;
	}

	/**
	 * A Helper function to strong-type the items of functionFactories array.
	 * @return FunctionFactory
	 */
	private function iterateFactory(& $factories) {
		static $inProgress = false;
		if(! $inProgress) {
			$inProgress = true;
			reset($factories);
		}
		$item = each($factories);
		if(false === $item) {
			$inProgress = false;
			return null;
		}
		return $item['value'];
	}

	
	public function evaluate(Tag $tag, Renderer $renderer)
	{
	  if($this->function === null) {
	    //Instanciate the Function handler:
	    $factories = $renderer->getRootView()->getFunctionFactories();
	    if ( (null != $factories) && (is_array($factories) ) ) {
	      while(null != ($factory = $this->iterateFactory($factories)) ) {
	        if(null !== ($this->function = $factory->create($this->name)))
	          break;
	      }
	    }
	  
	    if($this->function == null) {
	      $logger = LoggerFactory::getLogger(__CLASS__);
	      $message = 'Undeclared function: ' . $this->name;
	      $logger->error($message);
	      throw new FunctionNotFoundException($this->name);
	    }
	  }

	  $arguments = array();
	  if($this->operands) {
	    foreach($this->operands as $operandToken) {
	      $arguments[] = $operandToken->evaluate($tag, $renderer);
	    }
	  }
	  

	  return $this->function->evaluate($tag, $renderer, $this->arity, $arguments);
	}

	/**
	 * @return boolean
	 */
	public function isClosed() {
		return $this->closed;
	}
	public function close() {
		$this->closed = true;
	}
}
