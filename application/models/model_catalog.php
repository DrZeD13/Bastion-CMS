<?php

class Model_Catalog extends Model 
{

	private $table_name = '`catalogs`';
	var $rowsPerPage = 10; //выводить на страницу по умолчанию
    var $rowsOptions = array (10, 20, 50); //количество записей на страницу
	var $path_img = "media/products/";
	
	public function get_data() 
	{			
		
		$mainroutes = explode('/', $_SERVER['REQUEST_URI']);		
		//print_arr($mainroutes);
		$routes = explode('/', $_SERVER['REQUEST_URI']);
		$routes = explode('?', $routes[count($routes)-2]);
		// флаг для подкаталога (выводить продукцию всю или для конкретного каталога)
		$flag = false;		
		if (($routes[0] == CATALOG_LINK) && (count($mainroutes) <= 3))
		{
			$sql="SELECT news_date, title, head_title, text, description, keywords FROM menus WHERE url = '".CATALOG_LINK."/' and is_active='1'";			
		}
		elseif (isset($mainroutes[2]) and ($mainroutes[2] == "category"))
		{			
			$sql="SELECT category_id, news_date, title, head_title, text, description, keywords FROM category WHERE url = '".$this->db->RealEscapeString($routes[0])."/' and module = 'products' and is_active='1'";			
		}
		elseif (isset($mainroutes[2]) and ($mainroutes[2] == "tags"))
		{			
			$sql="SELECT tag_id, news_date, title, head_title, text, description, keywords FROM tags WHERE url = '".$this->db->RealEscapeString($routes[0])."/' and module = 'products' and is_active='1'";			
		}
		else
		{
			$sql="SELECT catalog_id, news_date, title, head_title, text, description, keywords FROM ".$this->table_name." WHERE url = '".$this->db->RealEscapeString($routes[0])."/' and is_active='1'";	
			$flag = true;
		}
		$row = $this->db->GetEntry($sql);	
		if (!$row)
		{
			$this->error404();
		}
		$data = array(
			"title" => $this->dec($row["title"]), 		
			"text" => $this->dec($row["text"]),
			"head_title" =>$this->dec($row["head_title"]),
			"description" =>$this->dec($row["description"]),
			"keywords" =>$this->dec($row["keywords"]),			
		);
		
		if ($flag)
		{
			$parent = "and parent_id = '".$row["catalog_id"]."'";
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false).GetNavCat($this->menuarr, $row["catalog_id"]);
			// проверяем на правильность передачи ссылки
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".CATALOG_LINK."/".GetLinkCat($this->menuarr, $row["catalog_id"]);
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}
		}
		elseif ($mainroutes[2] == "category")
		{			
			if (isset($row["category_id"]))
			{
				$parent = "and category = '".$row["category_id"]."'";
			}			
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".CATALOG_LINK."/category/".$routes[0]."/";
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."Категории / ".$this->dec($row["title"]);
		}	
		elseif ($mainroutes[2] == "tags")
		{			
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			$mainlink = "/".CATALOG_LINK."/tags/".$routes[0]."/";
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid, false)."Теги / ".$this->dec($row["title"]);
		}
		else
		{
			$mainlink = "/".CATALOG_LINK."/";
			$fullurl = explode('?', $_SERVER['REQUEST_URI']);
			if ($mainlink != $fullurl[0])
			{
				$this->error404();
			}
			$parent = "";
			$data["nav"] = MAIN_NAV.GetNav($this->menu, $this->cid);
		}
		// для тегов запрос не мнго другой
		if ($mainroutes[2] == "tags")
		{			
			$fromwhere = " FROM `products`, `tags`, `tags_value` WHERE products.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'products' and item_id = products.product_id GROUP BY products.product_id Order By products.news_date desc";
			$fromwhere_count = " FROM `products`, `tags`, `tags_value` WHERE products.is_active='1' and tags_value.tag_id = '".$row["tag_id"]."' and tags.module = 'products' and item_id = products.product_id GROUP BY tags.tag_id Order By products.news_date desc";
			/*$fromwhere = " FROM `products` INNER JOIN tags on tags.module = 'products' 
			INNER JOIN tags_value on tags_value.tag_id = '".$row["tag_id"]."' and tags_value.item_id = products.product_id WHERE products.is_active='1'  GROUP BY products.product_id Order By products.news_date desc";*/
		}
		else
		{
			$fromwhere_count = $fromwhere = " FROM `products` WHERE is_active='1' $parent Order By news_date desc";
		}
		
		$sql = "Select Count(*)".$fromwhere_count;		
		//echo $sql;
		$total = $this->db->GetOne ($sql, 0);		
		if ($total > 0)	
		{			
			$this->Get_Valid_Page($total);
			// подзапрос для получания количества комментариев для каждой записи
			$countcommet = "(Select count(*) From `comments` Where is_active='1' and module='products' and comments.parent_id = products.product_id) as totalcomments, ";
			$sql = "Select ".$countcommet."products.product_id, products.url, products.title, products.short_text, products.rating, products.respondents, products.views, products.parent_id, products.filename".$fromwhere;					
			$result=$this->db->ExecuteSql($sql, $this->Pages_GetLimits());
			while ($row = $this->db->FetchArray ($result))
			{
				$id = $row['product_id'];
				$p_url = $row['url'];
				$title = $this->dec($row['title']);				
				$short_text = $this->dec($row['short_text']);
				$rating = $this->dec($row['rating']);
				$respondents = $this->dec($row['respondents']);				
				$fullurl1 = GetLinkCat($this->menuarr, $row["parent_id"]);
				$link = $this->siteUrl.CATALOG_LINK."/".$fullurl1.$p_url;				
				if ($row['filename'] != "") {
					$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;
					$filename = $this->siteUrl.$this->path_img.$filename;			
				}
				else {
					$filename = $this->siteUrl."img/noimg.jpg";
				}
				$reiting="				
				<input type=\"range\" id=\"rateit$id\" data-role=\"none\" max=\"5\" value=\"$rating\" readonly />
				<div class=\"rateit\" id='rait$id' data-productid=\"$id\" data-rateit-backingfld=\"#rateit$id\"></div>";                         
										
				$delimetr = "<div class = 'clear'></div>";
			
				$data ['table_row'][] = array (
					"id" => $id,
					"title" => $title,
					"short_text" => $short_text,
					"filename" => $filename,										
					"link" => $link,
					"delimiter" => $delimetr,
					"rating" => $reiting,
					"comment" => $row['totalcomments'],
					"views" => $row['views'],
				);
			}
			$this->db->FreeResult ($result);
			$data['pages'] = $this->Pages_GetLinks_Site($total, $mainlink."?");
		}	
		else		
		{
			$data['empty'] = "Нет записей в базе данных";
		}
		
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
		$url = $this->db->RealEscapeString($temp[count($temp)-1]);	
		$sql = "SELECT * FROM `products` WHERE is_active='1' and url = '".$url."'";
		$row = $this->db->GetEntry($sql);
		if (!$row)
		{
			$this->error404();
		}	
		// проверяем на правильность передачи ссылки
		$fullurl = explode('?', $_SERVER['REQUEST_URI']);
		$link = "/".CATALOG_LINK."/".GetLinkCat($this->menuarr, $row["parent_id"]).$row["url"];
		if ($link != $fullurl[0])
		{
			$this->Redirect($link);
		}
		$id = $row["product_id"];
		$main_genre= $row['category'];
		$nav = MAIN_NAV.GetNav($this->menu, $this->cid, false).GetNavCat($this->menuarr, $row["parent_id"], false).$this->dec($row["title"]);
		if ($row['filename'] != "") {				
			$filename = $row['filename'];
			$filename = $this->siteUrl.$this->path_img.$filename;
		}
		else	{
			$filename = $this->siteUrl."img/noimg.jpg";
		}		
		/*Рейтинг*/
		$rating = $this->dec($row['rating']);
		$rating1 = number_format($rating, 1);
		$respondents = $this->dec($row['respondents']);
		//$id= $product_id;
		$reiting="				
				<input type=\"range\" id=\"rateit$id\" data-role=\"none\" max=\"5\" value=\"$rating\"/>
				<div class=\"rateit\" id='rait' data-productid=\"$id\" data-rateit-backingfld=\"#rateit$id\" style=\"float:left;margin-top: -1px;\"></div>
				<div itemprop=\"aggregateRating\" itemscope itemtype=\"http://schema.org/AggregateRating\" style=\"margin-left: 87px;display:none;\">
				<meta itemprop=\"ratingValue\" content=\"$rating1\" />
				<meta itemprop=\"bestRating\" content=\"5\" />
				Оценка: $rating1 из 5 (Всего голосов: <span itemprop=\"ratingCount\">$respondents</span>)
				</div>				 
				";
		/**/
		$views = $this->dec($row['views'])+1;
		$this->db->ExecuteSql ("Update `products` Set views='$views' Where product_id='$id'");
		$dateday = date("d", $row['news_date']);
		$datemonth = date("m", $row['news_date']);
		$dateyear = date("Y", $row['news_date']);
		$row1 = $this->db->GetEntry ("SELECT * FROM `category` WHERE category_id = '".$row['category']."'");
		$cat_name = $row1['title'];
		$cat_link = $this->siteUrl.CATALOG_LINK."/category/".$row1['url'];
		$data = array (
			"nav" =>$nav, 
			"title" => $this->dec($row["title"]),
			"text" => $this->dec($row["text"]),
			"short_text" => $this->dec($row["short_text"]),
			"head_title" =>$this->dec($row["head_title"])." - Кулинарный рецепт с фото",
			"description" =>$this->dec($row["description"]),
			"keywords" =>$this->dec($row["keywords"]),
			"filename" => $filename,
			"rating" => $reiting,     
			"views" => $views,
			"date_day" => $dateday,
			"date_month" => $datemonth,
			"date_year" => $dateyear,
			"link_cat" => $cat_link,
			"name_cat" => $cat_name,
			//"MAIN_CAT_ID" => $cat_id,
			"product_id" => $id,		
			"main_link" => $link,						
				"print" => $routes['path']."?action=print",
				//"message" => $message,			           
		);
		$data = $data + $this->GetFormComment($id);
		$print = $this->GetGP("print", 0);
		if ($print == 1) {
			
		}
		else {
			
		}
	
		/*----------------Индигриенты-------------*/
		$result = $this->db->ExecuteSql ("Select * From `indigrienty` Where parent_id='$id'");
		if ($result) {			
			while ($row = $this->db->FetchArray ($result)) 
			{
				$data['ingridients'][] = array (
					"row_ingridient" => $this->dec($row['value']),
				);
			}
			$this->db->FreeResult ($result);			
		}

		/*Комментарии*/
		$result = $this->db->ExecuteSql ("Select * From `comments` Where is_active='1' and module='products' and parent_id='$id' Order By news_date desc", false);
		if ($result) {			
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
		//------похожие рецепты
		$product_id =$id;
		$result = $this->db->ExecuteSql ("Select * From `products` Where is_active='1' and category='".$main_genre."' and product_id <> '".$product_id."' Order By RAND () LIMIT 9", false);
		if ($result) 
		{					
			while ($row = $this->db->FetchArray ($result)) 
			{
				$id = $row['product_id'];
				$p_url = $row['url'];
				$title = $this->dec($row['title']);
				$fullurl1 = GetLinkCat($this->menuarr, $row["parent_id"]);				
				$link = $this->siteUrl.CATALOG_LINK."/".$fullurl1.$p_url;				
				if ($row['filename'] != "") {
					$extension = substr($row['filename'], -3);
					$filename = substr($row['filename'], 0, -4)."_small.".$extension;
					$filename = $this->siteUrl."/media/products/".$filename;			
				}
				else {
					$filename = $this->siteUrl."img/noimg.jpg";
				}                      
				
				$data['similar'][] = array (
					"title" => $title,
					"filename" => $filename,										
					"link" => $link,
				);
			}
			$this->db->FreeResult ($result);
		}
		
		/*----------------Теги-------------*/
		$result = $this->db->ExecuteSql ("Select tags.name, tags.url From `tags`, `tags_value` Where tags_value.item_id='$product_id' and tags.tag_id = tags_value.tag_id", false);	
		if ($result) {						
			while ($row = $this->db->FetchArray ($result)) 
			{								
				$data['tags'][] = array (
					"row_name" => $this->dec($row['name']),
					"row_link" => "/".CATALOG_LINK."/tags/".$row['url'],
				);
			}
			$this->db->FreeResult ($result);			
		}				
		return $data;		
	}
	
	function act_asc ()
    {
		return $this->add_comment("products");
   }
	
	function get_rating()
	{
		$id = $this->GetGP("productID", 0);
		$value = $this->GetGP("value", 0);
		if ($value > 0)
		{
			$row = $this->db->GetEntry("Select rating, respondents from `products` Where product_id ='$id'");
			/*$res = $this->db->GetOne("Select respondents from `products` Where product_id ='$id'", 0);
			$rating = $this->db->GetOne("Select rating from `products` Where product_id ='$id'", 0);*/
			$res = $row['respondents'];
			$rating = $row['rating'];
			$res1=($res==0)?1:$res+1;
			$result = (($value - $rating)/$res1) + $rating;

			$res++;
			$this->db->ExecuteSql ("Update `products` Set rating='$result', respondents='$res' Where product_id='$id'");
		}
	}

}
