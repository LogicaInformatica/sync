// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridAreaGeoTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: '',
	tipoArea: '',
	
	initComponent : function() { 
		
		var IdMain = this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		var tipoA = this.tipoArea;
		if (tipoA=='R') 
			this.groupOn = 'TitoloAreaParent';
		var name = tipoA=='R'?'geografica':'commerciale';
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			DCS.showDetailAGEO.create('Nuova area '+name,null,gstore,tipoA);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloArea')==null || Arr[k].get('TitoloArea')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloArea');
		    		vectString = vectString + '|' + Arr[k].get('IdArea');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiOrganizzativi.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoOrg:'areaGeoO'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le aree selezionate sono stati eliminate.');
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

		var fields = [{name: 'IdArea', type: 'int'},
		              		{name: 'CodArea'},
							{name: 'TitoloArea'},
							{name: 'TipoArea'},
							{name: 'Cap'},
							{name: 'SiglaProvincia'},
							{name: 'TitoloProvincia'},
							{name: 'IdAreaParent', type: 'int'},
							{name: 'TitoloAreaParent'},
							{name: 'ordinatore', type: 'int'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdArea',width:10, header:'IdArea',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'ordinatore',width:10, header:'Ordinatore',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdAreaParent',width:10, header:'IdAreaParent',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TitoloArea',	width:100,	header:'Area', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'CodArea',width:50, header:'Codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'Cap',width:50, header:'Cap',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloProvincia',	width:100,	header:'Provincia', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'SiglaProvincia',width:50, header:'Sigla',align:'center',hidden: true, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloAreaParent',	width:110,	header:'Macroarea', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TipoArea',width:50, header:'TipoArea',align:'center',hidden: true, hideable: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiOrganizzativi.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoOrg:'areaGeoO', tipoArea: tipoA},
			remoteSort: false,
			groupField: this.groupOn,
			groupOnSort: false,
			multiSortInfo:{ 
                sorters: [{field: 'ordinatore', direction: "ASC"}
                		,{field: 'TitoloAreaParent', direction: "ASC"}], 
                direction: 'ASC'},
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
			autoExpandColumn:4,
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
					if(rec.get('TitoloArea')!=null)
						titolo = "Modifica area "+name+" '"+rec.get('TitoloArea')+"'";
					else 
						titolo = "Modifica area "+name+" *Titolo assente*";
					DCS.showDetailAGEO.create(titolo,rec,gstore,tipoA);
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
							id: 'bNago',
							pressed: false,
							enableToggle:false,
							text: 'Nuova area',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDago',
							pressed: false,
							enableToggle:false,
							text: 'Cancella area',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton((tipoA=='R')?"AreaGeografica":"AreaCommerciale"),' '
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

		DCS.GridAreaGeoTab.superclass.initComponent.call(this, arguments);
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

DCS.AreaGeoOrg = function(){

	return {
		create: function(tipo){
			var name = tipo=='R'?'geografiche':'commerciali';
			var subtitle;
			if (tipo=='R')
				subtitle = "<br>Le aree geografiche (regioni e province) sono usate in quelle regole di affidamento che dipendono dalla residenza del cliente."
					+"<br>Devono essere aggiornate manualmente solo in occasione di modifiche amministrative (ad es. la crezione di una nuova provincia).";
			else
				subtitle = "<br>Le aree commerciali sono utilizzate nella definizione dei contratti e della zona di influenza di ciascun dealer (tabella Filiali).";
			
			var gridAreaGeo = new DCS.GridAreaGeoTab({
				titlePanel: 'Lista aree '+name+'<span class="subtit">'+subtitle+'</span>',
				flex: 1,
				task: "readMainGrid",
				tipoArea:tipo
			});

			return gridAreaGeo;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;
DCS.recordComboAreaSuper = Ext.data.Record.create([
                  		{name: 'IdAreaC'},
                  		{name: 'TitAreaC'},
                 		{name: 'CodAreaC'}]);
DCS.recordComboProvince = Ext.data.Record.create([
                		{name: 'SiglaProvincia'},
                		{name: 'TitoloProvincia'}]);

DCS.dAgeoPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	invisibileLeg:true,
	store:null,
	tipo:'',
	initComponent: function() {
		var tipoA=this.tipo;
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "AreeGeoOrg"+tipoA;
		var extStore = this.store;
		var queryComboAreaSuper = "select null as IdAreaC,'Macroarea' as TitAreaC, null as CodAreaC "+ 
					"union all "+ 
					"select IdArea,TitoloArea,CodArea from v_area_geo_organizzazione "+ 
					"where tipoArea = '"+tipoA+"';";
		var queryCombo = "SELECT SiglaProvincia,TitoloProvincia FROM provincia order by TitoloProvincia asc;";
		var dsAsuper = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboAreaSuper,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboAreaSuper)
	    });
		var dsPro = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryCombo,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboProvince)
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
						xtype: 'textfield',
						fieldLabel: 'isEditing',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'flagEditing',
						name: 'flagEditGeo'
					},{
						xtype: 'textfield',
						fieldLabel: 'idArea',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'idarea',
						name: 'idarea'
					},{
						xtype: 'panel',
						layout: 'form',
						labelWidth: 65,
						columnWidth: 1,
						defaults: {xtype: 'textfield', anchor: '99%'},
						items: [{
							xtype: 'fieldset',
							autoHeight: true,
							border:false,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 85,
								columnWidth: 0.75,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'nome',
									fieldLabel: 'Denominazione',
									name: 'nomeGeo',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='' && Ext.getCmp(idDin+'sigla').getValue()!='')
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
								labelWidth: 45,
								columnWidth: 0.25,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									style: 'text-align:left',
									id:idDin+'sigla',
									fieldLabel: 'Codice',
									name: 'siglaGeo',
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
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							title:'Informazioni',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 75,
								columnWidth: 0.77,
								defaults: {xtype: 'combo', anchor: '97%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Provincia',
									name:'provincia',
									id:idDin+'cmbProv',
									allowBlank: true,
									hiddenName: 'provincia',
									typeAhead: false,
									disabled:true,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsPro,
									displayField: 'TitoloProvincia',
									valueField: 'SiglaProvincia',
									listeners:{
										select: function(combo, record, index){
											Ext.getCmp(idDin+'codiceProv').setValue(record.get('SiglaProvincia'));
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
									readOnly:false,
									allowBlank:true,
									disabled:true,
									style: 'text-align:left',
									id:idDin+'cap',
									fieldLabel: 'Cap',
									name: 'cap',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								},{	
									style: 'nowrap',
//										width: 30,
									readOnly:true,
									hidden:true,
									disabled:true,
									style: 'text-align:left',
									id:idDin+'codiceProv',
									fieldLabel: 'Codice',
									name: 'CodProv',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							}]
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							title:'Aggregato all\' area',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 75,
								columnWidth: 0.77,
								defaults: {xtype: 'combo', anchor: '97%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Macroarea',
									name:'areaParent',
									id:idDin+'cmbSuperArea',
									allowBlank: true,
									hiddenName: 'areaParent',
									typeAhead: false,
									lazyInit:true,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsAsuper,
									displayField: 'TitAreaC',
									valueField: 'IdAreaC',
									listeners:{
										select: function(combo, record, index){
											if(index==0){
												Ext.getCmp(idDin+'cmbProv').allowBlank=true;
												Ext.getCmp(idDin+'cmbProv').setDisabled(true);
												Ext.getCmp(idDin+'codiceProv').setDisabled(true);
												Ext.getCmp(idDin+'cap').setDisabled(true);
											}else{
//												Ext.getCmp(idDin+'cmbProv').allowBlank=false;
												Ext.getCmp(idDin+'cmbProv').setDisabled(false);
												Ext.getCmp(idDin+'codiceProv').setDisabled(false);
												Ext.getCmp(idDin+'cap').setDisabled(false);
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
								url: 'server/gestioneTipiOrganizzativi.php', method: 'POST',
								params: {task:"saveAgg", tipoOrg:'areaGeoO', tipoArea:tipoA},
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
		
		DCS.dAgeoPanel.superclass.initComponent.call(this);
		//editing
		dsAsuper.load({
//			params:{sql:queryCombo},
			callback : function(rows,options,success) {
				dsPro.load({
		//			params:{sql:queryCombo},
					callback : function(rows,options,success) {
						if(rows.length>0){
							if(this.recordMod!=null){
								Ext.getCmp(idDin+'idarea').setValue(this.recordMod.get('IdArea'));
								Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloArea')));
								Ext.getCmp(idDin+'sigla').setValue(this.recordMod.get('CodArea'));
								Ext.getCmp(idDin+'cmbProv').setValue(this.recordMod.get('SiglaProvincia'));
								Ext.getCmp(idDin+'codiceProv').setValue(this.recordMod.get('SiglaProvincia'));
								Ext.getCmp(idDin+'cap').setValue(this.recordMod.get('Cap'));
								var idPadre = this.recordMod.get('IdAreaParent');
								if(idPadre==0)
									Ext.getCmp(idDin+'cmbSuperArea').setValue(null);
								else{
									Ext.getCmp(idDin+'cmbSuperArea').setValue(idPadre);
									Ext.getCmp(idDin+'cmbProv').allowBlank=false;
									Ext.getCmp(idDin+'cmbProv').setDisabled(false);
									Ext.getCmp(idDin+'codiceProv').setDisabled(false);
									Ext.getCmp(idDin+'cap').setDisabled(false);
								}
								Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloArea'),'');
							}else{
								Ext.getCmp(idDin+'flagEditing').setValue(null);
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
Ext.reg('DCS_DettaglioAreaGeoPanel', DCS.dAgeoPanel);
	
DCS.showDetailAGEO= function(titolo,rec,store,tipo)
{
	return {
		create: function(titolo,rec,store,tipo){
			var invisibileLeg=true;
			wind = new Ext.Window({
				layout: 'fit',
				width: 500,
			    height: 310,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioAreaGeoPanel',
					titoloProc:'',
					recordMod:rec,
					invisibileLeg:invisibileLeg,
					store:store,
					tipo:tipo
					}]
			});
			wind.show();
			return true;
		}
	};
}();