<?php
	echo "(1)";
	require_once("server/common.php");
	echo "(2)";
/**
 * FUNZIONI PER ESTRAZIONE E COMUNICAZIONE CON Experian
 */
connectToExperian($error);
echo "<br>MESSAGGIO:$error<br>";

function my_ssh_disconnect($reason, $message, $language) {
	printf("<br>Server disconnected with reason code [%d] and message: %s\n",$reason, $message);
}
function my_ssh_ignore($message) {
	printf("<br>Server ignore with message: %s\n",$message);
}

function my_ssh_debug($message, $language, $always_display) {
	printf("<br>Server debug with message: %s\n",$message);
}

function my_ssh_macerror($packet) {
	printf("<br>Server macerror with packet: %s\n",print_r($packet,true));
}

/**
 * connectToExperian
 * Apre la connessione SFTP con experian
 * @return {Object} connessione aperta ($sftp)
 */
function connectToExperian(&$error) {
	global $ssh;
 
	if (!function_exists('ssh2_connect')) {
		$error = "Funzione ssh2_connect non disponibile";
		return false;
	}
	// SSH Host
	$ssh_host = 'st.uk.experian.com'; // '194.60.191.31' 
	// SSH Port
	$ssh_port = 22;
	// SSH Username
	$ssh_auth_user = 'cgtp5566toyoyatfs';
	// SSH Public Key File  (SI TROVANO NELLA CARTELLA ROOT DEL SITO (cnc/cnctest)
	$ssh_auth_pub = __DIR__.'/id_rsa.pub';
	// SSH Private Key File
	$ssh_auth_priv = __DIR__.'/id_rsa';
	// SSH Private Key Passphrase (null == no passphrase)
	$ssh_auth_pass = null;
	// SSH Connection
	$ssh = null;
	
	// Connect
	echo "<br>lancio connessione";
	$ssh = ssh2_connect($ssh_host, $ssh_port, array('hostkey'=>'ssh-rsa,ssh-dss'),
			array('disconnect' => 'my_ssh_disconnect',
			  	  'ignore'     => 'my_ssh_ignore',
				  'debug'      => 'my_ssh_debug',
   				  'macerror'   => 'my_ssh_macerror')
			);
	if (!$ssh) {
		$error = "Connessione al server $ssh_host:$ssh_port non riuscita";
		return false;
	}
	
	// Passa le chiavi di sicurezza
	echo "<br>lancio ssh2_auth_pubkey_file: $ssh_auth_user, $ssh_auth_pub, $ssh_auth_priv, $ssh_auth_pass";
	if (!ssh2_auth_pubkey_file($ssh, $ssh_auth_user, $ssh_auth_pub, $ssh_auth_priv, $ssh_auth_pass)) {
		$error = "Autenticazione con il server fallita (user=$ssh_auth_user, public key=$ssh_auth_pub, private_key=$ssh_auth_priv";
		return false;
	}
	
	// Collega in SFTP
	echo "<br>lancio sftp";
	$sftp = ssh2_sftp($ssh);
	if (!$sftp) {
		$error = "Fallita apertura della comunicazione SFTP";
		disconnectFromExperian();
		return false;
	}
	return $sftp;
}

/**
 * ssh_exec
 * Esegue un comando ssh
 */
function ssh_exec($cmd,&$error) {
	global $ssh;
	if (!($stream = ssh2_exec($ssh, $cmd))) {
		$error = "Comando ssh '$cmd' fallito";
		return false;
	}
	stream_set_blocking($stream, true);
	$data = "";
	while ($buf = fread($stream, 4096)) {
		$data .= $buf;
	}
	fclose($stream);
	return $data;
}

/**
 * sftp_getFiles
 * Restituisce una lista di files di una data cartella sftp
 */
function sftp_getFiles($dir,&$error) {
	$sftp = connectToExperian($error);
	if (!$sftp)
		return false;
	
	try {
		$dir = "ssh2.sftp://$sftp/$dir";
	    $arr = array();
    	$handle = opendir($dir);
	    while (false !== ($file = readdir($handle))) {
    		if (substr("$file", 0, 1) != "." and !is_dir($file)) {
            	  $arr[]=$file;
        	}
    	}
    	disconnectFromExperian();
    	return $arr;
   	} catch (Exception $e) {
		$error = $e->getMessage();
    	disconnectFromExperian();
		return false;
	}
}