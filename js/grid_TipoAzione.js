try
{
	var tableTipoAzione = new DCS.Table();
	tableTipoAzione.name = "tipoazione";
	tableTipoAzione.pk = "IdTipoAzione";
	tableTipoAzione.expandCol = "titolo";
	
	//------------------------- Record -------------------------
	tableTipoAzione.record = Ext.data.Record.create([
		{name: 'IdTipoAzione', type: 'int', allowBlank:false},
		{name: 'CodTipoAzione', allowBlank:false},		// Codice abbreviato dello stato
		{name: 'TitoloTipoAzione', allowBlank:false},
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);
	
	//------------------------- ColumnModel -------------------------
	tableTipoAzione.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdTipoAzione',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			},{
				dataIndex: 'CodTipoAzione',
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
				dataIndex: 'TitoloTipoAzione',
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
				align:'right',
				width: 20
			},{
				dataIndex: 'DataIni',
				header: "Inizio Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			},{
				dataIndex: 'DataFin',
				header: "Fine Validit&agrave;",
				renderer: DCS.render.date,
				width: 14,
				editor: dataEditor
			},{
				dataIndex: 'LastUpd',
				header: "Ultima modifica",
				renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'),
				width: 20
			},{
				dataIndex: 'LastUser',
				header: "Utente",
				width: 12
			}]
		}
	);
	
	tableTipoAzione.newRecord = function() {
		return new tableTipoAzione.record({	//specify default values
			IdTipoAzione: 0,
			CodTipoAzione: '', 
			TitoloTipoAzione: '',
			Ordine: 1,
			DataIni: (new Date()).clearTime(),
			DataFin: (new Date()).clearTime(),
			LastUpd: new Date(),
			LastUser: ''
		});
	};
	
	//--------------------------------
	//  Codici di stato del contratto
	//--------------------------------
	var fn_anagTAzioni = function() {
		var gridTAzione = new EditGrid(tableTipoAzione);
	
		return gridTAzione.getGrid();
	};
}
catch (e)
{
	alert("grid_TipoAzione.js: " + e);
}
