<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione con data"
// Contenuto: campo data (da inserire in scadenzario), note e pulsanti Conferma / Annulla

$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
//$dataVendita = $azione["DataVendita"];
$IdContratto = $idsArray[0];
$chkHidden = false;
if(rowExistsInTable("nota","IdContratto=$IdContratto and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;

$dataForm=getRow("select v.*,if (year('2012-04-01'-interval 3 month)>=year(curdate()),'Y','N') AS inFY from v_pratiche v WHERE IdContratto=$IdContratto");
$dataDBT  	= date('d/m/Y',dateFromString($dataForm['DataDBT']));
$importoDBT = $dataForm['ImpDBT']>0? number_format($dataForm['ImpDBT'],2,',','.'): '-';
$debito = $dataForm['ImpCapitale']+$dataForm['ImpAltriAddebiti'];
//$incassato  = $dataForm['ImpDBT']>$debito ? number_format($dataForm['ImpDBT']-$debito,2,',','.'):'-';
$debito     = number_format($debito,2,',','.');
// mostra gli interessi di mora (del DBT!) solo se sono ancora a debito
$interessi  = ($dataForm['inFY']=='Y' && $dataForm['ImpInteressiMoraAddebitati>0'])? $dataForm['ImpInteressiMaturati']:'-';

// Prepara la descrizione dell'eventuale piano di rientro
$pianorientro = getScalar("SELECT CONCAT(CASE IdStatoPiano WHEN 1 THEN 'proposto: ' WHEN 2 THEN '' ELSE 'respinto: ' END,'dal ',DATE_FORMAT(DataIni,'%d/%m/%y')
			,': ',REPLACE(PrimoImporto,'.',','),' &euro;+',NumeroRate,' rate da ',REPLACE(ImportoRata,'.',','),' &euro;') 
			FROM pianorientro WHERE IdContratto=$IdContratto ORDER BY 1 dESC LIMIT 1");
if (!$pianorientro)
	$pianorientro = "NO";

// Prepara la descrizione dell'eventuale saldo e stralcio
$saldostralcio = $dataForm["ImpSaldoStralcio"];
if (!$saldostralcio)
	$saldostralcio = 'NO';
else
{
	$saldostralcio = 'saldo = '.str_replace('.',',',$saldostralcio)
	.' - residuo = '.str_replace('.',',',($dataForm["ImpInsoluto"]-$dataForm["ImpSaldoStralcio"]));
	// Determina se è una richiesta o proposta e se è normale o differito
	$tipoSS = getScalar("SELECT CASE WHEN CodAzione LIKE 'WF%AUT%SS'  THEN ''
            WHEN CodAzione LIKE 'WF%SS'      THEN 'proposta: '
            WHEN CodAzione LIKE 'WF%AUT%SSD' THEN 'differito: '
            WHEN CodAzione LIKE 'WF%SSD'     THEN 'prop. differito: '
            WHEN CodAzione = 'RSS'           THEN 'richiesta: '
            WHEN CodAzione = 'RSD'           THEN 'rich. differito: '
            ELSE '' END
			FROM storiarecupero s JOIN azione a ON a.IdAzione=s.IdAzione
			WHERE CodAzione LIKE 'WF%SS' or CodAzione LIKE 'WF%SSD' OR CodAzione IN ('RSS','RSD')
			AND IdContratto=$IdContratto ORDER BY IdStoriaRecupero DESC LIMIT 1");
	$saldostralcio = $tipoSS.$saldostralcio;
}

if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
{
	$dataLimite = $dataForm["DataFineAffido"];
	if ($dataLimite==NULL)
		$dataLimite = '9999-12-31';
	else
		$dataLimite = ISODate($dataLimite);
}	
else
	$dataLimite = '9999-12-31';

$titolo = "Pratica n. ".$dataForm['CodContratto']." - ".$dataForm["NomeCliente"];

$idProcedura = getScalar("SELECT IdProcedura FROM azioneprocedura WHERE IdAzione=".$azione["IdAzione"]);
$nota = getScalar("select NotaEvento from storiarecupero where IdContratto=$IdContratto AND". 
                  " IdAzione in (SELECT IdAzione FROM azioneprocedura WHERE IdProcedura=0$idProcedura)".
                  " GROUP BY IdStoriaRecupero DESC LIMIT 1");
	
$dealer = $dataForm["Venditore"];
$idCliente = $dataForm["IdCliente"];

// Calcola numero rate pagate (quelle con data di scadenza passata e saldo OK non abbuonate o stornate)
$numRatePagate = calcolaNumRatePagate($IdContratto);

$sql = "SELECT CodContratto,concat(NomeAgenzia,' (',CodRegolaProvvigione,')') as Agenzia, DataFineAffido, ImpInsoluto" 
       ." FROM v_pratiche WHERE IdCliente=$idCliente AND idagenzia>0 AND IdContratto!=$IdContratto";
?>

var dsContratti = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read', sql: "<?php echo $sql?>"},
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'CodContratto'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'CodContratto'},
					{name: 'Agenzia'},
					{name: 'DataFineAffido'},
					{name: 'ImpInsoluto'},
				]
        ),
		sortInfo:{field: 'CodContratto', direction: "ASC"},
});	

var gridContrattiRecupero = new Ext.grid.GridPanel({
    	id: 'gridContratti',
        width:510,
        height:100,
        title:'Altri contratti a recupero',
        store: dsContratti,
        trackMouseOver:true,
        disableSelection:true,
        loadMask: true,
        viewConfig: {
			autoFill: true,
			forceFit: false
		},
        // grid columns
        columns:[{
 			id: 'codContratto',
			header: "Codice contratto",
            dataIndex: 'CodContratto',
	        width: 350,
			align: 'left',
			sortable: true
        },{
 			id: 'agenzia',
			header: "Agenzia",
            dataIndex: 'Agenzia',
	        width: 350,
			align: 'left',
			sortable: true
        },{
 			id: 'dataFineAffido',
			header: "Data fine affido",
            dataIndex: 'DataFineAffido',
	        width: 350,
			align: 'left',
			sortable: true
        },{
 			id: 'impInsoluto',
			header: "Importo insoluto",
            dataIndex: 'ImpInsoluto',
	        width: 350,
			align: 'left',
			sortable: true
        }
     ]/*,        

        // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 10,
            id: 'bbAll',
            store: dsContratti,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare",
            items:[]
        })*/
});

Ext.getCmp('gridContratti').getStore().load();

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	id: 'frmPan',
	labelWidth: 130, 
	frame: true, 
	autoScroll: true,
	title: "<?php echo $titolo?>",
    width: 525,
    autoHeight: true,
        defaultType: 'textfield',
        items: [
            // la struttura è fatta di righe isolate, perché se si usa uno solo layout column
            // il wrap dei testi o i campi vuoti disallineano le righe
		{xtype:'container', layout:'column',
        	items:[{
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Dealer',		  value: "<b><?php echo $dealer?></b>"}]
			   },{  
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Regione residenza',value: "<b><?php echo $dataForm['AreaIntest']?></b>"}]
			   }]
        },
        {xtype:'container', layout:'column',
		         items:[{  
		            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Stato pratica', value: "<b><?php echo $dataForm['Stato']?></b>"}]
		        },{
				    xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Categoria',     value: "<b><?php echo $dataForm['TitoloCategoria']?$dataForm['TitoloCategoria']:'Non specificata'?></b>"}]
						}]
         },
         {xtype:'container', layout:'column',
         items:[{  
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Rate pagate',   value: "<b><?php echo $dataForm['RatePagateSuTotali']?></b>"}]
			   },{  
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Data DBT/CM',      value: "<b><?php echo $dataDBT?></b>"}]
			   }]
         },
         {xtype:'container', layout:'column',
         items:[{  
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Importo DBT',   value: "<b><?php echo $importoDBT?></b>"}]
				},{  
            xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield', columnWidth: .50,
			items: [{fieldLabel: 'Debito residuo',   value: "<b><?php echo $debito?></b>"}]
			   }]
         },
         {xtype:'panel', layout:'form', labelWidth:110, defaultType:'displayfield',  
					defaults: {anchor:'98%'},
					items: [{fieldLabel: 'Piano di rientro', value: "<b><?php echo $pianorientro?></b>"}
						   ,{fieldLabel: 'Saldo e stralcio', value: "<b><?php echo $saldostralcio?></b>"}
						   ]
		 },
		 gridContrattiRecupero,
				{xtype:'displayfield',height: 10},
		 {xtype:'panel', layout:'form', labelWidth:85, defaultType:'textarea',
					defaults: {readOnly:false, anchor:'99%'},
					items: [{
						xtype:'textarea',
						height: 60,
			            fieldLabel: 'Nota',
			            id: 'nota',
			            value: "<?php echo str_replace('"','\"',$nota)?>"
			            //name: 'nota'
		            }]
		        },
        {xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield',
				  defaults: {anchor:'36%'},
				  items: [{
			        xtype: 'datefield',
					format: 'd/m/Y',
					fieldLabel: 'Data verifica',
					value: '<?php echo $dataDefault?>',
					minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'dataVerifica',
					id: 'dataVerifica'
				  }]
			  	},
	  	{xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield',
				  defaults: {anchor:'99%'},
				  items: [{  
				    xtype: 'checkbox',
				    height: 30,
				    boxLabel: '<span style="color:blue;"><b>Elimina scadenze gi&agrave; in calendario</b></span>',
					name: 'chkHidden',
					id: 'chkHidden',
					hidden: '<?php echo $chkHidden?>',
					disabled:'<?php echo $chkHidden?>',
					checked: false,
		
					listeners:{
			 			check: function(r,v)
			 			{
		 	 			  if(v==true)
			 			  {
							Ext.Msg.show({
							   title:'Attenzione...',
							   msg: '<span style="color:red;"><b align="justify">Selezionando questa voce saranno sostituite tutte le scadenze gi&agrave; inserite per questa pratica.</b></span>',
							   buttons: Ext.Msg.OK,
							   icon: Ext.MessageBox.WARNING
							});	
								
						  }
			 		    }
			 		}  
			 	  }]	  
				}
	]});