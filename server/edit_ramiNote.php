<?php
require_once("userFunc.php");
require_once("workflowFunc.php");
$task = ($_POST['task']) ? ($_POST['task']) : null;
//trace("Entrato edit_ramiNote.php con task=$task",false);

$schema = $_POST['schema'] ? $_POST['schema'] : MYSQL_SCHEMA;


switch($task){
	case "read":
		readRami();
		break;
	case "readFasciaCosto":
		readFasciaCosto();
		break;
	case "readTree":
		creaTree();
		break;
	case "export": // chiamato per esportare la lista note di un contratto
		export();
		break;
}

function creaTree()
{
	global $context,$schema;
	// prendo iduser corrente
	$IdUser = $context["IdUtente"];
	$RepUser = $context["IdReparto"];
	
	if (!$IdUser or !$RepUser) return "[]"; // 20/7/2016: aggiunto per evitare msg errore in perdita contesto
	
	$idPratica = ($_POST['IdPratica']) ? ($_POST['IdPratica']) : null;
	
	$sql = "SELECT * FROM $schema.nota where idContratto=$idPratica and TipoNota in ('C','N') and idNotaPrecedente is null";
		  
	$recLetti = getFetchArray($sql);
	$children = '[';
	$flagMultiConv=false;
	
	if (count($recLetti)>0)
	{
	  $i = count($recLetti);
	  foreach ($recLetti as $rec) 
	  {
	    $children .= creaNodi($rec['IdNota'],$flagMultiConv);
	    execute("REPLACE INTO $schema.notautente (IdNota,IdUtente,IdReparto) SELECT IdNota,$IdUser,$RepUser FROM $schema.nota where IdNota=".$rec['IdNota']);
		if($i>1)
		{
			$children .= ',';
		}
		$i = $i -1;
	  }
				
	}
	$children .= ']';		
	
	// aggiorna la tabella di ottimizzazione
	execute("REPLACE INTO $schema._opt_note_lette SELECT DISTINCT IdReparto,IdNota FROM $schema.notautente WHERE LastUpd>NOW() - INTERVAL 30 SECOND");
	
	//trace("figli ".print_r($children,true));
	echo $children;
}


function creaNodi($IdNodo,$flagMultiConv) {
		
		global $context,$schema;
		// prendo iduser corrente
		$IdUser = $context["IdUtente"];
		$RepUser = $context["IdReparto"];
		$destinatario='';
		$mittente='';
		$dataOra = date('d/m/y H.i');
		
		$sql = "SELECT IdNota from $schema.nota where idNotaPrecedente=$IdNodo"; 
		$recLetti = getFetchArray($sql); // leggo i figli del nodo
		
		$sqlMeStesso = "SELECT * from $schema.nota where IdNota=$IdNodo order by IdNota"; 
		$recMe = getRow($sqlMeStesso);
		$dataOra=$recMe['DataCreazione'];
		$dataOra = strtotime($dataOra);
		$dataOra = date('d/m/y H.i',$dataOra);
		switch ($recMe['TipoNota']) {
			case "N":	// nota
				$destinatario=' a Tutti';
				$icona = 'note_post_it';
				break;
			case "C":	// messaggio
				$icona = 'con_note';
				if($recMe['IdUtenteDest']!=''){
					$sqlDest = 'select Userid from utente where idutente='.$recMe['IdUtenteDest'];
					$dest=getRow($sqlDest);
					$destinatario=' a '.$dest['Userid'];
				}
				break;
		}
		
		if($recMe['IdUtente']!=''){
			$sqlMitt = 'select Userid from utente where idutente='.$recMe['IdUtente'];
			$mitt=getRow($sqlMitt);
			$mittente=' da '.$mitt['Userid'];
		}
			
		if ((count($recLetti))>0)
		{
			$flagMultiConv=true;
			$children = '[';
			$i = count($recLetti);
			foreach ($recLetti as $rec) 
			{
				$children .= creaNodi($rec['IdNota'],$flagMultiConv);
				execute("REPLACE INTO $schema.notautente (IdNota,IdUtente,IdReparto) SELECT IdNota,$IdUser,$RepUser FROM $schema.nota where IdNota=".$rec['IdNota']);
				if($i>1)
				{
					$children .= ',';
				}
				$i = $i -1;
			}
			$children .= ',{"id":"000'.$recMe['IdNota'].'","text":"Rispondi","iconCls":"arrow_redo","cls":"file","leaf":true,"children":null}';
			//$children .= ',{"id":"'.$recMe['IdNota'].'add","text":"Rispondi","iconCls":"arrow_redo","cls":"file","leaf":true,"children":null}';
			$children .= ']';
			/*if($recMe['TipoNota']=='C'){
				$icona = 'con_note';
			}else{
				$icona = 'annotazioni';
			}*/
			//trace("childrenMULTIPLO $children");
			//trace("TXTMULTILO ".$recMe['TestoNota']);
			
			$recMe['TestoNota'] = '<b>[</b><i>'.$dataOra.''.$mittente.''.$destinatario.'</i><b>]</b> '.str_replace("\n","<br>" ,$recMe['TestoNota']);	
			$nodo = '{"id":"'.$recMe['IdNota'].'","text":"'.addslashes($recMe['TestoNota']).'","iconCls":"'.$icona.'","cls":"file","leaf":false,"children":'.$children.'}';
		}
		else
		{
			if($recMe['TipoNota']=='C'){
				$icona = 'con_note';
			}else{
				$icona = 'note_post_it';
			}
			
			if(!$flagMultiConv){
				//$children .= '[{"id":"'.$recMe['IdNota'].'add","text":"Rispondi","iconCls":"arrow_redo","cls":"file","leaf":true,"children":null}]';
				$children .= '[{"id":"000'.$recMe['IdNota'].'","text":"Rispondi","iconCls":"arrow_redo","cls":"file","leaf":true,"children":null}]';
				$leaf = "false";				
			}else{
				$icona = 'empty_ico';
				$children .= 'null';
				$leaf = "true";
			}
			//trace("children $children");
			//trace("TXT ".$recMe['TestoNota']);
			$recMe['TestoNota'] = '<b>[</b><i>'.$dataOra.''.$mittente.''.$destinatario.'</i><b>]</b> '.str_replace("\n","<br>" ,$recMe['TestoNota']);	
			$nodo = '{"id":"'.$recMe['IdNota'].'","text":"'.addslashes($recMe['TestoNota']).'","iconCls":"'.$icona.'","cls":"file","leaf":'.$leaf.',"children":'.$children.'}';
		}

		// aggiorna la tabella di ottimizzazione
		execute("REPLACE INTO $schema._opt_note_lette SELECT DISTINCT IdReparto,IdNota FROM $schema.notautente WHERE LastUpd>NOW() - INTERVAL 30 SECOND");
		
		return $nodo;
}


function readRami($nodo)
{
	global $schema;
	try
	{	
		
		$idCompagnia = ($_POST['IdCompagnia']) ? ($_POST['IdCompagnia']) : null;
		$idSinistro  = ($_POST['idSinistro']) ? ($_POST['idSinistro']) : null;
		$where = "";
		
		// se arriva una richiesta di lettura dei rami (tutti senza specificare una compagnia)
		if(!($idCompagnia<=0 &&$idSinistro<=0))
		{
			if($idCompagnia =="" || $idCompagnia ==null)
			{
				$idCompagnia = getScalar("select IdCompagnia from sinistro where IdSinistro =".$idSinistro);	
				$where =  " WHERE IdCompagnia =$idCompagnia";
			}
		}
		
		$sql = "SELECT IdRamo, Ramo FROM v_ramocompagnia $where  order by Ramo ASC";
	
		$arr = getFetchArray($sql);
	
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
	       
		echo $cb . '({"results":' . $data . '})';
	}
	catch (Exception $e)
	{
		trace("Errore nella lettura del ramo: ".$e->getMessage());
		echo  json_encode_plus(array("success"=>false,"msg"=>$e->getMessage()));	
	}
}


function readFasciaCosto()
{
	global $schema;
	try
	{	
		
		$idSinistro  = ($_POST['idSinistro']) ? ($_POST['idSinistro']) : null;
		
		$idRamo = ($_POST['IdRamo']) ? ($_POST['IdRamo']) : null;
		
		if($idRamo =="" || $idRamo ==null)
		{
			$idRamo = getScalar("select IdRamo from sinistro where IdSinistro =".$idSinistro);	
		}
		
		$sql = 'SELECT FasciaDiCosto, IdFasciaDiCosto FROM v_fasciadicosto_ramo WHERE IdRamo ='. $idRamo;	
		
		$arr = getFetchArray($sql);
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
	       
		echo $cb . '({"results":' . $data . '})';
	}
	catch (Exception $e)
	{
		trace("Errore nella lettura della fascia di costo per il ramo con id=$idRamo: ".$e->getMessage());
		echo  json_encode_plus(array("success"=>false,"msg"=>$e->getMessage()));	
	}
}

function insertTree($idRamo,$idFamiglia,$nodo){
	$GLOBALS["myProgr"]=$GLOBALS["myProgr"]+1;
	$sql = "Insert into tree (idramo,idfamigliaramo,ordine,nodoFile,livello) "
		  ."values ($idRamo,$idFamiglia,".$GLOBALS["myProgr"].",'$nodo',".$GLOBALS["livello"].")";

	execute($sql);
}

function delTree(){
	$sql = "delete from tree";
	execute($sql);
}
//**************************************************************
// export
// Crea i dati per l'export della lista note di un contratto
// (chiamato da printer-all.js)
//**************************************************************
function export() {
	global $schema;
	
	$arr = getFetchArray("SELECT * FROM $schema.v_note_per_export WHERE IdContratto=".$_REQUEST["IdPratica"]." ORDER BY DataOra ASC");
	$data = json_encode_plus($arr);  
	
	echo '({"total":"' . (count($arr)) . '","results":' . $data . '})';
}
?>