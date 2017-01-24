<?php
require_once("userFunc.php");
require_once("workflowFunc.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
if (!$attiva) {
	exit();
}
doMain();

function doMain()
{
	global $context;
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	try
	 {
		switch ($task)
		{
			case "readU":
				$fields = "v.CodContratto,v.IdIncasso,v.TitoloTipoIncasso,v.Data,v.ImpPagato,v.ImpInsoluto,v.NomeCliente,v.UtenteInc,v.RepartoInc,v.UrlAllegato";
				$query = "v_incassi v WHERE ";
				$query.=" v.Data > DATE_SUB(curdate(), INTERVAL 30 DAY) ";
				$query.=filtroUtenteInc();
				$query.=" OR v.IdContratto IN ( select IdContratto from v_attorisucontratto where ";
				$query.= filtroUtenteContr();
				$query.=") ";
				$order = " RepartoInc ".$_REQUEST['groupDir'].", Data ".$_REQUEST['groupDir'];
				break;
			case "readA":
				$fields = "v.CodContratto,v.IdIncasso,v.TitoloTipoIncasso,v.Data,v.ImpPagato,v.ImpInsoluto,v.NomeCliente,v.UtenteInc,v.RepartoInc,v.UrlAllegato";
				$query = "v_incassi v WHERE ";
				$query.=" v.Data > DATE_SUB(curdate(), INTERVAL 30 DAY) ";
				$query.=filtroUtenteInc();
				$query.=" OR v.IdContratto IN ( select IdContratto from v_attorisucontratto where ";
				$query.= filtroAgenteContr();
				$query.=") ";
				$order = " Data ".$_REQUEST['groupDir'];
				break;
			case "AgenzieIncassiTabs":
				$fields = "IdRepartoInc,RepartoInc";
				$query = "v_incassi_tabs v";
				$order = " RepartoInc ".$_REQUEST['groupDir'];
				$valdis = "IdRepartoInc";
				break;
			case "AgenzieDistinteTabs":
				$fields = "d.IdCompagnia as IdCompagnia, RepartoInc";
				$query = "distintapagamento d left join v_incassi v on(d.IdCompagnia=v.IdRepartoInc)";
				$order = " RepartoInc ".$_REQUEST['groupDir'];
				$valdis = "d.IdCompagnia";
				break;
			case "readALot":
				$fields = "v.CodContratto,v.IdIncasso,v.TitoloTipoIncasso,v.Data,v.ImpPagato,v.ImpInsoluto,v.NomeCliente,v.UtenteInc,v.IdRepartoInc,v.RepartoInc,v.UrlAllegato,v.DataFineAffido,v.Lotto";
				$fields .=",v.IncCapitale,v.IncInteressi,v.IncSpese,v.IncAltriAddebiti,v.NumDocumento,v.IdDistinta";
				$query = "v_incassi v WHERE FlagModalita='V' AND ";
				$query.=" (v.Data > DATE_SUB(curdate(), INTERVAL 30 DAY) ";
				$query.=filtroUtenteInc();
				if ($context['InternoEsterno']!='E') // utente interno
				{		
					$query.=" OR v.IdContratto IN ( select IdContratto from v_attorisucontratto where ";
					$query.=filtroAgenteContr();
					$query.=")) ";
				}
				else
					$query .= ")";
				$order = " Data ".$_REQUEST['groupDir'];
				break;
			case "readDistLot":
				$fields = "d.IdDistinta,d.IdCompagnia,DataPagamento,Importo,UrlRicevuta,IBAN,LastUser,LastUpd,CRO";
				$query = "distintapagamento d left join v_incassi v on(d.IdCompagnia=v.IdRepartoInc) WHERE ";
				$query.=filtroUtenteDist();
				$order = " DataPagamento ".$_REQUEST['groupDir'];
				$valdis = "d.IdDistinta";
				break;
			case "readALotMain":
				$fields = "v.CodContratto,v.IdIncasso,v.TitoloTipoIncasso,v.Data,v.ImpPagato,v.ImpInsoluto,v.NomeCliente,v.UtenteInc,v.IdRepartoInc,v.RepartoInc,v.UrlAllegato,v.DataFineAffido,v.Lotto";
				$fields .=",v.IncCapitale,v.IncInteressi,v.IncSpese,v.IncAltriAddebiti,v.NumDocumento,v.IdDistinta";
				$query = "v_incassi v WHERE FlagModalita='V' AND ";
				$query.=" (v.Data > DATE_SUB(curdate(), INTERVAL 30 DAY) ";
				$repid = $_REQUEST['repId'];
				$query.=" AND ($repid=0 OR IFNULL(IdRepartoInc,0)=$repid) ";
				if ($context['InternoEsterno']!='E') // utente interno
				{		
					$query.=" OR v.IdContratto IN ( select IdContratto from v_attorisucontratto where ";
					$query.="  IFNULL(IdAgenzia,0)=".$_REQUEST['repId']."))";
				}
				else
					$query .= ")";
				$order = " Data ".$_REQUEST['groupDir'];
				break;
			case "readDistLotMain":
				$fields = "IdDistinta,IdCompagnia,DataPagamento,Importo,UrlRicevuta,IBAN,LastUser,LastUpd,CRO";
				$query = "distintapagamento WHERE";
				$query.=" IFNULL(IdCompagnia,0)=".$_REQUEST['repId'];
				$order = " DataPagamento ".$_REQUEST['groupDir'];
				break;
			case "readInc":
				$idIncasso=$_REQUEST['idIncasso'];
				if($idIncasso!="")
				{
					$fields="IdIncasso,IdContratto,CodContratto,IdTipoIncasso,TitoloTipoIncasso,NumDocumento,Data,DataDocumento,"
		                    ." UrlAllegato,IdAllegato,IFNULL(ImpPagato,0) as ImpPagato,IFNULL(IncCapitale,0) as IncCapitale,"
		                    ." IFNULL(IncInteressi,0) as IncInteressi,IFNULL(IncSpese,0) as IncSpese,"
		                    ." IFNULL(IncAltriAddebiti,0) as IncAltriAddebiti,IFNULL(InsCapitale,0) as InsCapitale,"
		                    ." IFNULL(InsInteressiMora,0) as InsInteressiMora,IFNULL(InsAltriAddebiti,0) as InsAltriAddebiti,"
		                    ." IFNULL(InsSpeseInscasso,0) as InsSpeseInscasso,Nota,NomeCliente";
					$query = "v_incassi WHERE IdIncasso=$idIncasso";
					$order="IdIncasso";
		 		}
				else
				{
					die ("{failure:true, msg: Errore nella lettura dell'incasso, idIncasso mancante.}");
				}
				break;
			case "readIncArr":
				$CodContratto=$_REQUEST['CodContratto'];
				if($CodContratto!="")
				{
					$fields="ins.ImpCapitale";
					$query = "insoluto ins
							left join contratto c on(ins.idcontratto=c.idcontratto)
							where c.codcontratto like '%$CodContratto%' and ins.ImpCapitale>0 and ins.ImpInsoluto>5"; 
					$order="ins.IdInsoluto";
		 		}
				else
				{
					die ("{failure:true, msg: Errore nella lettura dell'incasso, idIncasso mancante.}");
				}
				break;
			case "readDist":
				$idD=$_REQUEST['idDistint'];
				if($idD!="")
				{
					$fields="d.IdDistinta,d.IdCompagnia,DataPagamento,Importo,UrlRicevuta,IBAN,LastUser,LastUpd,CRO,v.RepartoInc as RepartoInc";
					$query = "distintapagamento d left join v_incassi v on(d.IdCompagnia=v.IdRepartoInc) WHERE d.IdDistinta=$idD";
					$order="IdDistinta";
					$valdis = "d.IdDistinta";
		 		}
				else
				{
					die ("{failure:true, msg: Errore nella lettura della distinta, idDistinta mancante.}");
				}
				break;
			case "updateInc":
				
				$idIncasso=$_REQUEST['idIncasso'];
				
				if($idIncasso!="")
				{
		 			
					$incasso = getRow("SELECT * FROM v_incassi where IdIncasso=$idIncasso");
					$delete="";
					$IdAllegato='';
					$IdAllegatoVecchio=$incasso['IdAllegato'];
					$UrlAllegatoVecchio=$incasso['UrlAllegato'];
					
					if ($_FILES['docPath']['name'] != "")
					{
						
						$tipoAll = getScalar("SELECT IdTipoAllegato FROM tipoallegato WHERE CodTipoAllegato='GEN'");
						
						allegaDocumento($incasso,$tipoAll,'Ricevuta di pagamento','N');
						
						$IdAllegato=getInsertId();
						
						if($IdAllegatoVecchio>0)
						{
							$delete=$IdAllegatoVecchio;
						}
					}
					else
					{
						if($_POST['Allegato']=="")
						{
							if($IdAllegatoVecchio>0)
							{
								$delete=$IdAllegatoVecchio;
							}
							
						}
						else
						{
							$IdAllegato = $IdAllegatoVecchio;
						}	
					}
					
					if(!updateIncasso($IdAllegato,$incasso['IdContratto'],$idIncasso))
					{
						die ('{failure:true, msg: "Errore durante l\'aggiornamento dell\'incasso, idIncasso mancante."}');
						trace("Errore durante l'aggiornamento dell'incasso, idIncasso mancante.",false);
					}
				
					if($delete!="")
					{
							if(!execute("delete from allegato where IdAllegato=$delete"))
							 {
							 	die ('{failure:true, msg: "Errore durante l\'aggiornamento dell\'incasso."}');
							 	trace("Errore durante l'aggiornamento dell'incasso.",false);
							 }	
							 if(!unlink("../$UrlAllegatoVecchio"))
							 {
							 	die ('{failure:true, msg: "Errore durante la cancellazione del vecchio allegato dell\'incasso."}');	
							 	trace("Errore durante la cancellazione del vecchio allegato dell'incasso.");
							 }		
					}	
				}
				else
				{
					die ('{failure:true, msg: "Errore durante l\'aggiornamento dell\'incasso, idIncasso mancante."}');
					trace("Errore durante l'aggiornamento dell'incasso, idIncasso mancante.",false);
				}
				
				die ('{success:true, msg: "Incasso aggiornato."}');
				break;
			case "updateDist":
				global $context;
				$IdUtente = $context["IdUtente"];
				$idDistint=$_REQUEST['idDistint'];
				$titolo=$_REQUEST['titolo'];
				$urlAttuale='';
				if($idDistint!="")
				{
		 			
					$distinta = getRow("SELECT * FROM distintapagamento where IdDistinta=$idDistint");
					$IdRicevuta='';
				
					$UrlAllegatoVecchio=ATT_PATH."/".$distinta['UrlRicevuta'];
					if ($_FILES['docPath']['name'] != "")
					{
						
						$fileName='docPath';
						$tmpName= $_FILES[$fileName]['tmp_name'];
						$fileName = $_FILES[$fileName]['name'];     
						$fileSize = $_FILES[$fileName]['size'];
						$fileType = $_FILES[$fileName]['type'];
						
						$fileName=urldecode($fileName);
						
						if(!get_magic_quotes_gpc())
							$fileName = addslashes($fileName);
						
						$localDir=ATT_PATH."/".$distinta['IdCompagnia']."/distinte";
						if (!file_exists($localDir)) // se necessario crea il folder che ha per nome il path della carella allegati + id Compagnia + codice Contratto
						{	
							if (!mkdir($localDir,0777,true)) // true --> crea le directory ricorsivamente
							{
								trace("errore creazione dir");
								Throw new Exception("Impossibile creare la cartella dei documenti");
							}	
						}
						if (move_uploaded_file ($tmpName, $localDir."/".$fileName))
						{
							//$idAzione = getscalar("select idAzione from azione where CodAzione='ALL'");
							//writeHistory($idAzione,"Allegato ricevuta",$distinta['IdDistinta'],"Documento: $fileName");				
							//return true;
						}
						else
						{
							setLastError("Impossibile copiare il file nel repository");
							trace("Impossibile copiare il file nel repository");
							return FALSE;
						}
						//distruggiamo il vecchio file associato
						if($UrlAllegatoVecchio!=ATT_PATH."/")
						{ 	
							if(eliminaRicevuta($UrlAllegatoVecchio))
								$UrlAllegatoVecchio='';
						}
						
						$urlAttuale=$distinta['IdCompagnia']."/distinte/".$fileName;
					}else{
						$urlAttuale=''; // caso in cui non c'è il vecchio file su tabella e non c'è il docPath
						//se non c'è il path sono due i casi:
						//1. Vi è il vecchio url da tabella e basta
						//2. Il vecchio url da tabella è stato eliminato logicamente SULLA FORM e l'utente ha 
						//	 inoltrato una form senza scegliere un altra ricevuta, quindi 
						//	 ci ritroviamo con un url presente in tabella (da eliminare) ma un Doc non riempito
						//	 Lo notiamo dal fatto che se il bottone elimina è VISIBILE sulla form (bEliminaVis=false)
						//	 siamo nel caso 1 altrimenti se (bEliminaVis=true) siamo nel caso 2
						if(($_REQUEST['bEliminaVis']=='true') && ($UrlAllegatoVecchio!=ATT_PATH."/")){
							$urlAttuale=$distinta['UrlRicevuta'];
							//il bottone è visibile...si usa il vecchio
						}elseif(($_REQUEST['bEliminaVis']=='false') && ($UrlAllegatoVecchio!=ATT_PATH."/"))
							{	//il bottone è invisibile, c'è il docPath ma è vuoto(cancellato da utente)
								$urlAttuale='';
								if(eliminaRicevuta($UrlAllegatoVecchio))
									$UrlAllegatoVecchio='';
							}
					}
					
					if(!updateDistinta($idDistint,$urlAttuale))
					{
						die ('{failure:true, msg: "Errore durante l\'aggiornamento dell\'incasso, idIncasso mancante."}');
						trace("Errore durante l'aggiornamento dell'incasso, idIncasso mancante.",false);
					}
				}
				else
				{
					trace("errore");
					die ('{failure:true, msg: "Errore durante l\'aggiornamento della distinta."}');
					trace("Errore durante l'aggiornamento della distinta.",false);
				}
				
				die ('{success:true, msg: "Distinta aggiornata."}');
				break;	
			case "deleteIncasso":
					beginTrans();
					if(deleteIncasso())
					{
						commit();
						die ('{success:true, msg: "Incasso cancellato."}');
					}else{
						rollback();
						die ('{failure:true, msg: "Errore nella cancellazione dell\'incasso"}');
					}					
				break;	
			case "deleteDistinta":
					beginTrans();
					if(deleteDistinta())
					{
						commit();
						die ('{success:true, msg: "Distinta cancellata."}');
					}else{
						rollback();
						die ('{failure:true, msg: "Errore nella cancellazione della distinta"}');
					}					
				break;
			case "addDistinta":
	
					if(addDistinta($_REQUEST['ImpPagato'],$_REQUEST['idCompagnia'])==true)
					{
						$sretriveid = "SELECT LAST_INSERT_ID() AS NewId";
						$lastDist=getScalar($sretriveid);
						
						$id = explode('|', $_REQUEST['ArrI']);
						$vectId=0;
						
						for($i=1;$i<count($id);$i++){
							if (!execute("UPDATE incasso SET IdDistinta=$lastDist  WHERE IdIncasso=$id[$i]")) 
							{
								trace("Errore: UPDATE incasso SET IdDistinta=$lastDist  WHERE IdIncasso=$id[$i]",FALSE);
							}
						}
						die ('{success:true, msg: "Distinta salvata."}');
					}					
					else
						die ('{failure:true, msg: "Errore nel salvataggio della distinta"}');
						
				break;
			default:
				echo "{failure:true, msg: '$task'}";
				return;
		}
	
		if(($task=='AgenzieDistinteTabs')||($task=='readDistLot')||($task=='readDist')){
			$counter = getScalar("SELECT count(distinct $valdis) FROM $query");
			//trace("SELECT count(distinct $valdis) FROM $query");
			$fields = "distinct $fields";
		}else{
			$counter = getScalar("SELECT count(*) FROM $query");
		}
		
		if ($counter == NULL)
			$counter = 0;
		if ($counter == 0) {
				$arr = array();
		} else {
		 
			$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
			$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
			$sql = "SELECT $fields FROM $query ORDER BY ";
			//trace("s $sql");
		
			if ($_REQUEST['groupBy']>' ') {
				$sql .= $_REQUEST['groupBy'] . ' ' . $_REQUEST['groupDir'] . ', ';
			} 
			if ($_REQUEST['sort']>' ') 
				$sql .= $_REQUEST['sort'] . ' ' . $_REQUEST['dir'];
			else
				$sql .= $order;
			
			if ($start!='' || $end!='') {
		    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
			//trace("safter $sql");
			//die();
			$arr = getFetchArray($sql);
			//trace($arr);
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
	catch (Exception $e)
	{
		trace($e->getMessage());
		echo  json_encode_plus(array("success"=>false,"msg"=>$e->getMessage()));	
	}
}
/*-------------------------------------FUNZIONI-----------------------------------------------------*/
////////////////////
//UTILITA'
////////////////////
function eliminaRicevuta($UrlAllegatoVecchio)
{
	 if(!unlink("$UrlAllegatoVecchio"))
	 {
	 	die ('{failure:true, msg: "Errore durante la cancellazione della vecchia ricevuta"}');	
	 	trace("Errore durante la cancellazione della vecchia ricevuta della distinta.");
	 }		
	 return true;
					
}
////////////////////
//FILTRI
////////////////////
function filtroUtenteInc()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(IdUtenteInc,0)=$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutti gli incassi  del proprio reparto
		$clause .= " OR IFNULL(IdRepartoInc,0)=$IdReparto";
	
	return " AND ($clause)";
}
function filtroUtenteDist()
{
	global $context;
	$CodUtente = $context["Userid"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(LastUser,'')='".$CodUtente."'";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutti gli incassi  del proprio reparto
		$clause .= " OR IFNULL(v.IdRepartoInc,0)=$IdReparto";
	
	return " ($clause)";
}

function filtroUtenteContr()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(IdOperatore,0)=$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutti contratti del proprio reparto
		$clause .= " OR IFNULL(IdReparto,0)=$IdReparto";
	return " ($clause)";
}

function filtroAgenteContr()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(IdAgente,0)=$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutti i contratti della della propria agenzia
		$clause .= " OR IFNULL(IdAgenzia,0)=$IdReparto";
	return " ($clause)";
}

////////////////////
//UPDATE
////////////////////
function updateIncasso($IdAllegato,$idContratto,$IdIncasso)
{
	$IdTipoIncasso=getscalar("select IdTipoIncasso from tipoincasso where TitoloTipoIncasso='".$_POST['TitoloTipoIncasso']."'");
	
	try
	{
		global $context;
		$setClause = "";
		addSetClause($setClause,"IdContratto",$idContratto,"N");
		addSetClause($setClause,"IdAllegato",$IdAllegato,"N");
		addSetClause($setClause,"IdTipoIncasso",$IdTipoIncasso,"N");
		addSetClause($setClause,"DataRegistrazione","NOW()","G");
		addSetClause($setClause,"DataDocumento",$_POST['DataDocumento'],"D");
		addSetClause($setClause,"NumDocumento",$_POST['NumDocumento'],"S");
		addSetClause($setClause,"ImpPagato",$_POST['ImpPagato'],"N");
		addSetClause($setClause,"ImpCapitale",str_replace('.','', $_POST['IncCapitale']),"N");
		addSetClause($setClause,"ImpInteressi",str_replace('.','', $_POST['IncInteressi']),"N");
		addSetClause($setClause,"ImpAltriAddebiti",str_replace('.','', $_POST['IncAltriAddebiti']),"N");
		addSetClause($setClause,"ImpSpese",str_replace('.','', $_POST['IncSpese']),"N");
		addSetClause($setClause,"Nota",$_POST['Nota'],"S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"LastUpd","NOW()","G");
		addSetClause($setClause,"IdUtente",$context['IdUtente'],"S");
		
		//trace("UPDATE incasso $setClause WHERE IdIncasso=$IdIncasso",FALSE);
					
		if (!execute("UPDATE incasso $setClause WHERE IdIncasso=$IdIncasso")) 
				{
					trace("Errore: UPDATE incasso $setClause WHERE IdIncasso=$IdIncasso",FALSE);
					return false;
				}
	    writeHistory("NULL","Modifica incasso",$idContratto,"");
		return true;
	}    
	catch (Exception $e)
	{
		trace($e->getMessage());
    }
}

function updateDistinta($idDistint,$urlAttuale)
{
	try
	{
		global $context;
		$setClause = "";
		//addSetClause($setClause,"IdDistinta",$idDistint,"N");
		addSetClause($setClause,"IdCompagnia",$_POST['IdCompagnia'],"N");
		addSetClause($setClause,"DataPagamento",$_POST['DataPagamento'],"D");
		addSetClause($setClause,"Importo",$_POST['Importo'],"N");
		addSetClause($setClause,"UrlRicevuta","$urlAttuale","S");
		addSetClause($setClause,"IBAN",$_POST['IBAN'],"S");
		addSetClause($setClause,"CRO",$_POST['CRO'],"S");
		addSetClause($setClause,"LastUser",$context['Userid'],"S");
		addSetClause($setClause,"LastUpd","NOW()","G");
		
		//trace("UPDATE incasso $setClause WHERE IdIncasso=$IdIncasso",FALSE);
					
		if (!execute("UPDATE distintapagamento $setClause WHERE IdDistinta=$idDistint")) 
				{
					trace("Errore: UPDATE distintapagamento $setClause WHERE IdDistinta=$idDistint",FALSE);
					return false;
				}
	    //writeHistory("NULL","Modifica Distinta",$idDistint,"MOD_DIST");
		return true;
	}    
	catch (Exception $e)
	{
		trace("listaLog.php ".$e->getMessage());
    }
}

////////////////////
//DELETE
////////////////////
function deleteIncasso()
{
			$IdIncasso = $_REQUEST['idIncasso'];
			$incasso = getRow("SELECT * FROM v_incassi where IdIncasso=$IdIncasso");
			
			if(!execute("delete from incasso where IdIncasso=$IdIncasso"))
			{
				trace("Errore durante la cancellazione dell\'incasso con id : $IdIncasso",false);
				return false;
			}
			
			if($incasso['IdAllegato']>0)
			{
				
				if(!execute("delete from allegato where IdAllegato = ".$incasso['IdAllegato']))
				{
					trace("Errore durante la cancellazione dell\'allegato con id ".$incasso['IdAllegato']." dell\'incasso con id $IdIncasso",false);
					return false;
				}
				
				if(!unlink("../".$incasso['UrlAllegato']))
				{
					trace("Errore durante la cancellazione dell\'allegato incasso nella cartella attachments.");
					return false;
				}	
			}
			
			writeHistory("NULL","Cancellazione incasso",$incasso['IdContratto'],"");
			return true;
}

function deleteDistinta()
{
			$IdDistinta = $_REQUEST['idDistinta'];
			$distinta = getRow("SELECT * FROM distintapagamento where IdDistinta=$IdDistinta");
			
			if(!execute("delete from distintapagamento where IdDistinta=$IdDistinta"))
			{
				trace("Errore durante la cancellazione della distinta con id : $IdDistinta",false);
				return false;
			}
			
			if($distinta['UrlRicevuta']!='')
			{
				if(!eliminaRicevuta($distinta['UrlRicevuta']))
				{
					trace("Errore durante la cancellazione della ricevuta nella cartella.");
					return false;
				}	
			}
			
			//writeHistory("NULL","Cancellazione distinta",$distinta['IdDistinta'],"DEL_DIST");
			return true;
}

////////////////////
//AGGIUNTE
////////////////////
function addDistinta($impPagato,$idCompagnia)
{
	global $context;
	
	$esito = "";
	$valList = "";
	$colList = "";

	//addInsClause($colList,$valList,"IdDistinta",'',"N");
	addInsClause($colList,$valList,"IdCompagnia",$idCompagnia,"N");
	addInsClause($colList,$valList,"DataPagamento","","D");
	addInsClause($colList,$valList,"Importo",$impPagato,"N");
	addInsClause($colList,$valList,"UrlRicevuta","","S");
	addInsClause($colList,$valList,"IBAN","","S");
	addInsClause($colList,$valList,"CRO","","S");
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	addInsClause($colList,$valList,"LastUpd","NOW()","G");
	
	$sql =  "INSERT INTO distintapagamento ($colList)  VALUES($valList)";
	//trace($sql);
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	$conn = getDbConnection();
	if (!execute($sql)) {
		$esito = getLastError();
	}else{
		$esito=true;
	}
	$lastDist=getInsertId();
	//writeHistory("NULL","Registrazione distinta",$lastDist,"INS_DIST");
	return $esito;
	
}

?>
