<?php



class SQLSelect extends SQLAbstract{

	function __construct($sql){
		parent::__construct($sql);
	}

	function convert(){
		$this->tableToWhereCondition();
		$this->whereOperatorCorrection();
		$cs = new PHPSQLCreator($this->parsed_sql);
		return $cs->created;
	}

	function execute($body,$endpoint=""){
		return parent::execute($this->convert(),"_query");
	}
}