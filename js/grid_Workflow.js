// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridWorkflowTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	
	initComponent : function() { 
		
		var IdMain = this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
	   		
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			DCS.showProcedureDetail.create('','',IdMain);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloProcedura');
		    		vectString = vectString + '|' + Arr[k].get('IdProcedura');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneProcedure.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le procedure selezionate sono state eliminate.');
					                gstore.reload();
					            } else {
					            	if(resp!=''){
						                Ext.MessageBox.alert('Esito', resp);
						                gstore.reload();			            		
					            	}
					            }
							},
							scope: this,
							waitMsg: 'Salvataggio in corso...'
					    });
		    	    }
		    	});
	    	}else{
	    		Ext.MessageBox.alert('Conferma', "Non si è selezionata alcuna voce.");
	    	}
	    };
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAz',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdProcedura', type: 'int'},
							{name: 'TitoloProcedura'},
							{name: 'numAzioni', type: 'int'},
							{name: 'numStati', type: 'int'},
							//{name: 'numPro', type: 'int'},
							{name: 'Attiva'},
							{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdProcedura',width:10, header:'IdP',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloProcedura',	width:130,	header:'Procedura', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Attiva',width:16, exportable:false, renderer:DCS.render.spunta, header:'Attiva',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'numAzioni',width:50, header:'Azioni associate',align:'right',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'numStati',	width:50,	header:'Stati associati',align:'right',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	//{dataIndex:'numPro',	width:50,	header:'n. Profili',hidden: false, hideable: false,filterable:false,groupable:false,sortable:true},
    		        	{dataIndex:'DataFin',	width:42,	header:'DataFine',hidden:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});
		
		Ext.apply(this,{
			store: gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
				//enableNoGroups: false,
				hideGroupedColumn: true,
				getRowClass : function(record, rowIndex, p, store){
					if(rowIndex%2)
					{
						return 'grid-row-azzurrochiaro';
					}
					return 'grid-row-azzurroscuro';
				}
			}),
			columns: columns,
			sm: selM,
			listeners: {
				celldblclick : function(grid,rowIndex,columnIndex,event){
					var rec = this.store.getAt(rowIndex);
					var campo;
					switch(columnIndex){
						/*case 2: campo='TitoloProcedura';
							break;
						case 3:	campo='Attiva';
							break;*/
						case 4:	campo='numAzioni';
							break;
						case 5:	campo='numStati';
							break;
						/*case 6:	campo='numPro';
							break;*/
						default:campo='';
							break;
					}
					var elem = rec.get(campo);
					if(elem != undefined){
						//Apertura pannello azioni/stati
						if (campo=='numAzioni'){
							Ext.getCmp(IdMain).showAzioneWfDettaglio(rec.get('IdProcedura'),null,rec.get('TitoloProcedura'));
						}else{
							Ext.getCmp(IdMain).showStatiWfDettaglio(rec.get('IdProcedura'),null,rec.get('TitoloProcedura'));
						}
					}else{
						//modifica generica
						DCS.showProcedureDetail.create(rec.get('IdProcedura'),rec.get('TitoloProcedura'),IdMain);
					}					
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNpr',
							pressed: false,
							enableToggle:false,
							text: 'Nuova Procedura',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDpr',
							pressed: false,
							enableToggle:false,
							text: 'Cancella Procedura',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("ListaWorkflow"),' '
				];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridWorkflowTab.superclass.initComponent.call(this, arguments);
		this.activation();
		//this.store.load();
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);

	},
	activation: function() {
		this.store.setBaseParam('attiva','Y'); 
		var lastOpt = this.store.lastOptions;
		if (!lastOpt || lastOpt.params==undefined) {
			if (this.pagesize>0) {
				this.store.load({
					params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
						start: 0, //pass start/limit parameters for paging
						limit: this.pagesize
					}
				}); 
			} else {
				this.store.load(); 
			}
		}
	},
	//--------------------------------------------------------
    // Visualizza dettaglio azioni per la procedura
    //--------------------------------------------------------
	showAzioneWfDettaglio: function(IdPr,oldWinProcDett,titoloP,gridM)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.AzioniWrkF.create(IdPr);
		winSAz = new Ext.Window({
    		width: 1000, height:500, minWidth: 700, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Azioni associate alla procedura \''+titoloP+'\'',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winSAz});
		winSAz.show();
		myMask.hide();
		pnl.activation.call(pnl);
		winSAz.on({
			'close' : function () {
					if(oldWinProcDett!=null)
					{	
						Ext.getCmp(oldWinProcDett).close();
						DCS.showProcedureDetail.create(IdPr,titoloP,gridM);
					}
				}
		});
    },
	//--------------------------------------------------------
    // Visualizza dettaglio stati per la procedura
    //--------------------------------------------------------
	showStatiWfDettaglio: function(IdPr,oldWinProcDett,titoloP,gridM)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.StatiWrkF.create(IdPr);
		winS = new Ext.Window({
    		width: 450, height:300, minWidth: 400, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Stati associati alla procedura \''+titoloP+'\'',
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winS});
		winS.show();
		myMask.hide();
		pnl.activation.call(pnl);
		winS.on({
			'close' : function () {
					Ext.getCmp(oldWinProcDett).close();
					DCS.showProcedureDetail.create(IdPr,titoloP,gridM);
				}
		});
    }
});

DCS.ProceduraWrkF = function(){

	return {
		create: function(){
			var subtitle = '<br><span class="subtit">Le procedure di workflow sono definite essenzialmente tramite stati (col. 3) e azioni (col. 2);'
				+'<br>con un doppio click sulle rispettive colonne si accede alle corrispondenti liste di dettaglio.</span>';
			var gridGestWF = new DCS.GridWorkflowTab({
				titlePanel: 'Lista procedure di workflow'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readPMainGrid"
			});

			return gridGestWF;
		}
	};
	
}();