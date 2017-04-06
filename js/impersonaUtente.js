// Crea namespace DCS
Ext.namespace('DCS'); 

var win;
//--------------------------------------------------------
//Visualizza window sms
//--------------------------------------------------------
function showAnswForm(userid, utente) {
	win = new Ext.Window({
		modal: true,
	    width: 350,
	    height: 140,
	    minWidth: 350,
	    minHeight: 140,
	    layout: 'fit',
	    plain: true,
		constrainHeader: true,
	    title: 'Conferma',
	    items: DCS.impUtente(userid,utente) 
	});
	win.show();
};
//fine window

//componente interno
DCS.impUtente = function(userid,utente) {
	
	var pannelloConferma = new Ext.form.FormPanel({
		xtype: "form",
		frame: true, 
		title: 'Impersona l\' utente: '+utente+'',
	    width: 640,height: 400,labelWidth:100,
	        defaultType: 'textfield',
	        items: [{
	 			xtype:'label',
	         	id: 'messaggio',
	        	name: 'messaggio',
	            anchor: '97%',
	            text:"Si e\' sicuri di voler continuare con l\'operazione?"
	            //height: 36
	        }],
	    buttons: [{
				text: 'Conferma',
				handler: function() {
	    			pannelloConferma.getForm().submit({
						url: 'server/utentiProfili.php',
				        method: 'POST',
				        params: {task: 'impUser',useridSlave:userid},
						success: function (frm,action) {
				        	if(action.result.success){
					        	win.close();
					        	document.location = 'main.php';
				        	}else{
				        		Ext.MessageBox.alert('Errore', action.result.msg);
				        	}
				        },
						failure: function (frm,action) {
				        	console.log("fail "+action.result.success);
				        }
					});			   		
				}
			}, 
			{text: 'Annulla',handler: function () {quitForm(pannelloConferma,win);} 
		}]  // fine array buttons
	});
	return pannelloConferma;
};
