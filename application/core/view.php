<?php

class View 
{

	function generate($template_view, $content_view ="",  $head = null, $data = null)	
	{
		
		header("Cache-Control: public, max-age=2592000");
		header("Expires: " . date("r", time() + 604800));
		header("Connection: Keep-Alive");
		header("Keep-Alive: timeout=5, max=100");
		// тут нужно сделать что бы передовалась дата поседнего изменения бд она есть
		//header("HTTP/1.x 304 Not Modified");	
		// решить проблему с HTTP_IF_MODIFIED_SINCE на хостинге что-то блокирует
		//echo $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		header("Last-Modified: ".gmdate("r", time()-3600)." GMT");
		include 'application/views/'.$template_view;
	}
	
	function generate_adm($template_view, $content_view ="", $head = null, $data = null)	
	{
		include 'application/views/adm/'.$template_view;
	}
}
