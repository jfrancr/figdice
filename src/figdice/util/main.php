<?php

require_once dirname(__FILE__).'/../../../autoload.php';

use figdice\util\CommandLine;
use figdice\classes\Dictionary;
use figdice\exceptions\XMLParsingException;
use figdice\exceptions\DictionaryDuplicateKeyException;

$arguments = CommandLine::parseArgs();

if (isset($arguments[0])) {
  if ($arguments[0] == 'dict') {
    if (isset($arguments[1])) {
      if ($arguments[1] == 'compile') {
        if (isset($arguments[2])) {
          exit(compileDictionaries($arguments[2], isset($arguments['output']) ? $arguments['output'] : null));
        }
      }
    }
  }
}

exit(usage());

function usage()
{
  $usage = <<<STRING
usage: figdice.phar dict compile [options] <languageFolder>

    --output      Output folder for compiled dictionaries.
                  Defaults to source folder.


STRING;
  file_put_contents('php://stderr', $usage);
  return 1;
}

/**
 * @param string $sourceFolder
 * @param string $targetFolder
 * @return integer
 */
function compileDictionaries($sourceFolder, $targetFolder = null)
{
  if (! $targetFolder) {
    $targetFolder = $sourceFolder;
  }
  
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceFolder), 
    RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::SELF_FIRST
  );
  
  foreach ($iterator as $sourceFile => $sourceNode) {
    if (substr($sourceFile, -4) != '.xml') {
      continue;
    }
    
    $targetFile = preg_replace(';^'.$sourceFolder.';', $targetFolder, $sourceFile) . '.php';
    
    try {
      if(file_exists($targetFile)) {
        $success = @ unlink($targetFile);
        if (! $success) {
          file_put_contents('php://stderr', 'Failed to overwrite file: ' . $targetFile . PHP_EOL . PHP_EOL);
          return 1;
        }
      }
      Dictionary::compile($sourceFile, $targetFile);
      touch($targetFile, filemtime($sourceFile));
      echo('  OK  ' . $sourceFile . PHP_EOL);
    } catch (DictionaryDuplicateKeyException $ex) {
      echo('FAILED' . PHP_EOL);
      file_put_contents('php://stderr', '  ERR ' . $targetFile . PHP_EOL);
      file_put_contents('php://stderr', 'Duplicate key: ' . $ex->getKey() . PHP_EOL . PHP_EOL);
      return 1;
    } catch (XMLParsingException $ex) {
      echo('FAILED' . PHP_EOL);
      file_put_contents('php://stderr', '  ERR ' . $targetFile . PHP_EOL);
      file_put_contents('php://stderr', $ex->getMessage() . PHP_EOL . PHP_EOL);
      return 1;
    }
  }
  echo(PHP_EOL);
  return 0;
}
