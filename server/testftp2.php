<?php
define('FTP_SERVER','88.33.222.78'); 
define('FTP_PORT','21');
define('FTP_USER_NAME','userw');
define('FTP_USER_PASS','fltymi1abfcU#');

define('PROXY_HOST','192.168.2.8');
define('PROXY_PORT','2121');

if (!function_exists('ftp_connect'))
{
	die("Funzione ftp_connect non disponibile");
}

echo "INIZIO TENTATIVO DI CONNESSIONE SENZA PROXY \n<BR>";

$conn = ftp_connect(FTP_SERVER,FTP_PORT);
if (!$conn) die("ftp_connect fallita \n<BR>");
ftp_pasv($conn,true);

if (!ftp_login($conn, FTP_USER_NAME, FTP_USER_PASS)) {
	echo "ftp_login fallita \n<BR>";
}

echo "INIZIO TENTATIVO DI CONNESSIONE CON PROXY \n<BR>";
$commands   = array(
	"USER ".FTP_USER_NAME."@".FTP_SERVER,
	"PASS ".FTP_USER_PASS
);
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

die();