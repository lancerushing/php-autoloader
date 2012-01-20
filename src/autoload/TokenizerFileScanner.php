<?php
/**
 * TokenizerFileScanner
 *
 * @package   autoload
 * @author    M.Olszewski
 * @since     2010-03-25
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once dirname(__FILE__) . '/FileScanner.php';


/**
 * Implementation of {@link autoload_FileScanner} interface that uses Tokenizer (basic PHP extension) to
 * detect class and interface names.
 *
 * @author  M.Olszewski
 * @package autoload
 */
class autoload_TokenizerFileScanner implements autoload_FileScanner
{
  /**
   * @var array
   */
  private $extensions = array();
  /**
   * @var array
   */
  private $exclusions = array();


  /**
   * Constructs instance of {@link autoload_TokenizerFileScanner}.
   *
   * @param boolean $useDefault Determines whether default extensions and exclusions should be used.
   *
   * @throws InvalidArgumentException if $useDefault parameter is invalid.
   */
  public function __construct($useDefault = true)
  {
    if (false == is_bool($useDefault))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $useDefault is not a boolean! $useDefault=' . $useDefault);
    }

    if ($useDefault)
    {
      $this->extensions = array(self::DEFAULT_EXTENSION_PHP, self::DEFAULT_EXTENSION_INC);
      $this->exclusions = array(self::DEFAULT_EXCLUSION_HIDDEN);
    }
  }


  /**
   * @see autoload_FileScanner::addExtension()
   */
  public function addExtension($extensions)
  {
    if (is_string($extensions))
    {
      $extensions = array($extensions);
    }
    if (false == is_array($extensions))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $extensions is not an array! $extensions=' . $extensions);
    }
    foreach ($extensions as $extension)
    {
      if (false == is_string($extension))
      {
        throw new InvalidArgumentException(__METHOD__ . '(): $extension is not a string! $extension=' . $extension);
      }
    }

    $this->extensions = self::mergeUniquely($this->extensions, $extensions);
  }

  /**
   * @see autoload_FileScanner::addExclusion()
   */
  public function addExclusion($exclusions)
  {
    if (is_string($exclusions))
    {
      $exclusions = array($exclusions);
    }
    if (false == is_array($exclusions))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $exclusions is not an array! $exclusions=' . $exclusions);
    }
    foreach ($exclusions as $exclusion)
    {
      if (false == is_string($exclusion))
      {
        throw new InvalidArgumentException(__METHOD__ . '(): $exclusion is not a string! $exclusion=' . $exclusion);
      }
    }

    $this->exclusions = self::mergeUniquely($this->exclusions, $exclusions);
  }

  private static function mergeUniquely(array& $array1, array& $array2)
  {
    $diff = array_diff($array2, $array1);
    return array_merge($array1, $diff);
  }

  /**
   * @see autoload_FileScanner::scan()
   */
  public function scan($paths, $enforceAbsolutePath = false)
  {
    if (is_string($paths))
    {
      $paths = explode(PATH_SEPARATOR, $paths);
    }
    if (false == is_array($paths))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $paths is not an array! $paths=' . $paths);
    }	
    // check if all paths are strings without PATH_SEPARATOR and they refer to existing directory/file
    foreach ($paths as $path)
    {
      if (false == is_string($path))
      {
        throw new InvalidArgumentException(__METHOD__ . '(): $path is not a string! $path=' . $path);
      }
      if (false !== strstr($path, PATH_SEPARATOR))
      {
        throw new InvalidArgumentException(__METHOD__ . '(): $path contains more than a single path - it is not allowed! $path=' . $path);
      }
      if (false == file_exists($path))
      {
        throw new InvalidArgumentException(__METHOD__ . '(): $path is not referencing existing file or directory! $path=' . $path);
      }
    }
    if (false == is_bool($enforceAbsolutePath))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $enforceAbsolutePath is not a boolean! $enforceAbsolutePath=' . $enforceAbsolutePath);
    }

    $class2File = array();

    foreach ($paths as $path)
    {
		
      $index = $this->scanPath($path, $enforceAbsolutePath);

      // detect duplicates - no class name can be present in existing indexes and index from scanned path
      $intersections = array_intersect_key($index, $class2File);
      if (false == empty($intersections))
      {
        throw new UnexpectedValueException(__METHOD__ . '(): ' . $path . ' contains class names that are already indexed! duplicates: ' . var_export($intersections, true));
      }

      // union
      $class2File = $class2File + $index;
    }

    return $class2File;
  }

  protected function scanPath($path, $enforceAbsolutePath)
  {
    $class2File = array();

    if (is_dir($path))
    {
      $this->scanDirectory($path, $class2File, $enforceAbsolutePath);
    }
    else
    {
      $this->scanSingleFile($path, $class2File, $enforceAbsolutePath);
    }

    return $class2File;
  }

  private function scanDirectory($dirName, array& $class2File, $enforceAbsolutePath)
  {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirName),
                                           RecursiveIteratorIterator::SELF_FIRST);

    foreach ($files as $fileName => $fileInfo)
    {
      if ($enforceAbsolutePath)
      {
        $fileName = $fileInfo->getRealPath();
      }
      if ($this->checkFile($fileInfo, $fileName))
      {
        $this->scanFileContent($fileName, $fileInfo, $class2File);
      }
    }
  }

  private function scanSingleFile($fileName, array& $class2File, $enforceAbsolutePath)
  {
    $fileInfo = new SplFileInfo($fileName);
    if ($enforceAbsolutePath)
    {
      $fileName = $fileInfo->getRealPath();
    }
    if ($this->checkFile($fileInfo, $fileName))
    {
      $this->scanFileContent($fileName, $fileInfo, $class2File);
    }
  }

  protected function checkFile(SplFileInfo $fileInfo, $fileName)
  {
    return $fileInfo->isFile() &&
           $fileInfo->isReadable() &&
           $this->checkIfIncluded($fileName) &&
           $this->checkIfNotExcluded($fileName);
  }

  private function checkIfIncluded($fileName)
  {
    $included = empty($this->extensions);
    foreach ($this->extensions as $extension)
    {
      if (substr($fileName, -strlen($extension)) === $extension)
      {
        $included = true;
        break;
      }
    }

    return $included;
  }

  private function checkIfNotExcluded($fileName)
  {
    $notExcluded = true;
    foreach ($this->exclusions as $exclusion)
    {
      if (0 != preg_match($exclusion, $fileName))
      {
        $notExcluded = false;
        break;
      }
    }

    return $notExcluded;
  }

  private function scanFileContent($fileName, SplFileInfo $fileInfo, array& $class2File)
  {
    $content = file_get_contents($fileName);
    $namespace_prefix = '';
    if (false === $content)
    {
      throw new RuntimeException(__METHOD__ . '(): cannot read file: ' . $fileName . '!');
    }

    $tokens = token_get_all($content);
    for($i = 0, $size = count($tokens); $i < $size; $i++)
    {
      switch($tokens[$i][0])
      {
        case T_NAMESPACE:
          $i += 2; //skip the whitespace token
          $namespace_prefix = $tokens[$i][1] . '\\';
          break;
        case T_CLASS:
        case T_INTERFACE:
        {
          $i += 2; //skip the whitespace token
          $className = $namespace_prefix . $tokens[$i][1];
          if (false == isSet($class2File[$className]))
          {
            $class2File[$className] = $this->dos2unix($fileName);
          }
          else
          {
            throw new UnexpectedValueException(__METHOD__ . '(): ' . $className . ' is already defined in file: '
                                               . $class2File[$className] . ' Please rename its duplicate found in ' . $fileName);
          }
        }
        break;
      }
    }
  }

  /*
   * We don't want to use Windows-style paths as they don't work on Unix systems.
   * On the other hand: Unix-style paths work fine on Windows - so we always store file names as Unix-style
   * paths.
   */
  private function dos2unix($fileName)
  {
    return preg_replace('/\\\/', '/', $fileName);
  }

  /**
   * Gets all extensions used by this {@link autoload_TokenizerFileScanner}.
   *
   * @return array Returns array with all extensions used by this {@link autoload_TokenizerFileScanner}.
   */
  public function getExtensions()
  {
    return $this->extensions;
  }

  /**
   * Gets all exclusion patterns used by this {@link autoload_TokenizerFileScanner}.
   *
   * @return array Returns array with all exclusion patterns used by this
   * {@link autoload_TokenizerFileScanner}.
   */
  public function getExclusions()
  {
    return $this->exclusions;
  }
}
