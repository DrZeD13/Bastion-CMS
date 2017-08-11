<?php
/*
структура таблицы
menu_id	Идентификатор меню
news_date	Дата создания
title Название
head_title Название <title>
url  Адрес
description	Описание
keywords Ключевые слова
text подробное описание
module название модуля
is_active	Флаг активности
order_index	порядок
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Main extends Model 
{

	private $table_name = '`menus`';
	// первый параметр тип поля второй проверка на ошибки 
	private $form = array (
		"fio" => array ("text", "1"), 
		"email" => array ("email", "1"), 
		"subject" => array ("text", "1"), 
		"mes_content" => array ("textarea", "1"), 
		"copy" => array ("checkbox", "0"), 
		"captcha" => array ("text", "1"), 		
		);
	
	public function get_data() 
	{
		$path="media/products/";
		$sql = "SELECT title, text FROM `pages` WHERE keyname = 'HomePage'";
		$row1 = $this->db->GetEntry($sql);
				
		//----рецепт дня--------------------------
		// подзапрос для получания количества комментариев для каждой записи
		$countcommet = "(Select count(*) From `comments` Where is_active='1' and module='products' and comments.parent_id = products.product_id) as totalcomments, ";
		$row = $this->db->GetEntry("Select ".$countcommet."title, filename, parent_id, views, url, short_text From `products` Where is_active='1' and recomend='1' ORDER BY news_date desc Limit 1", 0);		
		$recipedayname = $this->dec($row['title']);
		if ($row['filename'] != "") {          
			$extension = substr($row['filename'], -3);
            $recipedayimg = $this->siteUrl.$path.substr($row['filename'], 0, -4)."_small.".$extension;
		}
		else {
			$recipedayimg = $this->siteUrl."img/noimg.jpg";
		}	
		$recipedaydescr = $this->dec($row['short_text']);
		$fullurl = GetLinkCat($this->menuarrtree, $row["parent_id"]);
		$recipedaylink = $this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
		$data = array (            
			"title" => $this->dec($row1['title']),
			"text" => $this->dec($row1['text']),
			"head_title" =>$this->db->GetSetting ("SiteTitle"),
			"description" =>$this->db->GetSetting ("Description"),
			"keywords" =>$this->db->GetSetting ("Keywords"),	
			"recipeday_tile" => $recipedayname,
			"recipeday_img" => $recipedayimg,
			"recipeday_short_text" => $recipedaydescr,
			"recipeday_link" => $recipedaylink,
			"recipeday_views" => $row['views'],
			"recipeday_comments" => $row['totalcomments'],
        ); 
		
		//----последние добавления
		$result = $this->db->ExecuteSql("Select ".$countcommet."title, filename, parent_id, views, url From `products` Where is_active='1' ORDER BY news_date desc Limit 8");
		while ($row = $this->db->FetchArray ($result)) 
		{
			$recipelastname = $row['title'];
			if ($row['filename'] != "") {          
				$extension = substr($row['filename'], -3);
				$recipelastimg = $this->siteUrl.$path.substr($row['filename'], 0, -4)."_small.".$extension;
			}
			else {
				$recipelastimg = $this->siteUrl."img/noimg.jpg";
			}
			$fullurl = GetLinkCat($this->menuarrtree, $row["parent_id"]);
			$recipelastlink = $this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
			$data['recipelast'][] = array (
					"title" => $recipelastname,
					"filename" => $recipelastimg,
					"link" => $recipelastlink,
					"views" => $row['views'],
					"comments" => $row['totalcomments'],
			);		
		}
		$this->db->FreeResult ($result);
		//----популярные-------------------		
		$result = $this->db->ExecuteSql("Select ".$countcommet."title, filename, parent_id, views, url From `products` Where is_active='1' ORDER BY views desc Limit 8");	
		if ($result)
		{
			while ($row = $this->db->FetchArray ($result))  
			{
				$recipetopname = $row['title'];
				if ($row['filename'] != "") {          
					$extension = substr($row['filename'], -3);
					$recipetopimg = $this->siteUrl.$path.substr($row['filename'], 0, -4)."_small.".$extension;
				}
				else {
					$recipetopimg = $this->siteUrl."img/noimg.jpg";
				}
				$fullurl = GetLinkCat($this->menuarrtree, $row["parent_id"]);
				$recipetoplink = $this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
				$data['recipetop'][] = array (
					"title" => $recipetopname,
					"filename" => $recipetopimg,				
					"link" => $recipetoplink,
					"views" => $row['views'],
					"comments" => $row['totalcomments'],
				);		
			}
			$this->db->FreeResult ($result);
		}
		// запрос получения списка статей		
		$fromwhere = "FROM articles WHERE is_active='1' AND news_date < ".time()." ORDER BY news_date desc LIMIT 4";		
		$sql="SELECT title, short_text, news_date, url ".$fromwhere;	
		$result=$this->db->ExecuteSql($sql);
		while ($row = $this->db->FetchArray ($result))	
		{				
			$url = $row['url'];
			$title = $this->dec($row['title']);
			$short_text = $this->dec($row['short_text']);
			$link = $this->siteUrl.ARTICLES_LINK."/".$url;
			$data ["article_row"][] = array (
				"link" => $link,
				"title" => $title,
				"news_date" => date("d-m-Y", $row['news_date']),
				"short_descr" => $short_text,				
			);						
		}
		$this->db->FreeResult ($result);
		return $data;
	}
	
	public function get_view() 
	{
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT name, title, head_title, description, keywords, text, tamplatemain, tamplateview FROM ".$this->table_name." WHERE is_active='1' AND url = '".$url."'";
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		$data = array(
			"title" => $row['title'], 
			"text"=> $this->dec($row['text']),
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],	
			"tamplatemain" =>$row["tamplatemain"],	
			"tamplateview" =>$row["tamplateview"],	
		);	
		$nav = GetNav($this->menu, $this->cid);
		if ($nav)
		{
			$data["nav"] = MAIN_NAV.$nav;
		}
		else
		{
			$data["nav"] = MAIN_NAV.$row['name'];
		}
		if ($row['title'] == "Контакты") 
		{
			$data['form_send'] = $this->get_form(); 
		}				
		
		return $data;		
	}
	
	function get_form()
	{
		foreach ($this->form as $key => $value)
		{
			if ($value[0] == "checkbox") 
			{
				$data[$key] = ($this->GetGP($key, 0) == 1)?"checked":"";
			}
			else
			{
				if ($key == "captcha")
				{
					$data["error_".$key] = $this->GetError($key);
				}
				else
				{
					$data["form_".$key] = $this->GetGP($key);
					if ($value[1] == "1") $data["error_".$key] = $this->GetError($key);
				}
			}	
		}
		if (isset($_SESSION["MESSAGE"]))
		{
			$data["message"] = $_SESSION["MESSAGE"];
			unset($_SESSION["MESSAGE"]);
		}
		else
		{
			$data["message"] = "";
		}
		//$data["message"] = $this->GetError("message");
		$data ["action"] = "send";
		/*$data = array (			
			"form_fio" => $this->GetGP("fio"),
			"error_fio" => $this->GetError("fio"),
			"form_email" => $this->GetGP("email"),
			"error_email" => $this->GetError("email"),
			"form_subject" => $this->GetGP("subject"),
			"error_subject" => $this->GetError("subject"),
			"form_mes_content" => $this->GetGP("mes_content"),
			"error_mes_content" => $this->GetError("mes_content"),
			"copy" => ($this->GetGP("copy", 0) == 1)?"checked":"",
			"error_captcha" => $this->GetError("captcha"),			
			"message"=> $this->GetError("message"),			
			"action" => "send",
		);*/
		return $data;
	}
	
	function send()
	{
		$fio = $this->GetValidGP ("fio", "Ваше имя", VALIDATE_NOT_EMPTY);
        $subject = $this->GetValidGP ("subject", "Тема сообщения", VALIDATE_NOT_EMPTY);
        $email = $this->enc ($this->GetValidGP ("email", "Email адрес", VALIDATE_EMAIL));
        $mes_content = $this->GetValidGP ("mes_content", "Текст вашего сообщения", VALIDATE_NOT_EMPTY);
        /*@@@-- Begin: kcaptcha --@@@@@@@@@@@*/
        $code = $this->GetGP("keystring", "хрен");
		$flag = $this->ChecCode($code);		
		if (!$flag) {$this->SetError("captcha", "Неверная последовательность");}	
      	/*@@@-- END: kcaptcha --@@@@@@@@@@@@*/        
        
        if ($this->errors['err_count'] > 0) {
            return false;
        }
        else {
			$copy = $this->GetGP("copy", 0);
			$contactEmail = $this->db->GetSetting ("ContactEmail");
			$SiteName = $this->db->GetSetting ("SiteName");
			$message1 = "$mes_content<br><br>С уважением, Администрация ".$SiteName;
			$message2 = "$mes_content<br><br>С уважением, $fio.<br><br> Email: $email<br><br>Собщение отправлено с сайта {$this->siteUrl}<br><br>IP адрес: ".$_SERVER['REMOTE_ADDR'];	

			/*if ( !empty( $_FILES['filename']['tmp_name'] ) and $_FILES['filename']['error'] == 0 ) {
				$file_array[] = array(
					"filepath" => $_FILES['filename']['tmp_name'],
					"filename" => $_FILES['filename']['name'],
				);
			  } else {
				$file_array = array();
			  }*/

			
		   //$this->SendMail ($contactEmail, $subject, $message2, $file_array);
		   //$this->SendMail ($contactEmail, $subject, $message2);

           if ($copy == 1) {               
               //$this->SendMail ($email, $subject, $message1);
           }
		   
		   $data = array (
			"ip" => $_SERVER['REMOTE_ADDR'], 
			"name" => $fio,
			"email" => $email,
			"subject" => $subject,
			"news_date" => time(),
			"message" => $mes_content,
			"new" => 1,
			"module" => "Контакты",
		   );
		   
		   $sql = "Insert Into message ".ArrayInInsertSQL ($data);
		   $this->db->ExecuteSql($sql);
		   
		   //$this->SetError("message", "Ваше письмо успешно отправлено");
		   $_SESSION["MESSAGE"] = "Ваше письмо успешно отправлено";
           return true;
        }
	}
}