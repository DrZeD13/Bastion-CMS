<?php

class Controller_404 extends Controller 
{
	
	function __construct() 
	{	
		$this->model = new Model();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{
		header('HTTP/1.1 404 Not Found');
		header("Status: 404 Not Found");
		$data = array (
			"title" => "Ошибка 404", 
			"head_title" =>"Ошибка 404 - страница не найдена",
			"description" =>"Ошибка 404 - страница не найдена",
			"keywords" =>"Ошибка 404 - страница не найдена",
		);
		$data["nav"] = MAIN_NAV."Ошибка 404";
		$this->view->generate('template_view.php', '404_view.php', $this->model->data, $data);
	}

}
