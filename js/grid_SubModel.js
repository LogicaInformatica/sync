/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormSubModel = function(){
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
		var lineeT=0;
		var gridForm = new Ext.FormPanel({
			id: 'Sub-form',
			frame: true,
			hideLabels:false,
			items:[{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
					xtype:'textfield',
					width: 180,
					fieldLabel: 'Nome modello',
					allowBlank: false,
					id: 'nomeSMOD',
					name: 'NomeM'
				},{
					xtype:'textfield',
					width: 50,
					hidden:true,
					id: 'nomeSMFile',
					name: 'NomeFile'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Modello',
				hideLabels:false,
				items:[{
					xtype:'textarea',
					anchor: '100%',
					width:'100%',
					height: 415,
					allowBlank: false,
					id: 'Submodello',
					name: 'SubModello',
					//maxLength:160,
					//maxLengthText: "Testo troppo lungo",
					enableKeyEvents: true,
					listeners:{
						keydown:function(field,e){
							//conta dei caratteri
							var i = field.getValue();
							Ext.getCmp('caratteriL').setText("N. caratteri "+(i.length+1));
						}
					}
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
						xtype: 'combo',
						//fieldLabel: 'Allegare come',
						hiddenName: 'cAllegato',
						id:'comboAllegatoL',
						anchor: '50%',editable: true,forceSelection: true,hidden: false,
						typeAhead: false,triggerAction: 'all',
						lazyRender: true,
						allowBlank: true,
						hidden: true,
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
						id: 'chkrisL',
						//hidden: true,
						checked: false
				},{
					xtype:'label',
					//text: 'Max 74 cr. per riga',
					text: '0',
					id: 'caratteriL',
					style:'text-align:right',
					hidden: true,
					width:400,
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
					if(Ext.getCmp('chkrisL').checked){ck='Y';}else{ck='N';}
					if(Ext.getCmp('Submodello').getValue()!='' && Ext.getCmp('Submodello').getValue()!='<br>'){
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
						Ext.getCmp('nomeSMOD').setValue(record[0].get('TitoloModello'));
						Ext.getCmp('nomeSMFile').setValue(record[0].get('FileName'));
						Ext.getCmp('comboAllegatoL').setValue(record[0].get('TitoloTipoAllegato'));
						if(record[0].get('FlagRiservato')=='N'){
							Ext.getCmp('chkrisL').setValue(false);
						}else{
							Ext.getCmp('chkrisL').setValue(true);
						}
						//caricamento file .json
						Ext.Ajax.request({
			        		url : 'server/ana_modelli.php' , 
			        		params : {task: 'caricaJson',nomef:record[0].get('FileName')},
			        		method: 'POST',
			        		success: function ( result, request ) {
			        			if(result.responseText!= ''){
				        			Ext.getCmp('Submodello').setValue(result.responseText);
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
		showDetailSubModel: function(button,e,idMO){
			if(idMO==undefined){
				idMO='';
			}
			gridForm = create(idMO);
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 700,
				height: 530,
				minWidth: 700,
				minHeight: 530,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Sotto Modello',
				tools: [helpTool("SottoModello")],
				items: [gridForm]
			});
			win.show();
			//myMask.hide();
		}
	}

}();

