/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormNote = function(){
	var win;
	var gridForm;
	var primaVolta='S';
// viene reso visibile il panel visibilità solo agli utenti interni
	var VisibilHidden=true;  // rimettere=true nel caso in cui occorre gestire nuovamente interno esterno
	if (CONTEXT.InternoEsterno=='I')
		VisibilHidden=false;
	// visibilità flag riservato ed impostazione del valore della checkbox
	// per default il valore è true, ma nel caso di utenti non abilitati
	// imposto false	
	var RiservatoHidden=true;
	var ValueRiservato=false;
	if (CONTEXT.READ_RISERVATO){
		RiservatoHidden=false;
		ValueRiservato=true;
	}

	var dsNota;

//Define the Grid data and create the Grid
	var create = function (idPratica) {
	
		var flds = [{name: 'idUserCorrente'},
		         {name: 'rowNum'},
		         {name: 'IdNota'},
	             {name: 'IdContratto'},
	             {name: 'TipoNota'},
	             {name: 'IdUtente'},
	             {name: 'IdUtenteDest'},
	             {name: 'IdReparto'},
	             {name: 'TestoNota'},
	             {name: 'DataCreazione', type: 'date', dateFormat: 'Y-m-d H:i:s'},
	             {name: 'DataScadenza', type: 'date', dateFormat: 'Y-m-d H:i:s'},
				 {name: 'FlagRiservato', convert: bool_db},
	             {name: 'Riservato'},
	             {name: 'autore'},
	             {name: 'destinatario'},
	             {name: 'ufficio'},
	             {name: 'visib'},
	             {name: 'TipoDestinatario'}];

		dsNota = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				//where to retrieve data
				url: 'server/edit_note.php',
				method: 'POST'
			}),   
			baseParams:{task: "readNote", tipo:'N', pratica:idPratica},	//this parameter is passed for any HTTP request
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdNota'//the property within each row object that provides an ID for the record (optional)
				}, flds
			),
			sortInfo:{field: 'DataCreazione', direction: "DESC"},
			listeners: {
				load: function(g){
					if (sm.grid!=undefined)
						sm.selectRow(0);
				}
			}
	
		});

		var strSql  = "select u.IdUtente,u.NomeUtente from utente u where ";
		strSql += " u.idreparto IN (SELECT IdReparto FROM reparto WHERE IdCompagnia = (select idcompagnia from contratto where idcontratto="+idPratica+")) or";
		strSql += " u.idreparto=(select idagenzia from contratto where idcontratto="+idPratica+")";
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
		
	
	    var noteExpander = new Ext.ux.grid.RowExpander({
	        tpl : new Ext.Template(
	            '<p><b>Testo:</b> {TestoNota}</p><br>'
	        )
	    });
	    var rowSel=0;
		sm = new Ext.grid.RowSelectionModel({
			singleSelect: true,
			listeners: {
				rowselect: function(sm, row, rec) {
					var frm = Ext.getCmp("nota-form").getForm();
					rowSel=1;
					var bsave = Ext.getCmp('btnSalva');
					bsave.setVisible(rec.get('IdUtente')==CONTEXT.IdUtente);
					bsave.ownerCt.doLayout();
					frm.loadRecord(rec);
					var idUtDest=rec.get('IdUtenteDest');
					var idRep=rec.get('IdReparto');
					dsUtente.load({
				    	params: {
							task: 'read', 
							sql: strSql
						},
						callback: function(rec, opt, success){
							if (success)
								cmbUtenti.setValue(idUtDest);
						}
				    });
					DCS.Store.dsAgenzia.load({
						callback: function(rec, opt, success){
							if (success)
								cmbAgenzie.setValue(idRep);
						}
					});
					DCS.Store.dsReparto.load({
						callback: function(rec, opt, success){
							if (success)
								cmbReparti.setValue(idRep);
						}
					});
				}
			}
		});
	
		var newRecord = function(btn, pressed){
	   		loadNewRecord();
	
			var grd = Ext.getCmp("gridNote");
			grd.getSelectionModel().clearSelections(true);
	
			var bsave = Ext.getCmp('btnSalva');
			bsave.show();
			bsave.ownerCt.doLayout();
	    };
	
		function loadNewRecord() {
	   		var rec = Ext.data.Record.create(flds);
			var nRec = new rec({
	        	TipoDestinatario: 'T',
	       		IdContratto: idPratica,
				IdNota: 0,
	        	TipoNota: 'N',
				IdUtente: 0,
				IdUtenteDest: '',
	         	IdReparto: '',
	         	TestoNota: '',
	         	DataCreazione: '',
	         	DataScadenza: '',
	         	oggi: new Date(),
	         	FlagRiservato: ValueRiservato,
	         	autore: '',
	         	destinatario: '',
	         	ufficio: '',
	         	visib: ''
	    	});
			var frm = Ext.getCmp("nota-form").getForm();
			frm.loadRecord(nRec);
	    };
	
	    if (rowSel==0){
			dsUtente.load({
		    	params: {
					task: 'read', 
					sql: strSql
				},
				callback: function(rec, opt, success){
					if (success)
						cmbUtenti.setValue(null);
				}
		    });
			DCS.Store.dsAgenzia.load({
				callback: function(rec, opt, success){
					if (success)
						cmbAgenzie.setValue(null);
				}
			});
			DCS.Store.dsReparto.load({
				callback: function(rec, opt, success){
					if (success)
						cmbReparti.setValue(null);
				}
			});
	    }
	    
		var notePanel = new Ext.Panel({
			autoHeight: false,
			height: 170,
			layout: 'fit',
			border: true,
			items: [{
				id: 'gridNote',
				xtype: 'grid',
	            store: dsNota,
				height: 160,
				autoHeight: false,
				autoExpandColumn: 'TestoNota',
				border: false,
				plugins: [noteExpander],
				sm: sm,
				listeners: {
					viewready: function(g) {
						g.getSelectionModel().selectRow(0);
					} // Allow rows to be rendered.
				},
	
				columns: [ noteExpander,
					{header: "IdNota", width: 1,hidden:true, sortable: true, locked:false, dataIndex: 'IdNota'},
					{header: "Data", xtype:'datecolumn', format:'d/m/Y H:i:s', width: 70, sortable: true, dataIndex: 'DataCreazione'},
					{header: "Testo", width: 280, sortable: false, dataIndex: 'TestoNota'},
					{header: "Autore", width: 120, sortable: true, dataIndex: 'autore'},
					{header: "Visibilit&agrave;", width: 90, sortable: true, dataIndex: 'visib'},
					{header: "Riservato", width: 75, sortable: true, hidden: RiservatoHidden, dataIndex: 'Riservato'},
				{xtype: 'actioncolumn',
	                width: 70,
	                header:'Azioni',
	                sortable:false,  filterable:false,
	                items: [{icon:"images/space.png"},{icon:"images/space.png"},
	                        {tooltip: 'Cancella',
								getClass: function(v,meta,rec) {
	                				// è possibile eliminare solo l'ultima nota e solo da colui che l'ha emessa 
					 				//if ((rec.get('rowNum')=='1') && (rec.get('idUserCorrente')==rec.get('IdUtente'))) {
						 			if (rec.get('idUserCorrente')==rec.get('IdUtente')) {
					 					return 'del-row';
					 				} else {
					 					return '';
					 				}
					 			},                    	 
	                			handler: function(grid, rowIndex, colIndex) {
									if (gridForm.getForm().isValid()){	
	
						 				var rec = dsNota.getAt(rowIndex);
										var frm = gridForm.getForm();
										var arr = frm.getFieldValues(false);
										frm.submit({
											url: 'server/edit_note.php',
											method: 'POST',
											
											params: {task: 'delete',idNotaDel: rec['id']},
											success: function(){
												loadNewRecord();
												Ext.getCmp('gridNote').getStore().reload();
											},
											failure: function(frm, action){
												Ext.Msg.alert('Errore', action.result.error);
											},
											scope: this,
											waitMsg: 'Eliminazione in corso...'
										});
									}
								}
	                        }
	                ]   // fine icone di azione su riga
	            }// fine colonna action
	         ], // fine array colonne
	         // customize view config
	
			viewConfig: {
	            forceFit:true,
	            enableRowBody:true,
	            showPreview:true,
	            getRowClass : function(record, rowIndex, p, store){
	                if(this.showPreview){
	                    p.body = '<p style="color:darkblue">&nbsp&nbsp&nbsp;'+record.TestoNota+'</p>';
	                    return 'x-grid3-row-expanded';
	                }
	                return 'x-grid3-row-collapsed';
	            }
	        },
	        // paging bar on the bottom
	        bbar: new Ext.PagingToolbar({
	            pageSize: 5,
	            id: 'nBar',
	            store: dsNota,
	            displayInfo: true,
	            displayMsg: 'Note {0} - {1} di {2}',
	            emptyMsg: "Nessuna nota",
	            items:[
	                '-', {
						xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:true, 
						id: 'bNot',
						pressed: false,
						enableToggle:false,
						text: 'Nuova nota',
						handler: newRecord}]
	        }) // fine bbar
	        } // fine proprietà grid
	       ] // fine array items del panel
		});
		var statoBottNot='';
		var barNote='';
		if(CONTEXT.BOTT_NEW_NOTE == true)
		{	
			statoBottNot = Ext.getCmp('nBar'); // barra
			barNote = statoBottNot.items.get('bNot'); //bottone 
			barNote.hidden = false;
		}
	
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
				notePanel,
			{
				xtype: 'fieldset',
				labelWidth:85, 
				defaults: {border:false},    // Default config options for child items
				autoHeight: false,
				height: 265,
				items: [{
							xtype:'htmleditor',
				            fieldLabel: 'Testo',
				            id: 'TestoNota',
				            name: 'TestoNota',
				            anchor: '100%',
				            width:'100%', 
				            allowBlank: false,
				            height: 110
						},{
						xtype: 'panel',
						width:'100%',
						layout: 'column',
						items:[{
							columnWidth: 0.4,
							xtype: 'fieldset',
							border: false,
							labelWidth: 85,
							defaults: {width: 140, border:false},    // Default config options for child items
							defaultType: 'textfield',
							autoHeight: false,
							items: [{
									hidden:true,
									fieldLabel: 'IdNota',
									name: 'IdNota'
								},{
									hidden:true,
									fieldLabel: 'TipoNota',
									name: 'TipoNota'
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
									xtype: 'datefield',
									format: 'd/m/Y',
									fieldLabel: 'Scadenza',
									vtype: 'daterange',
									name: 'DataScadenza',
									id: 'DataScadenza',
									startDateField: 'oggi'
								},{ 
									hidden: true,
									name: 'IdUtente'
								},{ 
									hidden: true,
									name: 'IdContratto'
								}]            	  
							},{
								columnWidth: 0.6,
								xtype: 'fieldset',
	            				title: 'Visibilit&agrave;',
	            				hidden: VisibilHidden,
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
										checked: true
									}]
								}, {
									xtype: 'container',
									layout: 'column',
									items: [{
										columnWidth: 0.3,
										labelWidth: 40,
										xtype: 'fieldset',
										border:false,
										defaults: {width: 60, border:false},    // Default config options for child items
										items: [{
		           							xtype: 'radiogroup',
											ref: '../rgroup',
							            	columns: 1,
							            	items: [
							                	{boxLabel: 'Tutti',   name: 'TipoDestinatario', inputValue: 'T', checked: true},
							                	{boxLabel: 'Agenzia', name: 'TipoDestinatario', inputValue: 'A'},
							                	{boxLabel: 'Reparto', name: 'TipoDestinatario', inputValue: 'R'},
							                	{boxLabel: 'Utente',  name: 'TipoDestinatario', inputValue: 'U'}
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
										columnWidth: 0.7,
										labelWidth: 10,
										border:false,
										defaults: {border:false},    // Default config options for child items
										xtype: 'fieldset',
										items: [{xtype:'spacer', height:22}, {
											xtype: 'container',
											defaults: {width: 260},
											height: 22,
											items: [cmbAgenzie]
										}, {
											xtype: 'container',
											defaults: {width: 260},
											height: 22,
											items: [cmbReparti]
										}, {
											xtype: 'container',
											defaults: {width: 260},
											height: 22,
											items: [cmbUtenti]
										}]
									}]
								}]
							}]
						}    	  
			]}],
	
			buttons: [{
				text: 'Salva',
				id: 'btnSalva',
				hidden: (CONTEXT.BOTT_NEW_NOTE != true),
				handler: function() {
					var frm = gridForm.getForm();
					var arr = frm.getFieldValues(false);
					if(Ext.getCmp('TestoNota').getValue()!='' && Ext.getCmp('TestoNota').getValue()!='<br>'){
						frm.submit({
							url: 'server/edit_note.php',
							method: 'POST',
							
							params: {task: 'save'},
							success: function(){
								Ext.getCmp('gridNote').getStore().reload();
							},
							failure: function(frm, action){
								Ext.Msg.alert('Errore', action.result.error);
							},
							scope: this,
							waitMsg: 'Salvataggio in corso...'
						});
					}else{console.log("else");}	
				},
				scope: this
			}]                
		});
	
		loadNewRecord();
		
		return gridForm;
	};

	return {
		showDetailNote: function(idPratica, numPratica, tipoNota, idNota){
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Qualche istante prego..."
			});
			myMask.show();
			gridForm = create(idPratica);
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			this.caricaDati();
			
			win = new Ext.Window({
				modal: true,
				width: 700,
				height: 520,
				minWidth: 700,
				minHeight: 520,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Elenco note pratica n. ' + numPratica,
				items: [gridForm]
			});
			win.show();
			myMask.hide();
		},
			
		griglia: function(idPratica){
			return create(idPratica);
		},

		caricaDati: function(){
			console.log("there");
			dsNota.load(); 
		}
	
	}

}();

