/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormSMSModel = function(){
	var win;
	var gridForm;
	var fieldsM = [{name: 'IdModello', type: 'int', allowBlank:false},
		         		{name: 'TitoloModello', allowBlank:false},		// Codice abbreviato dello stato
		        		{name: 'TitoloTipoAllegato', allowBlank:false},
		        		{name: 'TipoModello', type: 'string'},
		        		{name: 'FileName', type: 'string'},
		        		{name: 'FlagRiservato', type: 'string'}];
	var locFields = Ext.data.Record.create([{name: 'IdTipoAllegato'},{name: 'TitoloTipoAllegato'}]);
	//Define the Grid data and create the Grid
	var create = function (idMO) 
	{
		var gridForm = new Ext.FormPanel({
			id: 'SMS-form',
			frame: true,
			hideLabels:false,
			items:[{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				width: 180,
				items:[{
					xtype:'textfield',
					width: 180,
					fieldLabel: 'Nome modello',
					allowBlank: false,
					id: 'nomeSOD',
					name: 'NomeM'
				},{
					xtype:'textfield',
					width: 50,
					hidden:true,
					id: 'nomeSFile',
					name: 'NomeFile'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Messaggio',
				hideLabels:false,
				items:[{
					xtype:'textarea',
					anchor: '100%',
					width:'100%',
					height: 130,
					allowBlank: false,
					id: 'Tsms',
					name: 'sms',
					//maxLength:160,
					//maxLengthText: "Testo troppo lungo",
					enableKeyEvents: true,
					listeners:{
						keydown:function(field,e){
							var i = field.getValue();
							Ext.getCmp('caratteri').setText("N. caratteri "+(i.length+1));
							/*if(i.length<=160){
								Ext.getCmp('caratteri').setText(i.length);
							}else{Ext.getCmp('caratteri').setText("Massimo n. caratteri raggiunto.");}*/
						}
					}
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
						xtype: 'combo',
						fieldLabel: 'Allegare come',
						hiddenName: 'cAllegato',
						id:'comboAllegatoS',
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
						id: 'chkrisS',
						checked: false
				},{
					xtype:'label',
					text: 'Max 160 cr.',
					id: 'caratteri',
					style:'text-align:right',
					width:215,
					anchor: '98%'
				}]
			}],
	
			buttons: [{
				text: 'Salva',
				id: 'btnSalvaMM',
				handler: function() {
					var frm = gridForm.getForm();
					var arr = frm.getFieldValues(false);
					var ck = '';
					if(Ext.getCmp('chkrisS').checked){ck='Y';}else{ck='N';}
					if(Ext.getCmp('Tsms').getValue()!='' && Ext.getCmp('Tsms').getValue()!='<br>'){
						frm.submit({
							url: 'server/ana_modelli.php',
							method: 'POST',
							
							params: {task: 'saveMM', model:idMO, riservato:ck},
							success: function(frm, action){
								//Ext.Msg.alert('Esito', "File salvato correttamente.");
								Ext.Msg.alert('Esito', action.result.error);
								win.close();
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
						Ext.getCmp('nomeSOD').setValue(record[0].get('TitoloModello'));
						Ext.getCmp('nomeSFile').setValue(record[0].get('FileName'));
						Ext.getCmp('comboAllegatoS').setValue(record[0].get('TitoloTipoAllegato'));
						if(record[0].get('FlagRiservato')=='N'){
							Ext.getCmp('chkrisS').setValue(false);
						}else{
							Ext.getCmp('chkrisS').setValue(true);
						}
						//caricamento file .json
						Ext.Ajax.request({
			        		url : 'server/ana_modelli.php' , 
			        		params : {task: 'caricaJson',nomef:record[0].get('FileName')},
			        		method: 'POST',
			        		success: function ( result, request ) {
			        			if(result.responseText!= ''){
				        			var jsonData = Ext.util.JSON.decode(result.responseText);
				        			Ext.getCmp('Tsms').setValue(jsonData.testoSMS);
			        			}
			        		},
			        		failure: function ( result, request) { 
			        			Ext.MessageBox.alert('Errore', result.responseText); 
			        		} 
			        	});
					}
				}
			});
		}
		
		return gridForm;
	};

	return {
		showDetailSMSModel: function(button,e,idMO){
			if(idMO==undefined){
				idMO='';
			}
			gridForm = create(idMO);
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 600,
				height: 265,
				minWidth: 600,
				minHeight: 265,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Modello SMS',
				tools: [helpTool("ModelloSMS")],
				items: [gridForm]
			});
			win.show();
			//myMask.hide();
		}
	}

}();

