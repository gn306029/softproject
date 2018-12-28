<?php
    
    class Announcement extends model{

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

            $aid = $dataArr["GET"]["data"];

            if($aid == "All"){
                return $this->Search_All();
            }else if($aid == "condition"){
                $condition = array(
                    "aid"=>$dataArr["GET"]["aid"],
                    "title"=>$dataArr["GET"]["title"],
                    "publishid"=>$dataArr["GET"]["publishid"],
                    "start"=>$dataArr["GET"]["start"],
                    "end"=>$dataArr["GET"]["end"]
                );
                return $this->Search_By_Condition($condition);
            }else{
                return $this->Search_Once($aid);
            }
        }
        // 處理新增請求
        public function Create($dataArr){

            $sql = "INSERT INTO announoement
                    VALUES (:AnnID , :PublishDate , :eid , :Title , :Content)";

            $nowTime = $this->getNowTime();
            $newID = $this->GenerateAID($nowTime);

            $parm = array(
                ":AnnID"=>$newID,
                ":PublishDate"=>$nowTime,
                ":eid"=>$dataArr["POST"]["eid"],
                ":Title"=>$dataArr["POST"]["title"],
                ":Content"=>$dataArr["POST"]["content"]
            );

            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                $this->conn->commit();
                return $result;
            }else{
                $this->conn->rollBack();
                return $result;
            }
        }
        // 處理刪除請求
        public function Delete($dataArr){

            $putData = json_decode($dataArr["PUT"] , true);
            $sql = "DELETE FROM announoement WHERE AnnID = :aid";
            $parm = array(
                ":aid"=>$putData["aid"]
            );
            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                $this->conn->commit();
                return $result;
            }else{
                $this->conn->rollBack();
                return $result;
            }
        }
        // 處理更新請求
        public function Update($dataArr){
            $putData = json_decode($dataArr["PUT"],true);
            $sql = "UPDATE announoement 
                    SET 
                    Title = :nTitle , 
                    Content = :nContent 
                    WHERE AnnID = :aid";
            $parm = array(
                ":aid"=>$putData["aid"],
                ":nTitle"=>$putData["nTitle"],
                ":nContent"=>$putData["nContent"]
            );
            $result = $this->result($sql , $parm);
            if($result["status_code"] == 0){
                $this->conn->commit();
                return $result;
            }else{
                $this->conn->rollBack();
                return $result;
            }
        }
        // 條件查詢
        public function Search_By_Condition($condition){
            $parm = array();

            $sql = "SELECT a.AnnID , PublishDate , Announcer , EmployeeName , Title
                    FROM announoement As a
                    LEFT JOIN employee ON a.Announcer = employee.EmployeeID
                    WHERE ";

            if($condition["aid"] != ""){
                $sql .= "a.AnnID LIKE :aid AND ";
                $parm[":aid"] = "%".$condition["aid"]."%";
            }

            if($condition["title"] != ""){
                $sql .= "Title LIKE :title AND ";
                $parm[":title"] = "%".$condition["title"]."%";
            }

            if($condition["publishid"] != ""){
                $sql .= "Announcer LIKE :publishid AND ";
                $parm[":publishid"] = "%".$condition["title"]."%";
            }

            if($condition["start"] != ""){
                $sql .= "PublishDate >= :start AND ";
                $parm[":start"] = $condition["start"];
            }

            if($condition["end"] != ""){
                $sql .= "PublishDate <= :end AND ";
                $parm[":end"] = $condition["end"];
            }

            $sql .= " 1=1";

            $result = $this->Allresults($sql , $parm);
            return $result;
        }
        // 單筆查詢
        public function Search_Once($aid){
            $sql = "SELECT 
                    a.AnnID , PublishDate ,
                    Announcer , EmployeeName , Title ,
                    Content
                    FROM announoement As a
                    LEFT JOIN employee ON a.Announcer = employee.EmployeeID
                    WHERE a.AnnID = :aid";
            $parm = array(
                ":aid"=>$aid
            );

            $result = $this->result($sql , $parm);

            $sql = "SELECT Object FROM annex WHERE AnnID = :aid";
            $ObjectRes = $this->Allresults($sql , $parm);

            foreach ($ObjectRes["table"] as $key => $value) {
                $ObjectRes["table"][$key]["Object"] = base64_encode($value["Object"]);
            }
            
            $result["Object"] = $ObjectRes["table"];
            return $result;
        }
        // 查詢全部
        public function Search_All(){

            $sql = "SELECT 
                    a.AnnID , PublishDate ,
                    Announcer , EmployeeName , Title
                    FROM announoement As a
                    LEFT JOIN employee ON a.Announcer = employee.EmployeeID";

            $result = $this->Allresults($sql , null);
            return $result;
        }
        private function GenerateAID($nt){

            $dt = new DateTime($nt);
            $y = substr($dt->format("Y"), -2);
            $m = $dt->format("m");
            $d = $dt->format("d");
            $s = substr(base64_encode($dt->getTimestamp()) , -10 , 8);
            return $y.$m.$d.'-'.$s;
        }


    }


?>