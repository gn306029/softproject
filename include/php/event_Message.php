<?php  
	class event_Message{
		function __construct(){
			$this->get=$_GET;
			$this->post=$_POST;
		}
		public function getGet(){
			return $this->get;
		}
		public function getPost(){
			return $this->post;
		}
	}
?>