// Crea namespace DCS
Ext.namespace('DCS');


var wind;


DCS.recordProcedura = Ext.data.Record.create([
                                   		{name: 'IdProcedura', type: 'int'},
                                   		{name: 'TitoloProcedura'},
                                   		{name: 'numAzioni', type: 'int'},
                                   		{name: 'numStati', type: 'int'},
                                   		{name: 'Attiva'},
                                   		{name: 'CodProcedura'}]);

DCS.DettaglioProcedura = Ext.extend(Ext.Panel, {
	idProcedura:'',
	titoloProc:'',
	Wmain:'',
	initComponent: function() {
		var bDisa=true;
		var grid=this.Wmain;
		var procId = this.idProcedura;
		var titProc = this.titoloProc;
		if(this.idProcedura!='')
		{
			var query = "SELECT * FROM v_procedure_workflow WHERE IdProcedura = "+this.idProcedura;
		}else{
			var query="";
		}
								
		var dsProced = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordProcedura)
	    });
		
		var formProc = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
			title: 'Procedura : ' + this.titoloProc,
		    width: 450,
		    height: 200,
		    labelWidth:100,
		    trackResetOnLoad: true,
			reader: new Ext.data.ArrayReader({
				root: 'results',
				fields: DCS.recordProcedura}),
			items: [{
					xtype: 'fieldset',
					autoHeight: true,
					width: 435,
					items: [{
						xtype: 'panel',
						layout: 'form',
						labelWidth: 70,
						columnWidth: 1,
						defaults: {xtype: 'textfield', anchor: '97%'},
						items: [{
							fieldLabel: 'IdProcedura',
							readOnly:true,
							hidden:true,
							style:'text-align:right',
							name: 'IdProcedura'
						},{
							fieldLabel: 'Cod.&nbsp;Procedura',
							readOnly:true,
							hidden:true,
							style:'text-align:right',
							name: 'CodProcedura'
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:false,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 100,
								columnWidth: 0.7,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{	
									style: 'nowrap',
									width: 130,
									style: 'text-align:left',
									id:'nomeProc',
									fieldLabel: 'Nome procedura',
									name: 'TitoloProcedura',
									enableKeyEvents: true,
									listeners:{
										change : function(field, newValue,oldValue ){
											if(newValue!='')
											{
												Ext.getCmp('bSaveProc').setDisabled(false);
											}else{
												Ext.getCmp('bSaveProc').setDisabled(true);
											}
										}
									}
								}]
							},{
								xtype: 'panel',
								layout: 'form',
								labelWidth: 1,
								columnWidth: 0.2,
								defaults: {xtype: 'textfield', anchor: '99%'},
								items: [{
									//labelStyle: 'width:80;',
									id:'chkatt',
	           						xtype: 'checkbox',
									boxLabel: 'Attiva',
									name: 'Attiva',
									hidden: false
									//checked: true
								}]
							}]
						},{
							xtype: 'fieldset',
							autoHeight: true,
							border:false,
							layout: 'column',
							items: [{
								xtype: 'panel',
								layout: 'form',
								columnWidth: 0.25,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{
				                	xtype:'button',
				                	boxMinWidth:80,
				                	width:80,
			                    	tooltip:"Edita le azioni della procedura",
									text:"Azioni",
									name: 'btnApriAz', 
								    id: 'btnApriAz',
								    anchor: '30%',
								    handler: function() {
										Ext.getCmp(grid).showAzioneWfDettaglio(procId,wind.getId(),titProc,grid);	
						        	},
									scope: this
			                    }]
							},{
								xtype: 'panel',
								layout: 'form',
								columnWidth: 0.25,
								defaults: {xtype: 'textfield', anchor: '97%'},
								items: [{
				                	xtype:'button',
				                	boxMinWidth:80,
			                    	tooltip:"Edita gli stati della procedura",
									text:"Stati",
									width:80,
									name: 'btnApriSt', 
								    id: 'btnApriSt',
								    anchor: '30%',
								    handler: function() {
										Ext.getCmp(grid).showStatiWfDettaglio(procId,wind.getId(),titProc,grid);
						        	},
									scope: this
			                    }]
							}]
						}]
					}]
			}], // fine array items del form
		    buttons: 
		    	[
		    	 {
				  text: 'Salva',
				  disabled:bDisa,
				  id:'bSaveProc',
				  handler: function() {
		    		 	if(Ext.getCmp('nomeProc').getValue()!='')
		    		 	{
		    		 		if (formProc.getForm().isValid()){
								this.setDisabled(true);
								formProc.getForm().submit({
									url: 'server/gestioneProcedure.php', method: 'POST',
									params: {task:"saveProc"},
									success: function (frm,action) {
										Ext.MessageBox.alert('Esito', action.result.messaggio); 
										wind.close();
										Ext.getCmp(grid).getStore().reload();
									},
									failure: saveFailure
								});
							}
		    		 	}else{
		    		 		Ext.MessageBox.alert('Errore', 'Nome non valido per la procedura.');
		    		 	}
					},
					scope:this
				 }, 
				{text: 'Annulla',handler: function () {quitForm(formProc,wind);} 
				}
			   ]  // fine array buttons
			   
		});

		Ext.apply(this, {
			items: [formProc]
		});
		
		DCS.DettaglioProcedura.superclass.initComponent.call(this);
		//caricamento dello store
		if(query!='')
		{
			dsProced.load({
				params:{sql:query},
				callback : function(rows,options,success) {
					if(rows.length>0){
						formProc.getForm().loadRecord(rows[0]);
						if(rows[0].get('Attiva')=='Y'){
							Ext.getCmp('chkatt').setValue(true);
						}
						if(Ext.getCmp('nomeProc').getValue()!='')
		    		 	{
							Ext.getCmp('bSaveProc').setDisabled(false);
		    		 	}
						Ext.getCmp('btnApriSt').setText(rows[0].get('numStati')+' '+Ext.getCmp('btnApriSt').getText());
						Ext.getCmp('btnApriAz').setText(rows[0].get('numAzioni')+' '+Ext.getCmp('btnApriAz').getText());
					}
				},
				scope:this
			});
		}		
	}	// fine initcomponent
});

// register xtype
Ext.reg('DCS_DettaglioProcedura', DCS.DettaglioProcedura);
		
//--------------------------------------------------------
//Visualizza dettaglio procedura
//--------------------------------------------------------
DCS.showProcedureDetail = function(){

	return {
		create: function(idProcedura,titoloProc,WinMain){
		
		wind = new Ext.Window({
			layout: 'fit',
			width: 465,
		    height: 230,
		    //labelWidth:100,
			//plain: true,
			//bodyStyle: 'padding:5px;',
			modal: true,
			title: 'Dettaglio Procedura',
			tools: [helpTool("DettaglioProcedura")],
			//constrain: true,
			flex: 1,
			items: [{
				xtype: 'DCS_DettaglioProcedura',
				idProcedura:idProcedura,
				titoloProc:titoloProc,
				Wmain:WinMain
				}]
		});
		wind.show();
		 return true;
		}
	};

}();

