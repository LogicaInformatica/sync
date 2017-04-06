try
{
	var tableFunzione = new DCS.Table();
	tableFunzione.name = "funzione";
	tableFunzione.pk = "IdFunzione";
	tableFunzione.expandCol = "titolo";

	//------------------------- Record -------------------------
	tableFunzione.record = Ext.data.Record.create([
		{name: 'IdFunzione', type: 'int', allowBlank:false},
		{name: 'CodFunzione', type: 'string' },
		{name: 'TitoloFunzione', type: 'string' }
	]);

	//------------------------- ColumnModel -------------------------
	tableFunzione.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdFunzione',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'CodFunzione',
				header: "Codice",
				//resizable: false,//disable column resizing (can also use fixed = true)
				width: 15,			
				//TextField editor - for an editable field add an editor
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			}, {
				id: 'titolo',
				dataIndex: 'TitoloFunzione',
				header: 'Titolo',
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			}]
		}
	);
	
	tableFunzione.newRecord = function() {
		return new tableFunzione.record({	//specify default values
			IdFunzione: 0,
			CodFunzione: '', 
			TitoloFunzione: ''
		});
	};

	//--------------------------------
	//  Lista Azioni sulle pratiche
	//--------------------------------
	var fn_anagFunzione= function() {
		var gridFunzione = new EditGrid(tableFunzione);
	
		return gridFunzione.getGrid();
	};
}
catch (e)
{
	alert("grid_Funzione.js: " + e);
}
