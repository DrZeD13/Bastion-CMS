<?php
/*
структура таблицы
article_id	Идентификатор блога
news_date	Дата создания блога
title Название блога
head_title Название блога <title>
url  Адрес блога
description	Описание блога
keywords Ключевые слова
filename картинка
category категория блога
short_text кртакое описание
text подробное описание
rating рейтинг
respondents количество проголосовавших
is_active	Флаг активности блога.
is_comment	Флаг - разрешены ли комментарии в блоге.
author автор блога
update_date дата обновления
update_user id пользователя который обновил
*/
class Model_Articles extends Model 
{

	private $table_name = '`articles`';
	
	var $orderDefault = "news_date";
	var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (6, 10, 20); //количество записей на страницу
	
	public function get_data() 
	{
		$mainlink = $this->siteUrl.ARTICLES_LINK."/";
		$mainroutes = explode('/', $_SERVER['REQUEST_URI']);
		$routes1 = explode('/', $_SERVER['REQUEST_URI']);
		$routes = explode('?', $routes1[count($routes1)-2]);
		if (isset($mainroutes[2]) and ($mainroutes[2] == "category"))
		{									
			/*эту часть нужно переработать*/
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			// дополнительная проверка на редирект категори на конце без /
			if (isset($routes1[3]) and !empty($routes1[3]))
			{
				$sql="SELECT category_id, news_date, title, head_title, text, description, keywords FROM category WHERE url = '".$this->db->RealEscapeString($routes1[3])."/' and module = 'articles'";	
				// запрос для получения шапки		
				$row = $this->db->GetEntry($sql);	
				if (!$row) 		
				{
					$this->error404();
				}
				
				$mainlink = "/".ARTICLES_LINK."/category/".$routes1[3];
				if ($mainlink == $fullurl[0]) 
				{
					$this->Redirect($mainlink."/");
				}
			}
			else
			{
				$sql="SELECT category_id, news_date, title, head_title, text, description, keywords FROM category WHERE url = '".$this->db->RealEscapeString($routes[0])."/' and module = 'articles'";	
				// запрос для получения шапки		
				$row = $this->db->GetEntry($sql);	
				if (!$row) 		
				{
					$this->error404();
				}
			}
			/*--------------------------- эту --------------------------------------------------------*/
			$mainlink = "/".ARTICLES_LINK."/category/".$routes[0]."/";
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}			
			$parent = "and articles.category = '".$row["category_id"]."'";			
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."Категории / ".$this->dec($row["title"]);
		}
		elseif (isset($mainroutes[2]) and ($mainroutes[2] == "tags"))
		{			
			$sql="SELECT tag_id, news_date, title, head_title, text, description, keywords FROM tags WHERE url = '".$this->db->RealEscapeString($routes[0])."/' and module = 'articles' and is_active='1'";
			// запрос для получения шапки		
			$row = $this->db->GetEntry($sql);	
			if (!$row) 		
			{
				$this->error404();
			}
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".ARTICLES_LINK."/tags/".$routes[0]."/";
			/*if ($mainlink == $fullurl[0]) 
			{
				$this->Redirect($mainlink."/");
			}
			$mainlink .= "/";*/
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}
			$parent = "";
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."Теги / ".$this->dec($row["title"]);
		}
		else
		{
			$mainlink = "/".ARTICLES_LINK."/";	
			$routes = explode('?', $_SERVER['REQUEST_URI']);
			if ("/".ARTICLES_LINK == $routes[0]) 
			{
				$this->Redirect($this->siteUrl.ARTICLES_LINK."/");
			}
			if ("/".ARTICLES_LINK."/" != $routes[0]) 		
			{
				$this->error404(); 
			}			
			$sql = "SELECT title, head_title, description, keywords, text FROM `menus` WHERE url = '".ARTICLES_LINK."/' and is_active='1'";
			// запрос для получения шапки		
			$row = $this->db->GetEntry($sql);
			if (!$row) 		
			{
				$this->error404();
			}			
			$parent = "";
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		}
		$data["title"] =$this->dec($row["title"]);
		$data["head_title"] = $this->dec($row["head_title"]);
		$data["description"] = $this->dec($row["description"]);
		$data["keywords"] = $this->dec($row["keywords"]);
		$data["text"] = $this->dec($row["text"]);

		// для тегов запрос не мнго другой
		if ($mainroutes[2] == "tags")
		{			
			$fromwhere = " FROM `articles`, `tags`, `tags_value`, `category` WHERE articles.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'articles' and item_id = articles.article_id GROUP BY articles.article_id Order By articles.news_date desc";
			$fromwhere_count = " FROM `articles`, `tags`, `tags_value` WHERE articles.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'articles' and item_id = articles.article_id GROUP BY tags.tag_id Order By articles.".$this->orderBy." ".$this->orderDir;
		}
		else
		{
			// запрос получения списка статей		
			$fromwhere = "FROM ".$this->table_name.", `category` WHERE articles.is_active='1' $parent AND articles.news_date < ".time()." AND category.category_id = articles.category ORDER BY ".$this->orderBy." ".$this->orderDir;
			$fromwhere_count = "FROM ".$this->table_name." WHERE is_active='1' $parent AND news_date < ".time()." ORDER BY ".$this->orderBy." ".$this->orderDir;	
		}		
				
		$sql="SELECT Count(*) ".$fromwhere_count;
		
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0) 
		{			
			$this->Get_Valid_Page($total);
			// подзапрос для получания количества комментариев для каждой записи
			$countcommet = "(Select count(*) From `comments` Where comments.is_active='1' and comments.module='articles' and comments.parent_id = articles.article_id) as totalcomments, ";	
			$sql="SELECT ".$countcommet."articles.title, articles.filename, articles.views, articles.url, articles.short_text, articles.category, articles.news_date, category.title as cattitle, category.url as caturl ".$fromwhere;
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))	
			{				
				$url = $row['url'];
				$title = $this->dec($row['title']);
				$short_text = $this->dec($row['short_text']);
				$link = $this->siteUrl.ARTICLES_LINK."/".$url;
				if ($row['filename'] != "") {
				$extension = substr($row['filename'], -3);
				$filename = substr($row['filename'], 0, -4)."_small.".$extension;
				$filename = $this->siteUrl."media/articles/".$filename;
				} 
				else {
					$filename = "/img/nophoto.jpg";
				}
				$cat_link = $this->siteUrl.ARTICLES_LINK."/category/".$row['caturl'];
				$data ["article_row"][] = array (
					"link" => $link,
					"title" => $title,
					"news_date" => date("d-m-Y", $row['news_date']),
					"short_descr" => $short_text,
					"filename" => $filename,
					"views" => $row["views"],
					"cat_name" => $row['cattitle'],
					"cat_link" => $cat_link,
					"comments" => $row["totalcomments"],
				);							
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}
		else
		{
			$data['empty_row'] = "Нет записей в базе данных";
		}
		
		return $data;
	}
	
	public function get_view() 
	{		
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		
		// проверяем на правильность передачи ссылки
		$fullurl = explode('?', $_SERVER['REQUEST_URI']);
		$link = "/".ARTICLES_LINK."/".$url;		
		
		// подзапрос для получания количества комментариев для каждой записи
		$countcommet = "(Select count(*) From `comments` Where is_active='1' and module='articles' and comments.parent_id = articles.article_id) as totalcomments, ";	
		$sql="SELECT ".$countcommet."articles.article_id, articles.head_title, articles.keywords, articles.description, articles.title, articles.filename, articles.views, articles.url, articles.text, articles.category, articles.news_date, category.title as cattitle, category.url as caturl FROM ".$this->table_name.", `category` WHERE articles.is_active='1' AND articles.news_date < ".time()." AND category.category_id = articles.category AND articles.url = '".$url."'";			
				
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		else
		{
			if ($link != $fullurl[0])
			{
				$this->error404();
			}
		}
		$views = $this->dec($row['views'])+1;
		$this->db->ExecuteSql ("Update `articles` Set views='$views' Where article_id='".$row['article_id']."'");
		$cat_link = $this->siteUrl.ARTICLES_LINK."/category/".$row['caturl'];
		$data = array(
			"title" => $this->dec($row["title"]), 
			"descr"=> $this->dec($row["text"]),
			"head_title" =>$this->dec($row["head_title"]),
			"description" =>$this->dec($row["description"]),
			"keywords" =>$this->dec($row["keywords"]),
			"news_date" => date("d-m-Y", $row['news_date']),
			"views" => $row["views"],
			"cat_name" => $row['cattitle'],
			"cat_link" => $cat_link,
			"comments" => $row['totalcomments'],
		);	
		// форма комментариев
		$data = $data + $this->GetFormComment($row["article_id"]);
		
		$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).$row["title"];
		
		/*Комментарии*/
		$result = $this->db->ExecuteSql ("Select * From `comments` Where is_active='1' and module='articles' and parent_id='".$row["article_id"]."' Order By news_date desc", false);	
		if ($result) {
			$result = $this->db->ExecuteSql ("Select * From `comments` Where is_active='1' and module='articles' and parent_id='".$row["article_id"]."' Order By news_date desc", false);
			while ($row = $this->db->FetchArray ($result))  
			{
				$comment = $this->dec($row['comment']);
				$date_added = date("d-m-Y", $row['news_date']);
				$name = $this->dec($row["name"]);				
				$data['table_comment'][] = array (
					"comment" => $comment,
					"date" => $date_added,
					"name" => $name,
				);
			}
			$this->db->FreeResult ($result);
		}
		
		/*if ($row["is_comment"] == 1) 
		{
			$sql = "SELECT * FROM `comments` WHERE parent_id = '".$row['article_id']."' and module = 'articles'";
			echo $sql;
		}
		else
		{
			$data['empty_comment'] = "Комментарии для этой статьи запрещены";
		}*/
		
		return $data;		
	}
	
	function act_asc ()
    {
		return $this->add_comment("articles");
   }
 
}
