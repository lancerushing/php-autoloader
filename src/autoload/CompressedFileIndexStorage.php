<?php
/**
 * CompressedFileIndexStorage
 *
 * @package   autoload
 * @author    M.Olszewski
 * @since     2010-03-26
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once dirname(__FILE__) . '/FileIndexStorage.php';


/**
 * Extension for {@link autoload_FileIndexStorage} which adds compression and decompression of the index content
 * on store and load.
 *
 * @author  M.Olszewski
 * @package autoload
 */
class autoload_CompressedFileIndexStorage extends autoload_FileIndexStorage
{
  /**
   * Minimum supported compression level (no compression).
   *
   * @var int
   */
  const MIN_COMPRESSION = 0;
  /**
   * Default compression level.
   *
   * @var int
   */
  const DEFAULT_COMPRESSION = 6;
  /**
   * Maximum supported compression level.
   *
   * @var int
   */
  const MAX_COMPRESSION = 9;

  /**
   * @var int
   */
  private $compression;

  /**
   * Constructs instance of {@link autoload_CompressedFileIndexStorage} that will load or store index content
   * from/in given filename with specified compression.
   *
   * @param SplFileInfo $fileName Name of the file where index content is stored.
   * @param int $compression Compression level (0..9). If value is greater or lesser then supported compression levels
   * then it will be aligned to 0..9 range (n < 0 => 0, n > 9 => 9).
   */
  public function __construct(SplFileInfo $fileName, $compression = self::DEFAULT_COMPRESSION)
  {
    parent::__construct($fileName);

    if (false == is_int($compression))
    {
      throw new InvalidArgumentException(__METHOD__ . '(): $compression is not an integer! $compression=' . $compression);
    }
    if ($compression < self::MIN_COMPRESSION)
    {
      $compression = self::MIN_COMPRESSION;
    }
    if ($compression > self::MAX_COMPRESSION)
    {
      $compression = self::MAX_COMPRESSION;
    }

    $this->compression = $compression;
  }

  /**
   * Compresses given index content after it is serialized.
   *
   * @see autoload_FileIndexStorage::beforeStore()
   */
  protected function beforeStore(array $content)
  {
    $serialized = parent::beforeStore($content);
    $compressed = gzcompress($serialized, $this->compression);

    if (false === $compressed)
    {
      $error = error_get_last();
      throw new RuntimeException(__METHOD__ . '(): cannot compress serialized content! Error message: '.
                                 $error['message']);
    }

    return $compressed;
  }

  /**
   * Decompresses given index content before it is unserialized.
   *
   * @see autoload_FileIndexStorage::beforeStore()
   */
  protected function afterLoad($content)
  {
    $uncompressed = $content;

    if (strlen($content) > 0)
    {
      $uncompressed = gzuncompress($content);
      if (false === $uncompressed)
      {
        $error = error_get_last();
        throw new RuntimeException(__METHOD__ . '(): cannot uncompress read content! Error message: '.
                                   $error['message']);
      }
    }

    $unserialized = parent::afterLoad($uncompressed);

    return $unserialized;
  }

  /**
   * Gets compression level set for this index storage.
   *
   * @return int Returns compression level set for this index storage.
   */
  public function getCompression()
  {
    return $this->compression;
  }
}
