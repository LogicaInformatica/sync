// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridFilialeTab = Ext.extend(Ext.grid.GridPanel, {
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
			DCS.showDetailFILIORG.create('Nuova filiale ',null,gstore,true);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloFiliale')==null || Arr[k].get('TitoloFiliale')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloFiliale');
		    		vectString = vectString + '|' + Arr[k].get('IdFiliale');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiOrganizzativi.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoOrg:'filialeO'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le filiali selezionate sono state eliminate.');
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

		var fields = [{name: 'IdFiliale', type: 'int'},
		              		{name: 'IdArea', type: 'int'},
		              		{name: 'CodFiliale'},
		              		{name: 'TitoloFiliale'},
							{name: 'MailPrincipale'},
							{name: 'MailResponsabile'},
							{name: 'CodArea'},
							{name: 'TitoloArea'},
							{name: 'TipoArea'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdFiliale',width:10, header:'IdFiliale',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdArea',width:10, header:'IdArea',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'CodArea',width:10, header:'CodArea',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TipoArea',width:10, header:'TipoArea',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TitoloFiliale',	width:100,	header:'Filiale', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'CodFiliale',width:50, header:'Codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloArea',	width:100,	header:'Area', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'MailPrincipale',	width:100,	header:'Mail principale', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'MailResponsabile',	width:100,	header:'Mail responsabile', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiOrganizzativi.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoOrg:'filialeO'},
			remoteSort: false,
			groupField: this.groupOn,
			groupOnSort: false,
//			multiSortInfo:{ 
//                sorters: [{field: 'TitoloUfficio', direction: "ASC"}], 
//                direction: 'ASC'},
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
			autoExpandColumn:5,
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
					if(rec.get('TitoloFiliale')!=null)
						titolo = "Modifica filiale "+name+" '"+rec.get('TitoloFiliale')+"'";
					else 
						titolo = "Modifica filiale "+name+" *Titolo assente*";
					DCS.showDetailFILIORG.create(titolo,rec,gstore);
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
							id: 'bNfilio',
							pressed: false,
							enableToggle:false,
							text: 'Nuova filiale',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDfilio',
							pressed: false,
							enableToggle:false,
							text: 'Cancella filiale',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Filiali"),' '
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

		DCS.GridFilialeTab.superclass.initComponent.call(this, arguments);
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

DCS.FilialiOrg = function(){

	return {
		create: function(){
		
		
			var subtitle = "<br>L'elenco delle filiali (cio&egrave; delle Funzioni di vendita, deve essere aggiornato manualmente ogni volta"
					+"<br>che si modifica l'organizzazione interna del mandatario. Si noti che gli indirizzi di mail indicati vengono usati"
					+"<br>per l' invio di alcune comunicazioni.";
			var gridFiliali = new DCS.GridFilialeTab({
				titlePanel: 'Lista delle filiali <span class="subtit">'+subtitle+'</span>',
				flex: 1,
				task: "readMainGrid"
			});

			return gridFiliali;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.recordComboAree = Ext.data.Record.create([
                		{name: 'IdArea'},
                		{name: 'CodArea'},
                		{name: 'TitoloArea'}]);

DCS.dFiliOrgPanel = Ext.extend(Ext.Panel, {
	recordMod:null,
	titoloProc:'',
	Wmain:'',
	invisibileLeg:true,
	store:null,
	tipo:'',
	initComponent: function() {
		var bDisa=true;
		var titProc = this.titoloProc;
		var chkCreaCompagnia = this.invisibileLeg;
		var idDin = "filialiOrg";
		var extStore = this.store;
		var queryComboAR = "SELECT IdArea,CodArea,TitoloArea FROM area Where TipoArea = 'C';";
		var dsAree = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboAR,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboAree)
	    });
		
		var selected=false;
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
					width: 670,
					items: [{
						xtype: 'textfield',
						fieldLabel: 'IdFiliale',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'idfiliale',
						name: 'idfiliale'
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
								labelWidth: 95,
								columnWidth: 0.65,
								defaults: {xtype: 'textfield', anchor: '95%'},
								items: [{	
									style: 'nowrap',
//									width: 100,
									style: 'text-align:left',
									id:idDin+'nome',
									fieldLabel: 'Denominazione',
									name: 'nomeC',
									allowBlank: false,
									//labelSeparator:labObb(false),
									blankText:'Denominazione obbligatoria', 
									msgTarget:'side',
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
								labelWidth: 50,
								columnWidth: 0.35,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
									style: 'text-align:left',
									id:idDin+'sigla',
									fieldLabel: 'Codice',
									name: 'siglaC',
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
								}]
							}]
						},{//1° fieldset relazioni : 2 combo
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							title:'Relazioni',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 78,
								columnWidth: 0.55,
								defaults: {xtype: 'combo', anchor: '92%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Area',
									name:'area',
									id:idDin+'cmbarea',
									allowBlank: false,
									//labelSeparator:labObb(false),
									blankText:'Selezionare un area', 
									msgTarget:'side',
									hiddenName: 'area',
									typeAhead: false,
									disabled:false,
									lazyInit:true,
									editable:true,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsAree,
									displayField: 'TitoloArea',
									valueField: 'IdArea',
									listeners:{
										select: function(combo, record, index){
												Ext.getCmp(idDin+'idarea').setValue(record.get('IdArea'));
												if(Ext.getCmp(idDin+'nome').getValue()!='' && Ext.getCmp(idDin+'sigla').getValue()!='')
													Ext.getCmp(idDin+'bSave').setDisabled(false);
												selected=true;
										},
										change : function(combo, newValue,oldValue ){
											var indice=combo.getStore().find('TitoloArea', newValue);
											if(indice==-1)
											{
												if(!selected){
													combo.setValue('');
													Ext.getCmp(idDin+'idarea').setValue('');
												}
											}else{
												combo.setValue(combo.getStore().getAt(indice).get('IdArea'));
												combo.fireEvent('select',combo,combo.getStore().getAt(indice),indice); 
												if(Ext.getCmp(idDin+'nome').getValue()!='' && Ext.getCmp(idDin+'sigla').getValue()!='')
													Ext.getCmp(idDin+'bSave').setDisabled(false);
											}
											selected=false;
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 200,
								columnWidth: 0.45,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:true,
									hidden:true,
									disabled:true,
									style: 'text-align:left',
									id:idDin+'idarea',
									fieldLabel: 'idarea',
									name: 'idarea',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							}]
						},{//2° fieldset 
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							title:'Informazioni generali',
//							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 110,
								width:500,
//								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'emailprin',
									fieldLabel: 'E-mail principale',
									name: 'emailprin',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 110,
//								columnWidth: 0.50,
								width:500,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'emailresp',
									fieldLabel: 'E-mail responsabile',
									name: 'emailresp',
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
								params: {task:"saveAgg", tipoOrg:'filialeO'},
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
		
		DCS.dFiliOrgPanel.superclass.initComponent.call(this);
		//editing
		dsAree.load({
			callback : function(rows,options,success) 
			{
				if(rows.length>0){
					if(this.recordMod!=null){
						Ext.getCmp(idDin+'idfiliale').setValue(this.recordMod.get('IdFiliale'));
						Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloFiliale')));
						Ext.getCmp(idDin+'sigla').setValue(this.recordMod.get('CodFiliale'));
						Ext.getCmp(idDin+'cmbarea').setValue(this.recordMod.get('IdArea'));
						Ext.getCmp(idDin+'idarea').setValue(this.recordMod.get('IdArea'));
						Ext.getCmp(idDin+'emailprin').setValue(this.recordMod.get('MailPrincipale'));
						Ext.getCmp(idDin+'emailresp').setValue(this.recordMod.get('MailResponsabile'));

						Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloFiliale'),'');
					}
				}
			},
			scope:this
		});
	}	// fine initcomponent
});
Ext.reg('DCS_DettaglioFilialeOrgPanel', DCS.dFiliOrgPanel);
	
DCS.showDetailFILIORG = function()
{
	return {
		create: function(titolo,rec,store,flagNew){
			var invisibileLeg=true;
			flagNew = flagNew||false;
			if(flagNew)
				invisibileLeg=false;
			wind = new Ext.Window({
				layout: 'fit',
				width: 700,
			    height: 336,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioFilialeOrgPanel',
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