// Crea namespace DCS
Ext.namespace('DCS');
//ASSEGNAZIONE
//window di dettaglio
var winOpR;

DCS.recordOA = Ext.data.Record.create([
           {name: 'IdRegolaAssegnazione', type: 'int'},
           {name: 'IdRegolaProvvigione', type: 'int'},
           {name: 'IdReparto'},
           {name: 'IdClasse'},
           {name: 'IdFamiglia'},
           {name: 'TipoDistribuzione'},
           {name: 'tipodistribuzioneConv'},
           {name: 'Condizione'},
           {name: 'Nominativo'},
           {name: 'DataIni', type:'date'},
           {name: 'DataFin', type:'date'},
           {name: 'TitoloRegolaProvvigione'}]);

DCS.DettaglioAssociazOP = Ext.extend(Ext.TabPanel, {
	idRegolaOp:'',
	listStore:null,
	rowIndex:0,
	titPrec:'',
	NomeOp:'',
	IdOp:'',
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		var regOpID=this.idRegolaOp;
		var mainGridStore=this.listStore;
		var roundGridStore=this.listStore;
		if(this.idRegolaOp=='')
		{
			roundGridStore=null;
			this.listStore=null;
			this.rowIndex='';
		}
		var IdMain = this.getId();
		var tReg = this.titPrec;
		var tTab='Dettaglio regola';
		var comChgFam=false;
		var rowIndex = this.rowIndex;	
		var subCondFam='';
		var campoId='';
		var operatore = this.NomeOp;
		var idOperatore = this.IdOp;
		var AllowBlank3gAlternate=true;//controllare in save
				
		//var creation=this.isCreation;
		//stores: dati da visualizzare
		var dsReg = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readRegOpDetGrid',idReg:this.idRegolaOp},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordOA)
		});
		
		//STORES
		var sqlClassCmb="SELECT cl.IdClasse,cl.TitoloClasse FROM classificazione cl where ifnull(FlagRecupero,'N') ='N'";
		sqlClassCmb+=" and ifnull(FlagNoAffido,'N') ='Y' union all select -1,'' order by 1";
		var dsClassi = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlClassCmb
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdClasse'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdClasse', type: 'int'},
				{name: 'TitoloClasse'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		var sqlFam="SELECT fp.IdFamiglia,fp.TitoloFamiglia FROM famigliaprodotto fp";
			sqlFam+=" where fp.IdFamigliaParent is null";
			sqlFam+=" and now()<fp.DataFin union all select -1,'' order by 1";
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
		
		var dsReparto = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "select IdReparto,TitoloUfficio from reparto where idtiporeparto>1 union all select -1,'' order by 1"
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdReparto'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdReparto', type: 'int'},
				{name: 'TitoloUfficio'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		var sqlRegP="select rp.IdRegolaProvvigione,concat(r.titoloufficio,' (',CodRegolaProvvigione,')') as Nominativo";
			sqlRegP+=" from regolaprovvigione rp"; 
			sqlRegP+=" left join reparto r on(rp.idreparto=r.idreparto)";
			sqlRegP+=" union all select -1,'' order by 1";
		var dsRegProvv = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlRegP
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdRegolaProvvigione'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdRegolaProvvigione', type: 'int'},
				{name: 'Nominativo'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		//BUTTONS
		var chiudi = new Ext.Button({
			text: 'Annulla',
			handler: function(b,event) {
				winOpR.close();
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
				if (formAssociazioneOp.getForm().isDirty()) 
				{	
					if (formAssociazioneOp.getForm().isValid())
					{
						if(Ext.getCmp('cmbTdAA').getValue()!='')
						{
							var cond=Ext.getCmp('TxtCond').getValue();
							var ErrorsSql='';
							if(cond!='')//se è di secondo tipo e la condizione c'è: controlla che sia valida prima di salvare  
							{
								Ext.Ajax.request({
									url : 'server/AjaxRequest.php' , 
									params : {task: 'read',sql: "SELECT 1 as num FROM v_cond_affidamento c where "+cond+" limit 1"},
									method: 'POST',
									autoload:true,
									success: function ( result, request ) {
										var jsonData = Ext.util.JSON.decode(result.responseText);
										if(jsonData.error==null || jsonData.error=='')
										{
											var slave=jsonData.total;
											if(slave>=0) { // accetta anche se 0 (dal 2016-05-08) regole assegnazione non automatiche
												ErrorsSql='';
												this.setDisabled(true);
												//scelta caso ed assegnazione di tipoassegnazione
												formAssociazioneOp.getForm().submit({
													url: 'server/gestioneAssegnazioni.php', method: 'POST',
													params: {task:"saveAssociazione",idReg:regOpID,tReg:tReg,idOperatore:idOperatore},
													success: function (frm,action) {
														winOpR.close();
														Ext.MessageBox.alert('Esito', action.result.messaggio);
														if(mainGridStore!=null){
															mainGridStore.reload();
														}
													},
													failure: function (frm,action) {//saveFailure
														Ext.MessageBox.alert('Esito', action.result.messaggio); 
														winOpR.close();
														if(mainGridStore!=null)
															mainGridStore.reload();
													}
												});
											}else{
												ErrorsSql="La condizione specificata non restituisce alcun risultato.";
												Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
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
							}else{
								//il campo condizione è vuoto
								this.setDisabled(true);
								//scelta caso ed assegnazione di tipoassegnazione
								formAssociazioneOp.getForm().submit({
									url: 'server/gestioneAssegnazioni.php', method: 'POST',
									params: {task:"saveAssociazione",idReg:regOpID,tReg:tReg,idOperatore:idOperatore},
									success: function (frm,action) {
										winOpR.close();
										Ext.MessageBox.alert('Esito', action.result.messaggio);
										if(mainGridStore!=null){
											mainGridStore.reload();
										}
									},
									failure: function (frm,action) {//saveFailure
										Ext.MessageBox.alert('Esito', action.result.messaggio); 
										winOpR.close();
										if(mainGridStore!=null)
											mainGridStore.reload();
									}
								});
							}
						}
					}else{
						ErrorsSql='Tipop di distribuzione non specificato.';
						//Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
					}
				}else{
					ErrorsSql='Non sono stati immessi dati.';
					Ext.MessageBox.alert('Impossibile salvare',ErrorsSql);
				}
			},
			scope: this
		});
		
		var	itemNewEdit = [{
			xtype:'fieldset', title:'', border: true,columnWidth:.98,
			items:[{
					xtype:'panel', layout:'form', labelWidth:120,/*columnWidth:.98,*/ defaultType:'textfield',
					defaults: {anchor:'97%', readOnly:false},
					items:[{
							xtype: 'combo',
							fieldLabel: 'Reparto',
							name:'cmbAssRepA',
							id:'cmbReA',
							allowBlank: true,
							hiddenName: 'cmbAssRepA',
							typeAhead: false, 
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsReparto,
							displayField: 'TitoloUfficio',
							valueField: 'IdReparto',
							listeners:{
										scope:this,
										select:function(combo, record, index){
											if(record!='')
											{
												if(record.get('TitoloUfficio')!='')
												{
													this.setDisabled(false);
													Ext.getCmp('cmbRPA').setDisabled(true);
													Ext.getCmp('cmbRPA').setValue('');
												}else{
													Ext.getCmp('cmbRPA').setDisabled(false);
												}
											}
										}
							}
					},{
							xtype: 'combo',
							fieldLabel: 'Regola provvigione',
							name:'cmbRegProAA',
							id:'cmbRPA',
							allowBlank: true,
							hiddenName: 'cmbRegProAA',
							typeAhead: false, 
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsRegProvv,
							valueNotFoundText:'',
							displayField: 'Nominativo',
							valueField: 'IdRegolaProvvigione',
							listeners:{
										scope:this,
										select:function(combo, record, index){
											if(record!='')
											{
												if(record.get('Nominativo')!='')
												{
													this.setDisabled(false);
													Ext.getCmp('cmbReA').setDisabled(true);
													Ext.getCmp('cmbReA').setValue('');
												}else{
													Ext.getCmp('cmbReA').setDisabled(false);
												}
											}
										}
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
							xtype: 'combo',
							fieldLabel: 'Classe',
							name:'cmbClassAA',
							id:'cmbClAA',
							allowBlank: true,
							hiddenName: 'cmbClassAA',
							typeAhead: false, 
							editable:false,
							disabled:true,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsClassi,
							valueNotFoundText:'',
							displayField: 'TitoloClasse',
							valueField: 'IdClasse',
							listeners:{
										scope:this,
										select:function(combo, record, index){
											
										}
							}
					},{
							xtype: 'combo',
							fieldLabel: 'Tipo distribuzione',
							name:'cmbTipDisAA',
							id:'cmbTdAA',
							allowBlank: false,
							hiddenName: 'cmbTipDisAA',
							typeAhead: false,
							blankText:'Campo obbligatorio',
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							mode:'local',
						    lazyRender:true,
							store: new Ext.data.ArrayStore({
						        id: 'distStore',
						        idIndex: 0,  
							    fields: [
									       'TipoDistribuzione',
									       {name: 'NomeT'},
									       {name: 'idT', type: 'int'}
									    ],
						        data: [	['C','Carico totale',0],
						   				['I','Carico giornaliero',1]]
						    }),
							displayField: 'NomeT',
							valueField: 'TipoDistribuzione',
							listeners:{
										scope:this,
										select:function(combo, record, index){
							
										}
							}
						}]
			}]
		},{
			xtype:'container',
			items:[{
				xtype:'panel', layout:'form', labelWidth:90,columnWidth:.99, defaultType:'textfield',
				defaults: {anchor:'99%', readOnly:false},
				items: [{fieldLabel:'Condizione',	name:'Condizione', id:'TxtCond', style:'text-align:left', disabled:true,hidden:false}]
			}]
		},{
			xtype:'container', layout:'column',
			items:[{
				xtype:'panel', layout:'form', labelWidth:91,columnWidth:.60, defaultType:'textfield',
				defaults: {anchor:'80%', readOnly:false},
				items: [{
					xtype: 'datefield',
					format: 'd/m/Y',
					width: 120,
					autoHeight:true,
					allowBlank:false,
					fieldLabel: 'Valida dal',
					name: 'DataIni',
					id:'valDa'
				}]
			},{
				xtype:'panel', layout:'form', labelWidth:50,columnWidth:.40, defaultType:'textfield',
				defaults: {anchor:'99%', readOnly:false},
				items: [{
					xtype: 'datefield',
					format: 'd/m/Y',
					width: 120,
					autoHeight:true,
					allowBlank:false,
					fieldLabel: 'al',
					name: 'DataFin',
					id:'valAd'
				}]
			}]
		}];
				
		//Form su cui montare gli elementi
		var formAssociazioneOp = new Ext.form.FormPanel({
			title:tTab,		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordOA
			}),
			items: [{
					xtype:'container',// layout:'column',
					items: [itemNewEdit]
			}],
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
							showAssegnazioneDetail(rec.get('IdRegolaAssegnazione'),roundGridStore,newIndex,rec.get('codregolaprovvigione'),operatore);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showAssegnazioneDetail(rec.get('IdRegolaAssegnazione'),roundGridStore,newIndex,rec.get('codregolaprovvigione'),operatore);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formAssociazioneOp],
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
					'-', helpButton("DettaglioAssegnazione")]
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
		
		DCS.DettaglioAssociazOP.superclass.initComponent.call(this);
		
		//caricamento dello store
		dsReparto.load({
			callback : function(r,options,success) 
			{
				dsFamiglia.load({
					callback : function(r,options,success) 
					{
						dsClassi.load({
							callback : function(r,options,success) 
							{
								dsRegProvv.load({
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
													Ext.getCmp('cmbTdAA').setValue(rec.data.tipodistribuzioneConv);
													Ext.getCmp('cmbClAA').setValue(rec.data.IdClasse);
													Ext.getCmp('cmbFpAA').setValue(rec.data.IdFamiglia);
													Ext.getCmp('valDa').setValue(rec.data.DataIni);
													Ext.getCmp('valAd').setValue(rec.data.DataFin);
													if(rec.data.IdFamiglia==null)
													{	
														Ext.getCmp('cmbFpAA').clearValue();
														comChgFam=true;
													}
													Ext.getCmp('TxtCond').setValue(rec.data.Condizione);
													console.log("id "+rec.data.IdRegolaProvvigione);
													console.log("Nom "+rec.data.Nominativo);
													if(rec.data.Nominativo!='' && rec.data.Nominativo!=null)
													{
														Ext.getCmp('cmbRPA').setDisabled(false);
														Ext.getCmp('cmbReA').setDisabled(true);
														Ext.getCmp('cmbReA').setValue('');
														Ext.getCmp('cmbRPA').setValue(rec.data.IdRegolaProvvigione);
													}else if(rec.data.IdReparto!='' && rec.data.IdReparto!=null){
														Ext.getCmp('cmbReA').setDisabled(false);
														Ext.getCmp('cmbRPA').setDisabled(true);
														Ext.getCmp('cmbRPA').setValue('');
														Ext.getCmp('cmbReA').setValue(rec.data.IdReparto);
													}else{
														Ext.getCmp('cmbRPA').setDisabled(false);
														Ext.getCmp('cmbReA').setDisabled(false);
													}
													
												}
												Ext.getCmp('cmbTdAA').setDisabled(false);
												Ext.getCmp('cmbClAA').setDisabled(false);
												Ext.getCmp('cmbFpAA').setDisabled(false);
												Ext.getCmp('TxtCond').setDisabled(false);
												Ext.getCmp('svBtnAOp').setDisabled(false);
												if(!loaded)
												{
													Ext.getCmp('cmbRPA').setDisabled(false);
													Ext.getCmp('cmbReA').setDisabled(false);
												}												
											},
											scope: this
										});
									},
									scope:this
								});
							},
							scope:this
						});	
					},
					scope:this
				});
			},
			scope:this
		});
	}
});

// register xtype
Ext.reg('DCS_DettaglioAssociazOP', DCS.DettaglioAssociazOP);

//--------------------------------------------------------------------------------------
//Visualizza dettaglio regole assegnazioni ad operatore interno
//--------------------------------------------------------------------------------------
function showAssegnazioneDetail(IdReg,store,rowIndex,titPrec,NomeOp,IdOp) 
{
	IdReg=IdReg||'';
	IdOp=IdOp||'';
	rowIndex=rowIndex||0;
	store=store||null;
	NomeOp=NomeOp||'';
	titPrec=titPrec||'';
	if(titPrec!='')
	{
		titPrec="\'"+titPrec+"\'";
	}
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	
	var h=380;
	if(IdReg==''){
		titolo='Creazione di una regola per l\'operatore \''+NomeOp+'\'';
	}else{
		titolo='Modifica della regola';
	}	
	
	var nameNW = 'dettaglioRegOp'+IdReg;
	if (oldWind != '') {
		winOpR = Ext.getCmp(oldWind);
		winOpR.close();
	}
	oldWind = nameNW;
	winOpR = new Ext.Window({
		width: 500,
		height: h,
		minWidth: 450,
		minHeight: h,
		layout: 'fit',
		id:'dettaglioRegOp'+IdReg,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		constrain: true,
		items: [{
			xtype: 'DCS_DettaglioAssociazOP',
			listStore: store,
			idRegolaOp:IdReg,
			rowIndex:rowIndex,
			titPrec:titPrec,
			NomeOp:NomeOp,
			IdOp:IdOp
		}]
	});
	winOpR.show();
	winOpR.on({
		'close' : function () {oldWind = '';}
	});
	myMask.hide();
	
}; // fine funzione 