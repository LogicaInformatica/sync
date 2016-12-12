// Crea namespace DCS
Ext.namespace('DCS');

var winSAz;

DCS.GridAutoProcTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,

	initComponent : function() { 
	
	   var idEvent = this.idEvento;
	   	
	   var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:true});
		
	   this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
	   });
	   
	   var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showAutoProDetail(idEvent,'','',gstore,'');
			myMask.hide();
	    };
	    
	   var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloAutomatismo');
		    		vectString = vectString + '|' + Arr[k].get('IdAutomatismo');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneProcessiAutomatici.php',
					        method: 'POST',
					        params: {task: 'deleteAutomatismoProcesso',vect: vectString, idEv:idEvent},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'L\'automatismo selezionato è stato eliminato.');
					                gstore.reload();
					            } else {
					            	if(resp!=''){
						                Ext.MessageBox.alert('Esito', resp);
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
		
		var fields = [{name: 'IdAutomatismo', type: 'int'},
		              {name: 'TipoAutomatismo'}, 
			          {name: 'TitoloAutomatismo'},
					  {name: 'Comando'},
					  {name: 'Condizione'},
					 ];

    	var columns = [selM,
		                {dataIndex:'IdAutomatismo',width:10, header:'IdAu',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TipoAutomatismo',width:40, header:'Tipo', hideable: false, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloAutomatismo', width:60, header:'Titolo', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'Comando', width:40, header:'Comando', hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Condizione', width:30, header:'Condizione', hideable: false,filterable:true,groupable:false,sortable:true},
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcessiAutomatici.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, idEv: this.idEvento, group: this.groupOn},
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
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					showAutoProDetail(this.idEvento,rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),gstore,rowIndex);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNap',
							pressed: false,
							enableToggle:false,
							text: 'Crea automatismo',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDap',
							pressed: false,
							enableToggle:false,
							text: 'Cancella automatismo',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("AutomatismiProcesso"),' '
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

		DCS.GridAutoProcTab.superclass.initComponent.call(this, arguments);
		
		this.activation();
		//this.store.load();
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);
    },
	activation: function() {
		this.store.setBaseParam('attiva','Y'); 
		var lastOpt = this.store.lastOptions;
		if(!lastOpt || lastOpt.params==undefined) {
		  if(this.pagesize>0) {
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

//----------------------------------------------------------//
// Visualizza dettaglio automatismi per processi automatico //
//----------------------------------------------------------//
    function showAutoProDettaglio(IdEv,oldWinProcDett,titoloE,gridM,listStore,rowIndex)
		{
    	var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
    	myMask.show();
		  var pnl = new DCS.GridAutomatismoProcessi.create(IdEv);
		  winSAz = new Ext.Window({
		     width: 1000, height:500, minWidth: 700, minHeight: 300,
		     autoHeight:false,
		     modal: true,
		     layout: 'fit', plain:true, bodyStyle:'padding:5px;',
		     title: 'Automatismi associati al processo \''+titoloE+'\'',
		     constrain: true,
			 listStore: this.listStore,
			 rowIndex: rowIndex,
			 items: [pnl]
		  });
		   Ext.apply(pnl,{winList:winSAz});
		   winSAz.show();
		   myMask.hide();
		   pnl.activation.call(pnl);
		   winSAz.on({
			 'close' : function () {
			 	 if (Ext.getCmp(oldWinProcDett) != null) {
				 	Ext.getCmp(oldWinProcDett).close();
				 	DCS.showPrAuDetail.create(IdEv, titoloE, gridM, listStore, rowIndex);
				 } else {
				 	  DCS.showPrAuDetail.create(IdEv, titoloE, gridM, listStore, rowIndex);
				   } 	
			 }
		   });
		};

DCS.GridAutomatismoProcessi = function(){

	return {
		create: function(idEv){
			var gridAutoProc = new DCS.GridAutoProcTab({
				//titlePanel: 'Automatismi del processo automatico',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readAP",
				idEvento:idEv
			});

			return gridAutoProc;
		}
	};
	
}();	