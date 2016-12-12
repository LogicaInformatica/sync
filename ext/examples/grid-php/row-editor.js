/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
Ext.onReady(function(){
    Ext.QuickTips.init();

    var Employee = Ext.data.Record.create([{
        name: 'name',
        type: 'string'
    }, {
        name: 'email',
        type: 'string'
    }, {
        name: 'start',
        type: 'date',
        dateFormat: 'n/j/Y'
    },{
        name: 'salary',
        type: 'float'
    },{
        name: 'active',
        type: 'bool'
    }]);

	/*
	 *  Codici di stato del contratto
	 */
	var recStatoContratto = Ext.data.Record.create([
		{name: 'IdStatoContratto', type: 'numeric'},
		{name: 'CodStatoContratto'},	// Codice abbreviato dello stato, usato in tutti i casi in cui il testo completo della definizione è troppo lungo per la visualizzazione.
		{name: 'TitoloStatoContratto'},
		{name: 'CodStatoLegacy'},		// Codice stato corrispondente sul sistema legacy (se applicabile)
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser'}		// Utente che ha effettuato l'ultimo "save"
	]);
	
	var readerStatoContratto = new Ext.data.JsonReader({
			root: 'results', //delimiter tag for each record (row of data)
			totalProperty: 'total',
			idProperty: 'IdStatoContratto'
        },
        recStatoContratto 
	);


    // hideous function to generate employee data
    var genData = function(){
        var data = [];
        var s = new Date(2007, 0, 1);
        var now = new Date(), i = -1;
        while(s.getTime() < now.getTime()){
            var ecount = Ext.ux.getRandomInt(0, 3);
            for(var i = 0; i < ecount; i++){
                var name = Ext.ux.generateName();
                data.push({
                    start : s.clearTime(true).add(Date.DAY, Ext.ux.getRandomInt(0, 27)),
                    name : name,
                    email: name.toLowerCase().replace(' ', '.') + '@exttest.com',
                    active: true,
                    salary: Math.floor(Ext.ux.getRandomInt(35000, 85000)/1000)*1000
                });
            }
            s = s.add(Date.MONTH, 1);
        }
        return data;
    }


    var store = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
				url: 'grid-editor-mysql-php.php', //url to data object (server side script)
				method: 'POST'
            }),   
		baseParams:{task: "readStock"},//this parameter is passed for any HTTP request
        reader: readerStatoContratto,
		sortInfo:{field: 'industryName', direction: "ASC"}
    });


    var editor = new Ext.ux.grid.RowEditor({
        saveText: 'Salva'
    });

    var grid = new Ext.grid.GridPanel({
        store: store,
        width: 600,
        region:'center',
        margins: '0 5 5 5',
        autoExpandColumn: 'TitoloStatoContratto',
        plugins: [editor],
        view: new Ext.grid.GridView({
            markDirty: false
        }),
        tbar: [{
            iconCls: 'icon-user-add',
            text: 'Aggiungi',
            handler: function(){
				var r = new recStatoContratto({	//specify default values
					IdStatoContratto: 0,
					CodStatoContratto: '', 
					TitoloStatoContratto: '',
					CodStatoLegacy: '',
					DataIni: (new Date()).clearTime(),
					DataFin: (new Date()).clearTime(),
					LastUpd: new Date(),
					LastUser: ''
				});
                editor.stopEditing();
                store.insert(0, r);
                grid.getView().refresh();
                grid.getSelectionModel().selectRow(0);
                editor.startEditing(0);
            }
        },{
            ref: '../removeBtn',
            iconCls: 'icon-user-delete',
            text: 'Elimina',
            disabled: true,
            handler: function(){
                editor.stopEditing();
                var s = grid.getSelectionModel().getSelections();
                for(var i = 0, r; r = s[i]; i++){
                    store.remove(r);
                }
            }
        }],

        columns: new Ext.grid.ColumnModel([ //instantiate ColumnModel
		        /**
		         * Here we give comma separated definitions of the fields we
		         * want displayable (some may be initially hidden) in the grid.
		         * Note you need not display every column in your data store 
		         * here; you can include fields here and have them be hidden or
		         * you can just not include some fields in your grid whatsoever
		         * (maybe you just retrieved them to do other behind the scenes
		         * processing client side instead of server side)
		         */ 
                 {  
				    /*optionally specify the aligment (default = left)*/
					align: 'right',
					
					/*[Required] Specify the dataIndex which is the DataStore
					 * field "name" this column draws its data from */
                    dataIndex: primaryKey, // 'IdStatoContratto'
					
                    header:'ID',//header = text that appears at top of column
                    //hidden: true, //true to initially hide the column
                    
					/**
					 * We can optionally place an id on a column so we can later
					 * reference the column specifically. This might be useful
					 * for instance if we want to set a css style to highlight
					 * the column (.x-grid3-col-classCompanyID)
					 * This doesn't appear to work well with an editor grid
					 * though, as the red triangle gets covered up. As a guess, 
					 * perhaps something with the z-index could be changed so the
					 * red triangle remained on top?
					 * Another use might be to select an entire column and do
					 * something with it. Example anyone?
					 */ 
                    id:    'classCompanyID',
                    //locked: false,//no longer supported, see user extensions
                    sortable: true,//false (default) disables sorting by this column
                    width: 9       //column width
                 },{                         
                    dataIndex: 'CodStatoContratto',
                    header: "Codice Stato",
                    id:    'idCodStatoContratto',
                    locked: false,
                    sortable: true,
                    //resizable: false,//disable column resizing (can also use fixed = true)
                    width: 12,      
					
					//TextField editor - for an editable field add an editor
                    editor: new Ext.form.TextField({ 
                        //specify options
                        allowBlank: false //default is true (nothing entered)
                    })                           
                },{                         
                    dataIndex: 'TitoloStatoContratto',
                    header: "Titolo Stato", 
                    sortable: true,
                    width: 60, 

	                /* (NOTE: as of Ext2.0-rc1 the GroupingStore has problems
					 * sizing the grid when columns are initially hidden)  */
                    //hidden: true,//true to initially hide the column 
                    
					/* optional rendering function to provide customized data
					 * formatting */
/*
					renderer: Ext.util.Format.usMoney,
                    editor: new Ext.form.NumberField({
                        //specify options
                        allowBlank: false,  //default is true (nothing entered)
                        allowNegative: false, //could also use minValue
                        maxValue: 100
                    }) */
                },{                         
                    dataIndex: 'CodStatoLegacy',
                    header: "Codice Legacy", 
                    sortable: true,
                    width: 12
                },{
					dataIndex: 'DataIni',
					header: "Inizio Validità",
					renderer: renderDate,
					sortable: true,
					width: 20,
                    editor: dataEditor
                },{
					dataIndex: 'DataFin',
					header: "Fine Validità",
					renderer: renderDate,
					sortable: true,
					width: 20,
                    editor: dataEditor
				},{
                    dataIndex: 'LastUpd',
                    header: "Ultima modifica", 
                    renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'), 
                    sortable: true, 
                    width: 20, 
                },{
					dataIndex: 'LastUser',
					header: "Utente",
					sortable: true, 
					width: 30
				} 
				
            ])
    });



    var layout = new Ext.Panel({
        title: 'Stato Contratto',
        layout: 'border',
        layoutConfig: {
            columns: 1
        },
        width:600,
        height: 600,
        items: [grid]
    });
    layout.render(Ext.getBody());

    grid.getSelectionModel().on('selectionchange', function(sm){
        grid.removeBtn.setDisabled(sm.getCount() < 1);
    });
});
