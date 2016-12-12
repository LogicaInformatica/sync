 try 
 {
	 initThis();
 } 
 catch (e) 
 {
	 alert("grigliaScadenze: "+e);
 }

function initThis()
{
 
  // DATI DI PROVA
        var scadData = [
        ['2011-01-15','Rientro pratica', '1220010','12.670,00','R03','CL','Toyota FS','ROSSETTI',''],
        ['2011-01-151','Rientro pratica', '1222012','880,00','INS','1C','Toyota FS','ROSSETTI',''],
        ['2011-01-15','Rientro pratica', '1220115','352,00','INS','1C','Toyota FS','SEBASTIANI',''],
        ['2011-01-25','Verifica azione legale', '1220010','2.670,00','CL','LEG','Toyota FS','AVV. LEONI',''],
        ['2011-01-31','Consuntivazione gennaio CL','','','','','Toyota FS','',''],
        ['2011-02-03','Assegnazione pratiche TKGI Febbraio','','','','','Toyota KG','','']
        ];
    //  grouping store
    var scadReader = new Ext.data.ArrayReader({fields:  ['data','evento','num','importo','classe','reparto',
                                                         'committente','operatore','nota']});
    var  scadStore = new Ext.data.GroupingStore({
        autoDestroy: true,
        reader: scadReader,
        data: scadData,
        groupField: 'data'
    });
    var  scadStore2 = new Ext.data.GroupingStore({
        autoDestroy: true,
        reader: scadReader,
        data: scadData,
        groupField: 'committente'
    });
    var  scadStore3 = new Ext.data.GroupingStore({
        autoDestroy: true,
        reader: scadReader,
        data: scadData,
        groupField: 'reparto'
    });
    // filtri
    var filtersScad = new Ext.ux.grid.GridFilters({
        // encode and local configuration options defined previously for easier reuse
        encode: true, // json encode the filter query
        local: true,   // defaults to false (remote filtering)
        filters: [{
            type: 'list',  options: ['INS','B14','B31','R01','R02','R03','R04'],
            dataIndex: 'classe'
        },
        {
            type: 'list',  options: ['Toyota FS','Toyota KG'],
            dataIndex: 'committente'
        },{
            type: 'string',
            dataIndex: 'evento'
        },
        {
            type: 'list',  options: ['CL','C5', '1C', 'LR', 'P4'],
            dataIndex: 'reparto'
        }]
    });
 
    gridScad1 =   {
    		xtype: 'grid',
    		title: 'Lista scadenze Gennaio 2011',
    		store: scadStore,
    		autoHeight: true,
    		border: false,
    		view: new Ext.grid.GroupingView(),
    		plugins: [filtersScad],
    		//width: 690,
    		columns: [
{dataIndex:'data',width:70,header:'Data',filterable:false,sortable:true,groupable:true},
{dataIndex:'evento',width:150,header:'Evento',filterable:true,sortable:true},
{dataIndex:'num',align:'right',width:70,header:'Num. pratica',filterable:true,sortable:true},
{dataIndex:'importo',align:'right',width:60,header:'Importo',filterable:true,sortable:true},
{dataIndex:'classe',width:60,align:'center',header:'Classific.',filterable:true,sortable:true,groupable:true},
{dataIndex:'reparto',width:50,header:'Reparto',filterable:true,sortable:true,groupable:true,groupable:true},
{dataIndex:'committente',width:70,header:'Committente',filterable:true,sortable:true,groupable:true},
{dataIndex:'operatore',width:70,header:'Operatore',filterable:true,sortable:true},
{dataIndex:'nota',width:120,header:'Nota'},
{xtype: 'actioncolumn',
	width: 810,
	header:'Azioni',
	sortable:false,  filterable:false,
	items: [
{icon   : 'images/stampa.gif',
	tooltip: 'Stampa',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore.getAt(rowIndex);
	alert("Stampa pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/application_go.png',
	tooltip: 'Invia',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore.getAt(rowIndex);
	alert("Invio messaggio per pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/user_add.png',
	tooltip: 'Assegna',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore.getAt(rowIndex);
	alert("Assegnazione pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'images/comment.png',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore.getAt(rowIndex);
	alert("Aggiungi nota alla scadenza");    }
}            ,'-',
{icon   : 'images/alert.gif',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore.getAt(rowIndex);
	alert("Aggiungi un avviso per la scadenza");    }
}
]
}                           
], // fine colonne
listeners: {
    	rowdblclick: function(grid,rowIndex,event) {showDetail();}, scope: this},


//  	paging bar on the bottom
    	bbar: new Ext.PagingToolbar({
    		pageSize: 25,
    		store: scadStore,
    		displayInfo: true,
    		displayMsg: 'Righe {0} - {1} di {2}',
    		emptyMsg: "Nessun elemento da mostrare",
    		items:[
    		       '-', {type:'button',text: '&nbsp;&nbsp;Stampa', icon:'images/stampa.gif'},
    		       '-', {type:'button',text: '&nbsp;&nbsp;Esporta', icon:'ext/examples/shared/icons/fam/application_go.png'}
    		       ]
    	})

    };    // fine grid

    MyTabPanelUiScadGlob = Ext.extend(Ext.TabPanel, {
    	activeTab: 0,
    	autoHeight: true, 
   	initComponent: function() {
    	this.items = [
    	              {
    	            	  xtype: 'panel',
    	            	  title: 'Gennaio 2011',
    	            	  //width: 840,
    	            	  //height: 600,
    	            	  autoHeight: true,
    	            	  items: [ gridScad1 ]      // fine items del  panel che contiene la grid
    	              },
    	              {
    	            	  xtype: 'panel',
    	            	  title: 'Febbraio 2011'
    	              },
    	              {
    	            	  xtype: 'panel',
    	            	  title: 'Marzo 2011'
    	              }
    	              ]; // fine assegnazione this.items = []
    	MyTabPanelUiScadGlob.superclass.initComponent.call(this);
    }
    });
    
    gridScad2 =   {
    		xtype: 'grid',
    		title: 'Lista scadenze Toyota FS',
    		store: scadStore2,
    		autoHeight: true,
    		border: false,
    		view: new Ext.grid.GroupingView(),
    		plugins: [filtersScad],
    		//width: 690,
    		columns: [
{dataIndex:'data',width:70,header:'Data',filterable:false,sortable:true,groupable:true},
{dataIndex:'evento',width:150,header:'Evento',filterable:true,sortable:true},
{dataIndex:'num',align:'right',width:70,header:'Num. pratica',filterable:true,sortable:true},
{dataIndex:'importo',align:'right',width:60,header:'Importo',filterable:true,sortable:true},
{dataIndex:'classe',width:60,align:'center',header:'Classific.',filterable:true,sortable:true,groupable:true},
{dataIndex:'reparto',width:50,header:'Reparto',filterable:true,sortable:true,groupable:true,groupable:true},
{dataIndex:'committente',width:70,header:'Committente',filterable:true,sortable:true,groupable:true},
{dataIndex:'operatore',width:70,header:'Operatore',filterable:true,sortable:true},
{dataIndex:'nota',width:120,header:'Nota'},
{xtype: 'actioncolumn',
	width: 810,
	header:'Azioni',
	sortable:false,  filterable:false,
	items: [
{icon   : 'images/stampa.gif',
	tooltip: 'Stampa',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore2.getAt(rowIndex);
	alert("Stampa pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/application_go.png',
	tooltip: 'Invia',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore2.getAt(rowIndex);
	alert("Invio messaggio per pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/user_add.png',
	tooltip: 'Assegna',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore2.getAt(rowIndex);
	alert("Assegnazione pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'images/comment.png',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore2.getAt(rowIndex);
	alert("Aggiungi nota alla scadenza");    }
}            ,'-',
{icon   : 'images/alert.gif',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore2.getAt(rowIndex);
	alert("Aggiungi un avviso per la scadenza");    }
}
]
}                           
], // fine colonne
listeners: {
    	rowdblclick: function(grid,rowIndex,event) {showDetail();}, scope: this},


//  	paging bar on the bottom
    	bbar: new Ext.PagingToolbar({
    		pageSize: 25,
    		store: scadStore2,
    		displayInfo: true,
    		displayMsg: 'Righe {0} - {1} di {2}',
    		emptyMsg: "Nessun elemento da mostrare",
    		items:[
    		       '-', {type:'button',text: '&nbsp;&nbsp;Stampa', icon:'images/stampa.gif'},
    		       '-', {type:'button',text: '&nbsp;&nbsp;Esporta', icon:'ext/examples/shared/icons/fam/application_go.png'}
    		       ]
    	})

    };    // fine grid
    
    MyTabPanelUiScadComm = Ext.extend(Ext.TabPanel, {
    	activeTab: 0,
    	autoHeight: true, 
   	initComponent: function() {
    	this.items = [
    	              {
    	            	  xtype: 'panel',
    	            	  title: 'Toyota FS',
    	            	  autoHeight: true,
    	            	  items: [ gridScad2 ]      // fine items del  panel che contiene la grid
    	              },
    	              {
    	            	  xtype: 'panel',
    	            	  title: 'Toyota KG'
    	              }
    	              ]; // fine assegnazione this.items = []
    	MyTabPanelUiScadComm.superclass.initComponent.call(this);
    }
    });
    
    gridScad3 =   {
    		xtype: 'grid',
    		title: 'Lista scadenze reparto CL',
    		store: scadStore3,
    		autoHeight: true,
    		border: false,
    		view: new Ext.grid.GroupingView(),
    		plugins: [filtersScad],
    		//width: 690,
    		columns: [
{dataIndex:'data',width:70,header:'Data',filterable:false,sortable:true,groupable:true},
{dataIndex:'evento',width:150,header:'Evento',filterable:true,sortable:true},
{dataIndex:'num',align:'right',width:70,header:'Num. pratica',filterable:true,sortable:true},
{dataIndex:'importo',align:'right',width:60,header:'Importo',filterable:true,sortable:true},
{dataIndex:'classe',width:60,align:'center',header:'Classific.',filterable:true,sortable:true,groupable:true},
{dataIndex:'reparto',width:50,header:'Reparto',filterable:true,sortable:true,groupable:true,groupable:true},
{dataIndex:'committente',width:70,header:'Committente',filterable:true,sortable:true,groupable:true},
{dataIndex:'operatore',width:70,header:'Operatore',filterable:true,sortable:true},
{dataIndex:'nota',width:120,header:'Nota'},
{xtype: 'actioncolumn',
	width: 810,
	header:'Azioni',
	sortable:false,  filterable:false,
	items: [
{icon   : 'images/stampa.gif',
	tooltip: 'Stampa',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore3.getAt(rowIndex);
	alert("Stampa pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/application_go.png',
	tooltip: 'Invia',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore3.getAt(rowIndex);
	alert("Invio messaggio per pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'ext/examples/shared/icons/fam/user_add.png',
	tooltip: 'Assegna',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore3.getAt(rowIndex);
	alert("Assegnazione pratica n. "+rec.get('num'));    }
},'-',
{icon   : 'images/comment.png',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore3.getAt(rowIndex);
	alert("Aggiungi nota alla scadenza");    }
}            ,'-',
{icon   : 'images/alert.gif',
	tooltip: 'Note',
	handler: function(grid, rowIndex, colIndex) {
	var rec = scadStore3.getAt(rowIndex);
	alert("Aggiungi un avviso per la scadenza");    }
}
]
}                           
], // fine colonne
listeners: {
    	rowdblclick: function(grid,rowIndex,event) {showDetail();}, scope: this},


//  	paging bar on the bottom
    	bbar: new Ext.PagingToolbar({
    		pageSize: 25,
    		store: scadStore3,
    		displayInfo: true,
    		displayMsg: 'Righe {0} - {1} di {2}',
    		emptyMsg: "Nessun elemento da mostrare",
    		items:[
    		       '-', {type:'button',text: '&nbsp;&nbsp;Stampa', icon:'images/stampa.gif'},
    		       '-', {type:'button',text: '&nbsp;&nbsp;Esporta', icon:'ext/examples/shared/icons/fam/application_go.png'}
    		       ]
    	})

    };    // fine grid
    
    MyTabPanelUiScadRep = Ext.extend(Ext.TabPanel, {
    	activeTab: 0,
    	autoHeight: true, 
   	initComponent: function() {
    	this.items = [
    	              {xtype: 'panel',title: 'CL', autoHeight: true, items: [ gridScad3 ]},
    	              {xtype: 'panel',title: 'C5'},
    	              {xtype: 'panel',title: 'LR'},
    	              {xtype: 'panel',title: 'P4'},
    	              {xtype: 'panel',title: '1C'}
    	              ]; // fine assegnazione this.items = []
    	MyTabPanelUiScadRep.superclass.initComponent.call(this);
    }
    });

} // fine funzione initThis