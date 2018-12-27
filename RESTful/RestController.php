<?php
session_start();
require_once("SiteRestHandler.php");
// 判斷 URL 請求是否為模組首頁

function CheckIndex(){
	$ReqUrl = explode("/", substr($_SERVER["REQUEST_URI"] , 0 , -1));
	if(end($ReqUrl) == "Index"){
		return true;
	}else{
		return false;
	}
}
// 判斷是否為不須權限即可使用的請求
// true => 需有權限
// fasle => 不須有全縣
function CheckAccess(){
	if($_GET["method"] == "Access"){
		if(!isset($_GET["do"])){
			return true;
		}else{
			if($_GET["do"] != "Login" && $_GET["do"] != "CheckKey"){
				return true;
			}else{
				return false;
			}
		}
	}else{
		return true;
	}
}

$method = (isset($_GET["method"])) ? $_GET["method"] : "";
$do = (isset($_GET["do"])) ? $_GET["do"] : "";


$dataArr = array(
	"GET"=>$_GET,
	"POST"=>$_POST,
	"PUT"=>file_get_contents('php://input')
);

//var_dump($dataArr);
if(!CheckIndex()){
	if(CheckAccess()){
		$check = new SiteRestHandler("Access");
		$res = json_decode($check->run("CheckKey" , $dataArr),true);
		if($res["status_code"] != 0){
			echo json_encode($res);
			return;
		}
		$dataArr["access"] = $res["access"];
		$dataArr["Key"] = $res["key"];
	}
}

switch($method){

	case "Order":
	case "VisitRecord":
	case "Customer":
	case "Products":
	case "Employee":
	case "Announcement":
		$siteRestHandler = new SiteRestHandler($method);
		
		switch ($_SERVER["REQUEST_METHOD"]) {
			case 'GET':
				$do = "Search";
				break;
			
			case 'POST':
				$do = "Create";
				break;

			case 'PUT':
				if($dataArr["GET"]["data"] == "Delete"){
					$do = "Delete";
				}else{
					$do = "Update";
				}
				break;

			default:
				# code...
				break;
		}

		$siteRestHandler->run($do , $dataArr);
		$siteRestHandler->echo();
		break;

	case "History":
		$siteRestHandler = new SiteRestHandler($method);
		$siteRestHandler->run($do , $dataArr);
		$siteRestHandler->echo();
		break;

	case "Sales":
		$siteRestHandler = new SiteRestHandler($method);

		switch ($_SERVER["REQUEST_METHOD"]) {
			case 'GET':
				$do = "Search";
				break;
		}

		$siteRestHandler->run($do , $dataArr);
		$siteRestHandler->echo();
		break;

	case "Access":
		$siteRestHandler = new SiteRestHandler($method);
		$siteRestHandler->run($do , $dataArr);
		$siteRestHandler->echo();
		break;

	// case "Announcement":
	// 	$siteRestHandler = new SiteRestHandler($method);

	// 	switch ($_SERVER["REQUEST_METHOD"]) {
	// 		case 'GET':
	// 			$do = "Search";
	// 			break;
	// 	}

	// 	$siteRestHandler->run($do , $dataArr);
	// 	$siteRestHandler->echo();
	// 	break;

	case "" :
		echo "NOT FOUND";
		//404 - not found;
		break;
}
?>