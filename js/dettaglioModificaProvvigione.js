// Crea namespace DCS
Ext.namespace('DCS');

var win; // per essere usato nelle close();

DCS.DettaglioModifica = Ext.extend(Ext.TabPanel, {
	   
	   initComponent: function() {
			DCS.recordModificaProvvigione = Ext.data.Record.create([
					{name: 'IdProvvigione', type: 'int'},
					{name: 'IdContratto', type: 'int'},
					{name: 'NumRata', type: 'int'},
					{name: 'CapAffidatoMod', type: 'float'},
					{name: 'TotAffidatoMod', type: 'float'},
					{name: 'PagatoMod', type: 'float'},
					{name: 'PagatoTotaleMod', type: 'float'},
					{name: 'InteressiMod', type: 'float'},
					{name: 'SpeseRecuperoMod', type: 'float'},
					{name: 'ImpCapitaleAffidato'}, // formattato come stringa
					{name: 'ImpTotaleAffidato'}, // formattato come stringa
					{name: 'ImpPagato'}, // formattato come stringa
					{name: 'ImpPagatoTotale'}, // formattato come stringa
					{name: 'ImpInteressi'}, // formattato come stringa
					{name: 'ImpSpese'}, // formattato come stringa
					{name: 'TipoCorrezione'},
					{name: 'FlagRataViaggiante'},
					{name: 'FlagRataViaggianteMod'},
					{name: 'DataFineAffido', type: 'date', dateFormat:'Y-m-d'},
					{name: 'DataLotto'},
					{name: 'LastUpd', xtype:'date', dateFormat:'Y-m-d H:i:s'},
					{name: 'LastUser'},
					{name: 'Nota'}
			]);
			
			var sql='select * FROM v_modificaprovvigione where IdProvvigione='+this.idProvvigione
			       +' AND IdContratto='+this.idContratto+' AND numRata='+this.numRata;
			
			/**-------------------------
			 * STORE 
			 *------------------------ */
			var dsStoreModifica = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{task: 'read'},
				reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordModificaProvvigione),
				listeners: {load: DCS.hideMask}
			});
						
			/**------------------------------------
			 * Funzione di chiamata al server
			 *----------------------------------- */
			var callServer = function (panel,task)
			{
				formModifica.getForm().submit({
					   url: 'server/provvigioni.php',
					   method: 'POST',
					   params: {
						 task: task,
						 idProvvigione: panel.idProvvigione,
						 idContratto: 	panel.idContratto,
						 numRata: 		panel.numRata
					   },
					   success: function (frm,action) 
					   		{	
						   		saveSuccess(win,frm,action,true);
		    	    			if (panel.parentStore)
		    	    			{
		    	    				DCS.showMask('Ricarico la lista rate...',true);
		    	    				panel.parentStore.reload(); // aggiorna la griglia sottostante
		    	    			}
					   		},
					   failure: saveFailure,
					   waitMsg: 'Operazione in corso...'
					});
			};
			
			/**------------------------------------
			 * BOTTONI
			 *----------------------------------- */
			// Bottone di eliminazione della modifica
			var elimina = new Ext.Button(
					{
						text: 'Elimina modifica',
						id:   'btnElimina',
						disabled: !CONTEXT.RICALCPROVV,
						handler: function(b,event)
						{
					    	Ext.Msg.confirm('Conferma', "Si desidera eliminare questa modifica al calcolo provvigioni ?",
					    			function (btn, text)
					    			{
					    	    		if (btn == 'yes')
					    	    		{
					    	    			formModifica.getForm().clearInvalid(); // evita validatori, visto che sta cancellando
					    	    			callServer(this,"deleteModifica");
					    	    		}
					    			},this); // l'ultimo parametro è lo scope, che viene passato a callServer
						},scope:this
					});
			
			// Bottone di salvataggio
			var save = new Ext.Button({
				store: dsStoreModifica,
				text: 'Salva',
				id:   'btnSave',
				disabled: !CONTEXT.RICALCPROVV,
				handler: function(b,event) 
				{
				  if (formModifica.getForm().isValid()) 
				  {
					  callServer(this,"saveModifica");
				  }
				  else
				  {
				    	Ext.MessageBox.alert('Errore', "Controlla i campi segnalati come non validi");
				  }
				},scope:this
			});	
			
			/**------------------------------------
			 * FORM
			 *----------------------------------- */
			// Campi di sola visualizzazione
			//var DataLotto 	 = {xtype:'displayfield', anchor:'80%', height:24, name:'DataLotto', fieldLabel: 'Data fine lotto', style: 'text-align:right;font-weight:bold'};
			var CapAffidato  = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpCapitaleAffidato', fieldLabel: 'Capitale affidato', style: 'text-align:right;font-weight:bold'};
			var TotAffidato  = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpTotaleAffidato', fieldLabel: 'Totale affidato', style: 'text-align:right;font-weight:bold'};
			var Pagato 		 = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpPagato', fieldLabel: 'Incassato (IPR)', style: 'text-align:right;font-weight:bold'};
			var PagatoTotale = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpPagatoTotale', fieldLabel: 'Incasso tot.(con viaggianti)', style: 'text-align:right;font-weight:bold'};
			var Interessi	 = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpInteressi', fieldLabel: 'Interessi di mora inc.', style: 'text-align:right;font-weight:bold'};
			var Spese		 = {xtype:'displayfield', anchor:'80%', height:24, name:'ImpSpese', fieldLabel: 'Spese di recupero inc.', style: 'text-align:right;font-weight:bold'};
			var RataViaggiante   = {xtype:'checkbox', anchor:'80%', height:24, name:'FlagRataViaggiante', id:'FlagRataViaggiante', fieldLabel: 'Rata viaggiante sì/no', style: 'text-align:right;font-weight:bold', disabled: true};
		
			// Campi nascosti usati nella funzione di save
			var DataFineAffido	   = {xtype:'hidden', id:'hiddenDataLotto', name:'DataLotto'};
			var ImpCapAffidatoOri  = {xtype:'hidden', id:'hiddenImpCapAffidato', name:'ImpCapitaleAffidato'};
			var ImpTotAffidatoOri  = {xtype:'hidden', id:'hiddenImpTotAffidato', name:'ImpTotaleAffidato'};
			var ImpPagatoOri  	   = {xtype:'hidden', id:'hiddenImpPagato', name:'ImpPagato'};
			var ImpPagatoTotaleOri = {xtype:'hidden', id:'hiddenImpPagatoTotale', name:'ImpPagatoTotale'};
			var ImpInteressiOri    = {xtype:'hidden', id:'hiddenImpInteressi', name:'ImpInteressi'};
			var ImpSpeseOri  	   = {xtype:'hidden', id:'hiddenImpSpese', name:'ImpSpese'};
			var RataViaggianteOri  = {xtype:'hidden', id:'hiddenFlagRataViaggiante', name:'FlagRataViaggiante'};
			var campiNascosti = {xtype: 'container',  
					items:[DataFineAffido,ImpCapAffidatoOri,ImpTotAffidatoOri,ImpPagatoOri,ImpPagatoTotaleOri,ImpInteressiOri,ImpSpeseOri,RataViaggianteOri]};

			// Campi di input
//			
//			// Data del lotto (combobox che mostra solo le date che è possibile impostare)
//			var DataLottoMod = {xtype: 'combo',
//				fieldLabel: 'spostata in',
//				hiddenName: 'DataLottoMod',
//				name: 'DataFineAffido', /* in modo che si posizioni sul valore corrente */
//				id: 'DataLottoMod',
//				editable: false,hidden: false,height:24,width: 92,
//				typeAhead: false,triggerAction: 'all',
//				lazyRender: true,
//				allowBlank: true,
//				store: {xtype:'store',
//					proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
//					baseParams:{task: 'read', sql: "SELECT idDataLotto,dataLotto FROM v_data_lotto WHERE IdProvvigione="+this.idProvvigione},
//					reader:  new Ext.data.JsonReader(
//								{root: 'results',id: 'idDataLotto'},
//								[{name: 'idDataLotto'},{name: 'dataLotto'}]),
//					sortInfo:{field: 'idDataLotto', direction: "ASC"}
//				},
//				displayField: 'dataLotto',
//				valueField: 'idDataLotto'
//			};

			var RataViaggianteMod   = {xtype:'checkbox', anchor:'80%', height:24, name:'FlagRataViaggianteMod', id:'FlagRataViaggianteMod', fieldLabel: 'Modifica viaggiante sì/no', style: 'text-align:right;font-weight:bold'};
			var space               = {xtype: 'displayfield', height:24};

			// Importi
			var CapAffidatoMod	= {xtype:'numberfield', name: 'CapAffidatoMod',id: 'CapAffidatoMod', fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};
			var TotAffidatoMod	= {xtype:'numberfield', name: 'TotAffidatoMod',id:'TotAffidatoMod', fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};
			var PagatoMod	= {xtype:'numberfield', name: 'PagatoMod',id:'PagatoMod', fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};
			var PagatoTotaleMod	= {xtype:'numberfield', name: 'PagatoTotaleMod',id: 'PagatoTotaleMod', fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};
			var InteressiMod	= {xtype:'numberfield', name: 'InteressiMod', id: 'InteressiMod', fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};
			var SpeseMod	= {xtype:'numberfield', name: 'SpeseRecuperoMod', id: 'SpeseRecuperoMod',fieldLabel: 'corretto in', width: 92,
					allowNegative: false, allowBlank: true,	style: 'text-align:right',height:24,
					decimalPrecision: 2, decimalSeparator: ','};

			// Le due colonne del form
			var colonna1 = {xtype: 'container', layout: 'form', columnWidth:.5,
							items: [CapAffidato,TotAffidato,Pagato,PagatoTotale,RataViaggiante]};
			var colonna2 = {xtype: 'container', layout: 'form', columnWidth:.5, 
							items: [CapAffidatoMod,TotAffidatoMod,PagatoMod,PagatoTotaleMod,RataViaggianteMod]};
			
			var container = {xtype: 'container', layout: "column", items:[colonna1,colonna2]};

			// fieldset che indica i campi aggiornabili a livello di pratica non di singola rata
			var colonna1a = {xtype: 'container', layout: 'form', columnWidth:.5,items: [Interessi,Spese]};
			var colonna2a = {xtype: 'container', layout: 'form', columnWidth:.5,items: [InteressiMod,SpeseMod]};
			var fieldset  = {xtype:'fieldset', title:'Importi non suddivisibili per singola rata (NB: modificare solo in casi eccezionali)',
					items:[
							{xtype: 'container', layout: "column", items:[colonna1a,colonna2a]}
					      ]};
			
			// indicazione autore ultima modifica
			var ultimaMod = {xtype:'displayfield', anchor:'92%', height:24, hidden:true, id:'ultimaModifica', name:'UltimaMod', fieldLabel: 'Registrata da', style: 'text-align:left;font-weight:bold'};
			
			// campo nota
			var nota = {xtype:'textarea', name:'Nota', fieldLabel: 'Nota', anchor: '92%', height: 80};
			
			// flag per cancellare la rata dalle provvigioni
			var flagCancellazione  = {xtype:'checkbox', anchor:'80%', height:24, name:'FlagCancellazione', id:'FlagCancellazione', boxLabel: 'Elimina questa rata dal calcolo provvigioni', style: 'text-align:right;font-weight:bold'
				,listeners: {check: function(checkbox,checked)
									{
										// disabilita (o abilita) gli altri campi, che non contano se si tratta di "cancellazione rata"
										//Ext.getCmp("DataLottoMod").setDisabled(checked);
										Ext.getCmp("FlagRataViaggianteMod").setDisabled(checked);
										Ext.getCmp("CapAffidatoMod").setDisabled(checked);
										Ext.getCmp("TotAffidatoMod").setDisabled(checked);
										Ext.getCmp("PagatoMod").setDisabled(checked);
										Ext.getCmp("PagatoTotaleMod").setDisabled(checked);
										Ext.getCmp("InteressiMod").setDisabled(checked);
										Ext.getCmp("SpeseRecuperoMod").setDisabled(checked);
										if (checked)
										{
//											Ext.getCmp("DataLottoMod").setValue('');
											Ext.getCmp("FlagRataViaggianteMod").setValue(false);
											Ext.getCmp("CapAffidatoMod").setValue("0");
											Ext.getCmp("TotAffidatoMod").setValue("0");
											Ext.getCmp("PagatoMod").setValue("0");
											Ext.getCmp("PagatoTotaleMod").setValue("0");
											Ext.getCmp("InteressiMod").setValue("0");
											Ext.getCmp("SpeseRecuperoMod").setValue("0");
										}
										else // non più cancellazione rata: rimette i valori di default
										{
											var rec = dsStoreModifica.getRange(0,0)[0];
											Ext.getCmp("FlagRataViaggianteMod").setValue(rec.json.FlagRataViaggianteMod);
											Ext.getCmp("CapAffidatoMod").setRawValue(rec.json.ImpCapitaleAffidato);
											Ext.getCmp("TotAffidatoMod").setRawValue(rec.json.ImpTotaleAffidato);
											Ext.getCmp("PagatoMod").setRawValue(rec.json.ImpPagato);
											Ext.getCmp("PagatoTotaleMod").setRawValue(rec.json.ImpPagatoTotale);
											Ext.getCmp("InteressiMod").setRawValue(rec.json.ImpInteressi);
											Ext.getCmp("SpeseRecuperoMod").setRawValue(rec.json.ImpSpese);
										}
									}
							}
				};

			// infine, il form
			var formModifica = new Ext.form.FormPanel({
				title:'Modifica manuale del calcolo provvigioni',		
				frame: true,
				bodyStyle: 'padding:5px 5px 0',
				border: false,
				labelWidth: 165,
				trackResetOnLoad: true,
				reader: new Ext.data.JsonReader({
					root: 'results',
					fields: DCS.recordModificaProvvigione
				}),
				layout: "form",
			    items: [container,fieldset,ultimaMod,nota,flagCancellazione,campiNascosti],
				buttons:[elimina,save,{text: 'Esci',handler: function () {win.close()}}]
			  });
		  
		  /**------------------------------------
		   * APPLY DELLA CLASSE
		   *----------------------------------- */
		  Ext.apply(this, {
			  activeTab:0,
			  items: [formModifica],
			  id: 'pnlModificaProvvigione'
          });
		
		  DCS.DettaglioModifica.superclass.initComponent.call(this);	
		  
		  dsStoreModifica.load({
				params:{sql: sql},
				callback: function(r, options, success){
					if (success && r.length > 0) {
						formModifica.getForm().loadRecord(r[0]);
						Ext.getCmp('FlagRataViaggiante').setValue(r[0].json.FlagRataViaggiante=='Y');
						Ext.getCmp('FlagRataViaggianteMod').setValue(r[0].json.FlagRataViaggianteMod=='Y');
						Ext.getCmp('hiddenFlagRataViaggiante').setValue(r[0].json.FlagRataViaggiante);
						Ext.getCmp('FlagCancellazione').setValue(r[0].json.TipoCorrezione=='D'); // è una cancellazione
						Ext.getCmp('hiddenDataLotto').setValue(r[0].json.DataLotto); 
						Ext.getCmp('hiddenImpCapAffidato').setValue(r[0].json.ImpCapitaleAffidato);
						Ext.getCmp('hiddenImpTotAffidato').setValue(r[0].json.ImpTotaleAffidato);
						Ext.getCmp('hiddenImpPagato').setValue(r[0].json.ImpPagato);
						Ext.getCmp('hiddenImpPagatoTotale').setValue(r[0].json.ImpPagatoTotale);
						Ext.getCmp('hiddenImpInteressi').setValue(r[0].json.ImpInteressi);
						Ext.getCmp('hiddenImpSpese').setValue(r[0].json.ImpSpese);
						if (!(r[0].json.TipoCorrezione>''))
						{
							Ext.getCmp('btnElimina').setDisabled(true); // se è una nuova correzione, disabilita il bottone "elimina"
							Ext.getCmp('ultimaModifica').hide();	
						}
						else
						{
							Ext.getCmp('ultimaModifica').setValue(r[0].json.LastUser +' - '+r[0].json.LastUpd);
							Ext.getCmp('ultimaModifica').show();
						}
					} 
				},
				scope: this
			  });
	  }
});	 

// register xtype
Ext.reg('DCS_dettaglioModifica', DCS.DettaglioModifica);

//--------------------------------------------------------
// Visualizza dettaglio modifica 
//--------------------------------------------------------
DCS.showModificaProvvigione = function(parentStore,IdProvvigione, IdContratto, numRata, codContratto, lotto)
{
	DCS.showMask('',true);			
	win = new Ext.Window({
		width: 620,
		height: 480,
		minWidth: 620,
		minHeight: 480,
		layout: 'fit',
		id: 'dettaglioModificaProvvigione',
		stateful: false,
		plain: true,
		resizable: false,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: 'Dettaglio modifica provvigione del '+ lotto + ' - Pratica '+codContratto+' - rata n. '+numRata,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioModifica',
			idProvvigione: IdProvvigione, 
			idContratto: IdContratto, 
			numRata: numRata,
			parentStore: parentStore
		}]
	});
	win.show();
};
