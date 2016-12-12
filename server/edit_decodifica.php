<?php
require_once("common.php");

$task = ($_POST['task']) ? ($_POST['task']) : null;
$table = ($_POST['table']) ? ($_POST['table']) : '';

//switchboard for the CRUD task requested
switch($task){
    case "init":
        initJS();
        break;
/*
    case "create":
        addData();
        break;
*/
    case "readTable":
        showData($table);
        break;
    case "update":
        saveData();
        break;
    case "delete":
        removeData();
        break;
    default:
    	echo "{failure:true}$task";
        break;
}//end switch

function initJS() {
    global $table;
	
	switch($table){
	    case "statocontratto":	// Codici di stato del contratto
			echo <<<EOF
	var recStatoContratto = Ext.data.Record.create([
		{name: 'IdStatoContratto', type: 'int', allowBlank:false},
		{name: 'CodStatoContratto', allowBlank:false},		// Codice abbreviato dello stato, usato in tutti i casi in cui il testo completo della definizione è troppo lungo per la visualizzazione.
		{name: 'TitoloStatoContratto', allowBlank:false},
		{name: 'CodStatoLegacy', allowBlank:false},			// Codice stato corrispondente sul sistema legacy (se applicabile)
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);

	var readerStatoContratto = new Ext.data.JsonReader ({
		root: 'results',
		totalProperty: 'total', 
		//groupField: '...',
		//sortInfo: {field: '...', direction: 'ASC'},
		remoteSort: true,
		id: 'IdStatoContratto'		// optional
        },
        recStatoContratto 
	);
	
	//        var ds = new Ext.data.GroupingStore({ //if grouping
	var ds = new Ext.data.Store({ //if not grouping
		proxy: new Ext.data.HttpProxy({
			url: 'my-grid-editor-mysql-php.php', //url to data object (server side script)
			method: 'POST'
			}),   
		baseParams:{
			task: "readTable", 
			table: "statocontratto"
		},
		reader: readerStatoContratto,
		//groupField:'...',
		//sortInfo:{field: 'CodStatoContratto', direction: "ASC"},
		//remoteSort: true //true if sort from server (false = sort from cache only)
	});
	
EOF;
		break;
	    default:
	        echo "{failure:true}";
	        break;
	}//end switch
}


function showData($table) {
     /* By specifying the start/limit params in ds.load 
      * the values are passed here
      * if using ScriptTagProxy the values will be in $_GET
      * if using HttpProxy      the values will be in $_POST (or $_REQUEST)
      * the following two lines check either location, but might be more
      * secure to use the appropriate one according to the Proxy being used
      */
	$start = isset($_POST['start']) ? (integer)$_POST['start'] : (isset($_GET['start'])? (integer)$_GET['start'] : '');
	$end =   isset($_POST['limit']) ? (integer)$_POST['limit'] : (isset($_GET['limit'])? (integer)$_GET['limit'] : '');

	$sql = "SELECT * FROM $table";

	if (isset($_POST['sort'])) {
		$sql .= ' ORDER BY ' . $_POST['sort'] . ' ' . $_POST['dir'];
	}

	if ($start!='' || $end!='') {
    		$sql .= ' LIMIT ' . (integer)$start . ', ' . (integer)$end;
	}

	$counter = getScalar("SELECT count(*) FROM $table");
	if ($counter == NULL)
		$counter = 0;
	if ($counter == 0) {
		$arr = array();
	} else {
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

}//end showData



function getData($table) 
{
	$arr = getFetchArray("SELECT * FROM $table");
    
    if (version_compare(PHP_VERSION,"5.2","<"))
    {    
        require_once("./JSON.php"); //if php<5.2 need JSON class
        $json = new Services_JSON();//instantiate new json object
        $data=$json->encode($arr);  //encode the data in json format
    } else
    {
        $data = json_encode_plus($arr);  //encode the data in json format
    }

    /* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified*/
    $cb = isset($_GET['callback']) ? $_GET['callback'] : '';
       
     echo $cb . '({"results":' . $data . '})';

}//end getData

                
function saveData()
{
    global $table;
	global $connection;
	global $context;
	/*
     * $key:   db primary key label
     * $id:    db primary key value
     * $field: column or field name that is being updated (see data.Record mapping)
     * $value: the new value of $field
     */ 
    
    $key = $_POST['key'];
	$newRecord = ($_POST['keyValue'] == 0?'yes':'no'); 
   	$fields = $_POST['fields'];
	//trace("dentro a saveData della editGrid key:".$key.", id:".$id.", fields:".$fields);
    if ($newRecord == 'yes'){
	   //	eval("\$valori = array(".stripslashes($_POST['values']).");");
	    $valori = explode('|',$_POST['values']);
//	   	trace("1: ".$_POST['values'],FALSE);

		if (stripos($fields,"LastUser")===FALSE)
		{
	   		$values = implode(",", quote_smart_deep($valori)) . "," . quote_smart($context['Userid']);
			$query = "INSERT INTO `$table` ($fields,LastUser) VALUES ($values)";
		}
		else
		{
	   		$values = implode(",", quote_smart_deep($valori));
			$query = "INSERT INTO `$table` ($fields) VALUES ($values)";
		}
    } else {
    	$id = quote_smart($_POST['keyValue']);
    	$v = $_POST['values']==''?"null":quote_smart($_POST['values']);
    	$query = "UPDATE `$table` SET `$fields` = $v, LastUser=".quote_smart($context['Userid'])."  WHERE `$key` = $id";
    }

 	if (execute($query))
	{
		if($newRecord == 'yes')
		{
           	$newID = getInsertId();
			echo "{success:true, newID:".$newID."}";	// torna la chiave del record inserito
       	} 
       	else
       	{
           	echo "{success:true}";
       	}
   	} 
   	else 
   	{
  		$tipoErr = "select * from `$table` WHERE `$key` = $id";
   		if(execute($tipoErr))
   		{
   			echo "{success:false, error:\"I dati sono stati cancellati da una terza parte durante l'attuale operazione di aggiornamento.\"}";
   		}
   		else
   		{
   			echo "{success:false, error:\"".getLastError()."\"}"; //if we want to trigger the false block we should redirect somewhere to get a 404 page
   		}
   	}
}//end saveData


// Elimina i records dalla tabella $table
// se $table inizia per 'v_' è una vista e la vera tabella da cui eliminare i records
// è data da quello che segue v_
function removeData()
{
    /*
     * $key:   db primary key label
     * $id:    db primary key value
     */ 

    global $table;
    $key = $_POST['key'];
    $arr    = $_POST['id'];
    $count = 0;

	$pos_v = stripos($table, 'v_');
	if ($pos_v !== false && $pos_v == 0)
    	$table = substr($table,2);
    	
    $selectedRows = json_decode(stripslashes($arr));//decode the data from json format

    $conn = getDbConnection();
    if ($conn) {
	    //should validate and clean data prior to posting to the database
    	foreach($selectedRows as $row_id)
	    {
    	    $id = (integer) $row_id;
        	$query = 'DELETE FROM '.$table.' WHERE `'.$key.'` = '.$id;
        	if (execute($query))
        		$count++;
        	else
				trace("Risultato della query  $sql: ".getLastError());
    	}
    }

    /* If using ScriptTagProxy:  In order for the browser to process the returned
       data, the server must wrap te data object with a call to a callback function,
       the name of which is passed as a parameter by the ScriptTagProxy. (default = "stcCallback1001")
       If using HttpProxy no callback reference is to be specified*/
    $cb = isset($_GET['callback']) ? $_GET['callback'] : '';
           
    $response = array('success'=>($count == count($selectedRows)), 'del_count'=>$count);
	$json_response = json_encode_plus($response);

    echo $cb . $json_response;
}//end removeData
?>