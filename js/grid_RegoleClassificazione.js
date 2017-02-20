// Crea namespace DCS
Ext.namespace('DCS');
//CLASSIFICAZIONI
//var winRCla;

DCS.GridClassificazioneTab = Ext.extend(Ext.grid.GridPanel, {
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
			showClasseDetail('',gstore,0,'');
			myMask.hide();
		};
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	
	    	if(Arr.length>0)
	    	{
		    	for(var k=0;k<Arr.length;k++)
		    	{
	    			confString += '<br />	-'+Arr[k].get('TitoloClasse');
		    		vectString = vectString + '|' + Arr[k].get('IdClasse');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare le seguenti classificazioni? "+confString,function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneClassificazioni.php',
					        method: 'POST',
					        params: {task: 'deleteClassRules',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le classificazioni selezionate sono state eliminate.');
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
				id: 'actionColAss',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdClasse', type: 'int'},
							{name: 'CodClasse'},
							{name: 'TitoloClasse'},
							{name: 'AbbrClasse'},
							{name: 'FlagRec'},
							{name: 'FlagNONAffido'},
							{name: 'DataIni',type:'date'},
							{name: 'DataFin',type:'date'},
							{name: 'Ordine', type: 'int'}];

    	var columns = [selM,
    	               	{dataIndex:'IdClasse',width:10, header:'IdC',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloClasse',	width:130,	header:'Classe', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'AbbrClasse',	width:100,	header:'Abbreviazione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodClasse',	width:80,	header:'Codice', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'FlagRec',width:50, exportable:false, renderer:DCS.render.spunta, header:'A recupero',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'FlagNONAffido',width:50, exportable:false, renderer:DCS.render.spunta, header:'Non da affidare',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'Ordine',width:50, header:'Gravit&agrave',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
						{dataIndex:'DataIni',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Valida dal',align:'left', filterable: true, groupable:false, sortable:true},
						{dataIndex:'DataFin',width:40,xtype:'datecolumn', format:'d/m/y',	header:'al',align:'left', filterable: true, groupable:false, sortable:true}
   		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneClassificazioni.php',
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
					showClasseDetail(rec.get('IdClasse'),gstore,rowIndex,rec.get('TitoloClasse'));
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNcla',
							pressed: false,
							enableToggle:false,
							text: 'Nuova classificazione',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDcla',
							pressed: false,
							enableToggle:false,
							text: 'Cancella classificazione',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Classificazioni"),' '
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

		DCS.GridClassificazioneTab.superclass.initComponent.call(this, arguments);
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

DCS.ClassificazioniRegole = function(){

	return {
		create: function(){
			var subtitle = '<br><span class="subtit">Le classificazioni servono a differenziare le pratiche a recupero ai fini del loro affidamento.</span>';
			var gridMainRule = new DCS.GridClassificazioneTab({
				titlePanel: 'Lista delle classificazioni'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readClassMainGrid"
			});

			return gridMainRule;
		}
	};
	
}();