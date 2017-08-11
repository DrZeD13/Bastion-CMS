<?php

class Model_Reviews extends Model 
{

	private $table_name = '`reviews`';
	
	public function get_data() 
	{
		$data = array("name" =>"Reviews", "descr"=> "Описание");
		return $data;
	}

}
