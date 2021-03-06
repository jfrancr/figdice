<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2015, Gabriel Zerbib.
 * @version 2.0.5
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

namespace figdice;

use figdice\classes\MockNullLogger;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * A bridge to the PSR3 LoggerInterface.
 */
class LoggerFactory {
	private static $delegate = null;

	public static function setDelegate(LoggerFactoryDelegate $delegate) {
		self::$delegate = $delegate;
	}

	/**
	 * Obtain a logger object for a given class.
	 * The logger itself complies with PSR-3 LoggerInterface,
	 * and you can use any third-party implementation.
	 * This factory class, and its Delegate companion, allows 
	 * one more level of flexibility, by specifying beforehand
	 * the class for which a Logger must be pulled. This allows you
	 * to decide beforehand (for example through configuration) on which
	 * classes you wish to activate logging (for example: your own
	 * custom functions, or some of your feeds, etc.)
	 *
	 * @param string $class The class name, or an object instance.
	 * @return LoggerInterface
	 */
	public static function getLogger($class) {
		if(null == self::$delegate) {
      // We instantiate an actual psr\log object only if there is
      // an implementation out there. In case there isn't one,
      // don't bother with logging: we don't want the FigDice phar to fail loading
      // just because logging dependency is not present.
      if (class_exists('Psr\Log\NullLogger', false)) {
        return new NullLogger();
      }
      else {
        return new MockNullLogger();
      }
		}
		return self::$delegate->getLogger($class);
	}
}

