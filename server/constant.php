<?php
//
// NOTA BENE: le costanti non possono essere ridefinite (almeno nella maggior parte delle versioni php)
//            quindi mettete fuori dallo "switch" sottostante solo le costanti che sono identiche in tutti gli ambienti
//            e dentro i case dello "switch" quelle che si differenziano almeno per uno degli ambienti
//
$sito = 'PROD';
switch ($sito) {
	case 'LOCAL':
		define('SITE_NAME', 'Conn@&cut Local');
		define('MYSQL_SERVER', '77.43.16.58');
		define('MYSQL_USER',   'cnclabit');
		define('MYSQL_PASS',   'cnclabit');
		define('MYSQL_SCHEMA', 'db_cnc');
		define('MYSQL_PORT',   '3306');
		define('PORTAL_URL',   'http://localhost:4001/DCS/');
		define('LINK_URL',   'http://localhost:4001/DCS/');
		define('SMS_TEST_NR','dummy'); // no sms
		define('MAIL_TEST', "dummy"); // indica che l'invio mail non viene fatto, in realt�
		define('MAIL_OVERRIDE','dummy');
		define('ADMIN_MAIL',"dummy"); // mail dell'amministratore di sistema
		define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
		define('TEXT_NEWLINE', "\n");  // salto linea per le righe delle lettere formato testo semplice
		define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
		define('LOGO_SOCIETA','<img src="images/logo TFSI.gif">');
		define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
		break;
	case 'LABIT':
		define('SITE_NAME', 'Conn@&cut LabIt');
		define('MYSQL_SERVER', '127.0.0.1');
		define('MYSQL_USER',   'cnclabit');
		define('MYSQL_PASS',   'cnclabit');
		define('MYSQL_SCHEMA', 'db_cnc');
		define('MYSQL_PORT',   '3306');
		define('PORTAL_URL',   'https://portallabit.tfsi.it/');
		define('LINK_URL',   'https://cnclabit.tfsi.it/');
		define('SMS_TEST_NR','dummy'); // no sms
		define('MAIL_TEST', "g.difalco@logicainformatica.it"); // indica che l'invio mail non viene fatto, in realt�
		define('MAIL_OVERRIDE','g.difalco@logicainformatica.it');
		define('ADMIN_MAIL',"g.difalco@logicainformatica.it"); // mail dell'amministratore di sistema
		define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
		define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
		define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
		define('LOGO_SOCIETA','<img src="images/nuovo_logo_TFSI.png">');
		define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
		break;
	case 'TEST':
		define('SITE_NAME', 'Conn@&cut Test');
		define('MYSQL_SERVER', '192.168.12.10');
		define('MYSQL_USER',   'cncprod');
		define('MYSQL_PASS',   'cncprod');
		define('MYSQL_SCHEMA', 'db_cnc');
		define('MYSQL_PORT',   '3306');
		define('PROXY', "192.168.12.8"); // usato nelle chiamate al DMS
		define('PROXYPORT', "3128");
		define('PORTAL_URL',   'https://portaltest.tfsi.it/');
		define('LINK_URL',   'https://cnctest.tfsi.it/');
		define('SMS_TEST_NR','dummy'); // NUMERO DESTINATARIO FISSO PER I TEST (Di Falco)
		define('MAIL_TEST', "giorgio.difalco@gmail.com"); // indica dove vengono mandati tutti i msg di mail
		define('MAIL_OVERRIDE','giorgio.difalco@gmail.com');
		define('ADMIN_MAIL',"giorgio.difalco@gmail.com"); // mail dell'amministratore di sistema
		define('CERVED_MAIL',"g.difalco@logicainformatica.it"); // mail dell'amministratore di sistema
		define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
		define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
		define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
		define('LOGO_SOCIETA','<img src="images/nuovo_logo_TFSI.png">');
		define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
		define('SPECIAL_LINK','DCS.showModificaProvvigione(492466,140946,46)');
		break;
	case 'DEMO':
		define('SITE_NAME', 'DCSys DEMO');
		define('MYSQL_SERVER', 'localhost');
		define('MYSQL_USER',   'cncprod');
		define('MYSQL_PASS',   'cncprod');
		//define('MYSQL_SCHEMA', 'logica_dcsys'); // versione in chiaro
		define('MYSQL_SCHEMA', 'dcsys_o');        // versione con dati mascherati
		define('MYSQL_PORT',   '3306');
		define('PORTAL_URL',   'http://95.110.157.84/dcsys');
		define('LINK_URL',   'http://95.110.157.84/dcsys');
		define('SMS_TEST_NR','dummy'); // no sms
		define('MAIL_TEST', "g.difalco@logicainformatica.it"); // indica che l'invio mail non viene fatto, in realt�
		define('MAIL_OVERRIDE','g.difalco@logicainformatica.it');
		define('ADMIN_MAIL',"g.difalco@logicainformatica.it"); // mail dell'amministratore di sistema
		define('CERVED_MAIL',"g.difalco@logicainformatica.it"); // mail dell'amministratore di sistema
		define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
		define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
		define('FAVICON','favicon.png');
		define('LOGO_PRODOTTO','<img src="images/logo DCS banner.png">');
		define('LOGO_SOCIETA','<img src="images/logo LI banner.png">');
		define('FOOTER','<div style="text-align:center;">&copy; Copyright 2011-2015 Logica Informatica srl - Tutti i diritti riservati</div>');
		break;
	case 'PROD':
		define('SITE_NAME', 'Conn@&cut');
//		define('MYSQL_SERVER', '77.43.16.59');
		define('MYSQL_SERVER', '192.168.2.10');
		define('MYSQL_USER',   'cncprod');
		define('MYSQL_PASS',   'cncprod');
		define('MYSQL_SCHEMA', 'db_cnc');
//		define('MYSQL_PORT',   '3306');
		define('MYSQL_PORT',   '6446');  // passa attraverso il MySql Monitor
		define('PORTAL_URL',   'https://portal.tfsi.it/');
		define('LINK_URL',   'https://cnc.tfsi.it/');
		define('SMS_TEST_NR',''); // NON PER TEST 
		define('MAIL_TEST', "");
		define('MAIL_OVERRIDE','collection@it.toyota-fs.com,francesca.bacci@toyota-fs.com,emanuela.rotondo@toyota-fs.com,CNCITDept@it.toyota-fs.com');
		define('ADMIN_MAIL',"CNCITDept@it.toyota-fs.com,g.difalco@logicainformatica.it,claudio.desantis01@gmail.com"); // mail dell'amministratore di sistema
		define('CERVED_MAIL',"CNCITDept@it.toyota-fs.com,g.difalco@logicainformatica.it,claudio.desantis01@gmail.com"); // mail dell'amministratore di sistema
		//		define('ADMIN_MAIL',"giorgio.difalco@gmail.com"); // mail dell'amministratore di sistema
		define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
		define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
		define('PROXY', "192.168.2.8");
		define('PROXYPORT', "3128");
		define('PROXYUSERPWD', "webservers:sette");
		define('PROXYAUTH', "CURLAUTH_BASIC");
		define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
		define('LOGO_SOCIETA','<img src="images/nuovo_logo_TFSI.png">');
		define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
		break;
}
define('NUM_VERSIONE','1.11.2');

// Ultimo mese di un anno fiscale
define('LAST_FY_MONTH', "3");  // Marzo

// Invio Mail (provvisorio)
define('MAIL_SENDER','Toyota Financial Services <noreply@tfsi.it>');
define('MAIL_NEWLINE', "\n");  // salto linea per gli header della mail

// invio SMS
//define('SMS_USER','mn2971@mclink.it'); vecchio account, di IT non di Collection
//define('SMS_PWD','t0y0ta.13');
define('SMS_USER','mn6904@mclink.it');
define('SMS_PWD','t0y002.q');
define('SMS_API','476'); // usare 477 per sms di ritorno
define('SMS_SENDER','Toyota F.S.');

// Directories
define('LOG_PATH',dirname(__FILE__).'/../logfiles');	// per i files di traccia
define('TMP_PATH',dirname(__FILE__).'/../tmp'); 		// per i file di import/export temporanei		
define('LETTER_PATH',dirname(__FILE__).'/../tmp/lettere'); 		// per i file rotomail
define('LETTER_URL',LINK_URL.'tmp/lettere'); 		// per i file rotomail
define('ATT_PATH',dirname(__FILE__).'/../attachments'); // per allegati
define('REL_PATH', 'attachments'); // inizio URL relativo per allegati
define('TMP_REL_PATH','tmp');      // inizio URL relativo per file in tmp
define('TEMPLATE_PATH',dirname(__FILE__).'/../templates'); // per i templates di email, lettere ecc.

// URL da richiamare per le funzioni di lista del DMS: i parametri sono: (1) codice pratica senza prefisso, (2) prefisso LE/CO
define('DMS_API_LIST_URL','http://desired.tfsi.it/php/k2/sf/k2/web/app_dev.php/api/document/list/%s/%s'); 
// URL da richiamare per le funzioni di download di documenti dal DMS: i parametri sono: (1) ID del documento sul DMS, (2) token
define('DMS_API_GET_URL','http://desired.tfsi.it/php/k2/sf/k2/web/app_dev.php/api/document/file/%s/%s'); 
// Authorization key da specificare nell'header HTTP
define('DMS_API_KEY','5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8');

// Parametri per il funzionamento
define('MAX_BATCH_ERRORS',9999990);	// numero massimo di errori rilevati prima che un batch termini
define('MAX_FLAT_MENU',12);	// numero massimo di voci per il menù flat (ad un livello)

define('ORA_FINE_GIORNO',20); // ora dopo la quale si considera il giorno dopo per l'affido
define('GIORNI_CANCELLAZIONE',90); // giorni dopo il quale si cancellano i dati di traccia
define('GG_CANC_OK_IMP_FILES',30); // giorni dopo il quale si cancellano i file importati ed corretamente elaborati (sotto cartella okFiles)
// parametri per range di estrazione del fine affido stragiudiziale
define('GG_ALLA_SCAD_AFF_STR',30); // vengono prelevate le pratiche che hanno una fine affido <= alla data attuale + il valore di questo param.
// parametro per range piano di rientro in scadenza
define('GG_ALLA_SCAD_RATA_PR',7); // vengono prelevate le pratiche che hanno una data pagamento rata >= alla data attuale + il valore di questo param.
define('GIORNI_RATA_SCAD',30); // vengono prelevate le pratiche che hanno come ultima data pagamento rata tra la data attuale e la data attuale - il valore di questo param.

define('INACTIVITY_TIMEOUT',1200);    // timeout di inattività in secondi, dopo il quale è richiesto un login
define('KEEPALIVE_TIME',180); // intervallo di polling per la chiamata di mantenimento in vita della sessione
define('BACKTRACE', true); 
define('TRACE',true);   // mettere false per evitare la scrittura su trace.txt
define('SQLTIMING',false); // misura e traccia il tempo di esecuzione di ciascuna query: ATT.NE spegnere nel batch
?>