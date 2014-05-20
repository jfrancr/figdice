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


use figdice\View;
use figdice\classes\File;
use figdice\classes\ViewElementTag;


/**
 * Unit Test Class for fig tags and attributes
 */
class FigXmlTest extends PHPUnit_Framework_TestCase {

	protected $view;

	protected function setUp() {
		$this->view = new View();
	}
	protected function tearDown() {
		$this->view = null;
	}


	public function testFigVoid()
	{
		$source = <<<ENDXML
<html>
	<br fig:void="true" />
	<hr fig:void="true">  </hr>
	<hr/>
</html>
ENDXML;

		$this->view->loadString($source);
		$expected = <<<ENDHTML
<html>
	<br>
	<hr>
	<hr />
</html>
ENDHTML;

		$this->assertEquals($expected, $this->view->render());
	}

	public function testFigAuto()
	{
		$source = <<<ENDXML
<html>
	<input name="name" fig:auto="true">
		<fig:attr name="id">myId</fig:attr>  
	</input>
</html>
ENDXML;

		$this->view->loadString($source);
		$expected = <<<ENDHTML
<html>
	<input name="name" id="myId" />
</html>
ENDHTML;

		$this->assertEquals($expected, $this->view->render());
	}

	public function testFigSingleFileSlotAndPlug() {
    $view = new View();
    $view->loadFile(__DIR__.'/resources/FigXmlSlot.xml');
    
    $output = $view->render();
    $expected = file_get_contents(__DIR__.'/resources/FigXmlSlotExpect.html');
    $this->assertEquals(trim($expected), trim($output));
	}
	
	public function testFigInclude()
	{
		$view = new View();
		$view->loadFile(__DIR__.'/resources/FigXmlInclude1.xml');
		
		$output = $view->render();
		$expected = file_get_contents(__DIR__.'/resources/FigXmlIncludeExpect.html');
		$this->assertEquals(trim($expected), trim($output));
	}
	
	public function testWalkIndexedArray()
	{
		$source = <<<ENDXML
<fig:x fig:walk="/data">
  <fig:x fig:text="."/>
</fig:x>
ENDXML;
		$this->view->loadString($source);
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->assertEquals("\n  a\n\n  b\n\n  c\n", $this->view->render());
	}

	public function testCompactWalkWithIndexedArrayAndText() {
		$this->view->mount('data',  array('a','b','c'));
		$source = <<<ENDXML
<fig:x fig:walk="/data" fig:text="first() + ' - ' + ."/>
ENDXML;
		$this->view->loadString($source);
		$result = $this->view->render();
		$this->assertEquals('1 - a - b - c', $result);
	}

	public function testLoadXMLwithUTF8AccentsAndDeclaredEntities()
	{
		$source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&#233;">
]>
<xml fig:mute="true">
  éà &eacute; €
</xml>
ENDXML;
		$this->view->loadString($source);
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->view->setReplacements(false);
		$this->assertEquals("éà é €", trim($this->view->render()) );
	}

	/**
	 * @expectedException figdice\exceptions\XMLParsingException
	 */
	public function testUndeclaredEntitiesRaiseException()
	{
		$source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE figdice [
  <!ENTITY eacute "&eacute;">
]>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
		$this->view->loadString($source);
		$this->view->mount('data', array('a', 'b', 'c'));
		$this->view->setReplacements(false);
		$this->assertEquals("éà &eacute; € &ocirc;", trim($this->view->render()) );
	}
	
	public function testHtmlEntitiesReplacementsByDefault() {
	    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
  éà &eacute; € &ocirc;
</xml>
ENDXML;
	    $this->view->loadString($source);
	    $this->view->mount('data', array('a', 'b', 'c'));
	    $this->assertEquals("éà é € ô", trim($this->view->render()) );
	}


	public function testHtmlEntitiesReplacementsKeepsAmpersandAndLt() {
	    $source = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>
<xml fig:mute="true">
&ocirc; &lt; &amp;lt;
</xml>
ENDXML;
	    $this->view->loadString($source);
	    $this->view->mount('data', array('a', 'b', 'c'));
	    $this->assertEquals("ô < &lt;", trim($this->view->render()) );
	}

	/**
	 * @expectedException \figdice\exceptions\RequiredAttributeException
	 */
	public function testMissingRequiredAttributeException() {
	  $source = <<<ENDXML
<xml>
  <fig:include />
</xml>
ENDXML;
	  $this->view->loadString($source);
	  $this->assertNull( $this->view->render() );
	}
}
