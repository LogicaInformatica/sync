// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridRipartizioni = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,

	initComponent : function() { 
		
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showRegRip('','','','','',gstore,'');
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		vectString = vectString + '|' + Arr[k].get('IdRegolaRipartizione');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare le ripartizioni selezionate?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneRipartizioni.php',
					        method: 'POST',
					        params: {task: 'delRipartizione',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le ripartizioni selezionate sono state eliminate.');
					                gstore.reload();
					            } else {
					            	if(resp!=''){
						                Ext.MessageBox.alert('Operazione annullata', resp);
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

		var fields = [{name: 'IdRegolaRipartizione', type: 'int'},
		              		{name: 'IdRegolaProvvigione', type: 'int'},
							{name: 'IdReparto', type: 'int'},
							{name: 'IdClasse', type: 'int'},
							{name: 'IdFamiglia', type: 'int'},
							{name: 'Agenzia'},
							{name: 'RegolaProvvigione'},
							{name: 'Famiglia'},
							{name: 'Classe'},
							{name: 'FlagInteressiMora'},
							{name: 'PercSpeseIncasso', type: 'float'},
							{name: 'ImpSpeseIncasso', type: 'float'},
							{name: 'DataIni', type:'date'},
							{name: 'DataFin', type:'date'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdRegolaProvvigione',width:10, header:'IdRR',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdReparto',width:10, header:'IdR',hidden: true, hideable: false, filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'IdClasse',	width:10,	header:'IdC',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'IdFamiglia',	width:10,	header:'IdF',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'RegolaProvvigione',	width:130,	header:'Regola provvigione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Agenzia',	width:100,	header:'Agenzia',hidden: false,hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Famiglia',	width:120,	header:'Famiglia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Classe',	width:90,	header:'Classe', hideable: false,filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'PercSpeseIncasso',	width:80,	header:'Perc. spese recupero', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:false},
    		        	{dataIndex:'ImpSpeseIncasso',	width:80,	header:'Importo spese recupero', xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,sortable:true,exportable:true, hidden:false},
    		        	{dataIndex:'FlagInteressiMora',width:40, exportable:false, renderer:DCS.render.spunta, header:'Interessi mora',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
						{dataIndex:'DataIni',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Valida dal',align:'left', filterable: true, groupable:false, sortable:true},
						{dataIndex:'DataFin',width:40,xtype:'datecolumn', format:'d/m/y',	header:'al',align:'left', filterable: true, groupable:false, sortable:true},
						{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden: true,filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden: true,filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneRipartizioni.php',
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
					showRegRip(rec.get('IdRegolaRipartizione'),rec.get('IdRegolaProvvigione'),rec.get('IdReparto'),rec.get('IdClasse'),rec.get('IdFamiglia'),this.store,rowIndex);
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
							text: 'Crea regola',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDAz',
							pressed: false,
							enableToggle:false,
							text: 'Cancella regola',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("RegoleRipartizione"),' '
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

		DCS.GridRipartizioni.superclass.initComponent.call(this, arguments);
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

DCS.GridRegRipartizioni = function(){

	return {
		create: function(){
		var subtitle = '<br><span class="subtit">Le regole di ripartizione stabiliscono come suddividere gli importi incassati tra capitale, interessi di mora e spese di recupero,'
			+'<br>in base alle caratteristiche delle pratiche e/o alla regola provvigionale applicata.</span>';

			var gridRip = new DCS.GridRipartizioni({
				titlePanel: 'Lista delle regole di ripartizione'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readRipG"
			});

			return gridRip;
		}
	};
	
}();
