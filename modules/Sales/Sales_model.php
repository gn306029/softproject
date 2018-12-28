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
                "eid"=>$dataArr["GET"]["id"],
                "eName"=>$dataArr["GET"]["Name"],
                "eStart"=>$dataArr["GET"]["Start"],
                "eEnd"=>$dataArr["GET"]["End"]
            );
            $access = $dataArr["access"];
            $key = $dataArr["Key"];
            if($access == 0){
                if($data == "employee"){
                    $key_eid = $this->GetEid($key);
                    $result = $this->Search_E_Condition($condition);
                    // 過濾不屬於自己的業績
                    $filterData = array();
                    foreach ($result["table"] as $key => $value) {
                        if($value["EmployeeID"] == $key_eid){
                            array_push($filterData, $value);
                        }
                    }
                    $result["table"] = $filterData;
                    return $result;
                }else if($data == "customer"){
                    $key_eid = $this->GetEid($key);
                    $result = $this->Search_C_Condition($condition);
                    // 過濾不屬於自己所負責的客戶
                    $filterData = array();
                    foreach ($result["table"] as $key => $value) {
                        if($value["EmployeeID"] == $key_eid){
                            array_push($filterData, $value);
                        }
                    }
                    $result["table"] = $filterData;
                    return $result;
                }else if($data == "product"){
                    $key_eid = $this->GetEid($key);
                    $condition["employeeID"] = $key_eid;
                    return $this->Search_P_Condition($condition , true);
                }
            }else{
                if($data == "employee"){
                    return $this->Search_E_Condition($condition);
                }else if($data == "customer"){
                    return $this->Search_C_Condition($condition);
                }else if($data == "product"){
                    return $this->Search_P_Condition($condition , false);
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
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) >=:eStart AND ";
                $parm[":eStart"] = $condition["eStart"];
            }

            if($condition["eEnd"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) <= :eEnd AND ";
                $parm[":eEnd"] = $condition["eEnd"];
            }

            $sql .= " 1=1 GROUP BY order_.EmployeeID , EmployeeName ORDER BY Sales DESC";
            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 根據條件查詢客戶的銷售紀錄
        public function Search_C_Condition($condition){
            $parm = array();

            $sql = "SELECT order_.EmployeeID , order_.CustomerID , CustomerName , SUM(Num * Price) As Sales
                    FROM order_
                    LEFT JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    LEFT JOIN customer ON order_.CustomerID = customer.CustomerID
                    WHERE ";

            if($condition["eid"] != ""){
                $sql .= "order_.CustomerID LIKE :eid AND ";
                $parm[":eid"] = "%".$condition["eid"]."%";
            }

            if($condition["eName"] != ""){
                $sql .= "CustomerName LIKE :eName AND ";
                $parm[":eName"] = "%".$condition["eName"]."%";
            }

            if($condition["eStart"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) >= :eStart AND ";
                $parm[":eStart"] = $condition["eStart"];
            }

            if($condition["eEnd"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) <= :eEnd AND ";
                $parm[":eEnd"] = $condition["eEnd"];
            }

            $sql .= " 1=1 GROUP BY order_.EmployeeID , order_.CustomerID , CustomerName ORDER BY Sales DESC";
            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 根據條件查詢產品的銷售紀錄
        public function Search_P_Condition($condition , $hasAccess){
            $parm = array();
            // 若為一般業務 則只會顯示自己所負責的產品的銷售數據
            if($hasAccess){
                $sql = "SELECT EmployeeID , orderdetail.ProductID , ProductName , SUM(Num * Price) As Sales
                    FROM order_
                    LEFT JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    LEFT JOIN product ON orderdetail.ProductID = product.ProductID
                    WHERE order_.EmployeeID = :employeeID AND ";
                $parm[":employeeID"] = $condition["employeeID"];
            }else{
                $sql = "SELECT orderdetail.ProductID , ProductName , SUM(Num * Price) As Sales
                    FROM order_
                    LEFT JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
                    LEFT JOIN product ON orderdetail.ProductID = product.ProductID
                    WHERE ";
            }

            if($condition["eid"] != ""){
                $sql .= "orderdetail.ProductID LIKE :eid AND ";
                $parm[":eid"] = "%".$condition["eid"]."%";
            }

            if($condition["eName"] != ""){
                $sql .= "ProductName LIKE :eName AND ";
                $parm[":eName"] = "%".$condition["eName"]."%";
            }

            if($condition["eStart"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) >= :eStart AND ";
                $parm[":eStart"] = $condition["eStart"];
            }

            if($condition["eEnd"] != ""){
                $sql .= "CONCAT(Year , '-' , LPAD(Month,2,'0') , '-' , LPAD(Day,2,'0')) <= :eEnd AND ";
                $parm[":eEnd"] = $condition["eEnd"];
            }

            if($hasAccess){
                $sql .= " 1=1 GROUP BY EmployeeID , orderdetail.ProductID , ProductName ORDER BY Sales DESC";
            }else{
                $sql .= " 1=1 GROUP BY orderdetail.ProductID , ProductName ORDER BY Sales DESC";
            }

            $result = $this->Allresults($sql , $parm);
            return $result;
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
    }


?>