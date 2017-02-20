// Crea namespace DCS
Ext.namespace('DCS');

//--------------------------------------------------------
// Avviso in sovrapposizione alla pagina
//--------------------------------------------------------
DCS.avvisoAge = function(){
	return {	create: function() {
				
				    
					var recordavvisoAge = new Ext.data.Record.create([
						                                    		{name: 'IdModello', type: 'int'},
						                                    		{name: 'FileName'},
						                                    		{name: 'Attivo'},
						                                    		{name: 'TestoAvviso'}
						                                    	  ]);
					
					var dsAvvisoAge = new Ext.data.Store({
						proxy: new Ext.data.HttpProxy({
							url: 'server/editAvvisoAgenzia.php',
							method: 'POST'
						}),   
						baseParams:{task: 'read'},
						reader:  new Ext.data.JsonReader(
							{root: 'results'},recordavvisoAge
				        )
					});
					
					var formAvvisoAge = new Ext.form.FormPanel({
						title:'Avviso agenzia',		
						frame: true,
						header: true,
						bodyStyle: 'padding:5px 5px 0',
						layoutConfig: {flex: 1},
						anchor:'95%',
						border: false,
						trackResetOnLoad: true,
						reader: new Ext.data.JsonReader({
							root: 'results',
							fields: recordavvisoAge}),
						items: [  
			    				 {  
									xtype:'container', 
									layout:'column',
									items:[
									       {
											xtype:'panel', 
											layout:'form', 
											labelWidth:100, 
											columnWidth:1, 
											buttonAlign: "center",
											//defaultType:'textfield',
											defaults: {anchor:'95%', readOnly:false},
											items: [
											         {
											          xtype:'textfield', 
										        	  fieldLabel:'modello',
										        	  hidden: true,
										        	  name:'IdModello',
										        	  id:'IdModello',	
										        	  style:'text-align:right'
											         }
											         ,
											         {xtype:'textfield', 
											          fieldLabel:'FileName',
											          hidden: true,
											          id:'FileName',
											          name:'FileName',
											          style:'text-align:right'
											         },
											         {xtype:'textfield', 
												          fieldLabel:'Attivo',
												          id:'Attivo',
												          hidden: true,
												          name:'Attivo',
												          style:'text-align:right'
												      },
												      {xtype:'htmleditor',
												       fieldLabel: 'Testo',
												       id: 'TestoAvviso',
												       name: 'TestoAvviso',
												       anchor: '100%',
												       width:'100%', 
												       allowBlank: false,
												       height: 500
											         }
									     ]
									     ,
									     buttons: [{
												text: 'Salva Testo',
												id: 'btnSalvaTxt',
												handler: function() {
									    	 		var frm = formAvvisoAge.getForm();
									    	 		frm.submit({
														url: 'server/editAvvisoAgenzia.php',
														method: 'POST',
														
														params: {task: 'saveTxt',
									    	 					 NomeFile: Ext.getCmp('FileName').getValue(),
									    	 					 TestoAvviso: Ext.getCmp('TestoAvviso').getValue()
									    	 					 },
														success: function(){
															Ext.Msg.alert('Salvataggio', 'Correttamente effettuato');
														},
														failure: function(frm, action){
															Ext.Msg.alert('Errore', action.result.error);
														},
														scope: this,
														waitMsg: 'Salvataggio in corso...'
													});
									    	 
												},
												scope: this
											}
											,
											{
												text: 'Attiva avviso',
												id: 'btnAttivaAvv',
												handler: function() {
									    	 		var frm = formAvvisoAge.getForm();
									    	 		frm.submit({
														url: 'server/editAvvisoAgenzia.php',
														method: 'POST',
														
														params: {task: 'saveStato',
									    	 					 AvvisoAttivo: Ext.getCmp('Attivo').getValue(),
									    	 					 IdModello: Ext.getCmp('IdModello').getValue()
									    	 					 },
														success: function(){
									    	 				if (Ext.getCmp('Attivo').getValue()=='S'){
									    	 					Ext.getCmp('Attivo').setValue("N");
									    	 					Ext.getCmp('btnAttivaAvv').setText("Attiva avviso");
									    	 				}else{
									    	 					Ext.getCmp('Attivo').setValue("S");
									    	 					Ext.getCmp('btnAttivaAvv').setText("Disattiva avviso");
									    	 				}
															Ext.Msg.alert('Salvataggio', 'Correttamente effettuato');
														},
														failure: function(frm, action){
															Ext.Msg.alert('Errore', action.result.error);
														},
														scope: this,
														waitMsg: 'Salvataggio in corso...'
													});
												
												}
												,
												scope: this
											}
											]
			    				 	}
							      
								        
							        	
								  ]
									      		  
								}]});	

      
								
									var myMask = new Ext.LoadMask(Ext.getBody(), {
									 	msg: "Caricamento in corso  ..."
								 	});
									myMask.show();
									
									dsAvvisoAge.load({
										callback : function(r,options,success) {
												if (success && r.length>0) 
												{
													formAvvisoAge.getForm().loadRecord(r[0]);
													
												}
												if (Ext.getCmp('Attivo').getValue()=="S")
												{
													Ext.getCmp('btnAttivaAvv').setText("Disattiva avviso");
												}
												myMask.hide();
										}
									 });
										

								
								//Ext.Msg.alert(dsAvvisoAge[0]);
								
								/*win.on({
									'close' : function () {
										oldWind = '';
										}
								});*/
								return formAvvisoAge;
					 		}
			 }
		
	
}(); // fine funzione 
