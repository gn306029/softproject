<?php
    
    class VisitRecord extends model{

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
        // 處理尋找拜訪紀錄的請求
        public function Search($dataArr){

            $rid = $dataArr["GET"]["data"];

            if($rid != "All"){
                return $this->Search_Once($rid);
            }else{
                return $this->Search_All();
            }
        }
        // 處理新增紀錄的需求
        public function Create($dataArr){

            $newData = $dataArr["POST"];

            $MainData = array(
                "vid"=>$newData["vid"],
                "cid"=>$newData["cid"],
                "eid"=>$newData["eid"],
                "detail"=>$newData["detail"],
                "Time"=>$this->getNowTime()
            );

            $res = $this->CreateVisitRecord($MainData);
            if($res["status_code"] == 0){
                $this->conn->commit();
            }else{
                var_dump($res["status_message"]);
                $this->conn->rollBack();
            }
        }
        // 處理更新紀錄的需求
        public function Update($dataArr){
            $putData = json_decode($dataArr["PUT"] , true);
            $MainData = array(
                "vid"=>$putData["vid"],
                "cid"=>$putData["cid"],
                "eid"=>$putData["eid"],
                "detail"=>$putData["detail"],
                "Time"=>$this->getNowTime()
            );
            $res = $this->UpdateVisitRecord($MainData);
            if($res["status_code"] == 0){
                $this->conn->commit();
            }else{
                var_dump($res["status_message"]);
                $this->conn->rollBack();
            }
        }

        // 找特定紀錄
        private function Search_Once($vid){

            $sql = "SELECT
                    v.VisitID , v.EmployeeID , v.CustomerID ,
                    EmployeeName , CustomerName , VisitDate , Detail
                    FROM visitcustomerrecord as v
                    LEFT JOIN employee ON v.EmployeeID = employee.EmployeeID
                    LEFT JOIN customer ON v.CustomerID = customer.CustomerID
                    WHERE v.VisitID = :vid";

            $parm = array(
                ":vid"=>$vid
            );

            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 找所有紀錄
        private function Search_All(){
            $sql = "SELECT
                    v.VisitID , v.EmployeeID , v.CustomerID ,
                    EmployeeName , CustomerName , VisitDate , Detail
                    FROM visitcustomerrecord as v
                    LEFT JOIN employee ON v.EmployeeID = employee.EmployeeID
                    LEFT JOIN customer ON v.CustomerID = customer.CustomerID";

            $result = $this->Allresults($sql , null);
            return $result;
        }
        // 新增記錄檔
        private function CreateVisitRecord($MainData){
            $sql = "INSERT INTO visitcustomerrecord
                    VALUES (:vid , :eid , :cid , :time , :detail)";

            $parm = array(
                ":vid"=>$MainData["vid"],
                ":eid"=>$MainData["eid"],
                ":cid"=>$MainData["cid"],
                ":time"=>$MainData["Time"],
                ":detail"=>$MainData["detail"]
            );

            $result = $this->result($sql , $parm);
            return $result;
        }
        // 更新記錄檔
        private function UpdateVisitRecord($MainData){

            $sql = "UPDATE visitcustomerrecord
                    SET CustomerID = :cid , EmployeeID = :eid , Detail = :detail , VisitDate = :time
                    WHERE VisitID = :vid";

            $parm = array(
                ":vid"=>$MainData["vid"],
                ":eid"=>$MainData["eid"],
                ":cid"=>$MainData["cid"],
                ":time"=>$MainData["Time"],
                ":detail"=>$MainData["detail"]
            );

            $result = $this->result($sql , $parm);
            return $result;
        }



    }


?>