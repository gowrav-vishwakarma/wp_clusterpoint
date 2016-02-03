<?php



class SQLInsert extends SQLAbstract{

	function __construct($sql){
		parent::__construct($sql);
	}

	function convert(){
		// $this->tableToField();
		// $this->whereOperatorCorrection();
		$cs = new PHPSQLCreator($this->parsed_sql);
		return $cs->created;
	}

	function execute($body,$endpoint=""){
		try{
			$nc=$this->convert();
		}catch(Exception $e){
			echo $this->sql;
			var_dump($this->parsed_sql);
			echo $e->getMessage();
			exit;
		}
		return parent::execute($nc,"_query");
	}
}