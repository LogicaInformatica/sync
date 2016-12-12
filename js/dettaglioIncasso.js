// Crea namespace DCS
Ext.namespace('DCS');


var win;


DCS.recordIncassso = Ext.data.Record.create([
                                   		{name: 'IdIncasso', type: 'int'},
                                   		{name: 'IdContratto', type: 'int'},
                                   		{name: 'CodContratto', type: 'string'},
                                   		//{name: 'IdTipoIncasso', type: 'int'},
                                   		{name: 'TitoloTipoIncasso'},
                                   		{name: 'NumDocumento'},
                                   		{name: 'Data'},
                                   		{name: 'DataDocumento'},
                                   		{name: 'UrlAllegato',type:'string'},
                                   		{name: 'IdAllegato',type:'int'},
                                   		{name: 'ImpPagato',	type:'float'},
                                   		{name: 'IncCapitale',	convert:numdec_it,	type:'float'},
                                   		{name: 'IncInteressi',	convert:numdec_it,	type:'float'},
                                   		{name: 'IncSpese',	convert:numdec_it,	type:'float'},
                                   		{name: 'IncAltriAddebiti',	convert:numdec_it,	type:'float'},
                                   		{name: 'InsCapitale',	convert:numdec_it,	type:'float'},
                                   		{name: 'InsInteressiMora',	convert:numdec_it,	type:'float'},
                                   		{name: 'InsAltriAddebiti',	convert:numdec_it,	type:'float'},
                                   		{name: 'InsSpeseInscasso',	convert:numdec_it,	type:'float'},
                                   		{name: 'Nota',type:'string'},
                                   		{name: 'NomeCliente',type:'string'}]);
DCS.recordIncasssoArray = Ext.data.Record.create([
                                		{name: 'ImpCapitale',type:'float'}]);

DCS.DettaglioIncasso = Ext.extend(Ext.Panel, {
	idcaller:'',
	idIncassso:'',
	UrlSi:'',
	UrlNo:'',
	Url:'',
	NomeCliente:'',
	CodContratto:'',
	initComponent: function() {
		var arrRate=[];
		var dsIncasso = new Ext.data.Store({
		    proxy: new Ext.data.HttpProxy({
		    			url: 'server/gestioneIncassi.php',
		    			method: 'POST'}),   
			baseParams:{task: 'readInc',idIncasso:this.idIncasso},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordIncassso)
	    });
		var dsIncassoArray = new Ext.data.Store({
		    proxy: new Ext.data.HttpProxy({
		    			url: 'server/gestioneIncassi.php',
		    			method: 'POST'}),   
			baseParams:{task: 'readIncArr',CodContratto:this.CodContratto},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordIncasssoArray)
	    });
		
		var formIncasso = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
			fileUpload: true, 
			title: 'Pratica : ' + this.CodContratto + ' - ' + this.NomeCliente,
		    width: 450,
		    height: 470,
		    labelWidth:100,
		    trackResetOnLoad: true,
			reader: new Ext.data.ArrayReader({
				root: 'results',
				fields: DCS.recordIncassso}),
			items: [
					{	
						xtype: 'combo',
						fieldLabel: 'Tipo Pagamento',
						hiddenName: 'TitoloTipoIncasso',
						anchor: '97%',
						editable: false,
						hidden: false,
						typeAhead: false,
						triggerAction: 'all',
						lazyRender: true,
						allowBlank: false,
						store: {
						 		xtype:'store',
								proxy: new Ext.data.HttpProxy({
															   url: 'server/AjaxRequest.php',
															   method: 'POST'}),   
								baseParams:{task: 'read', sql: "SELECT IdTipoIncasso,TitoloTipoIncasso FROM tipoincasso WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY 2"},
								reader:  new Ext.data.JsonReader(
											{root: 'results',id: 'IdTipoIncasso'},
											[{name: 'IdTipoIncasso'},{name: 'TitoloTipoIncasso'}]
					            			),
								sortInfo:{field: 'TitoloTipoIncasso', direction: "ASC"}
						},
						displayField: 'TitoloTipoIncasso',
						valueField: 'TitoloTipoIncasso',
						listeners: {select: function(combo, record, index) {}}
					},		
					{	
						xtype: 'textfield',
						style: 'nowrap',
						width: 120,
						style: 'text-align:right',
						fieldLabel: 'Num.&nbsp;Documento',
						name: 'NumDocumento'
					},{	
						xtype: 'datefield',
						format: 'd/m/Y',
						width: 120,
						autoHeight:true,
						fieldLabel: 'Data Documento',
						name: 'Data',
						id: 'Data'
					},{
					    xtype: 'fileuploadfield',
//					    anchor: '97%',
					    width: 300,
					    fieldLabel: 'Allega ricevuta',
					    name: 'docPath',
					    id: 'docPath',
					    buttonText: 'Cerca',
					    editable:true,
					    hidden: this.UrlNo
					},{
			   			xtype: 'compositefield',
			            fieldLabel: 'Allegato',
			            id:"cfSi",
			            anchor: '97%',
			            hidden: this.UrlSi,
			            items: [
								{
									xtype: 'textfield', 
									anchor: '60%',
									hidden: true,
									name:'Allegato',
									id: 'Allegato',
									disabled:false,
									readOnly: true,
									style: 'text-align:left',
									value:this.Url
								},
			                    {
				                	xtype:'button',
			                    	tooltip:"Apri allegato",
									text:"Apri",
									name: 'btnApri', 
								    id: 'btnApri',
								    anchor: '30%',
								    iconCls:'grid-edit',
									hidden: this.UrlSi,
								    handler: function() {
												var url = this.Url;
												var wfeatures = 'menubar=yes,resizable=yes,scrollbars=yes,status=yes,location=yes';
												window.open(url,"Allegato",wfeatures);
												
						        	},
									scope: this
			                    },{
				                	xtype: 'displayfield', 
				                	width: 1
				                },{
			                    	xtype:'button',
									tooltip:"Elimina allegato",
									text:"Elimina",
									anchor: '20%',
									name: 'btnElimina', 
								    id: 'btnElimina',
								    iconCls:'del-row',
									hidden: this.UrlSi,
								    handler: function() {

				                	var b = Ext.getCmp('cfSi');
						 			b.setVisible(false);
						 			
						 			var a = Ext.getCmp('docPath');
						 			a.setVisible(true);
						 			
						 			Ext.getCmp('Allegato').setValue("");
						 			
						 			},
									scope: this
			                	
			                    }
			                  ]
					 },{	
						xtype:'numberfield',
						fieldLabel: 'Importo',
						allowNegative: false,
						minValue :0.01,
						allowBlank: false,
						style: 'text-align:right',
						decimalPrecision: 2,
						width: 120,
						decimalSeparator: ',',
						name: 'ImpPagato',
						id: 'ImpPagato',
						listeners: {
							
							'change': function(){
//							    var impCapitale = getFloatValue('InsCapitale');
//					        	var impSpeseincasso = getFloatValue('InsSpeseInscasso');
//					        	var impInteressi = getFloatValue('InsInteressiMora');
//					        	var impAltriAddebiti = getFloatValue('InsAltriAddebiti');
//					        	var ImpPagato = parseFloat(Ext.getCmp('ImpPagato').getValue());
//					        									
//								if (ImpPagato >= (impCapitale+impSpeseincasso+impInteressi+impAltriAddebiti)) {
//									impCapitale=ImpPagato-impSpeseincasso-impInteressi-impAltriAddebiti;
//									setFloatValue('IncInteressi',impInteressi);
//									setFloatValue('IncCapitale',impCapitale);
//									setFloatValue('IncSpese',impSpeseincasso);
//									setFloatValue('IncAltriAddebiti',impAltriAddebiti);
//								} else
//									if (ImpPagato <= impCapitale) {
//										setFloatValue('IncCapitale',ImpPagato);
//										setFloatValue('IncSpese',0);
//										setFloatValue('IncInteressi',0);
//										setFloatValue('IncAltriAddebiti',0);
//									} else
//										if ((ImpPagato > impCapitale) && (ImpPagato <= (impCapitale +impSpeseincasso))) {
//											setFloatValue('IncCapitale',impCapitale);
//											setFloatValue('IncSpese',ImpPagato-impCapitale);
//											setFloatValue('IncInteressi',0);
//											setFloatValue('IncAltriAddebiti',0);
//										} else
//											if ((ImpPagato > (impCapitale + impSpeseincasso))  && 
//												(ImpPagato <= (impCapitale + impSpeseincasso + impInteressi ))) {
//												setFloatValue('IncCapitale',impCapitale);
//												setFloatValue('IncSpese',impSpeseincasso);
//												setFloatValue('IncInteressi',ImpPagato- impCapitale - impSpeseincasso);
//												setFloatValue('IncAltriAddebiti',0);
//											} else 
//												if ((ImpPagato > (impCapitale + impSpeseincasso + impInteressi ))  && 
//													(ImpPagato <= (impCapitale + impSpeseincasso + impInteressi + impAltriAddebiti))) {
//													setFloatValue('IncCapitale',impCapitale);
//													setFloatValue('IncSpese',impSpeseincasso);
//													setFloatValue('IncInteressi',impInteressi);
//													setFloatValue('IncAltriAddebiti',ImpPagato - impCapitale - impSpeseincasso - impInteressi);
//												}
							 	var impCapitale = getFloatValue('InsCapitale');
								var impCapitaleRate = 0;
								var l=arrRate.length;
								var rata=arrRate[0];
	
						        var impSpeseincasso = getFloatValue('InsSpeseInscasso');
						        var impInteressi = getFloatValue('InsInteressiMora');
						        var impAltriAddebiti = getFloatValue('InsAltriAddebiti');
						        var importo = parseFloat(Ext.getCmp('ImpPagato').getValue());
								
								if (importo >= (impCapitale+impSpeseincasso+impInteressi+impAltriAddebiti)) {
									//console.log("maggiore");
									impCapitale=importo-impSpeseincasso-impInteressi-impAltriAddebiti;
									setFloatValue('IncInteressi',impInteressi);
									setFloatValue('IncCapitale',impCapitale);
									setFloatValue('IncSpese',impSpeseincasso);
									setFloatValue('IncAltriAddebiti',impAltriAddebiti);
								}else{
									//console.log("minore");
									if (importo <= impCapitale) {
										var divIR=importo/rata;
										//console.log("divIR "+divIR);
										if(divIR<1){
											//console.log("divIR<1 "+divIR);
											//scarica sul totale(da controllare)
											setFloatValue('IncCapitale',importo);
											setFloatValue('IncSpese',0);
											setFloatValue('IncInteressi',0);
											setFloatValue('IncAltriAddebiti',0);
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
											
											if(restoToCapitalize<=impSpeseincasso)
											{
												//console.log("restoToCapitalize<=impSpeseincasso");
												setFloatValue('IncCapitale',valueToCapitalize);
												setFloatValue('IncSpese',restoToCapitalize);
												setFloatValue('IncInteressi',0);
												setFloatValue('IncAltriAddebiti',0);
											}else{
												if(restoToCapitalize<=(impSpeseincasso + impInteressi)){
													//console.log("restoToCapitalize<=(impSpeseincasso + impInteressi)");
													setFloatValue('IncCapitale',valueToCapitalize);
													setFloatValue('IncSpese',impSpeseincasso);
													setFloatValue('IncInteressi',restoToCapitalize - impSpeseincasso);
													setFloatValue('IncAltriAddebiti',0);
												}else{
													if(restoToCapitalize<=(impSpeseincasso + impInteressi + impAltriAddebiti)){
														//console.log("restoToCapitalize<=(impSpeseincasso + impInteressi + impAltriAddebiti)");
														setFloatValue('IncCapitale',valueToCapitalize);
														setFloatValue('IncSpese',impSpeseincasso);
														setFloatValue('IncInteressi',impInteressi);
														setFloatValue('IncAltriAddebiti',restoToCapitalize - impSpeseincasso - impInteressi);
													}else{
														if(restoToCapitalize>(impSpeseincasso + impInteressi + impAltriAddebiti)){
															//console.log("restoToCapitalize>(impSpeseincasso + impInteressi + impAltriAddebiti)");
															setFloatValue('IncSpese',impSpeseincasso);
															setFloatValue('IncInteressi',impInteressi);
															setFloatValue('IncAltriAddebiti',impAltriAddebiti);
															setFloatValue('IncCapitale',valueToCapitalize+(restoToCapitalize - impSpeseincasso - impInteressi));
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
											setFloatValue('IncCapitale',impCapitale);
											setFloatValue('IncSpese',resto);
											setFloatValue('IncInteressi',0);
											setFloatValue('IncAltriAddebiti',0);
										}else{
											if(resto<=(impSpeseincasso + impInteressi)){
												//console.log("resto<=(impSpeseincasso + impInteressi)");
												setFloatValue('IncCapitale',impCapitale);
												setFloatValue('IncSpese',impSpeseincasso);
												setFloatValue('IncInteressi',resto - impSpeseincasso);
												setFloatValue('IncAltriAddebiti',0);
											}else{
												if(resto<=(impSpeseincasso + impInteressi + impAltriAddebiti)){
													//console.log("resto<=(impSpeseincasso + impInteressi + impAltriAddebiti)");
													setFloatValue('IncCapitale',impCapitale);
													setFloatValue('IncSpese',impSpeseincasso);
													setFloatValue('IncInteressi',impInteressi);
													setFloatValue('IncAltriAddebiti',resto - impSpeseincasso - impInteressi);
												}else{
													if(resto>(impSpeseincasso + impInteressi + impAltriAddebiti)){
														//console.log("resto>(impSpeseincasso + impInteressi + impAltriAddebiti)");
														setFloatValue('IncSpese',impSpeseincasso);
														setFloatValue('IncInteressi',impInteressi);
														setFloatValue('IncAltriAddebiti',impAltriAddebiti);
														setFloatValue('IncCapitale',impCapitale+(resto - impSpeseincasso - impInteressi));
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
					    title: 'Ripartizione Importo',
					    collapsible: false, 
					    anchor: '97%',
						items:[
							{
					   			xtype: 'compositefield',
					            fieldLabel: 'Capitale',
					            items: [
					                {
					                	xtype: 'displayfield', 
					                	value: '',
					                	width: 100,
					                	style: 'text-align:right',
					                	name: 'InsCapitale', 
					                	id: 'InsCapitale'
					                },{
					                	xtype: 'displayfield', 
					                	width: 20
					                },{
					                	xtype: 'textfield', 
					                	width: 100, 
					                	style: 'text-align:right',
					                	readOnly: true, 
					                	disabled: false,
					                	name: 'IncCapitale', 
					                	id: 'IncCapitale'
					                }
					            ]
							},
							{
								xtype: 'compositefield',
					            fieldLabel: 'Int. di mora',
					            items: [
					                {	
					                	xtype: 'displayfield',
					                	value: '', 
					                	width: 100,
					                	style: 'text-align:right',
					                	name: 'InsInteressiMora', 
					                	id: 'InsInteressiMora'
					                },{
					                	xtype: 'displayfield', 
					                	width: 20
					                },{
					                	xtype: 'textfield', 
					                	width: 100, 
					                	style: 'text-align:right',
					                	readOnly: true, 
					                	disabled: false,
					                	name: 'IncInteressi', 
					                	id: 'IncInteressi'
					                }
					            ]
							},
							{
								xtype: 'compositefield',
					            fieldLabel: 'Altri addebiti',
					            items: [
					                {	
					                	xtype: 'displayfield',
					                	value: '', 
					                	width: 100,
					                	style: 'text-align:right',
					                	name: 'InsAltriAddebiti', 
					                	id: 'InsAltriAddebiti'
					                },{
					                	xtype: 'displayfield', 
					                	width: 20
					                },{
					                	xtype: 'textfield', 
					                	width: 100, 
					                	style: 'text-align:right',
					                	readOnly: true, 
					                	disabled: false,
					                	name: 'IncAltriAddebiti', 
					                	id: 'IncAltriAddebiti'
					                }
					            ]
							},
							{
								xtype: 'compositefield',
					            fieldLabel: 'Spese di incasso',
					            items: [
					                {	
					                	xtype: 'displayfield',
					                	value: '', 
					                	width: 100,
					                	style: 'text-align:right',
					                	name: 'InsSpeseInscasso', 
					                	id: 'InsSpeseInscasso'
					                },{
					                	xtype: 'displayfield', 
					                	width: 20
					                },{
					                	xtype: 'textfield', 
					                	width: 100, 
					                	style: 'text-align:right',
					                	readOnly: true, 
					                	disabled: false,
					                	name: 'IncSpese', 
					                	id: 'IncSpese'
					                }
					            ]
							}
					      ]
		        		},{
							xtype:'textarea',
		            		fieldLabel: 'Nota',
		            		name: 'Nota',
		            		height: 100,
		            		anchor: '97%'
		        		}
		        ], // fine array items del form
		    buttons: 
		    	[
		    	 {
				  text: 'Salva',
				  handler: function() {
		    		 	var chiamante=this.idcaller;
		    		 	if (formIncasso.getForm().isValid()){
							this.disable();
							formIncasso.getForm().submit({
								url: 'server/gestioneIncassi.php', method: 'POST',
								params: {task:"updateInc",idIncasso:this.idIncasso},
								success: function (frm,action) {
									console.log("successo");
									var grid = Ext.getCmp(chiamante).getStore().reload(); 
									saveSuccess(win,frm,action);},
								failure: saveFailure
							});
						}
					},
					scope:this
				 }, 
				{text: 'Annulla',handler: function () {quitForm(formIncasso,win);} 
				}
			   ]  // fine array buttons
			   
		});

		Ext.apply(this, {
			items: [formIncasso]
		});
		
		DCS.DettaglioIncasso.superclass.initComponent.call(this);
		//caricamento dello store
		dsIncasso.load({
			params:{task:"readInc",idIncassso:this.idIncasso},
			callback : function(rows,options,success) {
				formIncasso.getForm().loadRecord(rows[0]);
			},
			scope:this
		});
		dsIncassoArray.load({
			params:{task: 'readIncArr',CodContratto:this.CodContratto},
			callback : function(rows,options,success) {
				//arrRate = rows;
				for(var g=0;g<rows.length;g++){
					arrRate.push(rows[g].data.ImpCapitale);
				}
			},
			scope:this
		});
	}	// fine initcomponent
});

// register xtype
Ext.reg('DCS_DettaglioIncasso', DCS.DettaglioIncasso);
		
//--------------------------------------------------------
//Visualizza dettaglio incasso
//--------------------------------------------------------
DCS.showIncassoDetail = function(){

	return {
		create: function(idcaller,idIncasso,UrlAllegato,CodContratto,NomeCliente){
		
		if(UrlAllegato=="")
		{
		 var UrlSi=true;
		 var UrlNo=false;
		}
		else
		{
			var UrlSi=false;
			var UrlNo=true;
		}

		win = new Ext.Window({
			layout: 'fit',
			width: 460,
		    height: 500,
		    //labelWidth:100,
			//plain: true,
			//bodyStyle: 'padding:5px;',
			modal: true,
			title: 'Dettaglio incasso',
			tools: [helpTool("DettaglioIncasso")],
			//constrain: true,
			flex: 1,
			items: [{
				xtype: 'DCS_DettaglioIncasso',
				idcaller:idcaller,
				idIncasso: idIncasso,
				UrlSi:UrlSi,
				UrlNo:UrlNo,
				Url:UrlAllegato,
				CodContratto:CodContratto,
				NomeCliente:NomeCliente
				}]
		});
		win.show();
		 return true;
		}
	};

}();

