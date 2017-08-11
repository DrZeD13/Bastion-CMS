<?php
include "application/models/model_cart.php";
class Controller_Cart
{

	function __construct() 
	{	
		$this->model = new Model_Cart();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{									
		$this->view->generate('template_view.php', 'cart.php',  $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{		
		$data = $this->model->get_view();	
		$this->view->generate('template_view.php', 'cart_view.php', $this->model->data, $data);
	}
	
	function action_actions()
	{
		$this->model->cart_actions();
	}
	
	function action_send()
	{
		$this->model->send();
		$this->action_view();
	}
	
}