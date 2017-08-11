<?php
include "application/models/model_qanswers.php";
class Controller_Qanswers extends Controller 
{

	function __construct() 
	{
		$this->model = new Model_Qanswers();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{
		$this->view->generate('template_view.php', 'qanswers.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{	
		$this->view->generate('template_view.php', 'qanswers_view.php', $this->model->data, $this->model->get_view());
	}
	
	function action_send()
	{
		$this->model->send();
		$this->action_index();
	}
	
}
