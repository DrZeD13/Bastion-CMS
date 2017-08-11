<?php
include "application/models/adm/model_adm_history.php";
class Controller_Adm_History extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Adm_History();
		$this->view = new View();
		//проверяем есть ли доступ к этому разделу
		$this->model->Get_Access("history");
		// получаем шапку
		$this->model->data = $this->model->GetFixed();
	}
	
	function action_index()	
	{		
		$this->view->generate_adm('template_view.php', 'history.php', $this->model->data, $this->model->get_data());		
	}
	function action_clear()	
	{		
		$this->model->clear_history();
		$this->action_index();
	}
	
}