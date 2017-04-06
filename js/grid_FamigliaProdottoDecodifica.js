// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridFamigliaProdottoTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: 'gruppo',
	
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
			DCS.showDetailFPr.create('Nuova famiglia prodotto',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloFamiglia')==null || Arr[k].get('TitoloFamiglia')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else	
		    			confString += '<br />	-'+Arr[k].get('TitoloFamiglia');
		    		vectString = vectString + '|' + Arr[k].get('IdFamiglia');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiDecodifica.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoDec:'famigliaProdotto'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le famiglie selezionate sono state eliminate.');
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

		var fields = [{name: 'IdFamiglia', type: 'int'},
							{name: 'TitoloFamiglia'},
							{name: 'IdFamigliaParent'},
							{name: 'IdCompagnia', type: 'int'},
							{name: 'CodFamiglia', type: 'string'},
							{name: 'CodCompagnia', type: 'string'},
							{name: 'TitoloCompagnia', type: 'string'},
							{name: 'famigliaParent', type: 'string'},
							{name: 'gruppo', type: 'string'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdFamiglia',width:10, header:'IdFam',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdFamigliaParent',width:10, header:'IdFamP',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdCompagnia',width:10, header:'IdCom',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloFamiglia',	width:130,	header:'Famiglia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodFamiglia',width:60, header:'Codice famiglia',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'TitoloCompagnia',	width:120,	header:'Societ&agrave;',hidden: false, hideable: false,filterable:false,groupable:false,sortable:true},
    		        	{dataIndex:'CodCompagnia',	width:60,	header:'Codice societ&agrave;',align:'center',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'famigliaParent',	width:60,	header:'Macrofamiglia',align:'center' , hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'gruppo',	width:90,	header:'gruppo', hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiDecodifica.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoDec:'famigliaProdotto'},
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
					if(rec.get('TitoloFamiglia')!=null)
						titolo = "Modifica famiglia prodotto '"+rec.get('TitoloFamiglia')+"'";
					else 
						titolo = "Modifica famiglia prodotto *Titolo assente*";
					DCS.showDetailFPr.create(titolo,rec,gstore);
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
							id: 'bNFpro',
							pressed: false,
							enableToggle:false,
							text: 'Nuova famiglia',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDFpro',
							pressed: false,
							enableToggle:false,
							text: 'Cancella famiglia',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("FamiglieProdotto"),' '
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

		DCS.GridFamigliaProdottoTab.superclass.initComponent.call(this, arguments);
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

DCS.FamigliaProdotto = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>La tabella delle famiglie di prodotti, che indica i raggruppamenti di Prodotti in due livelli, deve rispecchiare fedelmente'
				+'<br>quanto trattato nei flussi di alimentazione dai sistemi centrali. La tabellla deve essere mantenuta aggiornata manualmente'
				+'<br>(nelle registrazioni degli esiti dei flussi di acquisizione vengono segnalati eventuali codici non riconosciuti).</span>';
			var gridFProd = new DCS.GridFamigliaProdottoTab({
				titlePanel: 'Lista delle famiglie di prodotti'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridFProd;
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

DCS.recordComboCompanyMacro = Ext.data.Record.create([
  		{name: 'IdCompagnia'},
  		{name: 'TitoloCompagnia'},
  		{name: 'CodCompagnia'}]);

DCS.dFROPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
//	invisibileLeg:true,
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "famigliaProd";
		var extStore = this.store;
		var queryComboFam = "select null as IdFam,'Macrofamiglia' as TitFam, null as CodFam "+
			"union all "+
			"Select IdFamiglia,TitoloFamiglia,CodFamiglia from famigliaprodotto fp order by 1;";
		var queryComboCompany = "SELECT IdCompagnia,TitoloCompagnia,CodCompagnia FROM compagnia WHERE IdTipoCompagnia=1 order by 1;";
		
		var ds = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboFam,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboFamiglieMacro)
	    });
		var dsComp = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboCompany,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboCompanyMacro)
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
							fieldLabel: 'IdFamiglia',
							readOnly:true,
							hidden:true,
							style:'text-align:right',
							id:idDin+'idF',
							name: 'IdFam'
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
									id:idDin+'nomeF',
									fieldLabel: 'Nome',
									name: 'TitoloFam',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'codiceF').getValue()!='')
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
									id:idDin+'codiceF',
									fieldLabel: 'Codice',
									name: 'CodFam',
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'nomeF').getValue()!='')
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
									fieldLabel: 'Societ&agrave;',
									name:'Compagnia',
									id:idDin+'cmbComp',
									allowBlank: false,
									hiddenName: 'Compagnia',
									typeAhead: false,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsComp,
									displayField: 'TitoloCompagnia',
									valueField: 'IdCompagnia',
									listeners:{
										select: function(combo, record, index){
											Ext.getCmp(idDin+'codiceComp').setValue(record.get('CodCompagnia'));
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
									id:idDin+'codiceComp',
									fieldLabel: 'Codice',
									name: 'CodCom',
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
									fieldLabel: 'Macrofamiglia',
									name:'macroFam',
									id:idDin+'cmbMacro',
									allowBlank: true,
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
								params: {task:"saveAgg", tipoDec:'famigliaProdotto'},
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
		
		DCS.dFROPanel.superclass.initComponent.call(this);
		//caricamento degli store
		dsComp.load({
			callback : function(rows,options,success) {
				ds.load({
		//			params:{sql:queryCombo},
					callback : function(rows,options,success) {
						if(rows.length>0){
							if(this.recordMod!=null){
								//editing
								Ext.getCmp(idDin+'idF').setValue(this.recordMod.get('IdFamiglia'));
								Ext.getCmp(idDin+'nomeF').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloFamiglia')));
		//						Ext.getCmp(idDin+'nome').setValue(this.recordMod.get('TitoloTipoPartita'));
								Ext.getCmp(idDin+'codiceF').setValue(this.recordMod.get('CodFamiglia'));
								Ext.getCmp(idDin+'cmbComp').setValue(this.recordMod.get('IdCompagnia'));
								Ext.getCmp(idDin+'codiceComp').setValue(this.recordMod.get('CodCompagnia'));
								Ext.getCmp(idDin+'cmbMacro').setValue(this.recordMod.get('IdFamigliaParent'));
								var indexM = Ext.getCmp(idDin+'cmbMacro').getStore().findExact('IdFam',Ext.getCmp(idDin+'cmbMacro').getValue());
								var codM = Ext.getCmp(idDin+'cmbMacro').getStore().getAt(indexM).get('CodFam');
								Ext.getCmp(idDin+'labDef').setText(codM);
								Ext.getCmp(idDin+'nomeF').fireEvent('change',Ext.getCmp(idDin+'nomeF'),this.recordMod.get('TitoloTipoPartita'),'');

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
Ext.reg('DCS_DettaglioFproPanel', DCS.dFROPanel);
	
DCS.showDetailFPr= function(titolo,rec,store)
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
					xtype: 'DCS_DettaglioFproPanel',
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