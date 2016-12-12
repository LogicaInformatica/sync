<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

$curl = curl_init();

$url = "http://desiredlabit.tfsi.it/php/k2/sf/k2/web/app.php/api/document/list/664958/CO";
curl_setopt($curl,CURLOPT_URL,$url);
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
curl_setopt($curl,CURLOPT_TIMEOUT,10); // aspetta al max 10 sec.
curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($curl,CURLOPT_POST,false);
curl_setopt($curl,CURLOPT_HTTPGET,true);
$resp = curl_exec($curl);
$info = curl_getinfo($curl);
echo "HTTP Code is ",$info['http_code'];
echo "<br>Response is '$resp'";

function aggiornaAllegati($IdContratto) {
	if (!(DMS_API_LIST_URL>'')) return; // non è definita interfaccia con il DMS
	list($cod,$prefix) = getRow("SELECT SUBSTR(CodContratto,2),SUBSTR(CodContratto,1,2) FROM contratto WHERE IdContratto=$IdContratto",MYSQLI_NUM);
	if ($prefix=='LO') $prefix='CO';

	$url = sprintf(DMS_API_LIST_URL,$cod,$prefix);
	trace("Legge lista documenti dal Documentale web: $url",false);

	$headers =  array('Accept: application/json',
			'Content-Type: application/json',
			'Authorization: '.DMS_API_KEY);

	$json = doCurl($url,null,$headers);
	if (!$json) {
		trace("Nessuna risposta dal Documentale web: $url",false);
		return false;
	} else {
		$list = json_decode($json,true);
		if (!$list) {
			trace("Risposta imprevista dal Documentale web: $json",false);
			return false;
		} else {
			if (count($list['errors'])>0) {
				foreach ($list['$errors'] as $error) {
					trace($error['Description'],false);
				}
				return false;
			}
			// Cancella le righe nella tabella allegato provenienti dal DMS
			beginTrans();
			if (!execute("DELETE FROM allegato WHERE UrlAllegato LIKE '".DMS_API_GET_URL."%'")) {
				rollback();
			}
			$list = $list['data'];
			foreach ($list as $item) {
				// Compone link per il download
				$link = sprintf( DMS_API_GET_URL, $item['document_id'], $item['token']);

				$valList = $colList = "";
				$valList = ""; // inizializza lista valori
				addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");
				addInsClause($colList,$valList,"TitoloAllegato", $item['filename'],"S");
				addInsClause($colList,$valList,"UrlAllegato",$link,"D");
				addInsClause($colList,$valList,"LastUpd",$item['last_modify'],"D");
				addInsClause($colList,$valList,"IdTipoAllegato",2,"N"); // documento generico

				if (!execute("INSERT INTO allegato ($colList) VALUES ($valList)")) {
					rollback();
					return false;
				}
			}
			commit();
		}
	}
	return true;
}