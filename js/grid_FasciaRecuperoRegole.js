// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridFasciaRecuperoTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: 'Gruppo',

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
			DCS.showDetailFRec.create('Nuova fascia di recupero',null,gstore);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('FasciaRecupero')==null || Arr[k].get('FasciaRecupero')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else	
		    			confString += '<br />	-'+Arr[k].get('FasciaRecupero');
		    		vectString = vectString + '|' + Arr[k].get('FasciaRecupero');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiRegola.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoReg:'fasciaRecupero'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le fasce selezionate sono state eliminate.');
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

		var fields = [{name: 'FasciaRecupero', type: 'String'},
							{name: 'FY'},
							{name: 'Valore', type: 'int'},
							{name: 'Ordine', type: 'int'},
							{name: 'ENDFY'},
							{name: 'Gruppo', type: 'string'},
							{name: 'DataIni', type:'date'},
							{name: 'DataFin', type:'date'}];

    	var columns = [selM,
    		        	{dataIndex:'FasciaRecupero',	width:130,	header:'Fascia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Valore',width:60, header:'Valore %',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Ordine',	width:50,	header:'Ordine', align:'center', hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'FY',	width:90,	header:'Anno inizio validit&agrave',align:'center',hidden: false, hideable: false,filterable:false,groupable:false,sortable:true},
    		        	{dataIndex:'ENDFY',	width:90,	header:'Anno fine validit&agrave',align:'center',hidden: false, hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'DataIni',	width:90,xtype:'datecolumn', format:'d/m/y',header:'Inizio',align:'center',hidden:false, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'DataFin',	width:90,xtype:'datecolumn', format:'d/m/y',header:'Fine',align:'center',hidden:false, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'Gruppo',	width:50,	header:'Gruppo', hidden: false, hideable: false,filterable:true,groupable:false,sortable:true}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiRegola.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoReg:'fasciaRecupero'},
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
			autoExpandColumn:1,
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
					if(rec.get('FasciaRecupero')!=null)
						titolo = "Modifica fascia di recupero '"+rec.get('FasciaRecupero')+"'";
					else 
						titolo = "Modifica fascia di recupero *Titolo assente*";
					DCS.showDetailFRec.create(titolo,rec,gstore);
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
							id: 'bNFrec',
							pressed: false,
							enableToggle:false,
							text: 'Nuova fascia',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDFrec',
							pressed: false,
							enableToggle:false,
							text: 'Cancella fascia',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("FamiglieProdotto"),' '//nuovo help
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

		DCS.GridFasciaRecuperoTab.superclass.initComponent.call(this, arguments);
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

DCS.FasciaRecuperoReg = function(){

	return {
		create: function(){
			var subtitle = '<span class="subtit"><br>'
				+'<br>'
				+'<br></span>';
			var gridFRec = new DCS.GridFasciaRecuperoTab({
				titlePanel: 'Lista delle fasce di recupero'+subtitle,
				//title: 'Utenti presenti',
				//groupOn: "TipoAutomatismo",
				flex: 1,
				task: "readMainGrid"
			});

			return gridFRec;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.dFRECPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	isNew:true,
//	invisibileLeg:true,
	store:null,
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "fasceRecReg";
		var extStore = this.store;
		
		var formAggregato = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
//			title: ' : ' + this.titoloProc,
		    width: 420,
//		    height: 200,
		    autoHeight: true,
		    labelWidth:100,
//		    trackResetOnLoad: true,
//				reader: new Ext.data.ArrayReader({
//					root: 'results',
//					fields: this.recordProcedura}),
			items: [{
					xtype: 'container',
					autoHeight: true,
					width: 420,
					items: [{
						xtype: 'panel',
						layout: 'form',
						labelWidth: 65,
						columnWidth: 1,
						defaults: {xtype: 'textfield', anchor: '99%'},
						items: [{
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 1,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'nome',
									fieldLabel: 'Nome',
									name: 'TitoloReg',
									allowBlank: false,
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){

										}
									}
								},{	
									style: 'nowrap',
//									width: 100,
									hidden:true,
									style: 'text-align:left',
									id:idDin+'oldName',
									fieldLabel: 'oldNome',
									name: 'oldName'
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '85%'},
								items: [{	
									xtype:'numberfield',
									allowNegative: false,
									minValue :0,
									allowBlank: false,
									decimalPrecision: 2,
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'valore',
									fieldLabel: 'Valore %',
									name: 'valore'
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 50,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '85%'},
								items: [{	
									allowBlank: false,
									maxLength:1,
									maxLengthText: 'La lunghezza massima del testo è di 1 carattere.',
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'gruppo',
									fieldLabel: 'Gruppo',
									name: 'gruppo'
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 50,
								columnWidth: 0.33,
								defaults: {xtype: 'textfield', anchor: '95%'},
								items: [{	
									xtype:'numberfield',
									allowNegative: false,
									minValue :1,
									allowBlank: false,
									decimalPrecision: 0,
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'ordine',
									fieldLabel: 'Ordine',
									name: 'Ordine'
								}]
							}]
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: .5,
								defaults: {anchor: '98%'},
								items: [{	
									xtype:'numberfield',
									allowNegative: false,
									minValue :0,
									allowBlank: false,
									decimalPrecision: 0,
									style: 'nowrap',
									style: 'text-align:left',
									id:idDin+'ny',
									fieldLabel: 'Anno fiscale dal',
									name: 'ny',
									enableKeyEvents: true
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: .5,
								defaults: {anchor: '98%'},
								items: [{	
									xtype:'numberfield',
									allowNegative: false,
									minValue :0,
									allowBlank: false,
									decimalPrecision: 0,
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'endny',
									fieldLabel: 'al',
									name: 'endny',
									enableKeyEvents: true
								}]
							}
							,validityDatesInColumns(90)]
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
		    		 	if (formAggregato.getForm().isDirty()) 
						{
		    		 		if (formAggregato.getForm().isValid()){
								this.setDisabled(true);
								this.isNew=this.recordMod==null?true:false;
								formAggregato.getForm().submit({
									url: 'server/gestioneTipiRegola.php', method: 'POST',
									params: {task:"saveAgg", tipoReg:'fasciaRecupero',nuovo:this.isNew},
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
		
		DCS.dFRECPanel.superclass.initComponent.call(this);
		//caricamento degli store
		
		if(this.recordMod!=null){
			//editing
			Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('FasciaRecupero')));
			Ext.getCmp(idDin+'oldName').setValue(replace_Tospecial_chars(this.recordMod.get('FasciaRecupero')));
			Ext.getCmp(idDin+'valore').setValue(this.recordMod.get('Valore'));
			Ext.getCmp(idDin+'gruppo').setValue(this.recordMod.get('Gruppo'));
			Ext.getCmp(idDin+'ordine').setValue(this.recordMod.get('Ordine'));
			Ext.getCmp(idDin+'ny').setValue(this.recordMod.get('FY'));
			Ext.getCmp(idDin+'endny').setValue(this.recordMod.get('ENDFY'));
			Ext.getCmp('DataIni').setValue(this.recordMod.get('DataIni'));
			Ext.getCmp('DataFin').setValue(this.recordMod.get('DataFin'));
			Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('FasciaRecupero'),'');
		}
		Ext.getCmp(idDin+'bSave').setDisabled(false);
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioFrecPanel', DCS.dFRECPanel);
	
DCS.showDetailFRec= function(titolo,rec,store)
{
	return {
		create: function(titolo,rec,store){

			wind = new Ext.Window({
				layout: 'fit',
				width: 450,
			    height: 320,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioFrecPanel',
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