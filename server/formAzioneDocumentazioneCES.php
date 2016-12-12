<?php
   require_once("workflowFunc.php"); 

   $zips = array();
   foreach ($idsArray as $IdContratto) {
      creaFilesPerUnContratto($IdContratto);
   	  $zips[] = creaZipDatiCessione($IdContratto,$esitoAzione,$link);
   	  if (!$link) break;
   }
 
   if (count($idsArray)==1) {  // una sola pratica: tutto fatto
   } else { // piu' pratiche, crea una cartella cumulativa
   		$fileZip = creaZipDatiCessioneMultipla($zips,$esitoAzione,$link);
   }
   
   
function creaFilesPerUnContratto($IdContratto) {   
   $pratica = getRow("SELECT * FROM v_pratiche WHERE IdContratto=$IdContratto");
   //copio gli allegati nella cartella cessioni dell'utente 
   $allegati = getFetchArray("SELECT TitoloAllegato,UrlAllegato From allegato where IdContratto =$IdContratto");
   foreach ($allegati as $allegato)
   {
	  $titolo=$allegato['TitoloAllegato'];
	  $urlFile=$allegato['UrlAllegato'];
	  $estensione = explode(".", $urlFile);
	  $fileName=$titolo.'.'.$estensione[1];
	  inserisciAllegatiCessione($pratica,$titolo,$urlFile,$fileName);
   }
             
   //creazione foglio exel dell'estratto conto nella cartella cessioni dell'utente 
   $sql = "SELECT NumRata,". 
   		  " DATE_FORMAT(DataScadenza,'%d/%m/%Y') AS DataScadenza,". 
   		  " DATE_FORMAT(DataRegistrazione,'%d/%m/%Y') AS DataRegistrazione,". 
   		  " DATE_FORMAT(DataCompetenza,'%d/%m/%Y')AS DataCompetenza,".
		  " DATE_FORMAT(DataValuta,'%d/%m/%Y') AS DataValuta,". 
		  " TitoloTipoMovimento, TitoloTipoInsoluto,".
		  " If(Debito!='null',replace(replace(replace(format(Debito,2),'.',';'),',','.'),';',','),NULL) AS Debito,". 
		  " If(Credito!='null',replace(replace(replace(format(Credito,2),'.',';'),',','.'),';',','),NULL) AS Credito". 
		  " FROM v_partite where IdContratto =$IdContratto ORDER BY NumRata";
	
   //preparazione dati con conversione in standard object
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM v_partite where IdContratto =$IdContratto ORDER BY NumRata");
   $resp='({"total":"' . $count . '","results":' . $data . '})';
   $ContrattiEstratto = json_decode(trim($resp,'()'));
   
   //preparazione array colonne
   $colonneEstratto['total'] = $count;
   $colonneEstratto['results'] = $arr;
   creaEXLDatiCessione($pratica, $colonneEstratto, $ContrattiEstratto, 'Estratto conto');
   
   //creazione foglio exel della storia recupero nella cartella cessioni dell'utente 
   $sql = "SELECT DataEvento AS Data, UserId AS Utente,".
  	      " DescrEvento, NotaEvento as Nota ".
	      " FROM v_storiarecupero where IdContratto =$IdContratto ORDER BY DataEvento DESC";
				  
   //preparazione dati con conversione in standard object
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM v_storiarecupero where IdContratto =$IdContratto ORDER BY DataEvento DESC");
   $resp='({"total":"' . $count . '","results":' . $data . '})';
   $datiStorico = json_decode(trim($resp,'()'));
	  
   //preparazione array colonne
   $colonneStorico['total'] = $count;
   $colonneStorico['results'] = $arr;
   creaEXLDatiCessione($pratica, $colonneStorico, $datiStorico, 'Storico recupero');
   			
   //creazione foglio exel degli altri soggetti nella cartella cessioni dell'utente 
   $sql = "SELECT Controparte, TitoloTipoRecapito,".
		  " CONCAT(CASE WHEN Nome IS NOT NULL THEN Nome ELSE '' END,' ',".
		  " CASE WHEN Indirizzo IS NOT NULL THEN Indirizzo ELSE '' END) AS Indirizzo,".
          " CONCAT(CASE WHEN Telefono IS NOT NULL THEN CONCAT('tel: ',Telefono,' ') ELSE '' END,".
          " CASE WHEN Cellulare IS NOT NULL THEN CONCAT('cell: ',Cellulare,' ') ELSE '' END,".
          " CASE WHEN Fax IS NOT NULL THEN CONCAT('fax: ',Fax,' ') ELSE '' END,".
          " CASE WHEN Email IS NOT NULL THEN CONCAT('e-mail: ',Email,' ') ELSE '' END) AS Recapiti".
          " FROM v_altri_soggetti where IdContratto =$IdContratto ORDER BY IdCliente, IdTipoRecapito ASC";
                  
   //preparazione dati con conversione in standard object
				  
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM v_altri_soggetti where IdContratto =$IdContratto ORDER BY IdCliente, IdTipoRecapito ASC");
   if($count>0) {
	 $resp='({"total":"' . $count . '","results":' . $data . '})';
	 $datiAltrSog = json_decode(trim($resp,'()'));
		 				
	 //preparazione array colonne
	 $colonneAltrSog['total'] = $count;
	 $colonneAltrSog['results'] = $arr;
	 creaEXLDatiCessione($pratica, $colonneAltrSog, $datiAltrSog, 'Altri soggetti');	
   }
   					
   //creazione foglio exel recapito nella cartella cessioni dell'utente 
   $sql = "SELECT TitoloTipoRecapito,".
		  " CONCAT(CASE WHEN Nome IS NOT NULL THEN Nome ELSE '' END,' ',".
		  " CASE WHEN Indirizzo IS NOT NULL THEN Indirizzo ELSE '' END,' ',".
		  " CASE WHEN CAP IS NOT NULL THEN CAP ELSE '' END,' ',".
		  " CASE WHEN Localita IS NOT NULL THEN Localita ELSE '' END,' ',".
		  " CASE WHEN SiglaProvincia IS NOT NULL THEN CONCAT('(',SiglaProvincia,')') ELSE '' END) AS Indirizzo,".
		  " CONCAT(CASE WHEN Telefono IS NOT NULL THEN CONCAT('tel: ',Telefono,' ') ELSE '' END,".
		  " CASE WHEN Cellulare IS NOT NULL THEN CONCAT('cell: ',Cellulare,' ') ELSE '' END,".
		  " CASE WHEN Fax IS NOT NULL THEN CONCAT('fax: ',Fax,' ') ELSE '' END,".
		  " CASE WHEN Email IS NOT NULL THEN CONCAT('e-mail: ',Email,' ') ELSE '' END) AS Recapiti".
		  " FROM v_recapito WHERE FlagAnnullato='N' AND IdCliente=".$pratica["IdCliente"];

   //preparazione dati con conversione in standard object
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM v_recapito WHERE FlagAnnullato='N' AND IdCliente=".$pratica["IdCliente"]);
   if($count>0) {
	 $resp='({"total":"' . $count . '","results":' . $data . '})';
	 $datiRec = json_decode(trim($resp,'()'));
				    
	 //preparazione array colonne
	 $colonneRec['total'] = $count;
	 $colonneRec['results'] = $arr;
	 creaEXLDatiCessione($pratica, $colonneRec, $datiRec, 'Recapiti cliente');	
   } 
   			
   //creazione foglio excel degli altri contratti nella cartella cessioni dell'utente 
   $sql = "SELECT Ruolo,numPratica,Prodotto,Stato,StatoRecupero,Agenzia, ".
		  " If(ImpFinanziato!='null',replace(replace(replace(format(ImpFinanziato,2),'.',';'),',','.'),';',','),NULL) AS Finanziato,".
		  " If(Importo!='null',replace(replace(replace(format(Importo,2),'.',';'),',','.'),';',','),NULL) AS Impagato".  
		  " FROM v_pratiche_collegate WHERE IdCliente=".$pratica["IdCliente"].
		  " AND IdContratto!=$IdContratto ORDER BY numPratica";

   //preparazione dati con conversione in standard object
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM v_pratiche_collegate WHERE IdCliente=".$pratica["IdCliente"]." AND IdContratto !=$IdContratto ORDER BY numPratica");
   if($count>0) {
	 $resp='({"total":"' . $count . '","results":' . $data . '})';
	 $datiAltrCon = json_decode(trim($resp,'()'));
				    
	 //preparazione array colonne
	 $colonneAltrCon['total'] = $count;
	 $colonneAltrCon['results'] = $arr;
	 creaEXLDatiCessione($pratica, $colonneAltrCon, $datiAltrCon, 'Altri contratti');	
   } 
   					
   //creazione foglio exel delle note nella cartella cessioni dell'utente 
   $sql = "SELECT DataCreazione, DataScadenza, TestoNota". 
		  " FROM nota".
		  " where tipoNota in ('N','C') AND IdContratto =$IdContratto ORDER BY DataCreazione DESC";

   //preparazione dati con conversione in standard object
   $arr = getFetchArray($sql);
   $data = json_encode_plus($arr);
   $count = getScalar("SELECT count(*) FROM nota where tipoNota in ('N','C') AND IdContratto =$IdContratto");
   if($count>0) {
	 $resp='({"total":"' . $count . '","results":' . $data . '})';
	 $datiNota = json_decode(trim($resp,'()'));
	 			    
	 //preparazione array colonne
	 $colonneNota['total'] = $count;
	 $colonneNota['results'] = $arr;
	 creaEXLDatiCessione($pratica, $colonneNota, $datiNota, 'Annotazioni');	

   }
   				  
   //creazione file doc dati generali
   creaDOCDatiCessione($pratica,'Dati Contratto.xml','Dati Contratto');
}			  
?>

var formPanelDocumentazione = Ext.Msg.show({
	  title      : 'Documentazione',
	  msg        : "<?php echo $esitoAzione?>",
	  width      : 500,
	  buttons    : Ext.MessageBox.OK,
	  icon       : Ext.MessageBox.INFO
});