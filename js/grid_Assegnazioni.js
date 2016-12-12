// Crea namespace DCS
Ext.namespace('DCS');
//ASSEGNAZIONI
var winSAz;

DCS.GridAssegnazioneTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	
	initComponent : function() { 
		var IdMain = this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    };
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAss',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: [/*{
	            	icon   : 'images/delete.gif',               
                    tooltip: 'Cancella automatismo',
	                handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var IdAutomatismo = rec.get('IdAutomatismo');
							//si sta cancellando la selezione: ok
							Ext.Ajax.request({
						        url: 'server/gestioneAutomatismi.php',
						        method: 'POST',
						        params: {task: 'deleteA',id: IdAutomatismo},
						        success: function(obj) {
						        	eval('var resp = '+obj.responseText);
						        	Ext.MessageBox.alert('Esito', resp.error);
						        	grid.getStore().reload();
								},
								failure: function (obj) {
									eval('var resp = '+obj.responseText);
	                    			Ext.MessageBox.alert('Failed', resp.error); 
	                    		},
								scope: this,
								waitMsg: 'Cancellazione in corso...'
						    });
					},
					scope: this
	            },'-',{
					icon:"images/space.png"
				},{
					iconCls: 'in_dettaglio',               
                    tooltip: 'Azioni associate',
					handler: function(grid, rowIndex, colIndex) {
	                    /*var rec = this.store.getAt(rowIndex);
						var utente = rec.get('NomeUtente');
						var mail = rec.get('Email');
						if (mail == ''){mail='Mail assente'}
						showMailForm(mail, utente);//
					},
					scope: this
	            }/*,'-',
	            {
					iconCls: 'invioSms',               
                    tooltip: 'Invia Sms',
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var numero = rec.get('Cellulare');
						if (numero == ''){numero='Numero di cellulare assente'}
						showSmsForm(rec.get('NomeUtente'), numero);
					},
					scope: this
	            },'-',
	            {
	            	//iconCls: 'impersonaUser',               
                    tooltip: 'Impersona',
                    getClass: function(v,meta,rec) {
					 	if (CONTEXT.IMPERSONA) {
					 		return 'impersonaUser';
                        } else {
                        	return '';
                        }
					},
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var utente = rec.get('NomeUtente');
						var idU = rec.get('IdUtente');
						var userid = rec.get('Userid');
						Ext.Ajax.request({
							url : 'server/AjaxRequest.php' , 
							params : {task: 'read',sql: "SELECT DISTINCT count(*)as presente FROM profiloutente pu, profilo p, profilofunzione pf, funzione f WHERE pu.idUtente="+idU+" and f.codfunzione='IMPERSONA' AND pu.idProfilo=p.idProfilo AND CURDATE() BETWEEN p.DataIni AND p.DataFin AND CURDATE() BETWEEN pf.DataIni AND pf.DataFin AND CURDATE() BETWEEN pu.DataIni AND pu.DataFin AND p.idProfilo=pf.idProfilo AND pf.idFunzione=f.idFunzione"},
							method: 'POST',
							autoload:true,
							success: function ( result, request ) {
								var jsonData = Ext.util.JSON.decode(result.responseText);
								var slave=jsonData.results[0] ['presente'];
								if(slave==0){
									showAnswForm(userid, utente);
								}else{
									Ext.MessageBox.alert('Non consentito', "Questo utente puo\' impersonare a sua volta.");
								}								
							},
							failure: function ( result, request) { 
								Ext.MessageBox.alert('Failed', result.responseText); 
							},
							scope:this
						});
					},
					scope: this
	            }*/]
			};

		var fields = [{name: 'IdUtente', type: 'int'},
							{name: 'NomeUtente'},
							{name: 'numRegole', type: 'int'}];

    	var columns = [selM,
    	               	{dataIndex:'IdUtente',width:10, header:'IdU',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'NomeUtente',	width:130,	header:'Utente', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'numRegole',width:50, header:'Regole associate',align:'left',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});
		
		Ext.apply(this,{
			store: gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: true,
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
			columns: columns,
			sm: selM,
			listeners: {
				celldblclick : function(grid,rowIndex,columnIndex,event){
					var rec = this.store.getAt(rowIndex);
					Ext.getCmp(IdMain).showGrigliaRegoleOperatore(rec.get('IdUtente'),rec.get('NomeUtente'),gstore);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', /*{xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNpr',
							pressed: false,
							enableToggle:false,
							text: 'Nuova agenzia',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDpr',
							pressed: false,
							enableToggle:false,
							text: 'Cancella agenzia',
							handler: delRecord
							},
	                '-',*/ {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("RegoleAssegnazione"),' '
				];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridAssegnazioneTab.superclass.initComponent.call(this, arguments);
		this.activation();
		//this.store.load();
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
	//--------------------------------------------------------------------
    // Visualizza griglia con le regole assegnate all'operatore ricercato
    //--------------------------------------------------------------------
	showGrigliaRegoleOperatore: function(IdOp,NomeOp,Gstore)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.AssOperatore.create(IdOp,NomeOp);
		
		winSAss = new Ext.Window({
    		width: 1000, height:500, minWidth: 700, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: "Regole di assegnazione all\'operatore \'"+NomeOp+"\'",
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winSAss});
		winSAss.show();
		myMask.hide();
		pnl.activation.call(pnl);
		winSAss.on({
			'close' : function () {
					/*if(oldWinProcDett!=null)
					{	
						//Ext.getCmp(oldWinProcDett).close();
						//DCS.showProcedureDetail.create(IdPr,titoloP,gridM);
					}*/
					Gstore.reload();
				}
		});
    }
});

DCS.AssegnazioniRegole = function(){

	return {
		create: function(){
			var subtitle = '<br><span class="subtitle">'
				+'Le regole di assegnazione definiscono quali pratiche sono assegnate automaticamente a ciascun operatore interno all\'Ente creditore'
				+'<br>in base alle loro caratteristiche o al fatto che sono affidate a determinate agenzie.</span>';
			var gridMainAss = new DCS.GridAssegnazioneTab({
				titlePanel: 'Lista delle regole di assegnazione agli operatori interni'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readAssocOpIntGrid"
			});

			return gridMainAss;
		}
	};
	
}();