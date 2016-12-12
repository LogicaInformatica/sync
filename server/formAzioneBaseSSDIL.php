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

$dataForm=getRow("select * from v_pratiche WHERE IdContratto=$IdContratto");
$dataDBT  	= date('d/m/Y',dateFromString($dataForm['DataDBT']));
$importoDBT = $dataForm['ImpDBT']>0? number_format($dataForm['ImpDBT'],2,',','.'): '-';
$debito = $dataForm['ImpCapitale']+$dataForm['ImpAltriAddebiti'];
//$incassato  = $dataForm['ImpDBT']>$debito ? number_format($dataForm['ImpDBT']-$debito,2,',','.'):'-';
$debito     = number_format($debito,2,',','.');
// mostra gli interessi di mora (del DBT!) solo se sono ancora a debito
$interessi  = ($dataForm['inFY']=='Y' && $dataForm['ImpInteressiMoraAddebitati>0'])? $dataForm['ImpInteressiMaturati']:'-';

$dataFormPiano = getRow("select * from pianorientro WHERE IdContratto=$IdContratto");
$primoImporto = $dataFormPiano['PrimoImporto'];
$dataPagPrimoImporto = $dataFormPiano['DataPagPrimoImporto'];
$numeroRate = $dataFormPiano['NumeroRate'];
$importoRata = $dataFormPiano['ImportoRata'];
$decorrenzaRata = $dataFormPiano['DecorrenzaRate'];

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
        width:610,
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
    width: 620,
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
		 {xtype:'panel', layout:'form', labelWidth:110, defaultType:'textarea',
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
	      // Prima e seconda riga campi di input	      
	      {xtype:'container', layout:'column',
			items:[
				{xtype:'panel', layout:'form', labelWidth:110, columnWidth: .34,defaultType:'textfield',
				 defaults: {anchor:'98%'},
				 items: [{
						xtype:'numberfield',
						fieldLabel: 'Importo proposto',
						allowNegative: false,
						minValue :0.01,
						allowBlank: false,
						style: 'text-align:right',
						decimalPrecision: 2,
						width: 80,
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
									var percAbbuon= (impoAbbonato/parseFloat(impTotInsoluto))*100;
									percAbbuon = Math.round(percAbbuon*100)/100; // arrotondo ai due decimali
									percAbbuon = percAbbuon + '%';
									impoAbbonato = Math.round(impoAbbonato*100)/100; // arrotondo ai due decimali

									var txtImpoAbbonato = impoAbbonato.toString().replace('.',',');
									var txtPercAbbuon   = percAbbuon.toString().replace('.',',');
									Ext.getCmp('impAbbuono').setValue(txtImpoAbbonato);
									Ext.getCmp('percAbbuono').setValue(txtPercAbbuon);
							   	}
						   	}						
						},
						{xtype:'numberfield',
						 fieldLabel:'Primo importo',	
						 name:'primoImporto',
					 	 id:'primoImporto',	
						 style:'text-align:right', 
						 width:90,
						 value: '<?php echo $primoImporto;?>',
						 decimalPrecision: 2,
						 decimalSeparator: ',', 
						 listeners: {
							'change': function(){
							    if(Ext.getCmp('numeroRate').getValue()!='') {
									//var impInsoluto = Ext.getCmp('impInsoluto').getValue();
									var impInsoluto = Ext.getCmp('importoProposto').getValue();
													
									/*impInsoluto = impInsoluto.replace('.','');
									impInsoluto = impInsoluto.replace(',','.');*/
									var primImporto = Ext.getCmp('primoImporto').getValue();
									if (primImporto==''){
									  Ext.Msg.alert("Attenzione","Il primo importo deve essere presente.");
									  Ext.getCmp('numeroRate').setValue('');
									  return;
									} 
									var impTotRate = parseFloat(impInsoluto) - parseFloat(primImporto);
									var numRate = Ext.getCmp('numeroRate').getValue();
									var impRata= (impTotRate/numRate);
									impoRata = Math.round(impRata*100)/100; // arrotondo ai due decimali
									
									//var txtImpoRata = impoRata.toString().replace('.',',');
									Ext.getCmp('importoRata').setValue(impoRata);
								}	
							}
						}									
					  },
					  {xtype:'textfield',
							format:'0.000,00/i',
							hidden: true,
							id: 'impInsoluto',
							value: '<?php echo $residuo;?>'
						}]}, // FINE PRIMA COLONNA PARTE BASSA PAGINA
					{xtype:'panel', layout:'form', labelWidth:85, columnWidth: .33,defaultType:'textfield',
						defaults: {anchor:'98%'},
						items: [{
								fieldLabel:'Abbuono',	
						    	name:'impAbbuono',
						    	id:'impAbbuono',	readOnly:true,
						   		style:'text-align:right', 
					   			width:80						
								}, 
								{
							     xtype: 'datefield',
								 format: 'd/m/Y',
								 allowBlank: false,
								 width: 90,
								 fieldLabel: 'Data pagam.',
								 minValue: new Date(),
								 style:'text-align:right', 
								 name: 'dataPagPrimoImporto',
								 id: 'dataPagPrimoImporto',
								 value: '<?php echo $dataPagPrimoImporto;?>'						
							  }]},	// FINE SECONDA COLONNA		        	
						{xtype:'panel', layout:'form', labelWidth:85, columnWidth: .33,defaultType:'textfield',
						defaults: {anchor:'98%'},
						items: [{
								fieldLabel:'Percent. abb.',	
							    name:'percAbbuono',	
							    id:'percAbbuono',	
							    style:'text-align:right', 
							    width:50,readOnly:true						
								}]} // FINE TERZA COLONNA			        	
				]}, // FINE CONTAINER CON LAYOUT COLUMN	PRIMA E SECONDA RIGA CAMPI DI INPUT	
				// TERZA RIGA CAMPI DI INPUT (separata dalle precedenti per evitare collassamento terza colonna)
	      {xtype:'container', layout:'column',
			items:[
				{xtype:'panel', layout:'form', labelWidth:110, columnWidth: .34,defaultType:'textfield',
				 defaults: {anchor:'98%'},
				 items: [				
				 	{xtype:'numberfield',
 		           		allowNegative: false,
        	    		minValue :1,
            			allowBlank: false,
            			decimalPrecision: 0,
 						fieldLabel:'N. Rate',	
						name:'numeroRate',
					 	id:'numeroRate',	
						style:'text-align:right', 
						width:50,
						value: '<?php echo $numeroRate;?>',
						listeners: {
							'change': function(){
							    //var impInsoluto = Ext.getCmp('impInsoluto').getValue();
								if(Ext.getCmp('numeroRate').getValue()=='') {
								  Ext.getCmp('decorrenzaRata').setValue('');
								  Ext.getCmp('importoRata').setValue('');
								  return;
								}
								var impInsoluto = Ext.getCmp('importoProposto').getValue();
								/*impInsoluto = impInsoluto.replace('.','');
								impInsoluto = impInsoluto.replace(',','.');*/
								var primImporto = Ext.getCmp('primoImporto').getValue();
								if (primImporto==''){
								  Ext.Msg.alert("Attenzione","Il primo importo deve essere presente.");
								  Ext.getCmp('numeroRate').setValue('');
								  return;
								} 
								var impTotRate = parseFloat(impInsoluto) - parseFloat(primImporto);
								var numRate = Ext.getCmp('numeroRate').getValue();
								var impRata= (impTotRate/numRate);
								impoRata = Math.round(impRata*100)/100; // arrotondo ai due decimali
								
								//var txtImpoRata = impoRata.toString().replace('.',',');
								Ext.getCmp('importoRata').setValue(impoRata);
							}
						}						
					  }]},
				{xtype:'panel', layout:'form', labelWidth:85, columnWidth: .33,
						defaults: {anchor:'98%'},
				 items: [{xtype:'numberfield',
							 fieldLabel:'Importo rata',	
							 name:'importoRata',
						 	 id:'importoRata',	
							 style:'text-align:right', 
							 width:80,readOnly:true,
							 value: '<?php echo $importoRata;?>',
							 decimalPrecision: 2,
							 decimalSeparator: ','						
						  }]},
				{xtype:'panel', layout:'form', labelWidth:85, columnWidth: .33,
						defaults: {anchor:'98%'},
				 items: [{
						     xtype: 'datefield',
							 format: 'd/m/Y',
							 allowBlank: false,
							 width: 90,
							 fieldLabel: 'Decorrenza',
							 minValue: new Date(),
							 value: '<?php echo $decorrenzaRata;?>',
							 style:'text-align:right', 
							 name: 'decorrenzaRata',
							 id: 'decorrenzaRata'						
						  }]}]},	 // fine terza riga campi di input 
		        { 
		         xtype:'container', layout:'column',
		         items:[
		            {   
					    xtype:'panel', layout:'form', labelWidth:110, defaultType:'textfield', columnWidth: .35,
						defaults: {anchor:'99%'},
						items: [{
				        	xtype: 'datefield',
							format: 'd/m/Y',
							fieldLabel: 'Data verifica',
							value: '<?php echo $dataDefault?>',
							minValue: new Date(),
							maxValue:'<?php echo italianDate($dataLimite) ?>',
							style:'text-align:right', 
							name: 'dataVerifica',
							id: 'dataVerifica'
						}]
				    },{   
					    xtype:'panel', layout:'form',labelWidth:0, defaultType:'textfield', columnWidth: .65,
						defaults: {anchor:'99%'},
						items: [{
						    xtype: 'checkbox',
						    //height: 30,
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
									   msg: '<span style="color:red; text-align:justify"><b>Selezionando questa voce saranno sostituite tutte le scadenze gi&agrave; inserite per questa pratica.</b></span>',
									   buttons: Ext.Msg.OK,
									   icon: Ext.MessageBox.WARNING
									});	
										
								  }
					 		    }
					 		}    
						}]}]}
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
	Ext.getCmp('dataPagPrimoImporto').setValue('<?php echo($dataPagPrimoImporto);?>');
<?php } ?>
	
formPanel.addButton({
	text: 'Salva e stampa',
	handler: function() {
	   var vectValue = saveSSDFormDataToVect();
	   			   
	   if (formPanel.getForm().isValid()){
			DCS.showMask();
			formPanel.getForm().submit({
				url: 'server/edit_azione.php', method: 'POST',
					params: {codazione: 'SSD' , idcontratti: "<?php echo addslashes($idcontratti) ?>", 
					txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue)}, 
				success: function (frm,action) {
					window.open('server/generaStampaComunicazioniSSDIL.php?TitoloModello=Comunicazione%20Piano%20di%20Rientro&IdContratto=<?php echo $ids?>','_parent','');
					DCS.hideMask();
				},
				failure: function(form,action) {
					DCS.hideMask();
					saveFailure(form,action);
					}
			});
		}
	}//,scope: this
});

//
// Salva i campi del form in array per la scrittura del contenuto HTML su storiarecupero
//
function saveSSDFormDataToVect() {
	   var vectValue = [];
	   obj = {nota : Ext.getCmp('nota').getValue()};
	   obj1 = {importoProposto : Ext.getCmp('importoProposto').getValue()};
	   obj2 = {impAbbuono : Ext.getCmp('impAbbuono').getValue()};
	   obj3 = {percAbbuono : Ext.getCmp('percAbbuono').getValue()};
	   obj4 = {primoImporto : Ext.getCmp('primoImporto').getValue()};
	   if(Ext.getCmp('dataPagPrimoImporto').getValue()!='') {
	     obj5 = {dataPagPrimoImporto : Ext.getCmp('dataPagPrimoImporto').getValue().format('d/m/Y')};
  	   } else {
  	       obj5 = {dataPagPrimoImporto : Ext.getCmp('dataPagPrimoImporto').getValue()};
  	     }
	   obj6 = {numeroRate : Ext.getCmp('numeroRate').getValue()}; 
	   obj7 = {importoRata : Ext.getCmp('importoRata').getValue()};
	   if(Ext.getCmp('decorrenzaRata').getValue()!='') {
	     obj8 = {decorrenzaRata : Ext.getCmp('decorrenzaRata').getValue().format('d/m/Y')};
  	   } else {
  	       obj8 = {decorrenzaRata : Ext.getCmp('decorrenzaRata').getValue()};
  	     }
  	   if(Ext.getCmp('dataVerifica').getValue()!='') {
	     obj9 = {dataVerifica : Ext.getCmp('dataVerifica').getValue().format('d/m/Y')};
  	   } else {
  	       obj9 = {dataVerifica : Ext.getCmp('dataVerifica').getValue()};
  	     }  
	   vectValue.push(obj);
	   vectValue.push(obj1);
	   vectValue.push(obj2);
	   vectValue.push(obj3); 
	   vectValue.push(obj4); 
	   vectValue.push(obj5);
	   vectValue.push(obj6); 
	   vectValue.push(obj7); 
	   vectValue.push(obj8); 
	   vectValue.push(obj9); 
	   
	   return vectValue;
}	 
