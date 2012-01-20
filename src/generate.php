<?php
/**
 * Script responsible for generating default autoloader's index storage.
 *
 * @author    M.Olszewski
 * @since     2010-03-26
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */

require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/autoload/AutoLoader.php';
require_once dirname(__FILE__) . '/autoload/CompressedFileIndexStorage.php';
require_once dirname(__FILE__) . '/autoload/TokenizerFileScanner.php';


// get path to index storage
$storagePath = autoload_get_index_path();
$storage     = autoload_get_index_storage($storagePath);
$scanPath    = dirname($storagePath);

$scanner     = new autoload_TokenizerFileScanner();
$autoLoader  = new autoload_AutoLoader();

$autoLoader->scanAndStore($scanPath, $scanner, $storage);
