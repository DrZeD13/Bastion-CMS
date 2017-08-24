<?php
include_once('config.php');
include_once('function.php');
include_once('setting.php');
include_once('db.php');
$db = new DB ();
class Model_Default
{
	var $db = null; //для работы с базой данных
	var $siteUrl; 
	var $currentPage; //текущая страница
    var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (10, 30, 50); //количество записей на страницу

    var $orderBy; // поле сортировки
    var $orderDir; // тип сортировки
    var $orderDefault = "news_date"; // поле сортировки по умолчанию
	var $orderDirDefault = "desc"; // тип сортировки по умолчанию
	var $orderType = array ();//по каким полям можно осуществлять сортировку
	
	var $data = array (); //данные для шапки и подвала
	var $menu = array (); // массив элеметов меню
	var $menutree = array (); //дерево из массива меню
	var $menuarr = array (); // массив элементов каталога
	var $menuarrtree = array (); // дерево из каталога
	var $cid = 0; //активный пункт меню для навигации
	var $cidmenu = 0; //активный пункт меню для меню
	var $is_user = false; // авторизован ли пользователь
	var $module; // для хранения ссесий разных разделов
	
	var $errors = array ("err_count" => 0);
	
	var $months = array (1=>"Январь", 2=>"Февраль", 3=>"Март", 4=>"Апрель", 5=>"Май", 6=>"Июнь", 7=>"Июль", 8=>"Август", 9=>"Сентябрь", 10=>"Октябрь", 11=>"Ноябрь", 12=>"Декабрь"); 
	var $imageTypeArray = array (1 => "gif", 2 => "jpg", 3 => "png", 4 => "swf", 5 => "psd", 6 => "bmp", 7 => "tiff", 8 => "tiff", 9 => "jpc", 10 => "jp2", 11 => "jpx", 12 => "jb2", 13 => "swc", 14 => "iff", 15 => "wbmp", 16 => "xbm");
	
	function __construct()	
	{		
        global $db;
        $this->db = $db;
		$this->is_user = $this->CheckLogin();
		$this->siteUrl = 'http://'.$_SERVER['HTTP_HOST']."/";
		$parse = parse_url($_SERVER['REQUEST_URI']);
		$routes = explode('/', $parse['path']);
		$temp=(isset($routes[2]))?$routes[2]:"login";
		// для кривых ссылок типа http://site.ru//		
		if (!isset($routes[1]))
		{
			$temp1 = explode('?', $_SERVER['REQUEST_URI']);
			if ($temp1[0] != "/")
				 $this->Redirect("/");
			$routes[1] = "temp";
		}
		$this->module = ($routes[1] == "adm")?$routes[1].$temp:$routes[count($routes)-2];//$routes[1];
		// так как в сессии нельзя использовать чесловой ключ поэтому для ошибки 404 делаем исключение		
		if ($this->module == 404)
		{			
			$this->module = "error404";			
		}
		// для каталога строит дерево, если его нет закомментировать эти две строчки
		$this->menuarr = $this->get_array_catalog(false);
		$this->menuarrtree = GetTreeFromArray($this->menuarr);
		
		// массив элементов меню
		$this->menu = $this->get_array_menu();
		// получаем дерево из массива меню
		$this->menutree = GetTreeFromArray($this->menu);
		
		if (strpos($routes[1], "html"))
		{
			$url1 = $url = $routes[1];			
		}
		else
		{
			$url = $routes[1]."/";
			$url1 = ltrim($_SERVER['REQUEST_URI'], "/");
		}
		$this->cidmenu = $this->cid = (int)$this->db->GetOne("SELECT menu_id FROM menus WHERE url = '".$this->db->RealEscapeString($url)."'", 0);
		// исключаем дополнительный запрос в БД если УРЛ один и тот же
		if ($url1 != $url)
		{
			$this->cidmenu = (int)$this->db->GetOne("SELECT menu_id FROM menus WHERE url = '".$this->db->RealEscapeString($url1)."'", 0);	
		}
		if ($this->cid == 0) 
		{
			// дополнительная проверка для главной страницы, что бы получить её id, если она есть в меню сайта
			if ($url == "/") $this->cid = (int)$this->db->GetOne("SELECT menu_id FROM menus WHERE url = '$this->siteUrl'", 0);
		}
		$this->RestoreState();						
	}
	
	// Получение данных для шапки и подвала админки
	public function GetFixed()
	{
		$data['header'] = $this->header_adm();
		return $data;
	}
	
	// Получение данных для шапки и подвала сайта
	public function GetFixedSite()
	{
		//шапка сайта
		return $this->main_head();
	}
	
	// шапка для сайта определяется в основном классе проекта
	function main_head ()
	{
	}
	
	// отправлет собщение на почту параметры как функции mail(), дополнительно можно отправлять файлы
	function SendMail ($email, $subject, $message, $file_array = array(), $email_headers = "", $reply = "")
	{
		$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
		$boundary = "--".md5(uniqid(time())); // генерируем разделитель
		if (empty($reply))
		{
			$reply = "-fno-reply@".$_SERVER['HTTP_HOST'];
		}
		if (empty($email_headers))
		{						
			$SiteName = $this->db->GetSetting ("SiteName");
			$headers = "From: ".$SiteName." <no-reply@".$_SERVER['HTTP_HOST'].">\r\n";    
			$headers .= "Return-path: <no-reply@".$_SERVER['HTTP_HOST'].">\r\n";
			$headers .= "MIME-Version: 1.0\r\n"; 
			$headers .="Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n"; 
			$multipart = "--".$boundary."\r\n"; 
			$multipart .= "Content-Type: text/html; charset=UTF-8\r\n";
			$multipart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
			
			$message = quoted_printable_encode( $message )."\r\n\r\n";	  
			$multipart .= $message;					
		}
		else
		{
			$multipart = $message;
		}		
	
		foreach ($file_array as $file_img)
		{
			$file = '';			
			$fp = fopen($file_img["filepath"], "r"); 
			if ( $fp ) 
			{ 
				$content = fread($fp, filesize($file_img["filepath"])); 
				fclose($fp);
				$file .= "--".$boundary."\r\n"; 
				$file .= "Content-Type: application/octet-stream\r\n"; 
				$file .= "Content-Transfer-Encoding: base64\r\n"; 
				$file .= "Content-Disposition: attachment; filename=\"".$file_img["filename"]."\"\r\n\r\n"; 
				$file .= chunk_split(base64_encode($content))."\r\n"; 
				$multipart .= $file."--".$boundary."--\r\n";
			}			
		}
		return mail($email, $subject, $multipart, $headers, $reply); 
	}	
	
	// получает спиок всех знаений таблицы счетчики (counters)
	function GetCounters()
	{
		$data = array();
		$result = $this->db->ExecuteSql ("Select * From `counters`", false);
		if ($result)
		{
			$i=1;
			while ($row = $this->db->FetchArray ($result))  
			{
				$data["code".$i] = str_replace("&#39;","'",dec($row["code"]));
				$i++;
			}
			$this->db->FreeResult ($result);
		}
		return $data;
	}
	
	// определяет действие для массового изменение в базе данных
	function GetMas()
	{
		$this->GetToken();
		$mas_action = $this->GetGP("mas_action");
		switch ($mas_action)
		{
			case "active":
				$this->Mas_Activate(1);
			break;
			case "notactive":
				$this->Mas_Activate(0);
			break;
			case "del":
				$this->Mas_Del();
			break;
			case "spam":
				$this->Mas_Spam();
			break;
		}
		$this->Redirect ("/adm/".$this->table_name);
	}
	
	// получает строку через заятую из POST для массового сапроса в базу данных через IN
	function GetInSQLString()
	{
		$i = 0;
		$sqlin = "";
		while (isset($_POST['itemid'][$i]))
		{
			$sqlin .= (int)$_POST['itemid'][$i].",";
			$i++;
		}
		return $sqlin;
	}
	
	// Выполняет массовое изменение статуса записей в базе данных
	// is_active - 0 - сделать не активным, 1 - сделать активным
	function Mas_Activate($is_active = 0)
	{
		$sqlin = $this->GetInSQLString();
		if (!empty($sqlin))
		{
			$sqlin = rtrim($sqlin, ",");
			$sql = "UPDATE ".$this->table_name." SET is_active = '".$is_active."' WHERE {$this->primary_key} IN (".$sqlin.")";
			$this->db->ExecuteSql($sql);
		}
	}
	
	// Выполняет массовое удаление записей и базы данных
	function Mas_Del()
	{
		$sqlin = $this->GetInSQLString();
		if (!empty($sqlin))
		{
			$sqlin = rtrim($sqlin, ",");
			$sql = "DELETE FROM ".$this->table_name." WHERE {$this->primary_key} IN (".$sqlin.")";
			$this->db->ExecuteSql($sql);
		}
	}
	
	// Выполняет массовое удаление записей и базы данных
	function Mas_Spam()
	{
		$i = 0;
		$sqlin = "";
		while (isset($_POST['itemid'][$i]))
		{
			$sqlin .= (int)$_POST['itemid'][$i].",";			
			$sql = "SELECT ip FROM ".$this->table_name." WHERE ".$this->primary_key." = '".(int)$_POST['itemid'][$i]."'";
			$ip = $this->db->GetOne($sql);			
			if (!$this->Get_Spam($ip))
			{
				$ip = ip2long($ip);	
				$data = array (
					"start" => $ip,
					"end" => $ip
				);
				$sql = "Insert Into spam ".ArrayInInsertSQL ($data);
				$this->db->ExecuteSql($sql);
			}			
			$i++;
		}		
		if (!empty($sqlin))
		{
			$sqlin = rtrim($sqlin, ",");
			$sql = "DELETE FROM ".$this->table_name." WHERE {$this->primary_key} IN (".$sqlin.")";
			$this->db->ExecuteSql($sql);
		}
	}
	
	// записывает изменения в логи
	//$status - какое событие изменение, удаление и т. д.
	//$admin_pages - id модуля в админке
	//$name - используется при удалении записи, и авторизации записывается какой логин был введен при авторизации
	//$item_id - id записи с которой были манипуляции
	function history($status, $admin_pages, $name, $item_id)
	{
		$sql = "INSERT INTO log (admin_id, name, ip, news_date, status, admin_pages, item_id) VALUE ('".$_SESSION["A_ID"]."', '$name', '".$_SERVER['REMOTE_ADDR']."', '".time()."', '$status', '$admin_pages', '$item_id')";
		$this->db->ExecuteSql($sql);
	}
	
	// Получает кнопки вверх вниз сортировок записей для админки 
	// $order - текущее сотояние записи
	// $minIndex - минимальный индекс среди записей что бы кнопку вниз не выводить
	// $maxIndex - максимальный индекс среди записей что бы кнопку вверх не выводить
	// $id - id записи с которой проходят манипуляции
	function OrderLink($order, $minIndex, $maxIndex, $id)
	{
		$token = $this->GetSession("token", false);
		$orderLink = "<div style='display:table;'>";
		$orderLink .= ($order > $minIndex) ? "<div style='display:table-row;'><div style='display:table-cell;'><a href='/adm/".$this->table_name."/up?id=$id&token=$token' style=' padding: 0px' title=\"Вверх\"><i class=\"fa fa-chevron-up\"></i></a></div></div>" : "<div style='display:table-row;'><div style='display:table-cell;'></div></div>";
		$orderLink .= ($order < $maxIndex) ? "<div style='display:table-row;'><div style='display:table-cell;'><a href='/adm/".$this->table_name."/down?id=$id&token=$token' style='padding: 0px' title=\"Вниз\"><i class=\"fa fa-chevron-down\"></i></a></div></div>" : "<div style='display:table-row;'><div style='display:table-cell;'></div></div>";
		$orderLink .= "</div>";
		return $orderLink;
	}
	
	// Опубликовать в меню сайта модуль в админке
	 // title - заголовок, module - имя url модуля, name - название поля select
    function publish_module ($title, $module, $name = "menu_id")
    {
        $parent_id = $this->GetGP($name, -1);
               
        if ($parent_id > -1)
        {
            $order_index = $this->db->GetOne ("SELECT Max(order_index) FROM menus", 0)+1;
			$totla = $this->db->GetOne("SELECT Count(*) FROM menus WHERE url = '$module'", 0);
			if ($totla > 0)
			{
				$this->db->ExecuteSql ("UPDATE menus SET parent_id='$parent_id' WHERE url = '$module'");
			}
			else
			{
				$this->db->ExecuteSql ("Insert Into `menus` (name, title, head_title, news_date, parent_id, order_index, url, is_active) Values ('$title', '$title', '$title', '".time()."', '$parent_id', $order_index, '$module', '1')");
			}
        }
        else {
            $this->db->ExecuteSql ("Delete From `menus` Where url='$module'");
        }
    }
	
	// список тегов для продукции, статей и т. д., в виде select
	// $value - массив текущих значений, что бы сделать их активными
	// $module - модуль для которого выводить список категорий
	function GetTags($value, $module)
	{				
		$result = $this->db->ExecuteSql ("Select * From `tags`  Where is_active='1' and module='$module' Order by order_index asc");
		$toRet = "<select data-placeholder='Выберите теги' name='tags[]' id='tags' class='chosen-select' multiple> \r\n
		<option value=''></option> \r\n";
		if ($result)
		{			
			while ($row = $this->db->FetchArray ($result)) {
				$selected = (array_key_exists ($row['tag_id'], $value)) ? "selected" : "";
				$toRet .= "<option value='".$row['tag_id']."' $selected>".$row['name']."</option> \r\n";
			}
			$this->db->FreeResult  ($result);
		}
		return $toRet."</select>\r\n";;	
	}
	
	// список тегов для продукции, статей и т. д., в виде select
	// $value - массив текущих значений, что бы сделать их активными
	// $module - модуль для которого выводить список категорий
	function GetOptions($value, $module)
	{				
		$result = $this->db->ExecuteSql ("Select * From `additions`  Where is_active='1' and module='$module' Order by order_index asc");
		$toRet = "<select data-placeholder='Выберите опции' name='options[]' id='options' class='chosen-select' multiple> \r\n
		<option value=''></option> \r\n";
		if ($result)
		{			
			while ($row = $this->db->FetchArray ($result)) {
				$selected = (array_key_exists ($row['addition_id'], $value)) ? "selected" : "";
				$toRet .= "<option value='".$row['addition_id']."' $selected>".$row['name']."</option> \r\n";
			}
			$this->db->FreeResult  ($result);
		}
		return $toRet."</select>\r\n";;	
	}
	
	// список категорий для продукции, статей и т. д., в виде select
	// $value - текущее значение, что бы сделать его активным
	// $module - модуль для которого выводить список категорий
	function getGenre ($value = 0, $module = "products")
	{		
		$toRet = "<select name='category' id='category' class='chosen-select'> \r\n";
		$result = $this->db->ExecuteSql ("Select * From `category`  Where is_active='1' and module='$module' Order by order_index asc");
        while ($row = $this->db->FetchArray ($result)) {				
			$selected = ($row['category_id'] == $value) ? "selected" : "";
			$toRet .= "<option value='".$row['category_id']."' $selected>".$row['title']."</option>";
		}
		$this->db->FreeResult  ($result);
		return $toRet."</select>\r\n";		
	}
	
	// список для каких модулей можно добавлять категории для админки
	// $value - текущее значение, что бы сделать его активным
	function GetCategory ($value = "all", $target = false, $siteUrl = "") 
	{           		
		if ($target)
		{
			$toRet = "<select name='module' id='module' onchange=\"top.location=this.value\" class='select-search'>\r\n";			
			$selected = ($value == 'all') ? "selected" : "";
			$link = $siteUrl."?module=";
			$toRet .= "<option value='".$link."all' ".$selected.">Все</option>\r\n";
		}
		else
		{
			$toRet = "<select name='module' id='module'>\r\n";
			$link = "";
		}   
        $selected = ('products' == $value) ? "selected" : "";
        $toRet .= "<option value='".$link."products' $selected>".$this->db->GetAdminTitle("products")."</option>";
        $selected = ('articles' == $value) ? "selected" : "";
        $toRet .= "<option value='".$link."articles' $selected>".$this->db->GetAdminTitle("articles")."</option>";
        
        return $toRet."</select>\r\n";        
    } 
	
	// для поиска по сайту | ищет в строке совпадение и возврашает отсеченый результат с подсвеченым совпадением
	// $str - строка где ищем
	// $search - строка что ищем
	function Search ($str, $search)
	{
		$searchsmall = mb_strtolower($search, "utf-8");
		$strAnons = "";
		$count = 0;
		$arrText = explode(".", strip_tags($str));					
		$countTemp = mb_substr_count (mb_strtolower ($str, "utf-8"), $searchsmall, "utf-8");
		$count += $countTemp; 					
		if ($countTemp > 0)
		{
			for ($i=0; $i<count($arrText); $i++) {	
			   if (mb_substr_count (mb_strtolower ($arrText[$i], "utf-8"), $searchsmall, "utf-8") > 0)
			   {
				  $temp = str_replace($search, "<b>$search</b>",$arrText[$i]);
				  //$strAnons .= $arrText[$i]."...";	
				  $strAnons .=$temp."...";
				  break;
			   }                       
			}					
		}	
		return $strAnons;
	}
	
	// проверяет url каталогов, что бы не создавать дублей страниц
	// сомнительная функция но где то используется
	function Valid_Url_Short ($link)
	{
		$routes = explode('?', $_SERVER['REQUEST_URI']);
		if ("/".$link."/" == $routes[0]) 
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// проверяет url, что бы не создавать дублей страниц
	function Valid_Url ($link) 
	{
		$routes = explode('?', $_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes[0]);
		$url =  explode('.', $temp[count($temp)-1]);	
		$sql = "SELECT title FROM `menus` WHERE url = '".$link."/' and is_active='1'";		
		$row = $this->db->GetEntry($sql);
		if (("/".$link."/".$url[0].".html" == $routes[0]) && $row)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	// получает массив всех элементов каталога 
	//$is_active - true только активные
	public function get_array_catalog($is_active = true, $table = "catalogs", $primary_key = "catalog_id")
	{
		$where = ($is_active)?" WHERE is_active='1'":"";
		
		$sql = "SELECT name, url, ".$primary_key.", parent_id FROM ".$table." ".$where." ORDER BY order_index";
		$result = $this->db->ExecuteSql($sql);
		$array = array();
		while ($row = $this->db->FetchArray ($result)) 
		{
			$array[$row[$primary_key]]['title'] = dec($row['name']);
			$array[$row[$primary_key]]['url'] = $row['url'];
			$array[$row[$primary_key]]['target'] = "";
			$array[$row[$primary_key]]['menu_id'] = $row[$primary_key];
			$array[$row[$primary_key]]['parent_id'] = $row['parent_id'];
		}
		$this->db->FreeResult ($result);
		return $array;
	}
	
	// получает массив всех элементов меню 
	// $is_active - true только активные
	public function get_array_menu($is_active = true)
	{
		$where = ($is_active)?" WHERE is_active='1' and is_menu='1'":"";		
		$where.=" ORDER BY order_index";
		
		$sql = "SELECT name, url, menu_id, parent_id, target FROM `menus`".$where;
		$result = $this->db->ExecuteSql($sql);
		$array = array();
		if ($result) {
		while ($row = $this->db->FetchArray ($result)) 
		{
			$array[$row['menu_id']]['title'] = $row['name'];
			$array[$row['menu_id']]['url'] = $row['url'];
			$array[$row['menu_id']]['target'] = ($row['target'] == 1)?"target='_blank'":"";
			$array[$row['menu_id']]['menu_id'] = $row['menu_id'];
			$array[$row['menu_id']]['parent_id'] = $row['parent_id'];
		}
		$this->db->FreeResult ($result);
		}
		return $array;
	}
	
	// получает Згаловок, описание и ключевые слова
	// сомнительная функция
	function Get_Header($sql)
	{
		$row = $this->db->GetEntry($sql);		
		//echo $sql;
		if (!$row) {
			$this->error404();
		}
		$result = array (
			"title" =>$row["title"],
			"head_title" =>$row["head_title"],
			"description" =>$row["description"],
			"keywords" =>$row["keywords"],
			"text" => (isset($row["text"])?dec($row["text"]):""),
		);
		return $result;
	}
	
	// преобразовывает теги в текст
	// нужно перенести в файл функций
    public function enc ($value)
	{
		/*$search = array ("/&/", "/</", "/>/", "/'/");
		$replace = array ("&amp;", "&lt;", "&gt;", "&#039;");
		return preg_replace ($search, $replace, $value);*/
		return $this->db->RealEscapeString($value);
	}		
	
	// преоброзовывает текст в теги
	// нужно перенести в файл функций
	public function dec ($value)
    {
		$search = array ("/&amp;/", "/&lt;/", "/&gt;/", "/&#039;/");
        $replace = array ("&", "<", ">", "'");
        $value = preg_replace ($search, $replace, $value);
		return stripcslashes($value);
	}
	
	// считывает из масива GET или POST целое значение
	// $key - ключ по которуму ищем значение
	// $defValue - значение по умолчанию если ключа не нашлось
    function GetID ($key, $defValue = 0)
    {
        $toRet = $defValue;
        if (array_key_exists ($key, $_GET)) {
            $toRet = trim ($_GET [$key]);
        }
        elseif (array_key_exists ($key, $_POST)) {
            $toRet = trim ($_POST [$key]);
        }
        if (!is_numeric ($toRet)) $toRet = 0;
        return $toRet;
    }
	
	// считывает из масива GET или POST безопасные данный для записи в MySQL
	// $key - ключ по которуму ищем значение
	// $defValue - значение по умолчанию если ключа не нашлось
    public function GetGP_SQL ($key, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($key, $_POST)) $toRet = trim ($_POST [$key]);
		elseif (array_key_exists ($key, $_GET)) $toRet = trim ($_GET [$key]);                
        return $this->db->RealEscapeString($toRet);
    }	
	
	// считывает из масива GET или POST
	// $key - ключ по которуму ищем значение
	// $defValue - значение по умолчанию если ключа не нашлось
    public function GetGP ($key, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($key, $_POST)) $toRet = trim ($_POST [$key]);
		elseif (array_key_exists ($key, $_GET)) $toRet = trim ($_GET [$key]);        
        str_replace ($toRet, "<", "");
        str_replace ($toRet, ">", "");
		return $this->db->RealEscapeString($toRet);
        //return (get_magic_quotes_gpc ()) ? stripslashes ($toRet) : $toRet;
    }
	
	// считывает из масива GET
	// $key - ключ по которуму ищем значение
	// $defValue - значение по умолчанию если ключа не нашлось
    public function GetG ($key, $defValue = "")
    {
        $toRet = $defValue;
		if (array_key_exists ($key, $_GET)) $toRet = trim ($_GET [$key]);        
		return $this->db->RealEscapeString($toRet);
    }
	
	// считывает из масива POST
	// $key - ключ по которуму ищем значение
	// $defValue - значение по умолчанию если ключа не нашлось
    public function GetP ($key, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($key, $_POST)) $toRet = trim ($_POST [$key]);   
		return $this->db->RealEscapeString($toRet);
    }
	
	// валидация форм
	// нужно переработать функциюю
	function GetValidGP ($key, $name, $type = VALIDATE_NOT_EMPTY, $defValue = "")
    {
        $value = $this->GetGP_SQL($key, $defValue);

        switch ($type)
        {
            case VALIDATE_NOT_EMPTY:
                if ($value == "") {
                    $error = " - обязательно для заполнения";
                    $this->SetError ($key, " Поле '$name' $error");
                }
                break;

            case VALIDATE_USERNAME:
                if (preg_match ("/^[а-Яa-Z0-9_]+$/", $value) == 0) {
                    $error = "допустимо от 4 до 12 символов (только буквы и цифры).";
                    $this->SetError ($key, "'$name': $error");
                }
                break;
           
           case VALIDATE_SHORT_TITLE:
                if (preg_match ("/^[\w.*,-_]{4,20}\$/iu", $value) == 0) {
                    $error = "допустимо от 4 до 20 символов (буквы только латинские).";
                    $this->SetError ($key, "'$name': $error");
                }
                break;     
			// было раньше в проверке /^[\w]{4,12}\$/iu     /^[а-Яa-Z0-9]+$/
            case VALIDATE_PASSWORD:
                if (preg_match ("/^[\w]{8,16}\$/iu", $value) == 0) {
                    $error = "допустим от 8 до 16 символов";
                    $this->SetError ($key, "'$name': $error");
                }
                break;

            case VALIDATE_PASS_CONFIRM:
                if ($value == "" or $value != $name) {
                    $error = "введеные пароли не совпадают.";
                    $this->SetError ($key, $error);
                }
                break;

            case VALIDATE_EMAIL:
                $value = mb_strtolower($value);
				if (preg_match ("/^[-_\.0-9a-z]+@[-_\.0-9a-z]+\.+[a-z]{2,4}\$/iu", $value) == 0) {
                    $error = "Недопустимый формат адреса эл.почты.";
                    $this->SetError ($key, "$error");
                }
                break;

            case VALIDATE_INT_POSITIVE:
                if (!is_numeric ($value) or (preg_match ("/^\d+\$/iu", $value) == 0)) {
                    $error = "должно быть положительным целым числом.";
                    $this->SetError ($key, "'$name': $error");
                }
                break;

            case VALIDATE_FLOAT_POSITIVE:
                if (!is_numeric ($value) or (preg_match ("/^[\d]+\.+[\d]+\$/iu", $value) == 0)) {
                    $error = "должно быть положительным дробным числом. (Формат: 12.34)";
                    $this->SetError ($key, "'$name': $error");
                }
                break;

            case VALIDATE_CHECKBOX:
                if ($value == $defValue) {
                    $error = "вам необходимо отметить это поле.";
                    $this->SetError ($key, "'$name': $error");
                }
                break;

            case VALIDATE_NUMERIC_POSITIVE:
                if (!is_numeric ($value) Or $value <= 0) {
                    $error = "должно быть положительным числом.";
                    $this->SetError ($key, "'$name': $error");
                }
                break;

            case VALIDATE_LONG_WORD:
                if (preg_match ("/[^\s]{30,}/u", stripTags ($value)) == 1) {
                    $error = "содержит слишком длинное слово.";
                    $this->SetError ($key, "'$name': $error");
                }
                break;
			case VALIDATE_URL:
				 if (preg_match ("/^[a-z0-9-.]+$/", $value) == 0) 
				 {
                    $error = "допустимо только строчные буквы латинского алфавита, цифры и -";
                    $this->SetError ($key, "'$name': $error");
                }
				else
				{
					if (preg_match("/^[a-z0-9-]+\.+html$/", $value) == 0) 
					{
						$value .= ".html";
					}
				}
			break;
        }

        return (get_magic_quotes_gpc ()) ? stripslashes ($value) : $value;
    }
	
	// функция для капчи имеет смыл тоже перенести в файл функций
	function GenerateCode() 
	{      
		// минута 
			$minuts = substr(date("H"), 0 , 1);
		// месяц     
			$mouns = date("m");
		// день в году
			$year_day = date("z"); 
		//создаем строку
			$str = $minuts.$mouns.$year_day; 
		//дважды шифруем в md5
			$str = md5(md5($str)); 
		// извлекаем 6 символов, начиная с 2
			$str = substr($str, 2, 6); 
			
		#	Вам конечно же можно постваить другие значения, 
		#	так как, если взломщики узнают, каким именно 
		#	способом это все генерируется, то в защите не будет смысла.
		
		//Тщательно все перемешиваем!!!
			$array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
			srand ((float)microtime()*1000000);
			shuffle ($array_mix);
		return implode("", $array_mix);
	}
	
// функция для капчи имеет смыл тоже перенести в файл функций	
	function ChecCode($code) 
	{
		//удаляем пробелы
		$code = trim($code);
		$code1 = $this->GenerateCode();
		
		$array_mix = preg_split ('//', $code1, -1, PREG_SPLIT_NO_EMPTY);
		$m_code = preg_split ('//', $code, -1, PREG_SPLIT_NO_EMPTY);

		$result = array_intersect ($array_mix, $m_code);		
		if (strlen($code1)!=strlen($code)){return FALSE;}
		if (sizeof($result) == sizeof($array_mix)){return TRUE;}else{return FALSE;}
}
	
	// установка ошибки
	// $key - ключ ошибки
	// $text - значение ошибки
    public function SetError ($key, $text)
    {
        $this->errors['err_count']++;
        $this->errors[$key] = $text;
    }

    //считывание ошибки
	// $key - ключ ошибки
    public function GetError ($key)
    {
        return (array_key_exists ($key, $this->errors)) ? $this->errors[$key] : "";
    }
	
	 // Получает номер страницы и тип сортировки
	public function RestoreState ()
    {
        // текущая страница если была передана получаем ее, если нет берем из сессии
		$this->currentPage = (is_numeric($this->GetGP ("pg")) && $this->GetGP ("pg") >= 0) ? $this->GetGP ("pg") : $this->GetStateValue ("pg", 0);		
		// количество записей на странице
		$this->rowsPerPage = (($this->GetGP ("rpp") >= 1) && ($this->GetGP ("rpp") <= $this->rowsOptions[count($this->rowsOptions)-1])) ? $this->GetGP ("rpp") :$this->GetStateValue ("rpp", $this->rowsPerPage);		
		$order = $this->GetGP ("order", "");
		// поле сортировки
		$this->orderBy = (array_key_exists ($this->GetGP ("order"), $this->orderType)) ? $this->GetGP ("order") : $this->GetStateValue ("order", $this->orderDefault);		
		// тип сортировки
		$this->orderDir = (($this->GetGP ("dir") == "desc") || $this->GetGP ("dir") == "asc") ? $this->GetGP ("dir") :  $this->GetStateValue ("dir", $this->orderDirDefault);
		// сохраняем в сессию
        $this->SaveState ();

    }
	
	// сохраняет в сессию номер страницы и тип сортировки
    function SaveState ()
    {        
		$_SESSION[$this->module]['pg'] = $this->currentPage;
        $_SESSION[$this->module]['rpp'] = $this->rowsPerPage;
        $_SESSION[$this->module]['order'] = $this->orderBy;
        $_SESSION[$this->module]['dir'] = $this->orderDir;
    }
	
	// сохраняет в сессию значение
	// $key - ключ
	// $value - значение
	function SaveStateValue ($key, $value)
    {
        $_SESSION[$this->module][$key] = $value;
    }
	
	// считывает из ссесиииномер страницы, тип сортировки и т. д.
	// $key - ключ
	// $defValue - значение по умолчанию если ключа не нашлось
    function GetStateValue ($key, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($this->module, $_SESSION)) {
            if (array_key_exists ($key, $_SESSION[$this->module])) {
                $toRet = trim ($_SESSION [$this->module][$key]);
            }
        }
        return $toRet;
    }
	
	// считывает данные из ссесиии 
	// $str - ключ
	// $defValue - значение по умолчанию если ключа не нашлось
	function GetSession ($str, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($str, $_SESSION)) $toRet = trim ($_SESSION [$str]);
        return $toRet;
    }
	
	// считывает данные из Куки 
	// $str - ключ
	// $defValue - значение по умолчанию если ключа не нашлось
	function GetCookie ($str, $defValue = "")
    {
        $toRet = $defValue;
        if (array_key_exists ($str, $_COOKIE)) $toRet = trim ($_COOKIE [$str]);
        return $toRet;
    }
	
	// получает список страниц для сайта
	function Pages_GetLinks_Site ($totalRows, $link)
    {
		$divider = " ";
		$toRet = "";
        
        $pageNo = $this->currentPage - 1;
        $prev = "<a href='".$link."pg=$pageNo' title='Предыдущая страница'>&larr;</a>";
        $pageNo = $this->currentPage + 1;
        $next = "<a href='".$link."pg=$pageNo' title='Следующая страница'>&rarr;</a>";
        
        $totalPages = ceil ($totalRows / $this->rowsPerPage);        
        
        if ($totalPages != 1)
        {
            $toRet = "<div class='nav_cmts'>";
			if ($this->currentPage > 0) $toRet .= "$divider$prev";
			if ($totalPages <= 12)
            {
                for ($i = 0; $i < $totalPages; $i++)
                {
                    $start = $i * $this->rowsPerPage + 1;
                    $end = $start + $this->rowsPerPage - 1;
                    if ($end > $totalRows) $end = $totalRows;
                    $pageNo = $i + 1;
                    if ($i == $this->currentPage)
                        $toRet .= "$divider<span>$pageNo</span>";
                    else
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                }
            }
            else {
               if ($this->currentPage > 4 and $this->currentPage < $totalPages-5)
               {
                  
                  $toRet .= "$divider<a href='".$link."pg=0' title='Страница №1'>1</a>";
                  $toRet .= "$divider<a href='".$link."pg=1' title='Страница №2'>2</a>";
                  if (ceil($this->currentPage - 2) > 2 and ceil($this->currentPage + 2) < $totalPages - 2) {
                    $toRet .= "$divider<span>...</span>";
                    for ($i = ceil($this->currentPage - 2); $i < ceil($this->currentPage + 3); $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                    $toRet .= "$divider<span>...</span>";
                    
                    for ($i = $totalPages-2; $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                  }
               }
               else if ($this->currentPage < 5) {
                  for ($i = 0; $i < ceil($this->currentPage + 3); $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                    $toRet .= "$divider<span>...</span>";
                    
                    for ($i = $totalPages-2; $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
               }
               else if ($this->currentPage > $totalPages-6) {
                  $toRet .= "$divider<a href='".$link."pg=0' title='Страница №1'>1</a>";
                  $toRet .= "$divider<a href='".$link."pg=1' title='Страница №2'>2</a>";
                  $toRet .= "$divider<span>...</span>";
                  for ($i = ceil($this->currentPage - 2); $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
               }
            }
            
            if ($this->currentPage < $totalPages - 1) $toRet .= "$divider$next";            
        
			$toRet .= "</div>";
			
			$toRet .= "<div class='nav_opts'>Показывать&nbsp;по&nbsp;&nbsp;";
			foreach ($this->rowsOptions as $val) {
				$toRet .= ($val == $this->rowsPerPage) ? "<span>{$val}</span>$divider" : "<a href='{$link}rpp=$val&amp;pg=0' title=''>$val</a>$divider";
			}
			$toRet .= "</div>";
		}
        return $toRet;
	}
	// для админки тоже самое что и для сайта
	function Pages_GetLinks ($totalRows, $link)
    {
		$divider = " ";
		$toRet = "";
        
        $pageNo = $this->currentPage - 1;
        $prev = "<a href='".$link."pg=$pageNo' title='Предыдущая страница'><i class='fa fa-long-arrow-left'></i></a>";
        $pageNo = $this->currentPage + 1;
        $next = "<a href='".$link."pg=$pageNo' title='Следующая страница'><i class='fa fa-long-arrow-right'></i></a>";
        
        $totalPages = ceil ($totalRows / $this->rowsPerPage);        
        
        if ($totalPages != 1)
        {
            $toRet = "<div class='col-md-8'><div class='nav_cmts'>";
			if ($this->currentPage > 0) $toRet .= "$divider$prev";
			if ($totalPages <= 12)
            {
                for ($i = 0; $i < $totalPages; $i++)
                {
                    $start = $i * $this->rowsPerPage + 1;
                    $end = $start + $this->rowsPerPage - 1;
                    if ($end > $totalRows) $end = $totalRows;
                    $pageNo = $i + 1;
                    if ($i == $this->currentPage)
                        $toRet .= "$divider<span>$pageNo</span>";
                    else
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                }
            }
            else {
               if ($this->currentPage > 4 and $this->currentPage < $totalPages-5)
               {
                  
                  $toRet .= "$divider<a href='".$link."pg=0' title='Страница №1'>1</a>";
                  $toRet .= "$divider<a href='".$link."pg=1' title='Страница №2'>2</a>";
                  if (ceil($this->currentPage - 2) > 2 and ceil($this->currentPage + 2) < $totalPages - 2) {
                    $toRet .= "$divider<span>...</span>";
                    for ($i = ceil($this->currentPage - 2); $i < ceil($this->currentPage + 3); $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                    $toRet .= "$divider<span>...</span>";
                    
                    for ($i = $totalPages-2; $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                  }
               }
               else if ($this->currentPage < 5) {
                  for ($i = 0; $i < ceil($this->currentPage + 3); $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
                    $toRet .= "$divider<span>...</span>";
                    
                    for ($i = $totalPages-2; $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
               }
               else if ($this->currentPage > $totalPages-6) {
                  $toRet .= "$divider<a href='".$link."pg=0' title='Страница №1'>1</a>";
                  $toRet .= "$divider<a href='".$link."pg=1' title='Страница №2'>2</a>";
                  $toRet .= "$divider<span>...</span>";
                  for ($i = ceil($this->currentPage - 2); $i < $totalPages; $i++)
                    {
                        $pageNo = $i + 1;
                        if ($i == $this->currentPage)
                            $toRet .= "$divider<span>$pageNo</span>";
                        else
                            $toRet .= "$divider<a href='".$link."pg=$i' title='Страница №$pageNo'>$pageNo</a>";
                    }
               }
            }
            
            if ($this->currentPage < $totalPages - 1) $toRet .= "$divider$next";            
        
			$toRet .= "</div></div>";
			
			$toRet .= "<div class='col-md-4'><div class='nav_opts'>Показывать&nbsp;по&nbsp;";
			foreach ($this->rowsOptions as $val) {
				$toRet .= ($val == $this->rowsPerPage) ? "<span>{$val}</span>$divider" : "<a href='{$link}rpp=$val&amp;pg=0' title=''>$val</a>$divider";
			}
			$toRet .= "</div></div>";
		}
        return $toRet;
	}
	
	// получает список страниц для админки вывоит все страницы не очень удобно когда много записей
	/*function Pages_GetLinks ($totalRows, $link)
    {
        $divider = "&nbsp;&nbsp;";
        $left = "[";
        $right = "]";

        $toRet = "<table width='100%' cellspacing='0' cellpadding='0'><tr>";

        $toRet .= "<td valign='top' align='left' class='page_records'>Записей на странице. &nbsp;";
        foreach ($this->rowsOptions as $val) {
            $toRet .= ($val == $this->rowsPerPage) ? $val.$divider : "<a href='{$link}rpp=$val&pg=0'>$val</a>$divider";
        }
        $toRet .= "</td>";

        $toRet .= "<td valign='top' align='right' class='page_records'>";
        $totalPages = ceil ($totalRows / $this->rowsPerPage);
        if ($totalPages > 1)
        {
            for ($i = 0; $i < $totalPages; $i++)
            {
                $start = $i * $this->rowsPerPage + 1;
                $end = $start + $this->rowsPerPage - 1;
                if ($end > $totalRows) $end = $totalRows;
                $pageNo = $left."$start-$end".$right;
                if ($i == $this->currentPage)
                    $toRet .= $divider.$pageNo;
                else
                    $toRet .= "$divider<a href='".$link."pg=$i'>$pageNo</a>";
            }
        }
        $toRet .= "</td>";

        return $toRet."</tr></table>";
	}*/
	
	// возвращает текущую позицию для базы данных
    public function Pages_GetLimits ()
    {        
		$start = $this->currentPage * $this->rowsPerPage;
        $toRet = " LIMIT $start, {$this->rowsPerPage} ";

        return $toRet;
    }	
	
	// добавляет комментарий
	// $module - название модуля к которому добавляется комментрий
	function add_comment ($module)
    {
		if ($this->is_user)
		{			
			 $name = $_COOKIE["U_LOGIN"];				 
		}
		else
		{
			$name = $this->enc ($this->GetValidGP ("name", "Ваше имя", VALIDATE_NOT_EMPTY));
			/*@@@@@@@@@@@@@@@@@@-- Begin: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/
			$code = $this->GetGP("keystring");
			$flag = $this->ChecCode($code);
			if (!$flag) {$this->SetError("capcha", "Не верная последовательность");}      	
			/*@@@@@@@@@@@@@@@@@@-- END: kcaptcha --@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*/			
		}
		$item_id = $this->GetID ("item_id");
        $comment = strip_tags($this->GetValidGP ("comment", "Комментарий", VALIDATE_NOT_EMPTY));
         
        if ($this->errors['err_count'] > 0) {
			return false;
        }
        else {
			$user_id = $this->GetCookie("id", 1);			
			// если есть ссылка в коммнтарии делаем её не активной
			if ((stristr($comment, '//')) or ($this->Get_Spam($_SERVER['REMOTE_ADDR'])))
			{
				$is_ative = 0;
			}
			else
			{
				$is_ative = 1;
			}
			$comment = strip_tags($comment);
            $this->db->ExecuteSql ("Insert Into `comments` (parent_id, module, name, news_date, comment, is_active, new, user_id, ip) Values ('$item_id', '$module', '$name', '".time()."', '$comment', '$is_ative', '1', '$user_id', '".$_SERVER['REMOTE_ADDR']."')");
			return true;
        }
   }
	
	// возврщает true если ip адрес есть в спам базе, false в противном случае
	// $ip - ip пользователя 
	function Get_Spam($ip)
	{
		$ip = ip2long($ip);
		$sql = "SELECT Count(*) FROM spam WHERE ($ip >= start) and ($ip <= end)";
		$total = $this->db->GetOne($sql, 0);
		if ($total > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	// формирует массив вывода формы комментарие для авторизированных и нет пользователей
	// $id - id записи к которой добавляетя комментарий
	function GetFormComment($id)
   {
	   $comment = $this->GetGP("comment");
		if ($this->is_user)
		{
			$name = $_COOKIE['U_LOGIN'];
			$disabled = "disabled";
			$capcha = "";
		}
		else 
		{
			$name = $this->GetGP("name");
			$disabled="";
			$capcha = "<script type='text/javascript'>
								function refreshcapcha() {
									document.getElementById('capcha-image').src='{$this->siteUrl}capcha/capcha.php?rid=' + Math.random();
								}
							</script>				

							<a href='javascript:void(0);' onclick='refreshcapcha();'><img title='нажмите чтобы изменить изображение' src='{$this->siteUrl}capcha/capcha.php' id='capcha-image' alt='нажмите чтобы изменить изображение' /></a><br />
                                 <input type='text' name='keystring' value='' class='form keywidth' placeholder='Вы не робот?'  required>";
		}
	   
	   $data = array (
			"item_id" => $id,	
			"name" => "<input type='text' name='name' value='$name' size='50' class='form namewidth' placeholder='Ваше имя или ник' $disabled required>",
			"name_error" => $this->getError("name"),
			"comment" => "<textarea name='comment' cols='49' rows='5' class='formarea' placeholder='Текст комментария' required>$comment</textarea>",
			"comment_error" => $this->getError("comment"),
			"capcha" => $capcha,
			"capcha_error" => $this->getError("capcha"),
			"action" => "asc",
		);
	   return $data;
   }
	
	// проверяет авторизирован ли пользователь
	function CheckLogin ()
	{
		if (isset($_COOKIE['id']) and isset($_COOKIE['hash']))
		{
			 $hash = $this->db->GetOne ("SELECT hash FROM users WHERE user_id = '".intval($_COOKIE['id'])."'");
			 if ($_COOKIE['hash'] == $hash)
			 {
				 return true;				 
			 }
			 else
			 {
				return false;
			 }
		}
		else
		{
			return false;
		}	   
   }
	
	// проверяем значение token если совпадает с сессией true иначе false
	public function GetToken()
	{
		$token = $this->GetGP("token", false);
		if ($token)
		{
			if ($token == $this->GetSession("token", 1))
			{
				return true;
			}
			else
			{
				$this->Redirect("/adm/login/logout");
			}
		}
		else
		{
			$this->Redirect("/adm/login/logout");
		}
	}
	
	// разрешен ли доступ пользователю к этому разделу
	// $value - название модуля
	public function Get_Access($value)
	{
		$sql = "SELECT pages FROM `admins` WHERE admin_id = '".$_SESSION["A_ID"]."'";		
		$temp = $this->db->GetOne($sql, "");
		if (substr_count($temp, $value) > 0) 
		{
			return true;
		}
		else
		{				
			$this->Redirect($this->siteUrl."adm/login");
			return false;			
		}
		
	}
	// формирует ссылки сортировки
	// $url - ссылка к которой нужно добавить параметры
	// $field - поле по которому сортировать
	// $title - название для ссылки
	function Header_GetSortLink ($url, $field, $title = "")
    {		
		if ($field == "order_index")
		{
			if ($field == $this->orderBy) 
			{
				if ($this->orderDir == "asc") 
				{
				$dir = "desc";
				$src = "<i class=\"fa fa-sort-amount-asc\"></i>";
				
				}
				else
				{
					$dir = "asc";
					$src = "<i class=\"fa fa-sort-amount-desc\"></i>";
				}
				$toRet = "<a href='".$url."order=$field&dir=$dir' class='a_text'>$src</a>";
			}
			else
			{
				$dir = $this->orderDirDefault;
				$toRet = "<a href='".$url."order=$field&dir=$dir' class='a_text'><i class=\"fa fa-chevron-down\"></i></a>";
			}
		}
		elseif ($field == "is_active") 
		{
			if ($field == $this->orderBy) 
			{
				if ($this->orderDir == "asc") 
				{
					$dir = "desc";
					$src = "<i class=\"fa fa-times\"></i>";
					$class="times";
				}
				else
				{
					$dir = "asc";
					$src = "<i class=\"fa fa-check\"></i>";
					$class="check";
				}				
				$toRet = "<a class=\"$class\" href='".$url."order=$field&dir=$dir' class='a_text'>$src</a>";
			}
			else
			{
				$dir = $this->orderDirDefault;
				$toRet = "<a class=\"check\" href='".$url."order=$field&dir=$dir' class='a_text'><i class=\"fa fa-check\"></i></a>";
			}
			
		}
		else
		{
			if ($title == "") $title = $field;
			
			if ($field == $this->orderBy)
			{
				$dir = ($this->orderDir == "asc") ? "desc" : "asc";
				$toRet = "<a href='".$url."order=$field&dir=$dir' class='a_text'><b>$title</b></a>";
				$type_order = ($this->orderDir == "desc")?"<i class=\"fa fa-sort-amount-desc\" style=\"font-size: 14px;\"></i>":"<i class=\"fa fa-sort-amount-asc\" style=\"font-size: 14px;\"></i>";
				$toRet .= " ".$type_order;
			}
			else
			{
				$dir = $this->orderDirDefault;
				$toRet = "<a href='".$url."order=$field&dir=$dir' class='a_text'><b>$title</b></a>";
			}
		}
        return $toRet;
    }
	
	// формирует месяца
	// $value - текущее значение
	// $name - имя для select
	// $straif - отступ от текущего месяца, при выводе по умолчанию, на пример +1 месяц от текущего (возможно не коректно будетработать в конце года)
	function getMonthSelect ($value = "", $name = "dateMonth", $straif = 0)
	{
		if ($value == "" Or $value == 0) $value = date ("m")+$straif;
		if ($value > 12) $value = $value-12;
		if ($value < 1) $value = $value+12;
		$toRet = "<select name='$name'>";
		for ($i=1; $i <= 12; $i++)
		{
			if ($value == $i) $check = "selected"; else $check = "";
			$toRet .= "<option value='$i' $check>{$this->months[$i]}</option>";
		}
		return $toRet."</select>";
	}

	// формирует года
	// $value - текущий год
	// $name - имя для select
	// $table - таблица из которой нужно получить минимальный год
	// $field - поле в таблице с датой
	// $start - год с которго нужно начинать от счет
	// $end - +- сколько лет от текущей даты
	function getYearSelect ($value = "", $name = "dateYear", $table = "", $field = "", $start = "", $end = 3)
	{
		$toRet = "<select name='$name'>";
		if ($value == "" Or $value == 0) $value = date ("Y");
		if ($start == "")
		{
			$start = date("Y") - $end;
			if ($value < $start) $start = $value - 1;
			if ($table != "" And $field != "")
			{
				$start = $this->db->GetOne ("Select Min($field) From $table", date ("Y")-5);
				$start = date ("Y", $start);
			}
		}

		for ($i = $start; $i <= (date ("Y")+$end); $i++)
		{
			if ($value == $i) $check = "selected"; else $check = "";
			$toRet .= "<option value='$i' $check> $i </option>";
		}

		return $toRet."</select>";
	}

	// формирует дни
	// $value - текущий день
	// $name - имя для select
	function getDaySelect ($value = "", $name = "dateDay")
	{
		if ($value == "" Or $value == 0) $value = date ("d");
		$toRet = "<select name='$name'>";

		for ($i = 1; $i < 32; $i++)
		{
			if ($value == $i) $check = "selected"; else $check = "";
			if (strlen ($i) == 1) $i = "0".$i;
			$toRet .= "<option value='$i' $check> $i </option>";
		}

		return $toRet."</select>";
	}
	
	// формирует часы
	// $value - текущий час
	// $name - имя для select
	function getHourSelect ($value = "", $name = "dateHour")
	{
		if ($value == "" Or $value == -1) $value = date ("h");
		$toRet = "<select name='$name' $class>";

		for ($i = 0; $i < 24; $i++)
		{
			if ($value == $i) $check = "selected"; else $check = "";
			if (strlen ($i) == 1) $i = "0".$i;
			$toRet .= "<option value='$i' $check> $i </option>";
		}
		return $toRet."</select>";
	}
	// формирует минуты
	// $value - текущая минута
	// $name - имя для select
	function getMinuteSelect ($value = "", $name = "dateMinute")
	{
		if ($value == "" Or $value == -1) $value = date ("i");
		$toRet = "<select name='$name' $class>";

		for ($i = 0; $i < 60; $i++)
		{
			if ($value == $i) $check = "selected"; else $check = "";
			if (strlen ($i) == 1) $i = "0".$i;
			$toRet .= "<option value='$i' $check> $i </option>";
		}

		return $toRet."</select>";
	}	
	
	public function GetAdminTitle($table_name, $def = "")
	{
		include('admin_pages.php');
		if (isset($ADMIN_PAGES[$table_name]))
		{
			return $ADMIN_PAGES[$table_name]["icon"]." ".$ADMIN_PAGES[$table_name]["title"];
		}
		else
		{
			return $def;
		}
	}
	
	function Get_Pages($srt = "") 
	{
		include('admin_pages.php');
		foreach ($ADMIN_PAGES as $key => $value)
		{
			$data[] = array (
				"check" => (substr_count($srt, $key) > 0)?"checked":"",
				"title" => $value['title'],
				"keyname" => $key,
			);
		}
		return $data;
	}
	
	function GetAccessAdminString()
	{
		$res="";		
		include('admin_pages.php');
		foreach ($ADMIN_PAGES as $key => $value)
		{
			$page = $this->GetGP($key, 0);
			if ($page > 0)
			{
				$res .=",".$key;
			}
		}		
		return $res;
	}
	
	// шапка для админки новая
	public function header_adm()
	{
		include_once('admin_pages.php');
		$sql = "SELECT pages FROM `admins` WHERE admin_id = '".$_SESSION["A_ID"]."'";				
		$temp = $this->db->GetOne($sql, "");
		$res="<ul class='sidebar-menu'>";
		if ($temp != "") 
		{
			$mas = explode(',', $temp);				
			foreach ($ADMIN_PAGES as $key => $value)
			{
				if (array_search($key, $mas))
				{					
					$title = $value['title'];
					// выводим количество новых записей если таковые есть
					$sql = "SELECT Count(*) FROM $key WHERE new='1'";
					$total = $this->db->GetOne($sql);
					$new =($total > 0)?"<span class='pull-right-container'><span class='label label-danger pull-right'>$total</span></span>":"";
					$class=($this->module == "adm".$key)?"class='active'":"";
					//$res.="<li class='topmenu'><a href='".$this->siteUrl."adm/".$key."/' ".$class."><img src='/img/icons/".$row['iconame']."' alt='$title' />$new"." ".$title."</a>";
					if (empty($value['parent']))
					{
						$res.="<li $class><a href='".$this->siteUrl."adm/".$key."/'>".$value['icon']." <span>".$title."</span>".$new."</a></li>";
					}
				}
			}	
		}
		//$res.="<li class='topmenu'><a href='/adm/login/logout'><img src='/img/icons/exit.png' alt='Выход' /> Выход</a></li>";
		$res.="</ul>";		
		return $res;
	}
	
	// делает запись активной/неактивной
	// $name_id - id записи
	function Activate($name_id)
	{
		$id = $this->GetGP ("id");
		$count = $this->db->GetOne ("SELECT Count(*) FROM ".$this->table_name." WHERE $name_id='$id'");
        if ($count > 0) 
		{
			$this->db->ExecuteSql ("UPDATE ".$this->table_name." SET is_active=1-is_active WHERE $name_id='$id'");
		}
		$this->Redirect ("/adm/".$this->table_name."/");
	}
	
	// устунавливает флаг новой записи в false (становится почитанной)
	// $id - id записи
	function FlagNewFalse($id)
	{
		$this->db->ExecuteSql ("UPDATE ".$this->table_name." SET new='0' WHERE ".$this->primary_key."='$id'");
	}
	
	// получает код для редактора, можно подумать в реализации не скольких редакторов, а также отключения редактора
	// $text - поле textrea для которого выводить редактор
	public function editor($text = "text") 
	{
		global $SETTING;
		if ($SETTING["editor"]["value"] == 1)
		return "<script type=\"text/javascript\">
				var editor = CKEDITOR.replace( '".$text."' );
				CKFinder.setupCKEditor( editor, '/ckfinder/');				CKEDITOR.config.protectedSource.push(/<(script)[^>]*>.*<\/script>/ig);				CKEDITOR.config.protectedSource.push(/<(video)[^>]*>.*<\/video>/ig);
			</script>";
		else
			return "";
	}
	
	// загрузка картини
	// $id - записи для назваие картинки (на самом деле не нужный параметр, но удобно потом отслеживать для какой записи залита картинка)
	// $xsize - размер картинки по горизонтали
	// $ysize - размер картинки по вертикали
	// $filename - поле из которго загружать файл
	// $watermark - флаг установки водного знака на картинку
	function ResizeAndGetFilename ($id, $xsize, $ysize, $filename = "filename", $watermark = true)
    {
        $physical_path = $_SERVER['DOCUMENT_ROOT'];		
        if (array_key_exists ($filename, $_FILES) and $_FILES[$filename]['error'] < 3)
        {            
			$tmp_name = $_FILES[$filename]['tmp_name'];			
            if (is_uploaded_file ($tmp_name))
            {
                if (list ($width, $height, $type, $attr) = getimagesize($tmp_name))
                {
                    if ($type < 1 or $type > 3) return false;   // Not gif, jpeg or png

                    $newname = $id."_".$this->getUnID (5);
                    $extension = $this->imageTypeArray[$type];
                    $new_full_name = $newname.".".$extension;
					if (!file_exists($physical_path.$this->path_img))
					{
						mkdir($physical_path.$this->path_img, 0755);
					}
                    if (!file_exists ($physical_path.$this->path_img.$new_full_name))
                    {
                        move_uploaded_file ($tmp_name, $physical_path.$this->path_img.$new_full_name);
						// уменьшаем большое изображение до приемлемых размеров 1024px
						$this->resizePhoto ($physical_path.$this->path_img.$new_full_name, 1280, 1280, true);
						if ($watermark)
						{
							if (file_exists($physical_path."/img/watermark.png"))
								watermark ($physical_path.$this->path_img.$new_full_name, $physical_path."/img/watermark.png");
						}
                        @chmod ($physical_path.$this->path_img.$new_full_name, 0644);

                        // Small size - block picture
                        $new_copy_name = $physical_path.$this->path_img.$newname."_small.".$extension;
                        copy ($physical_path.$this->path_img.$new_full_name, $new_copy_name);                        
                        $this->resizePhoto ($new_copy_name, $xsize, $ysize);

                        return $new_full_name;
                    }
                }
            }
        }
        return false;
    }
	
	// удаляет запись и добавляет в спам
	function spam()
	{					
		$this->GetToken();
		$id = $this->GetGP ("id");
		$sql = "SELECT name, ip FROM ".$this->table_name." WHERE ".$this->primary_key." = '".$id."'";
		$row = $this->db->GetEntry($sql);
		if (!$this->Get_Spam($row["ip"]))
		{
			$data = array (
				"start" => ip2long($row["ip"]),
				"end" => ip2long($row["ip"])
			);
			$sql = "Insert Into spam ".ArrayInInsertSQL ($data);
			$this->db->ExecuteSql($sql);
		}
		$name = $row["name"];
		
		$this->history("Спам", $this->table_name, $name, $id);
		$this->delElement($this->primary_key);
		$this->Redirect ("/adm/".$this->table_name);
	}
	
	// удаляет запись из базы данных
	// $name_id - id записи
	function delElement ($name_id)
	{
		$id = $this->GetGP ("id");
		$count = $this->db->GetOne ("SELECT Count(*) FROM ".$this->table_name." WHERE $name_id=$id");
        if ($count > 0) 
		{
			$this->db->ExecuteSql ("DELETE FROM ".$this->table_name." WHERE $name_id=$id");
		}
        $this->Redirect ("/adm/".$this->table_name);
	}
	
	// удаление картинки
	// $name_id - id записи у которй удаляем картику
	// $filename - поле с картинкой
	function delete_image ($name_id, $filename = "filename")
    {        		
        $id = $this->GetGP ("id");
		$pathSite = $_SERVER['DOCUMENT_ROOT'];
        $logoName = $this->db->GetOne ("SELECT $filename FROM ".$this->table_name." WHERE $name_id='$id'");	
        if ($logoName != "") {
            $extension = substr($logoName, -3);
            $fullName = $pathSite.$this->path_img.$logoName;
            if ($fullName != "" and file_exists ($fullName)) unlink ($fullName);
            
            $photo_name = substr($logoName, 0, -4)."_small.".$extension;
            $pathToImage = $pathSite.$this->path_img.$photo_name;
            if (file_exists ($pathToImage)) unlink ($pathToImage);

            $this->db->ExecuteSql ("UPDATE ".$this->table_name." SET $filename='' WHERE $name_id='$id'");
        }
    }	
	
	//проверяет коректность текущей страницы и делает редирект на первую
	// $total количество записей
	public function Get_Valid_Page($total)
	{		
		if ($this->currentPage > (($total-1)/$this->rowsPerPage))
		{
			$parse = parse_url($_SERVER['REQUEST_URI']);	
			$this->Redirect($parse['path']."?pg=0");
		}
	}
	
	//уменьшение картинки (функцию надо переработать png файлы не коректно обрабатывает)
	// $image - физический путь к картинке
	// $max_width - максимальная ширина
	// $max_height - максимальная высота
	// $proportion - true сохранять пропорции изображения
	public function resizePhoto ($image, $max_width, $max_height, $proportion = true)
	{
		if (list ($width, $height, $type, $attr) = getimagesize($image))
		{
			if ($max_width < $width or $max_height < $height)
			{
				 $image_create = "";
				  switch ($type)
				  {
					  case 1:     // GIF
						  $image_create = "imagecreatefromgif";
						  $image_save = "imagegif";
						  break;
					  case 2:     // JPEG
						  $image_create = "imagecreatefromjpeg";
						  $image_save = "imagejpeg";
						  break;
					  case 3:     // PNG
						  $image_create = "imagecreatefrompng";
						  $image_save = "imagepng";
						  break;
				  }
		  
				  if ($image_create != "")
				  {
					  $im = $image_create ($image);
					  if ($im)
					  {
						  /*$w = $max_width;
						  $h = $max_height;*/
						  
						  $k1 = $max_width / imagesx ($im);
						  $k2 = $max_height / imagesy ($im);
						 
						  
						  if ($proportion)
						  {
							   $k = ($k1 < $k2) ? $k1 : $k2;
						  
							  $w = intval (imagesx ($im) * $k);
							  $h = intval (imagesy ($im) * $k);
							  
							  $im1 = imagecreatetruecolor ($w, $h);
							  
							  imagealphablending($im1, false);
							  imagesavealpha($im1, true);	
							  
							  imagecopyresampled ($im1, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
						  }
						  else
						  {
							  $im1 = imagecreatetruecolor ($max_width, $max_height);
							  
							  imagealphablending($im1, false);
							  imagesavealpha($im1, true);	
							  
							  if ($k1 < $k2)
							  {
								  $k = $k1/$k2;
								  // получаем отступ отлевого края
								  $w_temp = intval((imagesx ($im) - $k*imagesx ($im))/2);
								  imagecopyresampled ($im1, $im, 0, 0,  $w_temp, 0, $max_width, $max_height,  intval($k*imagesx($im)), imagesy($im));
							  }
							  else
							  {
								  $k = $k2/$k1;
								  // получаем отступ отлевого края
								  $h_temp = intval((imagesy ($im) - $k*imagesy ($im))/2);
								  imagecopyresampled ($im1, $im, 0, 0, 0,  $h_temp, $max_width, $max_height, imagesx($im), intval($k*imagesy($im)));

							  }							  													  
						  }
		  
						  switch ($type)
						  {
							  case 1:     // GIF
								  $image_save ($im1, $image);
								  break;
							  case 2:     // JPEG
								  $image_save ($im1, $image, 95);
								  break;
							  case 3:     // PNG
								  $image_save ($im1, $image);
								  break;
						  }
		  
						  imagedestroy ($im);
						  imagedestroy ($im1);
		  
						  return true;
					  }
				  }			
			}
			
		}
		return false;
	}
	
	// используется для генерации случайного числа
	function make_seed ()
	{
		list ($usec, $sec) = explode (' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}
	
	//генерирует случайное значение
	function getUnID ($length)
	{
		$toRet = "";
		$symbols = array ();
		for ($i = 0; $i < 26; $i++)
			$symbols[] = chr (97 + $i);
		for ($i = 0; $i < 10; $i++)
			$symbols[] = chr (48 + $i);

		srand ($this->make_seed());
		for ($i = 0; $i < $length; $i++)
			$toRet .= $symbols[rand (0, 35)];
		return $toRet;
	}
	
	// вывод ошибки 404
	public function error404 () 
	{		
		include_once "application/controllers/controller_404.php";		
		// создаем контроллер
		$controller = new Controller_404;
		// вызываем действие контроллера
		$controller->action_index();
		exit();
	}
	
	// редирект
	// $targetURL - страница куда происходит редирект
	public function Redirect ($targetURL)
    {
		if (($targetURL == "404") or ($targetURL == "/404"))
		{
			$this->error404();
		}
		else
		{
			header("HTTP/1.1 301 Moved Permanently");
			header ("Location: $targetURL");
			exit;
		}
    }
}