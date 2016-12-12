/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormMailModel = function(){
	var win;
	var gridForm;
	var fieldsM = [{name: 'IdModello', type: 'int', allowBlank:false},
		         		{name: 'TitoloModello', allowBlank:false},		// Codice abbreviato dello stato
		        		{name: 'TitoloTipoAllegato', allowBlank:false},
		        		{name: 'TipoModello', type: 'string'},
		        		{name: 'FileName', type: 'string'},
		        		{name: 'FlagRiservato', type: 'string'}];
	var locFields = Ext.data.Record.create([{name: 'IdTipoAllegato'},{name: 'TitoloTipoAllegato'}]);
	var locFieldsType = Ext.data.Record.create([{name: 'TipoModello'},{name: 'NomeTipoModello'}]);
	
	//Define the Grid data and create the Grid
	var create = function (idMO,automatismoID,azioneID,AutStore,AutIndex,WAzDet) 
	{
		var gridForm = new Ext.FormPanel({
			id: 'mail-form',
			frame: true,
			hideLabels:false,
			items:[{
					xtype: 'compositefield',
					fieldLabel: '',
					hideLabels:false,
					width: 346,
					items:[{
						xtype:'textfield',
						width: 180,
						fieldLabel: 'Nome modello',
						allowBlank: false,
						id: 'nomeMOD',
						name: 'NomeM'
					},{
						xtype:'textfield',
						width: 50,
						hidden:true,
						id: 'nomeFile',
						name: 'NomeFile'
					},{
						xtype: 'compositefield',
						fieldLabel: '',
						hideLabels:false,
						items:[{
								xtype: 'combo',
								fieldLabel: 'Tipo',
								name: 'cTipo',
								id:'comboTipo',
								anchor: '50%',editable: false,forceSelection: true,hidden: false,
								typeAhead: false,triggerAction: 'all',
								lazyRender: true,
								allowBlank: false,
								store: {xtype:'store',
										proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
										baseParams:{task: 'read', sql: "SELECT distinct TipoModello,(CASE TipoModello WHEN 'E' THEN 'Email' WHEN 'X' THEN 'Sottomodello' WHEN 'W' THEN 'Workflow' END) as NomeTipoModello FROM modello where TitoloModello like '%mail%'"},
										reader:  new Ext.data.JsonReader(
													{root: 'results',id: 'TipoModello'},
													locFieldsType
							            			),
										sortInfo:{field: 'TipoModello', direction: "ASC"}
								},
								displayField: 'NomeTipoModello',
								valueField: 'TipoModello'
						}]
					}]
			},{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
					xtype:'textfield',
					anchor: '80%',
					width:'80%',
					fieldLabel: 'Soggetto',
					allowBlank: false,
					id: 'soggetto',
					name: 'Subj'
				}]
		},{
				xtype:'htmleditor',
	            name: 'TTMail',
	            id: 'TMail',
	            anchor: '100%',
	            //width:'100%', 
	            allowBlank: false,
	            height: 295
		},{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
						xtype: 'combo',
						fieldLabel: 'Allegare come',
						hiddenName: 'cAllegato',
						id:'comboAllegato',
						anchor: '50%',editable: true,forceSelection: true,hidden: false,
						typeAhead: false,triggerAction: 'all',
						lazyRender: true,
						allowBlank: true,
						store: {xtype:'store',
								proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
								baseParams:{task: 'read', sql: "SELECT IdTipoAllegato,TitoloTipoAllegato FROM tipoallegato "},
								reader:  new Ext.data.JsonReader(
											{root: 'results',id: 'IdTipoAllegato'},
											locFields
					            			),
								sortInfo:{field: 'TitoloTipoAllegato', direction: "ASC"}
						},
						displayField: 'TitoloTipoAllegato',
						valueField: 'IdTipoAllegato'
				},{
						labelStyle: 'width:300;',
						xtype: 'checkbox',
						boxLabel: '<span style="color:red;"><b>Riservata</b></span>',
						name: 'FlagRiservato',
						id: 'chkris',
						checked: false
				}]
		}],
	
			buttons: [{
				text: 'Salva',
				id: 'btnSalvaMM',
				handler: function() {
					var frm = gridForm.getForm();
					var arr = frm.getFieldValues(false);
					var ck = '';
					var Url='';
					var task='';
					if(Ext.getCmp('chkris').checked){ck='Y';}else{ck='N';}
					Ext.getCmp('comboTipo').enable();
					if(automatismoID!='' || azioneID!='')
					{
						Url='server/gestioneProcedure.php';
						task='saveModAndLink';
					}else{
						Url='server/ana_modelli.php';
						task='saveMM';
					}
					if(Ext.getCmp('TMail').getValue()!='' && Ext.getCmp('TMail').getValue()!='<br>'){
						frm.submit({
							url: Url,
							method: 'POST',

							params: {task: task, model:idMO, riservato:ck, IdAut:automatismoID},
							success: function(frm, action){
								//Ext.Msg.alert('Esito', "File salvato correttamente.");
								//Ext.Msg.alert('Esito', action.result.error);
								win.close();
								if(task=='saveModAndLink'){
									Ext.getCmp(WAzDet).close();
									showAutAzioneDetail(automatismoID,AutStore,AutIndex,azioneID,true);
								}
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
		
		//in caso di modifica
		if(idMO!=''){
			//caricamento campi generici
			var sqlM="SELECT * FROM modello m left join tipoallegato ta on(m.idtipoallegato=ta.idtipoallegato) where idModello="+idMO;
			var dsMod = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/AjaxRequest.php',
					method: 'POST'
				}),   
				baseParams:{task: 'read',sql:sqlM},
				reader:  new Ext.data.JsonReader(
					{root: 'results'}, fieldsM
		        ),
		        autoLoad:true,
		        listeners: {
					load: function(store,record,option){
						Ext.getCmp('nomeMOD').setValue(record[0].get('TitoloModello'));
						Ext.getCmp('comboTipo').getStore().reload({
							callback: function(rec, opt, success){
								switch(record[0].get('TipoModello'))
								{
									case 'W':
										Ext.getCmp('comboTipo').setValue(record[0].get('TipoModello'));
										break;
									case 'X':
										Ext.getCmp('comboTipo').setValue(record[0].get('TipoModello'));
										break;
									case 'E':
										Ext.getCmp('comboTipo').setValue(record[0].get('TipoModello'));
										break;
								}
							}
						});
						Ext.getCmp('nomeFile').setValue(record[0].get('FileName'));
						Ext.getCmp('comboAllegato').setValue(record[0].get('TitoloTipoAllegato'));
						if(record[0].get('FlagRiservato')=='N'){
							Ext.getCmp('chkris').setValue(false);
						}else{
							Ext.getCmp('chkris').setValue(true);
						}
						//caricamento file .json
						Ext.Ajax.request({
			        		url : 'server/ana_modelli.php' , 
			        		params : {task: 'caricaModelloEmail',nomef:record[0].get('FileName')},
			        		method: 'POST',
			        		success: function ( result, request ) {
			        			/** il modello e' restituito con soggetto e testo separati da newline **/
			        			var pos = result.responseText.indexOf("\n");
			        			Ext.getCmp('soggetto').setValue(result.responseText.substr(0,pos));
			        			Ext.getCmp('TMail').setValue(result.responseText.substr(pos+1));
			        		},
			        		failure: function ( result, request) { 
			        			Ext.MessageBox.alert('Errore', result.responseText); 
			        		} 
			        	});
					}
				}
			});
		}
		else if(automatismoID!='' || azioneID!='') 
		{
			Ext.getCmp('btnSalvaMM').disable();
			//costruzione di modello dalla funzionalità dell'autmatismo.
			//definizione store: per la maschera della mail in creazione dagli automatismi
			var dsAutomaStore = new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/gestioneProcedure.php',
					method: 'POST'
				}),   
				baseParams:{task: 'readModPrec',idaut:automatismoID,idaz:azioneID},
				reader:  new Ext.data.JsonReader(
						{
							root: 'results',//name of the property that is container for an Array of row objects
							id: 'NomeM'//the property within each row object that provides an ID for the record (optional)
						},
						[{name: 'NomeM'},
						{name: 'cTipo'},
						{name: 'FlagRiservato'},
						{name: 'Subj'},
						{name: 'TTMail'}]),
				autoLoad:true
			});
			
			//caricamento
			dsAutomaStore.load({
				callback : function(r,options,success) {
					if (success && r.length>0) {
						gridForm.getForm().loadRecord(r[0]);
						Ext.getCmp('comboTipo').setValue(r[0].get('cTipo'));
						Ext.getCmp('comboTipo').getStore().reload({
							callback: function(rec, opt, success){
								Ext.getCmp('comboTipo').setValue(r[0].get('cTipo'));
								Ext.getCmp('comboTipo').disable();
								Ext.getCmp('btnSalvaMM').enable();
							}
						});
					}else{
						Ext.MessageBox.alert('Errore', 'Generazione automatica della form fallita.'); 
						console.log("succ "+success);
					}
				}
			});
		}
		
		return gridForm;
	};

	return {
		showDetailMailModel: function(button,e,idMO,automatismoID,azioneID,AutStore,AutIndex,WAzDet){
			if(idMO==undefined){
				idMO='';
			}
			automatismoID=automatismoID||'';
			azioneID=azioneID||'';
			AutStore=AutStore||'';
			AutIndex=AutIndex||'';
			WAzDet=WAzDet||'';
		
			gridForm = create(idMO,automatismoID,azioneID,AutStore,AutIndex,WAzDet);
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 700,
				height: 460,
				minWidth: 700,
				minHeight: 460,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Modello E-mail',
				tools: [helpTool("ModelloEmail")],
				items: [gridForm]
			});
			win.show();
			//myMask.hide();
		}
	}

}();

