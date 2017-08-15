<?php
include "application/plagins/model_vk.php";
/*
структура таблицы
users_id идентификатор
news_date дата регистрации
login логин
pwd пароль
name имя
email 
is_active флаг активности
activate код активации или восстановления пароля
*/
class Model_Login extends Model 
{

	private $table_name = 'users';
	private $client_id = '5344724'; // ID приложения (vk.com)
	private $client_secret = 'AMGBWiIV5hgYu63Elkf5'; // Защищённый ключ
	
	public function get_data() 
	{
		$temp = "Авторизация";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		$data["error"] = $this->GetError("error");
		$data["nav"] = MAIN_NAV.$temp;
		
		
		$redirect_uri = $this->siteUrl.'login/authVK'; // Адрес сайта		
		$url = 'http://oauth.vk.com/authorize';

		$params = array(
			'client_id'     => $this->client_id,
			'redirect_uri'  => $redirect_uri,
			'scope' => 'offline,email,wall',
			'response_type' => 'code',			
		);
		$vk = new Model_Vk();
		//$data["vklink"] = $url.'?'.urldecode(http_build_query($params));
		$data["vklink"] = $vk->get_url_autorize($redirect_uri);
		
		return $data;
	}
	
	public function get_cabinet() 
	{
		$temp = "Личный кабинет";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		$data["nav"] = MAIN_NAV.$temp;
		if (isset($_COOKIE['social']))
		{
			$data["linkpassword"] = "";
		}
		else
		{			
			$data["linkpassword"] = "<a href='/login/changepassword'>Изменить пароль</a><br><br>";
		}
		return $data;
	}
	
	public function get_authVK ()
	{
		$redirect_uri = $this->siteUrl.'login/authVK'; // Адрес сайта		
		$vk = new Model_Vk();
		$userInfo = $vk->get_authVK($redirect_uri);
		if ($userInfo)
		{
			$login = $userInfo["response"][0]["last_name"]." ".$userInfo["response"][0]["first_name"];
			$sql = "SELECT user_id FROM ".$this->table_name." WHERE social_id = '".$userInfo["response"][0]["uid"]."' and social = 'vk'";
			$user = $this->db->GetOne($sql, 0);
			$hash = md5($this->getUnID(16));
			$psw =  md5($this->getUnID(16));
			$email = $token["email"];
			if ($user == 0)
			{
				$sql = "SELECT user_id FROM ".$this->table_name." WHERE email = '".$email."'";
				$user_old = $this->db->GetOne($sql, 0);
				if ($user_old > 0)
				{					
					$user = $user_old;
					$sql="UPDATE users SET hash = '$hash', social_id = '".$userInfo["response"][0]["uid"]."', social = 'vk' WHERE user_id='$user'";
					$this->db->ExecuteSql($sql);
				}
				else
				{
					$sql = "Insert Into `users` (news_date, login, pwd, email, hash, social_id, social, is_active) Values ('".time()."', '$login', '$psw', '$email', '$hash', '".$userInfo["response"][0]["uid"]."', 'vk', '1')";
					$this->db->ExecuteSql($sql);
					$user = $this->db->GetInsertID ();
				}				
			}
			else
			{
				$sql="UPDATE users SET hash = '$hash' WHERE user_id='$user'";
				$this->db->ExecuteSql($sql);
			}			
			setcookie("id", $user, time()+60*60*24*30, "/");
			setcookie("hash", $hash, time()+60*60*24*30, "/");
			setcookie("U_LOGIN", $login, time()+60*60*24*30, "/");			
			setcookie("social", "vk", time()+60*60*24*30, "/");			
			//echo $token["email"];
			$this->Redirect($this->siteUrl."login/cabinet");
		}
		else
		{
			$this->Redirect($this->siteUrl."login/");
		}
	}	
	
	public function get_changepassword() 
	{		
		if (isset($_COOKIE['social']))
		{
			$this->Redirect($this->siteUrl."login/cabinet");
		}
		$temp = "Изменение пароля";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		$data["nav"] = MAIN_NAV.$temp;
		if (isset($_POST["psw"]))
		{		
			$psw = $this->enc ($this->GetValidGP ("psw", "Пароль", VALIDATE_PASSWORD));
			$psw1 = $this->enc ($this->GetValidGP ("psw1", "Пароль", VALIDATE_PASSWORD));
			$psw2 = $this->enc ($this->GetGP("psw2"));
			if ($psw2 != $psw1) {$this->SetError("psw2", "Пароли не совпадают");}
			
			if ($this->errors['err_count'] > 0) {
				$data["message"] = "";
			}
			else
			{
				$sql = "SELECT Count(*) FROM users WHERE user_id = '".$_COOKIE["id"]."' and pwd = '".md5($psw)."'";
				$total = $this->db->GetOne ($sql);
				if ($total > 0)
				{
					$sql = "UPDATE users SET pwd='".md5($psw1)."' WHERE user_id = '".$_COOKIE["id"]."'";
					$this->db->ExecuteSql($sql);
					$data["message"] = "Пароль успешно изменен";
				}
				else
				{
					$data["message"] = "Не верно введен старый пароль";
				}
				
			}
		}
		else
		{
			$data["message"] = "";			
		}
		
		$data["psw_error"] = $this->GetError("psw");
		$data["psw1_error"] = $this->GetError("psw1");
		$data["psw2_error"] = $this->GetError("psw2");
		return $data;
	}
	
	public function get_lostpassword() 
	{		
		$temp = "Восстановление пароля";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		if (isset($_GET["hash"]))
		{
			$email = $this->GetGP_SQL("email", "");
			$hash = $this->GetGP_SQL("hash", "");
			$sql = "SELECT Count(*) FROM users WHERE email='$email' and hash='$hash'";
			if ($this->db->GetOne($sql, 0) > 0)
			{
				$psw = $this->getUnID(16);
				$login = $this->db->GetOne("SELECT login FROM users WHERE email = '$email'", "");
				$sql = "UPDATE users SET pwd='".md5($psw)."', hash='".md5(time())."' WHERE email = '$email'";
				$this->db->ExecuteSql($sql);
				$SiteName = $this->db->GetSetting ("SiteName");
				$subject = "Восстановление пароля на сайте ".$SiteName;
				$message = "Добрый день!<br><br>Логин: $login<br>Пароль: $psw<br><br>Обязательно измените пароль!<br><br>С уважением, Администрация ".$SiteName;
				$this->SendMail ($email, $subject, $message);
				$data["message"] = "Данные для <a href='/login/'>входа</a> отправлены на e-mail";
			}
			else
			{
				$data["message"] = "Данный email не зарестрирован или истек срок жизни ссылки, <a href='/login/registration'>зарегстрируйте</a> или <a href='/login/lostpassword'>восстановите пароль</a> повторно";
			}			
		}
		else
		{
			$data["message"] = $this->GetError("message");
		}		
		$data["email"] = $this->GetGP("email", "");
		$data["email_error"] = $this->GetError("email");
		$data["capcha_error"] = $this->GetError("capcha");
		$data["nav"] = MAIN_NAV.$temp;
		return $data;
	}
	
	public function get_lostpasswordOn() 
	{
		$email = $this->enc ($this->GetValidGP ("email", "Email адрес", VALIDATE_EMAIL));	
		 /*@@@@@@@@@@@@@@@@@@-- Begin: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
        $code = $this->GetGP("keystring");
		$flag = $this->ChecCode($code);
		if (!$flag) {$this->SetError("capcha", "Не верная последовательность");}      	
      	/*@@@@@@@@@@@@@@@@@@-- END: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
        if ($this->errors['err_count'] > 0) {
			return false;
        }
		 else 
		 {
			$hash = md5(mt_rand(1, 10000));
			$sql = "UPDATE users SET hash='$hash' WHERE email = '$email'";
			$this->db->ExecuteSql($sql);
			$SiteName = $this->db->GetSetting ("SiteName");
			$subject = "Восстановление пароля на сайте ".$SiteName;
			$link = $this->siteUrl."login/lostpassword?email=".$email."&hash=".$hash;
			$message = "Добрый день!<br><br>Вы запрашивали восстановление пароля на сайте ".$this->siteUrl.", для восстановления пароля перейдите по ссылке ниже, если Вы не создавали запрос, то просто проигнорируйте это письмо.<br><br>$link<br><br>С уважением, Администрация ".$SiteName;
			$this->SendMail ($email, $subject, $message);
			$this->SetError("message", "Инструкции по восстановлению пароля отправлены на e-mail");
			return true;
		 }
	}
	
	public function get_registration() 
	{		
		$temp = "Регистрация";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		$data["login"] = $this->GetGP("login", "");
		$data["login_error"] = $this->GetError("login");
		$data["email"] = $this->GetGP("email", "");
		$data["email_error"] = $this->GetError("email");
		$data["psw_error"] = $this->GetError("psw");
		$data["psw1_error"] = $this->GetError("psw1");
		$data["capcha_error"] = $this->GetError("capcha");
		
		$data["nav"] = MAIN_NAV.$temp;
		return $data;
	}
	
	public function get_registrationOn() 
	{
		$login = $this->enc ($this->GetValidGP ("login", "Ваш логин", VALIDATE_NOT_EMPTY));
		$email = $this->enc ($this->GetValidGP ("email", "Email адрес", VALIDATE_EMAIL));
		$psw = $this->enc ($this->GetValidGP ("psw", "Пароль", VALIDATE_PASSWORD));
        $psw1 = $this->enc ($this->GetGP("psw1"));
		if ($psw != $psw1) {$this->SetError("psw1", "Пароли не совпадают");}
         /*@@@@@@@@@@@@@@@@@@-- Begin: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
        $code = $this->GetGP("keystring");
		$flag = $this->ChecCode($code);
		if (!$flag) {$this->SetError("capcha", "Не верная последовательность");}      	
      	/*@@@@@@@@@@@@@@@@@@-- END: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
        if ($this->errors['err_count'] > 0) {
			return false;
        }
        else 
		{
			$sql = "SELECT login, email FROM users WHERE login = '$login' or email = '$email'";
			$row = $this->db->GetEntry($sql);
			if ($row["login"] == $login)
			{
				$this->SetError("login", "Такой логин занят");
				return false;
			}
			elseif ($row["email"] == $email)
			{
				$this->SetError("email", "Такой e-mail уже зарегистрирован");
				return false;
			}
			else
			{					
				$psw = md5($psw);
				$hash = md5(mt_rand(1, 10000));
				$sql = "Insert Into `users` (news_date, login, pwd, email, hash) Values ('".time()."', '$login', '$psw', '$email', '$hash')";
				//echo $sql;				
				$this->db->ExecuteSql ($sql);
				$SiteName = $this->db->GetSetting ("SiteName");
				$subject = "Регистрация на сайте".$SiteName;
				$link = $this->siteUrl."login/activate?login=".$login."&hash=".$hash;
				$message = "Добрый день!<br><br>Вы только что загистрировались на сайте ".$this->siteUrl.", для активации вашего аккаунта перейдите по ссылке ниже, если Вы не регистрировались, то просто проигнорируйте это письмо.<br><br>$link<br><br>С уважением, Администрация ".$SiteName;
				$this->SendMail ($email, $subject, $message);
				$this->SetError("message", "Регистраниця прошла успешно на Ваш e-mail выслано письмо с активацией аккаунта");
				return true;
			}
        }

	}
	
	/*public function get_registrationVK()
	{
		$client_id = '5344724'; // ID приложения
		$client_secret = 'AMGBWiIV5hgYu63Elkf5'; // Защищённый ключ
		$redirect_uri = $this->siteUrl; // Адрес сайта
		
		$url = 'http://oauth.vk.com/authorize';

		$params = array(
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'response_type' => 'code'
		);
		
		echo $link = '<p><a href="' . $url . '?' . urldecode(http_build_query($params)) . '">Аутентификация через ВКонтакте</a></p>';
	}*/
	
	public function get_activate()
	{
		$temp = "Активация пользователя";
		$data = array(
			"title" => $temp, 		
			"text" => $temp,
			"head_title" =>	$temp,
			"description" => $temp,
			"keywords" => $temp,			
		);
		$data["nav"] = MAIN_NAV.$temp;
		$login = $this->GetGP_SQL("login", "");
		$hash = $this->GetGP_SQL("hash", "");
		$sql = "SELECT Count(*) FROM users WHERE login='$login' and hash='$hash'";
		if ($this->db->GetOne($sql, 0) > 0)
		{
			$sql = "UPDATE users SET is_active='1', hash='".md5(time())."' WHERE login = '$login'";
			//echo $sql;
			$this->db->ExecuteSql($sql);
			$data["message"] = "Ваш акаунт активирован воспользуйтесь формой <a href='/login/'>входа</a> чтобы авторизоваться на сайте";
		}
		else
		{
			$data["message"] = "Данного пользователя не существует или истек срок жизни ссылки, <a href='/login/registration'>зарегстрируйте</a> или <a href='/login/'>войдите</a> чтобы выслать ссылку повторно";
		}
		return $data;
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
			$pwd = md5($this->GetGP_SQL("pwd", ""));
			$sql = "SELECT user_id FROM ".$this->table_name." WHERE login = '".$login."' and pwd = '".$pwd."' and is_active = '1'";		
			$user = $this->db->GetOne($sql, 0);
			if ($user) 
			{
				$hash = md5($this->getUnID(16));
				setcookie("id", $user, time()+60*60*24*30, "/");
				setcookie("hash", $hash, time()+60*60*24*30, "/");
				setcookie("U_LOGIN", $login, time()+60*60*24*30, "/");
				$sql="UPDATE users SET hash = '$hash' WHERE user_id='$user'";
				$this->db->ExecuteSql($sql);
				return true;
			}
			else
			{
				$this->SetError("error", "Неверный логин или пароль");
				
				$this->history($login, "off");
				$time1 = time()-300;
				$time = $time1+600;
				$sql = "SELECT Count(*) FROM log WHERE news_date < '$time' and news_date > '$time1' and status='off' and ip='".$_SERVER['REMOTE_ADDR']."'";
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
		$sql = "INSERT INTO log (admin_pages, name, ip, news_date, status) VALUE ('Авторизация сайт', '$name', '".$_SERVER['REMOTE_ADDR']."', '".time()."', '$status')";
		$this->db->ExecuteSql($sql);
	}

}
