//=================== OBSOLETO

try
{
	var tableProfiloUtenti = new DCS.Table();
	tableProfiloUtenti.name = "v_profili_utenti";
	tableProfiloUtenti.pk = "IdProfiUlotente";
	tableProfiloUtenti.expandCol = "titolo";

	//------------------------- Record -------------------------
	tableProfiloUtenti.record = Ext.data.Record.create([
		{name: 'IdProfiloUtente', type: 'int', allowBlank:false},
		{name: 'Userid', allowBlank:false},		// Codice abbreviato 
		{name: 'TitoloProfilo', allowBlank:false}
	]);

	//------------------------- ColumnModel -------------------------
	tableProfiloUtenti.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdProfiloUtente',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'Userid',
				header: "Utente",
				//resizable: false,//disable column resizing (can also use fixed = true)
				width: 10,			
				//TextField editor - for an editable field add an editor
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			}, {
				id: 'titolo',
				dataIndex: 'TitoloProfilo',
				header: 'Titolo',
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			}]
		}
	);
	
	tableProfiloUtenti.newRecord = function() {
		return new tableProfiloUtenti.record({	//specify default values
			IdProfiloUtente: 0,
			Userid: '', 
			TitoloProfilo: ''
		});
	};

	//--------------------------------
	//  Lista Azioni sulle pratiche
	//--------------------------------
	var fn_UtProfiloUtenti= function() {
		var gridProfiloUtenti = new EditGrid(tableProfiloUtenti);
	
		return gridProfiloUtenti.getGrid();
	};
}
catch (e)
{
	alert("grid_ProfiloUtenti.js: " + e);
}
