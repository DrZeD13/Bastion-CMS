<?php
/*
структура таблицы
actions_id	Идентификатор 
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
class Model_Actions extends Model 
{

	private $table_name = '`actions`';
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.ACTIONS_LINK."/";
		if (!$this->Valid_Url_Short(ACTIONS_LINK)) 		
		{
			$this->error404();
		}
		
		// запрос для получения шапки
		$sql="SELECT Count(*) FROM `menus` WHERE url = '".ACTIONS_LINK."/'";
		if ($this->db->GetOne ($sql, 0) > 0) 
		{
			$sql = "SELECT title, head_title, description, keywords FROM `menus` WHERE url = '".ACTIONS_LINK."/'";
			$data = $this->Get_Header($sql);
		}
		else
		{
			$data['head_title'] = $data['title'] = "Доска объявлений";
			$data['keywords'] = "";
			$data['description'] = "";
		}
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		
		// запрос получения списка статей
		$date=time();
		$fromwhere = "FROM ".$this->table_name." WHERE is_active='1' AND news_date < $date ORDER BY news_date desc";
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{
			$sql="SELECT title, short_text, url ".$fromwhere;
			$result=$this->db->ExecuteSql($sql);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$url = $row['url'];
				$title = $this->dec($row['title']);
				$short_text = $this->dec($row['short_text']);
				$link = $mainlink.$url;
				$data ["row"][] = array (
					"link" => $link,
					"title" => $title,
					"short_descr" => $short_text,
				);
				/*$genre = $row['genre'];
				if ($row['filename'] != "") {
					$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;
					$filename = $this->siteUrl."datas/articles/".$filename;
					$datas=array ("FILENAME" => $filename, "TITLE_IMG" => $title,);
					$this->data['TABLE_ROW']["MAIN_FILENAME"] = $datas;
				}       
				$row1 = $this->db->GetEntry ("Select url, name From category Where id = '$genre'");
				$link_cat = $this->siteUrl.ARTICLES_LINK.$row1['url'];
			*/								
			}
			$this->db->FreeResult ($result);
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		return $data;
	}
	
	public function get_view() 
	{
		if (!$this->Valid_Url(ACTIONS_LINK)) 		
		{
			$this->error404();
		}
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT title, head_title, description, keywords, text FROM ".$this->table_name." WHERE url = '".$url."' AND is_active='1'";
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		$data = array(
			"title" => $row["title"], 
			"descr"=> $this->dec($row["text"]),
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],
		);
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).$row["title"];
		return $data;		
	}

}
