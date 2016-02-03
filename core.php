<?php

/**
* This file does all the initialisation tasks
*/

// Logs are put in the pg4wp directory
define( 'CPS4WP_LOG', CPS4WP_ROOT.'/logs/');
define( 'WP_USE_EXT_MYSQL', true);
// Check if the logs directory is needed and exists or create it if possible
if( (CPS4WP_DEBUG || CPS4WP_LOG_ERRORS) &&
	!file_exists( CPS4WP_LOG) &&
	is_writable(dirname( CPS4WP_LOG)))
	mkdir( CPS4WP_LOG);

// Load the driver defined in 'db.php'
require_once( CPS4WP_ROOT.'/driver_'.DB_DRIVER.'_'.DB_DRIVER_VERSION.'.php');

// This loads up the wpdb class applying appropriate changes to it
$replaces = array(
	'define( '	=> '// define( ',
	'class wpdb'	=> 'class wpdb2',
	'new wpdb'	=> 'new wpdb2',
	'mysql_'	=> 'wpsql_',
	'is_resource'	=> 'wp_is_resource',
);

if(file_exists(ABSPATH.'/wp-includes/wp-clusterpoint-db.php'))
	include_once(ABSPATH.'/wp-includes/wp-clusterpoint-db.php');
else{
	file_put_contents(ABSPATH.'/wp-includes/wp-clusterpoint-db.php', str_replace( array_keys($replaces), array_values($replaces), file_get_contents(ABSPATH.'/wp-includes/wp-db.php')));
	include_once(ABSPATH.'/wp-includes/wp-clusterpoint-db.php');
}
// eval( str_replace( array_keys($replaces), array_values($replaces), file_get_contents(ABSPATH.'/wp-includes/wp-db.php')));

// Create wpdb object if not already done
if (! isset($wpdb))
	$wpdb = new wpdb2( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
