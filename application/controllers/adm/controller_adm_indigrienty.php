<?php
include "application/models/adm/model_adm_indigrienty.php";
class Controller_Adm_Indigrienty extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Adm_Indigrienty();
		$this->view = new View();
		//проверяем есть ли доступ к этому разделу
		$this->model->Get_Access($this->model->table_name);
		// получаем шапку
		$this->model->data = $this->model->GetFixed();
	}
	
	function action_index()	
	{		
		$this->view->generate_adm('template_view.php', 'indigrienty.php', $this->model->data, $this->model->get_data());		
	}
	
	function action_del()
	{
		$this->model->Delete();
		$this->action_index();
	}
	
	function action_insert()
	{
		$this->model->Insert();
		$this->action_index();		
	}
	
	function action_update()
	{
		$this->model->Update();		
		$this->action_index();
	}
	
	
}