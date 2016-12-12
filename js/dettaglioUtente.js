// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

DCS.recordUtente = Ext.data.Record.create([
		{name: 'IdUtente', type: 'int'},
		{name: 'IdStatoUtente', type: 'int'},
		{name: 'IdReparto', type: 'int'},
		{name: 'CodUtente'},
		{name: 'NomeUtente'},
		{name: 'Userid'},
		{name: 'Password'},
		{name: 'CellulareUtente'},
		{name: 'Telefono'},
		{name: 'EmailUtente'},
		{name: 'TitoloUfficio'},
		{name: 'TitoloTipoReparto'},
		{name: 'TitoloCompagnia'},										
		{name: 'nomeReferente'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validit�
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validit�
		{name: 'TitoloStatoUtente'}]);
DCS.recordChk = Ext.data.Record.create([
        {name: 'IdProfilo', type: 'int', allowBlank:false},
  		{name: 'CodProfilo', type: 'string' },
		{name: 'TitoloProfilo', type: 'string' },
		{name: 'Ordine', type: 'int'},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},	// Data inizio validit�
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},	// Data fine validit�
		{name: 'LastUpd', type: 'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'LastUser', type: 'string'}]);

DCS.DettaglioUtente = Ext.extend(Ext.TabPanel, {
	idUtente: 0,
	listStore: null,
	rowIndex: -1,
	nome:'',
	
	initComponent: function() {
		
		//Controllo iniziale per allineamento idUtente in caso di nuovo utente 
		if(this.idUtente == ''){
			this.idUtente = 0;
		}
		//stores: dati da visualizzare,dati per riempire il chkgroup,combo,
		var dsPratica = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordUtente)
		});
		
		var ckStore = new Ext.data.Store({
			//id:'ckS',
			proxy: new Ext.data.HttpProxy({
				url: 'server/utentiProfili.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordChk
			})
		});
		
		var dsRepartoUtente = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re order by re.TitoloUfficio" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdReparto'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdReparto'},
				{name: 'TitoloUfficio'}]
			),
			autoLoad: true
		});//end dsRepartoUtente 
		
		var dsStatoUtente = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{	//this parameter is passed for any HTTP request
				task: 'read',
				//Non cancellare//sql: "SELECT re.IdReparto,re.TitoloUfficio FROM reparto re where re.idcompagnia=(select idcompagnia from utente u join reparto r on(u.idreparto=r.idreparto) and u.idutente="+idUtente+");"
				sql: "SELECT IdStatoUtente,TitoloStatoUtente FROM statoutente" 
			},
			/*2. specify the reader*/
			reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdStatoUtente'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'IdStatoUtente'},
				{name: 'TitoloStatoUtente'}]
			),
			autoLoad: true
		});//end dsRepartoUtente 
		
		//CheckGroup e array di configurazione
		var checkboxconfigs = []; //array of about to be checkboxes.   
		var CheckProfGroup = new Ext.form.CheckboxGroup({
		    //id:'CPGroup',
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Single Column',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    items: [checkboxconfigs],
		    listeners:{
				change:function(CheckProfGroup,arr){
					//segna le aggiunte di check
					flag = false;
					arr = CheckProfGroup.getValue();
					for(k=0;k<arr.length;k++)
	            	{
						for(j=0;j<checkboxconfigs.length;j++)
		            	{
							if(arr[k].id==checkboxconfigs[j].id)
							{
								checkboxconfigs[j].checked = arr[k].checked;
								break;
							}
		            	}
	            	}
					//segna le detrazioni di check
					for(j=0;j<checkboxconfigs.length;j++)
	            	{
						flag = false;
						if(checkboxconfigs[j].checked){
							for(k=0;k<arr.length;k++)
			            	{
								if(arr[k].id==checkboxconfigs[j].id)
								{
									flag=true;
									break;
								}
							}
							if(!flag){checkboxconfigs[j].checked = false;}
						}
	            	}
				}
			}
		});
		
		//Bottone di salvataggio
		var save = new Ext.Button({
			sm:CheckProfGroup,
			store:dsPratica,
			text: 'Salva',
			handler: function(b,event) {
				var vect = '';
				
				for(j=0;j<checkboxconfigs.length;j++)
            	{
	            	if(checkboxconfigs[j].checked == true)
	            	{
	            		vect = vect + '|' + checkboxconfigs[j].id;
	            	}
            	}

				if (formPratica.getForm().isDirty()) {	// qualche campo modificato
					formPratica.getForm().submit({
						url: 'server/utentiProfili.php',
				        method: 'POST',
				        params: {task: 'saveP',vect: vect, idUtente: this.idUtente},
				        success: function(frm, action) {
				        	//eval('var resp = '+obj.responseText);
				        	if(action.result.success){
				        		Ext.MessageBox.alert('Esito', "Utente salvato");
				        	}else{
				        		console.log("resp no "+action.result.error);
				        		Ext.MessageBox.alert('Fallito', "Impossibile salvare il profilo: "+action.result.error);
				        	}
				        	console.log("id "+win.getComponent(0).idUtente);
				        	if(win.getComponent(0).idUtente==0){
				        		win.close();
				        	}
						},
						failure: saveFailure,
						waitMsg: 'Salvataggio in corso...'
					});
				}else{
					console.log("no change");
				}
			},
			scope: this
		});
		
		//Form su cui montare gli elementi
		var formPratica = new Ext.form.FormPanel({
			title:'Dati utente',		//il titolo � usato per testare il tab
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordUtente
			}),
			items: [{
				xtype:'container', layout:'column',
				items: [{//colonna sinistra
					xtype:'container',columnWidth:.55,
					items:[{
						/* INIZIO primo fieldset a sinistra: DATI PERSONALI */
						xtype:'fieldset', title:'Dati personali', border: false,
						items:[{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Nome',	name:'NomeUtente', style:'text-align:left',allowBlank: false}]
							}]
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Cellulare', name:'CellulareUtente', style:'text-align:left', vtype: 'cell_list'}]
							}]										
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Telefono', name:'Telefono', style:'text-align:left'}]
							}]										
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:60,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Email', name:'EmailUtente', style:'text-align:left',allowBlank: false}]
							}]														
						}]
						/* FINE primo fieldset a sinistra: DATI PERSONALI */
					},{
						/* INIZIO secondo fieldset a sinistra: DATI DI ACCESSO */
						xtype:'fieldset', title:'Accesso al sistema', border: false, columnWidth:.55,
						items:[{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Abbreviazione',name:'CodUtente', style:'text-align:left',allowBlank: false}]
							}]
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:false},
								items: [{fieldLabel:'Nome utente', name:'Userid', style:'text-align:left',allowBlank: false}]
							}]										
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95,
								defaults: {anchor:'97%', readOnly:false},
								items: [{xtype: 'combo',
									fieldLabel: 'Stato',
									name:'TitoloStatoUtente',
									id:'cmbStato',
									allowBlank: false,
									hiddenName: 'IdStatoUtente',
									typeAhead: false, 
									triggerAction: 'all',
									lazyRender: true,	//should always be true for editor
									store: dsStatoUtente,
									displayField: 'TitoloStatoUtente',
									valueField: 'IdStatoUtente',												
									listeners:{
										scope:this,
										select:function(combo, record, index){
											Ext.Ajax.request({
												url: 'server/AjaxRequest.php', 
				                        		params : {	task: 'read',
													sql: "SELECT IdStatoUtente,TitoloStatoUtente FROM statoutente where IdStatoUtente="+combo.value
												},
												method: 'POST',
												reader:  new Ext.data.JsonReader({
		                    						root: 'results',//name of the property that is container for an Array of row objects
		                    						id: 'IdStatoUtente'//the property within each row object that provides an ID for the record (optional)
													},
		                    						[{name: 'IdStatoUtente'},
		                    						 {name: 'TitoloStatoUtente'}]
			                    				),
				                    			success: function ( result, request ) {
													eval('var resp = ('+result.responseText+').results[0]');
													Ext.getCmp('idS').setValue(resp.IdStatoUtente);
												},
				                        		failure: function ( result, request) {
				                        			Ext.MessageBox.alert('Errore', result.responseText); 
							               		},
							               		autoLoad: true
						                  	});
										}
									}
								}] // fine combo e array item del form che la contiene
							}]  // fine items contenitore column, che contiene il form che contiene la combo
						},{ // campo invisibile contenente IdStatoUtente 
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:true},
								items: [{fieldLabel:'StatoUtente',id:'idS',hidden:true, name:'IdStatoUtente', style:'text-align:left'}]
							}]
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
								defaults: {anchor:'55%', readOnly:false},
								items: [{
									xtype: 'datefield',
									format: 'd/m/Y',
									width: 80,
									autoHeight:true,
									fieldLabel: 'Valido dal',
									name: 'DataIni',
									id:'_valDa',
									listeners:{
										change:function(fld,nv,ov){
										},
										scope:this
									}
								}] // fine items del form che contiene il campo data-da
							}] // fine items del contenitore del form										
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.98, defaultType:'textfield',
								defaults: {anchor:'55%', readOnly:false},
								items: [{
									xtype: 'datefield',
									format: 'd/m/Y',
									width: 80,
									autoHeight:true,
									fieldLabel: 'al',
									name: 'DataFin',
									id:'_valAd',
									listeners:{
										change:function(fld,nv,ov){},
										scope:this
									}
								}] // fine items del form che contiene il campo data-da
							}] // fine items del contenitore del form										
						}] // fine array items del secondo fieldset
						/* FINE secondo fieldset a sinistra: DATI DI ACCESSO */
					},{
						/* INIZIO terzo fieldset a sinistra: APPARTENENZA A REPARTO */
						xtype:'fieldset'/*, id:'Trp'*/, title:'Appartenenza', border: false, /*autoHeight:true,*/ columnWidth:.55,
						items:[{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95,
								defaults: {anchor:'97%', readOnly:false},
								items: [{xtype: 'combo',
									fieldLabel: 'Reparto',
									name:'TitoloUfficio',
									id:'cmbUff',
									allowBlank: false,
									hiddenName: 'IdReparto',
									typeAhead: false, 
									triggerAction: 'all',
									lazyRender: true,	//should always be true for editor
									store: dsRepartoUtente,
									displayField: 'TitoloUfficio',
									valueField: 'IdReparto',
									listeners:{
										scope:this,
										select:function(combo, record, index){
											Ext.Ajax.request({
												url: 'server/AjaxRequest.php', 
				                        		params : {	task: 'read',
															sql: "select r.idtiporeparto,tr.titolotiporeparto,r.nomeReferente,c.TitoloCompagnia from (reparto r left join tiporeparto tr on(r.idtiporeparto=tr.idtiporeparto)) join compagnia c on(r.IdCompagnia=c.Idcompagnia) where r.idreparto ="+combo.value
														},
												method: 'POST',
												reader:  new Ext.data.JsonReader({
														root: 'results',//name of the property that is container for an Array of row objects
														id: 'IdTipoReparto'//the property within each row object that provides an ID for the record (optional)
											 		},
											 		[{name: 'IdTipoReparto'},
											         {name: 'TitoloTipoReparto'},
											         {name: 'nomeReferente'},
											         {name: 'TitoloCompagnia'}]
											    ),
										        success: function ( result, request ) {
										        	eval('var resp = ('+result.responseText+').results[0]');
													Ext.getCmp('Tiporep').setValue(resp.titolotiporeparto);
													Ext.getCmp('NomeR').setValue(resp.nomeReferente);
													Ext.getCmp('CompT').setValue(resp.TitoloCompagnia);
										            //Ext.getCmp('Tiporep').setValue(result.responseText.substring(result.responseText.indexOf('titolotiporeparto')+20,result.responseText.indexOf('nomeReferente')-3));
										            //Ext.getCmp('NomeR').setValue(result.responseText.substring(result.responseText.indexOf('nomeReferente')+16,result.responseText.lastIndexOf('"')));
										            Ext.getCmp('IdRepCom').setValue(combo.value);
												},
										        failure: function ( result, request) { 
										        	Ext.MessageBox.alert('Errore', result.responseText); 
										        },
										        autoLoad: true
										    });
										} // fine funzione nel listenere select
									} // fine propriet� listeners
								}] // fine array items del form che contiene la combo
							}] // fine array items del contenitore column che contiene il form che contiene la combo
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:true},
								items: [{fieldLabel:'Tipo reparto', id:'Tiporep', name:'TitoloTipoReparto', style:'text-align:left'},
								        {fieldLabel:'VariabileRep', id:'IdRepCom', hidden: true, name:'IdRep'}]
							}]										
						},{
							xtype:'container', layout:'column',
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:true},
								items: [{fieldLabel:'Referente', id:'NomeR', name:'nomeReferente', style:'text-align:left'}]
							}]														
						},{
							xtype:'container', layout:'column',
							hidden: true, // dato che per ora non lo gestisce
							items:[{
								xtype:'panel', layout:'form', labelWidth:80,columnWidth:.95, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:true},
								items: [{fieldLabel:'Societ&agrave;', id:'CompT', name:'TitoloCompagnia', style:'text-align:left'}]
							}]														
						}] // fine array items del terzo fieldset
						/* FINE terzo fieldset a sinistra: APPARTENENZA A REPARTO */
					}
					] // fine array items che contiene i fieldsets della colonna sinistra
				},{	// colonna destra
					xtype:'container', columnWidth:.45,
					items:[{ // fieldset che contiene la lista di profili selezionabili
						xtype:'fieldset', id:'fsC', title:'Profili', border:true, layout:'column',
						bodyStyle: 'padding-left:5px;',autoScroll:true, height:440,
						items:[]
					}
				] // fine items della colonna di destra
			} // fine della colonna di destra
		]} // fine del container principale
	], // fine items del formPanel principale
	buttons: [save,
	          {text: 'Annulla', handler: function () {win.close();}}
			 ]
	}); // fine creazione FormPanel
		
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
							showUserDetail(rec.get('IdUtente'),rec.get('NomeUtente'),this.listStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showUserDetail(rec.get('IdUtente'),rec.get('NomeUtente'),this.listStore,newIndex);
			}
		};

		Ext.apply(this, {
			activeTab:0,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formPratica],
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
					'-', helpButton("DettaglioUtente")]
	        })
		//,
//	        id: 'pnlDettPratica',
	        //	        listeners: {
	        //	tabchange: function(panel, tab) {
	        //		var myIdx = panel.items.indexOf(panel.getActiveTab());
	        //		var showButtons = ((myIdx==3) && (panel.id=='pnlDettPratica'));
	        //
	        //		this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
	        //		this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	        //    }
	       //}
        });
		
		DCS.DettaglioUtente.superclass.initComponent.call(this);
		
		//caricamento dei 4 store
		dsPratica.load({
			params:{
				sql: 'Select u.IdUtente,u.IdStatoUtente,u.IdReparto,u.CodUtente,u.NomeUtente,u.Userid,u.Password,u.DataIni,u.DataFin,u.cellulare as CellulareUtente,u.Telefono, u.Email as EmailUtente, r.TitoloUfficio,tr.TitoloTipoReparto,c.TitoloCompagnia,r.nomeReferente,su.TitoloStatoUtente from (((utente u left join reparto r on(u.IdReparto=r.IdReparto)) left join tiporeparto tr on(r.IdTiporeparto=tr.IdTiporeparto)) left join compagnia c on(r.IdCompagnia=c.IdCompagnia))left join statoutente su on(u.IdStatoUtente=su.IdStatoUtente)where idUtente='+this.idUtente+'' 
			},
			callback : function(r,options,success) {
				if (success && r.length>0) {
					formPratica.getForm().loadRecord(r[0]);
				}
				//c'� un solo record il forEach lo estrae
				range = dsPratica.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					Ext.getCmp('cmbUff').setValue(rec.data.TitoloUfficio); 
					Ext.getCmp('IdRepCom').setValue(rec.data.IdReparto);
					Ext.getCmp('cmbStato').setValue(rec.data.TitoloStatoUtente);
					Ext.getCmp('idS').setValue(rec.data.IdStatoUtente);
				}
			},
			scope: this
		});
		
		ckStore.load({
			callback: function(r,options,success){
				  
				range = ckStore.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
				    checkboxconfigs.push({ 
				        id:rec.data.IdProfilo,
				        boxLabel:rec.data.TitoloProfilo,
				        checked: false
				      });
				}

				Ext.Ajax.request({
			        url: 'server/utentiProfili.php',
			        method: 'POST',
			        params: {task: 'checkP', idUtente: this.idUtente},
			        success: function(obj) {
			            if (obj.responseText != '') {
							eval("var ruoli = "+obj.responseText);
							for (var i=0; i<ruoli.length; i++) 
							{
								for (var u=0; u<checkboxconfigs.length; u++) 
								{
									if(checkboxconfigs[u].id==ruoli[i])
									{
										try {
											checkboxconfigs[u].checked= true;
										} catch (err) {}
									}
								}
							}
			            } else {
			                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
			            }
						Ext.getCmp('fsC').add(CheckProfGroup);
						Ext.getCmp('fsC').doLayout();
					},
					scope: this
			    });					
			},
			scope: this
		});
		
		dsRepartoUtente.load();
		dsStatoUtente.load();
	}	
});

// register xtype
Ext.reg('DCS_dettaglioUtente', DCS.DettaglioUtente);

//--------------------------------------------------------
//Visualizza dettaglio utente
//--------------------------------------------------------
function showUserDetail(idUtente,nome,listStore,rowIndex) {
	
	this.listStore = listStore;
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();
	if(nome==''){nome='Creazione nuova utenza';listStore=null;rowIndex=-1;}
	var winTitle = 'Dettaglio utente - ' + nome +'';
	
	var nameNW = 'dettaglio_utente_'+idUtente;
	win = Ext.getCmp(nameNW);
	if (win)
		win.close();

	win = new Ext.Window({
		width: 740,
		height: 600,
		minWidth: 740,
		minHeight: 600,
		layout: 'fit',
		id:nameNW,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		items: [{
			xtype: 'DCS_dettaglioUtente',
			idUtente: idUtente,
			nome:nome,
			listStore: listStore,
			rowIndex: rowIndex
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	win.show();
	win.on({
		'beforeclose' : function () {
				oldWind = '';
				this.listStore.reload();
			},
			scope:this
	});
	myMask.hide();
	
}; // fine funzione showUserDetail