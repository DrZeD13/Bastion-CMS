<?php
include "application/models/model_news.php";
class Controller_News extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_News();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'news.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{	
		$this->view->generate('template_view.php', 'news_view.php', $this->model->data, $this->model->get_view());
	}
	
}
