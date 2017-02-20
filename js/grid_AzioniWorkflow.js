// Crea namespace DCS
Ext.namespace('DCS');

var winS;

DCS.GridAzioniWorkflowTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	idProc:'',

	initComponent : function() { 
		
		var procAss=this.idProc||'';
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
			//Ext.getCmp(IdMain).showDettaglioAzioneCE();
			showAzioneDetail(0,gstore,0,procAss);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloAzione');
		    		vectString = vectString + '|' + Arr[k].get('IdAzione');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneProcedure.php',
					        method: 'POST',
					        params: {task: 'deleteAzione',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le azioni selezionate sono state eliminate.');
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

		var fields = [{name: 'IdAzione', type: 'int'},
							{name: 'TitoloAzione'},
							{name: 'CodAzione'},
							{name: 'condizione'},
							{name: 'Ordine'},
							{name: 'Attiva'},
							{name: 'TipoFormAzione'},
							{name: 'tipoazione'},
							{name: 'FlagMultipla'},
							{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdAzione',width:10, header:'IdA',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloAzione',	width:130,	header:'Nome azione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'condizione',	width:130,	header:'Condizione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	//{dataIndex:'CodAzione',	width:70,	header:'Codice', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Ordine',width:50, header:'Ordine',align:'right',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Attiva',width:30, exportable:false, renderer:DCS.render.spunta, header:'Attiva',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	//{dataIndex:'TipoFormAzione',	width:100,	header:'Tipo azione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'tipoazione',	width:100,	header:'Tipo azione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'FlagMultipla',width:30, exportable:false, renderer:DCS.render.spunta, header:'Multipla',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'DataFin',	width:42,	xtype:'datecolumn',format:'d/m/y',header:'DataFine',hidden:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, idProc:this.idProc},
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
					/*var campo;
					switch(columnIndex){
						case 2: campo='TitoloAzione';
							break;
						case 4:	campo='Ordine';
							break;
						case 5:	campo='Attiva';
							break;
						case 6:	campo='TipoFormAzione';
							break;
						case 7:	campo='FlagMultipla';
							break;
						default:campo='';
							break;
					}
					var elem = rec.get(campo);
					if(elem != undefined){
						//Apertura pannello modifica
						console.log("Mod");
					}else{
						//nessuna modifica da fare
						console.log("no mod");
					}*/	
					showAzioneDetail(rec.get('IdAzione'),gstore,rowIndex,procAss,rec.get('TitoloAzione'));
					//Ext.getCmp(IdMain).showDettaglioAzioneCE(rec.get('IdAzione'));
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:false, 
						id: 'bNAzpr',
						pressed: false,
						enableToggle:false,
						text: 'Aggiungi azione',
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDAzpr',
						pressed: false,
						enableToggle:false,
						text: 'Cancella azione',
						handler: delRecord
						},
					'-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("AzioniWorkflow"),' '
				];
		
		var bbarItems = [
					'->', {type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
								this.store.load();
							}, scope: this}
				];
				
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		
		Ext.apply(this, {
	        bbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:bbarItems
	        })		
		});

		DCS.GridAzioniWorkflowTab.superclass.initComponent.call(this, arguments);
		this.activation();
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
	}
});

DCS.AzioniWrkF = function(){

	return {
		create: function(idP){
			var gridGestAzWF = new DCS.GridAzioniWorkflowTab({
				titlePanel: '',
				flex: 1,
				task: "readAzProcGrid",
				idProc:idP
			});

			return gridGestAzWF;
		}
	};
	
}();