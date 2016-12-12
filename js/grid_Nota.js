/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormNota = function(){
	var primaVolta='S';
	var win;
	var tipologiaNota;
	var gridForm;
	var hiddenDataScad=false;
	var hiddenBtnContratto=true;
	var blankDataScad=true;
	var hiddenDataIniFin=false;
	var idPraticaN;
	var numPraticaN;
	var IdClienteN;
	var CodClienteN;
	var hiddenBtnEliminaAvv=true;
// viene reso visibile il panel visibilità solo agli utenti interni
	VisibilHidden = (CONTEXT.InternoEsterno=='E'); // gli utenti esterni non vedono la sezione destinatario
// visibilità flag riservato ed impostazione del valore della checkbox
// per default il valore è true, ma nel caso di utenti non abilitati
// imposto false	
	var RiservatoHidden=true;
	var ValueRiservato=false;
	if (CONTEXT.READ_RISERVATO){
		RiservatoHidden=false;
		ValueRiservato=true;
	}
//Define the Grid data and create the Grid
	var caricaDati = function (idPratica,idNota) {
		
		var flds = [{name: 'idUserCorrente'},
		         {name: 'rowNum'},
		         {name: 'IdNota'},
	             {name: 'IdContratto'},
	             {name: 'TipoNota'},
	             {name: 'IdUtente'},
	             {name: 'IdUtenteDest'},
	             {name: 'IdReparto'},
	             {name: 'TestoNota'},
	             {name: 'DataCreazione', type: 'date', dateFormat: 'Y-m-d'},
	             {name: 'DataScadenza', type: 'date', dateFormat: 'Y-m-d H:i:s'},
	             {name: 'OraScadenza'},
	             {name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},
	             {name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},
				 {name: 'FlagRiservato', convert: bool_db},
	             {name: 'Riservato'},
	             {name: 'autore'},
	             {name: 'destinatario'},
	             {name: 'ufficio'},
	             {name: 'visib'},
	             {name: 'TipoDestinatario'}];
	
		var dsNota = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				//where to retrieve data
				url: 'server/edit_note.php',
				method: 'POST'
			}),   
			baseParams:{task: "readNota", IdNota:idNota},	//this parameter is passed for any HTTP request
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdNota'//the property within each row object that provides an ID for the record (optional)
				}, flds
			),
			sortInfo:{field: 'DataCreazione', direction: "DESC"},
			listeners: {
				load: function(g,r,o){
					if(g.getCount()>0){
						if(r[0].get('TipoNota')=='A'){
							Ext.getCmp('btnEl').setVisible(true);
						}
					}
				}
			}
	
		});
	
		var dsUtente = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
				},[
					{name: 'IdUtente'},
					{name: 'NomeUtente'}
				]),
			autoLoad: false,
			sortInfo:{field: 'NomeUtente', direction: "ASC"}
		});
	
	
	
//    var noteExpander = new Ext.ux.grid.RowExpander({
//        tpl : new Ext.Template(
//            '<p><b>Testo:</b> {TestoNota}</p><br>'
//        )
//    });

		hiddenDataScad = (tipologiaNota=='A'); // Avvisi: senza campo data scadenza

		hiddenDataIniFin = (tipologiaNota=='S' || tipologiaNota=='N'); // note/scadenze: senza date inizio/fine
		blankDataScad = (tipologiaNota=='C' || tipologiaNota=='N'); // data scadenza opzionale per le note e le comunicazioni
		
		var newRecord = function(btn, pressed){
	   		var rec = Ext.data.Record.create(flds);
			var nRec = new rec({
	        	TipoDestinatario: 'T',
	       		IdContratto: idPratica,
				IdNota: 0,
	        	TipoNota: tipologiaNota,
				IdUtente: 0,
				IdUtenteDest: '',
	         	IdReparto: '',
	         	TestoNota: '',
	         	DataCreazione: '',
	         	DataScadenza: '',
	         	OraScadenza: '',
	         	oggi: new Date(),
	         	DataIni: new Date(),
	         	DataFin: '',
				FlagRiservato: ValueRiservato,
	         	autore: '',
	         	destinatario: '',
	         	ufficio: '',
	         	visib: ''
	    	});
			var frm = Ext.getCmp("nota-form").getForm();
			frm.loadRecord(nRec);
			var strSql  = "SELECT u.IdUtente,u.NomeUtente FROM utente u, reparto r, compagnia c";
			strSql += " where u.idreparto=r.idreparto and r.idcompagnia=c.idcompagnia and c.idtipocompagnia=1 order by nomeutente";
			dsUtente.load({
				params: {
					task: 'read', 
					sql: strSql
    			}
			});
	    };

	
		var cmbReparti = new Ext.form.ComboBox({
			hidden: true,
			hiddenName: 'id_reparto',
			anchor: '95%',
			editable: false,
	
			//create a dropdown based on server side data (from db)
			//if we enable typeAhead it will be querying database
			//so we may not want typeahead consuming resources
			typeAhead: false, 
			triggerAction: 'all',
			
			//By enabling lazyRender this prevents the combo box
			//from rendering until requested
			lazyRender: true,	//should always be true for editor
	
			//where to get the data for our combobox
			store: DCS.Store.dsReparto,
			mode: 'local',
			
			//the underlying data field name to bind to this
			//ComboBox (defaults to undefined if mode = 'remote'
			//or 'text' if transforming a select)
			displayField: 'TitoloUfficio',
			
			//the underlying value field name to bind to this
			//ComboBox
			valueField: 'IdReparto'
		});

		var cmbAgenzie = new Ext.form.ComboBox({
			hidden: true,
			hiddenName: 'id_agenzia',
			anchor: '95%',
			editable: false,
	
			//create a dropdown based on server side data (from db)
			//if we enable typeAhead it will be querying database
			//so we may not want typeahead consuming resources
			typeAhead: false, 
			triggerAction: 'all',
			
			//By enabling lazyRender this prevents the combo box
			//from rendering until requested
			lazyRender: true,	//should always be true for editor
	
			//where to get the data for our combobox
			store: DCS.Store.dsAgenzia,
			mode: 'local',
			
			//the underlying data field name to bind to this
			//ComboBox (defaults to undefined if mode = 'remote'
			//or 'text' if transforming a select)
			displayField: 'TitoloUfficio',
			
			//the underlying value field name to bind to this
			//ComboBox
			valueField: 'IdReparto'
		});

		var cmbUtenti = new Ext.form.ComboBox({
			hidden: true,
	//			fieldLabel: 'Operatore',
			hiddenName: 'IdUtenteDest',
			anchor: '95%',
			editable: false,
	
			//create a dropdown based on server side data (from db)
			//if we enable typeAhead it will be querying database
			//so we may not want typeahead consuming resources
			typeAhead: false, 
			triggerAction: 'all',
			
			//By enabling lazyRender this prevents the combo box
			//from rendering until requested
			lazyRender: true,	//should always be true for editor
	
			//where to get the data for our combobox
			store: dsUtente,
			mode: 'local',
			
			//the underlying data field name to bind to this
			//ComboBox (defaults to undefined if mode = 'remote'
			//or 'text' if transforming a select)
			displayField: 'NomeUtente',
			
			//the underlying value field name to bind to this
			//ComboBox
			valueField: 'IdUtente'
		});

		var gridForm = new Ext.FormPanel({
			id: 'nota-form',
			frame: true,
			trackResetOnLoad: true,
	//		bodyStyle: Ext.isIE ? 'padding:0 0 5px 15px;' : 'padding:5px 5px 0',
			border: false,
	/*		layout: {
				type:'vbox',
				padding:'5',
				align:'stretch'
			},
	*/		defaults:{margins:'0 0 5 0'},
			items:[
			{
				xtype: 'fieldset',
				labelWidth:85, 
				defaults: {border:false},    // Default config options for child items
				autoHeight: false,
				height: 290,
				items: [
					{
						xtype:'htmleditor',
			            fieldLabel: 'Testo',
			            id: 'TestoNota',
			            name: 'TestoNota',
			            anchor: '100%',
			            width:'100%', 
			            allowBlank: false,
			            height: 130
					},{
						xtype: 'panel',
						width:'100%',
						layout: 'column',
						items:[{
							columnWidth: 0.45,
							xtype: 'fieldset',
							border: false,
							labelWidth: 85,
							defaults: {width: 170, border:false},    // Default config options for child items
							defaultType: 'textfield',
							autoHeight: false,
							items: [{
									hidden:true,
									fieldLabel: 'IdNota',
									name: 'IdNota',
									id: 'IdNota'
								},{
									hidden:true,
									fieldLabel: 'TipoNota',
									name: 'TipoNota',
									id: 'TipoNota'
								},{	
									xtype: 'datefield',
									format: 'd/m/Y',
									hidden: true,
									value: new Date(),
									vtype: 'daterange',
									id: 'oggi',
									name: 'oggi',
									endDateField: 'DataScadenza'
								},{
//									xtype: 'datefield',
//									format: 'd/m/Y',
//									fieldLabel: 'Scadenza',
//									hidden: hiddenDataScad,
//									allowBlank: blankDataScad,
//									vtype: 'daterange',
//									name: 'DataScadenza',
//									id: 'DataScadenza',
//									minValue: new Date(),
//									startDateField: 'oggi'
//								},{						
								xtype: 'compositefield',
 	 								hidden: hiddenDataScad,
									items: [
									        {xtype: 'datefield',
									        	format: 'd/m/Y',
									        	width: 100,
									        	fieldLabel: 'Scadenza',
									        	allowBlank: blankDataScad,
									        	vtype: 'daterange',
									        	name: 'DataScadenza',
									        	id: 'DataScadenza',
									        	minValue: new Date(),
									        	disabled: hiddenDataScad,
									        	startDateField: 'oggi'},
									        {xtype: 'timefield',
								                format: 'H:i',
								                width: 60,
								                value: '',
								                vtype: 'time',
								                disabled: hiddenDataScad,
								                name: 'OraScadenza',
		                 						id: 'OraScadenza'}
									        ]
								},{
									xtype: 'datefield',
									format: 'd/m/Y',
						        	width: 100,
									fieldLabel: 'Inizio',
									hidden: hiddenDataIniFin,
									vtype: 'daterange',
									name: 'DataIni',
									disabled: hiddenDataIniFin,
									renderer: DCS.render.date,
									id: 'DataIni',
									endDateField: 'DataFin'
								},{
									xtype: 'datefield',
						        	width: 100,
									format: 'd/m/Y',
									fieldLabel: 'Fine',
									hidden: hiddenDataIniFin,
									disabled: hiddenDataIniFin,
									vtype: 'daterange',
									name: 'DataFin',
									id: 'DataFin',
									startDateField: 'DataIni'
								},{ 
									hidden: true,
									name: 'IdUtente',
									id:  'IdUtente'
								},{ 
									hidden: true,
									name: 'IdContratto',
									id: 'IdContratto'
								}]            	  
							},{
								columnWidth: 0.55,
								xtype: 'fieldset',
	            				title: 'Visibilit&agrave;',
	            				hidden: VisibilHidden,
	            				disabled: VisibilHidden,
								autoHeight: false,
								items: [
								{
	           						xtype: 'container',
									autoHeight: true,
									layout: 'form',
									items:[{
										labelStyle: 'width:300;',
		           						xtype: 'checkbox',
										boxLabel: '<span style="color:red;"><b>Riservata</b></span>',
										name: 'FlagRiservato',
										hidden: RiservatoHidden,
										disabled: RiservatoHidden,
										id: 'FlagRiservato',
										checked: true
									}]
								}, {
									xtype: 'container',
									layout: 'column',
									items: [{
										columnWidth: 0.35,
										labelWidth: 20,
										xtype: 'fieldset',
										border:false,
										defaults: {width: 100, border:false},    // Default config options for child items
										items: [{
		           							xtype: 'radiogroup',
											ref: '../rgroup',
							            	columns: 1,
							            	items: [
							                	{boxLabel: 'Tutti',   name: 'TipoDestinatario', id:'TipoDestT', inputValue: 'T', checked: true},
							                	{boxLabel: 'Agenzia', name: 'TipoDestinatario', id:'TipoDestA', inputValue: 'A'},
							                	{boxLabel: 'Reparto', name: 'TipoDestinatario', id:'TipoDestR', inputValue: 'R'},
							                	{boxLabel: 'Utente',  name: 'TipoDestinatario', id:'TipoDestU', inputValue: 'U'}
							            	],
											listeners: {
												change: function(gruppo, btn) {
													if ((btn.getGroupValue()== 'A') && (primaVolta=='N'))
														cmbAgenzie.setValue(null);
													if ((btn.getGroupValue()== 'R') && (primaVolta=='N'))
														cmbReparti.setValue(null);
													primaVolta='N';										
													cmbAgenzie.setVisible(btn.getGroupValue()== 'A');
													cmbReparti.setVisible(btn.getGroupValue()== 'R');
													cmbUtenti.setVisible(btn.getGroupValue() == 'U');
												}
											}
										}]
									},{
										columnWidth: 0.65,
										labelWidth: 10,
										border:false,
										defaults: {border:false},    // Default config options for child items
										xtype: 'fieldset',
										items: [{xtype:'spacer', height:22}, {
											xtype: 'container',
											defaults: {width: 250},
											height: 22,
											items: [cmbAgenzie]
										}, {
											xtype: 'container',
											defaults: {width: 250},
											height: 22,
											items: [cmbReparti]
										}, {
											xtype: 'container',
											defaults: {width: 250},
											height: 22,
											items: [cmbUtenti]
										}]
									}]
								}]
							}]
						}    	  
			]}],

			buttonAlign: 'left',
			buttons: [{
				text: 'Vedi pratica',
				hidden: hiddenBtnContratto,
				disabled: hiddenBtnContratto,
				handler: function() {
					win.close();
					showPraticaDetail(idPraticaN,numPraticaN,IdClienteN,CodClienteN,'',null,-1);
				},
				scope: this
			},'->',{
				text: 'Elimina',
				hidden: hiddenBtnEliminaAvv,
				id: 'btnEl',
				handler: function() {
					showAnswFormNota(Ext.getCmp('IdNota').getValue(),win);
				},
				scope: this
			},{
				text: 'Salva',
				handler: function() {
					if (gridForm.getForm().isValid()){	
						var frm = gridForm.getForm();
						var arr = frm.getFieldValues(false);
						if(checkVoidText())
						{
							frm.submit({
								url: 'server/edit_note.php',
								method: 'POST',
								
								params: {task: 'save'},
								success: function(){
									win.close();
									Ext.getCmp('avvisi_panel').load('server/avvisi.php');
									//Ext.Msg.alert('Salvataggio', 'Correttamente effettuato');
									//Ext.getCmp('gridNote').getStore().reload();
								},
								failure: function(frm, action){
									Ext.Msg.alert('Errore', action.result.error);
								},
								scope: this,
								waitMsg: 'Salvataggio in corso...'
							});
						}else{
							Ext.Msg.alert('Errore', 'Compilare il testo della nota.');
						}
					}
				},
				scope: this
			}]                
		});

		newRecord.call();
		dsNota.load({
			callback: function (rec, opt, success) {
				if (success==true){
					if (rec.length>0)
					{
						var frm = Ext.getCmp("nota-form").getForm();
						frm.loadRecord(rec[0]);
						if ((rec[0].get('IdContratto')!=='') && (rec[0].get('IdContratto')!==null)){
							var strSql  = "select u.IdUtente,u.NomeUtente from utente u where ";
							strSql += " u.idreparto IN (SELECT IdReparto FROM reparto WHERE IdCompagnia = (select idcompagnia from contratto where idcontratto="+idPratica+")) or";
							strSql += " u.idreparto=(select idagenzia from contratto where idcontratto="+rec[0].get('IdContratto')+") order by nomeutente";
						}else{
							var strSql  = "SELECT u.IdUtente,u.NomeUtente FROM utente u, reparto r, compagnia c";
							strSql += " where u.idreparto=r.idreparto and r.idcompagnia=c.idcompagnia and c.idtipocompagnia=1 order by nomeutente";
						}
						var utdest = rec[0].get('IdUtenteDest');
						dsUtente.load({
					    	params: {
								task: 'read', 
								sql: strSql
				    		},
							callback: function(rec, opt, success){
								if (success) {
									if ((utdest!==null) && (utdest!==''))
										cmbUtenti.setValue(utdest);
								}
									
								if  (Ext.getCmp('TipoDestU').checked==true)
									cmbUtenti.setVisible(true);
							}
					    });
						var idRep =	rec[0].get('IdReparto');
						DCS.Store.dsAgenzia.load({
							callback: function(rec, opt, success){
								if (success)
									cmbAgenzie.setValue(idRep);
								if  (Ext.getCmp('TipoDestA').checked==true)
									cmbAgenzie.setVisible(true);
							}
						});
						DCS.Store.dsReparto.load({
							callback: function(rec, opt, success){
								if (success)
									cmbReparti.setValue(idRep);
								if  (Ext.getCmp('TipoDestR').checked==true)
									cmbReparti.setVisible(true);
							}
						});

						
						var d1=Ext.getCmp('DataFin').getValue();
						if (d1!==''){
							if (d1.format('Y-m-d')=='9999-12-31')
								Ext.getCmp('DataFin').setValue(null);
					
						}
				
					}
				}
			}
		
		}); 
	
		return gridForm;
	};
	
	function checkVoidText(){
		var Nota=Ext.getCmp('TestoNota').getValue();
		var Nsucc=Nota;
		do
		{	
			Nota=Nsucc;
			Nsucc=Nota.replace('<br>','');
		}while(Nota!=Nsucc);
		if(Nota!='')
			return true;
		else
			return false;
	};
	
	return {
		showDetailNote: function(idPratica, numPratica, tipoNota, idNota,IdCliente,CodCliente){
			tipologiaNota=tipoNota;
			var descrTipoNota="";
			idPraticaN  = idPratica;
			numPraticaN = numPratica;
			IdClienteN  = IdCliente;
			CodClienteN = CodCliente;
			switch (tipoNota) {
				case 'C':
					descrTipoNota="comunicazioni";
					break;
				case 'A':
					descrTipoNota="avvisi";
					break;
				case 'N':
					descrTipoNota="note";
					break;
				case 'S':
					descrTipoNota="scadenze";
					break;
			}
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Lettura "+descrTipoNota+" ..."
			});
			myMask.show();
			hiddenBtnContratto=true;
			if ((idPratica!==0) && (idPratica!==''))
				hiddenBtnContratto=false;
			gridForm = caricaDati(idPratica,idNota);
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				cls: 'left-right-buttons',
				modal: true,
				width: 750,
				height: 370,
				minWidth: 750,
				minHeight: 370,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Gestione '+descrTipoNota,
				tools: [helpTool('Gestione '+descrTipoNota)],
				items: [gridForm]
			});
			win.show();
			myMask.hide();
		},
			
		griglia: function(idPratica){
			return caricaDati(idPratica);
		}
	
	}

}();

