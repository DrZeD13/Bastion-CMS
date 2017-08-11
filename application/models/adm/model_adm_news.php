<?php
/*
структура таблицы
news_id	Идентификатор блога
news_date	Дата создания блога
title Название блога
head_title Название блога <title>
url  Адрес блога
description	Описание блога
keywords Ключевые слова
filename картинка
category категория блога
short_text кртакое описание
text подробное описание
is_active	Флаг активности блога.
author автор блога
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_News extends Model 
{

	// имя таблицы для модуля
	var $table_name = 'news';
	// название id-поля таблицы для модуля
	var $primary_key = 'news_id';
	// по каким полям можно осуществлять сортировку
	var $orderType = array ("news_id"=>"", "name" =>"", "news_date"=>"", "is_active" => "", "url" => "");
	// поле по которуому осуществляется сортировка по умолчанию
	var $orderDefault = "news_date";
	// путь к папке с картинками
	var $path_img = "/media/news/";
	// количество записей выводимых на страницу по умолчанию
	var $rowsPerPage = 20;
	// массив параметров базы данных
	// ключи - поля в базе данных
	// type - тип поля
	// required - обязательно к заполнению
	// error - нужно ли делать ошибку
	// name - название поля для ошибки
	var $bd_array = array (
		"news_date" => array (
			"type" => "date", 
			"required" => true,
		),
		"name" => array (
			"type" => "text", 
			"required" => true,
			"error" => true,
			"name" => "Название",
		),
		"title" => array (
			"type" => "text", 
			"required" => false,
		),
		"head_title" => array (
			"type" => "text", 
			"required" => false,
		),
		"url" => array (
			"type" => "url", 
			"required" => false,
			"error" => true,
		),
		"keywords" => array (
			"type" => "textarea", 
			"required" => false,
		),
		"short_text" => array (
			"type" => "textarea", 
			"required" => false,
		),
		"description" => array (
			"type" => "textarea", 
			"required" => false,
		),
		"filename" => array (
			"type" => "file", 
			"required" => false,
		),
		"category" => array (
			"type" => "getGenre", 
			"required" => false,
		),		
		"text" => array (
			"type" => "editor", 
			"required" => false,
		),
		"is_active" => array (
			"type" => "none", 
			"required" => false,
		),
		"author" => array (
			"type" => "none", 
			"required" => false,
		),
		"update_date" => array (
			"type" => "none", 
			"required" => false,
		),
		"update_user" => array (
			"type" => "none", 
			"required" => false,
		),

	);
	
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
				$type = "name";
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
			't_title' => $this->Header_GetSortLink($mainlink, "name", "Название"),
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
			$sql="SELECT {$this->primary_key}, news_date, name, url, is_active ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			$token = $this->GetSession("token", false);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				
				$data ["row"][] = array (
					"id" => $id,
					"linktitle" => $row['url'],
					"link" => "/".NEWS_LINK."/".$row['url'],
					"title" => $this->dec($row['name']),
					"date" => date("d-m-Y", $this->dec($row['news_date'])),
					
					"active" => "/adm/".$this->table_name."/activate?id=".$id."&token=".$token,
					"active_img" => ($row['is_active'] == 0)?"times":"check",
                    "edit" => "/adm/".$this->table_name."/edit?id=".$id,
                    "del" => "/adm/".$this->table_name."/del?id=".$id."&token=".$token,
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
		$this->GetToken();
		$id = $this->GetGP ("id");
		$this->history("Изменение статуса", $this->table_name, "", $id);
		$this->Activate($this->primary_key);
		$this->Redirect ("/adm/".$this->table_name);
	}
	
	function Delete()
	{	
		$this->GetToken();
		$this->delete_image($this->primary_key);
		$id = $this->GetGP ("id");
		$sql = "SELECT name FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
		$this->Redirect ("/adm/".$this->table_name);
	}
	
	function Edit()
	{
		return $this->Get_Edit();
	}
	
	function Add()
	{		
		return $this->Get_Add ();
	}
	
	function Insert()
	{
		$this->GetToken();
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);		
		if ($this->errors['err_count'] > 0) 
		{
			return false;
		}
		else
		{
			$data["is_active"] = '0';
			$data["author"] = $_SESSION['A_ID'];
			$data["update_date"] = time();
			$data["update_user"] = $_SESSION['A_ID'];	
			$sql = "Insert Into ".$this->table_name." ".ArrayInInsertSQL ($data);
			$this->db->ExecuteSql($sql);
			$id = $this->db->GetInsertID ();
			$xsize = $this->db->GetSetting("XSizeSmallNewsPhoto", 200);
            $ysize = $this->db->GetSetting("YSizeSmallNewsPhoto", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);		
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE {$this->primary_key}='$id'");
            }
			$this->history("Добавление", $this->table_name, "", $id);
			return true;
		}
	}
	
	function Update()
	{
		$this->GetToken();
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);
		
		if ($this->errors['err_count'] > 0) 
		{
			return false;
		}
		else
		{			
			$data["update_date"] = time();
			$data["update_user"] = $_SESSION['A_ID'];			
			$sql = "UPDATE ".$this->table_name." SET ".ArrayInUpdateSQL ($data)." WHERE {$this->primary_key}='$id'";
			
			$this->db->ExecuteSql($sql);			
			$xsize = $this->db->GetSetting("XSizeSmallNewsPhoto", 200);
            $ysize = $this->db->GetSetting("YSizeSmallNewsPhoto", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);		
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE {$this->primary_key}='$id'");
            }
			$this->history("Изменение", $this->table_name, "", $id);
			return true;
		}
	}
	
	function Edit_error($edit = "update")
	{
		return $this->Get_Edit_Error($edit);
	}
	
	function Form_Valid($edit = false)
	{				
		foreach ($this->bd_array as $key => $value)
		{
			switch ($value["type"])
			{
				case "text":
				case "textarea":
				case "editor":
					if ($value["required"])
					{
						$data[$key] = $this->enc($this->GetValidGP ($key, $value["name"], VALIDATE_NOT_EMPTY));
					}
					else
					{
						$data[$key] = $this->enc($this->GetGP ($key), "");
					}	
					// заголовок зополняем name
					if ($key == "title")
					{
						if ($data["title"] == "") {$data["title"] = $data["name"];}
					}
					// заголовок зополняем tite
					if ($key == "head_title")
					{
						if ($data["head_title"] == "") {$data["head_title"] = $data["title"];}
					}
					// description заполняем кратким описанием
					if ($key == "description")
					{
						// краткое описание может и отсутствовать
						if (($data["description"] == "") && (isset($data["short_text"]))) {$data["description"] = $data["short_text"];}
					}
				break;
				case "date":
					$data[$key] = strtotime ($this->GetGP("news_date", 0));
				break;
				case "url":
					$url = $this->GetGP ("url", "");		
					$data[$key] = validUrl($url, $data["name"]);
					if ($data[$key])
					{
						$id = $this->GetID("id");
						$where = ($edit)?" and {$this->primary_key} <> '$id'":"";		
						$i=0;
						$route = explode(".", $data[$key]);
						$url = $route[0];
						do {
							if ($i != 0)
							{								
								$data[$key] = $url.$i.".html";
							}
							$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." Where url='".$data[$key]."' $where", 0);		
							$i++;
						} while ($total > 0);
					}
					else
					{
						$data[$key] = $url;
						$this->SetError("url", "допустимо только буквы, цифры и -");
					}
				break;
				case "getGenre":
					$data[$key] = $this->GetGP($key, 0);
				break;								
			}
		}		
		return $data;
	}
	
	function Delete_img ()
    {
        $this->GetToken();
		$id = $this->GetGP ("id");	
		$this->history("Удаление img", $this->table_name, "", $id);
        $this->delete_image($this->primary_key);
        $this->Redirect ($this->siteUrl."adm/".$this->table_name."/edit?id=$id");
    }
	
	function Get_Add ()
	{
		$data = $this->Form_Valid();
		$data["main_title"] = "Добавление пункта";
		$data["action"] = "insert";
		$data["token"] = $this->GetSession("token", false);
		$data["table_name"] = $this->table_name;
		foreach ($this->bd_array as $key => $value)
		{
			switch ($value["type"])
			{
				case "editor":	
					$data["editor"] = $this->editor();
				break;
				case "date":
					$data[$key] = time();
				break;
				case "getGenre":
					$data[$key] = $this->getGenre(0, $this->table_name);
				break;								
			}
			if ((isset($value["error"])) && ($value["error"]))
			{
				$data[$key."_error"] = "";
			}
		}

		return $data;
	}
	
	function Get_Edit ()
	{
		$id = $this->GetID("id");
		
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE {$this->primary_key}='$id'");	 

		$data = array (			
			"main_title" => "Редактирование пункта",
			"action" => "update",
			'table_name' => $this->table_name,
			"token" => $this->GetSession("token", false),
		);
		if (isset($this->bd_array["update_date"]))
		{				
			$data["update"] = array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			);
		}
		
		foreach ($this->bd_array as $key => $value)
		{
			switch ($value["type"])
			{
				case "editor":	
					$data["editor"] = $this->editor();
				case "text":
				case "textarea":
				case "editor":
					$data[$key] = $this->dec($row[$key]);
				break;
				case "date":
					$data[$key] = $row[$key];
				break;
				case "url":
					$data[$key] = $row[$key];
				break;
				case "getGenre":
					$data[$key] = $this->getGenre($row['category'], $this->table_name);
				break;	
				case "file":
					$filename = $row["filename"];
					if ($filename != "")
					{
						$extension = substr($filename, -3);
						$filename = substr($filename, 0, -4)."_small.".$extension;
						$filename = $this->path_img.$filename;
						$link = "/adm/".$this->table_name."/del_img?id=".$id."&token=".$data["token"];
						$data[$key] = array ("img" => $filename, "link" =>$link);
					}
				break;				
				
			}
			if ((isset($value["error"])) && ($value["error"]))
			{
				$data[$key."_error"] = $this->GetError($key);
			}
		}		
		return $data;				
	}
	
	function Get_Edit_Error ($edit)
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);	
		$data["token"] = $this->GetSession("token", false);
		$data["table_name"] = $this->table_name;
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT filename, update_date, update_user FROM ".$this->table_name." WHERE {$this->primary_key}='$id'");	 
			foreach ($this->bd_array as $key => $value)
			{				
				switch ($value["type"])
				{
					case "editor":	
						$data["editor"] = $this->editor();
					break;				
					case "file":
						$filename = $row["filename"];
						if ($filename != "")
						{
							$extension = substr($filename, -3);
							$filename = substr($filename, 0, -4)."_small.".$extension;
							$filename = $this->path_img.$filename;
							$link = "/adm/".$this->table_name."/del_img?id=".$id."&token=".$data["token"];
							$data[$key] = array ("img" => $filename, "link" =>$link);
						}
					break;				
					
				}
				if ((isset($value["error"])) && ($value["error"]))
				{
					$data[$key."_error"] = $this->GetError($key);
				}
			}									  
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			if (isset($this->bd_array["update_date"]))
			{				
				$data["update"] = array (
					"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
					"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
				);
			}				
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";				
			foreach ($this->bd_array as $key => $value)
			{				
				switch ($value["type"])
				{
					case "editor":	
						$data["editor"] = $this->editor();
					break;												
				}
				if ((isset($value["error"])) && ($value["error"]))
				{
					$data[$key."_error"] = $this->GetError($key);
				}
			}			
		}			
		return $data;				
	}
}