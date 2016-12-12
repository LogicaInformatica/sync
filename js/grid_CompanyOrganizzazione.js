// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridCompanyTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: '',
	tipoCompagnia: null,
	
	initComponent : function() { 
		
		var IdMain = this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
		var tipoC = this.tipoCompagnia;
		var name = tipoC==1?'mandatarie':(tipoC==2)?'di recupero':'dealer';
		if (tipoC==3) // dealer raggruppati per provincia
			this.groupOn = 'TitoloProvincia';
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			DCS.showDetailCORG.create('Nuova societ&agrave; '+name,null,gstore,tipoC);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloCompagnia')==null || Arr[k].get('TitoloCompagnia')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloCompagnia');
		    		vectString = vectString + '|' + Arr[k].get('IdCompagnia');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiOrganizzativi.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoOrg:'compagniaO', tipoCompagnia:tipoC},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le societ&agrave selezionate sono state eliminate.');
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

		var fields = [{name: 'IdCompagnia', type: 'int'},
		              		{name: 'IdTipoCompagnia', type: 'int'},
		              		{name: 'CodCompagnia'},
							{name: 'TitoloCompagnia'},
							{name: 'Cap'},
							{name: 'SiglaProvincia'},
							{name: 'TitoloProvincia'},
							{name: 'Indirizzo'},
							{name: 'Localita'},
							{name: 'Telefono'},
							{name: 'Fax'},
							{name: 'NomeTitolare'},
							{name: 'EmailTitolare'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdCompagnia',width:10, header:'IdCompagnia',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdTipoCompagnia',width:10, header:'IdTipoCompagnia',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'SiglaProvincia',width:50, header:'Sigla',align:'center',hidden: true, hideable: true, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloCompagnia',	width:100,	header:'Societ&agrave;', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'CodCompagnia',width:50, header:'Codice',align:'center',hidden: false, hideable: true, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloProvincia',	width:90,	header:'Provincia', filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Indirizzo',	width:100,	header:'Indirizzo',filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Localita',	width:90,	header:'Localita',filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Cap',width:50, header:'Cap',align:'center',hidden: false, filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Telefono',	width:70,	header:'Telefono',filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Fax',	width:70,	header:'Fax', filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'NomeTitolare',	width:80,	header:'Nome titolare',filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'EmailTitolare',	width:80,	header:'E-mail', filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiOrganizzativi.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoOrg:'compagniaO', tipoCompagnia: tipoC},
			remoteSort: false,
			groupField: this.groupOn,
			groupOnSort: false,
			multiSortInfo:{ 
                sorters: [{field: 'TitoloCompagnia', direction: "ASC"}], 
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
					if(rec.get('TitoloCompagnia')!=null)
						titolo = "Modifica societ&agrave; "+name+" '"+rec.get('TitoloCompagnia')+"'";
					else 
						titolo = "Modifica societ&agrave; "+name+" *Titolo assente*";
					DCS.showDetailCORG.create(titolo,rec,gstore,tipoC);
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
							id: 'bNcmpo',
							pressed: false,
							enableToggle:false,
							text: 'Nuova societ&agrave;',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDcmpo',
							pressed: false,
							enableToggle:false,
							text: 'Cancella societ&agrave;',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Societa"+(tipoC==1?'Mandatarie':(tipoC==2)?'Recupero':'Dealer')),' '
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

		DCS.GridCompanyTab.superclass.initComponent.call(this, arguments);
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

DCS.CompanyOrg = function(){

	return {
		create: function(tipo){
			var name = tipo==1?'mandatarie':(tipo==2)?'di recupero':'dealer';
			var subtitle;
			switch (tipo)
			{
				case 1:
					subtitle = '<br><b>Attenzione</b>: non aggiungere o cancellare righe da questa lista a meno che il sistema non sia stato opportunamente preparato'
						+'<br>con l\'aggiunta o la cancellazione dei corrispondenti mandatari a livello di flussi e di motore interno.';
					break;
				case 2:
					subtitle = '<br>Le Societ&agrave; di recupero sono le case madri delle varie agenzie di recupero: nel caso pi&ugrave; comune,'
						+'<br>ogni societ&agrave; di recupero si fa coincidere con l\' agenzia avente lo stesso nome.';
					break;
				case 3:
					subtitle = '<br>Questa tabella viene aggiornata automaticamente tramite i flussi giornalieri di dati acquisiti'
						+'<br>dai sistemi centrali; non deve essere quindi aggiornata manualmente se non in casi eccezionali.';
					break;
			}
			var gridCompany = new DCS.GridCompanyTab({
				titlePanel: 'Lista societ&agrave; '+name	+'<span class="subtit">'+subtitle+'</span>',
				flex: 1,
				task: "readMainGrid",
				tipoCompagnia:tipo
			});

			return gridCompany;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.recordComboProvince = Ext.data.Record.create([
                		{name: 'SiglaProvincia'},
                		{name: 'TitoloProvincia'}]);

DCS.dCcmpOrgPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	invisibileLeg:true,
	store:null,
	tipo:'',
	initComponent: function() {
		var tipoC=this.tipo;
		var bDisa=true;
		var titProc = this.titoloProc;
		var idDin = "CompagniaOrg_"+tipoC;
		var extStore = this.store;
		var queryCombo = "SELECT SiglaProvincia,TitoloProvincia FROM provincia order by TitoloProvincia asc;";
		var dsPro = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryCombo,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboProvince)
	    });
		var selected=false;
		var formAggregato = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
//			title: ' : ' + this.titoloProc,
		    width: 500,
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
						xtype: 'textfield',
						fieldLabel: 'isEditing',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'flagEditing',
						name: 'flagEditGeo'
					},{
						xtype: 'textfield',
						fieldLabel: 'IdCompagnia',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'idcompagnia',
						name: 'idcompagnia'
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
									name: 'nomeC',
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
									name: 'siglaC',
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
							title:'Informazioni generali',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 0.60,
								defaults: {xtype: 'combo', anchor: '97%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Provincia',
									name:'provincia',
									id:idDin+'cmbProv',
									allowBlank: true,
									hiddenName: 'provincia',
									typeAhead: false,
									disabled:false,
									lazyInit:true,
									editable:true,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsPro,
									displayField: 'TitoloProvincia',
									valueField: 'SiglaProvincia',
									listeners:{
										select: function(combo, record, index){
												Ext.getCmp(idDin+'codiceProv').setValue(record.get('SiglaProvincia'));
												selected=true;
										},
										change : function(combo, newValue,oldValue ){
											var indice=combo.getStore().find('TitoloProvincia', newValue);
											if(indice==-1)
											{
												if(!selected){
													combo.setValue('');
													Ext.getCmp(idDin+'codiceProv').setValue('');
												}
											}else{
												combo.setValue(combo.getStore().getAt(indice).get('TitoloProvincia'));
												combo.fireEvent('select',combo,combo.getStore().getAt(indice),indice); 
											}
											selected=false;
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.40,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'localita',
									fieldLabel: 'Localit&agrave',
									name: 'localita',
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
							title:'Dettaglio',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 0.75,
								defaults: {xtype: 'textfield', anchor: '78%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'address',
									fieldLabel: 'Indirizzo',
									name: 'address',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.25,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'cap',
									fieldLabel: 'Cap',
									name: 'cap',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 0.55,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'tel',
									fieldLabel: 'Telefono',
									name: 'telefono',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.45,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'fax',
									fieldLabel: 'Fax',
									name: 'fax',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 60,
								columnWidth: 0.55,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'titolare',
									fieldLabel: 'Titolare',
									name: 'titolare',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 40,
								columnWidth: 0.45,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'mail',
									fieldLabel: 'Email',
									name: 'mail',
									listeners:{
										change : function(field, newValue,oldValue ){}
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
								params: {task:"saveAgg", tipoOrg:'compagniaO', tipoCompagnia:tipoC},
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
		
		DCS.dCcmpOrgPanel.superclass.initComponent.call(this);
		//editing
		dsPro.load({
//			params:{sql:queryCombo},
			callback : function(rows,options,success) {
				if(rows.length>0){
					if(this.recordMod!=null){
						Ext.getCmp(idDin+'idcompagnia').setValue(this.recordMod.get('IdCompagnia'));
						Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloCompagnia')));
						Ext.getCmp(idDin+'sigla').setValue(this.recordMod.get('CodCompagnia'));
						Ext.getCmp(idDin+'cmbProv').setValue(this.recordMod.get('SiglaProvincia'));
						Ext.getCmp(idDin+'codiceProv').setValue(this.recordMod.get('SiglaProvincia'));
						Ext.getCmp(idDin+'cap').setValue(this.recordMod.get('Cap'));
						Ext.getCmp(idDin+'localita').setValue(this.recordMod.get('Localita'));
						Ext.getCmp(idDin+'address').setValue(this.recordMod.get('Indirizzo'));
						Ext.getCmp(idDin+'tel').setValue(this.recordMod.get('Telefono'));
						Ext.getCmp(idDin+'fax').setValue(this.recordMod.get('Fax'));
						Ext.getCmp(idDin+'titolare').setValue(this.recordMod.get('NomeTitolare'));
						Ext.getCmp(idDin+'mail').setValue(this.recordMod.get('EmailTitolare'));

						Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloCompagnia'),'');
					}else{
						Ext.getCmp(idDin+'flagEditing').setValue(null);
					}
				}
			},
			scope:this
		});
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioCompanyOrgPanel', DCS.dCcmpOrgPanel);
	
DCS.showDetailCORG= function(titolo,rec,store,tipo)
{
	return {
		create: function(titolo,rec,store,tipo){
			var invisibileLeg=true;
			var h = 400;
			wind = new Ext.Window({
				layout: 'fit',
				width: 550,
			    height: 360,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioCompanyOrgPanel',
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