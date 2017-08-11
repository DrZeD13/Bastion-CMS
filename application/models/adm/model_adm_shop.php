<?php
/*
структура таблицы
shop_id	Идентификатор меню
parent_id	Идентификатор родителя
news_date	 Дата создания
title Название
head_title Название <title>
url  Адрес
description	Описание
keywords Ключевые слова
filename изображение
category категории
short_text краткое описание
text подробное описание
is_active	Флаг активности
order_index	порядок
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_Shop extends Model 
{

	var $table_name = 'shop';
	var $primary_key = 'shop_id';
	var $orderType = array ("shop_id"=>"", "name" =>"", "news_date"=>"", "order_index" => "", "is_active" => "", "tags" => "");
	var $orderDefault = "news_date";
	var $orderDirDefault = "desc";
	var $path_img = "/media/shop/";
	var $rowsPerPage = 15;
	function __construct()
	{
		parent::__construct();
		$this->menuarr = $this->get_array_catalog(false, "shops", "shop_id");
		$this->menuarrtree = GetTreeFromArray($this->menuarr);
	}
	public function get_data() 
	{							
		$parent_id = $this->GetGP("parent_id", -1);
        if ($parent_id == -1)
        {
            $parent_id = $this->GetSession("PARENTSHOPID", 0);
        }
        else {
            $_SESSION['PARENTSHOPID'] = $parent_id;
        }
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?parent_id=".$parent_id."&";
		$field = $this->GetGP_SQL ("field", "name");
		// поиск осуществляем в нутри под каталога если такой выбран
		$temp = ($parent_id == 0)?"1":"parent_id = '$parent_id'";
		// поиск по статьям
		if ($search == "") 
		{
			
			$fromwhere = "FROM ".$this->table_name." WHERE ".$temp." ORDER BY ".$this->orderBy." ".$this->orderDir;		
			// для сортировки по тегам тотал не работает, т. к. этого поля нет в таблице
			$fromwheretotal = "FROM ".$this->table_name." WHERE ".$temp;	
		}
		else
		{
			// предусмотрен поиск по любому полю
			$mainlink .= "search=".$search."&field=".$field."&";
			$fromwhere = "FROM ".$this->table_name." WHERE $temp and $field LIKE '%$search%' ORDER BY ".$this->orderBy." ".$this->orderDir;
			$fromwheretotal = "FROM ".$this->table_name." WHERE $temp and $field LIKE '%$search%'";
		}
		// навигация
		$navigation = GetNav($this->menuarr, $parent_id, true, "&raquo;", true);
        $navigation = ($parent_id == 0) ?  "" : "<a href='".$this->siteUrl."adm/".$this->table_name."/?parent_id=0'>Каталог</a><span class='prow'> &raquo; </span>".$navigation;
		// запрос для получения шапки таблицы
		$title = $this->db->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'navigation' => $navigation,
			'field' => $this->GetSearch ($field),
			'search' => $search,
			'catalogtree' => getMenusSelectLink($this->siteUrl."adm/".$this->table_name."/", $this->menuarr, $parent_id, 0),
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "name", "Название"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
			'tags' => $this->Header_GetSortLink($mainlink, "tags", "Теги"),
			'link' => "Ссылка",
			'order_index' => $this->Header_GetSortLink($mainlink, "order_index"),
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwheretotal;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$maxIndex = $this->db->GetOne ("SELECT MAX(order_index) FROM ".$this->table_name." WHERE parent_id='$parent_id'", 0);
            $minIndex = $this->db->GetOne ("SELECT MIN(order_index) FROM ".$this->table_name." WHERE parent_id='$parent_id'", 0);
			$tags = "(SELECT Count(*) FROM tags_value, tags WHERE tags_value.item_id= shop.shop_id and tags.module='shop' and tags.tag_id = tags_value.tag_id) as tags, ";
			$sql="SELECT $tags".$this->primary_key.", parent_id, news_date, name, url, order_index, is_active ".$fromwhere;	
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$url = $row['url'];				
				$name = $this->dec($row['name']);
				$fullurl = GetLinkCat($this->menuarr, $row["parent_id"]);
				$link = $this->siteUrl.SHOP_LINK."/".$fullurl.$row['url'];			
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id;
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				if ($parent_id == 0) 
				{
					$orderLink = "";
				}
				else
				{
					$orderLink	= $this->OrderLink($row["order_index"], $minIndex, $maxIndex, $id);			
				}
				//$sql="SELECT Count(*) FROM tags_value WHERE item_id=$id";
				$data ["article_row"][] = array (
					"id" => $id,
					"linktitle" => $url,
					"link" => $link,
					"title" => $name,
					"date" => $news_date,
					"tags" => $row['tags'],//$this->db->GetOne($sql, 0),
					
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
		$this->delete_image($this->primary_key);
		$id = $this->GetGP ("id");
		$sql = "SELECT name FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$sql = "DELETE tags_value FROM tags_value, tags WHERE tags_value.item_id = '$id' and tags.module = 'shop' and tags.tag_id = tags_value.tag_id";
		$this->db->ExecuteSql($sql);
		$this->delElement($this->primary_key);
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		$sql= "Select tags.tag_id  From `tags`, `tags_value`  Where tags.tag_id = tags_value.tag_id and tags_value.item_id = '$id' and tags.module = '".$this->table_name."'  Order by order_index asc";
		$tag_array = array();
		$result = $this->db->ExecuteSql ($sql);
		if ($result)
		{			
			while ($row = $this->db->FetchArray ($result)) {
				$tag_array[$row['tag_id']] = "";
			}
			$this->db->FreeResult  ($result);
		}
		
		$sql= "Select additions.addition_id  From `additions`, `additions_value`  Where additions.addition_id = additions_value.addition_id and additions_value.item_id = '$id' and additions.module = '".$this->table_name."'  Order by order_index asc";
		$options_array = array();
		$result = $this->db->ExecuteSql ($sql);
		if ($result)
		{			
			while ($row = $this->db->FetchArray ($result)) {
				$options_array[$row['addition_id']] = "";
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
			"options" => $this->GetOptions($options_array, $this->table_name),			
			"recomend" => $row["recomend"],
			"short_text" => $this->dec($row["short_text"]),
			"price" => $row["price"],
			"text" => $this->dec($row["text"]),
			"main_title" => "Редактирование пункта",
			"action" => "update",
			"parents" => getMenusSelect($this->menuarr, $row['parent_id'], 0, "catalog_id"),
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
		$data["name_error"] = "";		
		$data["url_error"] = "";
		$data["action"] = "insert";				
		$data["parents"] = getMenusSelect ($this->menuarr, $this->GetSession("PARENTSHOPID", 0), 0, "catalog_id");
		$data["category"] = $this->getGenre(0, $this->table_name);
		$data["tags"] = $this->GetTags(array(), $this->table_name);
		$data["tags"] = $this->GetOptions(array(), $this->table_name);
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
			$xsize = $this->db->GetSetting("XSizeShopPhoto", 200);
            $ysize = $this->db->GetSetting("YSizeShopPhoto", 200);
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
			$i=0;
			while (isset($_POST['options'][$i]))
			{				
				$sql = "Insert Into additions_value (addition_id, item_id) VALUES  ('".$_POST['options'][$i]."', '$id')";
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
			
			$xsize = $this->db->GetSetting("XSizeShopPhoto", 200);
            $ysize = $this->db->GetSetting("YSizeShopPhoto", 200);
			$photo = $this->ResizeAndGetFilename ($id, $xsize, $ysize);
            if ($photo) {
                $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET filename='$photo' WHERE ".$this->primary_key."='$id'");
            }
			
			$sql = "DELETE tags_value FROM tags_value, tags WHERE tags_value.item_id = '$id' and tags.module = 'shop' and tags.tag_id = tags_value.tag_id";
			$this->db->ExecuteSql($sql);
			$i=0;
			while (isset($_POST['tags'][$i]))
			{				
				$sql = "Insert Into tags_value (tag_id, item_id) VALUES  ('".$_POST['tags'][$i]."', '$id')";
				$this->db->ExecuteSql($sql);
				$i++;
			}
			
			$sql = "DELETE additions_value FROM additions_value, additions WHERE additions_value.item_id = '$id' and additions.module = 'shop' and additions.addition_id = additions_value.addition_id";
			$this->db->ExecuteSql($sql);
			$i=0;
			while (isset($_POST['options'][$i]))
			{				
				$sql = "Insert Into additions_value (addition_id, item_id) VALUES  ('".$_POST['options'][$i]."', '$id')";
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
		$data["name_error"] = $this->GetError("name");
		$data["url_error"] = $this->GetError("url");
		$data["editor"] = $this->editor();
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			
			$data["parents"] = getMenusSelect ($this->menuarr, $this->dec($row['parent_id']), 0, "catalog_id");
			$data["update"] = array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			);				
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";				
			$data["parents"] = getMenusSelect ($this->menuarr, $this->GetSession("PARENTID", 0), 0);
		}
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
			$where = ($edit)?" and ".$this->primary_key." <> '$id'":"";				
			$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." Where url='".$data["url"]."' $where", 0);		
			if ($total > 0) {
				$this->SetError("url", "Такой ЧПУ уже есть");
			}
		}
		else
		{
			$data["url"] = $this->GetGP ("url", "");
			$this->SetError("url", "допустимо только буквы, цифры и -");
		}		
        $data["keywords"] = $this->enc($this->GetGP("keywords"));
		$data["description"] = $this->enc($this->GetGP("description"));
		$data["category"] = $this->GetGP("category", 0);
		$data["price"] = $this->GetGP("price", 0);
		$data["short_text"] = $this->enc($this->GetGP ("short_text"));
		$data["description"] = ($data["description"] == "")?$data["short_text"]:$data["description"];
		$data["text"] = $this->enc($this->GetGP ("text"));
		$data["news_date"] = mktime (date ("h", time()), date ("i", time()), date ("s", time()), $this->GetGP ("dateMonth", 0), $this->GetGP ("dateDay", 0), $this->GetGP ("dateYear", 0));
		$data["parent_id"] = $this->GetGP("catalog_id", 0);
		$data["recomend"] = $this->GetGP("recomend", 0);
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
	
	function Up()
	{
		$id = $this->GetGP ("id");
        $parent_id = $this->GetSession("PARENTSHOPID", 0);
        $curIndex = $this->db->GetOne ("SELECT order_index FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." FROM ".$this->table_name." WHERE order_index<$curIndex and parent_id='$parent_id' Order By order_index Desc Limit 1", 0);
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
        $parent_id = $this->GetSession("PARENTSHOPID", 0);
       
        $curIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." From ".$this->table_name." WHERE order_index>$curIndex  and parent_id='$parent_id' Order By order_index Asc Limit 1", 0);
        $nextIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$nextID'", 0);

        if ($nextID != 0)
        {
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$nextIndex' WHERE ".$this->primary_key."='$id'");
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$curIndex' WHERE ".$this->primary_key."='$nextID'");
			$this->history("Изменение позиции down", $this->table_name, "", $id);
        }

         //$this->Redirect ("/adm/".$this->table_name);
    }
	
	function GetSearch ($field)
	{
		$res = "<select name='field' class='select-search'>";	
		$selected = ($field == "name")?"selected":"";
		$res .= "<option value='name' $selected>Заголовок</option>";
		$selected = ($field == $this->primary_key)?"selected":"";
		$res .= "<option value='".$this->primary_key."' $selected>ID</option>";
		$selected = ($field == "recomend")?"selected":"";
		$res .= "<option value='recomend' $selected>Рекомендуем</option>";
		return $res."</select>";
	}
	
}