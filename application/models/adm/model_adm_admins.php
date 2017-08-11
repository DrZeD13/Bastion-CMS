<?php
/*
структура таблицы
admin_id	Идентификатор
username	Логин
passwd Пароль
pages уровень доступа
is_active	Флаг активности блога.
*/
class Model_Adm_Admins extends Model 
{

	var $table_name = 'admins';
	var $primary_key = 'admin_id';
	var $orderType = array ("admin_id" => "", "username" => "", "is_active" => "");
	var $orderDefault = "admin_id";
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
				$type = "username";
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
			'username' => $this->Header_GetSortLink($mainlink, "username", "Логин"),
			'is_active' => $this->Header_GetSortLink($mainlink, "is_active"),
		);
		
		// запрос получения списка элементов
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT ".$this->primary_key.", username, is_active ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			$token = $this->GetSession("token", false);
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				$username = $this->dec($row['username']);
				
				$activeLink = "/adm/".$this->table_name."/activate?id=".$id."&token=".$token;
				$activeImg = ($row['is_active'] == 0)?"times":"check";
				$editLink = "/adm/".$this->table_name."/edit?id=".$id;
				
				$delLink = ($id == 1)?"":"/adm/".$this->table_name."/del?id=".$id."&token=".$token;
				
				$data ["article_row"][] = array (
					"id" => $id,
					"username" => $username,
					
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
		if ($id > 1) 
		{
			$count = $this->db->GetOne ("SELECT Count(*) FROM ".$this->table_name." WHERE ".$this->primary_key." = '$id'");
			if ($count > 0) 
			{				
				$sql = "SELECT username FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
				$name = $this->db->GetOne($sql);
				$this->history("Удаление", $this->table_name, $name, $id);
				$this->db->ExecuteSql ("DELETE FROM ".$this->table_name." WHERE ".$this->primary_key." = '$id'");
			}
		}
        $this->Redirect ("/adm/".$this->table_name);
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
			"pages" => $this->Get_Pages($row['pages']),		
			"login" => $row["username"],
			"token" => $this->GetSession("token", false),
			"login_error" => "",
			"pass" => "",
			"required" => "",
			"pass_error" => "Оставьте пустым если не хотите менять пароль",
		);
		
		return $data;
	}
	
	function Add()
	{		
		$data = $this->Form_Valid();
		$data["pages"] = $this->Get_Pages(" ");
		$data["main_title"] = "Добавление пункта";
		$data["table_name"] = $this->table_name;
		$data["action"] = "insert";
		$data["required"] = "required";
		$data["login_error"] = "";
		$data["pass_error"] = "";
		$data["token"] = $this->GetSession("token", false);
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
			$res = $this->GetAccessAdminString();			
			$pass= md5($data['pass']);
			$sql = "Insert Into ".$this->table_name." (username, passwd, pages) Values ('".$data['login']."', '$pass', '$res')";
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
			$res = $this->GetAccessAdminString();
			if ($data["pass"] == "") 
			{
				$pass="";
			}
			else
			{
				$pass= " passwd = '".md5($data['pass'])."',";
			}
			$sql = "UPDATE ".$this->table_name." SET username = '".$data['login']."' ,$pass pages='$res' WHERE ".$this->primary_key." = '$id'";			
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
			$sql = "SELECT pages FROM ".$this->table_name." WHERE ".$this->primary_key." = '$id'";
			$pages = $this->db->GetOne($sql);
			$data["pages"] = $this->Get_Pages($pages);			
			$data["main_title"] = "Редактирование пункта";
			$data["action"] = "update";
			$data["required"] = "";
		}
		else
		{						
			$data["pages"] = $this->Get_Pages(" ");
			$data["main_title"] = "Добавление пункта";
			$data["action"] = "insert";					
			$data["required"] = "required";
		}
		$data["token"] = $this->GetSession("token", false);
		return $data;
	}
	
	function Form_Valid($edit = false)
	{		
		$data["login"] = $this->enc($this->GetValidGP ("login", "Логин", VALIDATE_NOT_EMPTY));
		$data["login_error"] = $this->GetError("login");
		$id = $this->GetID("id");
		$where = ($edit)?" and ".$this->primary_key." <> '$id'":"";				
		$total = $this->db->GetOne ("SELECT Count(*) FROM ".$this->table_name." WHERE username='".$data["login"]."' $where", 0);		
		if ($total > 0) {
			$this->SetError("login", "Такой Логин уже есть");
		} 	
		if ($edit)
		{
			$data["pass"] = $this->GetGP("pass", "");
		}
		else
		{
			$data["pass"] = $this->enc($this->GetValidGP ("pass", "Пароль", VALIDATE_NOT_EMPTY));
		}
		$data["pass_error"] = $this->GetError("pass");
			
		return $data;
	}
}
