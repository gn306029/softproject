<?php
    
    class Customer extends model{

        public function __construct() {
            parent::__construct();
            date_default_timezone_set("Etc/GMT-8");
        }
        // 取得單一筆結果
        private function result($sql,$array){
            if(!$this->conn->inTransaction()){
                $this->conn->beginTransaction();
            }
            $stmt = $this->conn->prepare($sql);
            if(empty($array)){
                $result = $stmt->execute();
            }else{
                $result = $stmt->execute($array);
            }
            if($result){
                $ds = $stmt->fetch(PDO::FETCH_ASSOC);
                $return['status_code'] = 0;
                $return['status_message'] = 'Execute Success';
                $return['table'] = $ds;
                $return['rowCount'] = $stmt->rowCount();
            }else{
                $return['status_code'] = -1;
                $return['status_message'] = $stmt->errorInfo()[2];
                $return['sql'] = $sql;
            }
            return $return;
        }
        // 取得多筆結果
        private function Allresults($sql,$array){
            if(!$this->conn->inTransaction()){
                $this->conn->beginTransaction();
            }
            $stmt = $this->conn->prepare($sql);
            if(empty($array)){
                $result = $stmt->execute();
            }else{
                $result = $stmt->execute($array);
            }
            if($result){
                $ds = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $return['status_code'] = 0;
                $return['status_message'] = 'Execute Success';
                $return['table'] = $ds;
            }
            else{
                $return['status_code'] = -1;
                $return['status_message'] = $stmt->errorInfo()[2];
                $return['sql'] = $sql;
            }
            return $return;
        }
        // 處理尋找客戶的請求
        public function Search($dataArr){

            if(substr($dataArr["GET"]["data"], -1) == "/"){
                $cid = substr($dataArr["GET"]["data"], 0 , -1);
            }else{
                $cid = $dataArr["GET"]["data"];
            }

            if($cid != "All"){
                return $this->Search_Once($cid);
            }else{
                return $this->Search_All();
            }
        }

        public function Search_Once($cid){

            $sql = "SELECT * FROM customer
                    WHERE CustomerID = :cid";

            $parm = array(
                ":cid"=>$cid
            );

            $result = $this->result($sql , $parm);
            return $result;
        }

        public function Search_All(){

            $sql = "SELECT * FROM customer";

            $result = $this->Allresults($sql , null);
            return $result;
        }

    }


?>