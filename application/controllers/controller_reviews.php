<?php
include "application/models/model_reviews.php";
class Controller_Reviews extends Controller {

	function __construct() {
	
		$this->model = new Model_Reviews();
		$this->view = new View();
	}
	
	function action_index()	{
	
		$data = $this->model->get_data();		
		$tree = $this->model->get_tree();	
		$this->view->generate('catalog_view.php', 'template_view.php', $data, $tree);
	}
	
}
