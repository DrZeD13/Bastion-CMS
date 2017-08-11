<?php
/*
структура таблицы
qanswer_id	 Идентификатор
news_date	Дата создания
title Название
head_title Название <title>
url  Адрес блога
description	Описание блога
keywords Ключевые слова
first_name Имя создателя
email E-mail адрес
short_text кртакое описание
text подробное описание
is_active	Флаг активности
new Флаг нового сообщения
*/
class Model_Qanswers extends Model 
{

	private $table_name = '`qanswers`';
	var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (10, 20, 50); //количество записей на страницу
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.QANSWERS_LINK."/";
		if (!$this->Valid_Url_Short(QANSWERS_LINK)) 		
		{
			$this->error404();
		}
		
		// запрос для получения шапки
		$sql = "SELECT title, head_title, description, keywords FROM `menus` WHERE url = '".QANSWERS_LINK."/' and is_active='1'";
		$data = $this->Get_Header($sql) + $this->get_form();
		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		// запрос получения списка статей
		$fromwhere = "FROM ".$this->table_name." WHERE is_active='1' AND news_date < ".time()." ORDER BY news_date desc";
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{
			$sql="SELECT title, short_text, news_date, url ".$fromwhere;
			$result=$this->db->ExecuteSql($sql);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$url = $row['url'];
				$title = $this->dec($row['title']);
				$short_text = $this->dec($row['short_text']);
				$link = $mainlink.$url;
				$data ["article_row"][] = array (
					"link" => $link,
					"title" => $title,
					"news_date" => date("d-m-Y", $row['news_date']),
					"short_text" => $short_text,
				);							
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}
		else
		{
			$data['empty_row'] = "Еще ни кто не задавал вопрос";
		}
		
		return $data;
	}
	
	public function get_view() 
	{
		if (!$this->Valid_Url(QANSWERS_LINK)) 		
		{
			$this->error404();
		}
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $temp[count($temp)-1];	
		$sql = "SELECT title, head_title, description, keywords, text, news_date FROM ".$this->table_name." WHERE url = '".$url."' AND is_active='1'";
		$row = $this->db->GetEntry($sql, "/404");
		$data = array(
			"title" => $row["title"], 
			"news_date" => date("d-m-Y", $row['news_date']),
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],
			"text" =>$this->dec($row["text"]),
		);		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).$row["title"];
		return $data;		
	}
	
	function get_form()
	{
		$data = array (			
			"first_name" => $this->GetGP("first_name"),
			"error_first_name" => $this->GetError("first_name"),
			"email" => $this->GetGP("email"),
			"error_email" => $this->GetError("email"),
			"question" => $this->GetGP("question"),
			"error_question" => $this->GetError("question"),
			"error_captcha" => $this->GetError("captcha"),			
			"message"=> $this->GetError("message"),			
			"action" => "send",
		);
		return $data;
	}
	
	function send()
	{
		$fio = $this->GetValidGP ("first_name", "Ваше имя", VALIDATE_NOT_EMPTY);
        $email = $this->enc ($this->GetValidGP ("email", "Email адрес", VALIDATE_EMAIL));
        $question = $this->GetValidGP ("question", "Задайте вопрос", VALIDATE_NOT_EMPTY);
        /*@@@-- Begin: kcaptcha --@@@@@@@@@@@*/
        $code = $this->GetGP("keystring", "");
		$flag = $this->ChecCode($code);		
		if (!$flag) {$this->SetError("captcha", "Неверная последовательность");}	
      	/*@@@-- END: kcaptcha --@@@@@@@@@@@@*/        
        
        if ($this->errors['err_count'] > 0) 
		{
			return false;
        }
        else 
		{
			$sql = "INSERT INTO ".$this->table_name." (first_name, news_date, email, title, is_active) VALUES ('$fio', '".time()."', '$email', '$question', '0')";
			$this->db->ExecuteSql($sql);
			$this->SetError("message", "Ваш вопрос будут рассмотрен в кратчайшие сроки");
			return true;
        }
	}

}
