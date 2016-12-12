// Crea namespace DCS
Ext.namespace('DCS');

var winS;

DCS.GridAutAzWorkflowTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	idAzione:'',
	titAzione:'',

	initComponent : function() { 
		
		var azioneAss=this.idAzione||'';
		var titoloAzz=this.titAzione||'';
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
			showAutAzioneDetail(0,gstore,0,azioneAss,false,'');//titoloAzz);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloAutomatismo');
		    		vectString = vectString + '|' + Arr[k].get('IdAutomatismo');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si stanno scollegando i seguenti automatismi: "+confString+" ?",function(btn, text){
		    		if (btn == 'ok'){
		    			Ext.MessageBox.show({
				    		   title:'Attenzione',
				    		   msg: "Si desidera anche eliminarli definitivamente ?",
				    		   buttons: Ext.Msg.YESNOCANCEL,
				    		   fn: function(btn, text,opt)
				    		   {
					    					var deletefin=0;
							    	    	if (btn == 'yes'){
							    	    		deletefin=1;
							    	    	}
							    	    	if (btn != 'cancel')
							    	    	{
								    	    	Ext.Ajax.request({
											        url: 'server/gestioneProcedure.php',
											        method: 'POST',
											        params: {task: 'deleteAutomatismoAz',vect: vectString, erase: deletefin, idAzione:azioneAss},
											        success: function(obj) {
											            var resp = obj.responseText;
											            if (resp == '' && vectString!='') {
											            	var mex='';
											            	if(deletefin){
											            		mex='eliminati';
											            	}else{
											            		mex='scollegati';
											            	}
											                Ext.MessageBox.alert('Esito', 'Gli automatismi selezionati sono stati '+mex+'.');
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
						    	},
						    	animEl: 'elId',
					    		icon: Ext.MessageBox.QUESTION
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

		var fields = [{name: 'IdAutomatismo', type: 'int'},
							{name: 'TitoloAutomatismo'},
							{name: 'TitoloModello'},
							{name: 'TipoAut'},
							{name: 'TipoAutomatismo'},
							{name: 'Condizione'},
							{name: 'Comando'},
							{name: 'Destinatari'},
							{name: 'DestAut'},
							{name: 'FlagCumulativo'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdAutomatismo',width:10, header:'IdAut',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloAutomatismo',	width:130,	header:'Nome automatismo', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TipoAut',	width:70,	header:'Tipo autom.', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloModello',	width:70,	header:'Modello', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	//{dataIndex:'Condizione',	width:130,	header:'Condizione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Comando',	width:120,	header:'Comando', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'DestAut',width:80, header:'Destinatari',align:'right',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'FlagCumulativo',width:30, exportable:false, renderer:DCS.render.spunta, header:'Attiva',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProcedure.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, idAzione:this.idAzione},
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
					showAutAzioneDetail(rec.get('IdAutomatismo'),gstore,rowIndex,azioneAss,false,rec.get('TitoloAutomatismo'));//titoloAzz);
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
						id: 'bNAutAzpr',
						pressed: false,
						enableToggle:false,
						text: 'Aggiungi automatismo',
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/connect.gif',
						hidden:false, 
						id: 'bLAutAzpr',
						pressed: false,
						enableToggle:false,
						text: 'Collega automatismi',
						handler: function(){Ext.getCmp(IdMain).showAutList(azioneAss,gstore);}
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/disconnect.png',
						hidden:false, 
						id: 'bDAutAzpr',
						pressed: false,
						enableToggle:false,
						text: 'Scollega automatismo',
						handler: delRecord
						},
					'-',{type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("AutomatismiWorkflow"),' '
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

		DCS.GridAutAzWorkflowTab.superclass.initComponent.call(this, arguments);
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
	},
	//--------------------------------------------------------
    // Visualizza lista automatismi
    //--------------------------------------------------------
	showAutList: function(IdAz,store)
    {
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		var pnl = new DCS.AutomatismiList.create(IdAz,store);
		winS = new Ext.Window({
    		width: 350, height:500, minWidth: 350, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    		id:'ListAutomAzWF',
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: 'Automatismi disponibili per l\'associazione', 
    		constrain: true,
			items: [pnl]
        });
		winS.show();
		myMask.hide();
		pnl.activation.call(pnl);
    }
});

DCS.AutomatismiAzWrkF = function(){

	return {
		create: function(idA,titoloAzione){
			var gridGestAutAzWF = new DCS.GridAutAzWorkflowTab({
				titlePanel: '',
				flex: 1,
				task: "readAutAzProcGrid",
				idAzione:idA,
				titAzione:titoloAzione
			});

			return gridGestAutAzWF;
		}
	};
	
}();