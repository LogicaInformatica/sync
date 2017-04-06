try
{
	var tableParametriSistema = new DCS.Table();
	tableParametriSistema.name = "parametrosistema";
	tableParametriSistema.pk = "IdParametro";
	tableParametriSistema.expandCol = "ValoreParametro";
	tableParametriSistema.hdnBtnDelete = true;
	tableParametriSistema.hdnBtnAdd = true;
		
	//------------------------- Record -------------------------
	tableParametriSistema.record = Ext.data.Record.create([
		{name: 'IdParametro', type: 'int', allowBlank:false},
		{name: 'CodParametro', type: 'string'},
		{name: 'TitoloParametro', type: 'string'},	
		{name: 'ValoreParametro', type: 'string', allowBlank:false},
		{name: 'lastupd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}						// Utente che ha effettuato l'ultimo "save"
	]);

	//------------------------- ColumnModel -------------------------
	tableParametriSistema.colModel = new Ext.grid.ColumnModel({
		defaults: {				// specify any defaults for each column
			sortable: true           
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel(),
			{
				align: 'right',
				dataIndex: 'IdParametro',
				header: 'ID',//header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			},{
				align: 'left',
				dataIndex: 'CodParametro',
				header: 'Codice',//header = text that appears at top of column
				hidden: false, //true to initially hide the column,
				readonly: true
			},{
				align: 'left',
				dataIndex: 'TitoloParametro',
				header: 'Titolo',//header = text that appears at top of column
				hidden: false, //true to initially hide the column,
				readonly: true
			},{
				dataIndex: 'ValoreParametro',
				header: "Valore",
				id : "ValoreParametro",
				editor: new Ext.form.TextField({
					allowBlank: false
				})
			}, {
				dataIndex: 'lastupd',
				header: "Ultima modifica",
				renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'),
				width: 80
			}, {
				dataIndex: 'LastUser',
				header: "Utente",
				width: 80
			}]
		}
	);
	
	tableParametriSistema.newRecord = function() {
		return new tableParametriSistema.record({	//specify default values
			IdParametro: 0,
			SMSAcquistati : 0,
			SMSResidui : 0,
			DataRicarica: new Date(),
			IdAgenzia : CONTEXT.IdAgenzia,
			LastUpd: new Date(),
			LastUser: CONTEXT.Userid
		});
	};

	//--------------------------------
	//  Lista categorie clienti portafoglio
	//--------------------------------
	var fn_ParametriSistema= function() {
		var gridParametriSistema = new EditGrid(tableParametriSistema);
	
		return gridParametriSistema.getGrid();
	};
}
catch (e)
{
	alert("grid_GestioneParametriSistema.js: " + e);
}
