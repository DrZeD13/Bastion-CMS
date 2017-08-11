<?
// Класс базыданных
class DB
{
    var $dbConnect;

    //--------
    function DB ()
    {
        $this->mysqli = $this->OpenDbConnect ();
        $this->ExecuteSql ("Set names utf8");
    }

    //----------
    function OpenDbConnect ($host = HOST, $dbName = DATABASE, $login = USER, $pwd = PASSWORD)
    {		
		$mysqli = new mysqli($host, $login, $pwd, $dbName);		
		if ($mysqli->connect_errno) {
			die('Ошибка соединения: ' . $mysqli->connect_error);
		}		
		return $mysqli;
    }

    //--------
    function ExecuteSql ($sql, $withPaging = "")
    {
        if ($withPaging != "") {			
            $sql.=$withPaging;
        }        	
		return $this->mysqli->query ($sql);
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
        if ($row = $this->FetchArray ($result))
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
		return mysqli_fetch_row ($result);
	}
	
	function FetchArray ($result)
	{
		return mysqli_fetch_array($result);
	}
	
	function FreeResult ($result)
	{
		return mysqli_free_result($result);
	}
	
	function RealEscapeString  ($result)
	{
		return $this->mysqli->real_escape_string ($result);
	}

	//--------------------------
    function GetInsertID ()
    {
        return $this->mysqli->insert_id;
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
				$toRet = stripslashes($SETTING[$keyname]["value"]);
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
       $this->mysqli->close ();
    }

}