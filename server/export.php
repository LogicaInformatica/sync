<?php
require_once("common.php");
try { 
	// NOTA BENE: se il php gira in modalità sicura, le due istruzioni sottostanti non hanno effetto
	set_time_limit(1000); // aumenta il tempo max di cpu
	ini_set('max_execution_time','300');
	$titolo = $_REQUEST['filename'];
	// toglie parte HTML (nota) dal titolo
	$titolo = substr($titolo,0,strpos($titolo."<","<"));
	$titolo = str_replace(":"," ",$titolo); // carattere non ammesso nel nome worksheet
	
	// Decide se esportare in Excel o CSV, in base al parametro di sistema e al settaggio eventuale in tabella
	// whitelist
	$exportFormatDefault = getSysParm('EXPORT_FORMAT','XLS');
	$exportFormatSpecific = getScalar("SELECT exportFormat FROM whitelist WHERE ipaddress='{$_SERVER['REMOTE_ADDR']}'",true);
	$exportFormat = $exportFormatSpecific>''?$exportFormatSpecific:$exportFormatDefault;
	
	if ($exportFormat=='CSV') {
		trace("export.php emette header per output csv",false);
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"".$titolo."_".date("Ymd_Hi").".csv\"");
	} else {
		trace("export.php emette header per output excel",false);
		mb_internal_encoding('UTF-8');
		header("Content-type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=\"".$titolo."_".date("Ymd_Hi").".xls\"");
		echo "\xEF\xBB\xBF";
	}
	// Se viene passato il parametro 'xls', si deve solo rimandare indietro con l'header impostato
	if (isset($_REQUEST['xls'])) {
		echo stripcslashes($_REQUEST['xls']);
		exit;
	}

/*------------------------------------------------------------------------------------
 * Senza xls: il file va generato lato server leggendo i dati sul db
 * Riceve il titolo, un array con le colonne della griglia, l'url della pagina php
 * che effettuerà la lettura (la stessa richiamata normalmente copn ajax) e
 * l'oggetto baseParams da passare alla pagina php
 *
 * Le colonne sono oggetti con i seguenti attributi:
 * 		xtype, align, dataIndex, format, header, width
 *-----------------------------------------------------------------------------------*/ 
	$titolo = $_REQUEST['titolo'];
	$titolo  = str_replace("/","-",$titolo); // la barra dà errore in Excel
	if (get_magic_quotes_gpc()) {
		$columns = json_decode(stripslashes($_REQUEST['columns']));
		$baseParams = json_decode(stripslashes($_REQUEST['baseParams']));
	} else	{
		$columns = json_decode($_REQUEST['columns']);
		$baseParams = json_decode($_REQUEST['baseParams']);
	}

	// Chiamata alla pagina php di lettura; l'output viene assegnato a un buffer
	// L'oggetto json ritornato deve avere i due elementi (standard per noi) 'total'
	// e 'results'
	$url = '../'.$_REQUEST['url'];
	trace("export richiama l'url $url con parametri ".$_REQUEST['baseParams'],false);
	ob_start();  // fa in modo che l'output vada in un buffer
	include($url);   // include (ed esegue) la pagina, ma senza parametri (per cui non restituisce niente di utile: serve solo
                 // a caricare le funzioni)
	ob_clean(); // elimina l'output eventuale


	// prepara il vettore globale utilizzato
	$exportingToExcel = TRUE;
	foreach ($baseParams as $k => $v) 
	$_REQUEST[$k] = $v;

	$numRighe = 0;
	$results  = array();
	ob_start();  // fa in modo che l'output vada in un buffer
	$limit = 2000;
	$functions = array("doMain","read","export"); // funzioni che tenta di chiamare per ottenere la griglia
	for ($from=0;;$from+=$limit) // mille righe per volta, altrimenti può dare errore
	{
		$exportFrom = $from;
		$exportLimit = $limit;
		$dati = "";
		foreach ($functions as $function)
		{
			if (function_exists($function))
			{
				try {
					eval($function."();");
				} catch (Exception $e) {
					trace($e->getMessage());
				}			
				break;
			}
		}
		$dati = ob_get_contents();	 	// legge il buffer
		ob_clean();                  	// e lo svuota
		$dati = json_decode(trim($dati,'()')); // toglie le parentesi che racchiudono il risultato e trasforma in struttura
		if (!($dati->total>"0")) break;
		$numRighe += $dati->total;
		$results = array_merge($results,$dati->results);
	 	if (($dati->total+0) != $limit) break;  // se non ho letto 1000 righe, o � finito o il chiamato non � predisposto
	                                             	     // e quindi ha letto tutte le righe
	}
	ob_end_clean();           

	// Il parametro selected, se non vuoto contiene l'array degli IdContratto selezionati
	if ($_REQUEST['selected']>'') {
		$selected = json_decode($_REQUEST['selected'],true);
		trace("Richiesta estrazione dei contratti ".print_r($sqlected,true),false);
	} else {
		$selected = array();
	}
	
	if ($exportFormat=='CSV') {
		trace("Produzione riga degli header di colonna",false);
		// Riga con gli header di colonna
		foreach ($columns as $col) {
			echo str_replace("<br>"," ",html_entity_decode(convertToUTF8($col->header))).';';
		}
		echo "\n";
		
		trace("Produzione ed eventuale filtraggio di ".count($results)." righe di dettaglio",false);
		foreach ($results as $row) {
			if (count($selected)>0) {
				if (!in_array($row->IdContratto,$selected)) continue;
			}
			$values = array();
			foreach ($columns as $col) {
				$fld = $col->dataIndex;
				$v = $row->$fld;
				if ($col->xtype=='datecolumn') {
					if($v > '') {
						$v = internetTime($v);
						if ($v>'') {
							$values[] = str_replace("T"," ",$v); // toglie la T del formato UTC
						} else {
							$values[] = "";
						}
					}else{
						$values[] = "";
					}
				}else if ($fld=='CAP' || $col->xtype=='string' || $v=='') { // 'string' è un xtype inventato per l'occasione
					$values[] = '"'.str_replace('"','""',$v).'"';
				}else{
					if (preg_match("/^(\d)*(\.{0,1})(\d)*$/", $v)) {  // è  un numero valido formato inglese
					 	if (preg_match('/(iva|cod.*fisc|c\..*f\.)/i',$fld) and preg_match('/^[0-9]{11}$/',$v) ) { // è una partita IVA
							$values[] = '"'.html_entity_decode('&nbsp;').str_replace('"','""',$v).'"'; //mette un nbsp in testa per evitare che Excel formatti il campo come numerico
 						} else { // non e' una partita IVA 
					 		$values[] = str_replace('.',',',$v); // mette con virgola italiana
 						}						
					} else if ($col->align=='right') { // qualcosa allineato a destra?
						if (preg_match("/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/", $v)) {	// numero decimale con separatori italiani
							$v = str_replace('.','',$v); // salva con virgola italiana ma senza separatore di migliaia
							$values[] = $v;
						} else if (preg_match("/^(\d)+,(\d)+$/", $v)) { // numero con virgola  
							$values[] = $v;
						} else {
							$values[] = '"'.str_replace('"','""',$v).'"';
						}		
					} else {
						$values[] = '"'.str_replace('"','""',$v).'"';
					}
				}
			}
			echo implode(';',$values)."\n";
			flush();
			ob_flush();
		}
		die(); // fine generazione formato csv
	}
		
	// Continua con l'export Excel 
	echo '<?xml version="1.0" encoding="utf-8"?><?mso-application progid="Excel.Sheet"?>';
	?>
	<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
			xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
			xmlns:x="urn:schemas-microsoft-com:office:excel" 
			xmlns:o="urn:schemas-microsoft-com:office:office" 
			xmlns:html="http://www.w3.org/TR/REC-html40">
	<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
		<Title><?php echo $titolo;?></Title>
		<Created><?php echo date("Y-m-d\TH:i:s\Z")?></Created>
	</DocumentProperties>
	<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
		<WindowHeight>9240</WindowHeight>
		<WindowWidth>50000</WindowWidth>
		<WindowTopX>0</WindowTopX>
        <WindowTopY>0</WindowTopY>
		<ProtectStructure>False</ProtectStructure>
		<ProtectWindows>False</ProtectWindows>
	</ExcelWorkbook>
	<Styles>
		<Style ss:ID="Default" ss:Name="Normal">
			<Alignment ss:Vertical="Top"/>
			<Font ss:FontName="arial"/>
			<Interior />
			<NumberFormat />
			<Protection />
			<Borders />
		</Style>
		<Style ss:ID="headercell">
			<Font ss:FontName="Arial" ss:Bold="1"/>
			<Interior ss:Pattern="Solid" ss:Color="#C0C0C0" />
			<Alignment ss:Vertical="Bottom" ss:Horizontal="Center" />
		</Style>
		<Style ss:ID="dec">
			<NumberFormat ss:Format="[$-410]#,##0.00"/>
		</Style>
		<Style ss:ID="dataFormat">
			<Alignment ss:Vertical="Bottom" ss:WrapText="1"/>
			<NumberFormat ss:Format="Short Date"/>
		</Style>
	</Styles>
	<Worksheet ss:Name="<?php echo $titolo;?>">
		<Names>
			<NamedRange ss:Name="Print_Titles" ss:RefersTo="='<?php echo $titolo;?>'!R1" />
		</Names>
	<?php
	echo '<Table x:FullRows="1" x:FullColumns="1" ss:ExpandedColumnCount="'.
			count($columns).'" ss:ExpandedRowCount="'.(1+$numRighe).'">';

			
	foreach ($columns as $col) {
		echo '<Column ss:AutoFitWidth="1" ss:Width="'.$col->width.'"/>';
	}
	echo '<Row ss:AutoFitHeight="1">';
	foreach ($columns as $col) {
		echo '<Cell ss:StyleID="headercell"><Data ss:Type="String">'.str_replace("<br>","\n",htmlspecialchars(html_entity_decode(convertToUTF8($col->header)))).'</Data><NamedCell ss:Name="Print_Titles" /></Cell>';
		echo "\n";
	}
	echo "</Row>\n";
	foreach ($results as $row) {
		if (count($selected)>0) {
			if (!in_array($row->IdContratto,$selected)) continue;
		}
		
		// per misteriosi motivi, solo se si mette una qualche pausa (anche un trace)
		// riesce a scrivere output molto grandi altrimenti si pianta
		//time_nanosleep ( 0 , 10*1000*1000 ); // aspetta 1 centesimo di secondo
		echo "\n";
		echo '<Row>';
		foreach ($columns as $col) {
			$fld = $col->dataIndex;
			if ($fld>'') {
				$v = convertToUTF8($row->$fld);
				if ($col->xtype=='datecolumn') {
					if($v > '') {
						$v = internetTime($v);
						if ($v>'') {
							echo '<Cell ss:StyleID="dataFormat">';
							echo '<Data ss:Type="DateTime">'.$v.'</Data></Cell>';	
						} else {
							echo '<Cell><Data ss:Type="String"></Data></Cell>';
						}
					}else{
						echo '<Cell><Data ss:Type="String"></Data></Cell>';
					}
				}else if ($fld=='CAP' || $col->xtype=='string' || $v=='') { // 'string' è un xtype inventato per l'occasione
					echo '<Cell><Data ss:Type="String">'.htmlspecialchars(html_entity_decode($v)).'</Data></Cell>';
				}else{
					if (preg_match("/^(\d)*(\.{0,1})(\d)*$/", $v)) {  // è  un numero valido
 						if (preg_match('/(iva|cod.*fisc|c\..*f\.)/i',$fld) and preg_match('/^[0-9]{11}$/',$v) ) { // è una partita IVA
							echo '<Cell><Data ss:Type="String">'.$v.'</Data></Cell>';
 						} else { // non e' una partita IVA 
							echo '<Cell ss:StyleID="dec"><Data ss:Type="Number">'.$v.'</Data></Cell>';
						}
					} else if ($col->align=='right')   // qualcosa allineato a destra?
					{
						if (preg_match("/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/", $v)) {	// numero decimale con separatori italiani
							echo '<Cell ss:StyleID="dec">';
							$v = str_replace(',','.',str_replace('.','',$v));
							echo "<Data ss:Type=\"Number\">$v</Data></Cell>";
						} else if (preg_match("/^(\d)+,(\d)+$/", $v)) { // numero con virgola (non ok per Excel XML)
							$v = str_replace(',','.',$v);
							echo '<Cell ss:StyleID="dec"><Data ss:Type="Number">'.$v.'</Data></Cell>';
						} else  
							echo '<Cell><Data ss:Type="String">'.htmlspecialchars(html_entity_decode($v)).'</Data></Cell>';
						 
					} else {
						echo '<Cell><Data ss:Type="String">'.htmlspecialchars(html_entity_decode($v)).'</Data></Cell>';
					}
				}
			}
		}
		echo "</Row>\n";
		flush();
		ob_flush();
	}
	?>
		</Table>
			  
		<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
			<PageSetup>
				<Layout x:CenterHorizontal="1" x:Orientation="Landscape" />
				<Footer x:Data="Page &amp;P of &amp;N"/>
				<PageMargins x:Top="0.5" x:Right="0.5" x:Left="0.5" x:Bottom="0.8" />
			</PageSetup>
			<FitToPage />
			<Print>
				<PrintErrors>Blank</PrintErrors>
				<FitHeight>32767</FitHeight>
				<ValidPrinterInfo />
				<VerticalResolution>600</VerticalResolution>
			</Print>
			<Selected />
			<ProtectObjects>False</ProtectObjects>
			<ProtectScenarios>False</ProtectScenarios>
		</WorksheetOptions>
	</Worksheet>
	</Workbook>
	<?php 
}
catch (Exception $e)
{
	trace("Errore nell'export: ".$e->getMessage(),FALSE,TRUE);
//	echo $e->getMessage();
}
?>