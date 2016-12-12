// Crea namespace DCS
Ext.namespace('DCS');


var win;


DCS.recordDistinta = Ext.data.Record.create([
										{name: 'IdDistinta',   	type: 'int'},
										{name: 'IdCompagnia',   type: 'int'},
										{name: 'DataPagamento', type: 'date'},
										{name: 'Importo',   	type: 'float'},
										{name: 'UrlRicevuta',	type: 'string'},
										{name: 'IBAN',    		type: 'string'},
										{name: 'LastUser',		type: 'string'},
										{name: 'LastUpd', 		type: 'date'},
										{name: 'CRO',     		type: 'string'},
										{name: 'RepartoInc',     		type: 'string'}]
										);

DCS.DettaglioDistinta = Ext.extend(Ext.Panel, {
	
	idDistint:'',
	UrlSi:'',
	UrlNo:'',
	Url:'',
	idGrid:'',
	
	initComponent: function() {
		function getFloatValue(name) {
			var v = Ext.getCmp(name).getValue();
			return parseFloat(v.replace('.','').replace(',','.'));
		}
		
		function setFloatValue(name,value) {
			Ext.getCmp(name).setValue(Ext.util.Format.number(value, '0.000,00/i'));
		}

		var dsDistinta = new Ext.data.Store({
		    proxy: new Ext.data.HttpProxy({
		    			url: 'server/gestioneIncassi.php',
		    			method: 'POST'}),   
			baseParams:{task: 'readDist',idDistint:this.idDistint},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordDistinta)
	    });
		
		var formDistinta = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			//autoWidth:true,
			frame: true, 
			fileUpload: true, 
			title: '',
		    //width: 450,
		    height: 270,
		    labelWidth:100,
		    trackResetOnLoad: true,
			reader: new Ext.data.ArrayReader({
				root: 'results',
				fields: DCS.recordDistinta}),
			items: [{	
						xtype: 'textfield',
						style: 'nowrap',
						width: 40,
						style: 'text-align:right',
						fieldLabel: 'IndDist',
						hidden:true,
						name: 'IdDistinta'
					},{	
						xtype: 'textfield',
						style: 'nowrap',
						width: 40,
						style: 'text-align:right',
						fieldLabel: 'IndComp',
						hidden:true,
						name: 'IdCompagnia'
					},{	
						xtype: 'textfield',
						style: 'nowrap',
						width: 160,
						style: 'text-align:right',
						fieldLabel: 'Compagnia',
						hidden:false,
						readOnly: true,
						name: 'RepartoInc'
					},{
						xtype:'fieldset',
					    title: '',
					    collapsible: false, 
					    anchor: '97%',
						items:[
							{
								xtype: 'compositefield',
					            fieldLabel: 'Importo',
					            items: [
					                {
					                	xtype: 'textfield', 
					                	width: 150, 
					                	style: 'text-align:right',
					                	readOnly: true, 
					                	disabled: false,
					                	name: 'Importo', 
					                	id: 'Importo'
					                }
					            ]
							},{
			        			xtype: 'textfield', 
			                	width: 150, 
			                	style: 'text-align:right',
			            		fieldLabel: 'IBAN',
			            		name: 'IBAN'
			        		},{
			        			xtype: 'textfield', 
			                	width: 150, 
			                	style: 'text-align:right',
			            		fieldLabel: 'CRO',
			            		name: 'CRO'
			        		}
					      ]
		        	},{	
						xtype: 'datefield',
						format: 'd/m/Y',
						width: 120,
						autoHeight:true,
						fieldLabel: 'Data Documento',
						name: 'DataPagamento',
						id: 'DataP'
					},{
					    xtype: 'fileuploadfield',
		//			    anchor: '97%',
					    width: 300,
					    fieldLabel: 'Allega ricevuta',
					    name: 'docPath',
					    id: 'docPath',
					    buttonText: 'Cerca',
					    editable:true,
					    hidden: this.UrlNo
					},{
			   			xtype: 'compositefield',
			            fieldLabel: 'Ricevuta',
			            id:"cfSiR",
			            anchor: '97%',
			            hidden: this.UrlSi,
			            items: [
								{
									xtype: 'textfield', 
									anchor: '60%',
									hidden: true,
									name:'Ricevuta',
									id: 'Ricevuta',
									disabled:false,
									readOnly: true,
									style: 'text-align:left',
									value:this.Url
								},
			                    {
				                	xtype:'button',
			                    	tooltip:"Apri ricevuta",
									text:"Apri",
									name: 'btnApriR', 
								    id: 'btnApriR',
								    anchor: '30%',
								    iconCls:'grid-edit',
									hidden: this.UrlSi,
								    handler: function() {
												var url = this.Url;
												var wfeatures = 'menubar=yes,resizable=yes,scrollbars=yes,status=yes,location=yes';
												window.open(url,"Ricevuta",wfeatures);
												
						        	},
									scope: this
			                    },{
				                	xtype: 'displayfield', 
				                	width: 1
				                },{
			                    	xtype:'button',
									tooltip:"Elimina ricevuta",
									text:"Elimina",
									anchor: '20%',
									name: 'btnEliminaR', 
								    id: 'btnEliminaR',
								    iconCls:'del-row',
									hidden: this.UrlSi,
								    handler: function() {
		
					                	var b = Ext.getCmp('cfSiR');
							 			b.setVisible(false);
							 			
							 			var a = Ext.getCmp('docPath');
							 			a.setVisible(true);
							 			
							 			Ext.getCmp('Ricevuta').setValue("");
						 			
						 			},
									scope: this
			                	
			                    }
			                  ]
					 }
		        ]
					, // fine array items del form
		    buttons: 
		    	[
		    	 {
				  text: 'Salva',
				  handler: function() {
		    		 	var IdMomG=this.idGrid;
		    		 	if (formDistinta.getForm().isValid()){
							this.disable();
		    		 		formDistinta.getForm().submit({
								url: 'server/gestioneIncassi.php', method: 'POST',
								params: {task:"updateDist",idDistint:this.idDistint,titolo:"DistintaN"+this.idDistint,bEliminaVis:Ext.getCmp('cfSiR').isVisible()},
								success: function (frm,action) {
									var grid = Ext.getCmp(IdMomG).getStore().reload();
									saveSuccess(win,frm,action);},
								failure: saveFailure
							});
						}
					},
					scope:this
				 }, 
				{text: 'Annulla',handler: function () {quitForm(formDistinta,win);} 
				}
			   ]  // fine array buttons
			   
		});

		Ext.apply(this, {
			items: [formDistinta]
		});
		
		DCS.DettaglioDistinta.superclass.initComponent.call(this);
		//caricamento dello store
		dsDistinta.load({
			params:{task:"readDist",idDistint:this.idDistint},
			callback : function(rows,options,success) {
				formDistinta.getForm().loadRecord(rows[0]);
			},
			scope:this
		});
		
	}	// fine initcomponent
});

// register xtype
Ext.reg('DCS_DettaglioDistinta', DCS.DettaglioDistinta);
		
//--------------------------------------------------------
//Visualizza dettaglio distinta
//--------------------------------------------------------
DCS.showDistinctDetail = function(){

	return {
		create: function(idDistint,UrlRicevuta,idGrid){
		//se mira agli attachments ma non v'è nulla allora bisogna uplodare
		if(UrlRicevuta=="attachments/")
		{
		 var UrlSi=true;
		 var UrlNo=false;
		}
		else
		{
			var UrlSi=false;
			var UrlNo=true;
		}

		win = new Ext.Window({
			layout: 'fit',
			width: 460,
		    height: 300,
		    minWidth: 460,
			minHeight: 300,
		    //labelWidth:100,
			//plain: true,
			//bodyStyle: 'padding:5px;',
			modal: true,
			title: 'Dettaglio distinta',
			tools: [helpTool("DettaglioDistinta")],
			//constrain: true,
			flex: 0,
			items: [{
				xtype: 'DCS_DettaglioDistinta',
				idDistint: idDistint,
				UrlSi:UrlSi,
				UrlNo:UrlNo,
				Url:UrlRicevuta,
				idGrid:idGrid
				}]
		});
		win.show();
		 return true;
		}
	};

}();

