<?php
include "application/models/model_sitemap.php";
class Controller_Sitemap extends Controller {

	function __construct() {
	
		$this->model = new Model_Sitemap();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	{
	
		$data = $this->model->get_data();		
		$this->view->generate('template_view.php', 'sitemap.php', $this->model->data, $data);
	}
	
}
