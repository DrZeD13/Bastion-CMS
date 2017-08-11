<?php
include "application/models/model_main.php";
class Controller_Main
{

	function __construct() 
	{	
		$this->model = new Model_Main();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{									
		$this->view->generate('template_main.php', 'main.php',  $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{		
		$data = $this->model->get_view();	
	$this->view->generate($data['tamplatemain'], $data['tamplateview'], $this->model->data, $data);
	}
	
	function action_send()
	{
		if ($this->model->send())
		{
			$this->model->Redirect($_SERVER['HTTP_REFERER']);
		}
		else
		{
			$this->action_view();
		}		
	}
	
}