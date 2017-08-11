<?php
include "application/models/adm/model_adm_login.php";
class Controller_Adm_Login extends Controller 
{
	function __construct() 
	{	
		$this->model = new Model_Adm_Login();
		$this->view = new View();
	}
	
	function action_index()	
	{					
		$data['error'] = $this->model->GetError('error');
		$this->model->get_data();		
		$this->view->generate_adm('login.php', '', null, $data);
	}
	
	
	function action_login()	
	{	
		//$_SESSION = array();
		if ($this->model->avtorized())
		{			
			//$this->model->Redirect("/adm/pages/");
		}
		else
		{
			$this->action_index();
		}		
		
	}
	
	function action_logout()	
	{			
		unset($_SESSION["A_ID"]);
		unset($_SESSION["token"]);
		$this->action_index();						
	}
	
}
