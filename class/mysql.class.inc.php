<?php
	class Mysql
	{
		var $_link;
		var $_Connected;
		var $_HostArray;
		var $_User;
		var $_Pass;
		var $_Database;
		var $_Source;
		var $_Sql;
		var $_err;
		var $_Start;
        var $_Result;
		var $_microStart;
		var $_microEnd;
		var $_iSrv;
		var $_logDB;
		var $_lastInsert=-1;
		var $_errorCodes = array("1296");
		var $_lastMouvement = 0;
		
		function __construct($HOST,$USER,$PASS,$DATABASE, $unused=false)
		{
			$this->_HostArray=$HOST;
			$this->_User=$USER;
			$this->_Pass=$PASS;
			$this->_Database=$DATABASE;
			$this->_iSrv=0;
		}
		
		function IsConnected()
		{
			return !(empty($this->_Connected));
		}

		function IsConnectedTo()
		{
			return $this->_Connected;
		}
		
		function Ping()
		{
			return mysqli_ping($this->_link);
		}
		
		function Connect()
		{
			// On parcours le tableau de noeud SQL en comman_ant par _iSrv
			while(empty($this->_Connected) && $this->_iSrv<count($this->_HostArray))
			{
				$this->_link=@mysqli_connect($this->_HostArray[$this->_iSrv],$this->_User,$this->_Pass,$this->_Database);
				if (!mysqli_connect_error()) 
				{
					$this->_Connected=$this->_HostArray[$this->_iSrv];
					$this->_lastMouvement=time();
					mysqli_query($this->_link,"SET NAMES 'utf8'");
				}
				else
					$this->_iSrv++;
			}			
							
			return !empty($this->_Connected) ;
		}
		
		function Query($sql,$source="")
		{ 
			global $LOG_DB_REQUESTS, $APP_MODE, $NOTRE_ID, $DB_MAIL;
			
			if(preg_match("#information_schema\.#",$sql)
				||preg_match("#INFORMATION_SCHEMA\.#",$sql)
				||preg_match("#sysdate\(\)#",$sql)
				||preg_match("#SYSDATE\(\)#",$sql)
				||preg_match("#chr\(#",$sql)
				||preg_match("#CHR\(#",$sql)
			    ||preg_match("#char\(#",$sql)
				||preg_match("#CHAR\(#",$sql)
				||preg_match("#extractvalue\(#",$sql)
				||preg_match("#EXTRACTVALUE\(#",$sql)
			    ||preg_match("#sleep\(#",$sql)
				||preg_match("#SLEEP\(#",$sql)
				||preg_match("#waitfor delay#",$sql)
				||preg_match("#WAITFOR DELAY#",$sql)
			  	||preg_match("#response.write#",$sql)
			   	||preg_match("#gethostbyname#",$sql))
			{
				
				//Ancien mail pour vérifier le bon fonctionnement du nouveau systeme 
				mail($DB_MAIL, "ATTENTION : Injection SQL (ancien mail) ".$NOTRE_ID." ".$APP_MODE,$sql."\n\n".print_r(debug_backtrace(),true)."\n\n".print_r($_SERVER,true));
				
				
				$IP_LIST = ["10.2.117.",
							"10.2.118.",
							"10.192.1.",
							"10.192.0.",
							"185.26.104.",
							"185.26.105.",
							"185.26.106.",
							"185.26.107.",
							"198.50.216.",
							"46.29.126.162"];
				
				$ipInjection = $_SERVER["REMOTE_ADDR"];
				
				foreach($IP_LIST as $IP)
				{
					if(preg_match("#^".$IP."[0-9]*$#", $ipInjection))
					{
						$IpInterne = true;
						break;
					}
				}
				
				if(!$IpInterne)
				{
					$IP  = $this->QuoteSmartString($ipInjection);
					$sql_log = "insert into DB_INJECTIONS (IP, INJECTION_COUNT, DATE_LAST_INJECTION) VALUES ($IP, 1, NOW()) on duplicate key update INJECTION_COUNT=INJECTION_COUNT+1, DATE_LAST_INJECTION=NOW()";

					$res = @mysqli_query($this->_link, $sql_log);
					
					$reason = 'DB_ERROR';
				}
				else
				{
					$res = false;
					$reason = 'IP INTERNE';
				}
				
				if(!$res)
					mail($DB_MAIL, "ATTENTION : Injection SQL $NOTRE_ID $APP_MODE", "RAISON : $reason\n\n$sql\n\n".print_r(debug_backtrace(),true)."\n\n".print_r($_SERVER,true));
				
				return false;
			}
			
			$this->_Source=$source;
			$this->_lastInsert=-1;
			$this->_Sql=$sql;
			
			$delay = time() - $this->_lastMouvement;
			
			// On reconnecte si besoin ou si la dernière requête était il y a plus de 60 secondes
			if(!$this->IsConnected() || $delay > 30)
			{
				$this->_Connected="";
				$this->Connect();
			}
			
			// Execution de la reqûete
			$this->_Start=date("Y-m-d H:i:s");
			$this->_microStart=microtime(true);
 			$this->_Result=@mysqli_query($this->_link,$sql);
			$this->_microEnd=microtime(true);
			
			// Gestion des erreurs
			if(!$this->_Result)
			{
				$errno=@mysqli_errno($this->_link);
				if(in_array($errno,$this->_errorCodes))
				{
					// Erreur de connexion avec le cluster, on reconnect sur un autre noeud sql
					$this->_iSrv++; // on passe au noeud suivant
					if($this->_iSrv<count($this->_HostArray))
					{
						$this->Close(); // on deconnecte, la reconnection sera faire dans Query suivant
						return $this->Query($sql,$source);
					}
					else
						$this->LogError();
				}
				else
					$this->LogError();
				
				return false;
			}
			else
			{
				$this->_lastInsert = $this->GetInsertID();
				$this->_lastMouvement=time();
				// Log de la requête
				if($LOG_DB_REQUESTS)
					$this->LogRequest();
					
				return true;
			}
		}

		
		function GetLastConnectError()
		{
			if(!empty($this->_Source))
				$this->_err=$this->_Source."::";
			$this->_err.=@mysqli_connect_errno($this->_link) . ": " . mysqli_connect_error();
			return "<font color=red><b>".$this->_err."</b></font>";
		}
		
		function GetLastError()
		{
			if(!empty($this->_Source))
				$this->_err=$this->_Source."::";
			$this->_err.=@mysqli_errno($this->_link) . ": " . @mysqli_error($this->_link);
			//return "<font color=red><b>".$this->_err."</b></font>";
			return "";
		}
		
		function GetAffectedRows()
		{
			return mysqli_affected_rows($this->_link);
		}
		
		function GetInsertID()
		{
			if($this->_lastInsert!=-1)
				return $this->_lastInsert;
			else
				return mysqli_insert_id($this->_link);
			
		}
		
		function QuoteSmart($value,$len=0)
		{
			return $this->QuoteSmartString($value,$len);
		}
		
		function QuoteSmartString($value,$len=0)
		{
			
			if($len!=0&&strlen($value)>$len)
				$value = substr($value,0,$len);

		   	// Protection si ce n'est pas une valeur numérique ou une chaîne numérique
		   	if (! is_int($value) && ! is_float($value))
				$value = "'" . @mysqli_real_escape_string($this->_link,$value) . "'";
 			
			return $value;
		}
		
		function QuoteSmartLike($value,$before=1,$after=1)
		{				
			if($before)
				$value="%".$value;
			
			if($after)
				$value.="%";
			
			return $this->QuoteSmartString($value);
		}
		
		function QuoteSmartNumeric($value)
		{
			$value = str_replace(",",".",$value);
			
			if(empty($value)||is_null($value)||!is_numeric($value))
				return 0;
 			
			return $value;
		}
		
		function QuoteSmartDate($value)
		{
			$value = trim($value);
			if(empty($value)||is_null($value))
				return "null";
			
			$value = str_replace("/","-",$value);
			$value = str_replace("T"," ",$value);
			$value = substr($value,0,19);

			$value = "'" . @mysqli_real_escape_string($this->_link,$value) . "'";
 			
			return $value;
		}

		function BeforeSql($string)
		{
			$string=stripslashes($string);
			$string=str_replace('"',"''",$string);
			return $string;
		}

		function AfterSql($string)
		{
			$string=stripslashes($string);
			return $string;
		}
		
		function GetNumRows()
		{
			return mysqli_num_rows($this->_Result);
		}
		
		
		function FetchObject()
		{
			return mysqli_fetch_object($this->_Result);
		}
		
		function FetchArray()
		{
			return mysqli_fetch_array($this->_Result);
		}
		
		function FetchArrayAssoc()
		{
			return mysqli_fetch_assoc($this->_Result);
		}
		
		function Close()
		{
			if( $this->IsConnected() )
				mysqli_close($this->_link);
			$this->_Connected = '';
		}
		
		function LogError()
		{
			global $APP_MODE,$NOTRE_ID,$DB_MAIL;
			
			$this->GetLastError();
			
			$this->_Sql=str_replace("\n"," ",$this->_Sql);
			
			$sql="INSERT INTO DB_ERRORS (SQL_ERR, ERROR_ERR, DATE_ERR,SOURCE_ERR) VALUES (".$this->QuoteSmart($this->_Sql).", ".$this->QuoteSmart($this->_err." ".$_SERVER["REMOTE_ADDR"]).",now(), ".$this->QuoteSmart($this->_Source).")";
			$res = @mysqli_query($this->_link,$sql);
			if(!$res)
			{
				mail($DB_MAIL, "Mysql::LogError() ".$NOTRE_ID." ".$APP_MODE,"SQL_ERR : \n".$this->_Sql."\n\n\nERROR_ERR : \n".$this->_err."\n\n\nDATE_ERR : \n".date("Y-m-d H:i:s")."\n\n\nSOURCE_ERR : \n".$this->_Source."\n\n\nSOURCE_FILE : \n".$_SERVER['PHP_SELF']."\n\n\n\n\nSQL : \n".$sql."\n\n\n\n\n".print_r($_SERVER,true));
			}
		}
		
		function LogRequest()
		{
			$this->_Sql=str_replace("\n"," ",$this->_Sql);
			$delai=number_format($this->_microEnd-$this->_microStart,2,'.','');
			//if($delai>1)
			{
				$sql="INSERT INTO DB_REQUESTS (SQL_REQUEST, SOURCE_REQUEST,START,DUREE) VALUES (".$this->QuoteSmart($this->_Sql).", ".$this->QuoteSmart($this->_iSrv.":".$this->_Source).",'".$this->_Start."',".$delai.")";
				@mysqli_query($this->_link,$sql);
			}
		}
	}
?>