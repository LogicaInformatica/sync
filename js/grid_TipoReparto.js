try
{
	var tableTReparto = new DCS.Table();
	tableTReparto.name = "tiporeparto";
	tableTReparto.pk = "IdTipoReparto";
	tableTReparto.expandCol = "titolo";

	//------------------------- Record -------------------------
	tableTReparto.record = Ext.data.Record.create([
		{name: 'IdTipoReparto', type: 'int', allowBlank:false},
		{name: 'CodTipoReparto', type: 'string' },
		{name: 'TitoloTipoReparto', allowBlank:false},
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'Lastuser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);

	//------------------------- ColumnModel -------------------------
	tableTReparto.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdTipoReparto',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'CodTipoReparto',
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
				dataIndex: 'TitoloTipoReparto',
				header: 'Titolo',
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			},{
				dataIndex: 'Ordine',
				header: "Posizione",
				editor: new Ext.form.NumberField({
	                allowBlank: true,
	                allowNegative: false,
	                maxValue: 100000
	            }),
				align:'center',
				width: 20
			},{
				dataIndex: 'DataIni',
				header: "Inizio Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			}, {
				dataIndex: 'DataFin',
				header: "Fine Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			}, {
				dataIndex: 'LastUpd',
				header: "Ultima modifica",
				renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'),
				width: 20
			}, {
				dataIndex: 'Lastuser',
				header: "Utente",
				width: 12
			}]
		}
	);
	
	tableTReparto.newRecord = function() {
		return new tableTReparto.record({	//specify default values
			IdTipoReparto: 0,
			CodTipoReparto: '', 
			TitoloTipoReparto: '',
			Ordine:1,
			DataIni: (new Date()).clearTime(),
			DataFin: (new Date()).clearTime(),
			LastUpd: new Date(),
			Lastuser: ''
		});
	};

	//--------------------------------
	//  Lista Azioni sulle pratiche
	//--------------------------------
	var fn_anagTReparto= function() {
		var gridTReparto = new EditGrid(tableTReparto);
	
		return gridTReparto.getGrid();
	};
}
catch (e)
{
	alert("grid_TipoReparto.js: " + e);
}
