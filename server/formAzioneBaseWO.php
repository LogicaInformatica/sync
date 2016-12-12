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

$dataForm=getRow("select * from v_dati_generali_writeoff v WHERE IdContratto=$IdContratto");

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

// Calcola numero rate pagate (quelle con data di scadenza passata e saldo OK non abbuonate o stornate)
$numRatePagate = calcolaNumRatePagate($IdContratto);
$dataForm["RatePagateSuTotali"] = "$numRatePagate su ".$dataForm["rateTot"];

?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	id: 'frmPan',
	frame: true, 
	autoScroll: true,
	title: "<?php echo $titolo?>",
    width: 900,
    autoHeight: true,
    defaultType: 'textfield',
    items: [
            // la struttura è fatta di righe isolate, perché se si usa uno solo layout column
            // il wrap dei testi o i campi vuoti disallineano le righe
		{xtype:'container', layout:'column',
         items:[{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .40, labelWidth: 80,
			items: [{fieldLabel: 'Dealer', id:'dealer', value: "<b><?php echo $dataForm['dealer']?></b>"}]
			   },{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .30,
			items: [{fieldLabel: 'Zona', id:'zona', value: "<b><?php echo $dataForm['zona']?></b>"}]
			   },{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .30,labelWidth: 80,
			items: [{fieldLabel: 'Prodotto', id:'prodotto', value: "<b><?php echo $dataForm['prodotto']?></b>"}]
			   }]
        },
         {xtype:'container', layout:'column',
         items:[{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .40, labelWidth: 80,
			items: [{fieldLabel: 'Finanziato', id: 'finanziato',  value: "<b><?php echo $dataForm['impFinanziato']?></b>"}]
			   },{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .30,
			items: [{fieldLabel: 'Data Liquidazione', id:'dataLiquidazione', value: "<b><?php echo $dataForm['dataLiquidazione']?></b>"}]
			   },{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .30,labelWidth: 80,
			items: [{fieldLabel: 'Rate pagate', id:'ratePagate', value: "<b><?php echo $dataForm["RatePagateSuTotali"]?></b>"}]
			   }]
         },
         {xtype:'container', layout:'column',
         items:[{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .40, labelWidth: 80,
			items: [{fieldLabel: 'Stato',id:'stato',   value: "<b><?php echo $dataForm["stato"]?></b>"}]
			   },{  
            xtype:'panel', layout:'form', defaultType:'displayfield', columnWidth: .30,
			items: [{fieldLabel: 'Data DBT/CIM',id:'dataDBT',	 value: "<b><?php echo $dataForm["dataPassConDBT"]?></b>"}]
			   }]
         },
//		 gridContrattiRecupero,

		 // SEZIONE DATI DI CONTROLLO
		 {xtype:'fieldset',title: 'Dati di controllo',
		  items: [
		  	// sezione Dati di controllo - RIGA 1
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .28, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Allegata relazione avvocato/agenzia', xtype:'checkbox', name: 'c1',
						 id:'c1', checked: <?php echo $dataForm['c1']=='SI'?'true':'false'?>}]
				   },{  
	            xtype:'panel', layout:'form', columnWidth: .37, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Fallimento / procedura concorsuale', xtype:'checkbox', name: 'c2',
				 		 id:'c2', checked: <?php echo $dataForm['c2']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota2',
			             id:'nota2', value: "<?php echo str_replace('"','\"',$dataForm['nota2'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 2
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .28, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Piano di rientro/rinegoziazione', xtype:'checkbox', name: 'c3',
						 id:'c3',checked: <?php echo $dataForm['c3']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .22, labelWidth:100,
	            items: [{xtype:'numberfield', fieldLabel:'Importo versato', allowNegative: false, allowBlank: true,
									style: 'text-align:right', decimalPrecision: 2,	decimalSeparator: ',', width: 76, 
									name: 'importo3a', id: 'importo3a', value: "<?php echo $dataForm['importo3a']?>",
									listeners: {change: ricalcolaTotaliWO} 
						}]
				   },{  
	            xtype:'panel', layout:'form', columnWidth: .15, labelWidth:90, 
	            items: [{xtype:'checkbox', fieldLabel:'Piano rispettato', name: 'c3a',  
	                    id:'c3a',checked: <?php echo $dataForm['c3a']=='SI'?'true':'false'?>}]
	               },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota3a',
			             id:'nota3a',value: "<?php echo str_replace('"','\"',$dataForm['nota3a'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 3
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .28, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Accordo a saldo e stralcio', xtype:'checkbox', name: 'c4',
						 id:'c4',checked: <?php echo $dataForm['c4']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .22, labelWidth:100,
	            items: [{xtype:'numberfield', fieldLabel:'Importo S&amp;S', allowNegative: false, allowBlank: true,
									style: 'text-align:right', decimalPrecision: 2,	decimalSeparator: ',', width: 76, 
									name: 'importo4a', id: 'importo4a', value: "<?php echo $dataForm['importo4a']?>",
									listeners: {change: ricalcolaTotaliWO} 
						}]
				   },{  
	            xtype:'panel', layout:'form', columnWidth: .15, labelWidth:90, 
	            items: [{xtype:'checkbox', fieldLabel:'Contabilizzato', name: 'c4a',  
	                    id:'c4a',checked: <?php echo $dataForm['c4a']=='SI'?'true':'false'?>}]
	               },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota4a',
			             id:'nota4a',value: "<?php echo str_replace('"','\"',$dataForm['nota4a'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 4
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .28, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Ripossesso del veicolo', xtype:'checkbox', name: 'c5',
						 id:'c5',checked: <?php echo $dataForm['c5']=='SI'?'true':'false'?>}]
				   },{  
	            xtype:'panel', layout:'form', columnWidth: .37, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Fatta perdita di possesso', xtype:'checkbox', name: 'c5a',
						 id:'c5a',checked: <?php echo $dataForm['c5a']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota5a',
			             id:'nota5a',value: "<?php echo str_replace('"','\"',$dataForm['nota5a'])?>"}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 5
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .28, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Perizia', xtype:'checkbox', name: 'c5b',
						 id:'c5b',checked: <?php echo $dataForm['c5b']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .22, labelWidth:100,
	            items: [{xtype:'numberfield', fieldLabel:'Valore veicolo', allowNegative: false, allowBlank: true,
									style: 'text-align:right', decimalPrecision: 2,	decimalSeparator: ',', width: 76, 
									name: 'importo5b', id: 'importo5b', value: "<?php echo $dataForm['importo5b']?>",
									listeners: {change: ricalcolaTotaliWO} 
						}]
				   },{  
	            xtype:'panel', layout:'form', columnWidth: .15, labelWidth:90, 
	            items: [{xtype:'displayfield', fieldLabel: '(IVA esclusa)'}]
	               },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota5b',
			             id:'nota5b',value: "<?php echo str_replace('"','\"',$dataForm['nota5b'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 6
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .65, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Procura a vendere', xtype:'checkbox', name: 'c5c',
						 id:'c5c',checked: <?php echo $dataForm['c5c']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota5c',
			             id:'nota5c',value: "<?php echo str_replace('"','\"',$dataForm['nota5c'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 7
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .65, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Cliente con reddito', xtype:'checkbox', name: 'c6',
						 id:'c6',checked: <?php echo $dataForm['c6']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota6',
			             id:'nota6',value: "<?php echo str_replace('"','\"',$dataForm['nota6'])?>"
				   		}]
				   }]
	         },
		  	 // sezione Dati di controllo - RIGA 8
	         {xtype:'container', layout:'column', 
	         items:[{  
	            xtype:'panel', layout:'form', columnWidth: .65, labelWidth:0,
				items: [{hideLabel:true, boxLabel: 'Cliente con immobili', xtype:'checkbox', name: 'c7',
						 id:'c7',checked: <?php echo $dataForm['c7']=='SI'?'true':'false'?>}]
				   },{
	            xtype:'panel', layout:'form', columnWidth: .35, labelWidth: 50,  
				items: [{xtype:'textarea',	height: 20, width: '100%', fieldLabel: 'Nota', name: 'nota7',
			             id:'nota7',value: "<?php echo str_replace('"','\"',$dataForm['nota7'])?>"
				   		}]
				   }]
	         }
      	  ]}, // fine fieldset "Dati di controllo"
 
 		 // SEZIONE DATI CONTABILI
		 {xtype:'fieldset',title: 'Dati contabili',
		  items: [
		  	{xtype:'container', layout:'column', style: 'margin-left:40px',
			 items:[
				 {xtype: 'displayfield',height: 20,columnWidth:.16, value: "Importo DBT", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.16, value: "Interessi mora", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.16, value: "Spese Legali", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.16, value: "Riscatto", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.16, value: "PDR/S&amp;S/REPO", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.17, value: "Passaggio a perdita", style:'font-size:13px;font-weight:bold'}
				 ]
			},{xtype:'container', layout:'column',style: 'margin-left:40px', 
			   default: {xtype:'panel', layout:'form', labelWidth:0, style:'text-align:center;'},
			   items:[ 
			   	{columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true, 
			   			 style: 'text-align:right; background-color:#F2F2F2; background-image:none', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',			             	
			             name: 'impDBT', id:'impDBT', readOnly: true, value: "<?php echo $dataForm['impDBT']?>"}]
			             // su indicazione del collection, è più giusto far vedere il capitale dovuto+altri addebiti: il campo impDbt
			             // della view è calcolato di conseguenza
			    },
			    {columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true, style: 'text-align:right', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impIntMora',id:'impIntMora', value: "<?php echo $dataForm['intMora']?>",
 						 listeners: {change: ricalcolaTotaliWO} 
			             }]
			    },
			    {columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true, style: 'text-align:right', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impSpeseLegali',id:'impSpeseLegali', value: "<?php echo $dataForm['speseLegali']?>",
			             listeners: {change: ricalcolaTotaliWO} 
			             }]
			    },
			    {columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true, 
			             style: 'text-align:right;  background-color:#F2F2F2; background-image:none', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impRis',id: 'impRis',  readOnly: true, value: "<?php echo $dataForm['impRis']?>"}]
			    },
			    {columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true, 
			    		 style: 'text-align:right; background-color:#F2F2F2; background-image:none', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impPdr', id: 'impPdr', readOnly: true, value: "<?php echo $dataForm['impPdr']?>"}]
			    },
			    {columnWidth: .16, items: [{xtype:'numberfield', hideLabel: true,
			    		 style: 'text-align:right; background-color:#F2F2F2; background-image:none; font-weigth:bold', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impPap', id: 'impPap', readOnly: true, value: "<?php echo $dataForm['impPap']?>"}]
			    }]
			},
			{xtype:'container', layout:'column', style: 'margin-left:40px; margin-top: 10px',
			 items:[
				 {xtype: 'displayfield',height: 20,columnWidth:.25, value: "% sval. crediti", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.25, value: "Accantonamento", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.25, value: "% sval. (riscatto)", style:'font-size:13px;font-weight:bold'},
				 {xtype: 'displayfield',height: 20,columnWidth:.25, value: "Accantonamento (riscatto)", style:'font-size:13px;font-weight:bold'}
				 ]
			},
			{xtype:'container', layout:'column',style: 'margin-left:40px', 
			   default: {xtype:'panel', layout:'form', labelWidth:0, style:'text-align:center;'},
			   items:[ 
			   	{columnWidth: .25, items: [{xtype:'numberfield', hideLabel: true, style: 'text-align:right', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',			             	
			             name: 'percSval', id:'percSval', value: "<?php echo $dataForm['percSval']?>",
			             listeners: {change: function(fld,newval,oldval) {
			             						var f2 = Ext.getCmp('impSval');
			             						f2.setValue(getFloatValue(fld.id) * getFloatValue('impPap') / 100);
			             					 }
			             			}
			             }]
			    },
			    {columnWidth: .25, items: [{xtype:'numberfield', hideLabel: true, 
			    		 style: 'text-align:right;background-color:#F2F2F2; background-image:none', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impSval',id:'impSval', readOnly: true, value: "<?php echo $dataForm['impSval']?>"}]
			    },
			   	{columnWidth: .25, items: [{xtype:'numberfield', hideLabel: true, 
			   	         style: 'text-align:right', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',			             	
			             name: 'percSvalLE',id:'percSvalLE', value: "<?php echo $dataForm['percSvalLE']?>",
			             listeners: {change: function(fld,newval,oldval) {
			             						var f2 = Ext.getCmp('impSvalLE');
			             						f2.setValue(getFloatValue(fld.id) * getFloatValue('impRis') / 100);
			             					 }
			             			}
			             }]
			    },
			    {columnWidth: .25, items: [{xtype:'numberfield', hideLabel: true, 
			    		 style: 'text-align:right;background-color:#F2F2F2; background-image:none', width: 80,
			             allowNegative: false, allowBlank: true, decimalPrecision: 2, decimalSeparator: ',',	
			             name: 'impSvalLE',id:'impSvalLE', readOnly: true, value: "<?php echo $dataForm['impSvalLE']?>"}]
			    }]
			}]	
		 }, // fine fieldset Dati contabili
		 {xtype:'panel', layout:'form', labelWidth:85, defaultType:'textarea',
			defaults: {readOnly:false, anchor:'99%'},
			items: [{
				xtype:'textarea',
				height: 60,
	            fieldLabel: 'Nota',
	            id: 'nota',
			            value: "<?php echo str_replace('"','\"',$dataForm['nota'])?>"
	            //name: 'nota'
            }]
        },
        
        {xtype:'container', layout:'column', 
        	items: [
		        {xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield', columnWidth: .35,
				  items: [{
			        xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					fieldLabel: 'Data verifica',
					value: '<?php echo $dataDefault?>',
					minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
					name: 'dataVerifica',
					id: 'dataVerifica'
				  }]
			  	},
	  			{xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield',
				  items: [{  
				    xtype: 'checkbox',
				    height: 30,
				    hideLabel:true, boxLabel: '<span style="color:blue;"><b>Elimina scadenze gi&agrave; in calendario</b></span>',
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
		]}
	]
});

//
// Pulsante di stampa
//
formPanel.addButton(
     {
			text: 'Salva e stampa',
			handler: function() {
			   var vectValue = saveWOFormDataToVect();
			   			   
			   if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
							params: {codazione: 'SSWO' , idcontratti: "<?php echo addslashes($idcontratti) ?>", 
							txtHTML: document.getElementById('frmPan').innerHTML, valuesHtml: Ext.encode(vectValue)}, 
						success: function (frm,action) {
							window.open('server/generaStampaWO.php?TitoloModello=Stampa%20Write%20Off&IdContratto=<?php echo $ids?>','_parent','');
							DCS.hideMask();
						},
						failure: function(form,action) {
							DCS.hideMask();
							saveFailure(form,action);
							}
					});
				}
			}//,scope: this
      }
  );

//
// Ricalcola i campi calcolati, all'evento "change" sui vari importi che contribuiscono
//
function ricalcolaTotaliWO(fld,newvalue,oldvalue) {
		var pdr = Ext.getCmp('impPdr');
		var pap = Ext.getCmp('impPap');
		pdr.setValue(getFloatValue('importo3a')+getFloatValue('importo4a')+getFloatValue('importo5b'));
 		//alert(getFloatValue('impDBT')+getFloatValue('impIntMora')+getFloatValue('impSpeseLegali')-getFloatValue('impPdr'));
		pap.setValue(getFloatValue('impDBT')+getFloatValue('impIntMora')+getFloatValue('impSpeseLegali')-getFloatValue('impPdr'));
		
		var impSval = Ext.getCmp('impSval');
		impSval.setValue(getFloatValue('percSval') * getFloatValue('impPap') / 100);
}

//
// Salva i campi del form in array per la scrittura del contenuto HTML su storiarecupero
//
function saveWOFormDataToVect() {
	   var vectValue = [];
			   
	   if(Ext.getCmp('dataVerifica').getValue()!='') {
	      vectValue.push({dataVerifica : Ext.getCmp('dataVerifica').getValue().format('d/m/Y')});
  	   } else {
  	       vectValue.push({dataVerifica : Ext.getCmp('dataVerifica').getValue()});
	   }  

	   vectValue.push({nota : Ext.getCmp('nota').getValue()});
	   vectValue.push({dealer: Ext.getCmp('dealer').getRawValue()});
	   vectValue.push({zona: Ext.getCmp('zona').getRawValue()});
	   vectValue.push({prodotto: Ext.getCmp('prodotto').getRawValue()});
	   vectValue.push({finanziato: Ext.getCmp('finanziato').getRawValue()});
	   vectValue.push({dataLiquidazione: Ext.getCmp('dataLiquidazione').getRawValue()});
	   vectValue.push({ratePagate: Ext.getCmp('ratePagate').getRawValue()});
	   vectValue.push({stato: Ext.getCmp('stato').getRawValue()});
	   vectValue.push({dataDBT: Ext.getCmp('dataDBT').getRawValue()});
	   vectValue.push({c1: Ext.getCmp('c1').getRawValue()});
	   vectValue.push({c2: Ext.getCmp('c2').getRawValue()});
	   vectValue.push({nota2: Ext.getCmp('nota2').getRawValue()});
	   vectValue.push({c3: Ext.getCmp('c3').getRawValue()});
	   vectValue.push({c3a: Ext.getCmp('c3a').getRawValue()});
	   vectValue.push({nota3a: Ext.getCmp('nota3a').getRawValue()});
	   vectValue.push({importo3a: Ext.getCmp('importo3a').getRawValue()});
	   vectValue.push({c4: Ext.getCmp('c4').getRawValue()});
	   vectValue.push({c4a: Ext.getCmp('c4a').getRawValue()});
	   vectValue.push({nota4a: Ext.getCmp('nota4a').getRawValue()});
	   vectValue.push({importo4a: Ext.getCmp('importo4a').getRawValue()});
	   vectValue.push({c5: Ext.getCmp('c5').getRawValue()});
	   vectValue.push({c5a: Ext.getCmp('c5a').getRawValue()});
	   vectValue.push({nota5a: Ext.getCmp('nota5a').getRawValue()});
	   vectValue.push({c5b: Ext.getCmp('c5b').getRawValue()});
	   vectValue.push({nota5b: Ext.getCmp('nota5b').getRawValue()});
	   vectValue.push({importo5b: Ext.getCmp('importo5b').getRawValue()});
	   vectValue.push({c5c: Ext.getCmp('c5c').getRawValue()});
	   vectValue.push({nota5c: Ext.getCmp('nota5c').getRawValue()});
	   vectValue.push({c6: Ext.getCmp('c6').getRawValue()});
	   vectValue.push({nota6: Ext.getCmp('nota6').getRawValue()});
	   vectValue.push({c7: Ext.getCmp('c7').getRawValue()});
	   vectValue.push({nota7: Ext.getCmp('nota7').getRawValue()});
	   vectValue.push({impDBT: Ext.getCmp('impDBT').getRawValue()});
	   vectValue.push({impIntMora: Ext.getCmp('impIntMora').getRawValue()});
	   vectValue.push({impSpeseLegali: Ext.getCmp('impSpeseLegali').getRawValue()});
	   vectValue.push({impRis: Ext.getCmp('impRis').getRawValue()});
	   vectValue.push({impPdr: Ext.getCmp('impPdr').getRawValue()});
	   vectValue.push({impPap: Ext.getCmp('impPap').getRawValue()});
	   vectValue.push({percSval: Ext.getCmp('percSval').getRawValue()});
	   vectValue.push({impSval: Ext.getCmp('impSval').getRawValue()});
	   vectValue.push({percSvalLE: Ext.getCmp('percSvalLE').getRawValue()});
	   vectValue.push({impSvalLE: Ext.getCmp('impSvalLE').getRawValue()});
	   
	   return vectValue;
}