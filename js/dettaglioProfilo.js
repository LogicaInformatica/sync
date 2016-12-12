// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

/**
 * RECORDS
 * */
DCS.recordPFunc = Ext.data.Record.create([
		{name: 'IdFunzione', type: 'int', allowBlank:false},
		{name: 'TitoloFunzione'},
		{name: 'IdGruppo', type: 'int'}]);
DCS.recordPSFunc = Ext.data.Record.create([
  		{name: 'IdFunzione', type: 'int', allowBlank:false},
  		{name: 'TitoloFunzione'}]);
DCS.recordProf = Ext.data.Record.create([
        {name: 'IdProfilo', type: 'int'},
  		{name: 'TitoloProfilo', type: 'string'},
  		{name: 'CodProfilo', type: 'string'},
  		{name: 'AbbrProfilo', type: 'string'},
  		{name: 'DataIni', type:'date'},
  		{name: 'DataFin', type:'date'}]);

/*****************************
 * CLASSE DCS DETTAGLIO PROFILO
 *****************************/
DCS.DettaglioProfilo = Ext.extend(Ext.Panel, {
	idP: 0,
	listStore: null,
	rowIndex: -1,
	nome:'',
	listFunc:[],
	listFuncOriginali:[],
	idMGF:'',
	counter:0,
	namePanel:'',
	initComponent: function() {

		//Controllo iniziale per allineamento idUtente in caso di nuovo utente 
		if(this.idP == ''){
			this.idP = 0;
		}
		var idObj=this.getId();
		var oldChoose=0;
		var nomeMG = this.namePanel;
		var mainGridStore=this.listStore;
		/**-------------------------
		 * STORES
		 *------------------------ */
		var dsProf = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordProf)
		});
				
		var ckStoreFunc = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProfiliFunzioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readFuncCk', who:'funzioni', which:this.namePanel},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordPFunc
			})
		});
		
		var ckStoreSubFunc = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneProfiliFunzioni.php',
				method: 'POST'
			}),   
			baseParams:{task: 'readFuncCk', who:'subfunzioni',idMGF:this.idMGF, which:this.namePanel},
			reader:  new Ext.data.JsonReader({
				root: 'results',
				totalProperty: 'total',
				fields: DCS.recordPSFunc
			})
		});
				
		/**------------------------------------
		 * CheckGroup e array di configurazione
		 *----------------------------------- */
		var SFUNCArray = [];  // array dei gruppi di sottofunzioni
		this.SFUNCArrayArray = [];  // array di array di configurazione dei gruppi di sottofunzioni
		this.SGROUPArrayArray = [];  // array di array di configurazione dei macrogruppi dell'utente
		this.SGROUPTOTArrayArray = [];  // array di array di configurazione di tutti i macrogruppi
		var lastCheck=0;
		
		//GRUPPI DI FUNZIONI
		//var monoide=false;
		var idPin = this.idP;
		var flagInterruzioneChange = false;
		var checkboxconfigsFUNC = []; //array of about to be checkboxes.   
		var CheckFuncGroup = new Ext.form.CheckboxGroup({
		    xtype: 'checkboxgroup',
		    fieldLabel: 'Funzioni',
		    itemCls: 'x-check-group-alt',
		    columns: 1,
		    id: this.namePanel+'_ChkGrp',
		    items: [checkboxconfigsFUNC],
		    listeners:{
				change:function(CheckFuncGroup,arr){
//					console.log("changeG");
					var nomeP = CheckFuncGroup.ownerCt.ownerCt.ownerCt.ownerCt.namePanel;
					Ext.getCmp(nomeP+'_bSalva').disable();
					if(!flagInterruzioneChange)
					{
						flagInterruzioneChange=true;
						var val = new Array();
						var memory='', newVal='';
						//console.log(">>>change");
						//vecchio valore nell'array di configurazione
						for(j=0;j<checkboxconfigsFUNC.length;j++)
		            	{
							//console.log("<<Array conf j-"+checkboxconfigsPROC[j].boxLabel);
							if(checkboxconfigsFUNC[j].checked)
							{
								memory=checkboxconfigsFUNC[j].name;
								//console.log("memory "+memory+" elemento "+checkboxconfigsPROC[j].boxLabel);
							}
		            	}
						
						//inizio gestione nuovo valore
						val=CheckFuncGroup.getValue();
						var TotSel = val.length;
						var indexSel = 0;
						var selezione = 0;
						/*console.log("tot "+TotSel);
						for(k=0;k<val.length;k++)
						{
							console.log("val iesimo: "+val[k].boxLabel);
						}*/
						if(TotSel==0){
							selezione=lastCheck;
							CheckFuncGroup.setValue(checkboxconfigsFUNC[selezione].name,true);
							checkboxconfigsFUNC[selezione].checked = true;
							CheckFuncGroup.ownerCt.ownerCt.ownerCt.ownerCt.idMGF=checkboxconfigsFUNC[selezione].name;
						}else{
							for(k=0;k<checkboxconfigsFUNC.length;k++)
			            	{
								//console.log(">>>>indexsel"+indexSel);
								if(indexSel<TotSel)
								{
									//console.log("k - "+k+" | nome: "+checkboxconfigsPROC[k].boxLabel+" | checked="+checkboxconfigsPROC[k].checked);
									if(val[indexSel].name==checkboxconfigsFUNC[k].name)
									{
										if(checkboxconfigsFUNC[k].name == memory)
										{
											//vecchio
											//console.log("val old: "+val[indexSel].name);
											checkboxconfigsFUNC[k].checked = false;
											CheckFuncGroup.setValue(val[indexSel].name, false);
											indexSel++;
											//console.log("memory: "+memory);
										}else{
											//console.log("val new: "+val[indexSel].name);
											//console.log("checked: "+checkboxconfigsPROC[k].checked+" nome: "+checkboxconfigsPROC[k].boxLabel);
											//console.log("memory: "+memory);
											CheckFuncGroup.setValue(val[indexSel].name,true);
											checkboxconfigsFUNC[k].checked = true;
											CheckFuncGroup.ownerCt.ownerCt.ownerCt.ownerCt.idMGF=checkboxconfigsFUNC[k].name;
											selezione=k;
											lastCheck=k;
											indexSel++;
										}
									}
								}
							}
						}
						flagInterruzioneChange=false;
						
						Ext.getCmp(nomeP+'_fsSCGrup').getComponent(nomeP+'_ChkSFgrp'+(oldChoose)).setVisible(false);

						oldChoose=selezione;

						Ext.getCmp(nomeP+'_fsSCGrup').getComponent(nomeP+'_ChkSFgrp'+(selezione)).setVisible(true);
						Ext.getCmp(nomeP+'_fsSCGrup').doLayout();
						Ext.getCmp(nomeP+'_bSalva').enable();
					}
				}
			}
		});
			
		/**------------------------------------
		 * BOTTONI
		 *----------------------------------- */
		//Bottone di salvataggio
		//var ListArr = Ext.getCmp(this.idF).listFunc;
		var save = new Ext.Button({
			store:dsProf,
			id:this.namePanel+'_bSalva',
			text: 'Salva',
			handler: function(b,event) {
				//console.log(this.listFunc.length);
				/*for(var j=0;j<this.listFunc.length;j++){
					console.log("elem "+this.listFunc[j]);
				}*/
				var vect = '';
				var arrComFunc=null;
				for(y=0; y<this.ownerCt.items.items.length;y++)
				{
					arrComFunc = this.ownerCt.items.items[y].SFUNCArrayArray;
					for(j=0;j<arrComFunc.length;j++)
		        	{
						for(var o=0;o<arrComFunc[j].length;o++){
							if(arrComFunc[j].length>0){//per i monoidi
								if(arrComFunc[j][o].checked){
									vect = vect + '|' + arrComFunc[j][o].name;
									//console.log("in Vect->"+SFUNCArrayArray[j][o].name);
								}
							}
						}
		        	}
				}
				//console.log("vect "+vect);
				var vectGr = '';
				var arrComGroup=null;
				for(y=0; y<this.ownerCt.items.items.length;y++)
				{
					arrComGroup = this.ownerCt.items.items[y].SGROUPArrayArray;
					for(j=0;j<arrComGroup.length;j++)
		        	{
						vectGr = vectGr + '|' + arrComGroup[j];
		        	}
				}
				//console.log("vectGr "+vectGr);
				var vectGrTOT = '';
				var arrComGroupTot=null;
				for(y=0; y<this.ownerCt.items.items.length;y++)
				{
					arrComGroupTot = this.ownerCt.items.items[y].SGROUPTOTArrayArray;
					for(var s=0;s<arrComGroupTot.length;s++){
						vectGrTOT = vectGrTOT + '|' + arrComGroupTot[s];
					}
				}
//				console.log("vectGrTOT "+vectGrTOT);
				//if (formPAz.getForm().isDirty()) {	// qualche campo modificato
					formPAz.getForm().submit({
						url: 'server/gestioneProfiliFunzioni.php',
				        method: 'POST',
				        params: {task:'savePro',idP: this.idP,vect:vect,vectGr:vectGr,vectGrTOT:vectGrTOT},
				        success: function(frm, action) {
				        	if(action.result.success){
				        		Ext.MessageBox.alert('Esito', "Profilo salvato");
				        	}else{
				        		Ext.MessageBox.alert('Fallito', "Impossibile salvare il profilo: "+action.result.error);
				        	}
				        	if(win.getComponent(0).idP==0){
				        		win.close();
				        	}
						},
						failure: function(frm, action){
							if(action.result==undefined)
							{
								Ext.Msg.alert('Errore', "Non sono stati scelti tutti i valori minimi necessari alla definizione del profilo.");
							}else{
								Ext.Msg.alert('Errore', action.result.error);
							}
							//.MessageBox.alert('Esito', "Utente non salvato");
						},
						waitMsg: 'Salvataggio in corso...'
					});
				/*}else{
					console.log("no change");
				}*/
			},
			scope: this
		});
		
		/**------------------------------------
		 * FORMS
		 *----------------------------------- */
		//Form su cui montare gli elementi
		var formPAz = new Ext.form.FormPanel({
//			title:this.namePanel,		//il titolo è usato per testare il tab
			id:this.namePanel+'_formPanel',
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordProf
			}),
			items: [{
					xtype:'container', layout:'column',
					items: [{//colonna sinistra
							xtype:'container',columnWidth:.30,
							items:[{//oggetto primo
												xtype:'container', layout:'column',
												items:[{
													xtype:'panel', layout:'form', labelWidth:60,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{fieldLabel:'Nome', name:'TitoloProfilo', id:this.namePanel+'_TitoloProfilo', style:'text-align:left',
														listeners:{
															change:function(fld,nv,ov){
																for(y=0; y<this.ownerCt.items.items.length;y++)
																{
																	var com = this.ownerCt.items.items[y];
																	Ext.getCmp(com.namePanel+'_TitoloProfilo').setValue(nv);
																}
															},
															scope:this
														}
													}]
												}]
										},{
												xtype:'container', layout:'column',
												items:[{
														xtype:'panel', layout:'form', labelWidth:100,columnWidth:.98, defaultType:'textfield',
														defaults: {anchor:'97%', readOnly:false},
														items: [{fieldLabel:'Codice', name:'CodProfilo', id:this.namePanel+'_CodProfilo', style:'text-align:left',
															listeners:{
																change:function(fld,nv,ov){
																	for(y=0; y<this.ownerCt.items.items.length;y++)
																	{
																		var com = this.ownerCt.items.items[y];
																		Ext.getCmp(com.namePanel+'_CodProfilo').setValue(nv);
																	}
																},
																scope:this
															}
														}]
												}]										
										},{
											xtype:'container', layout:'column',
											items:[{
													xtype:'panel', layout:'form', labelWidth:100,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{fieldLabel:'Abbreviazione', name:'AbbrProfilo', id:this.namePanel+'_AbbrProfilo', style:'text-align:left',
														listeners:{
															change:function(fld,nv,ov){
																for(y=0; y<this.ownerCt.items.items.length;y++)
																{
																	var com = this.ownerCt.items.items[y];
																	Ext.getCmp(com.namePanel+'_AbbrProfilo').setValue(nv);
																}
															},
															scope:this
														}
													}]
											}]										
										},{
											xtype:'container', layout:'column',
											items:[{
													xtype:'panel', layout:'form', labelWidth:100,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{
														xtype: 'datefield',
														format: 'd/m/Y',
														width: 120,
														autoHeight:true,
														fieldLabel: 'Valido dal',
														name: 'DataIni',
														id:this.namePanel+'_valDa',
														listeners:{
															change:function(fld,nv,ov){
																for(y=0; y<this.ownerCt.items.items.length;y++)
																{
																	var com = this.ownerCt.items.items[y];
																	Ext.getCmp(com.namePanel+'_valDa').setValue(nv);
																}
															},
															scope:this
														}
													}]
											}]										
										},{
											xtype:'container', layout:'column',
											items:[{
													xtype:'panel', layout:'form', labelWidth:100,columnWidth:.98, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:false},
													items: [{
														xtype: 'datefield',
														format: 'd/m/Y',
														width: 120,
														autoHeight:true,
														fieldLabel: 'al',
														name: 'DataFin',
														id:this.namePanel+'_valAd',
														listeners:{
															change:function(fld,nv,ov){
																for(y=0; y<this.ownerCt.items.items.length;y++)
																{
																	var com = this.ownerCt.items.items[y];
																	Ext.getCmp(com.namePanel+'_valAd').setValue(nv);
																}
															},
															scope:this
														}
													}]
											}]										
										}]//fine ogg primo
					//fine oggetti colonna sinistra
					},{		//colonna destra
							xtype:'fieldset', id:this.namePanel+'_fsCGrup', autoScroll:true, height:370, title:' Gruppi di azioni', border:true, layout:'column',columnWidth:.35,bodyStyle: 'padding-left:5px;',
							items:[]																							
					},{		//colonna destra
							xtype:'fieldset', id:this.namePanel+'_fsSCGrup', autoScroll:true, height:370, title:' Azioni associate', border:true, layout:'column',columnWidth:.35,bodyStyle: 'padding-left:5px;',
							items:[]																							
					}]
			}],
			buttons:[save,{text: 'Annulla',handler: function () {win.close();}}]
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
							showPFDetail(rec.get('IdProfilo'),rec.get('TitoloProfilo'),this.listStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showPFDetail(rec.get('IdProfilo'),rec.get('TitoloProfilo'),this.listStore,newIndex);
			}
		};

		/**------------------------------------
		 * APPLY DELLA CLASSE
		 *----------------------------------- */
		Ext.apply(this, {
			activeTab:0,
			title:this.namePanel,
			//items: [datiGenerali.create(this.idUtente,this.winList)],
			items: [formPAz],
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
					'-', helpButton("DettaglioProfilo")]
	        }),
	        listeners: {
				tabchange: function(panel, tab) {
					var myIdx = panel.items.indexOf(panel.getActiveTab());
					var showButtons = ((myIdx==3) && (panel.id=='pnlDettPratica'));

					this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
					this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	            },
	            afterrender: function(tab){
	            	Ext.getCmp(this.namePanel+'_bSalva').disable();
	            	CheckFuncGroup.disable();
	            }
	        }
        });
		
		DCS.DettaglioProfilo.superclass.initComponent.call(this);
		
		/**------------------------------------
		 * LOAD DEGLI STORES
		 *----------------------------------- */
		//caricamento dei 4 store
		dsProf.load({
			params:{
				sql: 'SELECT IdProfilo,CodProfilo,TitoloProfilo,AbbrProfilo,DataIni,DataFin FROM profilo where idProfilo='+this.idP 
			},
			callback : function(r,options,success) {
				if (success && r.length>0) {
					formPAz.getForm().loadRecord(r[0]);
				}
			},
			scope: this
		});
		
		var checkDummyFUNC = [];
		var sqlQ="";
		var artificialGroup=false;
		if(this.namePanel!="Azioni"){
			sqlQ="select distinct f.IdFunzione from funzione f where f.IdFunzione=f.IdGruppo and f.MacroGruppo = '"+this.namePanel+"'"
			  + " and IFNULL(visibile,'Y')='Y'";
		}else{
			artificialGroup=true;
			sqlQ="select f.idgruppo as IdFunzione"+
					" from tipoazione ta"+
					" left join azionetipoazione atz on(ta.idtipoazione=atz.idtipoazione)"+
					" left join azione a on(a.idazione=atz.idazione)"+
					" left join funzione f on (a.idfunzione=f.idfunzione) where ta.idtipoazione not in (9,12,13) AND IFNULL(visibile,'Y')='Y'"+ 
					" group by ta.IdTipoAzione"+ 
					" order by TitoloTipoAzione asc limit 1";
		}
		//LOAD STORE DEI GRUPPI
		ckStoreFunc.load({
			callback: function(r,options,success){
				//tutti i gruppi
				Ext.Ajax.request({
					url: 'server/AjaxRequest.php', 
	        		params : {	task: 'read',
								sql: sqlQ
							},
					method: 'POST',
					reader:  new Ext.data.JsonReader(
	        					{
	        						root: 'results',//name of the property that is container for an Array of row objects
	        						id: 'IdFunzione'//the property within each row object that provides an ID for the record (optional)
	        					},
	        					[{name: 'IdFunzione'}]
	        				),
	    			success: function ( result, request ) {
						eval('var resp = ('+result.responseText+').results');
						for(var r=0;r<resp.length;r++){
							this.SGROUPTOTArrayArray.push(resp[r].IdFunzione);
						}
					},
	        		failure: function ( result, request) { 
	        			Ext.MessageBox.alert('Errore', result.responseText); 
	        		},
	        		autoLoad: true,
	        		scope:this
	        	});
				//gruppi dell'utente
				range = ckStoreFunc.getRange();
				for (i=0; i<range.length; i++)
				{
					var rec = range[i];
					checkboxconfigsFUNC.push({ 
				        //id:rec.data.IdProcedura,
						name:rec.data.IdFunzione,
				        boxLabel:rec.data.TitoloFunzione,
				        checked: false
				      });
					if(artificialGroup){
						checkDummyFUNC.push({ 
					        grp:rec.data.IdGruppo,
							name:rec.data.IdFunzione,
					        boxLabel:rec.data.TitoloFunzione,
					        checked: false
					      });
					}else{
						checkDummyFUNC.push({ 
					        //id:rec.data.IdProcedura,
							name:rec.data.IdFunzione,
					        boxLabel:rec.data.TitoloFunzione,
					        checked: false
					      });
					}
				}
				checkboxconfigsFUNC[0].checked= true;
				
				//caricamento dei check dei gruppi
				Ext.Ajax.request({
			        url: 'server/gestioneProfiliFunzioni.php',
			        method: 'POST',
			        params: {task: 'checkGrup', idP: this.idP, who: 'funzioni', which:this.namePanel},
			        success: function(obj) {
						if (obj.responseText != '') {
//							console.log("obj.responseText FUNZIONI "+obj.responseText);
							//Caricamento dei check dei macromenu
							eval("var elems = "+obj.responseText);
							for (var i=0; i<elems.length; i++) {
								for (var k=0;k<checkDummyFUNC.length;k++)
								{
									//console.log("in K for: "+k);
									if(artificialGroup){
										if(checkDummyFUNC[k].grp == elems[i])
										{
											//console.log("in equal if : "+checkDummyFUNC[k].name);
											try {//no break: tutti devono avere lo stesso gruppo
												checkDummyFUNC[k].checked= true;
											} catch (err) {}
										}
									}else{
										if(checkDummyFUNC[k].name == elems[i])
										{
											//console.log("in equal if : "+checkDummyFUNC[k].name);
											try {
												checkDummyFUNC[k].checked= true;
												break;
											} catch (err) {}
										}
									}
								}
							}
							
							//LOAD STORE DELLE FUNZIONI PER OGNI GRUPPO DELL'UTENTE
							for(var l=0;l<checkDummyFUNC.length;l++)
							{
								//console.log("checkDummyFUNC "+checkDummyFUNC[l].name);
								this.SFUNCArrayArray[l]=checkDummyFUNC[l].name;
							}
							/*console.log("lung arr "+SFUNCArrayArray.length);
							for(var l=0;l<SFUNCArrayArray.length;l++)
							{
								console.log(" ARR "+SFUNCArrayArray[l]);
							}
							console.log(" ---------------START---------------------------");*/
							for(var p=0;p<checkDummyFUNC.length;p++)
							{
								createStartConfig(idObj,p,idPin,ckStoreSubFunc,checkDummyFUNC,SFUNCArray,this.SFUNCArrayArray,this.SGROUPArrayArray,checkDummyFUNC.length,this.namePanel,artificialGroup);
							}	
							
						} else {
			                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
			            }
						Ext.getCmp(this.namePanel+'_fsCGrup').add(CheckFuncGroup);
						Ext.getCmp(this.namePanel+'_fsCGrup').doLayout();
					},
					scope: this
			    });				
			},
			scope: this
		});
	}
});

// register xtype
Ext.reg('DCS_DettaglioProfilo', DCS.DettaglioProfilo);

//--------------------------------------------------------
//Instanziazione della configurazione iniziale delle funzioni
//--------------------------------------------------------
function createStartConfig(idObj,p,idPin,ckStoreSubFunc,checkDummyFUNC,SFUNCArray,SFUNCArrayArray,SGROUPArrayArray,max,npanel,artificialGroup){
	
	var rangeExt=0;
	var idM=0;
	if(artificialGroup)
		idM=checkDummyFUNC[p].grp;
	else
		idM=checkDummyFUNC[p].name;
	
	if(checkDummyFUNC[p].checked==true){
		if(artificialGroup){
			var index=0, trovato = false;
			for(index in SGROUPArrayArray){
				if(SGROUPArrayArray[index]==idM)
				{
					trovato=true;
					break;
				}
			}
			if(!trovato)
				SGROUPArrayArray.push(checkDummyFUNC[p].grp);
		}else{
			SGROUPArrayArray.push(checkDummyFUNC[p].name);
		}
	}
	
	var flagInterruzioneChangeSub = false;
	//caricamento dei macromenu interni
	ckStoreSubFunc.load({
		params:{task: 'readFuncCk', who:'subfunzioni',idMGF:checkDummyFUNC[p].name, which:npanel},
		callback: function(r,options,success){
			var checkboxconfigsSFUNC = [];
			range = ckStoreSubFunc.getRange();
			rangeExt = range.length;
			
			checkboxconfigsSFUNC.push({ 
		        id:npanel+'_'+p,
				name:'idle',
		        boxLabel:'Seleziona/deseleziona tutto',
		        checked: false
		      });
			
			for (i=0; i<range.length; i++)
			{
				var rec = range[i];
				checkboxconfigsSFUNC.push({ 
			        id:'',
					name:rec.data.IdFunzione,
			        boxLabel:rec.data.TitoloFunzione,
			        checked: false
			      });
			}
			
			Ext.Ajax.request({
		        url: 'server/gestioneProfiliFunzioni.php',
		        method: 'POST',
		        params: {task: 'checkGrup', idP: idPin, who: 'subfunzioni', idMGF:idM, which:npanel},
		        success: function(obj) {
		        	//console.log("obj.responseText "+obj.responseText+" P->"+p);
		        	var lastEmpty=false;
		        	var Femp=true;
					if (obj.responseText != '') {
						//Caricamento dei check dei macromenu
						eval("var elems = "+obj.responseText);
						for (var i=0; i<elems.length; i++) {
							for (var k=1;k<checkboxconfigsSFUNC.length;k++)
							{
								if(checkboxconfigsSFUNC[k].name == elems[i])
								{
									//console.log("in equal if : "+checkboxconfigsSFUNC[k].boxLabel);
									try {
										checkboxconfigsSFUNC[k].checked= true;
										Femp=false;
										break;
									} catch (err) {
										console.log("err "+err);
									}
								}
							}
						}
						//console.log("p "+p);
						//console.log("idM "+idM);
						//SFUNCArrayArray.push(checkboxconfigsSFUNC);
						//debug
						/*console.log("lungck "+checkboxconfigsSFUNC.length);
						for(var y=0;y<checkboxconfigsSFUNC.length;y++)
						{
							console.log('CHKconf='+p+' -> '+checkboxconfigsSFUNC[y].name+' | '+checkboxconfigsSFUNC[y].checked);
						}
						for(var l=0;l<SFUNCArrayArray.length;l++)
						{
							console.log('Before splice='+p+' -> '+SFUNCArrayArray[l]);
						}*/
						//debug
						SFUNCArrayArray.splice(p,1,checkboxconfigsSFUNC);
						/*console.log("lungSFUNCPOST "+SFUNCArrayArray.length+" SPLICE in pos "+p);
						for(var l=0;l<SFUNCArrayArray.length;l++)
						{
							console.log('After splice='+p+' -> '+SFUNCArrayArray[l]);
						}
						
						console.log(" ------------------------------------------");*/
						if(Femp){
							lastEmpty=true;
						}
		            } else {
		                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
		            }
					
					var selectAllBefore=false;
					var CheckSubFuncGroup = new Ext.form.CheckboxGroup({
					    xtype: 'checkboxgroup',
					    fieldLabel: 'Funzioni',
					    itemCls: 'x-check-group-alt',
					    columns: 1,
					    id: npanel+'_ChkSFgrp'+p,
					    items: [checkboxconfigsSFUNC],
					    listeners:{
							change:function(CheckSubFuncGroup,arr){
								//console.log("change");
								//segna le aggiunte di check
								if(flagInterruzioneChangeSub==false)
								{
									flagInterruzioneChangeSub=true;
									flag = false;
									var varr = CheckSubFuncGroup.getValue();
									var lungA=varr.length;
									if(lungA>0)
									{
										//console.log("in1.1");
										if(varr[0].name == 'idle')
										{
											//console.log("in1.1.1");
											var sceltaSel;
											if(selectAllBefore==true)
											{//deseleziona tutto
												selectAllBefore=false;
												sceltaSel=false;
												var still=false;
												if(artificialGroup){
													//segno che si è disattivata la macrovoce del menu artificiale (p corrisponde alla posizione)
													checkDummyFUNC[p].checked=false;
													//cerca se sono stati tutti disattivati i gruppi della voce artificiale
													for (var k=0;k<checkDummyFUNC.length;k++)
													{
														if(checkDummyFUNC[k].grp==idM && checkDummyFUNC[k].checked)
														{
															still=true;
															break;
														}
													}
													if(!still){
														for(var u=0;u<SGROUPArrayArray.length;u++){
															if(SGROUPArrayArray[u]==idM){
																SGROUPArrayArray.splice(u,1);
															}
														}
													}
												}else{
													//togli il gruppo
													for(var u=0;u<SGROUPArrayArray.length;u++){
														if(SGROUPArrayArray[u]==idM){
															SGROUPArrayArray.splice(u,1);
														}
													}
												}
											}else{
												//seleziona tutto
												selectAllBefore=true;
												sceltaSel=true;	
												//controlla se il gruppo era assente prima, se si aggiungilo
												var fPresente=false;
												for(var u=0;u<SGROUPArrayArray.length;u++){
													if(SGROUPArrayArray[u]==idM){
														fPresente=true;
													}
												}
												if(!fPresente){
													SGROUPArrayArray.push(idM);
												}
											}				
											//debug
											//console.log("LungDST "+SGROUPArrayArray.length);
//											for(var u=0;u<SGROUPArrayArray.length;u++){
//												console.log(SGROUPArrayArray[u]);
//											}
											var Aaggiornamento=[];
											checkboxconfigsSFUNC[0].checked=false;
											Aaggiornamento.push(false);
											for(j=1;j<checkboxconfigsSFUNC.length;j++)
							            	{
												checkboxconfigsSFUNC[j].checked=sceltaSel;
												SFUNCArrayArray[p][j].checked=sceltaSel;
												Aaggiornamento.push(sceltaSel);
							            	}	
											Ext.getCmp(npanel+'_ChkSFgrp'+p).setValue(Aaggiornamento);
										}else{
											//console.log("in1.1.2");
											for(k=0;k<varr.length;k++)
								        	{
												for(j=0;j<checkboxconfigsSFUNC.length;j++)
								            	{
													if(varr[k].name==checkboxconfigsSFUNC[j].name)
													{
														checkboxconfigsSFUNC[j].checked = varr[k].checked;
														//console.log("est "+p+" j "+j);
														SFUNCArrayArray[p][j].checked=true;
														//console.log("sfunc name "+SFUNCArrayArray[p][j].name);
														break;
													}
								            	}
								        	}
											//console.log("lastEmpty "+lastEmpty);
											//controlla se questo è il primo check di un gruppo vuoto, se si lo aggiunge
											if(lungA==1 && lastEmpty==true)
											{
												var still=false;
												if(artificialGroup){
													//cerca se c'era già...se non c'era aggiungilo
													for (var k=0;k<SGROUPArrayArray.length;k++)
													{
														if(SGROUPArrayArray[k]==idM)
														{
															still=true;
															break;
														}
													}
													if(!still){
														SGROUPArrayArray.push(idM);
														lastEmpty=false;
													}
												}else{
													SGROUPArrayArray.push(idM);
													lastEmpty=false;
												}
											}
											//segna le detrazioni di check
											for(j=0;j<checkboxconfigsSFUNC.length;j++)
								        	{
												flag = false;
												if(checkboxconfigsSFUNC[j].checked){
													for(k=0;k<varr.length;k++)
									            	{
														if(varr[k].name==checkboxconfigsSFUNC[j].name)
														{
															flag=true;
															break;
														}
													}
													if(!flag){
														checkboxconfigsSFUNC[j].checked = false;
														//console.log("Dest "+p+" j "+j);
														SFUNCArrayArray[p][j].checked=false;
														//console.log("Dsfunc name "+SFUNCArrayArray[p][j].name);
													}
												}
								        	}
										}
									}else{
										//console.log("in2.1");
										//controlla che ora i check nn siano tutto deselezionato,
										//se lo è togli il gruppo
										lastEmpty=true;
										var still=false;
										if(artificialGroup){
											//segno che si è disattivata la macrovoce del menu artificiale (p corrisponde alla posizione)
											checkDummyFUNC[p].checked=false;
											//cerca se sono stati tutti disattivati i gruppi della voce artificiale
											for (var k=0;k<checkDummyFUNC.length;k++)
											{
												if(checkDummyFUNC[k].grp==idM && checkDummyFUNC[k].checked)
												{
													still=true;
													break;
												}
											}
											if(!still){
												for(var u=0;u<SGROUPArrayArray.length;u++){
													if(SGROUPArrayArray[u]==idM){
														SGROUPArrayArray.splice(u,1);
													}
												}
											}
										}else{
											//togli il gruppo
											for(var u=0;u<SGROUPArrayArray.length;u++){
												if(SGROUPArrayArray[u]==idM){
													SGROUPArrayArray.splice(u,1);
												}
											}
										}
										//segna le detrazioni di check
										for(j=0;j<checkboxconfigsSFUNC.length;j++)
							        	{
											flag = false;
											if(checkboxconfigsSFUNC[j].checked){
												for(k=0;k<varr.length;k++)
								            	{
													if(varr[k].name==checkboxconfigsSFUNC[j].name)
													{
														flag=true;
														break;
													}
												}
												if(!flag){
													checkboxconfigsSFUNC[j].checked = false;
													//console.log("Dest "+p+" j "+j);
													SFUNCArrayArray[p][j].checked=false;
													//console.log("Dsfunc name "+SFUNCArrayArray[p][j].name);
												}
											}
							        	}
									}
									//console.log("Lung "+SGROUPArrayArray.length);
//									for(var u=0;u<SGROUPArrayArray.length;u++){
//										console.log(SGROUPArrayArray[u]);
//									}
									flagInterruzioneChangeSub=false;
								}
							}
						}
					});
//					console.log("chkSFUNC "+checkboxconfigsSFUNC.length);

					Ext.getCmp(npanel+'_fsSCGrup').add(CheckSubFuncGroup);
					if(p==0){
//						console.log("p="+p+" | ChkSFgrp"+p);
						Ext.getCmp(npanel+'_fsSCGrup').getComponent(npanel+'_ChkSFgrp'+p).setVisible(true);
					}else{
//						console.log("p="+p+" | ChkSFgrp"+p);
						Ext.getCmp(npanel+'_fsSCGrup').getComponent(npanel+'_ChkSFgrp'+p).setVisible(false);
					}
					
					Ext.getCmp(idObj).counter++;
//					console.log("counter "+Ext.getCmp(idObj).counter);
//					console.log("max "+max);
					Ext.getCmp(npanel+'_fsSCGrup').doLayout();
					if(Ext.getCmp(idObj).counter==max){
						Ext.getCmp(npanel+'_bSalva').enable();
						Ext.getCmp(npanel+'_ChkGrp').enable();
						//debug
						/*console.log("arr "+SFUNCArrayArray.length);
						for(var a=0;a<SFUNCArrayArray.length;a++)
						{
							for(var u=0;u<SFUNCArrayArray[a].length;u++)
							{
								console.log('after splice='+p+' -> '+SFUNCArrayArray[a][u].name+' | '+SFUNCArrayArray[a][u].checked);
							}
						}*/
						//debug
					}
				},
				scope: this
		    });	
		}
	});
}

//--------------------------------------------------------
//Visualizza dettaglio profilo
//--------------------------------------------------------
function showPFDetail(idP,nome,listStore,rowIndex) {
	
	this.myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	this.myMask.show();
	
	this.myTipo = idP;
	this.nome = nome;
	this.listStore = listStore;
	this.rowIndex = rowIndex;
	var TabPanelProf = new Ext.TabPanel({
		activeTab: 0,
		id: 'TabPanelDettProf_'+idP,
		enableTabScroll: true,
		flex: 1,
		//items: [gridPhone, gridEsattoriale, gridStragiudiziale, gridLegale, gridAltre]
		items: []
	});
	Ext.Ajax.request({
		url : 'server/AjaxRequest.php' , 
		params : {
			task: 'read',
			sql: "select IFNULL(MacroGruppo,'Varie') AS MacroGruppo FROM funzione group by IFNULL(MacroGruppo,'Varie') ORDER BY MacroGruppo"},
		method: 'POST',
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'MacroGruppo'//the property within each row object that provides an ID for the record (optional)
				},
				[{name: 'MacroGruppo'}]
		),
		autoload:true,
		success: function ( result, request ) {
			eval('var arr = ('+result.responseText+').results');
			eval('var resp = ('+result.responseText+').total');
			var tab = new Array();
			var nomeG='';
			var listTab = new Array();
			for(i=0;i<resp;i++){
				//console.log("arr titolo "+arr[i] ['titoloufficio']+" | arr ida "+arr[i] ['idAgenzia']);
				nomeG = "gridN"+i; 
				//console.log("Nome: "+nomeG);
				tab[nomeG] = new DCS.DettaglioProfilo({
					idP: this.myTipo,
					nome:this.nome,
					listStore: this.listStore,
					rowIndex: this.rowIndex,
					namePanel: arr[i]['MacroGruppo']
				});
				//Ext.getCmp('TabPanelAg').add(grid[nomeG]);
				//console.log("G: "+grid[nomeG].titlePanel);
				listTab.push(tab[nomeG]);
				//console.log("l "+listG[i].titlePanel);
			}
			Ext.getCmp('TabPanelDettProf_'+this.myTipo).add(listTab);
			for(j=0;j<resp;j++){
				Ext.getCmp('TabPanelDettProf_'+this.myTipo).setActiveTab(j);	
			}
			Ext.getCmp('TabPanelDettProf_'+this.myTipo).setActiveTab(0);
			//Ext.getCmp('TabPanelAg').doLayout();
			this.myMask.hide();
		},
		failure: function ( result, request) { 
			this.myMask.hide();
			//eval('var resp = '+result.responseText);
			Ext.MessageBox.alert('Errore', result.statusText); 
		},
		scope:this
	});
	
	if(nome==''){nome='Creazione nuova azione';listStore=null;rowIndex=-1;}
	var winTitle = 'Dettaglio profilo - ' + nome +'';
	
	var nameNW = 'dettaglio'+idP;
	
	if (oldWind != '') {
		win = Ext.getCmp(oldWind);
		win.close();
	}
	oldWind = nameNW;
	win = new Ext.Window({
		width: 900,
		height: 500,
		layout: 'fit',
		id:'dettaglio'+idP,
		stateful:false,
		plain: true,
		resizable: false,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		constrain: true,
		items: [
//		        {
//			xtype: 'DCS_DettaglioProfilo',
//			idP: idP,
//			nome:nome,
//			listStore: listStore,
//			rowIndex: rowIndex
//			}
		TabPanelProf
		]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	win.show();
	win.on({
		'close' : function () {
				oldWind = '';
				this.listStore.reload();
			},
			scope:this
	});
//	myMask.hide();
	
}; // fine funzione showPFDetail