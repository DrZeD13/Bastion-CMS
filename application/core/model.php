<?php
include_once('model_default.php');
class Model extends Model_Default
{	
	function __construct()	
	{		
       parent::__construct();				
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
}