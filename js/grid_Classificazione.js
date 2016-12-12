try
{
	var tableClassificazione = new DCS.Table();
	tableClassificazione.name = "v_classificazione";
	tableClassificazione.pk = "IdClasse";
	tableClassificazione.expandCol = "titolo";
	
	//------------------------- Record -------------------------
	tableClassificazione.record = Ext.data.Record.create([
		{name: 'IdClasse', type: 'int', allowBlank:false},
		{name: 'CodClasse', type: 'string', allowBlank:false},
		{name: 'TitoloClasse', type: 'string', allowBlank:false},
		{name: 'AbbrClasse', type: 'string', allowBlank:false},
		{name: 'CodClasseLegacy', type: 'string', allowBlank:false}
	]);
	
	//------------------------- ColumnModel -------------------------
	tableClassificazione.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdClasse',
				header: 'IDCl',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			},{
				dataIndex: 'CodClasse',
				header: "Codice",
				//resizable: false,//disable column resizing (can also use fixed = true)
				width: 10,			
				//TextField editor - for an editable field add an editor
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			},{
				id: 'titolo',
				dataIndex: 'TitoloClasse',
				header: "Definizione",
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			},{
				dataIndex: 'AbbrClasse',
				header: "Abbreviazione",
				width: 20
			},{
				dataIndex: 'CodClasseLegacy',
				header: "Vecchio codice",
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 20
			}]
		}
	);
	
	tableClassificazione.newRecord = function() {
		return new tableClassificazione.record({	//specify default values
			IdClasse: 0,
			CodClasse: '',
			TitoloClasse: '',
			AbbrClasse: '',
			CodClasseLegacy: ''
		});
	};
	
	//--------------------------------
	//  Codici di stato del contratto
	//--------------------------------
	var fn_anagClassificazioni = function() {
		var gridClassificazione = new EditGrid(tableClassificazione);
	
		return gridClassificazione.getGrid();
	};
}
catch (e)
{
	alert("grid_Classificazione.js: " + e);
}
