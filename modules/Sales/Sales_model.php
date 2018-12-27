<?php
    
    class Sales extends model{

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
        // 處理查詢請求
        public function Search($dataArr){

            $data = $dataArr["GET"]["data"];
            $condition = array(
                "eid"=>$dataArr["GET"]["eid"],
                "eName"=>$dataArr["GET"]["eName"],
                "eStart"=>$dataArr["GET"]["eStart"],
                "eEnd"=>$dataArr["GET"]["eEnd"]
            );
            $access = $dataArr["access"];
            $key = $dataArr["Key"];
            if($access == 0){
                if($data == "employee"){
                    //return $this->Search_Once($key_eid);
                }else if($data == "customerA"){
                    $key_eid = $this->GetEid($key);
                    return $this->Search_All_Cus($key_eid);
                }
            }else{
                if($data == "employee"){
                    return $this->Search_E_Condition($condition);
                }else if($data == "customerA"){
                    return $this->Search_All_Cus(null);
                }
            }
        }
        // 根據條件查詢業務
        public function Search_E_Condition($condition){

            $parm = array();

            $sql = "SELECT order_.EmployeeID , EmployeeName , SUM(Num * Price) As Sales
                    FROM order_
                    LEFT JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    LEFT JOIN employee ON order_.EmployeeID = employee.EmployeeID
                    WHERE ";

            if($condition["eid"] != ""){
                $sql .= "order_.EmployeeID LIKE :eid AND ";
                $parm[":eid"] = "%".$condition["eid"]."%";
            }

            if($condition["eName"] != ""){
                $sql .= "EmployeeName LIKE :eName AND ";
                $parm[":eName"] = "%".$condition["eName"]."%";
            }

            if($condition["eStart"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) < :eStart AND ";
                $parm[":eStart"] = $condition["eid"];
            }

            if($condition["eEnd"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) < :eEnd AND ";
                $parm[":eEnd"] = $condition["eid"];
            }

            $sql .= " 1=1 GROUP BY order_.EmployeeID , EmployeeName ORDER BY Sales DESC";
            $result = $this->Allresults($sql , $parm);
            return $result;

        }
        // 業務業績單筆查詢
        public function Search_Once($eid){
            $sql = "SELECT order_.EmployeeID , EmployeeName , SUM(Num * Price) AS Sales
                    FROM order_
                    JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    JOIN employee ON order_.EmployeeID = employee.EmployeeID
                    WHERE order_.employeeID = :eid
                    GROUP BY EmployeeName";
            $parm = array(
                ":eid"=>$eid
            );

            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 業務業績查詢全部
        public function Search_All(){

            $sql = "SELECT order_.EmployeeID , EmployeeName ,SUM(Num * Price) as Sales
                    FROM order_
                    JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    JOIN employee ON order_.EmployeeID = employee.EmployeeID
                    GROUP BY order_.EmployeeID , EmployeeName";

            $result = $this->Allresults($sql , null);
            return $result;
        }
        // 客戶銷售單筆查詢
        public function Search_Once_Cus($cid){

        }
        // 客戶銷售全部查詢
        public function Search_All_Cus($eid){

            if($eid == null){
                $particalSql = "";
                $parm = null;
            }else{
                $particalSql = "WHERE EmployeeID = :eid";
                $parm = array(
                    ":eid"=>$eid
                );
            }

            $sql = "SELECT order_.CustomerID , CustomerName ,
                    SUM(Num * Price) as Sales
                    FROM order_
                    JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    JOIN customer ON order_.CustomerID = customer.CustomerID
                    $particalSql
                    GROUP BY order_.CustomerID , CustomerName";
            return $this->Allresults($sql , $parm);
        }
        // 依據 AccessKey 取得員工編號
        public function GetEid($key){
            $sql = "SELECT EmployeeID
                    FROM employee
                    WHERE AccessKey = :key";
            $parm = array(
                ":key"=>$key
            );
            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                return $result["table"]["EmployeeID"];
            }else{
                return null;
            }
        }
        // 查詢該客戶是否為該業務所負責
        public function CheckE2C($eid , $cid){
            $sql = "SELECT EmployeeID
                    FROM order_
                    WHERE EmployeeID = :eid
                    AND CustomerID = :cid";
            $parm = array(
                ":eid"=>$eid,
                ":cid"=>$cid
            );

            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                if(!empty($result["table"])){
                    return true;
                }else{
                    return false;
                }
            }else{
                var_dump($result);
                return false;
            }
        }


    }


?>