<?php
//
// Esegue la lettura dei dati per tutte le liste di pratiche
//
require_once("userFunc.php");
require_once("customFunc.php");

$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;

if (!$attiva) {
	exit();
}

try {
	doMain();
}
catch (Exception $e)
{
	trace($e->getMessage());
}

function doMain()
{
	global $context,$exportingToExcel,$exportFrom,$exportLimit;
	
	if (!$exportingToExcel)
		$exportingToExcel = $_GET["excel"]=='Y'; // passato nel modo nuovo
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	$isStorico = ($task=='storico' || $task=='storicodip');

	$idUtente = $context["IdUtente"];
	if (!($idUtente>0)) $idUtente=0; // evita errore SQL
		
	creaSubselectNoteAllegati($nn,$haAllegati,$isStorico);
	
	// Clausole di ORDER BY per ottenere che i NULL vadano in fondo
	$sortDStato = "(CASE WHEN DataCambioStato IS NULL AND DataCambioClasse IS NULL THEN '2'"
		." WHEN DataCambioClasse IS NULL THEN '1' ELSE '0' END)";
	$sortDScad = "(CASE WHEN DataScadenzaAzione IS NULL THEN '1' ELSE '0' END)";
	
	// Aggiunge una join per campi ulteriori	
	if ($exportingToExcel && $task!='dipendenti' && $task!='storicodip') // variabile impostata in export.php
	{
		$fields = "v.*,dp.ImpCapitaleAffidato, CodiceFiscale,Indirizzo,CAP,Localita,ip.SiglaProvincia,TitoloRegione,lr.ListaRate,ce.*,lg.ListaGaranti";

		//$schema = $isStorico?'db_cnc_storico':'db_cnc';	
		$schema = MYSQL_SCHEMA.($isStorico?'_storico':'');
		$join = " LEFT JOIN $schema.v_indirizzo_principale ip ON ip.IdCliente=v.IdCliente";
		$join.= " LEFT JOIN $schema.v_lista_rate lr ON v.IdContratto=lr.IdContratto";
		$join.= " LEFT JOIN $schema.v_campi_export ce on v.IdContratto=ce.IdContratto";
		$join.= " LEFT JOIN $schema.dettaglioprovvigione dp ON dp.IdContratto=v.IdContratto AND dp.DataFineAffidoContratto=v.DataFineAffido AND TipoCalcolo NOT IN ('C','X')" ;
		$join.= " LEFT JOIN $schema.listagaranti lg ON lg.IdContratto=v.IdContratto";

		// 6/11/14: aggiunta ultima azione utente
		$fields .= ",ua.UltimaAzione,ua.DataUltimaAzione,ua.UtenteUltimaAzione,ua.NotaEvento";
		$join   .= " LEFT JOIN $schema.v_ultima_azione_utente ua ON ua.IdContratto=v.IdContratto";
		//17/10/2017
		if($task=='nonstarted'){
			$fields.= ", nse.*";
			$join.= " LEFT JOIN $schema.v_non_started_export nse ON nse.IdContratto = v.IdContratto";
		}
	} else if ($task=='dipendenti' or $task=='storicodip') {
		$fields = "v.*,NumNote,$haAllegati as NumAllegati";
		$join = " LEFT JOIN $nn nu ON nu.IdContratto=v.IdContratto";
	} else	{
		$fields = "v.*,NumNote,$haAllegati as NumAllegati";
		$fields .= ",IFNULL(v.ImpDebitoResiduo,0)+IFNULL(v.ImpCapitale,0) AS CapitaleResiduo";
		$join = " LEFT JOIN $nn nu ON nu.IdContratto=v.IdContratto";
	}	
	$ArrColor=array();

	// Condizione per escludere da alcune liste gli estinti con debito
	// (deve essere la negazione della WHERE di v_insoluti_estinti_opt
	/*$condNotEstinti = " AND NOT (v.impinsoluto>26 AND v.idstatocontratto in (2, 3, 5, 14, 17, 22, 24)"
				    . " AND (LEFT(CodContratto,2)='LO' OR v.IdAttributo IN (63,68,71,80,82,84,88))"
				    . " AND v.idcontrattoderivato is null AND v.DataChiusura<=CURDATE() and v.IdClasse!=19)";
	*/
	$condNotEstinti = " AND IFNULL(v.IdClasse,18)!=90";
	
	// Eventuale condizione passata come parametro
	$extraCondition = $_REQUEST['sqlExtraCondition'];
	//trace("extracondition='$extraCondition' task='$task'",POST);
	switch($task){
	//----------------------------------------------------------------
	// tipi di griglia per operatore interno
	//----------------------------------------------------------------
	case "inAttesa":
		// 21/5/2013: esclude quelle con importo <26 (positive)
		// sono in attesa quelle senza IdAgenzia e che non sono classificate in modo da richiedere
		// trattamento senza affido
		$query = "v_insoluti_opt v $join WHERE v.stato='ATT' and v.idclasse not in(12,37,40,41) AND v.ImpInsoluto>=26 $extraCondition $condNotEstinti";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='ATT' and v.idclasse not in(12,37,40,41) AND v.ImpInsoluto>=26 $extraCondition $condNotEstinti";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;
	case "preRecupero":
		// pratiche in prerecupero
		$query = "v_insoluti_opt v $join WHERE v.idclasse in (12,37,40,41) $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.idclasse in (12,37,40,41) $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;
	case "scadenzaAffidiSTR1": // scadenza affidamenti STR soft
		// pratiche in scadenza di affido stragiudiziale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_str_opt v $join Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT SOFT' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_str_opt v Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT SOFT' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.DataFineAffido,v.numPratica";
		break;
	case "scadenzaAffidiSTR2": // scadenza affidamenti STR hard
		// pratiche in scadenza di affido stragiudiziale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_str_opt v $join Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT HARD' $extraCondition ";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_str_opt v Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT HARD' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.DataFineAffido,v.numPratica";
		break;
	case "scadenzaAffidiSTR3": // scadenza affidamenti STR strong
		// pratiche in scadenza di affido stragiudiziale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_str_opt v $join Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT STRONG' $extraCondition ";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_str_opt v Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='DBT STRONG' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.DataFineAffido,v.numPratica";
		break;
	case "scadenzaAffidiSTR4": // scadenza affidamenti STR REPO
		// pratiche in scadenza di affidi stragiudiziale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_str_opt v $join Where v.DataFineAffido<='$NewDate' AND FasciaRecupero LIKE '%REPO%' $extraCondition ";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_str_opt v Where v.DataFineAffido<='$NewDate' AND FasciaRecupero LIKE '%REPO%' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.DataFineAffido,v.numPratica";
		break;
	case "scadenzaAffidiLEG": // scadenza affidamenti Legali
		// pratiche in scadenza di affidi legale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_str_opt v $join Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='LEGALE' $extraCondition ";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_str_opt v Where v.DataFineAffido<='$NewDate' AND FasciaRecupero='LEGALE' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.DataFineAffido,v.numPratica";
		break;
	case "scadenzaAzioniLEG": // scadenza azioni Legali
		// pratiche in scadenza di azione legale entro N giorni
		$NewDate=Date('Y-m-j', strtotime("+".getSysParm("GG_ALLA_SCAD_AFF_STR","15")." days"));
		$query = "v_insoluti_azioni v $join Where DataScadenza<='$NewDate' AND FasciaRecupero='LEGALE' $extraCondition ";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_azioni v Where DataScadenza<='$NewDate' AND FasciaRecupero='LEGALE' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "DataScadenza,v.numPratica";
		break;
	case "saldoStralcio":
		// pratiche in saldo e stralcio
		$query = "v_insoluti_opt v $join WHERE DataSaldoStralcio is not null $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.DataSaldoStralcio is not null $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "DataSaldoStralcio,v.numPratica";
		break;
	case "estinte":
		// Pratiche estinte con debito
		$anno = $_REQUEST['anno'];
		$query = "v_insoluti_opt v $join WHERE v.IdClasse=90 AND ($anno=0 OR YEAR(DataChiusura)=$anno)" ;
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_opt v WHERE IdClasse=90 AND ($anno=0 OR YEAR(DataChiusura)=$anno)";
		$ordine = "v.numPratica";
		break;
	case "storico":
		// Pratiche nello storico
		if ($extraCondition>'') { // se specificato il criteri, procede)
			$query = "v_insoluti_storico v $join WHERE v.NumPratica NOT LIKE 'KG%' AND $extraCondition" ;
			$queryForCount = "v_insoluti_storico_count v WHERE NumPratica NOT LIKE 'KG%' AND $extraCondition";
			$ordine = "v.numPratica";
		} else { // nessun criterio: � la visualizzazione iniziale dello storico, vuota
			echo '({"total":0,"results":[]})';
			return;
		}
		break;
	case "storicodip": // pratiche dipendenti storicizzate
		$query = MYSQL_SCHEMA."_storico.v_insoluti_dipendenti v $join WHERE true ";
		$queryForCount = MYSQL_SCHEMA."_storico.v_insoluti_dipendenti v WHERE true ";
		$ordine = "Nominativo";
		break;
		
	case "interne":
		// 21/5/2013: esclude quelle con importo <26 (positive)
		// Pratiche presso operatore con flag che indica niente affido
		
		if ($_REQUEST['expAll']==1) { // export di tutte le pagina in lavorazione interna
			$query = "v_insoluti_opt v $join WHERE v.stato='INT' AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
			$query .= filtroInsolutiOperatore();
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
			$queryForCount .= filtroInsolutiOperatore();
		} else {
			$cat = ($_REQUEST['Categoria']) ? ($_REQUEST['Categoria']) : 0;
			if ($cat>0)
			{
				$query = "v_insoluti_opt v $join WHERE v.stato='INT' and v.IdCategoria=$cat  AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' and v.IdCategoria=$cat AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			else
			{
				$query = "v_insoluti_opt v $join WHERE v.stato IN ('INT','OPE') and v.categoria='Nessuna' and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('INT','OPE') and v.categoria='Nessuna' and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		}
		break;
	case "maxirata":
		// 21/5/2013: esclude quelle con importo <26 (positive)
		// Pratiche presso operatore con flag che indica niente affido
		
		if ($_REQUEST['expAll']==1) { // export di tutte le pagina in lavorazione interna
			$query = "v_insoluti_opt v $join WHERE v.stato='INT' AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
			$query .= filtroInsolutiOperatore();
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
			$queryForCount .= filtroInsolutiOperatore();
		} else {
			$cat = ($_REQUEST['CategoriaMaxirata']) ? ($_REQUEST['CategoriaMaxirata']) : 0;
			if ($cat>0)
			{
				$query = "v_insoluti_opt v $join WHERE v.stato='INT' and v.IdCategoriaMaxirata=$cat  AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' and v.IdCategoriaMaxirata=$cat AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			else
			{
				$query = "v_insoluti_opt v $join WHERE v.stato IN ('INT','OPE') and v.categoria='Gestione maxi rate' and v.CategoriaMaxirata is null and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('INT','OPE') and v.categoria='Gestione maxi rate' and v.CategoriaMaxirata is null and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		}
		break;
	case "riscattoleasing":
		// 21/5/2013: esclude quelle con importo <26 (positive)
		// Pratiche presso operatore con flag che indica niente affido
		
		if ($_REQUEST['expAll']==1) { // export di tutte le pagina in lavorazione interna
			$query = "v_insoluti_opt v $join WHERE v.stato='INT' AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
			$query .= filtroInsolutiOperatore();
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
			$queryForCount .= filtroInsolutiOperatore();
		} else {
			$cat = ($_REQUEST['CategoriaRiscattoLeasing']) ? ($_REQUEST['CategoriaRiscattoLeasing']) : 0;
			if ($cat>0)
			{
				$query = "v_insoluti_opt v $join WHERE v.stato='INT' and v.IdCategoriaRiscattoLeasing=$cat  AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato='INT' and v.IdCategoriaRiscattoLeasing=$cat AND InRecupero='Y' and v.idclasse not in(12,37,40,41)  AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			else
			{
				$query = "v_insoluti_opt v $join WHERE v.stato IN ('INT','OPE') and v.categoria='Nessuna' and v.CategoriaRiscattoLeasing is null and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti" ;
				$query .= filtroInsolutiOperatore();
				$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('INT','OPE') and v.categoria='Nessuna' and v.CategoriaRiscattoLeasing is null and v.idclasse not in(18,12,37,40,41) AND v.ImpInsoluto>=26 $condNotEstinti";
				$queryForCount .= filtroInsolutiOperatore();
			}
			$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		}
		break;		
	case "attive":
		// Pratiche presso agenzia
		$query = "v_insoluti_opt v $join WHERE v.stato='AGE' AND classif!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='AGE' AND classif!='POS' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;

	/*case "workflow":
		$proc = ($_REQUEST['Procedura']) ? ($_REQUEST['Procedura']) : 0;
		$query = "v_insoluti_opt v $join WHERE stato LIKE 'WRK%'"
		       . " AND IdStatoRecupero IN (SELECT IdStatoRecuperoSuccessivo FROM statoazione s,azioneprocedura a "
		       . " WHERE s.IdAzione=a.IdAzione AND IdProcedura=$proc)";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_opt v WHERE stato LIKE 'WRK%'"
		       . " AND IdStatoRecupero IN (SELECT IdStatoRecuperoSuccessivo FROM statoazione s,azioneprocedura a "
		       . " WHERE s.IdAzione=a.IdAzione AND IdProcedura=$proc)";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "OrdineStato,$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;*/
	case "workflow":
		$stRec = ($_REQUEST['StatoRecupero']) ? ($_REQUEST['StatoRecupero']) : 0;
		//trace("chiamata praticheCorrenti.php per workflow stato=$stRec expAll=".$_REQUEST['expAll'],false);
		//---------------------------------------------------------------------------------------
		// Export cessioni con dati cliente o dati garante
		//---------------------------------------------------------------------------------------
		if ($_REQUEST['expAll']=='v_cessioni_con_cliente' || $_REQUEST['expAll']=='v_cessioni_con_garante') { 
			$query = $_REQUEST['expAll'] ." v WHERE IdStatoRecupero = $stRec";
			$queryForCount = $query;
			$ordine = "numPratica";
			$fields = "v.*";
		//---------------------------------------------------------------------------------------
		// Export speciale per workflow DBT
		//---------------------------------------------------------------------------------------
		} else if ($_REQUEST['expAll']=='v_pratiche_dbt') { 
			$query = $_REQUEST['expAll'] ." v WHERE IdStatoRecupero = $stRec";
			$queryForCount = $query;
			$ordine = "numPratica";
			$fields = "v.*";
		} else {
		//---------------------------------------------------------------------------------------
		// Lista o export normale
		//---------------------------------------------------------------------------------------
			$stRec = ($_REQUEST['StatoRecupero']) ? ($_REQUEST['StatoRecupero']) : 0;
			$query = "v_insoluti_opt v $join WHERE v.stato LIKE 'WRK%' AND IdStatoRecupero = $stRec";
			$queryForCount = "v_insoluti_opt v WHERE v.stato LIKE 'WRK%' AND IdStatoRecupero = $stRec";
			$ordine = "OrdineStato,$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
			$query .= filtroInsolutiOperatore();
			$queryForCount .= filtroInsolutiOperatore();
		}
		break;
	case "inScadenza":
		$query = "v_insoluti_scadenza v $join WHERE scadenza>=CURDATE() $extraCondition";
		$query .= filtroInsolutiScadenzeOperatore();
		$queryForCount = "v_insoluti_scadenza v WHERE scadenza>=CURDATE() $extraCondition";
		$queryForCount .= filtroInsolutiScadenzeOperatore();
		$ordine = "scadenza";
		break;
	case "positive":
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse";
		$query = "v_insoluti_positivi_opt v $join WHERE v.idAgenzia>0 AND v.TipoRecupero NOT IN (2,3,4) $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_positivi_opt v WHERE v.idAgenzia>0 AND v.TipoRecupero NOT IN (2,3,4) $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		break;
	case "incassiParziali":
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse";
// 28/6/2011 GDF: impPagato indica quanto ha pagato il cliente in questo affido, quindi basta testare
//      se � non zero e se c'� ancora insoluto
		$query = "v_insoluti_opt v $join WHERE v.IdAgenzia>0 AND v.Stato NOT IN ('STR1','STR2','LEG') AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.IdAgenzia>0 AND v.Stato NOT IN ('STR1','STR2','LEG') AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		break;
	case "override":
		$query = "v_insoluti_override v $join WHERE classif!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_override v WHERE classif!='POS' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza,TitoloTipoSpeciale";
		break;
	case "svaluta":
		$query = "v_insoluti_opt v $join WHERE PercSvalutazione>0 AND IdStatoRecupero NOT IN (79,84)";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE PercSvalutazione>0 AND IdStatoRecupero NOT IN (79,84)";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;

	// Situazione debitoria: il parametro di selezione per le varie pagine � passato in extraCondition
	// vedi tabs_PraticheSituazione.js
	case "situazione":
		$query = "v_insoluti_situazione v $join WHERE $extraCondition";
		$queryForCount = "v_insoluti_situazione v WHERE $extraCondition";
		$ordine = "CodContratto";
		break;

	// Stati legali: il parametro di selezione per le varie pagine � passato in extraCondition
	// vedi tabs_PraticheStatiLegali.js
	case "statolegale":
		if ($_REQUEST['expAll']==1) {//export di tutte le pratiche in stato legale
			$query = "v_insoluti_opt v $join WHERE v.stato='LEG' "; //AND (IdStatoLegale IS NULL OR IdStatoLegale>0) ";
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato='LEG' "; //AND (IdStatoLegale IS NULL OR IdStatoLegale>0) ";
			$ordine = "CodContratto";
			//trace("ERROR: ".$query, TRUE);
		}else{
			$query = "v_insoluti_opt v $join WHERE v.stato='LEG' AND $extraCondition";
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato='LEG' AND $extraCondition";
			$ordine = "CodContratto";
		}
		break;
	// Stati stragiudiziali: il parametro di selezione per le varie pagine � passato in extraCondition
	// vedi tabs_PraticheStatiStragiudiziali.js
	case "statostragiudiziale":
		if ($_REQUEST['expAll']==1) {//export di tutte le pratiche stragiudiziali
			$query = "v_insoluti_opt v $join WHERE v.stato IN ('STR1', 'STR2') "; //" AND $extraCondition";
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('STR1', 'STR2') "; // AND $extraCondition";
			$ordine = "CodContratto";
		}else{
			$query = "v_insoluti_opt v $join WHERE v.stato IN ('STR1', 'STR2') AND $extraCondition";
			$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('STR1', 'STR2') AND $extraCondition";
			$ordine = "CodContratto";
		}
		break;
	//---------------------------------------------------------------------------------------
	// Liste rinegoziazioni in affido
	//---------------------------------------------------------------------------------------
	case 'rinegozia_affidate':
		$query = "v_insoluti_opt v $join WHERE v.stato='AFR'";
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='AFR'";
		$ordine = "CodContratto";
		break;
		
	//----------------------------------------------------------------
	// Gestione dipendenti
	//----------------------------------------------------------------
	case "dipendenti":
		$query = "v_insoluti_dipendenti v $join WHERE true ";
		$queryForCount = "v_insoluti_dipendenti v WHERE true ";
		$ordine = "Nominativo";
		break;
	//----------------------------------------------------------------
	// tipi di griglia per stragiudiziale e legale
	//----------------------------------------------------------------
	case "STRLEGALL": // Lista completa pratiche STR/LEG o simili
		$condizioneAll = "v.stato IN ('STR1','STR2','LEG','ATS','ATP') # affidate STR/LEG 
OR v.stato LIKE 'WRK%' # in worflow
OR classif IN ('STR','LEG','DBT','FUR','TRU','FALL','DEC','PDR','PDR1','PDR2','PDR3','DPR','RIS')
OR v.stato='INT' AND ImpInsoluto>26 AND Classif!='EXIT'"; 

		$query = "v_insoluti_opt v $join WHERE ($condizioneAll) $extraCondition $condNotEstinti";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE ($condizioneAll) $extraCondition $condNotEstinti";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "ATSNULL": // in attesa di affido STR/LEG senza forzatura
		$query = "v_insoluti_opt v $join WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IS NULL  $extraCondition $condNotEstinti";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IS NULL  $extraCondition $condNotEstinti";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "ATSSTR": // in attesa di affido STR/LEG con forzatura STR
		// Individua i codice regola provvigionale di tipo stragiudiziale
		$regole = fetchValuesArray("SELECT CodRegolaProvvigione FROM regolaprovvigione WHERE CURDATE()+INTERVAL 1 MONTH BETWEEN DataIni AND DataFin AND (FasciaRecupero LIKE 'DBT%' OR FasciaRecupero LIKE '%REPO%')");
		$regole = "'".join("','",$regole)."'";
		$query = "v_insoluti_segnati v $join WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IN ($regole) $extraCondition $condNotEstinti";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IN ($regole) $extraCondition $condNotEstinti";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "ATSLEG": // in attesa di affido STR/LEG con forzatura LEG
		// Individua i codice regola provvigionale di tipo legale
		$regole = fetchValuesArray("SELECT CodRegolaProvvigione FROM regolaprovvigione WHERE CURDATE()+INTERVAL 1 MONTH BETWEEN DataIni AND DataFin AND FasciaRecupero = 'LEGALE'");
		$regole = "'".join("','",$regole)."','-2'"; // aggiunge anche il codice -2 che significa forzatura affido a legale generico (vedi combobox inproposta DBT)
		$query = "v_insoluti_segnati v $join WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IN ($regole) $extraCondition $condNotEstinti";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='ATS' AND IFNULL(classif,'')!='POS' AND CodRegolaProvvigione IN ($regole) $extraCondition $condNotEstinti";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "ATP": // in attesa di passaggio a perdita o cessione
		$query = "v_insoluti_opt v $join WHERE v.stato='ATP' AND IFNULL(classif,'')!='POS'";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='ATP' AND IFNULL(classif,'')!='POS'";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "STR1": // stragiudiziale soft
		$query = "v_insoluti_opt v $join WHERE v.stato='STR1' AND v.CodRegolaProvvigione NOT IN ('25','RE') AND IFNULL(classif,'')!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='STR1' AND v.CodRegolaProvvigione NOT IN ('25','RE') AND IFNULL(classif,'')!='POS' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "STR2": // stragiudiziale hard
		$query = "v_insoluti_opt v $join WHERE v.stato='STR1' AND v.CodRegolaProvvigione='25' AND IFNULL(classif,'')!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='STR1' AND v.CodRegolaProvvigione='25' AND IFNULL(classif,'')!='POS' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "STR3": // stragiudiziale strong
		$query = "v_insoluti_opt v $join WHERE v.stato='STR2' AND IFNULL(classif,'')!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='STR2' AND IFNULL(classif,'')!='POS' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "STRREPO": // stragiudiziale REPO
		$query = "v_insoluti_opt v $join WHERE v.stato='STR1' AND v.CodRegolaProvvigione='RE' AND IFNULL(classif,'')!='POS' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='STR1' AND v.CodRegolaProvvigione='RE' AND IFNULL(classif,'')!='POS' $extraCondition ";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "LEGLEA":
		$query = "v_insoluti_opt v $join WHERE v.stato='LEG' AND IFNULL(classif,'')!='POS' AND v.NumPratica Like 'LE%' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='LEG' AND IFNULL(classif,'')!='POS' AND v.NumPratica Like 'LE%' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "LEGLOA":
		$query = "v_insoluti_opt v $join WHERE v.stato='LEG' AND IFNULL(classif,'')!='POS' AND v.NumPratica Like 'LO%' $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato='LEG' AND IFNULL(classif,'')!='POS' AND v.NumPratica Like 'LO%' $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
				break;
	case "STRPOS":
		$query = "v_insoluti_positivi_opt v $join WHERE v.TipoRecupero=2 AND v.DataFineAffido>=CURDATE() $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_positivi_opt v WHERE  v.TipoRecupero=2 AND v.DataFineAffido>=CURDATE() $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "LEGPOS":
		$query = "v_insoluti_positivi_opt v $join WHERE v.TipoRecupero=3 AND v.DataFineAffido>=CURDATE() $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_positivi_opt v WHERE  v.TipoRecupero=3 AND v.DataFineAffido>=CURDATE() $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;		
	case "STRLEGPOS":
		$query = "v_insoluti_positivi_opt v $join WHERE v.TipoRecupero in (2,3) AND v.DataFineAffido>=CURDATE() $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_positivi_opt v WHERE  v.TipoRecupero in (2,3) AND v.DataFineAffido>=CURDATE() $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;		
	case "LEGINC":
		$query = "v_insoluti_opt v $join WHERE v.stato IN ('LEG') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato NOT IN ('LEG') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "STRINC":
		$query = "v_insoluti_opt v $join WHERE v.stato IN ('STR1','STR2') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('STR1','STR2') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;		
	case "STRLEGINC":
		$query = "v_insoluti_opt v $join WHERE v.stato IN ('STR1','STR2','LEG') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('STR1','STR2','LEG') AND v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0 $extraCondition";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;		
	case "CES": // in cessione
		$query = "v_insoluti_opt v $join WHERE v.stato IN ('CES','WRKAUTCES')";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('CES','WRKAUTCES')";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
	case "WO": // in write off
		$query = "v_insoluti_opt v $join WHERE v.stato IN ('WOF','WRKAUTWO')";
		$query .= filtroInsolutiOperatore();
		$queryForCount = "v_insoluti_count_opt v WHERE v.stato IN ('WOF','WRKAUTWO')";
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "v.numPratica";
		break;
		
	//----------------------------------------------------------------
	// tipi di griglia per affidamento
	//----------------------------------------------------------------
	case "aff-Ag":
		// Il parametro idA contiene una chiave composta da IdAgenzia,CodRegolaProvvigione
		$chiave = split(",",$_REQUEST['idA']);
		$IdAgenzia = $chiave[0];
		$CodProvv = $chiave[1];

		$fields .= ", v.DataFineAffido as barraFineAffido";
		if ($CodProvv=='')
		{
			$query = "v_insoluti_opt v $join WHERE v.IdAgenzia=$IdAgenzia";
			$queryForCount = "v_insoluti_count_opt v WHERE v.idagenzia=$IdAgenzia";
		}
		else // sottoinsieme degli affidi con un dato codice provvigionale
		{
			$query = "v_insoluti_opt v $join WHERE v.IdAgenzia=$IdAgenzia AND CodRegolaProvvigione='$CodProvv'";
			$queryForCount = "v_insoluti_count_opt v WHERE v.idagenzia=$IdAgenzia AND CodRegolaProvvigione='$CodProvv'";
		}
		$query .= filtroInsolutiOperatore();
		$queryForCount .= filtroInsolutiOperatore();
		$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza";
		break;
	case "AgenzieProrogheTabs":
		$fields = "distinct idAgenzia,TitoloUfficio AS Agenzia";
		$query = "contratto v"
			  . " JOIN storiarecupero sr ON sr.IdContratto=v.IdContratto AND IdAzione=140 AND DATE(DataEvento) BETWEEN v.DataInizioAffido AND v.DataFineAffido"
			  . " JOIN reparto r ON r.IdReparto=v.IdAgenzia";
		$ordine = "TitoloUfficio";
		break;
	case "readProLotMain":
		$IdAg=$_REQUEST['repId'];
		
		$SqlcontrattiDate="SELECT DataInizioAffido,DataFineAffido,Contratto,DataEvento 
						from 
						(select DataInizioAffido,v.DataFineAffido,v.IdContratto as Contratto,sr.DataEvento
						from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
						where sr.idazione=140 
						AND DATE(DataEvento) BETWEEN v.DataInizioAffido AND v.DataFineAffido 
						and idagenzia = $IdAg
						ORDER BY DataEvento desc) as tavola
						group by contratto
						ORDER BY DataEvento desc";
		$ContrDate=getFetchArray($SqlcontrattiDate);
		//trace("InfoArr ".print_r($ContrDate,true));
		//crea l'array degli stati di ognuno di essi per vedere se son stati espletati e come
		foreach($ContrDate as $elemento)
		{
			$sqlColor="select sr.*,sr.idcontratto,sr.DataEvento,c.DataFineAffido,
				        CASE 
				                        WHEN sr.IdAzione=8 THEN 'Accettata'
				                        WHEN sr.IdAzione=142 THEN 'Rifiutata'
				                        ELSE 
				            case
				               when date(now())>c.DataFineAffido Then 'Scaduta'
				               else 'In attesa' END
				        END AS ColorState
						from storiarecupero sr
						left join contratto c on(sr.idcontratto=c.idcontratto)
						where sr.idcontratto=".$elemento['Contratto']. 
						" and sr.DataEvento >= '".$elemento['DataEvento'].
						"' and sr.idazione in (8,142,140)
						order by sr.DataEvento desc";
			//trace("sqlcolor $sqlColor");
			$ResColor=getRow($sqlColor);
			$ArrColor[$ResColor['idcontratto']]=$ResColor['ColorState'];			
		}
		//trace("colorArr ".print_r($ArrColor,true));
		//query d'estrazione normale
		$fields .= ", v.DataFineAffido as barraFineAffido,v.IdContratto as Contratto,
				   1 AS ColorState,sr.DataEvento";
		$query = "v_insoluti_opt v $join JOIN storiarecupero sr on v.idcontratto=sr.idcontratto"
				." and sr.idAzione=140 AND DATE(DataEvento) BETWEEN DataInizioAffido AND DataFineAffido"
				." WHERE v.idagenzia =$IdAg";
		/*$fields .= ", v.DataFineAffido as barraFineAffido,v.IdContratto as Contratto,
				    CASE WHEN EXISTS (select 1 from storiarecupero x WHERE IdContratto=v.IdContratto AND IdAzione=8 
				    	              AND DataEvento BETWEEN sr.DataEvento AND DataFineAffido + INTERVAL 2 DAY)
				    	 THEN 'Accettata'
				         WHEN EXISTS (select 1 from storiarecupero x WHERE IdContratto=v.IdContratto AND IdAzione=142 
				    	              AND DataEvento BETWEEN sr.DataEvento AND DataFineAffido + INTERVAL 2 DAY)
				    	 THEN 'Rifiutata'
				    	 ELSE 'In attesa' END AS ColorState,sr.DataEvento";

		$query = "v_insoluti_opt v $join JOIN storiarecupero sr on v.idcontratto=sr.idcontratto"
			." and sr.idAzione = 140 AND DATE(DataEvento) BETWEEN DataInizioAffido AND DataFineAffido"
			." WHERE idagenzia =".$_REQUEST['repId'] ;*/
		$ordine = "DataFineAffido";
		break;

	//----------------------------------------------------------------
	// tipi di griglia per piano di rientro
	//----------------------------------------------------------------
	case "pianorientroIC":
		$idUtente = $_REQUEST['idUtente'];
		
		$query= " pianorientro pr".
				" join v_insoluti_opt v on v.IdContratto=pr.IdContratto $join".
				" where pr.IdStatoPiano=2 and IdOperatore=0$idUtente and pr.IdPianoRientro in (".
				" Select IdPianoRientro from ratapiano group by IdPianoRientro". 
				" having (SUM(Importo)-SUM(ifnull(ImpPagato,0)))>26)";
		
		$ordine = "DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza,pr.IdPianoRientro";
		
		break;
			
	case "pianorientroSC":
		$idUtente = $_REQUEST['idUtente'];
		
		$fields = " v.*, MIN(rp.DataPrevista) as DataRataNonPagata, MIN(rp.NumRata) as NumeroRataNonPagata,NumNote,$haAllegati as NumAllegati";
		$query=  "pianorientro pr".
                 " join v_insoluti_opt v on v.IdContratto=pr.IdContratto $join".
		         " join ratapiano rp on pr.IdPianoRientro=rp.IdPianoRientro ".
                 " where pr.IdStatoPiano=2 and IdOperatore=0$idUtente and (rp.DataPrevista < CURDATE() and rp.Importo-ifnull(rp.ImpPagato,0)>26)";
			
		$ordine = "DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza,pr.IdPianoRientro";
		break;
	case "pianorientroIS":
		$idUtente = $_REQUEST['idUtente'];
		
		$NewDate=Date('Y-m-j', strtotime("+".getSysParms("GG_ALLA_SCAD_RATA_PR","7")." days"));
		$fields = " v.*, MIN(rp.DataPrevista) as DataRataDaPagare, MIN(rp.NumRata) as NumeroRataDaPagare, NumNote,$haAllegati as NumAllegati";
		$query = "pianorientro pr".
				 " join v_insoluti_opt v on v.IdContratto=pr.IdContratto $join". 
				 " join ratapiano rp on pr.IdPianoRientro=rp.IdPianoRientro".
				 " where pr.IdStatoPiano=2 and IdOperatore=0$idUtente and (rp.DataPrevista > '$NewDate' and rp.Importo-ifnull(rp.ImpPagato,0)>26)";
		
		
		$ordine = "DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza,pr.IdPianoRientro";
		break;
	case "pianorientroPO":
		$idUtente = $_REQUEST['idUtente'];
		
		$NewDate=Date('Y-m-j', strtotime("-".getSysParms("GIORNI_RATA_SCAD","30")." days"));
		$query = "pianorientro pr".
                 " join v_insoluti_opt v on v.IdContratto=pr.IdContratto $join".
                 " where pr.IdStatoPiano=2 and IdOperatore=0$idUtente AND". 
                 " pr.IdPianoRientro in (select IdPianoRientro from ratapiano group by IdPianoRientro having (SUM(Importo)-SUM(ifnull(ImpPagato,0)))=0 and MAX(DataPrevista) between '$NewDate' and CURDATE())";
		$ordine = "DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
		break;						
	//----------------------------------------------------------------
	// tipi di griglia per agente esterno
	//----------------------------------------------------------------
	case "readProLot":
		$IdAg = $context["IdReparto"];
		//trovo contratti e date varie per cui quell'agenzia ha fatto richieste di proroga entro la fine 
		//dell'affidamento
		$SqlcontrattiDate="SELECT DataInizioAffido,DataFineAffido,Contratto,DataEvento 
						from 
						(select DataInizioAffido,v.DataFineAffido,v.IdContratto as Contratto,sr.DataEvento
						from storiarecupero sr left join v_insoluti_opt v on(sr.idcontratto=v.idcontratto) 
						where sr.idazione=140 
						AND DATE(DataEvento) BETWEEN DataInizioAffido AND DataFineAffido 
						and idagenzia = $IdAg
						ORDER BY DataEvento desc) as tavola
						group by contratto
						ORDER BY DataEvento desc";
		$ContrDate=getFetchArray($SqlcontrattiDate);
		//trace("InfoArr ".print_r($ContrDate,true));
		//crea l'array degli stati di ognuno di essi per vedere se son stati espletati e come
		foreach($ContrDate as $elemento)
		{
			$sqlColor="select sr.*,sr.idcontratto,sr.DataEvento,c.DataFineAffido,
				        CASE 
				                        WHEN sr.IdAzione=8 THEN 'Accettata'
				                        WHEN sr.IdAzione=142 THEN 'Rifiutata'
				                        ELSE 
				            case
				               when date(now())>c.DataFineAffido Then 'Scaduta'
				               else 'In attesa' END
				        END AS ColorState
						from storiarecupero sr
						left join contratto c on(sr.idcontratto=c.idcontratto)
						where sr.idcontratto=".$elemento['Contratto']. 
						" and sr.DataEvento >= '".$elemento['DataEvento'].
						"' and sr.idazione in (8,142,140)
						order by sr.DataEvento desc";
			//trace("sqlcolor $sqlColor");
			$ResColor=getRow($sqlColor);
			$ArrColor[$ResColor['idcontratto']]=$ResColor['ColorState'];			
		}
		//trace("colorArr ".print_r($ArrColor,true));
		//query d'estrazione normale
		$fields .= ", v.DataFineAffido as barraFineAffido,v.IdContratto as Contratto,
				   1 AS ColorState,sr.DataEvento";
		$query = "v_insoluti_opt v $join JOIN storiarecupero sr on v.idcontratto=sr.idcontratto"
				." and sr.idAzione=140 AND DATE(DataEvento) BETWEEN v.DataInizioAffido AND v.DataFineAffido"
				." WHERE v.idagenzia =$IdAg";
		$query .= filtroInsolutiAgenzia();
		//$query .= "group by ColorState,Condizione,Contratto";
		$ordine = "DataFineAffido,$sortDScad,DataScadenzaAzione,DataScadenza";
		break;
	case "inLavorazioneAg":
		$fields .= ", v.DataFineAffido as barraFineAffido";
		$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza";
		$query = "v_insoluti_opt v $join WHERE v.IdAgenzia>0 AND v.stato!='AFR' AND classif!='POS' AND v.DataFineAffido>=CURDATE()";
		$query .= filtroInsolutiAgenzia();
		$queryForCount = "v_insoluti_count_opt v WHERE v.IdAgenzia>0 AND v.stato!='AFR' AND classif!='POS' AND v.DataFineAffido>=CURDATE()";
		$queryForCount .= filtroInsolutiAgenzia();
		break;
	case "rinegoziaAg":
		$fields .= ", v.DataFineAffido as barraFineAffido";
		$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza";
// 10/8/16		$query = "v_insoluti_opt v $join WHERE v.IdAgenzia>0 AND (v.stato='AFR' OR v.FlagRinegoziazione IS NOT NULL) AND v.DataFineAffido>=CURDATE()";
		$query = "v_insoluti_opt v $join WHERE v.IdAgenzia>0 AND v.stato='AFR' AND v.DataFineAffido>=CURDATE()";
		$query .= filtroInsolutiAgenzia();
		$queryForCount = "v_insoluti_count_opt v WHERE v.IdAgenzia>0  AND v.stato='AFR' AND v.DataFineAffido>=CURDATE()";
		$queryForCount .= filtroInsolutiAgenzia();
		break;
	case "inScadenzaAg":
		$fields .= ", v.DataFineAffido as barraFineAffido";
		$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza";
		$query = "v_insoluti_scadenza v $join WHERE scadenza>=CURDATE()";
		$query .= filtroInsolutiScadenzeAgenzia();
		$queryForCount = "v_insoluti_scadenza v WHERE scadenza>=CURDATE()";
		$queryForCount .= filtroInsolutiScadenzeAgenzia();
		break;
	case "positiveAg":
		$fields .= ", v.DataFineAffido as barraFineAffido";
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse";
		$query = "v_insoluti_positivi_opt v $join WHERE v.DataFineAffido>=CURDATE()";
		$query .= filtroInsolutiAgenzia();
		$queryForCount = "v_insoluti_positivi_opt v  WHERE v.DataFineAffido>=CURDATE()";
		$queryForCount .= filtroInsolutiAgenzia();
		break;
	case "incassiParzialiAg":
		$ordine = "$sortDStato,DataCambioStato,DataCambioClasse";
		$query = "v_insoluti_opt v $join WHERE v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0";
		$query .= filtroInsolutiAgenzia();
		$queryForCount = "v_insoluti_count_opt v WHERE v.DataFineAffido>=CURDATE() AND v.ImpPagato>0 AND v.ImpInsoluto>0";
		$queryForCount .= filtroInsolutiAgenzia();
		break;
			
	default: // TRATTA LISTE CON CRITERI PARTICOLARI 
		//---------------------------------------------------------------------------------------
		// Liste rinegoziazioni per stato (hanno codice = rinegozia_NN dove NN � l'IdStatoRinegoziazione)
		//---------------------------------------------------------------------------------------
	 	if (substr($task,0,10)=='rinegozia_')
	 	{
			$statoRin = substr($task,10); // Id stato rinegoziazione
			if ($_REQUEST['expAll']==1) { // si vogliono esportare tutti gli insoluti con stato valorizzato a prescindere dal tab selezionato
				$query = "v_insoluti_opt v $join WHERE FlagRinegoziazione is not null or v.stato='AFR'";
				$queryForCount = "v_insoluti_count_opt v WHERE FlagRinegoziazione is not null or v.stato='AFR'";
			}else{
				$query = "v_insoluti_opt v $join WHERE FlagRinegoziazione=$statoRin";
				$queryForCount = "v_insoluti_count_opt v WHERE FlagRinegoziazione=$statoRin";
				if ($statoRin==1) // nelle candidate, non fa vedere quelle affidate in rinegoziazione
				{
					$query .= " AND v.stato!='AFR'";
					$queryForCount .= " AND v.stato!='AFR'";
				}
			}
			$ordine = "CodContratto";
	 	} else {
			$ordine = "$sortDStato,DataCambioStato,DataCambioClasse,DataScadenzaAzione,DataScadenza";
			if (!Custom_List($task,$join,$query,$queryForCount,$fields,$ordine)) // qualche altro tipo lista custom?
			{
				echo("{failure:true}");
				return;
			}
			else
			{
				if ($context["InternoEsterno"]=="E")
				{
					$query .= filtroInsolutiAgenzia();
					$queryForCount .= filtroInsolutiAgenzia();
				}
				else
				{
					$query .= filtroInsolutiOperatore();
					$queryForCount .= filtroInsolutiOperatore();
				}
			}
		}
		break;
	}
	if ($query=='') { // si verifica quanto viene incluso da export
		//trace("Query nulla: task=$task",true,true);
		//echo '({"total":"0","results":[]})';
		return;
	}
	
     /* By specifying the start/limit params in ds.load 
      * the values are passed here
      * if using ScriptTagProxy the values will be in $_GET
      * if using HttpProxy      the values will be in $_POST (or $_REQUEST)
      * the following two lines check either location, but might be more
      * secure to use the appropriate one according to the Proxy being used
      */
	//trace("arr ".print_r($_POST['filter'],true));
	$filtroWhere=creaFiltro();
	//trace("filtro>> $filtroWhere");
	if ($queryForCount=="")
		$queryForCount = $query;
	
	// Gestione abbreviata per l'export
	if ($exportingToExcel) // rinuncia all'order by, per accelerare l'export
	{   // la export pu� passare i parametri per il limit
		$start = $exportFrom>"" 	? $exportFrom:0;
		$limit = $exportLimit>"" 	? $exportLimit:9999999;
		$sql = "SELECT $fields FROM $query AND ($filtroWhere) LIMIT $start,$limit";		
 		//trace("export con query $sql",FALSE);
		$arr = getFetchArray($sql);
		$data = json_encode_plus($arr);  //encode the data in json format
		echo '({"total":"' . count($arr) . '","results":' . $data . '})';
	}
	else
	{	
		//
		// SELECT COUNT(*) SOPPRESSA IN FAVORE DELL'USO DI SQL_CALC_FOUND_ROWS
		//
	    /*
		if($task == 'AgenzieProrogheTabs'){
			$counter = getScalar("SELECT count(DISTINCT IdAgenzia) FROM $queryForCount AND($filtroWhere)");
		}else{
			if($task == 'readProLot' || $task == 'readProLotMain'){
				$sql = "SELECT $fields FROM $query AND($filtroWhere) ORDER BY ";
				$queryProroghe = "SELECT tavola.* from($sql DataEvento desc) as tavola group by Contratto Order by DataFineAffido asc";
				$queryForCount = "SELECT count(Contratto) from($queryProroghe) as tabcount";
				$counter = getScalar($queryForCount);
			}
			else {
			  if($task=='pianorientroIC'||$task=='pianorientroSC'||$task=='pianorientroIS'||$task=='pianorientroPO') {
			  	$counter = getScalar("SELECT count(*) FROM $queryForCount");}
			  else  
			  	   $counter = getScalar("SELECT count(*) FROM $queryForCount AND ($filtroWhere)");	
			}
				
		}
		if ($counter == NULL)
			$counter = 0;
		if ($counter == 0) {
				$arr = array();
		} else {
		*/
			$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
			$end =   isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
	
			$sql = "SELECT SQL_CALC_FOUND_ROWS $fields FROM $query AND ($filtroWhere) ORDER BY ";
			if($task == 'readProLot' || $task == 'readProLotMain')
			{
				//$sql = "SELECT tavola.* from($sql DataEvento desc) as tavola group by Contratto Order by DataFineAffido asc";
				$sql=$queryProroghe;
				if ($_POST['sort']>' ') 
						$sql .= ','.$_POST['sort'] . ' ' . $_POST['dir'];
				
			}else{
				if ($_POST['groupBy']>' ') {
						$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
				} 
				if ($_POST['sort']>' ') 
						$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
				else
					$sql .= $ordine;
				
				if ($start!='' || $end!='') {
					if($task!='workflow')
		    			$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
				}
			}
			$arr = getFetchArray($sql);
			if($task == 'readProLot' || $task == 'readProLotMain'){
				//trace("conto ".count($arr));
				for ($i=0; $i<count($arr);$i++)
				{
					$arr[$i]['ColorState']=$ArrColor[$arr[$i]['IdContratto']];
					//trace("arr[$i] ".print_r($arr[$i],true));
					if($arr[$i]['Condizione']=='Double' && $arr[$i]['ColorState']=='In attesa')
					{
						array_splice($arr, $i, 1);
					}
				}
			}
		/*}*/
		$data = json_encode_plus($arr);  //encode the data in json format
		$total = getScalar("SELECT FOUND_ROWS()");
	
		/* If using ScriptTagProxy:  In order for the browser to process the returned
	       data, the server must wrap te data object with a call to a callback function,
	       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
	       If using HttpProxy no callback reference is to be specified */
		$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
		echo $cb . '({"total":"' . $total . '","results":' . $data . '})';
	}
}
//--------------------------------------------------------------------
// creaFiltro
// Crea una clausola per filtrare le pratiche a seconda delle esigenze
// specificate dai filtri dell'utente
//--------------------------------------------------------------------
function creaFiltro()
{
	global $context;
	
	$clausola='true';
	$qs='';
	$filter=$_POST['filter'];
	for($i=0;$i<count($_POST['filter']);$i++)
	{
		switch($filter[$i]['data']['type'])
		{
			case 'string' :
				$qs .= " AND ".$filter[$i]['field']." LIKE ".quote_smart("%".$filter[$i]['data']['value']."%"); 
				Break;
			case 'list' : 
				if (strstr($filter[$i]['data']['value'],',')){
					$fi = explode(',',$filter[$i]['data']['value']);
					for ($q=0;$q<count($fi);$q++){
						$fi[$q] = "'".$fi[$q]."'";
					}
					$filter[$i]['data']['value'] = implode(',',$fi);
					$qs .= " AND ".$filter[$i]['field']." IN (".$filter[$i]['data']['value'].")"; 
				}else{
					$qs .= " AND ".$filter[$i]['field']." = '".$filter[$i]['data']['value']."'"; 
				}
			Break;
			case 'boolean' : $qs .= " AND ".$filter[$i]['field']." = ".($filter[$i]['data']['value']); Break;
			case 'numeric' : 
				switch ($filter[$i]['data']['comparison']) {
					case 'eq' : $qs .= " AND ".$filter[$i]['field']." = ".$filter[$i]['data']['value']; Break;
					case 'lt' : $qs .= " AND ".$filter[$i]['field']." < ".$filter[$i]['data']['value']; Break;
					case 'gt' : $qs .= " AND ".$filter[$i]['field']." > ".$filter[$i]['data']['value']; Break;
				}
			Break;
			case 'date' : 
				switch ($filter[$i]['data']['comparison']) {
					case 'eq' : $qs .= " AND ".$filter[$i]['field']." = '".date('Y-m-d',strtotime($filter[$i]['data']['value']))."'"; Break;
					case 'lt' : $qs .= " AND ".$filter[$i]['field']." < '".date('Y-m-d',strtotime($filter[$i]['data']['value']))."'"; Break;
					case 'gt' : $qs .= " AND ".$filter[$i]['field']." > '".date('Y-m-d',strtotime($filter[$i]['data']['value']))."'"; Break;
				}
			Break;
		}
	}
	$clausola.=$qs;
	return $clausola;
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
	
	$clause = "IFNULL(v.IdOperatore,0)=$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto
		$clause .= " OR IFNULL(v.IdReparto,0)=$IdReparto";
	if (userCanDo("READ_NONASSEGNATE")) // autorizzato a vedere le pratiche non assegnate
		$clause .= " OR v.IdReparto IS NULL";
	return " AND ($clause)";
}
//--------------------------------------------------------------------
// filtroInsolutiOperatore
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'operatore (diretta o indiretta) sulla view v_insoluti_scadenze
//--------------------------------------------------------------------
function filtroInsolutiScadenzeOperatore()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	$clause = "IFNULL(v.IdOperatore,0)=0$IdUtente";
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto
		$clause .= " OR IFNULL(v.IdReparto,0)=0$IdReparto";
	if (userCanDo("READ_NONASSEGNATE")) // autorizzato a vedere le pratiche non assegnate
		$clause .= " OR v.IdReparto IS NULL";
		
	// Aggiunge condizione per vedere solo le scadenze dirette all'utente o al suo reparto
	return " AND ($clause) AND ".userCondition(2);
}
//--------------------------------------------------------------------
// filtroInsolutiAgenzia
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'agenzia sulla view v_insoluti
//--------------------------------------------------------------------
function filtroInsolutiAgenzia()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
	
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto (=subagenzia)
		$clause = "v.IdAgenzia=$IdReparto";
	else if (!userCanDo("READ_NONASSEGNATE")) // se non autorizzato a vedere le pratiche non assegnate, vede solo le proprie
		$clause = "IdAgente=$IdUtente";
	else                                 // altrimenti vede le proprie piu' quelle non assegnate
		$clause = "v.IdAgenzia=$IdReparto AND (IFNULL(IdAgente,0)=$IdUtente OR IdAgente IS NULL)";
	
	if (userCanDo("READ_AGENZIA")) // autorizzato a vedere tutte le pratiche della propria (super-)agenzia
	{
		$clause .= " OR v.IdAgenzia IN (SELECT IdReparto FROM reparto"
			." WHERE IdCompagnia = (SELECT IdCompagnia FROM reparto WHERE IdReparto=$IdReparto))";
	}
	/* aggiunge condizione per non vedere le pratiche affidate dopo il giorni limite di visibilit� affidi */
	/* dal 3/9/2012: crea una condizione che utilizza la data visibilit� massima STR/LEG
	 * quando opportuno
	 */ 
	$dataMassima1 = $context["sysparms"]["DATA_ULT_VIS"]; 
	if ($dataMassima1=="") $dataMassima1 = '9999-12-31';
	$dataMassima2 = $context["sysparms"]["DATA_ULT_VIS_STR"]; 
	if ($dataMassima2=="") $dataMassima2 = '9999-12-31';
	$condData = " AND (v.DataInizioAffido<='$dataMassima1' AND v.stato NOT IN ('STR1','STR2','LEG')"
	           ." OR v.DataInizioAffido<='$dataMassima2' AND v.stato IN ('STR1','STR2','LEG'))";
	
	$esclusi = array();          
	if (!userCanDo("PRATICHE_STR")) 
	{
		$esclusi[] = 'STR1'; 
		$esclusi[] = 'STR2';
	} 
	if (!userCanDo("PRATICHE_LEG")) $esclusi[] = 'LEG';
	if (!userCanDo("PRATICHE_RINE")) $esclusi[] = 'RINE';

	if (count($esclusi)>0)
	{
		$esclusi = join("','",$esclusi);
		$condStato = " AND v.stato NOT IN ('$esclusi')";
	}	
	else
		$condStato = "";
	return "$condStato $condData AND ($clause)";
}

//--------------------------------------------------------------------
// filtroInsolutiScadenzeAgenzia
// Crea una clausola per filtrare le sole pratiche di competenza
// dell'agenzia sulla view v_insoluti_scadenze
//--------------------------------------------------------------------
function filtroInsolutiScadenzeAgenzia()
{
	global $context;
	$IdUtente = $context["IdUtente"];
	$IdReparto = $context["IdReparto"];
		
	if (userCanDo("READ_REPARTO")) // autorizzato a vedere tutte le pratiche del proprio reparto (=subagenzia)
		$clause = "v.IdAgenzia=$IdReparto";
	else if (!userCanDo("READ_NONASSEGNATE")) // se non autorizzato a vedere le pratiche non assegnate, vede solo le proprie
		$clause = "IdAgente=$IdUtente";
	else                                 // altrimenti vede le proprie più quelle non assegnate
		$clause = "v.IdAgenzia=$IdReparto AND (IFNULL(IdAgente,0)=$IdUtente OR IdAgente IS NULL)";

	if (userCanDo("READ_AGENZIA")) // autorizzato a vedere tutte le pratiche della propria agenzia
	{
		$clause .= " OR v.IdAgenzia IN (SELECT IdReparto FROM reparto"
			." WHERE IdCompagnia = (SELECT IdCompagnia FROM reparto WHERE IdReparto=$IdReparto))";
	}
	
	// Aggiunge condizione per vedere solo le scadenze dirette all'utente o al suo reparto
	return " AND ($clause) AND ".userCondition(2);
}
?>
