<?php
/*
структура таблицы
news_id	Идентификатор 
news_date	Дата создания 
title Название
head_title Название <title>
url  Адрес
description	Описание 
keywords Ключевые
filename картинка
category категория
short_text кртакое
text подробное
is_active	Флаг активности.
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_News extends Model 
{

	private $table_name = '`news`';
	var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (10, 20, 50); //количество записей на страницу
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.NEWS_LINK."/";
		if (!$this->Valid_Url_Short(NEWS_LINK)) 		
		{
			$this->error404();
		}
		
		// запрос для получения шапки
		$sql = "SELECT title, head_title, description, keywords FROM `menus` WHERE url = '".NEWS_LINK."/' and is_active='1'";
		$data = $this->Get_Header($sql);
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		// запрос получения списка статей
		$fromwhere = "FROM ".$this->table_name." WHERE is_active='1' AND news_date < ".time()." ORDER BY news_date desc";
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{
			$sql="SELECT title, short_text, news_date, url, filename ".$fromwhere;
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$url = $row['url'];
				$title = $this->dec($row['title']);
				$short_text = $this->dec($row['short_text']);
				$link = $mainlink.$url;
				if ($row['filename'] != "") {
					$filename = $this->siteUrl."media/news/".$row['filename'];
				} 
				else {
					//$filename = "/img/nophoto.jpg";
					$filename = "";
				}
				$data ["article_row"][] = array (
					"link" => $link,
					"title" => $title,
					"news_date" => date("d-m-Y", $row['news_date']),
					"short_text" => $short_text,
					"filename" => $filename,
				);
				/*$genre = $row['genre'];				  
				$row1 = $this->db->GetEntry ("Select url, name From category Where id = '$genre'");
				$link_cat = $this->siteUrl.ARTICLES_LINK.$row1['url'];
			*/								
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		return $data;
	}
	
	public function get_view() 
	{
		if (!$this->Valid_Url(NEWS_LINK)) 		
		{
			$this->error404();
		}
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT title, head_title, description, keywords, text, news_date, filename FROM ".$this->table_name." WHERE url = '".$url."'";
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		$data = array(
			"title" => $row["title"], 
			"news_date" => date("d-m-Y", $row['news_date']),
			"descr"=> $this->dec($row["text"]),
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],
			"filename" => ($row["filename"] != "")?$this->siteUrl."media/news/".$row["filename"]:"",
		);		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).$row["title"];
		return $data;		
	}

}
