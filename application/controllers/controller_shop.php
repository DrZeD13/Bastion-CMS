<?php
include "application/models/model_shop.php";
class Controller_Shop extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_Shop();
		$this->view = new View();
		$this->model->data = $this->model->GetFixedSite();
	}
	
	function action_index()	
	{		
		$this->view->generate('template_view.php', 'shops.php', $this->model->data, $this->model->get_data());
	}
	
	function action_view()	
	{			
		$this->view->generate('template_view.php', 'shops_view.php', $this->model->data, $this->model->get_view());
	}
	
	function action_print()	
	{			
		$this->view->generate('print_product_view.php', '', $this->model->data, $this->model->get_view());
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
	function action_rating()
	{
		$this->model->get_rating();
	}
	
}
