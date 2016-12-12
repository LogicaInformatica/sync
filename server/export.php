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
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=\"".$titolo."_".date("Ymd_Hi").".xls\"");
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
	 	if (($dati->total+0) != $limit) break;  // se non ho letto 1000 righe, o è finito o il chiamato non è predisposto
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
			echo str_replace("<br>"," ",$col->header).';';
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
						$values[] = str_replace('.',',',$v); // mette con virgola italiana
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
	<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" 
			xmlns:x="urn:schemas-microsoft-com:office:excel" 
			xmlns:o="urn:schemas-microsoft-com:office:office" 
			xmlns:html="http://www.w3.org/TR/REC-html40">
	<o:DocumentProperties>
		<o:Title><?php echo $titolo;?></o:Title>
		<o:Created><?php echo date("Y-m-d\TH:i\Z")?></o:Created>
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
		<ss:Style ss:ID="dataFormat">
			<ss:Alignment ss:Vertical="Bottom" ss:WrapText="1"/>
			<!-- <ss:Borders>
				<ss:Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
				<ss:Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C0C0C0"/>
				<ss:Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C0C0C0"/>
				<ss:Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#C0C0C0"/>
			</ss:Borders> -->
		<ss:NumberFormat ss:Format="Short Date"/>
		</ss:Style>
	</ss:Styles>
	<ss:Worksheet ss:Name="<?php echo $titolo;?>">
		<ss:Names>
			<ss:NamedRange ss:Name="Print_Titles" ss:RefersTo="='<?php echo $titolo;?>'!R1:R1" />
		</ss:Names>
	<?php
	echo '<ss:Table x:FullRows="1" x:FullColumns="1" ss:ExpandedColumnCount="'.
			count($columns).'" ss:ExpandedRowCount="'.(1+$numRighe).'">';

			
	foreach ($columns as $col) {
		echo '<ss:Column ss:AutoFitWidth="1" ss:Width="'.$col->width.'"/>';
	}
	echo '<ss:Row ss:AutoFitHeight="1">';
	foreach ($columns as $col) {
		echo '<ss:Cell ss:StyleID="headercell"><ss:Data ss:Type="String">'.str_replace("<br>","\n",$col->header).'</ss:Data><ss:NamedCell ss:Name="Print_Titles" /></ss:Cell>';
	}
	echo "</ss:Row>\n";
	foreach ($results as $row) {
		if (count($selected)>0) {
			if (!in_array($row->IdContratto,$selected)) continue;
		}
		
		// per misteriosi motivi, solo se si mette una qualche pausa (anche un trace)
		// riesce a scrivere output molto grandi altrimenti si pianta
		//time_nanosleep ( 0 , 10*1000*1000 ); // aspetta 1 centesimo di secondo
		echo '<ss:Row>';
		foreach ($columns as $col) {
			$fld = $col->dataIndex;
			if ($fld>'') {
				$v = $row->$fld;
				if ($col->xtype=='datecolumn') {
					if($v > '') {
						$v = internetTime($v);
						if ($v>'') {
							echo '<ss:Cell ss:StyleID="dataFormat">';
							echo '<ss:Data ss:Type="DateTime"><![CDATA['.$v.']]></ss:Data></ss:Cell>';	
						} else {
							echo '<ss:Cell><ss:Data ss:Type="String"></ss:Data></ss:Cell>';
						}
					}else{
						echo '<ss:Cell><ss:Data ss:Type="String"></ss:Data></ss:Cell>';
					}
				}else if ($fld=='CAP' || $col->xtype=='string' || $v=='') { // 'string' è un xtype inventato per l'occasione
					echo '<ss:Cell><ss:Data ss:Type="String"><![CDATA['.$v.']]></ss:Data></ss:Cell>';
				}else{
					if (preg_match("/^(\d)*(\.{0,1})(\d)*$/", $v))   // è  un numero valido
						echo '<ss:Cell ss:StyleID="dec"><ss:Data ss:Type="Number">'.$v.'</ss:Data></ss:Cell>';
					else if ($col->align=='right')   // qualcosa allineato a destra?
					{
						if (preg_match("/^((\d{1,3}\.(\d{3}\.)*\d{3}|\d{1,3}),\d+)$/", $v)) {	// numero decimale con separatori italiani
							echo '<ss:Cell ss:StyleID="dec">';
							$v = str_replace(',','.',str_replace('.','',$v));
							echo "<ss:Data ss:Type=\"Number\">$v</ss:Data></ss:Cell>";
						} else if (preg_match("/^(\d)+,(\d)+$/", $v)) { // numero con virgola (non ok per Excel XML)
							$v = str_replace(',','.',$v);
							echo '<ss:Cell ss:StyleID="dec"><ss:Data ss:Type="Number">'.$v.'</ss:Data></ss:Cell>';
						} else  
							echo '<ss:Cell><ss:Data ss:Type="String"><![CDATA['.$v.']]></ss:Data></ss:Cell>';
						 
					} else {
						echo '<ss:Cell><ss:Data ss:Type="String"><![CDATA['.$v.']]></ss:Data></ss:Cell>';
					}
				}
			}
		}
		echo "</ss:Row>\n";
		flush();
		ob_flush();
	}
	?>
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
	<?php 
}
catch (Exception $e)
{
	trace("Errore nell'export: ".$e->getMessage(),FALSE,TRUE);
//	echo $e->getMessage();
}
?>