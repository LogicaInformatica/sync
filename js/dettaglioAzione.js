// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

/**
 * RECORDS
 * */
DCS.recordAzione = Ext.data.Record.create([
		{name: 'IdAzione', type: 'int'},
		{name: 'IdFunzione', type: 'int'},
		{name: 'IdProcedura', type: 'int'},
		{name: 'IdStatoRecupero', type: 'int'},
		{name: 'Condizione'},
		{name: 'TitoloProcedura'},
		{name: 'CodAzione'},
		//{name: 'CodAzioneLegacy'},
		{name: 'TitoloAzione'},
		{name: 'TipoFormAzione'},
		{name: 'FlagSpeciale'},
		{name: 'PercSvalutazione'},
		{name: 'FlagAllegato'},
		{name: 'FlagAllegatoDesc'},
		{name: 'FormWidth', type: 'int'},
		{name: 'FormHeight', type: 'int'},
		{name: 'GiorniEvasione', type: 'int'},
		{name: 'LastUser'},
		{name: 'FlagMultipla', convert: bool_db}]);
DCS.recordChkProc = Ext.data.Record.create([
        {name: 'IdProcedura', type: 'int', allowBlank:false},
  		{name: 'TitoloProcedura', type: 'string' }]);
DCS.recordChkFunc = Ext.data.Record.create([
        {name: 'IdTipoAzione', type: 'int', allowBlank:false},
  		{name: 'TitoloTipoAzione', type: 'string' }]);
DCS.recordChkAut = Ext.data.Record.create([
        {name: 'IdAutomatismo', type: 'int', allowBlank:false},
  		{name: 'TitoloAutomatismo', type: 'string' }]);
DCS.recordAllegato = Ext.data.Record.create([
	    {name: 'FlagAllegato', type: 'string'},
		{name: 'FlagAllegatoDesc', type: 'string' }]);

/*****************************
 * CLASSE DCS DETTAGLIO AZIONE
 *****************************/
DCS.DettaglioAzione = Ext.extend(Ext.TabPanel, {
	idAz: '',
	listStore: null,
	rowIndex: '',
	nome:'',
	loadAfterCC:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idUtente in caso di nuovo utente 
		var mainGridStore=this.listStore;
		var roundGridStore=this.listStore;
		var procVisible=false;
		var ArrReload = this.loadAfterCC;
		var IdMain = this.getId();
		var rowIndexToSon = this.rowIndex;
		var titoloAzione = this.nome;
		var azioneId = this.idAz;
		var TooltipCC="Specifica una condizione comprendente più stati e variabili.";
		if(this.idAz=='')
		{
			procVisible=true;
			roundGridStore=null;
			this.listStore=null;
			this.rowIndex='';
		}
		/**-------------------------
		 * STORES
		 *------------------------ */
		var dsAzione = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordAzione)
		});
				
		var dsFormAz = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdFormA,TipoFormAzione from v_azioni_semplici_forms"
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdFormA'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdFormA'},
				 {name: 'TipoFormAzione'}]
			)
			//,autoLoad: true
		});//end dsFormAz 
		
		var dsFMultiplo = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "select distinct FlagMultipla from azione order by FlagMultipla desc" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'FlagMultipla'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'FlagMultipla'}]
			),
			autoLoad: true
		});//end dsFMultiplo 
		
		var dsStatoRecupero = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdStatoRecupero,TitoloStatoRecupero FROM statorecupero union all select -1,'' order by TitoloStatoRecupero asc" 
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdStatoRecupero'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdStatoRecupero'},
				{name: 'TitoloStatoRecupero'}]
			)
			//,autoLoad: true,
		});//end dsStatoRecupero 
		
		var dsStoreAllegato = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "select FlagAllegato,case when FlagAllegato='Y' then 'Obbligatorio' when FlagAllegato='N' then 'Non obbligatorio' else FlagAllegato end as FlagAllegatoDesc from azione where FlagAllegato is not null group by FlagAllegato;" 
			},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordAllegato
			})
		});
		
		var ckStoreFunc = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneGridAzioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readProcCk', who:'tipoazioni'},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordChkFunc
			})
		});
		
		var ckStoreAut = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneGridAzioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readProcCk', who:'Automatismi'},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordChkAut
			})
		});
		
		/**------------------------------------
		 * CheckGroup e array di configurazione
		 *----------------------------------- */
		//PROCEDURE
		//var flagInterruzioneChange = false;
		/*var checkboxconfigsPROC = []; //array of about to be checkboxes.   
		var CheckProcGroup = new Ext.form.CheckboxGroup({
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Procedura',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigsPROC],
		    listeners:{
				change:function(CheckProcGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckProcGroup.getValue();
					for(k=0;k<arr.length;k++)
		        	{
						for(j=0;j<checkboxconfigsPROC.length;j++)
		            	{
							if(arr[k].name==checkboxconfigsPROC[j].name)
							{
								checkboxconfigsPROC[j].checked = arr[k].checked;
								break;
							}
		            	}
		        	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigsPROC.length;j++)
		        	{
						flag = false;
						if(checkboxconfigsPROC[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].name==checkboxconfigsPROC[j].name)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigsPROC[j].checked = false;}
						}
		        	}
				}
			}
		});*/
		
		//TIPI AZIONE
		var checkboxconfigsFUNC = []; //array of about to be checkboxes.   
		var CheckFuncGroup = new Ext.form.CheckboxGroup({
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Tipo Azione',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigsFUNC],
		    listeners:{
				change:function(CheckFuncGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckFuncGroup.getValue();
					for(k=0;k<arr.length;k++)
		        	{
						for(j=0;j<checkboxconfigsFUNC.length;j++)
		            	{
							if(arr[k].name==checkboxconfigsFUNC[j].name)
							{
								checkboxconfigsFUNC[j].checked = arr[k].checked;
								break;
							}
		            	}
		        	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigsFUNC.length;j++)
		        	{
						flag = false;
						if(checkboxconfigsFUNC[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].name==checkboxconfigsFUNC[j].name)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigsFUNC[j].checked = false;}
						}
		        	}
					
					/*for(j=0;j<checkboxconfigsFUNC.length;j++)
		        	{
						console.log("elemento "+checkboxconfigsFUNC[j].name+" |chkd "+checkboxconfigsFUNC[j].checked);
		        	}*/
				}
			}
		});
		
		//AUTOMATISMI
		var checkboxconfigsAUTO = []; //array of about to be checkboxes.   
		var CheckAutoGroup = new Ext.form.CheckboxGroup({
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Automatismi',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigsAUTO],
		    listeners:{
				change:function(CheckAutoGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckAutoGroup.getValue();
					for(k=0;k<arr.length;k++)
		        	{
						for(j=0;j<checkboxconfigsAUTO.length;j++)
		            	{
							if(arr[k].name==checkboxconfigsAUTO[j].name)
							{
								checkboxconfigsAUTO[j].checked = arr[k].checked;
								break;
							}
		            	}
		        	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigsAUTO.length;j++)
		        	{
						flag = false;
						if(checkboxconfigsAUTO[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].name==checkboxconfigsAUTO[j].name)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigsAUTO[j].checked = false;}
						}
		        	}
				}
			}
		});
		
		/**------------------------------------
		 * BOTTONI
		 *----------------------------------- */
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsAzione,
			text: 'Salva',
			id:'btnSave',
			handler: function(b,event) 
			{
				/*var vect = '';
				for(j=0;j<checkboxconfigsPROC.length;j++)
	        	{if(checkboxconfigsPROC[j].checked == true)
	            	{vect = vect + '|' + checkboxconfigsPROC[j].name;}}*/
				Ext.getCmp('cAz').setDisabled(false);
				Ext.getCmp('cAzL').setDisabled(false);
				Ext.getCmp('chkFmul').setDisabled(false);
				Ext.getCmp('cmbFazione').setDisabled(false);
				var vectF = '';
				for(l=0;l<checkboxconfigsFUNC.length;l++)
	        	{
	            	if(checkboxconfigsFUNC[l].checked == true)
	            	{
	            		vectF = vectF + '|' + checkboxconfigsFUNC[l].name;
	            	}
	        	}
				var vectA = '';
				for(l=0;l<checkboxconfigsAUTO.length;l++)
	        	{
	            	if(checkboxconfigsAUTO[l].checked == true)
	            	{
	            		vectA = vectA + '|' + checkboxconfigsAUTO[l].name;
	            	}
	        	}
				
				//controllo sulla bontà delle condizioni
				var condizione='';
				var complesso=false;
				var t='';
				var v = Ext.getCmp('cmbStatoRec').getValue();
				var patt=new RegExp('[A-z]');
				if(!patt.test(v))
				{
					if(Ext.getCmp('cmbStatoRec').getValue()!=-1)
					{
						condizione = "IdStatoRecupero IN("+Ext.getCmp('cmbStatoRec').getValue()+")";
						if(Ext.getCmp('CondId').getValue()!='')
						{
							condizione=condizione+" and(true) and (";
							t=')';
						}
					}
					if(Ext.getCmp('CondId').getValue()!='')
					{
						condizione=condizione+Ext.getCmp('CondId').getValue()+t;
					}
				}else{
					complesso=true;
					if(Ext.getCmp('cmbStatoRec').getValue()!='' && Ext.getCmp('cmbStatoRec').getValue()!=null)
					{
						condizione = Ext.getCmp('cmbStatoRec').getValue();
						if(Ext.getCmp('CondId').getValue()!='')
						{
							condizione=condizione+" and(true) and (";
							t=')';
						}else{
							condizione=condizione+" and(true)";
						}
					}
					if(Ext.getCmp('CondId').getValue()!='')
					{
						condizione=condizione+Ext.getCmp('CondId').getValue()+t;
					}
				}
				//console.log("condizione "+condizione);
				var where='';
				if(condizione!='')
					where='where';
				Ext.Ajax.request({
					url: 'server/AjaxRequest.php', 
            		params : {	task: 'read',
								sql: "SELECT count(*) as num from contratto "+where+" "+condizione
							},
					method: 'POST',
					reader:  new Ext.data.JsonReader(
            					{
            						root: 'results',//name of the property that is container for an Array of row objects
            						id: 'num'//the property within each row object that provides an ID for the record (optional)
            					},
            					[{name: 'num'}]
            				),
        			success: function ( result, request ) {
						eval('var resp = ('+result.responseText+').results[0]');
						if(resp != undefined)
						{
							if(complesso){
								Ext.getCmp('CondId').setValue(condizione);
								Ext.getCmp('cmbStatoRec').setValue(-1);
							}
							Ext.getCmp('btnSave').setDisabled(true);
							//salvataggio/edit azione
							//if (formPAz.getForm().isDirty()) {	// qualche campo modificato
								formPAz.getForm().submit({
									url: 'server/gestioneGridAzioni.php',
							        method: 'POST',
							        params: {task:'saveAz'/*,vect: vect*/,vectF:vectF,vectA:vectA,idAz:azioneId},
							        success: function(frm, action) {
							        	//eval('var resp = '+obj.responseText);
							        	if(action.result.success){
							        		Ext.MessageBox.alert('Esito', "Azione salvata");
							        	}else{
							        		Ext.MessageBox.alert('Fallito', "Impossibile salvare l'azione: "+action.result.error);
							        	}
							        	Ext.getCmp('cAz').setDisabled(true);
										Ext.getCmp('cAzL').setDisabled(true);
										Ext.getCmp('chkFmul').setDisabled(true);
										Ext.getCmp('cmbFazione').setDisabled(true);
										Ext.getCmp('btnSave').setDisabled(false);
							        	//if(win.getComponent(0).idAz==0){
							        		win.close();
							        		mainGridStore.reload();
							        	//}
									},
									failure: function(frm, action){
										if(action.result==undefined)
										{
											Ext.Msg.alert('Errore', "Non sono stati scelti tutti i valori minimi necessari alla definizione dell'automatismo.");
										}else{
											Ext.Msg.alert('Errore', action.result.error);
										}
										Ext.getCmp('cAz').setDisabled(true);
										Ext.getCmp('cAzL').setDisabled(true);
										Ext.getCmp('chkFmul').setDisabled(true);
										Ext.getCmp('cmbFazione').setDisabled(true);
										//.MessageBox.alert('Esito', "Utente non salvato");
									},
									waitMsg: 'Salvataggio in corso...'
								});
							/*}else{
								console.log("no change");
							}*/
						}else{
							Ext.MessageBox.alert('Errore', 'Vi è un errore nelle condizioni applicate.');
						}								
					},
            		failure: function ( result, request) { 
            			Ext.MessageBox.alert('Errore', 'Errore durante l\'esecuzione dell\' interrogazione al database.'); 
            		},
            		autoLoad: true
            	});
				
			},
			scope: this
		});
		
		/**------------------------------------
		 * FORMS
		 *----------------------------------- */
		//Form su cui montare gli elementi
		var formPAz = new Ext.form.FormPanel({
			title:'Dati Azione',		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordAzione
			}),
			items: [{
					xtype:'container', layout:'column',
					items: [{//colonna sinistra
							xtype:'container',columnWidth:.50,
							items:[{//oggetto primo
												xtype:'container', layout:'column',
												items:[{
													xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{fieldLabel:'Nome', name:'TitoloAzione', id:'Tazione', style:'text-align:left',allowBlank: false}]
												}]
										},{
												xtype:'container', layout:'column',
												items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98, defaultType:'textfield',
														defaults: {anchor:'97%', readOnly:false},
														items: [{fieldLabel:'Cod. Azione',id:'cAz', name:'CodAzione', style:'text-align:left',allowBlank: false}]
												}]										
										},{
												xtype:'container', layout:'column',
												items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98, defaultType:'textfield',
														defaults: {anchor:'97%', readOnly:false},
														items: [{hidden:true, fieldLabel:'Cod. Az. Legacy',id:'cAzL',name:'CodAzioneLegacy', style:'text-align:left'},
														        {fieldLabel:'Procedura',id:'txtProc',name:'TitoloProcedura', style:'text-align:left',editable:false,hidden:procVisible}]
												}]
										},{
											xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98,defaultType:'combo',
											defaults: {anchor:'98%', readOnly:false},
											items: [{
														xtype:'container',
														items:[{
															xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98,defaultType:'combo',
															defaults: {anchor:'97%', readOnly:false},
															items: [{
																xtype: 'combo',
																fieldLabel: 'Form Azione',
																name:'CFazione',
																id:'cmbFazione',
																allowBlank: true,
																hiddenName: 'CFazione',
																typeAhead: false,
																editable:false,
																triggerAction: 'all',
																lazyRender: true,	//should always be true for editor
																store: dsFormAz,
																displayField: 'TipoFormAzione',
																valueField: 'IdFormA'
															}]
														}]
													},{
														xtype:'container',
														items:[{
															xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98,defaultType:'combo',
															defaults: {anchor:'97%', readOnly:false},
															items: [{
																//labelStyle: 'width:300;',
																style: 'padding-left:0px; anchor:"0%";',
								           						xtype: 'checkbox',
																boxLabel: 'Azione Multipla',
																id:'chkFmul',
																name:'FlagMultipla',
																hiddenName: 'FlagMultipla',
																hidden: false,
																checked: false
															}]
														}]
													}]
											},{
												xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98,defaultType:'textfield',
												defaults: {anchor:'98%', readOnly:false},
												items: [{
														xtype:'container', layout:'column',
														items:[{
															xtype:'panel', layout:'form', labelWidth:120,columnWidth:0.77,
															defaults: {anchor:'99%', readOnly:false},
															items: [{xtype: 'combo',
																fieldLabel: 'Eseguibile solo in',
																name:'StatoRecupero',
																id:'cmbStatoRec',
																allowBlank: true,
																hiddenName: 'StatoRecupero',
																typeAhead: false, 
																forceSelection:true,
																editable:false,
																triggerAction: 'all',
																lazyRender: true,	//should always be true for editor
																store: dsStatoRecupero,
																displayField: 'TitoloStatoRecupero',
																valueField: 'IdStatoRecupero',												
																listeners:{
																	scope:this,
																	select:function(combo, record, index){
																		
																	}
																}
															}]
														},{
															xtype: 'panel',	layout: 'form',	columnWidth: 0.22,
															defaults: {anchor:'99%', readOnly:false},
															items: [{
											                	xtype:'button',
										                    	tooltip:TooltipCC,
																text:"Condiz. complessa",
																name: 'btnApriCondC', 
															    id: 'btnApriCondC',
															    disabled:true,
															    anchor: '30%',
															    handler: function() 
															    {
																	var ArrSaveStateFields=[];
																	ArrSaveStateFields.push(Ext.getCmp('Tazione').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('cAz').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('cAzL').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('cmbFazione').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('chkFmul').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('cmbStatoRec').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('CondId').getValue());
																	ArrSaveStateFields.push(checkboxconfigsFUNC);
																	ArrSaveStateFields.push(checkboxconfigsAUTO);
																	ArrSaveStateFields.push(azioneId);
																	ArrSaveStateFields.push(Ext.getCmp('chkSpe').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('cmbAllegato').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('evasione').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('Flarg').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('Falt').getValue());
																	ArrSaveStateFields.push(Ext.getCmp('pSvalutazione').getValue());
																	showCCmxDetail(win,IdMain,true,ArrSaveStateFields,mainGridStore,rowIndexToSon,azioneId,titoloAzione,'isAzRule');
																	win.close();
													        	},
																scope: this
										                    }]
														}]
												},{
													xtype:'container', layout:'column',
													items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:.99, defaultType:'textfield',
														defaults: {anchor:'99%', readOnly:false},
														items: [{fieldLabel:'Condizione aggiunta',	name:'Condizione', id:'CondId', style:'text-align:left'}]
													}]
												},{
													xtype:'container', layout:'column',
													items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98,defaultType:'combo',
														defaults: {anchor:'97%', readOnly:false},
														items: [{
															//labelStyle: 'width:300;',
															style: 'padding-left:0px; anchor:"0%";',
							           						xtype: 'checkbox',
															boxLabel: 'Azione Speciale',
															id:'chkSpe',
															name:'speciale',
															hiddenName: 'speciale',
															hidden: false,
															checked: false
														}]
													}]
												},{
													xtype:'container', layout:'column',
													items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:0.77,
														defaults: {anchor:'99%', readOnly:false},
														items: [{xtype: 'combo',
															fieldLabel: 'Allegato',
															name:'allegato',
															id:'cmbAllegato',
															allowBlank: true,
															hiddenName: 'allegato',
															typeAhead: false, 
															forceSelection:true,
															editable:false,
															triggerAction: 'all',
															lazyRender: true,	//should always be true for editor
															store: dsStoreAllegato,
															displayField: 'FlagAllegatoDesc',
															valueField: 'FlagAllegato',												
															listeners:{
																scope:this,
																select:function(combo, record, index){
																	
																}
															}
														}]
													}]
												},
												{
													xtype:'container', layout:'column',
													items:[{
														xtype:'panel', layout:'form', labelWidth:120,columnWidth:.50,defaultType:'textfield',
														defaults: {anchor:'97%', readOnly:false},
														items: [{fieldLabel:'Giorni evasione',	name:'GiorniEvasione', id:'evasione', style:'text-align:left'}]
													}]
												},{
													xtype:'panel', layout:'form', labelWidth:150,defaultType:'numberfield',
													defaults: {anchor:'50%', readOnly:false},
													items: [{fieldLabel:'Percentuale svalutazione',	
															name:'PercSvalutazione', 
															id:'pSvalutazione', 
															style:'text-align:left',
															allowNegative: false,
															minValue :0,
															decimalPrecision: 0,
															maxLength:3,
															maxLengthText:'Numero di massimo tre cifre'}]
												}]
											},{
												xtype:'fieldset', id:'fsForms',title:'Form azione', border:false, bodyStyle: 'padding-left:5px;',
												items:[{
													xtype:'panel', layout:'form', labelWidth:80,columnWidth:.50, defaultType:'textfield',
													defaults: {anchor:'50%', readOnly:false},
													items: [{fieldLabel:'Larghezza', name:'FormWidth', id:'Flarg', style:'text-align:left',allowBlank: true}]
												},{
													xtype:'panel', layout:'form', labelWidth:80,columnWidth:.50, defaultType:'textfield',
													defaults: {anchor:'50%', readOnly:false},
													items: [{fieldLabel:'Altezza', name:'FormHeight', id:'Falt', style:'text-align:left',allowBlank: true}]
												}]
											}]//fine ogg primo
					//fine oggetti colonna sinistra
					},/*{		//colonna destra
							xtype:'fieldset', id:'fsCProc', autoScroll:true, height:320, title:'Procedure', border:true, layout:'column',columnWidth:.23,bodyStyle: 'padding-left:5px;',
							items:[]																							
					},*/{		//colonna destra
							xtype:'fieldset', id:'fsCFunc', autoScroll:true, height:350, title:'Tipi Azione', border:true, layout:'column',columnWidth:.20,bodyStyle: 'padding-left:5px;',
							items:[]
					},{		//colonna destra
							xtype:'fieldset', id:'fsCAuto', autoScroll:true, height:350, title:'Automatismi', border:true, layout:'column',columnWidth:.30,bodyStyle: 'padding-left:5px;',
							items:[]
					}]
			}],
			buttons:[save,{text: 'Annulla',handler: function () {win.close()}}]
		});
		
		// Indice del record nello store della lista
		var indexStore = this.rowIndex;
		if (this.listStore!=null && (this.listStore.lastOptions.params||{}).start != undefined)
			{indexStore += this.listStore.lastOptions.params.start;	}
		// Indice dell'ultimo record nello store della lista
		var lastRec = (this.listStore!=null?this.listStore.getTotalCount()-1:indexStore);
//		var si = this.listStore.getSortState();
		
		// Funzione che gestisace la pressione dei bottoni precedente/successivo
		var dettaglio_nextprev = function(btn) {
			var p = this.listStore.lastOptions.params || {};		// parametri di lettura dello store
			var newIndex = this.rowIndex + (btn.getItemId()=='btnPrev'?-1:+1);	// nuovo indice del record nella pagina
			var flg_reload = false;				// flag per eventuale caricamento pagina 
			if (p.start != undefined) {	// paginata
				if (newIndex < 0) {					// precedente da inizio pagina?
					p.start -= p.limit;
					flg_reload = true;
				} else
					if (newIndex >= p.limit) {		// successivo da fine pagina?
						p.start += p.limit;
						flg_reload = true;
					}
			}
			if (flg_reload) {					// richiesto record fuori pagina: deve caricarla 
				this.listStore.load({
					params:p, 
					callback : function(rows,options,success) {
						if (success) {			// mostra dettaglio record richiesto
							var newIndex = this.rowIndex==0?options.params.limit-1:0;
							var rec = rows[newIndex]; //this.listStore.getAt(newIndex);
							showAzDetail(rec.get('IdAzione'),rec.get('TitoloAzione'),roundGridStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showAzDetail(rec.get('IdAzione'),rec.get('TitoloAzione'),roundGridStore,newIndex);
			}
		};

		/**------------------------------------
		 * APPLY DELLA CLASSE
		 *----------------------------------- */
		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formPAz],
	        tbar: new Ext.Toolbar({
				items:[
					'->',{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSepar'},
						{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSeparExp'},
					//'-',
						{type:'button', text:'Precedente',
							itemId:'btnPrev',
							iconCls:'icon-prev',
							disabled: (indexStore<=0),
							disabledClass: 'x-item-disabled',
							handler:dettaglio_nextprev,
							scope:this},
					'-',{type:'button', text:'Seguente',
							itemId:'btnNext',
							iconCls:'icon-next',
							disabled: (indexStore >= lastRec),
							disabledClass: 'x-item-disabled',
							handler:dettaglio_nextprev,
							scope:this},
					'-', helpButton("DettaglioAzione")]
	        }),
	        id: 'pnlDettPratica',
	        listeners: {
				tabchange: function(panel, tab) {
					var myIdx = panel.items.indexOf(panel.getActiveTab());
					var showButtons = ((myIdx==3) && (panel.id=='pnlDettPratica'));

					this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
					this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	            }
	        }
        });
		
		DCS.DettaglioAzione.superclass.initComponent.call(this);
		
		/**------------------------------------
		 * LOAD DEGLI STORES
		 *----------------------------------- */
		//caricamento dei 4 store
		//controllo doppioni azione
		var DisableAll=false;
		if(this.idAz!='')
		{
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php', 
	    		params : {	task: 'read',
							sql: "SELECT count(*) as num from statoazione where idazione="+this.idAz
						},
				method: 'POST',
				reader:  new Ext.data.JsonReader(
	    					{
	    						root: 'results',//name of the property that is container for an Array of row objects
	    						id: 'num'//the property within each row object that provides an ID for the record (optional)
	    					},
	    					[{name: 'num'}]
	    				),
				success: function ( result, request ) {
					eval('var resp = ('+result.responseText+').results[0]');
					if(resp != undefined)
					{
						if(resp.num>1)
						{
							DisableAll=true;
						}
					}								
				},
	    		failure: function ( result, request) { 
	    			Ext.MessageBox.alert('Errore', 'Errore durante l\'esecuzione dell\' interrogazione al database.'); 
	    		},
	    		autoLoad: true
	    	});
		}
		var azione = this.idAz;
		var sqlForm='SELECT sa.IdStatoRecupero,p.IdProcedura,p.TitoloProcedura,sa.Condizione,a.IdAzione,a.IdFunzione,a.CodAzione,a.CodAzioneLegacy,a.TitoloAzione,a.TipoFormAzione as TfAz,a.FlagMultipla,a.LastUser,';
			sqlForm+=' a.FlagSpeciale,';
			sqlForm+=' a.FlagAllegato,case when a.FlagAllegato="Y" then "Obbligatorio" when a.FlagAllegato="N" then "Non obbligatorio" else a.FlagAllegato end as FlagAllegatoDesc,';
			sqlForm+=' a.FormWidth,a.FormHeight,a.GiorniEvasione,a.PercSvalutazione';
			sqlForm+=' FROM azione a'; 
			sqlForm+=' left join azioneprocedura ap on(a.IdAzione=ap.IdAzione)';
			sqlForm+=' left join procedura p on(ap.IdProcedura=p.IdProcedura)';
			sqlForm+=' left join statoazione sa on(sa.IdAzione=a.IdAzione) where a.idazione='+this.idAz+'';
		dsStoreAllegato.load({
			callback : function(r,options,success) {
				dsStatoRecupero.load({
					callback : function(r,options,success) {
						dsFormAz.load({
							callback : function(r,options,success) {
								if(azione!='')
								{
									dsAzione.load({
										params:{
											sql: sqlForm 
										},
										callback : function(r,options,success) 
										{
											if(ArrReload=='')
											{//se non è stata costituita un azione complessa
												var sliced=false;
												if (success && r.length>0) 
												{
													//carica dati
													var range = dsAzione.getRange();
													var rec = range[0];
													var voidOrNot='';
													if(rec.json.Condizione!='' && rec.json.Condizione!=null)
													{
														var splitMustBeDone=rec.json.Condizione.indexOf(' and(true) and (');
														if (splitMustBeDone == (-1))
														{
															var splitMustBeDoneVoid=rec.json.Condizione.indexOf(' and(true)');
															if (splitMustBeDoneVoid != (-1))
															{
																voidOrNot=' and(true)';
															}
														}else{
															voidOrNot=' and(true) and (';
														}
													}
													if (voidOrNot!='')
													{
														//splitta e carica
														var miaStringaReplace = rec.json.Condizione.replace(voidOrNot,".");
														var ArraySplit = miaStringaReplace.split('.');
														Ext.getCmp('cmbStatoRec').setValue(ArraySplit[0]);
														Ext.getCmp('CondId').setValue(ArraySplit[1].slice(0,(ArraySplit[1].length)-1));
														sliced=true;
														
														
													}else{
														formPAz.getForm().loadRecord(r[0]);
														Ext.getCmp('cmbStatoRec').setValue(rec.json.IdStatoRecupero);
													}
													Ext.getCmp('cAz').setValue(rec.json.CodAzione);
													//Ext.getCmp('cAzL').setValue(rec.json.CodAzioneLegacy);
													Ext.getCmp('cmbFazione').setValue(rec.json.TfAz);
													Ext.getCmp('cmbAllegato').setValue(rec.json.FlagAllegato);
													Ext.getCmp('txtProc').setValue(rec.json.TitoloProcedura);
													Ext.getCmp('evasione').setValue(rec.json.GiorniEvasione);
													Ext.getCmp('pSvalutazione').setValue(rec.json.PercSvalutazione);
													Ext.getCmp('Flarg').setValue(rec.json.FormWidth);
													Ext.getCmp('Falt').setValue(rec.json.FormHeight);
													if(rec.json.FlagMultipla=='Y')
													{
														Ext.getCmp('chkFmul').setValue(true);
													}else{
														Ext.getCmp('chkFmul').setValue(false);
													}
													if(rec.json.FlagSpeciale=='Y')
													{
														Ext.getCmp('chkSpe').setValue(true);
													}else{
														Ext.getCmp('chkSpe').setValue(false);
													}
													if(!sliced){
														Ext.getCmp('cmbStatoRec').setValue(rec.json.IdStatoRecupero);
													}
													
													Ext.getCmp('btnApriCondC').setDisabled(false);
													//se è un importazione da sistema solo alcuni campi sono editabili
													//if(rec.json.LastUser=='system')
													//{
													//	Ext.getCmp('cAz').setDisabled(true);
													//	Ext.getCmp('cAzL').setDisabled(true);
													//	Ext.getCmp('chkFmul').setDisabled(true);
													//	Ext.getCmp('cmbFazione').setDisabled(true);
													//	Ext.getCmp('cmbSpeciale').setDisabled(true);
													//	Ext.getCmp('txtProc').setDisabled(true);
													//	Ext.getCmp('chkAll').setDisabled(true);
													//	Ext.getCmp('evasione').setDisabled(true);
													//	Ext.getCmp('pSvalutazione').setDisabled(true);
													//	Ext.getCmp('Flarg').setDisabled(true);
													//	Ext.getCmp('Falt').setDisabled(true);
													//}
													//se c'è una procedura associata disabilita tutto perchè non è editabile
													if(rec.json.TitoloProcedura!='' && rec.json.TitoloProcedura!=null)
													{
														Ext.getCmp('Tazione').setDisabled(true);
														Ext.getCmp('cAz').setDisabled(true);
														Ext.getCmp('cAzL').setDisabled(true);
														Ext.getCmp('chkFmul').setDisabled(true);
														Ext.getCmp('cmbFazione').setDisabled(true);
														CheckFuncGroup.setDisabled(true);
														CheckAutoGroup.setDisabled(true);
														Ext.getCmp('btnSave').setDisabled(true);
														Ext.getCmp('btnApriCondC').setDisabled(true);
														Ext.getCmp('CondId').setDisabled(true);
														Ext.getCmp('cmbStatoRec').setDisabled(true);
														Ext.getCmp('cmbAllegato').setDisabled(true);
														Ext.getCmp('chkSpe').setDisabled(true);
														Ext.getCmp('evasione').setDisabled(true);
														Ext.getCmp('pSvalutazione').setDisabled(true);
														Ext.getCmp('Flarg').setDisabled(true);
														Ext.getCmp('Falt').setDisabled(true);
													}
												}
											}else{
												//sto ricaricando i dati da un elaborazione del campo condizionato complesso
												Ext.getCmp('Tazione').setValue(ArrReload[0]);
												Ext.getCmp('cAz').setValue(ArrReload[1]);
												Ext.getCmp('cAzL').setValue(ArrReload[2]);
												Ext.getCmp('cmbFazione').setValue(ArrReload[3]);
												Ext.getCmp('chkFmul').setValue(ArrReload[4]);
												Ext.getCmp('cmbStatoRec').setValue(ArrReload[5]);
												Ext.getCmp('CondId').setValue(ArrReload[6]);
												for(var k=0;k<ArrReload[7].length;k++)
												{
													CheckFuncGroup.setValue(ArrReload[7][k].name,ArrReload[7][k].checked);
												}
												for(var h=0;h<ArrReload[8].length;h++)
												{
													CheckAutoGroup.setValue(ArrReload[8][h].name,ArrReload[8][h].checked);
												}
												Ext.getCmp('chkSpe').setValue(ArrReload[10]);
												Ext.getCmp('cmbAllegato').setValue(ArrReload[11]);
												Ext.getCmp('evasione').setValue(ArrReload[12]);
												Ext.getCmp('Flarg').setValue(ArrReload[13]);
												Ext.getCmp('Falt').setValue(ArrReload[14]);
												Ext.getCmp('pSvalutazione').setValue(ArrReload[15]);
												var range = dsAzione.getRange();
												var rec = range[0];
												//if(rec.json.LastUser=='system')
												//{
												//	Ext.getCmp('cAz').setDisabled(true);
												//	Ext.getCmp('cAzL').setDisabled(true);
												//	Ext.getCmp('chkFmul').setDisabled(true);
												//	Ext.getCmp('cmbFazione').setDisabled(true);
												//	Ext.getCmp('txtProc').setDisabled(true);
												//	Ext.getCmp('cmbSpeciale').setDisabled(true);
												//	Ext.getCmp('chkAll').setDisabled(true);
												//	Ext.getCmp('evasione').setDisabled(true);
												//	Ext.getCmp('pSvalutazione').setDisabled(true);
												//	Ext.getCmp('Flarg').setDisabled(true);
												//	Ext.getCmp('Falt').setDisabled(true);
												//}
												//checkboxconfigsFUNC=ArrReload[7];
												//Ext.getCmp('fsCFunc').doLayout();
												//checkboxconfigsAUTO=ArrReload[8];
												//Ext.getCmp('fsCAuto').doLayout();
												Ext.getCmp('btnApriCondC').setDisabled(false);
											}
											//se vi sono doppioni disabilita
											if(DisableAll)
											{
												Ext.getCmp('cmbStatoRec').setDisabled(true);
												Ext.getCmp('CondId').setDisabled(true);
												Ext.getCmp('btnApriCondC').setDisabled(true);
											}
											Ext.getCmp('txtProc').setDisabled(true);
										},
										scope: this
									});
								}else{
									if(ArrReload!='')
									{
										//sto ricaricando i dati da un elaborazione del campo condizionato complesso
										Ext.getCmp('Tazione').setValue(ArrReload[0]);
										Ext.getCmp('cAz').setValue(ArrReload[1]);
										Ext.getCmp('cAzL').setValue(ArrReload[2]);
										Ext.getCmp('cmbFazione').setValue(ArrReload[3]);
										Ext.getCmp('chkFmul').setValue(ArrReload[4]);
										Ext.getCmp('cmbStatoRec').setValue(ArrReload[5]);
										Ext.getCmp('CondId').setValue(ArrReload[6]);
										Ext.getCmp('chkSpe').setValue(ArrReload[10]);
										Ext.getCmp('cmbAllegato').setValue(ArrReload[11]);
										Ext.getCmp('evasione').setValue(ArrReload[12]);
										Ext.getCmp('Flarg').setValue(ArrReload[13]);
										Ext.getCmp('Falt').setValue(ArrReload[14]);
										Ext.getCmp('pSvalutazione').setValue(ArrReload[15]);
									}else{
										Ext.getCmp('btnApriCondC').setDisabled(false);
									}
								}
							},
							scope: this
						});
					},
					scope: this
				});
			},
			scope: this
		});
		/*ckStoreProc.load({
			callback: function(r,options,success){
				  
				range = ckStoreProc.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					checkboxconfigsPROC.push({ 
				        //id:rec.data.IdProcedura,
						name:rec.data.IdProcedura,
				        boxLabel:rec.data.TitoloProcedura,
				        checked: false
				      });
				}

				Ext.Ajax.request({
			        url: 'server/gestioneGridAzioni.php',
			        method: 'POST',
			        params: {task: 'checkProc', IdAzione: this.idAz, who: 'procedure'},
			        success: function(obj) {
						if (obj.responseText != '') {
							eval("var elems = "+obj.responseText);
							for (var i=0; i<elems.length; i++) {
								for (var k=0;k<checkboxconfigsPROC.length;k++)
								{
									//console.log("in K for: "+k);
									if(checkboxconfigsPROC[k].name == elems[i])
									{
										//console.log("in equal if : "+checkboxconfigsPROC[k].boxLabel);
										try {
											checkboxconfigsPROC[k].checked= true;
											break;
										} catch (err) {}
									}
								}
							}
			            } else {
			                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
			            }
						Ext.getCmp('fsCProc').add(CheckProcGroup);
						Ext.getCmp('fsCProc').doLayout();
					},
					scope: this
			    });					
			},
			scope: this
		});*/
		
		
		ckStoreFunc.load({
			callback: function(r,options,success){
				  
				range = ckStoreFunc.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					checkboxconfigsFUNC.push({ 
				        //id:rec.data.IdProcedura,
						name:rec.data.IdTipoAzione,
				        boxLabel:rec.data.TitoloTipoAzione,
				        checked: false
				      });
				}				
				
				if(ArrReload=='' && azione!='')
				{
					Ext.Ajax.request({
				        url: 'server/gestioneGridAzioni.php',
				        method: 'POST',
				        params: {task: 'checkProc', IdAzione: this.idAz, who: 'tipoazioni'},
				        success: function(obj) {
							if (obj.responseText != '') {
								eval("var elems = "+obj.responseText);
								//console.log("el "+elems);
								for (var i=0; i<elems.length; i++) {
									for (var k=0;k<checkboxconfigsFUNC.length;k++)
									{
										//console.log("in K2 for: "+k);
										//console.log("name "+checkboxconfigsFUNC[k].name);
										if(checkboxconfigsFUNC[k].name == elems[i])
										{
											//console.log("in equal if : "+checkboxconfigsFUNC[k].boxLabel);
											try {
												checkboxconfigsFUNC[k].checked= true;
												break;
											} catch (err) {}
										}
									}
								}
				            } else {
				                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
				            }
							Ext.getCmp('fsCFunc').add(CheckFuncGroup);
							Ext.getCmp('fsCFunc').doLayout();
						},
						scope: this
				    });	
				}else{
					if(ArrReload!='')
					{
						for(var k=0;k<ArrReload[7].length;k++)
						{
							checkboxconfigsFUNC[k].checked=ArrReload[7][k].checked;
						}
					}
					Ext.getCmp('fsCFunc').add(CheckFuncGroup);
					Ext.getCmp('fsCFunc').doLayout();
				}
			},
			scope: this
		});
		
		ckStoreAut.load({
			callback: function(r,options,success){
				  
				range = ckStoreAut.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					checkboxconfigsAUTO.push({ 
				        //id:rec.data.IdProcedura,
						name:rec.data.IdAutomatismo,
				        boxLabel:rec.data.TitoloAutomatismo,
				        checked: false
				      });
				}
				if(ArrReload=='' && azione!='')
				{
					Ext.Ajax.request({
				        url: 'server/gestioneGridAzioni.php',
				        method: 'POST',
				        params: {task: 'checkProc', IdAzione: this.idAz, who: 'Automatismi'},
				        success: function(obj) {
							if (obj.responseText != '') {
								eval("var elems = "+obj.responseText);
								//console.log("el "+obj.responseText);
								//console.log("eL "+elems.length);
								for (var i=0; i<elems.length; i++) {
									for (var k=0;k<checkboxconfigsAUTO.length;k++)
									{
										//console.log("eLem "+elems[i]);
										//console.log("in K2 for: "+k);
										//console.log("name "+checkboxconfigsFUNC[k].name);
										if(checkboxconfigsAUTO[k].name == elems[i])
										{
											//console.log("in equal if : "+checkboxconfigsFUNC[k].boxLabel);
											try {
												checkboxconfigsAUTO[k].checked= true;
												break;
											} catch (err) {}
										}
									}
								}
				            } else {
				                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
				            }
							Ext.getCmp('fsCAuto').add(CheckAutoGroup);
							Ext.getCmp('fsCAuto').doLayout();
						},
						scope: this
				    });	
				}else{
					if(ArrReload!='')
					{
						for(var h=0;h<ArrReload[8].length;h++)
						{
							checkboxconfigsAUTO[h].checked=ArrReload[8][h].checked;
						}
						Ext.getCmp('btnApriCondC').setDisabled(false);
					}
					Ext.getCmp('fsCAuto').add(CheckAutoGroup);
					Ext.getCmp('fsCAuto').doLayout();
				}
			},
			scope: this
		});
		
		//dsFormAz.load();
		dsFMultiplo.load();
		//dsTipoMod.load();
	}	
});

// register xtype
Ext.reg('DCS_dettaglioAzione', DCS.DettaglioAzione);

//--------------------------------------------------------
//Visualizza dettaglio azione
//--------------------------------------------------------
function showAzDetail(idAz,nome,listStore,rowIndex,loadAfterCC) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	loadAfterCC=loadAfterCC||'';
	var winTitle = 'Dettaglio azione - ' + nome +'';
	if(nome==''){winTitle='Creazione nuova azione';rowIndex='';}
	
	var nameNW = 'dettaglio'+idAz;
	
	if (oldWind != '') {
		win = Ext.getCmp(oldWind);
		win.close();
	}
	oldWind = nameNW;
	win = new Ext.Window({
		width: 1000,
		height: 550,
		minWidth: 1000,
		minHeight: 550,
		layout: 'fit',
		id:'dettaglio'+idAz,
		stateful:false,
		plain: true,
		resizable: false,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		tools: [helpTool("DettaglioAzione")],
		items: [{
			xtype: 'DCS_dettaglioAzione',
			idAz: idAz,
			nome:nome,
			listStore: listStore,
			rowIndex: rowIndex,
			loadAfterCC:loadAfterCC
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	win.show();
	win.on({
		'close' : function () {
				oldWind = '';
			}
	});
	myMask.hide();
	
}; // fine funzione showAzDetail