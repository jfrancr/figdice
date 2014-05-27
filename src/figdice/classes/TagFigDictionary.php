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

class TagFigDictionary extends TagFig {
	const TAGNAME = 'dictionary';

	public function __construct($xmlLineNumber) {
		parent::__construct(null, $xmlLineNumber);
	}
	
	public function getAttributeName()
	{
	  return $this->getAttribute('name');
	}
	public function getAttributeFile()
	{
	  return $this->getAttribute('file');
	}
	public function getAttributeSource()
	{
	  return $this->getAttribute('source');
	}

	public function render(Renderer $renderer) 
	{
	  if (! $this->checkFigCond($renderer))
	    return '';

	  /**
	   * Loads a language XML file, to be used within the current view.
	   */
    //If a @source attribute is specified,
    //it means that when the target (view's language) is the same as @source,
    //then don't bother loading dictionary file, nor translating: just render the tag's children.
  
    $file = $this->getAttributeFile();
    $targetLanguage = $renderer->getRootView()->getLanguage();
    $filename = $renderer->getRootView()->getTranslationPath() . DIRECTORY_SEPARATOR . $targetLanguage . DIRECTORY_SEPARATOR . $file;
  
    $name = $this->getAttributeName();
    $dictionary = new Dictionary($filename);
    $source = $this->getAttributeSource();
  
    if( $source == $targetLanguage ) {
      // Don't load !
      return '';
    }

    //TODO: Please optimize here: cache the realpath of the loaded dictionaries,
    //so as not to re-load an already loaded dictionary in same View hierarchy.
  
  
    try {
      //Determine whether this dictionary was pre-compiled:
      $tmpPath = $renderer->getRootView()->getTempPath();
      if($tmpPath) {
        $tmpFile = $tmpPath . DIRECTORY_SEPARATOR . 'Dictionary' . DIRECTORY_SEPARATOR . $targetLanguage . DIRECTORY_SEPARATOR . $file . '.figdic';
        //If the tmp file already exists,
        if(file_exists($tmpFile)) {
          //but is older than the source file,
          if(filemtime($tmpFile) < filemtime($filename)) {
            Dictionary::compile($filename, $tmpFile);
          }
        }
        else {
          Dictionary::compile($filename, $tmpFile);
        }
        $dictionary->restore($tmpFile);
      }
  
      //If we don't even have a temp folder specified, load the dictionary for the first time.
      else {
        $dictionary->load();
      }
    } catch(FileNotFoundException $ex) {
      throw new FileNotFoundException('Translation file not found: file=' . $filename .
        ', language=' . $this->getView()->getLanguage() .
        ', source=' . $this->getCurrentFilename(),
        $this->getCurrentFilename() );
    } catch(DictionaryDuplicateKeyException $ddkex) {
      $this->getLogger()->error('Duplicate key: "' . $ddkex->getKey() . '" in dictionary: ' . $ddkex->getFilename());
    }
  
  
    //Hook the dictionary to the current file.
    //(in fact this will bubble up the message as high as possible, ie:
    //to the highest parent which does not bear a dictionary of same name)
    $renderer->addDictionary($dictionary, $name);
	  
	  return '';
	}
}
