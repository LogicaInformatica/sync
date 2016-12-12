// Crea namespace DCS
Ext.namespace('DCS');
//Ripartizioni
//window di dettaglio
var winRegRip;

DCS.recordRegRip = Ext.data.Record.create([
            {name: 'IdRegolaRipartizione', type: 'int'},
            {name: 'IdRegolaProvvigione', type: 'int'},
			{name: 'IdReparto', type: 'int'},
			{name: 'IdClasse', type: 'int'},
			{name: 'IdFamiglia', type: 'int'},
			{name: 'Agenzia'},
			{name: 'RegolaProvvigione'},
			{name: 'Famiglia'},
			{name: 'Classe'},
			{name: 'FlagInteressiMora'},
			{name: 'FlagMora'},
			{name: 'PercSpeseIncasso'},
			{name: 'ImpSpeseIncasso'},
			{name: 'DataIni', type:'date'},
	        {name: 'DataFin', type:'date'},
			{name: 'LastUser'},
			{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}]);

DCS.DettaglioRegRipartizione = Ext.extend(Ext.TabPanel, {
	idRegoleProvv:'',
	idReparto:'',
	listStore:null,
	rowIndex:0,
	IdClasse:'',
	IdFamiglia:'',
	idRegolaRipartizione:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		var regolaRipartizioneID=this.idRegolaRipartizione;
		var regoleProvvID=this.idRegoleProvv;
		var repID=this.idReparto;
		var mainGridStore=this.listStore;
		var movingGridStore=this.listStore;
		var IdMain = this.getId();
		var classeID = this.IdClasse;
		var famigliaID = this.IdFamiglia;
		if(regolaRipartizioneID=='')
		{
			movingGridStore=null;
			this.listStore=null;
		}
		
		//var creation=this.isCreation;
		//stores: dati da visualizzare
		var dsRegolaRip = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneRipartizioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readDettRegRip',idRegRip:this.idRegolaRipartizione},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordRegRip)
		});
		
		//STORES
		var sqlClassCmb="SELECT cl.IdClasse,cl.TitoloClasse FROM classificazione cl where ifnull(FlagRecupero,'N')='Y'";
		sqlClassCmb+=" and ifnull(FlagNoAffido,'N')='N' union all select -1,'' order by 1";
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
		
		var sqlFam="SELECT IdFamiglia,TitoloFamiglia FROM famigliaprodotto";
			sqlFam+=" where IdFamigliaParent is null";
			sqlFam+=" and CURDATE() BETWEEN DataIni AND DataFin union all select -1,'' order by 1";
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
		
		var dsAgenzie = new Ext.data.Store({
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
		sqlRegP+=" where CURDATE() BETWEEN rp.DataIni AND rp.DataFin";
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
				winRegRip.close();
			},
			scope: this
		});
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsRegolaRip,
			text: 'Salva',
			id:'svBtnAOp',
			disabled:true,
			handler: function(b,event) 
			{
				if (formReg.getForm().isDirty()) 
				{	
					if (formReg.getForm().isValid())
					{
						//funzione di controllo
						var Errors='';
						var reparto = Ext.getCmp('cmbReA').getValue();
						var regProvv= Ext.getCmp('cmbRPA').getValue();
						var famiglia= Ext.getCmp('cmbFpAA').getValue();
						var classe = Ext.getCmp('cmbClAA').getValue();
						Errors = validateRuleRip(reparto,regProvv,famiglia,classe);
						
						if(Errors=='')
						{
							Ext.getCmp('svBtnAOp').setDisabled(true);
							//scelta caso ed assegnazione di tipoassegnazione
							formReg.getForm().submit({
								url: 'server/gestioneRipartizioni.php', method: 'POST',
								params: {task:"saveRipartizione",idRegRip:regolaRipartizioneID},
								success: function (frm,action) {
									winRegRip.close();
									Ext.MessageBox.alert('Esito', action.result.error);
									if(mainGridStore!=null){
										mainGridStore.reload();
									}
								},
								failure: function (frm,action) {//saveFailure
									Ext.MessageBox.alert('Esito', action.result.error); 
									winRegRip.close();
									if(mainGridStore!=null)
										mainGridStore.reload();
								}
							});		
						}else{
							Ext.MessageBox.alert('Errore nella specifica della regola',Errors);
						}
					}
				}else{
					console.log("no change");
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
							name:'cmbRep',
							id:'cmbReA',
							allowBlank: true,
							hiddenName: 'cmbRep',
							typeAhead: false, 
							editable:false,
							disabled:true,
							hidden:false,
							triggerAction: 'all',
							lazyRender: true,	//should always be true for editor
							store: dsAgenzie,
							displayField: 'TitoloUfficio',
							valueField: 'IdReparto',
							listeners:{
										scope:this,
										select:function(combo, record, index){
											if(record!='')
											{
												
											}
										}
							}
					},{
							xtype: 'combo',
							fieldLabel: 'Regola provvigione',
							name:'cmbRegPro',
							id:'cmbRPA',
							allowBlank: true,
							hiddenName: 'cmbRegPro',
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
												
											}
										}
							}
					},{
							xtype: 'combo',
							fieldLabel: 'Famiglia prodotto',
							name:'cmbFamProd',
							id:'cmbFpAA',
							allowBlank: true,
							hiddenName: 'cmbFamProd',
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
							name:'cmbClass',
							id:'cmbClAA',
							allowBlank: true,
							hiddenName: 'cmbClass',
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
					}]
			}]
		},{
			xtype:'container',
			items:[{
				xtype:'panel', layout:'form', labelWidth:180,columnWidth:.99, defaultType:'textfield',
				defaults: {anchor:'99%', readOnly:false},
				items: [{
						style: 'padding-left:0px; anchor:"0%";',
						xtype: 'checkbox',
						boxLabel: 'Aggiungi interessi di mora',
						id: 'ckMor',
						name:'FlagMora',
						hiddenName: 'FlagMora',
						hidden: false,
						checked: false
				},{fieldLabel:'Percentuale spese di recupero',	name:'PercSpeseIncasso', id:'TxtPsRec', style:'text-align:left', disabled:true,hidden:false,
					listeners:{
						scope:this,
						change:function(field, nt, vt){
							adjustCheckFieldRec(nt,'TxtPsRec');
						}
					},
					allowBlank: false
				},{fieldLabel:'Importo spese di recupero',	name:'ImpSpeseIncasso', id:'TxtIsRec', style:'text-align:left', disabled:true,hidden:false,
					listeners:{
						scope:this,
						change:function(field, nt, vt){
							adjustCheckFieldRec(nt,'TxtIsRec');
						}
					},
					allowBlank: true
				},validityDatesInColumns(90)]
			}]
		}];
				
		//Form su cui montare gli elementi
		var formReg = new Ext.form.FormPanel({
			title:'Dettaglio',		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordRegRip
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
							showRegRip(rec.get('IdRegolaRipartizione'),rec.get('IdRegolaProvvigione'),rec.get('IdReparto'),rec.get('IdClasse'),rec.get('IdFamiglia'),movingGridStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showRegRip(rec.get('IdRegolaRipartizione'),rec.get('IdRegolaProvvigione'),rec.get('IdReparto'),rec.get('IdClasse'),rec.get('IdFamiglia'),movingGridStore,newIndex);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formReg],
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
					'-', helpButton("DettaglioRipartizione")]
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
		
		DCS.DettaglioRegRipartizione.superclass.initComponent.call(this);
		
		//caricamento dello store
		dsAgenzie.load({
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
										dsRegolaRip.load({
											callback : function(r,options,success) 
											{
												if (success && r.length>0) 
												{
													range = dsRegolaRip.getRange();
													var rec = range[0];
													Ext.getCmp('cmbClAA').setValue(rec.data.IdClasse);
													Ext.getCmp('cmbFpAA').setValue(rec.data.IdFamiglia);
													Ext.getCmp('cmbRPA').setValue(rec.data.IdRegolaProvvigione);
													Ext.getCmp('cmbReA').setValue(rec.data.IdReparto);
													Ext.getCmp('TxtPsRec').setValue(rec.data.PercSpeseIncasso);
													Ext.getCmp('TxtIsRec').setValue(rec.data.ImpSpeseIncasso);
													Ext.getCmp('ckMor').setValue(rec.data.FlagMora);
													Ext.getCmp('DataIni').setValue(rec.json.DataIni);
													Ext.getCmp('DataFin').setValue(rec.json.DataFin);

												}
												Ext.getCmp('cmbClAA').setDisabled(false);
												Ext.getCmp('cmbFpAA').setDisabled(false);
												Ext.getCmp('svBtnAOp').setDisabled(false);
												Ext.getCmp('cmbRPA').setDisabled(false);
												Ext.getCmp('cmbReA').setDisabled(false);
												Ext.getCmp('TxtPsRec').setDisabled(false);
												Ext.getCmp('TxtIsRec').setDisabled(false);
												Ext.getCmp('DataIni').setDisabled(false);
												Ext.getCmp('DataFin').setDisabled(false);
												
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

//--------------------------------------------------------------------------------------
//Controlla che almeno uno dei 4 campi riconscitivi sia compilao
//--------------------------------------------------------------------------------------
function validateRuleRip(reparto,regProvv,famiglia,classe)
{
	if(reparto=='' && regProvv=='' && famiglia=='' && classe=='')
	{
		return 'Specificare almeno un campo di definizione della regola.';
	}else{
		return '';
	}
}

// register xtype
Ext.reg('DCS_DettaglioRegRipartizione', DCS.DettaglioRegRipartizione);
//-----------------------------------------------------------------------------------
//Funzione di controllo/aggiustamento dei campi di % ed importo spese di recupero
//-----------------------------------------------------------------------------------
function adjustCheckFieldRec(campo,percOrNot)
{
	var regola='';
	var sub='';
	switch(percOrNot)
	{
		case 'TxtPsRec':
			regola='^(([0-1]{0,1}[0-9][0-9]{0,1})+([.]{1}[0-9]{2})+){1}$';
			sub='0.00';
			break;
		case 'TxtIsRec':
			regola='^([-]*([0-9][0-9]*)+([.]{1}[0-9]{2})+)*$';
			sub='';
			break;
	}
	testCom=campo;
	patt=new RegExp(regola);
	if(!patt.test(testCom))
	{
		Ext.MessageBox.alert('Errore','Il valore immesso non &egrave un numero valido.');
		Ext.getCmp(percOrNot).setValue(sub);
	}else if(percOrNot=='TxtPsRec'){
		if(campo>100)
		{
			Ext.MessageBox.alert('Errore','Il numero immesso non &egrave valido.');
			Ext.getCmp(percOrNot).setValue(sub);
		}
	}
}

//--------------------------------------------------------------------------------------
//Visualizza dettaglio regola ripartizione selezionata
//--------------------------------------------------------------------------------------
function showRegRip(IdRegRip,IdRegP,IdRep,IdCl,IdFam,store,rowIndex) 
{
	IdRegRip=IdRegRip||'';
	IdRegP=IdRegP||'';
	IdRep=IdRep||'';
	IdCl=IdCl||'';
	IdFam=IdFam||'';
	rowIndex=rowIndex||0;
	store=store||null;
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	
	if(IdRegP=='' && IdRep=='' && IdCl=='' && IdFam==''){
		titolo='Creazione di una regola di ripartizione';
	}else{
		titolo='Modifica della regola di ripartizione';
	}	
	
	var nameNW = 'dettaglioRegRip';
	if (oldWind != '') {
		winRegRip = Ext.getCmp(oldWind);
		winRegRip.close();
	}
	oldWind = nameNW;
	winRegRip = new Ext.Window({
		width: 450,
		height: 400,
		minWidth: 450,
		minHeight: 400,
		layout: 'fit',
		id:nameNW,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		tools: [helpTool("DettaglioRipartizione")],
		constrain: true,
		items: [{
			xtype: 'DCS_DettaglioRegRipartizione',
			idRegolaRipartizione: IdRegRip,
			idRegoleProvv: IdRegP,
			listStore: store,
			idReparto:IdRep,
			rowIndex:rowIndex,
			IdClasse:IdCl,
			IdFamiglia:IdFam
		}]
	});
	winRegRip.show();
	winRegRip.on({
		'close' : function () {oldWind = '';}
	});
	myMask.hide();
	
}; // fine funzione 