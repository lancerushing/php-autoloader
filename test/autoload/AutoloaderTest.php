<?php
/**
 * AutoloaderTest
 *
 * @package   test_autoload
 * @author    M.Olszewski
 * @since     2010-04-12
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once 'PHPUnit/Framework.php';
require_once 'src/autoload/AutoLoader.php';


/**
 * Test class for {@link autoload_Autoloader} class.
 *
 * @author  M.Olszewski
 * @package test_autoload
 */
class test_autoload_AutoloaderTest extends PHPUnit_Framework_TestCase
{
  private $indexContent;


  public function setUp()
  {
    $this->indexContent = array('ClassA' => './test/cases/case1/File1_ClassA.php',
                                'InterfaceA' => './test/cases/case1/File5_InterfaceA.php',
                                'ClassB' => './test/cases/case1/File4_ClassB_NonDefaultExtension.php.class',
                                'InterfaceB' => './test/cases/case1/File6_InterfaceB_NonDefaultExtension.php.interface',);

    $this->removeAllAutoloadCallbacks();
  }

  private function removeAllAutoloadCallbacks()
  {
    $register = spl_autoload_functions();
    if (false == empty($register))
    {
      foreach ($register as $callback)
      {
        spl_autoload_unregister($callback);
      }
    }
  }

  public function testConstruct_Default()
  {
    // spl_autoload_functions might return false (SPL stack not initialised) or empty array
    $register = spl_autoload_functions();
    // ..but empty() will cover both cases
    self::assertTrue(empty($register));

    new autoload_AutoLoader();

    $register = spl_autoload_functions();
    self::assertTrue(empty($register));
  }

  public function testRegister_WithAndWithoutAutoload()
  {
    // part 1, no __autoload defined
    $register = spl_autoload_functions();
    self::assertTrue(empty($register));

    $autoloader = new autoload_AutoLoader();
    self::assertTrue($autoloader->register());

    $register = spl_autoload_functions();
    self::assertEquals(1, count($register));
    self::assertEquals(array($autoloader, 'classAutoLoad'), $register[0]);

    // clean-up, part 1 ended
    $this->removeAllAutoloadCallbacks();

    // part 2, __autoload is defined

    function __autoload($className)
    {
      return true;
    }

    $autoloader = new autoload_AutoLoader();
    self::assertTrue($autoloader->register());

    $register = spl_autoload_functions();
    self::assertEquals(2, count($register));
    self::assertEquals('__autoload', $register[0]);
    self::assertEquals(array($autoloader, 'classAutoLoad'), $register[1]);
  }

  public function testRegister_TwoDifferentAutoloaders()
  {
    $register = spl_autoload_functions();
    self::assertTrue(empty($register));

    // ok, __autoload might or might NOT be defined in testRegister_WithAndWithoutAutoload (depends on unit tests
    // execution order) so we have to take that into account
    $autoloadModifier = function_exists('__autoload')? 1 : 0;

    $autoloader1 = new autoload_AutoLoader();
    self::assertTrue($autoloader1->register());

    $register = spl_autoload_functions();
    self::assertEquals($autoloadModifier + 1, count($register));
    self::assertEquals(array($autoloader1, 'classAutoLoad'), $register[$autoloadModifier]);

    $autoloader2 = new autoload_AutoLoader();
    self::assertTrue($autoloader2->register());

    $register = spl_autoload_functions();
    self::assertEquals($autoloadModifier + 2, count($register));
    self::assertEquals(array($autoloader1, 'classAutoLoad'), $register[$autoloadModifier]);
    self::assertEquals(array($autoloader2, 'classAutoLoad'), $register[$autoloadModifier + 1]);
  }

  public function testAddIndexStorage_EmptyStorage()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue(array()));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage));

    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(array($storage), $autoloader->getIndexStorages());
  }

  public function testAddIndexStorage_NotEmptyStorage()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage));

    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage), $autoloader->getIndexStorages());
  }

  public function testAddIndexStorage_NotEmptyStorageWithUniquenessCheck()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage, true));

    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage), $autoloader->getIndexStorages());
  }

  public function testAddIndexStorage_SameStorageAddedTwice()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage));

    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage), $autoloader->getIndexStorages());

    self::assertFalse($autoloader->addIndexStorage($storage));
    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage), $autoloader->getIndexStorages());
  }

  public function testAddIndexStorage_TerminatesDueToInvalidTypeOfCheckUniqueness()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->never())->method('load');

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    try
    {
      $autoloader->addIndexStorage($storage, 'failure');
      self::fail();
    }
    catch (InvalidArgumentException $e)
    {
      // Intentionally left empty.
    }

    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));
  }

  public function testAddIndexStorage_TerminatesDueToInvalidTypeReturnedByStorageLoad()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue('oh no - not another failure!'));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    try
    {
      $autoloader->addIndexStorage($storage);
      self::fail();
    }
    catch (UnexpectedValueException $e)
    {
      // Intentionally left empty.
    }

    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));
  }

  public function testAddIndexStorage_TerminatesDueToUniquenessCheckFailed()
  {
    $storage1 = $this->getMock('autoload_IndexStorage');
    $storage1->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));
    $storage2 = $this->getMock('autoload_IndexStorage');
    $storage2->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage1, true));

    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage1), $autoloader->getIndexStorages());

    try
    {
      $autoloader->addIndexStorage($storage2, true);
      self::fail();
    }
    catch (UnexpectedValueException $e)
    {
      // Intentionally left empty.
    }
    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage1), $autoloader->getIndexStorages());
  }

  public function testAddIndexStorage_IndexOverwrittenDueToLackOfUniquenessCheck()
  {
    // 'ClassA' will be overwritten, 'Classic' will be added
    $overwrite = array('ClassA' => './somefile.php', 'Classic' => './somefile2.php');
    $storage1 = $this->getMock('autoload_IndexStorage');
    $storage1->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));
    $storage2 = $this->getMock('autoload_IndexStorage');
    $storage2->expects($this->once())->method('load')->will($this->returnValue($overwrite));

    $autoloader = new autoload_AutoLoader();
    self::assertEquals(0, count($autoloader->getIndex()));
    self::assertEquals(0, count($autoloader->getIndexStorages()));

    self::assertTrue($autoloader->addIndexStorage($storage1));

    self::assertEquals($this->indexContent, $autoloader->getIndex());
    self::assertEquals(array($storage1), $autoloader->getIndexStorages());

    self::assertTrue($autoloader->addIndexStorage($storage2));

    // existing 'ClassA' entry will be overwritten
    $content = array_merge($this->indexContent, $overwrite);

    self::assertEquals($content, $autoloader->getIndex());
    self::assertEquals(array($storage1, $storage2), $autoloader->getIndexStorages());
  }

  public function testScanAndStore_Default()
  {
    $paths   = array('/path1', '/path2');
    $scanner = $this->getMock('autoload_FileScanner');
    $scanner->expects($this->once())->method('scan')->with($paths, false)->will($this->returnValue($this->indexContent));
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('store')->with($this->indexContent);

    $autoloader = new autoload_AutoLoader();
    $autoloader->scanAndStore($paths, $scanner, $storage);
  }

  public function testScanAndStore_WithEnforcedAbsolutePaths()
  {
    $paths = '/path1' . PATH_SEPARATOR . '/path2';
    $scanner = $this->getMock('autoload_FileScanner');
    $scanner->expects($this->once())->method('scan')->with($paths, true)->will($this->returnValue($this->indexContent));
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('store')->with($this->indexContent);

    $autoloader = new autoload_AutoLoader();
    $autoloader->scanAndStore($paths, $scanner, $storage, true);
  }

  public function testClassAutoLoad_LoadedClassSuccessfully()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    $autoloader->addIndexStorage($storage);

    self::assertTrue($autoloader->classAutoLoad('ClassA'));
    self::assertTrue(class_exists('ClassA', false));

    self::assertFalse($autoloader->classAutoLoad('ClassA'));
    self::assertTrue(class_exists('ClassA', false));
  }

  public function testClassAutoLoad_LoadedInterfaceSuccessfully()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    $autoloader->addIndexStorage($storage);

    self::assertTrue($autoloader->classAutoLoad('InterfaceA'));
    self::assertTrue(interface_exists('InterfaceA', false));

    self::assertFalse($autoloader->classAutoLoad('InterfaceA'));
    self::assertTrue(interface_exists('InterfaceA', false));
  }

  public function testClassAutoLoad_SecondAutoloaderNotLoadedExistingClass()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->exactly(2))->method('load')->will($this->returnValue($this->indexContent));

    $autoloader1 = new autoload_AutoLoader();
    $autoloader1->addIndexStorage($storage);

    $autoloader2 = new autoload_AutoLoader();
    $autoloader2->addIndexStorage($storage);

    self::assertTrue($autoloader1->classAutoLoad('ClassB'));
    self::assertTrue(class_exists('ClassB', false));

    self::assertFalse($autoloader2->classAutoLoad('ClassB'));
    self::assertTrue(class_exists('ClassB', false));
  }

  public function testClassAutoLoad_SecondAutoloaderNotLoadedExistingInterface()
  {
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->exactly(2))->method('load')->will($this->returnValue($this->indexContent));

    $autoloader1 = new autoload_AutoLoader();
    $autoloader1->addIndexStorage($storage);

    $autoloader2 = new autoload_AutoLoader();
    $autoloader2->addIndexStorage($storage);

    self::assertTrue($autoloader1->classAutoLoad('InterfaceB'));
    self::assertTrue(interface_exists('InterfaceB', false));

    self::assertFalse($autoloader2->classAutoLoad('InterfaceB'));
    self::assertTrue(interface_exists('InterfaceB', false));
  }

  public function testClassAutoLoad_FailedDueToUnknownClassNameWithEmptyIndex()
  {
    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));

    $autoloader = new autoload_AutoLoader();
    self::assertFalse($autoloader->classAutoLoad('VeryNonExistingClassName'));

    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));
  }

  public function testClassAutoLoad_FailedDueToUnknownClassNameWithNonEmptyIndex()
  {
    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));

    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($this->indexContent));

    $autoloader = new autoload_AutoLoader();
    $autoloader->addIndexStorage($storage);

    self::assertFalse($autoloader->classAutoLoad('VeryNonExistingClassName'));

    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));
  }

  public function testClassAutoLoad_FailedDueToNonExistingFile()
  {
    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));
    self::assertFalse(file_exists('./very_not_existing_file.non_existing_extension'));

    $indexContent = array('VeryNonExistingClassName' => './very_not_existing_file.non_existing_extension');
    $storage = $this->getMock('autoload_IndexStorage');
    $storage->expects($this->once())->method('load')->will($this->returnValue($indexContent));

    $autoloader = new autoload_AutoLoader();
    $autoloader->addIndexStorage($storage);

    self::assertFalse($autoloader->classAutoLoad('VeryNonExistingClassName'));

    self::assertFalse(class_exists('VeryNonExistingClassName'));
    self::assertFalse(interface_exists('VeryNonExistingClassName'));
  }

  public function testClassAutoLoad_TerminatesDueToAssertion()
  {
    if (false == function_exists('assertCallback'))
    {
      function assertCallback($file, $line, $message)
      {
        throw new Exception('assert');
      }
    }
    assert_options(ASSERT_WARNING,  0);
    assert_options(ASSERT_CALLBACK, 'assertCallback');

    $autoloader = new autoload_AutoLoader();
    try
    {
      $autoloader->classAutoLoad(155);
      self::fail();
    }
    catch (Exception $e)
    {
      self::assertEquals('assert', $e->getMessage());
    }
  }
}
