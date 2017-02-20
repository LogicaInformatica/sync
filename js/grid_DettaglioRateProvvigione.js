// Lista delle rate considerate in una determinata provvigione
Ext.namespace('DCS');

DCS.pnlDettaglioRateProvvigione = function (IdProvvigione,IdContratto)
{
	  var select = "SELECT * FROM v_dettaglio_rate_provvigione WHERE IdProvvigione="
		  +IdProvvigione+ " AND IdContratto=" + IdContratto;
	  
	  var dataStore = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
			  //where to retrieve data
			  url: 'server/AjaxRequest.php',
			  method: 'POST'
			}),   
			baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader({
               root: 'results'	//name of the property that is container for an Array of row objects
			},
			[
			 {name: 'IdProvvigione', type: 'int'},
			 {name: 'IdContratto', type: 'int'},
			 {name: 'NumRata', type: 'int'},
			 {name: 'ImpCapitaleAffidato', type: 'float'},
			 {name: 'ImpTotaleAffidato', type: 'float'},
			 {name: 'ImpPagato', type: 'float'},
			 {name: 'ImpPagatoTotale', type: 'float'},
			 {name: 'FlagRataViaggiante'},
			 {name: 'TipoAnomalia'},
			 {name: 'CodContratto'},
			 {name: 'Lotto'}
			]),
		    sortInfo:{field: 'NumRata', direction: "ASC"},
			autoLoad: true,
			listeners: {load: DCS.hideMask}
	  });
	  
      var gridRate = new Ext.grid.GridPanel({
			title:'Rate considerate nel calcolo provvigioni '+' (doppio click sulla riga per entrare nella funzione di modifica manuale)',
			width:830,
			height:250,
         	store: dataStore,
			border: false,
			viewConfig: {
			  autoFill: true,
			  forceFit: false,
			  getRowClass : function(record, rowIndex, p, store)
			  {
			  	return (rowIndex%2)?'grid-row-azzurrochiaro':'grid-row-azzurroscuro';
			  }
			},
			// grid columns
			columns:[{dataIndex: 'IdProvvigione',hidden: true, hideable: false},
			         {dataIndex: 'IdContratto',hidden: true, hideable: false},
			         {dataIndex: 'NumRata',header:"Num. Rata",width:70,align:'right'},
 		        	 {dataIndex: 'FlagRataViaggiante',width:70, renderer:DCS.render.spunta, header:'Viaggiante',align:'center', sizable:false, menuDisabled:true},
			         {dataIndex:'ImpCapitaleAffidato', width:70,	header:'Cap. aff.',align:'right',filterable:false,sortable:true,
		        			xtype:'numbercolumn',format:'0.000,00/i'},
					 {dataIndex:'ImpTotaleAffidato', width:70,	header:'Tot. aff.',align:'right',filterable:false,sortable:true,
			        			xtype:'numbercolumn',format:'0.000,00/i'},
			         {dataIndex:'ImpPagato', width:70,	header:'Incasso (IPR)',align:'right',filterable:false,sortable:true,
			        			xtype:'numbercolumn',format:'0.000,00/i'},
					 {dataIndex:'ImpPagatoTotale', width:100, header:'Incasso tot.(con viag.)',align:'right',filterable:false,sortable:true,
			        			xtype:'numbercolumn',format:'0.000,00/i'},
			         {dataIndex: 'TipoAnomalia',header:"Nota",width:140}
			],
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = grid.getStore().getAt(rowIndex);
				    DCS.showModificaProvvigione(dataStore,rec.get('IdProvvigione'), rec.get('IdContratto'), rec.get('NumRata'), rec.get('CodContratto'), rec.get('Lotto'));
				},
				scope: this
			}
	 });	  
	return gridRate;
};