// Form delle lettere di tipo Word (edit esterno a DCSys, poi caricate come file modello)
Ext.namespace('DCS');

DCS.FormLetteraModelWord = function(){
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
	var create = function (idMO,mod) 
	{
		var avviso='';
		if(idMO!=''){ // in modifica
			avviso="Per cambiare il contenuto del file, eseguire il download dal link indicato ed una volta effettuate le modifiche, ricaricare il file con l'apposito tasto."
				'Il file deve essere sempre salvato (da Word) nel formato XML di "Word 2003"';
		} else {
			avviso='il modello deve essere creato con Word e salvato (da Word) nel formato XML di "Word 2003"; dopodich&eacute; pu&ograve; essere caricato con l\'apposito tasto "Carica file"';
		}
		var lineeT=0;
		var gridForm = new Ext.FormPanel({
			id: 'Lettera-form',
			frame: true,
			fileUpload: true,
			hideLabels:false,
			items:[{
				xtype: 'compositefield',
				fieldLabel: '',
				hideLabels:false,
				width: 300,
				items:[{
					xtype:'textfield',
					width: 300,
					fieldLabel: 'Nome modello',
					allowBlank: false,
					id: 'nomeLOD',
					name: 'NomeM'
				},{
					xtype:'textfield',
					width: 50,
					hidden:true,
					id: 'tipoM',
					name: 'tipoMod'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Modello',
				hideLabels:false,
				items:[{
					xtype:'displayfield',
					width: 300,
					height: 20,
					id: 'nomeLFile',
					name: 'NomeFile'
				},{
					xtype:'fileuploadfield',
					id: 'docPath',
					name: 'docPath',
					buttonText: 'Carica file',
					buttonOnly: true,
		            listeners: {
			            'fileselected': function(){
			            	var valueTitolo=Ext.getCmp('docPath').getValue();
		                	// Ri-trasforma i caratteri URLEncoded in caratteri normali
		                	valueTitolo=unescape(String(valueTitolo).replace("/\+/g", " ")); 
			    			// Toglie il path
		                	if (valueTitolo.lastIndexOf("\\")>0) 
			    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("\\"));
		                	if (valueTitolo.lastIndexOf("/")>0) 
			    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("/"));
		                		Ext.getCmp('newFile').setValue(valueTitolo);
			            }
		   	        }
				},{
					xtype:'displayfield',
					width: 300,
					height: 20,
					id: 'newFile'
				}]
			},{
				xtype: 'compositefield',
				fieldLabel: 'Condizione',
				hideLabels:false,
				items:[{
					xtype:'textarea',
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
						hiddenName: 'IdTipoAllegato',
						id:'comboAllegatoL',
						anchor: '50%',editable: false,forceSelection: true,hidden: false,
						typeAhead: false,triggerAction: 'all',
						lazyRender: false,
						lazyInit: false,
						allowBlank: true,
						store: {xtype:'store',
								autoLoad: true,
								proxy: new Ext.data.HttpProxy({url: 'server/AjaxRequest.php',method: 'POST'}),   
								baseParams:{task: 'read', sql: "SELECT IdTipoAllegato,TitoloTipoAllegato FROM tipoallegato "},
								reader:  new Ext.data.JsonReader(
											{root: 'results',id: 'IdTipoAllegato'},
											locFields
					            			),
								sortInfo:{field: 'TitoloTipoAllegato', direction: "ASC"}
						},
						displayField: 'TitoloTipoAllegato',
						valueField: 'IdTipoAllegato',
						width: 300
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
					anchor: '98%',
					hidden : true
				}]
			},{
				xtype: 'displayfield',
				html: avviso,
				fieldLabel: 'Avviso',
				labelStyle: 'font-weight:bold',
				anchor: "90%",
				id: 'LAvviso'
			}],
	
			buttons: [{
				text: 'Salva',
				handler: function() {
					var frm = gridForm.getForm();
					if (frm.isValid()) {
						frm.submit({
							url: 'server/gestioneModelli.php',
							method: 'POST',
							
							params: {task: 'saveModelloWord', IdModello:idMO},
							success: function(frm, action){
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
						Ext.getCmp('nomeLFile').setValue('<a href="'+CONTEXT.TemplateUrl+encodeURI(record[0].get('FileName'))+'">'+record[0].get('FileName')+'</a>');
						Ext.getCmp('nomeLFile').setValue('<a href="'+CONTEXT.LinkUrl+'server/apriModelloWord.php?IdModello='+record[0].get('IdModello')+'">'+record[0].get('FileName')+'</a>');
						Ext.getCmp('comboAllegatoL').setValue(record[0].get('IdTipoAllegato'));
						Ext.getCmp('condLettera').setValue(record[0].get('condizione'));
						if(record[0].get('FlagRiservato')=='N'){
							Ext.getCmp('chkrisL').setValue(false);
						}else{
							Ext.getCmp('chkrisL').setValue(true);
						}
					}
				}
			});
		}else{
			Ext.getCmp('tipoM').setValue(mod);
		}
		
		return gridForm;
	};

	return {
		showDetailLetteraModelWord: function(button,e,idMO){
			if(idMO==undefined){
				idMO='';
			}
			gridForm = create(idMO,'H');
			gridForm.addButton('Chiudi', function() {win.close();}, this);
			
			win = new Ext.Window({
				modal: true,
				width: 800,
				height: 300,
				minWidth: 800,
				minHeight: 300,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: 'Modello Lettera in formato Word XML',
				tools: [helpTool("ModelloLettera")],
				items: [gridForm]
			});
			win.show();
		}
	}

}();

