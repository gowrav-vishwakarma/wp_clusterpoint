<?php

/**
* Provides a driver for Clutserpoint V 4.0
*
* This file maps original mysql_* functions with clusterpoint equivalents
*
*/
	
	require_once(CPS4WP_ROOT."/sql_parser/PHPSQLParser.php");
	require_once(CPS4WP_ROOT."/sql_parser/PHPSQLCreator.php");
	require_once(CPS4WP_ROOT."/SQLAbstract.php");
	require_once(CPS4WP_ROOT."/SQLSelect.php");
	require_once(CPS4WP_ROOT."/SQLInsert.php");
	require_once(CPS4WP_ROOT."/SQLUpdate.php");
	require_once(CPS4WP_ROOT."/SQLDelete.php");

	// Initializing some variables
	$GLOBALS['cps4wp_version'] = '0.1';
	$GLOBALS['cps4wp_result'] = 0;
	$GLOBALS['cps4wp_numrows_query'] = '';
	$GLOBALS['cps4wp_ins_table'] = '';
	$GLOBALS['cps4wp_ins_field'] = '';
	$GLOBALS['cps4wp_last_insert'] = '';
	$GLOBALS['cps4wp_connstr'] = '';
	$GLOBALS['cps4wp_conn'] = false;

	// CPS Specific implementations
	function cps_escape_string($s){
		return $s;
	}

	// wps functions 

	function wp_is_resource($result){
		return true;
	}
	
	function wpsql_num_rows($result)
		{ return count($result); }
	function wpsql_numrows($result)
		{ return count($result); }
	function wpsql_num_fields($result)
		{ return pg_num_fields($result); }
	function wpsql_fetch_field($result)
		{ return 'tablename'; }
	function wpsql_fetch_object($result)
		{
			static $counter=false;
			if($counter==false) $counter = 0;

			if($counter < $result->body->found){
				$counter++;
				return $result->body->results[$counter-1];
			} 
			$counter = false;
			return false;
			
		}
	function wpsql_free_result($result)
		{ return true;//pg_free_result($result); 
		}
	function wpsql_affected_rows()
	{
		if( $GLOBALS['cps4wp_result'] === false)
			return 0;
		else
			return $GLOBALS['cps4wp_result']->body->found;
	}
	function wpsql_fetch_row($result)
		{ return pg_fetch_row($result); }
	function wpsql_data_seek($result, $offset)
		{ return pg_result_seek ( $result, $offset ); }
	function wpsql_error()
		{ return ''; if( $GLOBALS['cps4wp_conn']) return pg_last_error(); else return ''; }
	function wpsql_fetch_assoc($result) { return pg_fetch_assoc($result); }
	function wpsql_escape_string($s) { return pg_escape_string($s); }
	function wpsql_real_escape_string($s,$c=NULL) { return cps_escape_string($s); }
	function wpsql_get_server_info() { return '5.0.30'; } // Just want to fool wordpress ...
	
/**** Modified version of wpsql_result() is at the bottom of this file
	function wpsql_result($result, $i, $fieldname)
		{ return pg_fetch_result($result, $i, $fieldname); }
****/

	// This is a fake connection except during installation
	function wpsql_connect($dbserver, $dbuser, $dbpass)
	{
		
		$GLOBALS['cps4wp_connstr'] = '';
		$hostport = explode(':', $dbserver);
		if( !empty( $hostport[0]))
			$GLOBALS['cps4wp_connstr'] .= ' host='.$hostport[0];
		if( !empty( $hostport[1]))
			$GLOBALS['cps4wp_connstr'] .= ' port='.$hostport[1];
		if( !empty( $dbuser))
			$GLOBALS['cps4wp_connstr'] .= ' user='.$dbuser;
		if( !empty( $dbpass))
			$GLOBALS['cps4wp_connstr'] .= ' password='.$dbpass;
		elseif( !CPS4WP_INSECURE)
			wp_die( 'Connecting to your Clutserpoint database without a password is not permitted.
					<br />If you want to do it anyway, please set "CPS4WP_INSECURE" to true in your "db.php" file.' );
		
		// // While installing, we test the connection to 'template1' (as we don't know the effective dbname yet)
		if( defined('WP_INSTALLING') && WP_INSTALLING)
			return wpsql_select_db( 'template1');
		
		return 1;
	}
	
	// The effective connection happens here
	function wpsql_select_db($dbname, $connection_id = 0)
	{

		// $pg_connstr = $GLOBALS['cps4wp_connstr'].' dbname='.$dbname;

		// $GLOBALS['cps4wp_conn'] = pg_connect($pg_connstr);
		
		// if( $GLOBALS['cps4wp_conn'])
		// {
		// 	$ver = pg_version($GLOBALS['cps4wp_conn']);
		// 	if( isset($ver['server']))
		// 		$GLOBALS['cps4wp_version'] = $ver['server'];
		// }
		
		// // Now we should be connected, we "forget" about the connection parameters (if this is not a "test" connection)
		// if( !defined('WP_INSTALLING') || !WP_INSTALLING)
		// 	$GLOBALS['cps4wp_connstr'] = '';
		
		// // Execute early transmitted commands if needed
		// if( isset($GLOBALS['cps4wp_pre_sql']) && !empty($GLOBALS['cps4wp_pre_sql']))
		// 	foreach( $GLOBALS['cps4wp_pre_sql'] as $sql2run)
		// 		wpsql_query( $sql2run);
		
		// return $GLOBALS['cps4wp_conn'];
		$GLOBALS['cps4wp_conn'] = $dbname;
		return DB_NAME;
	}

	function wpsql_fetch_array($result)
	{
		$res = pg_fetch_array($result);
		
		if( is_array($res) )
		foreach($res as $v => $k )
			$res[$v] = trim($k);
		return $res;
	}
	
	function wpsql_query($sql, $dbh)
	{
		
		if( !$GLOBALS['cps4wp_conn'])
		{
			// Catch SQL to be executed as soon as connected
			$GLOBALS['cps4wp_pre_sql'][] = $sql;
			return true;
		}
		$initial = $sql;
		if(!$sql) return;
		return cps4wp_query( $sql);
	}
	
	function wpsql_insert_id($lnk = NULL)
	{
		global $wpdb;
		$ins_field = $GLOBALS['cps4wp_ins_field'];
		$table = $GLOBALS['cps4wp_ins_table'];
		$lastq = $GLOBALS['cps4wp_last_insert'];
		
		$seq = $table . '_seq';
		
		// Table 'term_relationships' doesn't have a sequence
		if( $table == $wpdb->term_relationships)
		{
			$sql = 'NO QUERY';
			$data = 0;
		}
		// When using WP_Import plugin, ID is defined in the query
		elseif('post_author' == $ins_field && false !== strpos($lastq,'ID'))
		{
			$sql = 'ID was in query ';
			$pattern = '/.+\'(\d+).+$/';
			preg_match($pattern, $lastq, $matches);
			$data = $matches[1];
			// We should update the sequence on the next non-INSERT query
			$GLOBALS['cps4wp_queued_query'] = "SELECT SETVAL('$seq',(SELECT MAX(\"ID\") FROM $table)+1);";
		}
		else
		{
			return $GLOBALS['cps4wp_result']->body->
			$sql = "SELECT CURRVAL('$seq')";
			
			$res = pg_query($sql);
			if( false !== $res)
				$data = pg_fetch_result($res, 0, 0);
			elseif( CPS4WP_DEBUG || CPS4WP_ERROR_LOG)
			{
				$log = '['.microtime(true)."] wpsql_insert_id() was called with '$table' and '$ins_field'".
						" and generated an error. The latest INSERT query was :\n'$lastq'\n";
				error_log( $log, 3, CPS4WP_LOG.'cps4wp_errors.log');
			}
		}
		if( CPS4WP_DEBUG && $sql)
			error_log( '['.microtime(true)."] Getting inserted ID for '$table' ('$ins_field') : $sql => $data\n", 3, CPS4WP_LOG.'cps4wp_insertid.log');
			
		return $data;
	}
	
	function cps4wp_query( $sql)
	{
		global $wpdb;

		// echo $sql;
		
		$logto = 'queries';
		// The end of the query may be protected against changes
		$end = '';
		
		// Remove unusefull spaces
		$initial = $sql = trim($sql);
		
		if( 0 === strpos($sql, 'SELECT'))
		{
			// clutserpoint doesnot support @ in queries
			if(false !== strpos($sql, "@")) return false;

			$logto = 'SELECT';
			$s=new SQLSelect($sql);
			return $GLOBALS['cps4wp_result'] = $s->toCps();			
		} // SELECT
		elseif( 0 === strpos($sql, 'UPDATE'))
		{
			$logto = 'UPDATE';
			$s=new SQLUpdate($sql);
			return $GLOBALS['cps4wp_result'] = $s->toCps();	

		} // UPDATE
		elseif( 0 === strpos($sql, 'INSERT'))
		{
			$logto = 'INSERT';
			$s=new SQLInsert($sql);
			return $GLOBALS['cps4wp_result'] = $s->toCps();	
		} // INSERT
		elseif( 0 === strpos( $sql, 'DELETE' ))
		{
			$logto = 'DELETE';
			$s=new SQLDelete($sql);
			return $GLOBALS['cps4wp_result'] = $s->toCps();	
		}
		// Fix tables listing
		elseif( 0 === strpos($sql, 'SHOW TABLES'))
		{
			$logto = 'SHOWTABLES';
			$o = new SQLAbstract();
			return $o->execute('SELECT DISTINCT __table FROM '.DB_NAME);
		}
		// Rewriting optimize table
		elseif( 0 === strpos($sql, 'OPTIMIZE TABLE'))
		{
			$logto = 'OPTIMIZE';
			$sql = str_replace( 'OPTIMIZE TABLE', 'VACUUM', $sql);
		}
		// Handle 'SET NAMES ... COLLATE ...'
		elseif( 0 === strpos($sql, 'SET NAMES') && false !== strpos($sql, 'COLLATE'))
		{
			$logto = 'SETNAMES';
			$sql = "SET NAMES 'utf8'";
			$sql= false; //cps don't need this now
		}
		// Load up upgrade and install functions as required
		$begin = substr( $sql, 0, 3);
		$search = array( 'SHO', 'ALT', 'DES', 'CRE', 'DRO');
		if( in_array($begin, $search))
		{
			require_once( CPS4WP_ROOT.'/driver_pgsql_install.php');
			$sql = cps4wp_installing( $sql, $logto);
		}
		
		// WP 2.9.1 uses a comparison where text data is not quoted
		$pattern = '/AND meta_value = (-?\d+)/';
		$sql = preg_replace( $pattern, 'AND meta_value = \'$1\'', $sql);
		
		// Generic "INTERVAL xx YEAR|MONTH|DAY|HOUR|MINUTE|SECOND" handler
		$pattern = '/INTERVAL[ ]+(\d+)[ ]+(YEAR|MONTH|DAY|HOUR|MINUTE|SECOND)/';
		$sql = preg_replace( $pattern, "'\$1 \$2'::interval", $sql);
		$pattern = '/DATE_SUB[ ]*\(([^,]+),([^\)]+)\)/';
		$sql = preg_replace( $pattern, '($1::timestamp - $2)', $sql);
		
		// Remove illegal characters
		$sql = str_replace('`', '', $sql);
		
		// Field names with CAPITALS need special handling
		if( false !== strpos($sql, 'ID'))
		{
			$pattern = '/ID([^ ])/';
				$sql = preg_replace($pattern, 'ID $1', $sql);
			$pattern = '/ID$/';
				$sql = preg_replace($pattern, 'ID ', $sql);
			$pattern = '/\(ID/';
				$sql = preg_replace($pattern, '( ID', $sql);
			$pattern = '/,ID/';
				$sql = preg_replace($pattern, ', ID', $sql);
			$pattern = '/[0-9a-zA-Z_]+ID/';
				$sql = preg_replace($pattern, '"$0"', $sql);
			$pattern = '/\.ID/';
				$sql = preg_replace($pattern, '."ID"', $sql);
			$pattern = '/[\s]ID /';
				$sql = preg_replace($pattern, ' "ID" ', $sql);
			$pattern = '/"ID "/';
				$sql = preg_replace($pattern, ' "ID" ', $sql);
		} // CAPITALS
		
		// Empty "IN" statements are erroneous
		$sql = str_replace( 'IN (\'\')', 'IN (NULL)', $sql);
		$sql = str_replace( 'IN ( \'\' )', 'IN (NULL)', $sql);
		$sql = str_replace( 'IN ()', 'IN (NULL)', $sql);
		
		// Put back the end of the query if it was separated
		$sql .= $end;
		
		// For insert ID catching
		if( $logto == 'INSERT')
		{
			$pattern = '/INSERT INTO (\w+)\s+\([ a-zA-Z_"]+/';
			preg_match($pattern, $sql, $matches);
			$GLOBALS['cps4wp_ins_table'] = $matches[1];
			$match_list = split(' ', $matches[0]);
			if( $GLOBALS['cps4wp_ins_table'])
			{
				$GLOBALS['cps4wp_ins_field'] = trim($match_list[3],' ()	');
				if(! $GLOBALS['cps4wp_ins_field'])
					$GLOBALS['cps4wp_ins_field'] = trim($match_list[4],' ()	');
			}
			$GLOBALS['cps4wp_last_insert'] = $sql;
		}
		elseif( isset($GLOBALS['cps4wp_queued_query']))
		{
			pg_query($GLOBALS['cps4wp_queued_query']);
			unset($GLOBALS['cps4wp_queued_query']);
		}
		
		// Correct quoting for PostgreSQL 9.1+ compatibility
		$sql = str_replace( "\\'", "''", $sql);
		$sql = str_replace( '\"', '"', $sql);
		
		if( CPS4WP_DEBUG)
		{
			if( $initial != $sql)
				error_log( '['.microtime(true)."] Converting :\n$initial\n---- to ----\n$sql\n---------------------\n", 3, CPS4WP_LOG.'cps4wp_'.$logto.'.log');
			else
				error_log( '['.microtime(true)."] $sql\n---------------------\n", 3, CPS4WP_LOG.'cps4wp_unmodified.log');
		}
		return $sql;
	}

/*
	Quick fix for wpsql_result() error and missing wpsql_errno() function
	Source : http://vitoriodelage.wordpress.com/2014/06/06/add-missing-wpsql_errno-in-cps4wp-plugin/
*/
	function wpsql_result($result, $i, $fieldname = null) {
		if (is_resource($result)) {
			if ($fieldname) {
				return pg_fetch_result($result, $i, $fieldname);
			} else {
				return pg_fetch_result($result, $i);
			}
		}
	}
	
	function wpsql_errno( $connection) {
//		throw new Exception("Error Processing Request", 1);
		if($connection == 1) return false;
		
		$result = pg_get_result($connection);
		$result_status = pg_result_status($result);
		return pg_result_error_field($result_status, PGSQL_DIAG_SQLSTATE);
	}
