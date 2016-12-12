// Crea namespace DCS
Ext.namespace('DCS'); 

DCS.PraticaServizi = function(idContratto) {
     // create the Data Store
	console.log("id "+idContratto);
	var select = "SELECT * FROM accessorio where IdContratto="+idContratto;

	var dsServizi = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results'//name of the property that is container for an Array of row objects
					//,id: 'IdStoriaRecupero'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'IdAccessorio'},
					{name: 'Prodotto'},
					{name: 'Info'},
					{name: 'DataIni',type: 'date', dateFormat: 'Y-m-d'},
					{name: 'DataFin',type: 'date', dateFormat: 'Y-m-d'}
				]
            ),
			sortInfo:{field: 'IdAccessorio', direction: "ASC"},
			remoteSort:true
		}
	);//end dsIndustry   

    var grid = new Ext.grid.GridPanel({
       // width:1000,
        height:450,
        title:'',
        store: dsServizi,
        trackMouseOver:false,
        disableSelection:true,
        loadMask: true,

        // grid columns
        columns:[{
            header: "Prodotto",
            dataIndex: 'Prodotto',
            width:100,
			fixed:true,
            align: 'left',
            sortable: true
        },{
            header: "Firmatario",
            dataIndex: 'Info',
            width: 120,
            align: 'left',
            sortable: true
        },{
            header: "Inizio contratto",
            dataIndex: 'DataIni',
            width: 75,
            align: 'center',
            renderer:DCS.render.date,
            sortable: true
        },{
            header: "Fine contratto",
            dataIndex: 'DataFin',
            width: 75,
            align: 'center',
            renderer:DCS.render.date,
            sortable: true
        }],

        // customize view config
       viewConfig: {
			autoFill: true,
			forceFit: false
        },

        // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 20,
            store: dsServizi,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare"
        })
    });

    // render it
 //   grid.render('topic-grid');

    // trigger the data store load
    dsServizi.load({params:{start:0, limit:10}});
	
	return grid;
};
