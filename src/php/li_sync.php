<?php
/**
 * Funzioni php per la sincronizzazione Server => App
 */
/** 
 * SI PRESUME CHE QUESTO FILE SIA INCLUSO ALL'INTERNO DI UN main program che provvede alle necessita' del contesto,
 * includendo le dipendenze appropriate (li_common.php, config.php)
 */

/**
 * li_getupdates restituisce i dati per l'update di database, javascript e HTML dell'App
 * @param {Boolean} $return opzionale, se true indica che non deve terminare con success, ma piuttosto
 *  ritornare il risultato al chiamante (quando viene chiamato al termine di un'altra funzione dispositiva, tipo la
 *  prenotazione, per restituire nuovi aggiornamenti)
 * @param {String} $sync_prefix_local (in $_REQUEST) prefisso che contraddistingue i nomi delle tabelle interessate in SQLite
 *  (puo' essere stringa vuota, per default viene usato "", come nell'App Dior)
 * @param {String} $sync_prefix_remote (in $_REQUEST) prefisso che contraddistingue i nomi delle view e tabelle
 *  interessate in MySql (non puo' essere stringa vuota, per default viene usato "sync_")
 * @param {String} $sync_path path su cui si trovano i file .js e .html da inviare (per default "./sync")
 */
function li_getupdates($return=false) {
    extract($_REQUEST);
    
    if ($sync_prefix_local) $sync_prefix_local = '';
    if (!$sync_prefix_remote) $sync_prefix_remote = 'sync_';
    if (!$sync_path) $sync_path = './sync';
    
    // Le modifiche ai files javascript sono files di suffisso .js messi nella cartella di sync
    $updjs = [];
    $maxTime = "";
    li_trace("Individua files di aggiornamento javascript in $sync_path");
    foreach (scandir($sync_path) as $file) {
        if (is_dir($file) || pathinfo($file,PATHINFO_EXTENSION)!='js') continue;
        $filetime = date('Y-m-d H:i:s',filemtime("$sync_path/$file"));
        if ($filetime>$lastupd) {
            $updjs[] = file_get_contents("$sync_path/$file");
            if ($maxTime<$filetime) $maxTime = $filetime;
        }
    }
    li_trace("Trovati ".count($updjs)." files javascript di aggiornamento");
    
    // Le modifiche ai files html sono files di suffisso .html messi nella cartella di sync
    // l'array restituito ha elementi con le due proprieta' 'name' e 'content'
    $updhtml = [];
    $maxTime = "";
    li_trace("Individua files di aggiornamento HTML in $sync_path");
    foreach (scandir($sync_path) as $file) {
        if (is_dir($file) || pathinfo($file,PATHINFO_EXTENSION)!='html') continue;
        $filetime = date('Y-m-d H:i:s',filemtime("$sync_path/$file"));
        if ($filetime>$lastupd) {
            $updhtml[] = ["name"    => $file,
                          "content" => file_get_contents("$sync_path/$file")
                         ];
            if ($maxTime<$filetime) $maxTime = $filetime;
        }
    }
    li_trace("Trovati ".count($updhtml)." files HTML di aggiornamento");
        
    
    // Legge le eventuali istruzioni SQL di modifica del DB sqlite dalla tabella app_sql
    li_trace("Individua istruzioni SQL da applicare (app_sql)");
    $sql = "SELECT SqlStatement,LastUpd FROM app_sql WHERE LastUpd>'$lastupd' ORDER BY IdSql";
    $stmts = li_getRows($sql);
    if (li_getLastError()>'') li_fail();
    $updsql = [];
    foreach ($stmts as $stmt) {
        $updsql[] = $stmt['SqlStatement'];  // è come se il comando SQL fosse un file xxxx.sql
        $maxTime = max($maxTime, $stmt['LastUpd']);
    }
    //trace("Individuate ".count($updsql)." istruzioni sql da applicare");
    
    // Legge le modifiche di varie tabelle come istruzioni SQL
    $prefix = str_replace('_',"\\_",$sync_prefix_remote);
    $tables = li_getColumn("SELECT table_name FROM information_schema.tables "
        . "WHERE table_name like '{$prefix}%' AND table_name!='app_sql' order by 1");
    if (li_getlastError()>'') li_fail();

	foreach ($tables as $table) {
        // NB: il nome tabella da usare per SQLite usa un prefisso eventualmente diverso da quello MySql
	    li_sync_createSqlForTable($table,
                                  str_replace($sync_prefix_remote,$sync_prefix_local,$table),
                                  $lastupd,$updsql,$maxTime);
	}    
    
    $response = ["maxTime"=>$maxTime, "updjs"=>$updjs, "updhtml"=>$updhtml, "updsql"=>$updsql];
    if ($return)
    	return $response;
	else 
	    li_success($response);
}

/**
 * li_sync_createSqlForTable Crea una istruzione INSERT OR REPLACE per ogni riga di una data tabella app_xxxx modificata
 *  dopo la data fornita. (deve essere una istruzione SQLite)
 * @param {String} $table nome tabella su MySql
 * @param {String} $targetTable nome tabella su SQLite
 * @param {String} $lastupd data ultimo aggiornamento ricevto dall'App
 * @param {Array} $updates array delle istruzioni di aggiornamento già accumulate (by ref)
 * @param {String} $maxTime massimo valore di Lastupd trovato (by ref)
 */
function li_sync_createSqlForTable($table,$targetTable,$lastupd,&$updsql,&$maxTime) {
    $sql = "SELECT * FROM $table WHERE LastUpd>'$lastupd'";
    $rows = li_getRows($sql);
    if (li_getLastError()>'') fail();
    li_trace("Individuate ".count($rows)." righe aggiornate nella tabella $table ($sql)");
    foreach ($rows as $row) {
        $values = array_values($row); // prende i soli valori
        foreach ($values as &$value) {
            $value = str_replace("'","''",$value);
        }
        $updsql[] = "INSERT OR REPLACE INTO $targetTable VALUES('".implode("','",$values)."')";
        if ($maxTime<$row['LastUpd']) $maxTime = $row['LastUpd'];
    }
}
