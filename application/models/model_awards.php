<?php
/*
структура таблицы
award_id	Идентификатор 
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
class Model_Awards extends Model 
{

	private $table_name = '`awards`';
	var $rowsPerPage = 24; //выводить на страницу по умолчанию
    var $rowsOptions = array (12, 24, 48); //количество записей на страницу
	
	public function get_data() 
	{		
		if (!$this->Valid_Url_Short(AWARDS_LINK)) 		
		{
			$this->error404();
		}
		
		
		$sql = "SELECT * FROM menus WHERE url = '".AWARDS_LINK."/"."' AND is_active='1'";
		
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		$mainlink = $this->siteUrl.AWARDS_LINK."/";
		$total = $this->db->GetOne("SELECT Count(*) FROM awards WHERE is_active='1'", 0);
		
		if ($total > 0)
		{
			$this->Get_Valid_Page($total);	
			$sql = "SELECT title, short_text, news_date, filename FROM awards WHERE is_active='1' ORDER BY order_index desc";
			$result = $this->db->ExecuteSql($sql, $this->Pages_GetLimits());	
			while ($row1 = $this->db->FetchArray ($result))	
			{
				if ($row1['filename'] != "") {
				$extension = substr($row1['filename'], -3);
				$filename = substr($row1['filename'], 0, -4)."_small.".$extension;
				$filename = $this->siteUrl."media/awards/".$filename;
				} 
				else {
					$filename = "";
				}
				
				$data["photos_row"][] = array(
					"title" => $row1["title"], 
					"news_date" => date("d-m-Y", $row1['news_date']),
					"descr"=> $this->dec($row1["short_text"]),
					"filename" => $filename,
					"filenamebig" => $this->siteUrl."media/awards/".$row1['filename'],
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
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		return $data;		
	}

}
