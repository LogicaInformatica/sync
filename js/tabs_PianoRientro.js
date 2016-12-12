//Crea namespace DCS
Ext.namespace('DCS');

DCS.PianoRientro = function(idContratto){
	
	  var select = "select rp.IdPianoRientro,NumRata,DataPrevista, Importo,ImpPagato "+
                   "from pianorientro pr,ratapiano rp "+
                   "where pr.IdPianoRientro=rp.IdPianoRientro AND pr.IdContratto="+idContratto;
	  
	  var dsPianoRientro = new Ext.data.Store({
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
			 {name: 'IdPianoRientro'},
			 {name: 'NumRata', type: 'int'},
			 {name: 'DataPrevista', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
			 {name: 'Importo', type:'float'},
			 {name: 'ImpPagato', type:'float'},
			 {name: 'Pagata'}
		    ]),
		    sortInfo:{field: 'NumRata', direction: "ASC"},
			autoLoad: true
	  });
	  
	  /*var checkColumn = new Ext.grid.CheckColumn(
	             {    
				   header: 'Indoor?',    
				   dataIndex: 'Indoor',    
				   id: 'check',    
				   width: 55 
				 });*/

      var gridPianoRientro = new Ext.grid.GridPanel({
			title:'Piano rientro',
			id: 'gridPianoRientro',
			width:500,
			height:330,
         	store: dsPianoRientro,
		 	disableSelection : true,
			border: false,
			viewConfig: {
			  autoFill: true,
			  forceFit: false,
			  getRowClass : function(record, rowIndex, p, store){
			       if(record.get('NumRata')%2) {
			         return 'grid-row-azzurrochiaro';
			       }
				   return 'grid-row-azzurroscuro';
			  }
			},
			// grid columns
			columns:[
			  {
				id: 'idPianoRientro',
				header: "",
			    dataIndex: 'IdPianoRientro',
			    sortable: false,
				hidden: true
			  },
			  {
				id: 'numRata',
				header: "Rata num.",
			    dataIndex: 'NumRata',
			    width: 100,
				align: 'right',
				sortable: true
			  },
			  {
				id: 'dataPagamento',
				header: "Data Pagamento",
			    dataIndex: 'DataPrevista',
			    width: 200,
				align: 'right',
				sortable: true
			  },
			  {
				id: 'importoRata',
				header: "Importo",
			    dataIndex: 'Importo',
			    width: 200,
				align: 'right',
				sortable: true,
				xtype:'numbercolumn',
				format:'0.000,00/i'
			  },
			  {
				id: 'importoPagato',
				header: "Imp. Pagato",
			    dataIndex: 'ImpPagato',
			    width: 200,
				align: 'right',
				sortable: true,
				xtype:'numbercolumn',
				format:'0.000,00/i'
			 }
			]
	 });
	 	 
	 return gridPianoRientro;
};