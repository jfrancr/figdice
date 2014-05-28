<?php
namespace figdice\util;

use figdice\util\CommandLine;
use figdice\classes\Dictionary;
use figdice\exceptions\XMLParsingException;
use figdice\exceptions\DictionaryDuplicateKeyException;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \FilesystemIterator;


class Cli
{

  public static function tty($fd)
  {
    return function_exists('posix_isatty') && posix_isatty($fd);
  }
  public static function main()
  {
    $arguments = CommandLine::parseArgs();
    
    if (isset($arguments[0])) {
      if ($arguments[0] == 'compile') {
        if (isset($arguments[1])) {
          if ($arguments[1] == 'dict') {
            if (isset($arguments[2])) {
              exit(self::compileDictionaries($arguments[2], isset($arguments['output']) ? $arguments['output'] : null));
            }
          }
        }
      }
    }
    print_r($arguments);
    exit(self::usage());
  }

  private static function usage()
  {
    $usage = <<<STRING
usage: figdice.phar compile dict [options] <languageFolder>

    --output      Output folder for compiled dictionaries.
                  Defaults to <languageFolder>.

    --clean       Removes all the .figdic files in target folder.

    --clean-only  Removes all the .figdic files in target folder, but
                  do not compile files.


STRING;
    file_put_contents('php://stderr', $usage);
    return 1;
  }

  private static function COLORIZE($color, $string, $fd)
  {
    return self::tty($fd) ?
    "\e[".$color."m" . $string . "\e[0m"
      : $string;
  }
  
  private static function RED($string, $fd) {    return self::COLORIZE(91, $string, $fd); }
  private static function GREEN($string, $fd) {  return self::COLORIZE(92, $string, $fd); }
  private static function ON_RED($string, $fd) {  return self::COLORIZE(101, $string, $fd); }
  private static function BLACK_ON_GREEN($string, $fd) {  return self::COLORIZE(42, self::COLORIZE(30, $string, $fd), $fd); }
  
  /**
   * @param string $sourceFolder
   * @param string $targetFolder
   * @return integer
   */
  private static function compileDictionaries($sourceFolder, $targetFolder = null)
  {
    
    if (! $targetFolder) {
      $targetFolder = $sourceFolder;
    }


    // Should we clean first?
    if (CommandLine::getBoolean('clean') || CommandLine::getBoolean('clean-only')) {
      
      // If target dir does not exist, attempt to create it.
      if (! file_exists($targetFolder)) {
        mkdir($targetFolder, 0755, true);
      }
      
      echo('Cleaning .figdic files in target folder...' . PHP_EOL);
      try {
        $iterator = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($targetFolder, 
            FilesystemIterator::SKIP_DOTS), 
          RecursiveIteratorIterator::LEAVES_ONLY | 
          RecursiveIteratorIterator::SELF_FIRST 
        );
        foreach ($iterator as $file => $fileinfo) {
          if (substr($file, -7) == '.figdic') {
            unlink($file);
          }
        }
      } catch (\UnexpectedValueException $ex) {
        file_put_contents('php://stderr', 'Failed to open directory: ' . $targetFolder . PHP_EOL . PHP_EOL);
        echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
        return 1;
      }
      
      // If clean-only, we're all done!
      if (CommandLine::getBoolean('clean-only')) {
        echo(self::BLACK_ON_GREEN('OK', STDOUT) . PHP_EOL);
        return 0;
      }
      
    }

    

    try {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceFolder), 
        RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::SELF_FIRST
      );
    } catch (\UnexpectedValueException $ex) {
      file_put_contents('php://stderr', 'Failed to open directory: ' . $sourceFolder . PHP_EOL . PHP_EOL);
      echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
      return 1;
    }

    $failed = false;
    
    foreach ($iterator as $sourceFile => $sourceNode) {
      if (substr($sourceFile, -4) != '.xml') {
        continue;
      }
      
      $targetFile = preg_replace(';^'.$sourceFolder.';', $targetFolder, $sourceFile) . '.figdic';
      
      try {
        
        // Attempt to compile only if target file is older
        // that source file
        if ( (! file_exists($targetFile)) || (filemtime($sourceFile) > filemtime($targetFile)) ) {
          if(file_exists($targetFile)) {
            $success = @ unlink($targetFile);
            if (! $success) {
              $failed = true;
              file_put_contents('php://stderr', 'Failed to overwrite file: ' . $targetFile . PHP_EOL . PHP_EOL);
            }
          }
          
          Dictionary::compile($sourceFile, $targetFile);
          echo('  ['.self::GREEN('OK', STDOUT).']  ' . $sourceFile . PHP_EOL);
        }
        
      } catch (DictionaryDuplicateKeyException $ex) {
        $failed = true;
        file_put_contents('php://stderr', '  ['.self::RED('ERR', STDERR).'] ' . $targetFile . PHP_EOL);
        file_put_contents('php://stderr', 'Duplicate key: ' . $ex->getKey() . PHP_EOL . PHP_EOL);
        
      } catch (XMLParsingException $ex) {
        $failed = true;
        file_put_contents('php://stderr', '  ['.self::RED('ERR', STDERR).'] ' . $targetFile . PHP_EOL);
        file_put_contents('php://stderr', $ex->getMessage() . PHP_EOL . PHP_EOL);
      }
    }
    
    if ($failed) {
      echo(self::BLACK_ON_GREEN('FAILED', STDOUT) . PHP_EOL);
      return 1;
    }
    
    echo(self::BLACK_ON_GREEN('OK', STDOUT) . PHP_EOL);
    return 0;
  }

}
