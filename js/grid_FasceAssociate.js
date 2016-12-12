// Crea namespace DCS
Ext.namespace('DCS');

var winF;

DCS.GridFasce = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	idReg:'',
	titoloR:'',
	titoloReg:'',
	arrayStato:'',
	
	initComponent : function() { 
		
		var NotVisibleOpe=true;
		var buttAddName='';
		var buttDelName='';
		var regAss=this.idReg||'';
		var titoloRep=this.titoloR||'';
		var titoloReg=this.titoloReg||'';
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
			showFasciaDetail(IdMain,gstore,regAss,'',titoloReg);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	var vectValue='';
	    	var word='';
	    		    	
	    	if(Arr.length>0)
	    	{
		    	for(var k=0;k<Arr.length;k++)
		    	{
	    			confString += '<br />	-'+Arr[k].get('AbbrFasciaProvvigione');
		    		vectString = vectString + '|' + Arr[k].get('IdRegolaProvvigione');
		    		vectValue = vectValue + '|' + Arr[k].get('AbbrFasciaProvvigione');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneAssegnazioni.php',
					        method: 'POST',
					        params: {task: 'deleteFasceRules',vect: vectString, vectAbbr:vectValue, idRule:regAss},
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
	    
		
		buttAddName='Crea fascia';
		buttDelName='Elimina fasce selezionate';
		
		var fields = [	{name: 'IdRegolaProvvigione', type: 'int'},
						{name: 'ValoreSoglia', type: 'float'},
						{name: 'Formula'},
						{name: 'AbbrFasciaProvvigione'},
						{name: 'DataIni', type:'date'},
						{name: 'DataFin', type:'date'},
						{name: 'LastUser'},
						{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];
		
		var columns = [selM,
						{dataIndex:'IdRegolaProvvigione',width:10, header:'IdRp',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
						{dataIndex:'AbbrFasciaProvvigione',	width:130,	header:'Abbreviazione', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'ValoreSoglia',width:50, header:'Valore di soglia',align:'center',hidden: false, hideable: false,filterable:true,groupable:false,sortable:false},
						{dataIndex:'Formula',	width:130,	header:'Formula', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'DataIni',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Inizio associazione',align:'left', filterable: true, groupable:true, sortable:true},
						{dataIndex:'DataFin',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Fine associazione',align:'left', filterable: true, groupable:true, sortable:true},
						{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
						{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}];
				
		
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, idReg:this.idReg},
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
						var tit=titoloReg+"<br /> con fascia \'"+rec.get('AbbrFasciaProvvigione')+"\'";
						var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
						myMask.show();
						showFasciaDetail(IdMain,gstore,regAss,rec.get('ValoreSoglia'),tit);
						myMask.hide();
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:false, 
						id: 'bNFA',
						pressed: false,
						enableToggle:false,
						text: buttAddName,
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDFA',
						pressed: false,
						enableToggle:false,
						text: buttDelName,
						handler: delRecord
						},
					'-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("FasceProvvigioni"),' '
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

		DCS.GridFasce.superclass.initComponent.call(this, arguments);
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

DCS.AssFascie = function(){

	return {
		create: function(idR,titoloR,titoloReg,ArrSaveStateFields){
			var gridGestFasce = new DCS.GridFasce({
				titlePanel: '',
				flex: 1,
				task: "readFasceGrid",
				idReg:idR,
				titoloR:titoloR,
				titoloReg:titoloReg,
				arrayStato:ArrSaveStateFields
			});

			return gridGestFasce;
		}
	};
	
}();