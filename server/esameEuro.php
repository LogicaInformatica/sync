<?php 
// controllo dei link ai dossier Euroinvestigation
require_once("common.php");
$EIPATH = ATT_PATH.'/euroInvestigation';

$links = getRows("SELECT IdContratto,IdAllegato,UrlAllegato,DATE_FORMAT(lastupd,'%Y%m%d') AS DataAllegato "
    . "FROM allegato WHERE UrlAllegato like '%euroinv%'");
message("Individuati ".count($links)." allegati da controllare");
foreach ($links as $link) {
    extract($link);
    $fileName = pathinfo($UrlAllegato,PATHINFO_BASENAME);
    // Costruisce il path fisico
    $filePath = "$EIPATH/$fileName";
    if (!preg_match('/^\d{8}_/',$fileName)) { // se non è già nome prefissato
        $fileName2 = "{$DataAllegato}_$fileName";
    } else { // è prefissato, prova con quello non prefissato
        $fileName2 = substr($fileName,9);
    }
    $filePath2 = "$EIPATH/$fileName2";
    if (!file_exists($filePath)) {
        if (!file_exists($filePath2)) {
           message("File $fileName non trovato (né $fileName2), IdContratto=$IdContratto, data=$DataAllegato");
        } else {
            // Controlla data del file
            $data = date('Ymd',filectime($filePath2));
            if ($data != $DataAllegato) {
                message("File $filePath2 ha data $data ma la riga di allegato ha data $DataAllegato, IdContratto=$IdContratto");
            } else {
                message("File $fileName2 OK invece di $fileName; cambio URL");
                // Dato che il file giusto ha il nome diverso, cambia il link nell'URL
                if (!execute("UPDATE allegato SET UrlAllegato=REPLACE(UrlAllegato,'$fileName','$fileName2') WHERE IdAllegato=$IdAllegato")) {
                    die(getLastError());
                }
            }
        }
    } else {
        // Controlla data del file
        $data = date('Ymd',filectime($filePath));
        if ($data != $DataAllegato) {
            message("File $fileName ha data $data ma la riga di allegato ha data $DataAllegato, IdContratto=$IdContratto");
        } else {
            message("File $fileName OK");
        }
    }
}

function message($msg) {
    echo "\n<br>$msg";
}
