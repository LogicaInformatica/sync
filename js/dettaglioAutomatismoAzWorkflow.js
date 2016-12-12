// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

DCS.recordAutomatismo = Ext.data.Record.create([
		{name: 'IdAutomatismo', type: 'int'},
		{name: 'TipoAutomatismo'},
		{name: 'TitoloAutomatismo'},
		{name: 'Condizione'},
		{name: 'Destinatari'},
		{name: 'LastUser'},
		{name: 'IdModello'},
		{name: 'Cumulativo'}]);

DCS.DettaglioAutAzWkf = Ext.extend(Ext.TabPanel, {
	idAutomatismo: 0,
	idAzione: 0,
	listStore:null,
	rowIndex: -1,
	wid:'',
	AllDisabled:false,
	creatoMod:false,
	titolAzione:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		if(this.idAutomatismo == ''){
			this.idAutomatismo = 0;
		}
		var automatismoID=this.idAutomatismo;
		if(this.idAzione == ''){
			this.idAzione = 0;
		}
		var azioneID=this.idAzione;
		var tConsAzione=this.titolAzione;
		var mainGridStore=this.listStore;
		var rInx=this.rowIndex;
		var IdMain = this.getId();
		var winAzioneDet = this.wid;
		var creatoModello=this.creatoMod;

		//stores: dati da visualizzare,dati per riempire il chkgroup,combo,
		var dsAutomaGenerale = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readAutAzWKF',idaut:this.idAutomatismo},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordAutomatismo)
		});
		
		var dsModelli = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdModello,TitoloModello FROM modello where TipoModello='W' order by TitoloModello asc" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdModello'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdModello'},
				{name: 'TitoloModello'}]
			),
			autoLoad: true
		});//end dsModelli 
		
		var dsTipoAutomatismo = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "select * from v_automatismi_tipi" 
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdTa'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdTa'},
				{name: 'TipoAutomatismo'},
				{name: 'TipoNominativo'}]
			),
			autoLoad: true
		});//end dsTipoAutomatismo 
				
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsAutomaGenerale,
			text: 'Salva',
			handler: function(b,event) {
				if (formAutomatismo.getForm().isDirty()) {	// qualche campo modificato
					if (formAutomatismo.getForm().isValid()){
						this.setDisabled(true);
						//salvataggio/edit azione
						formAutomatismo.getForm().submit({
							url: 'server/gestioneProcedure.php', method: 'POST',
							params: {task:"saveAutoAzProc",idaz:azioneID,idauto:automatismoID},
							success: function (frm,action) {
								Ext.MessageBox.alert('Esito', action.result.messaggio); 
								win.close();
								//if(mainGridStore!=null)
								mainGridStore.reload();
							},
							failure: saveFailure
						});
					}
				}else{
					console.log("no change");
				}
			},
			scope: this
		});
		
		//Form su cui montare gli elementi
		var formAutomatismo = new Ext.form.FormPanel({
			title:'Dettaglio automatismo',		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordAutomatismo
			}),
			items: [{
					xtype:'container',//columnWidth:.60,
					items:[{//oggetto primo
							xtype:'fieldset', title:'Definizione', border: true,
							items:[{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
										defaults: {anchor:'97%', readOnly:false},
										items: [{fieldLabel:'Nome automatismo',	name:'TitoloAutomatismo', style:'text-align:left', allowBlank:false, id:'Taut', disabled:this.AllDisabled},
										        {fieldLabel:'IdAut',	name:'IdAutomatismo', style:'text-align:left',hidden:true}]
									}]
							},{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:80,columnWidth:.65,
										defaults: {anchor:'97%', readOnly:false},
										items: [{xtype: 'combo',
											fieldLabel: 'Tipo Automatismo',
											name:'TipoAutoma',
											id:'cmbTA',
											allowBlank: false,
											disabled:this.AllDisabled,
											hiddenName: 'TipoAutoma',
											typeAhead: false, 
											editable:false,
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsTipoAutomatismo,
											displayField: 'TipoNominativo',
											valueField: 'TipoAutomatismo',
											listeners:{
														scope:this,
														select:function(combo, record, index){
															switch(combo.getValue())
															{
																case "email":	Ext.getCmp('fieldDest').setValue('Approvatori'); 
																	break;
																case "emailComp":	Ext.getCmp('fieldDest').setValue('Destinatari di riferimento');
																	break;
															}
														}
													}
											}]
									}]										
							},{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:80,columnWidth:.65,
										defaults: {anchor:'97%', readOnly:false},
										items: [{xtype: 'combo',
											fieldLabel: 'Modello',
											name:'modAutomatismo',
											id:'cmbMA',
											allowBlank: false,
											hiddenName: 'modAutomatismo',
											typeAhead: false, 
											editable:false,
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsModelli,
											displayField: 'TitoloModello',
											valueField: 'IdModello',
											listeners:{
														scope:this,
														select:function(combo, record, index){
															if(combo.getValue()!='' || combo.getValue()!=null)
															{
																Ext.getCmp('Taut').setDisabled(false);
																Ext.getCmp('cmbTA').setDisabled(false);
																Ext.getCmp('AutCond').setDisabled(false);
																Ext.getCmp('fieldDest').setDisabled(false);
																Ext.getCmp('ckMulAut').setDisabled(false);
															}
														}
											}
										}]
									},{
										xtype:'panel', layout:'form', columnWidth:.35,
										defaults: {anchor:'97%', readOnly:false},
										items: [{
						                	xtype:'button',
						                	boxMinWidth:100,
						                	width:100,
					                    	tooltip:"Crea un modello per l\'automatismo.",
											text:"Crea modello",
											name: 'btnApriCMod', 
										    id: 'btnApriCMod',
										    anchor: '30%',
										    handler: function() {
												DCS.FormMailModel.showDetailMailModel('','','',automatismoID,azioneID,mainGridStore,rInx,winAzioneDet);
								        	},
											scope: this
										}]
				                    }]										
							}]//fine ogg primo
					},{
						xtype:'fieldset', title:'', border: true,
						items:[{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
										defaults: {anchor:'97%', readOnly:false},
										items: [{fieldLabel:'Condizione',	name:'Condizione', id:'AutCond',style:'text-align:left',disabled:this.AllDisabled}]
									}]
							},{
									xtype:'container', layout:'column',
									items:[{
										xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
										defaults: {anchor:'97%', readOnly:true},
										items: [{fieldLabel:'Destinatari',	id:'fieldDest', name:'Destinatari', style:'text-align:left',disabled:this.AllDisabled}]
									}]
							},{
									xtype:'container', layout:'column',
									items:[{
										/*xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95,defaultType:'checkbox',
										defaults: {anchor:'95%', readOnly:false},
										items: [{*/
											/*xtype: 'combo',
											fieldLabel: 'Cumulativo',
											name:'CCumulativo',
											id:'cmbCumul',
											allowBlank: false,
											editable:false,
											hiddenName: 'FlagCumulativo',
											typeAhead: false, 
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsFCumulativo,
											displayField: 'FlagCumulativo',
											valueField: 'FlagCumulativo'*/
											style: 'padding-left:0px; anchor:"0%";',
			           						xtype: 'checkbox',
											boxLabel: 'Una sola mail per tutti i contratti interessati',
											id: 'ckMulAut',
											disabled:this.AllDisabled,
											name:'Cumulativo',
											hiddenName: 'Cumulativo',
											hidden: false,
											checked: false
										//}]
									}]										
							}]
					}]//fine oggetti colonna sinistra
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
							showAutAzioneDetail(rec.get('IdAutomatismo'),this.listStore,newIndex,this.idAzione,false,rec.get('TitoloAutomatismo'));
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showAutAzioneDetail(rec.get('IdAutomatismo'),this.listStore,newIndex,this.idAzione,false,rec.get('TitoloAutomatismo'));
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formAutomatismo],
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
							scope:this}
					,'-', helpButton("DettaglioAutomatismoAzione")]
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
		
		DCS.DettaglioAutAzWkf.superclass.initComponent.call(this);
		
		if(creatoModello)
			Ext.getCmp('btnApriCMod').setDisabled(true);
		
		Ext.getCmp('Taut').setValue("Notifica "+tConsAzione.toLowerCase());
		//caricamento dei 3 store
		dsModelli.load({
			callback : function(r,options,success) {
				dsTipoAutomatismo.load({
					callback : function(r,options,success) {
						dsAutomaGenerale.load({
							callback : function(r,options,success) {
								if (success && r.length>0) {
									formAutomatismo.getForm().loadRecord(r[0]);
								}
								//c'è un solo record il forEach lo estrae
								range = dsAutomaGenerale.getRange();
								for (i=0; i<range.length; i++)
								{
									var rec = range[i];
									switch(rec.data.Destinatari)
									{
										case '*APPROVER': Ext.getCmp('fieldDest').setValue('Approvatori');
											break;
										case '*DESTINATARIRIF': Ext.getCmp('fieldDest').setValue('Destinatari di riferimento');
											break;
										case '*AUTHOR': Ext.getCmp('fieldDest').setValue('Autori');
											break;
									}
									Ext.getCmp('cmbMA').setValue(rec.data.IdModello);
									Ext.getCmp('cmbTA').setValue(rec.data.TipoAutomatismo);
									console.log("there");	
								}
								console.log("here");
							},
							scope: this
						});
					}
				});
			}
		});
	}
});

// register xtype
Ext.reg('DCS_dettaglioAutAzWork', DCS.DettaglioAutAzWkf);

//--------------------------------------------------------
//Visualizza dettaglio Automatismo creazione/modifica
//--------------------------------------------------------
function showAutAzioneDetail(IdAut,store,rowIndex,azAss,IsCreated,titoloAzz) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	IsCreated=IsCreated||false;
	var allDisable=false;
	IdAut=IdAut||'';
	if(IdAut==''){
		titolo='Creazione di un automatismo';
		//store=null;
		allDisable=true;
		rowIndex=0;
	}else{		
		titolo="Modifica dell\'automatismo '"+titoloAzz+"'";
	}	
	
	var nameNW = 'dettaglio'+IdAut;
	
	if (oldWind != '') {
		win = Ext.getCmp(oldWind);
		win.close();
	}
	oldWind = nameNW;
	
	win = new Ext.Window({
		width: 600,
		height: 400,
		minWidth: 600,
		minHeight: 400,
		layout: 'fit',
		id:'dettaglio'+IdAut,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAutAzWork',
			idAutomatismo: IdAut,
			listStore: store,
			rowIndex: rowIndex,
			idAzione:azAss,
			AllDisabled:allDisable,
			creatoMod:IsCreated,
			titolAzione:titoloAzz,
			wid:'dettaglio'+IdAut
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