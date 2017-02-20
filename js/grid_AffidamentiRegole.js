// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridAssMainTab = Ext.extend(Ext.grid.GridPanel, {
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
			/*var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			DCS.showProcedureDetail.create('','',IdMain);
			myMask.hide();*/
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	/*var Arr = selM.getSelections();
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
	    	}*/
	    };
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAss',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdReparto', type: 'int'},
							{name: 'TitoloUfficio'},
							{name: 'NumTipAff', type: 'int'},
							{name: 'NumRegAff', type: 'int'},
							{name: 'NumRegAffOpe', type: 'int'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdReparto',width:10, header:'IdA',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloUfficio',	width:130,	header:'Agenzia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'NumTipAff',width:50, header:'Regole provvigionali',align:'right',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'NumRegAff',	width:50,	header:'Regole affidamento all\'agenzia',align:'right',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'NumRegAffOpe',	width:50,	header:'Regole assegnazione agli operatori',align:'right',hidden: false, hideable: false,filterable:false,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
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
						case 3:	campo='NumTipAff';
							break;
						case 4:	campo='NumRegAff';
							break;
						case 5:	campo='NumRegAffOpe';
							break;
						default:campo='';
							break;
					}
					if(campo!='')
					{
						Ext.getCmp(IdMain).showGrigliaAssociazioneGenerica(rec.get('IdReparto'),rec.get('TitoloUfficio'),campo,gstore);
					}
					/*var elem = rec.get(campo);
					if(elem != undefined)
					{
						//Apertura pannello della colonna selezionata
						//Ext.getCmp(IdMain).showAzioneWfDettaglio(rec.get('IdProcedura'),null,rec.get('TitoloProcedura'));
					}	*/				
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', /*{xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNpr',
							pressed: false,
							enableToggle:false,
							text: 'Nuova agenzia',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDpr',
							pressed: false,
							enableToggle:false,
							text: 'Cancella agenzia',
							handler: delRecord
							},
	                '-',*/ {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("RegoleAffidamento"),' '
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

		DCS.GridAssMainTab.superclass.initComponent.call(this, arguments);
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
    // Visualizza griglia per la colonna selezionata
    //--------------------------------------------------------
	showGrigliaAssociazioneGenerica: function(IdRep,titoloR,campo,Gstore)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.AssGenerica.create(IdRep,campo,titoloR);
		var titleWin='';
		switch(campo){
			case 'NumTipAff':titleWin='Regole calcolo provvigioni per l\'agenzia \''+titoloR+'\'';
				break;
			case 'NumRegAff':titleWin='Regole di affidamento per l\'agenzia \''+titoloR+'\'';
				break;
			case 'NumRegAffOpe':titleWin='Regole assegnazione operatore per l\'agenzia \''+titoloR+'\'';
				break;
			default:titleWin='';
				break;
		}
		winSAz = new Ext.Window({
    		width: 1000, height:500, minWidth: 700, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: titleWin,
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winSAz});
		winSAz.show();
		myMask.hide();
		pnl.activation.call(pnl);
		winSAz.on({
			'close' : function () {
					/*if(oldWinProcDett!=null)
					{	
						//Ext.getCmp(oldWinProcDett).close();
						//DCS.showProcedureDetail.create(IdPr,titoloP,gridM);
					}*/
					Gstore.reload();
				}
		});
    }
});

DCS.AffidamentiRegole = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>Ad ogni agenzia di recupero sono assegnabili un certo numero di regole di calcolo delle provvigioni (col. 2), di regole di affidamento (col. 3)'
				+'<br>e di regole di assegnazione (col. 4), tra loro collegate. Con un doppio click su ciascuna colonna, si accede alla lista delle regole corrispondenti</span>';

			var gridAssegnazioni = new DCS.GridAssMainTab({
				titlePanel: 'Lista delle regole di affidamento alle agenzie'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readAssMainGrid"
			});

			return gridAssegnazioni;
		}
	};
	
}();