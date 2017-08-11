<?php
/*
структура таблицы
indigrienty_id	Идентификатор блога
parent_id	Идентификатор родителя
value значение 
*/
class Model_Adm_Indigrienty extends Model 
{
	
	// имя таблицы для модуля
	var $table_name = 'indigrienty';
	// название id-поля таблицы для модуля
	var $primary_key = 'indigrienty_id';
	// по каким полям можно осуществлять сортировку
	var $orderType = array ("indigrienty_id"=>"", "parent_id" =>"", "value"=>"");
	// поле по которуому осуществляется сортировка по умолчанию
	var $orderDefault = "indigrienty_id";
	// путь к папке с картинками
	// количество записей выводимых на страницу по умолчанию
	var $rowsPerPage = 20;
	var $orderDirDefault = "asc";
	
	
	public function get_data() 
	{			
		$parent_id = $this->GetGP("parent_id", -1);
        if ($parent_id == -1)
        {
            $parent_id = $this->GetSession("PRODUCTID", 0);
        }
        else {
            $_SESSION['PRODUCTID'] = $parent_id;
        }
		$sql = "SELECT Count(*) FROM `products` WHERE product_id='".$parent_id."'";
		$total = $this->db->GetOne($sql);
		// если нет такой продукции к кторой добавлять индигридиенты переадресуем на продукцию
		if ($total == 0) 
		{
			$this->Redirect ("/adm/products/");
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
				$type = "value";
			}
			else
			{
				$mainlink .= "type=".$type."&";
			}
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE  '%$search%' AND parent_id=".$parent_id." ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		$nav = $this->GetAdminTitle("products");
		$sql = "SELECT title FROM `products` WHERE product_id='".$parent_id."'";
		$navtitle = $this->db->GetOne($sql);
		$navigation = "<div class='breadcrumb'><a href='".$this->siteUrl."adm/products/'>".$nav."</a> / ".$navtitle."</div>";
		// запрос для получения шапки таблицы
		//$title = $this->db->GetAdminTitle($this->table_name);
		$data = array (
			'title' => "Ингредиенты рецепта $navtitle",
			'main_title' => "Ингредиенты рецепта $navtitle",
			'search' => $search,
			'navigation' => $navigation,
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			't_title' => $this->Header_GetSortLink($mainlink, "value", "Значение"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT ".$this->primary_key.", value ".$fromwhere;										
			$result=$this->db->ExecuteSql($sql);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];				
				$value = $this->dec($row['value']);								
				
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				
				$data ["row"][] = array (
					"id" => $id,
					"value" => $value,
					"action" => "update",
                    "edit" => $editLink,
                    "del" => $delLink,
				);						
			}
			$this->db->FreeResult ($result);
			//$data['pages'] = $this->Pages_GetLinks($total, $mainlink);
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		return $data;
	}
	
	function Delete()
	{
		$id = $this->GetGP ("id");
		$sql = "SELECT value FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
	}
	
	function Insert()
	{
		$data = $this->Form_Valid();		
		$sql = "Insert Into ".$this->table_name." ".ArrayInInsertSQL ($data);
		$this->db->ExecuteSql($sql);
		$id = $this->db->GetInsertID ();
		$this->history("Добавление", $this->table_name, "", $id);	
		return true;
		
	}
	
	function Update()
	{
		$id = $this->GetID("id", 0);
		$data = $this->Form_Valid();		
		$sql = "UPDATE ".$this->table_name." SET ".ArrayInUpdateSQL ($data)." WHERE {$this->primary_key}='$id'";			
		$this->db->ExecuteSql($sql);
		$this->history("Изменение", $this->table_name, "", $id);
		return true;
	}	
	
	function Form_Valid($edit = false)
	{
		$data["parent_id"] = $this->GetSession("PRODUCTID", 0);
		$data["value"] = $this->enc($this->GetGP("value"));
		
		return $data;
	}	

}
