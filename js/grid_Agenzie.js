// Crea namespace DCS
Ext.namespace('DCS');

DCS.AnagAgenzie = function(){
	var win;
	var key;

	var editGrid;	
	var gridPnlAgenzie;
	

	var tableAgenzie = new DCS.Table();
	
	tableAgenzie.name = "v_reparto";
	tableAgenzie.pk = "IdReparto";
	tableAgenzie.expandCol = "referente";
	
	//------------------------- Record -------------------------
	tableAgenzie.record = Ext.data.Record.create([
		{name: 'IdReparto', type: 'int', allowBlank: false},
		{name: 'TitoloCompagnia'},
		{name: 'CodUfficio'},
		{name: 'TitoloUfficio'},
		{name: 'NomeReferente'},
		{name: 'Telefono'},
		{name: 'Fax'},
		{name: 'EmailReferente'},
		{name: 'EmailFatturazione'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'}
	]);

	//------------------------- ColumnModel -------------------------
	tableAgenzie.colModel = new Ext.grid.ColumnModel({
		defaults: { // specify any defaults for each column
			sortable: true
		},
		columns: [new Ext.grid.CheckboxSelectionModel(), {
			/*optionally specify the aligment (default = left)*/
			align: 'right',
			dataIndex: 'IdReparto',
			header: 'ID',//header = text that appears at top of column
			hidden: true, //true to initially hide the column
			id: 'classIdReparto',
			width: 5 //column width
		}, {
			dataIndex: 'TitoloCompagnia',
			header: 'Compagnia',//header = text that appears at top of column
			id: 'classIdCompagnia',
			width: 20 //column width
		}, {
			dataIndex: 'CodUfficio',
			header: "Codice",
			id: 'idCodUfficio',
			//resizable: false,//disable column resizing (can also use fixed = true)
			width: 10
		}, {
			dataIndex: 'TitoloUfficio',
			header: 'Ufficio',
			width: 60
		}, {
			id: 'referente',
			dataIndex: 'NomeReferente',
			header: "Referente",
			width: 20
		}, {
			dataIndex: 'Telefono',
			header: "Telefono",
			width: 20
		}, {
			dataIndex: 'Fax',
			header: "Fax",
			width: 20
		}, {
			dataIndex: 'EmailReferente',
			header: "E-mail Referente",
			width: 20
		}, {
			dataIndex: 'EmailFatturazione',
			header: "E-mail Fatturazione",
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
		}
/*
		 , {
			dataIndex: 'LastUpd',
			header: "Ultima modifica",
			renderer: Ext.util.Format.dateRenderer('d/m/Y H:i:s'),
			width: 20
		}, {
			dataIndex: 'LastUser',
			header: "Utente",
			width: 12
		} 
*/
		]
	});
	
	tableAgenzie.newRecord = function(){
		return new tableAgenzie.record({ //specify default values
			IdReparto: 0,
			IdTipoReparto: 2,
			IdUfficioParent: '',
			IdCompagnia: '',
			CodUfficio: '',
			TitoloUfficio: '',
			NomeReferente: '',
			Telefono: '',
			Fax: '',
			EmailReferente: '',
			EmailFatturazione: '',
			DataIni: (new Date()).clearTime(),
			DataFin: (new Date()).clearTime(),
			LastUpd: new Date(),
			LastUser: '',
			
			TitoloCompagnia: '',
			NomeTitolare: '',
			Indirizzo: '',
			CAP: '',
			Localita: '',
			SiglaProvincia: '',
			TelefonoTitolare: '',
			FaxTitolare: '',
			EmailTitolare: ''
		});
	};
	
	var dsCompagnia = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/edit_agenzie.php',
			method: 'POST'
		}),   
		baseParams:{task: "readCompagnie"},//this parameter is passed for any HTTP request
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdCompagnia'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'IdCompagnia'},
					{name: 'TitoloCompagnia'},
					{name: 'SiglaProvincia'},
					{name: 'NomeTitolare'},
					{name: 'Indirizzo'},
					{name: 'CAP'},
					{name: 'Localita'},
					{name: 'TelefonoTitolare'}, 
					{name: 'FaxTitolare'},
					{name: 'EmailTitolare'}
				]
            ),
			sortInfo:{field: 'TitoloCompagnia', direction: "ASC"}
		}
	);//end dsIndustry        

	//--------------------------------
	//  
	//--------------------------------
	var formAgenzie = new Ext.form.FormPanel({
		autoHeight: true,
		frame: true,
		bodyStyle: 'padding:5px 5px 0',
		border: false,
		trackResetOnLoad: true,
		reader: new Ext.data.JsonReader({
			root: 'results'
		}, ['IdCompagnia', 'TitoloCompagnia', 'NomeTitolare', 'Indirizzo', 'CAP', 'Localita', 'SiglaProvincia', 'TelefonoTitolare', 
			 'FaxTitolare', 'EmailTitolare', 'IdReparto', 'CodUfficio', 'TitoloUfficio', 'NomeReferente', 'Telefono', 'Fax', 
			 'EmailReferente', 'EmailFatturazione', 'LastUpd']),
		items: [{
			xtype: 'fieldset',
			ref: '../fsCompagnia',
			autoHeight: true,
			layout: 'column',
			items: [{
				xtype: 'panel',
				layout: 'form',
				labelWidth: 110,
				columnWidth: .6,
				items: [{
					xtype: 'combo',
					fieldLabel: 'Compagnia',
					name: 'TitoloCompagnia',
					anchor: '95%',
					hidden: true,
 
					//create a dropdown based on server side data (from db)
					//if we enable typeAhead it will be querying database
					//so we may not want typeahead consuming resources
					typeAhead: false, 
					triggerAction: 'all',
					
					//By enabling lazyRender this prevents the combo box
					//from rendering until requested
					lazyRender: true,	//should always be true for editor

					//where to get the data for our combobox
					store: dsCompagnia,
					
					//the underlying data field name to bind to this
					//ComboBox (defaults to undefined if mode = 'remote'
					//or 'text' if transforming a select)
					displayField: 'TitoloCompagnia',
					
					//the underlying value field name to bind to this
					//ComboBox
					valueField: 'IdCompagnia',
					listeners: {
						select : function(combo, record, index) {
				            // By default, "this" will be the object that fired the event.
							formAgenzie.getForm().loadRecord(record);
				        }
				    }
				}, {
					xtype: 'textfield',
					fieldLabel: 'IdCompagnia',
					name: 'IdCompagnia',
					anchor: '95%',
					hidden: true
				}, {
					xtype: 'textfield',
					fieldLabel: 'Titolare',
					name: 'NomeTitolare',
					anchor: '95%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Indirizzo',
					name: 'Indirizzo',
					anchor: '95%'
				}, {
					xtype: 'compositefield',
					fieldLabel: '',
					anchor: '95%',
					items: [{
						xtype: 'textfield',
						width: 50,
						name: 'CAP'
					}, {
						xtype: 'textfield',
						flex: 1,
						name: 'Localita'
					}, {
						xtype: 'combo',
						width: 45,
						name: 'SiglaProvincia',
						forceSelection: true,
						editable: true,
						mode: 'local',
						displayField: 'sigla',
						valueField: 'sigla',
						lazyInit: false,
						value: 'SiglaProvincia',
						store: DCS.Store.dsProvince,
						triggerAction: 'all',
						autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '2'},
						style : {textTransform: "uppercase"}

					}]
				}]
			}, {
				xtype: 'panel',
				layout: 'form',
				labelWidth: 70,
				columnWidth: .4,
				items: [{
					xtype: 'textfield',
					fieldLabel: 'E-mail',
					name: 'EmailTitolare',
					vtype: 'email',
					anchor: '97%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Telefono',
					name: 'TelefonoTitolare',
					anchor: '97%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Fax',
					name: 'FaxTitolare',
					anchor: '97%'
				}]
			}]
		}, {
			xtype: 'fieldset',
			autoHeight: true,
			layout: 'column',
			items: [{
				xtype: 'panel',
				layout: 'form',
				labelWidth: 110,
				columnWidth: .6,
				items: [{
					xtype: 'textfield',
					fieldLabel: 'Reparto',
					name: 'TitoloUfficio',
					allowBlank: false,
					anchor: '95%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Referente',
					name: 'NomeReferente',
					anchor: '95%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'E-mail Referente',
					name: 'EmailReferente',
					anchor: '95%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'E-mail fatturazione',
					name: 'EmailFatturazione',
					anchor: '95%'
				}]
			}, {
				xtype: 'panel',
				layout: 'form',
				labelWidth: 70,
				columnWidth: .4,
				items: [{
					xtype: 'textfield',
					fieldLabel: 'Codice',
					name: 'CodUfficio',
					allowBlank: false,
					anchor: '97%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Telefono',
					name: 'Telefono',
					anchor: '97%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Fax',
					name: 'Fax',
					anchor: '97%'
				}, {
					xtype: 'textfield',
					fieldLabel: 'LastUpd',
					name: 'LastUpd',
					anchor: '97%',
					hidden: true
				}]
			}]
		}],
		buttons: [{
			text: 'Salva',
			handler: function() {
				if (formAgenzie.getForm().isDirty()) {	// qualche campo modificato
					formAgenzie.getForm().submit({
						url: 'server/edit_agenzie.php',
						method: 'POST',
						params: {task: key==0?'insert':'update', IdReparto: key},
						success: function(){
							editGrid.refresh();
							win.hide();
						},
						failure: function(frm, action){
							Ext.Msg.alert('Errore', action.result.error);
						},
						scope: this,
						waitMsg: 'Salvataggio in corso...'
					});
				} else
					win.hide();
			},
			scope: this
		}, {
			text: 'Annulla',
			handler: function(){
				if (formAgenzie.getForm().isDirty()) {
					Ext.Msg.confirm('', 'I valori sono stati modificati, uscire senza salvare?', function(btn, text){
    					if (btn == 'yes'){
				        	win.hide();
					    }
					});
				} else
					win.hide();
			},
			scope: this
		}]
	});

	var showForm = function(){
		if (!win) {
			win = new Ext.Window({
				modal: true,
				width: 800,
				height: 370,
				minWidth: 800,
				minHeight: 370,
				layout: 'fit',
				plain: true,
				constrain: true,
				bodyStyle: 'padding:5px;',
				title: 'Dettaglio agenzia',
				items: formAgenzie,
				closable: false
			});
		}
	
		var frm = formAgenzie.getForm();
		var compagnia = frm.findField('TitoloCompagnia');
		var compTitle = compagnia.getRawValue();

		var reparto = frm.findField('TitoloUfficio');
		var repTitle = reparto.getRawValue();

		if (compTitle == '') {
			compagnia.setVisible(true);
			compTitle = 'Compagnia';
			repTitle = 'Reparto';
			win.setHeight(396);
		} else {
			compagnia.setVisible(false);
			win.setHeight(370);
		}
		win.setWidth(800);

		formAgenzie.get(0).setTitle('<span style="font-size:1.2em">'+compTitle+'</span>');
		formAgenzie.get(1).setTitle('<span style="font-size:1.2em">'+repTitle+'</span>');
		win.show();
	};
	
	
	var editRecord = function(){
		key = gridPnlAgenzie.selModel.selections.keys[0];

		formAgenzie.load({
			url: 'server/edit_agenzie.php',
			method: 'POST',
			params: {task: 'read', id: key},
			success: showForm,
			scope: this,
			waitMsg: 'Caricamento in corso...'
		});
	};


	var newRecord = function() {
		formAgenzie.getForm().loadRecord(tableAgenzie.newRecord.call());
		key = 0;
		showForm.call(gridPnlAgenzie);
	};

	
	

	return {
		create: function(){
			editGrid = new EditGrid_Form(tableAgenzie);	
			gridPnlAgenzie = editGrid.getGrid();

			gridPnlAgenzie.editBtn.setHandler(editRecord, gridPnlAgenzie);
	
			gridPnlAgenzie.addBtn.setHandler(newRecord, gridPnlAgenzie);
	
	
			gridPnlAgenzie.on('rowdblclick', function(grid, rowIndex, event){
				grid.selModel.clearSelections();
				grid.selModel.selectRow(rowIndex);
				editRecord.call(grid);
			}, gridPnlAgenzie);

		/*	return new Ext.Panel({
				title: 'Agenzie',
				items: [gridPnlAgenzie]
			}) */
			gridPnlAgenzie.setTitle('Agenzie di recupero');
			return gridPnlAgenzie;
		}
	};
}();