<?php
include_once('config.php');
include_once('function.php');
include_once('setting.php');
include_once('db.php');
$db = new DB ();
class Model 
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
		$this->cidmenu = $this->cid = (int)$this->db->GetOne("SELECT menu_id FROM menus WHERE url = '$url'", 0);
		// исключаем дополнительный запрос в БД если УРЛ один и тот же
		if ($url1 != $url)
		{
			$this->cidmenu = (int)$this->db->GetOne("SELECT menu_id FROM menus WHERE url = '$url1'", 0);	
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
	
	function main_head ()
	{
		$parse = parse_url($_SERVER['REQUEST_URI']);
		$routes = explode('/', $parse['path']);		
		$main = "";
		// получаем счетчики
		$data = $this->GetCounters();
		// главное меню
		$data["main_top_menu"] = $main.GetUlMenu($this->siteUrl, $this->menutree, $this->cid, 1);
		// навигация нужно сделать исключение для главной страницы
		//$data["nav"] = "<a href='".$this->siteUrl."'>Главная</a> / ".GetNav($menu, $this->cid);

		$data["address"] = $this->db->GetSetting ("ContactAddress");
		$data["phone"] = $this->db->GetSetting ("ContactPhone");
		$data["email"] = $this->db->GetSetting ("ContactEmail");		
		$data["slogan"] = $this->db->GetSetting ("Slogan");
		$data["copy"] = $this->db->GetSetting ("Copyright");
		$data["sitetitle"] = $this->db->GetSetting ("SiteTitle");
		
		

		$result = $this->db->ExecuteSql ("Select * From `category` Where is_active='1' and module='products' ORDER BY order_index");
		if ($result)
		{			
			while ($row = $this->db->FetchArray($result)) 
			{   
				$name = dec($row['title']);
				$url = dec($row['url']);
				$link = $this->siteUrl.CATALOG_LINK."/category/".$url;
				$data['cat_link_product'][] = array (
						"title" => $name,
						"link" => $link,
				 );              
			}
			$this->db->FreeResult($result);
			 foreach ($this->menuarrtree as $row)
			{		
				$link = $this->siteUrl.CATALOG_LINK."/".$row['url'];			
				$data['cat_link_product'][] = array (
						"title" => $row['title'],
						"link" => $link,
				 );
			}
		}
		$result = $this->db->ExecuteSql ("Select * From `category` Where is_active='1' and module='articles' ORDER BY order_index");
		if ($result)
		{
			while ($row = $this->db->FetchArray($result)) 
			{   
				$name = dec($row['title']);
				$url = dec($row['url']);
				$link = $this->siteUrl.ARTICLES_LINK."/category/".$url;
				$data['cat_link_article'][] = array (
						"title" => $name,
						"link" => $link,
				   );              
			}
			$this->db->FreeResult($result);
		}
		//----рекомендуем-------------------
		// подзапрос для получания количества комментариев для каждой записи
		$countcommet = "(Select count(*) From `comments` Where is_active='1' and module='products' and comments.parent_id = products.product_id) as totalcomments, ";
		$result = $this->db->ExecuteSql("Select ".$countcommet."title, filename, parent_id, views, url From `products` Where is_active='1' and recomend='1' ORDER BY RAND () Limit 2");
		if ($result)
		{	
			while ($row = $this->db->FetchArray($result))  
			{
				$reciperecomendname = $row['title'];
				if ($row['filename'] != "") {          
					$extension = substr($row['filename'], -3);
					$reciperecomendimg = $this->siteUrl."media/products/".substr($row['filename'], 0, -4)."_small.".$extension;
				}
				else {
					$reciperecomendimg = $this->siteUrl."img/noimg.jpg";
				}			
				$fullurl = GetLinkCat($this->menuarrtree, $row["parent_id"]);		
				$reciperecomendlink = $this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
				$data['recomend'][] = array (
					"title" => $reciperecomendname,
					"filename" => $reciperecomendimg,				
					"link" => $reciperecomendlink,
					"views" => $row['views'],
					"comments" => $row['totalcomments'],
				);		
			}
			$this->db->FreeResult($result);
		}
		//-----------------------   
		/*Комментарии*/
		$result = $this->db->ExecuteSql ("Select * From `comments` Where is_active='1' and new='0' Order By news_date desc LIMIT 2", false);
		if ($result) {			
			while ($row = $this->db->FetchArray ($result))  
			{
				$comment = dec($row['comment']);
				$comment1 = mb_substr($comment, 0, 130);
				if ($comment != $comment1) 
					$comment = mb_substr($comment, 0, 129)."...";    
				$date_added = date("d-m-Y", $row['news_date']);
				$name = dec($row["name"]);	
				$module = $row["module"];
				$parent_id = $row["parent_id"];
				switch ($module)
				{
					case "products":
						$sql = "SELECT parent_id, url FROM products WHERE product_id = '$parent_id'";
						$row1 = $this->db->GetEntry($sql);
						$fullurl = GetLinkCat($this->menuarrtree, $row1["parent_id"]);
						$link = "/".CATALOG_LINK."/".$fullurl.$row1['url'];
					break;
					case "articles":
						$sql = "SELECT url FROM articles WHERE article_id = '$parent_id'";
						$url = $this->db->GetOne($sql);
						$link = "/".ARTICLES_LINK."/".$url;
					break;
				}
				
				$data['comments'][] = array (
					"comment" => $comment,
					"date" => $date_added,
					"name" => $name,
					"link" => $link,
				);
			}
			$this->db->FreeResult ($result);
		}		
		return $data;
	}	
	
	// отправлет собщение на почту параметры как функции mail()
	// DKIM запиь стоит вынети в конфигурационный файл
	function SendMail ($email, $subject, $message, $email_headers = "", $reply = "")
	{
		if (empty($email_headers))
		{
			$SiteName = $this->db->GetSetting ("SiteName");
			$email_headers = "From: ".$SiteName." <no-reply@".$_SERVER['HTTP_HOST'].">"."\r\n";
			$email_headers .= "DKIM-Signature: v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCjaPsyANv/I+AVNQ2yXI+3vg/IgCmdxn739x+FOV2nrPcXEcySlreK/iRfd0N+toEItpGrEOHoujAAR4rkMAXkSYZhBA6iVCemTRNunngak+etpgQlaLsAdlHjvJEfjVTMRYks9yWosLONzkcL6t5uaKffN5nVTPT3zGHICXjRUQIDAQAB"."\r\n";
			$email_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		}
		if (empty($reply))
		{
			$reply = "-fno-reply@".$_SERVER['HTTP_HOST'];
		}
		$subject ="=?UTF-8?B?".base64_encode($subject)."?=";
		return @mail ($email, $subject, $message, $email_headers, $reply);
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
		$orderLink = "<div style='display:table;'>";
		$orderLink .= ($order > $minIndex) ? "<div style='display:table-row;'><div style='display:table-cell;'><a href='/adm/".$this->table_name."/up?id=$id' style=' padding: 0px' title=\"Вверх\"><i class=\"fa fa-chevron-up\"></i></a></div></div>" : "<div style='display:table-row;'><div style='display:table-cell;'></div></div>";
		$orderLink .= ($order < $maxIndex) ? "<div style='display:table-row;'><div style='display:table-cell;'><a href='/adm/".$this->table_name."/down?id=$id' style='padding: 0px' title=\"Вниз\"><i class=\"fa fa-chevron-down\"></i></a></div></div>" : "<div style='display:table-row;'><div style='display:table-cell;'></div></div>";
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
        $toRet .= "<option value='".$link."products' $selected>".$this->db->GetAdminTitle("products")."</option>\r\n";
        $selected = ('articles' == $value) ? "selected" : "";
        $toRet .= "<option value='".$link."articles' $selected>".$this->db->GetAdminTitle("articles")."</option>\r\n";
        
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
		$where = ($is_active)?" WHERE is_active='1' and is_menu='1' ":"";		
		$where.="ORDER BY order_index";
		
		$sql = "SELECT name, url, menu_id, parent_id, target FROM `menus`".$where;
		$result = $this->db->ExecuteSql($sql);
		$array = array();
		if ($result) {
		while ($row = $this->db->FetchArray ($result)) 
		{
			$array[$row['menu_id']]['title'] = dec($row['name']);
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
			$this->Redirect("/404");
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
        return (get_magic_quotes_gpc ()) ? stripslashes ($toRet) : $toRet;
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
            $this->db->ExecuteSql ("Insert Into `comments` (parent_id, module, name, news_date, comment, is_active, new, user_id, ip) Values ('$item_id', '$module', '$name', '".time()."', '$comment', '1', '1', '$user_id', '".$_SERVER['REMOTE_ADDR']."')");
			return true;
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
   // формирует массив слайдов
   // $limit колчество слайдов которые попадают в массив, если не указано, то все слайды
   function GetSliders($limit="")
   {
		if ($limit !="")
		{
			$limit = " LIMIT ".$limit; 
		}
		$sql = "SELECT title, short_text, filename, url FROM slides WHERE news_date < ".time()." and is_active='1' ORDER BY order_index".$limit;
		$result=$this->db->ExecuteSql($sql);
		if ($result)
		{
			while ($row = $this->db->FetchArray($result)) 
			{				
				if ($row['filename'] != "") {
					$filename = $this->siteUrl."media/slides/".$row['filename'];
				} 
				else {
					$filename = "/img/nophoto.jpg";
				}
				$data [] = array (
					"title" => $this->dec($row['title']),
					"short_text" => $this->dec($row['short_text']),
					"url" => $this->dec($row['url']),
					"filename" => $filename,
				);
			}		
			$this->db->FreeResult($result);
		}
		else
		{
			$data = false;
		}
		return $data;		
   }
   
   // формирует массив партнеров
   // $limit колчество партнеров которые попадают в массив, если не указано, то все партнеры
   function GetPartners($limit="")
   {
		if ($limit !="")
		{
			$limit = " LIMIT ".$limit; 
		}
		$sql = "SELECT title, link, filename FROM partners WHERE news_date < ".time()." and is_active='1' ORDER BY order_index".$limit;
		$result=$this->db->ExecuteSql($sql);
		if ($result)
		{
			while ($row = $this->db->FetchArray($result)) 
			{				
				$link = $row['link'];
				$title = $this->dec($row['title']);										
				if ($row['filename'] != "") {
					$filename = $row['filename'];
					/*$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;*/
					$filename = $this->siteUrl."media/partners/".$filename;
				} 
				else {
					$filename = "/img/nophoto.jpg";
				}
				$data[] = array (
					"link" => $link,
					"title" => $title,
					"filename" => $filename,
				);
			}
			$this->db->FreeResult($result);
		}
		else
		{
			$data = false;
		}
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
				$src = "<i class=\"fa fa-chevron-down\"></i>";
				
				}
				else
				{
					$dir = "asc";
					$src = "<i class=\"fa fa-chevron-up\"></i>";
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
				$type_order = ($this->orderDir == "desc")?"<i class=\"fa fa-arrow-up\" style=\"font-size: 14px;\"></i>":"<i class=\"fa fa-arrow-down\" style=\"font-size: 14px;\"></i>";
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
				$start = $this->db->GetOne ("Select Min($field) From $table", 0);
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
	
	// шапка для админки
	public function header_adm()
	{
		$sql = "SELECT pages FROM `admins` WHERE admin_id = '".$_SESSION["A_ID"]."'";		
		$temp = $this->db->GetOne($sql, "");		
		if ($temp != "") 
		{
			$mas = explode(',', $temp);
			$res="<ul id='css3menu1'>";
			for ($i=1; $i < count($mas); $i++)
			{
				$sql = "SELECT parent_id, keyname, title, iconame FROM `admin_pages` WHERE keyname = '".$mas[$i]."'";
				$row = $this->db->GetEntry($sql);
				//проверяем нужно ли выводить в главное меню
				if ($row['parent_id'] == 0)
				{
				$title = $row['title'];
				$keyname = $row['keyname'];
				// выводим количество новых записей если таковые есть
				$sql = "SELECT Count(*) FROM $keyname WHERE new='1'";
				$total = $this->db->GetOne($sql);
				$new =($total > 0)?"<div class='new'>$total</div>":"";
				$class=($this->module == "adm".$keyname)?"class='active'":"";
				$res.="<li class='topmenu'><a href='".$this->siteUrl."adm/".$keyname."/' ".$class."><img src='/img/icons/".$row['iconame']."' alt='$title' />$new"." ".$title."</a>";
				
				$res.="</li>";
				}
			}	
			$res.="<li class='topmenu'><a href='/adm/login/logout'><img src='/img/icons/exit.png' alt='Выход' /> Выход</a></li>";
			$res.="</ul>";
		
			return $res;
		}
		else
		{
			return $res = "";
		}
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
						$this->resizePhoto ($physical_path.$this->path_img.$new_full_name, 1024, 1024);
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
	public function resizePhoto ($image, $max_width, $max_height)
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
						  $k = ($k1 < $k2) ? $k1 : $k2;
						  
						  //$k = 0.3125;
						  $w = intval (imagesx ($im) * $k);
						  $h = intval (imagesy ($im) * $k);
						  
						  $im1 = imagecreatetruecolor ($w, $h);
						  
						  imagealphablending($im1, false);
						  imagesavealpha($im1, true);	
						  
						  imagecopyresampled ($im1, $im, 0, 0, 0, 0, $w, $h, imagesx($im), imagesy($im));
		  
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
		header('HTTP/1.1 404 Not Found');
		header("Status: 404 Not Found");
		header('Location:'.$this->siteUrl.'404');
		exit;		
	}
	
	// редирект
	// $targetURL - страница куда происходит редирект
	public function Redirect ($targetURL)
    {
        header ("Location: $targetURL");
        exit;
    }
}