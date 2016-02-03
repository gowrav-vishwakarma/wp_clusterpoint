<?php

if( !defined('CPS4WP_ROOT'))
{
// You can choose the driver to load here
define('DB_DRIVER', 'clusterpoint'); // 'clutserpoint4' or 'mysql' are supported for now
define('DB_DRIVER_VERSION', '4.0'); // '4.0' or '4.1' coming soon when released ;)

// Set this to 'true' and check that `cps4wp` is writable if you want debug logs to be written
define( 'CPS4WP_DEBUG', false);
// If you just want to log queries that generate errors, leave CPS4WP_DEBUG to "false"
// and set this to true
define( 'CPS4WP_LOG_ERRORS', false);

// If you want to allow insecure configuration (from the author point of view) to work with CPS4WP,
// change this to true
define( 'CPS4WP_INSECURE', false);

// This defines the directory where PG4WP files are loaded from
//   2 places checked : wp-content and wp-content/plugins
if( file_exists( ABSPATH.'/wp-content/clusterpoint'))
	define( 'CPS4WP_ROOT', ABSPATH.'/wp-content/clusterpoint');
else
	define( 'CPS4WP_ROOT', ABSPATH.'/wp-content/plugins/clusterpoint');

// Here happens all the magic
require_once (CPS4WP_ROOT.'/httpful.phar');
require_once( CPS4WP_ROOT.'/core.php');
} // Protection against multiple loading
