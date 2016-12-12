// Crea namespace DCS
Ext.namespace('DCS');

var winS;

DCS.GridStatiWorkflowTab = Ext.extend(Ext.grid.GridPanel, {
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
		var isLink=1;
		
		var newRecord = function(btn, pressed)
		{
	   		
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			//Ext.getCmp(IdMain).showDettaglioAzioneCE();
			//showAzioneDetail(0,gstore,0,procAss);
			isLink=0;
			showStatoDetail(0,gstore,0,procAss,isLink);
			isLink=1;
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		confString += '<br />	-'+Arr[k].get('TitoloSRec');
		    		vectString = vectString + '|' + Arr[k].get('IdSRec');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si stanno scollegando i seguenti stati dagli esiti della procedura: "+confString+" ?",function(btn, text){
		    		if (btn == 'ok'){
		    			Ext.MessageBox.show({
				    		   title:'Attenzione',
				    		   msg: "Si desidera anche eliminarli definitivamente? <br /> <i>(Attenzione: questa operazione potrebbe scollegare altri esiti di altre procedure)</i>",
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
											        params: {task: 'deleteStatiProc',vect: vectString, erase: deletefin, idprocedura:procAss},
											        success: function(obj) {
											            var resp = obj.responseText;
											            if (resp == '' && vectString!='') {
											            	var mex='';
											            	if(deletefin){
											            		mex='eliminati';
											            	}else{
											            		mex='scollegati';
											            	}
											                Ext.MessageBox.alert('Esito', 'Gli stati selezionati sono stati '+mex+'.');
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
	    
		var fields = [{name: 'IdSRec', type: 'int'},
		              {name: 'TitoloSRec'},
					  {name: 'Abbr'}];

    	var columns = [selM,
    	               	{dataIndex:'IdSRec',width:10, header:'IdS',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloSRec',width:150,	header:'Nome Stato', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Abbr',	width:70,	header:'Abbreviazione', hideable: false,filterable:true,groupable:false,sortable:true}
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
					//showAzioneDetail(rec.get('IdAzione'),gstore,rowIndex,procAss);
					isLink=0;
					showStatoDetail(rec.get('IdSRec'),gstore,rowIndex,procAss,isLink,null,'',rec.get('TitoloSRec'));
					isLink=1;
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
						id: 'bNSpr',
						pressed: false,
						enableToggle:false,
						text: 'Aggiungi stato',
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/connect.gif',
						hidden:false, 
						id: 'bLSpr',
						pressed: false,
						enableToggle:false,
						text: 'Collega stato',
						handler: function(){showStatoDetail(0,gstore,0,procAss,isLink);}
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/disconnect.png',
						hidden:false, 
						id: 'bSSpr',
						pressed: false,
						enableToggle:false,
						text: 'Scollega stato',
						handler: delRecord
						},
					' '
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

		DCS.GridStatiWorkflowTab.superclass.initComponent.call(this, arguments);
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

DCS.StatiWrkF = function(){

	return {
		create: function(idP){
			var gridGestSTWF = new DCS.GridStatiWorkflowTab({
				titlePanel: '',
				flex: 1,
				task: "readStatiProcGrid",
				idProc:idP
			});

			return gridGestSTWF;
		}
	};
	
}();