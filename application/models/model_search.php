<?php

class Model_Search extends Model 
{	
	
	public function get_data() 
	{
		$search = $this->GetGP_SQL("search", "");
		if ($search=="")
		{
			$this->error404();
		}
		$data['head_title'] = $data['title'] = "Поиск";
		$data['keywords'] = "";
		$data['description'] = "";
		$data['search'] = $search;
		
		$data["nav"] = MAIN_NAV."Поиск";
		
		$fulltotal =0;
		if (strlen($search) > 2) 
		{
			//по меню сайта
			$fromwhere=" FROM menus WHERE (text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, text, url".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => "/".$row["url"],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['text']), $search),
						"category" => "Страницы сайта",
						"filename" => "",
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по новостям
			$fromwhere=" FROM news WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, short_text, text, url, filename".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					if ($row['filename'] != "") 
					{
						$extension = substr($row['filename'], -3);
						$filename = substr($row['filename'], 0, -4)."_small.".$extension;
						$filename = $this->siteUrl."/media/news/".$filename;
					}
					else
					{
						$filename = "";
					}
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => "/".NEWS_LINK."/".$row["url"],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Новости",
						"filename" => $filename,
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по статьям
			$fromwhere=" FROM articles WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, short_text, text, url, filename".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					if ($row['filename'] != "") 
					{
						$extension = substr($row['filename'], -3);
						$filename = substr($row['filename'], 0, -4)."_small.".$extension;
						$filename = $this->siteUrl."/media/articles/".$filename;
					}
					else
					{
						$filename = "";
					}
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => "/".ARTICLES_LINK."/".$row["url"],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Блог",
						"filename" => $filename,
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по объявления
			$fromwhere=" FROM actions WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, short_text, text, url, filename".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					if ($row['filename'] != "") 
					{
						$extension = substr($row['filename'], -3);
						$filename = substr($row['filename'], 0, -4)."_small.".$extension;
						$filename = $this->siteUrl."/media/actions/".$filename;
					}
					else
					{
						$filename = "";
					}
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => "/".ACTIONS_LINK."/".$row["url"],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Объявления",
						"filename" => $filename,
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по категориям
			$fromwhere=" FROM category WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, short_text, text, url, module".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					switch ($row["module"])
					{
						case "products": $temp = CATALOG_LINK."/";break;
						case "articles": $temp = ARTICLES_LINK."/";break;
						default: $temp = "";
					}					
					$filename = "";
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => $this->siteUrl.$temp."category/".$row['url'],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Категории",
						"filename" => $filename,
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по тегам
			$fromwhere=" FROM tags WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT title, short_text, text, url, module".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					switch ($row["module"])
					{
						case "products": $temp = CATALOG_LINK."/";break;
						case "articles": $temp = ARTICLES_LINK."/";break;
						default: $temp = "";
					}					
					$filename = "";
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => $this->siteUrl.$temp."tags/".$row['url'],
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Теги",
						"filename" => $filename,
					);
				}
				$this->db->FreeResult ($result);
			}
			
			// по продукции
			$fromwhere=" FROM products WHERE (short_text LIKE '%$search%' OR text LIKE '%$search%' OR title LIKE '%$search%') AND is_active='1' ORDER BY news_date desc";
			$total = $this->db->GetOne("SELECT Count(*)".$fromwhere, 0);
			$fulltotal +=$total;
			if ($total > 0) 
			{
				$result = $this->db->ExecuteSql("SELECT parent_id, title, short_text, text, url, filename".$fromwhere);			
				while ($row = $this->db->FetchArray ($result))	
				{					
					$fullurl = GetLinkCat($this->menuarrtree, $row["parent_id"]);
					$link =$this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
					if ($row['filename'] != "") 
					{
						$extension = substr($row['filename'], -3);
						$filename = substr($row['filename'], 0, -4)."_small.".$extension;
						$filename = $this->siteUrl."/media/products/".$filename;
					}
					else
					{
						$filename = "";
					}
					$data["row"][] = array (
						"title" => $row["title"],
						"link" => $link,
						"text" => $this->Search($this->dec($row ['title']).".".$this->dec($row ['short_text']).".".$this->dec($row ['text']), $search),
						"category" => "Рецепты",
						"filename" => $filename,
					);					
				}
				$this->db->FreeResult ($result);
			}
			
			if ($fulltotal == 0) 
			{
				$data["empty"] = "По вашему запросу '$search' ни чего не найдено";
			}
		}
		else
		{
			$data["empty"] = "По вашему запросу '$search' ни чего не найдено";
		}
		$data["total"] = $fulltotal;
		return $data;
	}

}
