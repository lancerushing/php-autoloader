<?php
/**
 * AutoLoader
 *
 * @package   autoload
 * @author    M.Olszewski
 * @since     2010-03-24
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */



require_once dirname(__FILE__) . '/FileScanner.php';
require_once dirname(__FILE__) . '/IndexStorage.php';



/**
 * Class responsible for providing auto-loading capabilities.
 *
 * Provides:
 * <ul>
 * <li>Use of multiple index storages (see: {@link addIndexStorage()})</li>
 * <li>Simple way to create index storages (see: {@link scanAndStore()})</li>
 * </ul>
 *
 * @author  M.Olszewski
 * @package autoload
 */
class autoload_AutoLoader
{
  /**
   * @var array
   */
  private $storages = array();
  /**
   * @var array
   */
  private $index = array();


  /**
   * Constructs instance of {@link autoload_Autoloader}.
   */
  public function __construct()
  {
    // Intentionally left empty.
  }

  /**
   * Adds specified {@link autoload_IndexStorage} to this {@link autoload_AutoLoader} if it is not already added.
   *
   * It also uses specified index storage to load its index content so it can be used during class auto-loading.
   *
   * @param autoload_IndexStorage $storage Index storage to add.
   *
   * @return boolean Returns true if index storage was added, false otherwise.
   */
  public function addIndexStorage(autoload_IndexStorage $storage, $checkUniqueness = false)
  {
    if (false == is_bool($checkUniqueness))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $checkUniqueness is not a boolean! $checkUniqueness=' . $checkUniqueness);
    }

    $added = false;
    if (false == in_array($storage, $this->storages))
    {
      $content = $storage->load();

      if (false == is_array($content))
      {
        throw new UnexpectedValueException(__METHOD__ . '(): autoload_IndexStorage::load() returned value of non-array type! Returned $content=' . $content);
      }

      if (false == empty($content))
      {
        if ($checkUniqueness)
        {
          $intersections = array_intersect_key($this->index, $content);
          if (false == empty($intersections))
          {
            throw new UnexpectedValueException(__METHOD__ . '(): Specified index storage contains class names that are already present in the index! duplicates: ' . var_export($intersections, true));
          }
        }

        $this->index = array_merge($this->index, $content);
      }

      $this->storages[] = $storage;
      $added = true;
    }

    return $added;
  }


  /**
   * Registers this class on the SPL autoloader stack.
   *
   * @return boolean Returns true if registration was successful, false otherwise.
   */
  public function register()
  {
    // as spl_autoload_register() disables __autoload() and this might be unwanted, we put it onto autoload stack
    if (function_exists('__autoload'))
    {
      spl_autoload_register('__autoload');
    }

    return spl_autoload_register(array($this, 'classAutoLoad'));
  }

  /**
   * Tries to autoload class with given name using all class indices associated with this autoloader.
   *
   * @param string $className Name of the class.
   *
   * @return boolean Returns true if class is loaded, false otherwise.
   */
  public function classAutoLoad($className)
  {
    assert('is_string($className)');

    if (class_exists($className, false) || interface_exists($className, false))
    {
      return false;
    }

    $path = isSet($this->index[$className])? $this->index[$className] : null;

    $found = false;
    if ($path !== null)
    {
      if (file_exists($path))
      {
        require_once $path;
        $found = true;
      }
    }

    return $found;
  }

  /**
   * Scans given paths using specified scanner and stores them using specified storage.
   *
   * @param string|array $paths All paths to scan. This parameter may be a string with single path or paths separated
   * by path separator or it can an array with multiple strings (each string is treated as single path, no
   * path separator is allowed).
   * @param autoload_FileScanner $scanner Scanner used to scan the paths.
   * @param autoload_IndexStorage $storage Storage used to store found index content.
   * @param boolean $enforceAbsolutePath Determines whether paths should be always stored as absolute.
   *
   * @return array Index content that has been found and stored.
   * @throws InvalidArgumentException if any of the parameters is invalid.
   */
  public function scanAndStore($paths,
                               autoload_FileScanner $scanner,
                               autoload_IndexStorage $storage,
                               $enforceAbsolutePath = false)
  {
    $content = $scanner->scan($paths, $enforceAbsolutePath);
    $storage->store($content);
    return $content;
  }

  /**
   * Gets array with all index storages.
   *
   * @return array Returns array with all index storages.
   */
  public function getIndexStorages()
  {
    return $this->storages;
  }

  /**
   * Gets array representing current index.
   *
   * @return array Returns array representing current index.
   */
  public function getIndex()
  {
    return $this->index;
  }
}
