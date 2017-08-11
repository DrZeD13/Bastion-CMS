<?php
/*
структура таблицы
testimonial_id	 Идентификатор
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
class Model_Adm_Testimonials extends Model 
{

	var $table_name = 'testimonials';
	var $primary_key = 'testimonial_id';
	var $orderType = array ("testimonial_id"=>"", "name" =>"", "news_date"=>"", "is_active" => "");
	var $orderDefault = "news_date";
	// путь к папке с картинками
	var $path_img = "/media/testimonials/";
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
			't_title' => $this->Header_GetSortLink($mainlink, "name", "Имя"),
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
			$sql="SELECT {$this->primary_key}, news_date, name, is_active, new ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				
				$data ["row"][] = array (
					"id" => $id,
					"name" => $this->dec($row['name']),
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
		$this->delete_image($this->primary_key);
		$id = $this->GetGP ("id");
		$sql = "SELECT name FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
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
			"name" => $this->dec($row["name"]),
			"name_error" => $this->GetError("name"),
			"company" => $this->dec($row["company"]),
			"position" => $this->dec($row["position"]),			
			"news_date" => $row["news_date"],
			"email" => $this->dec($row["email"]),
			"comment" => $this->dec($row["comment"]),
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",
		);
		
		$filename = $row["filename"];
		if ($filename != "")
		{
			$extension = substr($filename, -3);
			$filename = substr($filename, 0, -4)."_small.".$extension;
			$filename = $this->path_img.$filename;
			$link = "/adm/".$this->table_name."/del_img?id=".$id;
			$data["filename"] = array ("img" => $filename, "link" =>$link);
		}
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();
		$data["news_date"] = time();
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
		$data["name_error"] = "";
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
			$xsize = $this->db->GetSetting("XSizeTestimonials", 200);
            $ysize = $this->db->GetSetting("YSizeTestimonials", 200);
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

			$xsize = $this->db->GetSetting("XSizeTestimonials", 200);
            $ysize = $this->db->GetSetting("YSizeTestimonials", 200);
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
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);
		$data["table_name"] = $this->table_name;
		$data["name_error"] = $this->GetError("name");
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT filename, update_date FROM ".$this->table_name." WHERE {$this->primary_key}='$id'");	   
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			
			$data["update"] = array (
				"update_date" => date ("h:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$_SESSION["A_ID"]."'"),	  
			);				
			$filename = $row["filename"];
			if ($filename != "")
			{
				$extension = substr($filename, -3);
				$filename = substr($filename, 0, -4)."_small.".$extension;
				$filename = $this->path_img.$filename;
				$link = "/adm/".$this->table_name."/del_img?id=".$id;
				$data["filename"] = array ("img" => $filename, "link" =>$link);
			}	
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";					
		}
		return $data;
	}
	
	function Form_Valid($edit = false)
	{
		$data["name"] = $this->enc($this->GetValidGP ("name", "Имя", VALIDATE_NOT_EMPTY));		
		$data["company"] = $this->enc($this->GetGP ("company", ""));	
		$data["position"] = $this->enc($this->GetGP ("position", ""));	
		$data["email"] = $this->enc($this->GetGP ("email", ""));	
		$data["comment"] = $this->enc($this->GetGP ("comment"));		
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));	
		
		return $data;
	}
	
	function Delete_img ()
    {
        $id = $this->GetGP ("id");
		$this->history("Удаление img", $this->table_name, "", $id);
        $this->delete_image($this->primary_key);
        $this->Redirect ($this->siteUrl."adm/".$this->table_name."/edit?id=$id");
    }
	
}