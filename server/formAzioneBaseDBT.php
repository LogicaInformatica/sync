<?php
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione con data"
// Contenuto: campo data (da inserire in scadenzario), note e pulsanti Conferma / Annulla

$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
//$dataVendita = $azione["DataVendita"];
$IdContratto = $idsArray[0];
$chkHidden = false;
if(rowExistsInTable("nota","IdContratto=$IdContratto AND TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
$chkHidden = true;

$dataForm = getRow("select * from v_pratiche WHERE IdContratto=$IdContratto");
$capitale= number_format($dataForm['ImpDebitoResiduo']+$dataForm['ImpCapitale'],2,',','.');
$residuo= number_format($dataForm['ImpDebitoResiduo']+$dataForm['Importo'],2,',','.');
$speseIncasso = number_format( $dataForm['ImpSpeseRecupero'], 2, ',', '.');
$interessiMora = number_format( $dataForm['ImpInteressiMora'], 2, ',', '.');
$impAltriAdd=number_format($dataForm['ImpAltriAddebiti'], 2, ',', '.');

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

if($dataForm['DataUltimaScadenza']<date("Y-m-d")){
	$isOp='DBT';
}else{
	$isOp='CM';
}

// determina la forzatura di affido corrente
$IdRegolaProvvigione = getForzaturaAffidoCorrente($IdContratto);
if (!$IdRegolaProvvigione) $IdRegolaProvvigione = 0;

$titolo = "Pratica n. ".$dataForm['CodContratto']." - ".$dataForm["NomeCliente"];

if($chkHidden)
	$hw = 600;
else
	$hw = 680;

$idProcedura = getScalar("SELECT IdProcedura FROM azioneprocedura WHERE IdAzione=".$azione["IdAzione"]);
$nota = getScalar("select NotaEvento from storiarecupero where IdContratto=$IdContratto AND".
                  " IdAzione in (SELECT IdAzione FROM azioneprocedura WHERE IdProcedura=0$idProcedura)".
                  " GROUP BY IdStoriaRecupero DESC LIMIT 1");

$dealer = $dataForm["Venditore"];
$irreperibile = $dataForm["FlagIrreperibile"]=='Y'?'true':'false';
$ipoteca = $dataForm["FlagIpoteca"]=='Y'?'true':'false';
$concorsuale = $dataForm["FlagConcorsuale"]=='Y'?'true':'false';
$dataVendita = $dataForm["DataVendita"];
$boxLabel = 'Intestatario irreperibile';
$idCliente = $dataForm["IdCliente"];

// Calcola numero rate pagate (quelle con data di scadenza passata e saldo OK non abbuonate o stornate)
$numRatePagate = calcolaNumRatePagate($IdContratto);
$dataForm["RatePagateSuTotali"] = "$numRatePagate su ".$dataForm["NumRate"];

$sql = "SELECT CodContratto,concat(NomeAgenzia,' (',CodRegolaProvvigione,')') as Agenzia, DataFineAffido, ImpInsoluto" 
       ." FROM v_pratiche WHERE IdCliente=$idCliente AND idagenzia>0 AND IdContratto!=$IdContratto";

?>
var dsContratti = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read', sql: "<?php echo $sql?>"},//this parameter is passed for any HTTP request
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
					{name: 'ImpInsoluto'}
				]
        ),
		sortInfo:{field: 'CodContratto', direction: "ASC"}
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
    //height: <?php echo $hw?>,
    /*defaults: {
            width: 400, 
			height: 100
        },*/
        defaultType: 'textfield',
        items: [{xtype:'container', id:'frmCont',
        		items:[
        		{
        		 xtype:'container', layout:'column',
		         items:[{  
		            xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield', columnWidth: .55,
					defaults: {anchor:'98%'},
					items: [{
			        	xtype: 'displayfield',
					    fieldLabel: 'Dealer',
		                value: "<b><?php echo $dealer?></b>",
		                //width: 100,
		                style: 'text-align:left',
		                name: 'venditore'
					}]
				   },
				   {   
				    xtype:'panel', layout:'form', labelWidth:110, defaultType:'textfield', columnWidth: .45,
					defaults: {anchor:'98%'},
					items: [{
			        	xtype: 'displayfield',
					    fieldLabel: 'Regione residenza',
		                value: "<b><?php echo $dataForm['AreaIntest']?></b>",
		                //width: 100,
		                style: 'text-align:left',
		                name: 'regioneResidenza'
					}]
				   }
				 ] 		
				},
        		{
		        	xtype:'container', layout:'column',
					items:[{
							xtype:'panel', layout:'form', labelWidth:85, columnWidth: .55,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'98%'},
							items: [{
								xtype: 'displayfield', 
								fieldLabel: 'Stato pratica',
			                	value: "<b><?php echo $dataForm['Stato']?></b>",
			                	//width: 100,
			                	style: 'text-align:left',
			                	name: 'statoPratica'
							},{
								xtype: 'displayfield', 
								fieldLabel: 'Categoria',
			                	value: "<b><?php echo $dataForm['TitoloCategoria']?$dataForm['TitoloCategoria']:'Non specificata'?></b>",
			                	//width: 100,
			                	style: 'text-align:left',
			                	name: 'categoria'
							}]
						},{        
							xtype:'panel', layout:'form', labelWidth:110, columnWidth:.45,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'98%'},
							items: [{
								xtype: 'displayfield', 
								fieldLabel: 'Num. insoluti',
			                	value: "<b><?php echo $dataForm['Insoluti']?></b>",
			                	//width: 100,
			                	style: 'text-align:left',
			                	name: 'statoPratica'
							},{
								xtype: 'displayfield', 
								fieldLabel: 'Giorni',
			                	value: "<b><?php echo $dataForm['Giorni']?></b>",
			                	//width: 100,
			                	style: 'text-align:left',
			                	name: 'categoria'
							}]
						}]
		        },{
		        	xtype:'container', layout:'column',
					items:[{
						xtype:'panel', layout:'form', labelWidth:85, columnWidth: .55,defaultType:'textfield',
						defaults: {readOnly:true, anchor:'98%'},
						items: [{
							xtype: 'displayfield', 
							fieldLabel: 'Classificazione',
		                	value: "<b><?php echo $dataForm['Classificazione']?></b>",
		                	//width: 100,
		                	style: 'text-align:left',
		                	name: 'classificazione'
						}]
					},{        
						xtype:'panel', layout:'form', labelWidth:110, columnWidth:.45,defaultType:'textfield',
						defaults: {readOnly:true, anchor:'98%'},
						items: [{
							xtype: 'displayfield',
							fieldLabel: 'Rate pagate',
		                	value: "<b><?php echo $dataForm['RatePagateSuTotali']?></b>",
		                	style: 'text-align:left',
		                	name: 'ratePagateSuTotali'
						}]
					}]
		        },
		        {
				  xtype:'container', layout:'column', 
				  items:[
					 {xtype: 'displayfield',height: 20,width: 90, value: "Deb. Residuo", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 20,width: 90, value: "Capitale", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 20,width: 100, value: "Interessi di mora", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 20,width: 110, value: "Spese di recupero", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 20,width: 120, value: "Imp. altri addebiti", style:'text-align:center;font-size:13px;'}]
				},
				{
				  xtype:'container', layout:'column',
				  items:[
					 {xtype: 'displayfield',height: 25,width: 90, value: "<b><?php echo $residuo?></b>", style: 'text-align:center;font-size:13px;',name: 'debResiduo'}, 
					 {xtype: 'displayfield',height: 25,width: 90, name:'capitale', value: "<b><?php echo $capitale;?></b>", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 25,width: 100, value: "<b><?php echo $interessiMora;?></b>", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 25,width: 110, name:'SpInc', value: "<b><?php echo $speseIncasso;?></b>", style:'text-align:center;font-size:13px;'},
					 {xtype: 'displayfield',height: 25,width: 120, name:'impAltriAdd', value: "<b><?php echo $impAltriAdd?></b>", style:'text-align:center;font-size:13px;'}]//end sub fieldset left column
				},gridContrattiRecupero, 
		        {
		        	xtype:'container', layout:'column',
		        	items:[
					  {
					    xtype: 'checkbox',
					    height: 30,
					    width: 200,
						boxLabel: '<?php echo $boxLabel?>',
						name: 'chkFlag',
						id: 'chkFlag',
						checked: <?php echo $irreperibile?>,
					  },
					  {   
						xtype: 'checkbox',
						height: 30,
						width: 150,
						boxLabel: 'Ipoteca',
						name: 'chkFlagIpoteca',
						id: 'chkFlagIpoteca',
						checked: <?php echo $ipoteca?>,
					  },
					  {   
						xtype: 'checkbox',
						height: 30,
						width: 150,
						boxLabel: 'Procedura concorsuale',
						name: 'chkFlagConcorsuale',
						id: 'chkFlagConcorsuale',
						checked: <?php echo $concorsuale?>,
					  }]
				},{
		        	xtype:'panel', layout:'form', labelWidth:130, defaultType:'textarea',
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
		        {
		          xtype:'panel', layout:'form', labelWidth:130, defaultType:'combo',
				  defaults: {anchor:'99%'},
				  items: [{
		            xtype: 'combo',
		            id: 'comboAffido',
		            name: 'comboAffido',
					fieldLabel: 'Forza prossimo affido',
					hiddenName: 'IdRegolaProvvigione',
					anchor: '97%',editable: false,hidden: false,
					typeAhead: false,triggerAction: 'all',
					lazyRender: true,
					allowBlank: true,
					store: {
					   xtype:'store',
					   autoLoad:true,
					   proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
					   baseParams:{task: 'read', sql: "SELECT -1 AS IdRegolaProvvigione, '(Nessuna forzatura)' AS TitoloAgenzia UNION SELECT IdRegolaProvvigione,TitoloAgenzia FROM v_agenzia_provv_plus WHERE TipoAgenzia IN ('STR','GENLEG')"},
					   reader:  new Ext.data.JsonReader(
						  {root: 'results', id: 'IdRegolaProvvigione'},
						   [{name: 'IdRegolaProvvigione'},{name: 'TitoloAgenzia'}]
				       ),
					   sortInfo:{field: 'TitoloAgenzia', direction: "ASC"},
					   listeners:{
						   load: function(store,n) {
						     Ext.getCmp('comboAffido').setValue(<?php echo $IdRegolaProvvigione?>);
						   }
					   }
					},
					displayField: 'TitoloAgenzia',
			        valueField: 'IdRegolaProvvigione'
			        //startValue: '<?php echo $IdRegolaProvvigione ?>'
			      }]    
				},
		        { 
		         xtype:'container', layout:'column',
		         items:[{  
		            xtype:'panel', layout:'form', labelWidth:130, defaultType:'textfield', columnWidth: .53,
					defaults: {anchor:'90%'},
					items: [{
			        	xtype: 'datefield',
						format: 'd/m/Y',
						//width: 100,
						fieldLabel: 'Data vendita',
						value: '<?php echo $dataVendita?>',
						//minValue: new Date(), la data vendita può essere nel passato
						maxValue:'<?php echo italianDate($dataLimite) ?>',
						name: 'dataVendita',
						id: 'dataVendita'
					}]
				   },
				   {   
				    xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield', columnWidth: .47,
					defaults: {anchor:'80%'},
					items: [{
			        	xtype: 'datefield',
						format: 'd/m/Y',
						//width: 100,
						fieldLabel: 'Data verifica',
						value: '<?php echo $dataDefault?>',
						minValue: new Date(),
						maxValue:'<?php echo italianDate($dataLimite) ?>',
						name: 'dataVerifica',
						id: 'dataVerifica'
					}]
				   }
				 ] 		
				},{
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
				}
		        ]
		 }]
});


