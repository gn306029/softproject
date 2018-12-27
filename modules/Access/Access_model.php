<?php
    
    class Access extends model{

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
        // 處理登入請求
        public function Login($dataArr){

            $account = $dataArr["POST"]["Account"];
            $pwd = $dataArr["POST"]["Pwd"];

            $sql = "SELECT AccessKey , JobID
                    FROM employee
                    WHERE EmployeeID = :eid
                    AND Password = :pwd";

            $parm = array(
                ":eid"=>$account,
                ":pwd"=>$pwd
            );

            $result = $this->result($sql , $parm);

            if($result["status_code"] == 0){

                $_SESSION[$account] = $result["table"]["AccessKey"];

                switch ($result["table"]["JobID"]) {
                    case 'J0001':
                        // 一般
                        $Access = 0;
                        break;
                    
                    case 'J0002':
                        // 總公司
                        $Access = 1;
                        break;
                }

                if(!empty($result["table"]["AccessKey"])){
                    $res = array(
                        "status_code"=>0,
                        "key"=>$result["table"]["AccessKey"],
                        "permission"=>$Access
                    );
                    return $res;
                }else{
                    $res = array(
                        "status_code"=>-1,
                        "message"=>"Auth failed"
                    );
                    return $res;
                }
            }else{
                $res = array(
                    "status_code"=>-2,
                    "message"=>"System failed"
                );
                return $res;
            }
        }
        // 處理登出請求
        public function Logout($dataArr){

            $accesskey = $dataArr["POST"]["Key"];

            $sql = "SELECT
                    EmployeeID
                    FROM employee
                    WHERE AccessKey = :key";

            $parm = array(
                ":key"=>$accesskey
            );

            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                if($result["rowCount"] == 1){
                    $upRes = $this->UpdateKey($result["table"]["EmployeeID"]);
                    if($upRes){
                        try {
                            unset($_SESSION[$result["table"]["EmployeeID"]]);
                            $this->conn->commit();
                            $res = array(
                                "status_code"=>0,
                                "message"=>"Logout success"
                            );
                            return $res;
                        } catch (Exception $e) {
                            $res = array(
                                "status_code"=>-2,
                                "message"=>"Error"
                            );
                            return $res;
                        }
                    }else{
                        $this->conn->rollBack();

                        $res = array(
                            "status_code"=>-1,
                            "message"=>"Logout failed"
                        );
                        return $res;
                    }
                }else{
                    $this->conn->rollBack();

                    $res = array(
                        "status_code"=>-1,
                        "message"=>"Access failed"
                    );
                    return $res;
                }
            }
        }
        // 更改憑證
        private function UpdateKey($eid){

            $sql = "UPDATE employee SET AccessKey = :newKey WHERE EmployeeID = :eid";

            $parm = array(
                ":eid"=>$eid,
                ":newKey"=>base64_encode($eid.$this->getNowTime())
            );

            $result = $this->result($sql , $parm);

            if($result["status_code"] == 0){
                return true;
            }else{
                return false;
            }
        }
        // 檢查憑證是否存在
        public function CheckKey($dataArr){
            $key = "";
            if(empty($dataArr["POST"]["Key"])){
                if(empty($dataArr["GET"]["Key"])){
                    $matches = array();
                    preg_match('/\"Key\"\:\"(.*?)\"\,/', $dataArr["PUT"], $matches);
                    if(empty($matches)){
                        return array(
                            "status_code"=>-1,
                            "message"=>"Not Promission"
                        );
                    }else{
                        $key = $matches[1];
                    }
                }else{
                    $key = $dataArr["GET"]["Key"];
                }
            }else{
                $key = $dataArr["POST"]["Key"];
            }

            $sql = "SELECT EmployeeID , employee.JobID
                    FROM employee 
                    JOIN job ON employee.JobID = job.JobID
                    WHERE AccessKey = :Key";
            $parm = array(
                ":Key"=>$key
            );

            $result = $this->result($sql , $parm);
            if($result["rowCount"] == 1){

                switch ($result["table"]["JobID"]) {
                    case 'J0001':
                        // 一般
                        $Access = 0;
                        break;
                    
                    case 'J0002':
                        // 總公司
                        $Access = 1;
                        break;
                }

                return array(
                    "status_code"=>0,
                    "message"=>"Success",
                    "access"=>$Access,
                    "key"=>$key
                );
            }else{
                return array(
                    "status_code"=>-1,
                    "message"=>"Not Promission"
                );
            }
        }
    }


?>