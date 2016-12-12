<?php

//ini_set('display_errors', 1); 
//error_reporting(-1);

$buffer = array("authlogin" => "mn6904@mclink.it",
       	"authpasswd" => "t0y002.q",
		"sender" => base64_encode("Toyota F.S."),
		"body" => base64_encode("Prova invio n.1"),
		"destination" => "393483331774",
		"id_api" => "476"); 
				
//Inizializza e invia
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://secure.apisms.it/http/send_sms");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $buffer);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PROXY, "192.168.2.8");
curl_setopt($ch, CURLOPT_PROXYPORT, "3128");
curl_setopt($ch, CURLOPT_PROXYUSERPWD, "webservers:sette");
curl_setopt($ch, CURLOPT_PROXYAUTH, "CURLAUTH_BASIC");

$ret = curl_exec($ch);
curl_close($ch);
echo "Invio diretto ret=".$ret."<br>\n";
?>
