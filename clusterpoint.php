<?php

/*
Plugin Name: Clusterpoint for WordPress (CPS4WP)
Plugin URI: http://www.xavoc.com/cps
Description: CPS4WP is a special 'plugin' enabling WordPress to use a Clusterpoint database.
Version: 0.1
Author: Gowrav Vishwakarma
Author URI: http://xavoc.com
License: AGPL or newer.
*/


add_action('admin_menu', 'test_plugin_setup_menu');
 
function test_plugin_setup_menu(){
        add_menu_page( 'Just a step away from NoSQL World', 'To Clusterpoint', 'manage_options', 'clusterpoint-plugin', 'clusterpoint_init' );
}
 
function clusterpoint_init(){
	clusterpoint_migrate();
?>
        <h1>Migrate to NoSQL!</h1>
        <h2>Provide clusterpoint credentials</h2>
        <!-- Form to handle the upload - The enctype value here is very important -->
        <form  method="post" >
        		<label> Clusterpoint Host
                <input type='text' id='clusterpoint_host' name='clusterpoint_host' value="https://api-us.clusterpoint.com/v4/"></input>
        		</label> <br/>
        		Account ID
                <input type='text' id='clusterpoint_account_id' name='clusterpoint_account_id'></input>
                <br/>
                Database Name
                <input type='text' id='clusterpoint_db' name='clusterpoint_db' ></input>
                <br/>
                Username
                <input type='text' id='clusterpoint_username' name='clusterpoint_username' ></input>
                <br/>
                Password
                <input type='password' id='clusterpoint_password' name='clusterpoint_password' ></input>
                <br/>


                <?php submit_button('Migrate') ?>
        </form>
<?php
}

function clusterpoint_migrate(){
	if(!$_POST['clusterpoint_host']) return;

	global $wpdb;

	$host = $_POST['clusterpoint_host'];
	$account_id = '100293';//$_POST['clusterpoint_account_id'];
	$username = 'gowravvishwakarma@gmail.com';//$_POST['clusterpoint_username'];
	$password = 'nedlog67';//$_POST['clusterpoint_password'];
	$db = 'wordpress';//$_POST['clusterpoint_db'];

	set_time_limit(0);

	// get current tables

	$tables = $wpdb->get_results('SHOW TABLES',ARRAY_A);
	
	require_once (ABSPATH.'/wp-content/plugins/clusterpoint/httpful.phar');

	foreach ($tables as $table) {
		
		$table_data = $wpdb->get_results("SELECT * FROM ". $table[array_shift(array_keys($table))],ARRAY_A);
		$data = [];
		foreach ($table_data as $row) {
			$data_row=[];
			foreach ($row as $column => $value) {
				$data_row[$column] = $value;
			}

			$data_row['__table'] = $table[array_shift(array_keys($table))];
			
			$data[] = $data_row;
			
			// var_dump($data);
			
		}
		$response = \Httpful\Request::post($host.$account_id.'/'.$db)
			    ->authenticateWith($username, $password)  // authenticate with basic auth...
			    ->body(json_encode($data))             // attach a body/payload...
			    ->send(); 

		var_dump($response);
		
		// if($response->body->error)
			// print_r($response->body);
		// else
		// 	echo array_shift(array_keys($table)). " Uploaded<br/>";

	}




}
