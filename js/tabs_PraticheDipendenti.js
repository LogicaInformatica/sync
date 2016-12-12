Ext.namespace('DCS');

DCS.GridPraticheDipendenti = Ext.extend(DCS.GridPratiche, {

	initComponent : function() {
		var locFields = [	{name: 'IdCliente', type:'int'},
						{name: 'IdContratto', type:'int'},
						{name: 'CodAna'},
						{name: 'numPratica'},
						{name: 'Nominativo'},
						{name: 'NumInsoluti',  type: 'int'},
						{name: 'ImpDebito',    type: 'float'},
						{name: 'DataRata', type: 'date'},
						{name: 'GiorniRitardo',    type: 'int'},
						{name: 'FormDettaglio'}, // serve per avere il nome del dettaglio (xtype)
						{name: 'NumNote', type: 'int'}];
			

		var columns = [	{dataIndex:'CodAna',width:45,header:'Codice',align:'left', filterable: true, sortable:true,groupable:false},
			        	{dataIndex:'numPratica',width:45,header:'Posiz.',align:'left', filterable: true, sortable:true,groupable:false},
			        	{dataIndex:'Nominativo',width:90,header:'Nome',filterable:false,sortable:true},
			        	{dataIndex:'NumInsoluti',width:30,header:'Insoluti',align:'right',filterable:false,sortable:true,groupable:true},
			        	{dataIndex:'ImpDebito',width:70,header:'Debito',xtype:'numbercolumn',format:'0.000,00/i',align:'right',filterable:false,sortable:true},
			        	{dataIndex:'DataRata',width:30,xtype:'datecolumn', format:'d/m/y',header:'Scadenza',align:'left', filterable: true, groupable:true, sortable:true},
			        	{dataIndex:'GiorniRitardo',width:30,header:'Gg rit.',align:'right',filterable:false,sortable:true}			        	
			          ];
		

		Ext.apply(this,{
			fields:locFields,
			innerColumns: columns,
			isEmploye:true
		});
		DCS.GridPraticheDipendenti.superclass.initComponent.call(this, arguments);
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

DCS.PraticheDipendenti = function(){

	return {
		create: function(){
			DCS.showMask();
			var grid1 = new DCS.GridPraticheDipendenti({
				stateId: 'PraticheDipendenti',
				id: 'PraticheDipendenti',
				stateful: true,
				titlePanel: 'Lista pratiche TKGI dei dipendenti',
				title: 'Dipendenti',
				task: "dipendenti",
				flex: 1
			});
			
			return grid1;
		}
	};
	
}();

