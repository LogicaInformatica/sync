// Crea namespace DCS
Ext.namespace('DCS'); 

var win;
//--------------------------------------------------------
//Visualizza window sms
//--------------------------------------------------------
function showMailForm(mail, utente) {
	win = new Ext.Window({
		modal: true,
	    width: 670,
	    height: 420,
	    minWidth: 670,
	    minHeight: 420,
	    layout: 'fit',
	    plain: true,
		constrainHeader: true,
	    title: 'Invio e-mail: ('+mail+')',
	    items: DCS.mailUtente(mail,utente) 
	});
	win.show();
};
//fine window

//componente interno
DCS.mailUtente = function(mail,utente) {
	
	var pannelloInviomail = new Ext.form.FormPanel({
		xtype: "form",
		frame: true, 
		title: "a: "+utente,
	    width: 640,height: 400,labelWidth:100,
	        defaultType: 'textfield',
	        items: [{
	         	fieldLabel: 'Oggetto',
	 			xtype:'textarea',
	         	id: 'oggetto',
	        	name: 'oggetto',
	        	allowBlank: false,
	            anchor: '97%',
	            height: 36
	        },{
	         	fieldLabel: 'Indirizzo e-mail',
	            anchor: '97%',
	          	height: 20,
	         	value: mail,
	         	allowBlank: false,
	        	name: 'email',
	        	vtype: 'email_list'
	        },{
				xtype:'htmleditor',
	            fieldLabel: 'Nota/testo',
	            id: 'nota',
	            name: 'nota',
	            allowBlank: false,
	            enableAlignments : true,
				enableColors : true,
				enableFont : true,
				enableFontSize : true,
				enableFormat : true,
				enableLinks : true,
				enableLists : true,
				enableSourceEdit : true,
	            anchor: '97%',
	            height: 200
	        }],
	    buttons: [{
				text: 'Conferma',
				handler: function() {
			   		if (pannelloInviomail.getForm().isDirty()) {
			   			if (pannelloInviomail.getForm().isValid()){
							pannelloInviomail.getForm().submit({
								url: 'server/utentiProfili.php',
						        method: 'POST',
						        params: {task: 'invioMail',NomeUtente:utente},
								success: function (frm,action) {
						        	if(action.result.success=='true'){
							        	Ext.MessageBox.alert('Esito', action.result.msg);
							        	win.close();	
						        	}else{
						        		Ext.MessageBox.alert('Errore', action.result.msg);
						        	}
						        },
								failure: function (frm,action) {
						        	console.log("fail "+action.result.success);
						        }
							});
						}
			   		}else{Ext.Msg.alert('Errore', "Il form della e-mail non e\' stato completato.");}
				}
			}, 
			{text: 'Annulla',handler: function () {quitForm(pannelloInviomail,win);} 
		}]  // fine array buttons
	});
	return pannelloInviomail;
};
