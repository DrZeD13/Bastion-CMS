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
class Model_Adm_Qanswers extends Model 
{

	var $table_name = 'qanswers';
	var $primary_key = 'qanswer_id';
	var $orderType = array ("qanswer_id"=>"", "title" =>"", "news_date"=>"", "is_active" => "");
	var $orderDefault = "news_date";
	var $orderDirDefault = "desc";
	var $rowsPerPage = 20;
	
	public function get_data() 
	{					
		$mainlink = "/adm/".$this->table_name."/?";
		$search = $this->GetGP ("search", "");
		// поиск по статьям
		if ($search == "") 
		{
			$fromwhere = "FROM ".$this->table_name." WHERE 1 ORDER BY ".$this->orderBy." ".$this->orderDir;			
		}
		else
		{
			// предусмотрен поиск по любому полю
			$mainlink .= "search=".$search."&";
			$type = $this->GetGP ("type", "");
			if ($type == "")
			{ 
				$type = "title";
			}
			else
			{
				$mainlink .= "type=".$type."&";
			}
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE  '%$search%' ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		
		// запрос для получения шапки таблицы
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'search' => $search,
			'table_name' => $this->table_name,
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "title", "Заголовок"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
			'link' => "Ссылка",
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT {$this->primary_key}, news_date, title, url, is_active, new ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				
				$data ["row"][] = array (
					"id" => $id,
					"linktitle" => $row['url'],
					"link" => "/".QANSWERS_LINK."/".$row['url'],
					"title" => $this->dec($row['title']),
					"date" => date("d-m-Y", $this->dec($row['news_date'])),
					"new" => ($row['new']=="0")?"":"<span class='t_new'>new</span>",
					"active" => "/adm/".$this->table_name."/activate?id=".$id,
					"active_img" => ($row['is_active'] == 0)?"times":"check",
                    "edit" => "/adm/".$this->table_name."/edit?id=".$id,
                    "del" => "/adm/".$this->table_name."/del?id=".$id,
					"status" => ($row['is_active'] == 1)?"success":"danger",
				);						
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks($total, $mainlink);
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		return $data;
	}
	
	function GetActivate()
	{
		$id = $this->GetGP ("id");
		$this->history("Изменение статуса", $this->table_name, "", $id);
		$this->Activate($this->primary_key);
	}
	
	function Delete()
	{			
		$id = $this->GetGP ("id");
		$sql = "SELECT title FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		$this->FlagNewFalse($id);
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE {$this->primary_key}='$id'");	   
		
		$data = array (
			"title" => $this->dec($row["title"]),
			"title_error" => $this->GetError("title"),
			"head_title" => $this->dec($row["head_title"]),
			"url" => $this->dec($row["url"]),
			"url_error" => $this->GetError("url"),
			"news_date" => $row["news_date"],
			"keywords" => $this->dec($row['keywords']),
			"description" => $this->dec($row["description"]),			
			"first_name" => $this->dec($row["first_name"]),
			"email" => $this->dec($row["email"]),
			"short_text" => $this->dec($row["short_text"]),
			"text" => $this->dec($row["text"]),
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",
			"editor" => $this->editor(),
		);
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();
		$data["news_date"] = time();
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
		$data["title_error"] = "";
		$data["url_error"] = "";
		$data["action"] = "insert";
		$data["editor"] = $this->editor();

		return $data;
	}
	
	function Insert()
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);		
		if ($this->errors['err_count'] > 0) 
		{
			return false;
		}
		else
		{
			$data["is_active"] = '0';
			
			$sql = "Insert Into ".$this->table_name." ".ArrayInInsertSQL ($data);
			$this->db->ExecuteSql($sql);
			$id = $this->db->GetInsertID ();
			$this->history("Добавление", $this->table_name, "", $id);
			return true;
		}
	}
	
	function Update()
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);
		
		if ($this->errors['err_count'] > 0) 
		{
			return false;
		}
		else
		{					
			$sql = "UPDATE ".$this->table_name." SET ".ArrayInUpdateSQL ($data)." WHERE {$this->primary_key}='$id'";
			
			$this->db->ExecuteSql($sql);		
			$this->history("Изменение", $this->table_name, "", $id);
			return true;
		}
	}
	
	function Edit_error($edit = "update")
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);
		$data["table_name"] = $this->table_name;
		$data["title_error"] = $this->GetError("title");
		$data["url_error"] = $this->GetError("url");	
		if ($edit == "update") 
		{
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";					
		}
		$data["editor"] = $this->editor();
		return $data;
	}
	
	function Form_Valid($edit = false)
	{
		$data["title"] = $this->enc($this->GetValidGP ("title", "Название", VALIDATE_NOT_EMPTY));		
		$data["head_title"] = $this->enc($this->GetGP ("head_title", ""));
		if ($data["head_title"] == "") {$data["head_title"] = $data["title"];}
		$url = $this->GetGP ("url", "");		
		$data["url"] = validUrl($url, $data["title"]);
		if ($data["url"])
		{
			$id = $this->GetID("id");
			$where = ($edit)?" and {$this->primary_key} <> '$id'":"";				
			$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." Where url='".$data["url"]."' $where", 0);		
			if ($total > 0) {
				$this->SetError("url", "Такой ЧПУ уже есть");
			}
		}
		else
		{
			$data["url"] = $url;
			$this->SetError("url", "допустимо только буквы, цифры и -");
		}
			
        $data["keywords"] = $this->enc($this->GetGP("keywords"));
		$data["description"] = $this->enc($this->GetGP("description"));
		$data["first_name"] = $this->enc($this->GetGP ("first_name", ""));
		$data["email"] = $this->enc($this->GetGP ("email", ""));	
		$data["short_text"] = $this->enc($this->GetGP ("short_text"));
		$data["text"] = $this->enc($this->GetGP ("text"));		
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));
		
		return $data;
	}
	
}