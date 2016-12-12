<?php
define('MYSQL_USER', 'rcatool');
define('MYSQL_PASS', '?cje4P53Bq6hb5mqh');
define('MYSQL_SCHEMA', 'rcatool');
define('FTP_SERVER','ftp.aioi-europe.eu'); 
//213.61.104.211'); 
//ftp.aioi-europe.eu');
define('FTP_PORT','21');
define('FTP_USER_NAME','ftpuser031');

define('PROXY_HOST','192.168.2.8');
define('PROXY_PORT','3128');
define('PROXY_USER','webservers');
define('PROXY_PASS','sette');

define('FTP_USER_PASS','Icl=D8:T.g');
//define('FTP_USER_PASS', 'sLCZyeD=wz');
define('FTP_LOCAL_FILE_PATH','../TFS_IN/');
define('FTP_REMOTE_FILE_PATH','/PROD/TFS_IN/');
define('FTP_REMOTE_FILE_PATH_GET','/PROD/TIM_OUT/');
define('FTP_REMOTE_FILE_PATH_ST','/PROD/TIM_IMPORTED/');
	if (!function_exists('ftp_connect'))
	{
		die("Funzione ftp_connect non disponibile");
	}

$conn_id = ftp_connect("ftp.aioi-europe.eu","21");
		$login_result = ftp_login($conn_id, FTP_USER_NAME, FTP_USER_PASS);
		if ((!$conn_id) || (!$login_result)) {
			die ("Errore Connessione FTP\n");
			return FALSE;
		}
ftp_pasv($conn_id,true);
//die ("id=$conn_id\n");

	echo "INIZIO CONNESSIONE CON PROXY \n<BR>";
		$commands   = array(
			"USER ".FTP_USER_NAME."@".FTP_SERVER." ".PROXY_USER,
			"PASS ".FTP_USER_PASS,
			"PASS ".PROXY_PASS
		);
		print_r($commands);
		$conn_id = ftp_connect(PROXY_HOST, PROXY_PORT);
		if (!$conn_id) {
			echo "Errore ftp_connect con proxy\n<BR>";
		} else {
			foreach($commands as $c) {
				echo "Invio comando $c";
				$ret = ftp_raw($conn_id,$c);
				if(!ftp_parse_response($ret,$errstr)) {
					ftp_close($conn_id);
					echo "Errore comando FTP $c: $errstr \n<br>";
				}
				else
					echo "Riuscito comando $c \n<br>";
			}
			echo "Connessione OK $conn_id \n<br>";
		}

	echo "INIZIO CONNESSIONE SENZA  PROXY \n<BR>";
		$conn_id = ftp_connect(FTP_SERVER, FTP_PORT);
		if (!$conn_id)
			echo "Errore ftp_connect senza proxy \n<br>";
		else
		{
			// login with username and password
			$login_result = ftp_login($conn_id, FTP_USER_NAME, FTP_USER_PASS);
			if (!$login_result) 
				echo "Errore ftp_login \n<br>";
			else
				echo "ftp_login OK";
		}
?>
