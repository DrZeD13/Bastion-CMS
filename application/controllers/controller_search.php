<?php
include "application/models/model_search.php";
class Controller_Search extends Controller 
{

	function __construct() 
	{
	
		$this->model = new Model_Search();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{	
		$this->view->generate('template_view.php', 'search.php', $this->model->data, $this->model->get_data());
	}
	
}
