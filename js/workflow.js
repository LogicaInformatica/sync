//================================================================
// Funzioni per il Workflow
//================================================================

//----------------------------------------------------------------
// eseguiAzione
// Funzione chiamata dal click sulle azioni del menù azioni
// Argomenti: elemento del menù (menuitem), che contiene nella
//            proprietà "data" un array con tre elementi:
//            1. idStatoAzione (oppure CodAzione)
//            2. array degli id contratto
//            3. gridPanel sottostante
//            e nella proprietà "text" il titolo dell'azione
//----------------------------------------------------------------
function eseguiAzione(item) 
{
	var array    = item.data;
    var idStatoAzione = array[0];
	var idContratti  = array[1]; // array
	var idGrid  = array[2]; // array
	var tit_parts = item.text.split(" - ");
	var titolo = 'Azione: ';
	if (tit_parts.length==1) {
		titolo += item.text;
	} else {
		tit_parts.shift();
		titolo += tit_parts.join(" - ");
	}
	
	eseguiAzioneBase(idStatoAzione,idContratti,titolo,idGrid);
}

//----------------------------------------------------------------
// eseguiAzioneBase
// Come la precedente, con i parametri passati singolarmente
// Nota: in idstatoazione può essere passato anche un CodAzione (lo traduce poi la generaFormAzione)
//----------------------------------------------------------------
function eseguiAzioneBase(idStatoAzione,idContratti,titolo,idGrid,data) 
{
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Qualche istante, prego..."});
	myMask.show();

	var isStorico = 'N';
	var mainp = Ext.getCmp('mainPanel'); 
	if (mainp.findById('tabStorico') && !mainp.findById('tabStorico').hidden)
		isStorico = 'Y';

	Ext.Ajax.request({
        url: 'server/generaFormAzione.php', method:'POST',
        // Nota: in idstatoazione può essere passato anche un CodAzione (lo traduce poi la generaFormAzione)
		params: {idstatoazione: idStatoAzione, idcontratti: Ext.encode(idContratti), idGrid:idGrid,
					ArrayDati:Ext.encode(data), isStorico:isStorico},
		failure: function() {Ext.Msg.alert("Impossibile aprire la pagina di dettaglio dell'azione", "Errore Ajax");},
        success: function(req)
        {
			var formPanel;
			try {
				// Caso mai fosse racchiuso tra "script" per comodità nel costruire il formAzioneXXX.php, li toglie
				var resp = req.responseText.replace(/^<script>/,'').replace(/<\/script>\s*$/,'');
	            eval(resp); // esegue il javascript, che contiene la definizione del form o simile
			} catch(e) {
                myMask.hide();
	            Ext.Msg.alert("Errore","La definizione del pannello non &egrave; corretta: "+e);
            	return;
			}
			if (formPanel!=undefined) {	// se costruito un form
	            var win = new Ext.Window({
	                width: formPanel.width+30, height:formPanel.height+30, 
	                minWidth: formPanel.width+30, minHeight: formPanel.height+30,
	                layout: 'fit', plain:true, bodyStyle:'padding:5px;',modal: true,
	                title:  titolo,
					constrain: true,
					modal: true,
					closable: true, // cambiato da false il 13/5/2016 perché altrimenti non si chiudono i messaggi
					// mandati con Ext.BoxComponent (vedi ad es. formAzioneNonSupportata)
	                items: formPanel,
	                tools:[helpTool(titolo)]
	                });
	            win.show();
	            if (formPanel.messaggioAvviso>'')
	            	Ext.Msg.alert("Attenzione",formPanel.messaggioAvviso);
			}
            myMask.hide();
        } // fine corpo funzione Ajax.success
     } // fine corpo richiesta Ajax
     ) // fine parametri Ajax.request
}

//----------------------------------------------------------------
// vediManuale
// Funzione chiamata dal click sulle azioni del menù procedure
//          che corrispondono ad un manuale d'uso (norma)
// Argomenti: (nell'elemento "data" dell'item corrente)
//            filename (senza path: viene cercato nella links)
//----------------------------------------------------------------
function vediManuale(item) 
{
	window.open("links/"+item.data);
}

//----------------------------------------------------------------
// quitForm
// Funzione richiamata al tasto annulla di ogni form "azione"
//----------------------------------------------------------------
function quitForm(formPanel,win)
{
    DCS.hideMask();
	if (formPanel.getForm().isDirty()) 
	{
		Ext.Msg.confirm('', 'Si vuole uscire senza salvare le modifiche effettuate?', 
				function(btn, text) {if (btn == 'yes') win.close();	});
	} 
	else 
		win.close();
}

//----------------------------------------------------------------
// saveSuccess
// Funzione chiamata nel parametro success dei form azione
//----------------------------------------------------------------
function saveSuccess(win,form,action,noOKmsg) 
{
	try
	{
		if (action.success) // impostato dal programma = true
		{
			DCS.hideMask();
			win.close();
			if (noOKmsg!=true) // vuole messaggio di completamento
			{
				Ext.MessageBox.show({
				   title: "Esito azione",
				   msg: action.result.msg,
				   buttons: Ext.Msg.OK //,				   icon: Ext.Msg.INFO
				});
			}
		}
		else 
			Ext.Msg.alert('Registrazione non riuscita',action.result.msg);
	}
	catch (e)
	{
		Ext.Msg.alert('Registrazione non riuscita',e);
	}
}
//----------------------------------------------------------------
//saveFailure
//Funzione chiamata nel parametro failure dei form azione
//----------------------------------------------------------------
function saveFailure(form,action) 
{
	try
	{
		DCS.hideMask();
		if (!form.isValid())
			Ext.Msg.alert('Registrazione non riuscita','Controlla che non siano stati immessi valori non validi nei campi di input');
		else if (action.result)
			Ext.Msg.alert('Registrazione non riuscita',action.result.msg || action.result.error);
		else
			Ext.Msg.alert('','Registrazione non riuscita');
	}
	catch (e)
	{
		Ext.Msg.alert('Registrazione non riuscita',e);
	}
} 

//
function calcoloRata(m,n,i,k)
{
  m = parseInt(m); //mutuo
  n = parseInt(n); //annualità
  i = parseFloat(i)/100; //dal tasso percentuale annuo a quello unitario
  k = parseInt(k); // periodizzazione
  ikpiu1 = Math.pow(1+i,1/k) // radice k-esima di (1+i)
  ik = ikpiu1-1; // tasso periodico
  rata = m*ik/(1-Math.pow(ikpiu1,-k*n)); // implementazione della formula
  return Math.round(rata*100)/100; // arrotondamento al centesimo
}
