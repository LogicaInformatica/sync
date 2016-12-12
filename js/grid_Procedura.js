// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridProceduraTab = function(obj){
	var editGrid;	
	var gridPnlProc;

	var tableProcedure = new DCS.Table();

	tableProcedure.name = "statoazione";
	tableProcedure.view = "v_procedure_azioni_statoazione where IdProcedura="+obj.IdProcedura;
	tableProcedure.pk = "IdAzione";
	//tableProcedure.updateF='updateDBView'; // da implementare se necessario: nel caso si debba toccare (.name) + di una tabella[see DCS.Table, common.php]
	
	//------------------------- Record -------------------------
	tableProcedure.record = Ext.data.Record.create([
			{name: 'IdAzione', type: 'int', allowBlank: false},
			{name: 'TitoloAzione'},
			{name: 'IdStatoAzione'},
			{name: 'Condizione'},
			{name: 'IdClasseSuccessiva', type: 'int'},
			{name: 'IdStatoRecuperoSuccessivo', type: 'int'},
			//{name: 'IdStatoRecupero', type: 'int'},
			//{name: 'AbbrStatoRecupero'},
			{name: 'ClassSucc'},
			{name: 'StatRecSucc'},
			{name: 'IdProcedura', type: 'int'}
		]);
	
		// combo Stato di recupero successivo
		var comboStatoRecSucc = new Ext.form.ComboBox({
	    	typeAhead: true,
	    	triggerAction: 'all',
	    	lazyRender: true,
			editable: true,
			allowBlank: true,
			store: DCS.Procedura.getStoreStatoRecSucc(),
	    	valueField: 'IdStatoRecuperoSuccessivo',
	    	displayField: 'StatRecSucc'
		});
	
		// combo Classificazione successiva
		var comboClasse = new Ext.form.ComboBox({
	    	typeAhead: true,
	    	triggerAction: 'all',
	    	lazyRender:true,
			editable: true,
			allowBlank: true,
			forceSelection: true,
			store: DCS.Procedura.getStoreClasse(),
	    	valueField: 'IdClasseSuccessiva',
	    	displayField: 'ClassSucc'
		});
	
		//------------------------- Columns -------------------------
		tableProcedure.colModel = new Ext.grid.ColumnModel({
			defaults: { // specify any defaults for each column
				sortable: true
			},
			columns:[new Ext.grid.CheckboxSelectionModel(), {
				/*optionally specify the aligment (default = left)*/
				align: 'right',
				dataIndex: 'IdAzione',
				header: 'ID', //header = text that appears at top of column
				hidden: true, //true to initially hide the column
				width: 5 //column width
			}, {
				dataIndex: 'TitoloAzione',
				header: "Azione",
				width: 80
			}/*, {
				dataIndex: 'AbbrStatoRecupero',
				header: 'Stato Recupero',
				//editor: comboStatoRec,
				//renderer: DCS.render.combo(comboStatoRec), // pass combo instance to reusable renderer
				width: 70
			}*/, {
				dataIndex: 'IdClasseSuccessiva',
				header: "Classe successiva",
				editor: comboClasse,
				renderer: DCS.render.combo(comboClasse), // pass combo instance to reusable renderer
				width: 70
			}, {
				dataIndex: 'IdStatoRecuperoSuccessivo',
				header: "Stato successivo",
				editor: comboStatoRecSucc,
				renderer: DCS.render.combo(comboStatoRecSucc), // pass combo instance to reusable renderer
				width: 70
			}, {
				dataIndex: 'Condizione',
				header: "Condizione",
				editor: new Ext.form.TextField({
					allowBlank: true
				}),
				align:'right',
				width: 70
			}/*, {
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
			}*/]
		});
		tableProcedure.newRecord = function(){
			return new tableProcedure.record({
				IdAzione: null,
				TitoloAzione: '',
				IdStatoAzione: null,
				Condizione: '',
				IdClasseSuccessiva: null,
				IdStatoRecuperoSuccessivo: null,// obj.IdReparto,
				IdStatoRecupero: null,
				AbbrStatoRecupero: '',
				ClassSucc: '',
				StatRecSucc: '',
				IdProcedura: null
				//DataIni: (new Date()).clearTime(),
				//DataFin: new Date(9999,11,31)
			});
		};
	
		//--------------------------------
		//  
		//--------------------------------
		editGrid = new EditGrid(tableProcedure);
		gridPnlProc = editGrid.getGrid();
		gridPnlProc.setTitle(obj.CodProcedura);
	
		return gridPnlProc;
	}
	
	
//-----------------------------------------
// Tabpanel 
//-----------------------------------------
DCS.Procedura = function() {
	var idTabs;

	var storeStatoRecSucc; 
	var storeClasse; 
	
	return {
		getStoreStatoRecSucc: function () {
			return storeStatoRecSucc;
		},
		getStoreClasse: function () {
			return storeClasse;
		},
		
		create: function(){
			idTabs = new Array();
			
			//stores per i combobox della griglia
			storeStatoRecSucc = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: "SELECT IdStatoRecupero as IdStatoRecuperoSuccessivo,CONCAT(AbbrStatoRecupero,' (',IdStatoRecupero,')') as StatRecSucc FROM statorecupero"
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdStatoRecuperoSuccessivo'},
					[{name: 'IdStatoRecuperoSuccessivo'}, {name: 'StatRecSucc'}]
        			),
				autoLoad: true
			});

			storeClasse =  new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}), 
				baseParams:{	//this parameter is passed for any HTTP request
					task: 'read',
					sql: "SELECT IdClasse as IdClasseSuccessiva,CONCAT(AbbrClasse,' (',IdClasse,')') as ClassSucc FROM classificazione"
				},
				reader:  new Ext.data.JsonReader(
					{root: 'results', id: 'IdClasseSuccessiva'},
					[{name: 'IdClasseSuccessiva'}, {name: 'ClassSucc'}]
	        		),
				autoLoad: true
			});
			
			//panel per il frmAdd
			var tabPanelProc = new Ext.TabPanel({
				enableTabScroll: true,
				id: 'tabPanProcList',
				flex: 1,
				items: [],
				listeners: {
					beforetabchange: function() {
						frmAdd.nome.setValue('');
						frmAdd.codice.setValue('');
					}
				}
			});

			//form per il tabs (+)
   			var frmAdd = new Ext.form.FormPanel({
				style: 'z-index: 30000;',
				bodyStyle: 'padding:5px 5px 0;',
				frame: true,
				id: 'frmAddID',
				hideLabels : false,
				width: 250,
				listeners: {
					render: function(pnl){pnl.el.center(pnl.ownerCt.el);}
				},
				buttons: [{
					xtype:'button', 
					text:'Aggiungi', 
					handler: function() {
							var ind = tabPanelProc.items.length-1;

							if (frmAdd.getForm().isDirty()) 
							{
								frmAdd.getForm().submit({
									url: 'server/gestioneProcedure.php',
							        method: 'POST',
							        params: {task: 'saveProc',CodProcedura:frmAdd.codice.getValue() ,TitoloProcedura:frmAdd.nome.getValue()},
							        success: function(frm, action) {
							        	if(action.result.success)
							        	{
							        		Ext.MessageBox.alert('Esito', "Procedura salvata correttamente");
							        		
							        		var nuovo = action.result;
							        		nuovo.CodProcedura=frmAdd.codice.getValue();
							        		tabPanelProc.insert(ind,new DCS.GridProceduraTab(nuovo));
							        		idTabs.push(nuovo.IdProcedura);
					    					tabPanelProc.setActiveTab(ind);
							        	}else{
							        		Ext.MessageBox.alert('Fallito', "Impossibile salvare la procedura: "+action.result.error);
							        	}
									},
									failure: function(frm, action){
										Ext.Msg.alert('Errore', action.result.error);
									},
									scope: this,
									waitMsg: 'Salvataggio in corso...'
								});
							}
							tabPanelProc.doLayout();
						},
					 scope:this
				},{
					xtype:'button', 
					text:'Chiudi', 
					handler: function() {
						tabPanelProc.setActiveTab(0);
						tabPanelProc.doLayout();
					}
				}],
				items: [{
					xtype: 'textfield',
					width: 120,
					fieldLabel: 'Nome Procedura',
					allowBlank: false,
					ref: 'nome',
					name: 'nomeProc'
				},{
					xtype: 'textfield',
					width: 120,
					fieldLabel: 'Codice Procedura',
					allowBlank: false,
					ref: 'codice',
					name: 'codProc'
				}]
			});
			
   			//caricamento tabs e dei similtabs(+,-)
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: 'SELECT IdProcedura,CodProcedura FROM procedura'
				},
				method: 'POST',
				autoload: true,
				success: function(result, request){
					eval('var resp = ' + result.responseText);
					var arr = resp.results;
					for (i = 0; i < resp.total; i++) {
						tabPanelProc.add(new DCS.GridProceduraTab(arr[i]));
						idTabs.push(arr[i].IdProcedura);
					}
					
					var vec=resp.results;
					vec[0].IdProcedura=-1;
					tabPanelProc.add({
						xtype:'panel',
						flex:1,
						id: 'pnlAddProcedura',
						title:'+',
					    layout:'absolute',
						items: [new DCS.GridProceduraTab(vec[0]), frmAdd],
						listeners: {
							render: function(pnl){pnl.el.mask();},
							resize: function(pnl){pnl.get(1).el.center(pnl.el);}
						}
					});
					tabPanelProc.add({
						xtype:'panel',
						flex:1,
						id: 'pnlDelProcedura',
						title:'-',
					    layout:'absolute',
						items: [new DCS.GridProceduraTab(vec[0])],// showListaProcedure],
						listeners: {
							activate: function(pnl){showListaProcedure();} 
						}
					});
					tabPanelProc.setActiveTab(0);
				},
				scope: this
			});
			
			return tabPanelProc;
		}
		
	}
}();
//----------------------------------------
//Visualizza lista procedure da cancellare
//----------------------------------------
function showListaProcedure()
{
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
	myMask.show();
	var pnl = new DCS.pnlProcList();
	var win = new Ext.Window({
		width: 450, height:600, minWidth: 300, minHeight: 500,
		autoHeight:true,modal: true,
	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    title: 'Procedure',
		constrain: true,
		items: [pnl]		
	});
	win.show();
	win.on({
		'close' : function () {
				Ext.getCmp('tabPanProcList').setActiveTab(0);
			}
    });
	myMask.hide();
	pnl.activation.call(pnl);
}