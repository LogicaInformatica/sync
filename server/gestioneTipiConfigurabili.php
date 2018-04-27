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
		case "delete":delete();//partite_decodifica
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
	$fieldTipo = $_REQUEST['tipoConf'];
	$secondary=false;
	switch($fieldTipo)
	{
		case 'categoriaConf':
			$query = "categoria";
			$ordine="CodCategoria asc";
			$campoTitolo="TitoloCategoria";
			break;
		case 'statorecuperoConf':
			$query = "statorecupero";
			$ordine="Ordine asc";
			$campoTitolo="TitoloStatoRecupero";
			break;
		case 'allegatoConf':
			$query = "tipoallegato";
			$ordine="CodTipoAllegato asc";
			$campoTitolo="TitoloTipoAllegato";
			break;
		case 'azioneConf':
			$query = "tipoazione";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoAzione";
			break;
		case 'tesitoConf':
			$query = "tipoesito";
			$ordine="CodTipoEsito asc";
			$campoTitolo="TitoloTipoEsito";
			break;
		case 'tipoincassoConf':
			$query = "tipoincasso";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoIncasso";
			break;
		case 'tiporichiestaConf':
			$query = "tiporichiesta";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoRichiesta";
			break;
		case 'statoLegaleConf':
			$query = "statolegale";
			$ordine="IdStatoLegale asc";
			$campoTitolo="TitoloStatoLegale";
			break;
		case 'statoStragiudConf':
			$query = "statostragiudiziale";
			$ordine="IdStatoStragiudiziale asc";
			$campoTitolo="TitoloStatoStragiudiziale";
			break;
		case 'categoriaMaxirata':
		    $query="categoriamaxirata";
			$ordine="CodMaxirata asc";
			$campoTitolo="CategoriaMaxirata";
			break;	
		case 'categoriaRiscattoLeasing':
		    $query="categoriariscattoleasing";
			$ordine="CodRiscattoLeasing asc";
			$campoTitolo="CategoriaRiscattoLeasing";
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
	
	$toAbbr=false;//abbreviazione
	$toOrder=false;//occuparsi dell'inserimento dell'ordinamento SPECIFICATO
	$toOrderAuto=false;//occuparsi dell'inserimento dell'ordinamento AUTOMATICO
	$toOver0=false;//la tabella in questione ha un indice valido a 0
	$isAlfaOmega=true;//inserisci data inizio e fine
	$toCheck=false;//check da salvare
	$fieldTipo = $_REQUEST['tipoConf'];
	switch($fieldTipo)
	{
		default:
			isset($_POST['IdTipo'])?$_POST['IdTipo']:0;
			isset($_POST['TitoloTipo'])?$_POST['TitoloTipo']:'';
			isset($_POST['CodTipo'])?$_POST['CodTipo']:'';
			isset($_POST['CodTipoLegacy'])?$_POST['CodTipoLegacy']:'';
			isset($_POST['AbbrTipo'])?$_POST['AbbrTipo']:'';
			isset($_POST['Ordine'])?$_POST['Ordine']:null;
			isset($_POST['isNew'])?$_POST['isNew']:false;
			isset($_POST['FlagT'])?$_POST['FlagT']:0;
			
			if($_POST['IdTipo']=='')
				$_POST['IdTipo']=0;
			if($_POST['forzatura']=='')
				$_POST['forzatura']=null;
			if($_POST['Ordine']=='')
				$_POST['Ordine']=null;
			if($_POST['FlagT']=="on")
				$_POST['FlagT']="Y";
			else
				$_POST['FlagT']="N";
				
			$_POST['CodTipo'] = strtoupper($_POST['CodTipo']);
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['IdTipo'];
			$codTab=$_POST['CodTipo'];
			$abbrTab=$_POST['AbbrTipo'];
  			$percTab=$_POST['PercProvvigione'];
			$ordine=$_POST['Ordine'];
			$flagTab=$_POST['FlagT'];
			break;
	}
	
	//trace("tipo $fieldTipo");
	switch($fieldTipo)
	{
		case 'categoriaConf':
			//variabili
			$tab = 'categoria';
			$idField = 'IdCategoria';
			$codField = 'CodCategoria';
			$titleName = 'categoria';
			$codMexName = 'CATEGORIA';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloCategoria';
			$isAlfaOmega=false;
			if($indexTab==0)
				$_POST['isNew']=true;
			break;
		case 'statoLegaleConf':
			//variabili
			$tab = 'statolegale';
			$idField = 'IdStatoLegale';
			$codField = 'CodStatoLegale';
			$titleName = 'statolegale';
			$codMexName = 'STATO LEGALE';
			$neww = Array();
			$neww[]='Nuovo';
			$neww[]='salvato';	
			//campi tab
			$titField = 'TitoloStatoLegale';
            $percField = "PercProvvigione";
			$isAlfaOmega=false;
			if($indexTab==0)
				$_POST['isNew']=true;
			break;
		case 'statoStragiudConf':
			//variabili
			$tab = 'statostragiudiziale';
			$idField = 'IdStatoStragiudiziale';
			$codField = 'CodStatoStragiudiziale';
			$titleName = 'statostragiudiziale';
			$codMexName = 'STATO STRAGIUDIZIALE';
			$neww = Array();
			$neww[]='Nuovo';
			$neww[]='salvato';
			//campi tab
			$titField = 'TitoloStatoStragiudiziale';
			$isAlfaOmega=false;
			if($indexTab==0)
				$_POST['isNew']=true;
				break;
		case 'statorecuperoConf':
			//variabili
			$tab = 'statorecupero';
			$idField = 'IdStatoRecupero';
			$codField = 'CodStatoRecupero';
			$titleName = 'stato di recupero';
			$codMexName = 'STATO_RECUPERO';
			$neww = Array();
			$neww[]='Nuovo';
			$neww[]='salvato';	
			//campi tab
			$titField = 'TitoloStatoRecupero';
			$abbrField='AbbrStatoRecupero';
			$toAbbr=true;
			$toOrder=true;
			//ordini
			if($indexTab==0 && $_POST['isNew']==true)
				$indexTab=null;
			break;
		case 'allegatoConf':
			//variabili
			$tab = 'tipoallegato';
			$idField = 'IdTipoAllegato';
			$codField = 'CodTipoAllegato';
			$pattField = 'Pattern';
			$titleName = 'allegato';
			$codMexName = 'TIPO_ALLEGATO';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloTipoAllegato';
			if($indexTab==0)
				$_POST['isNew']=true;
			break;
		case 'azioneConf':
			//variabili
			$tab = 'tipoazione';
			$idField = 'IdTipoAzione';
			$codField = 'CodTipoAzione';
			$titleName = 'azione';
			$codMexName = 'TIPO_AZIONE';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'TitoloTipoAzione';
			if($indexTab==0)
				$_POST['isNew']=true;
			$toOrderAuto=true;
			break;
		case 'tesitoConf':
			//variabili
			$tab = 'tipoesito';
			$idField = 'IdTipoEsito';
			$codField = 'CodTipoEsito';
			$titleName = 'esito';
			$codMexName = 'TIPO_ESITO';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			$flagField = 'Negativo';
			//campi tab
			$titField = 'TitoloTipoEsito';
			if($indexTab==0)
				$_POST['isNew']=true;
			$toCheck=true;
			break;
		case 'tipoincassoConf':
			//variabili
			$tab = 'tipoincasso';
			$idField = 'IdTipoIncasso';
			$codField = 'CodTipoIncasso';
			$titleName = 'incasso';
			$codMexName = 'TIPO_INCASSO';
			$neww = Array();
			$neww[]='Nuovo';
			$neww[]='salvato';	
			//campi tab
			$titField = 'TitoloTipoIncasso';
			$toOrder=true;
			//ordini
			if($indexTab==0)
				$_POST['isNew']=true;
			break;
		case 'tiporichiestaConf':
			//variabili
			$tab = 'tiporichiesta';
			$idField = 'IdTipoRichiesta';
			$codField = 'CodTipoRichiesta';
			$titleName = 'richiesta';
			$codMexName = 'TIPO_RICHIESTA';
			$neww = Array();
			$neww[]='Nuovo';
			$neww[]='salvato';	
			//campi tab
			$titField = 'TitoloTipoRichiesta';
			$toOrder=true;
			//ordini
			if($indexTab==0)
				$_POST['isNew']=true;
			break;
		case 'categoriaMaxirata':
			//variabili
			$tab = 'categoriamaxirata';
			$idField = 'IdCategoriaMaxirata';
			$codField = 'CodMaxirata';
			$titleName = 'categoria maxirata';
			$codMexName = 'CATEGORIA_MAXIRATA';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'CategoriaMaxirata';
			$isAlfaOmega=false;
			if($indexTab==0)
				$_POST['isNew']=true;
			break;	
		case 'categoriaRiscattoLeasing':
			//variabili
			$tab = 'categoriariscattoleasing';
			$idField = 'IdCategoriaRiscattoLeasing';
			$codField = 'CodRiscattoLeasing';
			$titleName = 'categoria riscatti scaduti';
			$codMexName = 'CATEGORIA_RISCLEAS';
			$neww = Array();
			$neww[]='Nuova';
			$neww[]='salvata';	
			//campi tab
			$titField = 'CategoriaRiscattoLeasing';
			$isAlfaOmega=false;
			if($indexTab==0)
				$_POST['isNew']=true;
			break;		
	}
	//trace("TA ".$_POST['TitoloTipoPartita']);
	//*****inserimento
	$orderAuto = getScalar("select count(*) from $tab");
	$orderAuto=$orderAuto+1;
	if($indexTab!=null)
		$counter = getScalar("Select count(*) FROM $tab where $idField = $indexTab");
	else
		$counter=0;
	//trace("Select count(*) FROM $tab where $idField = $indexTab ==> $counter");
	if($counter==0 && $_POST['isNew'])
	{
		//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
		$arrCod = getFetchArray("Select $idField FROM $tab where $codField='$codTab'");
		//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod)." post $indexTab");
		if(count($arrCod)==0 || (count($arrCod)>0 && $arrCod[0][$idField]==$indexTab))
		{
			$codMex="ADD_".$codMexName;
			$mex="Inserimento nuovo tipo configurabile $titleName: ".$_POST['TitoloTipo'];
			addInsClause($colList,$valList,$titField,$_POST['TitoloTipo'],"S");
			addInsClause($colList,$valList,$codField,$_POST['CodTipo'],"S");
			if($toAbbr)
				addInsClause($colList,$valList,$abbrField,$abbrTab,"S");
			if($toOrder)
				addInsClause($colList,$valList,"Ordine",$ordine,"N");
			if($toOrderAuto)
				addInsClause($colList,$valList,"Ordine",$orderAuto,"N");
            if ($percField>'') 
   				addInsClause($colList,$valList,$percField,$percTab,"I");
			if($pattField)
				addInsClause($colList,$valList,"Pattern",$_POST['Pattern'],"S");	
			if($isAlfaOmega)
			{
				addInsClause($colList,$valList,"DataIni",'2001-01-01',"S");
				addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
			}
			if($toCheck)
				addInsClause($colList,$valList,$flagField,$flagTab,"S");
			addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
			
			$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
			//trace("ins par: $sqlInsTipo");
			if (execute($sqlInsTipo))
			//if(true)
			{
				$mexFinale="$neww[0] $titleName, $neww[1].";
				writeLog("APP","Gestione tipi configurabili ",$mex,$codMex);
				echo "{success:true, messaggio:\"$mexFinale\"}";
			}else{writeLog("APP","Gestione tipi configurabili","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
		}else{writeLog("APP","Gestione tipi configurabili","Il codice utilizzato &egrave gi&agrave presente.",$codMex); 
			echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
	}else{
			$codMex="MOD_".$codMexName;
			
			//$counterCod = getScalar("Select count(*) FROM $tab where $codField='".$_POST['CodTipo']."'");
			$arrCod = getFetchArray("Select $idField FROM $tab where $codField='$codTab'");
			//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod). " post $codTab");
			if(count($arrCod)==0 || (count($arrCod)>0 && $arrCod[0][$idField]==$indexTab))
			{
				$mex="Modifica tipo configurabile $titleName: ".$_POST['TitoloTipo'];
				addSetClause($setClause,$titField,$_POST['TitoloTipo'],"S");
				addSetClause($setClause,$codField,$_POST['CodTipo'],"S");
				if($toAbbr)
					addSetClause($setClause,$abbrField,$abbrTab,"S");
				if($toOrder)
					addSetClause($setClause,"Ordine",$ordine,"N");
                if ($percField>'') 
                    addSetClause($setClause,$percField,$percTab,"I");
				//if($toOrderAuto)//da testare e torgliere in modifica
				//	addSetClause($setClause,"Ordine",$orderAuto,"N");
				if($pattField)
					addSetClause($setClause,"Pattern",$_POST['Pattern'],"S");
				if($toCheck)
					addSetClause($setClause,$flagField,$flagTab,"S");
				addSetClause($setClause,"LastUser",$context['Userid'],"S");
				$sqlModTipo = "UPDATE $tab $setClause WHERE $idField=$indexTab";
				trace("Mod part: $sqlModTipo");
				if (execute($sqlModTipo))
				//if(true)
				{
					$mexFinale="Registrazione correttamente eseguita";
					writeLog("APP","Gestione tipi configurabili",$mex,$codMex);
					echo "{success:true, messaggio:\"$mexFinale\"}";
				}else{writeLog("APP","Gestione tipi configurabili","\"".getLastError()."\"",$codMex); echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{
				writeLog("APP","Gestione tipi configurabili","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
				echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
	}
}
///////////////////////////////////////////////////////////////////
//Funzione di cancellazione delle partite selezionate
///////////////////////////////////////////////////////////////////
function delete()
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
	$toOrderAuto = false;
	$isAStringKey=false;
	$fieldTipo = $_REQUEST['tipoConf'];
	switch($fieldTipo)
	{
		case 'categoriaConf':
			//variabili
			$tab='categoria';
			$idField = 'IdCategoria';
			$chkField= 'TitoloCategoria';
			$titleName = 'categoria';
			$titField = 'TitoloCategoria';
			break;
		case 'statoLegaleConf':
			//variabili
			$tab='statolegale';
			$idField = 'IdStatoLegale';
			$chkField= 'TitoloStatoLegale';
			$titleName = 'statolegale';
			$titField = 'TitoloStatoLegale';
			break;
		case 'statoStragiudConf':
			//variabili
			$tab='statostragiudiziale';
			$idField = 'IdStatoStragiudiziale';
			$chkField= 'TitoloStatoStragiudiziale';
			$titleName = 'statostragiudiziale';
			$titField = 'TitoloStatoStragiudiziale';
			break;
		case 'statorecuperoConf':
			//variabili
			$tab='statorecupero';
			$idField = 'IdStatoRecupero';
			$chkField= 'TitoloStatoRecupero';
			$titleName = 'stato di recupero';
			$titField = 'TitoloStatoRecupero';
			break;
		case 'allegatoConf':
			//variabili
			$tab='tipoallegato';
			$idField = 'IdTipoAllegato';
			$chkField= 'TitoloTipoAllegato';
			$titleName = 'allegato';
			$titField = 'TitoloTipoAllegato';
			break;
		case 'azioneConf':
			//variabili
			$tab='tipoazione';
			$idField = 'IdTipoAzione';
			$chkField= 'TitoloTipoAzione';
			$titleName = 'azione';
			$titField = 'TitoloTipoAzione';
			$toOrderAuto = true;
			break;
		case 'tesitoConf':
			//variabili
			$tab='tipoesito';
			$idField = 'IdTipoEsito';
			$chkField= 'TitoloTipoEsito';
			$titleName = 'esito';
			$titField = 'TitoloTipoEsito';
			break;
		case 'tipoincassoConf':
			//variabili
			$tab='tipoincasso';
			$idField = 'IdTipoIncasso';
			$chkField= 'TitoloTipoIncasso';
			$titleName = 'incasso';
			$titField = 'TitoloTipoIncasso';
			break;
		case 'tiporichiestaConf':
			//variabili
			$tab='tiporichiesta';
			$idField = 'IdTipoRichiesta';
			$chkField= 'TitoloTipoRichiesta';
			$titleName = 'richiesta';
			$titField = 'TitoloTipoRichiesta';
			break;
		case 'categoriaMaxirata':
			//variabili
			$tab='categoriamaxirata';
			$idField = 'IdCategoriaMaxirata';
			$chkField= 'CategoriaMaxirata';
			$titleName = 'categoria maxirata';
			$titField = 'CategoriaMaxirata';
			break;
		case 'categoriaRiscattoLeasing':
			//variabili
			$tab='categoriariscattoleasing';
			$idField = 'IdCategoriaRiscattoLeasing';
			$chkField= 'CategoriaRiscattoLeasing';
			$titleName = 'categoria riscatti scaduti';
			$titField = 'CategoriaRiscattoLeasing';
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
	$codMex="CANC_CONFIG";
	$mex="Cancellazione delle configurazioni ($list)";
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
		trace("Delete $tab: $sqlDel");
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
	
	//riordina gli indici di Ordinamento se necessario
	if($toOrderAuto){
		$num = getScalar("select count(*) from $tab");
		$arr = getFetchArray("SELECT * FROM $tab");
		//trace("arr ".print_r($arr,true));
		$toChange=array();
		foreach($arr as $key=>$row)
		{
			//trace("k $key");
			//trace("row ".print_r($row,true));
			if($row['Ordine']!=$key+1){
				$toChange['id'][]=$row[$idField];
				$toChange['name'][]=$row[$chkField];
				$toChange['NOrd'][]=$key+1;
			}
		}
		//trace("toChange ".print_r($toChange,true));
		foreach($toChange['id'] as $subK=>$indice)
		{
			$sqlModOrdine = "UPDATE $tab SET Ordine=".$toChange['NOrd'][$subK]." WHERE $idField=$indice";
			//trace("qmodOrd ".$sqlModOrdine);
			if (!execute($sqlModOrdine))
			//if(true)
			{
				$j=$toChange['NOrd'][$subK]-1;
				//trace("j $j");
				if($arrErrors[$j]['Rule']!='')
				{
					$arrErrors[$j]['Rule'] .= ' e ';
				}else{
					$arrErrors[$j]['Rule'] .= ' per l\'elemento "'.$toChange['name'][$subK].'"';
				}
				$arrErrors[$j]['Rule'] .= ' nel riordino degli indici.';
				$arrErrors[$j]['Result']='E';
			}
		}
	}
	
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
		$mexFinale="Configurazioni cancellate con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}