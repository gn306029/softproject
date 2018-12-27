<?php
	
	class Order extends model{

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
        // 處理尋找訂單的請求
        public function Search($dataArr){

        	if(substr($dataArr["GET"]["data"], -1) == "/"){
                $oid = substr($dataArr["GET"]["data"], 0 , -1);
            }else{
                $oid = $dataArr["GET"]["data"];
            }

        	if($oid == "All"){
                return $this->Search_All();
            }elseif($oid == "Search"){
                return $this->Search_Input($dataArr["GET"]);
            }else{
                return $this->Search_Once($oid);
            }
        }
        // 處理新建訂單的請求
        public function Create($dataArr){

        	$newData = $dataArr["POST"];
            $d = new DateTime($newData["Date"]);
            $newOrderID = $this->GenOrderID($d);

        	$MainData = array(
        		"oid"=>$newOrderID,
        		"cid"=>$newData["cid"],
        		"eid"=>$newData["eid"],
        		"Year"=>$d->format("Y"),
                "Month"=>$d->format("m"),
                "Day"=>$d->format("d"),
                "Hour"=>$d->format("h"),
                "Minute"=>$d->format("i")
        	);

        	$DetailData = $newData["Detail"];

        	$res1 = $this->CreateMainOrder($MainData);
        	$res2 = $this->CreateOrderDetail($MainData , $DetailData);

        	if($res1["status_code"] == 0 && $res2["status_code"] == 0){
                $this->conn->commit();
                return array(
                    "status_code"=>0,
                    "status_message"=>"Success"
                );
        	}else{
        		return array(
                    "status_code"=>-1,
                    "status_message1"=>$res1["status_message"],
                    "status_message2"=>$res2["status_message"],
                );
        		$this->conn->rollBack();
        	}
        }
        // 處理更新訂單的請求
        public function Update($dataArr){
        	$putData = json_decode($dataArr["PUT"] , true);
            $putDetail = json_decode($putData["Detail"] , true);
        	$MainData = array(
        		"oid"=>$putData["oid"]
        	);
        	$res = $this->CreateOrderDetail($MainData , $putData["Detail"]);
        	if($res["status_code"] == 0){
                $this->conn->commit();
                return $res;
        	}else{
                $this->conn->rollBack();
                return $res;
        	}
        }
        // 找特定訂單
        private function Search_Once($oid){
        	$sql = "SELECT 
        			order_.OrderID , order_.CustomerID , order_.EmployeeID , orderdetail.ProductID ,
        			CONCAT(Year , '-' , LPAD(Month , 2 , '0') , '-' , LPAD(Day , 2 , '0') , ' ' , LPAD(Hour , 2 , '0') , ':' , LPAD(Minute , 2 , '0') ) As CreateTime ,
                    Num , Specification , isGift , LastUpdateTime , ProductName , Price
        			FROM order_
        			LEFT JOIN orderdetail ON order_.OrderID = orderdetail.OrderID
        			LEFT JOIN product ON orderdetail.ProductID = Product.ProductID
        			LEFT JOIN employee ON order_.EmployeeID = employee.EmployeeID
        			LEFT JOIN customer ON order_.CustomerID = customer.CustomerID
        			WHERE order_.OrderID = :oid
                    AND LastUpdateTime = (
                        SELECT MAX(LastUpdateTime) FROM orderdetail
                        WHERE orderdetail.OrderID = :oid2
                    )";

        	$parm = array(
        		":oid"=>$oid,
                ":oid2"=>$oid
        	);

        	$result = $this->Allresults($sql , $parm);
        	return $result;
        }
        // 找所有訂單
        private function Search_All(){
        	$sql = "SELECT 
        			order_.OrderID , order_.CustomerID , order_.EmployeeID , CustomerName ,
                    CONCAT(Year , '-' , LPAD(Month , 2 , '0') , '-' , LPAD(Day , 2 , '0') , ' ' , LPAD(Hour , 2 , '0') , ':' , LPAD(Minute , 2 , '0') ) As CreateTime
        			FROM order_
                    LEFT JOIN customer ON order_.CustomerID = customer.CustomerID
                    ORDER BY CreateTime DESC";

        	$result = $this->Allresults($sql , null);
        	return $result;
        }
        // 新增訂單主檔
        private function CreateMainOrder($MainData){

        	$sql = "INSERT INTO order_ VALUES (:oid , :cid , :eid , :Year , :Month , :Day , :Hour , :Minute)";
        	$parm = array(
        		":oid"=>$MainData["oid"],
        		":cid"=>$MainData["cid"],
        		":eid"=>$MainData["eid"],
        		":Year"=>$MainData["Year"],
                ":Month"=>$MainData["Month"],
                ":Day"=>$MainData["Day"],
                ":Hour"=>$MainData["Hour"],
                ":Minute"=>$MainData["Minute"]
        	);

        	$result = $this->Allresults($sql , $parm);
        	return $result;
        }
        // 新增訂單明細
        private function CreateOrderDetail($MainData , $DetailData){
        	$isError = false;
        	$error = array("status_code"=>0);
        	$LastUpdateTime = $this->getNowTime();
            $DetailData = json_decode($DetailData , true);
            if(empty($DetailData)){
                $error["status_code"] = -10;
                $error["status_message"] = "Detail is Empty";
                return $error;
            }
            try{
        		foreach ($DetailData as $key => $value) {
        			$sql = "INSERT INTO orderdetail
        					VALUES(:oid , :pid , :num , :price , :spec , :isGift , :LastTime)";
        			$parm = array(
        				":oid"=>$MainData["oid"],
        				":pid"=>$value["ProductID"],
        				":num"=>$value["Num"],
                        ":price"=>$value["Price"],
        				":spec"=>$value["Specification"],
        				":isGift"=>$value["IsGift"],
        				":LastTime"=>$LastUpdateTime
        			);

        			$res = $this->result($sql , $parm);
        			if($res["status_code"] == -1){
        				$isError = true;
        				array_push($error, $res);
        			}
        		}
            }catch(Exception $e){
                $error["status_code"] = -1;
                $error["status_message"] = "SystemError";
                return $error; 
            }

    		if($isError){
    			$error["status_code"] = -1;
    			return $error;
    		}else{
    			$error["status_code"] = 0;
    			return $error;
    		}
        }
        // 產生訂單編號
        private function GenOrderID($datetime){
            $SerialID = substr(base64_encode($datetime->getTimestamp()) , -9 , 7);
            $newOrderID = "O-".$datetime->format("Y-m-d")."-".$SerialID;
            return $newOrderID;
        }

        private function Search_Input($oid){
            $sql = "
                SELECT
                order_.OrderID , order_.CustomerID , order_.EmployeeID , CustomerName ,
                CONCAT(Year , '-' , LPAD(Month , 2 , '0') , '-' , LPAD(Day , 2 , '0') , ' ' , LPAD(Hour , 2 , '0') , ':' , LPAD(Minute , 2 , '0') ) As CreateTime                
                FROM order_
                LEFT JOIN customer ON order_.CustomerID = customer.CustomerID
                WHERE ";
            if($oid["SearchKind"]=="date"){
                try {
                    $temp = explode("-", $oid["SearchInput"]);
                    $parm = array(
                        ":oid1"=>$temp[0],
                        ":oid2"=>$temp[1]
                     );
                    $sql .= "Year = :oid1 AND Month = :oid2";
                } catch (Exception $e) {
                    $parm = null;
                    $sql .= "1";
                }
            }else{
                $sql .=  $oid["SearchKind"]." LIKE :oid";
                $parm = array(
                    ":oid"=>"%".$oid["SearchInput"]."%"
                );
            }
            $sql .= " ORDER BY CreateTime DESC";
            $result = $this->Allresults($sql , $parm);
            return $result;
        }

	}


?>