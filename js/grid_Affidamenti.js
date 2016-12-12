// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridRegoleAff = function(obj){
	var editGrid;	
	var gridPnlAffidamenti;

	var tableAffidamenti = new DCS.Table();
	
	tableAffidamenti.name = "regolaassegnazione";
	tableAffidamenti.view = "regolaassegnazione where TipoAssegnazione=2 and IdReparto="+obj.IdReparto;
	tableAffidamenti.pk = "IdRegolaAssegnazione";
	
	//------------------------- Record -------------------------
	tableAffidamenti.record = Ext.data.Record.create([
		{name: 'IdRegolaAssegnazione', type: 'int', allowBlank: false},
		{name: 'DurataAssegnazione'},
		{name: 'IdFamiglia'},
		{name: 'IdClasse'},
		{name: 'IdArea', type: 'int'},
		{name: 'Ordine', type: 'int'},
		{name: 'ImportoDa', type: 'float'},
		{name: 'ImportoA', type: 'float'},
		{name: 'IdTipoCliente', type: 'int'},
		{name: 'IdReparto', type: 'int'},
		{name: 'TipoDistribuzione'},
		{name: 'TipoAssegnazione'},
		{name: 'GiorniFissiInizio'},
		{name: 'GiorniFissiFine'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'}
	]);

	// combo classificazione
	var comboClass = new Ext.form.ComboBox({
    	typeAhead: true,
    	triggerAction: 'all',
    	lazyRender: true,
		editable: false,
		store: DCS.RegoleAffidamento.getStoreClass(),
    	valueField: 'IdClasse',
    	displayField: 'AbbrClasse'
	});

	// combo classificazione
	var comboProd = new Ext.form.ComboBox({
    	typeAhead: true,
    	triggerAction: 'all',
    	lazyRender:true,
		editable: true,
		allowBlank: true,
		forceSelection: true,
		store: DCS.RegoleAffidamento.getStoreProd(),
    	valueField: 'IdFamiglia',
    	displayField: 'TitoloFamiglia'
	});

	// combo area
	var comboArea = new Ext.form.ComboBox({
    	typeAhead: true,
    	triggerAction: 'all',
    	lazyRender:true,
		editable: true,
		allowBlank: true,
		forceSelection: true,
		store: DCS.RegoleAffidamento.getStoreArea(),
    	valueField: 'IdArea',
    	displayField: 'TitoloArea'
	});
	
	var comboDistr = new Ext.form.ComboBox({
	    typeAhead: true,
	    triggerAction: 'all',
	    lazyRender:true,
		editable: false,
	    mode: 'local',
	    store: new Ext.data.ArrayStore({
	        id: 0,
	        fields: ['id','descr'],
	        data: [['I', 'Identico'], ['C', 'Carico']]
	    }),
	    valueField: 'id',
	    displayField: 'descr'
	});

	//------------------------- ColumnModel -------------------------
	tableAffidamenti.colModel = new Ext.grid.ColumnModel({
		defaults: { // specify any defaults for each column
			sortable: true
		},
		columns: [new Ext.grid.CheckboxSelectionModel(), {
			/*optionally specify the aligment (default = left)*/
			align: 'right',
			dataIndex: 'IdRegolaAssegnazione',
			header: 'ID', //header = text that appears at top of column
			hidden: true, //true to initially hide the column
			width: 5 //column width
		}, {
			dataIndex: 'IdClasse',
			header: "Class.",
			editor: comboClass,
			renderer: DCS.render.combo(comboClass), // pass combo instance to reusable renderer
			width: 70
		}, {
			dataIndex: 'IdFamiglia',
			header: 'Prodotto',
			editor: comboProd,
			renderer: DCS.render.combo(comboProd), // pass combo instance to reusable renderer
			width: 70
		}, {
			dataIndex: 'IdArea',
			header: "Area",
			editor: comboArea,
			renderer: DCS.render.combo(comboArea), // pass combo instance to reusable renderer
			width: 70
		}, {
			dataIndex: 'ImportoDa',
			header: "Importo Da",
			editor: new Ext.form.NumberField({
				allowBlank: true,
				allowNegative: false
			}),
			align:'right',
			width: 70
		}, {
			dataIndex: 'ImportoA',
			header: "Importo A",
			editor: new Ext.form.NumberField({
				allowBlank: true,
				allowNegative: false
			}),
			align:'right',
			width: 70
		}, {
			dataIndex: 'TipoDistribuzione',
			header: "Distribuzione",
			editor: comboDistr,
			renderer: DCS.render.combo(comboDistr), // pass combo instance to reusable renderer
			width: 60
		}, {
			dataIndex: 'DurataAssegnazione',
			header: 'Durata',
			editor: new Ext.form.NumberField({
				allowBlank: false,
				allowNegative: false
			}),
			align:'right',
			width: 50
		}, {
			dataIndex: 'GiorniFissiInizio',
			header: "Giorni Fissi Inizio",
			editor: new Ext.form.TextField({
				allowBlank: true
			}),
			width: 90
		}, {
			dataIndex: 'GiorniFissiFine',
			header: "Giorni Fissi Fine",
			editor: new Ext.form.TextField({
				allowBlank: true
			}),
			width: 90
		}, {
			dataIndex: 'DataIni',
			header: "Inizio Validit&agrave;",
			renderer: DCS.render.date,
			width: 65,
			editor: dataEditor
		}, {
			dataIndex: 'DataFin',
			header: "Fine Validit&agrave;",
			renderer: DCS.render.date,
			width: 65,
			editor: dataEditor
		}]
	});
	
	tableAffidamenti.newRecord = function(){
		return new tableAffidamenti.record({
			IdRegolaAssegnazione: 0,
			DurataAssegnazione: 30,
			IdTipoCliente: null,
			IdFamiglia: null,
			IdClasse: null,
			IdReparto: obj.IdReparto,
			IdUtente: null,
			IdArea: null,
			Ordine: null,
			ImportoDa: null,
			ImportoA: null,
			TipoDistribuzione: 'C',
			TipoAssegnazione: '2',
			GiorniFissiInizio: null,
			GiorniFissiFine: null,
			DataIni: (new Date()).clearTime(),
			DataFin: new Date(9999,11,31)
		});
	};

	//--------------------------------
	//  
	//--------------------------------
	editGrid = new EditGrid(tableAffidamenti);
	gridPnlAffidamenti = editGrid.getGrid();
	gridPnlAffidamenti.setTitle(obj.TitoloUfficio);

	return gridPnlAffidamenti;
}	
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.RegoleAffidamento = function() {
	var idTabs;

	var storeClass; 
	var storeProd; 
	var storeArea; 
	
	function getSql() {
		var sql = "SELECT r.IdReparto, r.TitoloUfficio"
			+" FROM reparto r LEFT JOIN compagnia c on c.IdCompagnia = r.IdCompagnia"
			+" WHERE c.IdTipoCompagnia=2 and now() between r.DataIni and r.DataFin";
		if (idTabs.length>0) {
			sql += " AND r.IdReparto not in (";
			for (i=0; i<idTabs.length; i++) {
				sql += idTabs[i]+(i+1==idTabs.length?")":",");
			}
		}
		return sql;
	}

	return {
		getStoreClass: function () {
			return storeClass;
		},
		getStoreProd: function () {
			return storeProd;
		},
		getStoreArea: function () {
			return storeArea;
		},
		
		create: function(){
			var recCombo;
			idTabs = new Array();
			
			storeClass = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: "SELECT IdClasse, AbbrClasse FROM classificazione c where IFNULL(FlagNoAffido,'N')='N' and now() between DataIni and DataFin order by ordine"
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdClasse'},
					[{name: 'IdClasse'}, {name: 'AbbrClasse'}]
        			),
				autoLoad: true
			});

			storeProd = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: "SELECT IdFamiglia, TitoloFamiglia FROM famigliaprodotto f where now() between DataIni and DataFin order by IdFamigliaParent, TitoloFamiglia"
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdFamiglia'},
					[{name: 'IdFamiglia'}, {name: 'TitoloFamiglia'}]
	        		),
				autoLoad: true
			});
			
			storeArea =  new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: "SELECT IdArea, TitoloArea FROM area a where TipoArea='R' and now() between DataIni and DataFin order by 2"
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdArea'},
					[{name: 'IdArea'}, {name: 'TitoloArea'}]
	        		),
				autoLoad: true
			});

			var tabPanelAff = new Ext.TabPanel({
				enableTabScroll: true,
				flex: 1,
				items: [],
				listeners: {
					beforetabchange: function() {
						frmAdd.combo.clearValue();
						recCombo = undefined;
					}
				}
			});

			var dsAgenzia = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read'
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdReparto'},
					[{name: 'IdReparto'}, {name: 'TitoloUfficio'}]
			        )/*,
				autoLoad: true*/
			});     

			var frmAdd = new Ext.form.FormPanel({
				style: 'z-index: 30000;',
				bodyStyle: 'padding:5px 5px 0;',
				frame: true,
				hideLabels : true,
				width: 400,
				listeners: {
					render: function(pnl){pnl.el.center(pnl.ownerCt.el);}
				},
				buttons: [{
					xtype:'button', 
					text:'Aggiungi', 
					handler: function() {
						if (recCombo != undefined) {
							idTabs.push(recCombo.IdReparto);
							dsAgenzia.setBaseParam("sql",getSql());
							dsAgenzia.load();
							
							var name = frmAdd.combo.getValue();
							var ind = tabPanelAff.items.length-1;
							tabPanelAff.insert(ind,new DCS.GridRegoleAff(recCombo));
							tabPanelAff.setActiveTab(ind);
							tabPanelAff.doLayout();
							}
						},
					 scope:this}],
				items: [{
					xtype: 'displayfield',
					style: 'vertical-align:bottom',
					value: '<br>Selezionare la Compagnia da aggiungere:',
					height: 40
				},{
					xtype: 'combo',
					ref: 'combo',
					editable: false,

//					fieldLabel: 'Compagnia',
					name: 'TitoloCompagnia',
					anchor: '100%',
 
					//create a dropdown based on server side data (from db)
					//if we enable typeAhead it will be querying database
					//so we may not want typeahead consuming resources
					typeAhead: false, 
					triggerAction: 'all',
					
					//By enabling lazyRender this prevents the combo box
					//from rendering until requested
					lazyRender: true,	//should always be true for editor

					//where to get the data for our combobox
					store: dsAgenzia,
					
					//the underlying data field name to bind to this
					//ComboBox (defaults to undefined if mode = 'remote'
					//or 'text' if transforming a select)
					displayField: 'TitoloUfficio',
					
					//the underlying value field name to bind to this
					//ComboBox
					valueField: 'IdReparto',
					listeners: {
						select : function(combo, record, index) {
							recCombo = record.data;
				        }
				    }
				}]
			});
			
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: 'SELECT distinct re.TitoloUfficio, re.IdReparto FROM regolaassegnazione r join reparto re on r.IdReparto=re.IdReparto where TipoAssegnazione=2 order by 1'
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var resp = ' + result.responseText);
					var arr = resp.results;
					for (i = 0; i < resp.total; i++) {
						tabPanelAff.add(new DCS.GridRegoleAff(arr[i]));
						idTabs.push(arr[i].IdReparto);
					}
					
					dsAgenzia.setBaseParam("sql",getSql());

					tabPanelAff.add({
						xtype:'panel',
						flex:1,
						id: 'pnlAddAgenzia',
						title:'+',
					    layout:'absolute',
						items: [new DCS.GridRegoleAff(-1), frmAdd],
						listeners: {
							render: function(pnl){pnl.el.mask();},
							resize: function(pnl){pnl.get(1).el.center(pnl.el);}
						}
					});
					tabPanelAff.setActiveTab(0);
				},
				scope: this
			});
			
			return tabPanelAff;
		}
		
	}
}();