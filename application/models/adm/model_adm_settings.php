<?php
/*
без бд работает на файле
*/
class Model_Adm_Settings extends Model 
{
	var $table_name = "settings";
	
	public function get_data() 
	{
		global $SETTING;
		$title = $this->GetAdminTitle($this->table_name);
		$data = array (
			'title' => $title,
			'main_title' => $title,
			"token" => $this->GetSession("token", false),
		);
		foreach ($SETTING as $key => $value)
		{			
			$temp =  str_replace ("\\\"", "\"", $value["value"]);
			$data ["row"][] = array (
				"title" => $value["title"],
				"keyname" => $key,
				"value" => $temp,
				"type" => $value["type"],
				"error" => $this->GetError($key),
			);
		}
		
		return $data;
	}
	
	public function Save()
	{
		global $SETTING;
		$this->GetToken();
		foreach ($SETTING as $key => $value)
		{			
			if ($SETTING[$key]['type'] == "checkbox")
			{
				$SETTING[$key]['value'] = $this->GetGP($key, 0);
			}
			elseif ($SETTING[$key]['type'] == "numeric")
			{
				 $temp = $this->GetGP($key, 0);
				 if (($temp < 50) || ($temp > 1024))
				 {
					 $this->SetError($key, "Введите значение от 50 до 1024");
				 }
				 else
				 {
					 $SETTING[$key]['value'] = $temp;
				 }	 								
			}
			else
			{
				$temp = $this->GetValidGP($key, $value["title"], VALIDATE_NOT_EMPTY);
				$temp =  str_replace ("'", '"', $temp);
				$SETTING[$key]['value']  = $temp;
			}
		}
		if ($this->errors['err_count'] == 0)
		{
			$this->ArrayToPHP($SETTING, $_SERVER["DOCUMENT_ROOT"]."/application/core/setting.php");
			$this->history("Изменение", $this->table_name, "", "");
		}		
	}
	
	function ArrayToPHP($value, $filename)
	{		
		$f = fopen($filename, 'w');
		fwrite($f, "<?\n".'$SETTING'." = ".var_export($value,true).";");
		fclose($f);
	}

}
