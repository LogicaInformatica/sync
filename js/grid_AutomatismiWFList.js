// Crea namespace DCS
Ext.namespace('DCS');


DCS.GridAutListTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	listStore:null,
	IdAzione:'',
	initComponent : function() { 
		var IdMain = this.getId();
		var MainStore = this.listStore;
		var azioneID = this.IdAzione;
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});

		var fields = [{name: 'IdAutomatismo', type: 'int'},
							{name: 'TitoloAutomatismo'},
							{name: 'TipoNominativo'},
							{name: 'TipoAutomatismo'}];

    	var columns = [selM,
    	               	{dataIndex:'IdAutomatismo',width:10, header:'IdAut',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloAutomatismo',	width:300,	header:'Nome automatismo', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TipoNominativo',	width:70,	header:'Tipologia', hideable: false,filterable:true,groupable:false,sortable:true}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, idAzione:this.IdAzione},
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
		
		var collAuto = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0)
	    	{
		    	for(var k=0;k<Arr.length;k++){
		    		vectString = vectString + '|' + Arr[k].get('IdAutomatismo');
		    	}
    	    	Ext.Ajax.request({
			        url: 'server/gestioneProcedure.php',
			        method: 'POST',
			        params: {task: 'linkAutomatismoAz',vect: vectString, idAzione:azioneID},
			        success: function(obj) {
			            var resp = obj.responseText;
			            if (resp == '' && vectString!='') {
			                Ext.MessageBox.alert('Esito', 'Gli automatismi selezionati sono stati collegati.');
			                MainStore.reload();
			                Ext.getCmp('ListAutomAzWF').close();
			            } else {
			            	if(resp!=''){
				                Ext.MessageBox.alert('Esito', resp);
			            	}
			            }
					},
					scope: this,
					waitMsg: 'Salvataggio in corso...'
			    });
	    	}else{
	    		Ext.MessageBox.alert('Conferma', "Non si è selezionata alcuna voce.");
	    	}
	    };
	    
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
			sm: selM
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("AutomatismiWorkflowList"),' '
				];
		
		var bbarItems = [
					'->', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/connect.png',
						hidden:false, 
						id: 'bNCollAut',
						pressed: false,
						enableToggle:false,
						text: 'Collega',
						handler: collAuto
						},
					'-', {type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
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

		DCS.GridAutListTab.superclass.initComponent.call(this, arguments);
		this.activation();
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

DCS.AutomatismiList = function(){

	return {
		create: function(IdAz,store){
			var gridListAuto = new DCS.GridAutListTab({
				titlePanel: '',
				id:'gridListaAutom',
				flex: 1,
				task: "readAutListGrid",
				groupOn: 'TipoNominativo',
				listStore: store,
				IdAzione:IdAz
			});

			return gridListAuto;
		}
	};
	
}();