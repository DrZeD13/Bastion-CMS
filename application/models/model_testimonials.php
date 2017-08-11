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
class Model_Testimonials extends Model 
{

	private $table_name = 'testimonials';
	var $rowsPerPage = 9; //выводить на страницу по умолчанию
    var $rowsOptions = array (9, 15, 21); //количество записей на страницу
	var $path_img = "/media/testimonials/";
	var $primary_key = 'testimonial_id';
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.TESTIMONIALS_LINK."/";
		if (!$this->Valid_Url_Short(TESTIMONIALS_LINK)) 		
		{
			$this->error404();
		}
		
		// запрос для получения шапки
		$sql = "SELECT title, head_title, description, keywords FROM `menus` WHERE url = '".TESTIMONIALS_LINK."/' and is_active='1'";
		$data = $this->Get_Header($sql) + $this->get_form();
		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		// запрос получения списка статей
		$fromwhere = "FROM ".$this->table_name." WHERE is_active='1' AND news_date < ".time()." ORDER BY news_date desc";
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{
			$sql="SELECT * ".$fromwhere;
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$name = $this->dec($row['name']);
				$title = $this->dec($row['title']);
				$company = $this->dec($row['company']);
				$position = $this->dec($row['position']);				
				$comment = mb_substr(strip_tags($this->dec($row['comment'])), 0, 150)."...";
				$link = $this->siteUrl.TESTIMONIALS_LINK."/".$row['url'];
				
				if ($row['filename'] != "") {
				$extension = substr($row['filename'], -3);
				$filename = substr($row['filename'], 0, -4)."_small.".$extension;
				$filename = $this->siteUrl."media/testimonials/".$filename;
				} 
				else {
					$filename = "/img/nophoto.png";
				}
				
				$data ["article_row"][] = array (
					"name" => $name,					
					"title" => $title,					
					"link" => $link,					
					"company" => $company,					
					"position" => $position,					
					"comment" => $comment,
					"filename" => $filename,
				);							
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}
		else
		{
			$data['empty_row'] = "Еще ни кто не оставлял отзыв";
		}
		
		return $data;
	}
	
	public function get_view() 
	{		
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT * FROM ".$this->table_name." WHERE url = '".$url."' AND is_active='1'";
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		else
		{
			if (!$this->Valid_Url(TESTIMONIALS_LINK)) 		
			{
				$this->error404();
			}
		}
		$data = array(
			"title" => $row["title"], 
			"news_date" => date("d-m-Y", $row['news_date']),
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],
			"other" => $row["other"],	
			"comment" =>$this->dec($row["comment"]),
			"filename" => ($row["filename"] != "")?$this->siteUrl."media/testimonials/".$row["filename"]:"/img/nophoto_big.png",
		);		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."<li><a href='#'>".$row["title"]."</a></li>";
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
			"company" => $this->GetGP("company"),
			"tel" => $this->GetGP("tel"),
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
        $question = $this->GetValidGP ("question", "Оставьте отзыв", VALIDATE_NOT_EMPTY);
		$company = $this->GetGP_SQL("company", "");
		$tel = $this->GetGP_SQL("tel", "");
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
			$sql = "INSERT INTO ".$this->table_name." (name, news_date, email, company, position, comment, is_active) VALUES ('$fio', '".time()."', '$email', '$company', '$tel', '$question', '0')";
			$this->db->ExecuteSql($sql);
			$id = $this->db->GetInsertID ();
			$xsize = $this->db->GetSetting("XSizeTestimonials", 200);
            $ysize = $this->db->GetSetting("YSizeTestimonials", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);		
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE testimonial_id='$id'");
            }
			$this->SetError("message", "Ваш отзыв отправлен на модерацию");
			
			$contactEmail = $this->db->GetSetting ("ContactEmail");
			$email_headers = "From: $email"."\r\n";
			$subject ="Новый отзыв"; 
           
			$message2 = "Добрый день,<br><br>$question<br><br>С уважением, $fio.<br><br> Собщение отправлено с сайта {$this->siteUrl}";
		   $this->SendMail ($contactEmail, $subject, $message2);
			
			return true;
        }
	}

}
