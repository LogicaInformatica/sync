<?php
//
// NOTA BENE: le costanti non possono essere ridefinite (almeno nella maggior parte delle versioni php)
//            quindi mettete fuori dallo "switch" sottostante solo le costanti che sono identiche in tutti gli ambienti
//            e dentro i case dello "switch" quelle che si differenziano almeno per uno degli ambienti
//
$sito = get_cfg_var('EB_ENVIRONMENT');
switch ($sito) {
        case 'lbit':
                define('SITE_NAME', 'Conn@&cut LabIt');
                define('SMS_TEST_NR','dummy'); // no sms
                define('MAIL_TEST', "g.difalco@logicainformatica.it"); // indica che l'invio mail non viene fatto, in realt�
                define('MAIL_OVERRIDE','g.difalco@logicainformatica.it');
                define('ADMIN_MAIL',"g.difalco@logicainformatica.it"); // mail dell'amministratore di sistema
                define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rima$
                define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
                define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
                define('LOGO_SOCIETA','<img src="images/nuovo_logo_TFSI.png">');
                define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
        break;
        case 'test':
                define('SITE_NAME', 'Conn@&cut Test');
                define('PROXY', ""); // usato nelle chiamate al DMS
                define('PROXYPORT', "");
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
                break;
        case 'prod':
                define('SITE_NAME', 'Conn@&cut');
                define('SMS_TEST_NR',''); // NON PER TEST
                define('MAIL_TEST', "");
                define('MAIL_OVERRIDE','collection@it.toyota-fs.com,francesca.bacci@toyota-fs.com,emanuela.rotondo@toyota-fs.com,CNCITDept@it.toyota-fs.com');
                define('ADMIN_MAIL',"CNCITDept@it.toyota-fs.com,g.difalco@logicainformatica.it,claudio.desantis01@gmail.com"); // mail dell'amministratore di sistema
                define('CERVED_MAIL',"CNCITDept@it.toyota-fs.com,g.difalco@logicainformatica.it,claudio.desantis01@gmail.com"); // mail dell'amministratore di sistema
                //              define('ADMIN_MAIL',"giorgio.difalco@gmail.com"); // mail dell'amministratore di sistema
                define('MEN_AT_WORK_PAGE',""); // pagina di avviso per interruzione servizio. Se impostata (es. Avviso.html) non fa entrare nell'app ma rimanda ad essa
                define('TEXT_NEWLINE', "\r\n");  // salto linea per le righe delle lettere formato testo semplice
                define('PROXY', "");
                define('PROXYPORT', "");
                define('PROXYUSERPWD', "");
                define('PROXYAUTH', "");
                define('LOGO_PRODOTTO','<img src="images/logo conn@&cut.jpg">');
                define('LOGO_SOCIETA','<img src="images/nuovo_logo_TFSI.png">');
                define('FOOTER','<div style="text-align:center;font-size:10px;color:gray">&copy; 2011-2015 Toyota Financial Services (UK) PLC - P.IVA 05303901002</div>');
                break;
}
define('NUM_VERSIONE','1.11.24');
define('DATA_VERSIONE','2017-05-24');

define('PORTAL_URL',   get_cfg_var('EB_WEB_PROTOCOL')."://portal".get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN')."/");
define('LINK_URL',   get_cfg_var('EB_WEB_PROTOCOL')."://cnc".get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN')."/");

//mysql
define('MYSQL_SERVER', get_cfg_var('EB_MYSQL_HOST'));//mysql-tfsi-lab.cwikgb27tggo.eu-west-1.rds.amazonaws.com');
define('MYSQL_USER',  get_cfg_var('EB_C_DB_USER'));// 'cnclabit');
define('MYSQL_PASS',  get_cfg_var('EB_C_DB_PASS')); //'cnclabit');
define('MYSQL_SCHEMA', 'db_cnc');
define('MYSQL_PORT',  get_cfg_var('EB_MYSQL_PORT')); //'3306');

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
define('LOG_PATH','/efs/cnc/logfiles'); // per i files di traccia
define('TMP_PATH','/efs/cnc/tmp');              // per i file di import/export temporanei
define('LETTER_PATH','/efs/cnc/tmp/lettere');           // per i file rotomail
define('LETTER_URL',LINK_URL.'tmp/lettere');            // per i file rotomail
define('ATT_PATH','/efs/cnc/attachments'); // per allegati
define('REL_PATH', 'attachments'); // inizio URL relativo per allegati
define('TMP_REL_PATH','tmp');      // inizio URL relativo per file in tmp
define('TEMPLATE_PATH','/efs/cnc/templates'); // per i templates di email, lettere ecc.

// URL da richiamare per le funzioni di lista del DMS: i parametri sono: (1) codice pratica senza prefisso, (2) prefisso LE/CO
define('DMS_API_LIST_URL',get_cfg_var('EB_WEB_PROTOCOL')."://desired".get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN').'/php/k2/sf/k2/web/app_dev.php/api/document/list/%s/%s');
// URL da richiamare per le funzioni di download di documenti dal DMS: i parametri sono: (1) ID del documento sul DMS, (2) token
define('DMS_API_GET_URL',get_cfg_var('EB_WEB_PROTOCOL')."://desired".get_cfg_var('EB_DNS_SUFFIX').".".get_cfg_var('EB_DNS_DOMAIN').'/php/k2/sf/k2/web/app_dev.php/api/document/file/%s/%s');
// Authorization key da specificare nell'header HTTP
define('DMS_API_KEY','5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8');

// Parametri per il funzionamento
define('MAX_BATCH_ERRORS',9999990);     // numero massimo di errori rilevati prima che un batch termini
define('MAX_FLAT_MENU',12);     // numero massimo di voci per il menÃ¹ flat (ad un livello)

define('ORA_FINE_GIORNO',20); // ora dopo la quale si considera il giorno dopo per l'affido
define('GIORNI_CANCELLAZIONE',90); // giorni dopo il quale si cancellano i dati di traccia
define('GG_CANC_OK_IMP_FILES',30); // giorni dopo il quale si cancellano i file importati ed corretamente elaborati (sotto cartella okFiles)
// parametri per range di estrazione del fine affido stragiudiziale
define('GG_ALLA_SCAD_AFF_STR',30); // vengono prelevate le pratiche che hanno una fine affido <= alla data attuale + il valore di questo param.
// parametro per range piano di rientro in scadenza
define('GG_ALLA_SCAD_RATA_PR',7); // vengono prelevate le pratiche che hanno una data pagamento rata >= alla data attuale + il valore di questo param.
define('GIORNI_RATA_SCAD',30); // vengono prelevate le pratiche che hanno come ultima data pagamento rata tra la data attuale e la data attuale - il valore di questo param.

define('INACTIVITY_TIMEOUT',1200);    // timeout di inattivitÃ  in secondi, dopo il quale Ã¨ richiesto un login
define('KEEPALIVE_TIME',180); // intervallo di polling per la chiamata di mantenimento in vita della sessione
define('BACKTRACE', true);
define('TRACE',true);   // mettere false per evitare la scrittura su trace.txt
define('SQLTIMING',false); // misura e traccia il tempo di esecuzione di ciascuna query: ATT.NE spegnere nel batch
?>
