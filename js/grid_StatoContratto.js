var tableStatoContratto = new DCS.Table();
tableStatoContratto.name = "statocontratto";
tableStatoContratto.pk = "IdStatoContratto";
tableStatoContratto.expandCol = "titolo";

//------------------------- Record -------------------------
tableStatoContratto.record = Ext.data.Record.create([
	{name: 'IdStatoContratto', type: 'int', allowBlank:false},
	{name: 'CodStatoContratto', allowBlank:false},		// Codice abbreviato dello stato
	{name: 'TitoloStatoContratto', allowBlank:false},
	{name: 'CodStatoLegacy', allowBlank:false},			// Codice stato corrispondente sul sistema legacy (se applicabile)
	{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
	{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
	{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
	{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
]);

//------------------------- ColumnModel -------------------------
tableStatoContratto.colModel = new Ext.grid.ColumnModel({
	defaults: {				// specify any defaults for each column
		sortable: true           
	},
	columns: [
		new Ext.grid.CheckboxSelectionModel(),
		{
			/*optionally specify the aligment (default = left)*/
			align: 'right',
			dataIndex: 'IdStatoContratto',
			header: 'ID',//header = text that appears at top of column
			hidden: true, //true to initially hide the column
			id: 'classIdStatoContratto',
			width: 5 //column width
		}, {
			dataIndex: 'CodStatoContratto',
			header: "Codice",
			id: 'idCodStatoContratto',
			//resizable: false,//disable column resizing (can also use fixed = true)
			width: 10,			
			//TextField editor - for an editable field add an editor
			editor: new Ext.form.TextField({
				//specify options
				allowBlank: false //default is true (nothing entered)
			})
		}, {
			id: 'titolo',
			dataIndex: 'TitoloStatoContratto',
			header: 'Titolo',
			editor: {
				xtype: 'textfield',
				allowBlank: false
			},
			width: 60
		}, {
			dataIndex: 'CodStatoLegacy',
			header: "Codice Legacy",
			editor: {
				xtype: 'textfield',
				allowBlank: false
			},
			width: 20
		}, {
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
			dataIndex: 'LastUser',
			header: "Utente",
			width: 12
		}]
	}
);

tableStatoContratto.newRecord = function() {
	return new tableStatoContratto.record({	//specify default values
		IdStatoContratto: 0,
		CodStatoContratto: '', 
		TitoloStatoContratto: '',
		CodStatoLegacy: '',
		DataIni: (new Date()).clearTime(),
		DataFin: (new Date()).clearTime(),
		LastUpd: new Date(),
		LastUser: ''
	});
}

//--------------------------------
//  Codici di stato del contratto
//--------------------------------
var fn_anagStati = function() {
	var gridStatoContratto = new EditGrid(tableStatoContratto);

	return gridStatoContratto.getGrid();
}

