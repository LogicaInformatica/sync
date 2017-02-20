// Crea namespace DCS
Ext.namespace('DCS');
//ASSEGNAZIONI
var winS;

DCS.GridRegOperTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	IdOp:'',
	NomeOp:'',
	
	initComponent : function() { 
		
		var buttAddName='';
		var buttDelName='';
		var IdOp=this.IdOp||'';
		var NomeOp=this.NomeOp||'';
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
			showAssegnazioneDetail('',gstore,'','',NomeOp,IdOp);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	var field='';
	    	var indexf='';
	    	var word='';
	    	var arrContr=[];
			word='le regole selezionate';
	    	indexf='IdRegolaAssegnazione';
	    	
	    	if(Arr.length>0)
	    	{
		    	for(var k=0;k<Arr.length;k++)
		    	{
		    		vectString = vectString + '|' + Arr[k].get(indexf);
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare "+word+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneAssegnazioni.php',
					        method: 'POST',
					        params: {task: 'deleteASSRules',vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le regole selezionate sono state eliminate.');
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
				id: 'actionColGen',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};
		
			
		buttAddName='Nuova regola';
		buttDelName='Cancella regola';
		var fields = [	{name: 'IdRegolaAssegnazione', type: 'int'},
						{name: 'IdReparto',type: 'int'},
						{name: 'TipoDistribuzione'},
						{name: 'tipodistribuzioneConv'},
						{name: 'titolofamiglia'},
						{name: 'titoloclasse'},
						{name: 'titoloufficio'},
						{name: 'codregolaprovvigione'},
						{name: 'Nominativo'},
						{name: 'Condizione'},
						{name: 'DataIni', type:'date'},
				        {name: 'DataFin', type:'date'},
						{name: 'LastUser'},
						{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];
		
		var columns = [selM,
						{dataIndex:'IdRegolaAssegnazione',width:10, header:'Idra',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
						{dataIndex:'IdReparto',width:10, header:'IdR',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
						{dataIndex:'codregolaprovvigione',	width:130,	header:'cod provvigione', hidden:true, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'Nominativo',	width:130,	header:'Regola provvigione', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'titolofamiglia',	width:130,	header:'Famiglia di prodotto', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'titoloclasse',	width:130,	header:'Classificazione', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'titoloufficio',	width:130,	header:'Agenzia', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'TipoDistribuzione',	width:80,	header:'Tipo distribuzione', hidden:true,hideable: false,align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'tipodistribuzioneConv',	width:80,	header:'Tipo distribuzione', hideable: false,align:'right',filterable:true,groupable:false,sortable:true},
						{dataIndex:'Condizione',	width:110,	header:'Condizione', hideable: false,filterable:true,groupable:false,sortable:true},
						{dataIndex:'DataIni',	width:80,xtype:'datecolumn',header:'Inizio assegnazione',hidden:false, filterable:true,sortable:true,groupable:false},
						{dataIndex:'DataFin',	width:80,xtype:'datecolumn',header:'Fine assegnazione',hidden:false, filterable:true,sortable:true,groupable:false},
						{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
						{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, IdOp:this.IdOp},
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
					var titoloRegola=rec.get('codregolaprovvigione');
					showAssegnazioneDetail(rec.get('IdRegolaAssegnazione'),gstore,rowIndex,titoloRegola,NomeOp,IdOp);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:false, 
						id: 'bNRA',
						pressed: false,
						enableToggle:false,
						text: buttAddName,
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDRA',
						pressed: false,
						enableToggle:false,
						text: buttDelName,
						handler: delRecord
						},
					'-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("RegoleAssegnazioneOperatore"),' '
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

		DCS.GridRegOperTab.superclass.initComponent.call(this, arguments);
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

DCS.AssOperatore = function(){

	return {
		create: function(IdOp,NomeOp){
			var gridGestAssOP = new DCS.GridRegOperTab({
				titlePanel: '<span class="subtit">Le regole di assegnazione agli operatori possono essere basate su uno dei parametri seguenti:'
						+'<br>1) regola provvigionale applicata, 2) famiglia di prodotto, 3) agenzia affidataria, 4) classificazione della pratica.</span>',
				flex: 1,
				task: "readRegoleAssOp",
				IdOp:IdOp,
				NomeOp:NomeOp
			});

			return gridGestAssOP;
		}
	};
	
}();