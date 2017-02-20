<?php
require_once("server/login.php");
echo "\n<script>",
	"\n var SITE_NAME = '".SITE_NAME."';", // genera variabile JS con il titolo del sito
	"\n var MYSQL_SCHEMA = '".MYSQL_SCHEMA."';", // genera variabile JS con il nome del db
	"\n var error_msg = '".addslashes($_SESSION['error_msg'])."';", // genera variabile JS con l'eventuale messaggio di errore (vedi login.php)
	"\n</script>"; 
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	if (constant("FAVICON")>"")
	{
    	echo "<link rel='icon' href='images/".constant("FAVICON")."'/>";
	}
?>
    <link rel="icon" href="images/<?php echo FAVICON?>" />
    <title><?php echo SITE_NAME;?></title>
	<?php include "css/stylesheets.inc"?>
<script>

	//*****************************************************************************************************************************************************************
	//-------------------------------------------------------INIZIO CONFIGURAZIONE VISIBILITA' PANELLI E COLONNE-------------------------------------------------------
	// 	NB. QUI VENGONO SOLO DICHIARATE LE VARIABILI CHE DETERMINANTO LA VISIBILITA' DEI TAB, LA CONFIGURAZIONE DEVE ESSERE EFFETTUATA NEI SINGOLI SUBMAIN
	//*****************************************************************************************************************************************************************
	
	//array delle colonne da escludere dalle grid. 
	var arrayHiddenColumns=[];
	
	//PANELS CORRENTI 
	var PraCorrentiAttesa   	= true;
	var PraPreRecupero      	= true;
	var PraCorrentiAttive 		= true;
	var PraCorrentiScadenza 	= true;
	var PraCorrentiPositive 	= true;
	var PraCorrentiIncParz  	= true;
	var PraCorrentiNoStart  	= true;
	var PraCorrentiOverride 	= true;

	//PANELS  Stragiudiziale / Legale 
	var PraStrLegAttesaNULL   	= true;
	var PraStrLegAttesaSTR      = true;
	var PraStrLegAttesaLEG 	 	= true;
	var PraStrStep1STR1 		= true;
	var PraStrStep1STR2 		= true;
	var PraStrStep1STR3  		= true;
	var PraStrRepo  			= true;
	var PraLegLEA 				= true;
	var PraLegLOA 				= true;
	var PraStrLegPositive		= true;
	var PraStrLegIncParz		= true;

	//PANELS  Scadenzario pre-DBT
	var ScadScadenzePratiche   	= true;
	var ScadScadenzeGenerali    = true;

	//PANELS  Scadenzario stragiudiziali
	var PraScadenzaSTRSoft		= true;
	var PraScadenzaSTRHard		= true;
	var PraScadenzaSTRStrong	= true;
	var PraScadenzaSTRREPO		= true;
	var PraScadenzaSTRLEG		= true;
	var PraScadenzaSTRLEGScad	= true;
	var PraSaldoStralcio		= true;


	//PANELS  Comunicazioni
	var ComNormaliNonRis		= true;
	var ComRiservate			= true;
	var MsgNonLetti				= true;
	var MsgLetti				= true;

	//PANELS  Sintesi recupero
	var PraticheSintesiStato	= true;
	var PraticheSintesiAgenzia	= true;
	var PraticheSintesiClassi	= true;
	var PraticheSintesiProd		= true;
	var PraticheSintesiLavInt	= true;
	
	//PANELS  Sintesi affidamenti
	var PraticheSintesiLotto	= true;
	var PraticheSintesiAffidoAg	= true;

	//flag di configurazione della visualizzazione delle pratiche correnti divise per famiglia prodotto
	var PraticheCorrPerFamiglia		= false;

	//*****************************************************************************************************************************************************************
	//-------------------------------------------------------FINE CONFIGURAZIONE VISIBILITA' PANELLI E COLONNE-------------------------------------------------------
	//*****************************************************************************************************************************************************************
	

	//--------------------------------------------------------------------
	// Prepara l'oggetto Javascript contenente il contesto
	//--------------------------------------------------------------------
	var CONTEXT = <?php echo json_encode_plus($context);?>;
	
	// Crea una proprietà = true per ogni funzione ammessa dal profilo utente
	for (f in CONTEXT.functions) {
		 eval('CONTEXT.' + CONTEXT.functions[f] + '=true;');
	}
	CONTEXT.sessionExpired  = false;
	//--------------------------------------------------------------------
	// Costanti
	//--------------------------------------------------------------------
	var PAGESIZE = 25; // numero righe per pagina di lista
	var oldWind = ''; //ultima finestra dettaglio
	var LAST_FY_MONTH = <?php echo getSysParm("LAST_FY_MONTH","3");?>;

	//--------------------------------------------------------------------
	// Impostazioni per i controlli di autorizzazione sui grafici
	//--------------------------------------------------------------------
	//CONTEXT.CAN_GRAPH_ALL		impostato mediante profilo standard
	if (!CONTEXT.CAN_GRAPH_ALL) // autorizzazioni ai grafici determinate dalla visibilità sulle agenzie
	{
		<?php 
			$IdUtente = $context["IdUtente"]; 
			$fasceVisibili = fetchValuesArray("SELECT FasciaRecupero FROM v_fasce_visibili WHERE IdUtente=$IdUtente");
			//trace("SELECT FasciaRecupero FROM v_fasce_visibili WHERE IdUtente=$IdUtente",FALSE);
			// Crea proprietà con nome CONTEXT.CAN_GRAPH_INS ecc.
			foreach ($fasceVisibili as $fascia)
			{
				$fascia = str_replace("+","_",str_replace("&deg;","o",str_replace("°","o",str_replace(" ","_",$fascia))));
				echo "CONTEXT.CAN_GRAPH_$fascia = true;\n";
			}
		?> 
	}
	//--------------------------------------------------------------------
	// Costanti che contengono i nomi dei loghi ecc. per Javascript
	//--------------------------------------------------------------------
	CONTEXT.LogoProdotto = '<?php echo LOGO_PRODOTTO;?>';
	CONTEXT.LogoSocieta  = '<?php echo LOGO_SOCIETA;?>';
	CONTEXT.Footer       = '<?php echo FOOTER;?>';
	CONTEXT.TemplateUrl  = '<?php echo LINK_URL."templates/";?>';
	CONTEXT.LinkUrl      = '<?php echo LINK_URL;?>';

</script>
<?php include "js/scripts.inc"?>
<script type="text/javascript" src="js/common.js"></script>
<script type="text/javascript" src="js/edit_global.js"></script>
<script type="text/javascript" src="js/stores.js"></script>

<script type="text/javascript" src="js/NoticeCalendar.js"></script>
<script type="text/javascript" src="js/grid_Note.js"></script> 
<script type="text/javascript" src="js/grid_Nota.js"></script> 
<script type="text/javascript" src="js/grid_NotaMexAzione.js"></script> 

<script type="text/javascript" src="js/editGrid.js"></script>
<script type="text/javascript" src="js/editGrid_Form.js"></script>
<script type="text/javascript" src="js/azioniNote.js"></script>
<script type="text/javascript" src="js/azioni.js"></script>
<script type="text/javascript" src="js/azioniProvvigione.js"></script>

<script type="text/javascript" src="js/tabs_Pratiche.js"></script>
<script type="text/javascript" src="js/grid_Affidamenti.js"></script>
<script type="text/javascript" src="js/grid_AffidamentiRegole.js"></script>
<script type="text/javascript" src="js/grid_Azione.js"></script>
<script type="text/javascript" src="js/grid_ListaInsolutiDipendenti.js"></script>
<script type="text/javascript" src="js/grid_Agenzie.js"></script>
<!--  script type="text/javascript" src="js/grid_Classificazione.js"></script-->
<script type="text/javascript" src="js/grid_FasceAssociate.js"></script>
<script type="text/javascript" src="js/grid_StoriaRecupero.js"></script>
<script type="text/javascript" src="js/grid_PraticaServizi.js"></script>
<script type="text/javascript" src="js/grid_Allegato.js"></script>
<script type="text/javascript" src="js/grid_SmsUtente.js"></script>
<script type="text/javascript" src="js/grid_MailUtente.js"></script>
<script type="text/javascript" src="js/grid_Categoria.js"></script>
<script type="text/javascript" src="js/grid_Workflow.js"></script>
<script type="text/javascript" src="js/grid_Automatismo.js"></script>
<script type="text/javascript" src="js/grid_AutomatismiAzioniWorkflow.js"></script>
<script type="text/javascript" src="js/grid_AutomatismiWFList.js"></script>
<script type="text/javascript" src="js/grid_AzioniWorkflow.js"></script>
<script type="text/javascript" src="js/grid_Assegnazioni.js"></script>
<script type="text/javascript" src="js/grid_RegoleAssegnazioneGen.js"></script>
<script type="text/javascript" src="js/grid_RegoleAssegnateOperatore.js"></script>
<script type="text/javascript" src="js/grid_RegoleClassificazione.js"></script>
<script type="text/javascript" src="js/grid_RegolaRipartizione.js"></script>
<script type="text/javascript" src="js/grid_StatiWorkflow.js"></script>
<script type="text/javascript" src="js/grid_Lottomatica.js"></script>
<script type="text/javascript" src="js/grid_StatoRecupero.js"></script>
<!-- script type="text/javascript" src="js/grid_ProfiloUtenti.js"></script -->
<script type="text/javascript" src="js/grid_TipoModello.js"></script>
<script type="text/javascript" src="js/grid_EmailModel.js"></script>
<script type="text/javascript" src="js/grid_SmsModel.js"></script>
<script type="text/javascript" src="js/grid_LetteraModelTesto.js"></script>
<script type="text/javascript" src="js/grid_LetteraModelWord.js"></script>
<script type="text/javascript" src="js/grid_SubModel.js"></script>
<script type="text/javascript" src="js/grid_Funzione.js"></script>
<script type="text/javascript" src="js/tabs_PraticheInWorkflow.js"></script>
<script type="text/javascript" src="js/tabs_PraticheLavorazioneInterna.js"></script>
<script type="text/javascript" src="js/tabs_PraticheEstinte.js"></script>
<script type="text/javascript" src="js/tabs_PraticheStorico.js"></script>

<script type="text/javascript" src="js/grid_ViewComNote.js"></script>
<script type="text/javascript" src="js/grid_ProcessiAutomatici.js"></script>
<script type="text/javascript" src="js/grid_AutomatismoProcessi.js"></script>
<script type="text/javascript" src="js/grid_GestioneParametriSistema.js"></script>
<script type="text/javascript" src="js/tabs_Experian.js"></script>
<script type="text/javascript" src="js/tabs_Charts.js"></script>
<script type="text/javascript" src="js/tabs_Comunicazioni.js"></script>
<script type="text/javascript" src="js/tabs_Distinte.js"></script>
<script type="text/javascript" src="js/tabs_ErrMsgImportedFiles.js"></script>
<script type="text/javascript" src="js/tabs_Incassi.js"></script>
<script type="text/javascript" src="js/tabs_IncassiValori.js"></script>
<script type="text/javascript" src="js/tabs_ImportedFiles.js"></script>
<script type="text/javascript" src="js/tabs_Log.js"></script>
<script type="text/javascript" src="js/tabs_MessaggiDifferiti.js"></script>
<script type="text/javascript" src="js/tabs_MexNote.js"></script>
<script type="text/javascript" src="js/tabs_PraticheCorrenti.js"></script>
<script type="text/javascript" src="js/tabs_ScadenzarioSTR.js"></script>
<script type="text/javascript" src="js/tabs_PianoRientro.js"></script>
<script type="text/javascript" src="js/tabs_PraticheStrLeg.js"></script>
<script type="text/javascript" src="js/tabs_PraticheCessioniWO.js"></script>
<script type="text/javascript" src="js/tabs_PraticheAgenzia.js"></script>
<script type="text/javascript" src="js/tabs_PraticheAgenziaSintesi.js"></script>
<script type="text/javascript" src="js/tabs_PraticheDipendenti.js"></script>
<script type="text/javascript" src="js/tabs_PraticheSintesi.js"></script>
<script type="text/javascript" src="js/tabs_PraticheAffidate.js"></script>
<script type="text/javascript" src="js/tabs_PraticheProroghe.js"></script>
<script type="text/javascript" src="js/tabs_PraticheSvalutate.js"></script>
<script type="text/javascript" src="js/tabs_PraticheSituazione.js"></script>
<script type="text/javascript" src="js/tabs_PraticheStatiLegali.js"></script>
<script type="text/javascript" src="js/tabs_PraticheStatiStragiudiziali.js"></script>
<script type="text/javascript" src="js/tabs_PraticheRinegoziate.js"></script>
<script type="text/javascript" src="js/tabs_PratichePianoRientro.js"></script>
<script type="text/javascript" src="js/tabs_ProcedureLista.js"></script>
<script type="text/javascript" src="js/tabs_Provvigioni.js"></script>
<script type="text/javascript" src="js/tabs_ProfiliLista.js"></script>
<script type="text/javascript" src="js/tabs_ProfiliUtenti.js"></script>
<script type="text/javascript" src="js/tabs_Ricerca.js"></script>
<script type="text/javascript" src="js/tabs_SubFunctionLista.js"></script>
<script type="text/javascript" src="js/tabs_SubActionAutomLista.js"></script>
<script type="text/javascript" src="js/tabs_Utenti.js"></script>
<script type="text/javascript" src="js/dettaglioUtente.js"></script>
<script type="text/javascript" src="js/dettaglioIncasso.js"></script>
<script type="text/javascript" src="js/dettaglioDistinta.js"></script>
<script type="text/javascript" src="js/dettaglioAutomatismo.js"></script>
<script type="text/javascript" src="js/dettaglioAzione.js"></script>
<script type="text/javascript" src="js/dettaglioAzioneWorkflow.js"></script>
<script type="text/javascript" src="js/dettaglioStatoWorkflow.js"></script>
<script type="text/javascript" src="js/dettaglioAutomatismoAzWorkflow.js"></script>
<script type="text/javascript" src="js/dettaglioCondizioneComplessa.js"></script>
<script type="text/javascript" src="js/dettaglioClassificazione.js"></script>
<!-- script type="text/javascript" src="js/dettaglioCreaFascia.js"></script-->
<script type="text/javascript" src="js/dettaglioPratica.js"></script>
<script type="text/javascript" src="js/dettaglioPraticaTax.js"></script>
<script type="text/javascript" src="js/dettaglioProcedura.js"></script>
<script type="text/javascript" src="js/dettaglioRegAssOperatore.js"></script>
<script type="text/javascript" src="js/dettaglioRegolaOpAssegnazione.js"></script>
<script type="text/javascript" src="js/dettaglioRegRipartizione.js"></script>
<script type="text/javascript" src="js/dettaglioInsoluto.js"></script>
<script type="text/javascript" src="js/dettaglioProfilo.js"></script>
<script type="text/javascript" src="js/dettaglioLottomatica.js"></script>
<script type="text/javascript" src="js/dettaglioProcessiAutomatici.js"></script>
<script type="text/javascript" src="js/dettaglioAutoPro.js"></script>
<script type="text/javascript" src="js/dettaglioModificaProvvigione.js"></script>
<script type="text/javascript" src="js/workflow.js"></script>
<script type="text/javascript" src="js/impersonaUtente.js"></script>
<script type="text/javascript" src="js/importaFileLottomatica.js"></script>
<script type="text/javascript" src="js/sceltaModelli.js"></script>
<script type="text/javascript" src="js/eliminaAvviso.js"></script>
<script type="text/javascript" src="js/avvisoAgenzia.js"></script>
<script type="text/javascript" src="js/importNote.js"></script>
<script type="text/javascript" src="js/parametroSistema.js"></script>
<script type="text/javascript" src="js/tabs_PraticheAzioniSpeciali.js"></script>
<script type="text/javascript" src="js/dettaglioAzioneSpeciale.js"></script>
<script type="text/javascript" src="js/menuModuliConfigurazione.js"></script>
<!-- <script type="text/javascript" src="js/grid_BancheOrganizzazione.js"></script> -->
<script type="text/javascript" src="js/grid_TipoPartitaDecodifica.js"></script>
<script type="text/javascript" src="js/grid_TipoSpecialeDecodifica.js"></script>
<script type="text/javascript" src="js/grid_TipoInsolutoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_TipoPagamentoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_TipoCompagniaDecodifica.js"></script>
<script type="text/javascript" src="js/grid_FamigliaProdottoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_ProdottoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_AttributoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_NazioneDecodifica.js"></script>
<script type="text/javascript" src="js/grid_RegioneDecodifica.js"></script>
<script type="text/javascript" src="js/grid_ProvinciaDecodifica.js"></script>
<script type="text/javascript" src="js/grid_StatoUtenteDecodifica.js"></script>
<script type="text/javascript" src="js/grid_StatoContrattoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_AreaGeoOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoRepartoOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoRecapitoOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoRelazioneOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoControparteOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoClienteOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoCategorieConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_StatoRecuperoConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_StatiLegaliConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_StatiStragiudizialiConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_TipoAllegatoConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_TipoAzioniConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_TipoEsitoConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_TipoIncassoConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_TipoRichiestaConfigurabili.js"></script>
<script type="text/javascript" src="js/grid_CompanyOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_RepartoOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_FilialeOrganizzazione.js"></script>
<script type="text/javascript" src="js/grid_TipoMovimentoDecodifica.js"></script>
<script type="text/javascript" src="js/grid_FasciaRecuperoRegole.js"></script>
<script type="text/javascript" src="js/grid_DettaglioRateProvvigione.js"></script>
<script type="text/javascript" src="js/grid_dettaglioProcesso.js"></script>
<script type="text/javascript" src="js/grid_PianoRateazione.js"></script>

<script type="text/javascript" src="ux/menu/EditableItem.js"></script>
<script type="text/javascript" src="ux/menu/RangeMenu.js"></script>
<script type="text/javascript" src="ux/menu/ListMenu.js"></script>
<script type="text/javascript" src="ux/menu/TreeMenu.js"></script>

<script type="text/javascript" src="ux/form/Ext.ux.form.SearchBox.js"></script>
<script type="text/javascript" src="ux/form/Ext.ux.form.ExtendedComboBox.js"></script>

<script type="text/javascript" src="ux/grid/GridFilters.js"></script>
<script type="text/javascript" src="ux/grid/filter/Filter.js"></script>
<script type="text/javascript" src="ux/grid/filter/StringFilter.js"></script>
<script type="text/javascript" src="ux/grid/filter/DateFilter.js"></script>
<script type="text/javascript" src="ux/grid/filter/ListFilter.js"></script>
<script type="text/javascript" src="ux/grid/filter/NumericFilter.js"></script>
<script type="text/javascript" src="ux/grid/filter/BooleanFilter.js"></script>
<script type="text/javascript" src="ext/examples/shared/examples.js"></script><!-- EXAMPLES -->

<script type="text/javascript" src="js/ButtonSubmenu.js"></script>
<script type="text/javascript" src="js/MainViewport.js"></script>
        <style type="text/css">
            h2.headline {
                font: normal 110%/137.5% "Trebuchet MS", Arial, Helvetica, sans-serif;
                padding: 0;
                margin: 25px 0 25px 0;
                color: #7d7c8b;
                text-align: center;
            }
            p.small {
                font: normal 68.75%/150% Verdana, Geneva, sans-serif;
                color: #919191;
                padding: 0;
                margin: 0 auto;
                width: 664px;
                text-align: center;
            }
        </style>
        <script src="FusionCharts/FusionCharts.js"></script>

	<script>
	Ext.BLANK_IMAGE_URL = 'ext/resources/images/default/s.gif';
	Ext.Ajax.timeout = 120000; // aumenta il timeout di default da 30 sec a 120 sec
							   // ma non sembra avere effetto
	Ext.Updater.defaults = 120000;
	
	var funzioneNonDisponibile = new Ext.Panel({title:"Funzione non ancora disponibile"});

	Ext.state.Manager.setProvider(new Ext.ux.state.HttpProvider({
	   url:'server/stateProvider.php'
	  ,user:'<?php echo $context['IdUtente'];?>'
	  ,autoRead:false
	  ,readBaseParams:{cmd:'readState'}
	  ,saveBaseParams:{cmd:'saveState'}
//	    ,logFailure:true
//	    ,logSuccess:true
	}));
	Ext.state.Manager.getProvider().initState(Ext.decode('<?php echo $_SESSION['uistate'];?>'));

	Ext.onReady(function() {
    	Ext.QuickTips.init();
      		<?php
      			$subMainFile  =$context['MainFile'];
      			if($subMainFile!="")  
      				include($subMainFile);
      		?>
		//-------------------------------------------------------------------------------
		// Se l'utente è abilitato a vedere l'eventuale avviso in ingresso, esegue
		// l'istruzione js necessaria
		//-------------------------------------------------------------------------------
		<?php 
			echo displayPopupWarning();
		?>
		//----------------------------------------------------------------------------
		// Funzione che entra allo scadere di un timeout: questa serve a mantenere
		// viva la sessione (che scadrà solo quando scade l'intervallo previsto
		// applicativamente (vedi funzione successiva)). Inoltre questa funzione,
		// che viene chiamata con intervallo piccolo (1-5 min), serve a rilevare la
		// presenza di un nuovo messaggio in popup per presentarlo senza che
		// l'utente abbia bisogno di rifare login o refresh della pagina
		//----------------------------------------------------------------------------
		Ext.namespace('DCS');
		DCS.periodicNotify = new Ext.util.DelayedTask ( function() 
		{
			Ext.Ajax.request({
					url: 'server/AjaxRequest.php', method:'GET',
					params: { task: 'session', redisplay:(CONTEXT.redisplayMsg?'Y':'N') },
					scope: this,
	        		success: function(xhr) {
		        		if (xhr.responseText.length>0)  // se c'è qualcosa, è una istruzione js da eseguire
                			eval(xhr.responseText);     // esegue istruzione
					}
				});
			DCS.periodicNotify.delay(<?php echo KEEPALIVE_TIME*1000; ?>); // rilancia se stesso
		});

		// lancia l'esecuzione
		DCS.periodicNotify.delay(<?php echo KEEPALIVE_TIME*1000; ?>); 
		
		//----------------------------------------------------------------------------
		// Gestione della sessione scaduta 
		//----------------------------------------------------------------------------
		DCS.emetteMessaggioSessioneScaduta = function() {
			Ext.MessageBox.show({
				   title: "Sessione scaduta",
				   msg: "La sessione corrente &egrave; scaduta per prolungata inattivit&agrave;. Per motivi di sicurezza, &egrave; necessario ripetere il login.",
				   buttons: Ext.Msg.OK,
				   icon: Ext.Msg.INFO,
				   fn: function(btn,text,opts) {
					   document.location.replace("<?php echo PORTAL_URL;?>");
				   }
			});
		}
		
		// Crea un delayedTask che entra allo scadere del timeout
		var timeoutTask = new Ext.util.DelayedTask ( DCS.emetteMessaggioSessioneScaduta );

		var restartTimeout = function(conn, options) 
		{
			// esclude la particolare chiamata per il mantenimento della sessione fisica
			if (options.url == "server/AjaxRequest.php")
				if (options.params.task == "session")
					return;
			// restart del periodo di timeout
			timeoutTask.delay(<?php echo getSysParm("INACTIVITY_TIMEOUT","1200")*1000; ?>); // millisecondi prima dell'avvio del messaggio di timeout
		};
			
		// Imposta un handler per intercettare (come attività utente) tutte le chiamate a funzioni server
		Ext.Ajax.on('beforerequest', restartTimeout, this);
		// Imposta in handler per intercettare i click del mouse (non i movimenti, troppi)
		Ext.getDoc().on("mouseup", restartTimeout, this);
		// non c'è bisogno di startare ora il timeout, visto che avverrà comunque
		// qualche chiamata Ajax o movimento del mouse
<?php
if (isset($_REQUEST['idcontratto']) && 
	isset($_REQUEST['numpratica']) &&
	isset($_REQUEST['idcliente'])) {
	$idContratto = $_REQUEST['idcontratto'];
	$numPratica  = $_REQUEST['numpratica'];
	$idCliente   = $_REQUEST['idcliente'];
	$cliente	 = $_REQUEST['cliente'];
	$msg	 	 = isset($_REQUEST['msg'])?$_REQUEST['msg']:"";

	echo <<<EOT
		var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Lettura dettaglio..."});
		myMask.show();
    	Ext.Ajax.request({
			url: 'server/paginaDettaglio.php',
			method:'GET',
            params: {idcontratto: {$idContratto}, pratica: {$numPratica}, cliente: {$idCliente}},
			failure: function() {Ext.Msg.alert("Impossibile aprire la pagina di dettaglio", "Errore Ajax");},
            success: function(xhr)
            {
                eval('tabPanel = '+xhr.responseText);
                var win = new Ext.Window({
                    width: 740, height:585, minWidth: 740, minHeight: 585,
                    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
                    title: 'Dettaglio insoluto - {$numPratica} {$cliente}',
					constrain: true,
                    items: tabPanel
                    });

                win.show();
                myMask.hide();
                
                if ($msg!="") {
                	Ext.Msg.alert({$msg}, "");
				}
            }
         });
EOT;
}
if(isset($_SESSION["workflow"]))
{	
	$wrkflw=$_SESSION["workflow"];
	switch($wrkflw)
	{
		case "WRFL":
			?>showPraticheWorkflow();<?php
			unset($_SESSION["workflow"]);
			break;
	}
}
?>
	    
	});
		
	</script>
</head>
<body></body>
</html>
