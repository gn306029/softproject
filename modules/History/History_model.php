<?php
    
    class History extends model{

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
        // 依客戶查詢
        public function C_Search($dataArr){

            $sql = "SELECT
                    order_.OrderID , order_.CustomerID , order_.EmployeeID ,
                    CreateTime , CustomerName , EmployeeName
                    FROM order_
                    LEFT JOIN customer ON order_.CustomerID = customer.CustomerID
                    LEFT JOIN employee ON order_.EmployeeID = employee.EmployeeID
                    WHERE order_.CustomerID = :cid";

            $parm = array(
                ":cid"=>$dataArr["GET"]["cid"]
            );

            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 依照產品查詢
        public function P_Search($dataArr){

            $sql = "SELECT
                    orderdetail.OrderID , order_.CreateTime , 
                    Num , isGift , Specification
                    FROM orderdetail
                    JOIN order_ ON orderdetail.OrderID = order_.OrderID
                    WHERE ProductID = :pid";

            $parm = array(
                ":pid"=>$dataArr["GET"]["pid"]
            );

            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 依照月份查詢
        public function M_Search($dataArr){

            $sql = "SELECT
                    orderdetail.OrderID , order_.CreateTime , 
                    Num , isGift , Specification
                    FROM orderdetail
                    JOIN order_ ON orderdetail.OrderID = order_.OrderID
                    WHERE substring(CreateTime , 1 , 7) = :Ym";

            $parm = array(
                ":Ym"=>$dataArr["GET"]["Ym"]
            );

            $result = $this->Allresults($sql , $parm);
            return $result;
        }


    }


?>