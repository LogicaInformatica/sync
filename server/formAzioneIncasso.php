<?php 
require_once("workflowFunc.php");

// formAzioneData
// Genera la struttura del form di tipo "azione incasso ricevuto"

$comboPagamento = generaCombo("Tipo Pagamento","IdTipoIncasso","TitoloTipoIncasso","FROM tipoincasso WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY 2");

// ATTENZIONE, TOGLIERE questa query e sosituire con quella che prende i dati (già calcolati) dal contratto
// Non l'ho fatto per mancanza di tempo dopo la modifica del 13/3/2014 alla aggiornaCampiDerivati
$arr=getrow("select * from v_dettaglio_insoluto where idcontratto= $ids");
$arrRate=getFetchArray("select * from insoluto where idcontratto = $ids and ImpCapitale>0 and ImpInsoluto>5");

?>
var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	labelWidth: 40, frame: true, fileUpload: true, title: "<?php echo $titolo?>",
    width: 450,height: 480,labelWidth:100,
    defaults: {
            width: 322
        },
        items: [{	
					xtype: 'radiogroup',
					fieldLabel: 'Tipo Operazione',
					labelStyle: 'color:#15428B; font: bold 11px tahoma,arial,verdana,sans-serif;',
					style: 'color:red',
           			listeners: {
						change: function(group, radioChecked) {
							formPanel.ricevuta.setVisible(false);
							formPanel.incasso.setVisible(false);
							eval('formPanel.'+radioChecked.refCont+'.setVisible(true)');
						}, 
						scope: this
					},
					items: [{	
						checked: true,
        				xtype: 'radio',
						boxLabel: 'Registrazione ricevuta',
						refCont: 'ricevuta',
						name: 'flag_modalita',
						inputValue: 'E'
					},{	
        				xtype: 'radio',
						boxLabel: 'Incasso valori',
						refCont: 'incasso',
						name: 'flag_modalita',
						inputValue: 'V'
					}]
				},
        		<?php echo $comboPagamento ?>,
        		
        		{	
					xtype:'container',
					hideBorders : true,
			        ref: 'ricevuta',
			        layout : 'form',
			        width: 440,
			        defaults: {
            			width: 322
        			},
               		items:[
        		
        		{	
        			xtype: 'textfield',
        			style: 'nowrap',
        			fieldLabel: 'Num.&nbsp;Documento',
					name: 'nrDoc'
				},{	
        			xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					autoHeight:true,
					fieldLabel: 'Data Documento',
					name: 'dataDoc',
					id: 'data1'
				},{
		            xtype: 'fileuploadfield',
		            fieldLabel: 'Allega ricevuta',
		            name: 'docPath',
		            id: 'docPath1',
		            buttonText: 'Cerca'
         		}]},
         		{	    
         			xtype: 'compositefield',
         			hidden: true,
			        ref: 'incasso',
//			        width: 430,
				    items: [
        		{
        			xtype: 'datefield',
					format: 'd/m/Y',
					width: 100,
					autoHeight:true,
					fieldLabel: 'Data Operazione',
					value: new Date(),
					name: 'dataOp',
					id: 'data2'
				},{
		            xtype: 'displayfield',
					width: 40
				},{
		            xtype: 'button',
					width: 100,
		            text: 'Stampa ricevuta'
         		}]},
         		
         		
         		
         		
         		
         		{
					xtype:'numberfield',
            		fieldLabel: 'Importo',
            		allowNegative: false,
            		minValue :0.01,
            		allowBlank: false,
            		style: 'text-align:right',
            		decimalPrecision: 2,
            		width: 100,
            		decimalSeparator: ',',
            		name: 'importo',
            		id: 'importo',
            		blankText: 'totale pagato',
            		listeners: {
            		
            			'change': function(field, newValue, oldValue){
							var impCapitale = getFloatValue('capitaleD');
							var impCapitaleRate = 0;
							var l=<?php echo count($arrRate)?>;
							var rata=<?php echo $arrRate[0]['ImpCapitale']?>;

					        var impSpeseincasso = getFloatValue('speseincassoD');
					        var impInteressi = getFloatValue('interessiMoraD');
					        var impAltriAddebiti = getFloatValue('altriAddebitiD');
					        var importo = parseFloat(newValue);
													
							if (importo >= (impCapitale+impSpeseincasso+impInteressi+impAltriAddebiti)) {
								//console.log("maggiore");
								impCapitale=importo-impSpeseincasso-impInteressi-impAltriAddebiti;
								setFloatValue('interessiMoraI',impInteressi);
								setFloatValue('capitaleI',impCapitale);
								setFloatValue('speseIncassoI',impSpeseincasso);
								setFloatValue('altriAddebitiI',impAltriAddebiti);
							}else{
								//console.log("minore");
								if (importo <= impCapitale) {
									var divIR=importo/rata;
									//console.log("divIR "+divIR);
									if(divIR<1){
										//console.log("divIR<1 "+divIR);
										//scarica sul totale(da controllare)
										setFloatValue('capitaleI',importo);
										setFloatValue('speseIncassoI',0);
										setFloatValue('interessiMoraI',0);
										setFloatValue('altriAddebitiI',0);
									}else{
										//almeno 1 rata
										var intDivIR = Ext.util.Format.number(divIR,"0");
										var floatDivIR = divIR-intDivIR;
										if(floatDivIR<0){
											intDivIR=intDivIR-1;
											floatDivIR=(1+(floatDivIR));
										}
										var valueToCapitalize = intDivIR*rata;	
										var restoToCapitalize = floatDivIR*rata;
										
										/*console.log("intDivIR "+intDivIR);
										console.log("floatDivIR "+floatDivIR);
										console.log("valueToCapitalize "+valueToCapitalize);
										console.log("restoToCapitalize "+restoToCapitalize);*/
										
										if(restoToCapitalize<=impSpeseincasso)
										{
											//console.log("restoToCapitalize<=impSpeseincasso");
											setFloatValue('capitaleI',valueToCapitalize);
											setFloatValue('speseIncassoI',restoToCapitalize);
											setFloatValue('interessiMoraI',0);
											setFloatValue('altriAddebitiI',0);
										}else{
											if(restoToCapitalize<=(impSpeseincasso + impInteressi)){
												//console.log("restoToCapitalize<=(impSpeseincasso + impInteressi)");
												setFloatValue('capitaleI',valueToCapitalize);
												setFloatValue('speseIncassoI',impSpeseincasso);
												setFloatValue('interessiMoraI',restoToCapitalize - impSpeseincasso);
												setFloatValue('altriAddebitiI',0);
											}else{
												if(restoToCapitalize<=(impSpeseincasso + impInteressi + impAltriAddebiti)){
													//console.log("restoToCapitalize<=(impSpeseincasso + impInteressi + impAltriAddebiti)");
													setFloatValue('capitaleI',valueToCapitalize);
													setFloatValue('speseIncassoI',impSpeseincasso);
													setFloatValue('interessiMoraI',impInteressi);
													setFloatValue('altriAddebitiI',restoToCapitalize - impSpeseincasso - impInteressi);
												}else{
													if(restoToCapitalize>(impSpeseincasso + impInteressi + impAltriAddebiti)){
														//console.log("restoToCapitalize>(impSpeseincasso + impInteressi + impAltriAddebiti)");
														setFloatValue('speseIncassoI',impSpeseincasso);
														setFloatValue('interessiMoraI',impInteressi);
														setFloatValue('altriAddebitiI',impAltriAddebiti);
														setFloatValue('capitaleI',valueToCapitalize+(restoToCapitalize - impSpeseincasso - impInteressi));
													}
												}
											}	
										}					
									}																		
								}else{
								 	var resto = importo-impCapitale;
									if(resto<=impSpeseincasso)
									{
										//console.log("resto<=impSpeseincasso");
										setFloatValue('capitaleI',impCapitale);
										setFloatValue('speseIncassoI',resto);
										setFloatValue('interessiMoraI',0);
										setFloatValue('altriAddebitiI',0);
									}else{
										if(resto<=(impSpeseincasso + impInteressi)){
											//console.log("resto<=(impSpeseincasso + impInteressi)");
											setFloatValue('capitaleI',impCapitale);
											setFloatValue('speseIncassoI',impSpeseincasso);
											setFloatValue('interessiMoraI',resto - impSpeseincasso);
											setFloatValue('altriAddebitiI',0);
										}else{
											if(resto<=(impSpeseincasso + impInteressi + impAltriAddebiti)){
												//console.log("resto<=(impSpeseincasso + impInteressi + impAltriAddebiti)");
												setFloatValue('capitaleI',impCapitale);
												setFloatValue('speseIncassoI',impSpeseincasso);
												setFloatValue('interessiMoraI',impInteressi);
												setFloatValue('altriAddebitiI',resto - impSpeseincasso - impInteressi);
											}else{
												if(resto>(impSpeseincasso + impInteressi + impAltriAddebiti)){
													//console.log("resto>(impSpeseincasso + impInteressi + impAltriAddebiti)");
													setFloatValue('speseIncassoI',impSpeseincasso);
													setFloatValue('interessiMoraI',impInteressi);
													setFloatValue('altriAddebitiI',impAltriAddebiti);
													setFloatValue('capitaleI',impCapitale+(resto - impSpeseincasso - impInteressi));
												}
											}
										}	
									}
								}
							}
					   	}
	               	}
        		},{
					xtype:'fieldset',
			        title: 'Ripartizione Importo (si pu&ograve; modificare)',
			        collapsible: false,
			        labelWidth: 110,
			        width: 426,
               		items:[
               			{
	               			xtype: 'compositefield',
	                        fieldLabel: 'Capitale',
	                        items: [
	                            {
	                            	xtype: 'displayfield', 
	                            	value: '<?php echo number_format($arr[Capitale], 2, ',', '.'); ?>',
	                            	width: 100,
	                            	style: 'text-align:right',
	                            	name: 'capitaleD', 
	                            	id: 'capitaleD'
	                            },{
	                            	xtype: 'displayfield', 
	                            	width: 20
	                            },{
	                            	xtype: 'numberfield', 
				            		allowNegative: false,
				            		minValue :0.00,
				            		allowBlank: false,
				            		style: 'text-align:right',
				            		decimalPrecision: 2,
				            		width: 100,
				            		decimalSeparator: ',',
	                            	name: 'capitaleI', 
	                            	id: 'capitaleI',
				            		listeners: {'change': ricalcolaTotaleIncassato}
	                            }
	                        ]
               			},
               			{
               				xtype: 'compositefield',
	                        fieldLabel: 'Interessi di mora',
	                        items: [
	                            {	
	                            	xtype: 'displayfield',
	                            	value: '<?php echo number_format($arr[InteressiMora], 2, ',', '.'); ?>', 
	                            	width: 100,
	                            	style: 'text-align:right',
	                            	name: 'interessiMoraD', 
	                            	id: 'interessiMoraD'
	                            },{
	                            	xtype: 'displayfield', 
	                            	width: 20
	                            },{
	                            	xtype: 'numberfield', 
				            		allowNegative: false,
				            		minValue :0.00,
				            		allowBlank: false,
				            		style: 'text-align:right',
				            		decimalPrecision: 2,
				            		width: 100,
				            		decimalSeparator: ',',
	                            	name: 'interessiMoraI', 
	                            	id: 'interessiMoraI',
				            		listeners: {'change': ricalcolaTotaleIncassato}
	                            }
	                        ]
               			},
               			{
               				xtype: 'compositefield',
	                        fieldLabel: 'Altri addebiti',
	                        items: [
	                            {	
	                            	xtype: 'displayfield',
	                            	value: '<?php echo number_format($arr[AltriAddebiti], 2, ',', '.'); ?>', 
	                            	width: 100,
	                            	style: 'text-align:right',
	                            	name: 'altriAddebitiD', 
	                            	id: 'altriAddebitiD'
	                            },{
	                            	xtype: 'displayfield', 
	                            	width: 20
	                            },{
	                            	xtype: 'numberfield', 
				            		allowNegative: false,
				            		minValue :0.00,
				            		allowBlank: false,
				            		style: 'text-align:right',
				            		decimalPrecision: 2,
				            		width: 100,
				            		decimalSeparator: ',',
	                            	name: 'altriAddebitiI', 
	                            	id: 'altriAddebitiI',
				            		listeners: {'change': ricalcolaTotaleIncassato}
	                            }
	                        ]
               			},
               			{
               				xtype: 'compositefield',
	                        fieldLabel: 'Spese di recupero',
	                        items: [
	                            {	
	                            	xtype: 'displayfield',
	                            	value: '<?php echo number_format($arr[Speseincasso], 2, ',', '.'); ?>', 
	                            	width: 100,
	                            	style: 'text-align:right',
	                            	name: 'speseincassoD', 
	                            	id: 'speseincassoD'
	                            },{
	                            	xtype: 'displayfield', 
	                            	width: 20
	                            },{
	                            	xtype: 'numberfield', 
				            		allowNegative: false,
				            		minValue :0.00,
				            		allowBlank: false,
				            		style: 'text-align:right',
				            		decimalPrecision: 2,
				            		width: 100,
				            		decimalSeparator: ',',
	                            	name: 'speseIncassoI', 
	                            	id: 'speseIncassoI',
				            		listeners: {'change': ricalcolaTotaleIncassato}
	                            }
	                        ]
               			}
               	      ]
        		},{
					xtype:'textarea',
            		fieldLabel: 'Nota',
            		name: 'nota',
            		height: 90
        		}],
    buttons: [{
			text: 'Salva',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {
							var inT = "<?php 	echo 'p'.substr(str_replace('&nbsp;','',$titolo),1);?>";
							var testo="Pagamento ricevuto su "+inT+". Nota: "+frm.findField('nota').getValue();
							Ext.Ajax.request({
						        url: 'server/edit_note.php',
						        method: 'POST',
						        params: {task: 'save',IdContratto:<?php echo $ids?>,TipoNota:'N',TipoDestinatario:'T',TestoNota:testo},
						        success: function(obj) {
						        	console.log("salvato");
								},
								failure: function (obj) {
	                    		},
								scope: this
						    });
							saveSuccess(win,frm,action);
						},
						failure: saveFailure
					});
				}	
			}//,scope: this
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 
		}]  // fine array buttons
});

//
// ricalcolaTotaleIncassato: funzione per ricalcolare il campo "Importo" quando l'utente cambia uno dei campi di 
// ripartizione (è collagata all'evento 'change' dei campi, per cui riceve i tre argomenti indicati)
//
function ricalcolaTotaleIncassato(field, newValue, oldValue) {
    var totale = parseFloat(Ext.getCmp('importo').getValue());
    oldValue = parseFloat(oldValue);
    newValue = parseFloat(newValue);
    totale   += newValue-oldValue;
	setFloatValue('importo',totale);
}
