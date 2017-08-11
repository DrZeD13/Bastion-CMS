<?php
include "application/models/model_ajax.php";
class Controller_Ajax extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Ajax();
	}
	
	function action_index()	
	{		
		$this->model->index();
	}
	
}
