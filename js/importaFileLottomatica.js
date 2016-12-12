// Crea namespace DCS
Ext.namespace('DCS');


var win;

DCS.PanelImport = Ext.extend(Ext.Panel, {
	idObjMother:'',
	initComponent: function() {

		var formImport = new Ext.form.FormPanel({
			xtype: 'form',
			//labelWidth: 40, 
			frame: true, 
			fileUpload: true, 
			//title: 'Pratica : ' + this.CodContratto + ' - ' + this.NomeCliente,
		    width: 345,
		    height: 100,
		    hideLabel:true,
		    trackResetOnLoad: true,
			reader: new Ext.data.ArrayReader({
				root: 'results',
				fields: DCS.recordIncassso}),
			items: [{
					    xtype: 'fileuploadfield',
//					    anchor: '97%',
					    width: 300,
					    hideLabel:true,
					    //fieldLabel: 'Importa file',
					    name: 'docFLPath',
					    id: 'docFLPathid',
					    buttonText: 'Cerca',
					    editable:true,
					    listeners:{
							fileselected: function(fcomp,file){
								//console.log("file "+file);
								//console.log("File "+fcomp.getValue());
								formImport.getForm().submit({
									url: 'server/processLottomatica.php',
									method: 'POST',
									params: {task: 'process',check:true},
									success: function(frm,action) {
										if(action.result.success==true){
											//Ext.MessageBox.alert('Esito', action.result.error);
											Ext.getCmp('bSalva').setDisabled(false);
										}else{
											console.log("fallimento");
											Ext.MessageBox.alert('Errore', action.result.error);
										}
									},
									failure: function (frm,action) {
										console.log("fallimento critico");
										Ext.MessageBox.alert('Errore','Si &egrave verificato un errore durante l\'operazione.'); 
									},
									scope: this,
									waitMsg: 'Controllo in corso...'
								});
							}
						}
					}], // fine array items del form
		    buttons: 
		    	[{
				  text: 'Salva',
				  id:'bSalva',
				  disabled:true,
				  handler: function() {
		    		 	if (formImport.getForm().isValid()){
							this.disable();
							var idM=this.idObjMother;
							formImport.getForm().submit({
								url: 'server/processLottomatica.php', method: 'POST',
								params: {task:"insert"},
								success: function (frm,action) {
									Ext.MessageBox.alert('Esito',action.result.error);
									var grid = Ext.getCmp(idM).getStore().reload();
									win.close();
									//saveSuccess(win,frm,action);
								},
								failure: saveFailure
							});
						}
					},
					scope:this
				 }, 
				{text: 'Annulla',handler: function () {quitForm(formImport,win);} 
				}
			   ]  // fine array buttons
		});

		Ext.apply(this, {
			items: [formImport]
		});
		
		DCS.PanelImport.superclass.initComponent.call(this);
		
	}	// fine initcomponent
});

// register xtype
Ext.reg('DCS_ImportFLotto', DCS.PanelImport);
		
//--------------------------------------------------------
//Visualizza dettaglio incasso
//--------------------------------------------------------
DCS.showImportLottForm = function(idObj){

	return {
		create: function(idObj){
		win = new Ext.Window({
			layout: 'fit',
			width: 355,
		    height: 130,
		    //labelWidth:100,
			//plain: true,
			//bodyStyle: 'padding:5px;',
			modal: true,
			title: 'Importa file lottomatica',
			tools: [helpTool("ImportaLottomatica")],
			//constrain: true,
			flex: 1,
			items: [{
					xtype: 'DCS_ImportFLotto',
					idObjMother:idObj
			}]
		});
		win.show();
		 return true;
		}
	};

}();

