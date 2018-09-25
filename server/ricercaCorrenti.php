<?php
require_once("userFunc.php");
require_once("customFunc.php");

try {
	doMain();
}
catch (Exception $e)
{
	trace($e->getMessage());
}

function doMain()
{ 
	global $context,$exportingToExcel,$exportFrom,$exportLimit;;
	$search = trim($_REQUEST["task"]);
	if ($search != '')
	{
		$search = quote_smart($search);
		$search = substr($search,1,strlen($search)-2); // toglie gli apostrofi di delimitazione, per la LIKE
		
		$idUtente = $context["IdUtente"];
		if (!($idUtente>0)) $idUtente=0; // evita errore SQL
		
		if ($_REQUEST["storico"]=='Y' || $_REQUEST["storico"]=='true') { // richiesta ricerca su storico
			$schema = MYSQL_SCHEMA."_storico";
			$mainTable = "v_insoluti_storico";
		} else {
			$schema = MYSQL_SCHEMA;
			$mainTable = "v_insoluti_opt";
		}
		
		creaSubselectNoteAllegati($nn,$haAllegati,$_REQUEST["storico"]=='Y' || $_REQUEST["storico"]=='true');

		$joinCount = ""; // join da usare nella SELECT COUNT(*)
		if ($exportingToExcel) // variabile impostata in export.php
		{	
			$fields = "v.*,dp.ImpCapitaleAffidato, CodiceFiscale,Indirizzo,CAP,Localita,ip.SiglaProvincia,TitoloRegione,lr.ListaRate,ce.*,lg.ListaGaranti";
//			$fields = "v.*,dp.ImpCapitaleAffidato, v.DataFineAffido as barraFineAffido, CodiceFiscale,Indirizzo,CAP,Localita,ip.SiglaProvincia,TitoloRegione,CodRegolaProvvigione";
			$join = " LEFT JOIN $schema.v_indirizzo_principale ip ON ip.IdCliente=v.IdCliente";
			$join.= " LEFT JOIN $schema.v_lista_rate lr ON v.IdContratto=lr.IdContratto";
			$join.= " LEFT JOIN $schema.v_campi_export ce on v.IdContratto=ce.IdContratto";
			// la condizione !='C' sottostante serve ad evitare righe duplicate sulle provvigioni STR/LEG
			$join.= " LEFT JOIN $schema.dettaglioprovvigione dp ON dp.IdContratto=v.IdContratto AND dp.DataFineAffidoContratto=v.DataFineAffido AND TipoCalcolo NOT IN ('C','X')" ;
			$join.= " LEFT JOIN $schema.listagaranti lg ON lg.IdContratto=v.IdContratto";
		}
		else 
		{
			$fields = "v.*, v.DataFineAffido as barraFineAffido, NumNote,$haAllegati as NumAllegati";
			$join = " LEFT JOIN $nn nu ON nu.IdContratto=v.IdContratto";
		}	
			
		$ordine = "v.numPratica";
		switch ($search)
		{
			case 'DettaglioExperianInvio': // NB ha colonne diverse da quelle delle liste pratiche standard
				$parm = json_decode($_REQUEST["searchFields"]);
				$fields = "v.*";
				$join = "";
				$mainTable = "v_experian_dettaglio";
				$where_search = "IdExperian=".$parm->IdExperian;
				$ordine = "Nominativo";
				break;
			case 'DettaglioExperianCliente': // NB ha colonne diverse da quelle delle liste pratiche standard
				$parm = json_decode($_REQUEST["searchFields"]);
				$fields = "v.*";
				$join = "";
				$mainTable = "v_experian_dettaglio";
				$where_search = "IdCliente=".$parm->IdCliente;
				$ordine = "Nominativo";
				break;
			case "Provvigioni": // NB: la lista dettaglio provvigioni ha colonne diverse dalla lista standard di pratiche
			case "ProvvigioniSingole": // lista usata per le provvigioni con importo su singolo contratto (es. rinegoziazioni)
				if (rowExistsInTable("dettaglioprovvigione","IdProvvigione=".$_REQUEST["stato"]))
					$mainTable = "v_dettaglio_provvigioni";
				else
					$mainTable = "v_dettaglio_provvigioni_old";
				$where_search = "v.IdProvvigione=".$_REQUEST["stato"]; // il parametro stato contiene IdProvvigione
				$fields = "v.*,prov.Provenienza";
				$join = " LEFT JOIN v_provenienza_affido prov ON prov.idcontratto=v.idcontratto AND v.idProvvigione=prov.idProvvigione";
				if ($exportingToExcel)
				{
					$fields .= ",ua.UltimaAzione,ua.DataUltimaAzione,ua.UtenteUltimaAzione,ua.NotaEvento";
					$join   .= " LEFT JOIN v_ultima_azione_utente ua ON ua.IdContratto=v.IdContratto";
				}
				
				if ($context['InternoEsterno']=="E")
				{
					/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilit� affidi */ 
					$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
					if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
					$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
					if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
					$where_search .= " AND (DataFineAffidoContratto<=CURDATE()" // 2018: se il lotto è chiuso non ci sono condizioni da applicare
                        ." OR DataInizioAffidoContratto<='$dataMassima1' AND v.stato NOT IN ('INT','STR1','STR2','LEG')"
	           			." OR DataInizioAffidoContratto<='$dataMassima2' AND v.stato IN ('STR1','STR2','LEG'))";
				}
				$ordine = "CodContratto"; // perch� nella view non c'� numPratica
				break;
			case "PSintesi":
				//trace("stato= ".$_REQUEST["stato"]." agenzia= ".$_REQUEST["agenzia"]." classe= ".$_REQUEST["classe"]." famiglia= ".$_REQUEST["prodotto"],FALSE);
				
				if (is_numeric($_REQUEST["stato"]))
					if ($_REQUEST["stato"]==1)
					{
						$mainTable = "v_insoluti_positivi";
						$where_search = "IdStatoRecupero=".$_REQUEST["stato"]." AND IdClasse=18"; //positive
					}
					else
						$where_search = "IdStatoRecupero=".$_REQUEST["stato"];
				else if (is_numeric($_REQUEST["agenzia"]))
				{
					if ($_REQUEST["lotto"]>"")
					{
						$where_search = "v.IdAgenzia=".$_REQUEST["agenzia"]." AND v.DataFineAffido='".$_REQUEST["lotto"]."'";
						$where_search .= " OR EXISTS (SELECT 1 FROM storiainsoluto si WHERE si.IdContratto=v.IdContratto AND si.IdAgenzia=".$_REQUEST["agenzia"]
						              .= " AND si.DataFineAffido='".$_REQUEST["lotto"]."' AND CodAzione!='REV')"; 
					}
					else
						$where_search = "v.IdAgenzia=".$_REQUEST["agenzia"]." AND (IdStatoRecupero!=1 OR IdClasse=18)"; 
                }
				else if	(is_numeric($_REQUEST["classe"]))	
					if ($_REQUEST["classe"]==0)
						$where_search = "IdClasse IS NULL AND (IdStatoRecupero!=1 OR IdClasse=18)";
					else
						$where_search = "IdClasse=".$_REQUEST["classe"]." AND (IdStatoRecupero!=1 OR IdClasse=18)";
				else if	(is_numeric($_REQUEST["prodotto"]))	
					$where_search = "IdFamiglia=".$_REQUEST["prodotto"]." AND (IdStatoRecupero!=1 OR IdClasse=18)";			
				else // sintesi lavorazioni interne
				{
					$inizioMese = substr($_REQUEST["lotto"],0,8)."01";
					$where_search = "v.IdContratto IN (SELECT IdContratto FROM assegnazione"
					." WHERE IdAgenzia IS NULL AND DataIni<='" . $_REQUEST["lotto"] . "' AND DataFin>='". $inizioMese ."')";
				}
				break;
			case "PSintesiAgenzia":
				$IdAgente = $_REQUEST["agente"];
				$IdAgenzia = $_REQUEST["agenzia"];
				if ($IdAgente>0)
				{
					if ($_REQUEST["lotto"]>"")
						$where_search = "v.IdAgente = $IdAgente AND v.DataFineAffido='".$_REQUEST["lotto"]."'"; 
					else
						$where_search = "EXISTS (SELECT 1 FROM assegnazione a WHERE v.IdContratto=a.IdContratto AND IdAgente=$IdAgente)"; 
				}
				else
				{
					if ($_REQUEST["lotto"]>"")
						$where_search = "v.IdAgente IS NULL AND v.IdAgenzia=$IdAgenzia AND v.DataFineAffido='".$_REQUEST["lotto"]."'"; 
					else
						$where_search = "EXISTS (SELECT 1 FROM assegnazione a WHERE v.IdContratto=a.IdContratto AND IdAgente IS NULL AND IdAgenzia=$IdAgenzia)"; 
				}
				break;
			case "PSintesiStato":
				$CodStato = $_REQUEST["stato"]; // codice stato lavorazione vedi vista v_stato_lavorazione
				$IdAgenzia = $_REQUEST["agenzia"];
				$where_search = userCanDo("READ_REPARTO")?"v.IdAgenzia=$IdAgenzia":"v.IdAgente=$idUtente";
				$join .= " left join v_stato_lavorazione sl ON v.IdContratto=sl.IdContratto";
				$joinCount = " left join v_stato_lavorazione sl ON v.IdContratto=sl.IdContratto"; // serve anche nella SELECT COUNT
				$where_search .= " AND CodStato='$CodStato'";
 				$mainTable = $CodStato=='05'?"v_insoluti_positivi":"v_insoluti_opt";
				break;
			case "NoteNonLette":
				/*$where_search = "$NumNote>0";
				if ($context['InternoEsterno']=="E")
					$where_search .= filtroInsolutiAgenziaSearch();*/
				#$fields = "v.*";
				
				$where_search = " v.IdContratto IN (SELECT IdContratto FROM nota"
                              . " WHERE TipoNota = 'C' and idutente!=$idUtente and IdUtenteDest=$idUtente"
	                          . " and Idnota not in (SELECT IdNota FROM notautente WHERE idutente=$idUtente))";
				break;
			case "Wrkflow":
				//le condizioni delle azioni di workflow che l'utente pu� gestire
				$sqlWrkFlowCondition="select CASE WHEN condizione IS NOT NULL THEN condizione
                 WHEN sa.IdStatoRecupero>0 THEN CONCAT('IdStatoRecupero=',sa.IdStatoRecupero)
                 ELSE 'true' END AS condizione ".
									 "from statoazione sa ".
									 "JOIN azione a ON sa.IdAzione=a.IdAzione ".
									 "JOIN azioneprocedura ap ON ap.IdAzione=sa.idazione ".
									 "JOIN profilofunzione pf ON pf.idfunzione=a.idfunzione ".
									 "JOIN profiloutente pu ON pu.idprofilo=pf.idprofilo ".
									 "where idstatorecuperosuccessivo is not null ".
									 "and idutente=$idUtente";
				//trace("sqlWKFCOND>>$sqlWrkFlowCondition ");
				$arrCondition = fetchValuesArray($sqlWrkFlowCondition);
				$stringCond = join(" or ",$arrCondition);
				//trace("stringCond= $stringCond");
				$fields = "v.*";
				$join .= " LEFT JOIN (SELECT IdContratto AS IdC,IdFamiglia,IdFamigliaParent,IdTipoCliente,RatePagate FROM v_contratto_workflow) cw ON cw.IdC=v.IdContratto";
				$joinCount = " LEFT JOIN (SELECT IdContratto AS IdC,IdFamiglia,IdFamigliaParent,IdTipoCliente,RatePagate FROM v_contratto_workflow) cw ON cw.IdC=v.IdContratto";
				$where_search = " stato like 'WRK%' ";
				$where_search .= filtroInsolutiOperatore();
				$where_search .= " and (".$stringCond.")";
				//trace("where= $where_search");
				$ordine = "stato";
				break;
			case "PraticheSoggetto": // lista pratiche di un coobbligato
				$parm = json_decode($_REQUEST["searchFields"]);
				$idcliente = $parm->IdCliente;
				$fields = "v.*,IFNULL(tc.TitoloTipoControparte,'Intestatario') AS ruolo";
				$join .= " LEFT JOIN controparte con ON v.IdContratto=con.IdContratto LEFT JOIN tipocontroparte tc ON con.IdTipoControparte=tc.IdTipoControparte";
				$where_search = "(v.IdCliente=$idcliente OR con.IdCliente=$idcliente)";
				break;
			case "ComplexSearch": // criteri di ricerca complessi
				$where_search = generaSearch(json_decode($_REQUEST["searchFields"],true),$schema);
				if ($where_search=="") $where_search = "true";
				if ($context['InternoEsterno']=="E")
					$where_search .= filtroInsolutiAgenziaSearch();
				trace ("Ricerca avanzata con clausola generata: ".$where_search,FALSE);
				break;
			case "ComplexSearchRin": // criteri di ricerca complessi
				$where_search = generaSearch(json_decode($_REQUEST["searchFields"],true),$schema);
				if ($context['InternoEsterno']=="E")
					$where_search .= filtroInsolutiAgenziaSearch();
				trace ("Ricerca avanzata con clausola generata: ".$where_search,FALSE);
				break;	
			default:
				$customRes = Custom_Search($search,$where_search,$schema);	
				//trace("custom $customRes");
				//trace("search $where_search");
				if ($customRes===FALSE)
				{
					$where_search = "v.numPratica LIKE ".quote_smart("%$search%");
					// Determina la lista di IdCliente che soddisfano i criteri (nome o rag. sociale)
					$clienti = fetchValuesArray("SELECT IdCliente FROM $schema.cliente WHERE IFNULL(Nominativo,RagioneSociale) LIKE '%".$search."%'");
					if (is_array($clienti) && count($clienti)>0)
						$where_search = "($where_search OR v.IdCliente IN (".join($clienti,",")."))";
				}
				
				if ($context['InternoEsterno']=="E")
					$where_search .= filtroInsolutiAgenziaSearch();
				//trace("search $where_search");
		}
		$query =  "SELECT $fields FROM $mainTable v $join WHERE $where_search";
		// Ottimizzazione, prima esegue la query con limit+1 righe, poi, se le righe sono meno di limit+1, non occorre
		// eseguire il count, altrimenti lo esegue

		// Gestione abbreviata per l'export
		if ($exportingToExcel) // rinuncia all'order by, per accelerare l'export
		{   // la export pu� passare i parametri per il limit
	//		trace("exporting con $exportFrom $exportLimit",FALSE);
			$start = $exportFrom>"" 	? $exportFrom:0;
			$limit = $exportLimit>"" 	? $exportLimit:9999999;
			$arr = getFetchArray($query);
			$data = json_encode_plus($arr);  //encode the data in json format
			echo '({"total":"' . count($arr) . '","results":' . $data . '})';
			return;
		}
		else
		{	
			$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
			$numrows = isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
			$sql = "$query ORDER BY ";
				
			if ($_POST['groupBy']>' ') 
				$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
			if ($_POST['sort']>' ') 
				$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
			else
				$sql .= $ordine;
			
			if ($start>'' || $numrows>'') 
	   			$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)($numrows+1); 
			$arr = getFetchArray($sql);
			
			if (count($arr)>$numrows && $numrows>0) // tutte le n+1 righe sono state restituite, deve contare quante ce ne sono in tutto
			{
				$counter = getScalar("SELECT count(*) FROM $mainTable v $joinCount WHERE $where_search");
				unset($arr[count($arr)-1]); // elimina elemento letto in pi�
			}
			else // sono state lette meno righe del massimo
				$counter = count($arr)+$start;
		} 
	}
	else 
		$arr = array();
	
	$myInventory = json_encode_plus($arr);  //encode the data in json format
	echo '({"total":"' . $counter . '","results":' . $myInventory . '})'; 
}

//--------------------------------------------------------------------
// filtroInsolutiAgenzia
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'agenzia sulla view v_insoluti
//--------------------------------------------------------------------
function filtroInsolutiAgenziaSearch()
{
	global $context;
	$IdReparto = $context["IdReparto"];
	
	/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilit� affidi */ 
	$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
	if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
	$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
	if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
	$condData = " AND (v.DataInizioAffido<='$dataMassima1' AND v.stato NOT IN ('STR1','STR2','LEG')"
	           ." OR v.DataInizioAffido<='$dataMassima2' AND v.stato IN ('STR1','STR2','LEG'))";
		
	return $condData." AND (v.IdAgenzia=$IdReparto OR $IdReparto IN (SELECT IdAgenzia FROM assegnazione WHERE "
	       ." IdContratto=v.IdContratto AND DataFin>=CURDATE()))";
}	
//--------------------------------------------------------------------
// filtroInsolutiOperatore
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'operatore (diretta o indiretta) sulla view v_insoluti
//--------------------------------------------------------------------
function filtroInsolutiOperatore()
{
	global $context;
	if (userCanDo("READ_TUTTE")) { // pu� vedere tutte le pratiche
		return ""; // nessuna condizione
	}
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(v.IdOperatore,0)=0$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto
		$clause .= " OR IFNULL(v.IdReparto,0)=$IdReparto";
	if (userCanDo("READ_NONASSEGNATE")) // autorizzato a vedere le pratiche non assegnate
		$clause .= " OR v.IdReparto IS NULL";
	return " AND ($clause)";
}

//--------------------------------------------------------------------
// generaSearch
// Crea la clausola Where corrispondente ai criteri specificati nel
// pannello di ricerca avanzata 
//--------------------------------------------------------------------
function generaSearch($fields,$schema)
{
	try
	{
		$clauses = array();
		
		foreach ($fields as $key=>$value)
		{
			$value = addslashes($value);
			if ($value=="") continue;
			switch ($key)
			{
				case "fldCodice":  // codice contratto
					$clauses[] = "v.numPratica LIKE '%$value%'";
					break;
				case "fldNome":   // nome
					$clienti = fetchValuesArray("SELECT IdCliente FROM $schema.cliente WHERE Nominativo LIKE '%$value%' OR RagioneSociale LIKE '%$value%'");
					$clienti = join(",",$clienti);
					if ($fields["chkIntestatario"])
					{
						if ($fields["chkGarante"]) // cerca intestatario e garanti
							$clauses[] = "(v.IdCliente IN (0$clienti)"
	                                   . " OR EXISTS (SELECT 1 FROM $schema.controparte c JOIN tipocontroparte tc ON tc.IdTipoControparte=c.IdTipoControparte
	                                                  WHERE c.IdContratto=v.IdContratto AND FlagGarante='Y' 
	                                                  AND c.IdCliente IN (0$clienti)))";   
	                    else  // cerca solo intestatario                             				
							$clauses[] = "v.IdCliente IN (0$clienti)";
					}
					else if ($fields["chkGarante"]) // cerca solo garanti
					{
						$clauses[] = "EXISTS (SELECT 1 FROM $schema.controparte c JOIN tipocontroparte tc ON tc.IdTipoControparte=c.IdTipoControparte
	                                                  WHERE c.IdContratto=v.IdContratto AND FlagGarante='Y' 
	                                                  AND c.IdCliente IN (0$clienti))";   
					}
					else // tutti e due spenti, vale come tutti e due accesi
					{
						$clauses[] = "(v.IdCliente IN (0$clienti)"
	                                   . " OR EXISTS (SELECT 1 FROM $schema.controparte c JOIN tipocontroparte tc ON tc.IdTipoControparte=c.IdTipoControparte
	                                                  WHERE c.IdContratto=v.IdContratto AND FlagGarante='Y' 
	                                                  AND c.IdCliente IN (0$clienti)))";   
					}
					break;
				case "chkFisica": // specificata persona fisica
					if ($fields["chkGiuridica"]!=1) // solo persone fisiche
					   $clauses[] = "v.IdCliente IN (SELECT IdCliente FROM $schema.cliente WHERE IdTipoCliente=2)"; 
					  break;
				case "chkGiuridica": // specificata persona giuridica
					if ($fields["chkFisica"]!=1) // solo persone giuridiche
					   $clauses[] = "v.IdCliente IN (SELECT IdCliente FROM $schema.cliente WHERE IdTipoCliente=1)"; 
					break;
				case "fldModello":  // modello veicolo
					$clauses[] = "DescrBene LIKE '%$value%'";
					break;
				case "fldTarga":  // targa
					$clauses[] = "CodBene LIKE '%$value%'";
					break;
				case "IdArea": // area di residenza (lista di id)
					$clauses[] = "v.IdCliente in (SELECT IdCliente FROM $schema.cliente WHERE ".clausolaIn("IdArea",$value).")";
					break;
				case "IdCompagnia": // dealer (lista di id)
					$clauses[] = clausolaIn("IdDealer",$value);
					break;
				case "IdStatoContratto": // stato contratto (lista di id)
					$clauses[] = clausolaIn("IdStatoContratto",$value);
					break;
				case "IdTipoPagamento": // tipo pagamento (lista di id)
					$clauses[] = clausolaIn("IdTipoPagamento",$value);
					break;
				case "IdClasse": // Classificazione (lista di id)
					$clauses[] = clausolaIn("IdClasse",$value);
					break;
				case "IdRegolaProvvigione": // affido (lista di id)
					$clauses[] = clausolaIn("IdRegolaProvvigione",$value);
					break;
				case "fldImpFinanziatoDa":
					$clauses[] = "ImpFinanziato>=$value";
					break;
				case "fldImpFinanziatoA":
					$clauses[] = "ImpFinanziato<=$value";
					break;
				case "dataInizioDa":
					$value = substr($value,0,10); // prende solo fino al giorno
					$clauses[] = "DataInizioAffido>='$value'";
					break;
				case "dataInizioA":
					$value = substr($value,0,10); // prende solo fino al giorno
					$clauses[] = "DataInizioAffido<='$value'";
					break;
				case "CodTipoContratto":
					$clauses[] = "SUBSTR(numPratica,1,2) IN ('". str_replace(",","','",$value)."')";
					break;
				case "IdFiliale": // filiale (lista di id)
					$clauses[] = clausolaIn("IdFiliale",$value);
					break;
				case "IdFormaGiuridica": // forma giuridica (attenzione id in realt� � CodFormaGiuridica, una stringa)
					$clauses[] = "CodFormaGiuridica IN ('". str_replace(",","','",$value)."')";
					break;
				case "IdProdotto": // prodotto (lista di id)
					$clauses[] = clausolaIn("IdProdotto",$value);
					break;
				case "IdStatoRecupero": // stato recupero (lista di id)
					$clauses[] = clausolaIn("IdStatoRecupero",$value);
					break;
				case "IdAttributo": // attributo (lista di id)
					$clauses[] = clausolaIn("IdAttributo",$value);
					break;
				case "IdCategoria": // categoria (lista di id)
					$clauses[] = clausolaIn("IdCategoria",$value);
					break;
				case "IdAgente": // Agente esterno assegnatario
					$clauses[] = clausolaIn("IdAgente",$value);
					break;
				case "IdOperatore": // Operatore interno assegnatario
					$clauses[] = clausolaIn("IdOperatore",$value);
					break;
				case "IdTeam": // Team interno assegnatario
					$clauses[] = clausolaIn("IdTeam",$value);
					break;
				case "fldImpDebitoDa":
					$clauses[] = "ImpInsoluto>=$value";
					break;
				case "fldImpDebitoA":
					$clauses[] = "ImpInsoluto<=$value";
					break;
				case "fldImpResiduoDa":
					$clauses[] = "ImpDebitoResiduo>=$value";
					break;
				case "fldImpResiduoA":
					$clauses[] = "ImpDebitoResiduo<=$value";
					break;
				case "dataFineDa":
					$value = substr($value,0,10); // prende solo fino al giorno
					$clauses[] = "DataFineAffido>='$value'";
					break;
				case "dataFineA":
					$value = substr($value,0,10); // prende solo fino al giorno
					$clauses[] = "DataFineAffido<='$value'";
					break;
				case "fldPercDebitoDa":
					$clauses[] = "ImpInsoluto*100/ImpFinanziato>=$value";
					break;
				case "fldPercDebitoA":
					$clauses[] = "ImpInsoluto*100/ImpFinanziato<=$value";
					break;
				case "fldNumInsolutiDa":
					$clauses[] = "insoluti>=$value";
					break;
				case "fldNumInsolutiA":
					$clauses[] = "insoluti<=$value";
					break;
				case "fldNumRateDa": // rate future
					$clauses[] = "RateFuture>=$value";
					break;
				case "fldNumRateA":
					$clauses[] = "RateFuture<=$value";
					break;
				case "fldNumRatePagateDa": // rate future
					$clauses[] = "v.NumRate-RateFuture-NumInsoluti>=$value";
					break;
				case "fldNumRatePagateA":
					$clauses[] = "v.NumRate-RateFuture-NumInsoluti<=$value";
					break;
				case "fldNumRateTotaliDa": // rate future
					$clauses[] = "v.NumRate>=$value";
					break;
				case "fldNumRateTotaliA":
					$clauses[] = "v.NumRate<=$value";
					break;
			}
		}
		
		return join(" AND ",$clauses); // genera la clausola WHERE
	}
	catch (Exception $e)
	{
		return $e->getMessage();
	}
}
//--------------------------------------------------------------------
// clausolaIn
// Genera nua clausola IN per le liste di ID separati da virgole
// tenendo anche conto del fatto che un id=-1 significa NULL
// (perch� lo generano cos� le viste usate in formRicercaAvanzata.php
//--------------------------------------------------------------------
function clausolaIn($field,$values)
{
	return "IFNULL($field,-1) IN ($values)";
	/*
	if (strpos(",$values,","-1")===FALSE) // non c'� il valore -1
		return "$field IN ($values)";
	else if ($values=="-1") // c'� solo il valore -1
		return "$field IS NULL";
	else // ci sono sia il -1 sia altri valori
		return "($field IS NULL OR $field IN ($values))";	
		*/
}
?>