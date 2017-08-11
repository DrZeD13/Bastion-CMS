<?php
include "application/models/model_comments.php";
class Controller_Comments extends Controller {

	function __construct() {
	
		$this->model = new Model_Comments();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'comments.php', $this->model->data, $this->model->get_data());
	}
	
}
