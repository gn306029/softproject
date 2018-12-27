<?php  
	require_once 'mySQL.php';
    abstract class model{
        protected $conn = null;
        public function __construct(){
            $this->conn = mySQL::getConnection();
        }

        public function getNowTime(){
        	return date("Y-m-d h:i:s");
        }

        public function getNotDate(){
            return date("Y-m-d");
        }
    }

?>
