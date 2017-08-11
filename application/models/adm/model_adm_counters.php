<?php
/*
структура таблицы
counter_id идентификатор
code код счетчика
*/
class Model_Adm_Counters extends Model 
{

	var $table_name = 'counters';
	
	public function get_data() 
	{
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
		);
		$sql = "SELECT counter_id, code FROM ".$this->table_name;
		$result = $this->db->ExecuteSql($sql);
		while ($row = $this->db->FetchArray ($result))	
		{			
			$data ["row"][] = array (
				"counter_id" => "count".$row["counter_id"],
				"code" => $this->dec($row["code"]),
			);
		}
		$this->db->FreeResult ($result);
		
		return $data;
	}
	
	public function Save()
	{
		$sql = "SELECT counter_id, code FROM ".$this->table_name;
		$result = $this->db->ExecuteSql($sql);
		while ($row = $this->db->FetchArray ($result))	
		{			
			$temp = $this->enc($this->GetGP("count".$row["counter_id"]));
			$sql = "UPDATE ".$this->table_name." SET code='".$temp."' WHERE counter_id='".$row["counter_id"]."'";

			$this->db->ExecuteSql($sql);
		}
		$this->db->FreeResult ($result);		
		$this->history("Изменение", "counters", "", "");
	}

}
