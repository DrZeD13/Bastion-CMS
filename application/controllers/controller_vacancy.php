<?php
include "application/models/model_vacancy.php";
class Controller_Vacancy extends Controller {

	function __construct() {
	
		$this->model = new Model_Vacancy();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'vacancy.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{	
		$this->view->generate('template_view.php', 'vacancy_view.php', $this->model->data, $this->model->get_view());
	}
	
}
