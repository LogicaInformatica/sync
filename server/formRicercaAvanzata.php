<?php
require_once("common.php"); 
global $context;
//----------------------------------------------------------------------------------------
// form da cui si avvia la ricerca avanzata: due versioni, per utente interno e per
// utente esterno
//----------------------------------------------------------------------------------------
if ($context["InternoEsterno"]=='I')
{
	// Rilegge i dati da restorare (subito, per sapere quali combobox definire con lazyInit: false)
	$sql = "SELECT value FROM uistate WHERE IdUtente=".$context['IdUtente']." AND StateId='*RicercaAvanzata'";
	$value = getScalar($sql);
	if ($value>'')
		$restored = json_decode($value,true); // legge come array associativo
	else
		$restored = array();
	// Genera le combo extended necessarie
	$comboTipiContratto 	= generaCombo("Tipo contratto","CodTipoContratto","TitoloTipoContratto","FROM v_tipo_contratto","",true,true
									      ,$restored["CodTipoContratto"]>""); // preload se valore da restorare
	//$comboTipoCliente 	= generaCombo("Tipo forma giuridica","IdTipoCliente","TitoloTipoCliente","FROM v_tipo_cliente","",true,true);
	$comboStatoContratto 	= generaCombo("Stato del contratto","IdStatoContratto","TitoloStatoContratto","FROM v_stato_contratto","",true,true
									      ,$restored["IdStatoContratto"]>"");
	$comboStatoRecupero 	= generaCombo("Stato recupero","IdStatoRecupero","TitoloStatoRecupero","FROM v_stato_recupero","",true,true
									      ,$restored["CodTipoContratto"]>"");
	$comboTipoPagamento 	= generaCombo("Tipo pagamento","IdTipoPagamento","TitoloTipoPagamento","FROM v_tipo_pagamento","",true,true
									      ,$restored["IdStatoRecupero"]>"");
	$comboAttributo 		= generaCombo("Attributo","IdAttributo","TitoloAttributo","FROM v_attributo","",true,true
									      ,$restored["IdAttributo"]>"");
	$comboClassificazione	= generaCombo("Classificazione","IdClasse","TitoloClasse","FROM v_tipo_classe","",true,true
									      ,$restored["IdClasse"]>"");
	$comboCategoria			= generaCombo("Categoria lav. int.","IdCategoria","TitoloCategoria","FROM v_categoria","",true,true
									      ,$restored["IdCategoria"]>"");
	$comboArea				= generaCombo("Area residenza","IdArea","TitoloArea","FROM v_tipo_area","",true,true
									      ,$restored["IdArea"]>"");
	$comboFiliale			= generaCombo("Filiale","IdFiliale","TitoloFiliale","FROM v_filiale","",true,true
									      ,$restored["IdFiliale"]>"");
	$comboDealer			= generaCombo("Dealer","IdCompagnia","TitoloCompagnia","FROM v_dealer","",true,true
									      ,$restored["IdCompagnia"]>"");
	$comboProdotto			= generaCombo("Tipo di prodotto","IdProdotto","TitoloProdotto","FROM v_tipo_prodotto","",true,true
									      ,$restored["IdProdotto"]>"");
	$comboAgenzia			= generaCombo("Agenzia affidataria","IdRegolaProvvigione","TitoloAgenzia","FROM v_agenzia_aff","",true,true
									      ,$restored["IdRegolaProvvigione"]>"");
	$comboOperatore			= generaCombo("Operatore assegnato","IdUtente","NomeUtente","FROM v_operatore","",true,true
									      ,$restored["IdUtente"]>"");
?>

// Campi del form    
var space               = {xtype: 'displayfield', height:24};
var fldCodice 			= {xtype:'textfield', fieldLabel: 'Codice contratto', hiddenName: 'fldCodice'};
var fldNome   			= {xtype:'textfield', fieldLabel: 'Nome o rag. sociale', hiddenName: 'fldNome', width: 230};
var fldModello 			= {xtype:'textfield', fieldLabel: 'Modello veicolo', hiddenName: 'fldModello', width: 230};
var fldTarga   			= {xtype:'textfield', fieldLabel: 'Targa', hiddenName: 'fldTarga', width: 230};
var cmbArea			 	= <?php echo $comboArea;?>;
var cmbFiliale			= <?php echo $comboFiliale;?>;
var cmbDealer			= <?php echo $comboDealer;?>;
var cmbProdotto			= <?php echo $comboProdotto;?>;

var cmbTipoContratto 	= <?php echo $comboTipiContratto;?>;
//var cmbTipoCliente   	= <?php echo $comboTipoCliente;?>;
var cmbStatoContratto 	= <?php echo $comboStatoContratto;?>;
var cmbStatoRecupero 	= <?php echo $comboStatoRecupero;?>;
var cmbTipoPagamento 	= <?php echo $comboTipoPagamento;?>;
var cmbAttributo 		= <?php echo $comboAttributo;?>;
var cmbClassificazione  = <?php echo $comboClassificazione;?>;
var cmbCategoria		= <?php echo $comboCategoria;?>;
var cmbAgenzia			= <?php echo $comboAgenzia;?>;
var cmbOperatore		= <?php echo $comboOperatore;?>;

var chkIntestatario     = {xtype: 'checkbox', fieldLabel: "ricerca come", boxLabel: 'Intestatario', name:'chkIntestatario', checked: true};
var chkGarante		    = {xtype: 'checkbox', boxLabel: 'Garante/coobbligato', name:'chkGarante', checked: false};
var compoIntGarante     = {xtype:'compositefield',items:[chkIntestatario,chkGarante],height:24};

var chkFisica   		= {xtype: 'checkbox', fieldLabel: "Tipo soggetto", boxLabel: 'Persona fisica', name:'chkFisica', checked: true};
var chkGiuridica		= {xtype: 'checkbox', boxLabel: 'Persona giuridica', name:'chkGiuridica', checked: true};
var compoFisGiur        = {xtype:'compositefield',items:[chkFisica,chkGiuridica],height:24};


var fldImpFinanziatoDa    = {xtype:'numberfield', fieldLabel: 'Importo finanziato tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpFinanziatoDa'};
var lblImp               = {xtype:'displayfield', value: ' e'};
var fldImpFinanziatoA    = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpFinanziatoA'};	
var compoFinanz         = {xtype:'compositefield',items:[fldImpFinanziatoDa,lblImp,fldImpFinanziatoA]};

var fldImpDebitoDa    	= {xtype:'numberfield', fieldLabel: 'Debito impagato tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpDebitoDa'};
var fldImpDebitoA    	= {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpDebitoA'};	
var compoDebito         = {xtype:'compositefield',items:[fldImpDebitoDa,lblImp,fldImpDebitoA]};

var fldImpResiduoDa    	= {xtype:'numberfield', fieldLabel: 'Capitale residuo tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpResiduoDa'};
var fldImpResiduoA    	= {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 2,
									width: 92,
									decimalSeparator: ',',
									name: 'fldImpResiduoA'};	
var compoResiduo        = {xtype:'compositefield',items:[fldImpResiduoDa,lblImp,fldImpResiduoA]};

var dataInizioDa   	   = {xtype: 'datefield',format: 'd/m/Y',allowBlank: true,fieldLabel:'Inizio affido dal ',name:'dataInizioDa'};
var lblData            = {xtype: 'displayfield', value: 'al'};
var dataInizioA   	   = {xtype: 'datefield',format: 'd/m/Y',allowBlank: true,name:'dataInizioA'};
var compoDataIni       = {xtype: 'compositefield',items:[dataInizioDa,lblData,dataInizioA]};

var dataFineDa   	   = {xtype: 'datefield',format: 'd/m/Y',allowBlank: true,fieldLabel:'Fine affido dal ',name:'dataFineDa'};
var dataFineA   	   = {xtype: 'datefield',format: 'd/m/Y',allowBlank: true,name:'dataFineA'};
var compoDataFin       = {xtype: 'compositefield',items:[dataFineDa,lblData,dataFineA]};

var percDebitoDa      = {xtype:'numberfield', fieldLabel: 'Percentuale debito tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldPercDebitoDa'};
var percDebitoA       = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldPercDebitoA'};
var compoPercDeb      = {xtype:'compositefield',items:[percDebitoDa,lblImp,percDebitoA]};
									
var numInsolutiDa      = {xtype:'numberfield', fieldLabel: 'Numero insoluti tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumInsolutiDa'};
var numInsolutiA       = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumInsolutiA'};
var compoNumIns        = {xtype:'compositefield',items:[numInsolutiDa,lblImp,numInsolutiA]};

var numRateDa      = {xtype:'numberfield', fieldLabel: 'Numero rate future tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRateDa'};
var numRateA       = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRateA'};
var compoRate        = {xtype:'compositefield',items:[numRateDa,lblImp,numRateA]};

var numRatePagateDa  = {xtype:'numberfield', fieldLabel: 'Num. rate pagate tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRatePagateDa'};
var numRatePagateA   = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRatePagateA'};
var compoRatePagate  = {xtype:'compositefield',items:[numRatePagateDa,lblImp,numRatePagateA]};
var numRateTotaliDa  = {xtype:'numberfield', fieldLabel: 'Numero rate totali tra',
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRateTotaliDa'};
var numRateTotaliA   = {xtype:'numberfield', 
									allowNegative: false,
									allowBlank: true,
									style: 'text-align:right',
									decimalPrecision: 0,
									width: 92,
									name: 'fldNumRateTotaliA'};
var compoRateTotali  = {xtype:'compositefield',items:[numRateTotaliDa,lblImp,numRateTotaliA]};

// Le due colonne del form
var colonna1 = {xtype: 'container', layout: 'form', columnWidth:.5,     labelStyle: 'text-align:right',
				items: [fldCodice,fldNome,space,fldModello,cmbArea,cmbDealer,space,cmbStatoContratto,cmbTipoPagamento,cmbClassificazione,
						space,cmbAgenzia,space,compoFinanz,compoResiduo,compoNumIns,compoRatePagate,compoDataIni]};
var colonna2 = {xtype: 'container', layout: 'form', columnWidth:.5, 
				items: [cmbTipoContratto,compoIntGarante,compoFisGiur,fldTarga,cmbFiliale,cmbProdotto,space,cmbStatoRecupero,cmbAttributo,cmbCategoria,
						space,cmbOperatore,space,compoDebito,compoPercDeb,compoRate,compoRateTotali,compoDataFin]};
<?php 
} // fine if utente interno
else // utente esterno
{
		// Genera le combo extended necessarie
	$comboTipiContratto 	= generaCombo("Tipo contratto","CodTipoContratto","TitoloTipoContratto","FROM v_tipo_contratto","",true,true);
?>
// Campi del form    
var space               = {xtype: 'displayfield', height:24};
var cmbTipoContratto 	= <?php echo $comboTipiContratto;?>;
var fldCodice 			= {xtype:'textfield', fieldLabel: 'Codice contratto', hiddenName: 'fldCodice'};
var fldNome   			= {xtype:'textfield', fieldLabel: 'Nome o rag. sociale', hiddenName: 'fldNome', width: 230};
var fldModello 			= {xtype:'textfield', fieldLabel: 'Modello veicolo', hiddenName: 'fldModello', width: 230};
var fldTarga   			= {xtype:'textfield', fieldLabel: 'Targa', hiddenName: 'fldTarga', width: 230};
var chkIntestatario     = {xtype: 'checkbox', fieldLabel: "ricerca come", boxLabel: 'Intestatario', name:'chkIntestatario', checked: true};
var chkGarante		    = {xtype: 'checkbox', boxLabel: 'Garante/coobbligato', name:'chkGarante', checked: false};
var compoIntGarante     = {xtype:'compositefield',items:[chkIntestatario,chkGarante],height:24};
var chkFisica   		= {xtype: 'checkbox', fieldLabel: "Tipo soggetto", boxLabel: 'Persona fisica', name:'chkFisica', checked: true};
var chkGiuridica		= {xtype: 'checkbox', boxLabel: 'Persona giuridica', name:'chkGiuridica', checked: true};
var compoFisGiur        = {xtype:'compositefield',items:[chkFisica,chkGiuridica],height:24};

// Le due colonne del form
var colonna1 = {xtype: 'container', layout: 'form', columnWidth:.5,     labelStyle: 'text-align:right',
				items: [fldCodice,fldNome,space,fldModello]};
var colonna2 = {xtype: 'container', layout: 'form', columnWidth:.5, 
				items: [cmbTipoContratto,compoIntGarante,compoFisGiur,fldTarga]};
<?php 
} // fine else utente esterno
?>
//-------------------------------------------------------
// Funzioni 
//-------------------------------------------------------
var resetRicerca = function()    // per il pulsante Reset Ricerca
{
	var form = formPanel.getForm();
	resetFormItems(form); // funzione definita in common.js
};

var salvaRicerca = function() 	// per il pulsante Salva Ricerca
{
	var form = formPanel.getForm();
	saveFormItems(form,'RicercaAvanzata'); // funzione definita in common.js
};

var restoreRicerca = function() 	// after render: ripristina ricerca salvata
{
	var form = formPanel.getForm();
	restoreFormItems(form,'RicercaAvanzata'); // funzione definita in common.js
};


var avviaRicerca = function()    // pulsante Avvia Ricerca
{
	var form = formPanel.getForm();
	// qualche campo modificato
	if (!form.isDirty())
	{
		Ext.Msg.alert('',"Specificare almeno un criterio di ricerca");
		return;
	}
	if (!form.isValid())
	{
		Ext.Msg.alert('',"Correggere i campi segnalati");
		return;
	}
	//------------------------------------------------------------------------
	// Richiama il pannello standard di lista dei risultati, passandogli il
	// necessario per effettuare la ricerca
	//------------------------------------------------------------------------
	//DCS.showMask('',true);
	// Compone il sottotitolo del pannello di dettaglio
	<?php 
    //Controllo se ricerca per rinegoziazione
	$idRic=$_POST['idRic'];
	if ($idRic=='RicRin') {?>
       var pnl = new DCS.pnlSearch({IdC: 'ComplexSearchRin', searchFields: getFormItems(form)});
	<?php } else {?> 
	   var pnl = new DCS.pnlSearch({IdC: 'ComplexSearch', searchFields: getFormItems(form)});
	<?php }?>     
	var win = new Ext.Window({
				width: 1100, height:600, minWidth: 700, minHeight: 500,
    			autoHeight:true,modal: true,
    			layout: 'fit', plain:true, bodyStyle:'padding:5px;',
    			title: 'Risultato della ricerca',
    			constrain: true,items: [pnl]
                });
    win.show();
	pnl.activation.call(pnl);
};
		
// Il form		
var formPanel = new Ext.form.FormPanel({
	xtype: "form", layout: "column",
	frame: true, title: "Specificare i criteri di ricerca e premere 'Avvia ricerca'",
    width: 760,height:(CONTEXT.InternoEsterno=='I'?580:220), labelWidth: 130,
    items: [colonna1,colonna2],
    buttonAlign: 'left', // serve a far funzionare come dovuto il simbolo '->' qui sotto
    listeners: {beforerender:restoreRicerca},
    buttons: [     {text: 'Salva ricerca',handler: salvaRicerca},
    		       {text: 'Reset ricerca',handler: resetRicerca},'->',
    		  	   {text: 'Avvia ricerca',handler: avviaRicerca},
		           {text: 'Annulla',handler: function () {win.close();}}
		     ]  // fine array buttons
});

