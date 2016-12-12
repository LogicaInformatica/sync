<?php
require_once("userFunc.php");
require_once('riempimentoOptInsoluti.php');

$task = ($_POST['task']) ? ($_POST['task']) : null;

switch($task){
	case "save":
		if (isset($_POST['IdNota']) && $_POST['IdNota']!=0)
			update();
		else
			insert();
		break;
	case "delete":
		deleteNota();
		break;
	case "read":
		read();
		break;
	case "readNote":
		readNote();
		break;
	case "readNota":
		readNota();
		break;
	default:
		echo "{failure:true}";
		break;
}
// legge le note associate all'utente collegato
function readNote() {
	global $context;
	$where = "";
	if (isset($_POST['tipo']))
		$where = " WHERE TipoNota='".$_POST['tipo']."' ";
	if (isset($_POST['pratica'])) {
		if ($where == "")
			$where = " WHERE ";
		else
			$where .= " AND ";
		$where .= " IdContratto=".$_POST['pratica']." ";
	}
	// prendo iduser corrente
	$IdUser = $context["IdUtente"];
	$RepUser = $context["IdReparto"];
	if ($where == "")
		$where = userCondition();
	else
		$where .= " AND ".userCondition();
		
	$sql = "SELECT @row := @row + 1 as rowNum,$IdUser as idUserCorrente, (CASE WHEN TipoDestinatario='T' THEN 'Tutti' WHEN TipoDestinatario='U' THEN destinatario " .
			" ELSE ufficio END) AS visib, n.* from v_nota n,(SELECT @row := 0) r $where ORDER BY DataCreazione DESC";
	$arr = getFetchArray($sql);
	
	$profilo=$context["profiles"];
	$profiliChiavi = array_keys($profilo);
	$flagGood=false;
	foreach($profiliChiavi as $chiave)
	{
		if($chiave==1)
		{
			//trace("super ".$chiave);
			$flagGood=true;
		}	
	}

	if($flagGood)
	{
		//è super
		for($h=0;$h<count($arr);$h++)
		{
			if(($arr[$h]['UserSuper']!=null)&&($arr[$h]['UserSuper']!=''))
			{
				$arr[$h]['autore']=$arr[$h]['UserSuper'].'/'.$arr[$h]['autore'];
			}	
		}			
	}else{
		//è utente normale
		for($h=0;$h<count($arr);$h++)
		{
			if(($arr[$h]['UserSuper']!=null)&&($arr[$h]['UserSuper']!=''))
			{
				$arr[$h]['autore']=$arr[$h]['UserSuper'];
			}					
		}
	}
	
	// Marca tutte le note incluse come "lette"
	execute("REPLACE INTO notautente (IdNota,IdUtente,IdReparto) SELECT IdNota,$IdUser,$RepUser FROM nota $where");
	
	// aggiorna la tabella di ottimizzazione
	execute("REPLACE INTO _opt_note_lette SELECT DISTINCT IdReparto,IdNota FROM notautente WHERE LastUpd>NOW() - INTERVAL 30 SECOND");
	
	$data = json_encode_plus($arr);  //encode the data in json format

   	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	echo $cb . '({"results":' . $data . '})';
}
// legge la singola Nota
function readNota() {
	global $context;
	
	$sql = "SELECT n.* from v_nota n where IdNota=".$_POST['IdNota'];
	$arr = getFetchArray($sql);
	
	if($_POST['isChat'] && count($arr)>0){
		//tolgo la vecchia data di aggiornamento
		//$testoCommento=explode('<br><span class="Apple-tab-span" style="white-space:pre">	</span><i>postato il </i><b><i>',$arr[0]['TestoNota']);
		//$arr[0]['TestoNota']=$testoCommento[0];
		//trace("testocommento ".$arr[0]['TestoNota']);
		//aggiustamento lettura tabs
		$testoN=explode('<br><span class="Apple-tab-span" style="white-space:pre">	</span>',$arr[0]['TestoNota']);
		$arr[0]['TestoNota']=implode('<br>',$testoN);
		//$tNOTA = '<span class="Apple-tab-span" style="white-space:pre">	</span><b><i>['.$dataOra.' da '.$mittente.''.$destinatario.'] </i></b>'.$arr[0]['TestoNota'];
		//$arr[0]['TestoNota']=$tNOTA;
	}
	$data = json_encode_plus($arr);  //encode the data in json format
	// Marca tutte le note incluse come "lette"
	//execute("REPLACE INTO notautente (IdNota,IdUtente,IdReparto) SELECT IdNota,$IdUser,$RepUser FROM nota where IdNota=".$_POST['IdNota']);
   	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified */
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
	echo $cb . '({"results":' . $data . '})';
	
	// Marca la nota come "letta"
	$IdUser = $context["IdUtente"];
	$RepUser = $context["IdReparto"];
	execute("REPLACE INTO notautente (IdNota,IdUtente,IdReparto) SELECT IdNota,0$IdUser,0$RepUser FROM nota WHERE IdNota=".$_POST['IdNota']);

	// aggiorna la tabella di ottimizzazione
	execute("REPLACE INTO _opt_note_lette SELECT DISTINCT IdReparto,IdNota FROM notautente WHERE LastUpd>NOW() - INTERVAL 30 SECOND");
	
}
//-----------------------------------------------------------------------
// read
// Lettura valori per ...
//-----------------------------------------------------------------------
function read() {
/*
	$id = $_POST['id'];

	$sql = 'SELECT C.IdCompagnia, C.TitoloCompagnia, C.NomeTitolare, C.Indirizzo, C.CAP, C.Localita, C.SiglaProvincia, ' .
		'C.Telefono as TelefonoTitolare, C.Fax as FaxTitolare, C.EmailTitolare, R.IdReparto, ' .
		'R.CodUfficio, R.TitoloUfficio, R.NomeReferente, R.Telefono, R.Fax, R.EmailReferente, R.EmailFatturazione ' .
		'FROM Compagnia C JOIN Reparto R WHERE C.IdCompagnia = R.IdCompagnia AND R.IdReparto=' . $id;
/*
	`DataIni` date NOT NULL,
	`DataFin` date NOT NULL,
	`LastUpd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	`LastUser` varchar(20) default NULL,
* /	
	$result = mysql_query($sql);

	while($rec = mysql_fetch_array($result, MYSQL_ASSOC)){
		$arr[] = $rec;
	}

	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode($arr);  //encode the data in json format
	}

   	/* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified * /
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
	echo $cb . '({"results":' . $data . '})';
*/
}

//-----------------------------------------------------------------------
// insert
// Inserimento nuova Nota
//-----------------------------------------------------------------------
function insert() {
	global $context;

	$destinatario='';
	$mittente=$context['NomeUtente'];
	$dataOra = date('d/m/Y H.i');
	$valList = "";
	$colList = "";
	addInsClause($colList,$valList,"IdContratto",($_POST['IdContratto']=='0')?"null":$_POST['IdContratto'],"N");
	addInsClause($colList,$valList,"TipoNota",$_POST['TipoNota'],"S");
	$codMex='ADD_NOTA';
	$mex='Inserimento nota per ';
	switch ($_POST['TipoDestinatario']) {
		case "T":	// Tutti
			addInsClause($colList,$valList,"IdUtenteDest","","N");
			addInsClause($colList,$valList,"IdReparto","","N");
			$destinatario='Tutti';
			$mex.=$destinatario;
			break;
		case "A":	// Agenzia
			addInsClause($colList,$valList,"IdUtenteDest","","N");
			addInsClause($colList,$valList,"IdReparto",$_POST['id_agenzia'],"N");
			break;
			$mex.="agenzia n.".$_POST['id_agenzia'];
		case "R":	// Reparto
			addInsClause($colList,$valList,"IdUtenteDest","","N");
			addInsClause($colList,$valList,"IdReparto",$_POST['id_reparto'],"N");
			$mex.="reparto n.".$_POST['id_reparto'];
			break;
		case "U":	// Utente
			addInsClause($colList,$valList,"IdUtenteDest",$_POST['IdUtenteDest'],"N");
			addInsClause($colList,$valList,"IdReparto","","N");
			$sqlDest = 'select NomeUtente from utente where idutente='.$_POST['IdUtenteDest'];
			$dest=getRow($sqlDest);
			$destinatario=$dest['NomeUtente'];
			$mex.=$destinatario;
			break;
	}
	//trace("dest ".$_POST['tDest']);
	if($_POST['tDest']!= ''){
		switch ($_POST['tDest']) {
			case "T":	// Tutti
				addInsClause($colList,$valList,"IdUtenteDest","","N");
				addInsClause($colList,$valList,"IdReparto","","N");
				$destinatario='Tutti';
				$mex.=$destinatario;
				break;
			case "U":	// Utente
				addInsClause($colList,$valList,"IdUtenteDest",$_POST['IdUtenteDest'],"N");
				addInsClause($colList,$valList,"IdReparto","","N");
				$sqlDest = 'select NomeUtente from utente where idutente='.$_POST['IdUtenteDest'];
				$dest=getRow($sqlDest);
				$destinatario=$dest['NomeUtente'];
				$mex.=$destinatario;
				break;
		}
	}
			
	$ris = "N";
	if (isset($_POST['FlagRiservato']) && $_POST['FlagRiservato']=="on") {
		$ris = "Y";
		$mex.=" (Riservata)";
	}
	//replace per copy/paste da word
	$_POST['TestoNota']=str_replace ( chr(10) , '<br>', $_POST['TestoNota'] );
	if($_POST['isChat']){
		$testoN=explode('<br>',$_POST['TestoNota']);
		$testoNota=implode('<br><span class="Apple-tab-span" style="white-space:pre">	</span>',$testoN);
	}else{
		$testoNota=$_POST['TestoNota'];
	}
	//$testoNota.= '<br><span class="Apple-tab-span" style="white-space:pre">	</span><i>postato il </i><b><i>'.$dataOra.' da '.$mittente.' a '.$destinatario.'</i></b>';
	addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
	addInsClause($colList,$valList,"IdUtente",$context['IdUtente'],"N");
	addInsClause($colList,$valList,"TestoNota",$testoNota,"S");
	addInsClause($colList,$valList,"FlagRiservato",$ris,"S");
	addInsClause($colList,$valList,"DataCreazione","NOW()","G");
	if ($_POST['TipoNota']!=='A')
	{
		$dataScad = $_POST['DataScadenza'];
		if ($_POST['OraScadenza']>' ')
			$dataScad .= ' '.$_POST['OraScadenza'].":00"; 
		addInsClause($colList,$valList,"DataScadenza",$dataScad,"SD");
	}
	else
		addInsClause($colList,$valList,"DataScadenza",'',"SD");
	if (($_POST['TipoNota']=='C') || ($_POST['TipoNota']=='A'))
	{
		if(isset($_POST['DataIni'])){
			addInsClause($colList,$valList,"DataIni",$_POST['DataIni'],"D");
		}else{
			addInsClause($colList,$valList,"DataIni","1970-01-01","S");
		}
		
		if ($_POST['DataFin']=='')
			addInsClause($colList,$valList,"DataFin","9999-12-31","S");
		else
			addInsClause($colList,$valList,"DataFin",$_POST['DataFin'],"D");
	}else{
		addInsClause($colList,$valList,"DataIni","1970-01-01","S");
		addInsClause($colList,$valList,"DataFin","9999-12-31","S");
		}	
	
	$master=$context["master"];
	//trace("master ".$master);
	if($master!=''){
		$sqlIdMaster="SELECT IdUtente FROM utente where userid='$master'";
		$IdM = getScalar($sqlIdMaster);
		addInsClause($colList,$valList,"IdSuper",$IdM ,"N");
	}
		
	if(isset($_POST['IdNotaPrecedente'])){
		if($_POST['IdNotaPrecedente']!='')
			$var=$_POST['IdNotaPrecedente'];
		else
			$var=$_POST['idPadre'];
			
		addInsClause($colList,$valList,"IdNotaPrecedente",$var,"N");
	}

	$sql =  "INSERT INTO nota ($colList)  VALUES($valList)";
	
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	if (execute($sql)) {
		traceLogNote($_POST['IdContratto'],$_POST['TipoNota'],"Inserimento",$_POST['TestoNota'],$dataScad);
		writeLog("APP","Gestione note",$mex,$codMex);
		echo "{success:true}";
		
		if ($dataScad>'')
			updateOptInsoluti("IdContratto=".$_POST['IdContratto']); // aggiorna record ottimizzazione
	} else {
		writeLog("APP","Gestione note","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// update
// Salvataggio record Nota
//-----------------------------------------------------------------------
function update() {
	global $context;
	$setClause = "";
	$destinatario='';
	$mittente=$context['NomeUtente'];
	$dataOra = date('d/m/Y H.i');
	addSetClause($setClause,"IdContratto",$_POST['IdContratto'],"N");
	addSetClause($setClause,"TipoNota",$_POST['TipoNota'],"S");
	$codMex='MOD_NOTA';
	$mex="Modifica nota n.".$_POST['IdNota']." per ";
	switch ($_POST['TipoDestinatario']) {
		case "T":	// Tutti
			addSetClause($setClause,"IdUtenteDest","","N");
			addSetClause($setClause,"IdReparto","","N");
			$destinatario='Tutti';
			$mex.=$destinatario;
			break;
		case "A":	// Agenzia
			addSetClause($setClause,"IdUtenteDest","","N");
			addSetClause($setClause,"IdReparto",$_POST['id_agenzia'],"N");
			$mex.="agenzia n.".$_POST['id_reparto'];
			break;
		case "R":	// Reparto
			addSetClause($setClause,"IdUtenteDest","","N");
			addSetClause($setClause,"IdReparto",$_POST['id_reparto'],"N");
			$mex.="reparto n.".$_POST['id_reparto'];
			break;
		case "U":	// Utente
			addSetClause($setClause,"IdUtenteDest",$_POST['IdUtenteDest'],"N");
			addSetClause($setClause,"IdReparto","","N");
			$sqlDest = 'select NomeUtente from utente where idutente='.$_POST['IdUtenteDest'];
			$dest=getRow($sqlDest);
			$destinatario=$dest['NomeUtente'];
			$mex.=$destinatario;
			break;
	}
				
//??	addSetClause($setClause,"IdUtente",$context['idUtente'],"N");
	addSetClause($setClause,"LastUser",$context['Userid'],"S");
	if($_POST['isChat']){
		$mex.=" (via chat";
		$testoN=explode('<br>',$_POST['TestoNota']);
		$testoNota=implode('<br><span class="Apple-tab-span" style="white-space:pre">	</span>',$testoN);
	}else{
		$testoNota=$_POST['TestoNota'];
	}
	//$testoNota.= '<br><span class="Apple-tab-span" style="white-space:pre">	</span><i>postato il </i><b><i>'.$dataOra.' da '.$mittente.' a '.$destinatario.'</i></b>';
	addSetClause($setClause,"TestoNota",$testoNota,"S");
	$ris = "N";
	if (isset($_POST['FlagRiservato']) && $_POST['FlagRiservato']=="on") {
		$mex.=",Riservata";
		$ris = "Y";
	}
	$mex.=")";
	addSetClause($setClause,"FlagRiservato",$ris,"S");
	
	if ($_POST['TipoNota']!=='A')
	{
		$dataScad = ISODate($_POST['DataScadenza']);
		if ($_POST['OraScadenza']>' ')
			$dataScad  .= " ".$_POST['OraScadenza'].":00"; 
		addSetClause($setClause,"DataScadenza","'$dataScad'","G");
	}
	else {
		$mex.="[AVVISO]";
		addSetClause($setClause,"DataScadenza",'',"SD");
	}
	
	if (($_POST['TipoNota']=='C') || ($_POST['TipoNota']=='A'))
	{
		if(isset($_POST['DataIni'])){
			addSetClause($setClause,"DataIni",$_POST['DataIni'],"D");
		}else{
			addSetClause($setClause,"DataIni","1970-01-01","S");
		}
		if ($_POST['DataFin']=='')
			addSetClause($setClause,"DataFin","9999-12-31","S");
		else
			addSetClause($setClause,"DataFin",$_POST['DataFin'],"D");
		
	}else{
		addSetClause($setClause,"DataIni","1970-01-01","S");
		addSetClause($setClause,"DataFin","9999-12-31","S");
	}	
	
	if(isset($_POST['IdNotaPrecedente'])){
		addSetClause($setClause,"IdNotaPrecedente",$_POST['IdNotaPrecedente'],"N");
	}
	
	$master=$context["master"];
	//trace("master ".$master);
	if($master!=''){
		$sqlIdMaster="SELECT IdUtente FROM utente where userid='$master'";
		$IdM = getScalar($sqlIdMaster);
		addSetClause($setClause,"IdSuper",$IdM ,"N");
	}
	
	$sql =  "UPDATE nota $setClause WHERE IdNota=".$_POST['IdNota'];
	
	// Controllo successo dell'operazione (non usare il numero di righe modificate che potrebbe essere 0
	// nel caso in cui non ci fosse nessuna modifica di valore) 
	if (execute($sql)) {
		traceLogNote($_POST['IdContratto'],$_POST['TipoNota'],"Modifica",$_POST['TestoNota'],$dataScad);
		echo "{success:true}";

		if ($dataScad>'' && $_POST['IdContratto']>0)
			updateOptInsoluti("IdContratto=".$_POST['IdContratto']); // aggiorna record ottimizzazione
	} else {
		writeLog("APP","Gestione note","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";
	}
}

//-----------------------------------------------------------------------
// deleteNota
// Elimina una determinata nota, la chiave viene ricevuta in post
//-----------------------------------------------------------------------
function deleteNota()
{

	$codMex='CANC_NOTE';
	$sql =  "delete from notautente where idnota =".$_POST['idNotaDel'];
	if (execute($sql)) 
	{
		$sql =  "delete from nota where idnota =".$_POST['idNotaDel'];
		// serve per trace su log
		$arr=getRow("SELECT IdContratto, TipoNota, TestoNota from nota where IdNota =".$_POST['idNotaDel']);
		
		if (execute($sql)) {
			traceLogNote($arr[IdContratto],$arr[TipoNota],"Cancellazione",$arr[TestoNota],"");
			echo "{success:true}";
		} else{ 
			writeLog("APP","Cancellazione note","\"".getLastError()."\"",$codMex);
			echo "{success:false, error:\"".getLastError()."\"}";}
		
	} 
	else{ 
		writeLog("APP","Cancellazione note","\"".getLastError()."\"",$codMex);
		echo "{success:false, error:\"".getLastError()."\"}";}
	
}

// trace sel log 
function traceLogNote($idContratto,$tipoNota,$task,$testo,$dtScad)
{

	switch($task)
	{
		case 'Inserimento':
			$codEventoOp="INS";
		break;
		case 'Cancellazione':
			$codEventoOp="CANC";
		break;
		case 'Modifica':
			$codEventoOp="MOD";
		break;
		default:
		break;		
	}
	
	switch($tipoNota)
	{
		case 'A':
			$eventi = " avvisi";
			$evento = " avviso";
			$codEventoTask="AVV";
		break; 
		case 'S':
			$eventi = " scadenze";
			$evento = " scadenza";
			$codEventoTask="SCA";
		break;
		case 'C':
			$eventi .= " comunicazioni";
			$evento = " comunicazione";
			$codEventoTask="COM";
		break;
		case 'N':
			$evento = " note";
			$evento = " nota";
			$codEventoTask="NOT";
		break;
		default:
		break;	
	}	

	$codEvento = $codEventoOp."_".$codEventoTask;
	
	$descrizione .= "$task $evento"; 
	
	if($idContratto!="")
	{	
	  	$codContratto = getscalar("SELECT CodContratto From contratto where IdContratto=".$idContratto);
	}
	
	
	if($codContratto!="")
	{
		$descrizione .=" per il contratto: $codContratto";
	}
	
	if($dtScad!="")
	{
	 $descrizione .=" con scad. $dtScad";	
	}
	$descrizione.= " - $testo";
	
	writeLog("APP","Gestione $eventi",$descrizione,$codEvento);
}
?>
