// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridCategoriaRiscattoLeasingTab = Ext.extend(Ext.grid.GridPanel, {
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
			DCS.showDetailCRL.create('Nuovo tipo categoria riscatto leasing',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('CategoriaRiscattoLeasing')==null || Arr[k].get('CategoriaRiscattoLeasing')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('CategoriaRiscattoLeasing');
		    		vectString = vectString + '|' + Arr[k].get('IdCategoriaRiscattoLeasing');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiConfigurabili.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoConf:'categoriaRiscattoLeasing'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le categorie selezionate sono state eliminate.');
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
	    		Ext.MessageBox.alert('Conferma', "Non si � selezionata alcuna voce.");
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

		var fields = [{name: 'IdCategoriaRiscattoLeasing', type: 'int'},
							{name: 'CategoriaRiscattoLeasing'},
							{name: 'CodRiscattoLeasing', type: 'string'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdCategoriaRiscattoLeasing',width:10, header:'IdMRCat',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'CategoriaRiscattoLeasing',	width:130,	header:'Categoria riscatto leasing', hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodRiscattoLeasing',width:50, header:'Codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiConfigurabili.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoConf:'categoriaRiscattoLeasing'},
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
//					var campo;
//					switch(columnIndex){
//						case 3:	campo='NumTipAff';
//							break;
//						case 4:	campo='NumRegAff';
//							break;
//						case 5:	campo='NumRegAffOpe';
//							break;
//						default:campo='';
//							break;
//					}
//					if(campo!='')
//					{
//						Ext.getCmp(IdMain).showGrigliaAssociazioneGenerica(rec.get('IdReparto'),rec.get('TitoloUfficio'),campo,gstore);
//					}
					/*var elem = rec.get(campo);
					if(elem != undefined)
					{
						//Apertura pannello della colonna selezionata
						//Ext.getCmp(IdMain).showAzioneWfDettaglio(rec.get('IdProcedura'),null,rec.get('TitoloProcedura'));
					}	*/	
					var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
					myMask.show();
					var titolo="";
					if(rec.get('CategoriaRiscattoLeasing')!=null)
						titolo = "Modifica tipo categoria riscatto leasing '"+rec.get('CategoriaRiscattoLeasing')+"'";
					else 
						titolo = "Modifica tipo categoria riscatto leasing *Titolo assente*";
					DCS.showDetailCRL.create(titolo,rec,gstore);
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
							text: 'Nuova categoria',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDcom',
							pressed: false,
							enableToggle:false,
							text: 'Cancella categoria',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Categorie"),' '
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

		DCS.GridCategoriaRiscattoLeasingTab.superclass.initComponent.call(this, arguments);
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

DCS.CategoriaRiscattoLeasing = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>Le categorie sono classificazioni liberamente attribuibili ai contratto in stato di "lavorazione interna",'
				+'<br>e permettono di suddividere tali contratti in pi&ugrave; pagine nella corrispondente lista, per una pi&ugrave; facile gestione.</span>';
			var gridCategoriaRiscattoLeasing = new DCS.GridCategoriaRiscattoLeasingTab({
				titlePanel: 'Lista categorie riscatto leasing'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridCategoriaRiscattoLeasing;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.dCategorieRiscattoLeasingPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "categoriaRiscattoLeasing";
		var extStore = this.store;
		var formRiscattoLeasing = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
//			title: ' : ' + this.titoloProc,
		    width: 450,
//		    height: 200,
		    autoHeight: true,
		    labelWidth:100,
//		    trackResetOnLoad: true,
//				reader: new Ext.data.ArrayReader({
//					root: 'results',
//					fields: this.recordProcedura}),
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
							fieldLabel: 'IdCategoria',
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
	    		 		if (formRiscattoLeasing.getForm().isValid()){
							this.setDisabled(true);
							formRiscattoLeasing.getForm().submit({
								url: 'server/gestioneTipiConfigurabili.php', method: 'POST',
								params: {task:"saveAgg", tipoConf:'categoriaRiscattoLeasing'},
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
				{text: 'Annulla',handler: function () {quitForm(formRiscattoLeasing,wind);} 
				}
			   ]  // fine array buttons
			   
		});

		Ext.apply(this, {
			layout:'fit',
			items: [formRiscattoLeasing]
		});
		
		DCS.dCategorieRiscattoLeasingPanel.superclass.initComponent.call(this);
		//editing
		if(this.recordMod!=null){
			Ext.getCmp(idDin+'id').setValue(this.recordMod.get('IdCategoriaRiscattoLeasing'));
			Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('CategoriaRiscattoLeasing')));
			Ext.getCmp(idDin+'codice').setValue(this.recordMod.get('CodRiscattoLeasing'));
			Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('CategoriaRiscattoLeasing'),'');
		}
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioCategorieRiscattoLeasingPanel', DCS.dCategorieRiscattoLeasingPanel);
	
DCS.showDetailCRL= function(titolo,rec,store)
{
	return {
		create: function(titolo,rec,store){
			var h=160;
			wind = new Ext.Window({
				layout: 'fit',
				width: 500,
			    height: h,
				modal: true,
				title: titolo,
				tools: [helpTool("CategoriaRiscattoLeasing")],
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioCategorieRiscattoLeasingPanel',
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