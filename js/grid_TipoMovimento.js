try
{
	var tableTipoMovimento = new DCS.Table();
	tableTipoMovimento.name = "tipomovimento";
	tableTipoMovimento.pk = "IdTipoMovimento";
	tableTipoMovimento.expandCol = "titolo";

	//------------------------- Record -------------------------
	tableTipoMovimento.record = Ext.data.Record.create([
		{name: 'IdTipoMovimento', type: 'int', allowBlank:false},
		{name: 'CodTipoMovimento', allowBlank:false},		// Codice abbreviato dello stato
		{name: 'CategoriaMovimento', allowBlank:true},		// Categoria movimento
		{name: 'TitoloTipoMovimento', allowBlank:false},
		{name: 'CodTipoMovimentoLegacy', type: 'string'},		// Codice abbreviato dello stato vecchio
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validità
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validità
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);

	//------------------------- ColumnModel -------------------------
	tableTipoMovimento.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdTipoMovimento',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'CodTipoMovimento',
				header: "Codice",
				//resizable: false,//disable column resizing (can also use fixed = true)
				width: 10,			
				//TextField editor - for an editable field add an editor
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			}, {
				dataIndex: 'CategoriaMovimento',
				header: "Categoria",
				width: 10,			
				editor: new Ext.form.TextField({
					//specify options
					allowBlank: false //default is true (nothing entered)
				})
			},
			{
				id: 'titolo',
				dataIndex: 'TitoloTipoMovimento',
				header: 'Titolo',
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				width: 60
			}, {
				dataIndex: 'CodTipoMovimentoLegacy',
				header: "Vecchio Codice",
				editor: {
					xtype: 'textfield',
					allowBlank: false
				},
				align:'center',
				width: 20
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
				dataIndex: 'LastUser',
				header: "Utente",
				width: 12
			}]
		}
	);
	
	tableTipoMovimento.newRecord = function() {
		return new tableTipoMovimento.record({	//specify default values
			IdTipoMovimento: 0,
			CodTipoMovimento: '', 
			TitoloTipoMovimento: '',
			CodTipoMovimentoLegacy: '',
			CategoriaMovimento: '',
			Ordine:1,
			DataIni: (new Date()).clearTime(),
			DataFin: (new Date()).clearTime(),
			LastUpd: new Date(),
			LastUser: ''
		});
	};

	//--------------------------------
	//  Lista Azioni sulle pratiche
	//--------------------------------
	var fn_anagTipoMovimento= function() {
		var gridTipoMovimento = new EditGrid(tableTipoMovimento);
	
		return gridTipoMovimento.getGrid();
	};
}
catch (e)
{
	alert("grid_TipoMovimento.js: " + e);
}
