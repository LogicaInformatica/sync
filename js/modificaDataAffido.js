// Crea namespace DCS
Ext.namespace('DCS');

//--------------------------------------------------------
// Avviso in sovrapposizione alla pagina
//--------------------------------------------------------
DCS.ModificaDataAffido = function(){
	return {
		create: function() {
							    
			var recordModDataAffido = new Ext.data.Record.create([
				                                    		{name: 'DateStandard'},
				                                    		{name: 'DateVariate'}
				                                    	  ]);
			
			var dsModDataAffido= new Ext.data.Store({
				proxy: new Ext.data.HttpProxy({
					url: 'server/editModificaDataAffido.php',
					method: 'POST'
				}),   
				baseParams:{task: 'read'},
				reader:  new Ext.data.JsonReader(
					{root: 'results'},recordModDataAffido
		        )
			});
			
				
			var formModDataAffido = new Ext.form.FormPanel({
				title:'Modifica data affido',
				frame: true,
				header: true,
				bodyStyle: 'padding:5px 5px 0',
				layoutConfig: {flex: 1},
				anchor:'95%',
				height: 600,
				border: false,
				trackResetOnLoad: true,
				buttonAlign: "center",
				autoScroll: true, 
				reader: new Ext.data.JsonReader({
					root: 'results',
					fields: recordModDataAffido}),
				items: [],
				buttons: [{
						text: 'Salva',
						id: 'btnSalvaModDataAffido',
						handler: function() {
			    	 		var frm = formModDataAffido.getForm();
			    	 		if (frm.isDirty()) {
			    	 			//numero di date che posso modificare 
			    	 			var numDate = formModDataAffido.items.length;
			    	 			frm.submit({
									url: 'server/editModificaDataAffido.php',
									method: 'POST',
									params: {task: 'saveDateModificate', numDate: numDate},
									success: function(){
										Ext.Msg.alert('Salvataggio', 'Correttamente effettuato');
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
					}
				],
				listeners: {
				    beforeRender: function (component, eOpts) {
				        component.removeAll();
				        
				        // code
				        var myMask = new Ext.LoadMask(Ext.getBody(), {
							 	msg: "Caricamento in corso  ..."
					 	});
						myMask.show();
						
				        dsModDataAffido.load({
							callback : function(r,options,success) {
								    if (success) {
								       for (i=0;i<r.length;i++) {
								       	  var tabGestioneDateAffido = new Ext.Container({
												//autoEl: 'div',  // This is the default
												layout:'hbox',
												id: 'tabGestioneDateAffido' + i,
												defaults: {layout: 'form', border: false, width: 350},
												//  The two items below will be Ext.Containers, each encapsulated by a <DIV> element.
												items: [
												   {items: [
	        											{
															xtype: 'datefield',
															format: 'd/m/Y',
														    fieldLabel: 'Data affido standard',
														    width: 100,
															id: 'DateStandard' + i,
															name: 'DateStandard' + i,
															readOnly: true,
															labelStyle: 'white-space: nowrap; width:130;'
														}
													]},
													{items: [
	                                                    {
															xtype: 'datefield',
															format: 'd/m/Y',
															width: 100,
															fieldLabel: 'Data affido modificata',
															id: 'DateVariate' + i,
															name: 'DateVariate' + i,
															//disabledDays:  [0, 6],
															vtype: 'daterange',
															editable: false,
															labelStyle: 'white-space: nowrap; width:130;'
														}
													]}
												]
										  });  
								       	  
								       	  component.insert(i,tabGestioneDateAffido);
								       	  Ext.getCmp('DateStandard' + i).setValue(Date.parseDate(r[i].data.DateStandard, "Y-m-d"));
								       	  Ext.getCmp('DateVariate' + i).setValue(Date.parseDate(r[i].data.DateVariate, "Y-m-d"));
								       	  Ext.getCmp('DateVariate' + i).originalValue = new Date(Ext.getCmp('DateStandard' + i).getValue());
								       	  var minValue = new Date(r[i].data.DateStandard);
								       	  minValue.setDate(minValue.getDate()-3);
								       	  Ext.getCmp('DateVariate' + i).setMinValue(minValue.toISOString().slice(0,10));
								       	  var maxValue = new Date(r[i].data.DateStandard);
								       	  maxValue.setDate(maxValue.getDate()+3);
								       	  Ext.getCmp('DateVariate' + i).setMaxValue(maxValue.toISOString().slice(0,10));
								       }
								    }
								    component.doLayout();
								    myMask.hide();
							}
						 });
				    }   
				}
			});	

    		return formModDataAffido;
		}
	};
}(); // fine funzione 
