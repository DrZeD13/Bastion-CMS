<?php

class Model_Comments extends Model 
{

	private $table_name = '`comments`';
	
	public function get_data() 
	{
		$data = array("name" =>"Comments", "descr"=> "Описание");
		return $data;
	}

}
