<?php
include "application/models/adm/model_adm_settings.php";
class Controller_Adm_Settings extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Adm_Settings();
		$this->view = new View();
		//��������� ���� �� ������ � ����� �������
		$this->model->Get_Access($this->model->table_name);
		// �������� �����
		$this->model->data = $this->model->GetFixed();
	}
	
	function action_index()	
	{									
		$this->view->generate_adm('template_view.php', 'settings.php',  $this->model->data, $this->model->get_data());
	}	
	
	function action_save()
	{
		$this->model->Save();
		$this->action_index();
	}

}