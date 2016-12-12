<?php
require_once("common.php");
require_once("userFunc.php");
require_once("engineFunc.php"); 

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}

doMain();

function doMain()
{
	global $context;
	set_time_limit(600); // aumenta il tempo max di cpu  
	
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	if ($_POST['tipo']>"") // tipo provvigione 1=pre-dbt, 2=str, 3=leg, 4=rineg
	{
		$condTipo = "AND TipoProvvigione=".$_POST['tipo'];
	}
	
	//trace("Task: $task",FALSE);
	
	$fields = "*";
	switch ($task)
	{
		case "saveModifica": // registra una modifica manuale alle provvigioni
			saveModifica();
			return;
		case "deleteModifica": // cancella una  modifica manuale alle provvigioni
			deleteModifica();
			return;
		case "sintesiPerLotto":  // lotto normale pre-DBT e lotto periodico mensile STR/LEG
			$query = "v_provvigione WHERE TipoCalcolo IN ('N','C') AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$order = "DataFineAffido DESC,ordine,Agenzia,CodRegolaProvvigione";
			break;
		case "sintesiPerLotto2": // secondo tipo di sintesi STR/LEG (lotto vero arrotondato fine mese)
			$query = "v_provvigione WHERE TipoCalcolo='M' AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$order = "DataFineAffido DESC,ordine,Agenzia,CodRegolaProvvigione";
			break;
		case "sintesiMeseRine": // sintesi mensile provvigioni rinegoziazione
			$query = "v_provvigione WHERE TipoCalcolo='R' AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$order = "DataFineAffido DESC,ordine,Agenzia,CodRegolaProvvigione";
			break;
		case "sintesiMeseRineAgenzia": // sintesi mensile provvigioni rinegoziazione viste da un superv. di agenzia
			$query = "v_provvigione WHERE IdReparto={$context["IdReparto"]} AND TipoCalcolo='R' AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$query .= " AND (StatoProvvigione<2 OR DataFineAffido>CURDATE()-INTERVAL 2 MONTH) ";
			
			/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilita' affidi */ 
			$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
			if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
			$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
			if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
			$query .= " AND (DataIni<='$dataMassima1' AND tipoProvvigione=1"
		           ." OR DataIni<='$dataMassima2' AND tipoProvvigione>1)";
			$order = "DataFineAffido DESC,ordine,Agenzia,CodRegolaProvvigione";
			break;
		case "sintesiUnAgenzia": // sintesi di una sola agenzia preDBT oppure STR, vista dall'operatore
			//5/5/2014 $query = "v_provvigione WHERE  TipoCalcolo !='M' AND IdReparto=".$_REQUEST['IdAgenzia']." AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$query = "v_provvigione WHERE  TipoCalcolo IN ('N','C') AND IdReparto=".$_REQUEST['IdAgenzia']." AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$order = "Stato,DataFineAffido DESC,CodRegolaProvvigione";
			break;
		case "sintesiUnAgenziaRine": // idem per rinegoziazione
			$query = "v_provvigione WHERE  TipoCalcolo ='R' AND IdReparto=".$_REQUEST['IdAgenzia']." AND DataFineAffido>CURDATE()-INTERVAL 1 YEAR $condTipo";
			$order = "Stato,DataFineAffido DESC,CodRegolaProvvigione";
			break;
		case "sintesiPerLAgenzia": // sintesi di un'agenzia vista dal manager dell'agenzia
			                       // non vede quelle prima di agosto 2011 ne' quelle piu' vecchie di 2 mesi
			                       // inoltre vede le provv. di tipo "X" e non "C" perché è limitata dalla data di max visibilità STR
			$dataIntroduzioneTipoX = getScalar("SELECT MIN(DataFin) FROM provvigione WHERE TipoCalcolo='X'");                    
			$query = "v_provvigione WHERE IdReparto=".$context["IdReparto"] . 
				" AND (StatoProvvigione<2 OR DataFineAffido>CURDATE()-INTERVAL 2 MONTH AND DataFineAffido>'2011-08-01')";
			$query .= " AND (TipoCalcolo IN ('N','X') OR  TipoCalcolo='C' AND DataFineAffido<'$dataIntroduzioneTipoX')";
			
			/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilita' affidi */ 
			$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
			if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
			$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
			if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
			$query .= " AND (DataIni<='$dataMassima1' AND tipoProvvigione=1"
		           ." OR DataIni<='$dataMassima2' AND tipoProvvigione>1)";
	
		    $order = "Stato,DataFineAffido DESC,CodRegolaProvvigione";
			break;
		case "cambiaStato":
			$query = "provvigione WHERE IdProvvigione='".$_REQUEST['idProvvigione']."'";
			$statoProv = "SELECT StatoProvvigione FROM $query";
			$stato = getScalar($statoProv);
			if($stato==2) {
			  $sqlCambiaStato = "UPDATE provvigione SET StatoProvvigione='1' WHERE IdProvvigione='".$_REQUEST['idProvvigione']."'";	
			  if(execute($sqlCambiaStato)) {
			    echo "{success:true, message:\"Cambio stato riuscito\"}";
				die();
			  } else {
			      echo "{success:false, error:\"Cambio stato non riuscito\"}";
			      die();
			    }  	
			} else {
				$sqlCambiaStato = "UPDATE provvigione SET StatoProvvigione='2' WHERE IdProvvigione='".$_REQUEST['idProvvigione']."'";
				if(execute($sqlCambiaStato)) {
			      echo "{success:true, message:\"Cambio stato riuscito\"}";
				  die();
			    } else {
			        echo "{success:false, error:\"Cambio stato non riuscito\"}";
			        die();
			      }   	
			  }
			break;	
		case "ricalcolaProvvigione":
			$query="provvigione WHERE IdProvvigione=".$_REQUEST['idProvvigione'];
			$sql="SELECT StatoProvvigione,IdRegolaProvvigione,DataFin,TipoCalcolo FROM $query";
			$row = getRow($sql);
			if (!is_array($row))
			{
			      echo "{success:false, error:\"Ricalcolo provvigione non riuscito (ricarica la lista e riprova)\"}";
				  die();		
			}
			// Se si tratta di riga tipo C, ricalcola anche la riga corrispondente di tipo X (è come la C, ma con visibilità
			// ristretta per le agenzie) 
			$idprovv  = $_REQUEST['idProvvigione'];
			$tipocalc = "'".$row["TipoCalcolo"]."'";
			if ($row["TipoCalcolo"]=='C') {
				$idprovv  .= ",0" . getScalar("SELECT IdProvvigione FROM provvigione WHERE IdRegolaProvvigione=".$row['IdRegolaProvvigione']." AND DataFin='".$row['DataFin']."' AND TipoCalcolo='X'");
				$tipocalc .= ",'X'"; 		
			}	
			//controllo se la provvigione  e' nello stato di Consolidato
			//se si lo rendo Completo per poter ricalcolare la provvigione e poi la riporto a Consolidato
			if($row["StatoProvvigione"]=='2') // gia' consolidato, lo deve sbloccare
			{
			  $sqlCambiaStato = "UPDATE provvigione SET StatoProvvigione='1' WHERE IdProvvigione IN ($idprovv)";
			  if (!execute($sqlCambiaStato)) 
			    die("{success:false, error:\"Cambio stato non riuscito\"}");
			}
			$condizione="IdRegola=".$row['IdRegolaProvvigione']." AND DataFineAffido='".$row['DataFin']."' AND TipoCalcolo IN ($tipocalc)";
			$result = aggiornaProvvigioni(true,$condizione);
			if($result==true) {
			      echo "{success:true, message:\"Ricalcolo provvigione riuscito\"}";
				  die();		
			} else {
			    	echo "{success:false, error:\"Ricalcolo provvigione non riuscito\"}";
			        die();	
			}
			break;
		case "fileCerved":
			$filePath = eseguiCreazioneFileCerved($_REQUEST['idProvvigione'],$errmsg,$fileURL,$_REQUEST['tipoCliente']);
			if ($errmsg>"")
			    die("{success:false, error:\"$errmsg\"}");
			else if ($filePath[0]=="0")
			{
	   			die("{success:true, message:\"Non ci sono pratiche da estrarre\"}");
			}
			else
			{
				$parti = split("/",$filePath[0]);
				$filename = $parti[count($parti)-1];
				if (count($filePath)==1)
		   			die("{success:true, message:\"Creato file per CERVED: <a href='$fileURL[0]' target='_blank'>$filename</a>\"}");
				else {
					$parti1 = split("/",$filePath[1]);
					$filename1 = $parti1[count($parti1)-1];
					die("{success:true, message:\"Creati file per CERVED: <a href='$fileURL[0]' target='_blank'>$filename</a>&nbsp;&nbsp;<a href='$fileURL[1]' target='_blank'>$filename1</a>\"}");
				}
			}
			break;
		default:
			echo "{failure:true, task: '$task'}";
			return;
	}
	
	/* By specifying the start/limit params in ds.load 
	 * the values are passed here
	 * if using ScriptTagProxy the values will be in $_GET
	 * if using HttpProxy      the values will be in $_REQUEST (or $_REQUEST)
	 * the following two lines check either location, but might be more
	 * secure to use the appropriate one according to the Proxy being used
	*/
	 
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
		$sql = "SELECT $fields FROM $query ORDER BY ";
	
		if ($_REQUEST['groupBy']>' ') {
			if ($_REQUEST['groupBy']=="Lotto")
				$sql .= "DataFineAffido DESC,Ordine, ";
			else
				$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ", $order,";
		} 
		if ($_REQUEST['sort']>' ') 
			if ($_REQUEST['sort']=="Lotto")
				$sql .= "DataFineAffido DESC,ordine";
			else
				$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
		else
			$sql .= $order;
		
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//trace($sql,false);
		$arr = getFetchArray($sql);
	}
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	
	   /* If using ScriptTagProxy:  In order for the browser to process the returned
	       data, the server must wrap te data object with a call to a callback function,
	       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
	       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}

//-----------------------------------------------------------------------------
// saveModifica
// Registra una modifica definita con il form dettaglioModificaProvvigione.js
//-----------------------------------------------------------------------------
function saveModifica()
{
	global $context;
	//trace(print_r($_REQUEST,true),false);
	
	$IdProvvigione = $_REQUEST["idProvvigione"];
	$IdContratto   = $_REQUEST["idContratto"];
	$NumRata	   = $_REQUEST["numRata"];
	$DataFineAffido = $_POST["DataLotto"];
	$CapAffidato   = cleanNumber($_POST["CapAffidatoMod"])-cleanNumber($_POST["ImpCapitaleAffidato"]);
	$TotAffidato   = cleanNumber($_POST["TotAffidatoMod"])-cleanNumber($_POST["ImpTotaleAffidato"]);
	$Pagato		   = cleanNumber($_POST["PagatoMod"])-cleanNumber($_POST["ImpPagato"]);
	$PagatoTotale  = cleanNumber($_POST["PagatoTotaleMod"])-cleanNumber($_POST["ImpPagatoTotale"]);
	$Interessi	   = cleanNumber($_POST["InteressiMod"])-cleanNumber($_POST["ImpInteressi"]);
	$Spese		   = cleanNumber($_POST["SpeseRecuperoMod"])-cleanNumber($_POST["ImpSpese"]);
	$TipoCorrezione = $_POST["FlagCancellazione"]=='on' ? 'D':'M';
	if ($_POST["FlagRataViaggianteMod"]=='on' && $_POST["FlagRataViaggiante"]!='Y')
		$Viaggiante = 1;  // diffRataViaggiante = +1
	else if ($_POST["FlagRataViaggianteMod"]!='on' && $_POST["FlagRataViaggiante"]=='Y')
		$Viaggiante = -1;   
	else 
		$Viaggiante = 0;
	$Nota = $_POST["Nota"];
	
	// CONTROLLI
	if ($TipoCorrezione=='M')
	{
		if ($Pagato>$PagatoTotale)
		{
			$resp["success"] = false;
			$resp["msg"]   = "Non si puï¿½ specificare un incasso totale (inclusivo di eventuali rate viaggianti) minore dell'incasso (IPR)";
			echo json_encode_plus($resp);	
			return;
		}
		if ($CapAffidato>$TotAffidato)
		{
			$resp["success"] = false;
			$resp["msg"]   = "Non si puï¿½ specificare un totale affidato minore del capitale affidato";
			echo json_encode_plus($resp);	
			return;
		}
	}
	
	// NOTA BENE:
	// Nel caso di flagCancellazione='Y' (rata da eliminare dalle provvigioni), i campi "Mod" sono disabilitati
	// quindi non arrivano e vengono trattati come Zero, il che ï¿½ corretto perchï¿½ produce uno storno di tutti i 6 importi
	
	//	NOTA BENE: Le spese e gli interessi incassati non sono suddivisibili per rata
	//             vengono perï¿½ registrati su ogni rata (nelle views in lettura invece della somma viene fatto il MAX)
	
	// UPDATE oppure INSERT
	beginTrans();
	$condKey = "IdContratto=$IdContratto AND IdProvvigione=$IdProvvigione AND NumRata=$NumRata";
	if (rowExistsInTable("modificaprovvigione",$condKey))
	{
		$setClause = "";
		addSetClause($setClause,"IdProvvigione",$IdProvvigione,"N");
		addSetClause($setClause,"IdContratto",$IdContratto,"N");
		addSetClause($setClause,"NumRata",$NumRata,"N");
		addSetClause($setClause,"DataFineAffido",$DataFineAffido,"D");
		addSetClause($setClause,"DiffCapitaleAffidato",$CapAffidato,"N");
		addSetClause($setClause,"DiffTotaleAffidato",$TotAffidato,"N");
		addSetClause($setClause,"DiffPagato",$Pagato,"N");
		addSetClause($setClause,"DiffPagatoTotale",$PagatoTotale,"N");
		addSetClause($setClause,"DiffInteressi",$Interessi,"N");
		addSetClause($setClause,"DiffSpeseRecupero",$Spese,"N");
		addSetClause($setClause,"TipoCorrezione",$TipoCorrezione,"S");
		addSetClause($setClause,"DiffRataViaggiante",$Viaggiante,"N");
		addSetClause($setClause,"Nota",$Nota,"S");
		addSetClause($setClause,"LastUser",$context["Userid"],"S");
		addSetClause($setClause,"LastUpd","NOW()","G");
		
		$sql = "UPDATE modificaprovvigione $setClause WHERE $condKey";		
	}
	else
	{
		$colList = "";
		$valList = "";
		addInsClause($colList,$valList,"IdProvvigione",$IdProvvigione,"N");	 	
		addInsClause($colList,$valList,"IdContratto",$IdContratto,"N");	 	
		addInsClause($colList,$valList,"NumRata",$NumRata,"N");
		addInsClause($colList,$valList,"DataFineAffido",$DataFineAffido,"D");
		addInsClause($colList,$valList,"DiffCapitaleAffidato",$CapAffidato,"N");
		addInsClause($colList,$valList,"DiffTotaleAffidato",$TotAffidato,"N");
		addInsClause($colList,$valList,"DiffPagato",$Pagato,"N");
		addInsClause($colList,$valList,"DiffPagatoTotale",$PagatoTotale,"N");
		addInsClause($colList,$valList,"DiffInteressi",$Interessi,"N");
		addInsClause($colList,$valList,"DiffSpeseRecupero",$Spese,"N");
		addInsClause($colList,$valList,"TipoCorrezione",$TipoCorrezione,"S");
		addInsClause($colList,$valList,"DiffRataViaggiante",$Viaggiante,"N");
		addInsClause($colList,$valList,"Nota",$Nota,"S");
		addInsClause($colList,$valList,"LastUser",$context["Userid"],"S");
		addInsClause($colList,$valList,"LastUpd","NOW()","G");
		
		$sql = "INSERT INTO modificaprovvigione ($colList) VALUES ($valList)";		
	}

	if (!execute($sql))	
	{
		rollback();
		$resp["success"] = false;
		$resp["msg"]   = getLastError();
	}   
	else
	{
		commit(); 
		writeLog("PROVV","Gestione provvigioni","Registrata correzione manuale alla provvigione con id=$IdProvvigione","MOD_PROVV");
		
		$resp["success"] = true;
		$resp["msg"]   = "Modifica provvigioni registrata correttamente";
	}
	echo json_encode_plus($resp);	
}
//-----------------------------------------------------------------------------
// cleanNumber
// Trasforma un numero in formato italiano con separatori in uno valido per
// l'aritmetica pgp
//-----------------------------------------------------------------------------
function cleanNumber($fieldValue)
{
	if ($fieldValue>"")
		return str_replace(',','.', str_replace('.','', $fieldValue));
	else
		return "0";
}

//-----------------------------------------------------------------------------
// deleteModifica
// Elimina una modifica definita con il form dettaglioModificaProvvigione.js
//-----------------------------------------------------------------------------
function deleteModifica()
{
	$IdProvvigione = $_REQUEST["idProvvigione"];
	$IdContratto   = $_REQUEST["idContratto"];
	$NumRata	   = $_REQUEST["numRata"];
	$condKey = "IdContratto=$IdContratto AND IdProvvigione=$IdProvvigione AND NumRata=$NumRata";
	
	$sql = "DELETE FROM modificaprovvigione WHERE $condKey";		
	if (!execute($sql))	
	{
		$resp["success"] = false;
		$resp["msg"]   = getLastError();
	}   
	else	
	{
		writeLog("PROVV","Gestione provvigioni","Eliminata correzione manuale alla provvigione con id=$IdProvvigione","DEL_PROVV");
		$resp["success"] = true;
		$resp["msg"]   = "Modifica provvigioni eliminata";
	}
	echo json_encode_plus($resp);	
}
?>
