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

class Function_GET implements FigFunction {

	/**
	 * @param integer $arity
	 * @param array $arguments
	 * @param Anchor $anchor
	 */
	public function evaluate($arity, $arguments, Anchor $anchor) {
		if(isset($_GET)) {
			$varName = $arguments[0];
			if(isset($_GET[$varName])) {
				return $_GET[$varName];
			}
		}
		return false;
	}
}
