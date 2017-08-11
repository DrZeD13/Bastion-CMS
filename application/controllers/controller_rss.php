<?php
include "application/models/model_rss.php";
class Controller_Rss extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Rss();
		$this->view = new View();
	}
	
	function action_index()	
	{		
		header("Content-Type: application/xml");
		$this->view->generate('rss.php', '', $this->model->get_data());
	}
	
}
