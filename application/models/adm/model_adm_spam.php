<?php
/*
структура таблицы
spam_id	Идентификатор
start - начало маски
end - конец маски

*/
class Model_Adm_Spam extends Model 
{
	
	// имя таблицы для модуля
	var $table_name = 'spam';
	// название id-поля таблицы для модуля
	var $primary_key = 'spam_id';
	// по каким полям можно осуществлять сортировку
	var $orderType = array ("spam_id"=>"", "news_date" => "", "start" =>"", "end"=>"");
	// поле по которуому осуществляется сортировка по умолчанию
	var $orderDefault = "news_date";
	// количество записей выводимых на страницу по умолчанию
	var $rowsPerPage = 20;
	var $orderDirDefault = "desc";
	
	
	public function get_data() 
	{			
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?";
		// поиск по статьям
		if ($search == "") 
		{			
			$fromwhere = "FROM ".$this->table_name." ORDER BY ".$this->orderBy." ".$this->orderDir;	
		}
		else
		{
			// предусмотрен поиск по любому полю
			$mainlink .= "search=".$search."&";
			$type = $this->GetGP ("type", "");
			if ($type == "")
			{ 
				$type = "start";
			}
			else
			{
				$mainlink .= "type=".$type."&";
			}
			$search1 = ip2long($search);
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE  '%$search1%' ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		
		// запрос для получения шапки таблицы
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'search' => $search,
			'table_name' => $this->table_name,
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			'start' => $this->Header_GetSortLink($mainlink, "start", "ip"),
			'end' => $this->Header_GetSortLink($mainlink, "end", "ip"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT ".$this->primary_key.", news_date, start, end ".$fromwhere;										
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];			
				$news_date = date("d-m-Y", $this->dec($row['news_date']));
				
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				
				$data ["row"][] = array (
					"id" => $id,
					"news_date" => $news_date,
					"start" => long2ip($row['start']),
					"end" => long2ip($row['end']),					

                    "edit" => $editLink,
                    "del" => $delLink,
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
	
	function Delete()
	{
		$id = $this->GetGP ("id");
		$sql = "SELECT start FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		
		$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
		
		$data = array (
			"news_date" => $row["news_date"],
			"start" => long2ip($row['start']),
			"start_error" => $this->GetError("start"),
			"end" => long2ip($row['end']),	
			"end_error" => $this->GetError("end"),
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",
		);		
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();
		$data["news_date"] = time();
		$data["start_error"] = "";
		$data["end_error"] = "";
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
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
		$data["start_error"] = $this->GetError("start");
		$data["end_error"] = $this->GetError("end");
		if ($edit == "update") 
		{
			$row = $this->db->GetEntry ("SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key."='$id'");	   
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
        $data["start"] = $this->GetGP("start");
		if ($this->Get_Spam($data["start"]))
		{
			$this->SetError("start", "Такой ip уже есть в базе данных");
		}
		else
		{
			$data["start"] = ip2long($data["start"]);
		}
		$data["end"] = $this->GetGP("end");
		if ($data["end"] == "") $data["end"] = $this->GetGP("start");
		if ($this->Get_Spam($data["end"]))
		{
			$this->SetError("end", "Такой ip уже есть в базе данных");
		}
		else
		{
			$data["end"] = ip2long($data["end"]);
		}
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));
		
		return $data;
	}

}
