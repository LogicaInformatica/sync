// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridAzioni = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,

	initComponent : function() { 
		
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:true});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showAzDetail('','',gstore,'');
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloAzione');
		    		vectString = vectString + '|' + Arr[k].get('IdAzione');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneProcedure.php',
					        method: 'POST',
					        params: {task: 'deleteAzione',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le azioni selezionate sono state eliminate.');
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
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAz',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdAzione', type: 'int'},
							{name: 'IdFunzione', type: 'int'},
							{name: 'IdProcedura', type: 'int'},
							{name: 'CodAzione'},
							//{name: 'CodAzioneLegacy'},
							{name: 'TitoloAzione'},
							{name: 'TitoloFunzione'},
							{name: 'TitoloProcedura'},
							{name: 'TipoFormAzione'},
							{name: 'FlagMultipla'},
							{name: 'FlagSpeciale'},
							{name: 'FlagAllegato'},
							{name: 'FlagAllegatoDesc'},
							{name: 'FormWidth', type: 'int'},
							{name: 'FormHeight', type: 'int'},
							{name: 'GiorniEvasione', type: 'int'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdAzione',width:10, header:'IdA',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdFunzione',width:10, header:'IdF',hidden: true, hideable: false, filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'IdProcedura',	width:10,	header:'IdP',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloAzione',	width:140,	header:'Azione', hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloFunzione',	width:130,	header:'Funzione',hidden: true,hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloProcedura',	width:130,	header:'Procedura', hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodAzione',	width:90,	header:'Codice',filterable:true,sortable:true,groupable:false},
    		        	//{dataIndex:'CodAzioneLegacy',	width:50,	header:'Codice legacy',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'TipoFormAzione',	width:70,	header:'Tipo form',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'FlagMultipla',width:30, exportable:false, renderer:DCS.render.spunta, header:'Multipla',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'FlagSpeciale',width:30, exportable:false, renderer:DCS.render.spunta, header:'Speciale',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
						{dataIndex:'FlagAllegato',width:30, exportable:false, header:'Fallegato',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:true},
						{dataIndex:'FlagAllegatoDesc',	width:70,	header:'Allegato', hideable: true,filterable:true,groupable:false,sortable:true},
						{dataIndex:'FormWidth',	width:50,	header:'Larghezza form',hidden: false, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'FormHeight',	width:50,	header:'Altezza form',hidden: false, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'GiorniEvasione',	width:50,	header:'Giorni evasione',hidden: false, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden: true,filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden: true,filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneGridAzioni.php',
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
					showAzDetail(rec.get('IdAzione'),rec.get('TitoloAzione'),this.store,rowIndex);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNaz',
							pressed: false,
							enableToggle:false,
							text: 'Nuova azione',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDAz',
							pressed: false,
							enableToggle:false,
							text: 'Cancella azione',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Azioni"),' '
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

		DCS.GridAzioni.superclass.initComponent.call(this, arguments);
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
	}
});

DCS.GridGestAzioni = function(){

	return {
		create: function(){
			var subtitle = '<br><span class="subtit">La tabella delle azioni &egrave; in gran parte predefinita; si possono per&ograve; modificare alcune caratteristiche'
				+'<br>delle azioni preesistenti oppure creare nuove azioni, se abbastanza simili ad azioni esistenti da poter utilizzare uno dei "form" standard.'
				+'<br>Si noti che le azioni facenti parte di un workflow sono definibili interamente nella sezione Workflow.</span>';
			var gridGestAz = new DCS.GridAzioni({
				titlePanel: 'Lista delle azioni'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readAz"
			});

			return gridGestAz;
		}
	};
	
}();
