<?php
/*
структура таблицы
photo_id	Идентификатор меню
parent_id	Идентификатор родителя
news_date	 Дата создания
title Название
short_text краткое описание
is_active	Флаг активности
order_index	порядок
author автор
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Adm_Form_Item extends Model 
{

	var $table_name = 'form_item';
	var $primary_key = 'form_item_id';
	var $orderType = array ("form_item_id"=>"", "title" =>"", "news_date"=>"", "order_index" => "", "is_active" => "");
	var $orderDefault = "order_index";
	var $orderDirDefault = "asc";
	var $path_img = "/media/photos/";
	var $rowsPerPage = 15;
	
	public function get_data() 
	{							
		$parent_id = $this->GetGP("parent_id", -1);
        if ($parent_id == -1)
        {
            $parent_id = $this->GetSession("FORMSID", 0);
        }
        else {
            $_SESSION['FORMSID'] = $parent_id;
        }
		
		$mainlink = "/adm/".$this->table_name."/?parent_id=".$parent_id."&";
		$serch = $this->GetGP ("search", "");		
		// поиск по статьям
		if ($serch == "") 
		{			
			$fromwhere = "FROM ".$this->table_name." WHERE parent_id = '$parent_id' ORDER BY ".$this->orderBy." ".$this->orderDir;	
		}
		else
		{
			// предусмотрен поиск по любому полю
			$mainlink .= "search=".$serch."&";
			$type = $this->GetGP ("type", "");
			if ($type == "")
			{ 
				$type = "title";
			}
			else
			{
				$mainlink .= "type=".$type."&";
			}
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE '%$serch%' ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		// навигация
		//$navigation = GetNav($this->menuarr, $parent_id, true, "&raquo;", true);
		$title = $this->db->GetAdminTitle($this->table_name);
        $navigation = "<a href='".$this->siteUrl."adm/forms/'>Формы</a><span class='prow'> &raquo; </span>".$title;
		// запрос для получения шапки таблицы		
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'navigation' => $navigation,
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "title", "Заголовок"),
			'view' => "Вид",
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
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
			$sql="SELECT ".$this->primary_key.", news_date, title, order_index, is_active, type, name, value, class, label, placeholder, required, def ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];			
				$title = $this->dec($row['title']);								
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id;				
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				$orderLink	= $this->OrderLink($row["order_index"], $minIndex, $maxIndex, $id);				
				
				$data ["article_row"][] = array (
					"id" => $id,
					"title" => $title,
					"view" => $this->GetForm($row["type"], $row["name"], $row["value"], $row["class"], $row["placeholder"], $row["label"], $row["required"], $row["def"]),
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
		$id = $this->GetGP ("id");
		$this->history("Изменение статуса", $this->table_name, "", $id);
		$this->Activate($this->primary_key);
	}
	
	function Delete()
	{
		$this->delete_image($this->primary_key);
		$id = $this->GetGP ("id");
		$sql = "SELECT title FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
		$required = ($row["required"] == 0)?"":"checked";
		$data = array (
			"title" => $this->dec($row["title"]),
			"title_error" => $this->GetError("title"),
			"news_date" => $this->getDaySelect (date ("d", $row["news_date"]), "dateDay") . $this->getMonthSelect (date ("m", $row["news_date"]), "dateMonth") . $this->getYearSelect (date ("Y", $row["news_date"]), "dateYear"),
			"name" => $this->dec($row["name"]),
			"type" => $this->GetTypeForms(dec($row["type"])),
			"value" => $this->dec($row["value"]),
			"def" => $this->dec($row["def"]),
			"placeholder" => $this->dec($row["placeholder"]),
			"label" => $this->dec($row["label"]),
			"class" => $this->dec($row["class"]),
			"required" => "<input type='checkbox' name='required' $required value='1' />",
			"main_title" => "Редактирование пункта",
			"action" => "update",
			"editor" => $this->editor(),
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
		$data["news_date"] = $this->getDaySelect("", "dateDay").$this->getMonthSelect("", "dateMonth").$this->getYearSelect("", "dateYear");
		$data["main_title"] = "Добавление пункта";
		$data["title_error"] = "";
		$data["head_title_error"] = "";
		$data["url_error"] = "";
		$data["action"] = "insert";		
		$data["required"] = "<input type='checkbox' name='required' value='1' />";
		$data["type"] = $this->GetTypeForms("");
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
		$data["title_error"] = $this->GetError("title");
		$data["news_date"] = $this->getDaySelect($this->GetGP ("dateDay"), "dateDay").$this->getMonthSelect($this->GetGP ("dateMonth"), "dateMonth").$this->getYearSelect($this->GetGP ("dateYear"), "dateYear");
		$required = ($data["required"] == 0)?"":"checked";
		$data["required"] = "<input type='checkbox' name='required' $required value='1' />";
		$data["editor"] = $this->editor();
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";			
			$data["update"] = array (
				"update_date" => date ("H:i:s d-m-Y", $row["update_date"]),
				"update_user" =>$this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$_SESSION["A_ID"]."'"),	  
			);				
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";				
			$data["parents"] = getMenusSelect ($this->menuarrtree, $this->GetSession("PARENTID", 0), 0);
		}
		return $data;
	}
	
	function Form_Valid($edit = false)
	{
		$data["title"] = $this->enc($this->GetValidGP ("title", "Название", VALIDATE_NOT_EMPTY));	        
		$data["name"] = $this->enc($this->GetGP ("name"));
		$data["type"] = $this->enc($this->GetGP ("type"));
		$data["value"] = $this->enc($this->GetGP ("value"));
		$data["def"] = $this->enc($this->GetGP ("def"));
		$data["placeholder"] = $this->enc($this->GetGP ("placeholder"));
		$data["label"] = $this->enc($this->GetGP ("label"));
		$data["class"] = $this->enc($this->GetGP ("class"));
		$data["required"] = $this->enc($this->GetGP ("required"), 0);
		
		$data["news_date"] = mktime (date ("h", time()), date ("i", time()), date ("s", time()), $this->GetGP ("dateMonth", 0), $this->GetGP ("dateDay", 0), $this->GetGP ("dateYear", 0));
		$data["parent_id"] = $this->GetSession("FORMSID", 0);
		
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
        $parent_id = $this->GetSession("PARENTCATID", 0);
        $curIndex = $this->db->GetOne ("SELECT order_index FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." FROM ".$this->table_name." WHERE order_index<$curIndex Order By order_index Desc Limit 1", 0);
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
        $parent_id = $this->GetSession("PARENTCATID", 0);
       
        $curIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$id'", 0);
        $nextID = $this->db->GetOne ("SELECT ".$this->primary_key." From ".$this->table_name." WHERE order_index>$curIndex Order By order_index Asc Limit 1", 0);
        $nextIndex = $this->db->GetOne ("SELECT order_index From ".$this->table_name." WHERE ".$this->primary_key."='$nextID'", 0);

        if ($nextID != 0)
        {
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$nextIndex' WHERE ".$this->primary_key."='$id'");
            $this->db->ExecuteSql ("UPDATE ".$this->table_name." Set order_index='$curIndex' WHERE ".$this->primary_key."='$nextID'");
			$this->history("Изменение позиции down", $this->table_name, "", $id);
        }

         //$this->Redirect ("/adm/".$this->table_name);
    }
	
	function GetTypeForms ($field)
	{
		$res = "<select name='type' class='chosen-select'>";	
		$selected = ($field == "text")?"selected":"";
		$res .= "<option value='text' $selected>Текстовое поле</option>";
		$selected = ($field == "tel")?"selected":"";
		$res .= "<option value='tel' $selected>Телефон</option>";
		$selected = ($field == "email")?"selected":"";
		$res .= "<option value='email' $selected>Email</option>";
		$selected = ($field == "hidden")?"selected":"";
		$res .= "<option value='hidden' $selected>Скрытое поле</option>";
		$selected = ($field == "select")?"selected":"";
		$res .= "<option value='select' $selected>Select</option>";
		$selected = ($field == "checkbox")?"selected":"";
		$res .= "<option value='checkbox' $selected>Checkbox</option>";
		$selected = ($field == "submit")?"selected":"";
		$res .= "<option value='submit' $selected>Submit</option>";
		return $res."</select>";
	}
	
	function GetForm ($type, $name, $value, $class, $placeholder, $label, $required, $def)
	{
		if ($required)
		{
			$required = "required='true'";
		}
		else
		{
			$required = "";
		}
		if ($label != "")
		{
			$label = "<label>$label</label>";
		}
		else
		{
			$label = "";
		}
		switch ($type)
		{
			case "text":
			case "tel":
			case "email":
				
				$res="$label<input type='$type' name='$name' class='$class' value='$value' placeholder='$placeholder' $required>";
			break;
			case "select":				
				if ($value!="")
				{
					$res = "$label<select name='$name' class='$class'>";
					//echo $value;		
					$result = explode ("\r\n" , $value);	
							
					
					for ($i = 0; $i < (count($result)-1); $i++)
					{
						$selected = ($def == $result[$i])?"selected":"";
						$res .= "<option value='".$result[$i]."' $selected>".$result[$i]."</option>";
					}
					$res."</select>";
				}
				else
				{
					$res="";
				}
			break;
			default: $res="<input type='hidden' name='$name' class='$class' value='$value'>";
		}
		return $res;
	}
}