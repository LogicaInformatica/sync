// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridTMovimentoTab = Ext.extend(Ext.grid.GridPanel, {
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
			DCS.showDetailTMov.create('Nuova causale',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloTipoMovimento')==null || Arr[k].get('TitoloTipoMovimento')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else	
		    			confString += '<br />	-'+Arr[k].get('TitoloTipoMovimento');
		    		vectString = vectString + '|' + Arr[k].get('IdTipoMovimento');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiDecodifica.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoDec:'movimentod'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'I tipi selezionati sono stati eliminati');
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

		var fields = [{name: 'IdTipoMovimento', type: 'int'},
							{name: 'TitoloTipoMovimento'},
							{name: 'CodTipoMovimentoLegacy', type: 'string'},
							{name: 'CodTipoMovimento', type: 'string'},
							{name: 'CategoriaMovimento', type: 'string'},
							{name: 'Categoria', type: 'string'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdTipoMovimento',width:10, header:'IdTMov',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloTipoMovimento',	width:130,	header:'Causale', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CodTipoMovimentoLegacy',width:50, header:'Vecchio codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},    		        	
    		        	{dataIndex:'CodTipoMovimento',	width:50,	header:'Nuovo codice',align:'center',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'CategoriaMovimento',	width:50,	header:'CodiceCategoria',align:'right',hidden: true, hideable: false,filterable:false,groupable:false,sortable:true},
    		        	{dataIndex:'Categoria',	width:90,	header:'Categoria', hidden: false, hideable: true,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiDecodifica.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoDec:'movimentod'},
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
					if(rec.get('TitoloTipoMovimento')!=null)
						titolo = "Modifica causale '"+rec.get('TitoloTipoMovimento')+"'";
					else 
						titolo = "Modifica causale *Titolo assente*";
					DCS.showDetailTMov.create(titolo,rec,gstore);
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
							id: 'bNmovd',
							pressed: false,
							enableToggle:false,
							text: 'Nuova causale',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDmo',
							pressed: false,
							enableToggle:false,
							text: 'Cancella causale',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("CausaliMovimento"),' '
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

		DCS.GridTMovimentoTab.superclass.initComponent.call(this, arguments);
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

DCS.TipoMovimento = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>Le causali definiscono i vari tipi di movimento contabile nei partitari. L\'elenco deve rispecchiare fedelmente l\'insieme '
				+'<br>di causali previste sui sistemi centrali, ma deve essere mantenuto aggiornato manualmente (nelle registrazioni degli esiti dei flussi'
				+'<br>di acquisizione vengono segnalati eventuali codici causale non riconosciuti). La "categoria" viene invece attribuita a mano, e serve'
				+'<br>a guidare il motore nell\'interpretazione del partitario (per&ograve; in second\'ordine rispetto al "tipo partita").</span>';

			var gridTmovimento = new DCS.GridTMovimentoTab({
				titlePanel: 'Lista causali di movimento'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridTmovimento;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;
DCS.recordComboMoviMacro = Ext.data.Record.create([
   		{name: 'CategoriaMovimento'},
   		{name: 'Categoria'}]);

DCS.dMoviPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	invisibileLeg:true,
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "movimento";
		var extStore = this.store;
		var queryCombo = "SELECT CategoriaMovimento,Categoria FROM v_tipomovimento_decodifica group by CategoriaMovimento;";
		
		var ds = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryCombo,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboMoviMacro)
	    });
		
		var formAggregato = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
//			title: ' : ' + this.titoloProc,
//		    width: 450,
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
							fieldLabel: 'IdTipoMovimento',
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
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'nome',
									fieldLabel: 'Nome',
									name: 'TitoloTipo',
									allowBlank: false,
									//labelSeparator:labObb(false),
									blankText:'Denominazione obbligatoria', 
									msgTarget:'side',
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
								labelWidth: 70,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '90%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'codice',
									fieldLabel: 'Codice',
									name: 'CodTipo',
									//labelSeparator:labObb(false),
									blankText:'Codice obbligatorio', 
									msgTarget:'side',
									allowBlank: false,
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
								},{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'codiceL',
									fieldLabel: 'Vecchio codice',
									name: 'CodTipoLegacy',
									readOnly:true,
									hidden:this.invisibileLeg
								}]
							}]
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:false,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 70,
								columnWidth: 1,
								defaults: {xtype: 'combo', anchor: '95%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Categoria',
									name:'categoria',
									id:idDin+'cmbCat',
									allowBlank: false,
									//labelSeparator:labObb(false),
									blankText:'Selezionare una categoria', 
									msgTarget:'side',
									hiddenName: 'categoria',
									typeAhead: false,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: ds,
									displayField: 'Categoria',
									valueField: 'CategoriaMovimento'
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
								params: {task:"saveAgg", tipoDec:'movimentod'},
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
		
		DCS.dMoviPanel.superclass.initComponent.call(this);
		//caricamento dello store
		ds.load({
//			params:{sql:queryCombo},
			callback : function(rows,options,success) {
				if(rows.length>0){
					if(this.recordMod!=null){
						//editing
						Ext.getCmp(idDin+'id').setValue(this.recordMod.get('IdTipoMovimento'));
						Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloTipoMovimento')));
						Ext.getCmp(idDin+'codice').setValue(this.recordMod.get('CodTipoMovimento'));
						Ext.getCmp(idDin+'codiceL').setValue(this.recordMod.get('CodTipoMovimentoLegacy'));
						Ext.getCmp(idDin+'cmbCat').setValue(this.recordMod.get('Categoria'));
						Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloTipoMovimento'),''); 
					}
				}
			},
			scope:this
		});
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioMoviPanel', DCS.dMoviPanel);
	
DCS.showDetailTMov= function(titolo,rec,store)
{
	return {
		create: function(titolo,rec,store){
			var invisibileLeg=true;
			var h=215;
			if(rec!=null && rec.get('CodTipoMovimentoLegacy')!='' && rec.get('CodTipoMovimentoLegacy')!=null)
			{
				invisibileLeg=false;
				h=255;
			}
			wind = new Ext.Window({
				layout: 'fit',
				width: 500,
			    height: h,
				modal: true,
				title: titolo,
				tools: [helpTool("Causale")],
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioMoviPanel',
					titoloProc:'',
					recordMod:rec,
					invisibileLeg:invisibileLeg,
					store:store
					}]
			});
			wind.show();
			return true;
		}
	};
}();