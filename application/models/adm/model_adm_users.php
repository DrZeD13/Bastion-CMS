<?php
/*
структура таблицы
user_id	Идентификатор
login	Логин
pwd Пароль
is_active	Флаг активности блога.
*/
class Model_Adm_Users extends Model 
{

	var $table_name = 'users';
	var $orderType = array ("user_id", "login", "is_active");
	var $orderDefault = "user_id";
	var $primary_key = 'user_id';
	var $orderDirDefault = "asc";
	
	public function get_data() 
	{			
		$search = $this->GetGP ("search", "");
		$mainlink = "/adm/".$this->table_name."/?";
		// поиск
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
				$type = "login";
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
			'login' => $this->Header_GetSortLink($mainlink, "login", "Логин"),
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка элементов
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT ".$this->primary_key.", login, is_active ".$fromwhere;					
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$login = $this->dec($row['login']);
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id;
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				$delLink = "/adm/".$this->table_name."/del?id=".$id;
				
				$data ["article_row"][] = array (
					"id" => $id,
					"login" => $login,
					
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
		$sql = "SELECT login FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$name = $this->db->GetOne($sql);
		$this->history("Удаление", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);        
	}
	
	function Edit()
	{
		$id = $this->GetID("id");
		$data = $this->Form_Valid();
		$sql = "SELECT * FROM ".$this->table_name." WHERE ".$this->primary_key." = '$id'";
		$row = $this->db->GetEntry($sql);
		$data = array (
			"main_title" => "Редактирование пункта",
			'table_name' => $this->table_name,
			"action" => "update",			
			"login" => $row["login"],
			"email" => $row["email"],
			"news_date" => $row["news_date"],
			"login_error" => "",
			"pwd" => "",
			"required" => "",
			"pwd_error" => "Оставьте пустым если не хотите менять пароль",
		);
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();		
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
		$data["action"] = "insert";
		$data["required"] = "required";
		$data["news_date"] = time();
		$data["login_error"] = "";
		$data["pwd_error"] = "";
		
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
			$data['pwd'] = md5($data['pwd']);
			
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
			if ($data["pwd"] == "") 
			{
				$sql="SELECT pwd FROM ".$this->table_name." WHERE {$this->primary_key}='$id'";
				
				$data["pwd"] = $this->db->GetOne($sql);
			}
			else
			{
				$data["pwd"] = md5($data['pwd']);
			}
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
		if ($edit == "update") 
		{ 		
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			$data["required"] = "";
		}
		else
		{						
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";					
			$data["required"] = "required";
		}
		$data["login_error"] = $this->GetError("login");;
		$data["pwd_error"] = $this->GetError("pwd");;
		return $data;
	}
	
	function Form_Valid($edit = false)
	{
		$data["login"] = $this->enc($this->GetValidGP ("login", "Логин", VALIDATE_NOT_EMPTY));
		$id = $this->GetID("id");
		$where = ($edit)?" and ".$this->primary_key." <> '$id'":"";				
		$total = $this->db->GetOne ("SELECT Count(*) FROM ".$this->table_name." WHERE login='".$data["login"]."' $where", 0);		
		if ($total > 0) {
			$this->SetError("login", "Такой Логин уже есть");
		} 	
		if ($edit)
		{
			$data["pwd"] = $this->GetGP("pwd", "");
		}
		else
		{
			$data["pwd"] = $this->enc($this->GetValidGP ("pwd", "Пароль", VALIDATE_NOT_EMPTY));
		}		
		
		$data["news_date"] = strtotime ($this->GetGP("news_date", ""));
		$data["email"] = $this->enc($this->GetGP("email"));
			
		return $data;
	}
}
