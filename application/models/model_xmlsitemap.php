<?php
class Model_XMLSitemap extends Model 
{
	
	public function get_data() 
	{		
		$cachefile = $_SERVER['DOCUMENT_ROOT'].'/application/cache/cached-xmlsitemap.php';
		$cachetime = 18000;
		if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) 
		{
			$data["cache"] = true;
			$data["cachefile"] = $cachefile;
		}
		else
		{
			$data["cache"] = false;
			$data["cachefile"] = $cachefile;
			// главна€ страница
			$data["row"][] = array (
				"date" => date("Y-m-d", time()),
				"link" => $this->siteUrl,
				"priority" => "0.9",
				"changefreq" => "daily",
			);
			// меню		
			$result = $this->db->ExecuteSql ("SELECT update_date, url FROM `menus` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					// страницы внешние и внутренние с вложенностью 2 и более не выводим
					if (substr_count($row["url"], "/") < 2)
					{$link =$this->siteUrl.$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
					}
				}
				$this->db->FreeResult($result);
			}		
			// новости
			$result = $this->db->ExecuteSql ("SELECT update_date, url FROM `news` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$link =$this->siteUrl.NEWS_LINK."/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			// статьи
			$result = $this->db->ExecuteSql ("SELECT update_date, url FROM `articles` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$link =$this->siteUrl.ARTICLES_LINK."/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			// объ€влени€
			$result = $this->db->ExecuteSql ("SELECT update_date, url FROM `actions` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$link =$this->siteUrl.ACTIONS_LINK."/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			
			// продукци€
			$result = $this->db->ExecuteSql ("SELECT parent_id, update_date, url FROM `products` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$fullurl = GetLinkCat($this->menuarr, $row["parent_id"]);
					$link =$this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			
			// каталог
			$result = $this->db->ExecuteSql ("SELECT parent_id, update_date, url FROM `catalogs` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$fullurl = GetLinkCat($this->menuarr, $row["parent_id"]);
					$link =$this->siteUrl.CATALOG_LINK."/".$fullurl.$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			
			// категории
			$result = $this->db->ExecuteSql ("SELECT module, update_date, url FROM `category` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					switch ($row["module"])
					{
						case "products": $temp = CATALOG_LINK."/";break;
						case "articles": $temp = ARTICLES_LINK."/";break;
						default: $temp = "";
					}				
					$link =$this->siteUrl.$temp."category/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			
			// теги        
			$result = $this->db->ExecuteSql ("SELECT module, update_date, url FROM `tags` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					switch ($row["module"])
					{
						case "products": $temp = CATALOG_LINK."/";break;
						case "articles": $temp = ARTICLES_LINK."/";break;
						default: $temp = "";
					}				
					$link =$this->siteUrl.$temp."tags/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			
			// галереи
			$result = $this->db->ExecuteSql ("SELECT update_date, url FROM `gallery` WHERE news_date<='".time()."' AND is_active='1' ORDER BY update_date DESC");
			if ($result) 
			{
				while ($row = $this->db->FetchArray($result))
				{                
					$link =$this->siteUrl.GALLERY_LINK."/".$row['url'];
					$data["row"][] = array (
						"date" => date("Y-m-d", $row['update_date']),
						"link" => $link,
						"priority" => "0.6",
						"changefreq" => "monthly",
					);                
				}
				$this->db->FreeResult($result);
			}
			if (!file_exists($cachefile))
			{
				@file_put_contents($cachefile, '');
			}
			
			$fp = @fopen($cachefile, "w+");
			if ($fp)
			{
				$delim = "\r\n";
				fwrite($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>".$delim);
				fwrite($fp, "<?xml-stylesheet type=\"text/xsl\" href=\"/js/adm/sitemap.xsl\"?>".$delim);
				fwrite($fp, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">".$delim);
				foreach($data['row'] as $row)
				{
					fwrite($fp, "  <url>".$delim);
					fwrite($fp, "    <loc>".$row['link']."</loc>".$delim);
					fwrite($fp, "    <lastmod>".$row['date']."</lastmod>".$delim);
					fwrite($fp, "    <changefreq>".$row['changefreq']."</changefreq>".$delim);
					fwrite($fp, "    <priority>".$row['priority']."</priority>".$delim);
					fwrite($fp, "  </url>".$delim);
				}
				fwrite($fp, "</urlset>");
				// закрываем
				fclose($fp);
			}
		}	
		return $data;
	}

}
