// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var winA;
var winSAz;

DCS.recordAutoEvento = Ext.data.Record.create([
		{name: 'IdEvento', type: 'int'},
		{name: 'IdAutomatismo', type: 'int'}
]);

DCS.recordAuto = Ext.data.Record.create([
		{name: 'IdAutomatismo', type: 'int'},
		{name: 'TipoAutomatismo'},
		{name: 'TitoloAutomatismo'},
		{name: 'Comando'},
		{name: 'Condizione'}
]);

		
/*********************************
 * CLASSE DCS DETTAGLIO PROCESSO *
 *********************************/
DCS.DettaglioAutomatismoProcesso = Ext.extend(Ext.TabPanel, {
	   idEv: '',
	   idAuto: '',
	   listStore: null,
	   rowIndex: '',
	   name:'',
	   loadAfterCC:'',
	   
	   initComponent: function() {
	   	    //Controllo iniziale per allineamento idUtente in caso di nuovo utente 
			var eventoId = this.idEv;
			var automatismoId = this.idAuto;
			var nomeAutomatismo = this.name;
			var mainGridStore=this.listStore;
			var procVisible=false;
						
		    if(this.idAuto=='')
			{
				procVisible=true;
				this.listStore=null;
				this.rowIndex='';
			}
	   	    
			var dsAutomatismo = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{task: 'read'},
				reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordAuto)
			});
			
			//BUTTONS
			var chiudi = new Ext.Button({
				text: 'Chiudi',
				handler: function(b,event) {
					winA.close();
				},
				scope: this
			});
			
			//Bottone di salvataggio
			var save = new Ext.Button({
				store:dsAutomatismo,
				text: 'Salva',
				handler: function(b,event) {
					if(formAutP.getForm().isDirty()) {//qualche campo modificato
					  if(formAutP.getForm().isValid()) {
					  	var comando = Ext.getCmp('comando').getValue();
						var tipo = Ext.getCmp('tipo').getValue();
						var condizione = Ext.getCmp('condizione').getValue();
						var Errors = '';
						Errors = validateRuleAutoPro(tipo,comando);
						if(Errors == '') {
							if(condizione=='') {
							  var sqlAjax = "SELECT 0 as num"	
							} else {
								var sqlAjax = "SELECT "+condizione+" as num" 
							  }
							Ext.Ajax.request({
								url: 'server/AjaxRequest.php',
								params: {
									task: 'read',
									sql: sqlAjax
								},
								method: 'POST',
								reader: new Ext.data.JsonReader({
									root: 'results',//name of the property that is container for an Array of row objects
									id: 'num'//the property within each row object that provides an ID for the record (optional)
								}, [{
									name: 'num'
								}]),
								success: function(result, request){
									eval('var resp = (' + result.responseText + ').results[0]');
									if (resp != undefined) {
										if((resp.num == 0 || resp.num == 1)) {
											formAutP.getForm().submit({
													url: 'server/gestioneProcessiAutomatici.php',
													method: 'POST',
													params: {
														task: 'saveAutoPr',
														idEv: eventoId,
														idAuto: automatismoId
													},
													success: function(frm, action){
														//eval('var resp = '+obj.responseText);
														if (action.result.success) {
															Ext.MessageBox.alert('Esito', "Automatismo salvato");
														}
														else {
															Ext.MessageBox.alert('Fallito', "Impossibile salvare l\'automatismo: " + action.result.error);
														}
														//if(win.getComponent(0).idEv == 0) {
														winA.close();
														mainGridStore.reload();
													//}
													},
													failure: function(frm, action){
														if (action.result == undefined) {
															Ext.Msg.alert('Errore', "Non sono stati scelti tutti i valori minimi necessari alla definizione dell\'automatismo.");
														}
														else {
															Ext.Msg.alert('Errore', action.result.error);
														}
													//.MessageBox.alert('Esito', "Utente non salvato");
													},
													waitMsg: 'Salvataggio in corso...'
												});
										}
										else {
											Ext.MessageBox.alert('Errore', 'Vi è un errore nel campo Condizione inserito.');
										}
									}
									else {
										Ext.MessageBox.alert('Errore', 'Vi è un errore nel campo Condizione inserito.');
									}
								},
								failure: function(result, request){
									Ext.MessageBox.alert('Errore', 'Errore durante l\'esecuzione dell\' interrogazione al database.');
								}
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
			var formAutP = new Ext.form.FormPanel({
				title:'Dati Automatismo',		//il titolo è usato per testare il tab
				frame: true,
				bodyStyle: 'padding:5px 5px 0',
				border: false,
				trackResetOnLoad: true,
				reader: new Ext.data.JsonReader({
						    root: 'results',
							fields: DCS.recordAuto
				}),
				items: [{
					xtype:'container', layout:'column',
					items: [
						{//colonna sinistra
						    xtype:'container',columnWidth:.46,
							items:[
							    {//oggetto primo
								    xtype:'container', layout:'column',
								    items:[
									{
									    xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
									    defaults: {anchor:'97%', readOnly:false,allowBlank: false},
									    items: [{fieldLabel:'Nome', id:'titolo', width:300 ,name:'TitoloAutomatismo', style:'text-align:left'}]
									}]
								},
								{
									xtype:'container', layout:'column',
									items:[
									{
										xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
										defaults: {anchor:'97%', readOnly:false},
										items: [{fieldLabel:'Comando', id:'comando', width:300 ,name:'Comando', style:'text-align:left'}]
									}]
								}
							]//fine ogg primo
							//fine oggetti colonna sinistra
						},
						{//colonna destra
							xtype:'container',columnWidth:.54,
							items:[
							    {//oggetto primo
									xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95,defaultType:'combo',
									defaults: {anchor:'57%', readOnly:false},
									items: [
									{
									    id:'tipo',
										name:'TipoAutomatismo',
										fieldLabel :"Tipo",
										width:120,
										//emptyText :"Seleziona...",
										store: new Ext.data.ArrayStore({
										   fields: ['tipo'],
										   data: [
											 ['php'],
											 ['SQL'],
										   ]
										}),
										mode : 'local',
										value: '',
										triggerAction : 'all',
										displayField  : 'tipo',
										valueField    : 'tipo',
										editable      : false,
										forceSelection: true
									}]
								},
								{
									xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
									defaults: {anchor:'97%', readOnly:false},
									items: [{fieldLabel:'Condizione', id:'condizione',width:300 ,name:'Condizione', style:'text-align:left'}]
						        }  
							]	     
						}
					]//fine items colonne
				}], //fine items principale  	   
				buttons:[chiudi,save]
	        });
				
			// Indice del record nello store della lista
			var indexStore = this.rowIndex;
			if (this.listStore!=null && (this.listStore.lastOptions.params||{}).start != undefined)
				{indexStore += this.listStore.lastOptions.params.start;	}
			// Indice dell'ultimo record nello store della lista
			var lastRec = (this.listStore!=null?this.listStore.getTotalCount()-1:indexStore);
	//		var si = this.listStore.getSortState();
			
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
								showAutoProDetail(eventoId,rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),mainGridStore,newIndex);	
							}
						},
						scope:this
					});
				} else {			// nella pagina: mostra dettaglio record richiesto
					var rec = this.listStore.getAt(newIndex);
					showAutoProDetail(eventoId,rec.get('IdAutomatismo'),rec.get('TitoloAutomatismo'),mainGridStore,newIndex);
				}
			};
	
			Ext.apply(this, {
				activeTab:0,
				//items: [datiGenerali.create(this.idUtente,this.winList)],
				items: [formAutP],
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
						'-', helpButton("DettaglioAutomatismoProcesso")]
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
			
			DCS.DettaglioAutomatismoProcesso.superclass.initComponent.call(this);
			
			/**------------------------------------
			 * LOAD DEGLI STORES
			 *----------------------------------- */
			//caricamento dei 4 store
			//controllo doppioni azione
			var DisableAll=false;
			if(automatismoId!='')
			{
				Ext.Ajax.request({
					url: 'server/AjaxRequest.php', 
		    		params : {	task: 'read',
								sql: "SELECT count(*) as num from automatismo where IdAutomatismo="+automatismoId
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
			
			var automatismo = this.idAuto;
			var sqlForm ='SELECT IdAutomatismo, TipoAutomatismo, TitoloAutomatismo, Comando, Condizione '; 
				sqlForm+='FROM automatismo '; 
				sqlForm+='where IdAutomatismo='+automatismoId+'';
				
			dsAutomatismo.load({
				params:{
				   sql: sqlForm 
				},	
				callback: function(r, options, success)
				{
				   if(success && r.length > 0) {
					 range = dsAutomatismo.getRange();
					 var rec = range[0];
					 //Ext.getCmp('idAut').setValue(rec.data.IdAutomatismo);
					 Ext.getCmp('tipo').setValue(rec.data.TipoAutomatismo);
					 Ext.getCmp('titolo').setValue(rec.data.TitoloAutomatismo);
					 Ext.getCmp('comando').setValue(rec.data.Comando);
					 Ext.getCmp('condizione').setValue(rec.data.Condizione);
				   }
				},
				scope: this	
			});
				
			//dsProcesso.load();
	   }
	
});

//-------------------------------------------------------------//
//Controlla che il campo Condizione sia inserito correttamente //
//-------------------------------------------------------------//
function validateRuleAutoPro(tipo, comando)
{
	if(tipo=="php" && comando!='') {
	  var patt=new RegExp('[;]$');
	  if(!patt.test(comando)&&comando!=''){
	  	return 'il campo Comando deve terminare con ;'
	  }	else {
	  	  return '';
	  } 
	} else {
		return ''
	  }
}	

// register xtype
Ext.reg('DCS_dettaglioAutomatismoProcesso', DCS.DettaglioAutomatismoProcesso);

//-----------------------------------------//
//Visualizza dettaglio processi automatici //
//-----------------------------------------//
function showAutoProDetail(idEv,idAu,name,listStore,rowIndex) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	var winTitle = 'Dettaglio automatismo - ' + name +'';
	if(name==''){
	  winTitle='Creazione nuovo processo';
	  rowIndex='';
	} 
	
	var nameNW = 'dettaglio'+idAu;
	if(oldWind != '') {
	  winA = Ext.getCmp(oldWind);
	  winA.close();
	}
	oldWind = nameNW;
	  
	winA = new Ext.Window({
		width: 800,
		height: 300,
		minWidth: 800,
		minHeight: 300,
		layout: 'fit',
		id:'dettaglio'+idAu,
		stateful:false,
		plain: true,
		resizable: false,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAutomatismoProcesso',
			idEv: idEv,
			idAuto: idAu,
			name:name,
			listStore: listStore,
			rowIndex: rowIndex
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	winA.show();
	winA.on({
		'close' : function () {
			    oldWind = '';
				
		}
	});
	myMask.hide();
	
}; // fine funzione showAutoProDetail

//-----------------------------------------------------------------//
//Creazione del primo automatismo associato al processo automatico //
//-----------------------------------------------------------------//
function newAutoProDetail(idEv,oldWinProcDett,name,gridM,listStore,rowIndex) {
	
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	winTitle='Creazione nuovo processo';
	
	winA = new Ext.Window({
		width: 800,
		height: 300,
		minWidth: 800,
		minHeight: 300,
		layout: 'fit',
		//id:'dettaglio'+idAu,
		stateful:false,
		plain: true,
		resizable: false,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioAutomatismoProcesso',
			idEv: idEv,
			idAuto: '',
			name:name,
			listStore: listStore,
			rowIndex: rowIndex
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	winA.show();
	winA.on({
		'close' : function () {
			     if(Ext.getCmp(oldWinProcDett) != null) {
				   Ext.getCmp(oldWinProcDett).close();
				   DCS.showPrAuDetail.create(idEv, name, gridM, listStore, rowIndex);
				 } else {
				 	  DCS.showPrAuDetail.create(idEv, name, gridM, listStore, rowIndex);
				   } 
		}
	});
	myMask.hide();
	
}; // fine funzione newAutoProDetail
