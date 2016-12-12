// Crea namespace DCS
Ext.namespace('DCS');
//AFFIDAMENTI
//window di dettaglio
var winReg;

DCS.recordRegAOp = Ext.data.Record.create([
           {name: 'IdRegolaAssegnazione', type: 'int'},
           {name: 'IdRegolaProvvigione', type: 'int'},
           {name: 'IdUtente'},
           {name: 'IdClasse'},
           {name: 'IdFamiglia'},
           {name: 'TipoDistribuzione'},
           {name: 'tipodistribuzioneConv'},
           {name: 'DurataAssegnazione'},
           {name: 'GiorniFissiInizio'},
           {name: 'GiorniFissiFine'},
           {name: 'DataIni', type:'date'},
           {name: 'DataFin', type:'date'},
           {name: 'IdArea'},
           {name: 'durata'},
           {name: 'FlagNoRientro'},
           {name: 'FlagMensile'},
           {name: 'FlagCerved'},
           {name: 'Condizione'},
           {name: 'TitoloRegolaProvvigione'},
           {name: 'CodRegolaProvvigione'},
           {name: 'Formula'},
           {name: 'FormulaFascia'},
           {name: 'numFasce', type: 'int'},
           {name: 'AbbrRegolaProvvigione'}]);

DCS.DettaglioRegAssOp = Ext.extend(Ext.TabPanel, {
	idAgenzia:'',
	idRegolaOp:'',
	listStore:null,
	rowIndex:0,
	titPrec:'',
	titAg:'',
	campo:'',
	arOldFields:'',
	initComponent: function() {
		
		//Controllo iniziale per allineamento idazione in caso di creazione 
		var agenzID=this.idAgenzia;
		var regOpID=this.idRegolaOp;
		var mainGridStore=this.listStore;
		var IdMain = this.getId();
		var tAg = this.titAg;
		var tReg = this.titPrec;
		var sceltaC = this.campo;
		var OldF = this.arOldFields;
		var Var3grid=false;
		var Var2grid=false;
		var Var1grid=false;
		var tTab='';
		var idTipoAss='';
		var comChgFam=false;
		var sqlClassCmb='';
		var tabella='';
		var subCondFam='';
		var campoId='';
		var AllowBlank3gAlternate=true;//controllare in save
		var itemDistribuzione='';
		switch(this.campo){
			case 'NumTipAff':
				Var3grid=false;
				Var2grid=false;
				Var1grid=true;
				tabella='regolaprovvigione';
				campoId='IdRegolaProvvigione';
				subCondFam=' where';
				tTab='Dettaglio regola';
				sqlClassCmb="SELECT cl.IdClasse,cl.TitoloClasse FROM classificazione cl where ifnull(FlagRecupero,'N') ='N'";
				sqlClassCmb+=" and ifnull(FlagNoAffido,'N') ='Y' union all select -1,'' order by 1";
				itemDistribuzione=[['C','Equa distribuzione del carico totale'],['I','Equa distribuzione del carico per lotto']];
				break;
			case 'NumRegAff':
				Var3grid=false;
				Var2grid=true;
				Var1grid=false;
				idTipoAss=2;
				tabella='regolaassegnazione';
				campoId='IdRegolaAssegnazione';
				subCondFam=" where ra.tipoassegnazione="+idTipoAss+" and";
				sqlClassCmb="SELECT cl.IdClasse,cl.TitoloClasse FROM classificazione cl where ifnull(FlagRecupero,'N') ='N'";
				sqlClassCmb+=" and ifnull(FlagNoAffido,'N') ='Y' union all select -1,'' order by 1";
				tTab='Dettaglio regola affidamento';
				itemDistribuzione=[['C','Equa distribuzione del carico totale'],['I','Equa distribuzione del carico per lotto'],['P','Preferenziale']];
				break;
			case 'NumRegAffOpe':
				Var3grid=true;
				Var2grid=false;
				Var1grid=false;
				idTipoAss=3;
				tabella='regolaassegnazione';
				campoId='IdRegolaAssegnazione';
				subCondFam=" where ra.tipoassegnazione="+idTipoAss+" and";
				sqlClassCmb="SELECT cl.IdClasse,cl.TitoloClasse FROM classificazione cl where ifnull(FlagRecupero,'N') ='N'";
				sqlClassCmb+=" and ifnull(FlagNoAffido,'N') ='Y' union all select -1,'' order by 1";
				tTab='Dettaglio regola assegnazione ad operatore';
				itemDistribuzione=[['C','Equa distribuzione del carico totale'],['I','Equa distribuzione del carico per lotto']];
				break;
		}
		//var creation=this.isCreation;
		
		//--------------------------------------------
		// STORES DATI 
		//--------------------------------------------

		//store della regola 
		var dsReg = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readAffOpGrid',idReg:this.idRegolaOp, sceltaLettura:this.campo},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordRegAOp)
		});
		
		//store delle classi
		var dsClassi = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlClassCmb
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdClasse'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdClasse', type: 'int'},
				{name: 'TitoloClasse'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		
		//store famiglia
		var sqlFam="SELECT fp.IdFamiglia,fp.TitoloFamiglia FROM famigliaprodotto fp";
			sqlFam+=" where fp.IdFamigliaParent is null";
			sqlFam+=" and now()<fp.DataFin union all select -1,'' order by 1";
		var dsFamiglia = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlFam
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdFamiglia'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdFamiglia', type: 'int'},
				{name: 'TitoloFamiglia'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		
		//store operatore
		var dsOperatore = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "Select IdUtente,NomeUtente from utente where idreparto="+agenzID
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdUtente', type: 'int'},
				{name: 'NomeUtente'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		//store aree
		var dsAree = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: "SELECT IdArea,TitoloArea FROM area where tipoarea='R' union all select -1,'' order by TitoloArea asc"
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdArea'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdArea', type: 'int'},
				{name: 'TitoloArea'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		//store distribuzione
		var dsDistr = new Ext.data.ArrayStore({
	        id: 'distStore',
	        idIndex: 0,  
		    fields: [
				       'TipoDistribuzione',
				       {name: 'tipodistribuzioneConv'}
				    ]
	    });
		
		
		//store regole provvigione
		var sqlRegP="select rp.IdRegolaProvvigione,concat(r.titoloufficio,' (',CodRegolaProvvigione,')') as Nominativo";
		sqlRegP+=" from regolaprovvigione rp"; 
		sqlRegP+=" left join reparto r on(rp.idreparto=r.idreparto)";
		sqlRegP+=" union all select -1,'' order by 1";
		var dsRegProvv = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				sql: sqlRegP
			},
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdRegolaProvvigione'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdRegolaProvvigione', type: 'int'},
				{name: 'Nominativo'}]
			),
			listeners:{
				'load':function(){}
			}
		});
		
		//-----------------------------------------------
		//BUTTONS
		//-----------------------------------------------
		var dettFascia = new Ext.Button({
			text: 'fasce associate',
			boxMinWidth:100,
			hidden:!Var1grid,
			disabled:(regOpID==''),
			handler: function(b,event) {
				var ArrSaveStateFields=[];
				ArrSaveStateFields.push(Ext.getCmp('Treg').getValue());
				ArrSaveStateFields.push(Ext.getCmp('Creg').getValue());
				ArrSaveStateFields.push(Ext.getCmp('cmbFpAA').getValue());
				ArrSaveStateFields.push(Ext.getCmp('cmbClAA').getValue());
				ArrSaveStateFields.push(Ext.getCmp('TxtFormula').getValue());
				ArrSaveStateFields.push(Ext.getCmp('TxtAbbReg').getValue());
				ArrSaveStateFields.push(Ext.getCmp('cmbTFaA').getValue());
				ArrSaveStateFields.push(Ext.getCmp('DataIni').getValue());
				ArrSaveStateFields.push(Ext.getCmp('DataFin').getValue());
				
				ArrSaveStateFields.push(Ext.getCmp('IDurataProvv').getValue());
				ArrSaveStateFields.push(Ext.getCmp('ckRiFAff').getValue());
				ArrSaveStateFields.push(Ext.getCmp('ckChConMens').getValue());
				ArrSaveStateFields.push(Ext.getCmp('ckCerved').getValue());
				Ext.getCmp(IdMain).showGrigliaFasceAssociate(ArrSaveStateFields);
				winReg.close();
			},
			scope: this
		});
		
		var chiudi = new Ext.Button({
			text: 'Annulla',
			handler: function(b,event) {
				winReg.close();
			},
			scope: this
		});
		
		//Bottone di salvataggio
		var save = new Ext.Button({
			store:dsReg,
			text: 'Salva',
			id:'svBtnAOp',
			disabled:true,
			handler: function(b,event) 
			{
				if (formAssOp.getForm().isDirty()) 
				{	
					if (formAssOp.getForm().isValid())
					{
						//funzione di controllo
						var Errors='';
						if(idTipoAss==2)// se è una griglia di secondo tipo (regole per agenzia)
						{
							//controlla i campi in questione solo per la form del tipo che li richiede
							Errors=validateForm();
						}
						
						if(Errors=='')
						{
							var cond=Ext.getCmp('TxtCond').getValue();
							var ErrorsSql='';
							if(cond!='' && idTipoAss!='')//se è di secondo tipo e la condizione c'è: controlla che sia valida prima di salvare  
							{
								Ext.Ajax.request({
									url : 'server/AjaxRequest.php' , 
									params : {task: 'read',sql: "SELECT 1 as num FROM v_cond_affidamento c where "+cond+" limit 1"},
									method: 'POST',
									autoload:true,
									success: function ( result, request ) {
										var jsonData = Ext.util.JSON.decode(result.responseText);
										//console.log("jd "+jsonData.results[0]);
										if(jsonData.error==null || jsonData.error=='')
										//if(jsonData.results[0]!=null && jsonData.results[0]!='')
										{
											var slave=jsonData.total;
											if(slave>=0) { // accetta anche se 0 (dal 2016-05-08) regole assegnazione non automatiche
												ErrorsSql='';
												this.setDisabled(true);
												//scelta caso ed assegnazione di tipoassegnazione
												formAssOp.getForm().submit({
													url: 'server/gestioneAssegnazioni.php', method: 'POST',
													params: {task:"saveAssOp",idReg:regOpID,idRep:agenzID,scelta:sceltaC,idTipoAss:idTipoAss},
													success: function (frm,action) {
														winReg.close();
														Ext.MessageBox.alert('Esito', action.result.messaggio);
														if(mainGridStore!=null){
															mainGridStore.reload();
														}
													},
													failure: function (frm,action) {//saveFailure
														Ext.MessageBox.alert('Esito', action.result.messaggio); 
														winReg.close();
														if(mainGridStore!=null)
															mainGridStore.reload();
													}
												});
											}else{
												ErrorsSql="La condizione specificata non restituisce alcun risultato.";
												Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
											}
										}else{
											ErrorsSql=jsonData.error;
											Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
										}
									},
									failure: function ( result, request) { 
										ErrorsSql='Errore nel contattare il server. Controllare che sia online.';
										Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
									},
									scope:this
								});
							}else{
								//se nn siamo nella griglia di secondo tipo od il campo condizione di questa
								//è vuoto
								
								//controllo per griglia di primo tipo su eventuali errori nei campi di fascia
								var eliminaPreexistFasce=0;
								var erroreG3fasce='';
								var stopNormalToSpecial=false;
								var fail=false;
								if(idTipoAss=='')//se siamo nella griglia 1 ossia di tipologia regola
								{
//									if(AllowBlank3gAlternate)
//									{
//										//combo selezionata
//										var nF=0;
//										if(dsReg.getAt(0)!=undefined)
//										{
//											nF=dsReg.getAt(0).get('numFasce');
//										}
//										if(Ext.getCmp('cmbTFaA').getValue()!='')// && nF>0)
//										{
//											//ok
//											fail=false;
//										}else{
//											//pessimo
//											fail=true;
//											if(Ext.getCmp('cmbTFaA').getValue()=='')
//											{
//												erroreG3fasce='Tipologia di fascia non selezionata.';
//											}/*else{
//												erroreG3fasce='Fasce per questa tipologia non specificate.';
//											}*/
//										}
//									}else{
										//textfield di fascia non nullo e abbreviazione specificata 
										var formula=Ext.getCmp('TxtFormula').getValue();
										var abbr=Ext.getCmp('TxtAbbReg').getValue();
										var cmbFascia=Ext.getCmp('cmbTFaA').getValue();
										if((formula!='' && cmbFascia!='')||(formula=='' && cmbFascia!='')||(formula!='' && cmbFascia=='' && abbr!=''))
										{
											//ok
											fail=false;
											//controllo che non vi siano ancora fasce associate
											var nF=0;
											if(dsReg.getAt(0)!=undefined)
											{
												nF=dsReg.getAt(0).get('numFasce');
											}
											//fasce presenti e TIPO 1
											if(nF>0)
											{	
												var ErrorsSql='';
												if(formula=='')
													formula=true;
												
												Ext.Ajax.request({
													url : 'server/AjaxRequest.php' , 
													params : {task: 'read',sql: "SELECT 1 as num FROM v_provvigione c where "+formula+" limit 1"},
													method: 'POST',
													autoload:true,
													success: function ( result, request ) {
														var jsonData = Ext.util.JSON.decode(result.responseText);
														//console.log("jd "+jsonData.results[0]);
														if(jsonData.error==null || jsonData.error=='')
														//if(jsonData.results[0]!=null && jsonData.results[0]!='')
														{
															var slave=jsonData.total;
															if(slave>=0) { // accetta anche se 0 (dal 2016-05-08) regole assegnazione non automatiche
																ErrorsSql='';
																this.setDisabled(true);
																stopNormalToSpecial=true;
																Ext.MessageBox.show(
																{
														    		   title:'Attenzione',
														    		   msg: "Vi sono fasce ancora collegate alla regola, l\'operazione le canceller&agrave. <br />Si desidera continuare?",
														    		   buttons: Ext.Msg.YESNO,
														    		   fn: function(btn, text,opt)
														    		   {
																			if (btn == 'yes')
																			{
																				eliminaPreexistFasce=1;
																			}
																			Ext.getCmp('svBtnAOp').setDisabled(true);
																			//scelta caso ed assegnazione di tipoassegnazione
																			console.log("in save + fasce");
																			formAssOp.getForm().submit({
																				url: 'server/gestioneAssegnazioni.php', method: 'POST',
																				params: {task:"saveAssOp",idReg:regOpID,idRep:agenzID,scelta:sceltaC,idTipoAss:idTipoAss,delOldFasce:eliminaPreexistFasce},
																				success: function (frm,action) {
																					winReg.close();
																					Ext.MessageBox.alert('Esito', action.result.messaggio);
																					if(mainGridStore!=null){
																						mainGridStore.reload();
																					}
																				},
																				failure: function (frm,action) {//saveFailure
																					Ext.MessageBox.alert('Esito', action.result.messaggio); 
																					winReg.close();
																					if(mainGridStore!=null)
																						mainGridStore.reload();
																				}
																			});	
														    		   },
														    		   animEl: 'elId',
														    		   icon: Ext.MessageBox.QUESTION
															    });
															}else{
																ErrorsSql="La condizione specificata non restituisce alcun risultato.";
																Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
															}
														}else{
															ErrorsSql=jsonData.error;
															Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
														}
													},
													failure: function ( result, request) { 
														ErrorsSql='Errore nel contattare il server. Controllare che sia online.';
														Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
													},
													scope:this
												});
											}
										}else{
											//pessimo
											fail=true;
											if(formula=='' && cmbFascia=='')
											{
												erroreG3fasce='La formula e la fascia non possono essere entrambe non specificate.';
											}else {
												erroreG3fasce='Abbreviazione obbligatoria in mancanza di una fascia specificata.';
											}
										}
//									}
								}
								
								//caso di salvataggio per una TIPO 1 senza fasce associate
								if(!fail && !stopNormalToSpecial)
								{
									if(idTipoAss=='')
									{
										var formula=Ext.getCmp('TxtFormula').getValue();
										var abbr=Ext.getCmp('TxtAbbReg').getValue();
										var cmbFascia=Ext.getCmp('cmbTFaA').getValue();
										if((formula!='' && cmbFascia!='')||(formula=='' && cmbFascia!='')||(formula!='' && cmbFascia=='' && abbr!=''))
										{
											var ErrorsSql='';
											if(formula=='')
												formula=true;
											
											Ext.Ajax.request({
												url : 'server/AjaxRequest.php' , 
												params : {task: 'read',sql: "SELECT 1 as num FROM v_provvigione c where "+formula+" limit 1"},
												method: 'POST',
												autoload:true,
												success: function ( result, request ) {
													var jsonData = Ext.util.JSON.decode(result.responseText);
													//console.log("jd "+jsonData.results[0]);
													if(jsonData.error==null || jsonData.error=='')
													//if(jsonData.results[0]!=null && jsonData.results[0]!='')
													{
														var slave=jsonData.total;
														if(slave>=0) { // accetta anche se 0 (dal 2016-05-08) regole assegnazione non automatiche
															ErrorsSql='';
															this.setDisabled(true);
															console.log("in save 0 fasce");
															//scelta caso ed assegnazione di tipoassegnazione
															formAssOp.getForm().submit({
																url: 'server/gestioneAssegnazioni.php', method: 'POST',
																params: {task:"saveAssOp",idReg:regOpID,idRep:agenzID,scelta:sceltaC,idTipoAss:idTipoAss,delOldFasce:eliminaPreexistFasce},
																success: function (frm,action) {
																	winReg.close();
																	Ext.MessageBox.alert('Esito', action.result.messaggio);
																	if(mainGridStore!=null){
																		mainGridStore.reload();
																	}
																},
																failure: function (frm,action) {//saveFailure
																	Ext.MessageBox.alert('Esito', action.result.messaggio); 
																	winReg.close();
																	if(mainGridStore!=null)
																		mainGridStore.reload();
																}
															});
														}else{
															ErrorsSql="La condizione specificata non restituisce alcun risultato.";
															Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
														}
													}else{
														ErrorsSql=jsonData.error;
														Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
													}
												},
												failure: function ( result, request) { 
													ErrorsSql='Errore nel contattare il server. Controllare che sia online.';
													Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
												},
												scope:this
											});
										}else{
											//pessimo
											fail=true;
											if(formula=='' && cmbFascia=='')
											{
												erroreG3fasce='La formula e la fascia non possono essere entrambe non specificate.';
											}else {
												erroreG3fasce='Abbreviazione obbligatoria in mancanza di una fascia specificata.';
											}
											Ext.MessageBox.alert('Errore nella compilazione dei campi',erroreG3fasce);
										}
									}else{
										//caso 2 e 3
										var formula=Ext.getCmp('TxtFormula').getValue();
										var ErrorsSql='';
										if(formula=='')
											formula=true;
										
										Ext.Ajax.request({
											url : 'server/AjaxRequest.php' , 
											params : {task: 'read',sql: "SELECT 1 as num FROM v_provvigione c where "+formula+" limit 1"},
											method: 'POST',
											autoload:true,
											success: function ( result, request ) {
												var jsonData = Ext.util.JSON.decode(result.responseText);
												//console.log("jd "+jsonData.results[0]);
												if(jsonData.error==null || jsonData.error=='')
												//if(jsonData.results[0]!=null && jsonData.results[0]!='')
												{
													var slave=jsonData.total;
													if(slave>=0) { // accetta anche se 0 (dal 2016-05-08) regole assegnazione non automatiche
														ErrorsSql='';
														this.setDisabled(true);
														console.log("in save 0 fasce");
														//scelta caso ed assegnazione di tipoassegnazione
														formAssOp.getForm().submit({
															url: 'server/gestioneAssegnazioni.php', method: 'POST',
															params: {task:"saveAssOp",idReg:regOpID,idRep:agenzID,scelta:sceltaC,idTipoAss:idTipoAss,delOldFasce:eliminaPreexistFasce},
															success: function (frm,action) {
																winReg.close();
																Ext.MessageBox.alert('Esito', action.result.messaggio);
																if(mainGridStore!=null){
																	mainGridStore.reload();
																}
															},
															failure: function (frm,action) {//saveFailure
																Ext.MessageBox.alert('Esito', action.result.messaggio); 
																winReg.close();
																if(mainGridStore!=null)
																	mainGridStore.reload();
															}
														});
													}else{
														ErrorsSql="La condizione specificata non restituisce alcun risultato.";
														Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
													}
												}else{
													ErrorsSql=jsonData.error;
													Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
												}
											},
											failure: function ( result, request) { 
												ErrorsSql='Errore nel contattare il server. Controllare che sia online.';
												Ext.MessageBox.alert('Errore nella compilazione dei campi',ErrorsSql);
											},
											scope:this
										});
									}
								}else{
									if(!stopNormalToSpecial)
										Ext.MessageBox.alert('Errore nella compilazione dei campi',erroreG3fasce);
								}
							}
						}else{
							Ext.MessageBox.alert('Errore nella compilazione dei campi','Nella:'+Errors);
						}
					}
				}else{
					console.log("no change");
				}
			},
			scope: this
		});
		
		//----------------------------------------------
		//COSTRUZIONE DEL FORM 
		
		// DICHIARAZIONE DEI VARI CAMPI DEL FORM
		
		// TEXTFIELDS
		var titoloRegolaTxt  = new Ext.form.TextField({
			fieldLabel:'Titolo regola',
			name:'TitoloRegolaProvvigione', 
			id:'Treg', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var1grid, 
			allowBlank:!Var1grid
        });
		
		var codiceRegolaTxt  = new Ext.form.TextField({
			fieldLabel:'Codice regola',	
			name:'CodRegolaProvvigione', 
			id:'Creg', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var1grid, 
			allowBlank:!Var1grid
        });
		
		var giorniFineFissatoTxt  = new Ext.form.TextField({
			fieldLabel:'Giorni fine fissato',	
			name:'GiorniFissiFine', 
			id:'Gff', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var2grid
        });
		
		var giorniInizioFissatoTxt  = new Ext.form.TextField({
			fieldLabel:'Giorni inizio fissato',
			name:'GiorniFissiInizio', 
			id:'Gif', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var2grid,
			listeners:{
				change: function(field,nw,ow){
					if(nw=='')
					{
						giorniFineFissato.setDisabled(false);
					}else{
						giorniFineFissato.setDisabled(true);
						var fixEndField=adjustFixedField(nw);
						if(fixEndField=='')
						{
							Ext.MessageBox.alert('Errore', 'Valori inseriti nel campo di "Giorni inizio fissato" non conformi alle regole.');
							field.setValue('');
						}else{
							field.setValue(fixEndField);
						} // fine else
					}//fine else
				}//fine change
	        }// fine listeners
        });
		
		var durataAssegnazioneTxt  = new Ext.form.TextField({
			fieldLabel:'Durata assegnazione',	
			name:'DurataAssegnazione', 
			id:'IDurata', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var2grid
        });
		
		var durataAssegnazioneProvvTxt  = new Ext.form.TextField({
			xtype:'numberfield',
			fieldLabel: 'Durata assegnazione',
			name:'durata', 
			id:'IDurataProvv',
    		allowNegative: false,
    		minValue :0,
    		allowBlank: !Var1grid,
    		disabled:true,
			hidden:!Var1grid,
    		style: 'text-align:right',
    		decimalPrecision: 0,
    		width: 30
        });
		
		var condizioneTxt  = new Ext.form.TextField({
			fieldLabel:'Condizione',	
			name:'Condizione', 
			id:'TxtCond', 
			style:'text-align:left', 
			disabled:true,
			hidden:Var1grid
        });
		
		
		var formulaTxt  = new Ext.form.TextField({
			fieldLabel:'Formula',
			name:'Formula', 
			id:'TxtFormula', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var1grid,
			allowBlank:true
		});
		
		var abbreviazioneRegolaProvv  = new Ext.form.TextField({
			fieldLabel:'Abbreviazione regola',	
			name:'AbbrRegolaProvvigione', 
			id:'TxtAbbReg', 
			style:'text-align:left', 
			disabled:true,
			hidden:!Var1grid,
			allowBlank:true
		});
		
		
		//LABEL
		var giorniInizioFissatoLbl  = new Ext.form.Label({
			xtype:'label',
			text:'[0 indica l\'ultimo giorno del mese]',	
			hidden:!Var2grid
        });
		
		//CHECKBOX
		
		var chkChiusuraContabMensile  = new Ext.form.Checkbox({
			style: 'padding-left:5px; anchor:"0%";',
			boxLabel: 'Chiusura contabile mensile',
			id: 'ckChConMens',
			name:'ChkChConMens',
			hiddenName: 'ChkChConMens',
			hideLabel: true,
			hidden: !Var1grid,
			checked: false
		});
		var chkRientroFineAffido  = new Ext.form.Checkbox({
			style: 'padding-left:0px; anchor:"0%";',
			boxLabel: 'Rientro a fine affido',
			id: 'ckRiFAff',
			name:'ChkRiFAff',
			hiddenName: 'ChkRiFAff',
			hideLabel: true,
			hidden: !Var1grid,
			checked: false
        });
		
		var chkFileCervedAutom  = new Ext.form.Checkbox({
			style: 'padding-left:0px; anchor:"0%";',
			boxLabel: 'File Cerved automatico',
			id: 'ckCerved',
			name:'ChkCerved',
			hiddenName: 'ChkCerved',
			hideLabel: true,
			hidden: !Var1grid,
			checked: false
        });
		
		
		// COMBO
		//combo operatore
		var comboOperatore  = new Ext.form.ComboBox({
			fieldLabel: 'Operatore',
			name:'cmbAssOpeAA',
			id:'cmbOpAA',
			allowBlank: !Var3grid,
			hiddenName: 'cmbAssOpeAA',
			typeAhead: false, 
			editable:false,
			disabled:true,
			hidden:!Var3grid,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			store: dsOperatore,
			displayField: 'NomeUtente',
			valueField: 'IdUtente',
			listeners:{
						scope:this,
						select:function(combo, record, index){
							//Ext.getCmp('abbStato').setValue(dsReg.getAt(index).get('Abbr'));
						}
			}
        });
		
		//combo famiglia prodotto
		var comboFamigliaProdotto  = new Ext.form.ComboBox({
			fieldLabel: 'Famiglia prodotto',
			name:'cmbFamProdAA',
			id:'cmbFpAA',
			allowBlank: true,
			hiddenName: 'cmbFamProdAA',
			typeAhead: false,
			valueNotFoundText:'',
			editable:false,
			disabled:true,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			store: dsFamiglia,
			displayField: 'TitoloFamiglia',
			valueField: 'IdFamiglia',
			listeners:{
						scope:this,
						select:function(combo, record, index){
							
						}								
			}
        });
		
		//combo classe
		var comboClasse  = new Ext.form.ComboBox({
			fieldLabel: 'Classe',
			name:'cmbClassAA',
			id:'cmbClAA',
			allowBlank: true,
			hiddenName: 'cmbClassAA',
			typeAhead: false, 
			editable:false,
			disabled:true,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			store: dsClassi,
			valueNotFoundText:'',
			displayField: 'TitoloClasse',
			valueField: 'IdClasse',
			listeners:{
						scope:this,
						select:function(combo, record, index){
							
						}
			}
        });
		
		//combo tipo distribuzione
		var comboTipoDistribuzione  = new Ext.form.ComboBox({
			fieldLabel: 'Tipo distribuzione',
			name:'cmbTipDisAA',
			id:'cmbTdAA',
			allowBlank: Var1grid,
			hiddenName: 'cmbTipDisAA',
			typeAhead: false, 
			editable:false,
			disabled:true,
			hidden:Var1grid,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			mode:'local',
		    lazyRender:true,
			store: dsDistr,
			displayField: 'tipodistribuzioneConv',
			valueField: 'TipoDistribuzione',
			listeners:{
						scope:this,
						select:function(combo, record, index){
			
						}
			}
        });
		
		//combo Area
		var comboArea = new Ext.form.ComboBox({
			fieldLabel: 'Area',
			name:'cmbAreaAA',
			id:'cmbAAA',
			allowBlank: true,
			hiddenName: 'cmbAreaAA',
			typeAhead: false, 
			editable:false,
			disabled:true,
			hidden:!Var2grid,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			store: dsAree,
			valueNotFoundText:'',
			displayField: 'TitoloArea',
			valueField: 'IdArea',
			listeners:{
						scope:this,
						select:function(combo, record, index){
							
						}
			}
        });
		
		
		//combo tipo fascia
		var comboTipoFascia = new Ext.form.ComboBox({
			xtype: 'combo',
			fieldLabel: 'Tipo di fascia',
			name:'cmbTipFascA',
			id:'cmbTFaA',
			allowBlank: true,
			hiddenName: 'cmbTipFascA',
			typeAhead: false, 
			editable:false,
			disabled:true,
			hidden:!Var1grid,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			mode:'local',
		    lazyRender:true,
			store: new Ext.data.ArrayStore({
		        id: 'fasciaStore',
		        idIndex: 0,  
			    fields: [
					       'FormulaFascia',
					       {name: 'idF', type: 'int'}
					    ],
		        data: [	['',-1],
		               	['IPR',0],
		   				['IPM',1],
		   				['IPF',2],
		   				['NuovoTasso']]
		    }),
			displayField: 'FormulaFascia',
			valueField: 'FormulaFascia',
        });
		
		//combo tipo regola provv.
		var comboRegolaProvviggione = new Ext.form.ComboBox({
			fieldLabel: 'Regola provvigione',
			name:'cmbRegPro',
			id:'cmbProvvAAA',
			allowBlank: true,
			hiddenName: 'cmbRegPro',
			typeAhead: false, 
			editable:false,
			disabled:true,
			hidden:!Var2grid,
			triggerAction: 'all',
			lazyRender: true,	//should always be true for editor
			store: dsRegProvv,
			valueNotFoundText:'',
			displayField: 'Nominativo',
			valueField: 'IdRegolaProvvigione'
        });

		// DICHIARAZIONE DEI VARI FIELDSET
		var fieldsetRegola = new Ext.form.FieldSet({
			xtype:'fieldset', 
			title:'', 
			border: Var1grid,
			columnWidth:1,
			hidden:!Var1grid
        });
		
		var fieldsetConfig_1 = new Ext.form.FieldSet({
			xtype:'fieldset', 
			title:'', 
			border: true,
			columnWidth:1
        });
		
		var fieldsetConfig_2 = new Ext.form.FieldSet({
			xtype:'fieldset', 
			title:'', 
			border: true,
			columnWidth:1
        });
		
		// DICHIARAZIONE DEI VARI PANEL INSERITI NEI FIELDSET
		var regolaPanel = new Ext.Panel({
			layout:'form', 
			labelWidth:130,
			columnWidth:.70, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var configPnl_1 = new Ext.Panel({
			layout:'form', 
			labelWidth:130,
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var ggInizioFissatoPnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:.50, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var durataAssegnazionePnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:.50, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var ggFineFissatoPnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:.50, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var ggFineFissatoLblPnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:.50, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var checkBoxesPnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:.50, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var condizionePnl = new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:1, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var provvConfigPnl_1= new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:1, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var tipoFasciaPnl= new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			labelWidth:130,
			columnWidth:0.80, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		var dettFasciaPnl= new Ext.Panel({
			xtype:'panel', 
			layout:'form', 
			columnWidth:0.20, 
			defaults: {anchor:'99%', readOnly:false}
        });
		
		// DICHIARAZIONE DEI VARI CONTAINERS
		var durataAssegnazioneCnt = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var ggInizioFissatoCnt = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var ggFineFissatoCnt = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var checkBoxesCnt = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var condizioneCnt = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var provvConfigCnt_1 = new Ext.Container({
			xtype:'container', 
			layout:'column'
        });
		
		var provvFasciaConfigCnt = new Ext.Container({
			xtype:'container', 
			anchor : '100%',
			layout:'column'
        });

		//COMPONGO IL FIELDSET DEI DATI DELLA REGOLA (1° FIELDSET VISIBILE SUL FORM)
		regolaPanel.add(titoloRegolaTxt);
		regolaPanel.add(codiceRegolaTxt);
		fieldsetRegola.add(regolaPanel);
		
		//COMPONGO IL FIELDSET DEI DATI DI CONFIGURAZIONE  DELLA REGOLA (2° FIELDSET VISIBILE SUL FORM)
		configPnl_1.add(comboOperatore);
		configPnl_1.add(comboFamigliaProdotto);
		configPnl_1.add(comboClasse);
		configPnl_1.add(comboTipoDistribuzione);
		configPnl_1.add(comboArea);
		configPnl_1.add(comboRegolaProvviggione);
		fieldsetConfig_1.add(configPnl_1);
		
		//COMPONGO IL CONTAINER DEI GIORNI INZIO E FINE E DURATA ASSEGNAZIONE
		ggInizioFissatoPnl.add(giorniInizioFissatoTxt);
		ggInizioFissatoCnt.add(ggInizioFissatoPnl);

		durataAssegnazionePnl.add(durataAssegnazioneTxt);      // in caso di assegn.
		durataAssegnazionePnl.add(durataAssegnazioneProvvTxt); // in caso di provv.
		ggInizioFissatoCnt.add(durataAssegnazionePnl);
		
		ggFineFissatoPnl.add(giorniFineFissatoTxt);
		ggFineFissatoCnt.add(ggFineFissatoPnl);
		
		ggFineFissatoLblPnl.add(giorniInizioFissatoLbl)
		ggFineFissatoCnt.add(ggFineFissatoLblPnl);
		
		//COMPONGO IL CONTAINER DEI CHECKBOX 
		checkBoxesPnl.add(chkChiusuraContabMensile);
		checkBoxesPnl.add(chkRientroFineAffido);
		checkBoxesPnl.add(chkFileCervedAutom);
		checkBoxesCnt.add(checkBoxesPnl);
		
		
		//COMPONGO IL CONTAINER DELLA CONDIZIONE
		condizionePnl.add(condizioneTxt);
		condizioneCnt.add(condizionePnl);
		
		
		//COMPONTO IL CONTAINER DELLA CONFIGURAZIONE DELLA PROVVIGIONE 
		provvConfigPnl_1.add(formulaTxt);
		provvConfigPnl_1.add(abbreviazioneRegolaProvv);
		provvConfigCnt_1.add(provvConfigPnl_1);
		
		tipoFasciaPnl.add(comboTipoFascia);
		dettFasciaPnl.add(dettFascia);
		provvFasciaConfigCnt.add(tipoFasciaPnl);
		provvFasciaConfigCnt.add(dettFasciaPnl);
		
		//COMPONGO IL FIELDSET (3° FIELDSET VISIBILE SUL FORM)
		fieldsetConfig_2.add(ggInizioFissatoCnt);
		fieldsetConfig_2.add(ggFineFissatoCnt);
		fieldsetConfig_2.add(checkBoxesCnt);
		fieldsetConfig_2.add(condizioneCnt);
		fieldsetConfig_2.add(provvConfigCnt_1);
		fieldsetConfig_2.add(provvFasciaConfigCnt);
		fieldsetConfig_2.add(validityDatesInColumns(130)); // date di inizio e fine validità
		
		//COMPONGO IL FORM FINALE
		var	formItems = [fieldsetRegola, fieldsetConfig_1, fieldsetConfig_2];
				
		//Form su cui montare gli elementi
		var formAssOp = new Ext.form.FormPanel({
			title:tTab,		//il titolo è usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordRegAOp
			}),
			items: [formItems],
			buttons:[save,chiudi]
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
							showRegAssOpDetail(agenzID,rec.get(campoId),mainGridStore,newIndex,tReg,tAg,this.campo);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showRegAssOpDetail(agenzID,rec.get(campoId),mainGridStore,newIndex,tReg,tAg,this.campo);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formAssOp],
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
					'-', helpButton("DettaglioAssOperatore")]
	        }),
	        id: 'pnlDettAz',
	        listeners: {
				tabchange: function(panel, tab) {
					var myIdx = panel.items.indexOf(panel.getActiveTab());
					var showButtons = ((myIdx==3) && (panel.id=='pnlDettAz'));

					this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
					this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	            }
	        }
        });
		
		DCS.DettaglioRegAssOp.superclass.initComponent.call(this);
		
		//caricamento dello store
		dsOperatore.load({
			callback : function(r,options,success) 
			{
				dsFamiglia.load({
					callback : function(r,options,success) 
					{
						dsClassi.load({
							callback : function(r,options,success) 
							{
								dsRegProvv.load({
									callback : function(r,options,success) 
									{	
										dsAree.load({
											callback : function(r,options,success) 
											{
												dsReg.load({
													callback : function(r,options,success) 
													{
														var loaded=false;
														if (success && r.length>0) 
														{
															range = dsReg.getRange();
															var rec = range[0];
															loaded=true;
															Ext.getCmp('cmbTdAA').setValue(rec.data.tipodistribuzioneConv);
															Ext.getCmp('cmbClAA').setValue(rec.data.IdClasse);
															Ext.getCmp('cmbFpAA').setValue(rec.data.IdFamiglia);
															if(rec.data.IdFamiglia==null)
															{	
																Ext.getCmp('cmbFpAA').clearValue();
																comChgFam=true;
															}
															Ext.getCmp('cmbOpAA').setValue(rec.data.IdUtente);
															Ext.getCmp('cmbAAA').setValue(rec.data.IdArea);
															Ext.getCmp('cmbProvvAAA').setValue(rec.data.IdRegolaProvvigione);
															Ext.getCmp('Gif').setValue(rec.data.GiorniFissiInizio);
															Ext.getCmp('Gff').setValue(rec.data.GiorniFissiFine);
															Ext.getCmp('IDurata').setValue(rec.data.DurataAssegnazione);
															Ext.getCmp('TxtCond').setValue(rec.data.Condizione);
															Ext.getCmp('Treg').setValue(rec.data.TitoloRegolaProvvigione);
															Ext.getCmp('Creg').setValue(rec.data.CodRegolaProvvigione);
															Ext.getCmp('DataIni').setValue(rec.data.DataIni);
															Ext.getCmp('DataFin').setValue(rec.data.DataFin);
															Ext.getCmp('IDurataProvv').setValue(rec.data.durata);
															if(rec.data.FlagNoRientro=='Y')
																Ext.getCmp('ckRiFAff').setValue(true);
															if(rec.data.FlagMensile=='Y')
																Ext.getCmp('ckChConMens').setValue(true);
															if(rec.data.FlagCerved=='Y')
																Ext.getCmp('ckCerved').setValue(true);
															
															Ext.getCmp('TxtFormula').setValue(rec.data.Formula);
															Ext.getCmp('TxtAbbReg').setValue(rec.data.AbbrRegolaProvvigione);
															Ext.getCmp('cmbTFaA').setValue(rec.data.FormulaFascia);
															Ext.getCmp('TxtFormula').setDisabled(false);
															Ext.getCmp('TxtAbbReg').setDisabled(false);
															Ext.getCmp('cmbTFaA').setDisabled(false);
															
															dettFascia.setText(rec.data.numFasce+' '+dettFascia.getText());
															if(regOpID!='')
															{
																dettFascia.setDisabled(false);
															}
		//													if(rec.data.Formula!=null && rec.data.FormulaFascia==null)
		//													{
		//														Ext.getCmp('TxtFormula').setValue(rec.data.Formula);
		//														Ext.getCmp('TxtAbbReg').setValue(rec.data.AbbrRegolaProvvigione);
		//														Ext.getCmp('TxtFormula').setDisabled(false);
		//														Ext.getCmp('TxtAbbReg').setDisabled(false);
		//														Ext.getCmp('cmbTFaA').setDisabled(true);
		//														dettFascia.setText(rec.data.numFasce+' '+dettFascia.getText());
		//														dettFascia.setDisabled(true);
		//													}else if(rec.data.Formula==null && rec.data.FormulaFascia!=null){
		//														Ext.getCmp('cmbTFaA').setValue(rec.data.FormulaFascia);
		//														Ext.getCmp('TxtFormula').setDisabled(true);
		//														Ext.getCmp('TxtAbbReg').setDisabled(true);
		//														Ext.getCmp('cmbTFaA').setDisabled(false);
		//														dettFascia.setText(rec.data.numFasce+' '+dettFascia.getText());
		//														if(regOpID!='')
		//														{
		//															dettFascia.setDisabled(false);
		//														}
		//													}else{
		//														//non dovrebbe mai entrare qui poichè significa che entrambi i campi sono 
		//														//o vuoti o scritti e ciò dovrebbe non essere possibile a meno di azioni
		//														//dirette sui dati del database
		//														Ext.getCmp('TxtFormula').setValue(rec.data.Formula);
		//														Ext.getCmp('TxtAbbReg').setValue(rec.data.AbbrRegolaProvvigione);
		//														Ext.getCmp('cmbTFaA').setValue(rec.data.FormulaFascia);
		//														dettFascia.setText(rec.data.numFasce+' '+dettFascia.getText());
		//														if(regOpID!='')
		//														{
		//															dettFascia.setDisabled(false);
		//														}
		//													}
														}
														Ext.getCmp('cmbTdAA').setDisabled(false);
														Ext.getCmp('cmbClAA').setDisabled(false);
														Ext.getCmp('cmbFpAA').setDisabled(false);
														Ext.getCmp('cmbOpAA').setDisabled(false);
														Ext.getCmp('cmbAAA').setDisabled(false);
														Ext.getCmp('cmbProvvAAA').setDisabled(false);
														Ext.getCmp('Gif').setDisabled(false);
														Ext.getCmp('Gff').setDisabled(false);
														Ext.getCmp('IDurata').setDisabled(false);
														Ext.getCmp('IDurataProvv').setDisabled(false);
														Ext.getCmp('TxtCond').setDisabled(false);
														Ext.getCmp('svBtnAOp').setDisabled(false);
														Ext.getCmp('Treg').setDisabled(false);
														Ext.getCmp('Creg').setDisabled(false);
														if(!loaded)
														{
															Ext.getCmp('TxtFormula').setDisabled(false);
															Ext.getCmp('TxtAbbReg').setDisabled(false);
															Ext.getCmp('cmbTFaA').setDisabled(false);
															dettFascia.setText('0 '+dettFascia.getText());
															if(regOpID!='')
															{
																dettFascia.setDisabled(false);
															}
														}
														dsDistr.loadData(itemDistribuzione);
														if(OldF!='')
														{
															Ext.getCmp('Treg').setValue(OldF[0]);
															Ext.getCmp('Creg').setValue(OldF[1]);
															Ext.getCmp('cmbFpAA').setValue(OldF[2]);
															Ext.getCmp('cmbClAA').setValue(OldF[3]);
															if(OldF[4]=='' && OldF[5]=='')
															{
																Ext.getCmp('TxtFormula').setDisabled(true);
																Ext.getCmp('TxtAbbReg').setDisabled(true);
																Ext.getCmp('cmbTFaA').setDisabled(false);
																dettFascia.setDisabled(false);
															}
															Ext.getCmp('TxtFormula').setValue(OldF[4]);
															Ext.getCmp('TxtAbbReg').setValue(OldF[5]);
															Ext.getCmp('cmbTFaA').setValue(OldF[6]);
															Ext.getCmp('DataIni').setValue(OldF[7]);
															Ext.getCmp('DataFin').setValue(OldF[8]);
															
															Ext.getCmp('IDurataProvv').setValue(OldF[9]);
															Ext.getCmp('ckRiFAff').setValue(OldF[10]);
															Ext.getCmp('ckChConMens').setValue(OldF[11]);
															Ext.getCmp('ckCerved').setValue(OldF[12]);
														}
													},
													scope: this
												});
											},
											scope:this
										});
									},
									scope:this
								});
							},
							scope:this
						});	
					},
					scope:this
				});
			},
			scope:this
		});
	},
	//--------------------------------------------------------
    // Visualizza griglia per le fasce
    //--------------------------------------------------------
	showGrigliaFasceAssociate: function(ArrSaveStateFields)
    {
		var winFaGrid;
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
		myMask.show();
		
		var repAss = this.idAgenzia;
		var regProv = this.idRegolaOp;
		var storeG = this.listStore;
		var ri = this.rowIndex;
		var titReg = this.titPrec;
		var titRep = this.titAg;
		var camp = this.campo;
		var pnl = new DCS.AssFascie.create(this.idRegolaOp,this.titAg,this.titPrec,ArrSaveStateFields);
		var titleWin='Fasce associate all\'agenzia \''+this.titAg+'\' sulla fascia \"'+this.titPrec+'\"';
		winFaGrid = new Ext.Window({
    		width: 1000, height:500, minWidth: 700, minHeight: 300,
    		autoHeight:false,
    		modal: true,
    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    	    title: titleWin,
    		constrain: true,
			items: [pnl]
        });
		Ext.apply(pnl,{winList:winSAz});
		winFaGrid.show();
		myMask.hide();
		pnl.activation.call(pnl);
		winFaGrid.on({
			'close' : function () {
					showRegAssOpDetail(repAss,regProv,storeG,ri,titReg,titRep,camp,ArrSaveStateFields);
				}
		});
    }
});
//-----------------------------------------------------------------------------------
//Funzione di aggiustamento campo fineAffidofissato rispetto all'inizionfAffidofisso
//-----------------------------------------------------------------------------------
function adjustFixedField(campo)
{
	var fixfield='';
	var testCom='';
	var patt='';
	//campo durata
	testCom=campo;
	patt=new RegExp('^(([1-9][0-9]*){1,2},*)*$');
	if(patt.test(testCom))
	{
		//scomponilo e ricomponi un buon campo per il finefix
		var arrSplitted=campo.split(",");
		var flagGood=true;
		for(var k=0;k<arrSplitted.length;k++)
		{
			if(arrSplitted[k]<=31 && arrSplitted[k]>0)
			{
				fixfield+=arrSplitted[k]-1+',';
			}else{
				flagGood=false;
				fixfield="";
			}
		}
		if(flagGood)
		{
			fixfield=fixfield.slice(0,(fixfield.length-1));
		}
	}else{
		//errore: campo non conforme
		fixfield ="";
	}
	return fixfield;
}
//----------------------------------------------------
//Funzione di validazione campi di affidamento agenzia
//----------------------------------------------------
function validateForm()
{
	var errorMsg='';
	var testCom='';
	var patt='';
	//campo durata
	testCom=Ext.getCmp('IDurata').getValue();
	patt=new RegExp('^([1-9][0-9]*){0,}$');
	if(patt.test(testCom))
	{
		var durVal=false;
		if(testCom=='')
		{
			durVal=true;
		}else if(testCom>0 && testCom<=31){
			durVal=true;
		}

		if(durVal)
		{
			//campo di specifica dei campi fissi di inizio/fine 
			testCom=Ext.getCmp('Gif').getValue();
			patt=new RegExp('^(([1-9][0-9]*){1,2},*)*$');
			if(patt.test(testCom))
			{
				if(isInMounth(testCom,false))
				{
					//i valori sono errati anche se la forma è giusta
					//errore: stringa non adatta per data inizio
					errorMsg +="<br /> -Validazione del campo \"Data di inizio fissato\"";
				}else{
					//buono
					testCom=Ext.getCmp('Gff').getValue();
					patt=new RegExp('^((([0]?)|([1-9]{1,2})),*)*$');
					if(patt.test(testCom))
					{
						if(isInMounth(testCom,true))
						{
							//i valori sono errati anche se la forma è giusta
							//errore: stringa non adatta per data fine
							errorMsg +="<br /> -Validazione del campo \"Data di fine fissato\"";
						}
					}else{
						//errore: stringa non adatta per data fine
						errorMsg +="<br /> -Validazione del campo \"Data di fine fissato\"";
					}
				}
			}else{
				//errore: stringa non adatta per data inizio
				errorMsg +="<br /> -Validazione del campo \"Data di inizio fissato\"";
			}
		}else{
			//errore: un carattere numerale ma errato
			errorMsg +="<br /> -Validazione del campo \"Durata\"";
		}
	}else{
		//errore: un carattere non numerale
		errorMsg +="<br /> -Validazione del campo \"Durata\"";
	}
	return errorMsg;
}
//----------------------------------------------
//Funzione di controllo validità semantico array
//----------------------------------------------
function isInMounth(vect,isEnd)
{
	var flagValidMounth=false;
	var arr=vect.split(',');
	var bBound = isEnd==true?0:1;
	if(arr[0]!='')
	{
		for(var k=0;k<arr.length;k++)
		{
			if(arr[k]<bBound || arr[k]>31)
			{
				flagValidMounth=true;
				break;
			}
		}
	}
	return flagValidMounth;
}

// register xtype
Ext.reg('DCS_dettaglioRegAssOp', DCS.DettaglioRegAssOp);

//--------------------------------------------------------------------------------------
//Visualizza dettaglio regole assegnazioni ad operatore per agenzia creazione/modifica
//--------------------------------------------------------------------------------------
function showRegAssOpDetail(IdAg,IdReg,store,rowIndex,titPrec,titAg,campo,arOldFields) 
{
	IdAg=IdAg||'';
	IdReg=IdReg||'';
	rowIndex=rowIndex||0;
	store=store||null;
	campo=campo||'';
	arOldFields=arOldFields||'';
	//var isCreation=false;
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	
	var h=340;
	var wInsT='';
	var wInsTCrea='';
	var wInsTMod='';
	switch(campo)
	{
		case 'NumTipAff':
			wInsT=titPrec;
			wInsTCrea='regola di';
			wInsTMod='regola';
			h=550;
			break;
		case 'NumRegAff':
			wInsT="per l\'agenzia "+titAg;
			wInsTCrea='';
			wInsTMod='regola';
			h=460;
			break;
		case 'NumRegAffOpe':
			h=380;
			if(store!=null)
			{
				wInsT="per l\'operatore "+store.getAt(rowIndex).get('nomeutente');
				wInsTCrea='';
				wInsTMod='regola';
			}
			break;
	}
	
	if(IdReg==''){
		titolo='Creazione di una '+wInsTCrea+' regola per l\'agenzia \''+titAg+'\'';
	}else{
		if(campo!='NumTipAff')
		{
			if(store.getAt(rowIndex).get('TitoloClasse')!="" && store.getAt(rowIndex).get('TitoloClasse')!=null && store.getAt(rowIndex).get('TitoloClasse')!=undefined)
				titPrec = wInsT+" <br />per la classe di affidamento \'"+store.getAt(rowIndex).get('TitoloClasse')+"\'";
		}else{
			titPrec = wInsT;
		}
		titolo='Modifica della '+wInsTMod+' '+titPrec;
	}	
	
	var nameNW = 'dettaglioAssAgOp'+IdAg+'R'+IdReg;
	if (oldWind != '') {
		winReg = Ext.getCmp(oldWind);
		winReg.close();
	}
	oldWind = nameNW;
	winReg = new Ext.Window({
		width: 600,
		height: h,
		minWidth: 550,
		minHeight: h,
		layout: 'fit',
		id:'dettaglioAssAgOp'+IdAg+'R'+IdReg,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: titolo,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioRegAssOp',
			idAgenzia: IdAg,
			listStore: store,
			idRegolaOp:IdReg,
			rowIndex:rowIndex,
			titPrec:titPrec,
			titAg:titAg,
			campo:campo,
			arOldFields:arOldFields
		}]
	});
	winReg.show();
	winReg.on({
		'close' : function () {oldWind = '';}
	});
	myMask.hide();
	
}; // fine funzione 