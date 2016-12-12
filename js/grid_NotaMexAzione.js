/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormNotaMex = function(){
	var primaVolta='S';
	var win;
	var tipologiaNota;
	var gridForm;
	var hiddenBtnContratto=true;
	var idPraticaN;
	var numPraticaN;
	var IdClienteN;
	var CodClienteN;
	var hiddenBtnEliminaAvv=true;
	var rootN;
	var storeRel;
	var kidsN;
// viene reso visibile il panel visibilità solo agli utenti interni
	VisibilHidden = (CONTEXT.InternoEsterno=='E'); // gli utenti esterni non vedono la sezione destinatario
	// visibilità flag riservato ed impostazione del valore della checkbox
	// per default il valore è true, ma nel caso di utenti non abilitati
	// imposto false	
	var RiservatoHidden=true;
	var ValueRiservato=false;
	
//Define the Grid data and create the Grid
	var caricaDati = function (idPratica,idNota,window,store,risp) {
		//console.log("here");
		var storeRel=store;
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
	             {name: 'TipoDestinatario'},
	             {name: 'IdNotaPrecedente'}];
	
		var dsNota = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				//where to retrieve data
				url: 'server/edit_note.php',
				method: 'POST'
			}),   
			baseParams:{task: "readNota", IdNota:idNota, isChat:true},	//this parameter is passed for any HTTP request
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
						/*if(r[0].get('TipoNota')=='A'){
							Ext.getCmp('btnEl').setVisible(true);
						}*/
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
	         	visib: '',
	         	IdNotaPrecedente: null
	    	});
			var frm = Ext.getCmp("nota-form").getForm();
			frm.loadRecord(nRec);
			
			if(tipologiaNota=='C'){
				if((risp>0)||(rootN!=null)){
					var rNode=rootN;
					var isRootSon = rootN.substring(0,1);
					if(isRootSon=='x'){rNode=null;}
					var strSql = "select distinct tabella.IdUtente as IdUtente,u.nomeutente as NomeUtente from";
					strSql =	strSql+" (select Distinct IdUtente from nota where idnotaprecedente="+rNode;
					strSql =	strSql+" Union all";
					strSql =	strSql+" select Distinct IdUtenteDest from nota where idnotaprecedente="+rNode;
					strSql =	strSql+" Union all";
					strSql =	strSql+" select Distinct IdUtenteDest from nota where idnota="+rNode;
					strSql =	strSql+" Union all";
					strSql =	strSql+" select Distinct IdUtente from nota where idnota="+rNode+") as tabella";
					strSql =	strSql+" left join utente u on(tabella.idutente=u.idutente)";
					strSql =	strSql+" where tabella.IdUtente != "+CONTEXT.IdUtente;
				}else{
					var strSql  ="select * from ((select u.idUtente as IdUtente,u.NomeUtente as NomeUtente";
					strSql += " from utente u";
					strSql += " left join contratto c on(c.idagenzia=u.idreparto)";
					strSql += " where c.idcontratto="+idPratica+")";
					strSql += " union";
					strSql += " (SELECT ut.idUtente as IdUtente,ut.NomeUtente as NomeUtente";
					strSql += " FROM reparto rep";
					strSql += " left join compagnia com on(rep.idcompagnia=com.idcompagnia)";
					strSql += " right join utente ut on(ut.idreparto=rep.idreparto)";
					strSql += " where idtipocompagnia =1)";
					strSql += " order by nomeutente) as Alias";
				}
				dsUtente.load({
					params: {
						task: 'read', 
						sql: strSql
	    			}
				});
			}
	    };

		var cmbUtenti = new Ext.form.ComboBox({
			hidden: true,
			hiddenName: 'IdUtenteDest',
			anchor: '97%',
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
			valueField: 'IdUtente',
			listeners:{
				render: function(cmb){
					if(tipologiaNota == 'C')
						cmb.setVisible(true);
						cmb.setDisabled(true);
				} 
			}
		});
		
		var heightEditor=130;
		var sqlComboMessaggio='';
		//var elementiRadio;
		var tDest='';
		switch (tipologiaNota) {
		case 'C':
			if(idNota==0){
				//nuova comunicazione o risposta
				//elementiRadio = [{boxLabel: 'Utente',  name: 'TipoDestinatario', id:'TipoDestU', inputValue: 'U', checked: true}];
				if(rootN==null){
					//creazione
					var IdP;
					if(CONTEXT.InternoEsterno=='E'){
						IdP='IdOperatore';
					}else{
						IdP='IdAgente';
					}
					sqlComboMessaggio = "select ifnull(u.idutente,0) as IdUtente, ifnull(u.NomeUtente,'NA') as NomeUtente from utente u where u.idUtente = (select ifnull("+IdP+",0) as res from contratto where idcontratto = "+idPratica+")";
					VisibilHidden=false;
					heightEditor=160;
				}else{
					//risposta a padre
					/*if(risp==0){
						sqlComboMessaggio = "select IdUtente from nota where idnota="+rootN;
						VisibilHidden=true;
						heightEditor=180;
					}else{*/
						sqlComboMessaggio = "select distinct IdUtente from";
						sqlComboMessaggio =	sqlComboMessaggio+" (select Distinct IdUtente from nota where idnotaprecedente="+rootN;
						sqlComboMessaggio =	sqlComboMessaggio+" Union all";
						sqlComboMessaggio =	sqlComboMessaggio+" select Distinct IdUtenteDest from nota where idnotaprecedente="+rootN;
						sqlComboMessaggio =	sqlComboMessaggio+" Union all";
						sqlComboMessaggio =	sqlComboMessaggio+" select Distinct IdUtenteDest from nota where idnota="+rootN;
						sqlComboMessaggio =	sqlComboMessaggio+" Union all";
						sqlComboMessaggio =	sqlComboMessaggio+" select Distinct IdUtente from nota where idnota="+rootN+") as tabella";
						sqlComboMessaggio =	sqlComboMessaggio+" where IdUtente != "+CONTEXT.IdUtente;
						VisibilHidden=false;
						heightEditor=160;
					//}
				}
				tDest='U';
			}else{
				//editing
				//elementiRadio=[{}];
				sqlComboMessaggio="";
				VisibilHidden=true;
				heightEditor=180;
			}
			break;
		case 'N':
			//elementiRadio = [{boxLabel: 'Tutti',   name: 'TipoDestinatario', id:'TipoDestT', inputValue: 'T', checked: true}];
			tDest='T';
			VisibilHidden=false;
			heightEditor=160;
			break;
		}
		
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
				height: 220,
				items: [
					{
						xtype:'htmleditor',
			            fieldLabel: 'Testo',
			            id: 'TestoNota',
			            name: 'TestoNota',
			            anchor: '100%',
			            width:'100%', 
			            allowBlank: false,
			            height: heightEditor
					},{
						xtype: 'panel',
						width:'100%',
						layout: 'column',
						items:[{
							columnWidth: 0.6,
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
									hidden: true,
									name: 'IdUtente',
									id:  'IdUtente'
								},{ 
									hidden: true,
									name: 'IdContratto',
									id: 'IdContratto'
								},{
									hidden:true,
									name: 'IdNotaPrecedente',
									id: 'IdNotaPrecedente'
								},{xtype:'spacer', height:22}]            	  
							},{
								columnWidth: 0.4,
								xtype: 'fieldset',
	            				//title: 'Visibilit&agrave;',
	            				hidden: VisibilHidden,
	            				disabled: VisibilHidden,
								autoHeight: false,
								border: false,
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
									items: [/*{
										columnWidth: 0.3,
										labelWidth: 20,
										xtype: 'fieldset',
										border:false,
										defaults: {width: 100, border:false},    // Default config options for child items
										items: [{
		           							xtype: 'radiogroup',
											ref: '../rgroup',
							            	columns: 1,
							            	items: [elementiRadio],
											listeners: {
												change: function(gruppo, btn) {
													primaVolta='N';										
													cmbUtenti.setVisible(btn.getGroupValue() == 'U');
												}
											}
										}]
									},*/{
										columnWidth: 0.25,
										xtype:'label',
										hidden:true,
										text: 'Destinatario',
										name: 'labDest',
										id: 'labDest'
									},{
										columnWidth: 0.75,
										labelWidth: 10,
										border:false,
										id:'fieldDest',
										defaults: {border:false},    // Default config options for child items
										xtype: 'fieldset',
										items: [{
											xtype: 'container',
											defaults: {width: 210},
											height: 22,
											items: [cmbUtenti]
										}]
									}]
								}]
							}]
						}    	  
					]
			}],
			
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
				disabled:true,
				id: 'btnEl',
				handler: function() {
					showAnswFormNota(Ext.getCmp('IdNota').getValue(),win,window,idPratica,numPraticaN,storeRel);
				},
				scope: this
			},{
				text: 'Salva',
				id: 'btnSave',
				disabled:true,
				handler: function() {
					if (gridForm.getForm().isValid()){	
						var frm = gridForm.getForm();
						var arr = frm.getFieldValues(false);
						
						if(cmbUtenti.getValue()!='Scegliere un destinatario')
						{
							if(checkVoidText())
							{
								frm.submit({
									url: 'server/edit_note.php',
									method: 'POST',
									
									params: {task: 'save', idPadre:rootN, isChat:true, tDest:tDest},
									success: function(){
										win.hide();
										win.close();
										
										Ext.Ajax.request({
											url: 'server/edit_ramiNote.php', method:'POST',
											params :{task:'readTree',IdPratica:idPraticaN},
											callback : 	function(r,options,success) 
														{
											 				var idRamoSelezionato = 0;
															var myMask = new Ext.LoadMask(Ext.getBody(), {
																msg: "Aggiornamento note..."
															});											
															myMask.show();
															var arrayStr = '';
														 	arrayStr =  success.responseText;
														 	var child = Ext.util.JSON.decode(arrayStr); 
														 	
														 	var nroot=new Ext.tree.AsyncTreeNode({
													            expanded: true,
													            children: child
													        });
														 	if(window!=null){
														 		Ext.getCmp('winRami').setRootNode(nroot);
														 	}else{
														 		Ext.getCmp('treeNotePratica').setRootNode(nroot);
														 	}
														 	myMask.hide();
														},
											failure:	function (obj)
														{
															Ext.MessageBox.alert('Errore', 'Errore durante la lettura dei rami.');
														},
											scope: this
										});
										if(storeRel != null)
											storeRel.reload();
									},
									failure: function(frm, action){
										Ext.Msg.alert('Errore', action.result.error);
									},
									scope: this,
									waitMsg: 'Salvataggio in corso...'
								});
							}else{
								Ext.MessageBox.alert('Errore', 'Compilare il campo di testo.');
							}
						}else{
							Ext.MessageBox.alert('Errore', 'Campo destinatario non determinato.');
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
						Ext.Ajax.request({
							url: 'server/AjaxRequest.php', 
				    		params : {	task: 'read',
										sql: "select count(*) as NP FROM nota where IdNotaPrecedente="+idNota
									},
							method: 'POST',
							reader:  new Ext.data.JsonReader(
				    					{
				    						root: 'results',//name of the property that is container for an Array of row objects
				    						id: 'NP'//the property within each row object that provides an ID for the record (optional)
				    					},
				    					[{name: 'NP'}]
				    				),
							success: function ( result, request ) {
								eval('var resp = ('+result.responseText+').results[0]');
								Ext.getCmp("nota-form").doLayout();
								if (resp != undefined)
								{
									if(resp.NP!='')
									{
										switch (resp.NP) {
											case '0':
												var isRootSon = rootN.substring(0,1);
												if(isRootSon=='x'){//se è figlio di radice
													if(((Ext.getCmp('IdUtente').getValue()==CONTEXT.IdUtente)||(CONTEXT.profiles[1]!=undefined))&&(rec[0].get('IdNotaPrecedente')==null)){
														Ext.getCmp('btnEl').setVisible(true);
														Ext.getCmp('btnEl').setDisabled(false);
													}
												}
												break;
											default:
												Ext.getCmp('btnEl').setVisible(false);
												break;
										}
									}
								}
							},
				    		failure: function ( result, request) { 
				    			Ext.MessageBox.alert('Errore', result.responseText); 
				    		},
				    		autoLoad: true
				    	});
						
					}
					if(sqlComboMessaggio==''){
						Ext.getCmp('btnSave').setDisabled(false);
					}
				}
			}
		
		}); 
		
		if(sqlComboMessaggio!=''){
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php', 
	    		params : {	task: 'read',
							sql: sqlComboMessaggio
						},
				method: 'POST',
				reader:  new Ext.data.JsonReader(
	    					{
	    						root: 'results',//name of the property that is container for an Array of row objects
	    						id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
	    					},
	    					[{name: 'IdUtente'},
	    					{name: 'NomeUtente'}]
	    				),
				success: function ( result, request ) {
					eval('var resp = ('+result.responseText+').results[0]');
					if (resp != undefined)
					{
						if((resp.NomeUtente!='NA')&&(resp.NomeUtente!=''))
						{
							console.log("dentro");
							cmbUtenti.setValue(resp.IdUtente);
							cmbUtenti.setDisabled(false);
						}else{
							console.log("dentrofuori");
							cmbUtenti.setValue('Scegliere un destinatario');
							cmbUtenti.setDisabled(false);
						}
					}else{
						console.log("fuori");
						cmbUtenti.setValue('Scegliere un destinatario');
						cmbUtenti.setDisabled(false);
					}
					Ext.getCmp('labDest').setVisible(true);
					Ext.getCmp('btnSave').setDisabled(false);
				},
	    		failure: function ( result, request) { 
	    			Ext.MessageBox.alert('Errore', result.responseText); 
	    		},
	    		autoLoad: true
	    	});
		}
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
		showDetailNoteMex: function(idPratica, numPratica, tipoNota, idNota,IdCliente,CodCliente,
				root,window,store,isStorico){
			tipologiaNota=tipoNota;
			var descrTipoNota="";
			idPraticaN  = idPratica;
			numPraticaN = numPratica;
			IdClienteN  = IdCliente;
			CodClienteN = CodCliente;
			rootN=root;
			var titolo;
			var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
			//console.log("rootN "+rootN);
			if(idNota==''){
				if(root==null){
					titolo = 'Creazione';
				}else{
					titolo = 'Risposta';
				}
			}else{
				titolo = 'Modifica';
			}
			
			switch (tipoNota) {
				case 'C':
					descrTipoNota="messaggi";
					titolo = titolo+" "+descrTipoNota;
					break;
				case 'N':
					descrTipoNota="note";
					titolo = titolo+" "+descrTipoNota;
					break;
			}

			
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Lettura "+descrTipoNota+" ..."
			});
			myMask.show();
			hiddenBtnContratto=true;
			if ((idPratica!==0) && (idPratica!==''))
				hiddenBtnContratto=false;
			store = store || null;
			
			if(rootN!=null){
				var isRootSon = rootN.substring(0,1);
				var rootVar='';
				if(isRootSon=='x'){//se è figlio di radice
					rootVar=0;
				}else{
					rootVar=rootN;
				}
			}else{
				rootVar=0;
			}
			//controlla se è una risposta di base o se stiamo facendo 
			//una risposta ad un thread già popolato con altri operatori.
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php', 
	    		params : {	task: 'read',
							sql: "select count(*)as num from "+schema+".nota where idnotaprecedente="+rootVar
						},
				method: 'POST',
				reader:  new Ext.data.JsonReader(
	    					{
	    						root: 'results',//name of the property that is container for an Array of row objects
	    						id: 'num'//the property within each row object that provides an ID for the record (optional)
	    					},
	    					[{name: 'num'}]
	    				),
				success: function ( result, request ) {
					eval('var resp = ('+result.responseText+').results[0]');
					if (resp != undefined)
					{						
						gridForm = caricaDati(idPratica,idNota,window,store,resp.num);
						
						//set flag riservato
						if (CONTEXT.READ_RISERVATO && tipologiaNota=='N'){
							RiservatoHidden=false;
						}else{
							RiservatoHidden=true;
						} 	
						Ext.getCmp('FlagRiservato').checked=!RiservatoHidden;
						Ext.getCmp('FlagRiservato').setVisible(!RiservatoHidden);
						Ext.getCmp('FlagRiservato').setDisabled(RiservatoHidden);
						
						gridForm.addButton('Chiudi', function() {win.close();}, this);
						
						win = new Ext.Window({
							cls: 'left-right-buttons',
							modal: true,
							width: 770,
							height: 300,
							minWidth: 770,
							minHeight: 300,
							layout: 'fit',
							plain: true,
							constrain: true,
							title: titolo,
							items: [gridForm]
						});
						win.show();
						myMask.hide();
					}else{
						Ext.MessageBox.alert('Errore', result.responseText);
					}
				},
	    		failure: function ( result, request) { 
	    			Ext.MessageBox.alert('Errore', result.responseText); 
	    		},
	    		autoLoad: true
	    	});
		},
			
		griglia: function(idPratica){
			return caricaDati(idPratica);
		}
	
	};

}();

