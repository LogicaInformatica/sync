// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win,storeOr;

DCS.recordAut = Ext.data.Record.create([
		{name: 'IdAutomatismo', type: 'int'},
		{name: 'TipoAutomatismo'},
		{name: 'TitoloAutomatismo'},
		{name: 'Comando'},
		{name: 'Condizione'},
		{name: 'Destinatari'},
		{name: 'LastUser'},
		{name: 'IdModello', type: 'int'},
		{name: 'FlagCumulativo',convert: bool_db},
		{name: 'FileName'},
		{name: 'IdModello'},
		{name: 'TitoloModello'},
		{name: 'lastupd', type:'date'}]);

DCS.DettaglioAutomatismo = Ext.extend(Ext.TabPanel, {
	idAut: 0,
	listStore: null,
	listStore4New: null,
	rowIndex: -1,
	nome:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idUtente in caso di nuovo utente 
		if(this.idAut == ''){
			this.idAut = 0;
		}
		//stores: dati da visualizzare,dati per riempire il chkgroup,combo,
		var dsAutomatismo = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordAut)
		});
				
		var dsModelloAut = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "SELECT IdModello,TitoloModello FROM modello" 
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
		});//end dsModelloAut 
		
		var dsFCumulativo = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "select distinct FlagCumulativo from automatismo" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'FlagCumulativo'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'FlagCumulativo'}]
			),
			autoLoad: true
		});//end dsFCumulativo 
		
		var dsTipoMod = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "select distinct TipoAutomatismo from automatismo" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'TipoAutomatismo'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'TipoAutomatismo'}]
			),
			autoLoad: true
		});//end dsFCumulativo 
		
		storeOr= this.listStore4New!=null?this.listStore4New:this.listStore;
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsAutomatismo,
			text: 'Salva',
			handler: function(b,event) {
				if (formPAut.getForm().isDirty()) {	// qualche campo modificato
					formPAut.getForm().submit({
						url: 'server/gestioneAutomatismi.php',
				        method: 'POST',
				        params: {task: 'saveA',idAut: this.idAut},
				        success: function(frm, action) {
				        	//eval('var resp = '+obj.responseText);
				        	if(action.result.success){
				        		Ext.MessageBox.alert('Esito', "Automatismo salvato");
				        	}else{
				        		Ext.MessageBox.alert('Fallito', "Impossibile salvare l'automatismo: "+action.result.error);
				        	}
				        	if(win.getComponent(0).idAut==0){
				        		win.close();
				        	}
				        	storeOr.reload();
						},
						failure: function(frm, action){
							if(action.result==undefined)
							{
								Ext.Msg.alert('Errore', "Non sono stati scelti tutti i valori minimi necessari alla definizione dell'automatismo.");
							}else{
								Ext.Msg.alert('Errore', action.result.error);
							}
							//.MessageBox.alert('Esito', "Utente non salvato");
						},
						waitMsg: 'Salvataggio in corso...'
					});
				}else{
					console.log("no change");
				}
			},
			scope: this
		});
		
		//Form su cui montare gli elementi
		var formPAut = new Ext.form.FormPanel({
			title:'Dati Automatismo',		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordAut
			}),
			items: [{
					xtype:'container', layout:'column',
					items: [{//colonna sinistra
							xtype:'container',columnWidth:.46,
							items:[{//oggetto primo
											xtype:'container', layout:'column',
											items:[{
												xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
												defaults: {anchor:'97%', readOnly:false,allowBlank: false},
												items: [{fieldLabel:'Nome', id:'NAut', name:'TitoloAutomatismo', style:'text-align:left'}]
											}]
									},{
											xtype:'container', layout:'column',
											items:[{
													xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{fieldLabel:'Comando', id:'Comand', name:'Comando', style:'text-align:left'}]
											}]										
									},{
											xtype:'container', layout:'column',
											items:[{
													xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{fieldLabel:'Destinatari', id:'Dest',name:'Destinatari', style:'text-align:left'}]
											}]										
							}]//fine ogg primo
					//fine oggetti colonna sinistra
					},{		//colonna destra
							xtype:'container',columnWidth:.54,
							items:[{//oggetto primo
									xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95,defaultType:'combo',
									defaults: {anchor:'97%', readOnly:false},
									items: [{
											xtype: 'combo',
											fieldLabel: 'Modello',
											name:'CModello',
											id:'cmbMod',
											allowBlank: true,
											hiddenName: 'IdModello',
											typeAhead: false, 
											editable:true,
											triggerAction: 'all',
											lazyRender: true,	//should always be true for editor
											store: dsModelloAut,
											displayField: 'TitoloModello',
											valueField: 'IdModello',
											listeners:{
														scope:this,
														select:function(combo, record, index){
															Ext.Ajax.request({
																url: 'server/AjaxRequest.php', 
								                        		params : {	task: 'read',
																			sql: "SELECT IdModello,FileName FROM modello where IdModello ="+combo.value
																		},
																method: 'POST',
																reader:  new Ext.data.JsonReader(
									                    					{
									                    						root: 'results',//name of the property that is container for an Array of row objects
									                    						id: 'IdModello'//the property within each row object that provides an ID for the record (optional)
									                    					},
									                    					[{name: 'IdModello'},
									                    					{name: 'FileName'}]
									                    				),
								                    			success: function ( result, request ) {
																	eval('var resp = ('+result.responseText+').results[0]');
																	Ext.getCmp('Fn').setValue(resp.FileName);
																},
								                        		failure: function ( result, request) { 
								                        			Ext.MessageBox.alert('Errore', result.responseText); 
								                        		},
								                        		autoLoad: true
								                        	});
														}
												}
											},{
												xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95,defaultType:'textfield',
												defaults: {anchor:'97%', readOnly:true},
												items: [{fieldLabel:'File', id:'Fn', name:'FileName', hidden: true, style:'text-align:left'}]
											}]
									},{
												xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
												defaults: {anchor:'97%', readOnly:false},
												items: [{fieldLabel:'Condizione', id:'cond',name:'Condizione', style:'text-align:left'}]
									},{
											xtype:'container', layout:'column',columnWidth:.95,
											items: [{
												xtype:'container',columnWidth:.17,
												items:[{
													xtype:'panel', layout:'form', labelWidth:60,columnWidth:.90,defaultType:'label',
													defaults: {anchor:'95%', readOnly:false},
													items: [{
														xtype:'label', 	
														text:'Cumulativo:',	
														width:60
													}]
												}]
											},{
												xtype:'container',columnWidth:.18,
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
														//boxLabel: 'Cum.',
														//id:'cmbFmul',
														name:'FlagCumulativo',
														hiddenName: 'FlagCumulativo',
														hidden: false,
														checked: false
													//}]
												}]
											},{
												xtype:'container',columnWidth:.65,
												items:[{
													xtype:'panel', layout:'form', labelWidth:30,columnWidth:.95, defaultType:'combo',
													defaults: {anchor:'97%', readOnly:false},
													items: [{
														xtype: 'combo',
														fieldLabel: 'Tipo',
														name:'CTipo',
														id:'cmbTipo',
														allowBlank: false,
														editable:false,
														hiddenName: 'TipoAutomatismo',
														typeAhead: false, 
														triggerAction: 'all',
														lazyRender: true,	//should always be true for editor
														store: dsTipoMod,
														displayField: 'TipoAutomatismo',
														valueField: 'TipoAutomatismo',
														listeners:{
															select : function(combo,record,index){
																switch(record.get('TipoAutomatismo'))
																{
																	case 'SMSD':
																		Ext.getCmp('cmbMod').allowBlank=false;
																		Ext.getCmp('cmbMod').setEditable(false);
																		break;
																	case 'lettera':
																		Ext.getCmp('cmbMod').allowBlank=false;
																		Ext.getCmp('cmbMod').setEditable(false);
																		break;
																	case 'email':
																		Ext.getCmp('cmbMod').allowBlank=false;
																		Ext.getCmp('cmbMod').setEditable(false);
																		break;
																	case 'emailComp':
																		Ext.getCmp('cmbMod').allowBlank=false;
																		Ext.getCmp('cmbMod').setEditable(false);
																		break;
																	default:
																		Ext.getCmp('cmbMod').allowBlank=true;
																		Ext.getCmp('cmbMod').setEditable(true);
																		break;
																}
															} 
														}
													}]
												}]
											}]																							
									}]
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
							showAutDetail(rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),this.listStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showAutDetail(rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),this.listStore,newIndex);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formPAut],
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
					'-', helpButton("DettaglioAutomatismo")]
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
		
		DCS.DettaglioAutomatismo.superclass.initComponent.call(this);
		
		//caricamento dei 4 store
		dsTipoMod.load({
				callback : function(r,options,success) {
					var idaut=this.idAut;
					dsAutomatismo.load({
						params:{
							sql: 'select a.*,m.IdModello,m.FileName,m.TitoloModello from automatismo a left join modello m on(a.IdModello=m.IdModello) where a.IdAutomatismo='+idaut+'' 
						},
						callback : function(r,options,success) {
							if (success && r.length>0) {
								formPAut.getForm().loadRecord(r[0]);
							}
							//c'è un solo record il forEach lo estrae
							range = dsAutomatismo.getRange();
							for (i=0; i<range.length; i++)
							{
								var rec = range[i];
								//Ext.getCmp('NAut').setValue(rec.data.TitoloAutomatismo);
								//Ext.getCmp('Comand').setValue(rec.data.Comando);
								//Ext.getCmp('Dest').setValue(rec.data.Destinatari);
								Ext.getCmp('cmbMod').setValue(rec.data.TitoloModello); 
								//Ext.getCmp('Fn').setValue(rec.data.FileName);
								//Ext.getCmp('cond').setValue(rec.data.Condizione);
								//Ext.getCmp('cmbCumul').setValue(rec.data.FlagCumulativo);
								//Ext.getCmp('cmbTipo').setValue(rec.data.TipoAutomatismo);
								switch(rec.data.TipoAutomatismo)
								{
									case 'SMSD':
										Ext.getCmp('cmbMod').allowBlank=false;
										Ext.getCmp('cmbMod').setEditable(false);
										break;
									case 'lettera':
										Ext.getCmp('cmbMod').allowBlank=false;
										Ext.getCmp('cmbMod').setEditable(false);
										break;
									case 'email':
										Ext.getCmp('cmbMod').allowBlank=false;
										Ext.getCmp('cmbMod').setEditable(false);
										break;
									case 'emailComp':
										Ext.getCmp('cmbMod').allowBlank=false;
										Ext.getCmp('cmbMod').setEditable(false);
										break;
									default:
										Ext.getCmp('cmbMod').allowBlank=true;
										Ext.getCmp('cmbMod').setEditable(true);
										break;
								}
							}
						},
						scope: this
					});
				},
				scope: this
		});
		dsModelloAut.load();
		dsFCumulativo.load();
		//dsTipoMod.load();
	}	
});

// register xtype
Ext.reg('DCS_dettaglioAutomatismo', DCS.DettaglioAutomatismo);

//--------------------------------------------------------
//Visualizza dettaglio automatismo
//--------------------------------------------------------
function showAutDetail(idAut,nome,listStore,rowIndex) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	var storetopass = idAut==0?null:listStore;
	var storetopassNew = idAut!=0?null:listStore;
	if(nome==''){nome='Creazione nuovo automatismo';listStore=null;rowIndex=-1;}
	var winTitle = 'Dettaglio automatismo - ' + nome +'';

	var nameNW = 'dettaglio'+idAut;
	if (oldWind != '') {
		win = Ext.getCmp(oldWind);
		win.close();
	}
	oldWind = nameNW;
	win = new Ext.Window({
		width: 800,
		height: 240,
		minWidth: 800,
		minHeight: 240,
		layout: 'fit',
		id:'dettaglio'+idAut,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAutomatismo',
			idAut: idAut,
			nome:nome,
			listStore: storetopass,
			listStore4New: storetopassNew,
			rowIndex: rowIndex
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
	
}; // fine funzione showAutDetail