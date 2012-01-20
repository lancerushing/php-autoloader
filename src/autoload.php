<?php
/**
 * Script responsible for simple loading of default autoloader.
 *
 * @author    M.Olszewski
 * @since     2010-03-26
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


require_once dirname(__FILE__) . '/common.php';
require_once dirname(__FILE__) . '/autoload/AutoLoader.php';


// get path to index storage
$path    = autoload_get_index_path();
// get storage
$storage = autoload_get_index_storage($path);

$autoLoader = new autoload_AutoLoader();
$autoLoader->addIndexStorage($storage);
$autoLoader->register();
