<?php
/**
 * TokenizerFileScannerTest
 *
 * @package   test_autoload
 * @author    M.Olszewski
 * @since     2010-03-30
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once 'PHPUnit/Framework.php';
require_once 'src/autoload/TokenizerFileScanner.php';


/**
 * Test class for {@link autoload_TokenizerFileScanner} class.
 *
 * @author  M.Olszewski
 * @package test_autoload
 */
class test_autoload_TokenizerFileScannerTest extends PHPUnit_Framework_TestCase
{
  /**
   * @var array
   */
  private $defaultExclusions;
  /**
   * @var array
   */
  private $defaultExtensions;


  private static function dos2unix($fileName)
  {
    return preg_replace('/\\\/', '/', $fileName);
  }


  public function setUp()
  {
    parent::setUp();

    $this->defaultExclusions = array(autoload_FileScanner::DEFAULT_EXCLUSION_HIDDEN);
    $this->defaultExtensions = array(autoload_FileScanner::DEFAULT_EXTENSION_PHP,
                                     autoload_FileScanner::DEFAULT_EXTENSION_INC);
  }

  public function testConstruct_Default()
  {
    $scanner = new autoload_TokenizerFileScanner();
    self::assertSame($this->defaultExclusions, $scanner->getExclusions());
    self::assertSame($this->defaultExtensions, $scanner->getExtensions());
  }

  public function testConstruct_DoNotUseDefaults()
  {
    $scanner = new autoload_TokenizerFileScanner(false);
    self::assertSame(array(), $scanner->getExclusions());
    self::assertSame(array(), $scanner->getExtensions());
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testConstruct_TerminatesDueToInvalidTypeOfUseDefaults()
  {
    new autoload_TokenizerFileScanner('not-a-boolean');
  }

  public function testAddExtension_AddOneToDefaultOnes()
  {
    $extension = '.class';
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension($extension);

    $expected = array_merge($this->defaultExtensions, array($extension));
    self::assertSame($expected, $scanner->getExtensions());
  }

  public function testAddExtension_AddOneWithoutDefaultOnes()
  {
    $extension = '.class';
    $scanner = new autoload_TokenizerFileScanner(false);
    $scanner->addExtension($extension);

    self::assertSame(array($extension), $scanner->getExtensions());
  }

  public function testAddExtension_AddExtensionTwice()
  {
    $extension = '.class';
    $scanner = new autoload_TokenizerFileScanner(false);

    // two additions
    $scanner->addExtension($extension);
    $scanner->addExtension($extension);

    self::assertSame(array($extension), $scanner->getExtensions());
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testAddExtension_TerminatesDueToInvalidTypeOfExtension()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension(0xe5505);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testAddExtension_TerminatesDueToInvalidTypeOfOneOfExtensions()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension(array('.valid.extension', 143.935));
  }

  public function testAddExclusion_AddOneToDefaultOnes()
  {
    $exclusion = '/some.pattern/';
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExclusion($exclusion);

    $expected = array_merge($this->defaultExclusions, array($exclusion));
    self::assertSame($expected, $scanner->getExclusions());
  }

  public function testAddExclusion_AddOneWithoutDefaultOnes()
  {
    $exclusion = '/some.pattern/';
    $scanner = new autoload_TokenizerFileScanner(false);
    $scanner->addExclusion($exclusion);

    self::assertSame(array($exclusion), $scanner->getExclusions());
  }

  public function testAddExclusion_AddExclusionTwice()
  {
    $exclusion = '/some.pattern/';
    $scanner = new autoload_TokenizerFileScanner(false);

    // two additions
    $scanner->addExclusion($exclusion);
    $scanner->addExclusion($exclusion);

    self::assertSame(array($exclusion), $scanner->getExclusions());
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testAddExclusion_TerminatesDueToInvalidTypeOfExclusion()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExclusion(1004);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testAddExclusion_TerminatesDueToInvalidTypeOfOneOfExclusions()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExclusion(array('/valid.pattern/', false));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testScan_TerminatesDueToInvalidTypeOfPaths()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan(14);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testScan_TerminatesDueToInvalidTypeOfOneOfPaths()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan(array('valid_path1', 10.5305));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testScan_TerminatesDueToPathSeparatorInOneOfPaths()
  {
    $invalidPath = 'invalid_path' . PATH_SEPARATOR . '_no2';

    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan(array('valid_path1', $invalidPath));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testScan_TerminatesDueToNotExistingFile()
  {
    $fileName = './non_existing_filename5ef944bcc004532de2235541.some_extension';

    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan($fileName);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testScan_TerminatesDueToInvalidTypeOfEnforceAbsPath()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan('test/autoload/TokenizerFileScannerTest.php', 1000);
  }

  public function testScan_Case1SingleFileWithRelativePath()
  {
    $file = './test/cases/case1/File1_ClassA.php';

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($file);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertEquals($file, $class2File['ClassA']);
    self::assertNotEquals(self::dos2unix(realpath($file)), $class2File['ClassA']);
  }

  public function testScan_Case1SingleFileWithAbsolutePath()
  {
    $file = self::dos2unix(realpath('./test/cases/case1/File1_ClassA.php'));

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($file);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertEquals($file, $class2File['ClassA']);
    self::assertEquals(self::dos2unix(realpath($file)), $class2File['ClassA']);
  }

  public function testScan_Case1SingleFileWithEnforcedAbsolutePath()
  {
    $file = './test/cases/case1/File1_ClassA.php';

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($file, true);

    $realPath = self::dos2unix(realpath($file));

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertEquals($realPath, $class2File['ClassA']);
    self::assertEquals(self::dos2unix(realpath($file)), $class2File['ClassA']);
  }

  public function testScan_Case1WithRelativePath()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($path);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayNotHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayNotHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);

    self::assertEquals($path . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($path . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File5_InterfaceA.php')), $class2File['InterfaceA']);

    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($path . '/File4_ClassB_NonDefaultExtension.php.class', $class2File));
    self::assertFalse(in_array($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File));
    self::assertFalse(in_array($path . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithAbsolutePath()
  {
    $path = self::dos2unix(realpath('./test/cases/case1'));

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($path);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayNotHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayNotHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);

    self::assertEquals($path . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($path . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertEquals(self::dos2unix(realpath($path . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertEquals(self::dos2unix(realpath($path . '/File5_InterfaceA.php')), $class2File['InterfaceA']);

    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($path . '/File4_ClassB_NonDefaultExtension.php.class', $class2File));
    self::assertFalse(in_array($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File));
    self::assertFalse(in_array($path . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithEnforcedAbsolutePath()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner();
    $class2File = $scanner->scan($path, true);

    $realPath = self::dos2unix(realpath($path));

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayNotHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayNotHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);

    self::assertEquals($realPath . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($realPath . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertEquals(self::dos2unix(realpath($path . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertEquals(self::dos2unix(realpath($path . '/File5_InterfaceA.php')), $class2File['InterfaceA']);

    self::assertFalse(in_array($realPath . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($realPath . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($realPath . '/File4_ClassB_NonDefaultExtension.php.class', $class2File));
    self::assertFalse(in_array($realPath . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File));
    self::assertFalse(in_array($realPath . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithRelativePathAndCustomExtensions()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension(array('.class', '.interface'));
    $class2File = $scanner->scan($path);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);

    self::assertEquals($path . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($path . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertEquals($path . '/File4_ClassB_NonDefaultExtension.php.class', $class2File['ClassB']);
    self::assertEquals($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File['InterfaceB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File5_InterfaceA.php')), $class2File['InterfaceA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File4_ClassB_NonDefaultExtension.php.class')), $class2File['ClassB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File6_InterfaceB_NonDefaultExtension.php.interface')), $class2File['InterfaceB']);

    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($path . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithRelativePathAndCustomExtensionsOnly()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner(false);
    $scanner->addExtension(array('.class', '.interface'));
    $class2File = $scanner->scan($path);

    self::assertArrayNotHasKey('ClassA', $class2File);
    self::assertArrayHasKey('ClassB', $class2File);
    self::assertArrayNotHasKey('InterfaceA', $class2File);
    self::assertArrayHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);

    self::assertEquals($path . '/File4_ClassB_NonDefaultExtension.php.class', $class2File['ClassB']);
    self::assertEquals($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File['InterfaceB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File4_ClassB_NonDefaultExtension.php.class')), $class2File['ClassB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File6_InterfaceB_NonDefaultExtension.php.interface')), $class2File['InterfaceB']);

    self::assertFalse(in_array($path . '/File1_ClassA.php', $class2File));
    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($path . '/File5_InterfaceA.php', $class2File));
    self::assertFalse(in_array($path . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithRelativePathAndOneCustomExtensionOnly()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner(false);
    $scanner->addExtension('.class');
    $class2File = $scanner->scan($path);

    self::assertArrayNotHasKey('ClassA', $class2File);
    self::assertArrayHasKey('ClassB', $class2File);
    self::assertArrayNotHasKey('InterfaceA', $class2File);
    self::assertArrayNotHasKey('InterfaceB', $class2File);
    self::assertArrayNotHasKey('HiddenClass', $class2File);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File4_ClassB_NonDefaultExtension.php.class')), $class2File['ClassB']);

    self::assertFalse(in_array($path . '/File1_ClassA.php', $class2File));
    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
    self::assertFalse(in_array($path . '/File5_InterfaceA.php', $class2File));
    self::assertFalse(in_array($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File));
    self::assertFalse(in_array($path . '/.hidden_file', $class2File));
  }

  public function testScan_Case1WithRelativePathAndCustomExclusions()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner();
    // exclude File1_ClassA.php
    $scanner->addExclusion(array('/File1_ClassA\.php$/', '/File5_InterfaceA\.php$/'));
    $class2File = $scanner->scan($path);

    self::assertTrue(empty($class2File));
  }

  public function testScan_Case1WithRelativePathNoDefaultExtensionsAndExclusions()
  {
    $path = './test/cases/case1';

    $scanner = new autoload_TokenizerFileScanner(false);
    $class2File = $scanner->scan($path);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayHasKey('InterfaceB', $class2File);
    self::assertArrayHasKey('HiddenClass', $class2File);

    self::assertEquals($path . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($path . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertEquals($path . '/File4_ClassB_NonDefaultExtension.php.class', $class2File['ClassB']);
    self::assertEquals($path . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File['InterfaceB']);
    self::assertEquals($path . '/.hidden_file', $class2File['HiddenClass']);

    self::assertNotEquals(self::dos2unix(realpath($path . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File5_InterfaceA.php')), $class2File['InterfaceA']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File4_ClassB_NonDefaultExtension.php.class')), $class2File['ClassB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/File6_InterfaceB_NonDefaultExtension.php.interface')), $class2File['InterfaceB']);
    self::assertNotEquals(self::dos2unix(realpath($path . '/.hidden_file')), $class2File['HiddenClass']);

    self::assertFalse(in_array($path . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($path . '/File3_EmptyFile.php', $class2File));
  }

  public function testScan_Case1ScansEqualUsingFileNamesOrDirectory()
  {
    $dirPath    = './test/cases/case1';
    $filesPaths = array($dirPath . '/.hidden_file',
                        $dirPath . '/File1_ClassA.php',
                        $dirPath . '/File2_NoClass.php',
                        $dirPath . '/File3_EmptyFile.php',
                        $dirPath . '/File4_ClassB_NonDefaultExtension.php.class',
                        $dirPath . '/File5_InterfaceA.php',
                        $dirPath . '/File6_InterfaceB_NonDefaultExtension.php.interface');

    $scanner = new autoload_TokenizerFileScanner(false);
    $class2File = $scanner->scan($dirPath);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertArrayHasKey('ClassB', $class2File);
    self::assertArrayHasKey('InterfaceA', $class2File);
    self::assertArrayHasKey('InterfaceB', $class2File);
    self::assertArrayHasKey('HiddenClass', $class2File);

    self::assertEquals($dirPath . '/File1_ClassA.php', $class2File['ClassA']);
    self::assertEquals($dirPath . '/File5_InterfaceA.php', $class2File['InterfaceA']);
    self::assertEquals($dirPath . '/File4_ClassB_NonDefaultExtension.php.class', $class2File['ClassB']);
    self::assertEquals($dirPath . '/File6_InterfaceB_NonDefaultExtension.php.interface', $class2File['InterfaceB']);
    self::assertEquals($dirPath . '/.hidden_file', $class2File['HiddenClass']);

    self::assertNotEquals(self::dos2unix(realpath($dirPath . '/File1_ClassA.php')), $class2File['ClassA']);
    self::assertNotEquals(self::dos2unix(realpath($dirPath . '/File5_InterfaceA.php')), $class2File['InterfaceA']);
    self::assertNotEquals(self::dos2unix(realpath($dirPath . '/File4_ClassB_NonDefaultExtension.php.class')), $class2File['ClassB']);
    self::assertNotEquals(self::dos2unix(realpath($dirPath . '/File6_InterfaceB_NonDefaultExtension.php.interface')), $class2File['InterfaceB']);
    self::assertNotEquals(self::dos2unix(realpath($dirPath . '/.hidden_file')), $class2File['HiddenClass']);

    self::assertFalse(in_array($dirPath . '/File2_NoClass.php', $class2File));
    self::assertFalse(in_array($dirPath . '/File3_EmptyFile.php', $class2File));

    $class2File2 = $scanner->scan($filesPaths);
    self::assertEquals($class2File, $class2File2);
  }

  /**
   * @expectedException UnexpectedValueException
   */
  public function testScan_Case1TerminatesDueToDuplicatedPaths()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->scan(array('./test/cases/case1/File1_ClassA.php', './test/cases/case1/File1_ClassA.php'));
  }

  public function testScan_Case2WithRelativePath()
  {
    $path = './test/cases/case2';

    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension('.err');
    $class2File = $scanner->scan($path);

    self::assertArrayHasKey('ClassA', $class2File);
    self::assertEquals($path . '/error.err', $class2File['ClassA']);
  }

  public function testScan_Case3WithRelativePath()
  {
    $path = './test/cases/case3';

    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension('.err');
    $class2File = $scanner->scan($path);

    self::assertTrue(empty($class2File));
  }

  /**
   * @expectedException UnexpectedValueException
   */
  public function testScan_Case4TerminatesDueToDuplicatedClassesInOneFile()
  {
    $scanner = new autoload_TokenizerFileScanner();
    $scanner->addExtension('.err');
    $scanner->scan('./test/cases/case4');
  }
}
