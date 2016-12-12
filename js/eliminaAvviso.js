// Crea namespace DCS
Ext.namespace('DCS'); 

var win;
//--------------------------------------------------------
//Visualizza window sms
//--------------------------------------------------------
function showAnswFormNota(idNota,winM,winMM,idP,numP,storeRel) {
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
	    items: DCS.impAvvDel(idNota,winM,winMM,idP,numP,storeRel) 
	});
	win.show();
};
//fine window

//componente interno
DCS.impAvvDel = function(idNota,winM,winMM,idP,numP,storeRel) {
	winMM = winMM || null;
	numP = numP || '';
	idP = idP || '';
	storeRel = storeRel || '';
	var pannelloConferma = new Ext.form.FormPanel({
		xtype: "form",
		frame: true, 
		title: 'Eliminazione nota',
	    width: 640,height: 400,labelWidth:100,
	        defaultType: 'textfield',
	        items: [{
	 			xtype:'label',
	         	id: 'messaggioEN',
	        	name: 'messaggioEN',
	            anchor: '97%',
	            text:"Confermare l\'eliminazione della nota."
	            //height: 36
	        }],
	    buttons: [{
				text: 'Conferma',
				handler: function() {
	    			var WINMain = winM;
	    			var WINMotherMain = winMM;
	    			var idPratica = idP;
	    			var numPratica = numP;
	    			var storegriglia = storeRel;
	    			//retrive info su nota
	    			Ext.Ajax.request({
						url : 'server/AjaxRequest.php' , 
						params : {task: 'read',sql: "Select TipoNota FROM nota where IdNota="+idNota},
						method: 'POST',
						autoload:true,
						success: function ( result, request ) {
							var jsonData = Ext.util.JSON.decode(result.responseText);
							var tipo = jsonData.results[0]['TipoNota'];
							//cancellazione
							Ext.Ajax.request({
								url : 'server/AjaxRequest.php' , 
								params : {task: 'read',sql: "DELETE FROM notautente where IdNota="+idNota},
								method: 'POST',
								autoload:true,
								success: function ( result, request ) 
								{
									Ext.Ajax.request({
										url : 'server/AjaxRequest.php' , 
										params : {task: 'read',sql: "DELETE FROM nota where IdNota="+idNota},
										method: 'POST',
										autoload:true,
										success: function ( result, request ) 
										{
											var mex='';
											win.close();
											WINMain.close();
											//aggiornamento finestre tree
											Ext.Ajax.request({
												url: 'server/edit_ramiNote.php', method:'POST',
												params :{task:'readTree',IdPratica:idPratica},
												callback : 	function(r,options,success) 
															{
												 				var idRamoSelezionato = 0;
																var myMask = new Ext.LoadMask(Ext.getBody(), {
																	msg: "Aggiornamento note..."
																});											
																myMask.show();
																var arrayStr = '';
															 	arrayStr =  success.responseText;
															 	var child = Ext.util.JSON.decode(arrayStr); 
															 	
															 	var nroot=new Ext.tree.AsyncTreeNode({
														            expanded: true,
														            children: child
														        });
															 	switch (tipo) {
																case 'C': 
																	if(WINMotherMain!=null){
																 		Ext.getCmp('winRami').setRootNode(nroot);
																 	}else{
																 		Ext.getCmp('treeNotePratica').setRootNode(nroot);
																 	}
																	//WINMotherMain.close();
																	//DCS.FormVistaNote.showDetailVistaNote(idPratica,numPratica,'C',0);
																	storegriglia.reload;
																	break;
																case 'N': 
																	if(WINMotherMain!=null){
																 		Ext.getCmp('winRami').setRootNode(nroot);
																 	}else{
																 		Ext.getCmp('treeNotePratica').setRootNode(nroot);
																 	}
																	//WINMotherMain.close();
																	//DCS.FormVistaNote.showDetailVistaNote(idPratica,numPratica,'N',0);
																	storegriglia.reload;
																	break;
																default: 
																	mex= "Avviso cancellato.";
																	Ext.getCmp('avvisi_panel').load('server/avvisi.php');
																	Ext.MessageBox.alert('Esito', mex);
																	break;
															 	}
															 	
															 	myMask.hide();
															},
												failure:	function (obj)
															{
																Ext.MessageBox.alert('Errore', 'Errore durante la lettura dei rami.');
															},
												scope: this
											});
										},
										failure: function ( result, request) { 
											Ext.MessageBox.alert('Errore', result.responseText); 
										},
										scope:this
									});
								},
								failure: function ( result, request) { 
									Ext.MessageBox.alert('Errore', result.responseText); 
								},
								scope:this
							});
						},
						failure: function ( result, request) { 
							Ext.MessageBox.alert('Errore', result.responseText); 
						},
						scope:this
					});
				}
			}, 
			{text: 'Annulla',handler: function () {quitForm(pannelloConferma,win);} 
		}]  // fine array buttons
	});
	return pannelloConferma;
};
