<?php
/**
 * MemoryIndexStorageTest
 *
 * @package   test_autoload
 * @author    M.Olszewski
 * @since     2010-03-30
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once 'PHPUnit/Framework.php';
require_once 'src/autoload/MemoryIndexStorage.php';


/**
 * Test class for {@link autoload_MemoryIndexStorage} class.
 *
 * @author  M.Olszewski
 * @package test_autoload
 */
class test_autoload_MemoryIndexStorageTest extends PHPUnit_Framework_TestCase
{
  public function testStore_EmptyArray()
  {
    $storage = new autoload_MemoryIndexStorage();
    $storage->store(array());

    self::assertSame(array(), $storage->load());
  }

  public function testStore_NonEmptyArray()
  {
    $stored = array('Class1' => 'file1.php', 'Class2' => 'file2.php');

    $storage = new autoload_MemoryIndexStorage();
    $storage->store($stored);

    self::assertSame($stored, $storage->load());
  }

  public function testStore_RepeatedStore()
  {
    $stored = array('Class1' => 'file1.php', 'Class2' => 'file2.php');

    $storage = new autoload_MemoryIndexStorage();

    $storage->store($stored);
    self::assertSame($stored, $storage->load());

    $storage->store($stored);
    self::assertSame($stored, $storage->load());

    $storage->store($stored);
    self::assertSame($stored, $storage->load());
  }

  public function testStore_TwoDifferentStores()
  {
    $stored1 = array('Class1' => 'file1.php', 'Class2' => 'file2.php');
    $stored2 = array('ClassA' => 'fileA.php', 'ClassB' => 'fileC.php', 'ClassD' => 'fileX.php');

    $storage = new autoload_MemoryIndexStorage();

    $storage->store($stored1);
    self::assertSame($stored1, $storage->load());

    $storage->store($stored2);
    self::assertSame($stored2, $storage->load());
  }

  public function testLoad_AfterConstruct()
  {
    $storage = new autoload_MemoryIndexStorage();

    self::assertSame(array(), $storage->load());
  }

  public function testLoad_RepeatedLoad()
  {
    $stored = array('Class1' => 'file1.php', 'Class2' => 'file2.php');

    $storage = new autoload_MemoryIndexStorage();
    $storage->store($stored);

    self::assertSame($stored, $storage->load());
    self::assertSame($stored, $storage->load());
    self::assertSame($stored, $storage->load());
  }
}
