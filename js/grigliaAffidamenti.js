    //--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
    function showDetailAff()
    {
    	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Lettura insoluti..."});
    	myMask.show();
        Ext.Ajax.request({
            url: 'dettaglioAffidamento.js', method:'GET',
            failure: function() {Ext.Msg.alert("Impossibile aprire la pagina di dettaglio", "Errore Ajax");},
            success: function(xhr)
            {
                eval(xhr.responseText);
                var win = new Ext.Window({
                    //width: 700, height:500, minWidth: 700, minHeight: 500,ù
                	autoHeight:true,
                	modal: true,
                    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
                    title: 'Dettaglio affidamenti',
					constrain: true,
                    items: tabDA
                    });
                win.show();
                myMask.hide();
            } // fine corpo funzione Ajax.success
         } // fine corpo richiesta Ajax
         ) // fine parametri Ajax.request
    } // fine funzione showDetail

  // DATI DI PROVA
        var affidData = [
        ['I1','ATS', 230,'INS','02/10/2010','29/10/2010',38, '12.670,10',12,'5020,00','RL'],
        ['I7','KREOS', 231,'INS','02/10/2010','29/10/2010',17, '8.809,00',3,'907,00','RL'],
        ['C5','CITY', 232,'B14','02/10/2010','29/10/2010',3, '1.600,80',0,'0,00','RL'],
        ['1C','CITY', 233,'R02','02/10/2010','29/10/2010',1, '820,00',0,'0,00','RL'],
        ['1S','SOGEC', 234,'R04','02/10/2010','29/10/2010',30, '9.555,00',21,'7.095,00','RL'],
        ['CL','CITY', 235,'B31','02/10/2010','29/10/2010',2, '440,00',0,'0,00','RL'],
        ['LR','CITY', 236,'R01','02/10/2010','29/10/2010',3, '970,50',0,'0,00','RL'],
        ['P2','SOGEC', 237,'R01','02/10/2010','29/10/2010',4, '819,00',1,'119,00','RL']
        ];
    //  grouping store
    var affidReader = new Ext.data.ArrayReader({fields:  [
                {name: 'ag_code'}, {name: 'ag_name'},{name: 'lotto',type:'int'},{name:'classe'},{name: 'dataini'},{name:'datafin'},
                {name:'num',type:'int'},  {name:'importo'},{name:'numok',type:'int'},  {name:'importook'},'operatore'
        ]});
    var  affidStore = new Ext.data.GroupingStore({
        autoDestroy: true,
        reader: affidReader,
        data: affidData
    });

    // colonna combo di selezione
    var  affidsm = new Ext.grid.CheckboxSelectionModel();

    // filtri
    var filtersAffid = new Ext.ux.grid.GridFilters({
        // encode and local configuration options defined previously for easier reuse
        encode: true, // json encode the filter query
        local: true,   // defaults to false (remote filtering)
        filters: [{
            type: 'list',  options: ['INS','B14','B31','R01','R02','R03','R04'],
            dataIndex: 'classe'
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
    tabPanelAffidamenti = Ext.extend(Ext.TabPanel, {
    activeTab: 0,
 /*   width: 694,
    height: 600,
    autoHeight: true, */
    initComponent: function() {
        this.items = [
            {
                xtype: 'panel',
                title: 'Phone Collection',
                //width: 840,
                //height: 600,
                autoHeight: true,
                items: [
                    {
                        xtype: 'grid',
                        title: 'Lista affidamenti agenzie di phone collection',
                        store: affidStore,
                        autoHeight: true,
                        border: false,
  			view: new Ext.grid.GroupingView(),
                      plugins: [filtersAffid],
                        //width: 690,
                        sm: affidsm,
                        columns: [ affidsm,
        {dataIndex:'ag_code',width:50,header:'Cod.Ag.',filterable:true,sortable:true,groupable:true},
        {dataIndex: 'ag_name',width:70, header: 'Agenzia', filterable: true,sortable:true,groupable:true},
        {dataIndex:'lotto',width:40,align:'right',header:'Lotto',sortable:true},
        {dataIndex:'classe',width:60,align:'center',header:'Classific.',filterable:true,sortable:true,groupable:true},
        {dataIndex:'dataini',width:70,header:'Data inizio',filterable:false,sortable:true,groupable:true},
        {dataIndex:'datafin',width:70,header:'Data fine',filterable:false,sortable:true,groupable:true},
        {dataIndex:'num',align:'right',width:80,header:'Num. pratiche',filterable:true,sortable:true},
        {dataIndex:'importo',align:'right',width:80,header:'Tot. Importo',filterable:true,sortable:true},
        {dataIndex:'numok',align:'right',width:75,header:'Num. incassi',filterable:true,sortable:true},
        {dataIndex:'importook',align:'right',width:75,header:'Tot. Incassi',filterable:true,sortable:true},
        {dataIndex:'operatore',width:60,header:'Operatore',filterable:true,sortable:true}
                     ], // fine colonne
           listeners: {
                rowdblclick: function(grid,rowIndex,event) {showDetailAff();}, scope: this},


         // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 25,
            store: affidStore,
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
                title: 'Esattoriale'
            },
            {
                xtype: 'panel',
                title: 'Stragiudiziale'
            },
            {
                xtype: 'panel',
                title: 'Altre agenzie'
            }
        ];
        tabPanelAffidamenti.superclass.initComponent.call(this);
    }
});
