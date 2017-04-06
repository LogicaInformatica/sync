// Crea namespace DCS
Ext.namespace('DCS'); 

var win;
//--------------------------------------------------------
//Visualizza window sms
//--------------------------------------------------------
function showSmsForm(NomeUtente, numero) {
	win = new Ext.Window({
		modal: true,
	    width: 500,
	    height: 300,
	    minWidth: 500,
	    minHeight: 300,
	    layout: 'fit',
	    plain: true,
		constrainHeader: true,
	    title: 'Invio sms: ('+numero+')',
	    items: DCS.smsUtente(NomeUtente, numero) 
	});
	win.show();
};
//fine window

//componente interno
DCS.smsUtente = function(NomeUtente, numero) {
		
    var pannelloInvioSms = new Ext.form.FormPanel({
    	xtype: "form",
    	frame: true, 
    	title: "a: "+NomeUtente,
        width: 420,height: 250,labelWidth:100,
             defaults: {
                width: 300, 
    			height: 100
            },
        defaultType: 'textfield',
        items: [{
         	fieldLabel: 'Cellulare',
         	height: 20,
         	value: numero,
        	allowBlank: false,
        	name: 'Cellulare',
        	vtype: 'cell_list'
        },{
			xtype:'textarea',
            fieldLabel: 'Testo',
            maxLength: 700,
            id: 'nota',
            name: 'nota'
        }],

	   buttons: [{
			text: 'Invio',
			handler: function() {
		   		if (pannelloInvioSms.getForm().isDirty()) {
		   			if (pannelloInvioSms.getForm().isValid()){
						pannelloInvioSms.getForm().submit({
							url: 'server/utentiProfili.php',
					        method: 'POST',
					        params: {task: 'invioSms',NomeUtente:NomeUtente},
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
					        	Ext.MessageBox.alert('Errore', action.result.msg);
					        }
						});
					}
		   		}else{Ext.Msg.alert('Errore', "Il form del messaggio non e\' stato completato.");}
			}
		},{
			text: 'Annulla',handler: function () {quitForm(pannelloInvioSms,win);} 
		}] 
    });
      
	return pannelloInvioSms;
};
