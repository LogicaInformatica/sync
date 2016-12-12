<?php 
// formAzioneNoteMex
// Genera la struttura del form di tipo "NoteMex" per inserire note e messaggi su una pratica
// Contenuto: 

$impDebito = getScalar("SELECT ImpInsoluto FROM contratto where IdContratto = ".$ids);


?>
/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');
DCS.RegPianoRateazione = function(){
	return {
		registra: function(idContratto,impDebito,idPianoRientro,idGrid){
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Caricamento in corso"
			});
			myMask.show();
			
		//**********************************INIZIO GESTIONE GRID RATE***************************************

		var arrayDataRate = [];
		var importoRata;
		var numRate;
		
		//renderer function
		function loadRate(idPianoRientro) {
		
			var selectRate = "SELECT NumRata, IdPianoRientro,Importo, date_format(DataPrevista,'%d/%m/%Y') as DataPrevista" 
			+" FROM ratapiano"
			+" WHERE IdPianoRientro="+idPianoRientro+" ORDER BY NumRata ASC";	

			Ext.Ajax.request({
				url : 'server/AjaxRequest.php',
				method : 'POST',
				params : {
					task: 'read', sql: selectRate
				},
				success : function(results, request) {
					if(results !="" && results != undefined)
					{	
						eval('var Rate = ('+results.responseText+').results');
						if(Rate != undefined)
						{
							arrayDataRate = [];
							var sommaR = 0;
							for(var j=0; j<Rate.length;j++){
								arrayDataRate[j] = [Rate[j].NumRata,Rate[j].IdPianoRientro,Rate[j].DataPrevista,Rate[j].Importo];
								sommaR = sommaR + parseFloat(Rate[j].Importo);
							}
							refreshStoreLocal();
							Ext.getCmp("TotaleAvereRateazione").setValue(sommaR);
						}
					}	

				},
				failure : function(results, request) {
					Ext.Msg.alert("Caricamento Rate", results.responseText);
				}
			});
			
		}
			
		var gridRate = null;
		
		var selM = new Ext.grid.CheckboxSelectionModel({
			printable : false,
			singleSelect : false
		});
		
		var storeRate = new Ext.data.ArrayStore({
			data : arrayDataRate,
			fields : ['NumRata', 'IdPianoRientro','DataPrevista','Importo']
		});

		function refreshStoreLocal(){
			storeRate.loadData(arrayDataRate);
			gridRate.setTitle()
			
		}

		var cmRich = new Ext.grid.ColumnModel({
			// specify any defaults for each column
			defaults : {
				sortable : true // columns are not sortable by default
			},
			columns : [selM,{
				dataIndex : 'NumRata',
				hidden : false,
				hideable : false,
				header: 'Num. rata'
			},{
				header : 'IdPianoRientro',
				hidden : true,
				hideable : false,
				dataIndex : 'IdPianoRientro'
			},{
				header : 'Data scad.',
				dataIndex : 'DataPrevista'
			},{
				header : 'Importo',
				dataIndex : 'Importo',
				align : 'center'
			}]
		});
		
		var tbarItemsRich = [{
			xtype : 'tbtext',
			text : "Elenco Rate",
			cls : 'panel-title'
		}, '->', '-', {
			type : 'button',
			text : 'Stampa elenco rate',
			width : 100,
			tooltip : 'Stampa elenco rate',
			icon : 'images/stampa.gif',
			handler : function() {
				Ext.ux.Printer.print(gridRate);
			},
			scope : this
		},
		' ', '-'];
		

		// create the editor grid
		gridRate = new Ext.grid.EditorGridPanel({
			store : storeRate,
			cm : cmRich,
			frame :true,
			sm : selM,
			layout : "fit",
			id : 'gridRatePiano',
			autoScroll : true,
			tbar : new Ext.Toolbar({
				cls : "x-panel-header",
				items : tbarItemsRich
			})
		});
		
		
		//**********************************FINE GESTIONE GRID Rate***************************************			
								
		var recordRateazione = Ext.data.Record.create([
			{name: 'IdPianoRientro', type: 'int'},
			{name: 'IdContratto', type: 'int'},
			{name: 'IdStatoPiano', type: 'int'},
			{name: 'DecorrenzaRate',type: 'date', dateFormat: 'Y-m-d'},
			{name: 'RataCrescente', type: 'string'},
			{name: 'Spese', type: 'float'},
			{name: 'DurataAnni', type: 'int'},
			{name: 'Periodicita', type: 'int'},
			{name: 'Nota', type: 'string'},
			{name: 'Tasso', type: 'float'},
			{name: 'ImportoRata', type: 'float'},
			{name: 'NumeroRate', type: 'int'},
			{name: 'TitoloStatoPiano', type: 'string'}	
		]);	
			
		var select = "SELECT pr.NumeroRate, pr.ImportoRata, pr.IdPianoRientro,pr.IdContratto,pr.IdStatoPiano,pr.DecorrenzaRate,"
			+" if(pr.RataCrescente = 'S',pr.RataCrescente,'N')  AS RataCrescente , pr.Spese, pr.Tasso, pr.DurataAnni, pr.Periodicita, pr.Nota, sp.TitoloStatoPiano " 
			+" FROM pianorientro pr, statopiano sp"
			+" WHERE pr.IdStatoPiano = sp.IdStatoPiano"
			+" AND  pr.IdPianoRientro="+idPianoRientro+" ORDER BY pr.DataAccordo";	
			
		var dsRateazione = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read', sql: select},
			reader:  new Ext.data.JsonReader({root: 'results'},recordRateazione)
		});
			
		var comboPeriodo = new Ext.form.ComboBox({
				name : "Periodicita",
				fieldLabel : 'Periodicit&agrave;',
				anchor : "95%",
				hidden : false,
				allowBlank : false,
				editable : true,
				mode: 'local',
				forceSelection : true,
				typeAhead : true,
				autoSelect: true,
				triggerAction : 'all',
				store : new Ext.data.ArrayStore({
					fields : ['Periodicita', 'NomePeriodo'],
					data : [[1, 'Annuale'], [2, 'Semestrale'], [3, 'Quadrimestrale'], [4, 'Trimestrale'], [6, 'Bimestrale'], [12, 'Mensile']]
				}),
				hiddenName: 'Periodicita',
				valueField : 'Periodicita',
				displayField : 'NomePeriodo',
				listeners : 
			    {
				   select: function()
		  			{
		  				Ext.getCmp("TotaleDebitoRateazione").setValue(impDebito+Ext.getCmp("SpeseRateazione").getValue());
		  				if(gridFormRateazione.getForm().isValid())
							calcolaRate(idPianoRientro);
		  			}
			   }
			});
		
			var comboTipoRata = new Ext.form.ComboBox({
				name : "RataCrescente",
				fieldLabel : 'Tipo rata',
				anchor : "95%",
				hidden : false,
				allowBlank : false,
				editable : true,
				mode: 'local',
				forceSelection : true,
				typeAhead : true,
				autoSelect: true,
				triggerAction : 'all',
				store : new Ext.data.ArrayStore({
					fields : ['RataCrescente', 'TipoRata'],
					data : [['N', 'Costante'], ['S', 'Crescente']]
				}),
				hiddenName: 'RataCrescente',
				valueField : 'RataCrescente',
				displayField : 'TipoRata'
			});		
			
	   function calcolaRate(idPianoRientro){
	   			if(comboTipoRata.getValue()=='N')
	   			{
	   			
	   				var myMask = new Ext.LoadMask(Ext.getBody(), {
						msg : "Calcolo rate in corso..."
					});
					myMask.show();
	   			
		   			var anni 			= parseInt(Ext.getCmp("DurataAnniRateazione").getValue());
		   			var periodo 		= parseInt(comboPeriodo.getValue());
		   			var importo 		= parseFloat(Ext.getCmp("TotaleDebitoRateazione").getValue());
		   			var dataInizio 		= new Date(Ext.getCmp("DataInizioPagamento").getValue());
		   			var tasso 			= parseFloat(Ext.getCmp("TassoInteresseRateazione").getValue());
		   			var impRata 		= parseFloat(calcoloRata(importo,anni,tasso,periodo));
		   			var numeroRate 		=  parseInt(anni*periodo);
		   			var stepMesi 		=  parseInt(12/periodo);
		   			var sommaAvere 		= 0;
		   			arrayDataRate 		= [];
		   			
		   			for(i=0; i<numeroRate; i++)
		   			{
		   				if(i<=0)
		   				{
		   					dataScadeRata = dataInizio;
		   				}	
		   				else
		   				{
		   					dataScadeRata = new Date(dataScadeRata.setMonth(dataScadeRata.getMonth()+stepMesi));
		   					
		   				}
		   				arrayDataRate[i] = [i+1,idPianoRientro,dataScadeRata.format("d/m/Y"),impRata];
		   				sommaAvere = sommaAvere + impRata;
		   				
		   			}
		   			Ext.getCmp("TotaleAvereRateazione").setValue(sommaAvere);
		   			
		   			importoRata = impRata; // sono variabili globali nella funzione
					numRate = numeroRate;  // sono variabili globali nella funzione
		
		   			refreshStoreLocal();
		   			myMask.hide();
		   		}
		   		else
		   		{
		   			Ext.Msg.alert("Attenzione","Calcolo non disponibile per il piano di rateazione con rata crescente.");
		   		}		
	  	 };		
			
		var gridFormRateazione = new Ext.FormPanel(
			{
				id: 'form_piano_rateazione',
				frame: true,
				title : "Registrazione piano di rateazione",
				trackResetOnLoad: true,
				border: false,
				store : dsRateazione,
				wait : "Operazione in corso.....",
				reader: new Ext.data.JsonReader({
					root: 'results',
					fields: recordRateazione
				}),
			  	layout: {
            	    type: 'vbox',
            	    align : 'stretch'
             	},
				items:[
						{   
				        		xtype:'panel', 
				        		layout:'form',
				        		flex : 1,
        		        		buttons : [
				        					
				        		],
				        		items : [
									{   
						        		xtype:'panel', 
						        		layout:'column',
						        		items:[
						        			   {
											     xtype:'panel', 
											     layout:'form', 
											     columnWidth:.33, 
											     labelWidth:100, 
												 items: 
												 [
												 	 {
												          fieldLabel:'Importo debito',	
												          name:'ImportoDebito',
												          xtype:'numberfield', 	
												          cls : 'red-title',
												          id:'ImportoDebito',
												          anchor:"95%",
												          format: '0,00',
												          style: 'text-align:right',
												          value : impDebito,
												          readOnly: true
												     }
												     ,
												 	 {
												          fieldLabel:'Tasso int. % ',	
												          name:'Tasso',
												          xtype:'numberfield', 	
												          id:'TassoInteresseRateazione',
												          allowBlank : false,
												          anchor:"95%",
												          format: '0,00000',
												          minValue : 0.01,
												          style: 'text-align:right',
												          readOnly: false,
											              listeners : 
															   {
																   change: function()
														  			{
														  				Ext.getCmp("TotaleDebitoRateazione").setValue(impDebito+Ext.getCmp("SpeseRateazione").getValue());
														  				if(gridFormRateazione.getForm().isValid())
																			calcolaRate(idPianoRientro);
														  			}
															   }
												     } 
												     ,
												     {
													          xtype: 'datefield',
															  format: 'd/m/Y',
															  fieldLabel: 'Data inizio pag.',
															  allowBlank: false,
															  anchor:"95%",
															  style: 'text-align:right',
															  blankText : "Indicare la data di inizio pagamento",
															  //vtype: 'daterange',
															  name: 'DecorrenzaRate',
															  id: 'DataInizioPagamento'
															  //,minValue: new Date()
													 }
												 ]
											   }
											   ,
											   {
											     xtype:'panel', 
											     layout:'form', 
											     columnWidth:.33, 
											     labelWidth:100, 
												 items: 
												 [
											         {
												          fieldLabel:'Spese',	
												          name:'Spese',
												          xtype:'numberfield', 	
												          id:'SpeseRateazione',
												          allowBlank : false,
												          anchor:"95%",
												          format: '0,00',
												          style: 'text-align:right',
												          readOnly: false,
											              listeners : 
															   {
																   change: function()
														  			{
														  				Ext.getCmp("TotaleDebitoRateazione").setValue(impDebito+Ext.getCmp("SpeseRateazione").getValue());
														  				if(gridFormRateazione.getForm().isValid())
																			calcolaRate(idPianoRientro);
														  			}
															   }
												     }
												     ,
												     {
												          fieldLabel:'Durata anni',	
												          name:'DurataAnni',
												          id:'DurataAnniRateazione',
												          xtype:'numberfield', 	
												          anchor:"95%",
												          format: '0,00',
												          allowBlank : false,
												          style: 'text-align:right',
												          readOnly: false,
												          minValue: 1,
												          maxValue: 100,
												          listeners : 
															   {
																   change: function()
														  			{
														  				Ext.getCmp("TotaleDebitoRateazione").setValue(impDebito+Ext.getCmp("SpeseRateazione").getValue());
														  				if(gridFormRateazione.getForm().isValid())
																			calcolaRate(idPianoRientro);
														  			}
															   }
												     } 
												     ,
													 {
													     xtype:'panel', 
													     layout:'form', 
													     columnWidth:.40, 
													     labelWidth:100, 
														 items: 
														 [
														 	comboTipoRata
														 ]
													 }
												 ]
											   },{
											     xtype:'panel', 
											     layout:'form', 
											     columnWidth:.33, 
											     labelWidth:80, 
												 items: 
												 [
												 	{
												          fieldLabel:'Totale debito',	
												          name:'TotaleDebito',
												          cls : 'red-title',
												          id:'TotaleDebitoRateazione',
												          xtype:'numberfield', 	
												          anchor:"95%",
												          format: '0,00',
												          style: 'text-align:right',
												          readOnly: true
												     }
												     ,
												     comboPeriodo
												     ,
												     {
												          fieldLabel:'Totale avere',	
												          name:'TotaleAvere',
												          cls : 'red-title',
												          id:'TotaleAvereRateazione',
												          xtype:'numberfield', 	
												          anchor:"95%",
												          format: '0,00',
												          style: 'text-align:right',
												          readOnly: true
												     }
												 ]
											   }
											  
											  ]
									}
								    ,
								    {
								     xtype:'panel', 
								     layout:'form', 
								     labelWidth:100, 
									 items: 
									 [
									 	{
											xtype:'htmleditor',
								            fieldLabel: 'Note',
								            anchor:"100%",
								            enableLinks: false,
								            enableSourceEdit :false,
								         	enableFormat : false,
								            id: 'NotePianoRateazione',
								            name: 'Nota',
								            allowBlank: true
						    	        }
									 ]
								   }
						   ]
						},
						{
					     xtype:'panel', 
					     layout:'fit',
					     flex : 1,
					     frame : true, 
					     labelWidth:100, 
						 items: [gridRate]
						}			 
						
				      ]
				      ,
				       buttons: [ {
												text : 'Calcola rate',
												width : 80,
												handler : function() {
													if(gridFormRateazione.getForm().isValid())
														calcolaRate(idPianoRientro);
												},
												scope : this
								  },{
												text : 'Stampa bollettini',
												width : 80,
												handler : function() {
														var arr = Ext.encode(arrayDataRate);
														window.open('server/generaStampaBollettino.php?arrayRate='+arr+'&idContratto='+idContratto,'_parent','');
												},
												scope : this
								  },
								  {
												text : 'Approva piano rientro',
												width : 80,
												handler : function() {
						
												},
												scope : this
								  },
						          {
									text: 'Salva',
									width : 80,
									id: 'btnSalvaDett',
									handler: function() 
											{
												if (gridFormRateazione.getForm().isValid()) 
												{
													var myMask = new Ext.LoadMask(Ext.getBody(), {
														msg : "Operazione in corso..."
													});
													myMask.show();
													
													gridFormRateazione.getForm().submit(
													{
														url: 'server/edit_azione.php', method: 'POST',
														params: {idstatoazione: <?php echo $idstatoazione?>,importoRata : importoRata , numRate : numRate,idcontratti: "<?php echo addslashes($idcontratti)?>", idPianoRientro : idPianoRientro, arrayRate : Ext.encode(arrayDataRate)},
														success: function (frm,action) {
															if(idGrid !="" && idGrid != undefined && idGrid != null)
																Ext.getCmp(idGrid).getStore().reload();
															saveSuccess(win,frm,action);
															myMask.hide();
														},
														failure: function(frm,action){saveFailure(frm,action); myMask.hide();}
													});
													
												
												}
											}
									
								  }
								  , 
								  {
									text: 'Chiudi',
									width : 80,
									id: 'btnAnnullaDett',
									handler: function() 
											{
												win.close();
											}
								  }
								  
						   		]	
			});	

		var win = new Ext.Window(
		{
				cls: 'left-right-buttons',
				modal: true,
				width: 800,
				height: 500,
				minWidth: 800,
				minHeight: 500,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: titolo,
				items: [gridFormRateazione]
		});
		
		if(idPianoRientro > 0)
		{	
			dsRateazione.load({callback : function(records, options){
					var rec = records[0];
					gridFormRateazione.getForm().loadRecord(rec);
					var statoPiano = "";
					if(rec.get("TitoloStatoPiano")!="" && rec.get("TitoloStatoPiano")!=undefined && rec.get("TitoloStatoPiano")!=null)
						statoPiano = " - " + rec.get("TitoloStatoPiano");
					gridFormRateazione.setTitle("Piano di rateizazione" + statoPiano);
					Ext.getCmp("TotaleDebitoRateazione").setValue(impDebito + rec.get("Spese"));
					
					importoRata = rec.get("ImportoRata"); 
					numRate     = rec.get("NumeroRate"); 
					loadRate(idPianoRientro);
					win.show();
					myMask.hide();
			}});
		}
		else
		{
			win.show();
			myMask.hide();
		}	
					
		}// fine funzione registra
	}// fine return
}();

DCS.RegPianoRateazione.registra(<?php echo $ids?>,<?php echo $impDebito?>,<?php echo $arrDatiExtra->IdPianoRientro!=''?$arrDatiExtra->IdPianoRientro:"''"; ?>,'<?php echo $idGrid?>');
