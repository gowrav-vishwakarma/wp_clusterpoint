<?php


class SQLAbstract {
	public $sql=null;
	public $parsed_sql=null;
	public $cps_sql="";

	function __construct($sql){
		$this->sql = $sql;
		$p = new PHPSQLParser($this->sql);
		$this->parsed_sql = $p->parsed;
	}

	function tableToWhereCondition(){
		$table = $this->parsed_sql['FROM'][0]['table']	;
		$this->parsed_sql['FROM'][0]['table'] = DB_NAME;
		if(!isset($this->parsed_sql['WHERE'])) $this->parsed_sql['WHERE']=[];
		$this->parsed_sql['WHERE'][] = Array
				        (
				            'expr_type' => 'operator',
				            'base_expr' => 'and',
				            'sub_tree' => false
				        );
		$this->parsed_sql['WHERE'][] = Array
				        (
				            'expr_type' => 'colref',
				            'base_expr' => '__table',
				            'no_quotes' => 'col2',
				            'sub_tree' => false
				        );
		$this->parsed_sql['WHERE'][]= Array
				        (
				            'expr_type' => 'operator',
				            'base_expr' => '=',
				            'sub_tree' => false,
				        );
				        
		$this->parsed_sql['WHERE'][]= Array
				        (
				            'expr_type' => 'const',
				            'base_expr' => "'$table'",
				            'sub_tree'=> false
				        );
	}

	function tableToField(){

	}

	function whereOperatorCorrection(){
		if(!isset($this->parsed_sql['WHERE'])) return;

		foreach ($this->parsed_sql['WHERE'] as &$where) {
			if($where['expr_type'] !='operator') continue;
			switch ($where['base_expr']) {
				case 'and':
					$where['base_expr'] = '&&';
					break;

				case '=':
					$where['base_expr'] = '==';
					break;
				
				default:
					# code...
					break;
			}
		}
	}

	function execute($body,$endpoint=""){
		if($endpoint) $endpoint="/$endpoint";

		$response = \Httpful\Request::post(DB_HOST.'/'.DB_NAME.$endpoint)                  // Build a PUT request...
				    ->authenticateWith(DB_USER, DB_PASSWORD)  // authenticate with basic auth...
				    ->body($body)             // attach a body/payload...
				    ->expectsJson()
				    ->send();
		if($response->body->error)
			wp_die($response->body->error[0]->message);

		return $response;
	}


	function toCps(){
		return $this->execute();
	}
}