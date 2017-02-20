/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormLetteraModel = function(){
	var win;
	var gridForm;
	var fieldsM = [{name: 'IdModello', type: 'int', allowBlank:false},
		         		{name: 'TitoloModello', allowBlank:false},		// Codice abbreviato dello stato
		        		{name: 'TitoloTipoAllegato', allowBlank:false},
		        		{name: 'TipoModello', type: 'string'},
		        		{name: 'FileName', type: 'string'},
		        		{name: 'condizione', type: 'string'},
		        		{name: 'FlagRiservato', type: 'string'}];
	var locFields = Ext.data.Record.create([{name: 'IdTipoAllegato'},{name: 'TitoloTipoAllegato'}]);
	//Define the Grid data and create the Grid
	var create = function (idMO,isClient,mod) 
	{
		var avviso='';
		var hiddenAvv=true;
		if(isClient){
			if(idMO!=''){
				avviso='<span style="color:red;"><b>Attenzione, per modificare il contenuto del file contattare l\'amministratore di sistema.</b></span>';
			}else{
				avviso='<span style="color:red;"><b>Attenzione, verr&agrave creato un file vuoto.<p>Per valorizzarlo contattare l\'amministratore di sistema.</b></span>';
			}
			hiddenAvv=false;
		}
		var lineeT=0;
		var gridForm = new Ext.FormPanel({
			id: 'Lettera-form',
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
					id: 'nomeLOD',
					name: 'NomeM'
				},{
					xtype:'textfield',
					width: 50,
					hidden:true,
					id: 'nomeLFile',
					name: 'NomeFile'
				},{
					xtype:'textfield',
					width: 50,
					hidden:true,
					id: 'tipoM',
					name: 'tipoMod'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Messaggio',
				hideLabels:false,
				items:[{
					xtype:'textarea',
					anchor: '100%',
					width:'100%',
					height: 415,
					disabled:isClient,
					allowBlank: false,
					id: 'Lettera',
					name: 'lettera',
					//maxLength:160,
					//maxLengthText: "Testo troppo lungo",
					enableKeyEvents: true,
					listeners:{
						keydown:function(field,e){
							/*//il keydown avviene prima del immissione dell'ultimo carattere.
							//Quindi non troverai l'ultima lettera scritta nello streaming
							//che invece è nell'evento: e
							var i = field.getValue();
							//var l = parseInt((i.length+1)/75);//numero linea = tot/dim linea
							var c = ((i.length+1)-(75*lineeT)); //totale - ( dim linea * numero linee)
							console.log("lettera i["+(i.length-1)+"]="+i[i.length-1]);
							console.log("evento "+e.keyCode);
							console.log("lung "+(i.length+1));
							//se cancella controlla se le linee devono diminuire
							if(e.keyCode==8){
								console.log("tornato indietro");
								console.log("lung canc "+(i.length-1));//togli -1indietro -1carattereCancellato
								console.log("linea nuova "+parseInt((i.length-1)/75)+" linaT "+lineeT);
								if(parseInt((i.length-1)/75) < lineeT){
									lineeT--;//scala
								}
								c = ((i.length-1)-(75*lineeT));//ricalcola
							}
							//se va a capo aumenta le linee
							if(e.keyCode==13){
								console.log("accapo");
								lineeT++;
							}
							Ext.getCmp('caratteriL').setText("riga "+(lineeT+1)+", carattere "+c);
							if(c>74){
								field.setValue(i+"\n");
								lineeT++;
							}*/
					
							//conta dei caratteri
							var i = field.getValue();
							Ext.getCmp('caratteriL').setText("N. caratteri "+(i.length+1));
						}
					}
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Condizione',
				hidden:!isClient,
				hideLabels:false,
				items:[{
					xtype:'textfield',
//					width: 180,
					width:'90%',
					allowBlank: true,
					id: 'condLettera',
					name: 'condizioneH'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				items:[{
						xtype: 'combo',
						fieldLabel: 'Allegare come',
						hiddenName: 'cAllegato',
						id:'comboAllegatoL',
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
						id: 'chkrisL',
						checked: false
				},{
					xtype:'label',
					//text: 'Max 74 cr. per riga',
					text: '0',
					id: 'caratteriL',
					style:'text-align:right',
					width:400,
					anchor: '98%'
				}]
			},{
				xtype: 'label',
				html: avviso,
				id: 'LAvviso',
				hidden:hiddenAvv
			}],
	
			buttons: [{
				text: 'Salva',
				id: 'btnSalvaMM',
				handler: function() {
					var frm = gridForm.getForm();
					var arr = frm.getFieldValues(false);
					var ck = '';
					var isGood=false;
					if(Ext.getCmp('chkrisL').checked){ck='Y';}else{ck='N';}
					if(Ext.getCmp('tipoM').getValue()=='L')
					{
						if(Ext.getCmp('Lettera').getValue()!='' && Ext.getCmp('Lettera').getValue()!='<br>'){
							isGood=true;
						}else{console.log("else");}	
					}else{
						isGood=true;
					}
					
					if(isGood){
						frm.submit({
							url: 'server/ana_modelli.php',
							method: 'POST',
							
							params: {task: 'saveMM', model:idMO, riservato:ck, client:isClient},
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
					}
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
						Ext.getCmp('tipoM').setValue(record[0].get('TipoModello'));
						Ext.getCmp('nomeLOD').setValue(record[0].get('TitoloModello'));
						Ext.getCmp('nomeLFile').setValue(record[0].get('FileName'));
						Ext.getCmp('comboAllegatoL').setValue(record[0].get('TitoloTipoAllegato'));
						Ext.getCmp('condLettera').setValue(record[0].get('condizione'));
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
				        			Ext.getCmp('Lettera').setValue(result.responseText);
			        			}
			        		},
			        		failure: function ( result, request) { 
			        			Ext.MessageBox.alert('Errore', result.responseText); 
			        		} 
			        	});
					}
				}
			});
		}else{
			Ext.getCmp('tipoM').setValue(mod);
		}
		
		return gridForm;
	};

	return {
		showDetailLetteraModel: function(button,e,idMO,isClient){
			var h = 550;
			if(idMO==undefined){
				idMO='';
			}
			if(isClient==undefined)
				isClient=false;
			if(isClient)
				h=600;
			gridForm = create(idMO,isClient,'L');
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 700,
				height: h,
				minWidth: 700,
				minHeight: h,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Modello Lettera',
				tools: [helpTool("ModelloLettera")],
				items: [gridForm]
			});
			win.show();
			//myMask.hide();
		},
		showDetailLetteraModelOnline: function(button,e,idMO,isClient){
			var h = 550;
			if(idMO==undefined){
				idMO='';
			}
			if(isClient==undefined)
				isClient=true;
			if(isClient)
				h=600;
			gridForm = create(idMO,isClient,'H');
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 700,
				height: h,
				minWidth: 700,
				minHeight: h,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Modello Lettera',
				tools: [helpTool("ModelloLettera")],
				items: [gridForm]
			});
			win.show();
			//myMask.hide();
		}
	}

}();

