// Crea namespace DCS
Ext.namespace('DCS');

var winS;
var winSAz;

DCS.GridRepartoTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: 'TitoloTipoReparto',
	
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
			DCS.showDetailAGEORG.create('Nuova agenzia/reparto ',null,gstore,true);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	if(Arr.length>0){
		    	for(var k=0;k<Arr.length;k++){
		    		if(Arr[k].get('TitoloUfficio')==null || Arr[k].get('TitoloUfficio')=='')
		    			confString += '<br />	- *Titolo assente*';
		    		else
		    			confString += '<br />	-'+Arr[k].get('TitoloUfficio');
		    		vectString = vectString + '|' + Arr[k].get('IdReparto');
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare: "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneTipiOrganizzativi.php',
					        method: 'POST',
					        params: {task: 'delete',vect: vectString, tipoOrg:'agenziaO'},
					        success: function(obj) {
					            var resp = obj.responseText;
					            console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le definizioni selezionate sono state eliminate.');
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

		var fields = [{name: 'IdReparto', type: 'int'},
		              		{name: 'IdTipoReparto', type: 'int'},
		              		{name: 'IdCompagnia', type: 'int'},
		              		{name: 'CodUfficio'},
		              		{name: 'CodTipoReparto'},
							{name: 'TitoloUfficio'},
							{name: 'TitoloTipoReparto'},
							{name: 'TitoloCompagnia'},
							{name: 'NomeReferente'},
							{name: 'FaxRep'},
							{name: 'TelefonoRep'},
							{name: 'Fax'},
							{name: 'EmailReferente'},
							{name: 'EmailFatturazione'},
							{name: 'TelefonoPerClienti'},
							{name: 'MaxSMSContratto'},
							{name: 'FlagDelega'},
							{name: 'NomeBanca'},
							{name: 'IBAN'},
							{name: 'Nota'},
							{name: 'LastUser'},
							{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];

    	var columns = [selM,
    	               	{dataIndex:'IdReparto',width:10, header:'IdReparto',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdTipoReparto',width:10, header:'IdTipoReparto',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'IdCompagnia',width:10, header:'IdCompagnia',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'CodTipoReparto',width:10, header:'CodTipoReparto',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'TitoloUfficio',	width:100,	header:'Agenzia/Reparto', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'CodUfficio',width:50, header:'Codice',align:'center',hidden: false, hideable: false, filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloTipoReparto',	width:100,	header:'Tipo ag./rep.', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'TitoloCompagnia',	width:100,	header:'Societ&agrave;', hideable: false,filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'NomeReferente',	width:100,	header:'Referente', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TelefonoRep',	width:70,	header:'Tel. principale', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'FaxRep',	width:70,	header:'Fax', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'EmailReferente',	width:80,	header:'E-mail referente', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'EmailFatturazione',	width:80,	header:'E-mail fatturazione', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TelefonoPerClienti',	width:80,	header:'Tel. per clienti', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'MaxSMSContratto',width:20, header:'Max SMS',hidden: false, hideable: false,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'FlagDelega',width:16, exportable:false, renderer:DCS.render.spunta, header:'Delega',align:'center', sizable:false, menuDisabled:true, hideable:false, groupable:false, sortable:false, hidden:false},
    		        	{dataIndex:'NomeBanca',	width:80,	header:'Banca', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'IBAN',	width:80,	header:'IBAN', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Nota',	width:80,	header:'Nota', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneTipiOrganizzativi.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, tipoOrg:'agenziaO'},
			remoteSort: true,
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
					if(rec.get('TitoloUfficio')!=null)
						titolo = "Modifica agenzia/reparto "+name+" '"+rec.get('TitoloUfficio')+"'";
					else 
						titolo = "Modifica agenzia/reparto "+name+" *senza nome*";
					DCS.showDetailAGEORG.create(titolo,rec,gstore);
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
							id: 'bNageo',
							pressed: false,
							enableToggle:false,
							text: 'Nuova agenzia/reparto',
							handler: newRecord
							},
					'-', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/delete.gif',
							hidden:false, 
							id: 'bDageo',
							pressed: false,
							enableToggle:false,
							text: 'Cancella agenzia/reparto',
							handler: delRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Agenzie"),' '
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

		DCS.GridRepartoTab.superclass.initComponent.call(this, arguments);
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

DCS.RepartiOrg = function(){

	return {
		create: function(){
			var subtitle = "<br>Ogni agenzia di recupero fa parte di una Societ&agrave; di recupero (che di solito coincide con l'agenzia);"
				+"<br>per essere operativa, deve essere associata ad opportune regole di affidamento oltre a contenere almeno un utente definito";
			var gridReparti = new DCS.GridRepartoTab({
				titlePanel: 'Lista delle agenzie di recupero e dei reparti <span class="subtit">'+subtitle+'</span>',
				flex: 1,
				task: "readMainGrid"
			});

			return gridReparti;
		}
	};
	
}();

//--------------------------------------------------------
// Inserimento/editing
//--------------------------------------------------------
var wind;

DCS.recordComboTreparto = Ext.data.Record.create([
                		{name: 'IdTipoReparto'},
                		{name: 'CodTipoReparto'},
                		{name: 'TitoloTipoReparto'}]);
DCS.recordComboCompagnia = Ext.data.Record.create([
                  		{name: 'IdCompagnia'},
                  		{name: 'TitoloCompagnia'}]);

DCS.dCageOrgPanel = Ext.extend(Ext.Panel, {
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
		var idDin = "agenzieOrg";
		var extStore = this.store;
		var queryComboTR = "SELECT IdTipoReparto,CodTipoReparto,TitoloTipoReparto FROM tiporeparto;";
		var queryComboCMP = "select IdCompagnia,TitoloCompagnia from compagnia WHERE IdTipoCompagnia<3 order by TitoloCompagnia asc;";
		var dsTrep = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboTR,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboTreparto)
	    });
		var dsCompany = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{sql:queryComboCMP,task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordComboCompagnia)
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
					width: 700,
					items: [{
						xtype: 'textfield',
						fieldLabel: 'IdReparto',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						id:idDin+'idreparto',
						name: 'idreparto'
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
								labelWidth: 90,
								columnWidth: 0.55,
								defaults: {xtype: 'combo', anchor: '92%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Tipo ag./rep.',
									name:'treparto',
									id:idDin+'cmbTReparto',
									allowBlank: false,
									//labelSeparator:labObb(false),
									blankText:'Selezionare un tipo', 
									msgTarget:'side',
									hiddenName: 'treparto',
									typeAhead: false,
									disabled:false,
									lazyInit:false,
									editable:true,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsTrep,
									displayField: 'TitoloTipoReparto',
									valueField: 'IdTipoReparto',
									listeners:{
										select: function(combo, record, index){
												Ext.getCmp(idDin+'idtiporep').setValue(record.get('IdTipoReparto'));
												selected=true;
										},
										change : function(combo, newValue,oldValue ){
											var indice=combo.getStore().find('TitoloTipoReparto', newValue);
											if(indice==-1)
											{
												if(!selected){
													combo.setValue('');
													Ext.getCmp(idDin+'idtiporep').setValue('');
												}
											}else{
												combo.setValue(combo.getStore().getAt(indice).get('IdTipoReparto'));
												combo.fireEvent('select',combo,combo.getStore().getAt(indice),indice); 
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
									style: 'padding-left:0px; anchor:"0%";',
									fieldLabel: 'Crea automaticamente una societ&agrave; omonima?',
	           						xtype: 'checkbox',
	           						id:idDin+'chkcrea',
									name:'FlagCrea',
									hiddenName: 'FlagCrea',
									checked: false,
									hidden: chkCreaCompagnia,
									listeners:{
										check: function(box,checked){
											Ext.getCmp(idDin+'cmbCompagnia').setDisabled(checked);
											if(!checked && Ext.getCmp(idDin+'cmbCompagnia').getValue()=='' )
												Ext.getCmp(idDin+'bSave').setDisabled(true);
											else if(Ext.getCmp(idDin+'nome').getValue()!='' && Ext.getCmp(idDin+'sigla').getValue()!='')
												Ext.getCmp(idDin+'bSave').setDisabled(false);
										}
									}
								},{	
									style: 'nowrap',
//										width: 30,
									readOnly:true,
									hidden:true,
									disabled:true,
									style: 'text-align:left',
									id:idDin+'idtiporep',
									fieldLabel: 'idtiporep',
									name: 'idtiporep',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.55,
								defaults: {xtype: 'combo', anchor: '92%'},
								items: [{
									xtype: 'combo',
									fieldLabel: 'Societ&agrave;',
									name:'compagnia',
									id:idDin+'cmbCompagnia',
									allowBlank: true,
									hiddenName: 'compagnia',
									typeAhead: false,
									disabled:false,
									lazyInit:false,
									editable:false,
									triggerAction: 'all',
									lazyRender: true,	//should always be true for grid editor
									store: dsCompany,
									displayField: 'TitoloCompagnia',
									valueField: 'IdCompagnia',
									listeners:{
										select: function(combo, record, index){
												Ext.getCmp(idDin+'idcompagnia').setValue(record.get('IdCompagnia'));
												if(Ext.getCmp(idDin+'nome').getValue()!='' && Ext.getCmp(idDin+'sigla').getValue()!='')
													Ext.getCmp(idDin+'bSave').setDisabled(false);
												selected=true;
										},
										change : function(combo, newValue,oldValue ){
											var indice=combo.getStore().find('TitoloCompagnia', newValue);
											if(indice==-1)
											{
												if(!selected){
													combo.setValue('');
													Ext.getCmp(idDin+'idcompagnia').setValue('');
													if(!Ext.getCmp(idDin+'chkcrea').getValue())
														Ext.getCmp(idDin+'bSave').setDisabled(true);
												}
											}else{
												combo.setValue(combo.getStore().getAt(indice).get('TitoloCompagnia'));
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
								labelWidth: 40,
								columnWidth: 0.45,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:true,
									hidden:true,
									disabled:true,
									style: 'text-align:left',
									id:idDin+'idcompagnia',
									fieldLabel: 'idcompagnia',
									name: 'idcompagnia',
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
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: .69,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'referenteName',
									fieldLabel: 'Referente',
									name: 'referenteN',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'telefonorep',
									fieldLabel: 'Tel. societ&agrave',
									name: 'telefonorep',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'faxrep',
									fieldLabel: 'Fax societ&agrave',
									name: 'faxrep',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'emailref',
									fieldLabel: 'E-mail referente',
									name: 'emailref',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'emailfatt',
									fieldLabel: 'E-mail fatturazione',
									name: 'emailfatt',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '92%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									allowBlank:true,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'telclienti',
									fieldLabel: 'Tel. per clienti',
									name: 'telclienti',
									listeners:{
										change : function(field, newValue,oldValue ){
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.50,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{	
									style: 'nowrap',
//										width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'maxsms',
									fieldLabel: 'Max. SMS',
									name: 'maxsms',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							}]
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:true,
							title:'Dettagli bancari',
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.80,
								defaults: {xtype: 'textfield', anchor: '73%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'nomebanca',
									fieldLabel: 'Banca',
									name: 'nomebanca',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.20,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'padding-left:0px; anchor:"0%";',
									fieldLabel: 'Delega',
	           						xtype: 'checkbox',
	           						id:idDin+'chkdelega',
									name:'FlagDelega',
									hiddenName: 'FlagDelega',
									checked: false
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 90,
								columnWidth: 0.80,
								defaults: {xtype: 'textfield', anchor: '73%'},
								items: [{	
									style: 'nowrap',
//									width: 30,
									readOnly:false,
									hidden:false,
									disabled:false,
									style: 'text-align:left',
									id:idDin+'iban',
									fieldLabel: 'IBAN',
									name: 'iban',
									listeners:{
										change : function(field, newValue,oldValue ){}
									}
								}]
							}]
						},{
							xtype: 'panel',
							layout: 'form',
							labelWidth: 90,
							columnWidth: 1,
							defaults: {xtype: 'textarea', anchor: '99%'},
							items: [{	
								style: 'nowrap',
								readOnly:false,
								hidden:false,
								disabled:false,
								autoScroll:true,
								height:50,
								style: 'text-align:left',
								id:idDin+'note',
								fieldLabel: 'Note',
								name: 'note',
								listeners:{
									change : function(field, newValue,oldValue ){}
								}
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
								params: {task:"saveAgg", tipoOrg:'agenziaO'},
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
		
		DCS.dCageOrgPanel.superclass.initComponent.call(this);
		//editing
		dsTrep.load({
			callback : function(rows,options,success) 
			{
				dsCompany.load({
					callback : function(rows,options,success) 
					{
						if(rows.length>0){
							if(this.recordMod!=null){
								Ext.getCmp(idDin+'idreparto').setValue(this.recordMod.get('IdReparto'));
								Ext.getCmp(idDin+'nome').setValue(replace_Tospecial_chars(this.recordMod.get('TitoloUfficio')));
								Ext.getCmp(idDin+'sigla').setValue(this.recordMod.get('CodUfficio'));
								Ext.getCmp(idDin+'cmbTReparto').setValue(this.recordMod.get('IdTipoReparto'));
								Ext.getCmp(idDin+'idtiporep').setValue(this.recordMod.get('IdTipoReparto'));
								Ext.getCmp(idDin+'cmbCompagnia').setValue(this.recordMod.get('IdCompagnia'));
								Ext.getCmp(idDin+'idcompagnia').setValue(this.recordMod.get('IdCompagnia'));
								Ext.getCmp(idDin+'referenteName').setValue(this.recordMod.get('NomeReferente'));
								Ext.getCmp(idDin+'telefonorep').setValue(this.recordMod.get('TelefonoRep'));
								Ext.getCmp(idDin+'faxrep').setValue(this.recordMod.get('FaxRep'));
								Ext.getCmp(idDin+'emailref').setValue(this.recordMod.get('EmailReferente'));
								Ext.getCmp(idDin+'emailfatt').setValue(this.recordMod.get('EmailFatturazione'));
								Ext.getCmp(idDin+'telclienti').setValue(this.recordMod.get('TelefonoPerClienti'));
								Ext.getCmp(idDin+'maxsms').setValue(this.recordMod.get('MaxSMSContratto'));
								Ext.getCmp(idDin+'nomebanca').setValue(this.recordMod.get('NomeBanca'));
								Ext.getCmp(idDin+'chkdelega').setValue(this.recordMod.get('FlagDelega')=="Y"?true:false);
								Ext.getCmp(idDin+'iban').setValue(this.recordMod.get('IBAN'));
								Ext.getCmp(idDin+'note').setValue(replace_Tospecial_chars(this.recordMod.get('Nota')));
		
								Ext.getCmp(idDin+'nome').fireEvent('change',Ext.getCmp(idDin+'nome'),this.recordMod.get('TitoloUfficio'),'');
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
Ext.reg('DCS_DettaglioAgenziaOrgPanel', DCS.dCageOrgPanel);
	
DCS.showDetailAGEORG = function()
{
	return {
		create: function(titolo,rec,store,flagNew){
			var invisibileLeg=true;
			flagNew = flagNew||false;
			if(flagNew)
				invisibileLeg=false;
			wind = new Ext.Window({
				layout: 'fit',
				width: 720,
			    height: 576,
				modal: true,
				title: titolo,
				resizable:false,
				items: [{
					xtype: 'DCS_DettaglioAgenziaOrgPanel',
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