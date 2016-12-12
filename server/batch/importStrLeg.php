<?php
//
// Programma per acquisire il file di estrazione una-tantum dei contratti in stato STR/LEG
// Il file ha righe nel seguente formato:
//  LO18856,LEG,07,AVV,20120610 
// cioè: codice, stato (LEG/STR), codice agenzia STR oppure codice legale, codice operatore (che farne?) data scadenza
// (la data scadenza viene portata alla fine del mese)
require_once('commonbatch.php');
require_once('../engineFunc.php');
global $azioni;
$azioni['ACC']="Accodamento Rate";
$azioni['ASP']="Soll. Pagam. Premio Assic.";
$azioni['ATB']="Attesa Bollettino";
$azioni['ATV']="Bene Ritirato - Attesa Vendita";
$azioni['CAT']="Cambio Attributo Recupero";
$azioni['CCA']="Cambia Classificazione";
$azioni['CCR']="Cessione Credito";
$azioni['CCS']="Ann. Proposta di Cessione";
$azioni['DBT']="Decadenza beneficio termine";
$azioni['FAS']="Predisposto Fascicolo";
$azioni['FBP']="Ricevuto fax promesse di pag.";
$azioni['IFU']="Istruttoria furto";
$azioni['IND']="Segnalazione nuovo indirizzo";
$azioni['INS']="Insoluto / Protesto";
$azioni['IPO']="Ipoteca da Iscrivere";
$azioni['LEG']="Passaggio a legale";
$azioni['OBP']="VARIA OPERATORE PER VER. BP";
$azioni['OPE']="Cambio Operatore";
$azioni['PAG']="Pagamento";
$azioni['PAP']="Passaggio a perdite";
$azioni['PAR']="Pagamento parziale da rapport.";
$azioni['POS']="Definizione positiva";
$azioni['PRE']="Passaggio a recupero esterno";
$azioni['PSE']="Proroga a Società Esterna";
$azioni['RES']="Sollecito Pagamento Residuo";
$azioni['RIP']="Ricerca Pagamento";
$azioni['RIS']="Riscatti Leasing";
$azioni['RIT']="Segnalazione ritiro mezzo";
$azioni['RLG']="Rientro da Legale";
$azioni['RRE']="Rientro da recupero esterno";
$azioni['SCA']="Scarico Pratica in Archivio";
$azioni['SDD']="Decesso Debitore";
$azioni['SL0']="Sollecito Residui";
$azioni['SL1']="Primo Sollecito";
$azioni['SL2']="Secondo Sollecito";
$azioni['SOL']="Sollecito Mensile Automatico";
$azioni['SPC']="Pagamenti Viaggianti Certi";
$azioni['SRA']="Attesa Risarcimento Assicur.";
$azioni['SRD']="Recupero Domiciliare in Corso";
$azioni['SRR']="Ricerca Rata";
$azioni['STO']="Passaggio a storico";
$azioni['SVR']="Veicolo Reso (Attesa Vendita)";
$azioni['TDC']="Telefonata del Cliente";
$azioni['TEL']="Telefonata";
$azioni['TLG']="Telegramma";
$azioni['ULT']="Ultimo Avviso";
$azioni['VEN']="Vendita Bene";
$azioni['10']="RISOLUZIONE";
$azioni['14']="Richiesta Estratto Crono";
$azioni['15']="RISOLUZIONE PER SINIST/FURTO";
$azioni['16']="ATTESA IND.ASSICURATIVO";
$azioni['17']="Richiesta Informazioni";
$azioni['18']="GEST.STRAGIUDIZIALE";
$azioni['19']="Richiesta Documenti al Convenz";
$azioni['20']="PIANO DI RIMBORSO";
$azioni['21']="Effetti all'Incasso";
$azioni['25']="DIFFIDA STRAGIUDIZIALE";
$azioni['30']="DECRETO INGIUNTIVO";
$azioni['31']="Pagamento Tassa di Registro";
$azioni['32']="DECRETO ING.GARANTI";
$azioni['33']="DECRETO ING.OPPOSTO";
$azioni['34']="ESECUZIONE FORZATA";
$azioni['35']="AZIONE CAMBIARIA";
$azioni['36']="Pignoramento 1/5 stipendio";
$azioni['37']="Truffa";
$azioni['38']="Precetto";
$azioni['39']="Valutazione per Cessione";
$azioni['40']="Ricorso per Sequestro";
$azioni['41']="Approvazione Cessione";
$azioni['42']="Attesa Chiusura Pratica";
$azioni['43']="Perdita di Possesso";
$azioni['44']="Proposta di Transazione";
$azioni['45']="ISCRIZIONE IPOTECARIA";
$azioni['46']="Radiazione Cespite";
$azioni['47']="Incarico d'Asta";
$azioni['48']="Pignoramento";
$azioni['49']="Istanza di Dissequestro";
$azioni['50']="ISTANZA DI FALLIMENTO";
$azioni['51']="Istanza di Rivendica";
$azioni['52']="Insinuazione al Passivo Fall.";
$azioni['55']="AMMINISTRAZ.CONTROLLATA";
$azioni['60']="CONCORDATO PREVENTIVO";
$azioni['65']="FALLIMENTO";
$azioni['67']="Azioni contro Convenzionato";
$azioni['70']="DA PASSARE A PERDITE FNT";
$azioni['75']="DA PASSARE A PERDITE FT";
$azioni['80']="Querela";
$azioni['90']="Riquantificazione Credito";
$azioni['DEO']="AZIONE DEONTOLOGICA";
$azioni['DIF']="DIFFIDA";
$azioni['RIN']="RINTRACCIO";
$azioni['RIE']="PDR - AZIONE CREAZIONE";
$azioni['APR']="PDR - AZIONE ANNULLO";
$azioni['CHR']="PDR - AZIONE CHIUSURA";
$azioni['RP1']="PDR - UNA RATA IMPAGATA";
$azioni['RP2']="PDR - DUE RATE IMPAGATE";
$azioni['RP3']="PDR - TRE RATE IMPAGATE";
$azioni['CPR']="CAMBIA CLASSIFICAZIONE X PDR";
$azioni['ARE']="ANNULLO AFFIDO";
$azioni['VIB']="VERIFICA INCASSO BONIFICO";
$azioni['ALC']="AFFIDAMENTO AD UN LEGALE C/R";
$azioni['ALL']="AFFIDAMENTO AD UN LEGALE  LSG";
$azioni['DFF']="DIFFIDA";
$azioni['QAI']="QUERELA PER APPR. INDEBITA";
$azioni['PDP']="PERDITA DI POSSESSO";
$azioni['RVC']="RIPOSSESSO VEICOLO";
$azioni['RDI']="RICORSO PER DECRETO INGIUNTIVO";
$azioni['NED']="NOTIFICA ESITO DECRETO INGIUN";
$azioni['ODI']="OPP. AL DECRETO INGIUNTIVO";
$azioni['COD']="CONTINUA OPP.AL DECRETO  INGIU";
$azioni['EDI']="ESECUTIVITÀ DEL DECRETO INGIUN";
$azioni['PCT']="PRECETTO";
$azioni['PPT']="PIGNORAMENTO PRESSO TERZI";
$azioni['PMO']="PIGNORAMENTO MOBILIARE";
$azioni['PMI']="PIGNORAMENTO IMMOBILIARE";
$azioni['SS1']="ACC.DI SALDO E STRALCIO 20%";
$azioni['SS2']="ACC.DI SALDO E STRALCIO 30%";
$azioni['SS3']="ACC.DI SALDO E STRALCIO 40%";
$azioni['SS4']="ACC.DI SALDO E STRALCIO 50%";
$azioni['SS5']="ACC.DI SALDO E STRALCIO 60%";
$azioni['SS6']="ACC.DI SALDO E STRALCIO 70%";
$azioni['SS7']="ACC.DI SALDO E STRALCIO 80%";
$azioni['SS8']="ACC.DI SALDO E STRALCIO 90%";
$azioni['PDR']="PIANO DI RIENTRO";
$azioni['VPT']="PIGNORAMENTO PRESSO TERZI";
$azioni['FPR']="FALLIMENTO DEL PIANO DI RIENTR";
$azioni['RVP']="RIVENDICA POST PROCEDURA";
$azioni['TRF']="TRUFFA";
$azioni['VCR']="VENDITA CESPITE RIPOSSESSATO";
$azioni['PPP']="PROSSIMO PASSAGGIO A PERDITA";
$azioni['PCC']="PROCEDURA CONCORSUALE CO E RE";
$azioni['PCL']="PROCEDURA CONCORSUALE  LE";
$azioni['TEK']="INSOLUTO TECNICO 50007";
$azioni['COM']="INSOLUTO / COMODATO D'USO";

// Legge parametro
if ($argc)  // chiamata da riga comando
	$filepath = $argv[1]; // path completo del file
else
	$filepath  = $_GET["file"].$_GET["FILE"]; // path completo del file

trace ("Inizio elaborazione file $filepath",false);
echo "Inizio elaborazione file $filepath\n<br>";

if (!is_file($filepath))
{
	trace("Il file specificato($filepath) non esiste oppure non è un file",FALSE);
	die("Il file specificato($filepath) non esiste oppure non è un file\n<br>");
}	

$file = fopen($filepath,"r");
while (!feof($file))
{
	$riga = fgets($file);
	$riga = str_replace("\n","",$riga); // toglie newline finale
	$riga = str_replace("\r","",$riga); // toglie newline finale
	$campi = split(",",$riga);
	if (count($campi)!=6)
		break;
	$codContratto  = $campi[0];
	$codStato      = $campi[1];
	$codAgenzia    = $campi[2];
	$codOper       = $campi[3];
	$dataScad      = $campi[4];
	$dataScad      = dateFromString(substr($dataScad,0,4)."-".substr($dataScad,4,2)."-".substr($dataScad,6,2)); 
	$ultimaAzione  = $campi[5];
	registra($codContratto,$codStato,$codAgenzia,$dataScad,$ultimaAzione);
}	
fclose($file);
trace ("Finita elaborazione file $filepath",FALSE);
die("Finita elaborazione file $filepath\n<br>");

//---------------------------------------------------------------------------------------------
// Registra l'affido del contratto dato
//---------------------------------------------------------------------------------------------
function registra($codContratto,$codStato,$codAgenzia,$dataScad,$ultimaAzione)
{
	global $azioni;
	// Legge il contratto
	$row = getRow("SELECT * FROM contratto WHERE codContratto='$codContratto'");
	if ($row["IdContratto"]>0)
	{
		if ($row["IdAgenzia"]>0 && $codAgenzia>"")
		{
			echo "Il contratto $codContratto è già affidato all'agenzia ".$row['IdAgenzia']." (cod=".$row['CodRegolaProvvigione'].")\n<br>";		
			trace("Il contratto $codContratto è già affidato all'agenzia ".$row['IdAgenzia']." (cod=".$row['CodRegolaProvvigione'].")",FALSE);		
//			return;
		}		
	}
	else 
	{
		trace("Il contratto $codContratto non esiste",FALSE);	
		echo "Il contratto $codContratto non esiste\n<br>";	
		return;
	}
	
	// Controlla il tipo di affido
	if ($codStato=="LEG" || $codStato=="STR")
	{
		if ($codStato=="STR")
			// agenzie IRC e NCP (DBT Strong)
			$codStato = ($codAgenzia=="26" || $codAgenzia=="30")? "STR2":"STR1";
		$IdStato = getScalar("SELECT IdStatoRecupero FROM statorecupero WHERE CodStatoRecupero='$codStato'");
	}
	else
	{
		trace("Il contratto $codContratto ha tipo di affido=$codStato non valido",FALSE);	
		echo "Il contratto $codContratto ha tipo di affido=$codStato non valido\n<br>";	
		return;
	}
	// Se contratto non classificato, lo classifica
	if (!($row["IdClasse"]>0))
		classify($row["IdContratto"],$changed);
	
	// RIPRODUCE L' AFFIDAMENTO OCS
	if ($codAgenzia>"")
	{
		// Legge la riga di RegolaProvvigione corrispondente
		$codAge = ($codStato=='LEG')? "L$codAgenzia":$codAgenzia;
		$provv = getRow("SELECT * FROM regolaprovvigione WHERE CodRegolaProvvigione='$codAge'");
		if (!is_array($provv))
		{
			trace("Il contratto $codContratto ha cod agenzia=$codAge non riconosciuto tra le regole provvigionali",FALSE);	
			echo "Il contratto $codContratto ha cod agenzia=$codAge non riconosciuto tra le regole provvigionali\n<br>";	
			return;
		}
		$IdProvv = $provv["IdRegolaProvvigione"];

		$dataFineAffido = mktime(0,0,0,date('n',$dataScad)+1,0,date('Y',$dataScad));
		if ($dataScad<time())
		{
			$dataFineAffido = mktime(0,0,0,date('n')+1,0,date('Y'));
			trace("Contratto $codContratto data fine affido superata (".ISODate($dataScad)."), forzata a ".ISODate($dataFineAffido),FALSE);	
			echo "Contratto $codContratto data fine affido superata (".ISODate($dataScad)."), forzata a ".ISODate($dataFineAffido)."\n<br>";	
		}
		
		// Se il contratto in CNC è affidato ad agenzia diversa da quella OCS, segnala ma non affida
		if ($codAge != $row["CodRegolaProvvigione"] && $row["IdAgenzia"]>0 )
		{
			trace("Il contratto $codContratto ha affido=$codAge su OCS e ".$row["CodRegolaProvvigione"]." su CNC; non forzato",FALSE);
		}
		else // stessa agenzia o nessuna agenzia
		{
			if ($codAge == $row["CodRegolaProvvigione"] && ISODate($dataFineAffido)!=ISODate($row["DataFineAffido"]))
			{
				trace("Il contratto $codContratto ha fine affido=".ISODate($dataFineAffido)." su OCS e ".ISODate($row["DataFineAffido"])." su CNC; uso la prima.",FALSE);
				echo "Il contratto $codContratto ha fine affido=".ISODate($dataFineAffido)." su OCS e ".ISODate($row["DataFineAffido"])." su CNC; uso la prima.";
				prorogaAgenzia($row["IdContratto"],$dataFineAffido);
			}
			else 
			{
				$nomeAgenzia = affidaAgenzia($row["IdContratto"],$provv["IdReparto"],$dataFineAffido,
					$writeHist=true,$dataInizioAffido=NULL,$IdProvv);	
				if ($nomeAgenzia>"")
				{
					echo "Contratto $codContratto affidato a $nomeAgenzia fino al ".ISODate($dataFineAffido)."\n<br>";
					trace("Contratto $codContratto affidato a $nomeAgenzia fino al ".ISODate($dataFineAffido),FALSE);
				}
				else  
				{
					echo "Contratto $codContratto fallito affidamento\n<br>";
					trace("Contratto $codContratto fallito affidamento",FALSE);
				}
			}
		}
	}

	// REGISTRA NELLA HISTORY L'ULTIMA AZIONE
	if ($azioni[$ultimaAzione]>"")
		writeHistory("NULL","Ultima azione registrata sul sistema OCS: ".$azioni[$ultimaAzione],$row["IdContratto"],"");
}
?>
