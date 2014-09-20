<?php
$BASE = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
define('BASE_PATH', $BASE);
define('APP_PATH', $BASE.'application'.DIRECTORY_SEPARATOR);
define('LIBRARY_PATH', $BASE.'library'.DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('PUBLIC_URL', str_replace(['//','\\\\'],'/',str_replace('\\','/',dirname($_SERVER['PHP_SELF'])).'/'));
require_once APP_PATH.'bootstrap.php';
?>
