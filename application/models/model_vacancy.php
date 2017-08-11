<?php

class Model_Vacancy extends Model 
{

	private $table_name = '`vacancy`';
	
	public function get_data() 
	{
		$data = array("name" =>"Vacancy", "descr"=> "Описание");
		return $data;
	}

}
