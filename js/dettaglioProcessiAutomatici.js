// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

DCS.recordProc = Ext.data.Record.create([
		{name: 'IdEvento', type: 'int'},
		{name: 'CodEvento'},
		{name: 'Processo'},
		{name: 'Stato'},
		{name: 'OraIni'},
		{name: 'OraFin'},
		{name: 'numAuto'}
]);

/*********************************
 * CLASSE DCS DETTAGLIO PROCESSO *
 *********************************/
DCS.DettaglioProcesso = Ext.extend(Ext.TabPanel, {
	   idEv: '',
	   listStore: null,
	   rowIndex: '',
	   name:'',
	   loadAfterCC:'',
	   
	   initComponent: function() {
	   	    //Controllo iniziale per allineamento idUtente in caso di nuovo utente 
			var grid=this.WinMain;
			var eventoId = this.idEv;
			var nomeEvento = this.name;
			//var mainGridStore=this.listStore;
			var procVisible=false;
			var numeroAuto;
			var listStore = Ext.getCmp(grid).getStore();
						
		    if(this.idEv=='')
			{
				procVisible=true;
				this.listStore=null;
				this.rowIndex='';
			}
	   	    
			//stores: dati da visualizzare,dati per riempire il chkgroup,combo,
			var dsProcesso = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{task: 'read'},
				reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordProc)
			});
			
			//BUTTONS
			var chiudi = new Ext.Button({
				text: 'Chiudi',
				handler: function(b,event) {
					win.close();
				},
				scope: this
			});
			
			//Bottone di salvataggio
			var save = new Ext.Button({
				store:dsProcesso,
				text: 'Salva',
				handler: function(b,event) {
					if(formPAut.getForm().isDirty()) {//qualche campo modificato
					  if(formPAut.getForm().isValid()) {
					  	//funzione di controllo
						var Errors = '';
						var idEvent = Ext.getCmp('idEv').getValue();
						var codEvent = Ext.getCmp('codEv').getValue();
						var titoloEvent = Ext.getCmp('processo').getValue();
						Errors = validateRulePro(idEvent, codEvent, titoloEvent);
								
						if(Errors == '') {
						  formPAut.getForm().submit({
							 url: 'server/gestioneProcessiAutomatici.php',
							 method: 'POST',
							 params: {
								task: 'savePr',
								idEv: this.idEv
							 },
							 success: function(frm, action){
								//eval('var resp = '+obj.responseText);
								if(action.result.success) {
								  Ext.MessageBox.alert('Esito', "Processo salvato");
								} else {
									Ext.MessageBox.alert('Fallito', "Impossibile salvare il processo: " + action.result.error);
								  }
								  //if(win.getComponent(0).idEv == 0) {
								  //mainGridStore.reload();
								  Ext.getCmp(grid).getStore().reload();
								  win.close();
								  
								//}
							 },
							 failure: function(frm, action){
								if(action.result == undefined) {
								  Ext.Msg.alert('Errore', "Non sono stati scelti tutti i valori minimi necessari alla definizione del processo.");
								} else {
									Ext.Msg.alert('Errore', action.result.error);
								  }
								//.MessageBox.alert('Esito', "Utente non salvato");
							 },
							 waitMsg: 'Salvataggio in corso...'
						  });
						} else {
							Ext.MessageBox.alert('Errore nella specifica della regola', Errors);
						  }
					  }			  		
					}else{
						console.log("no change");
					}
				},
				scope: this
			});
			
			//Form su cui montare gli elementi
			var formPAut = new Ext.form.FormPanel({
				title:'Dati Processi automatici',		//il titolo � usato per testare il tab
				frame: true,
				bodyStyle: 'padding:5px 5px 0',
				border: false,
				id: 'frmProcesso',
				name: 'frmProcesso',
				trackResetOnLoad: true,
				reader: new Ext.data.JsonReader({
					root: 'results',
					fields: DCS.recordProc
				}),
				items: [{
				  xtype:'container', 
				  layout:'column',
				  columnWidth:.80,
				  items: [ 
				    {
					  xtype:'container', layout:'column',
				      items:[{
					    xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98, defaultType:'textfield',
					    defaults: {anchor:'97%', readOnly:false},
					    items: [{xtype:'numberfield', fieldLabel:'Id Evento', name:'IdEvento', id:'idEv', style:'text-align:left',allowBlank: false}]
					  }]
					},
					{
					  xtype:'container', layout:'column',
					  items:[{
						xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:false},
						items: [{fieldLabel:'Cod. Evento', width:350, id:'codEv', name:'CodEvento', style:'text-align:left',allowBlank: false}]
					  }]										
					},
					{
					  xtype:'container', layout:'column',
					  items:[{
						xtype:'panel', layout:'form', labelWidth:120, columnWidth:.98, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:false},
						items: [{fieldLabel:'Processo', width:350, id:'processo', name:'Processo', style:'text-align:left'}]
					  }]
					},
					{
					  xtype:'container', layout:'column',
					  items:[{
						xtype:'panel', layout:'form', labelWidth:120, columnWidth:.98, defaultType:'combo',
						defaults: {anchor:'97%', readOnly:false},
						items: [
						  {
						    id:'stato',
							name:'Stato',
							fieldLabel :"Stato",
							width:145,
							emptyText :"Seleziona...",
							store: new Ext.data.ArrayStore({
							   fields: ['id','stato'],
							   data: [
								 ['N','Attivo'],
								 ['Y','Sospeso'],
								 ['U','Una tantum'],
							   ]
							}),
							mode : 'local',
							value: '',
							triggerAction : 'all',
							displayField  : 'stato',
							valueField    : 'id',
							editable      : false,
							forceSelection: true
						}]
					  }]
					},
					{
					  xtype:'container', layout:'column',
					  items:[{
						xtype:'panel', layout:'form', labelWidth:120,columnWidth:.98, defaultType:'timefield',
						defaults: {anchor:'77%', readOnly:false},
						items: [
						  {
						  	fieldLabel: 'Ora inizio',
							format: 'H:i',
							width: 300,
							vtype: 'time',
							name: 'OraIni',
		                 	id: 'orainizio',
							increment: 15
						  }
						]
					  }]
					},
					{
					  xtype:'container', layout:'column',
					  items:[{
						xtype:'panel', layout:'form', labelWidth:120, columnWidth:.98, defaultType:'timefield',
						defaults: {anchor:'77%', readOnly:false},
						items: [
						  { 
						    fieldLabel: 'Ora fine',
							format: 'H:i',
							width: 300,
							vtype: 'time',
							name: 'OraFin',
		                 	id: 'orafine',
							increment: 15
						  }
						]
					  }]
					},
					{
					  xtype: 'container', layout: 'column',
					  items: [{
						xtype: 'panel', layout: 'form', columnWidth: .98, defaultType:'textfield',
						defaults: {anchor: '97%', readOnly: false},
						items: [
						  {
							xtype: 'button',
							boxMinWidth: 120,
							width: 150,
							tooltip: "Visualizza automatismi associati.",
							text: 'Automatismo',
							name: 'btnApriAuto',
							id: 'btnApriAuto',
							handler: function(){
							  if(numeroAuto>0){
							  	showAutoProDettaglio(eventoId,win.getId(),nomeEvento,grid,this.listStore,this.rowIndex);
							  }	else {
							  	  newAutoProDetail(eventoId,win.getId(),nomeEvento,grid,this.listStore,this.rowIndex);
							    }
							},
							scope: this
						  }
						]
					  }]
					} 	
				  ]
				}],
				buttons:[chiudi,save]
	    	});
				
			// Indice del record nello store della lista
			var indexStore = this.rowIndex;
			if (this.listStore!=null && (this.listStore.lastOptions.params||{}).start != undefined)
				{indexStore += this.listStore.lastOptions.params.start;	}
			// Indice dell'ultimo record nello store della lista
			var lastRec = (this.listStore!=null?this.listStore.getTotalCount()-1:indexStore);
	        // var si = this.listStore.getSortState();
	
           	// Funzione che gestisace la pressione dei bottoni precedente/successivo
			var dettaglio_nextprev = function(btn) {
				var p = this.listStore.lastOptions.params || {};		// parametri di lettura dello store
				var newIndex = this.rowIndex + (btn.getItemId()=='btnPrev'?-1:+1);	// nuovo indice del record nella pagina
				var flg_reload = false;				// flag per eventuale caricamento pagina 
				if (p.start != undefined) {	// paginata
					if (newIndex < 0) {					// precedente da inizio pagina?
						p.start -= p.limit;
						flg_reload = true;
					} else
						if (newIndex >= p.limit) {		// successivo da fine pagina?
							p.start += p.limit;
							flg_reload = true;
						}
				}
				if (flg_reload) {					// richiesto record fuori pagina: deve caricarla 
					this.listStore.load({
						params:p, 
						callback : function(rows,options,success) {
							if (success) {			// mostra dettaglio record richiesto
								var newIndex = this.rowIndex==0?options.params.limit-1:0;
								var rec = rows[newIndex]; //this.listStore.getAt(newIndex);
								DCS.showPrAuDetail.create(rec.get('IdEvento'),rec.get('Processo'),grid,this.listStore,newIndex);
							}
						},
						scope:this
					});
				} else {			// nella pagina: mostra dettaglio record richiesto
					var rec = this.listStore.getAt(newIndex);
					DCS.showPrAuDetail.create(rec.get('IdEvento'),rec.get('Processo'),grid,this.listStore,newIndex);
				}
			};
			
			Ext.apply(this, {
				activeTab:0,
				//items: [datiGenerali.create(this.idUtente,this.winList)],
				items: [formPAut],
		        tbar: new Ext.Toolbar({
					items:[
						'->',{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSepar'},
							{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSeparExp'},
						//'-',
							{type:'button', text:'Precedente',
								itemId:'btnPrev',
								iconCls:'icon-prev',
								disabled: (indexStore<=0),
								disabledClass: 'x-item-disabled',
								handler:dettaglio_nextprev,
								scope:this},
						'-',{type:'button', text:'Seguente',
								itemId:'btnNext',
								iconCls:'icon-next',
								disabled: (indexStore >= lastRec),
								disabledClass: 'x-item-disabled',
								handler:dettaglio_nextprev,
								scope:this},
						'-', helpButton("DettaglioProcessoAuto")]
		        }),
		        id: 'pnlDettPratica',
		        listeners: {
					tabchange: function(panel, tab) {
						var myIdx = panel.items.indexOf(panel.getActiveTab());
						var showButtons = ((myIdx==3) && (panel.id=='pnlDettPratica'));
	
						this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
						this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
		            }
		        }
	        });	
			
			DCS.DettaglioProcesso.superclass.initComponent.call(this);
			
			/**------------------------------------
			 * LOAD DEGLI STORES
			 *----------------------------------- */
			
			var DisableAll=false;
			if(this.idEv!='')
			{
				Ext.Ajax.request({
					url: 'server/AjaxRequest.php', 
		    		params : {	task: 'read',
								sql: "SELECT count(*) as num from v_processi_automatici where idEvento="+this.idEv
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
						if(resp != undefined)
						{
							if(resp.num>1)
							{
								DisableAll=true;
							}
						}								
					},
		    		failure: function ( result, request) { 
		    			Ext.MessageBox.alert('Errore', 'Errore durante l\'esecuzione dell\' interrogazione al database.'); 
		    		},
		    		autoLoad: true
		    	});
			} 
			
			var evento = this.idEv;
			var sqlForm ='SELECT * '; 
				sqlForm+='FROM v_processi_automatici '; 
				sqlForm+='where IdEvento=0'+this.idEv+'';
				
			dsProcesso.load({
				params:{
				   sql: sqlForm 
				},	
				callback: function(r, options, success)
				{
				   if(success && r.length > 0) {
					 range = dsProcesso.getRange();
					 var rec = range[0];
					 Ext.getCmp('idEv').setValue(rec.data.IdEvento);
					 Ext.getCmp('codEv').setValue(rec.data.CodEvento);
					 Ext.getCmp('processo').setValue(rec.data.Processo);
					 Ext.getCmp('stato').setValue(rec.data.Stato);
					 Ext.getCmp('orainizio').setValue(rec.data.OraIni);
					 Ext.getCmp('orafine').setValue(rec.data.OraFin);
					 Ext.getCmp('btnApriAuto').setText(rec.data.numAuto +' '+Ext.getCmp('btnApriAuto').getText());
					 numeroAuto = rec.data.numAuto;
				   }
				   if(this.idEv != '') {
				   	 Ext.getCmp('idEv').setDisabled(true);
				   	 Ext.getCmp('codEv').setDisabled(true);
				   	 Ext.getCmp('processo').setDisabled(false);
				   	 Ext.getCmp('stato').setDisabled(false);
				   	 Ext.getCmp('orainizio').setDisabled(false);
				   	 Ext.getCmp('orafine').setDisabled(false);
				   } else {
				   	   Ext.getCmp('btnApriAuto').setDisabled(true);
				     } 	   
				},
				scope: this	
			});
			
			//dsProcesso.load();
	   }
	
});

//--------------------------------------------------------------------------------------
//Controlla che tutti e 3 campi siano compilati
//--------------------------------------------------------------------------------------
function validateRulePro(idEvent, codEvent, titoloEvent)
{
	if(idEvent=='' || codEvent=='' || titoloEvent=='')
	{
		return 'I campi Id Evento, Cod. Evento e Processo devono essere specificati.';
	}else{
		return '';
	}
}	

// register xtype
Ext.reg('DCS_dettaglioProcesso', DCS.DettaglioProcesso);

//--------------------------------------------------------
//Visualizza dettaglio procedura
//--------------------------------------------------------
DCS.showPrAuDetail = function(){

	return {
		create: function(idEv, name, WinMain, listStore, rowIndex){
		
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Lettura dettaglio..."
			});
			myMask.show();
			var winTitle = 'Dettaglio processo - ' + name + '';
			if (name == '') {
				winTitle = 'Creazione nuovo processo';
				rowIndex = '';
			}
			
			var nameNW = 'dettaglio' + idEv;
			
			if (oldWind != '') {
				win = Ext.getCmp(oldWind);
				win.close();
			}
			oldWind = nameNW;
			win = new Ext.Window({
				width: 500,
				height: 400,
				minWidth: 500,
				minHeight: 350,
				layout: 'fit',
				id: 'dettaglio' + idEv,
				stateful: false,
				plain: true,
				resizable: false,
				bodyStyle: 'padding:5px;',
				modal: true,
				title: winTitle,
				tools: [helpTool("DettaglioProcesso")],
				constrain: true,
				items: [{
					xtype: 'DCS_dettaglioProcesso',
					idEv: idEv,
					name: name,
					WinMain: WinMain,
					listStore: listStore,
			        rowIndex: rowIndex
				}]
			});
			
			//Ext.apply(win.getComponent(0),{winList:win});
			win.show();
			win.on({
				'close': function(){
					oldWind = '';
				}
			});
			myMask.hide();
		}	
	};	
}();