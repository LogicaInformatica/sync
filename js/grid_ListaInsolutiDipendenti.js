// Crea namespace DCS
Ext.namespace('DCS');
//Insoluti dipendenti

DCS.GridInsDipTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	IdContratto:'',
	isStorico: false,
	
	initComponent : function() { 
		
		var buttAddName='';
		var buttDelName='';
		var IdC=this.IdContratto||'';
		var NomeOp=this.NomeOp||'';
		var IdMain = this.getId();

		var newRecord = function(btn, pressed)
		{};
	    
	    var delRecord = function(btn, pressed)
	    {};
			
		/*buttAddName='Crea regola per utente';
		buttDelName='Elimina regole selezionate';*/
		var fields = [	{name: 'IdInsoluto', type: 'int'},
		              	{name: 'IdContratto', type: 'int'},
		              	{name: 'DataScadenza', type:'date', dateFormat: 'Y-m-d'},
						{name: 'ImpCapitale',type: 'float'},
						{name: 'ImpInteressi',type: 'float'},
						{name: 'ImpInteressiMora',type: 'float'},
						{name: 'ImpCommissioni',type: 'float'},
						{name: 'ImpPagato',type: 'float'},
						{name: 'LastUser'},
						{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];
		
		var columns = [{dataIndex:'IdInsoluto',width:10, header:'IdIns',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
		               {dataIndex:'IdContratto',width:10, header:'IdIns',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
		               {dataIndex:'DataScadenza',	width:70,xtype:'datecolumn',header:'Data scadenza', format:'d/m/y',hidden:false, filterable:true,sortable:true,groupable:false},	
						{dataIndex:'ImpCapitale',	width:60,	header:'Quota capitale', hidden:false,hideable: false,xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'ImpInteressi',	width:60,	header:'Quota interessi', hidden:false,hideable: false,xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'ImpInteressiMora',	width:60,	header:'Interessi di mora', hidden:false,hideable: false,xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'ImpCommissioni',	width:60,	header:'Commissioni', hidden:false,hideable: false,xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'ImpPagato',	width:60,	header:'Pagato', hidden:false,hideable: false,xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'LastUpd',	width:70,xtype:'datecolumn', format:'d/m/y',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
						{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}];
		
		var schema = MYSQL_SCHEMA+(this.isStorico?'_storico':'');
		var sql="SELECT * FROM "+schema+".insolutodipendente where idContratto="+this.IdContratto;
		var gstore = new Ext.data.GroupingStore({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sql,
				group: this.groupOn
			},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			}),
			listeners: {load: DCS.hideMask}
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
			columns: columns
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',/*{xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:false, 
						id: 'bNRA',
						pressed: false,
						enableToggle:false,
						text: buttAddName,
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDRA',
						pressed: false,
						enableToggle:false,
						text: buttDelName,
						handler: delRecord
						},
					'-',*/ {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaInsolutiDipendenti"),' '
				];
		
		var bbarItems = [
					'->', {type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
								this.store.load();
							}, scope: this}
				];
				
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		
		Ext.apply(this, {
	        bbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:bbarItems
	        })		
		});

		DCS.GridInsDipTab.superclass.initComponent.call(this, arguments);
		this.activation();
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

DCS.ListInsDip = function(){

	return {
		create: function(IdCo,isStorico){
			var gridVistaInsDip = new DCS.GridInsDipTab({
				titlePanel: '',
				flex: 1,
				task: "",
				isStorico: isStorico,
				IdContratto:IdCo
			});

			return gridVistaInsDip;
		}
	};
	
}();