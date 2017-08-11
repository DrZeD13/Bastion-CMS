<?php
include "application/models/model_xmlsitemap.php";
class Controller_XMLSitemap extends Controller 
{

	function __construct() 
	{	
		$this->model = new Model_XMLSitemap();
		$this->view = new View();
	}
	
	function action_index()	
	{			
		header("Content-Type: application/xml");
		$this->view->generate('xmlsitemap.php', '', $this->model->get_data());		
	}
	
}
