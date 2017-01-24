<?php
require_once('commonbatch.php');
//----------------------------------------------------------------------------------------------------------------------
// listaContratti
// Scopo:        Crea in risposta la lista dei contratti in stato LEG - STR (separati da ",")  	
// Argomenti:	from:   sistema mittente (ad es. TFSI)
//              func:   =allegati (contratti che richiedono allegati) default se func manca
//                      =recupero (contratti di cui si chiedono i movimenti)
// Risposta:	U\t messaggio			La lista dei contratti è stata creata
//	     		K\t messaggio			KO: errore nella creazione lista contratti
//----------------------------------------------------------------------------------------------------------------------
$pageurl = $_SERVER["REQUEST_URI"]; // nome pagina con parametri

//-------------------------------------------------------
// Controllo parametri
//-------------------------------------------------------
$from = $_REQUEST["from"].$_REQUEST["FROM"];
$func = $_REQUEST["func"].$_REQUEST["FUNC"];

//controlla l'argomento from arrivato dal chiamante
if ($from=="")
	returnError("Parametro 'from' assente",$pageurl,FALSE);

// Ottiene la chiave della Compagnia (= sistema mittente)
$idCompany = getCompanyId($from);		
if ($idCompany==0)
{
     //returnError("Sistema mittente '$from' non identificato nella tabella Compagnia",$pageurl,FALSE);
	 echo("Sistema mittente '$from' non identificato nella tabella Compagnia");	
}


try
{
	if ($func=="recupero") {
		// legge i contratti per i quali si vogliono i partitari da batch (anche se invariati; inoltre il 
		// batch manda sempre tutti i variati e i nuovi)
		// Include anche quelli storicizzati ma aventi movimenti futuri: sono stati storicizzati per sbaglio
		$strQyery = "select CodContratto FROM contratto WHERE IdContratto in (SELECT IdContratto FROM movimento)
					 UNION ALL SELECT CodContratto FROM db_cnc_storico.contratto x WHERE EXISTS 
					 (SELECT 1 FROM db_cnc_storico.movimento m WHERE x.IdContratto=m.IdContratto AND 
					 (m.DataCompetenza>m.DataRegistrazione OR m.DataValuta>m.DataRegistrazione))"; 		
	} else {
		// Leggi i contratti per i quali scaricare una copia contratto
		$strQyery="select CodContratto
				  from contratto C left join statorecupero S  ON C.IdStatoRecupero = S.IdStatoRecupero
				  where IdContratto  not in (
	                            select IdContratto
	                            from allegato a join tipoallegato t on a.IdTipoAllegato=t.IdTipoAllegato
	                            and t.CodTipoAllegato IN ('CON','DOC') and a.IdUtente IS NULL)
			 	 and C.IdCompagnia      = $idCompany
			 	 and (S.CodStatoRecupero in ('ATS','STR1','STR2','LEG','ATP','CES')
			 	 or DataDbt IS NOT NULL or C.IdClasse=90)
			 	 or exists (select 1 from storiarecupero s WHERE IdContratto=C.IdContratto AND IdAzione=160
	       					 and idcontratto not in (select idcontratto
	                        		 from allegato a join tipoallegato t on a.IdTipoAllegato=t.IdTipoAllegato
	                            	and t.CodTipoAllegato IN ('CON','DOC') and a.IdUtente IS NULL where a.lastupd>s.dataevento))";
	}	   
	$list =  join(',',fetchValuesArray($strQyery));
	trace("Lista contratti: $list",false);
	echo("U\t".$list);
	//die("U\t".join(',',fetchValuesArray($strQyery)));
}
catch(Exception $e)
{
  //returnError("Errore nell'elaborazione della query: ".$strQyery." Errore: ".$e,$pageurl,FALSE);
    echo("K\tErrore nell'elaborazione lista contratti. Errore: ".$e);
}   

?>