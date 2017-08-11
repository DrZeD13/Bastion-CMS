<?php
include "application/models/model_gallery.php";
class Controller_Gallery extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Gallery();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'gallery.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{			
		$this->view->generate('template_view.php', 'gallery_view.php', $this->model->data, $this->model->get_view());
	}
	
}
