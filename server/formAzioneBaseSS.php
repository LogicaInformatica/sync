<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione con data"
// Contenuto: campo data (da inserire in scadenzario), note e pulsanti Conferma / Annulla

$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
//$dataVendita = $azione["DataVendita"];
$IdContratto = $idsArray[0];
$chkHidden = false;
if (rowExistsInTable("nota","IdContratto=$IdContratto and TipoNota='S' and DataScadenza>=curdate()")==false)
	$chkHidden = true;

$dataForm 	= getRow("select * from v_pratiche WHERE IdContratto=$IdContratto");
$dataDBT  	= date('d/m/Y',dateFromString($dataForm['DataDBT']));
$importoDBT = $dataForm['ImpDBT']>0? number_format($dataForm['ImpDBT'],2,',','.'): '-';
$debito = $dataForm['ImpCapitale']+$dataForm['ImpAltriAddebiti'];
//$incassato  = $dataForm['ImpDBT']>$debito ? number_format($dataForm['ImpDBT']-$debito,2,',','.'):'-';
$debito     = number_format($debito,2,',','.');
// mostra gli interessi di mora (del DBT!) solo se sono ancora a debito
//$interessi  = ($dataForm['inFY']=='Y' && $dataForm['ImpInteressiMoraAddebitati>0'])? $dataForm['ImpInteressiMaturati']:'-';

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
$irreperibile = $dataForm["FlagIrreperibile"]=='Y'?'true':'false';
$ipoteca = $dataForm["FlagIpoteca"]=='Y'?'true':'false';
$concorsuale = $dataForm["FlagConcorsuale"]=='Y'?'true':'false';
$dataVendita = $dataForm["DataVendita"];
$boxLabel = 'Intestatario irreperibile';
$idCliente = $dataForm["IdCliente"];
$impSaldoStralcio  = $dataForm["ImpSaldoStralcio"];
$dataSaldoStralcio = $dataForm["DataSaldoStralcio"];

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
		        {
		        	xtype:'container', layout:'column',
					items:[
						{xtype:'panel', layout:'form', labelWidth:130, columnWidth: .50,defaultType:'textfield',
						defaults: {anchor:'98%'},
						items: [{
									xtype:'numberfield',
									fieldLabel: 'Importo proposto',
									allowNegative: false,
									minValue :0.01,
									allowBlank: false,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 120,
									decimalSeparator: ',',
									name: 'importoProposto',
									id: 'importoProposto',
									listeners: {
										// quando si edita l'importo proposto, ricalcola gli altri
										'change': function(){
											
											var impTotInsoluto = <?php echo $dataForm['ImpInsoluto']?>;
											var impProposto = Ext.getCmp('importoProposto').getValue();
											if ((impProposto=='') || (impProposto=='0')){
												Ext.Msg.alert("Attenzione","L'importo proposto non &egrave; valido.");
												Ext.getCmp('importoProposto').markInvalid();
												return;
											} 
											var impoAbbonato = parseFloat(impTotInsoluto) - parseFloat(impProposto);
											var percAbbuon   = (impoAbbonato/parseFloat(impTotInsoluto))*100;
											percAbbuon = Math.round(percAbbuon*100)/100; // arrotondo ai due decimali
											percAbbuon = percAbbuon + ' %';
											impoAbbonato = Math.round(impoAbbonato*100)/100; // arrotondo ai due decimali

											var txtImpoAbbonato = impoAbbonato.toString().replace('.',',');
											var txtPercAbbuon   = percAbbuon.toString().replace('.',',');
											Ext.getCmp('impAbbuono').setValue(txtImpoAbbonato);
											Ext.getCmp('percAbbuono').setValue(txtPercAbbuon);
									   	}
								   	}						
								},
								{
									xtype:'textfield',
									format:'0.000,00/i',
									hidden: true,
									id: 'impInsoluto',
									value: '<?php echo $residuo;?>'
								}]}			        	
				]},
		        {
		        	xtype:'container', layout:'column',
					items:[
						{xtype:'panel', layout:'form', labelWidth:130, columnWidth: .50,defaultType:'textfield',
						defaults: {readOnly:true, anchor:'98%'},
						items: [{
								fieldLabel:'Abbuono',	
						    	name:'impAbbuono',
						    	id:'impAbbuono',	
						   		style:'text-align:right', 
					   			width:90						
								}]},			        	
						{xtype:'panel', layout:'form', labelWidth:130, columnWidth: .50,defaultType:'textfield',
						defaults: {readOnly:true, anchor:'98%'},
						items: [{
								fieldLabel:'Percent. abbuono',	
							    name:'percAbbuono',	
							    id:'percAbbuono',	
							    style:'text-align:right', 
							    width:90						
								}]}
				]},								        	
		        { 
		         xtype:'container', layout:'column',
		         items:[{  
		            xtype:'panel', layout:'form', labelWidth:130, defaultType:'textfield', columnWidth: .50,
					defaults: {anchor:'98%'},
					items: [{
			        	xtype: 'datefield',
						format: 'd/m/Y',
						allowBlank: false,
						fieldLabel: 'Data pagamento',
						minValue: new Date(),
						maxValue:'<?php echo italianDate($dataLimite) ?>',
						name: 'dataPagamento',
						id: 'dataPagamento'
					}]
				   },
				   {   
				    xtype:'panel', layout:'form', labelWidth:130, defaultType:'textfield', columnWidth: .50,
					defaults: {anchor:'98%'},
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
		        ] // fine items form principale
		 }); // fine form
// reimposto i valori salvati in precedenza
<?php if ($impSaldoStralcio !='') { ?>
 
 	var impTotInsoluto = <?php echo $dataForm['ImpInsoluto']?>;
	var impProposto = <?php echo($impSaldoStralcio);?>;
	
	var impoAbbonato = parseFloat(impTotInsoluto) - parseFloat(impProposto);
	var percAbbuon= (impoAbbonato/parseFloat(impTotInsoluto))*100;
	percAbbuon = Math.round(percAbbuon*100)/100; // arrotondo ai due decimali
	percAbbuon = percAbbuon + ' %';
	impoAbbonato = Math.round(impoAbbonato*100)/100; // arrotondo ai due decimali
	
	var txtImpoAbbonato = impoAbbonato.toString().replace('.',',');
	var txtPercAbbuon   = percAbbuon.toString().replace('.',',');
	
	Ext.getCmp('impAbbuono').setValue(txtImpoAbbonato);
	Ext.getCmp('percAbbuono').setValue(txtPercAbbuon);
	Ext.getCmp('importoProposto').setValue('<?php echo($impSaldoStralcio);?>');
	console.log(<?php echo($dataSaldoStralcio);?>);
	Ext.getCmp('dataPagamento').setValue('<?php echo($dataSaldoStralcio);?>');
<?php } ?>	
//
// Pulsante di stampa
//
debugger;
formPanel.addButton({
	text: 'Salva e stampa',
	handler: function() {
	   var vectValue = saveSSFormDataToVect();
	   			   
	   if (formPanel.getForm().isValid()){
			DCS.showMask();
			formPanel.getForm().submit({
				url: 'server/edit_azione.php', method: 'POST',
					params: {codazione: 'SS' , idcontratti: "<?php echo addslashes($idcontratti) ?>", 
					txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue)}, 
				success: function (frm,action) {
					window.open('server/generaStampaComunicazioniSS.php?TitoloModello=Comunicazione%20Sal/Str&IdContratto=<?php echo $ids?>','_parent','');
					DCS.hideMask();
				},
				failure: function(form,action) {
					DCS.hideMask();
					saveFailure(form,action);
					}
			});
		}
	}
});

//
// Salva i campi del form in array per la scrittura del contenuto HTML su storiarecupero
//
function saveSSFormDataToVect() {
	   var vectValue = [];
	   obj = {nota : Ext.getCmp('nota').getValue()};
	   obj1 = {importoProposto : Ext.getCmp('importoProposto').getValue()};
	   obj2 = {impAbbuono : Ext.getCmp('impAbbuono').getValue()};
	   obj3 = {percAbbuono : Ext.getCmp('percAbbuono').getValue()};
	    
	   if(Ext.getCmp('dataPagamento').getValue()!='') {
	     obj4 = {dataPagamento : Ext.getCmp('dataPagamento').getValue().format('d/m/Y')};
  	   } else {
  	       obj4 = {dataPagamento : Ext.getCmp('dataPagamento').getValue()};
  	     }
  	   if(Ext.getCmp('dataVerifica').getValue()!='') {
	     obj5 = {dataVerifica : Ext.getCmp('dataVerifica').getValue().format('d/m/Y')};
  	   } else {
  	       obj5 = {dataVerifica : Ext.getCmp('dataVerifica').getValue()};
  	     }  
	   vectValue.push(obj);
	   vectValue.push(obj1);
	   vectValue.push(obj2);
	   vectValue.push(obj3); 
	   vectValue.push(obj4); 
	   vectValue.push(obj5); 
	   
	   return vectValue;
}	


