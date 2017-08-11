<?php
include_once('config.php');

class Route
{

	static function start()
	{
		// действие по умолчанию
		$action_name = 'index';	
		$controller_path = "application/controllers/";
		$parse = parse_url($_SERVER['REQUEST_URI']);
		$routes = explode('/', $parse['path']);
		// получаем имя действия
		// проверяем переданное действие через форму или url
		if ((!isset($_POST['action'])) && (!isset($_GET['action'])))
		{
			if (strpos($_SERVER['REQUEST_URI'], '.html'))
			{	
				$action_name = 'view';
				if (count($routes) == 2) $routes[1] = "main";
			}
		}
		else
		{
			if (isset($_POST['action']))
			{
				$action_name = $_POST['action'];
			}
			elseif ($_GET['action'] == "print")
			{
				$action_name = $_GET['action'];
			}			
			if (count($routes) == 2) $routes[1] = "main";
		}	
		//echo (count($routes));
		// проверяем на админ панель
		if (!empty($routes[1]) && ($routes[1] == "adm"))
		{			
			if (isset($_SESSION["A_ID"]))
			{
				// сделать проверку на переход по умолчанию авторизованного пользователя
				if (!empty($routes[2])) 
				{
					$controller_name = "Controller_Adm_".$routes[2];
				}
				else
				{
					$controller_name = "Controller_Adm_Login";
				}
				if (!empty($routes[3])) $action_name = $routes[3];
			}
			else
			{
				$controller_name = "Controller_Adm_Login";
			}
			if (isset($_POST['action']))
			{
				$action_name = $_POST['action'];
			}
			$controller_path .= "adm/";
		}
		// определяем котнроллеры и их модули
		// $routes[1] == "0" проверка длясылки типа http://домен/0/		
		//if ((!empty($routes[1]) || ($routes[1] == "0")) && $flag) 
		//elseif ((!empty($routes[1]) || ($routes[1] == "0")) && $flag)
		else
		{	
			/*switch ($routes[1])
			{
				case CATALOG_LINK:
					$controller_name = 'Controller_Catalog';
				break;
				case SHOP_LINK:
					$controller_name = 'Controller_Shop';
				break;
				case ARTICLES_LINK:
					$controller_name = 'Controller_Articles';
				break;
				case ACTIONS_LINK:
					$controller_name = 'Controller_Actions';
				break;
				case QANSWERS_LINK:
					$controller_name = 'Controller_Qanswers';
				break;
				case NEWS_LINK:
					$controller_name = 'Controller_News';
				break;
				case SERVICES_LINK:
					$controller_name = 'Controller_Services';
				break;
				case VACANCY_LINK:
					$controller_name = 'Controller_Vacancy';
				break;
				case REVIEWS_LINK:
					$controller_name = 'Controller_Reviews';
				break;
				case COMMENTS_LINK:
					$controller_name = 'Controller_Comments';
				break;
				case AWARDS_LINK:
					$controller_name = 'Controller_Awards';
				break;
				case GALLERY_LINK:
					$controller_name = 'Controller_Gallery';
				break;
				case SITEMAP_LINK:
					$controller_name = 'Controller_Sitemap';
				break;
				case XMLSITEMAP_LINK:
					$controller_name = 'Controller_XMLSitemap';
				break;
				case SEARCH_LINK:
					$controller_name = 'Controller_Search';
				break;
				case RSS_LINK:
					$controller_name = 'Controller_Rss';
				break;
				case '404':
					$controller_name = 'Controller_404';
				break;	
				case 'ajax':
					$controller_name = 'Controller_Ajax';
					//if ((count($routes) > 2) && ($routes[2] != "")) $action_name = $routes[2];					
					//if (isset($_POST['action'])) $action_name = $_POST['action'];
				break;	
				case 'login':
					$controller_name = 'Controller_Login';
					// действие после /login/"action"
					if ((count($routes) > 2) && ($routes[2] != "")) $action_name = $routes[2];					
					if (isset($_POST['action'])) $action_name = $_POST['action'];
				break;
				case CART_LINK:
					$controller_name = 'Controller_Cart';
					// действие после /login/"action"
					if ((count($routes) > 2) && ($routes[2] != "")) $action_name = $routes[2];					
					if (isset($_POST['action'])) $action_name = $_POST['action'];
				break;
				case 'main':
					$controller_name = 'Controller_Main';
				break;
				default:
					// можно сделать action равно view и сделать вложение под страниц
					if ($routes[1] == "")
					{
						$controller_name = 'Controller_Main';
					}
					else
					{
						$controller_name = 'Controller_404';	
					}						
			}*/
			global $ROUTS;
			if (isset($ROUTS[$routes[1]]))
			{
				$controller_name = $ROUTS[$routes[1]]['controler'];
				if ($ROUTS[$routes[1]]['action'])
				{
					// действие после /$ROUTS[$routes[1]/"action"
					if ((count($routes) > 2) && ($routes[2] != "")) $action_name = $routes[2];					
					if (isset($_POST['action'])) $action_name = $_POST['action'];
				}
			}
			else
			{
				if ($routes[1] == "")
				{
					$controller_name = 'Controller_Main';
				}
				else
				{
					$controller_name = 'Controller_404';	
				}
			}
		}
		// подцепляем файл с классом контроллера
		$controller_file = strtolower($controller_name).".php";
		$controller_path .= $controller_file;		
		if(file_exists($controller_path))
		{
			include_once $controller_path;
		}
		else
		{
			/*
			правильно было бы кинуть здесь исключение,
			но для упрощения сразу сделаем редирект на страницу 404
			*/
			Route::ErrorPage404();
		}
		
		// создаем контроллер
		$controller = new $controller_name;
		$action = "action_".$action_name;		
		if(method_exists($controller, $action))
		{
			// вызываем действие контроллера
			$controller->$action();
		}
		else
		{
			// здесь также разумнее было бы кинуть исключение			
			Route::ErrorPage404();
		}		
	}

	function ErrorPage404()
	{        
		include_once "application/controllers/controller_404.php";		
		// создаем контроллер
		$controller = new Controller_404;
		// вызываем действие контроллера
		$controller->action_index();
		exit();
    }
	    
}
