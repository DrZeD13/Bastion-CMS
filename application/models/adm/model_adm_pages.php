<?php
/*
структура таблицы
page_id идентификатор
keyname ключ идентификатор
title заголовок
text текст
*/
class Model_Adm_Pages extends Model 
{

	var $table_name = 'pages';
	
	public function get_data() 
	{
		$title = $this->GetAdminTitle($this->table_name);
		
		$page_id = $this->GetGP ("page_id", 0);
		if ($page_id > 0) $this->SaveStateValue ("page_id", $page_id);
        $page_id = $this->GetStateValue ("page_id", 0);

		if ($page_id == 0) {
            $page_id = $this->db->GetOne ("Select page_id From `pages` Order By title", 0);
            $this->SaveStateValue ("page_id", $page_id);
        }
		$sql = "SELECT title, text FROM ".$this->table_name." WHERE page_id='$page_id'";
		$row = $this->db->GetEntry($sql);
		
		$data = array (
			'title' => $title,
			'main_title' => $title,
			'page_select' => $this->getPageSelect($page_id),
			't_title' => $row['title'],
			'title_error' => $this->GetError("title"),
			'text' => dec($row['text']),
			"action" => "save",
            "id" => $page_id,
			"editor" => $this->editor(),
			"token" => $this->GetSession("token", false),
		);
		
		return $data;
	}
	
	public function Save()
	{
		$this->GetToken();
		$page_id = $this->GetGP ("page_id", 0);
        $title = $this->GetValidGP ("title", "Заголовок", VALIDATE_NOT_EMPTY);
        $text =$this->GetGP ("text");

		if ($this->errors['err_count'] == 0) 
		{			
			$this->db->ExecuteSql ("UPDATE ".$this->table_name." SET text='$text', title='$title' WHERE page_id='$page_id'");
			$this->history("Изменение", $this->table_name, "", $page_id);
		}
	}
	
	function getPageSelect ($value = 0)
    {
        $toRet = "<select name='page_id' onChange='this.form.submit();'> \r\n";

        $result = $this->db->ExecuteSql ("Select * From `pages` Order By title");
        while ($row = $this->db->FetchArray ($result))
        {
            $selected = ($row['page_id'] == $value) ? "selected" : "";
            $toRet .= "<option value='".$row['page_id']."' $selected>".$row['title']." - ".$row['keyname']."</option>";
        }
		$this->db->FreeResult ($result);
        return $toRet."</select>\r\n";
    }

}
