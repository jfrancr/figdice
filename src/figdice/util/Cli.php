<?php
namespace figdice\util;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \FilesystemIterator;
use figdice\util\CommandLine;
use figdice\exceptions\XMLParsingException;
use figdice\exceptions\DictionaryDuplicateKeyException;
use figdice\classes\Dictionary;
use figdice\classes\TagFigDictionary;
use figdice\View;


class Cli
{

  public static function tty($fd)
  {
    return function_exists('posix_isatty') && posix_isatty($fd);
  }
  public static function main()
  {
    $arguments = CommandLine::parseArgs(null, array('clean', 'clean-only'));
    
    if (isset($arguments[0])) {
      if ($arguments[0] == 'compile') {
        if (isset($arguments[1])) {
          if ($arguments[1] == 'dict') {
            if (isset($arguments[2])) {
              exit(self::compileDictionaries($arguments[2], isset($arguments['output']) ? $arguments['output'] : null));
            }
          }
          else if ($arguments[1] == 'view') {
            if (isset($arguments[2])) {
              exit(self::compileViews($arguments[2], isset($arguments['output']) ? $arguments['output'] : null));
            }
          }
        }
      }
      else if ($arguments[0] == 'check') {
        if (isset($arguments[1]) && ($arguments[1] == 'dict')) {
          if (isset($arguments[2])) {
            exit(self::checkDictionaries($arguments[2]));
          }
        }
      }
    }
    exit(self::usage());
  }

  private static function usage()
  {
    $usage = <<<STRING
usage:

figdice.phar {GREEN}compile dict{RESET} [options] <languageFolder>

   --output=<folder>   Output folder for compiled dictionaries.
                       Defaults to <languageFolder>.

   --clean             Removes all the .figdic files in target folder.

   --clean-only        Removes all the .figdic files in target folder, but
                       do not compile files.


figdice.phar {GREEN}compile view{RESET} [options] <sourceFolder>

   --output=<folder>   Output folder for compiled views.
                       Defaults to <sourceFolder>.

   --clean             Removes all the .fig files in target folder.

   --clean-only        Removes all the .fig files in target folder, but
                       do not compile files.


figdice.phar {GREEN}check dict{RESET} <dictionariesFolder>

      
      

STRING;
    
    if (self::tty(STDERR)) {
      $usage = str_replace('{GREEN}', "\e[92m", $usage);
      $usage = str_replace('{RESET}', "\e[0m", $usage);
    }
    else {
      $usage = str_replace('{GREEN}', '', $usage);
      $usage = str_replace('{RESET}', '', $usage);
    }
    
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
  private static function YELLOW($string, $fd) {  return self::COLORIZE(93, $string, $fd); }
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
      
      $targetFile =  preg_replace(';^'.
        preg_replace(';/$;', '', $sourceFolder).';',
        preg_replace(';/$;', '', $targetFolder), $sourceFile). '.figdic';
      
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
          
          $result = Dictionary::compile($sourceFile, $targetFile);
          if ($result)
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
      echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
      return 1;
    }
    
    echo(self::BLACK_ON_GREEN('OK', STDOUT) . PHP_EOL);
    return 0;
  }


  /**
   * @param string $sourceFolder
   * @param string $targetFolder
   * @return integer
   */
  private static function compileViews($sourceFolder, $targetFolder = null)
  {
    if (! $targetFolder) {
      $targetFolder = $sourceFolder;
    }
    else {
      // If target dir does not exist, attempt to create it.
      if (! file_exists($targetFolder)) {
        if (! mkdir($targetFolder, 0755, true)) {
          file_put_contents('php://stderr', 'Failed to create target directory: ' . $targetFolder . PHP_EOL . PHP_EOL);
          echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
          return 1;
        }
      }
    }
  
  
    // Should we clean first?
    if (CommandLine::getBoolean('clean') || CommandLine::getBoolean('clean-only')) {
  
  
      echo('Cleaning .fig files in target folder...' . PHP_EOL);
      try {
        $iterator = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($targetFolder,
            FilesystemIterator::SKIP_DOTS),
          RecursiveIteratorIterator::LEAVES_ONLY |
          RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file => $fileinfo) {
          if (substr($file, -4) == '.fig') {
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
  
      
      $targetFile =  preg_replace(';^'.
        preg_replace(';/$;', '', $sourceFolder).';', 
        preg_replace(';/$;', '', $targetFolder), $sourceFile). '.fig';

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
  
          $view = new View();
          $view->loadFile($sourceFile);
          // Specify temppath after Load, so as to force a reload
          // of the source, even if there is already a compiled version present.
          $view->setTempPath(dirname($targetFile));
          $compiledView = $view->compile();
          
          // Do not compile Dictionaries.
          if ($compiledView->getRootTag() instanceof TagFigDictionary) {
            continue;
          }
           
          $success = @ $view->saveCompiled();
          if ($success) {
            echo('  ['.self::GREEN('OK', STDOUT).']  ' . $sourceFile . PHP_EOL);
          }
          else {
            $failed = true;
            echo('  ['.self::RED('ERR', STDOUT).'] ' . $sourceFile . PHP_EOL);
            file_put_contents('php://stderr', 'Failed to overwrite file: ' . $targetFile . PHP_EOL . PHP_EOL);
          }
        }
  
  
      } catch (XMLParsingException $ex) {
        $failed = true;
        file_put_contents('php://stderr', '  ['.self::RED('ERR', STDERR).'] ' . $targetFile . PHP_EOL);
        file_put_contents('php://stderr', $ex->getMessage() . PHP_EOL . PHP_EOL);
      }
    }
  
    if ($failed) {
      echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
      return 1;
    }
  
    echo(self::BLACK_ON_GREEN('OK', STDOUT) . PHP_EOL);
    return 0;
  }

  

  /**
   * @param string $sourceFolder
   * @return integer
   */
  private static function checkDictionaries($sourceFolder)
  {
    // Remove trailing slash
    $sourceFolder = preg_replace(';/+$;', '', $sourceFolder);
    
    //Dectect languages:
    $languageCodes = glob($sourceFolder . '/*', GLOB_ONLYDIR);
    
    if (empty($languageCodes)) {
      file_put_contents('php://stderr', 'Nothing to do.' . PHP_EOL);
      return 0;
    }


    $languages = array();
    foreach ($languageCodes as $languageDir) {
      $languages []= basename($languageDir);
    }
    echo('Working on languages: ' . self::YELLOW(implode(', ', $languages), STDOUT) . PHP_EOL . PHP_EOL);
    
    
    
    $allFiles = array();
    $allDics = array();
    foreach ($languageCodes as $languageDir) {
      $allFiles[$languageDir] = array();
      $allDics[$languageDir] = array();
      
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($languageDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME),
        RecursiveIteratorIterator::SELF_FIRST | RecursiveIteratorIterator::LEAVES_ONLY
      );
      
      foreach($iterator as $item) {
        // store every filename (relative to the current language dir)
        if (substr($item, -strlen('.xml.figdic')) == '.xml.figdic') {
          $dicfile = substr($item, strlen($languageDir) + 1);
          $allFiles[$languageDir] []= $dicfile;
          $dic = unserialize(file_get_contents($item));
          $allDics[$languageDir][$dicfile] = array_keys($dic);
        }
      }
    }

    
    // Check that every language contains every file
    $failed = false;
    foreach ($allFiles as $languageDir => $files) {
      foreach ($files as $file) {
        foreach ($allFiles as $otherLang => $otherFiles) {
          if ($otherLang == $languageDir)
            continue;
          if (! in_array($file, $otherFiles)) {
            $failed = true;
            file_put_contents('php://stderr', '  ['.self::RED('ERR', STDERR).'] File ' . $file . ' missing in ' . basename($otherLang) . PHP_EOL);
            continue;
          }
        }
      }
    }

    if ($failed) {
      echo(self::ON_RED('FAILED', STDOUT) . PHP_EOL);
      return 1;
    }
    
    
    // Now check that every key in every file
    // exists in same file of every other lang
    $missingKeys = array();
    foreach ($allDics as $languageDir => $dics) {
      foreach ($dics as $filename => $keys) {
        foreach ($keys as $key) {
          foreach ($allDics as $otherLang => $dummy) {
            if ($otherLang == $languageDir)
              continue;
            if (! in_array($key, $allDics[$otherLang][$filename], true)) {
              $failed = true;
              $missingKey = $key.'#'.$otherLang.'#'.$filename;
              // Don't display twice the same error
              if (! in_array($missingKey, $missingKeys)) {
                file_put_contents('php://stderr', '  ['.self::RED('ERR', STDERR).'] Key "' . $key . '" missing in ' . self::RED(basename($otherLang), STDERR) .'/' . $filename . PHP_EOL);
                $missingKeys []= $missingKey;
                continue;
              }
            }
          }
        }
      }
    }
    
    echo(self::BLACK_ON_GREEN('OK', STDOUT) . PHP_EOL);
    return 0;
    
  }
}
