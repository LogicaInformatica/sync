// Crea namespace DCS
Ext.namespace('DCS');

//--------------------------------------------------------------------
// Pannello di visualizzazione di un parametro di sistema
// oppure due parametri, passati con i loro nomi separati da virgola
//--------------------------------------------------------------------
DCS.Parametro = function(codParametro) 
{
	var dueParms = (codParametro.split(",").length>1);
	if (dueParms)
	{
		nomiParms  = codParametro.split(",");
		codParametro1 = nomiParms[0];
		codParametro2 = nomiParms[1];
	}
	else
	{
		codParametro1 = codParametro;
		codParametro2 = "";
	}
	/* definizione campi riga letta da DB */
	var record = new Ext.data.Record.create([{name: 'IdParametro', type: 'int'},
	                                         {name: 'CodParametro'},
				                             {name: 'TitoloParametro'},
				                             {name: 'ValoreParametro'},
				                             {name: 'IdParametro2', type: 'int'},
	                                         {name: 'CodParametro2'},
				                             {name: 'TitoloParametro2'},
				                             {name: 'ValoreParametro2'}]);
	/* definizione data store collegato al form */
	var datastore = new Ext.data.Store({
							proxy: new Ext.data.HttpProxy({url: 'server/editParametroSistema.php',method: 'GET'}),   
							baseParams:{task: 'read',cod:codParametro},
							reader:  new Ext.data.JsonReader({root: 'results'},record)
									  });
					
	/* Tipo di campo per l'edit: se il codice parametro comincia per DATA genera un datefield */
	var campo = {xtype:'textfield', name:'ValoreParametro'};
	if (codParametro1.substr(0,4)=='DATA')
		campo = {xtype: 'datefield',
				format: 'd/m/Y',
				name:'ValoreParametro',style:"vertical-align:top"};
				
	var campo2 = {xtype:'textfield', name:'ValoreParametro2',hidden:!dueParms,style: 'align:left'};
	if (codParametro2.substr(0,4)=='DATA')
		campo2 = {xtype: 'datefield',
				format: 'd/m/Y',
				name:'ValoreParametro2',style:"vertical-align:top"};

	/* form */
	var colonna1 = {xtype: 'container', layout: 'form', height:100, columnWidth:.45, labelStyle: 'text-align:right',
			items: [{xtype:'textfield',  hidden: true,  name:'CodParametro'},
			        {xtype:'textfield',  hidden: true,  name:'IdParametro'},
			        {xtype:'displayfield', name:'TitoloParametro', style:"color:blue;font-weight:bold"},
			        {xtype:'displayfield', name:'TitoloParametro2', style:"color:blue;font-weight:bold",hidden:!dueParms}]};
	
	var colonna2 = {xtype: 'container', layout: 'form', height:100, columnWidth:.2, 
			items: [{xtype:'textfield',  hidden: true,  name:'CodParametro2'},
			        {xtype:'textfield',  hidden: true,  name:'IdParametro2'},
			        campo,campo2]};
	var colonna3 = {xtype: 'container', layout: 'form', height:100, columnWidth:.35,items:[{xtype:'displayfield'}]};
	
	var formParametro = new Ext.form.FormPanel({
		title:'Modifica parametro di sistema',		
		frame: true,
		header: true,
		bodyStyle: 'padding:5px 5px 0',
		buttonAlign: "left",
		labelAlign: "right",
		layout:'column',height:140,labelWidth:10,
		alwaysRefresh:true, // introdotto per provocare reload alla selezione della voce di menù corrispondente
	//	flex: 1,
		border: false,
		trackResetOnLoad: true,
		reader: new Ext.data.JsonReader({root: 'results',fields: record}),
		items: [colonna1,colonna2,colonna3],
        buttons: [{text: 'Salva',
			    handler: function() {
							var frm = formParametro.getForm();
							if (frm.isValid()) {
								Ext.Msg.confirm('', 'Vuoi anche produrre e inviare l\'estratto delle spese di recupero?',
								function(btn, text){
									frm.submit({url: 'server/editParametroSistema.php',
									     method: 'POST',
										 params: {task: 'write', report:btn}, // btn vale "yes" se confermato report
										 success: function(){Ext.Msg.alert('Salvataggio', 'Correttamente effettuato');},
										 failure: function(frm, action){Ext.Msg.alert('Errore', action.result.error);},
										 scope: this,
										 waitMsg: 'Salvataggio in corso...'
									}); // fine submit()
								});
							} // fine if
			           	}, // fine function() in parametro handler
				  scope: this},'->'] // fine array buttons
		}); // fine formPanel;
	
	/* messaggio attesa lettura */
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso  ..."});
	myMask.show();
									
	/* esegue lettura */
	datastore.load({callback : function(rows,options,success) {
							if (success && rows.length>0) 
							{
								formParametro.getForm().loadRecord(rows[0]); // carica dati nel form
							}
							myMask.hide();
							}});
	return formParametro;
}; // fine funzione DCS.Parametro
