<?php

class Model_Shop extends Model 
{

	private $table_name = '`shops`';
	var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (10, 20, 50); //количество записей на страницу
	var $path_img = "/media/shop/";
	
	function __construct()
	{
		parent::__construct();
		$this->menuarr = $this->get_array_catalog(false, "shops", "shop_id");
		$this->menuarrtree = GetTreeFromArray($this->menuarr);
	}
	
	public function get_data() 
	{	
		$mainroutes = explode('/', $_SERVER['REQUEST_URI']);		
		//print_arr($mainroutes);
		$routes = explode('/', $_SERVER['REQUEST_URI']);
		$routes = explode('?', $routes[count($routes)-2]);
		// флаг для подкаталога (выводить продукцию всю или для конкретного каталога)
		$flag_tamplate = $flag = false;	
		$titlecatalog = "";
		if (($routes[0] == SHOP_LINK) && (count($mainroutes) <= 3))
		{
			$sql="SELECT news_date, title, head_title, text, description, keywords FROM menus WHERE url = '".SHOP_LINK."/' and is_active='1'";	
		}
		elseif ($mainroutes[2] == "category")
		{			
			$sql="SELECT name, category_id, news_date, title, head_title, text, description, keywords FROM category WHERE url = '".$routes[0]."/'";		
			$flag_tamplate = true;	
		}
		elseif ($mainroutes[2] == "tags")
		{			
			$sql="SELECT name, tag_id, news_date, title, head_title, text, description, keywords FROM tags WHERE url = '".$routes[0]."/' and module = 'shop' and is_active='1'";		
			$flag_tamplate = true;	
		}
		else
		{
			$sql="SELECT name, shop_id, news_date, title, head_title, text, description, keywords FROM ".$this->table_name." WHERE url = '".$routes[0]."/' and is_active='1'";	
			$flag = true;			
		}
// menu tree получить
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}
		$data = array(	
			"title" => $this->dec($row["title"]),
			"descr" => $this->dec($row["text"]),
			"head_title" => $this->dec($row["head_title"]),
			"description" => $this->dec($row["description"]),
			"keywords" => $this->dec($row["keywords"]),
			"catalog_ul" => GetUlMenu($this->siteUrl.SHOP_LINK."/", $this->menuarrtree, (isset($row["shop_id"]))?$row["shop_id"]:0, 1),
		);
		
		if ($flag)
		{
			$parent = "and parent_id = '".$row["shop_id"]."'";
			$data["nav"] = "<a href='".$this->siteUrl."'>Главная</a> / ".GetNav($this->menu, $this->cid, false).GetNavCat($this->menuarr, $row["shop_id"], true, SHOP_LINK);
			// проверяем на правильность передачи ссылки
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".SHOP_LINK."/".GetLinkCat($this->menuarr, $row["shop_id"]);
			if ($mainlink != $fullurl[0])
			{
				$this->Redirect($mainlink);
			}
			// проверяем количество потомков у каталога и если они есть будем обрабатывать шаблоном каталога $flag=false
			$sql = "SELECT Count(*) FROM ".$this->table_name." WHERE parent_id = '".$row["shop_id"]."'";
			$total = $this->db->GetOne($sql);
			if ($total == 0)
			{
				$flag_tamplate=true;
			}
		}
		elseif ($mainroutes[2] == "category")
		{			
			$parent = "and category = '".$row["category_id"]."'";
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".SHOP_LINK."/category/".$routes[0]."/";
			if ($mainlink != $fullurl[0])
			{
				$this->Redirect($mainlink);
			}
			$data["nav"] = "<a href='".$this->siteUrl."'>Главная</a> / ".GetNav($this->menu, $this->cid, false)."Категории / ".$this->dec($row["name"]);
		}
		else
		{
			$mainlink = "/".SHOP_LINK."/";
			$parent = "and parent_id = '0'";
			$data["nav"] = "<a href='".$this->siteUrl."'>Главная</a> / ".GetNav($this->menu, $this->cid).$titlecatalog;
		}

		// для тегов запрос не мнго другой
		if ($mainroutes[2] == "tags")
		{			
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".SHOP_LINK."/tags/".$routes[0]."/";
			if ($mainlink != $fullurl[0])
			{
				$this->Redirect($mainlink);
			}
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."Теги / ".$this->dec($row["name"]);
			$fromwhere = " FROM `shop`, `tags`, `tags_value` WHERE shop.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'shop' and item_id = shop.shop_id GROUP BY shop.shop_id Order By shop.news_date desc";
			$fromwhere_count = " FROM `shop`, `tags`, `tags_value` WHERE shop.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'shop' and item_id = shop.shop_id GROUP BY tags.tag_id Order By shop.news_date desc";
			/*$fromwhere = " FROM `shop` INNER JOIN tags on tags.module = 'shop' 
			INNER JOIN tags_value on tags_value.tag_id = '".$row["tag_id"]."' and tags_value.item_id = shop.shop_id WHERE shop.is_active='1'  GROUP BY shop.shop_id Order By shop.news_date desc";*/
		}
		else
		{
			$fromwhere_count = $fromwhere = " FROM `shop` WHERE is_active='1' $parent Order By news_date desc";
		}
		
		if ($flag_tamplate)
		{
		$sql = "Select Count(*)".$fromwhere_count;	
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0)	
		{			
			$this->Get_Valid_Page($total);
			$sql = "Select shop.name, shop.shop_id, shop.url, shop.recomend, shop.title, shop.short_text, shop.parent_id, shop.filename, shop.category".$fromwhere;			
			//echo $sql;
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))
			{
				$id = $row['shop_id'];
				$p_url = $row['url'];
				$title = $this->dec($row['name']);				
				$short_text = $this->dec($row['short_text']);							
				$fullurl1 = GetLinkCat($this->menuarr, $row["parent_id"]);
				$link = $this->siteUrl.SHOP_LINK."/".$fullurl1.$p_url;				
				if ($row['filename'] != "") {
					$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;
					$filename = $this->siteUrl."/media/shop/".$filename;			
				}
				else {
					$filename = $this->siteUrl."img/noimg.jpg";
				}   
				
				
				$data ['product_row'][] = array (
					"id" => $id,
					"title" => $title,
					"short_text" => $short_text,
					"filename" => $filename,										
					"link" => $link,
				);
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}	
		else		
		{
			$data['empty'] = "Нет записей в базе данных";
		}
		}
		else
		{
		$fromwhere = " FROM ".$this->table_name."WHERE is_active='1' $parent ORDER BY order_index asc";
		$sql = "Select Count(*)".$fromwhere;	
		//echo $sql;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0)	
		{			
			$this->Get_Valid_Page($total);
			$sql = "SELECT *".$fromwhere;			
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))
			{
				$id = $row['shop_id'];
				$p_url = $row['url'];
				$title = $this->dec($row['title']);				
				$short_text = $this->dec($row['short_text']);							
				$fullurl1 = GetLinkCat($this->menuarr, $row["parent_id"]);
				$link = $this->siteUrl.SHOP_LINK."/".$fullurl1.$p_url;				
				if ($row['filename'] != "") {
					$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;
					$filename = $this->siteUrl."/media/shops/".$filename;			
				}
				else {
					$filename = $this->siteUrl."img/noimg.jpg";
				}    
				
				$data ['table_row'][] = array (
					"id" => $id,
					"title" => $title,
					"short_text" => $short_text,
					"filename" => $filename,										
					"link" => $link,
				);
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}	
		else		
		{
			$data['empty'] = "Нет записей в базе данных";
		}
		}
		
		
		//$data['tags_ul'] = $this->Ul_tags($routes[0]);
		$data['tags_ul'] = array();
		return $data;
		
	}
	
	public function get_view() 
	{
		/*if (!$this->Valid_Url(CATALOG_LINK)) 		
		{
			$this->error404();
		}*/		
		$routes = parse_url($_SERVER['REQUEST_URI']);
		$temp = explode('/', $routes['path']);
		$url = $temp[count($temp)-1];	
		$sql = "SELECT * FROM `shop` WHERE is_active='1' and url = '".$url."'";
		$row = $this->db->GetEntry($sql, "/404");
		
		// проверяем на правильность передачи ссылки
		$fullurl = explode('?', $_SERVER['REQUEST_URI']);
		$link = "/".SHOP_LINK."/".GetLinkCat($this->menuarr, $row["parent_id"]).$row["url"];
		if ($link != $fullurl[0])
		{
			$this->Redirect($link);
		}
		$id = $row["shop_id"];
		$nav = "<a href='".$this->siteUrl."'>Главная</a> / ".GetNav($this->menu, $this->cid, false).GetNavCat($this->menuarr, $row["parent_id"], false, SHOP_LINK).dec($row["name"]);
		if ($row['filename'] != "") {				
			$filename = $row['filename'];
			$filename = $this->siteUrl.$this->path_img.$filename;
		}
		else	{
			$filename = $this->siteUrl."img/noimg.jpg";
		}	

		
		if ($row['filename'] != "") {
			for ($j=0;$j<5;$j++)
			{
				if ($j == 0)
				{
					if ($row['filename'] != "") {
						$array ['file'][] = $this->siteUrl.$this->path_img.$row['filename'];
					}
				}
				else
				{
					if ($row['filename'.$j] != "") {
						$array ['file'][] = $this->siteUrl.$this->path_img.$row['filename'.$j];
					}
				}
			}					
		}
		else {
			$array ['file'][] = $this->siteUrl."img/noimg1.jpg";
		}
// получить menu tree		
		$data = array (
			"id" => $id,
			"price" => $row["price"],
			"file" => $array,
			"nav" =>$nav, 
			"title" => dec($row["title"]),
			"text" => $this->dec($row["text"]),
			"short_text" => $this->dec($row["short_text"]),
			"head_title" =>dec($row["head_title"]),
			"description" =>dec($row["description"]),
			"keywords" =>dec($row["keywords"]),
			"filename" => $filename,
			"catalog_ul" => GetUlMenu($this->siteUrl.SHOP_LINK."/", $this->menuarrtree, $row["parent_id"], 1),
		);
		
		/*----------------Опции-------------*/
		$result = $this->db->ExecuteSql ("Select additions.addition_id, additions.name, additions.price From `additions`, `additions_value` Where additions_value.item_id='$id' and additions.addition_id = additions_value.addition_id GROUP BY additions.addition_id", false);
		if ($result) {						
			while ($row = $this->db->FetchArray ($result)) 
			{								
				$data['options'][] = array (
					"row_name" => $this->dec($row['name']),
					"row_price" => $row['price'],
					"row_addition_id" => $row['addition_id'],
				);
			}
			$this->db->FreeResult ($result);			
		}	
		
		return $data;	
	}
	
	function Ul_tags ($url_tags = "")
	{
		$result = $this->db->ExecuteSql ("Select title, url From `tags` Where is_active='1' and module='shop' ORDER BY order_index asc");
		 
		if ($result)
		{			
			while ($row = $this->db->FetchArray($result)) 
			{   
				$name = dec($row['title']);
				$url = dec($row['url']);
				$link = $this->siteUrl.SHOP_LINK."/tags/".$url;
				if ($url_tags."/" == $url){
					$active = "class='active'";
				}
				else
				{
					$active="";
				}
				$data[] = array (
						"title" => $name,
						"link" => $link,
						"active" => $active,
				 );              
			}
			$this->db->FreeResult($result);
		}
		return $data;		
	}

}
