<?php 
require_once("SimpleRest.php");
require_once "../include/php/model.php";
require_once "../include/php/mySQL.php";

class SiteRestHandler extends SimpleRest {

	private $MainObject;
	private $result;

	function __construct($className){
		mySQL::$db_host="localhost";
		mySQL::$db_name="softproject";
		mySQL::$db_user="root";
		mySQL::$db_password="";

		require_once "../modules/".$className."/".$className."_model.php";
		$this->MainObject = new $className();
	}
	
	public function encodeJson($responseData) {
		$jsonResponse = json_encode($responseData);
		return $jsonResponse;		
	}

	public function run($do , $dataArr){

		$this->result = $this->MainObject->$do($dataArr);
		return $this->encodeJson($this->result);

	}
	
	public function echo(){
		echo $this->encodeJson($this->result);
	}

}
?>