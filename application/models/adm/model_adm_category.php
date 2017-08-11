<?php
/*
структура таблицы
category_id	Идентификатор блога
news_date	Дата создания блога
title Название блога
head_title Название блога <title>
url  Адрес блога
description	Описание блога
keywords Ключевые слова
module раздел категории
short_text кртакое описание
text подробное описание
order_index сортировка
is_active	Флаг активности блога.
author автор блога
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_Category extends Model 
{
	
	// имя таблицы для модуля
	var $table_name = 'category';
	// название id-поля таблицы для модуля
	var $primary_key = 'category_id';
	// по каким полям можно осуществлять сортировку
	var $orderType = array ("category_id"=>"", "name" =>"", "news_date"=>"", "order_index" => "", "is_active" => "", "module" => "");
	// поле по которуому осуществляется сортировка по умолчанию
	var $orderDefault = "order_index";
	// путь к папке с картинками
	var $path_img = "/media/category/";
	// количество записей выводимых на страницу по умолчанию
	var $rowsPerPage = 20;
	var $orderDirDefault = "asc";
	
	
	public function get_data() 
	{			
		$getmodule = $this->GetGP("module", -1);
        if ($getmodule == -1)
        {
            $getmodule = $this->GetSession("CATMODULE", "all");
        }
        else {
            $_SESSION['CATMODULE'] = $getmodule;
        }
		if ($getmodule == "all")
		{
			$wheremodule = "1";
		}
		else
		{
			$wheremodule = "module = '".$getmodule."'";
		}
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?";
		// поиск по статьям
		if ($search == "") 
		{			
			$fromwhere = "FROM ".$this->table_name." WHERE $wheremodule ORDER BY ".$this->orderBy." ".$this->orderDir;	
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
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE  '%$search%' and $wheremodule ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		
		// запрос для получения шапки таблицы
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'search' => $search,
			'table_name' => $this->table_name,
			"getmodule" => $this->GetCategory($getmodule, true, $this->siteUrl."adm/category/"),
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "name", "Название"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
			'module' => $this->Header_GetSortLink($mainlink, "module", "Раздел"),
			'order_index' => $this->Header_GetSortLink($mainlink, "order_index"),
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$maxIndex = $this->db->GetOne ("SELECT MAX(order_index) FROM ".$this->table_name, 0);
            $minIndex = $this->db->GetOne ("SELECT MIN(order_index) FROM ".$this->table_name, 0);
			$sql="SELECT ".$this->primary_key.", news_date, name, module, order_index, is_active ".$fromwhere;										
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$module = $row['module'];
				$title = $this->dec($row['name']);				
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id;
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				
				$orderLink	= $this->OrderLink($row["order_index"], $minIndex, $maxIndex, $id);
				
				$data ["row"][] = array (
					"id" => $id,
					"title" => $title,
					"date" => $news_date,
					"module" => $module,
					
					"order_index" => $orderLink,
					"active" => $activeLink,
					"active_img" => $activeImg,
                    "edit" => $editLink,
                    "del" => $delLink,
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
		$sql = "SELECT name FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
		
		$data = array (
			"name" => $this->dec($row["name"]),
			"name_error" => $this->GetError("name"),
			"title" => $this->dec($row["title"]),			
			"head_title" => $this->dec($row["head_title"]),
			"url" => $this->dec($row["url"]),
			"url_error" => $this->GetError("url"),
			"module" => $this->GetCategory($this->dec($row["module"])),
			"news_date" => $row["news_date"],
			"keywords" => $this->dec($row['keywords']),
			"description" => $this->dec($row["description"]),			
			"short_text" => $this->dec($row["short_text"]),
			"text" => $this->dec($row["text"]),			
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"editor" => $this->editor(),
			"action" => "update",
			"update" => array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			),
		);
		
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();
		$data["news_date"] = time();
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
		$data["name_error"] = "";
		$data["url_error"] = "";
		$data["module"] = $this->GetCategory();
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
			$data["author"] = $_SESSION['A_ID'];
			$data["update_date"] = time();
			$data["update_user"] = $_SESSION['A_ID'];	
			$data["order_index"] = $this->db->GetOne ("SELECT MAX(order_index) FROM ".$this->table_name, 0)+1;	
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
			$data["update_date"] = time();
			$data["update_user"] = $_SESSION['A_ID'];			
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
		$data["module"] = $this->GetCategory($data["module"]);
		$data["name_error"] = $this->GetError("title");
		$data["url_error"] = $this->GetError("url");	
		$data["table_name"] = $this->table_name;
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			
			$data["update"] = array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			);				

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
		$data["name"] = $this->enc($this->GetValidGP ("name", "Название", VALIDATE_NOT_EMPTY));		
		$data["title"] = $this->enc($this->GetGP ("title", ""));
		if ($data["title"] == "") {$data["title"] = $data["name"];}			
		$data["head_title"] = $this->enc($this->GetGP ("head_title", ""));
		if ($data["head_title"] == "") {$data["head_title"] = $data["title"];}
		$data["url"] = $this->GetGP("url", "");
		$id = $this->GetID("id");
		if ($data["url"] == "")
		{
			$data["url"] = TransUrl($data["title"]);
		}
		else
		{
			$data["url"] = TransUrl($data["url"]);
		}
		if (preg_match ("/^[a-z0-9-_]+$/", $data["url"]))
		{
			$url = $data["url"];
			$data["url"].="/";
			$getmodule = $this->GetSession("CATMODULE", "all");
			if ($getmodule == "all")
			{
				$wheremodule = "and 1";
			}
			else
			{
				$wheremodule = "and module = '".$getmodule."'";
			}
			
			$where = ($edit)?" and ".$this->primary_key." <> '$id' $wheremodule":"";					
			$i=0;	
			do {
				if ($i != 0)
				{								
					$data["url"] = $url.$i."/";
				}
				$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." Where url='".$data["url"]."' $where", 0);		
				$i++;
			} while ($total > 0);	
		}
		else
		{
			$this->SetError("url", "допустимо только буквы, цифры и -");
		}
			
        $data["keywords"] = $this->enc($this->GetGP("keywords"));
		$data["description"] = $this->enc($this->GetGP("description"));
		$data["short_text"] = $this->enc($this->GetGP ("short_text"));
		if ($data["description"] == "") {$data["description"] = strip_tags($this->GetGP ("short_text"));}
		$data["text"] = $this->enc($this->GetGP ("text"));
		$data["module"] = $this->GetGP("module", "");
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));
		
		return $data;
	}
	
	function Up()
	{
		$id = $this->GetGP ("id");        
		
		$getmodule = $this->GetSession("CATMODULE", "all");
		if ($getmodule == "all")
		{
			$wheremodule = "and 1";
		}
		else
		{
			$wheremodule = "and module = '".$getmodule."'";
		}
		
        $curIndex = $this->db->GetOne ("SELECT order_index FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." FROM ".$this->table_name." WHERE order_index<$curIndex $wheremodule Order By order_index Desc Limit 1", 0);
        $nextIndex = $this->db->GetOne ("SELECT order_index FROM ".$this->table_name." WHERE ".$this->primary_key."='$nextID'", 0);

		if ($nextID != 0)
        {       
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$nextIndex' WHERE ".$this->primary_key."='$id'");
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$curIndex' WHERE ".$this->primary_key."='$nextID'");
			$this->history("Изменение позиции up", $this->table_name, "", $id);
        }

        //$this->Redirect ("/adm/".$this->table_name);
	}
	
	function Down ()
    {
        $id = $this->GetGP ("id");     
		
        $getmodule = $this->GetSession("CATMODULE", "all");
		if ($getmodule == "all")
		{
			$wheremodule = "and 1";
		}
		else
		{
			$wheremodule = "and module = '".$getmodule."'";
		}
       
        $curIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." From ".$this->table_name." WHERE order_index>$curIndex $wheremodule Order By order_index Asc Limit 1", 0);
        $nextIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$nextID'", 0);

        if ($nextID != 0)
        {
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$nextIndex' WHERE ".$this->primary_key."='$id'");
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$curIndex' WHERE ".$this->primary_key."='$nextID'");
			$this->history("Изменение позиции down", $this->table_name, "", $id);
        }

         //$this->Redirect ("/adm/".$this->table_name);
    }

}
