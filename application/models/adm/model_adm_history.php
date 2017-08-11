<?php
/*
*/
class Model_Adm_History extends Model 
{

	// имя таблицы для модуля
	var $table_name = 'log';
	// название id-поля таблицы для модуля
	var $primary_key = 'log_id';
	// по каким полям можно осуществлять сортировку
	var $orderType = array ("log_id"=>"", "admin_id" =>"", "news_date"=>"", "ip"=>"", "status" => "", "admin_pages"=>"",);
	// поле по которуому осуществляется сортировка по умолчанию
	var $orderDefault = "news_date";
	// количество записей выводимых на страницу по умолчанию
	var $rowsPerPage = 20;
	
	public function get_data() 
	{					
		$mainlink = "/adm/history/?";
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
				$type = "status";
			}
			else
			{
				$mainlink .= "type=".$type."&";
			}
			$fromwhere = "FROM ".$this->table_name." WHERE $type LIKE  '%$search%' ORDER BY ".$this->orderBy." ".$this->orderDir;
		}
		
		// запрос для получения шапки таблицы
		$title = $this->GetAdminTitle("history");
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'search' => $search,
			'table_name' => "history",
			'id' => $this->Header_GetSortLink($mainlink, $this->primary_key, "ID"),
			'admin_id' => $this->Header_GetSortLink($mainlink, "admin_id", "Админ"),
			'date' => $this->Header_GetSortLink($mainlink, "news_date", "Дата"),
			'ip' => $this->Header_GetSortLink($mainlink, "ip", "IP"),
			'status' => $this->Header_GetSortLink($mainlink, "status", "Статус"),
			'admin_pages' => $this->Header_GetSortLink($mainlink, "admin_pages", "Модуль"),
			'item_id' => "Название",
		);
		
		// запрос получения списка статей
		$sql="SELECT Count(*) ".$fromwhere;
		$total = $this->db->GetOne ($sql, 0);		
		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			$sql="SELECT * ".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$id = $row[$this->primary_key];
				if ($row['admin_pages'] == "Авторизация")
				{
					$admin_id = $row['name'];
					$item_id = "";
					$link_admin_pages = "login";
				}
				elseif ($row['admin_pages'] == "Авторизация сайт")
				{
					$admin_id = $row['name'];
					$item_id = "";
					$link_admin_pages = "login";
				}
				else
				{
					$admin_id = $this->db->GetOne ("SELECT username FROM `admins` WHERE admin_id='".$row['admin_id']."'")." (".$row['admin_id'].")";
					
					if ($row['name'] == "")
					{
						$item_id = $row['item_id'];
					}
					else
					{
						$item_id = $row['name'];
					}
					$link_admin_pages = $row['admin_pages'];
				}
				$admin_pages = $this->db->GetAdminTitle($row['admin_pages'], $row['admin_pages']);
				$data ["row"][] = array (
					"id" => $id,
					"admin_id" => $admin_id,
					"date" => date("H:i:s d-m-Y", $this->dec($row['news_date'])),
					"ip" => $row['ip'],
					"status" => $row['status'],
					"admin_pages" => $admin_pages,
					"item_id" => $item_id,
					"link_admin_pages" => $link_admin_pages,
					
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
	
	function clear_history()
	{
		// удаляем все записи старше 1 месяца
		$date = time()-(30*24*60*60);
		$sql = "DELETE FROM ".$this->table_name." WHERE news_date < ".$date;
		$this->db->ExecuteSql($sql);
	}
}