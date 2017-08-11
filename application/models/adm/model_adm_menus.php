<?php
/*
структура таблицы
menu_id	Идентификатор меню
parent_id	Идентификатор родителя
news_date	Дата создания
title Название
head_title Название <title>
url  Адрес
description	Описание
keywords Ключевые слова
text подробное описание
module название модуля
is_active	Флаг активности
order_index	порядок
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_Menus extends Model 
{

	var $table_name = 'menus';
	var $primary_key = 'menu_id';
	var $orderType = array ("menu_id"=>"", "title" =>"", "news_date"=>"", "order_index" => "", "is_active" => "");
	var $orderDefault = "order_index";
	var $orderDirDefault = "asc";
	var $rowsPerPage = 20;
	var $menuarr = array();
	var $menuarrtree = array();
	
	function __construct()	
	{	
		parent::__construct();
		$this->menuarr = $this->get_array_menu(false);
		// получаем дерево из массива меню
		$this->menuarrtree = GetTreeFromArray($this->menuarr);		
	}
	
	public function get_data() 
	{							
		$parent_id = $this->GetGP("parent_id", -1);
        if ($parent_id == -1)
        {
            $parent_id = $this->GetSession("PARENTID", 0);
        }
        else {
            $_SESSION['PARENTID'] = $parent_id;
        }
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?parent_id=".$parent_id."&";
		// поиск по статьям
		if ($search == "") 
		{
			$fromwhere = "FROM ".$this->table_name." WHERE parent_id = '$parent_id' ORDER BY ".$this->orderBy." ".$this->orderDir;			
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
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE '%$search%' ORDER BY ".$this->orderBy." ".$this->orderDir;
		}		
		// запрос для получения шапки таблицы
		/*include($_SERVER['DOCUMENT_ROOT'].'/application/core/admin_pages.php');
		if (isset($ADMIN_PAGES[$this->table_name]))
		{
			$title = $ADMIN_PAGES[$this->table_name]["icon"]." ".$ADMIN_PAGES[$this->table_name]["title"];
		}
		else
		{
			$title ="";
		}*/
		$title = $this->GetAdminTitle($this->table_name);
		
		// навигация
		$navigation = GetNav($this->menuarr, $parent_id, true, "/", true);		        
        $navigation = ($parent_id == 0) ?  "" : "<div class='breadcrumb'><a href='".$this->siteUrl."adm/menus/?parent_id=0'>".$title."</a> / ".$navigation."</div>";
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'navigation' => $navigation,
			'search' => $search,			
			'table_name' => $this->table_name,			
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "title", "Название"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
			'link' => "Ссылка",
			'order_index' => $this->Header_GetSortLink($mainlink, "order_index"),
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$maxIndex = $this->db->GetOne ("SELECT MAX(order_index) FROM ".$this->table_name." WHERE parent_id='$parent_id'", 0);
            $minIndex = $this->db->GetOne ("SELECT MIN(order_index) FROM ".$this->table_name." WHERE parent_id='$parent_id'", 0);
			$sql="SELECT ".$this->primary_key.", news_date, name, url, order_index, is_active ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			$token = $this->GetSession("token", false);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$url = $row['url'];				
				$title = $this->dec($row['name']);
				
				$sql = "SELECT Count(*) FROM ".$this->table_name." WHERE parent_id = '".$id."'";
				$child_total = $this->db->GetOne($sql, 0);
				$title.=" [".$child_total."]";
				if (substr_count($row["url"], ":") == 0)
				{
					$link = "/".$url;
				}	
				else
				{
					$link = $url;
				}
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id."&token=".$token;
				// если есть дети то не активным пункт меню сделать нельзя (зачем-то делал)
				//$activeImg = (($child_total > 0) && ($row['is_active']>0))?"":$activeImg = ($row['is_active'] == 0)?"times":"check";
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				
				$delLink = ($child_total > 0)?"":"/adm/".$this->table_name."/del?id=".$id."&token=".$token;		
				$orderLink	= $this->OrderLink($row["order_index"], $minIndex, $maxIndex, $id);
				
				$data ["article_row"][] = array (
					"id" => $id,
					"linktitle" => $url,
					"link" => $link,
					"title" => $title,
					"date" => $news_date,
					
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
		$this->GetToken();
		$id = $this->GetGP ("id");
		$this->history("Изменение статуса", $this->table_name, "", $id);
		$this->Activate($this->primary_key);		
		$this->Redirect ("/adm/".$this->table_name);
	}
	
	function Delete()
	{		
		$this->GetToken();
		$id = $this->GetGP ("id");
		$sql = "SELECT Count(*) FROM ".$this->table_name." WHERE parent_id = '".$id."'";
		$child_total = $this->db->GetOne($sql, 0);
		if ($child_total == 0)
		{
			$sql = "SELECT name FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
			$name = $this->db->GetOne($sql);
			$this->history("Удаление", $this->table_name, $name, $id);
			$this->delElement($this->primary_key);
		}
		else
		{
			$this->Redirect ("/adm/".$this->table_name);
		}
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
			"news_date" => $row["news_date"],
			"keywords" => $this->dec($row['keywords']),
			"description" => $this->dec($row["description"]),
			"text" => $this->dec($row["text"]),
			"is_menu" => $row["is_menu"],
			"target" => $row["target"],
			"tamplatemain" => $this->GetFileTamplateMain($row["tamplatemain"]),
			"tamplateview" => $this->GetFileTamplateView($row["tamplateview"]),
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",
			"parents" => getMenusSelect ($this->menuarrtree, $row['parent_id'], $row[$this->primary_key]),
			"editor" => $this->editor(),
			"token" => $this->GetSession("token", false),
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
		$data["url"] = "";
		$data["tamplatemain"] = $this->GetFileTamplateMain();
		$data["tamplateview"] = $this->GetFileTamplateView();
		$data["is_menu"] = 1;
		$data["action"] = "insert";	
		$data["token"] = $this->GetSession("token", false);
		$data["parents"] = getMenusSelect ($this->menuarrtree, $this->GetSession("PARENTID", 0), 0);
		$data["editor"] = $this->editor();
		return $data;
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
		$data["token"] = $this->GetSession("token", false);
		$data["table_name"] = $this->table_name;
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			$data["tamplatemain"] = $this->GetFileTamplateMain($row["tamplatemain"]);
			$data["tamplateview"] = $this->GetFileTamplateView($row["tamplateview"]);
			$data["parents"] = getMenusSelect ($this->menuarrtree, $row['parent_id'], $id);
			$data["update"] = array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row["update_user"]."'"),	  
			);				
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";		
			$data["tamplatemain"] = $this->GetFileTamplateMain($data["tamplatemain"]);
			$data["tamplateview"] = $this->GetFileTamplateView($data["tamplateview"]);
			$data["parents"] = getMenusSelect ($this->menuarrtree, $this->GetSession("PARENTID", 0), 0);
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
		$data["url"] = $this->GetGP("url", "");
		$id = $this->GetID("id");
		if (($data["url"] == "") || (substr_count($data["url"], "/") == 0)) 
		{
			$prefix = ".html";
			if ($data["url"] == "")
			{
				$temp = $data["title"];			
			}
			else
			{
				$route = explode(".", $data["url"]);
				$temp = $route[0];
			}
			$data["url"] = TransUrl($temp).$prefix;
		}
		/*else
		{
			$url = explode('.', $data["url"]);
			if ($url[0][strlen($url[0])-1] == "/")
			{
				$prefix = "/";
			}
			else
			{
				$prefix = ".html";
			}
			$data["url"] = TransUrl($url[0]);	
		}*/
		/*if (preg_match ("/^[a-z0-9-_]+$/", $data["url"]))
		{*/
			//$data["url"].=$prefix;			
			$where = ($edit)?" AND ".$this->primary_key." <> '$id'":"";				
			$total = $this->db->GetOne ("Select Count(*) From ".$this->table_name." WHERE url='".$data["url"]."' $where", 0);	
			if ($total > 0) {
				$this->SetError("url", "Такой ЧПУ уже есть");
			} 	
		/*}
		else
		{
			$this->SetError("url", "допустимо только буквы, цифры и -");
		}*/
        $data["keywords"] = $this->enc($this->GetGP("keywords"));
		$data["description"] = $this->enc($this->GetGP("description"));
		$data["is_menu"] = $this->GetGP("is_menu", 0);
		$data["target"] = $this->GetGP("target", 0);
		$data["tamplatemain"] = $this->GetGP("tamplatemain");
		$data["tamplateview"] = $this->GetGP("tamplateview");
		$data["text"] = $this->enc($this->GetGP ("text"));
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));
		$data["parent_id"] = $this->GetGP($this->primary_key, 0);
		
		return $data;
	}

	function Up()
	{
		$this->GetToken();
		$id = $this->GetGP ("id");
        $parent_id = $this->GetSession("PARENTID", 0);

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
        $this->GetToken();
		$id = $this->GetGP ("id");
        $parent_id = $this->GetSession("PARENTID", 0);
       
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
	
	function GetFileTamplateView ($file = "main_view.php")
	{
		return $this->GetFileTamplate($file, "tamplateview", "view");
	}
	
	function GetFileTamplateMain ($file = "template_view.php")
	{
		return $this->GetFileTamplate($file, "tamplatemain", "main");
	}
	
	function GetFileTamplate ($file, $name, $type)
	{
		$toRet = "<select data-placeholder='Выберите шаблон' name='$name' class='chosen-select'> \r\n";	
		$search = ($type == "main")?"/^template(.*?).php/":"/^main(.*?).php/";
		$dir = $_SERVER['DOCUMENT_ROOT']."/application/views/";   //задаём имя директории				
		if(is_dir($dir)) 
		{
			$files = scandir($dir);
			for($i=0; $i<sizeof($files); $i++) 
			{				
				if(preg_match ($search, $files[$i], $found)) 
				{
					$selected =  ($files[$i] == $file)?"selected":"";
					$toRet .="<option value='$files[$i]' $selected>$files[$i]</option> \r\n";
				}
			}
		}		
		$toRet .="</select> \r\n";
		return $toRet;
	}
}
