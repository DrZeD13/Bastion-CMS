<?php
include "application/models/model_articles.php";
class Controller_Articles extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Articles();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'articles.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{			
		$this->view->generate('template_view.php', 'articles_view.php', $this->model->data, $this->model->get_view());
	}
	
	function action_asc()
	{
		if ($this->model->act_asc())
		{
			unset ($_POST["name"]);
			unset ($_POST["comment"]);
		}
		$this->action_view();
	}
	
}
