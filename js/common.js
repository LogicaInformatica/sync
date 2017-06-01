//========================================================================
// Funzioni di utilit� comuni Javascript
//========================================================================
Ext.namespace('DCS');

// Funzione Ext.clone copiata dalla versione 4.0 di Exths (nella versione  3 non c'�)
enumerables = ['hasOwnProperty', 'valueOf', 'isPrototypeOf', 'propertyIsEnumerable',
               'toLocaleString', 'toString', 'constructor'];
Ext.clone = function(item) {
    if (item === null || item === undefined) {
        return item;
    }

    // DOM nodes
    // TODO proxy this to Ext.Element.clone to handle automatic id attribute changing
    // recursively
    if (item.nodeType && item.cloneNode) {
        return item.cloneNode(true);
    }

    var type = toString.call(item);

    // Date
    if (type === '[object Date]') {
        return new Date(item.getTime());
    }

    var i, j, k, clone, key;

    // Array
    if (type === '[object Array]') {
        i = item.length;

        clone = [];

        while (i--) {
            clone[i] = Ext.clone(item[i]);
        }
    }
    // Object
    else if (type === '[object Object]' && item.constructor === Object) {
        clone = {};

        for (key in item) {
            clone[key] = Ext.clone(item[key]);
        }

        if (enumerables) {
            for (j = enumerables.length; j--;) {
                k = enumerables[j];
                clone[k] = item[k];
            }
        }
    }

    return clone || item;
};

/*-------------------------------------------------------------------------------------
 * Maschera di attesa di uso comune
 * In particolare, viene usata per metterla prima delle letture preliminari di dati e 
 * toglierla nell'evento load dello store della griglia
 *-------------------------------------------------------------------------------------*/ 
DCS.pendingMask = null; // pu� contenere la mask che deve essere tolta a fine load 
// Funzione per far apparire il messaggio di attesa
DCS.showMask    = function (msg,screenCenter)
{ 
	if (DCS.pendingMask) // gi� visibile, evita di metterne due
		return;
	if (msg==undefined || msg=='')
		msg = "Elaborazione in corso...";
	if (!screenCenter && DCS.mainPanel && Ext.WindowMgr.getActive()==null) // solo se non c'� una window aperta
		body = DCS.mainPanel.el; // se pu�, maschera solo il pannello centrale
	else
		body = Ext.getBody();	 // altrimenti tutta la pagina
	DCS.pendingMask = new Ext.LoadMask(body, {msg: msg, removeMask:true});
	DCS.pendingMask.show();
};

//Funzione per far sparire il messaggio di attesa
DCS.hideMask    = function ()
{ 
	if (DCS.pendingMask)
	{
		DCS.pendingMask.hide();
		DCS.pendingMask = null;
	}
};

/*----------------------------------------
 * Validazione per range di date 
 *---------------------------------------*/
Ext.apply(Ext.form.VTypes, {
    daterange : function(val, field) {
        var date = field.parseDate(val);
        if(!date){
            return false;
        }
        
        if (field.startDateField) {
       	
            var start = Ext.getCmp(field.startDateField);
            if (!start.maxValue || (date.getTime() != start.maxValue.getTime())) {
                start.setMaxValue(date);
                start.validate();
            }
        }
        else if (field.endDateField) {
  
            var end = Ext.getCmp(field.endDateField);
            if (!end.minValue || (date.getTime() != end.minValue.getTime())) {
                end.setMinValue(date);
                end.validate();
            }
        }
        return true;
    },
    daterangeText: 'Data non valida'
});

/*----------------------------------------
 * Validazione per Ora:minuti 
 *---------------------------------------*/
var timeTest = /^([1-9]|[01][0-9]|2[0-3]):([0-5][0-9])$/i;
Ext.apply(Ext.form.VTypes, {
    //  vtype validation function
    time: function(val, field) {
        return timeTest.test(val);
    },
    // vtype Text property: The error text to display when the validation function returns false
    timeText: 'Ora non valida.  Deve essere nel formato "hh:mm".',
    // vtype Mask property: The keystroke filter mask
    timeMask: /[\d\s:amp]/i
});

/*----------------------------------------
 * Validazione per lista di email 
 *---------------------------------------*/
Ext.apply(Ext.form.VTypes, {
    email_list : function(val, field) {
        var arr = val.split(";");

        for (var i=0; i<arr.length; i++) {
        	var v = arr[i].trim();
			if (!this.email(v)) {
            	return false;
			}
        }

        return true;
    },
	email_listText: "Indirizzo di posta non valido.",
	email_listMask: /[a-z0-9_\.\-@; ]/i

});
/*----------------------------------------
 * Validazione per lista di cellulari 
 *---------------------------------------*/
var validazione = 
Ext.apply(Ext.form.VTypes, {
	cell_list : function(val, field) {
        var arr = val.split(",");
		
		for (var i = 0; i < arr.length; i++) {
			var v = arr[i].trim();	
			if(!(/^[(3{1})|(393{1})]+([0-9]){8,10}$/.test(v))) 
				return false;
			if (v.length < 9 || v.length > 13){
				return false;
			}	
		}
      
        return true;
    },
	cell_listText: "Numero Cellulare non valido.",
	cell_listMask: /[\d\,]/i



});


function showPraticaDetail(idContratto,numPratica,idCliente,cliente,telefoni,listStore,rowIndex,isStorico) {

	if(listStore!=null && listStore!=undefined)
	{
		var rec = listStore.getAt(rowIndex);
		var form = rec.get("FormDettaglio");
		if (!(form>""))
			form = "DCS_dettagliopratica";
		showPratica(idContratto,numPratica,idCliente,cliente,telefoni,listStore,rowIndex,form,isStorico);
	}
	else
	{
		var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
		var sql = "SELECT FormDettaglio,codContratto AS numPratica,c.idCliente," +
				  "ifnull(Nominativo,RagioneSociale) AS cliente, cl.Telefono " +
				  "FROM "+schema+".contratto c " +
				  "JOIN "+schema+".cliente cl on c.IdCliente=cl.IdCliente " +
				  "JOIN prodotto p on c.IdProdotto=p.IdProdotto " +
				  "JOIN tipodettaglio t on t.IdTipoDettaglio = p.IdTipoDettaglio " +
				  "WHERE c.IdContratto=" + idContratto;
		Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: sql
				},
			    success: function(response, opts) {
					if (response.responseText>'')
						eval('var resp = '+response.responseText);
					else
						var resp = {error:'dati non disponibili'};
					if (resp.error>'' || !resp.results || !resp.results.length)
						Ext.Msg.alert("Errore",response.responseText.error);
					else {				
						var row = resp.results[0];
						form = row.FormDettaglio;
						if (!(form>""))
							form = "DCS_dettagliopratica";
						showPratica(idContratto,row.numPratica,row.idCliente,row.cliente,row.Telefono,null,-1,form,isStorico);
					}
			   },
			   failure: function(response, opts) {
					Ext.Msg.alert("Errore","Impossibile aprire la pagina di dettaglio della pratica.");
			   }
			});
	}	
}

//--------------------------------------------------------------------
// Visualizza griglia per le rate insolute del dipendente selezionato
//--------------------------------------------------------------------
function showGrigliaRate(IdContratto,NomeDip,isStorico)
{
	DCS.showMask();
	var pnl = new DCS.ListInsDip.create(IdContratto,isStorico);
	var titleWin='Lista rate insolute per il dipendente \''+NomeDip+'\'';
	var winDipList = new Ext.Window({
		width: 1000, height:500, minWidth: 700, minHeight: 300,
		autoHeight:false,
		modal: true,
	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    title: titleWin,
		constrain: true,
		items: [pnl]
    });
	Ext.apply(pnl,{winList:winDipList});
	winDipList.show();
	pnl.activation.call(pnl);
}
//--------------------------------------------------------
// Visualizza dettaglio pratiche
//--------------------------------------------------------
function showPratica(idContratto,numPratica,idCliente,cliente,telefoni,listStore,rowIndex,FormDettaglio,isStorico) {

	var win;
	var myMask = new Ext.LoadMask(Ext.getBody(), {
		msg: "Lettura dettaglio..."
	});
	myMask.show();

	var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
	if (telefoni == undefined || telefoni == '') {
		var sql = "SELECT * FROM "+schema+".v_lista_telefoni WHERE IdCliente=" + idCliente;
		
		var dsNumTel = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),
			/*2. specify the reader*/
			reader: new Ext.data.JsonReader({
				root: 'results',//name of the property that is container for an Array of row objects
				id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
			}, [{
				name: 'IdCliente'
			}, {
				name: 'telefoni'
			}])
		});
		
		dsNumTel.load({
			params: {
				task: 'read',
				sql: sql
			},
			callback: function(rec, opt, success){
				if (success) {
					var winTitle = 'Dettaglio pratica - ' + numPratica + ' ' + cliente;
					if (dsNumTel.getCount() > 0) {
						var MyTel = dsNumTel.getAt(0);
						winTitle += ' &nbsp;&nbsp;&nbsp;<img src="images/telefono.png" ' +
							'align="absbottom"><b>&nbsp;' + MyTel.data.telefoni + '</b>';
					}
					
					
					var nameNW = 'dettaglio'+numPratica;

					//console.log("(no telefono) oldWind BC "+oldWind);
					if (oldWind != '')
					{
						win = Ext.getCmp(oldWind);
						//console.log("(no telefono) chiusa "+win.id);
						if (win) win.close();
					}
						
					oldWind = nameNW;
					//console.log("(no telefono) oldWind AC "+oldWind);
					//console.log("(no telefono) nuova win "+nameNW);
					win = new Ext.Window({
						width: 860,
						height: 660,
						minWidth: 860,
						minHeight: 660,
						layout: 'fit',
						id:'dettaglio'+numPratica,
						stateful:false,
						plain: true,
						bodyStyle: 'padding:5px;',
						modal: true,
						title: winTitle,
						constrain: true,
						items: [{
								xtype: FormDettaglio,//'DCS_dettagliopratica',
								idContratto: idContratto,
								numPratica: numPratica,
								cliente: idCliente,
								listStore: listStore,
								isStorico: isStorico,
								rowIndex: rowIndex}]
					});
					win.show();
					win.on({
						'close' : function () {
								oldWind = '';
								//console.log("(no telefono) oldWind canc "+oldWind);
								//console.log("(no telefono) Cancellazione/chiusura win"+win.id);
								}
				    });				
					myMask.hide();
				}			
			}	// fine callback
		}); // fine load
	} else {
		var winTitle = 'Dettaglio pratica - ' + numPratica + ' ' + cliente + ' ' + 
						'&nbsp;&nbsp;&nbsp;<img src="images/telefono.png" align="absbottom">' +
						'<b>&nbsp;' + telefoni + '</b>';


		var nameNW = 'dettaglio'+numPratica;

		if (oldWind != '') {
			win = Ext.getCmp(oldWind);
			win.close();
		}
		oldWind = nameNW;
		win = new Ext.Window({
			width: 860,
			height: 660,
			minWidth: 860,
			minHeight: 660,
			layout: 'fit',
			id:'dettaglio'+numPratica,
			stateful:false,
			plain: true,
			bodyStyle: 'padding:5px;',
			modal: true,
			title: winTitle,
			constrain: true,
			items: [{
				xtype: FormDettaglio,//'DCS_dettagliopratica',
				idContratto: idContratto,
				numPratica: numPratica,
				cliente: idCliente,
				listStore: listStore,
				isStorico: isStorico,
				rowIndex: rowIndex}]
		});
		win.show();
		win.on({
			'close' : function () {
					oldWind = '';
				}
	    });
		myMask.hide();
	}
} // fine funzione showPraticaDetail

//--------------------------------------------------------
// Visualizza lista note non lette (usata in pUtente.php)
// Obsoleta: sostituita dalla successiva
//--------------------------------------------------------
function showPraticheConNoteNonLette()
{
	var pnl = new DCS.pnlSearch({IdC: 'NoteNonLette'});
	var win = new Ext.Window({
		width: 1100, height:700, 
		autoHeight:true,modal: true,
	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    title: 'Lista pratiche con nuove note non ancora lette',
		constrain: true,
		items: [pnl]
  });
	win.show();
	pnl.activation.call(pnl);
}

//--------------------------------------------------------
// Posizione l'utente sulle comunicazioni non lette
// (usata in pUtente.php)
//--------------------------------------------------------
function showMessaggiNonLetti()
{
	//var menu = Ext.getCmp('navigatore');
	var btn  = Ext.getCmp('voceMenuComunicazioni');
	
	if (DCS.menu_insoluti.collapsed) 
		DCS.menu_insoluti.expand(true); // espande il macro men�
	btn.toggle(true);
	// il penultimo tab della pagina Comunicazioni � quello dei messaggi non letti
	if (btn.panelCmp && btn.panelCmp.items.length>1)
		btn.panelCmp.setActiveTab(btn.panelCmp.items.length-2);
}

//--------------------------------------------------------
//Visualizza lista pratiche in workflow (usata in pUtente.php)
//--------------------------------------------------------
function showPraticheWorkflow()
{
	var pnl = new DCS.pnlSearch({IdC: 'Wrkflow'});
	var win = new Ext.Window({
		width: 1100, height:700, 
		autoHeight:true,modal: true,
	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    title: 'Lista pratiche in worflow alla sua attenzione',
		constrain: true,
		items: [pnl]
});
	win.show();
	pnl.activation.call(pnl);
}

//--------------------------------------------------------
//sostituisce tutti i caratteri speciali da &xgrave 
//al corrispettivo
//--------------------------------------------------------
function replace_Tospecial_chars(value)
{
	string = new Array('&ograve;','&oacute;','&agrave;','&aacute;','&atilde;','&aelig;','&Oslash;','&ccedil;','&Ccedil;','&iacute;','&igrave;','&eacute;','&egrave;','&iuml;','&ugrave;','&uacute;');
	replace = new Array('�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�');
	if(value!=null && value!=''){
		for(var j=0; j<string.length;j++){
			value = value.replace(string[j],replace[j]);
		}
	}
	return value;
}

//--------------------------------------------------------
// inserisce un asterisco accanto la label del campo 
// se obbligatorio
//--------------------------------------------------------
function labObb(flagRO) 
{
	return flagRO?':':': <span style="color:red;">*</span>';
}

//--------------------------------------------------------
// Funzione richiamata alla pressione dei pulsanti di help
//--------------------------------------------------------
function helpFunc(fileName)
{
	/* Usa una Ajax.request per controllare se la pagina esiste */
    parti = fileName.split("#"); // divide l'anchor HTML se c'�
    if (parti.length==2)
    {
    	fileName = parti[0];
    	anchor   = parti[1];
    }
    else
    	anchor   = "";
	Ext.Ajax.request({
		   url: 'doc/'+fileName+'.html',
		   success: function(response, opts) {
				window.open('doc/'+fileName+'.html#'+anchor, "Guida per l'utente",
						'height=600,width=900,menubar=no,resizable=yes,status=no,toolbar=no');	
		   },
		   failure: function(response, opts) {
				Ext.Msg.alert("","La pagina del Manuale Utente \""+ fileName + ".html\" non &egrave; ancora disponibile");
		   }
		});
}

//-----------------------------------------------------------
// Funzione che crea il pulsante di help sulle toolbar
//-----------------------------------------------------------
function helpButton(fileName)
{
   var button =  {text: '', tooltip: 'Manuale utente', iconCls:'help_it', handler: function() {helpFunc(fileName);} };
   return button;
}
//-----------------------------------------------------------
// Funzione che crea il pulsante di help per pannelli senza
// toolbar (il risultato va messo nella propriet� .tools) 
//-----------------------------------------------------------
function helpTool(fileName)
{
   var button =  {id: 'help', qtip: 'Manuale utente', handler: function() {helpFunc(fileName);}};
   return button;
}

//-----------------------------------------------------------------------
// getFormItems
// Costruisce una array associativo contenente chiavi e valori dei campi
// del form dato
//-----------------------------------------------------------------------
function getFormItems(form)
{
	var values = {}; // array associativo in js = object 

	 var getFieldValue =
		 function(field) {
		   	if (field.items == undefined) // campo semplice
		   	{
		   		if (typeof field.getSubmitValue == "function")
		   			var val = field.getSubmitValue();
		   		else if (typeof field.getValue == "function")
		   			var val = field.getValue();
		   		else
		   			return;
		          
		        var fldName = field.hiddenName>''?field.hiddenName:field.name;
		        if (fldName>'')
		        	values[fldName] = val;
		   	} else { // elemento contenente altri items
		         field.items.each(getFieldValue);	 	  
		    }
	     };

	 form.items.each(getFieldValue); // loop sui campi del form
	 return values;
}

//---------------------------------------------------------------------------
// setFormItems
// Riempie i campi di un forms con i valori contenuti in un array associativo
//---------------------------------------------------------------------------
function setFormItems(form,valueArray)
{
	 var setFieldValue =
		 function(field) {
		   	if (field.items == undefined) // campo semplice
		   	{
		        var fldName = field.hiddenName>''?field.hiddenName:field.name;
		        if (field.xtype=='datefield')
		        { // date salvate in formato internet yyyy-mm-ddThh:mm:ss
		        	field.setValue(valueArray[fldName].substring(0,10));
		        }
		        // Att.ne: le combo ed extendedComboBox possono essere valorizzate solo se sono precaricate
		        // (cio� con lazyInit: false)
		        else
		        	field.setValue(valueArray[fldName]);
		   	}
		   	else // campo complesso
		   	{
		         field.items.each(setFieldValue);	 	  
		    }
	     };

	 try
	 {
		 form.items.each(setFieldValue); // loop sui campi del form
	 }
	 catch (err)
	 {
		 console.log(err);
	 }
	 return;
}

//-----------------------------------------------------------------------
// saveFormItems
// Salva nella tabella uistate i valori dei campi di un form
//-----------------------------------------------------------------------
function saveFormItems(form,formName)
{
	var values = getFormItems(form); // ottiene array associativo

	Ext.Ajax.request({
		url: 'server/stateProvider.php',
		method: 'POST',
		params: {
			user: CONTEXT.IdUtente,
			cmd: 'saveState',
			// NB in uistate le entry che cominciano con * non vengono lette dallo stateProvider standard (le usiamo per scopi vari)
			data: Ext.encode(new Array({name:'*'+formName, value: Ext.encode(values)}))
		},
 	    waitMsg: 'Registrazione in corso...',
	    success: function(response, opts) {
			eval('var resp = '+response.responseText);
			if (resp.success)
				Ext.Msg.alert("","Registrazione eseguita");
			else
				Ext.Msg.alert("Errore","Registrazione non riuscita");
	   },
	   failure: function(response, opts) {
			Ext.Msg.alert("Errore","Registrazione non riuscita");
	   }
	});
}

//-----------------------------------------------------------------------
// restoreFormItems
// Ripristina dalla tabella uistate i valori dei campi di un form
//-----------------------------------------------------------------------
function restoreFormItems(form,formName)
{
	Ext.Ajax.request({
		url: 'server/stateProvider.php',
		method: 'POST',
		params: {
			user: CONTEXT.IdUtente,
			cmd: 'readOneState',
			stateId: '*'+formName
		},
	    waitMsg: 'Ripristino dati salvati...',
	    success: function(response, opts) {
			eval('var resp = '+response.responseText);
			if (resp.success && resp.data>'')
			{
				setFormItems(form,Ext.decode(resp.data));
			}
			else
				console.log("Ripristino campi non riuscito");
	   },
	   failure: function(response, opts) {
			console.log("Ripristino campi non riuscito");
	   }
	});
}

//-----------------------------------------------------------------------
// resetFormItems
// Resetta tutti i campi di un form
//-----------------------------------------------------------------------
function resetFormItems(form)
{
	 var resetField =
		 function(field) {
		   	if (field.items == undefined) // campo semplice
		   	{
		   		// per le extendedComboBox, riseleziona tutti gli elementi (poi fa pure reset per vuotare il campo visibile)
		   		if (field.xtype=='extendedComboBox')
		   			field.selectAll();
		   		if (typeof field.reset == "function") // ammette una funzione reset
		   			field.reset();
		   		else // non ammette una funzione reset
		   			if (typeof field.setValue == "function") // ammette una funzione setValue
		   				field.setValue(null);
		   	}
		   	else // campo complesso
		   	{
		         field.items.each(resetField);	 	  
		    }
	     };

	 form.items.each(resetField); // loop sui campi del form
}

//------------------------------------------------------------------------------
// Costruisce un container per due colonne di data validit� da/a
// con nomi "DataIni" e "DataFin"
//------------------------------------------------------------------------------
function validityDatesInColumns(labelWidth)
{
	return {
	xtype:'container', layout:'column', columnWidth:1, // columnwidth=100% casomai stesse in un layout columns
	items:[{
		xtype:'panel', layout:'form', labelWidth:labelWidth, columnWidth:.50, 
		defaults: {anchor:'98%', readOnly:false},
		items: [{
			xtype: 'datefield',
			format: 'd/m/Y',
			width: 120,
			autoHeight:true,
			allowBlank: false,
			fieldLabel: 'Inizio validit&agrave;',
			name: 'DataIni',
			id:'DataIni'
		}]
	},{
		xtype:'panel', layout:'form', labelWidth:90,columnWidth:.50, 
		defaults: {anchor:'98%', readOnly:false},
		items: [{
			xtype: 'datefield',
			format: 'd/m/Y',
			width: 120,
			autoHeight:true,
			allowBlank: false,
			fieldLabel: 'Fine validit&agrave;',
			name: 'DataFin',
			id:'DataFin'
		}]
	}]
	};
}

//--------------------------------------------------------
// IMPOSTA LA VISIBILITA' DELLE COLONNE DELLA GRID SECONDO LE CONFIGURAZIONI
//--------------------------------------------------------
function setColumnVisibility(columns)
{
	var tmpColumns = columns;
	if(arrayHiddenColumns.length>0)
	{	
		for(var i=0; i<arrayHiddenColumns.length;i++){
			columns = tmpColumns;
			tmpColumns=[];
			for(var j=0; j<columns.length;j++){
				var col = columns[j];
				if(columns[j].dataIndex != arrayHiddenColumns[i])
					tmpColumns.push(columns[j]);
			}
		}
	}	
	return tmpColumns;
}

//--------------------------------------------------------
//GetPraticheCorrenti
//--------------------------------------------------------
function getPraticheCorrenti()
{
	if(PraticheCorrPerFamiglia)
	{	
		var sql = "select CodFamiglia, IdFamiglia, TitoloFamiglia from famigliaprodotto where IdFamigliaParent is null and now() between DataIni AND DataFin and IdCompagnia = "+ CONTEXT.idCompagnia +" order by Ordine asc;";
		
		var tp = new Ext.TabPanel({
			activeTab: 0,
			enableTabScroll: true,
			flex: 1,
			items: []
		});	
		
		
		Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: sql
				},
			    success: function(response, opts) {
					eval('var resp = ('+response.responseText+').results');
					if(resp)
					{	
						for(var i=0; i<resp.length;i++)
						{
							var tpx = DCS.PraticheCorrenti.create("and IdFamiglia =" + resp[i].IdFamiglia,resp[i].TitoloFamiglia);
							tp.add(tpx);
							tpx.setActiveTab(0);
						}
						tp.setActiveTab(0);
					}	
					else
					{	
						Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
					}	
			   },
			   failure: function(response, opts) {
					Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
			   }
			});
		return tp;
	}
	else
	{
		return DCS.PraticheCorrenti.create("","");
	}	
}


//--------------------------------------------------------
//getPraticheStrLeg
//--------------------------------------------------------
function getPraticheStrLeg(type)
{
	if(PraticheCorrPerFamiglia)
	{	
		var sql = "select CodFamiglia, IdFamiglia, TitoloFamiglia from famigliaprodotto where IdFamigliaParent is null and now() between DataIni AND DataFin and IdCompagnia = "+ CONTEXT.idCompagnia +" order by Ordine asc;";
		
		var tp = new Ext.TabPanel({
			activeTab: 0,
			enableTabScroll: true,
			flex: 1,
			items: []
		});	
		
		
		Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: sql
				},
			    success: function(response, opts) {
					eval('var resp = ('+response.responseText+').results');
					if(resp)
					{	
						for(var i=0; i<resp.length;i++)
						{
							var tpx =  DCS.PraticheStrLeg.create(type,"and IdFamiglia =" + resp[i].IdFamiglia,resp[i].TitoloFamiglia);
							tp.add(tpx);
							tpx.setActiveTab(0);
						}
						tp.setActiveTab(0);
					}	
					else
					{	
						Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
					}	
			   },
			   failure: function(response, opts) {
					Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
			   }
			});
		return tp;
	}
	else
	{
		return DCS.PraticheStrLeg.create(type,"","");
	}	
}


//--------------------------------------------------------
//getScadenzarioSTR
//--------------------------------------------------------
function getScadenzarioSTR(type)
{
	if(PraticheCorrPerFamiglia)
	{	
		var sql = "select CodFamiglia, IdFamiglia, TitoloFamiglia from famigliaprodotto where IdFamigliaParent is null and now() between DataIni AND DataFin and IdCompagnia = "+ CONTEXT.idCompagnia +" order by Ordine asc;";
		
		var tp = new Ext.TabPanel({
			activeTab: 0,
			enableTabScroll: true,
			flex: 1,
			items: []
		});	
		
		
		Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: sql
				},
			    success: function(response, opts) {
					eval('var resp = ('+response.responseText+').results');
					if(resp)
					{	
						for(var i=0; i<resp.length;i++)
						{
							var tpx =  DCS.ScadenzarioSTR.create(type,"and IdFamiglia =" + resp[i].IdFamiglia,resp[i].TitoloFamiglia);
							tp.add(tpx);
							tpx.setActiveTab(0);
						}
						tp.setActiveTab(0);
					}	
					else
					{	
						Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
					}	
			   },
			   failure: function(response, opts) {
					Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
			   }
			});
		return tp;
	}
	else
	{
		return DCS.ScadenzarioSTR.create(type,"","");
	}	
}


//--------------------------------------------------------
//getScadenzarioDBT
//--------------------------------------------------------
function getScadenzarioDBT()
{
	if(PraticheCorrPerFamiglia)
	{	
		var sql = "select CodFamiglia, IdFamiglia, TitoloFamiglia from famigliaprodotto where IdFamigliaParent is null and now() between DataIni AND DataFin and IdCompagnia = "+ CONTEXT.idCompagnia +" order by Ordine asc;";
		
		var tp = new Ext.TabPanel({
			activeTab: 0,
			enableTabScroll: true,
			flex: 1,
			items: []
		});	
		
		
		Ext.Ajax.request({
				url: 'server/AjaxRequest.php',
				params: {
					task: 'read',
					sql: sql
				},
			    success: function(response, opts) {
					eval('var resp = ('+response.responseText+').results');
					if(resp)
					{	
						for(var i=0; i<resp.length;i++)
						{
							var tpx =  DCS.Comunicazioni.createScadenzario("and IdFamiglia =" + resp[i].IdFamiglia,resp[i].TitoloFamiglia);
							tp.add(tpx);
							tpx.setActiveTab(0);
						}
						tp.setActiveTab(0);
					}	
					else
					{	
						Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
					}	
			   },
			   failure: function(response, opts) {
					Ext.Msg.alert("Errore","Errore durante il caricamento delle liste.");
			   }
			});
		return tp;
	}
	else
	{
		return DCS.Comunicazioni.createScadenzario("","");
	}	
}

