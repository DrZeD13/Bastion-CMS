<?php
include "application/models/model_actions.php";
class Controller_Actions extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Actions();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'actions.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{	
		$this->view->generate('template_view.php', 'actions_view.php', $this->model->data, $this->model->get_view());
	}
	
}
