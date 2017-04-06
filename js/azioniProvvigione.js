// Crea namespace DCS
Ext.namespace('DCS'); 

var win;
//--------------------------------------------------------
// Visualizza window  
//--------------------------------------------------------
function showAnswFormProvvigione(task, subtitle,idProvvigione, store) {
	win = new Ext.Window({
		modal: true,
	    width: task=='fileCerved'?400:320,
	    height: 120,
	    minWidth: task=='fileCerved'?400:320,
	    minHeight: 120,
	    layout: 'fit',
	    plain: true,
		constrainHeader: true,
	    title: 'Conferma dell\'operazione di '+subtitle,
	    items: DCS.azProvvigione(task, idProvvigione, store) 
	});
	win.show();
};
//fine window

//componente interno
DCS.azProvvigione = function(task, idProvvigione, store) {
	
	var hiddenCerved=true;
	var hiddenOther=false;
	if (task=='fileCerved')
	{
		hiddenCerved=false;
		hiddenOther=true;
	}
	
	var pannelloConferma = new Ext.form.FormPanel({
		xtype: "form",
		frame: true, 
		//title: 'Operazione: '+utente+'',
	    width: 450,height: 120,
	        items: [{
	 			xtype:'label',
	         	id: 'messaggio',
	        	name: 'messaggio',
	            anchor: '97%',
	            style: 'text-align:middle',
	            text:hiddenCerved?"Si e\' sicuri di voler continuare con l\'operazione?":"Quali files vuoi generare?"
	            //height: 36
	        }],
	    buttons: [{
					text: 'Conferma',
					hidden: hiddenOther,
					handler: function(){submitFormProvv(0,task,idProvvigione, store,pannelloConferma);}
				}, 
				{text: 'Persone fisiche', hidden: hiddenCerved, handler: function(){submitFormProvv(2,task,idProvvigione, store,pannelloConferma);}}, 
				{text: 'Persone giuridiche', hidden: hiddenCerved, handler: function () {submitFormProvv(1,task,idProvvigione, store,pannelloConferma);}},
				{text: 'Entrambi', hidden: hiddenCerved, handler: function () {submitFormProvv(3,task,idProvvigione, store,pannelloConferma);}},
				{text: 'Annulla', handler: function () {quitForm(pannelloConferma,win);}}]  // fine array buttons
	});
	return pannelloConferma;
};


function submitFormProvv(tipoCliente,task,idProvvigione, store,pannelloConferma) {
	var myMaskAzione = new Ext.LoadMask(Ext.getBody(), {
		msg: "Azione in corso..."
	});
	/* provata estensione timeout, senza effetto: dopo 30 secondi va in errore.
	Ext.override(Ext.data.Connection, { // aumenta il timeout ajax, altrimenti non ce la fa
        timeout:300000 // 300 seconds
	});
	Ext.Ajax.timeout = 300000;
	*/
	myMaskAzione.show();
	pannelloConferma.getForm().submit({
		url : 'server/provvigioni.php',
		method: 'POST', 
	 	params : {task: task, idProvvigione: idProvvigione,tipoCliente: tipoCliente },
		success: function (frm,action) {
			if(action.result.success){
				myMaskAzione.hide();
				Ext.MessageBox.alert('Esito', action.result.message);
	        	win.close();
				if (task!="fileCerved") store.load(); // se serve, ricrea la lista
	        	//document.location = 'main.php';
			} else {
				myMaskAzione.hide();
        		Ext.MessageBox.alert('Errore', action.result.error);
			  }
        },
		failure: function (frm,action) {
			myMaskAzione.hide();
			if (action.result)
				Ext.MessageBox.alert('Errore', action.result.error);
			else
				Ext.MessageBox.alert('Errore', action.response.statusText);
        	win.close();
        }
	});			   		
}