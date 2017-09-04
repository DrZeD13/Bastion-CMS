<?
define('CMS_BASTION', true);
define('HOST', 'localhost');
define('USER', '');
define('PASSWORD', '');
define('DATABASE', '');

define('CATALOG_LINK', 'recipes');//каталог
define('SHOP_LINK', 'shop');//каталог
define('ARTICLES_LINK', 'blog');//статьи
define('SERVICES_LINK', 'services');//услуги
define('QANSWERS_LINK', 'qanswers');//вопрос-ответ
define('NEWS_LINK', 'news');//новости
define('ACTIONS_LINK', 'actions');//акции
define('VACANCY_LINK', 'vacancy');//вакансии
define('REVIEWS_LINK', 'reviews');//отзывы
define('COMMENTS_LINK', 'comments');//комментарии
define('AWARDS_LINK', 'awards');//награды, дипломы
define('GALLERY_LINK', 'gallery');//галерея

define('SITEMAP_LINK', 'sitemap');//карта сайта
define('XMLSITEMAP_LINK', 'sitemap.xml');//карта сайта XML
define('SEARCH_LINK', 'search');//поиск
define('RSS_LINK', 'rss');//новостная лента
define('CART_LINK', 'cart');//корзина

define('DELIM_NAV', '/');//разделитель навигации
define('MAIN_NAV', '<a href="/">Главная</a> / ');//главная страница навигации
define('DKIM', "");//DKIM запись для отправки почты
define('reCAPTCHA_SECRET', '');//секретный код для рекаптчи
define('reCAPTCHA_KEY', '');//открытый ключ для рекаптчи

define ("VALIDATE_NOT_EMPTY", 1);
define ("VALIDATE_USERNAME", 2);
define ("VALIDATE_PASSWORD", 3);
define ("VALIDATE_PASS_CONFIRM", 4);
define ("VALIDATE_EMAIL", 5);
define ("VALIDATE_INT_POSITIVE", 6);
define ("VALIDATE_FLOAT_POSITIVE", 7);
define ("VALIDATE_CHECKBOX", 8);
define ("VALIDATE_URL", 9);
define ("VALIDATE_NUMERIC_POSITIVE", 10 );
define ("VALIDATE_LONG_WORD", 11);
define ("VALIDATE_SHORT_TITLE", 15);

$ROUTS = array (
  'main' => // главная
  array (
    'url' => 'main',
	'controler' => 'Controller_Main',
    'action' => false,
  ),
  CATALOG_LINK => // каталог
  array (
    'url' => 'catalog',
	'controler' => 'Controller_Catalog',
    'action' => false,
  ),
  SHOP_LINK => // магазин
  array (
    'url' => 'shop',
	'controler' => 'Controller_Shop',
    'action' => false,
  ),
  ARTICLES_LINK => // статьи
  array (
    'url' => 'articles',
	'controler' => 'Controller_Articles',
    'action' => false,
  ), 
  XMLSITEMAP_LINK => // карта сайт xml
  array (
    'url' => 'sitemap.xml',
	'controler' => 'Controller_XMLSitemap',
    'action' => false,
  ),
  SEARCH_LINK => // поик по сайту
  array (
    'url' => 'search',
	'controler' => 'Controller_Search',
    'action' => false,
  ),
  RSS_LINK => // rss
  array (
    'url' => 'rss',
	'controler' => 'Controller_Rss',
    'action' => false,
  ),
  SITEMAP_LINK => // sitemap
  array (
    'url' => 'rss',
	'controler' => 'Controller_Sitemap',
    'action' => false,
  ),
  'ajax' => // ajax
  array (
    'url' => 'ajax',
	'controler' => 'Controller_Ajax',
    'action' => false,
  ),
  'login' => // login
  array (
    'url' => 'login',
	'controler' => 'Controller_Login',
    'action' => true,
  ), 
);
?>
