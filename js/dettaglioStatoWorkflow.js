// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var winState;

DCS.recordStato = Ext.data.Record.create([
           {name: 'IdSRec', type: 'int'},
           {name: 'TitoloSRec'},
           {name: 'Abbr'}]);

DCS.DettaglioSTWkf = Ext.extend(Ext.TabPanel, {
	idStato: 0,
	idProcedura: 0,
	listStore:null,
	listUseStore:null,
	rowIndex: -1,
	creation:0,
	link:0,
	uLs:null,
	idAzSon:0,
	hideCMB:false,
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		if(this.idStato == ''){
			this.idStato = 0;
		}
		var statoID=this.idStato;
		if(this.idProcedura == ''){
			this.idProcedura = 0;
		}
		var underLStore = this.uLs;
		var linking = this.link;
		var proceduraID=this.idProcedura;
		var mainGridStore=this.listStore;
		var mainGridUsefulStore=this.listUseStore;
		var IdMain = this.getId();
		var idAzioneSon = this.idAzSon;
		var sonIndex = this.rowIndex;
		var nascondiCmb = this.hideCMB;
		
		//stores: dati da visualizzare
		var dsStatoGenerale = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readStatiProcGrid',idS:this.idStato, fc:this.creation, idProc:this.idProcedura, link:this.link},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordStato)
		});
		
		var dsAzioneCollegata = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT ap.IdAzione as IdAzione, a.titoloazione as TitoloAzione FROM azioneprocedura ap left join statoazione sa on(ap.idazione=sa.idazione) left join azione a on(ap.idazione=a.idazione) where ap.Idprocedura="+proceduraID+" and sa.IdStatoRecuperoSuccessivo is null"
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdAzione'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdAzione'},
				{name: 'TitoloAzione'}]
			),
			autoLoad: true,
			listeners:{
				load : function( store, records,options ){
					if(store.getCount()==0){
						if(nascondiCmb){
							if(this.link==1){
								Ext.getCmp('cmbAzCN').setValue("Non vi sono azioni collegabili.");
								Ext.getCmp('cmbAzCN').setEditable(false);
								Ext.getCmp('svBtn').setDisabled(true);
							}
						}
					}
				}
			}
		});//end dsClasseSucc 
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsStatoGenerale,
			text: 'Salva',
			id:'svBtn',
			handler: function(b,event) {
				if (formStato.getForm().isDirty()) {	// qualche campo modificato
					if (formStato.getForm().isValid()){
						this.setDisabled(true);
						formStato.getForm().submit({
							url: 'server/gestioneProcedure.php', method: 'POST',
							params: {task:"saveStatProc",idp:proceduraID, linking:linking},
							success: function (frm,action) {
								winState.close();
								if(underLStore!=null){
									//underLStore.reload();
//									Ext.getCmp(oldWind).close();
//									showAzioneDetail(idAzioneSon,underLStore,sonIndex,proceduraID);
									Ext.MessageBox.alert('Esito', action.result.messaggio);
								}else{
									Ext.MessageBox.alert('Esito', action.result.messaggio);
									if(mainGridStore!=null){
										mainGridStore.reload();
									}
								}
							},
							failure: function (frm,action) {//saveFailure
								Ext.MessageBox.alert('Esito', action.result.messaggio); 
								winState.close();
								if(underLStore!=null){
//									Ext.getCmp(oldWind).close();
//									showAzioneDetail(idAzioneSon,underLStore,sonIndex,proceduraID);
								}else{
									if(mainGridStore!=null)
										mainGridStore.reload();
								}
							}
						});
					}
				}else{
					console.log("no change");
				}
			},
			scope: this
		});
		
		var itemNewEdit='';
		if(this.link==1){
			itemNewEdit = [
				   {
						xtype: 'combo',
						fieldLabel: 'Nome Stato',
						name:'cmbStatoNome',
						id:'cmbSN',
						allowBlank: false,
						hiddenName: 'cmbStatoNome',
						typeAhead: false, 
						editable:false,
						triggerAction: 'all',
						lazyRender: true,	//should always be true for editor
						store: dsStatoGenerale,
						displayField: 'TitoloSRec',
						valueField: 'IdSRec',
						listeners:{
									scope:this,
									select:function(combo, record, index){
										Ext.getCmp('abbStato').setValue(dsStatoGenerale.getAt(index).get('Abbr'));
									}
						}
					},{fieldLabel:'Abbreviazione',name:'Abbr', readOnly:true, id:'abbStato', style:'text-align:left'},
			        {fieldLabel:'IdStato',	name:'IdSRec', style:'text-align:left',hidden:true},
			        {
						xtype: 'combo',
						fieldLabel: 'Collegato ad',
						name:'cmbAzioneNome',
						id:'cmbAzCN',
						allowBlank: false,
						hiddenName: 'cmbAzioneNome',
						typeAhead: false, 
						editable:false,
						triggerAction: 'all',
						lazyRender: true,	//should always be true for editor
						store: dsAzioneCollegata,
						displayField: 'TitoloAzione',
						valueField: 'IdAzione',
						listeners:{
									scope:this,
									select:function(combo, record, index){
										
									}
						}
					}
			];
		}else{
			itemNewEdit = [{fieldLabel:'Nome Stato',	name:'TitoloSRec', allowBlank: false, style:'text-align:left'},
					        {fieldLabel:'Abbreviazione',name:'Abbr', allowBlank: false, style:'text-align:left'},
					        {fieldLabel:'IdStato',	name:'IdSRec', style:'text-align:left',hidden:true},
					        {
								xtype: 'combo',
								fieldLabel: 'Collegato ad',
								name:'cmbAzioneNome',
								id:'cmbAzCNN',
								allowBlank: true,
								hidden:this.hideCMB,
								hiddenName: 'cmbAzioneNome',
								typeAhead: false, 
								editable:false,
								triggerAction: 'all',
								lazyRender: true,	//should always be true for editor
								store: dsAzioneCollegata,
								displayField: 'TitoloAzione',
								valueField: 'IdAzione',
								listeners:{
											scope:this,
											select:function(combo, record, index){
												
											}
								}
							}];
		}
		
		//Form su cui montare gli elementi
		var formStato = new Ext.form.FormPanel({
			title:'Dettaglio stato',		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordStato
			}),
			items: [{
					xtype:'container',// layout:'column',
					items: [{//colonna sinistra
							xtype:'fieldset', title:'', border: true,columnWidth:.98,
							items:[{
									xtype:'panel', layout:'form', labelWidth:80,/*columnWidth:.98,*/ defaultType:'textfield',
									defaults: {anchor:'97%', readOnly:false},
									items: [itemNewEdit]
							}]//fine ogg primo
					}]
			}],
			buttons:[save,{text: 'Annulla',handler: function () {winState.close()}}]
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
							showStatoDetail(rec.get('IdSRec'),this.listUseStore,newIndex,proceduraID);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showStatoDetail(rec.get('IdSRec'),this.listUseStore,newIndex,proceduraID);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formStato],
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
					'-', helpButton("DettaglioStatoWF")]
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
		
		DCS.DettaglioSTWkf.superclass.initComponent.call(this);
		
		//caricamento dello store
		dsStatoGenerale.load({
			callback : function(r,options,success) {
				if (success && r.length>0) {
					if(!linking){
						formStato.getForm().loadRecord(r[0]);
					}
				}
			},
			scope: this
		});
	}
});

// register xtype
Ext.reg('DCS_dettaglioStatoWork', DCS.DettaglioSTWkf);
//debug1
//DCS.showStatoDetailSec = function(){
//
//	return {
//		create: function(IdSt,store,rowIndex,procAss,link,idAzSon,flagCreation,undergridLinkStore,hideCmb){
//			var gridGestSTWkf = new DCS.DettaglioSTWkf({
//				idStato: IdSt,
//				listStore: store,
//				rowIndex: rowIndex,
//				idProcedura:procAss,
//				creation:flagCreation,
//				link:link,
//				uLs:undergridLinkStore,
//				idAzSon:idAzSon,
//				hideCMB:hideCmb
//			});
//
//			return gridGestSTWkf;
//		}
//	};
//	
//}();
//--------------------------------------------------------
//Visualizza dettaglio Stato creazione/modifica
//--------------------------------------------------------
function showStatoDetail(IdSt,store,rowIndex,procAss,link,isAson,idAzSon,titPrec) {
	
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
	var copyStore = store, copyRow=rowIndex;
	var flagCreation=0;
	var h=230;
	var undergridLinkStore=null;
	var usefulStore=store;
	var hideCmb=false;
	var copyNameAz='';
	if(copyStore.getCount()>0){
		if(isAson)
			copyNameAz=copyStore.getAt(copyRow).get('TitoloAzione')
		else
			titPrec=copyStore.getAt(copyRow).get('TitoloSRec')
	}
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
			usefulStore=null;
		}
		if(isAson)//se è in creazione e non è arrivato dal dettaglio azione
		{	
//			undergridLinkStore=store;
			undergridLinkStore=null;
			usefulStore=null;
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
		tools: [helpTool("DettaglioStatoWF")],
		constrain: true,
		saved:false,
		items: [{
			xtype: 'DCS_dettaglioStatoWork',
			idStato: IdSt,
			listStore: store,
			listUseStore: usefulStore,
			rowIndex: rowIndex,
			idProcedura:procAss,
			creation:flagCreation,
			link:link,
			uLs:undergridLinkStore,
			idAzSon:idAzSon,
			hideCMB:hideCmb
			}]
	});
	winState.show();
	winState.on({
		'close' : function () {
				if(!isAson){
					oldWind = '';
				}else{
					Ext.getCmp(oldWind).close();
					showAzioneDetail(idAzSon,copyStore,copyRow,procAss,copyNameAz);
				}
			}
	});
	myMask.hide();
	
}; // fine funzione 