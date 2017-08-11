<?php
include "application/models/model_awards.php";
class Controller_Awards extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Awards();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'awards.php', $this->model->data, $this->model->get_data());
	}
	
}
