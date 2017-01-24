<?php
require_once("workflowFunc.php");
require_once("userFunc.php");
doMain();

function doMain()
{
	global $context;
	
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch ($task)
	{
		case "delete":deletePartite();//partite_decodifica
			break;
		case "readMainGrid":readGrid();//Tipi_decodifica
			break;
		case "saveAgg":aggiornaTipo();//Tipi_decodifica
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
///////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia delle tipologie di partite
///////////////////////////////////////////////////////////////////////
function readGrid()
{
	global $context;
	$fields = "*";
	$fieldTipo = $_REQUEST['tipoDec'];
	$secondary=false;
	switch($fieldTipo)
	{
		case 'partita':
			$query = "v_tipoPartita_decodifica";
			$ordine="CodTipoPartita asc";
			$campoTitolo="TitoloTipoPartita";
			break;
		case 'speciale':
			$query = "v_tipospeciale_decodifica";
			$ordine="CodTipoSpeciale asc";
			$campoTitolo="TitoloTipoSpeciale";
			break;
		case 'insoluto':
			$query = "tipoinsoluto";
			$ordine="CodTipoInsoluto asc";
			$campoTitolo="TitoloTipoInsoluto";
			break;
		case 'pagamento':
			$query = "tipopagamento";
			$ordine="CodTipoPagamento asc";
			$campoTitolo="TitoloTipoPagamento";
			break;
		case 'compagnia':
			$query = "tipocompagnia";
			$ordine="CodTipoCompagnia asc";
			$campoTitolo="TitoloTipoCompagnia";
			break;
		case 'famigliaProdotto':
			$query = "v_famigliaprodotto_decodifica";
			$ordine="gruppo,TitoloFamiglia";
			$campoTitolo="TitoloFamiglia";
			$campoTitoloComp="TitoloCompagnia";
			$secondary=true;
			break;
		case 'prodotto':
			$query = "v_prodotto_decodifica";
			$ordine="TitoloProdotto";
			$campoTitolo="TitoloProdotto";
			$campoTitoloComp="TitoloFamiglia";
			$secondary=true;
			break;
		case 'attributo':
			$query = "attributo";
			$ordine="TitoloAttributo asc";
			$campoTitolo="TitoloAttributo";
			break;
		case 'nazioneDec':
			$query = "nazione";
			$ordine="TitoloNazione asc";
			$campoTitolo="TitoloNazione";
			break;
		case 'regioneDec':
			$query = "regione";
			$ordine="TitoloRegione asc";
			$campoTitolo="TitoloRegione";
			break;
		case 'provinceDec':
			$query = "v_provincia_decodifica";
			$ordine="TitoloProvincia asc";
			$campoTitolo="TitoloProvincia";
			break;
		case 'userState':
			$query = "statoutente";
			$ordine="CodStatoUtente asc";
			$campoTitolo="TitoloStatoUtente";
			break;
		case 'statocontratto':
			$query = "statocontratto";
			$ordine="CodStatoContratto asc";
			$campoTitolo="TitoloStatoContratto";
			break;
		case 'movimentod':
			$query = "v_tipomovimento_decodifica";
			$ordine="CodTipoMovimento asc";
			$campoTitolo="TitoloTipoMovimento";
			break;
	}
	$counter = getScalar("SELECT count(*) FROM $query");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
			$arr = array();
	} else {
	 
		$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
		$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
		
		$sql = "SELECT $fields FROM $query ORDER BY ";

		if ($_POST['groupBy']>' ') {
					$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
			} 
			if ($_POST['sort']>' '){ 
					$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
			}else{
				$sql .= $ordine;
			}
				
		if ($start!='' || $end!='') {
	    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
		}
		//tipo di profilo
		$arr=getFetchArray($sql); 
		//correzione caratteri html
		for($i=0; $i<count($arr); $i++){
			$arr[$i][$campoTitolo] = htmlstr($arr[$i][$campoTitolo]);
			if($secondary){
				$arr[$i][$campoTitoloComp] = htmlstr($arr[$i][$campoTitoloComp]);
			}
		}
		  
	}
	if (version_compare(PHP_VERSION,"5.2","<")) {    
		require_once("./JSON.php"); //if php<5.2 need JSON class
		$json = new Services_JSON();//instantiate new json object
		$data=$json->encode($arr);  //encode the data in json format
	} else {
		$data = json_encode_plus($arr);  //encode the data in json format
	}
	
	$cb = isset($_GET['callback']) ? $_GET['callback'] : '';
	       
	echo $cb . '({"total":"' . $counter . '","results":' . $data . '})';
}
/////////////////////////////////////////////////////////////
//Funzione di salvataggio della Partita
/////////////////////////////////////////////////////////////
function aggiornaTipo()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$secondary=false;
	$stepOut=false;
	$toAbbr=false;
	$categoria=false;
	$fieldTipo = $_REQUEST['tipoDec'];
	switch($fieldTipo)
	{
		case 'famigliaProdotto':
			isset($_POST['IdFam'])?$_POST['IdFam']:0;
			isset($_POST['TitoloFam'])?$_POST['TitoloFam']:'';
			isset($_POST['CodFam'])?$_POST['CodFam']:'';
			isset($_POST['Compagnia'])?$_POST['Compagnia']:null;
			isset($_POST['macroFam'])?$_POST['macroFam']:null;
			if($_POST['macroFam']=='')
				$_POST['macroFam']=null;
			if($_POST['Compagnia']=='')
				$_POST['Compagnia']=null;
			$titolo=$_POST['TitoloFam'];
			$indexTab=$_POST['IdFam'];
			$codTab=$_POST['CodFam'];
			$secondary=true;
			break;
		case 'prodotto':
			isset($_POST['IdProd'])?$_POST['IdProd']:0;
			isset($_POST['TitoloProd'])?$_POST['TitoloProd']:'';
			isset($_POST['CodProd'])?$_POST['CodProd']:'';
			isset($_POST['CodMark'])?$_POST['CodMark']:'';
			isset($_POST['macroFam'])?$_POST['macroFam']:null;
			//riverso nelle etichette generiche i campi spediti
			$_POST['IdFam'] = $_POST['IdProd'];
			$_POST['TitoloFam'] = $_POST['TitoloProd'];
			$_POST['CodFam'] = $_POST['CodProd'];
			$_POST['Compagnia'] = $_POST['CodMark'];
			//end riversamento
			if($_POST['macroFam']=='')
				$_POST['macroFam']=null;
			if($_POST['Compagnia']=='')
				$_POST['Compagnia']=null;
			$titolo=$_POST['TitoloProd'];
			$indexTab=$_POST['IdFam'];
			$codTab=$_POST['CodFam'];
			$secondary=true;
			break;
		case 'nazioneDec':
			$stepOut=true;
			echo aggiornaGeo();
			break;
		case 'regioneDec':
			$stepOut=true;
			echo aggiornaGeo();
			break;
		case 'provinceDec':
			$stepOut=true;
			echo aggiornaGeo();
			break;
		default:
			isset($_POST['IdTipo'])?$_POST['IdTipo']:0;
			isset($_POST['TitoloTipo'])?$_POST['TitoloTipo']:'';
			isset($_POST['CodTipo'])?$_POST['CodTipo']:'';
			isset($_POST['CodTipoLegacy'])?$_POST['CodTipoLegacy']:'';
			isset($_POST['AbbrTipo'])?$_POST['AbbrTipo']:'';
			isset($_POST['categoria'])?$_POST['categoria']:'';
			isset($_POST['forzatura'])?$_POST['forzatura']:null;
			if($_POST['IdTipo']=='')
				$_POST['IdTipo']=0;
			if($_POST['forzatura']=='')
				$_POST['forzatura']=null;
			$titolo=$_POST['TitoloTipo'];
			$_POST['CodTipo'] = strtoupper($_POST['CodTipo']);
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['IdTipo'];
			$codTab=$_POST['CodTipo'];
			$abbrTab=$_POST['AbbrTipo'];
			break;
	}
	
	//trace("tipo $fieldTipo");
	if(!$stepOut)
	{
		switch($fieldTipo)
		{
			case 'partita':
				//variabili
				$tab = 'tipopartita';
				$idField = 'IdTipoPartita';
				$codField = 'CodTipoPartita';
				$titleName = 'partita';
				$codMexName = 'PARTITE';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloTipoPartita';
				$codFieldLeg = 'CodTipoPartitaLegacy';
				$catField='CategoriaPartita';
				$categoria=true;
				break;
			case 'speciale':
				//variabili
				$tab = 'tipospeciale';
				$idField = 'IdTipoSpeciale';
				$codField = 'CodTipoSpeciale';
				$titleName = 'speciale';
				$codMexName = 'SPECIALI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloTipoSpeciale';
				$codFieldLeg = 'CodTipoSpecialeLegacy';
				break;
			case 'insoluto':
				//variabili
				$tab = 'tipoinsoluto';
				$idField = 'IdTipoInsoluto';
				$codField = 'CodTipoInsoluto';
				$titleName = 'insoluto';
				$codMexName = 'INSOLUTI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloTipoInsoluto';
				$codFieldLeg = 'CodTipoInsolutoLegacy';
				break;
			case 'pagamento':
				//variabili
				$tab = 'tipopagamento';
				$idField = 'IdTipoPagamento';
				$codField = 'CodTipoPagamento';
				$titleName = 'pagamento';
				$codMexName = 'PAGAMENTI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloTipoPagamento';
				$codFieldLeg = 'CodTipoPagamentoLegacy';
				break;
			case 'compagnia':
				//variabili
				$tab = 'tipocompagnia';
				$idField = 'IdTipoCompagnia';
				$codField = 'CodTipoCompagnia';
				$titleName = 'compagnia';
				$codMexName = 'COMPAGNIE';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';
				//campi tab
				$titField = 'TitoloTipoCompagnia';
				break;
			case 'famigliaProdotto':
				//variabili
				$tab = 'famigliaprodotto';
				$idField = 'IdFamiglia';
				$codField = 'CodFamiglia';
				$titleName = 'famiglia';
				$codMexName = 'FAMIGLIE_PRODOTTO';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloFamiglia';
				$parentFamily = 'IdFamigliaParent';
				$company = 'IdCompagnia';
				break;
			case 'prodotto':
				//variabili
				$tab = 'prodotto';
				$idField = 'IdProdotto';
				$codField = 'CodProdotto';
				$titleName = 'prodotto';
				$codMexName = 'PRODOTTI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloProdotto';
				$parentFamily = 'IdFamiglia';
				$company = 'CodMarca';
				break;
			case 'attributo':
				//variabili
				$tab = 'attributo';
				$idField = 'IdAttributo';
				$codField = 'CodAttributo';
				$titleName = 'attributo';
				$codMexName = 'ATTRIBUTI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloAttributo';
				$codFieldLeg = 'CodAttributoLegacy';
				break;
			case 'userState':
				//variabili
				$tab = 'statoutente';
				$idField = 'IdStatoUtente';
				$codField = 'CodStatoUtente';
				$titleName = 'stato utenza';
				$codMexName = 'STATI_UTENZA';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloStatoUtente';
				break;
			case 'statocontratto':
				//variabili
				$tab = 'statocontratto';
				$idField = 'IdStatoContratto';
				$codField = 'CodStatoContratto';
				$titleName = 'stato del contratto';
				$codMexName = 'STATO_CONTRATTO';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';
				//campi tab
				$titField = 'TitoloStatoContratto';
				$codFieldLeg = 'CodStatoLegacy';
				$abbrField = 'AbbrStatoContratto';
				$toAbbr=true;
				break;
			case 'movimentod':
				//variabili
				$tab = 'tipomovimento';
				$idField = 'IdTipoMovimento';
				$codField = 'CodTipoMovimento';
				$titleName = 'movimento';
				$codMexName = 'MOVIMENTI';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoMovimento';
				$codFieldLeg = 'CodTipoMovimentoLegacy';
				$catField='CategoriaMovimento';
				$categoria=true;
				break;
		}
		//trace("TA ".$_POST['TitoloTipoPartita']);
		//*****inserimento
		$counter = getScalar("Select count(*) FROM $tab where $idField = $indexTab");
		if($counter==0 && $indexTab==0)
		{
			//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
			$arrCod = getFetchArray("Select $idField FROM $tab where $codField='$codTab'");
			//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod)." post $indexTab");
			if(count($arrCod)==0 || (count($arrCod)>0 && $arrCod[0][$idField]==$indexTab))
			{
				$codMex="ADD_".$codMexName;
				$mex="Aggiunta tipo decodifica";
				if($secondary){
					if($fieldTipo=='famigliaProdotto'){
						$mex="Inserimento nuova $titleName: ".$_POST['TitoloFam'];
					}else{
						$mex="Inserimento nuovo $titleName: ".$_POST['TitoloFam'];
					}
					addInsClause($colList,$valList,$titField,$_POST['TitoloFam'],"S");
					addInsClause($colList,$valList,$codField,$_POST['CodFam'],"S");
					addInsClause($colList,$valList,$parentFamily,$_POST['macroFam'],"S");
					addInsClause($colList,$valList,$company,$_POST['Compagnia'],"S");
				}else{
					$mex="Inserimento nuovo tipo $titleName: ".$_POST['TitoloTipo'];
					addInsClause($colList,$valList,$titField,$_POST['TitoloTipo'],"S");
					addInsClause($colList,$valList,$codField,$_POST['CodTipo'],"S");
					if($categoria)
						addInsClause($colList,$valList,$catField,$_POST['categoria'],"S");
					if($fieldTipo=='speciale')
						addInsClause($colList,$valList,"FlagForzatura",$_POST['forzatura'],"S");
					if($toAbbr)
						addInsClause($colList,$valList,$abbrField,$abbrTab,"S");
				}
				addInsClause($colList,$valList,"DataIni",'2001-01-01',"S");
				addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				
				$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
				//trace("ins par: $sqlInsTipo");
				if (execute($sqlInsTipo))
				//if(true)
				{
					$mexFinale="$neww[0] $titleName '$titolo', $neww[1].";
					writeLog("APP","Gestione $titleName ",$mex,$codMex);
					echo "{success:true, messaggio:\"$mexFinale\"}";
				}else{
					writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
					echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{
				writeLog("APP","Gestione $titleName ","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
				echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
		}else{
				$codMex="MOD_".$codMexName;
				
				//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
				$arrCod = getFetchArray("Select $idField FROM $tab where $codField='$codTab'");
				//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod). " post $codTab");
				if(count($arrCod)==0 || (count($arrCod)>0 && $arrCod[0][$idField]==$indexTab))
				{
					if($secondary){
						$mex="Modifica $titleName: ".$_POST['TitoloFam'];
						addSetClause($setClause,$titField,$_POST['TitoloFam'],"S");
						addSetClause($setClause,$codField,$_POST['CodFam'],"S");
						addSetClause($setClause,$parentFamily,$_POST['macroFam'],"S");
						addSetClause($setClause,$company,$_POST['Compagnia'],"S");
					}else{
						$mex="Modifica $titleName: ".$_POST['TitoloTipo'];
						addSetClause($setClause,$titField,$_POST['TitoloTipo'],"S");
						addSetClause($setClause,$codField,$_POST['CodTipo'],"S");
						if($fieldTipo!='compagnia' && $fieldTipo!='userState')
							addSetClause($setClause,$codFieldLeg,$_POST['CodTipoLegacy'],"S");
						if($categoria)
							addSetClause($setClause,$catField,$_POST['categoria'],"S");
						if($fieldTipo=='speciale')
							addSetClause($setClause,"FlagForzatura",$_POST['forzatura'],"S");
						if($toAbbr)
							addSetClause($setClause,$abbrField,$abbrTab,"S");
					}
					addSetClause($setClause,"LastUser",$context['Userid'],"S");
					$sqlModTipo = "UPDATE $tab $setClause WHERE $idField=$indexTab";
					//trace("Mod part: $sqlModTipo");
					if (execute($sqlModTipo))
					//if(true)
					{
						// All'aggiornamento di una tipologica, devo ricalcolare _opt_insoluti
						updateOptInsoluti(true); // aggiorna l'intera tabella
						
						$mexFinale="Registrazione correttamente eseguita";
						writeLog("APP","Gestione $titleName ",$mex,$codMex);
						echo "{success:true, messaggio:\"$mexFinale\"}";
					}else{
						writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
						echo "{success:false, messaggio:\"".getLastError()."\"}";}
				}else{
					writeLog("APP","Gestione $titleName ","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
					echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
		}
	}
}
///////////////////////////////////////////////////////////////////
//Funzione di aggiornamento decodifica geografica
///////////////////////////////////////////////////////////////////
function aggiornaGeo()
{
	global $context;
	$valList = "";
	$colList = "";
	$setClause = "";
	$isAStringKey=true;
	isset($_POST['nomeGeo'])?$_POST['nomeGeo']:'';
	isset($_POST['siglaGeo'])?$_POST['siglaGeo']:'';
	isset($_POST['flagEditGeo'])?$_POST['flagEditGeo']:null;
	isset($_POST['regioneProv'])?$_POST['regioneProv']:0;
			
	$_POST['siglaGeo'] = strtoupper($_POST['siglaGeo']);
	$indexTab=$_POST['siglaGeo'];
	$textTab=$_POST['nomeGeo'];
	$oldOne=$_POST['flagEditGeo'];
	$ofRegion=$_POST['regioneProv'];
	
	$fieldTipo = $_REQUEST['tipoDec'];
	switch($fieldTipo)
	{
		case 'nazioneDec':
			//variabili
			$tab = 'nazione';
			$idField = 'SiglaNazione';
			$codField = 'TitoloNazione';
			$titleName = 'nazione';
			$codMexName = 'NAZIONI';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloNazione';
			break;
		case 'regioneDec':
			//variabili
			if($indexTab=='')
				$indexTab=0;
			$tab = 'regione';
			$idField = 'IdRegione';
			$codField = 'TitoloRegione';
			$titleName = 'regione';
			$codMexName = 'REGIONI';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloRegione';
			$isAStringKey=false;
			break;
		case 'provinceDec':
			//variabili
			if($ofRegion=='')
				$ofRegion=0;
			$tab = 'provincia';
			$idField = 'SiglaProvincia';
			$codField = 'TitoloProvincia';
			$titleName = 'provincia';
			$codMexName = 'PROVINCE';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloProvincia';
			break;
	}
	
//	$counter = getScalar("Select count(*) FROM $tab where $codField = $textTab");

	if($oldOne==null)
	{
		//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
		if(!$isAStringKey)
			$arrCod = getFetchArray("Select $idField FROM $tab where $codField='$textTab'");
		else
			$arrCod = getFetchArray("Select $idField FROM $tab where $idField='$indexTab'");
		//trace(">>>> Select $idField FROM $tab where $codField='$textTab'");
		//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod)." post $indexTab");
		if(count($arrCod)==0)
		{
			$codMex="ADD_".$codMexName;
			$mex="Inserimento nuova $titleName: $textTab";

			addInsClause($colList,$valList,$titField,$textTab,"S");
			if($isAStringKey){
				addInsClause($colList,$valList,$idField,$indexTab,"S");
				if($fieldTipo=='provinceDec')
					addInsClause($colList,$valList,'IdRegione',$ofRegion,"N");
				addInsClause($colList,$valList,"DataIni",'2001-01-01',"S");
				addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
			}else
				addInsClause($colList,$valList,$idField,$indexTab,"N");
			
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			
			$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
			//trace("ins par: $sqlInsTipo");
			if (execute($sqlInsTipo))
			//if(true)
			{
				$mexFinale="$neww[0] $titleName, $neww[1].";
				writeLog("APP","Gestione $titleName ",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{
				writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
				echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{
			writeLog("APP","Gestione $titleName ","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
			echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
	}else{
			$codMex="MOD_".$codMexName;
			
			//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
			if($isAStringKey)
			{//se è una stringa c'è bisogno di una certa gestione
				$arrCod = getFetchArray("Select * FROM $tab where $idField='$oldOne'");
				//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod). " post $idField");
				if(count($arrCod)>0)
				{
					$mex="Modifica $titleName: $textTab";
					//se sono diversi old e new sigla allora devi inserire il nuovo e cancellare il vecchio
					//se sono uguali si modifica solo il campo nome.
					if($oldOne == $indexTab){
						addSetClause($setClause,$titField,$textTab,"S");
						if($fieldTipo=='provinceDec')
							addSetClause($setClause,'IdRegione',$ofRegion,"N");
						addSetClause($setClause,"LastUser",$context['Userid'],"S");
						$sqlModTipo = "UPDATE $tab $setClause WHERE $idField='$oldOne'";
						//trace("Mod part: $sqlModTipo");
						if (execute($sqlModTipo))
						//if(true)
						{
							$mexFinale="Registrazione correttamente eseguita";
							writeLog("APP","Gestione $titleName ",$mex,$codMex);
							echo "{success:true, messaggio:\"$mexFinale\"}";
						}else{echo "{success:false, messaggio:\"".getLastError()."\"}";}
					}else{
						//inserimento nuovo
						$counter = getScalar("Select count(*) FROM $tab where $idField = '$indexTab'");
						if($counter==0)
						{
							addInsClause($colList,$valList,$titField,$textTab,"S");
							addInsClause($colList,$valList,$idField,$indexTab,"S");
							if($fieldTipo=='provinceDec')
								addInsClause($colList,$valList,'IdRegione',$ofRegion,"N");
							addInsClause($colList,$valList,"DataIni",'2001-01-01',"S");
							addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
							addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
							$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
							if (execute($sqlInsTipo))
							{
								//cancellazione vecchio
								$goodDel=false;
								$sqlDel =  "DELETE FROM $tab where $idField='$oldOne'";	
								$goodDel=execute($sqlDel);
								if($goodDel){
									$mexFinale="Registrazione correttamente eseguita";
									writeLog("APP","Gestione $titleName ",$mex,$codMex);
									echo "{success:true, messaggio:\"$mexFinale\"}";
								}else{
									writeLog("APP","Gestione $titleName ","Errore nella modifica, dati pendenti.",$codMex);
									echo "{success:false, messaggio:\"Errore nella modifica, dati pendenti.\"}";}
							}else{
								writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
								echo "{success:false, messaggio:\"".getLastError()."\"}";}
						}else{
							writeLog("APP","Gestione $titleName ","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
							echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
					}
				}else{
					writeLog("APP","Gestione $titleName ","Errore nella modifica del dato.",$codMex);
					echo "{success:false, messaggio:\"Errore nella modifica del dato.\"}";}
			}else{
				//la gestione è quella base
				$arrCod = getFetchArray("Select * FROM $tab where $codField='$textTab'");
				if(count($arrCod)==0)
				{
					$mex="Modifica $titleName: $textTab";
					addSetClause($setClause,$titField,$textTab,"S");
					addSetClause($setClause,"LastUser",$context['Userid'],"S");
					$sqlModTipo = "UPDATE $tab $setClause WHERE $idField=$indexTab";
					trace("Mod part: $sqlModTipo");
					if (execute($sqlModTipo))
					//if(true)
					{
						$mexFinale="Registrazione correttamente eseguita";
						//writeLog("APP","Gestione partite ",$mex,$codMex);
						echo "{success:true, messaggio:\"$mexFinale\"}";
					}else{
						writeLog("APP","Gestione $titleName ","\"".getLastError()."\"",$codMex);
						echo "{success:false, messaggio:\"".getLastError()."\"}";}
				}else{
					writeLog("APP","Gestione $titleName ","Il nome utilizzato &egrave gi&agrave presente.",$codMex);
					echo "{success:false, messaggio:\"Il nome utilizzato &egrave gi&agrave presente.\"}";}
			}
	}
}

///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle partite selezionate
///////////////////////////////////////////////////////////////////
function deletePartite()
{
	global $context;

	$stringaRitorno='';
	$values = explode('|', $_REQUEST['vect']);
	$list = substr(join(",", $values),1); // toglie virgola iniziale
	$num = count($values)-1;
	$arrErrors=array();
	//trace("valori passati: ".print_r($values,true));
	//trace("numero. $num");
	//Delete
	$isAStringKey=false;
	$fieldTipo = $_REQUEST['tipoDec'];
	switch($fieldTipo)
	{
		case 'partita':
			//variabili
			$tab='tipopartita';
			$idField = 'IdTipoPartita';
			$chkField= 'TitoloTipoPartita';
			$titleName = 'partita';
			$titField = 'TitoloTipoPartita';
			break;
		case 'speciale':
			//variabili
			$tab = 'tipospeciale';
			$idField = 'IdTipoSpeciale';
			$chkField = 'TitoloTipoSpeciale';
			$codField = 'CodTipoSpeciale';
			$titleName = 'speciale';
			$titField = 'TitoloTipoSpeciale';
			break;
		case 'insoluto':
			//variabili
			$tab='tipoinsoluto';
			$idField = 'IdTipoInsoluto';
			$chkField= 'TitoloTipoInsoluto';
			$titleName = 'insoluto';
			$titField = 'TitoloTipoInsoluto';
			break;
		case 'pagamento':
			//variabili
			$tab='tipopagamento';
			$idField = 'IdTipoPagamento';
			$chkField= 'TitoloTipoPagamento';
			$titleName = 'pagamento';
			$titField = 'TitoloTipoPagamento';
			break;
		case 'compagnia':
			//variabili
			$tab='tipocompagnia';
			$idField = 'IdTipoCompagnia';
			$chkField= 'TitoloTipoCompagnia';
			$titleName = 'compagnia';
			$titField = 'TitoloTipoCompagnia';
			break;
		case 'famigliaProdotto':
			//variabili
			$tab='famigliaprodotto';
			$idField = 'IdFamiglia';
			$idFieldParent = 'IdFamigliaParent';
			$chkField= 'TitoloFamiglia';
			$titleName = 'famiglia';
			$titField = 'TitoloFamiglia';
			break;
		case 'prodotto':
			//variabili
			$tab='prodotto';
			$idField = 'IdProdotto';
			$chkField= 'TitoloProdotto';
			$titleName = 'prodotto';
			$titField = 'TitoloProdotto';
			break;
		case 'attributo':
			//variabili
			$tab='attributo';
			$idField = 'IdAttributo';
			$chkField= 'TitoloAttributo';
			$titleName = 'attributo';
			$titField = 'TitoloAttributo';
			break;
		case 'nazioneDec':
			//variabili
			$tab='nazione';
			$idField = 'SiglaNazione';
			$chkField= 'TitoloNazione';
			$titleName = 'nazione';
			$titField = 'TitoloNazione';
			$isAStringKey=true;
			break;
		case 'regioneDec':
			//variabili
			$tab='regione';
			$idField = 'IdRegione';
			$chkField= 'TitoloRegione';
			$titleName = 'regione';
			$titField = 'TitoloRegione';
			break;
		case 'provinceDec':
			//variabili
			$tab='provincia';
			$idField = 'SiglaProvincia';
			$chkField= 'TitoloProvincia';
			$titleName = 'provincia';
			$titField = 'TitoloProvincia';
			$isAStringKey=true;
			break;
		case 'userState':
			//variabili
			$tab='statoutente';
			$idField = 'IdStatoUtente';
			$chkField= 'TitoloStatoUtente';
			$titleName = 'stato utenza';
			$titField = 'TitoloStatoUtente';
			break;
		case 'statocontratto':
			//variabili
			$tab='statocontratto';
			$idField = 'IdStatoContratto';
			$chkField= 'TitoloStatoContratto';
			$titleName = 'stato del contratto';
			$titField = 'TitoloStatoContratto';
			break;
		case 'movimentod':
			//variabili
			$tab='tipomovimento';
			$idField = 'IdTipoMovimento';
			$chkField= 'TitoloTipoMovimento';
			$titleName = 'movimento';
			$titField = 'TitoloTipoMovimento';
			break;
	}
	$titoliLog = getFetchArray("SELECT $titField FROM $tab where $idField in ($list)");
	$list="";
	for($i=1;$i<=$num;$i++)
	{
		if($i<$num)
			$list .=$titoliLog[$i][$titField].",";
		else
		 	$list .=$titoliLog[$i][$titField];
	}
	$codMex="CANC_DECO";
	$mex="Cancellazione delle decodifiche ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		// serve per il log
		if(!$isAStringKey)
			$titolo = getFetchArray("SELECT $chkField FROM $tab where $idField=$values[$i]");
		else
			$titolo = getFetchArray("SELECT $chkField FROM $tab where $idField='$values[$i]'");
		$arrErrors[$i]['Rule']='';
		$arrErrors[$i]['Result']='K';
		
		//se stiamo cancellando le famiglie controlla non sia padre di nessun altra famiglia
		$isLukeFather=false;
		if($fieldTipo=='famigliaProdotto'){
			$sons = getScalar("select count(*) from $tab where $idFieldParent = $values[$i]");
			if($sons>0){
				$isLukeFather=true;
			}
		}
		
		//eliminazione dalla tabella
		if(!$isAStringKey)
			$sqlDel =  "DELETE FROM $tab where $idField=".$values[$i];
		else
			$sqlDel =  "DELETE FROM $tab where $idField='$values[$i]'";
		//trace("Delete $tab: $sqlDel");
		$noGoodDel=false;
		if(!$isLukeFather)
			$noGoodDel=!execute($sqlDel);
			
		if($noGoodDel || $isLukeFather)
		//if(true)
		{
			if($isLukeFather)
				$arrErrors[$i]['Rule']=" la $titleName ".$titolo[0][$titField]." &egrave una Macrofamiglia con sottofamiglie.";
			else
				$arrErrors[$i]['Rule']="nella cancellazione del tipo $titleName ".$titolo[0][$titField];
			$arrErrors[$i]['Result']='E';
		}
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	$messaggioErr='';
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		if($arrErrors[$tindex]['Result']=='E'){
			if($arrErrors[$tindex]['Rule']!='')
			{
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['Rule'];
			}
		}
	}
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Decodifiche cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}
?>
