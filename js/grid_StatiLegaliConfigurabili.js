// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridStatoLegaleTab = Ext.extend(Ext.grid.GridPanel, {
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
			DCS.showDetailSLEG.create('Nuovo stato legale',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloStatoLegale')==null || Arr[k].get('TitoloStatoLegale')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloStatoLegale');
		    		vectString = vectString + '|' + Arr[k].get('IdStatoLegale');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiConfigurabili.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoConf:'statoLegaleConf'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Gli stati legali selezionati sono stati eliminati.');
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
	    		Ext.MessageBox.alert('Conferma', "Non si &egrave; selezionata alcuna voce.");
	    	}
	    };
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColAss',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdStatoLegale', type: 'int'},
							{name: 'TitoloStatoLegale'},
							{name: 'CodStatoLegale', type: 'string'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdStatoLegale',width:10, header:'IdSLeg',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloStatoLegale',	width:130,	header:'Stato Legale', hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodStatoLegale',width:50, header:'Codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiConfigurabili.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoConf:'statoLegaleConf'},
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
					var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
					myMask.show();
					var titolo="";
					if(rec.get('TitoloStatoLegale')!=null)
						titolo = "Modifica stato legale '"+rec.get('TitoloStatoLegale')+"'";
					else 
						titolo = "Modifica stato legale *Titolo assente*";
					DCS.showDetailSLEG.create(titolo,rec,gstore);
					myMask.hide();
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNccat',
							pressed: false,
							enableToggle:false,
							text: 'Nuovo stato legale',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDcom',
							pressed: false,
							enableToggle:false,
							text: 'Cancella stato legale',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("StatiLegali"),' '
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

		DCS.GridStatoLegaleTab.superclass.initComponent.call(this, arguments);
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
	}
});

DCS.StatoLegaleConf = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>Gli Stati Legali sono attribuibili alle pratiche in gestione legale,'
				+'<br>e permettono di suddividere tali pratiche in pi&ugrave; pagine nella corrispondente lista, per una pi&ugrave; facile gestione.</span>';
			return new DCS.GridStatoLegaleTab({
				titlePanel: 'Lista Stati Legali'+subtitle,
				flex: 1,
				task: "readMainGrid"
			});
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.dStatoLegalePanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "statoLegaleConf";
		var extStore = this.store;
		var formAggregato = new Ext.form.FormPanel({
			xtype: 'form',
			frame: true, 
		    width: 500,
		    autoHeight: true,
		    labelWidth:100,
			items: [{
					xtype: 'fieldset',
					autoHeight: true,
					width: 470,
					items: [{
						xtype: 'panel',
						layout: 'form',
						labelWidth: 65,
						columnWidth: 1,
						defaults: {xtype: 'textfield', anchor: '99%'},
						items: [{
							fieldLabel: 'IdStatoLegale',
							readOnly:true,
							hidden:true,
							style:'text-align:right',
							id:idDin+'id',
							name: 'IdTipo'
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:false,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 55,
								columnWidth: 0.67,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'nome',
									fieldLabel: 'Nome',
									name: 'TitoloTipo',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'codice').getValue()!='')
											{
												Ext.getCmp(idDin+'bSave').setDisabled(false);
											}else{
												Ext.getCmp(idDin+'bSave').setDisabled(true);
											}
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'codice',
									fieldLabel: 'Codice',
									name: 'CodTipo',
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'nome').getValue()!='')
											{
												Ext.getCmp(idDin+'bSave').setDisabled(false);
											}else{
												Ext.getCmp(idDin+'bSave').setDisabled(true);
											}
										}
									}
								}]
							}]
						}]
					}]
			}], // fine array items del form
		    buttons: 
		    	[
		    	 {
				  text: 'Salva',
				  disabled:bDisa,
				  id:idDin+'bSave',
				  handler: function() {
	    		 		if (formAggregato.getForm().isValid()){
							this.setDisabled(true);
							formAggregato.getForm().submit({
								url: 'server/gestioneTipiConfigurabili.php', method: 'POST',
								params: {task:"saveAgg", tipoConf:'statoLegaleConf'},
								success: function (frm,action) {
									Ext.MessageBox.alert('Esito', action.result.messaggio); 
									extStore.reload();
									wind.close();
								},
								failure: function (frm,action) {
									Ext.MessageBox.alert('Errore', action.result.messaggio); 
									wind.close();
								}
							});
						}
		    	 	},
					scope:this
				 }, 
				{text: 'Annulla',handler: function () {quitForm(formAggregato,wind);} 
				}
			   ]  // fine array buttons
			   
		});

		Ext.apply(this, {
			layout:'fit',
			items: [formAggregato]
		});
		
		DCS.dStatoLegalePanel.superclass.initComponent.call(this);
		//editing
		if(this.recordMod!=null){
			Ext.getCmp(idDin+'id').setValue(this.recordMod.get('IdStatoLegale'));
			Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloStatoLegale')));
			Ext.getCmp(idDin+'codice').setValue(this.recordMod.get('CodStatoLegale'));
			Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloStatoLegale'),'');
		}
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioStatoLegalePanel', DCS.dStatoLegalePanel);
	
DCS.showDetailSLEG= function(titolo,rec,store)
{
	return {
		create: function(titolo,rec,store){
			var h=160;
			wind = new Ext.Window({
				layout: 'fit',
				width: 520,
			    height: h,
				modal: true,
				title: titolo,
				tools: [helpTool("StatoLegale")],
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioStatoLegalePanel',
					titoloProc:'',
					recordMod:rec,
					store:store
					}]
			});
			wind.show();
			return true;
		}
	};
}();