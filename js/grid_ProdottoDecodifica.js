// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridProdottoTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: 'TitoloFamiglia',
	
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
			DCS.showDetailPr.create('Nuovo prodotto',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloProdotto')==null || Arr[k].get('TitoloProdotto')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else	
		    			confString += '<br />	-'+Arr[k].get('TitoloProdotto');
		    		vectString = vectString + '|' + Arr[k].get('IdProdotto');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiDecodifica.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoDec:'prodotto'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'I prodotti selezionati sono stati eliminati.');
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

		var fields = [{name: 'IdProdotto', type: 'int'},
		              		{name: 'IdFamiglia', type: 'int'},
							{name: 'TitoloFamiglia'},
							{name: 'TitoloProdotto'},
							{name: 'CodProdotto', type: 'string'},
							{name: 'CodMarca', type: 'string'},
							{name: 'descrizioneMarca', type: 'string'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdFamiglia',width:10, header:'IdFam',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdProdotto',width:10, header:'IdProd',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TitoloProdotto',	width:130,	header:'Prodotto', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloFamiglia',	width:130,	header:'Famiglia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodProdotto',width:60, header:'Codice prodotto',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'descrizioneMarca',width:60, header:'Marca',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiDecodifica.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoDec:'prodotto'},
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
			autoExpandColumn:3,
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
					if(rec.get('TitoloProdotto')!=null)
						titolo = "Modifica prodotto '"+rec.get('TitoloProdotto')+"'";
					else 
						titolo = "Modifica prodotto *Titolo assente*";
					DCS.showDetailPr.create(titolo,rec,gstore);
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
							id: 'bNpro',
							pressed: false,
							enableToggle:false,
							text: 'Nuovo prodotto',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDpro',
							pressed: false,
							enableToggle:false,
							text: 'Cancella prodotto',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Prodotti"),' '
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

		DCS.GridProdottoTab.superclass.initComponent.call(this, arguments);
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

DCS.Prodotto = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>La tabella dei prodotti deve rispecchiare fedelmente i prodotti trattati nei flussi di alimentazione'
				                               +'<br>dai sistemi centrali; viene mantenuta aggiornata manualmente (nelle registrazioni degli esiti dei'
				                               +'<br>flussi di acquisizione vengono segnalati eventuali codici prodotto non riconosciuti).</span>';

			var gridProd = new DCS.GridProdottoTab({
				titlePanel: 'Lista dei prodotti'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridProd;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;
DCS.recordComboFamiglieMacro = Ext.data.Record.create([
   		{name: 'IdFam'},
   		{name: 'TitFam'},
  		{name: 'CodFam'}]);

DCS.recordComboMarkMacro = Ext.data.Record.create([
  		{name: 'codMarca'},
  		{name: 'descrizioneMarca'}]);

DCS.dPROPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
//	invisibileLeg:true,
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "prodottoForm";
		var extStore = this.store;
		var queryComboFam = "Select IdFamiglia as IdFam,TitoloFamiglia as TitFam,CodFamiglia as CodFam from famigliaprodotto fp order by 1;";
		var queryComboMark = "SELECT * FROM v_brand";
		
		var ds = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboFam,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboFamiglieMacro)
	    });
		var dsMark = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboMark,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboMarkMacro)
	    });
		
		var formAggregato = new Ext.form.FormPanel({
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
							fieldLabel: 'IdProdotto',
							readOnly:true,
							hidden:true,
							style:'text-align:right',
							id:idDin+'idP',
							name: 'IdProd'
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
									id:idDin+'nomeP',
									fieldLabel: 'Nome',
									name: 'TitoloProd',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'codiceP').getValue()!='')
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
								labelWidth: 60,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'codiceP',
									fieldLabel: 'Codice',
									name: 'CodProd',
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'nomeP').getValue()!='')
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
								labelWidth: 75,
								columnWidth: 0.77,
								defaults: {xtype: 'combo', anchor: '97%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Marchio',
									name:'Marchio',
									id:idDin+'cmbMark',
									allowBlank: true,
									hiddenName: 'Marchio',
									typeAhead: false,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsMark,
									displayField: 'descrizioneMarca',
									valueField: 'codMarca',
									listeners:{
										select: function(combo, record, index){
											Ext.getCmp(idDin+'codiceMark').setValue(record.get('codMarca'));
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.23,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:true,
									style: 'text-align:left',
									id:idDin+'codiceMark',
									fieldLabel: 'Codice',
									name: 'CodMark',
									listeners:{
										change : function(field, newValue,oldValue ){
//											if(newValue!='' && Ext.getCmp(idDin+'nome').getValue()!='')
//											{
//												Ext.getCmp(idDin+'bSave').setDisabled(false);
//											}else{
//												Ext.getCmp(idDin+'bSave').setDisabled(true);
//											}
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 75,
								columnWidth: 0.77,
								defaults: {xtype: 'combo', anchor: '97%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Famiglia',
									name:'macroFam',
									id:idDin+'cmbMacro',
									allowBlank: false,
									hiddenName: 'macroFam',
									typeAhead: false,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: ds,
									displayField: 'TitFam',
									valueField: 'IdFam',
									listeners:{
										select: function(combo, record, index){
											Ext.getCmp(idDin+'labDef').setText(record.get('CodFam'));
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.23,
								defaults: {xtype: 'label', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'labDef',
									fieldLabel: '',
									listeners:{
										change : function(field, newValue,oldValue ){
//											if(newValue!='' && Ext.getCmp(idDin+'nome').getValue()!='')
//											{
//												Ext.getCmp(idDin+'bSave').setDisabled(false);
//											}else{
//												Ext.getCmp(idDin+'bSave').setDisabled(true);
//											}
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
								url: 'server/gestioneTipiDecodifica.php', method: 'POST',
								params: {task:"saveAgg", tipoDec:'prodotto'},
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
		
		DCS.dPROPanel.superclass.initComponent.call(this);
		//caricamento degli store
		dsMark.load({
			callback : function(rows,options,success) {
				ds.load({
		//			params:{sql:queryCombo},
					callback : function(rows,options,success) {
						if(rows.length>0){
							if(this.recordMod!=null){
								//editing
								Ext.getCmp(idDin+'idP').setValue(this.recordMod.get('IdProdotto'));
								Ext.getCmp(idDin+'nomeP').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloProdotto')));
								Ext.getCmp(idDin+'codiceP').setValue(this.recordMod.get('CodProdotto'));
								if(this.recordMod.get('CodMarca')!=""){
									Ext.getCmp(idDin+'cmbMark').setValue(this.recordMod.get('CodMarca'));
									Ext.getCmp(idDin+'codiceMark').setValue(this.recordMod.get('CodMarca'));
								}else{
									Ext.getCmp(idDin+'cmbMark').setValue(null);
								}
								Ext.getCmp(idDin+'cmbMacro').setValue(this.recordMod.get('IdFamiglia'));
								var indexM = Ext.getCmp(idDin+'cmbMacro').getStore().findExact('IdFam',Ext.getCmp(idDin+'cmbMacro').getValue().toString());
								var codM = Ext.getCmp(idDin+'cmbMacro').getStore().getAt(indexM).get('CodFam');
								Ext.getCmp(idDin+'labDef').setText(codM);
								Ext.getCmp(idDin+'nomeP').fireEvent('change',Ext.getCmp(idDin+'nomeP'),this.recordMod.get('TitoloProdotto'),'');

							}
						}
					},
					scope:this
				});
			},
			scope:this
		});
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioProPanel', DCS.dPROPanel);
	
DCS.showDetailPr= function(titolo,rec,store)
{
	return {
		create: function(titolo,rec,store){
			wind = new Ext.Window({
				layout: 'fit',
				width: 500,
			    height: 215,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioProPanel',
					titoloProc:'',
					recordMod:rec,
//					invisibileLeg:invisibileLeg,
					store:store
					}]
			});
			wind.show();
			return true;
		}
	};
}();