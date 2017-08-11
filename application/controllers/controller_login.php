<?php
include "application/models/model_login.php";
class Controller_Login extends Controller 
{
	function __construct() 
	{	
		$this->model = new Model_Login();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{			
		if ($this->model->is_user)
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{			
			$this->view->generate('template_view.php', 'login.php',  $this->model->data, $this->model->get_data());
		}
	}
	
	function action_cabinet()	
	{	
		if ($this->model->is_user)
		{
			$this->view->generate('template_view.php', 'cabinet.php',  $this->model->data, $this->model->get_cabinet());
		}
		else
		{
			$this->model->Redirect($this->model->siteUrl."login/");
		}
		
	}
	
	function action_changepassword()	
	{	
		if ($this->model->is_user)
		{
			$this->view->generate('template_view.php', 'changepassword.php',  $this->model->data, $this->model->get_changepassword());
		}
		else
		{
			$this->model->Redirect($this->model->siteUrl."login/");
		}
		
	}
	
	function action_registration()	
	{	
		if ($this->model->is_user)
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{
			$this->view->generate('template_view.php', 'registration.php',  $this->model->data, $this->model->get_registration());
		}		
	}
	
	function action_authVK()	
	{	
		if ($this->model->is_user)
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{
			$this->model->get_authVK();
			//$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}		
	}
	
	function action_registrationOn()	
	{	
		if ($this->model->get_registrationOn())
		{
			$this->view->generate('template_view.php', 'registrationOn.php',  $this->model->data, $this->model->get_registration());
		}
		else
		{
			$this->action_registration();
		}		
	}
	
	function action_activate()	
	{	
		if ($this->model->is_user)
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{
			$this->view->generate('template_view.php', 'activate.php',  $this->model->data, $this->model->get_activate());
		}
		
	}
	
	function action_login()	
	{	
		if ($this->model->avtorized())
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{
			$this->action_index();
		}		
		
	}
	
	function action_logout()	
	{			
		unset($_SESSION["U_ID"]);
		setcookie("id", "", 0, "/");
		setcookie("hash", "", 0, "/");
		setcookie("U_LOGIN", "", 0, "/");
		setcookie("social", "", 0, "/");
		if (isset($_SERVER["HTTP_REFERER"]))
		{
			$this->model->Redirect($_SERVER["HTTP_REFERER"]);	
		}
		else
		{
			$this->model->Redirect($this->model->siteUrl."login/");	
		}
						
	}
	
	function action_lostpassword()	
	{	
		if ($this->model->is_user)
		{
			$this->model->Redirect($this->model->siteUrl."login/cabinet");
		}
		else
		{
			$this->view->generate('template_view.php', 'lostpassword.php',  $this->model->data, $this->model->get_lostpassword());
		}					
	}
	
	function action_lostpasswordOn()	
	{	
		$this->model->get_lostpasswordOn();
		$this->action_lostpassword();
	}
	
}
