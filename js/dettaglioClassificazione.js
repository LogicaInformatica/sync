// Crea namespace DCS
Ext.namespace('DCS');
//ASSEGNAZIONE
//window di dettaglio
var winClass;

DCS.recordClGen = Ext.data.Record.create(
				{name: 'IdClasse', type: 'int'},
				{name: 'CodClasse'},
				{name: 'CodClasseLegacy'},
				{name: 'TitoloClasse'},
				{name: 'AbbrClasse'},
				{name: 'FlagRec'},
				{name: 'FlagNONAffido'},
				{name: 'IdTipoPagamento'},
				{name: 'IdFamiglia'},
				{name: 'NumInsolutiDa'},
				{name: 'NumInsolutIA'},
				{name: 'NumRataDa'},
				{name: 'NumRataA'},
				{name: 'ImpInsolutoDa'},
				{name: 'ImpInsolutoA'},
				{name: 'NumGiorniDa'},
				{name: 'NumGiorniA'},
				{name: 'Condizione'},
				{name: 'FlagManuale'},
				{name: 'FlagRecidivoMAN'},
				{name: 'TipoPagamentoMAN'},
				{name: 'TitoloFamigliaMAN'},
				{name: 'DataIni',type:'date'},
				{name: 'DataFin',type:'date'},
				{name: 'gravita', type: 'int'});

DCS.DettaglioClasse = Ext.extend(Ext.TabPanel, {
	IdClasse:'',
	listStore:null,
	rowIndex:0,
	titPrec:'',
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		var ClassID=this.IdClasse;
		//var mainGridStore=this.listStore;
		var roundGridStore=this.listStore;
		if(this.IdClasse=='')
		{
			roundGridStore=null;
			this.listStore=null;
			this.rowIndex='';
		}
		var IdMain = this.getId();
		var tClass = this.titPrec;
		var tTab='Dettaglio classificazione';
		var comChgFam=false;
		var rowIndex = this.rowIndex;	
		var subCondFam='';
		var campoId='';
		var AllowBlank3gAlternate=true;//controllare in save
		var itemTipClassificazione=[['A','Impostazione automatica'],['M','Impostazione manuale'],['B','Automatica e manuale'],['S','Speciale']];
		var itemTipoRecidivo=[['Y','Recidivo'],['N','Non recidivo'],['-1','Indifferente']];

		//stores: dati da visualizzare
		var dsReg = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneClassificazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readClassDett',idClasse:this.IdClasse},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordClGen)
		});
		
		//STORES
		var dsTipClass = new Ext.data.ArrayStore({
	        id: 'classTStore',
	        idIndex: 0,  
		    fields: [
				       {name: 'FlagManuale'},
				       {name: 'tipoClassificazione'}
				    ]
	    });
		var dsRecidivo = new Ext.data.ArrayStore({
	        id: 'classRECtore',
	        idIndex: 0,  
		    fields: [
				       {name: 'FlagRecidivoMAN'},
				       {name: 'TipoRecidivo'}
				    ]
	    });
		dsTipClass.loadData(itemTipClassificazione);
		dsRecidivo.loadData(itemTipoRecidivo);
		var sqlPagamentoCmb="select IdTipoPagamento, TitoloTipoPagamento from tipopagamento where ordine is not null union all select -1,'' order by 1";
		var dsTipoPagamento = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlPagamentoCmb
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdTipoPagamento'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdTipoPagamento', type:'int'},
				{name: 'TitoloTipoPagamento'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		var sqlFam="SELECT fp.IdFamiglia,fp.TitoloFamiglia FROM famigliaprodotto fp";
			sqlFam+=" where now()<fp.DataFin union all select -1,'' order by 1";
		var dsFamiglia = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlFam
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdFamiglia'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdFamiglia', type: 'int'},
				{name: 'TitoloFamiglia'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		//BUTTONS
		var chiudi = new Ext.Button({
			text: 'Annulla',
			handler: function(b,event) {
				winClass.close();
			},
			scope: this
		});
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsReg,
			text: 'Salva',
			id:'svBtnAOp',
			disabled:true,
			handler: function(b,event) 
			{
				if (formClassificazione.getForm().isDirty()) 
				{
					if (formClassificazione.getForm().isValid())
					{
						Ext.getCmp(IdMain).checkCond(Ext.getCmp('TxtCond').getValue(),true,formClassificazione);
					}else{
						ErrorsSql='Tipologia di distribuzione non specificato.';
						//Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
					}
				}else{
					ErrorsSql='Non sono stati immessi dati.';
					Ext.MessageBox.alert('Impossibile salvare',ErrorsSql);
				}
			},
			scope: this
		});
		
		// Componenti del form
		var fieldSet1 = { // fieldset in alto a sinistra
				xtype:'fieldset', title:'Descrizione', border: true,columnWidth:1,
				items:[{
					xtype:'panel', layout:'form', labelWidth:90, defaultType:'textfield',
					defaults: {anchor:'99%', readOnly:false},
					items: [{fieldLabel:'Titolo',	name:'TitoloClasse', id:'TxtTit', style:'text-align:left', disabled:true,hidden:false,allowBlank: false},
					        {fieldLabel:'Codice',	name:'CodClasse', id:'TxtCod', style:'text-align:left', disabled:true,hidden:false,allowBlank: false},
					        {fieldLabel:'Cod. Legacy',	name:'CodClasseLegacy', id:'TxtCodL', style:'text-align:left', disabled:true,hidden:false},
					        {fieldLabel:'Abbreviazione',	name:'AbbrClasse', id:'TxtAbbr', style:'text-align:left', disabled:true,hidden:false,allowBlank: false}						        ]
				}]};
				
		var comboClass = {xtype:'panel', layout:'form', labelWidth:90,columnWidth:1, 
					defaults: {anchor:'99%'},
					items: [{xtype: 'combo',
							fieldLabel: 'Modalità',
							name:'cmbTclass',
							id:'cmbTC',
							allowBlank: false,
							hiddenName: 'cmbTclass',
							typeAhead: false, 
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							mode:'local',
							lazyRender: true,	//should always be true for editor
							store: dsTipClass,
							displayField: 'tipoClassificazione',
							valueField: 'FlagManuale'
					}]};
		var comboRecidivo = {xtype:'panel', layout:'form', labelWidth:90,columnWidth:1, 
					defaults: {anchor:'99%'},
					items: [{xtype: 'combo',
						fieldLabel: 'Recidivo',
						name:'cmbRecidivo',
						id:'cmbRCD',
						allowBlank: false,
						hiddenName: 'cmbRecidivo',
						typeAhead: false, 
						editable:false,
						disabled:true,
						hidden:false,
						triggerAction: 'all',
						mode:'local',
						lazyRender: true,	//should always be true for editor
						store: dsRecidivo,
						displayField: 'TipoRecidivo',
						valueField: 'FlagRecidivoMAN'}]};
		var checkRecupero = {xtype:'panel', layout:'form', labelWidth:90,columnWidth:.50, 
			defaults: {anchor:'99%'},
			items: [{style: 'padding-left:0px; anchor:"0%";',
				xtype: 'checkbox',
				fieldLabel: 'A recupero',
				id: 'ckRec',
				name:'ChkRecupero',
				hiddenName: 'ChkRecupero',
				hidden: false,
				checked: false
			}]};
		var checkNonAff = {xtype:'panel', layout:'form', labelWidth:90,columnWidth:.50, 
				defaults: {anchor:'99%'},
				items: [{
					style: 'padding-left:0px; anchor:"0%";',
					xtype: 'checkbox',
					fieldLabel: 'Da non affidare',
					id: 'ckNAff',
					name:'ChkNonAff',
					hiddenName: 'ChkNonAff',
					hidden: false,
					checked: false
				}]};
		
		// secondo fieldset a sinistra
		var fieldSet2 = {xtype:'fieldset', title:'Dettagli', border: true, columnWidth:1,
				items:[comboClass,comboRecidivo
					   ,{xtype:'container', layout:'column',
						items: [checkRecupero,checkNonAff]
					   }
					   ,{xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:90,columnWidth:.50, defaultType:'textfield',
								defaults: {anchor:'99%', readOnly:false},
								items: [{fieldLabel:'Gravit&agrave',	name:'gravita', id:'TxtOrdine', style:'text-align:center', disabled:true,hidden:false
									   }]
							}]
					   }
					   ,validityDatesInColumns(90)
					  ]};
		
		// Fieldset lato destro
		var fieldSet3 = {xtype:'fieldset', title:'Vincoli', border: true,columnWidth:1,height:314,
				items:[{
					xtype:'panel', layout:'form', labelWidth:110,/*columnWidth:.98,*/ defaultType:'textfield',
					defaults: {anchor:'97%', readOnly:false},
					items:[{
							xtype: 'combo',
							fieldLabel: 'Tipo pagamento',
							name:'cmbTpaga',
							id:'cmbTPG',
							allowBlank: true,
							hiddenName: 'cmbTpaga',
							typeAhead: false, 
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsTipoPagamento,
							displayField: 'TitoloTipoPagamento',
							valueField: 'IdTipoPagamento',
							listeners:{
										scope:this,
										select:function(combo, record, index){}
							}
					},{
							xtype: 'combo',
							fieldLabel: 'Famiglia prodotto',
							name:'cmbFamProdAA',
							id:'cmbFpAA',
							allowBlank: true,
							hiddenName: 'cmbFamProdAA',
							typeAhead: false,
							valueNotFoundText:'',
							editable:false,
							disabled:true,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsFamiglia,
							displayField: 'TitoloFamiglia',
							valueField: 'IdFamiglia',
							listeners:{
										scope:this,
										select:function(combo, record, index){
											
										}								
							}
					},{
						xtype:'fieldset', title:'', border: true,columnWidth:.99,
						items:[{
							xtype:'container', layout:'column',
							items:[{
								xtype:'container',columnWidth:.62,
								items:[{
									xtype:'panel', layout:'form', labelWidth:150,defaultType:'textfield',
									defaults: {anchor:'99%', readOnly:false},
									items: [{fieldLabel:'Numero insoluti da',	name:'NumInsolutiDa', id:'TxtInsDA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50},
									        {fieldLabel:'Numero rate da',	name:'NumRataDa', id:'TxtRataDA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50}]
								}]
							},{
								xtype:'container',columnWidth:.38,
								items:[{
									xtype:'panel', layout:'form', labelWidth:35,/*columnWidth:.50,*/defaultType:'textfield',
									defaults: {anchor:'99%', readOnly:false},
									items: [{fieldLabel:'a',	name:'NumInsolutiA', id:'TxtInsA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50},
									        {fieldLabel:'a',	name:'NumRataA', id:'TxtRataA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50}]
								}]
							}]
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'container',columnWidth:.62,
								items:[{
									xtype:'panel', layout:'form', labelWidth:150,defaultType:'textfield',
									defaults: {anchor:'99%', readOnly:false},
									items: [{fieldLabel:'Totale importo da',	name:'ImpInsolutoDa', id:'TxtImpInsDA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:70},
									        {fieldLabel:'Numero di giorni da',	name:'NumGiorniDa', id:'TxtDayDA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50}]
								}]
							},{
								xtype:'container',columnWidth:.38,
								items:[{
									xtype:'panel', layout:'form', labelWidth:35,/*columnWidth:.50,*/defaultType:'textfield',
									defaults: {anchor:'99%', readOnly:false},
									items: [{fieldLabel:'a',	name:'ImpInsolutoA', id:'TxtImpInsA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:70},
									        {fieldLabel:'a',	name:'NumGiorniA', id:'TxtDayA', style:'text-align:center', disabled:true,hidden:false,boxMaxWidth:50}]
								}]
							}]
						}]
					},{xtype:'textarea', fieldLabel:'Condizione',	name:'Condizione', id:'TxtCond', style:'text-align:left', disabled:true,hidden:false}]
				}]
			};

		//Form su cui montare gli elementi
		var formClassificazione = new Ext.form.FormPanel({
			title:tTab,		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordClGen
			}),
			items: [{xtype:'container', layout:'column',
					items:[{xtype:'container',columnWidth:.50, items:[fieldSet1,fieldSet2]}
					      ,{xtype:'container',columnWidth:.50, items:[fieldSet3]}]}
					],
			buttons:[save,chiudi]
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
							showClasseDetail(rec.get('IdClasse'),roundGridStore,newIndex,rec.get('TitoloClasse'));
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showClasseDetail(rec.get('IdClasse'),roundGridStore,newIndex,rec.get('TitoloClasse'));
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formClassificazione],
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
					'-', helpButton("DettaglioClassificazione")]
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
		
		DCS.DettaglioClasse.superclass.initComponent.call(this);
		
		//caricamento dello store
		dsFamiglia.load({
			callback : function(r,options,success) 
			{
				dsTipoPagamento.load({
					callback : function(r,options,success) 
					{
						dsReg.load({
							callback : function(r,options,success) 
							{
								var loaded=false;
								if (success && r.length>0) 
								{
									range = dsReg.getRange();
									var rec = range[0];
									loaded=true;
									//formClassificazione.getForm().loadRecord(rec.json);
									Ext.getCmp('TxtTit').setValue(rec.json.TitoloClasse);
									Ext.getCmp('TxtCod').setValue(rec.json.CodClasse);
									Ext.getCmp('TxtCodL').setValue(rec.json.CodClasseLegacy);
									Ext.getCmp('TxtAbbr').setValue(rec.json.AbbrClasse);
									
									Ext.getCmp('cmbTC').setValue(rec.json.FlagManuale);
									Ext.getCmp('TxtOrdine').setValue(rec.json.gravita);
									Ext.getCmp('cmbRCD').setValue(rec.json.FlagRecidivoMAN);
									Ext.getCmp('ckRec').setValue(rec.json.FlagRec);
									Ext.getCmp('ckNAff').setValue(rec.json.FlagNONAffido);
									
									Ext.getCmp('cmbTPG').setValue(rec.json.IdTipoPagamento);
									Ext.getCmp('cmbFpAA').setValue(rec.json.IdFamiglia);
									
									Ext.getCmp('TxtInsDA').setValue(rec.json.NumInsolutiDa);
									Ext.getCmp('TxtInsA').setValue(rec.json.NumInsolutiA);
									Ext.getCmp('TxtRataDA').setValue(rec.json.NumRataDa);
									Ext.getCmp('TxtRataA').setValue(rec.json.NumRataA);
									Ext.getCmp('TxtImpInsDA').setValue(rec.json.ImpInsolutoDa);
									Ext.getCmp('TxtImpInsA').setValue(rec.json.ImpInsolutoA);
									Ext.getCmp('TxtDayDA').setValue(rec.json.NumGiorniDa);
									Ext.getCmp('TxtDayA').setValue(rec.json.NumGiorniA);
									Ext.getCmp('DataIni').setValue(rec.json.DataIni);
									Ext.getCmp('DataFin').setValue(rec.json.DataFin);
									Ext.getCmp('TxtCond').setValue(rec.json.Condizione);
								}
								Ext.getCmp('TxtTit').setDisabled(false);
								Ext.getCmp('TxtCod').setDisabled(false);
								Ext.getCmp('TxtCodL').setDisabled(false);
								Ext.getCmp('TxtAbbr').setDisabled(false);
								Ext.getCmp('cmbTC').setDisabled(false);
								Ext.getCmp('TxtOrdine').setDisabled(false);
								Ext.getCmp('cmbRCD').setDisabled(false);
								Ext.getCmp('cmbTPG').setDisabled(false);
								Ext.getCmp('cmbFpAA').setDisabled(false);
								Ext.getCmp('TxtCond').setDisabled(false);
								Ext.getCmp('TxtInsDA').setDisabled(false);
								Ext.getCmp('TxtInsA').setDisabled(false);
								Ext.getCmp('TxtRataDA').setDisabled(false);
								Ext.getCmp('TxtRataA').setDisabled(false);
								Ext.getCmp('TxtImpInsDA').setDisabled(false);
								Ext.getCmp('TxtImpInsA').setDisabled(false);
								Ext.getCmp('TxtDayDA').setDisabled(false);
								Ext.getCmp('TxtDayA').setDisabled(false);
								Ext.getCmp('DataIni').setDisabled(false);
								Ext.getCmp('DataFin').setDisabled(false);
								
								Ext.getCmp('svBtnAOp').setDisabled(false);
							},
							scope: this
						});
					},
					scope:this
				});
			},
			scope:this
		});
		
		//listeners definizione ed assegnazione
		var onChangeCond = function(ev,nw,ow){
		    this.checkCond(nw,false);
		};
		var onChangeNumericRange = function(ev,nw,ow){
		    this.checkNumCond(nw,ev.id);
		};
		var scope = this;
		var condizione = Ext.getCmp('TxtCond');
		condizione.on('change', onChangeCond, scope);
		var daA11 = Ext.getCmp('TxtInsDA');
		var daA12 = Ext.getCmp('TxtInsA');
		var daA21 = Ext.getCmp('TxtRataDA');
		var daA22 = Ext.getCmp('TxtRataA');
		var daA31 = Ext.getCmp('TxtDayDA');
		var daA32 = Ext.getCmp('TxtDayA');
		var daAImp1 = Ext.getCmp('TxtImpInsDA');
		var daAImp2 = Ext.getCmp('TxtImpInsA');
		daA11.on('change', onChangeNumericRange,scope);
		daA12.on('change', onChangeNumericRange,scope);
		daA21.on('change', onChangeNumericRange,scope);
		daA22.on('change', onChangeNumericRange,scope);
		daA31.on('change', onChangeNumericRange,scope);
		daA32.on('change', onChangeNumericRange,scope);
		daAImp1.on('change', onChangeNumericRange,scope);
		daAImp2.on('change', onChangeNumericRange,scope);
		//end listeners assegnazione
	},
	checkNumCond: function(condToCk,idObj)
	{
		var regola='';
		switch(idObj)
		{
			case 'TxtImpInsDA':regola='^([-]*([0-9][0-9]*)+([.]{0,1}[0-9]+)*)*$';
				break;
			case 'TxtImpInsA':regola='^([-]*([0-9][0-9]*)+([.]{0,1}[0-9]+)*)*$';
				break;
			default:regola='^[-]*[0-9]*$';
				break;
		}
		testCom=condToCk;
		patt=new RegExp(regola);
		if(!patt.test(testCom))
		{
			Ext.MessageBox.alert('Errore','Il valore immesso non &egrave un numero valido.');
			Ext.getCmp(idObj).setValue('');
			condToCk='';
		}
	},
	checkCond: function(condToCk,isSave,formSave)
	{
		var ClassID=this.IdClasse;
		var tClass=this.titPrec;
		var storeReload=this.listStore;
		var comodoArr=condToCk.split('\n');
		var SecondCondToCk=comodoArr[0];
		for(var g=1;g<comodoArr.length;g++)
		{
			SecondCondToCk+=' '+comodoArr[g];
		}
		testCom=SecondCondToCk;
		patt=new RegExp('^[ ]+$');
		if(patt.test(testCom))
		{
			Ext.getCmp('TxtCond').setValue('');
			condToCk='';
		}
		
		if(condToCk=='')
		{
			condToCk=1;	
		}
		
		Ext.Ajax.request({
			url : 'server/AjaxRequest.php' , 
			params : {task: 'read',sql: "SELECT 1 as num FROM v_pratiche where "+condToCk+" limit 1"},
			method: 'POST',
			autoload:true,
			success: function ( result, request ) {
				var jsonData = Ext.util.JSON.decode(result.responseText);
				if(jsonData.error==null || jsonData.error=='')
				{
					var slave=jsonData.total;
					if(slave>0){
						ErrorsSql='';
						if(isSave)
						{
							Ext.getCmp('TxtCond').setValue(SecondCondToCk);
							//salvataggio form
							formSave.getForm().submit({
								url: 'server/gestioneClassificazioni.php', method: 'POST',
								params: {task:"saveClassificazione",idClass:ClassID,tClass:tClass},
								success: function (frm,action) {
									winClass.close();
									Ext.MessageBox.alert('Esito', action.result.messaggio);
									if(storeReload!=null){
										storeReload.reload();
									}
								},
								failure: function (frm,action) {//saveFailure
									Ext.MessageBox.alert('Esito', action.result.messaggio); 
									winClass.close();
									if(storeReload!=null)
										storeReload.reload();
								}
							});
						}
					}else{
						if(!isSave)
						{
							ErrorsSql="La condizione specificata non restituisce alcun risultato.";
							Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
						}
					}
				}else{
					ErrorsSql=jsonData.error;
					Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
				}
			},
			failure: function ( result, request) { 
				ErrorsSql='Errore nel contattare il server. Controllare che sia online.';
				Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
			},
			scope:this
		});
	}
});

// register xtype
Ext.reg('DCS_DettaglioClasse', DCS.DettaglioClasse);

//--------------------------------------------------------------------------------------
//Visualizza dettaglio della classe
//--------------------------------------------------------------------------------------
function showClasseDetail(IdClasse,store,rowIndex,titPrec) 
{
	IdClasse=IdClasse||'';
	rowIndex=rowIndex||0;
	store=store||null;
	titPrec=titPrec||'';
	if(titPrec!='')
	{
		titPrec="\'"+titPrec+"\'";
	}
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	
	if(IdClasse==''){
		titolo='Creazione di una classificazione';
	}else{
		titolo='Modifica della classificazione '+titPrec+'';
	}	
	
	var nameNW = 'dettaglioClass'+IdClasse;
	if (oldWind != '') {
		winClass = Ext.getCmp(oldWind);
		winClass.close();
	}
	oldWind = nameNW;
	winClass = new Ext.Window({
		width: 1000,
		height: 480,
		minWidth: 1000,
		minHeight: 480,
		layout: 'fit',
		id:'dettaglioClass'+IdClasse,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		constrain: true,
		items: [{
			xtype: 'DCS_DettaglioClasse',
			listStore: store,
			IdClasse:IdClasse,
			rowIndex:rowIndex,
			titPrec:titPrec
		}]
	});
	winClass.show();
	winClass.on({
		'close' : function () {oldWind = '';}
	});
	myMask.hide();
	
}; // fine funzione 