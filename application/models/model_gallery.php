<?php
/*
структура таблицы
gallery_id	Идентификатор 
news_date	Дата создания 
title Название
head_title Название <title>
url  Адрес
description	Описание 
keywords Ключевые
filename картинка
short_text кртакое
text подробное
is_active	Флаг активности.
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Gallery extends Model 
{

	private $table_name = '`gallery`';
	var $rowsPerPage = 24; //выводить на страницу по умолчанию
    var $rowsOptions = array (12, 24, 48); //количество записей на страницу
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.GALLERY_LINK."/";
		if (!$this->Valid_Url_Short(GALLERY_LINK)) 		
		{
			$this->error404();
		}
		
		// запрос для получения шапки
		$sql = "SELECT title, head_title, description, keywords FROM `menus` WHERE url = '".GALLERY_LINK."/' and is_active='1'";
		$data = $this->Get_Header($sql);
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		// запрос получения списка статей
		$fromwhere = "FROM ".$this->table_name." WHERE is_active='1' AND news_date < ".time()." ORDER BY order_index desc";
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);				
		if ($total > 0) 
		{
			$this->Get_Valid_Page($total);			
			$sql="SELECT title, short_text, news_date, url, filename ".$fromwhere;				
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{												
				$url = $row['url'];
				$title = $this->dec($row['title']);
				$short_text = $this->dec($row['short_text']);
				$link = $mainlink.$url;
				if ($row['filename'] != "") {
				$extension = substr($row['filename'], -3);
				$filename = substr($row['filename'], 0, -4)."_small.".$extension;
				$filename = $this->siteUrl."media/gallery/".$filename;
				} 
				else {
					$filename = "";
				}
				$data ["article_row"][] = array (
					"link" => $link,
					"title" => $title,
					"news_date" => date("d-m-Y", $row['news_date']),
					"short_descr" => $short_text,
					"filename" => $filename,
				);						
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
		if (!$this->Valid_Url(GALLERY_LINK)) 		
		{
			$this->error404();
		}
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT * FROM ".$this->table_name." WHERE url = '".$url."' AND is_active='1'";
		$row = $this->db->GetEntry($sql);	
		if (!$row)
		{
			$this->error404();
		}
		$mainlink = $this->siteUrl.GALLERY_LINK."/".$row["url"];
		$total = $this->db->GetOne("SELECT Count(*) FROM photos WHERE parent_id = '".$row["gallery_id"]."' AND is_active='1'", 0);
		if ($total > 0)
		{
			$this->Get_Valid_Page($total);	
			$sql = "SELECT title, short_text, news_date, filename FROM photos WHERE parent_id = '".$row["gallery_id"]."' AND is_active='1'";
			$result = $this->db->ExecuteSql($sql, $this->Pages_GetLimits());	
			while ($row1 = $this->db->FetchArray ($result))	
			{
				if ($row1['filename'] != "") {
				$extension = substr($row1['filename'], -3);
				$filename = substr($row1['filename'], 0, -4)."_small.".$extension;
				$filename = $this->siteUrl."media/photos/".$filename;
				} 
				else {
					$filename = "";
				}
				
				$data["photos_row"][] = array(
					"title" => $row1["title"], 
					"news_date" => date("d-m-Y", $row1['news_date']),
					"descr"=> $this->dec($row1["short_text"]),
					"filename" => $filename,
					"filenamebig" => $this->siteUrl."media/photos/".$row1['filename'],
				);		
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		$data["title"] = $row["title"];						
		$data["head_title"] = $row["head_title"];
		$data["description"] = $row["description"];
		$data["keywords"] = $row["keywords"];
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).$row["title"];
		return $data;		
	}

}
