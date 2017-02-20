/**
 * grid_dettaglioProcesso costruisce la finestra di dettaglio
 * in cui l'utente può osservare i log provenienti dal processo di import dei dati.
 * Questa finestra compare al click del pulsante "Salva" nel pannello
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.dettaglioProcesso = function(IdLotto,TipoImport,processName,files){
	var select = "SELECT * FROM processlog where ProcessName='"+processName+"'";
	var dsDettProc = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),
		baseParams:{task: 'read', sql: select},
		reader: new Ext.data.JsonReader(
				{
					root: 'results'
				},
				[
				 	{name: 'IdProcessLog', type:'int'},
				 	{name: 'LogLevel', type:'int'},
				 	{name: 'lastupd', type : 'date', dateFormat : 'Y-m-d H:i:s'},
				 	{name: 'ProcessName', type:'string'},
				 	{name: 'LogMessage', type:'string'}
				]
		)
	});//end datastore
	
	var grid = new Ext.grid.GridPanel({
		id: 'gridDettProc',
		width:900,
        store: dsDettProc,
        columnLines: true,
        trackMouseOver:false,
        disableSelection:true,
        loadMask: true,
        viewConfig: {
        	autoFill: true,
			forceFit: false,
	        getRowClass : function(record, rowIndex, p, store){ // colora i messaggi di tipo diverso
                switch (record.get('LogLevel')) {
                case  0:    // informativo, normale             
                case  4:    // informativo, normale             
                	return; // no 
                case -1:    // terminazione
                case -2:    // interruzione
                    return 'grid-row-giallochiaro';
                	return;
                default: // errore
                    return 'grid-row-rosso';
		        }
			},
        },
        
        //grid columns
        columns:[{
        	header: "Data/Ora",
        	dataIndex: 'lastupd',
            xtype: 'datecolumn',
            format: 'd/m/y H:i:s',
            width: 120,
            align: 'Left'
        },{
        	header: "Messaggio",
    		dataIndex: 'LogMessage',
            width: 800,
            align: 'Left',
            renderer: DCS.render.word_wrap
            //css: 'word-wrap: break-word; white-space: normal;' // ottiene il wrap dei testi
        }
      ],
      
     //bottom bar
     bbar:new Ext.Toolbar({
	 	id: 'refresh',
	 	cls: "x-panel-header",
		store: dsDettProc,
		items: [{xtype:'button', 
				text:'Aggiorna', 
				tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', 
				handler: function(){
					dsDettProc.load();
				}, scope: this},
		        '->',
			        {xtype: 'button',
					 id: 'stopProcess',
					 tooltip:'Interrompi il processo', icon: 'ext/examples/shared/icons/fam/delete.gif',
					 text: 'Interrompi',
					 handler: function(){
						 Ext.Ajax.request({
                     		url : 'server/funzioniWizard.php' , 
                     		params: {task: 'interrompiProcesso', processName: processName},
                     		method: 'POST',
                     		success: function ( result, request ) {
									eval("res = "+result.responseText);
									if (res.success)
										setTimeout(function(){
											dsDettProc.reload();
										}, 2000);
									else
	                        			Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile interrompere il processo'); 										
                     		},
                     		failure: function ( result, request) { 
                     			Ext.MessageBox.alert('Interruzione non eseguita', result.responseText); 
                     		} 
                     	}); 
					 }
			     },{
					xtype:'button',
					id: 'step1',	
					tooltip:'Caricamento preliminare del file',
					iconCls:'grid-add',
					hidden: true,
					text: 'Caricamento preliminare',
					handler:  function(btn, pressed){
						eseguiFase2(btn);
				    }
				},{
					xtype:'button',
					id: 'btnRipeti',
					idLotto: IdLotto,
					hidden: true,
					tooltip:'Ripeti l\'ultima fase eseguita',
					icon:'ext/resources/images/default/grid/refresh.gif',
					hidden: true,
					text: 'Ripeti questa fase',
					handler:  function(btn, pressed){
						btn.funzioneDaRipetere();
				    }
				},{
					xtype:'button',
					id: 'step2',
					tooltip:'Caricamento definitivo del file',
					iconCls:'grid-add',
					hidden: true,
					text: 'Caricamento finale',
					tipoImport: TipoImport, // 'R'=rimpiazzo totale, 'A'=aggiornamento
					handler:  function(btn,pressed){
						eseguiFase3(btn);
					}
				},
				'-',
				{
					xtype: 'button',  
				    text: 'Annulla',
					handler: function (){grid.ownerCt.close();}
				}]
     }),//fine Toolbar
	});//fine GridPanel
	
	// Aggiorna il data store mezzo secondo dopo l'avvio, in modo da far vedere (probabilmente) il primo
	// messaggio del processo	
	setTimeout(function(){
		dsDettProc.load();
	}, 500);
	
	//avvia il refresh automatico
	avviaRefreshAutomatico = function() {
		// la variabile intervalDettaglioProcesso e' a livello globale, in modo da essere usata nella beforeclose in dettaglioImport.js
		intervalDettaglioProcesso = setInterval(
				function(){
					dsDettProc.reload({
						callback: function(r){
							if(r.length == 0 || r[r.length-1].get('LogLevel')== -1){ // l'ultimo messaggio nel processlog e' un messaggio di terminazione
								if(intervalDettaglioProcesso) {
									clearInterval(intervalDettaglioProcesso);
									intervalDettaglioProcesso = null;
								}
							}
							// posiziona la griglia in modo che si vede l'ultima riga
							Ext.getCmp('gridDettProc').getView().focusRow(r.length-1);
						}
					});
				},5000);
	},
	
	eseguiFase1 = function() {
		avviaRefreshAutomatico();
		Ext.Ajax.request({
			url : 'server/processControl.php' , 
			params: {task: 'cmdUnix', processName: processName, IdLotto: IdLotto, info: JSON.stringify(files), oper: 'v'},// il parametro info riceve dati del file dal success del task importFile  
			method: 'POST',
			timeout: 3600000,
			success: function ( result, request ) {
				DCS.hideMask();
				eval("res = "+result.responseText);
				if(intervalDettaglioProcesso) {
					clearInterval(intervalDettaglioProcesso);
					intervalDettaglioProcesso = null;
				}
				// Disabilita il pulsante "Interrompi ed abilita il pulsante "Caricamento dati: passo 1" in caso di successo
				Ext.getCmp('stopProcess').hide();
				var btnRipeti = Ext.getCmp('btnRipeti');
				btnRipeti.show();
				btnRipeti.funzioneDaRipetere = eseguiFase1;
				dsDettProc.reload();
				Ext.getCmp('step1').show();
			},
			failure: function ( result, request) { 
				DCS.hideMask();
				Ext.MessageBox.alert('Caricamento non eseguito', result.responseText); 
				var btnRipeti = Ext.getCmp('btnRipeti');
				btnRipeti.show();
				btnRipeti.funzioneDaRipetere = eseguiFase1;
			}
		 });
	};

	eseguiFase2 = function(btn) {
	   	 DCS.showMask('Caricamento in corso...');
		 btn.hide(); // nasconde questo stesso pulsante
		 Ext.getCmp('btnRipeti').hide();
		 Ext.Ajax.request({
				url : 'server/processControl.php' , 
				params: {task: 'cmdUnix', processName: processName, IdLotto: IdLotto, info: JSON.stringify(files), oper: 'p'},// il parametro info riceve dati del file dal success del task importFile  
				method: 'POST',
				timeout: 3600000,
				success: function ( result, request ) {
					DCS.hideMask();
					Ext.getCmp('stopProcess').hide(); // nascondi il tasto Interrompi
					var btnRipeti = Ext.getCmp('btnRipeti');
					btnRipeti.show();
					btnRipeti.funzioneDaRipetere = function() {eseguiFase2(btn);};
	
					eval("res = "+result.responseText);
					if(intervalDettaglioProcesso) {
						clearInterval(intervalDettaglioProcesso);
						intervalDettaglioProcesso = null;
					}
					
					dsDettProc.reload({ // aggiorna la lista
						callback: function(r){
							// posiziona la griglia in modo che si vede l'ultima riga
							Ext.getCmp('gridDettProc').getView().focusRow(r.length-1);
						}
					});
	
					// Disabilita il pulsante "Interrompi ed abilita il pulsante "Caricamento dati: passo 1" in caso del success
					Ext.getCmp('step2').show();
					//Ext.getCmp('preview').tpshow = res.data;
					//Ext.getCmp('preview').show();
				},
				failure: function ( result, request) { 
					DCS.hideMask();
					Ext.MessageBox.alert('Caricamento non eseguito', result.responseText); 
					var btnRipeti = Ext.getCmp('btnRipeti');
					btnRipeti.show();
					btnRipeti.funzioneDaRipetere = function() {eseguiFase2(btn);};
				}
		});
		avviaRefreshAutomatico();
		Ext.getCmp('stopProcess').show(); // ripresenta il tasto Interrompi
	};

	eseguiFase3 = function(btn) {
	   	 DCS.showMask('Caricamento in corso...');
		 btn.hide(); // nasconde questo stesso pulsante
		 Ext.getCmp('btnRipeti').hide();
		 //Ext.getCmp('preview').hide();
		 Ext.Ajax.request({
				url : 'server/processControl.php' , 
				timeout: 3600000,
				params: {task: 'cmdUnix', processName: processName, IdLotto: IdLotto, info: JSON.stringify(files), 
					oper: btn.tipoImport=='R'?'l':'u'},
				method: 'POST',
				success: function ( result, request ) {
					Ext.getCmp('stopProcess').hide(); // nascondi il tasto Interrompi
					var btnRipeti = Ext.getCmp('btnRipeti');
					btnRipeti.show();
					btnRipeti.funzioneDaRipetere = function() {eseguiFase3(btn);};
					DCS.hideMask();
					eval("res = "+result.responseText);
					if(intervalDettaglioProcesso) {
						clearInterval(intervalDettaglioProcesso);
						intervalDettaglioProcesso = null;
					}
					dsDettProc.reload({
						callback: function(r){
							// posiziona la griglia in modo che si vede l'ultima riga
							Ext.getCmp('gridDettProc').getView().focusRow(r.length-1);
						}
					});
					
				},
				failure: function ( result, request) { 
					DCS.hideMask();
					Ext.MessageBox.alert('Caricamento non eseguito', result.responseText); 
					var btnRipeti = Ext.getCmp('btnRipeti');
					btnRipeti.show();
					btnRipeti.funzioneDaRipetere = function() {eseguiFase3(btn);};
			}
		});
		avviaRefreshAutomatico();
		Ext.getCmp('stopProcess').show(); // ripresenta il tasto Interrompi
	 };
	
	eseguiFase1();
	
	return grid;
};
