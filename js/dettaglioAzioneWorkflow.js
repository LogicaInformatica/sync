// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

DCS.recordAzione = Ext.data.Record.create([
		{name: 'IdAzione', type: 'int'},
		{name: 'IdStatoRecupero'},
		{name: 'IdClasseSuccessiva'},
		{name: 'IdStatoRecuperoSuccessivo'},
		{name: 'Ordine'},
		{name: 'NumAut', type: 'int'},
		{name: 'TitoloAzione'},
		{name: 'FlagMultipla'},
		{name: 'TipoFormAzione'},
		{name: 'PercSvalutazione'},
		{name: 'Attiva'},
		{name: 'FlagAllegato'},
		{name: 'FormWidth', type: 'int'},
		{name: 'FormHeight', type: 'int'},
		{name: 'GiorniEvasione', type: 'int'},
		{name: 'Condizione'}]);
DCS.recordChk = Ext.data.Record.create([
        {name: 'IdProfilo', type: 'int', allowBlank:false},
  		{name: 'CodProfilo', type: 'string' },
		{name: 'TitoloProfilo', type: 'string' },
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}]);

DCS.DettaglioAzWkf = Ext.extend(Ext.TabPanel, {
	idAzione: 0,
	idProcedura: 0,
	listStore:null,
	listUseStore:null,
	rowIndex: -1,
	titoloAz:'',
	windowAzComplessa:'',
	loadAfterCC:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		if(this.idAzione == ''){
			this.idAzione = 0;
		}
		var azioneID=this.idAzione;
		if(this.idProcedura == ''){
			this.idProcedura = 0;
		}
		var titoloAzione = this.titoloAz;
		var proceduraID=this.idProcedura;
		var mainGridStore=this.listStore;
		var mainGridUsefulStore=this.listUseStore;
		var IdMain = this.getId();
		var rowIndexToSon = this.rowIndex;
		var wACC=this.windowAzComplessa;
		var ArrReload = this.loadAfterCC;

		//stores: dati da visualizzare,dati per riempire il chkgroup,combo,
		var dsAzioneGenerale = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readAzWKF',ida:this.idAzione},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordAzione)
		});
		
		var ckStore = new Ext.data.Store({
			id:'ckS',
			proxy: new Ext.data.HttpProxy({
				url: 'server/utentiProfili.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordChk
			})
		});
		
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
			),
			autoLoad: true
		});//end dsStatoRecupero 
		
		var dsClasseSucc = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdClasse,TitoloClasse FROM classificazione union all select -1,'' order by TitoloClasse asc"
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdClasse'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdClasse'},
				{name: 'TitoloClasse'}]
			),
			autoLoad: true
		});//end dsClasseSucc 
		
		var dsFormAzione = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdFormA,TipoFormAzione from v_azione_forms" 
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdFormA'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdFormA'},
				 {name: 'TipoFormAzione'}]
			),
			autoLoad: true
		});//end  
		
		//CheckGroup e array di configurazione
		var checkboxconfigs = []; //array of about to be checkboxes.   
		var CheckProfGroup = new Ext.form.CheckboxGroup({
		    id:'CPGroup',
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Single Column',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigs],
		    listeners:{
				change:function(CheckProfGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckProfGroup.getValue();
					for(k=0;k<arr.length;k++)
	            	{
						for(j=0;j<checkboxconfigs.length;j++)
		            	{
							if(arr[k].id==checkboxconfigs[j].id)
							{
								checkboxconfigs[j].checked = arr[k].checked;
								break;
							}
		            	}
	            	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigs.length;j++)
	            	{
						flag = false;
						if(checkboxconfigs[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].id==checkboxconfigs[j].id)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigs[j].checked = false;}
						}
	            	}
				}
			}
		});
		
		//Bottone di salvataggio
		var save = new Ext.Button({
			sm:CheckProfGroup,
			store:dsAzioneGenerale,
			id:'btnSaveAzione',
			text: 'Salva',
			handler: function(b,event) {
				var vect = '';
				
				for(j=0;j<checkboxconfigs.length;j++)
            	{
	            	if(checkboxconfigs[j].checked == true)
	            	{
	            		vect = vect + '|' + checkboxconfigs[j].id;
	            	}
            	}

				if (formAzione.getForm().isDirty()) {	// qualche campo modificato
					if (formAzione.getForm().isValid()){
						if(vect.length>0)//controllo su presenza di un profilo
						{
							//controllo sulla bontà delle condizioni
							var condizione='';
							var complesso=false;
							var t='';
							var v = Ext.getCmp('cmbStatoRec').getValue();
							var patt=new RegExp('[A-z]');
							if(!patt.test(v))
							{
								if(Ext.getCmp('cmbStatoRec').getValue()>0)
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
										Ext.getCmp('btnSaveAzione').setDisabled(true);
										//salvataggio/edit azione
										formAzione.getForm().submit({
											url: 'server/gestioneProcedure.php', method: 'POST',
											params: {task:"saveAzProc",vect: vect,ida:azioneID,idp:proceduraID},
											success: function (frm,action) {
												Ext.MessageBox.alert('Esito', action.result.messaggio); 
												win.close();
												mainGridStore.reload();
											},
											failure: saveFailure
										});
									}else{
										Ext.MessageBox.alert('Errore', 'Vi è un errore nelle condizioni applicate.');
									}								
								},
                        		failure: function ( result, request) { 
                        			Ext.MessageBox.alert('Errore', 'Errore durante l\'esecuzione dell\' interrogazione al database.'); 
                        		},
                        		autoLoad: true
                        	});
						}else{
							Ext.MessageBox.alert('Errore', 'Non si è assegnata l\'azione ad alcun profilo.');
						}
					}
				}else{
					//console.log("no change");
				}
			},
			scope: this
		});
		
		//Form su cui montare gli elementi
		var formAzione = new Ext.form.FormPanel({
			title:'Dettaglio azione',		//il titolo è usato per testare il tab
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
							xtype:'container',columnWidth:.60,
							items:[{//oggetto primo
									xtype:'fieldset', title:'Definizione', border: false,
									items:[{
											xtype:'container', layout:'column',
											items:[{
												xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
												defaults: {anchor:'99%', readOnly:false},
												items: [{fieldLabel:'Nome azione',	name:'TitoloAzione', id:'Nazione', style:'text-align:left'},
												        {fieldLabel:'IdAz',	name:'IdAzione', id:'IDazione', style:'text-align:left',hidden:true}]
											}]
									},{
											xtype:'container', layout:'column',
											items:[{
												xtype:'panel', layout:'form', labelWidth:80,columnWidth:.50,
												defaults: {anchor:'97%', readOnly:false},
												items: [{xtype: 'combo',
													fieldLabel: 'Form azione',
													name:'TipoFormAzione',
													id:'cmbFA',
													allowBlank: false,
													hiddenName: 'TipoFormAzione',
													typeAhead: false, 
													editable:false,
													triggerAction: 'all',
													lazyRender: true,	//should always be true for editor
													store: dsFormAzione,
													displayField: 'TipoFormAzione',
													valueField: 'IdFormA',
													listeners:{
																scope:this,
																select:function(combo, record, index){
																	
																}
															}
													}]
											}]										
									},{
											xtype:'container', layout:'column',
											items:[{
												xtype:'panel', layout:'form', labelWidth:80,columnWidth:.40, defaultType:'textfield',
												defaults: {anchor:'85%', readOnly:false},
												items: [{fieldLabel:'Ordine',	name:'Ordine', id:'ordinef', style:'text-align:left'}]
											},{
												xtype:'panel', layout:'form', labelWidth:120,columnWidth:.40, defaultType:'textfield',
												defaults: {anchor:'97%', readOnly:false},
												items: [{fieldLabel:'Giorni evasione',	name:'GiorniEvasione', id:'evasione', style:'text-align:left'}]
											}]
									},{
											xtype:'panel', layout:'form', labelWidth:160,defaultType:'numberfield',
											defaults: {anchor:'50%', readOnly:false},
											items: [{fieldLabel:'Percentuale svalutazione',	
													name:'PercSvalutazione', 
													id:'pSvalutazione', 
													style:'text-align:left',
													allowNegative: false,
													minValue :0,
													decimalPrecision: 0,
													maxLength:3,
													maxLengthText:'Numero di massimo tre cifre'
											}]
									},{
											xtype:'container', layout:'column',
											items:[{
												xtype:'panel', labelWidth:80, columnWidth:.33,
												defaults: {anchor:'97%', readOnly:false},
												items: [{
													style: 'padding-left:0px; anchor:"0%";',
					           						xtype: 'checkbox',
													boxLabel: 'Azione Multipla',
													id: 'ckMul',
													//id:'cmbFmul',
													name:'FlagMultipla',
													hiddenName: 'FlagMultipla',
													hidden: false,
													checked: false
												}]
											},{
												xtype:'panel', labelWidth:80, columnWidth:.33,
												defaults: {anchor:'97%', readOnly:false},
												items: [{
													style: 'padding-left:0px; anchor:"0%";',
					           						xtype: 'checkbox',
													boxLabel: 'Attiva',
													id: 'ckAtt',
													//id:'cmbFmul',
													name:'Attiva',
													hiddenName: 'Attiva',
													hidden: false,
													checked: false
												}]
											},{
												xtype:'panel', labelWidth:80, columnWidth:.33,
												defaults: {anchor:'97%', readOnly:false},
												items: [{
													style: 'padding-left:0px; anchor:"0%";',
					           						xtype: 'checkbox',
													boxLabel: 'Allegato',
													id: 'chkAll',
													name:'allegato',
													hiddenName: 'allegato',
													hidden: false,
													checked: false
												}]
											}]										
									}]//fine ogg primo
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
							},{
								xtype:'fieldset', title:'Vincoli', border: false,
								items:[{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:115,columnWidth:0.77,
										defaults: {anchor:'99%', readOnly:false},
										items: [{xtype: 'combo',
											fieldLabel: 'Eseguibile solo in',
											name:'IdStatoRecupero',
											id:'cmbStatoRec',
											allowBlank: true,
											hiddenName: 'IdStatoRecupero',
											listWidth:380,
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
										xtype: 'panel',	layout: 'form',	columnWidth: 0.23,
										defaults: {anchor:'99%', readOnly:false},
										items: [{
						                	xtype:'button',
					                    	tooltip:"Specifica una condizione comprendente più stati e variabili.",
											text:"Condiz. complessa",
											name: 'btnApriCondC', 
										    id: 'btnApriCondC',
										    disabled:true,
										    anchor: '30%',
										    handler: function() 
										    {
												var ArrSaveStateFields=[];
												ArrSaveStateFields.push(Ext.getCmp('Nazione').getValue());
												ArrSaveStateFields.push(Ext.getCmp('IDazione').getValue());
												ArrSaveStateFields.push(Ext.getCmp('cmbFA').getValue());
												ArrSaveStateFields.push(Ext.getCmp('ckMul').getValue());
												ArrSaveStateFields.push(Ext.getCmp('ckAtt').getValue());
												ArrSaveStateFields.push(Ext.getCmp('cmbStatoRec').getValue());
												ArrSaveStateFields.push(Ext.getCmp('CondId').getValue());
												ArrSaveStateFields.push(Ext.getCmp('cmbClasseSucc').getValue());
												ArrSaveStateFields.push(Ext.getCmp('cmbStatoRecSucc').getValue());
												ArrSaveStateFields.push(checkboxconfigs);
												ArrSaveStateFields.push(Ext.getCmp('chkAll').getValue());
												ArrSaveStateFields.push(Ext.getCmp('Flarg').getValue());
												ArrSaveStateFields.push(Ext.getCmp('Falt').getValue());
												ArrSaveStateFields.push(Ext.getCmp('evasione').getValue());
												ArrSaveStateFields.push(Ext.getCmp('ordinef').getValue());
												ArrSaveStateFields.push(Ext.getCmp('pSvalutazione').getValue());
												showCCmxDetail(win,IdMain,true,ArrSaveStateFields,mainGridStore,rowIndexToSon,proceduraID,titoloAzione,'isAzWrkF');
												win.close();
								        	},
											scope: this
					                    }]
									}]
								},{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:115,columnWidth:.98, defaultType:'textfield',
										defaults: {anchor:'99%', readOnly:false},
										items: [{fieldLabel:'Condizione aggiunta',	name:'Condizione', id:'CondId', style:'text-align:left'}]
									}]
								}]
							},{
								xtype:'fieldset', title:'Effetti', border: false,
								items:[{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:115,columnWidth:.98,
										defaults: {anchor:'99%', readOnly:false},
										items: [{xtype: 'combo',
											fieldLabel: 'Cambio di classe',
											name:'IdClasseSuccessiva',
											id:'cmbClasseSucc',
											allowBlank: true,
											hiddenName: 'IdClasseSuccessiva',
											typeAhead: false, 
											forceSelection:true,
											editable:true,
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsClasseSucc,
											displayField: 'TitoloClasse',
											valueField: 'IdClasse',												
											listeners:{
												scope:this,
												select:function(combo, record, index){
													
												}
											}
										}]
									}]
								},{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:115,columnWidth:.98,
										defaults: {anchor:'99%', readOnly:false},
										items: [{xtype: 'combo',
											fieldLabel: 'Cambio di stato',
											name:'IdStatoRecuperoSuccessivo',
											id:'cmbStatoRecSucc',
											allowBlank: true,
											hiddenName: 'IdStatoRecuperoSuccessivo',
											typeAhead: false,
											forceSelection:true,
											editable:true,
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsStatoRecupero,
											displayField: 'TitoloStatoRecupero',
											valueField: 'IdStatoRecupero',												
											listeners:{
												scope:this,
												select:function(combo, record, index){
													console.log("in");
												}
											}
										}]
									}]
								}]
							},{
								xtype:'container', layout:'column',
								items: [{
									xtype: 'panel',
									layout: 'form',
									columnWidth: 0.2,
									defaults: {xtype: 'textfield', anchor: '98%'},
									items: [{
					                	xtype:'button',
				                    	tooltip:"Genera uno stato di arrivo per l\'azione",
										text:"Crea uno stato",
										name: 'btnCrStato', 
									    id: 'btnCrStato',
									    disabled:false,
									    anchor: '30%',
									    handler: function() {
											var isLink=0;
											var isActionSon = true;
											console.log("row "+rowIndexToSon);
//											showStatoDetail('',null,'',proceduraID,isLink,isActionSon,azioneID);
											showStatoDetail('',mainGridStore,rowIndexToSon,proceduraID,isLink,isActionSon,azioneID);
											//Ext.getCmp(IdMain).showStatoDetail('',null,'',proceduraID,isLink,isActionSon,azioneID);
											isLink=1;
							        	},
										scope: this
				                    }]
								},{
									xtype: 'panel',
									layout: 'form',
									columnWidth: 0.5,
									defaults: {xtype: 'textfield', anchor: '98%'},
									items: [{
					                	xtype:'button',
				                    	tooltip:"Automatismi associati",
										text:"Calcolo automatismi associati...",
										name: 'btnApriAut', 
									    id: 'btnApriAut',
									    disabled:true,
									    anchor: '30%',
									    handler: function() {
											Ext.getCmp(IdMain).showAutAzWfDettaglio(azioneID,titoloAzione);
							        	},
										scope: this
				                    }]
								}]
							}]//fine oggetti colonna sinistra
					},{		//colonna destra
							xtype:'fieldset', id:'fsC', title:'Profili associati',autoScroll:true, border:true, layout:'column',columnWidth:.40, height:440,bodyStyle: 'padding-left:5px;',
							items:[]
					}]
			}],
			buttons:[save,{text: 'Annulla',handler: function () {win.close()}}]
		});
		
		// Indice del record nello store della lista
		var indexStore=0;
		if(this.listUseStore!=null)
			indexStore = this.rowIndex;
		if (this.listUseStore!=null && (this.listUseStore.lastOptions.params||{}).start != undefined)
			{indexStore += this.listUseStore.lastOptions.params.start;	}
		// Indice dell'ultimo record nello store della lista
		var lastRec = (this.listUseStore!=null?this.listUseStore.getTotalCount()-1:indexStore);
//		var si = this.listStore.getSortState();
		
		// Funzione che gestisace la pressione dei bottoni precedente/successivo
		var dettaglio_nextprev = function(btn) {
			var p = this.listUseStore.lastOptions.params || {};		// parametri di lettura dello store
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
				this.listUseStore.load({
					params:p, 
					callback : function(rows,options,success) {
						if (success) {			// mostra dettaglio record richiesto
							var newIndex = this.rowIndex==0?options.params.limit-1:0;
							var rec = rows[newIndex]; //this.listStore.getAt(newIndex);
							showAzioneDetail(rec.get('IdAzione'),this.listUseStore,newIndex,proceduraID,rec.get('TitoloAzione'));
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listUseStore.getAt(newIndex);
				showAzioneDetail(rec.get('IdAzione'),this.listUseStore,newIndex,proceduraID,rec.get('TitoloAzione'));
			}
		};

		Ext.apply(this, {
			activeTab:0,
			items: [formAzione],
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
					'-', helpButton("DettaglioAzioneWF")]
	        }),
	        id: 'pnlDettAz',
	        listeners: {
				tabchange: function(panel, tab) {
					var myIdx = panel.items.indexOf(panel.getActiveTab());
					var showButtons = ((myIdx==3) && (panel.id=='pnlDettAz'));

					this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
					this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	            }
	        }
        });
		
		DCS.DettaglioAzWkf.superclass.initComponent.call(this);
		
		//caricamento dei 4 store
		dsStatoRecupero.load({
			callback : function(r,options,success) {
				dsClasseSucc.load({
					callback : function(r,options,success) {
						dsFormAzione.load({
							callback : function(r,options,success) {
								dsAzioneGenerale.load({
									callback : function(r,options,success) {
										if(ArrReload=='')
										{
											//controllo sullo splitting eventuale della condizione complessa
											//sul campo condizione aggiuntiva
											var sliced=false;
											range = dsAzioneGenerale.getRange();
											if (success && r.length>0) 
											{
												var rec = range[0];
												//console.log("rec.data.Condizione "+rec.data.Condizione);
												var voidOrNot='';
												if(rec.data.Condizione!='' && rec.data.Condizione!=null)
												{
													var splitMustBeDone=rec.data.Condizione.indexOf(' and(true) and (');
													if (splitMustBeDone == (-1))
													{
														var splitMustBeDoneVoid=rec.data.Condizione.indexOf(' and(true)');
														if (splitMustBeDoneVoid != (-1))
														{
															voidOrNot=' and(true)';
														}
													}else{
														voidOrNot=' and(true) and (';
													}
												}
												//console.log("splitMustBeDone "+splitMustBeDone);
												if (voidOrNot!='')
												{
													//splitta e carica
													var miaStringaReplace = rec.data.Condizione.replace(voidOrNot,".");
													var ArraySplit = miaStringaReplace.split('.');
													Ext.getCmp('cmbStatoRec').setValue(ArraySplit[0]);
													Ext.getCmp('CondId').setValue(ArraySplit[1].slice(0,(ArraySplit[1].length)-1));
													sliced=true;
													
													Ext.getCmp('IDazione').setValue(rec.data.IdAzione);
													Ext.getCmp('Nazione').setValue(rec.data.TitoloAzione);
												}else{
													formAzione.getForm().loadRecord(r[0]);
												}
											}
											//c'è un solo record il forEach lo estrae
											for (i=0; i<range.length; i++)
											{
												var rec = range[i];
												Ext.getCmp('cmbFA').setValue(rec.data.TipoFormAzione);
												/*switch(rec.data.TipoFormAzione)
												{
													case 'Annulla': Ext.getCmp('cmbFA').setValue('Annullamento');
														break;
													case 'Autorizza': Ext.getCmp('cmbFA').setValue('Approvazione');
														break;
													case 'Base': Ext.getCmp('cmbFA').setValue('Semplice');
														break;
													case 'Data': Ext.getCmp('cmbFA').setValue('Con data');
														break;
													case 'InoltroWF': Ext.getCmp('cmbFA').setValue('Inoltro notifica');
														break;
													case 'Rifiuta': Ext.getCmp('cmbFA').setValue('Rifiuto');
														break;
												}*/
												if(!sliced){
													Ext.getCmp('cmbStatoRec').setValue(rec.data.IdStatoRecupero);
												}
												Ext.getCmp('cmbClasseSucc').setValue(rec.data.IdClasseSuccessiva);
												Ext.getCmp('cmbStatoRecSucc').setValue(rec.data.IdStatoRecuperoSuccessivo);
												Ext.getCmp('ckMul').setValue(rec.data.FlagMultipla);
												Ext.getCmp('ckAtt').setValue(rec.data.Attiva);
												Ext.getCmp('chkAll').setValue(rec.data.FlagAllegato);
												Ext.getCmp('Flarg').setValue(rec.json.FormWidth);
												Ext.getCmp('Falt').setValue(rec.json.FormHeight);
												Ext.getCmp('evasione').setValue(rec.json.GiorniEvasione);
												Ext.getCmp('pSvalutazione').setValue(rec.json.PercSvalutazione);
												Ext.getCmp('ordinef').setValue(rec.json.Ordine);
												var nomeAu=' Automatismi';
												if(rec.data.NumAut==1)
													nomeAu=' Automatismo';
												Ext.getCmp('btnApriAut').setText(rec.data.NumAut+nomeAu);
												Ext.getCmp('btnApriAut').setDisabled(false);
												Ext.getCmp('btnApriCondC').setDisabled(false);
											}
											if(range.length==0){
												Ext.getCmp('btnApriAut').setText('Associa automatismi');
												Ext.getCmp('btnApriAut').setDisabled(false);
												Ext.getCmp('btnApriCondC').setDisabled(false);
											}
										}else{
											//sto ricaricando i dati da un elaborazione del campo condizionato complesso
											Ext.getCmp('Nazione').setValue(ArrReload[0]);
											Ext.getCmp('IDazione').setValue(ArrReload[1]);
											Ext.getCmp('cmbFA').setValue(ArrReload[2]);
											Ext.getCmp('ckMul').setValue(ArrReload[3]);
											Ext.getCmp('ckAtt').setValue(ArrReload[4]);
											Ext.getCmp('cmbStatoRec').setValue(ArrReload[5]);
											Ext.getCmp('CondId').setValue(ArrReload[6]);
											Ext.getCmp('cmbClasseSucc').setValue(ArrReload[7]);
											Ext.getCmp('cmbStatoRecSucc').setValue(ArrReload[8]);
											//checkboxconfigs=ArrReload[9];
											for(var k=0;k<ArrReload[9].length;k++)
											{
												CheckProfGroup.setValue(ArrReload[9][k].id,ArrReload[9][k].checked);
											}
											Ext.getCmp('chkAll').setValue(ArrReload[10]);
											Ext.getCmp('Flarg').setValue(ArrReload[11]);
											Ext.getCmp('Falt').setValue(ArrReload[12]);
											Ext.getCmp('evasione').setValue(ArrReload[13]);
											Ext.getCmp('ordinef').setValue(ArrReload[14]);
											Ext.getCmp('pSvalutazione').setValue(ArrReload[15]);
											range = dsAzioneGenerale.getRange();
											var rec = range[0];
											var nomeAu=' Automatismi';
											if(r.length>0)
											{
												if(rec.data.NumAut==1)
													nomeAu=' Automatismo';
												Ext.getCmp('btnApriAut').setText(rec.data.NumAut+nomeAu);
											}else{
												Ext.getCmp('btnApriAut').setText('0 '+nomeAu);
											}
											Ext.getCmp('btnApriAut').setDisabled(false);
											Ext.getCmp('btnApriCondC').setDisabled(false);
											//Ext.getCmp('fsC').doLayout();
										}
									},
									scope: this
								});
							}
						});
					}
				});
			}
		});
		
		ckStore.load({
			callback: function(r,options,success){
				  
				range = ckStore.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
				    checkboxconfigs.push({ 
				        id:rec.data.IdProfilo,
				        boxLabel:rec.data.TitoloProfilo,
				        checked: false
				      });
				}
				if(ArrReload=='')
				{
					Ext.Ajax.request({
				        url: 'server/utentiProfili.php',
				        method: 'POST',
				        params: {task: 'checkPAzione', idAzione: this.idAzione},
				        success: function(obj) {
				            if (obj.responseText != '') {
								eval("var ruoli = "+obj.responseText);
								for (var i=0; i<ruoli.length; i++) {
									try {
										for (var i1=0; i1<checkboxconfigs.length; i1++) {
											if (checkboxconfigs[i1].id==ruoli[i]){
												checkboxconfigs[i1].checked= true;
												break;
											}
										}
									} catch (err) {}
								}
				            } else {
				                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
				            }
							Ext.getCmp('fsC').add(CheckProfGroup);
							Ext.getCmp('fsC').doLayout();
						},
						scope: this
				    });
				}else{
					Ext.getCmp('fsC').add(CheckProfGroup);
					Ext.getCmp('fsC').doLayout();
				}
			},
			scope: this
		});
	},
	//--------------------------------------------------------
    // Visualizza automatismi
    //--------------------------------------------------------
	showAutAzWfDettaglio: function(IdAz,titoloAzione)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.AutomatismiAzWrkF.create(IdAz,titoloAzione);
		winS = new Ext.Window({
    		width: 1000, height:500, minWidth: 700, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Automatismi associati all\'azione \''+titoloAzione+'\'',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winS});
		winS.show();
		myMask.hide();
		pnl.activation.call(pnl);
    },
	showStatoDetail: function(IdSt,store,rowIndex,procAss,link,isAson,idAzSon,titPrec) 
	{
		
		/*console.log("IdSt "+IdSt);
		console.log("rowIndex "+rowIndex);
		console.log("procAss "+procAss);
		console.log("link "+link);
		console.log("indexson "+rowIndex);*/
		isAson=isAson||false;
		idAzSon=idAzSon||'';
		var myMask = new Ext.LoadMask(Ext.getBody(), {
			msg: "Lettura dettaglio..."
		});
		myMask.show();
		IdSt=IdSt||'';
		var flagCreation=0;
		var h=230;
		var undergridLinkStore=null;
		var hideCmb=false;
		if(IdSt==''){
			if(link==1){
				titolo='Collegamento di uno stato';
				h=255;
				flagCreation=0;//fai caricar lo store come fosse la griglia
				undergridLinkStore=store;
			}else{
				h=255;
				titolo='Creazione di uno stato';
				flagCreation=1;//non carica nulla nella griglia
			}
			if(isAson)//se è in creazione e non è arrivato dal dettaglio azione
			{	
				undergridLinkStore=store;
				hideCmb=true;
				h=220;
			}else{
				store=null;
				rowIndex=0;
			}
		}else{
			titolo='Modifica dello stato \''+titPrec+'\'';
			hideCmb=true;
		}	
		
		if(!isAson)
		{
			var nameNW = 'dettaglioStato'+IdSt;
			
			if (oldWind != '') {
				winState = Ext.getCmp(oldWind);
				winState.close();
			}
			oldWind = nameNW;
		}
		var pnl = new DCS.showStatoDetailSec.create(IdSt,store,rowIndex,procAss,link,idAzSon,flagCreation,undergridLinkStore,hideCmb);
		winState = new Ext.Window({
			width: 450,
			height: h,
			minWidth: 300,
			minHeight: h,
			layout: 'fit',
			id:'dettaglioStato'+IdSt,
			stateful:false,
			plain: true,
			bodyStyle: 'padding:5px;',
			modal: true,
			title: titolo,
			constrain: true,
			items: [pnl]
		});
		winState.show();
		winState.on({
			'close' : function () {
					if(!isAson){oldWind = '';}
				}
		});
		myMask.hide();
		
	}
});

// register xtype
Ext.reg('DCS_dettaglioAzioneWork', DCS.DettaglioAzWkf);

//--------------------------------------------------------
//Visualizza dettaglio Azione creazione/modifica
//--------------------------------------------------------
function showAzioneDetail(IdAz,store,rowIndex,procAss,titPass,loadAfterCC) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	loadAfterCC=loadAfterCC||'';
	IdAz=IdAz||'';
	var usefulStore=store;
	if(IdAz==''){
		titolo='Creazione di un azione';
		usefulStore=null;
		rowIndex=0;
	}else{
		titolo='Modifica dell\'azione \''+titPass+'\'';
	}	
	
	var nameNW = 'dettaglio'+IdAz;
	
	if (oldWind != '') {
		win = Ext.getCmp(oldWind);
		win.close();
	}
	oldWind = nameNW;

	win = new Ext.Window({
		width: 900,
		height: 610,
		minWidth: 800,
		minHeight: 610,
		layout: 'fit',
		id:'dettaglio'+IdAz,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAzioneWork',
			idAzione: IdAz,
			listStore: store,
			listUseStore: usefulStore,
			rowIndex: rowIndex,
			idProcedura:procAss,
			titoloAz:titPass,
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
	
}; // fine funzione 