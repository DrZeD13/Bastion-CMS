<?php
/*
структура таблицы
article_id	Идентификатор блога
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
rating рейтинг
respondents количество проголосовавших
is_active	Флаг активности блога.
is_comment	Флаг - разрешены ли комментарии в блоге.
author автор блога
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_Articles extends Model 
{

	var $table_name = 'articles';
	var $orderType = array ("article_id"=>"", "name" =>"", "news_date"=>"", "is_active" => "");
	var $primary_key = "article_id";
	var $orderDefault = "news_date";
	var $path_img = "/media/articles/";
	var $rowsPerPage = 20;
	
	public function get_data() 
	{			
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?";
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
		$row = $this->db->GetEntry ("SELECT parent_id, menu_id FROM menus WHERE url = '". ARTICLES_LINK."/"."'");
		if ($row)
		{
			$parent_id = $row["parent_id"]; 
			$current_id = $row["menu_id"]; 
			$child = $this->db->GetOne ("SELECT Count(*) FROM menus WHERE parent_id = '$current_id'", 0);
		}
		else
		{
			$parent_id = -1; 
			$current_id = 0; 
			$child = false;
		}
		// запрос для получения шапки таблицы
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'search' => $search,
			'table_name' => $this->table_name,
			'publish' => getMenusSelect ($this->menutree, $parent_id, $current_id, "menu_id", true, $child),
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
			$sql="SELECT ".$this->primary_key.", news_date, name, url, is_active ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$url = $row['url'];
				$title = $this->dec($row['name']);
				$link = "/".ARTICLES_LINK."/".$url;
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id;
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				
				$data ["article_row"][] = array (
					"id" => $id,
					"linktitle" => $url,
					"link" => $link,
					"title" => $title,
					"date" => $news_date,
					
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
		
		$sql= "Select tags.tag_id  From `tags`, `tags_value`  Where tags.tag_id = tags_value.tag_id and tags_value.item_id = '$id'  Order by order_index asc";
		$tag_array = array();
		$result = $this->db->ExecuteSql ($sql);
		if ($result)
		{			
			while ($row = $this->db->FetchArray ($result)) {
				$tag_array[$row['tag_id']] = "";
			}
			$this->db->FreeResult  ($result);
		}
		
		
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
		
		$data = array (
			"name" => $this->dec($row["name"]),
			"name_error" => $this->GetError("name"),
			"title" => $this->dec($row["title"]),			
			"head_title" => $this->dec($row["head_title"]),			
			"url" => $this->dec($row["url"]),
			"url_error" => $this->GetError("url"),
			"news_date" => $row["news_date"],
			"keywords" => $this->dec($row['keywords']),
			"description" => $this->dec($row["description"]),
			"category" => $this->getGenre($this->dec($row['category']), $this->table_name),
			"tags" => $this->GetTags($tag_array, $this->table_name),
			"short_text" => $this->dec($row["short_text"]),
			"text" => $this->dec($row["text"]),
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",
			"display" => "none",
			"editor" => $this->editor(),
			"update" => array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			),
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
		$data["url_error"] = "";
		$data["category"] = $this->getGenre(0, $this->table_name);
		$data["tags"] = $this->GetTags(array(), "articles");
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
			
			$sql = "Insert Into ".$this->table_name." ".ArrayInInsertSQL ($data);
			$this->db->ExecuteSql($sql);
			$id = $this->db->GetInsertID ();
			$xsize = $this->db->GetSetting("XSizeSmallArticlePhoto", 200);
            $ysize = $this->db->GetSetting("YSizeSmallArticlePhoto", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE ".$this->primary_key."='$id'");
            }
			
			$i=0;
			while (isset($_POST['tags'][$i]))
			{				
				$sql = "Insert Into tags_value (tag_id, item_id) VALUES  ('".$_POST['tags'][$i]."', '$id')";
				$this->db->ExecuteSql($sql);
				$i++;
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
			$data["update_date"] = time();
			$data["update_user"] = $_SESSION['A_ID'];			
			$sql = "UPDATE ".$this->table_name." SET ".ArrayInUpdateSQL ($data)." WHERE {$this->primary_key}='$id'";
			
			$this->db->ExecuteSql($sql);
			$xsize = $this->db->GetSetting("XSizeSmallArticlePhoto", 200);
            $ysize = $this->db->GetSetting("YSizeSmallArticlePhoto", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE ".$this->primary_key."='$id'");
            }
			
			$sql = "DELETE tags_value  FROM tags_value, tags WHERE item_id = '$id' and tags.tag_id = tags_value.tag_id and tags.module='articles'";
			$this->db->ExecuteSql($sql);
			$i=0;
			while (isset($_POST['tags'][$i]))
			{				
				$sql = "Insert Into tags_value (tag_id, item_id) VALUES  ('".$_POST['tags'][$i]."', '$id')";
				$this->db->ExecuteSql($sql);
				$i++;
			}
			
			$this->history("Изменение", $this->table_name, "", $id);
			return true;
		}
	}
	
	function Edit_error($edit = "update")
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid($id);
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
		$data["url"] = $this->GetGP ("url", "");		
		$data["url"] = validUrl($data["url"], $data["name"]);
		if ($data["url"])
		{
			$id = $this->GetID("id");
			$where = ($edit)?" and {$this->primary_key} <> '$id'":"";		
			$i=0;
			$route = explode(".", $data["url"]);
			$url = $route[0];
			do {
				if ($i != 0)
				{								
					$data["url"] = $url.$i.".html";
				}
				$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." Where url='".$data["url"]."' $where", 0);		
				$i++;
			} while ($total > 0);
		}
		else
		{
			$data["url"] = $this->GetGP ("url", "");
			$this->SetError("url", "допустимо только буквы, цифры и -");
		}
		$data["keywords"] = $this->enc($this->GetGP("keywords"));
		$data["description"] = $this->enc($this->GetGP("description"));
		$data["short_text"] = $this->enc($this->GetGP ("short_text"));
		if ($data["description"] == "") {$data["description"] = strip_tags($this->GetGP ("short_text"));}
		$data["text"] = $this->enc($this->GetGP ("text"));
		$data["category"] = $this->GetGP("category", 0);
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
