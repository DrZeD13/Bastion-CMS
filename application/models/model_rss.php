<?php

class Model_Rss extends Model 
{
	
	public function get_data() 
	{
		$cachefile = $_SERVER['DOCUMENT_ROOT'].'/application/cache/cached-rss.php';
		$cachetime = 18000;
		$data["cachefile"] = $cachefile;
		if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) 
		{
			$data["cache"] = true;
		}
		else
		{
			$data["cache"] = false;		
			$data['title'] = $this->db->GetSetting("SiteTitle");
			$data['link'] = $this->siteUrl."rss/";
			$data['description'] = $this->db->GetSetting("Description");
			// новости
			$total = $this->db->GetOne ("SELECT Count(*) FROM `articles` WHERE news_date<='".time()."' AND is_active='1'", 0);
			if ($total > 0) 
			{
				$data["cache"] = false;
				$data["cachefile"] = $cachefile;
				
				$result = $this->db->ExecuteSql ("SELECT title, news_date, description, url FROM `articles` WHERE news_date<='".time()."' AND is_active='1' ORDER BY news_date DESC LIMIT 20");
				while ($row = $this->db->FetchArray ($result))
				{                
					$link =$this->siteUrl.ARTICLES_LINK."/".$row['url'];
					$data["row"][] = array (
						"date" => date ("r", $row['news_date']),
						"link" => $link,
						"title" => $row['title'],
						"description" => $row['description'],
					);                
				}
				$this->db->FreeResult($result);
			}
			
			if (!file_exists($cachefile))
			{
				@file_put_contents($cachefile, '');
			}
			$delim = "\r\n";
			$fp = @fopen($cachefile, "w+");
			if ($fp)
			{
				fwrite($fp, "<?xml version=\"1.0\" encoding=\"utf-8\"?>".$delim);
				fwrite($fp, "<rss xmlns:dc=\"http://purl.org/dc/elements/1.1/\" version=\"2.0\">".$delim);
				fwrite($fp, "<channel>".$delim);
				fwrite($fp, "  <title>".$data['title']."</title>".$delim);
				fwrite($fp, "  <link>".$data['link']."</link>".$delim);
				fwrite($fp, "  <description>".$data['description']."</description>".$delim);
				fwrite($fp, "  <language>ru</language>".$delim);
				
				foreach($data['row'] as $row)
				{
					fwrite($fp, "  <item>".$delim);
					fwrite($fp, "    <title>".$row['title']."</title>".$delim);
					fwrite($fp, "    <link>".$row['link']."</link>".$delim);
					fwrite($fp, "    <description>".$row['description']."</description>".$delim);
					fwrite($fp, "    <pubDate>".$row['date']."</pubDate>".$delim);
					fwrite($fp, "  </item>".$delim);
				}
				fwrite($fp, "</channel>".$delim);
				fwrite($fp, "</rss>");
				// закрываем
				fclose($fp);	
			}
		}	
		return $data;
	}

}
