<?php
    
    class Employee extends model{

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

            $eid = $dataArr["GET"]["data"];

            if($eid != "All"){
                return $this->Search_Once($eid);
            }else{
                return $this->Search_All();
            }
        }

        public function Search_Once($eid){

            $sql = "SELECT 
                    employee.EmployeeID , employee.DepartmentID , employee.JobID , 
                    EmployeeName , JobName , RegionName
                    FROM employee
                    LEFT JOIN job ON employee.JobID = job.JobID
                    LEFT JOIN department ON employee.DepartmentID = department.DepartmentID
                    LEFT JOIN region ON department.RegionID = region.RegionID
                    WHERE EmployeeID = :eid";

            $parm = array(
                ":eid"=>$eid
            );

            $result = $this->result($sql , $parm);
            return $result;
        }

        public function Search_All(){

            $sql = "SELECT 
                    employee.EmployeeID , employee.DepartmentID , employee.JobID , 
                    EmployeeName , JobName , RegionName
                    FROM employee
                    LEFT JOIN job ON employee.JobID = job.JobID
                    LEFT JOIN department ON employee.DepartmentID = department.DepartmentID
                    LEFT JOIN region ON department.RegionID = region.RegionID";

            $result = $this->result($sql , null);
            return $result;
        }

    }


?>