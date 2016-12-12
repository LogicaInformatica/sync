    // create the data store
    var storeComm = new Ext.data.ArrayStore({
        fields: [
           {name: 'data'},
           {name: 'tipomsg'},
           {name: 'mittente'},
           {name: 'destinatario'},
           {name: 'agenzia'},
           {name: 'numero'},
           {name: 'cliente'},
           {name: 'titolo'},
           {name: 'testo'} ]
    });

    // DATI DI PROVA
        var myDataComm = [
        ['21/10/2010 09:37','In','KREOS (I7)','Collection','I7','123456','KOBUS ANNA KATARZYNA','Cliente ha gi&agrave; pagato?','Il cliente dichiara di aver gi&agrave;&nbsp;pagato in sede; chiudiamo?'],
        ['21/10/2010 10:03','Out','Collection','KREOS (I7)','I7','123456','KOBUS ANNA KATARZYNA','Re: Cliente ha gi&agrave; pagato?','Confermo pagamento con assegno in sede (rata n.17)']
         ];
    // manually load local data
    storeComm.loadData(myDataComm);

    // colonna espansione riga
   // var expander = new Ext.ux.grid.RowExpander({
   //     tpl : new Ext.Template(
   //         '<p><b>Testo:</b> {testo}</p><br>'
   //    )
   // });
     // row expander
    var expander = new Ext.ux.grid.RowExpander({
        tpl : new Ext.Template(
            '<p><b>Testo:</b> {testo}</p><br>'
        )
    });


    // filtri
    var filtersComm = new Ext.ux.grid.GridFilters({
        // encode and local configuration options defined previously for easier reuse
        encode: true, // json encode the filter query
        local: true,   // defaults to false (remote filtering)
        filters: [{
            type: 'list',options:['In','Out'],
            dataIndex: 'tipomsg'
        }, {
            type: 'string',
            dataIndex: 'agenzia'
        },{
            type: 'numeric',
            dataIndex: 'numero'
        }]
    });
     // GRIGLIA
    grigliaComunicazioni =
      {
                xtype: 'panel',
                title: 'Comunicazioni',
                //width: 840,
                //height: 600,
                autoHeight: true,
                items: [    grid=
                    {xtype: 'grid',
                        title: 'Messaggi in/out',
                        store: storeComm,
                        autoHeight: true,
                        border: false,
                        plugins: [filtersComm,expander],
                        collapsible: true,
                        animCollapse: false,

                        //width: 830,
                        //sm:smComm,
                        columns: [ expander,
           {dataIndex: 'data',width:90,header:'Data/ora',filterable:true,sortable:true},
           {dataIndex: 'tipomsg',width:40,header:'In/out',filterable:true,sortable:true},
           {dataIndex: 'mittente',width:80,header:'Mittente',filterable:true,sortable:true},
           {dataIndex: 'destinatario',width:80,header:'Destinatario',filterable:true,sortable:true},
           {dataIndex: 'agenzia',width:40,header:'Agenzia',filterable:true,sortable:true},
           {dataIndex: 'numero',width:70,header:'Pratica',filterable:true,sortable:true},
           {dataIndex: 'cliente',width:120,header:'Cliente',filterable:true,sortable:true},
           {dataIndex: 'titolo',width:200,header:'Soggetto'},
           {xtype: 'actioncolumn',
                width: 70,
                header:'Azioni',
                sortable:false,  filterable:false,
                items: [{icon   : 'ext/examples/shared/icons/fam/folder_go.png',
                    tooltip: 'Rispondi',
                    handler: function(grid, rowIndex, colIndex) {
                        var rec = storeComm.getAt(rowIndex);
                        alert("Rispondi");    }
                },'-',
                {icon:"images/space.png"},
                {icon   : 'ext/examples/shared/icons/fam/delete.gif',
                    tooltip: 'Cancella',
                    handler: function(grid, rowIndex, colIndex) {
                        var rec = storeComm.getAt(rowIndex);
                        alert("Cancella messaggio");    }
                }
                ]   // fine icone di azione su riga
            }// fine colonna action
                     ], // fine array colonne
         // customize view config
        viewConfig: {
			autoFill: true,
			forceFit: false,
            enableRowBody:true,
            showPreview:true,
            getRowClass : function(record, rowIndex, p, store){
                if(this.showPreview){
                    p.body = '<p style="color:darkblue">&nbsp&nbsp&nbsp;'+record.data.testo+'</p>';
                    return 'x-grid3-row-expanded';
                }
                return 'x-grid3-row-collapsed';
            }
        },
        // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 25,
            store: storeComm,
            displayInfo: true,
            displayMsg: 'Messaggi {0} - {1} di {2}',
            emptyMsg: "Nessun messaggio",
            items:[
                '-', {    xtype:'button' , icon:'ext/examples/shared/icons/fam/add.png',
                pressed: false,
                enableToggle:false,
                text: 'Nuovo messaggio',
                toggleHandler: function(btn, pressed){
                    alert('creazione nuovo messaggio');
                }
            }]
        }) // fine bbar
        } // fine proprietà grid
        ] // fine array items del panel
     } // fine proprietà del panel
     ;
