    //--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
    function showDetailStorico()
    {
    	try 
    	{
    		var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Lettura dettaglio storico..."});
    		myMask.show();
    		Ext.Ajax.request({
    			url: 'dettaglioStorico.js', method:'GET',
    			failure: function() {Ext.Msg.alert("Impossibile aprire la pagina di dettaglio", "Errore Ajax");},
    			success: function(xhr)
    			{
    				eval(xhr.responseText);
    				var window = new Ext.Window({
    					//width: 700, height:500, minWidth: 700, minHeight: 500,ù
    					autoHeight:true,
    					modal: true,
    					layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    					title: 'Dettaglio affidamenti Dicembre 2010',
    					items: tabST
                    	});
    				window.show();
    				myMask.hide();
    			} // fine corpo funzione Ajax.success
    		} // fine corpo richiesta Ajax
    		) // fine parametri Ajax.request
    	} // fine try
    	catch (e)
    	{
			myMask.hide();
    		alert("showDetaiStorico: " +e);
    	}
    } // fine funzione showDetailStorico

  // DATI DI PROVA
        var storicoData = [
        ['I1', 230,'INS','02/10/2010','29/10/2010',38, '12.670,10',12,'5020,00','TFS','Ott. 2010'],
        ['I1', 231,'INS','02/10/2010','29/10/2010',17, '8.809,00',3,'907,00','TKG','Ott. 2010'],
        ['C5', 232,'B14','02/10/2010','29/10/2010',3, '1.600,80',0,'0,00','TFS','Ott. 2010'],
        ['1C', 233,'R02','02/10/2010','29/10/2010',1, '820,00',0,'0,00','TFS','Ott. 2010'],
        ['1S', 234,'R04','02/11/2010','29/11/2010',30, '9.555,00',21,'7.095,00','TFS','Nov. 2010'],
        ['CL', 235,'B31','02/11/2010','29/11/2010',2, '440,00',0,'0,00','TFS','Nov. 2010'],
        ['LR', 236,'R01','02/11/2010','29/11/2010',3, '970,50',0,'0,00','TFS','Nov. 2010'],
        ['P4', 237,'R01','02/11/2010','29/11/2010',4, '819,00',1,'119,00','TKG','Nov. 2010']
        ];
    //  grouping store
    var storicoReader = new Ext.data.ArrayReader({fields:  [
                {name: 'ag_code'}, {name: 'lotto',type:'int'},{name:'classe'},{name: 'dataini'},{name:'datafin'},
                {name:'num',type:'int'},  {name:'importo'},{name:'numok',type:'int'},  {name:'importook'},'committente',
                'mese'
        ]});
    var  storicoStore = new Ext.data.GroupingStore({
        autoDestroy: true,
        reader: storicoReader,
        data: storicoData,
        groupField: 'mese'
    });

    // colonna combo di selezione
    var  storicosm = new Ext.grid.CheckboxSelectionModel();

    // filtri
    var filtersStorico = new Ext.ux.grid.GridFilters({
        // encode and local configuration options defined previously for easier reuse
        encode: true, // json encode the filter query
        local: true,   // defaults to false (remote filtering)
        filters: [{
            type: 'list',  options: ['INS','B14','B31','R01','R02','R03','R04'],
            dataIndex: 'classe'
        }, {
            type: 'list',  options: ['TFS','TKG'],
            dataIndex: 'committente'
        }, {
            type: 'string',
            dataIndex: 'ag_code'
        },
        {
            type: 'numeric',
            dataIndex: 'lotto'
        }]
    });
     // TABS e GRIGLIA
    tabPanelStorico = Ext.extend(Ext.TabPanel, {
    activeTab: 1,
    autoHeight: true,
    initComponent: function() {
        this.items = [{xtype: 'panel', title: 'Anno 2011'},
            {
                xtype: 'panel',
                title: 'Anno 2010',
                autoHeight: true,
                items: [
                    {
                        xtype: 'grid',
                        title: 'Lista affidamenti Anno 2010',
                        store: storicoStore,
                        autoHeight: true,
                        border: false,
  			view: new Ext.grid.GroupingView(),
                      plugins: [filtersStorico],
                        //width: 690,
                        sm: storicosm,
                        columns: [ storicosm,
        {dataIndex:'ag_code',width:50,header:'Cod.Ag.',filterable:true,sortable:true,groupable:true},
        {dataIndex:'lotto',width:40,align:'right',header:'Lotto',sortable:true},
        {dataIndex:'classe',width:60,align:'center',header:'Classific.',filterable:true,sortable:true,groupable:true},
        {dataIndex:'dataini',width:70,header:'Data inizio',filterable:false,sortable:true,groupable:true},
        {dataIndex:'datafin',width:70,header:'Data fine',filterable:false,sortable:true,groupable:true},
        {dataIndex:'num',align:'right',width:80,header:'Num. pratiche',filterable:true,sortable:true},
        {dataIndex:'importo',align:'right',width:80,header:'Tot. Importo',filterable:true,sortable:true},
        {dataIndex:'numok',align:'right',width:75,header:'Num. incassi',filterable:true,sortable:true},
        {dataIndex:'importook',align:'right',width:75,header:'Tot. Incassi',filterable:true,sortable:true},
        {dataIndex:'committente',width:60,header:'Committente',filterable:true,sortable:true,groupable:true},
        {dataIndex:'mese',width:60,header:'Mese',filterable:true,sortable:true,groupable:true}
                     ], // fine colonne
           listeners: {
                rowdblclick: function(grid,rowIndex,event) {showDetailStorico();}, scope: this},


         // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 25,
            store: storicoStore,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare",
            items:[
                 '-', {type:'button',text: '&nbsp;&nbsp;Stampa', icon:'images/stampa.gif'},
                '-', {type:'button',text: '&nbsp;&nbsp;Esporta', icon:'ext/examples/shared/icons/fam/application_go.png'}
                ]
        })

                   }    // fine grid
                 ]      // fine items del tab
            },
            {
                xtype: 'panel',
                title: 'Anno 2009'
            }
        ];
        tabPanelStorico.superclass.initComponent.call(this);
    }
});
