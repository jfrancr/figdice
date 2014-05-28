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

namespace figdice\classes;

use figdice\exceptions\RequiredAttributeException;
use figdice\exceptions\DictionaryEntryNotFoundException;

class TagFigTrans extends TagFig {
	const TAGNAME = 'trans';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function validate()
	{
	}

	public function getAttributeSource()
	{
	  return $this->getAttribute('source');
	}
	public function getAttributeKey()
	{
	  return $this->getAttribute('key');
	}
	public function getAttributeDict()
	{
	  return $this->getAttribute('dict');
	}

	public function render(Renderer $renderer)
	{
	  if (! $this->checkFigCond($renderer))
	    return '';


	  //If a @source attribute is specified, and is equal to
	  //the view's target language, then don't bother translating:
	  //just render the contents.
	  $source = $this->getAttributeSource();
	  
	  //The $key is also needed in logging below, even if
	  //source = view's language, in case of missing value,
	  //so this is a good time to read it.
	  $key = $this->getAttributeKey();
	  $dictionaryName = $this->getAttributeDict();
	  
	  $targetLanguage = $renderer->getRootView()->getLanguage();

    if($source == $targetLanguage) {
	    $value = $this->renderChildren($renderer);
    }
	  
	  else {
	  //Cross-language dictionary mechanism:
	  
	    if(null == $key) {
	      //Missing @key attribute : consider the text contents as the key.
	      //throw new SyntaxErrorException($this->getCurrentFile()->getFilename(), $this->xmlLineNumber, $this->name, 'Missing @key attribute.');
	      $key = $this->renderChildren($renderer);
	    }
	    //Ask current file to translate key:
	    try {
	      $value = $renderer->translate($key, $dictionaryName);
	    } catch(DictionaryEntryNotFoundException $ex) {
	      $logger = LoggerFactory::getLogger('Dictionary');
	      if ($logger)
	        $logger->error('Translation not found: key=' . $key . ', dictionary=' . $dictionaryName . ', language=' . $targetLanguage . ', file=' . $renderer->getView()->getFilename() . ', line=' . $this->xmlLineNumber);
	      return $key;
	    }
	  }
	  
	  
	  //Fetch the parameters specified as immediate children
	  //of the trans call : <fig:param name="" value=""/>
	  //TODO: Currently, the <fig:param> of a macro call cannot hold any fig:cond or fig:case conditions.
	  $arguments = array();
	  if (count($this->children)) {
  	  foreach ($this->children as $child) {
	      if($child instanceof TagFigParam) {
      	  //If param is specified with an immediate value="" attribute :
    	    if($child->hasAttribute('value')) {
      	    $arguments[$child->getAttribute('name')] = $renderer->evaluate($child->getAttribute('value'), $this);
    	    }
  	      //otherwise, the actual value is not scalar but is
  	      //a nodeset in itself. Let's pre-render it and use it as text for the argument.
  	      else {
    	      $arguments[$child->getAttribute('name')] = $child->render($renderer);
      	  }
     	  }
   	  }
	  }
	  
    //We must now perform the replacements of the parameters of the translation,
    //which are written in the shape : {paramName}
    //and are specified as extra attributes of the fig:trans tag, or child fig:param tags
    //(fig:params override inline attributes).
    $matches = array();
    while(preg_match('/{([^}]+)}/', $value, $matches)) {
      $attributeName = $matches[1];
      //If there is a corresponding fig:param, use it:
      if(array_key_exists($attributeName, $arguments)) {
        //TODO: is this utf8_encode necessary??
        $attributeValue = utf8_encode($arguments[$attributeName]);
      }
      //Otherwise, use the inline attribute.
      else {
        $expression = $this->getAttribute($attributeName);
        $attributeValue = null;
        if($expression) {
          $attributeValue = $renderer->evaluate($expression, $this);
        }
      }
  	  $value = str_replace('{' . $attributeName . '}', $attributeValue, $value);
	  }
	  
	  
	  //If the translated value is empty (ie. we did find an entry in the proper dictionary file,
	  //but this entry has an empty value), it means that the entry remains to be translated by the person in charge.
	  //So in the meantime we output the key.
	  if($value == '') {
  	  $value = $key;
  	  $logger = LoggerFactory::getLogger('Dictionary');
  	  if ($logger)
  	    $logger->error(
    	  'Empty translation: key=' . $key .
    	  ', dictionary=' . $dictionaryName .
    	  ', language=' . $targetLanguage .
    	  ', file=' . $renderer->getView()->getFilename() . ', line=' . $this->xmlLineNumber);
	  }
		return $value;

	}

	private function renderChildren(Renderer $renderer)
	{
	  $childrenAppender = '';
	  if (count($this->children)) {
	    foreach ($this->children as $child) {
	      // Nothing to render for a fig:param, because we have already taken care of them
	      // in the parent section.
	      if ($child instanceof TagFigParam)
	        continue;

	      $childAppender = $child->render($renderer);
	      $childrenAppender .= $childAppender;
	    }
	  }
	  return $childrenAppender;
	}
	
}
