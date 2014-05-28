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

namespace figdice\classes\functions;

use \figdice\FigFunction;
use \figdice\classes\Anchor;
use \figdice\LoggerFactory;

class Function_const implements FigFunction {

	/**
	 * @param integer $arity
	 * @param array $arguments one element: name of global constant, or class constant (myClass::myConst)
	 * @param Anchor $anchor
	 */
	public function evaluate($arity, $arguments, Anchor $anchor) {
		$constantName = trim($arguments[0]);
		
		if(preg_match('#([^:]+)::#', $constantName, $matches)) {
			$className = $matches[1];
			if(! class_exists($className)) {
				$logger = LoggerFactory::getLogger(__CLASS__);
				if ($logger) {
  				$logger->warning("Undefined class: $className in static: $constantName");
				}
				return null;
			}
		}
		
		//Global constant
		if(defined($constantName)) {
			return constant($constantName);
		}
		//Undefined symbol: error.
		else {
			$logger = LoggerFactory::getLogger(__CLASS__);
			if ($logger) {
  			$logger->warning("Undefined constant: $constantName");
			}
			return null;
		}
	}
}
