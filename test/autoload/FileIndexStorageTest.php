<?php
/**
 * FileIndexStorageTest
 *
 * @package   test_autoload
 * @author    M.Olszewski
 * @since     2010-04-09
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once 'PHPUnit/Framework.php';
require_once 'src/autoload/FileIndexStorage.php';


/**
 * Test class for {@link autoload_FileIndexStorageTest} class.
 *
 * @author  M.Olszewski
 * @package test_autoload
 */
class test_autoload_FileIndexStorageTest extends PHPUnit_Framework_TestCase
{
  /**
   * Path to index storage file.
   *
   * @var string
   */
  const INDEX_STORAGE_PATH = './index_storage.idx';

  private $indexContent;


  public function setUp()
  {
    $this->indexContent = array('class1' => 'file1', 'class2' => 'file2');

    $this->removeIndexFile();
  }

  public function tearDown()
  {
    $this->removeIndexFile();
  }

  private function removeIndexFile()
  {
   if (file_exists(self::INDEX_STORAGE_PATH))
    {
      chmod(self::INDEX_STORAGE_PATH, 0777);
      unlink(self::INDEX_STORAGE_PATH);
    }
  }

  public function testConstruct_Default()
  {
    new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));
  }

  public function testStore_EmptyContent()
  {
    $content  = array();
    $expected = serialize($content);

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));

    $storage = new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));
    $storage->store($content);

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
    self::assertEquals($expected, file_get_contents(self::INDEX_STORAGE_PATH));
  }

  public function testStore_NonEmptyContent()
  {
    $expected = serialize($this->indexContent);

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));

    $storage = new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));
    $storage->store($this->indexContent);

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
    self::assertEquals($expected, file_get_contents(self::INDEX_STORAGE_PATH));
  }

  public function testStore_TerminatesDueToFileNameReferencingDirectory()
  {
    $fileInfo = $this->getMock('SplFileInfo', array(), array(self::INDEX_STORAGE_PATH));
    $fileInfo->expects($this->once())->method('isDir')->will($this->returnValue(true));
    $fileInfo->expects($this->never())->method('isFile');
    $fileInfo->expects($this->never())->method('isWritable');
    $fileInfo->expects($this->never())->method('openFile');

    $storage = new autoload_FileIndexStorage($fileInfo);
    try
    {
      $storage->store(array());
      self::fail();
    }
    catch (RuntimeException $e)
    {
      // Intentionally left empty.
    }

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));
  }

  public function testStore_TerminatesDueToFileNameReferencingNonWritableFile()
  {
    $fileInfo = $this->getMock('SplFileInfo', array(), array(self::INDEX_STORAGE_PATH));
    $fileInfo->expects($this->once())->method('isDir')->will($this->returnValue(false));
    $fileInfo->expects($this->once())->method('isFile')->will($this->returnValue(true));
    $fileInfo->expects($this->once())->method('isWritable')->will($this->returnValue(false));
    $fileInfo->expects($this->never())->method('openFile');

    $storage = new autoload_FileIndexStorage($fileInfo);
    try
    {
      $storage->store(array());
      self::fail();
    }
    catch (RuntimeException $e)
    {
      // Intentionally left empty.
    }

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));
  }

  public function testStore_TerminatesDueToInvalidTypeReturnedByBeforeStore()
  {
    $file = new SplFileInfo(self::INDEX_STORAGE_PATH);
    $storage = $this->getMock('autoload_FileIndexStorage', array('beforeStore'), array($file));
    $storage->expects($this->once())->method('beforeStore')->will($this->returnValue(1500));

    try
    {
      $storage->store(array());
      self::fail();
    }
    catch (UnexpectedValueException $e)
    {
      // Intentionally left empty.
    }

    self::assertFalse(is_file(self::INDEX_STORAGE_PATH));
  }

  public function testStore_TerminatesDueToInvalidNumberOfWrittenBytes()
  {
    $expected = serialize($this->indexContent);
    // make it half, so it will be considered incorrect
    $incorrectLength = strlen($expected) / 2;

    $fileObject = $this->getMock('SPLFileObject', array('fwrite'), array(self::INDEX_STORAGE_PATH, 'w+'));
    $fileObject->expects($this->once())->method('fwrite')->will($this->returnValue($incorrectLength));

    $fileInfo = $this->getMock('SplFileInfo', array(), array(self::INDEX_STORAGE_PATH));
    $fileInfo->expects($this->once())->method('isDir')->will($this->returnValue(false));
    $fileInfo->expects($this->once())->method('isFile')->will($this->returnValue(true));
    $fileInfo->expects($this->once())->method('isWritable')->will($this->returnValue(true));
    $fileInfo->expects($this->once())->method('openFile')->will($this->returnValue($fileObject));

    $storage = new autoload_FileIndexStorage($fileInfo);

    try
    {
      $storage->store($this->indexContent);
      self::fail();
    }
    catch (RuntimeException $e)
    {
      // Intentionally left empty.
    }

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
  }

  public function testLoad_EmptyFile()
  {
    // Create empty file
    file_put_contents(self::INDEX_STORAGE_PATH, '');
    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));

    $storage = new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));
    $loaded = $storage->load();

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
    self::assertEquals(array(), $loaded);
  }

  public function testLoad_FileWithEmptyArray()
  {
    $expected = array();
    file_put_contents(self::INDEX_STORAGE_PATH, serialize($expected));
    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));

    $storage = new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));
    $loaded = $storage->load();

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
    self::assertEquals($expected, $loaded);
  }

  public function testLoad_FileWithNonEmptyArray()
  {
    file_put_contents(self::INDEX_STORAGE_PATH, serialize($this->indexContent));
    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));

    $storage = new autoload_FileIndexStorage(new SplFileInfo(self::INDEX_STORAGE_PATH));
    $loaded = $storage->load();

    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));
    self::assertEquals($this->indexContent, $loaded);
  }

  public function testLoad_TerminatesDueToFileNameReferencingDirectory()
  {
    $fileInfo = $this->getMock('SplFileInfo', array(), array(self::INDEX_STORAGE_PATH));
    $fileInfo->expects($this->once())->method('isDir')->will($this->returnValue(true));
    $fileInfo->expects($this->never())->method('isFile');
    $fileInfo->expects($this->never())->method('isWritable');
    $fileInfo->expects($this->never())->method('openFile');

    $storage = new autoload_FileIndexStorage($fileInfo);
    try
    {
      $storage->load();
      self::fail();
    }
    catch (RuntimeException $e)
    {
      // Intentionally left empty.
    }
  }

  public function testLoad_TerminatesDueToFileNameReferencingNonReadableFile()
  {
    $fileInfo = $this->getMock('SplFileInfo', array(), array(self::INDEX_STORAGE_PATH));
    $fileInfo->expects($this->once())->method('isDir')->will($this->returnValue(false));
    $fileInfo->expects($this->once())->method('isFile')->will($this->returnValue(true));
    $fileInfo->expects($this->once())->method('isReadable')->will($this->returnValue(false));
    $fileInfo->expects($this->never())->method('openFile');

    $storage = new autoload_FileIndexStorage($fileInfo);
    try
    {
      $storage->load();
      self::fail();
    }
    catch (RuntimeException $e)
    {
      // Intentionally left empty.
    }
  }

  public function testStore_TerminatesDueToInvalidTypeReturnedByAfterLoadStore()
  {
    file_put_contents(self::INDEX_STORAGE_PATH, serialize($this->indexContent));
    self::assertTrue(is_file(self::INDEX_STORAGE_PATH));

    $file = new SplFileInfo(self::INDEX_STORAGE_PATH);
    $storage = $this->getMock('autoload_FileIndexStorage', array('afterLoad'), array($file));
    $storage->expects($this->once())->method('afterLoad')->will($this->returnValue(1500));

    try
    {
      $storage->load();
      self::fail();
    }
    catch (UnexpectedValueException $e)
    {
      // Intentionally left empty.
    }
  }
}
