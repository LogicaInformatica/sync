// Crea namespace DCS
Ext.namespace('DCS'); 

DCS.PianoRateazione = function(idContratto, codContratto) {
	
     // create the Data Store
	
	var select = "SELECT  pr.ImportoRata, pr.DecorrenzaRate, pr.NumeroRate, pr.DataPagPrimoImporto, pr.PrimoImporto, pr.LastUser, pr.lastupd, pr.DataAccordo, pr.IdStatoPiano, pr.IdPianoRientro, pr.IdContratto , sp.CodStatoPiano, sp.TitoloStatoPiano " 
				+" FROM pianorientro pr, statopiano sp"
				+" WHERE pr.IdStatoPiano = sp.IdStatoPiano"
				+" AND  pr.IdContratto="+idContratto+" ORDER BY pr.DataAccordo";

	var dsPianoRateazione = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdPianoRientro'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'ImportoRata'},
					{name: 'DecorrenzaRate', type:'date'},
					{name: 'NumeroRate'},
					{name: 'DataPagPrimoImporto', type:'date'},
					{name: 'PrimoImporto'},
					{name: 'LastUser'},
					{name: 'lastupd'},
					{name: 'DataAccordo', type:'date'},
					{name: 'IdStatoPiano'},
					{name: 'IdPianoRientro'},
					{name: 'IdContratto'},
					{name: 'CodStatoPiano'},
					{name: 'TitoloStatoPiano'}
				]
        ),
		sortInfo:{field: 'DataAccordo', direction: "ASC"}
	});//end dsIndustry   

    var grid = new Ext.grid.GridPanel({
    	pagesize: 0,
    	id: 'gridPianoRateazione',
        width:500,
        height:300,
        title:'',
        store: dsPianoRateazione,
        trackMouseOver:false,
        disableSelection:true,
        loadMask: true,
		listeners: {
			rowdblclick: function(grid,rowIndex,event) {
				var rec = dsPianoRateazione.getAt(rowIndex);
				eseguiAzioneBase(422,[idContratto],"Dettaglio piano rateazione - "+codContratto,'gridPianoRateazione',{"IdPianoRientro":rec.get("IdPianoRientro")});
			},
			activate: function() {
	 			var lastOpt = dsPianoRateazione.lastOptions;
	 			if (!lastOpt || lastOpt.params==undefined) {
	 				if (this.pagesize>0) {
	 					dsPianoRateazione.load({
	 						params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
	 							start: 0, //pass start/limit parameters for paging
	 							limit: this.pagesize
	 						}
	 					}); 
	 				} else {
	 					dsPianoRateazione.load(); 
	 				}
	 			}
	 		},
			scope: this
		},
		viewConfig: {
			autoFill: true,
			forceFit: false,
	        getRowClass : function(record, rowIndex, p, store){
			        if(record.get('NumRata')%2)
			        {
				        return 'grid-row-azzurrochiaro';
			        }
			        return 'grid-row-azzurroscuro';
			}
		},
        // grid columns
        columns:[{
        	xtype:'datecolumn', 
        	format:'d/m/Y',
            header: "Data piano",
            dataIndex: 'DataAccordo',
            width: 120,
            align: 'Left'
        },{
            header: "Stato",
            dataIndex: 'TitoloStatoPiano',
            width: 150,
            align: 'Left'
        },{
            header: "Num. rate",
            dataIndex: 'NumeroRate',
            width: 150,
            align: 'Left'
        },{
            header: "Imp. rata",
            dataIndex: 'ImportoRata',
            width: 150,
            align: 'Left'
        },{
            header: "Decorrenza",
        	xtype:'datecolumn', 
        	format:'d/m/Y',
            dataIndex: 'DecorrenzaRate',
            width: 150,
            align: 'Left'
        },{
            header: "Utente",
            dataIndex: 'LastUser',
            width: 70,
            align: 'Left'
        }],        

     bbar: new Ext.Toolbar({
            id: 'bbAllXX',
			cls: "x-panel-header",
			items:['->',{ref: '../addBtn',
				  id: 'bAllXX',
				  text: 'Nuovo piano rateazione',
				  hidden: true,
				  tooltip: 'Crea un nuovo piano rateazione',
				  iconCls:'grid-add'}
				  ]
     	   })	
    });
    var statoBottAll='';
	var barAll='';
    statoBottAll = Ext.getCmp('bbAllXX'); // barra
	barAll = statoBottAll.items.get('bAllXX'); //bottone 
	barAll.on('click', function() {
		eseguiAzioneBase(422,[idContratto],"Nuovo piano rateazione",'gridPianoRateazione',{"IdPianoRientro":0});
	}, this);
    

	if(CONTEXT.AZIONE_ALL == true)
	{	
		barAll.hidden = false;
	}
     // trigger the data store load
    //dsPianoRateazione.load();
    
	return grid;
};
