// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridStatoRecuperoTab = Ext.extend(Ext.grid.GridPanel, {
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
			DCS.showDetailSRECC.create('Nuovo stato',null,gstore,true);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('AbbrStatoRecupero')==null || Arr[k].get('AbbrStatoRecupero')=='')
		    			confString += '<br />	- *Abbreviazione assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('AbbrStatoRecupero');
		    		vectString = vectString + '|' + Arr[k].get('IdStatoRecupero');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiConfigurabili.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoConf:'statorecuperoConf'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Gli stati selezionati sono stati eliminati.');
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
				id: 'actionColAss',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};

		var fields = [{name: 'IdStatoRecupero', type: 'int'},
							{name: 'TitoloStatoRecupero'},
							{name: 'AbbrStatoRecupero'},
							{name: 'CodStatoRecupero', type: 'string'},
							{name: 'LastUser'},
							{name: 'Ordine'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdStatoRecupero',width:10, header:'IdSRecC',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloStatoRecupero',	width:130,	header:'Stato recupero', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodStatoRecupero',	width:50,	header:'Codice',align:'center',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'AbbrStatoRecupero',	width:80,	header:'Abbreviazione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiConfigurabili.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoConf:'statorecuperoConf'},
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
					if(rec.get('TitoloStatoRecupero')!=null)
						titolo = "Modifica stato '"+rec.get('TitoloStatoRecupero')+"'";
					else 
						titolo = "Modifica stato *Titolo assente*";
					DCS.showDetailSRECC.create(titolo,rec,gstore);
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
							id: 'bNstc',
							pressed: false,
							enableToggle:false,
							text: 'Nuovo stato',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDstc',
							pressed: false,
							enableToggle:false,
							text: 'Cancella stato',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("StatiRecupero"),' '
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

		DCS.GridStatoRecuperoTab.superclass.initComponent.call(this, arguments);
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

DCS.StatoRecuperoConf = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>Lo stato di recupero &egrave; la distinzione fondamentale per l\'iter delle pratiche nel sistema di recupero.'
				+'<br>Si possono creare nuovi stati solo come conseguenza della definizione di nuovi workflow o la modifica di workflow esistenti.</span>';

			var gridStatoRecupero = new DCS.GridStatoRecuperoTab({
				titlePanel: 'Lista degli stati di recupero'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridStatoRecupero;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;
DCS.recordComboOrdine = Ext.data.Record.create([
              		{name: 'ordine'}]);
DCS.dSRConfPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "statorecupero";
		var extStore = this.store;
		var nuovo = this.nuovo;
		var queryComboOrdine = "SELECT ordine FROM statorecupero group by ordine;";
		var ds = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboOrdine,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboOrdine)
	    });
		var formAggregato = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
//			title: ' : ' + this.titoloProc,
//		    width: 500,
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
					width: 520,
					items: [{
						xtype: 'panel',
						layout: 'form',
						labelWidth: 65,
						columnWidth: 1,
						defaults: {xtype: 'textfield', anchor: '99%'},
						items: [{
							fieldLabel: 'IdStatoRecupero',
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
											if(newValue!='' && Ext.getCmp(idDin+'codice').getValue()!='' && Ext.getCmp(idDin+'abbr').getValue()!='')
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
								labelWidth: 65,
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
											if(newValue!='' && Ext.getCmp(idDin+'nome').getValue()!=''&& Ext.getCmp(idDin+'abbr').getValue()!='')
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
								labelWidth: 85,
								columnWidth: 0.67,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'abbr',
									fieldLabel: 'Abbreviazione',
									name: 'AbbrTipo',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'codice').getValue()!='' && Ext.getCmp(idDin+'nome').getValue()!='')
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
								labelWidth: 70,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '90%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Ordinamento',
									name:'Ordine',
									id:idDin+'cmbOrdine',
									allowBlank: true,
									hiddenName: 'Ordine',
									typeAhead: false,
									lazyInit:true,
									editable:true,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: ds,
									msgTarget:'side',
									invalidText:"Inserire solo valori numerici o lasciare vuoto",
									displayField: 'ordine',
									valueField: 'ordine',
//									regex:'^[0-9]*$',
//									regexText:"Inserire solo valori numerici o lasciare vuoto",
									validator: function(value){
										var patt=new RegExp('^[0-9]*$');
										var ret=true;
										if(!patt.test(value)){
											ret = false;
										}
										return ret;
									},
									listeners:{
										select: function(combo, record, index){},
										change : function(field, newValue,oldValue ){
											var patt=new RegExp('^[0-9]*$');
											if(!patt.test(newValue)){
												this.markInvalid();
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
								params: {task:"saveAgg", tipoConf:'statorecuperoConf', isNew:nuovo},
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
		
		DCS.dSRConfPanel.superclass.initComponent.call(this);
		//editing
		ds.load({
//			params:{sql:queryCombo},
			callback : function(rows,options,success) 
			{
				if(this.recordMod!=null){
					Ext.getCmp(idDin+'id').setValue(this.recordMod.get('IdStatoRecupero'));
					Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloStatoRecupero')));
					if(this.recordMod.get('AbbrStatoRecupero')!=null)
						Ext.getCmp(idDin+'abbr').setValue(replace_Tospecial_chars(this.recordMod.get('AbbrStatoRecupero')));
					Ext.getCmp(idDin+'codice').setValue(this.recordMod.get('CodStatoRecupero'));
					Ext.getCmp(idDin+'cmbOrdine').setValue(this.recordMod.get('Ordine'));
					Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloStatoRecupero'),'');
				}
			},
			scope:this
		});
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioStatoRecuperoConfPanel', DCS.dSRConfPanel);
	
DCS.showDetailSRECC= function()
{
	return {
		create: function(titolo,rec,store,isnew){
			isnew=isnew||false;
			wind = new Ext.Window({
				layout: 'fit',
				width: 550,
			    height: 190,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioStatoRecuperoConfPanel',
					titoloProc:'',
					recordMod:rec,
					store:store,
					nuovo:isnew
					}]
			});
			wind.show();
			return true;
		}
	};
}();