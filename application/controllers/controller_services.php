<?php
include "application/models/model_services.php";
class Controller_Services extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Services();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'services.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{	
		$this->view->generate('template_view.php', 'services_view.php', $this->model->data, $this->model->get_view());
	}
	
}
