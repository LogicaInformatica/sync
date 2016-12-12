// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridAutomatismi = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,

	initComponent : function() { 
		
		/**---------------------------	
		Gestione tasto Nuovo Automatismo
		----------------------------*/
		var newRecord = function(btn, pressed)
		{
	   		
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showAutDetail(0,'',gstore,'');
			myMask.hide();
	    };      
		//Fine Gestione Tasto Nuovo Utente
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='',vectStringTit='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloAutomatismo')==null || Arr[k].get('TitoloAutomatismo')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloAutomatismo');
		    		vectString = vectString + '|' + Arr[k].get('IdAutomatismo');
		    		vectStringTit = vectStringTit + '|' + Arr[k].get('TitoloAutomatismo');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneAutomatismi.php',
					        method: 'POST',
					        params: {task: 'deleteA',vect: vectString, vectit: vectStringTit },
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Gli automatismo selezionati sono stati eliminati.');
					                gstore.reload();
					            } else {
					            	if(resp!=''){
						                Ext.MessageBox.alert('Esito', resp);
						                gstore.reload();			            		
					            	}
					            }
							},
							scope: this,
							waitMsg: 'Salvataggio in corso...'
					    });
		    	    }
		    	});
	    	}else{
	    		Ext.MessageBox.alert('Conferma', "Non si è selezionata alcuna voce.");
	    	}
	    };
	    
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAut',
	            width: 30,
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
	                    			Ext.MessageBox.alert('Errore', resp.error); 
	                    		},
								scope: this,
								waitMsg: 'Cancellazione in corso...'
						    });
					},
					scope: this
	            },'-',{
					icon:"images/space.png"
				},*/{
					iconCls: 'in_dettaglio',               
                    tooltip: 'Azioni associate',
					handler: function(grid, rowIndex, colIndex) {
						var rec = grid.getStore().getAt(rowIndex);
						this.showListaAzioni(rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'));
					},
					scope: this
	            }]
			};

		var fields = [{name: 'IdAutomatismo', type: 'int'},
							{name: 'TipoAutomatismo'},
							{name: 'TitoloAutomatismo'},
							{name: 'Comando'},
							{name: 'Condizione'},
							{name: 'Destinatari'},
							{name: 'LastUser'},
							{name: 'IdModello', type: 'int'},
							{name: 'FlagCumulativo'},
							{name: 'FileName'},
							{name: 'TitoloModello'},
							{name: 'lastupd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdAutomatismo',width:10,hidden: true, hideable: false, header:'IdA',filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TipoAutomatismo',width:150, header:'Tipo', filterable:true,groupable:true,sortable:true},
    		        	{dataIndex:'TitoloAutomatismo',	width:140,	header:'Nome', hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Comando',	width:120,	header:'Comando', hideable: true,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'Condizione',	width:120,	header:'Condizione', hideable: true,filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'Destinatari',	width:70,	header:'Destinatari',filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'FileName',	width:70,	header:'Modello',hidden: true, hideable: false,filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'TitoloModello',	width:70,	header:'Modello',filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'FlagCumulativo',	width:10,	header:'Cumulativo',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'lastupd',	width:100,xtype:'datecolumn',header:'Last update',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',filterable:true,sortable:true,groupable:false},
    		        	actionColumn
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAutomatismi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, group: this.groupOn},
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
			autoExpandColumn:3,
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
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					showAutDetail(rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),this.store,rowIndex);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNa',
							pressed: false,
							enableToggle:false,
							text: 'Nuovo Automatismo',
							handler: newRecord
							},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDa',
						pressed: false,
						enableToggle:false,
						text: 'Cancella Automatismo',
						handler: delRecord
						},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Automatismi"),' '
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

		DCS.GridAutomatismi.superclass.initComponent.call(this, arguments);
		this.activation();
		//this.store.load();
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);

	},
	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	showListaAzioni: function(idAut,NomeAut)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var win;
		var pnl = new DCS.pnlAAList({IdAut: idAut, nome_Aut: NomeAut});
		win = new Ext.Window({
    		width: 410, height:150, minWidth: 410, minHeight: 150,
    		autoHeight:true,modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Azioni',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:win});
    	win.show();
		myMask.hide();
		pnl.activation.call(pnl);
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
	}
});

DCS.GridGestAutomatismi = function(){

	return {
		create: function(){
			var gridGestAut = new DCS.GridAutomatismi({
				titlePanel: 'Gestione degli Automatismi',
				//title: 'Utenti presenti',
				groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readA"
			});

			return gridGestAut;
		}
	};
	
}();
