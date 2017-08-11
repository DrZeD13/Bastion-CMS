<?php

class Model_Sitemap extends Model 
{

	private $table_name = '`menus`';
	
	public function get_data() 
	{
		$data = array(
			"title" => "Карта сайта", 
			"news_date" => "",
			"descr"=> GetUlMenu($this->siteUrl, $this->menutree, 0, 10),
			"head_title" =>"Карта сайта",
			"description" =>"Карта сайта",
			"keywords" =>"Карта сайта",
		);
		$data["nav"] = MAIN_NAV."Карта сайта";
		return $data;
	}

}
