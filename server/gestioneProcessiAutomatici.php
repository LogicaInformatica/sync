<?php
	require_once("workflowFunc.php");
	require_once("userFunc.php");
	
	$attiva = isset($_POST['attiva']) ? $_POST['attiva']!='N' : true;
	if (!$attiva) {
		exit();
	}
doMain();

function doMain()
{
	global $context;
	
	$task = ($_REQUEST['task']) ? ($_REQUEST['task']) : null;
	
	switch ($task)
	{
		case "readPr":
		  readPr();
		  break;
		case "savePr":
		  saveProcesso();
		  break;
		case "saveAutoPr":
		  saveAutomatismoProcesso();
		  break;  
		case "deleteProcesso":
		  delProcesso();
		  break;
		case "deleteAutomatismoProcesso":
		  delAutomatismoProcesso();
		  break;  
		case "readAP":
		  readAP();
		  break;    	
	    default:
			echo "{failure:true, task: '$task'}";
	}
}
	/////////////////////////////////////////////////////////
	//Funzione di lettura della griglia Processi automatici//
	////////////////////////////////////////////////////////
	function readPr()
	{
		global $context;
		$fields = "*";
		$query = "v_processi_automatici";
		$counter = getScalar("SELECT count(*) FROM $query");
		$ordine="Processo asc";
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
				if ($_POST['sort']>' ') 
						$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
				else
					$sql .= $ordine;
					
			if ($start!='' || $end!='') {
		    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
			//tipo di profilo
			$arr=getFetchArray($sql); 
			
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
	
	////////////////////////////////////////////////////////////////////////////
	//Funzione di lettura della griglia Automatismi per il Processo automatico//
	///////////////////////////////////////////////////////////////////////////
	function readAP()
	{
		global $context;
		$idEv = isset($_REQUEST['idEv']) ? (integer)$_REQUEST['idEv'] : (isset($_GET['idEv'])? (integer)$_GET['idEv'] : '');
		$fields = "a.IdAutomatismo, a.TipoAutomatismo, a.TitoloAutomatismo, a.Comando, a.Condizione";
		$query = "automatismo a left join automatismoevento ae on (ae.IdEvento=".$idEv.")";
		$where = "a.IdAutomatismo=ae.IdAutomatismo";
		$counter = getScalar("SELECT count(*) FROM $query WHERE $where" );
		$ordine="a.TitoloAutomatismo asc";
		if ($counter == NULL)
			$counter = 0;
		if ($counter == 0) {
				$arr = array();
		} else {
		 
			$start = isset($_REQUEST['start']) ? (integer)$_REQUEST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
			$end =   isset($_REQUEST['limit']) ? (integer)$_REQUEST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');
			
			$sql = "SELECT $fields FROM $query WHERE $where ORDER BY ";
						
			if ($_POST['groupBy']>' ') {
						$sql .= $_POST['groupBy'] . ' ' . $_POST['groupDir'] . ', ';
				} 
				if ($_POST['sort']>' ') 
						$sql .= $_POST['sort'] . ' ' . $_POST['dir'];
				else
					$sql .= $ordine;
					
			if ($start!='' || $end!='') {
		    	$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
			}
			//tipo di profilo
			$arr=getFetchArray($sql); 
			
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
	
	
    ///////////////////////////////////////
    //Funzione di salvataggio di processi//
    ///////////////////////////////////////	
	function saveProcesso() 
	{
		global $context;
		$Operatore = $context['Userid'];
		$arrIns=array();
		$AzLogTitle='';
		$CodLog='';
		
		beginTrans();
		$IdEvento = ($_REQUEST['idEv']) ? ($_REQUEST['idEv']) : '';
		$codEvento = ($_REQUEST['codEv']) ? ($_REQUEST['codEv']) : '';
		if($IdEvento!='')
		{
		  //editing
		  //raccolta campi
		  /*array_push($arrIns, $IdEvento, 'IdEvento');
		  $codEv = isset($_REQUEST['CodEvento'])?$_REQUEST['CodEvento']:'';
		  trace("Il valore del codice evento:".$codEvento,false);
		  array_push($arrIns, $codEvento, 'CodEvento');*/
		  $titoloEv = isset($_REQUEST['Processo'])?$_REQUEST['Processo']:'';
		  array_push($arrIns, $titoloEv, 'TitoloEvento');
		  array_push($arrIns, $Operatore, 'LastUser');
		  $flagEv = isset($_REQUEST['Stato'])?$_REQUEST['Stato']:'';
		  if($flagEv=="Attivo"){
		  	$flagEv="N";
		  };
		  if($flagEv=="Sospeso"){
		  	$flagEv="Y";
		  };
		  if($flagEv=="Una tantum"){
		  	$flagEv="U";
		  };
		  array_push($arrIns, $flagEv, 'FlagSospeso');
		  $oraInizioEv = isset($_REQUEST['OraIni'])?$_REQUEST['OraIni']:'';
		  array_push($arrIns, $oraInizioEv, 'OraInizio');
		  $oraFineEv = isset($_REQUEST['OraFin'])?$_REQUEST['OraFin']:'';
		  array_push($arrIns, $oraFineEv, 'OraFine');
		  
		  //variabili
		  $query = '';
		
		  //costruzione query
		  for ($i=0; $i<count($arrIns); $i++)
		  {
			if ($arrIns[$i] == '')
			{
				$query .= $arrIns[$i+1]."=null,";
			} else {
				$query .= $arrIns[$i+1]."='".$arrIns[$i]."',";
			}
			$i++;		
		  }	
		  $query=substr($query,0,$query.length-1);
		  
		  //------------------//
		  //MODIFICA PROCESSO //
		  //------------------//
		  $sqlUp="UPDATE eventosistema SET $query where IdEvento=$IdEvento";
		  //trace("s $sqlUp");
		  if(execute($sqlUp)){
		  	writeLog("APP","Gestione processi automatici","Processo automatico aggiornato.",$CodLog);
			commit();
			echo "{success:true, error:\"Processo automatico aggiornato.\"}";
			//writeLog("APP","Gestione ripartizioni",$RipLogTitle,$CodLog);
		  }else{
		  	writeLog("APP","Gestione processi automatici","\"".getLastError()."\"",$CodLog);
			 rollback();
			 echo "{success:false, error:\"".getLastError()."\"}";
		   }
		} else 
		  {
		     //creazione
		     $idEv = isset($_REQUEST['IdEvento'])?$_REQUEST['IdEvento']:''; 
		     $codEv = isset($_REQUEST['CodEvento'])?$_REQUEST['CodEvento']:'';
		     $titoloEv = isset($_REQUEST['Processo'])?$_REQUEST['Processo']:'';
		     $flagEv = isset($_REQUEST['Stato'])?$_REQUEST['Stato']:'';
		     if($flagEv=="Attivo"){
		  	   $flagEv="N";
		     };
			 if($flagEv=="Sospeso"){
			   $flagEv="Y";
			 };
			 if($flagEv=="Una tantum"){
			   $flagEv="U";
			 };
		     $oraInizioEv = isset($_REQUEST['OraIni'])?$_REQUEST['OraIni']:'';
		     $oraFineEv = isset($_REQUEST['OraFin'])?$_REQUEST['OraFin']:'';
		     $valList = "";
			 $colList = "";
			 addInsClause($colList,$valList,"IdEvento",$idEv,"N");
			 addInsClause($colList,$valList,"CodEvento",$codEv,"S");
			 addInsClause($colList,$valList,"TitoloEvento",$titoloEv,"S");
			 addInsClause($colList,$valList,"LastUser",$Operatore,"S");
			 addInsClause($colList,$valList,"FlagSospeso",$flagEv,"S");
			 addInsClause($colList,$valList,"OraInizio",$oraInizioEv,"S");
			 addInsClause($colList,$valList,"OraFine",$oraFineEv,"S");
			 
		     $sqlINPRO =  "INSERT INTO eventosistema ($colList)  VALUES($valList)";
			 //trace("nR $sqlINPRO");
			 if(execute($sqlINPRO)){
			   $IdEv=getInsertId();
			   $ProLogTitle="Creazione processo automatico n.".$IdRegRip;
			   $CodLog='ADD_PRO';
			   writeLog("APP","Gestione processi automatici",$ProLogTitle,$CodLog);
			   commit();
			   echo "{success:true, error:\"Processo automatico creato.\"}";
			 }else{
			 	writeLog("APP","Gestione processi automatici","\"".getLastError()."\"",$CodLog);
				rollback();
				echo "{success:false, error:\"".getLastError()."\"}";
			  }
		  }
		
	}

    ////////////////////////////////////////////////////
    //Funzione di salvataggio automatismo del processo//
    ///////////////////////////////////////////////////	
	function saveAutomatismoProcesso() 
	{
		global $context;
		$Operatore = $context['Userid'];
		$arrInsAuto=array();
		$arrInsEvAuto=array();
		$AzLogTitle='';
		
		beginTrans();
		$IdEvento = ($_REQUEST['idEv']) ? ($_REQUEST['idEv']) : '';
		//trace("id evento passato:".$IdEvento, false);
		$IdAutomatismo = ($_REQUEST['idAuto']) ? ($_REQUEST['idAuto']) : '';
		//trace("id automatismo passato:".$IdAutomatismo, false);
		if($IdAutomatismo!='')
		{
			$codMex="MOD_AUTPROC";
			$mex="Aggiornamento degll'automatismo '".$_REQUEST['TitoloAutomatismo']."'";
		  $tipo = isset($_REQUEST['TipoAutomatismo'])?$_REQUEST['TipoAutomatismo']:'';
		  array_push($arrInsAuto, $tipo, 'TipoAutomatismo');
		  $titolo = isset($_REQUEST['TitoloAutomatismo'])?$_REQUEST['TitoloAutomatismo']:'';
		  array_push($arrInsAuto, $titolo, 'TitoloAutomatismo');
		  $comando = isset($_REQUEST['Comando'])?$_REQUEST['Comando']:'';
		  array_push($arrInsAuto, $comando, 'Comando');
		  $condizione = isset($_REQUEST['Condizione'])?$_REQUEST['Condizione']:'';
		  array_push($arrInsAuto, $condizione, 'Condizione');
		  array_push($arrInsAuto, $Operatore, 'LastUser');
		  
		  //variabili
		  $query = '';
		
		  //costruzione query
		  for ($i=0; $i<count($arrInsAuto); $i++)
		  {
			if ($arrInsAuto[$i] == '')
			{
				$query .= $arrInsAuto[$i+1]."=null,";
			} else {
				$query .= $arrInsAuto[$i+1]."='".$arrInsAuto[$i]."',";
			}
			$i++;		
		  }	
		  $query=substr($query,0,$query.length-1);
		  
		  //---------------------//
		  //MODIFICA AUTOMATISMO //
		  //---------------------//
		  $sqlUpAuto="UPDATE automatismo SET $query where IdAutomatismo=$IdAutomatismo";
		  //trace("s $sqlUpAuto");
		  if(execute($sqlUpAuto)){
			commit();
			echo "{success:true, error:\"Automatismo aggiornato.\"}";
			writeLog("APP","Gestione automatismo processo",$mex,$codMex);
		  }else{
		  		writeLog("APP","Gestione automatismo processo","\"".getLastError()."\"",$codMex);
			 rollback();
			 echo "{success:false, error:\"".getLastError()."\"}";
		   }
		} else 
		  {
		     //creazione
		     $tipo = isset($_REQUEST['TipoAutomatismo'])?$_REQUEST['TipoAutomatismo']:'';
			 $titolo = isset($_REQUEST['TitoloAutomatismo'])?$_REQUEST['TitoloAutomatismo']:'';
			 $comando = isset($_REQUEST['Comando'])?$_REQUEST['Comando']:'';
			 $condizione = isset($_REQUEST['Condizione'])?$_REQUEST['Condizione']:'';
			 $valList = "";
			 $colList = "";
			 addInsClause($colList,$valList,"TipoAutomatismo",$tipo,"S");
			 addInsClause($colList,$valList,"TitoloAutomatismo",$titolo,"S");
			 addInsClause($colList,$valList,"Comando",$comando,"S");
			 addInsClause($colList,$valList,"Condizione",$condizione,"S");
			 addInsClause($colList,$valList,"LastUser",$Operatore,"S");
			 						 
		     $sqlINAUTO =  "INSERT INTO automatismo ($colList) VALUES($valList)";
			 
			 if(execute($sqlINAUTO)){
			   $IdAuto=getInsertId();
			   //trace("nR $IdAuto");
			   $valListAutoPro = "";
			   $colListAutoPro = "";
			   addInsClause($colListAutoPro,$valListAutoPro,"IdEvento",$IdEvento,"N");
			   addInsClause($colListAutoPro,$valListAutoPro,"IdAutomatismo",$IdAuto,"N");
			   addInsClause($colListAutoPro,$valListAutoPro,"LastUser",$Operatore,"S");
			   $sqlINAUTOPRO =  "INSERT INTO automatismoevento ($colListAutoPro) VALUES($valListAutoPro)";
			   
			   if(execute($sqlINAUTOPRO)){
			   		$codMex="ADD_AUTPROC";
					$mex="Creazione degll'automatismo '".$_REQUEST['TitoloAutomatismo']."'";
			   	 $mex="Creazione automatismo n.".$IdAuto;
			     writeLog("APP","Gestione automatismo processo",$mex,$codMex);
			     commit();
			     echo "{success:true, error:\"Processo automatico creato.\"}";
			   }else{
			   		writeLog("APP","Gestione automatismo processo","\"".getLastError()."\"",$codMex);
				  rollback();
				  echo "{success:false, error:\"".getLastError()."\"}";
			   }
			 }else{
			 	writeLog("APP","Gestione automatismo processo","\"".getLastError()."\"",$codMex);
				rollback();
				echo "{success:false, error:\"".getLastError()."\"}";
			  }
		  }
		
	}	
	
    ////////////////////////////////////////////////////////
    //Funzione di cancellazione di una processo automatico//
    ////////////////////////////////////////////////////////
	function delProcesso() 
	{
	    global $context;
		$stringaRitorno='';
		$values = explode('|', $_REQUEST['vect']);
		$list = substr(join(",", $values),1); // toglie virgola iniziale
		$num = count($values)-1;
		$arrErrors=array();
		$titoliLog = getFetchArray("SELECT TitoloEvento FROM eventosistema where IdEvento in ($list)");
		$list="";
		for($i=1;$i<=$num;$i++)
		{
			if($i<$num)
				$list .=$titoliLog[$i]['TitoloEvento'].",";
			else
			 	$list .=$titoliLog[$i]['TitoloEvento'];
		}
		$codMex="CANC_PROC";
		$mex="Cancellazione dei processi ($list)";
		beginTrans();
		for($i=1;$i<=$num;$i++)
		{
			$flagAzioneTipoDel=true;
			execute("DELETE FROM automatismoevento where IdEvento=".$values[$i]);
			$sqlDelRip =  "DELETE FROM eventosistema where IdEvento=".$values[$i];
			if(!execute($sqlDelRip)) {
				$arrErrors[$i]['IdEvento']=	'cancellazione del processo n. "'.$values[$i].'"';
				$arrErrors[$i]['Result']='E';
			}		
		}	
	
		$messaggioErr='';
		$indiciErrori = array();
		foreach($arrErrors as $lkey=> $error){
			$indiciErrori[]=$lkey;
		}
		for($h=1;$h<=count($arrErrors);$h++)
		{
			$tindex = $indiciErrori[$h-1];
			if($arrErrors[$tindex]['Result']=='E'){
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdEvento'];
			}
		}
		if($messaggioErr!=''){
			$stringaRitorno ="Errori almeno per la seguente cancellazione:";
			$stringaRitorno .=	$messaggioErr;
			$mexFinale=$stringaRitorno;
			rollback();
		}else{
			$mexFinale="Processi cancellati con successo.";
			commit();
		}
		//trace("stringaritorno = $stringaRitorno");
		writeLog("APP",$mex,$mexFinale,$codMex);
		echo $stringaRitorno;	
	}

    ///////////////////////////////////////////////////////////////////////////////////
    //Funzione di cancellazione di un'automatismo associato ad un processo automatico//
    //////////////////////////////////////////////////////////////////////////////////
	function delAutomatismoProcesso() 
	{
	    global $context;
		$stringaRitorno='';
		$values = explode('|', $_REQUEST['vect']);
		$list = substr(join(",", $values),1); // toglie virgola iniziale
		$idEvento = $_REQUEST['idEv'];
		//trace("id evento:".$idEvento,false);
		$num = count($values)-1;
		$arrErrors=array();
		//trace("valori passati: ".print_r($values,true));
		//trace("numero. $num");
		//Delete
		$titoliLog1 = getFetchArray("SELECT TitoloAutomatismo FROM automatismo where IdAutomatismo in ($list)");
		$titoliLog2 = getFetchArray("SELECT TitoloEvento FROM eventosistema where IdEvento in ($idEvento)");
		$list="";
		for($i=1;$i<=$num;$i++)
		{
			if($i<$num)
				$list .=$titoliLog1[$i]['TitoloEvento']."-".$titoliLog2[$i]['TitoloEvento'].",";
			else
			 	$list .=$titoliLog1[$i]['TitoloEvento']."-".$titoliLog2[$i]['TitoloEvento'];
		}
		$codMex="CANC_AUTPROC";
		$mex="Cancellazione degli automatismi ($list)";
		beginTrans();
		for($i=1;$i<=$num;$i++)
		{
			$flagAzioneTipoDel=true;
			$sqlDelAutPro =  "DELETE FROM automatismoevento where IdAutomatismo=".$values[$i]." AND IdEvento=".$idEvento;
			
			if(!execute($sqlDelAutPro))
			//if(false)
			{
				$arrErrors[$i]['IdEvento']=	'cancellazione dell\'automatismo n. "'.$values[$i].'"';
				$arrErrors[$i]['Result']='E';
			} else {
				$sqlDelAuto =  "DELETE FROM automatismo where IdAutomatismo=".$values[$i];
				
			    if(!execute($sqlDelAuto))
				//if(false)
				{
					$arrErrors[$i]['IdEvento']=	'cancellazione dell\'automatismo n. "'.$values[$i].'"';
					$arrErrors[$i]['Result']='E';
				}
			  }		
		}	
	
		$messaggioErr='';
		$indiciErrori = array();
		foreach($arrErrors as $lkey=> $error){
			$indiciErrori[]=$lkey;
		}
		for($h=1;$h<=count($arrErrors);$h++)
		{
			$tindex = $indiciErrori[$h-1];
			if($arrErrors[$tindex]['Result']=='E'){
				$messaggioErr .= '<br />'.' -'.$arrErrors[$tindex]['IdEvento'];
			}
		}
		if($messaggioErr!=''){
			$stringaRitorno ="Errori almeno per la seguente cancellazione:";
			$stringaRitorno .=	$messaggioErr;
			$mexFinale=$stringaRitorno;
			rollback();
		}else{
			$mexFinale="Automatismi cancellati con successo.";
			commit();
		}
		//trace("stringaritorno = $stringaRitorno");
		writeLog("APP",$mex,$mexFinale,$codMex);
		echo $stringaRitorno;	
	}


?>