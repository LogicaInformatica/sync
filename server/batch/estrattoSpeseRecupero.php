<?php  
//================================================================================
// estrattoSpeseRecupero 
// Crea e spedisce il file csv compatibile con Excel contenente le spese di
// recupero maturate, per uso in OCS
//================================================================================
function estrattoSpeseRecupero()
{
	trace("Produzione estratto spese di recupero in formato Excel",FALSE);
	
	$rows = getFetchArray("select IF(SUBSTR(CodContratto,1,2)='LO','CO','LE'),
	SUBSTR(CodContratto,3,INSTR(CONCAT(CodContratto,'-'),'-')-3),
	'E',DATE_FORMAT(CURDATE(),'%Y%m%d'),	round(ImpSpeseRecupero*100),'','','','',''
	FROM contratto where impSpeseRecupero>0 and impinsoluto>=26 order by 1,2");
	
	$filepath = LETTER_PATH."/EstrattoSpeseRecupero.csv"; 
	
	$firstRow = "RSPE_PROVENIENZA;RSPE_PRATICA;RSPE_TIPO;RSPE_DATA_REG;RSPE_IMPORTO_MATURATO;RSPE_RATA;RSPE_IMPORTO_RATA;RSPE_IMPONIBILE;RSPE_PERC_CALC;RSPE_PERC_CONT";
	if (!file_put_contents($filepath,"$firstRow\r\n")) {
		writeLog("ESTRATTO","estrattoSpeseRecupero.php","Fallita scrittura estratto spese di recupero","estrattoSpeseRecupero");
		return;
	}
	
	foreach ($rows as $row) {
		$s = implode(";",$row);
		file_put_contents($filepath,"$s\r\n",FILE_APPEND);
	}
	
	trace("Scritte ".count($rows)." righe+1 nel file $filepath",FALSE);
	
	$allegato = array("tmp_name"=> $filepath, 
					  "name"	=> "EstrattoSpeseRecupero.csv", 
					  "type" 	=> filetype($filepath));
	$title = "Estratto Spese di Recupero ".date('d/m/Y');
	$msg   = "Estratto delle spese di recupero maturate al ".date('d/m/Y')."<br>"
			."Sono incluse tutte le pratiche con debito totale maggiore o uguale a 26 euro e spese di recupero maggiori di zero.";
	sendMail("cnc".$sito."@toyota-fs.com",getSysParm("SPESE_REC_MAIL"),$title,$msg,$allegato);
	
	// Registra su log l'avvenuta esecuzione 
	writeLog("ESTRATTO","estrattoSpeseRecupero.php","Produzione estratto spese di recupero","estrattoSpeseRecupero");
}
?>	