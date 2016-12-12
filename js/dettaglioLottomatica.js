/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.PnlLott = Ext.extend(Ext.grid.GridPanel, {
	IdC: '',
	titolo: '',
	IdLottomatica:'',
	gstore: null,
	pagesize: 0,
	btnMenuAzioni: null,
	task: '',
	filters: null,
	pagesize: PAGESIZE,
	
	initComponent : function() {
		var summary = new Ext.ux.grid.GroupSummary();
		var fields, columns;
		
		fields = [
			{name: 'idTransazione'},
			{name: 'CcBeneficiario'},
			{name: 'dataTransazione'},
			{name: 'importo', type: 'float'},
			{name: 'ufficioSportello'},
			{name: 'dataContbAccredito'},
			{name: 'codiceContratto'}
		];
	
		columns = [
		    {dataIndex:'idTransazione',	width:20,	header:'IdT', hidden:true, filterable:false,sortable:true},
		    {dataIndex:'codiceContratto',width:45,	header:'Cod.Pratica',align:'left', filterable: false, sortable:true},
		    {dataIndex:'CcBeneficiario',	width:120,	header:'Conto corrente beneficiario',filterable:true,sortable:true,groupable:true},
		    {dataIndex:'importo',	width:45,	header:'Importo', xtype:'numbercolumn',summaryType:'sum',format:'0.000,00/i',align:'right',filterable:true,sortable:true},
		    {dataIndex:'ufficioSportello',width:45,	header:'Sportello',filterable:true,sortable:true,groupable:true},
		    {dataIndex:'dataTransazione',width:55, header:'Data operazione',align:'left', sizable:false, groupable:true, sortable:true},
		    {dataIndex:'dataContbAccredito',width:55, header:'Data contabile di accredito',align:'left', sizable:false, groupable:true, sortable:true}		    
		];
		

		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/processLottomatica.php',
				method: 'POST'
			}),   
			baseParams:{lottomatica: this.IdLottomatica, task: this.IdC},
			remoteSort: true,
			groupField:'CcBeneficiario',
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});

		this.titolo = 'Ricevute di pagamento nel file di lottomatica: "'+this.IdC+'"';
		
		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			height: 442,
			view: new Ext.grid.GroupingView({
				autoFill: true, //(Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
			}),
			plugins: [summary],
			columns: columns,
			titlePanel: this.titolo,
			listeners: {
				activate: function(pnl) {
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
				scope: this
			}
		});
		
		DCS.PnlLott.superclass.initComponent.call(this, arguments);
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
