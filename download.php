<?php 
// Programma usato nel link ai documenti, mandato per email, in modo da non esporre il vero indirizzo dei documenti nella
// cartella attachment. Il programma viene chiamato con id=XXXX-YYYYYYYYYYY, dove XXXX è l'Id dell'allegato e YYYYYYYYYY
// è il risultato di md5(IdContratto)
require_once("server/common.php");

try
{
	$id = split('-',$_GET['id']);
	if (count($id)!=2) {
		die("Il link non &egrave; valido oppure il documento non &egrave; pi$ugrave; disponibile");
	}
	
	$row = getRow("SELECT * FROM allegato WHERE IdAllegato={$id[0]}");
	extract($row);
	if (md5($IdContratto) !== $id[1]) {
		die("Il link non &egrave; valido oppure il documento non &egrave; pi$ugrave; disponibile");
	}
	
	
	
	$url = LINK_URL.$UrlAllegato;
	
	// Ritorna il contenuto in un iframe full screen, in modo che non si veda nella location il vero indirizzo del file
	// (anche se si può vedere nel sorgente HTML e con il debugger: non sono riuscito a ottenere lo stesso effetto col curl)
	echo <<<EOT
<html><head>
<style type="text/css">
body {
   margin: 0;
   overflow: hidden;
}
iframe {
    position:absolute;
    left: 0px;
    top: 0px;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    border: none;
}
</style>
</head>
<body>
<iframe frameborder="0" src="$url" width="100%" height="100%"/>
</body>
</html>
EOT;
} catch (Exception $e) {
	trace("edit_azione.php ".$e->getMessage());
	die($e->getMessage());
}	
?>