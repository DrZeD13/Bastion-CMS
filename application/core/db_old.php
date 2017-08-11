<?
// Класс базыданных
class DB
{
    var $dbConnect;

    //--------
    function DB ()
    {
        $this->dbConnect = $this->OpenDbConnect ();
        $this->ExecuteSql ("Set names utf8");
    }

    //----------
    function OpenDbConnect ($host = HOST, $dbName = DATABASE, $login = USER, $pwd = PASSWORD)
    {
        $connect = mysql_connect ($host, $login, $pwd) or die('Ошибка соединения: ' . mysql_error());
        mysql_select_db ($dbName) or die('Ошибка подключения к бд: ' . mysql_error());
        return $connect;
    }

    //--------
    function ExecuteSql ($sql, $withPaging = "")
    {
        if ($withPaging != "") {			
            $sql.=$withPaging;
        }        
		return mysql_query ($sql, $this->dbConnect);
    }

    //-------------
    function GetOne ($sql, $defVal = "")
    {
        $toRet = $defVal;
        $result = $this->ExecuteSql ($sql);
        if ($result != false) {
            $line = $this->FetchRow ($result);
            $toRet = $line[0];
            $this->FreeResult ($result);
        }
        if ($toRet == NULL) $toRet = $defVal;
        return $toRet;
    }

    //-----------
    function GetEntry ($sql, $redir_url = "")
    {
        $result = $this->ExecuteSql ($sql);
        if ($row = mysql_fetch_array ($result))
        {
            $this->FreeResult ($result);
            return $row;
        }
        else
        {
            if (strlen ($redir_url) > 0) 
			{
                $this->Close ();
                header ("Location: $redir_url");
                exit ();
            }
            else 
			{
                return false;
            }
        }
    }

	function FetchRow ($result)
	{
		return mysql_fetch_row ($result);
	}
	
	function FetchArray ($result)
	{
		return mysql_fetch_array($result);
	}
	
	function FreeResult ($result)
	{
		return mysql_free_result($result);
	}
	
	function RealEscapeString  ($result)
	{
		return mysql_real_escape_string ($result);
	}
	
	//--------------------------
    function GetInsertID ()
    {
        return mysql_insert_id ($this->dbConnect);
    }

    //-------------------------
    function GetSetting ($keyname, $defVal = "")
    {
        global $SETTING;
		$toRet = $defVal;
		/*if (file_exists($_SERVER["DOCUMENT_ROOT"]."/application/core/setting.php"))
		{*/						
			if (isset($SETTING[$keyname]["value"]))
			{
				$toRet = $SETTING[$keyname]["value"];
			}
		/*}*/
		/*$toRet = $defVal;
        $result = $this->ExecuteSql ("Select value From `settings` Where keyname='$keyname'");		
        if ($result != false) {
            $line = mysql_fetch_row ($result);
            $toRet = $line[0];
            mysql_free_result ($result);
        }
        if ($toRet == NULL) $toRet = $defVal;*/
        return $toRet;
    }

    //--------------------------
    function SetSetting ($keyname, $value)
    {
        $this->ExecuteSql ("Update `settings` Set value='$value' Where keyname='$keyname'");
    }

	function GetAdminTitle($val, $val1 = "")
	{
		return $this->GetOne("SELECT title FROM `admin_pages` WHERE keyname='".$val."'", $val1);
	}
	
    //------------------
    function Close ()
    {
        mysql_close ($this->dbConnect);
    }

}