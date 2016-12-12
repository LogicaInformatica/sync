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
		case "delete":delete();
			break;
		case "readMainGrid":readGrid();
			break;
		case "saveAgg":aggiornaTipo();
			break;
		default:
			echo "{failure:true, task: '$task'}";
	}
}
///////////////////////////////////////////////////////////////////////
//Funzione di lettura della griglia
///////////////////////////////////////////////////////////////////////
function readGrid()
{
	global $context;
	$fields = "*";
	$fieldTipo = $_REQUEST['tipoOrg'];
	switch($fieldTipo)
	{
		case 'areaGeoO':
			isset($_REQUEST['tipoArea'])?$_REQUEST['tipoArea']:null;
			$fieldAreaGeoType = $_REQUEST['tipoArea'];
			$query = "v_area_geo_organizzazione where TipoArea='$fieldAreaGeoType'";
			$ordine="Ordinatore asc";
			$campoTitolo="TitoloArea";
			break;
		case 'trepartoO':
			$query = "tiporeparto";
			$ordine="TitoloTipoReparto asc";
			$campoTitolo="TitoloTipoReparto";
			break;
		case 'agenziaO':
			$query = "v_reparto_organizzazione";
			$ordine="TitoloUfficio asc";
			$campoTitolo="TitoloUfficio";
			break;
		case 'trecapitoO':
			$query = "tiporecapito";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoRecapito";
			break;
		case 'trelazioneO':
			$query = "tiporelazione";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoRelazione";
			break;
		case 'tcontroparteO':
			$query = "tipocontroparte";
			$ordine="TitoloTipoControparte asc";
			$campoTitolo="TitoloTipoControparte";
			break;
		case 'tclienteO':
			$query = "tipocliente";
			$ordine="Ordine asc";
			$campoTitolo="TitoloTipoCliente";
			break;
		case 'compagniaO':
			isset($_REQUEST['tipoCompagnia'])?$_REQUEST['tipoCompagnia']:null;
			$fieldCompanyType = $_REQUEST['tipoCompagnia'];
			$query = "v_societa_organizzazione where IdTipoCompagnia=$fieldCompanyType";
			$ordine="TitoloCompagnia asc";
			$campoTitolo="TitoloCompagnia";
			break;
		case 'filialeO':
			$query = "v_filiale_organizzazione";
			$ordine="TitoloArea asc";
			$campoTitolo="TitoloArea";
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
			if($fieldTipo=='compagniaO'){
				$nameGroup=$arr[$i]['TitoloProvincia']!=null?$arr[$i]['TitoloProvincia']:"Nessuna";
				$arr[$i]['TitoloProvincia'] = $nameGroup;
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
	//per inserimenti annidati
	$valCicloList = "";
	$colCicloList = "";
	$youcango=true;
	$errCango='';
	
	$toOrder=false;//da ordinare rispetto a Ordine
	$toCodeUpper=false;//codice maiuscolo
	$toCheck=false;//check da salvare
	$fieldTipo = $_REQUEST['tipoOrg'];
	switch($fieldTipo)
	{
		case 'areaGeoO':
			isset($_REQUEST['tipoArea'])?$_REQUEST['tipoArea']:null;
			$fieldAreaGeoType = $_REQUEST['tipoArea'];
			isset($_POST['idarea'])?$_POST['idarea']:0;
			isset($_POST['nomeGeo'])?$_POST['nomeGeo']:'';
			isset($_POST['siglaGeo'])?$_POST['siglaGeo']:'';
			isset($_POST['provincia'])?$_POST['provincia']:'';
			isset($_POST['cap'])?$_POST['cap']:'';
			isset($_POST['areaParent'])?$_POST['areaParent']:null;
			if($_POST['idarea']=='')
				$_POST['idarea']=0;
			if($_POST['areaParent']=='')
				$_POST['areaParent']=null;
			if($_POST['cap']=='')
				$_POST['cap']=null;
			if($_POST['provincia']=='')
				$_POST['provincia']=null;	
				
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['idarea'];
			$titTab=$_POST['nomeGeo'];
			$codTab=$_POST['siglaGeo'];
			break;
		case 'compagniaO':
			isset($_REQUEST['tipoCompagnia'])?$_REQUEST['tipoCompagnia']:null;
			$fieldCompanyType = $_REQUEST['tipoCompagnia'];
			isset($_POST['idcompagnia'])?$_POST['idcompagnia']:0;
			isset($_POST['nomeC'])?$_POST['nomeC']:'';
			isset($_POST['siglaC'])?$_POST['siglaC']:'';
			isset($_POST['provincia'])?$_POST['provincia']:'';
			isset($_POST['cap'])?$_POST['cap']:'';
			isset($_POST['localita'])?$_POST['localita']:'';
			isset($_POST['address'])?$_POST['address']:'';
			isset($_POST['telefono'])?$_POST['telefono']:'';
			isset($_POST['fax'])?$_POST['fax']:'';
			isset($_POST['titolare'])?$_POST['titolare']:'';
			isset($_POST['mail'])?$_POST['mail']:'';
			if($_POST['idcompagnia']=='')
				$_POST['idcompagnia']=0;
			if($_POST['cap']=='')
				$_POST['cap']=null;
			if($_POST['provincia']==''){
				$_POST['provincia']=null;
			}else{
				$_POST['provincia'] = strtoupper($_POST['provincia']);
			}	
			$_POST['address'] = strtoupper($_POST['address']);
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['idcompagnia'];
			$titTab=$_POST['nomeC'];
			$codTab=$_POST['siglaC'];
			break;
		case 'agenziaO':
			isset($_POST['idreparto'])?$_POST['idreparto']:0;
			isset($_POST['nomeC'])?$_POST['nomeC']:'';
			isset($_POST['siglaC'])?$_POST['siglaC']:'';
			isset($_POST['treparto'])?$_POST['treparto']:'';
			isset($_POST['FlagCrea'])?$_POST['FlagCrea']:'';
			isset($_POST['compagnia'])?$_POST['compagnia']:'';
			isset($_POST['referenteN'])?$_POST['referenteN']:'';
			isset($_POST['telefonorep'])?$_POST['telefonorep']:'';
			isset($_POST['faxrep'])?$_POST['faxrep']:'';
			isset($_POST['emailref'])?$_POST['emailref']:'';
			isset($_POST['emailfatt'])?$_POST['emailfatt']:'';
			isset($_POST['telclienti'])?$_POST['telclienti']:'';
			isset($_POST['maxsms'])?$_POST['maxsms']:'';
			isset($_POST['nomebanca'])?$_POST['nomebanca']:'';
			isset($_POST['iban'])?$_POST['iban']:'';
			isset($_POST['note'])?$_POST['note']:'';
			isset($_POST['FlagDelega'])?$_POST['FlagDelega']:'';
			if($_POST['idreparto']=='')
				$_POST['idreparto']=0;
			if($_POST['treparto']=='')
				$_POST['treparto']=null;
			if($_POST['FlagCrea']=="on")
				$_POST['FlagCrea']=true;
			else
				$_POST['FlagCrea']=false;
			if($_POST['compagnia']=='')
				$_POST['compagnia']=null;
			if($_POST['maxsms']=='')
				$_POST['maxsms']=null; // era 0; corretto il 20/10/2015: null significa no limit
			if($_POST['FlagDelega']=="on")
				$_POST['FlagDelega']="Y";
			else
				$_POST['FlagDelega']="N";
			$_POST['siglaC'] = strtoupper($_POST['siglaC']);
			$lastInsertCompany=0;
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['idreparto'];
			$titTab=$_POST['nomeC'];
			$codTab=$_POST['siglaC'];
			$toCreate=$_POST['FlagCrea'];
			break;
		case 'filialeO':
			isset($_POST['idfiliale'])?$_POST['idfiliale']:0;
			isset($_POST['nomeC'])?$_POST['nomeC']:'';
			isset($_POST['siglaC'])?$_POST['siglaC']:'';
			isset($_POST['area'])?$_POST['area']:'';
			isset($_POST['emailprin'])?$_POST['emailprin']:'';
			isset($_POST['emailresp'])?$_POST['emailresp']:'';
			if($_POST['idfiliale']=='')
				$_POST['idfiliale']=0;
			if($_POST['area']==''){
				$_POST['area']=null;
			}	
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['idfiliale'];
			$titTab=$_POST['nomeC'];
			$codTab=$_POST['siglaC'];
			break;
		default:
			isset($_POST['idtipo'])?$_POST['idtipo']:0;
			isset($_POST['nomeTipo'])?$_POST['nomeTipo']:'';
			isset($_POST['siglaTipo'])?$_POST['siglaTipo']:'';
			isset($_POST['FlagT'])?$_POST['FlagT']:0;
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			trace("flag ".$_POST['FlagT']);
			if($_POST['FlagT']=="on")
				$_POST['FlagT']="Y";
			else
				$_POST['FlagT']="N";
							
			//$indexTab=$_POST['CodTipo'];
			$indexTab=$_POST['idtipo'];
			$titTab=$_POST['nomeTipo'];
			$codTab=$_POST['siglaTipo'];
			$flagTab=$_POST['FlagT'];
			break;
	}
	
	//trace("tipo $fieldTipo");
	if(!$stepOut)
	{
		switch($fieldTipo)
		{
			case 'areaGeoO':
				//variabili
				$tab = 'area';
				$idField = 'IdArea';
				$codField = 'CodArea';
				$titleName = 'area';
				$codMexName = 'AREE';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloArea';
				$typeField = 'TipoArea';
				$capField = 'Cap';
				$codProvField = 'SiglaProvincia';
				$idParentField = 'IdAreaParent';
				break;
			case 'compagniaO':
				//variabili
				$tab = 'compagnia';
				$idField = 'IdCompagnia';
				$codField = 'CodCompagnia';
				$titleName = 'comagnia';
				$codMexName = 'COMPAGNIE';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloCompagnia';
				$typeField = 'IdTipoCompagnia';
				$capField = 'CAP';
				$codProvField = 'SiglaProvincia';
				$nomeTitField = 'NomeTitolare';
				$addressField = 'Indirizzo';
				$placeField = 'Localita';
				$phoneField = 'Telefono';
				$faxField = 'Fax';
				$mailTitField = 'EmailTitolare';
				$toCodeUpper=true;
				break;
			case 'agenziaO':
				//variabili
				$tab = 'reparto';
				$idField = 'IdReparto';
				$codField = 'CodUfficio';
				$titleName = 'societ&agrave';
				$codMexName = 'SOCIETA';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloUfficio';
				$typeUffField = 'IdTipoReparto';
				$idCompany = 'IdCompagnia';
				$nomeRefField = 'NomeReferente';
				$phoneField = 'Telefono';
				$faxField = 'Fax';
				$mailRefField = 'EmailReferente';
				$mailFattField = 'EmailFatturazione';
				$flagDelegaField = 'FlagDelega';
				$bancaField = 'NomeBanca';
				$ibanField = 'IBAN';
				$notaField = 'Nota';
				$phoneClientField = 'TelefonoPerClienti';
				$msmsField = 'MaxSMSContratto';
				$toCodeUpper=true;
				break;
			case 'filialeO':
				//variabili
				$tab = 'filiale';
				$idField = 'IdFiliale';
				$codField = 'CodFiliale';
				$titleName = 'filiale';
				$codMexName = 'FIALIALI';
				$neww = Array();
				$neww[]='Nuova';
				$neww[]='salvata';	
				//campi tab
				$titField = 'TitoloFiliale';
				$areaField = 'IdArea';
				$mailRespField = 'MailResponsabile';
				$mailPrincField = 'MailPrincipale';
				break;
			case 'trepartoO':
				//variabili
				$tab = 'tiporeparto';
				$idField = 'IdTipoReparto';
				$codField = 'CodTipoReparto';
				$titleName = 'tipo reparto';
				$codMexName = 'TIPO_REPARTO';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoReparto';
				break;
			case 'trecapitoO':
				//variabili
				$tab = 'tiporecapito';
				$idField = 'IdTipoRecapito';
				$codField = 'CodTipoRecapito';
				$titleName = 'tipo recapito';
				$codMexName = 'TIPO_RECAPITO';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoRecapito';
				$toOrder=true;
				$toCodeUpper=true;
				break;
			case 'trelazioneO':
				//variabili
				$tab = 'tiporelazione';
				$idField = 'IdTipoRelazione';
				$codField = 'CodTipoRelazione';
				$titleName = 'tipo relazione';
				$codMexName = 'TIPO_RELAZIONE';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoRelazione';
				$toOrder=true;
				$toCodeUpper=true;
				break;
			case 'tcontroparteO':
				//variabili
				$tab = 'tipocontroparte';
				$idField = 'IdTipoControparte';
				$codField = 'CodTipoControparte';
				$titleName = 'tipo controparte';
				$codMexName = 'TIPO_CONTROPARTE';
				$flagField = 'FlagGarante';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoControparte';
				$toCheck=true;
				$toCodeUpper=true;
				break;
			case 'tclienteO':
				//variabili
				$tab = 'tipocliente';
				$idField = 'IdTipoCliente';
				$codField = 'CodTipoCliente';
				$titleName = 'tipo cliente';
				$codMexName = 'TIPO_CLIENTE';
				$neww = Array();
				$neww[]='Nuovo';
				$neww[]='salvato';	
				//campi tab
				$titField = 'TitoloTipoCliente';
				$toOrder=true;
				$toCodeUpper=true;
				break;
		}
		//trace("TA ".$_POST['TitoloTipoPartita']);
		//*****inserimento
		$order = getScalar("select count(*) from $tab");
		$order=$order+1;
		$counterId = getScalar("select count(*) from $tab where $idField=$indexTab");
		if($fieldTipo=='areaGeoO'){
			$counter = getScalar("select count(*) from $tab where $typeField='$fieldAreaGeoType' and $codField like '$codTab'");
		}else{
			$counter = getScalar("select count(*) from $tab where $codField like '$codTab'");
		}
		if($counterId==0)
		{
			//trace("arr ".print_r($arrCod,true)." lung ".count($arrCod)." post $indexTab");
			if($counter==0)
			{
				$codMex="ADD_".$codMexName;
				$mex="Inserimento tipo organizzativo $titleName: $titTab";
				addInsClause($colList,$valList,$titField,$titTab,"S");
				
				if($toCodeUpper){
					addInsClause($colList,$valList,$codField,strtoupper($codTab),"S");
				}else{
					addInsClause($colList,$valList,$codField,$codTab,"S");
				}
				if($fieldTipo=='areaGeoO'){
					addInsClause($colList,$valList,$typeField,$fieldAreaGeoType,"S");
					addInsClause($colList,$valList,$capField,$_POST['cap'],"S");
					addInsClause($colList,$valList,$codProvField,$_POST['provincia'],"S");
					addInsClause($colList,$valList,$idParentField,$_POST['areaParent'],"N");					
				}else if($fieldTipo=='compagniaO'){
					addInsClause($colList,$valList,$typeField,$fieldCompanyType,"N");
					addInsClause($colList,$valList,$capField,$_POST['cap'],"S");
					addInsClause($colList,$valList,$codProvField,$_POST['provincia'],"S");
					addInsClause($colList,$valList,$nomeTitField,$_POST['titolare'],"S");
					addInsClause($colList,$valList,$addressField,$_POST['address'],"S");
					addInsClause($colList,$valList,$placeField,$_POST['localita'],"S");
					addInsClause($colList,$valList,$phoneField,$_POST['telefono'],"S");
					addInsClause($colList,$valList,$faxField,$_POST['fax'],"S");
					addInsClause($colList,$valList,$mailTitField,$_POST['mail'],"S");
				}else if($fieldTipo=='agenziaO'){
					addInsClause($colList,$valList,$typeUffField,$_POST['treparto'],"N");
					addInsClause($colList,$valList,$nomeRefField,$_POST['referenteN'],"S");
					addInsClause($colList,$valList,$phoneField,$_POST['telefonorep'],"S");
					addInsClause($colList,$valList,$faxField,$_POST['faxrep'],"S");
					addInsClause($colList,$valList,$mailRefField,$_POST['emailref'],"S");
					addInsClause($colList,$valList,$mailFattField,$_POST['emailfatt'],"S");
					addInsClause($colList,$valList,$bancaField,$_POST['nomebanca'],"S");
					addInsClause($colList,$valList,$ibanField,$_POST['iban'],"S");
					addInsClause($colList,$valList,$notaField,$_POST['note'],"S");
					addInsClause($colList,$valList,$phoneClientField,$_POST['telclienti'],"S");
					addInsClause($colList,$valList,$msmsField,$_POST['maxsms'],"N");
					addInsClause($colList,$valList,$flagDelegaField,$_POST['FlagDelega'],"S");
					if($toCreate){
						//crea compagnia omonima
						$youcango=false;
						//$lastNumCod=getFetchArray("select CodCompagnia from compagnia where CodCompagnia REGEXP '^[0-9]+$'=1 order by CodCompagnia desc limit 1;");
						//if(count($lastNumCod)>0){
							//$codiceGen=$lastNumCod[0]['CodCompagnia']+1;
							addInsClause($colCicloList,$valCicloList,'TitoloCompagnia',$titTab,"S");
							addInsClause($colCicloList,$valCicloList,'CodCompagnia',$codTab,"S");
							addInsClause($colCicloList,$valCicloList,'IdTipoCompagnia',2,"N");
							addInsClause($colCicloList,$valCicloList,"DataIni",'2001-01-01',"S");
							addInsClause($colCicloList,$valCicloList,"DataFin",'9999-12-31',"S");
							addInsClause($colCicloList,$valCicloList,"LastUser",$context['Userid'],"S");
							$sqlInsCompanyAuto = "INSERT INTO compagnia ($colCicloList)  VALUES($valCicloList)";
							if (execute($sqlInsCompanyAuto)){
								//$lastInsertCompany = mysql_insert_id(); // non è autoincrement
								$lastInsComp=getFetchArray("select IdCompagnia from compagnia where TitoloCompagnia = '$titTab' and IdTipoCompagnia=2 order by IdCompagnia desc limit 1;");
								if(count($lastInsComp)>0){
									$lastInsertCompany = $lastInsComp[0]['IdCompagnia'];
									addInsClause($colList,$valList,$idCompany,$lastInsertCompany,"N");
									$youcango=true;
								}else{$errCango="Errore nella creazione automatica della compagnia omonima.";}
							}else{$errCango="Errore nella creazione automatica della compagnia omonima.";}
						//}else{$errCango="Errore nella creazione automatica del codice della compagnia omonima.";}
					}else
						addInsClause($colList,$valList,$idCompany,$_POST['compagnia'],"N");
				}else if($fieldTipo=='filialeO'){
					addInsClause($colList,$valList,$areaField,$_POST['area'],"N");
					addInsClause($colList,$valList,$mailRespField,$_POST['emailresp'],"S");
					addInsClause($colList,$valList,$mailPrincField,$_POST['emailprin'],"S");
				}
				
				if($toOrder){
					addInsClause($colList,$valList,"Ordine",$order,"N");
				}
				if($toCheck)
					addInsClause($colList,$valList,$flagField,$flagTab,"S");
					
				addInsClause($colList,$valList,"DataIni",'2001-01-01',"S");
				addInsClause($colList,$valList,"DataFin",'9999-12-31',"S");
				addInsClause($colList,$valList,"LastUser",$context['Userid'],"S");
				
				$sqlInsTipo = "INSERT INTO $tab ($colList)  VALUES($valList)";
				//trace("ins par: $sqlInsTipo");
				//trace("ucango $youcango");
				if($youcango)
				{
					if (execute($sqlInsTipo))
					//if(true)
					{
						$mexFinale="$neww[0] $titleName, $neww[1].";
						writeLog("APP","Gestione tipi organizzativi ",$mex,$codMex);
						echo "{success:true, messaggio:\"$mexFinale\"}";
					}else{
						writeLog("APP","Gestione tipi organizzativi","\"".getLastError()."\"",$codMex);
						echo "{success:false, messaggio:\"".getLastError()."\"}";}
				}else{
					writeLog("APP","Gestione tipi organizzativi",$errCango,$codMex);
					echo "{success:true, messaggio:\"$errCango\"}";}
			}else{
				writeLog("APP","Gestione tipi organizzativi","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
				echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
		}else{
			$codMex="MOD_".$codMexName;
			if($fieldTipo=='areaGeoO'){
				$arrChk=getFetchArray("select * from $tab where $typeField='$fieldAreaGeoType' and $codField like '$codTab'");
			}else{
				$arrChk=getFetchArray("select * from $tab where $codField like '$codTab'");
			}
			if(($counter==0)||($counter>0)&&($arrChk[0][$idField]==$indexTab))
			{
				$mex="Modifica tipo organizzativo $titleName: $titTab";
				addSetClause($setClause,$titField,$titTab,"S");
				if($toCodeUpper){
					addSetClause($setClause,$codField,strtoupper($codTab),"S");
				}else{
					addSetClause($setClause,$codField,$codTab,"S");
				}
				if($fieldTipo=='areaGeoO'){
					addSetClause($setClause,$typeField,$fieldAreaGeoType,"S");
					addSetClause($setClause,$capField,$_POST['cap'],"S");
					addSetClause($setClause,$codProvField,$_POST['provincia'],"S");
					addSetClause($setClause,$idParentField,$_POST['areaParent'],"S");
				}else if($fieldTipo=='compagniaO'){
					addSetClause($setClause,$typeField,$fieldCompanyType,"N");
					addSetClause($setClause,$capField,$_POST['cap'],"S");
					addSetClause($setClause,$codProvField,$_POST['provincia'],"S");
					addSetClause($setClause,$nomeTitField,$_POST['titolare'],"S");
					addSetClause($setClause,$addressField,$_POST['address'],"S");
					addSetClause($setClause,$placeField,$_POST['localita'],"S");
					addSetClause($setClause,$phoneField,$_POST['telefono'],"S");
					addSetClause($setClause,$faxField,$_POST['fax'],"S");
					addSetClause($setClause,$mailTitField,$_POST['mail'],"S");
				}else if($fieldTipo=='agenziaO'){
					addSetClause($setClause,$typeUffField,$_POST['treparto'],"N");
					addSetClause($setClause,$idCompany,$_POST['compagnia'],"N");
					addSetClause($setClause,$nomeRefField,$_POST['referenteN'],"S");
					addSetClause($setClause,$phoneField,$_POST['telefonorep'],"S");
					addSetClause($setClause,$faxField,$_POST['faxrep'],"S");
					addSetClause($setClause,$mailRefField,$_POST['emailref'],"S");
					addSetClause($setClause,$mailFattField,$_POST['emailfatt'],"S");
					addSetClause($setClause,$faxField,$_POST['fax'],"S");
					addSetClause($setClause,$bancaField,$_POST['nomebanca'],"S");
					addSetClause($setClause,$ibanField,$_POST['iban'],"S");
					addSetClause($setClause,$notaField,$_POST['note'],"S");
					addSetClause($setClause,$phoneClientField,$_POST['telclienti'],"S");
					addSetClause($setClause,$msmsField,$_POST['maxsms'],"N");
					addSetClause($setClause,$flagDelegaField,$_POST['FlagDelega'],"S");
				}else if($fieldTipo=='filialeO'){
					addSetClause($setClause,$areaField,$_POST['area'],"N");
					addSetClause($setClause,$mailRespField,$_POST['emailresp'],"S");
					addSetClause($setClause,$mailPrincField,$_POST['emailprin'],"S");
				}
				//if($toOrder)
				//	addInsClause($colList,$valList,"Ordine",$order,"N");
				
				if($toCheck)
					addSetClause($setClause,$flagField,$flagTab,"S");
					
				addSetClause($setClause,"LastUser",$context['Userid'],"S");
				$sqlModTipo = "UPDATE $tab $setClause WHERE $idField=$indexTab";
				//trace("Mod part: $sqlModTipo");
				if (execute($sqlModTipo))
				//if(true)
				{
					$mexFinale="Registrazione correttamente eseguita";
					writeLog("APP","Gestione tipi organizzativi ",$mex,$codMex);
					echo "{success:true, messaggio:\"$mexFinale\"}";
				}else{
					writeLog("APP","Gestione tipi organizzativi","\"".getLastError()."\"",$codMex);
					echo "{success:false, messaggio:\"".getLastError()."\"}";}
			}else{
				writeLog("APP","Gestione tipi organizzativi","Il codice utilizzato &egrave gi&agrave presente.",$codMex);
				echo "{success:false, messaggio:\"Il codice utilizzato &egrave gi&agrave presente.\"}";}
		}
	}
}
///////////////////////////////////////////////////////////////////
//Funzione di cancellazione 
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
	$toOrder = false;
	$toFindParent=false;
	$toFindParent2=false;
	$fieldTipo = $_REQUEST['tipoOrg'];
	switch($fieldTipo)
	{
		case 'areaGeoO':
			isset($_REQUEST['tipoArea'])?$_REQUEST['tipoArea']:null;
			$fieldAreaGeoType = $_REQUEST['tipoArea'];
			if($_POST['idarea']=='')
				$_POST['idarea']=0;
			if($_POST['areaParent']=='')
				$_POST['areaParent']=null;
			//variabili
			$tab='area';
			$tabToceck='area';
			$tabToceck2='filiale';
			$idField = 'IdArea';
			$chkField= 'TitoloArea';
			$titleName = 'area';
			$titField = 'TitoloArea';
			$idParentField = 'IdAreaParent';
			$idParentField2 = 'IdArea';
			$mexParentError = "&egrave un'Area di Controllo con sottoaree assegnate";
			$mexParentError2 = "&egrave un'Area di Controllo con filiali assegnate";
			$toFindParent=true;
			$toFindParent2=true;
			break;
		case 'compagniaO':
			isset($_REQUEST['tipoCompagnia'])?$_REQUEST['tipoCompagnia']:null;
			$fieldCompanyType = $_REQUEST['tipoCompagnia'];
			//variabili
			$tab='compagnia';
			$tabToceck='reparto';
			$idField = 'IdCompagnia';
			$chkField= 'TitoloCompagnia';
			$titleName = 'compagnia';
			$titField = 'TitoloCompagnia';
			$idParentField = 'IdCompagnia';
			$mexParentError = "&egrave una compagnia con societ&agrave assegnate";
			if($fieldCompanyType==2)
				$toFindParent=true;
			break;
		case 'agenziaO':
			//variabili
			$tab='reparto';
			$idField = 'IdReparto';
			$chkField= 'TitoloUfficio';
			$titleName = 'societ&agrave';
			$titField = 'TitoloUfficio';
			break;
		case 'filialeO':
			//variabili
			$tab='filiale';
			$idField = 'IdFiliale';
			$chkField= 'TitoloFiliale';
			$titleName = 'filiale';
			$titField = 'TitoloFiliale';
			break;
		case 'trepartoO':
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			//variabili
			$tab='tiporeparto';
			$idField = 'IdTipoReparto';
			$chkField= 'TitoloTipoReparto';
			$titleName = 'tipo reparto';
			$titField = 'TitoloTipoReparto';
			break;
		case 'trecapitoO':
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			//variabili
			$tab='tiporecapito';
			$idField = 'IdTipoRecapito';
			$chkField= 'TitoloTipoRecapito';
			$titleName = 'tipo recapito';
			$titField = 'TitoloTipoRecapito';
			$toOrder = true;
			break;
		case 'trelazioneO':
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			//variabili
			$tab='tiporelazione';
			$idField = 'IdTipoRelazione';
			$chkField= 'TitoloTipoRelazione';
			$titleName = 'tipo relazione';
			$titField = 'TitoloTipoRelazione';
			$toOrder = true;
			break;
		case 'tcontroparteO':
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			//variabili
			$tab='tipocontroparte';
			$idField = 'IdTipoControparte';
			$chkField= 'TitoloTipoControparte';
			$titleName = 'tipo controparte';
			$titField = 'TitoloTipoControparte';
			break;
		case 'tclienteO':
			if($_POST['idtipo']=='')
				$_POST['idtipo']=0;
			//variabili
			$tab='tipocliente';
			$idField = 'IdTipoCliente';
			$chkField= 'TitoloTipoCliente';
			$titleName = 'tipo cliente';
			$titField = 'TitoloTipoCliente';
			$toOrder = true;
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
	$codMex="CANC_TPORGAN";
	$mex="Cancellazione dei tipi organizzativi ($list)";
	beginTrans();
	for($i=1;$i<=$num;$i++)
	{
		// serve per il log
		$titolo = getFetchArray("SELECT $chkField FROM $tab where $idField='$values[$i]'");
		$arrErrors[$i]['Rule']='';
		$arrErrors[$i]['Result']='K';
		
		//se stiamo cancellando le famiglie controlla non sia padre di nessun altra famiglia
		$mexParentFinalError="";
		$isLukeFather=false;
		if($toFindParent){
			$sons = getScalar("select count(*) from $tabToceck where $idParentField = $values[$i]");
			trace("figli: $sons -> select count(*) from $tabToceck where $idParentField = $values[$i]");
			if($sons>0){
				$isLukeFather=true;
				$mexParentFinalError = $mexParentError;
			}
		}
		if($toFindParent2){
			$sons2 = getScalar("select count(*) from $tabToceck2 where $idParentField2 = $values[$i]");
			trace("figli: $sons -> select count(*) from $tabToceck2 where $idParentField2 = $values[$i]");
			if($sons2>0){
				$isLukeFather=true;
				if($mexParentFinalError=="")
					$mexParentFinalError .= $mexParentError2;
				else
					$mexParentFinalError .= " ed ".$mexParentError2;
			}
		}
		
		//eliminazione dalla tabella
		$sqlDel =  "DELETE FROM $tab where $idField=".$values[$i];
		trace("Delete $tab: $sqlDel");
		$noGoodDel=false;
		if(!$isLukeFather)
			$noGoodDel=!execute($sqlDel);

		if($noGoodDel || $isLukeFather)
		//if(true)
		{
			if($isLukeFather)
				$arrErrors[$i]['Rule']=' l\'elemento '.$titleName.' "'.$titolo[0][$titField].'" '.$mexParentFinalError;
			else
				$arrErrors[$i]['Rule']=' nella cancellazione dell\' elemento '.$titleName.' "'.$titolo[0][$titField].'"';
			$arrErrors[$i]['Result']='E';
		}
	}	
	$numero = count($arrErrors);
	//trace("--numero errori n.$numero");
	
	
	//riordina gli indici di Ordinamento se necessario
	if($toOrder){
		$num = getScalar("select count(*) from $tab");
		$arr = getFetchArray("SELECT * FROM $tab");
		trace("arr ".print_r($arr,true));
		$toChange=array();
		foreach($arr as $key=>$row)
		{
			trace("k $key");
			trace("row ".print_r($row,true));
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
	
	$messaggioErr='';
	$indiciErrori = array();
	foreach($arrErrors as $lkey=> $error){
		$indiciErrori[]=$lkey;
	}
	//trace("indiciErrori ".print_r($indiciErrori,true));
	for($h=1;$h<=count($arrErrors);$h++)
	{
		$tindex = $indiciErrori[$h-1];
		//trace("tindex $tindex");
		if($arrErrors[$tindex]['Result']=='E'){
			if($arrErrors[$tindex]['Rule']!='')
			{
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['Rule'];
			}
		}
	}
	//trace("arrErrors ".print_r($arrErrors,true));
	if($messaggioErr!=''){
		rollback();
		$stringaRitorno ="Errori almeno per la seguente cancellazione:";
		$stringaRitorno .=	$messaggioErr;
		$mexFinale=$stringaRitorno;
	}else{
		$mexFinale="Tipi organizzativi cancellati con successo.";
		commit();
	}
	//trace("stringaritorno = $stringaRitorno");
	writeLog("APP",$mex,$mexFinale,$codMex);
	echo $stringaRitorno;	
}
?>
