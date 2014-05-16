<?php
/**
 * @author Gabriel Zerbib <gabriel@figdice.org>
 * @copyright 2004-2014, Gabriel Zerbib.
 * @version 2.0.4
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

namespace figdice\classes;

class Compiler
{
  private $figNamespace;
  private $figNamespaceLength;
  private $rootTag;

  public function __construct($figNamespace)
  {
    $this->figNamespace = $figNamespace;
    $this->figNamespaceLength = strlen($figNamespace); 
  }
 
  public function compile(ViewElementTag $rootNode)
  {
    $this->rootTag = $this->compileNode($rootNode);
    return new CompiledView($this->figNamespace, $this->rootTag);
  }

  private function checkNsPrefix($string)
  {
    return ($this->figNamespace == substr($string, 0, $this->figNamespaceLength));
  }
  private function stripNs($string)
  {
    if ($this->checkNsPrefix($string)) {
      return substr($string, $this->figNamespaceLength);
    }
    return $string;
  }
  
  private function compileNode(ViewElementTag $node)
  {
    if ($node->getName() == $this->figNamespace.TagFigInclude::TAGNAME) {
      $tag = new TagFigInclude($node->getLineNumber());
    }
    else if ($node->getName() == $this->figNamespace.TagFigCData::TAGNAME) {
      $tag = new TagFigCData($node->getLineNumber());
    }
    else if ($node->getName() == $this->figNamespace.TagFigAttr::TAGNAME) {
      $tag = new TagFigAttr($node->getLineNumber());
    }
    else if ($node->getName() == $this->figNamespace.TagFigParam::TAGNAME) {
      $tag = new TagFigParam($node->getLineNumber());
    }
    else if ($node->getName() == $this->figNamespace.TagFigFeed::TAGNAME) {
      $tag = new TagFigFeed($node->getLineNumber());
    }
    else if ($this->checkNsPrefix($node->getName())) {
      $tag = new TagFig($this->stripNs($node->getName()), $node->getLineNumber());
    }
    else {
      $tag = new Tag($node->getName(), $node->getLineNumber());
    }
    
    foreach ($node->getAttributes() as $name => $value) {
      if ($this->checkNsPrefix($name)) {
        $tag->putFigAttribute($this->stripNs($name), $value);
      }
      else {
        $tag->putAttribute($name, $value);
      }
    }

    for ($i = 0; $i < $node->getChildrenCount(); ++ $i) {
      $child = $node->getChildNode($i);
      
      if ($child instanceof ViewElementTag) {
        $tag->addChild($this->compileNode($child));
      }
      else {
        $tag->addChild($this->compileCdata($child));
      }
    }

    // Detect mandatory attributes,
    // incompatible attributes,
    // errors in expressions, etc.
    $tag->validate();

    return $tag;
  }
  
  private function compileCdata(ViewElementCData $cdata)
  {
    return new CData($cdata->outputBuffer);
  }
}
