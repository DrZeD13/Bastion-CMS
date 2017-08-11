<?php
/*
структура таблицы
admin_id идентификатор
username логин
passwd пароль
pages страницы разрешенные для редактирования
is_active флаг активности
*/
class Model_Adm_Login extends Model 
{

	private $table_name = '`admins`';
	
	public function get_data() 
	{
		if (isset($_SESSION["A_ID"]))
		{
			$sql = "SELECT pages FROM `admins` WHERE admin_id = '".$_SESSION["A_ID"]."'";		
			$temp = $this->db->GetOne($sql, "");	
			$mas = explode(',', $temp);		
			$this->Redirect("/adm/".$mas[1]."/");
		}
	}
	
	public function avtorized() 
	{	
		if (isset($_SESSION[$_SERVER['REMOTE_ADDR']]['ip']))
		{			
			if (($_SESSION[$_SERVER['REMOTE_ADDR']]['time']) > time())
			{
				$this->SetError("error", "По пробуйте чуть позже");				
				return false;
			}
			else
			{				
				$this->SetError("error", "По пробуйте чуть позже");
				unset($_SESSION[$_SERVER['REMOTE_ADDR']]['ip']);
				return false;
			}
		}
		else
		{
			$login = $this->GetGP_SQL("login", "");
			$pwd = $this->GetGP_SQL("pwd", "");
			$pwd = md5($pwd);
			$from = "FROM ".$this->table_name." WHERE username = '".$login."' and passwd = '".$pwd."' and is_active = '1'";
			$sql = "SELECT admin_id ".$from;		
			$admin = $this->db->GetOne($sql, 0);
			if ($admin > 0) 
			{
				$this->history($login, "on");				
				$_SESSION["A_ID"] = $admin;
				$_SESSION["token"] = md5(time());
				$sql = "SELECT pages FROM `admins` WHERE admin_id = '".$admin."'";		
				$temp = $this->db->GetOne($sql, "");	
				$mas = explode(',', $temp);		
				$this->Redirect ("/adm/".$mas[1]."/");
				//return true;
			}
			else
			{
				$this->SetError("error", "Неверный логин или пароль");
				
				$this->history($login, "off");
				$time1 = time()-300;
				$time = $time1+600;
				$sql = "SELECT Count(*) FROM log WHERE news_date < '$time' and news_date > '$time1' and status='off'";
				$total = $this->db->GetOne($sql, 0);				
				if ($total > 3)
				{
					$_SESSION[$_SERVER['REMOTE_ADDR']]['ip'] = $_SERVER['REMOTE_ADDR'];					
					$_SESSION[$_SERVER['REMOTE_ADDR']]['time'] = $time;
				}
				return false;
			}
		}
	}
	
	function history($name, $status)
	{
		$sql = "INSERT INTO log (admin_pages, name, ip, news_date, status) VALUE ('Авторизация', '$name', '".$_SERVER['REMOTE_ADDR']."', '".time()."', '$status')";
		$this->db->ExecuteSql($sql);
	}

}
