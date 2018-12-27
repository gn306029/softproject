<?php
	define('__ROOT__', dirname(__FILE__));
	require_once  __ROOT__."/include/php/event_Message.php";
	require_once  __ROOT__."/include/php/mySQL.php";
	require_once __ROOT__."/include/php/model.php";
	require_once __ROOT__."/include/php/actionListener.php";
	session_start();
	/**/
	echo json_encode((new Main())->run());
	class Main{
		function __construct(){
			mySQL::$db_host="localhost";
			mySQL::$db_name="order";
			mySQL::$db_user="root";
			mySQL::$db_password="";
		}

		function run(){
			try {
				if(isset($_POST['module'])){
					$module=$_POST['module'];
				}else{
					$module="login";
				}
				require_once __ROOT__."/modules/".$module."/action_Dispatcher.php";
				$event_message= new event_Message($_GET,$_POST);
				$module_object= new action_Dispatcher();
				return $module_object->doAction($event_message);
				if($_POST['module']=="login"||isset($_SESSION['login'])){
					if(isset($_POST['module'])){
						$module=$_POST['module'];
					}else{
						$module="login";
					}
					require_once __ROOT__."/modules/".$module."/action_Dispatcher.php";
					$event_message= new event_Message($_GET,$_POST);
					$module_object=new action_Dispatcher();
					return $module_object->doAction($event_message);
				}else{
					$return_value['status_code']=-1;
					$return_value['status_message']="Not Logged_In";
					return $return_value;
				}
			}catch(Exception $e){
				$error=fopen("./error.log", "a");
				fwrite($error,$e->getMessage()."\r\n");
				fclose($error);
			}
		}
	}
?>
