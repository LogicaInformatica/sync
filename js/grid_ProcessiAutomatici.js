// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridProcessi = Ext.extend(Ext.grid.GridPanel, {
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
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			//showPrAuDetail('','',gstore,'');
			DCS.showPrAuDetail.create('','',IdMain,gstore,'');
			myMask.hide();
	    };
	    
	   var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('Processo');
		    		vectString = vectString + '|' + Arr[k].get('IdEvento');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneProcessiAutomatici.php',
					        method: 'POST',
					        params: {task: 'deleteProcesso',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Il processo selezionato è stato eliminato.');
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
		
		var fields = [{name: 'IdEvento', type: 'int'},
		              {name: 'CodEvento'}, 
			          {name: 'Processo'},
					  {name: 'Stato'},
					  {name: 'OraIni'},
					  {name: 'OraFin'},
					 ];

    	var columns = [selM,
		                {dataIndex:'IdEvento',width:10, header:'IdEv',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'CodEvento',width:40, header:'CodEv',hidden: true, hideable: false, filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'Processo', width:60, header:'Processo', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'Stato', width:40, header:'Stato', hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'OraIni', width:30, header:'Ora inizio', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'OraFin', width:30,	header:'Ora fine', hideable: true,filterable:true,groupable:false,sortable:true},
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcessiAutomatici.php',
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
				    DCS.showPrAuDetail.create(rec.get('IdEvento'),rec.get('Processo'),IdMain,this.store,rowIndex);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNpa',
							pressed: false,
							enableToggle:false,
							text: 'Crea processo',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDpa',
							pressed: false,
							enableToggle:false,
							text: 'Cancella processo',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ProcessiAutomatici"),' '
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

		DCS.GridProcessi.superclass.initComponent.call(this, arguments);
		
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

DCS.GridProcessiAuto = function(){

	return {
		create: function(){
			var gridProcAut = new DCS.GridProcessi({
				titlePanel: 'Lista processi automatici',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readPr"
			});

			return gridProcAut;
		}
	};
	
}();