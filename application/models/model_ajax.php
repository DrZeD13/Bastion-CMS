<?php
//include_once('../core/model_default.php');
//include "application/core/model_default.php";
class Model_Ajax extends Model_Default
{	
	function __construct()	
	{		
		global $db;
        $this->db = $db;
		$this->is_user = $this->CheckLogin();			
	}
	
	function index() 
	{
		$parse = parse_url($_SERVER['REQUEST_URI']);
		$routes = explode('/', $parse['path']);
		if (isset($routes[2]))
		{
			switch ($routes[2])
			{
				case "rating":
					$this->get_rating();
				break;
				default: 
					$this->error404();
			}			
		}
		else
		{
			$this->error404();
		}
	}
	
	function get_rating()
	{
		$id = $this->GetGP("productID", 0);
		$value = $this->GetGP("value", 0);
		if ($value > 0)
		{
			$row = $this->db->GetEntry("Select rating, respondents from `products` Where product_id ='$id'");
			/*$res = $this->db->GetOne("Select respondents from `products` Where product_id ='$id'", 0);
			$rating = $this->db->GetOne("Select rating from `products` Where product_id ='$id'", 0);*/
			if ($row)
			{
				$res = $row['respondents'];
				$rating = $row['rating'];
				$res1=($res==0)?1:$res+1;
				$result = (($value - $rating)/$res1) + $rating;

				$res++;
				$this->db->ExecuteSql ("Update `products` Set rating='$result', respondents='$res' Where product_id='$id'");
			}
		}
	}

}