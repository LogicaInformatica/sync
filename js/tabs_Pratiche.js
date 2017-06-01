// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridPratiche = Ext.extend(Ext.grid.GridPanel, {
	fields: null,
	filters: null,
	summary: null,
	innerColumns: null,
	grpField: undefined,
	grpDir:undefined,
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	btnMenuAzioniNote: null,
	task: '',
	hideStato: false,
	agenzia:'',
	idAgenz:'',
	utenteId:'',
	actionCol: (CONTEXT.AZIONI==true),
	selectCol: true,
	IdProcedura:'',
	IdCategoria:'',
	IdStatoRecupero:'',
	sqlExtraCondition : '',
	SelmTPratiche:null,
	isEmploye:false,
	isSintesi:false,
	isStorico:false,
	isDettaglioProvvigioni:false,
	
	initComponent : function() {
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false});
		this.SelmTPratiche=selM;
		var employ = this.isEmploye||false;
		
		this.btnMenuAzioni = new DCS.Azioni({
			employ: this.isEmploye,
			isStorico: this.isStorico,
			gstore: this.store,
			sm: selM
		});
	
		this.btnMenuAzioniNote = new DCS.AzioniNote({
			idGrid: this.getId(),
			gstore: this.store,
			sm: selM
		});
			
		if (!CONTEXT.AZIONI)
			this.btnMenuAzioni.hidden = true;
				
		var allegatoAcCol={
	            getClass: function(v, meta, rec){ // Or return a class from a function
					if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia')) 
						return 'invisible';
					if (rec.get('NumAllegati') > 0) {
						actionColumn.items[6].tooltip = 'Allegati';
						return 'con_allegati';
					}
					else {
						actionColumn.items[6].tooltip = 'Nessun allegato';
						return 'senza_allegati';
					}
				},
				handler: function(grid, rowIndex, colIndex) 
				{
		            var rec = this.store.getAt(rowIndex);
					var numPratica = rec.get('numPratica')?rec.get('numPratica'):rec.get('CodContratto');
		
				    var win = new Ext.Window({
				    	modal: true,
				        width: 800,
				        height: 450,
				        minWidth: 800,
				        minHeight: 450,
				        layout: 'fit',
				        plain: true,
						constrainHeader: true,
				        title: 'Allegati - Pratica: '+numPratica,
				        items: DCS.Allegato(rec.get('IdContratto'), numPratica, this.isStorico) 
				    });
				    win.show();
				},
				scope: this
		};
		var itemsNotaNonNuova = {
				getClass: function(v, meta, rec) {  // Or return a class from a function
					if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia'))  
						return 'invisible';
		        	var nn =  rec.get('NumNote');
		        	if (nn==0)
		        	{
						actionColumn.items[7].tooltip = 'Nessuna nota';
		            	return '';
		        	}
		        	else if (nn<0) // solo note gia' lette
		        	{
		        		actionColumn.items[7].tooltip = (nn==-1)?'1 nota gi&agrave; letta':((-nn)+' note gi&agrave; lette');
		            	return 'con_note';
		        	}
		        	else
		        	{
						actionColumn.items[7].tooltip = (nn==1)?'1 nuova nota':(nn+' nuove note');
		            	return 'con_note_non_lette';
		         	}
				},
		        handler: function(grid, rowIndex, colIndex) {
		            var rec = this.store.getAt(rowIndex);
		        	//DCS.FormNote.showDetailNote(rec.get('IdContratto'),rec.get('numPratica'),'N',0);
		            DCS.FormVistaNote.showDetailVistaNote(rec.get('IdContratto'),rec.get('numPratica')?rec.get('numPratica'):rec.get('CodContratto'),
		            					'N',0,this.store,this.isStorico);
		        },
				scope: this
	    };
		
		var itemsNotaNuova = {
				getClass: function(v, meta, rec) {  // Or return a class from a function
					if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia'))  
						return 'invisible';
		        	var nn =  rec.get('NumNote');
		        	if (nn==0)
		        	{
						actionColumn.items[8].tooltip = 'Nessuna nota';
		            	return 'senza_note';
		        	}
		        	else if (nn<0) // solo note gi� lette
		        	{
		        		actionColumn.items[8].tooltip = (nn==-1)?'1 nota gi&agrave; letta':((-nn)+' note gi&agrave; lette');
		            	return '';
		        	}
		        	else
		        	{
						actionColumn.items[8].tooltip = (nn==1)?'1 nuova nota':(nn+' nuove note');
		            	return '';
		         	}
				},
		        handler: (this.iStorico?function(){}:this.btnMenuAzioniNote.caricaMenu),
				scope: this.btnMenuAzioniNote
	    };
		var pallinoRinegoziazione = {
	            getClass: function(v, meta, rec){ // Or return a class from a function
					if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia'))  
						return 'invisible';
					switch (rec.json.FlagRinegoziazione)
					{
						case "1": // pratica candidata alla rinegoziazione
							if (rec.json.DataFineAffido && rec.json.CodStatoRecupero!='AFR') // in affido (non a Rineg.)
							{
								var dt = oggi = new Date();
								dt = Date.parseDate(rec.json.DataFineAffido,'Y-m-d');
								var interval = parseInt(CONTEXT.sysparms.RINEG_INTERVAL);
								if (oggi.add(Date.DAY, interval)>=dt) // deve essere visibile alle agenzie
								{
									if (CONTEXT.InternoEsterno=='I')
										actionColumn.items[9].tooltip = 'Candidata a rinegoziazione, visibile all\'agenzia'; // modificato per mail 3/10/13 da Cerrato
									else
										actionColumn.items[9].tooltip = 'Candidata a rinegoziazione'; 
									return 'rineg_blu';
								}
								else if (CONTEXT.InternoEsterno=='I') // non � il momento: lo vede solo l'interno, ma in giallo
								{
									actionColumn.items[9].tooltip = 'Candidata a rinegoziazione o accodamento, non ancora visibile all\'agenzie';
									return 'rineg_giallo';
								}
								else // non deve essere visibile
								{
									return '';
								}
							}
							else if (CONTEXT.InternoEsterno=='I' && !rec.json.DataFineAffido) // non in affido
							{
								actionColumn.items[9].tooltip = 'Candidata a rinegoziazione o accodamento, non in affido';
								return 'rineg_giallo';
							}
							else if (CONTEXT.InternoEsterno=='I'  // lista senza data affido oppure affidata in rinegoziazione
								 ||  rec.json.CodStatoRecupero=='AFR')
							{
								actionColumn.items[9].tooltip = 'Candidata a rinegoziazione'; // modificato per mail 3/10/13 da Cerrato
								return 'rineg_blu';
							}
							else // non deve essere visibile
							{
								return '';
							}
						case "2": 
							actionColumn.items[9].tooltip = 'Proposta di rinegoziazione in corso';
							return 'rineg_grigio';
						case "3": 
							actionColumn.items[9].tooltip = 'Proposta di accodamento in corso';
							return 'rineg_grigio';
						case "4": // proposta rinegoziazione respinta
							actionColumn.items[9].tooltip = 'Proposta di rinegoziazione respinta';
							return 'rineg_rosso';
						case "5": 
							actionColumn.items[9].tooltip = 'Proposta di rinegoziazione accettata';
							return 'rineg_verde';
						case "6": 
							actionColumn.items[9].tooltip = 'Proposta di accodamento accettata';
							return 'rineg_verde';
						case "7": 
							actionColumn.items[9].tooltip = 'Conclusa rinegoziazione nuovo contratto';
							return 'rineg_verde';
						case "8": 
							actionColumn.items[9].tooltip = 'Eseguito accodamento';
							return 'rineg_verde';
						case "9": // proposta accodamento respinta
							actionColumn.items[9].tooltip = 'Proposta di accodamento respinta';
							return 'rineg_rosso';
						default:
							return '';
					}
				},
				scope: this
		};
		
		var vediDettaglioRate = {
            	tooltip: 'Dettaglio rate',
            	handler: showDettaglioRateProvvigione,
	            getClass: function(v, meta, rec)
	            { 
					switch (rec.json.NumModifiche)
					{
						case "0":
							actionColumn.items[11].tooltip = 'dettaglio rate';
							return 'dettaglioRateNoChange';
						case "1":
							actionColumn.items[11].tooltip = 'dettaglio rate: 1 forzatura';
							return 'dettaglioRateChanged';
						default: 
							actionColumn.items[11].tooltip = 'dettaglio rate: '+rec.json.NumModifiche+' forzature';
							return 'dettaglioRateChanged';
					}
	            }
            };
		
		var actionColumn = {
			xtype: 'actioncolumn',
            width: 120,
            header:'Azioni',
            printable:false, sortable:false,  filterable:false, resizable:true,
            items: [{icon   : 'ext/examples/shared/icons/fam/table_refresh.png',
                tooltip: 'Azioni',
                handler: this.btnMenuAzioni.caricaMenu,
                getClass: this.condMakeInvisible,
				scope: this.btnMenuAzioni
            },'-',
            {icon:"images/space.png"},
            {icon: 'images/clock.png',
                tooltip: 'Storia',
                getClass: this.condMakeInvisible,
                handler: function(grid, rowIndex, colIndex) {
                    var rec = this.store.getAt(rowIndex);
					var numPratica = rec.get('numPratica')?rec.get('numPratica'):rec.get('CodContratto');

				    var win = new Ext.Window({
				    	modal: true,
				        width: 800,
				        height: 650,
				        minWidth: 800,
				        minHeight: 650,
				        layout: 'fit',
				        plain: true,
						constrainHeader: true,
				        title: 'Storia Recupero - Pratica: '+numPratica,
				        items: DCS.StoriaRecupero(rec.get('IdContratto'), numPratica, this.isStorico) 
				    });
				    win.show();
				},
				scope: this
            },'-',
            allegatoAcCol,'-',itemsNotaNonNuova,itemsNotaNuova,pallinoRinegoziazione,'-',vediDettaglioRate]
		};
		
		if (employ) // lista impiegati
		{
			actionColumn.items[5]=actionColumn.items[8];
			actionColumn.items[6]=actionColumn.items[9];
			actionColumn.items[7]='';
			actionColumn.items[8]='';
			actionColumn.items[9]='';
			actionColumn.items[10]='';
			actionColumn.items[11]='';
		} else if (this.isStorico) { // lista storico
			actionColumn.items[9]='';
			actionColumn.items[10]='';
			actionColumn.items[11]='';
		} else if (!this.isDettaglioProvvigioni || CONTEXT.InternoEsterno=='E')	{
			actionColumn.items[10]='';
			actionColumn.items[11]=''; // no pulsante dettaglio rate provvigioni
		}
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/praticheCorrenti.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, sqlExtraCondition : this.sqlExtraCondition, agenzia: this.agenzia, idA: this.idAgenz, idUtente:this.utenteId ,Procedura:this.IdProcedura, 
				Categoria:this.IdCategoria, StatoRecupero: this.IdStatoRecupero, anno: this.anno},
			remoteSort: true,
			groupField: this.grpField,
			groupDir: this.grpDir,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: this.fields
			}),
			listeners: { 
				// a fine load, toglie l'eventuale maschera di attesa messa da altri
				load: DCS.hideMask 
			}
  		});

		if (this.selectCol)
			this.innerColumns.splice(0,0,selM);
		if (this.actionCol && !this.isSintesi)
			this.innerColumns.push(actionColumn);
		
		Ext.apply(this,{
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''), //false, //true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true,
	            getRowClass : function(record, rowIndex, p, store){
	                if(rowIndex%2)
	                {
				        return 'grid-row-azzurrochiaro';
	                }
			        return 'grid-row-azzurroscuro';
				}
	        }),
			plugins: (this.summary==null)?((this.filters==null?[]:[this.filters]))
					                     :((this.filters==null?[this.summary]:[this.summary,this.filters]))	,
			columns: this.innerColumns,
			sm: selM,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					if (this.IdC == 'DettaglioExperianInvio' || this.IdC == 'DettaglioExperianCliente') {
						titolo    =  'Lista pratiche di '+rec.get('Nominativo')+ ' (come intestatario o coobbligato)';
						parametri = {IdC: 'PraticheSoggetto', 
									 searchFields: {IdCliente: rec.get('IdCliente')},
									 titolo: 'Lista pratiche di '+rec.get('Nominativo') };
						var pnl = new DCS.pnlSearch(parametri);
						var win = new Ext.Window({
							width: 1100, height:700, 
							autoHeight:true,modal: true,
						    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
						    title: titolo,
							constrain: true,
							items: [pnl]
						});
						win.show();
						pnl.activation.call(pnl);													
					} else if (employ) {
						showGrigliaRate(rec.get('IdContratto'),rec.get('Nominativo'),this.isStorico);
					}else{
						// 7/4/2014: controlla che l'apertura non sia fatta da un utente (di agenzia) non autorizzato a vedere questa pratica
						// (pu� avvenire a partire dalle liste delle provvigioni, in cui i supervisori di agenzia vedono le pratiche anche dopo
						// l'affido)
						if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia'))
							Ext.Msg.alert('','Il dettaglio della pratica non � pi� visibile');
						else
							showPraticaDetail(rec.get('IdContratto'),
								rec.get('numPratica')?rec.get('numPratica'):rec.get('CodContratto'),
								rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.store,rowIndex,this.isStorico);
					}
				},
				activate: this.activation,
				scope: this
			}
	    });

		Ext.applyIf(this, {
			store: gstore
		});
		
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {type: 'button', text: 'Stampa elenco',  icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}, scope:this},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png',  
							handler: function()
							{
								Ext.ux.Printer.exportXLS(this);
							}, scope:this},
	                '-', helpButton("Listapratichecorrenti"),' '
				];
		if (this.actionCol) {	// bottone Azioni solo se presente colonna azioni
			tbarItems.splice(2,0,this.btnMenuAzioni,'-');
		}

		if (this.pagesize > 0 && this.task!='workflow') {
			var comboPagesize = new Ext.form.ComboBox({
				  name : 'perpage',
				  width: 50,
				  store: new Ext.data.ArrayStore({
				    fields: ['id'],
				    data  : [ ['20'],['25'],['50'],['100'],['500'],['1000'] ]
				  }),
				  mode : 'local',
				  value: this.pagesize,				 
				  listWidth     : 40,
				  triggerAction : 'all',
				  displayField  : 'id',
				  valueField    : 'id',
				  editable      : false,
				  forceSelection: true
				});
			
			var bbar = new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
				items: [
				        '-',
				        comboPagesize,
				        'righe per pagina']
			});
			
			comboPagesize.on('select', function(combo, record) {
				  bbar.pageSize = parseInt(record.get('id'), 10);
				  bbar.doLoad(bbar.cursor);
				}, this);
			
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: bbar 
			});
		} else {
			tbarItems.splice(2,0,
				{type:'button', text:'Aggiorna', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridPratiche.superclass.initComponent.call(this, arguments);
		
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);

	},

	activation: function() {
		this.store.setBaseParam('attiva','Y'); 
		var lastOpt = this.store.lastOptions;
		if (!lastOpt || lastOpt.params==undefined) {
			if (this.pagesize>0) {
				this.store.load({
					params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
						start: 0, //pass start/limit parameters for paging
						limit: this.pagesize
					}
				}); 
			} else {
				this.store.load(); 
			}
		}
	},    
	
	// 7/4/2014: Rende invisibili i pulsanti di azione se l' utente (di agenzia) non autorizzato a vedere questa pratica
	// (pu� avvenire a partire dalle liste delle provvigioni, in cui i supervisori di agenzia vedono le pratiche anche dopo
	// l'affido)
	condMakeInvisible: function(v, meta, rec) {
		if (CONTEXT.InternoEsterno=='E' && rec.get('IdAgenzia')>0 && rec.get('IdAgenziaCorrente')!=rec.get('IdAgenzia')) {
			return 'invisible';
		} else {
			return '';
		}
	}
});


