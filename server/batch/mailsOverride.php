<?php
require_once("../engineFunc.php");
require_once("../userFunc.php");
//------------------------------------------------------------------------------------
// doOverride
// Creazione delle liste di pratiche con override (forzature) e invio degli elenchi
// alle filiali
//------------------------------------------------------------------------------------
function doOverride($idCompagnia=1,$idFiliale=0)
{
	global $context;
	
	trace("Elaborazione pratiche con override per invio mail a filiali",FALSE);
	$idUtente = $context["IdUtente"];
	$fileModello = "MailOverride.json";
	
	$sortDScad = "(CASE WHEN DataScadenzaAzione IS NULL THEN '1' ELSE '0' END)";
	$fields = "v.numPratica as Pratica,v.cliente as Cliente,v.prodotto as Prodotto,v.DataDecorrenza AS \"Data decorrenza\" ,v.insoluti as \"Num. rate\",".
		"v.giorni as \"Giorni ritardo\",v.importo as Importo,v.AbbrClasse as Stato,v.Dealer,v.Responsabile as Responsabile,CONCAT('(',IdTipoSpeciale,')',TitoloTipoSpeciale) as \"Motivo override\"";
	$query = "v_insoluti_override v WHERE IdFiliale=";
	$queryForCount = "v_insoluti_override v left join filiale f on(v.idfiliale=f.idfiliale)";
	$queryForCount .= " where f.mailprincipale>'' or f.mailresponsabile>''";
	if ($idFiliale>0)
		$queryForCount .= " and v.idFiliale=$idFiliale";
	$ordine = "DataInizioAffido,$sortDScad,DataScadenzaAzione,DataScadenza,TitoloTipoSpeciale";
	
	$listF = getFetchArray("SELECT distinct v.IdFiliale FROM $queryForCount order by v.TitoloFiliale");
	//trace("lista ".print_r($listF));
	
	foreach($listF as $filiale){
		trace("Mail a filiale ".$filiale["CodFiliale"],FALSE);
		$idF=$filiale["IdFiliale"];
		$sql = "SELECT $fields FROM $query$idF ORDER BY $ordine";
		//preparazione dati con conversione in standard object
		$arr = getFetchArray($sql);
		$data = json_encode_plus($arr);
		$count = getScalar("SELECT count(*) FROM $query$idF ORDER BY $ordine");
		$resp='({"total":"' . $count . '","results":' . $data . '})';
		$ContrattiFiliale = json_decode(trim($resp,'()'));

		//preparazione array colonne
		$colonneContratti['total'] = $count;
		$colonneContratti['results'] = $arr;

		// invio mail
		preparaMail($idCompagnia,$fileModello,$idF,$ContrattiFiliale,$colonneContratti);
	}
}

//--------------------------------------------------------------------
// preparaMail
// Crea la mail per una determinata filiale, con destinari(to e cc),
// testo del modello
//--------------------------------------------------------------------
function preparaMail($idComp,$fileModello,$idF,$ContrattiFiliale,$colonneContratti,&$txt="")
{
	global $context;
	
	try
	{
		$IdUser = $context["IdUtente"];
		//parametri
		$parameters = array();
		$parameters["NOMEAUTORE"]=$context['NomeUtente'];

		//seleziona la data dell'import più recente e la mette nei parametri di sostituzione
		$sqlDataImp="select DATE_FORMAT(max(ImportTime),'%d/%m/%Y') as ImportTime from importlog where status not in('N','R') and ImportResult='U' and FileType='movimenti' and IdCompagnia=$idComp"; 
		$parameters["DATAIMPORT"] = getScalar($sqlDataImp);
		
		//crea l'attachment: dal 1/9/2011 è in formato HTML per essere visualizzabile
		//                   correttamente anche su Blackberry
		//$Allegato = creaXls($colonneContratti,$ContrattiFiliale,$idF);
		$Allegato = creaHTML($colonneContratti,$ContrattiFiliale,$idF);
		
		//seleziona le due mail a cui mandare
		$sqlDest="select IF(mailprincipale>'',mailprincipale,mailresponsabile) AS mailprincipale,
				IF(mailprincipale>'',mailresponsabile,'') AS mailresponsabile from filiale where idfiliale=$idF";
		$dest=getRow($sqlDest);

		//crea un testo seguendo il modello
		$arr = preparaEmail($fileModello,$parameters);
		$subject = $arr[0];
		$body    = $arr[1];
		
		//mail mittente
		$mailMitt = getScalar("SELECT Email FROM utente WHERE IdUtente=0".$IdUser);
		if($mailMitt!='')
			$mitt = $parameters['NOMEAUTORE']."<$mailMitt>";
		else
			$mitt = NULL;

		$txt = $subject."<br><br>".$body;   // usato per far tornare il testo della mail usato su cronprocess
		
		// Causa limitazioni del blackberry, oltre a inviare come allegato, prende la Table HTML e la aggiunge al body:
		$str = file_get_contents($Allegato['tmp_name']);
		$pos1 = stripos($str,"<table");
		$pos2 = stripos($str,"/table",$pos1);
		$pos2 = stripos($str,">",$pos2);
		$body .= "<br>".substr($str,$pos1,$pos2-$pos1);
		
		//spedisce la mail
		// dal 24/7/2014, la mail viene destinata solo agli amministratori del sistema
//		$ret = sendMail($mitt,$dest["mailprincipale"],$subject,$body,$Allegato,
//					getSysParm("MAIL_OVERRIDE","dummy").",".$dest["mailresponsabile"]);
//		writeLog("SYS","Invio mail alle filiali per pratiche con override","Inviata mail a ".$dest["mailprincipale"],"MAIL_OVERRIDE");
		$ret = sendMail($mitt,getSysParm("MAIL_OVERRIDE","dummy"),$subject,$body,$Allegato);
		writeLog("SYS","Invio mail per pratiche con override","Inviata mail a ".getSysParm("MAIL_OVERRIDE","dummy"),"MAIL_OVERRIDE");
		if(!unlink($Allegato['tmp_name']))
			trace("Errore nella cancellazione del file temporaneo: ".$Allegato['tmp_name']);
		return TRUE;
	}
	catch (Exception $e)
	{
		trace($e->getMessage());
		return FALSE;
	}
}

//--------------------------------------------------------------------
// creaXls
// Crea un allegato .xls contenente i dati dei contratti coinvolti per 
// quella filiale
//--------------------------------------------------------------------
function creaXls($colonne,$dati,$idF)
{
	$filiale=getRow("select titolofiliale from filiale where idfiliale=$idF");
	//$titolo = "Insoluti in override per ".$filiale['titolofiliale'];
	$titolo=str_replace(" ", "_", $filiale['titolofiliale']);
	$titolo=str_replace("-", "", $titolo);	
	$nomeFile="ListaOverride".$titolo."_".date("Ymd_Hi").".xls";

	$columns=array();
	$id=0;
	//trace("dati ".print_r($dati['results'][0],true));
	$chiavi=array_keys($colonne['results'][0]);
	foreach($chiavi as $elemento)
	{
		$columns[$id]['dataIndex']=$elemento;
		$columns[$id]['header']=$elemento;
		switch($elemento)
		{
			case "DataCambioStato":
				$columns[$id]['width']=100;
				break;
			case ($elemento=="Cliente" || $elemento=="Prodotto" || $elemento=="Dealer" || $elemento=="Responsabile" || $elemento=="Motivo override"): 
				$columns[$id]['width']=200;
				break;
			case ($elemento=="Pratica" || $elemento=="Importo"  || $elemento=="Stato"): 
				$columns[$id]['width']=90;
				break;
			default: 
				$columns[$id]['width']=60;
				break;
			
		}
		$id++;
	}
	//conversione in standard object
	$columns = json_encode_plus($columns);
	$columns = json_decode($columns);
	
	
	$momW = '<?xml version="1.0" encoding="utf-8"?><?mso-application progid="Excel.Sheet"?>';
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	
	$dataCreazione=date("Y-m-d\TH:i\Z");
	$momW = <<<EOT
	<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"  
				xmlns:x="urn:schemas-microsoft-com:office:excel" 
				xmlns:o="urn:schemas-microsoft-com:office:office" 
				xmlns:html="http://www.w3.org/TR/REC-html40">
		<o:DocumentProperties>
			<o:Title>$titolo</o:Title>
			<o:Created>$dataCreazione</o:Created>
		</o:DocumentProperties>
		<ss:ExcelWorkbook>
			<ss:WindowHeight>9240</ss:WindowHeight>
			<ss:WindowWidth>50000</ss:WindowWidth>
			<ss:ProtectStructure>false</ss:ProtectStructure>
			<ss:ProtectWindows>false</ss:ProtectWindows>
		</ss:ExcelWorkbook>
		<ss:Styles>
			<ss:Style ss:ID="Default" ss:Name="Normal">
				<ss:Alignment ss:Vertical="Top" ss:WrapText="0" />
				<ss:Font ss:FontName="arial" ss:Size="10" />
				<ss:Interior />
				<ss:NumberFormat />
				<ss:Protection />
				<ss:Borders />
			</ss:Style>
			<ss:Style ss:ID="headercell">
				<ss:Font ss:Bold="1" ss:Size="10" />
				<ss:Interior ss:Pattern="Solid" ss:Color="#C0C0C0" />
				<ss:Alignment ss:WrapText="0" ss:Horizontal="Center" />
			</ss:Style>
			<ss:Style ss:ID="dec">
				<ss:NumberFormat ss:Format="[$-410]#,##0.00"/>
			</ss:Style>
		</ss:Styles>
		<ss:Worksheet ss:Name="$titolo">
			<ss:Names>
				<ss:NamedRange ss:Name="Print_Titles" ss:RefersTo="='$titolo'!R1:R1" />
			</ss:Names>
EOT;
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	
	$momW = '<ss:Table x:FullRows="1" x:FullColumns="1" ss:ExpandedColumnCount="'.
				count($columns).'" ss:ExpandedRowCount="'.(1+$dati->total).'">';
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($columns as $col) {
		$momW = '<ss:Column ss:AutoFitWidth="1" ss:Width="'.$col->width.'"/>';
		$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	}
	$momW = '<ss:Row ss:AutoFitHeight="1">';
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($columns as $col) {
		$momW = '<ss:Cell ss:StyleID="headercell"><ss:Data ss:Type="String">'.$col->header.'</ss:Data><ss:NamedCell ss:Name="Print_Titles" /></ss:Cell>';
		$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	}
	$momW =  "</ss:Row>\n";
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	
	foreach ($dati->results as $row) {
		$momW = '<ss:Row>';
		$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
		foreach ($columns as $col) {
			$fld = $col->dataIndex;
			$v = $row->$fld;
			if ($col->align=='right') {
				if (preg_match("/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/", $v)) {	// numero decimale con separatori italiani
					$momW = '<ss:Cell ss:StyleID="dec">';
					$v = str_replace(',','.',str_replace('.','',$v));
				} else {
					$momW = '<ss:Cell>';
				}
				$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
				
				$momW = '<ss:Data ss:Type="Number">'.$v.'</ss:Data></ss:Cell>';
				$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
			} else {
				$momW = '<ss:Cell><ss:Data ss:Type="String"><![CDATA['.$v.']]></ss:Data></ss:Cell>';
				$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
			}
		}
		$momW = "</ss:Row>\n";
		$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND);
	}

	$momW = <<<EOT
			</ss:Table>
				  
			<x:WorksheetOptions>
				<x:PageSetup>
					<x:Layout x:CenterHorizontal="1" x:Orientation="Landscape" />
					<x:Footer x:Data="Page &amp;P of &amp;N" x:Margin="0.5" />
					<x:PageMargins x:Top="0.5" x:Right="0.5" x:Left="0.5" x:Bottom="0.8" />
				</x:PageSetup>
				<x:Print>
					<x:PrintErrors>Blank</x:PrintErrors>
					<x:FitWidth>1</x:FitWidth>
					<x:FitHeight>32767</x:FitHeight>
					<x:ValidPrinterInfo />
					<x:VerticalResolution>600</x:VerticalResolution>
				</x:Print>
				<x:FitToPage /><x:Selected />
				<x:ProtectObjects>False</x:ProtectObjects>
				<x:ProtectScenarios>False</x:ProtectScenarios>
			</x:WorksheetOptions>
		</ss:Worksheet>
	</ss:Workbook>
EOT;
	$number=file_put_contents(TMP_PATH."/$nomeFile",$momW,FILE_APPEND); 

	$File=array();
	$File["tmp_name"] = TMP_PATH."/$nomeFile";
	$File["name"] = $nomeFile;
	$File["type"] = filetype(TMP_PATH."/$nomeFile");
	return $File;
}
//--------------------------------------------------------------------
// creaHTML
// Crea un allegato .htm contenente i dati dei contratti coinvolti per 
// quella filiale
//--------------------------------------------------------------------
function creaHTML($colonne,$dati,$idF)
{
	$filiale = getScalar("select titolofiliale from filiale where idfiliale=$idF");
	//$titolo = "Insoluti in override per ".$filiale['titolofiliale'];
	$titolo   = str_replace("-", "",str_replace(" ", "_", $filiale));
	$nomeFile = "Lista_Override_".$titolo."_".date("Ymd_Hi").".xls";
	$filePath = TMP_PATH."/$nomeFile";

	$columns = array();
	$id = 0;
	$chiavi = array_keys($colonne['results'][0]);
	foreach($chiavi as $elemento)
	{
		$columns[$id]['dataIndex'] = $elemento;
		$columns[$id]['header']    = $elemento;
		$id++;
	}
	//conversione in standard object
	$columns = json_encode_plus($columns);
	$columns = json_decode($columns);
	
	$str = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"'
          .' xmlns="http://www.w3.org/TR/REC-html40">'
    	  ." <head>\n"
    	  ." <style>\n"
          ." {mso-displayed-decimal-separator:',';\n" 	
          ."  mso-displayed-thousand-separator:'.';}"
          ."</style>\n"
		  ."</head>\n"
		  ."<body>\n"
		  ."<table style='border:1px gray solid'>\n";
    if (!file_put_contents($filePath,$str,FILE_APPEND))
    {
    	trace("Errore nella scrittura del file $filePath");
    	return FALSE;
    }
	
	//---------------------------------------
	// Compone la testata della tabella HTML
	//---------------------------------------
	$str = "<thead><tr>";	
	foreach ($columns as $col) 
	{
		$str .= "<td style='background-color: lightblue;font-weight:bold'>".$col->header."</td>";
 	}
	$str .= "</tr></thead><tbody>";	
 	if (!file_put_contents($filePath,$str,FILE_APPEND))
    {
    	trace("Errore nella scrittura del file $filePath");
    	return FALSE;
    }    	
 	
	//---------------------------------------
	// Compone le righe della tabella HTML
	//---------------------------------------
	foreach ($dati->results as $row) 
	{
		$str = "\n<tr>";
		// Loop sulle colonne
		foreach ($columns as $col) 
		{
			$fld = $col->dataIndex;
			$v   = $row->$fld;
			if (is_numeric($v)) // numerico (all'americana)
				$v = str_replace(".",",",$v); // mette decimale italiano, per Excel
			$str .= "<td>".$v."</td>";
    	}   	
		$str .= "</tr>";	
		if (!file_put_contents($filePath,$str,FILE_APPEND))
    	{
    		trace("Errore nella scrittura del file $filePath");
    		return FALSE;
    	} 
	} // fine loop sulle righe	
	//---------------------------------------
	// Legenda
	//---------------------------------------
	$str = "\n<tr><td></td></tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td>"
	     . "<td colspan='2'><b>Legenda</b>:<br><br>BP = bollettini postali"
         . "<br>recid.   = recidivi (gi&agrave; andati a recupero)"
         . "<br>insoluto = &lt; a 30gg di ritardo (RID)"
         . "<br>BP 8-20  = &lt; a 20gg di ritardo (Bollettino Postale)"
	     . "</tr>";
	$str .= "</tbody></table>\n</body></html>";
	if (!file_put_contents($filePath,$str,FILE_APPEND))
    {
    	trace("Errore nella scrittura del file $filePath");
    	return FALSE;
    } 
	
    // Restituisce l'array necessario per l'allegato e-mail
 	$File=array();
 	$File['tmp_name'] = $filePath;
	$File['name'] = $nomeFile;
	$File['type'] = filetype($filePath);
	return $File;
}
?>
