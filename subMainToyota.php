<?php
      	
?>
		// SEZIONE DI CONFIGURAZIONE DELLA VISUALIZZAZIONE DEI PANEL E DELLE COLONNE DEI PANEL
		
		arrayHiddenColumns = [];
		
		//PANELS CORRENTI 
		PraCorrentiAttesa   		= true;
		PraPreRecupero      		= true;
		PraCorrentiAttive 			= true;
		PraCorrentiScadenza 		= true;
		PraCorrentiPositive 		= true;
		PraCorrentiIncParz  		= true;
		PraCorrentiNoStart  		= true;
		PraCorrentiOverride 		= true;
		
		//PANELS  Stragiudiziale / Legale 
		PraStrLegAttesaALL   		= true;
		PraStrLegAttesaNULL   		= true;
		PraStrLegAttesaSTR      	= true;
		PraStrLegAttesaLEG 	 		= true;
		PraStrStep1STR1 			= true;
		PraStrStep1STR2 			= true;
		PraStrStep1STR3  			= true;
		PraStrRepo  				= true;
		PraLegLEA 					= true;
		PraLegLOA 					= true;
		PraStrLegPositive			= true;
		PraStrLegIncParz			= true;

		//PANELS  Scadenzario pre-DBT
		ScadScadenzePratiche   		= true;
		ScadScadenzeGenerali    	= true;
		
		//PANELS  Scadenzario stragiudiziali
		PraScadenzaSTRSoft			= true;
		PraScadenzaSTRHard			= true;
		PraScadenzaSTRStrong		= true;
		PraScadenzaSTRREPO			= true;
		PraScadenzaSTRLEG			= true;
		PraScadenzaSTRLEGScad		= true;
		PraSaldoStralcio			= true;
		
		//PANELS  Comunicazioni
		ComNormaliNonRis			= true;
		ComRiservate				= true;
		MsgNonLetti					= true;
		MsgLetti					= true;
		
		//PANELS  Sintesi recupero
		PraticheSintesiStato		= true;
		PraticheSintesiAgenzia		= true;
		PraticheSintesiClassi		= true;
		PraticheSintesiProd			= true;
		PraticheSintesiLavInt		= true;
		
		//PANELS  Sintesi affidamenti
		PraticheSintesiLotto		= true;
		PraticheSintesiAffidoAg		= true;
		
		//flag di configurazione della visualizzazione delle pratiche correnti divise per famiglia prodotto
		PraticheCorrPerFamiglia		= false;
		

    	//----------------SOTTOMENU GESTIONE PRATICHE ORDINARIE (OPERATORE INTERNO) ------------------
    	var menu_insoluti = new DCS.Menu ({title: 'Gestione pratiche',items: []});
    	if (CONTEXT.InternoEsterno=='I')
	    	DCS.menu_insoluti = menu_insoluti;

    	if (CONTEXT.MENU_GP_CORR)
	    	menu_insoluti.add({xtype: 'btnsubmenu',text: 'Correnti',		panel: getPraticheCorrenti});			 // getPraticheCorrenti è una funzione in common.js
    	if (CONTEXT.MENU_GP_STRLEG)
	    	menu_insoluti.add({xtype: 'btnsubmenu',text: 'Stragiudiziale/Legale',	panel: getPraticheStrLeg, param:'STRLEG'});		// getPraticheCorrenti è una funzione in common.js
 		if (CONTEXT.MENU_GP_LIN)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Lavorazione interna',  	panel: DCS.PraticheLavorInt.create});
 		if (CONTEXT.MENU_GP_SLE)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Stati legali',  	panel: DCS.PraticheStatiLegali.create});
		if (CONTEXT.MENU_GP_SSTG)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Stati stragiudiziali',  	panel: DCS.PraticheStatiStragiudiziali.create});
 		if (CONTEXT.MENU_GP_EST)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Estinti con debito',  	panel: DCS.PraticheEstinte.create});
		if (CONTEXT.MENU_GP_PR)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Piani di rientro',  	    panel: DCS.PratichePianoRientro.create, param:2});
		if (CONTEXT.MENU_GP_CESWO)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Cessioni e write-off',  	panel: DCS.PraticheCessWO.create});
		if (CONTEXT.MENU_GP_WKF)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Pratiche in workflow',	panel: DCS.PraticheWorkflow.create});			
		if (CONTEXT.MENU_GP_DIP)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Dipendenti',				panel: DCS.PraticheDipendenti.create});
		if (CONTEXT.MENU_GP_SINT)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Sintesi',					panel: DCS.PraticheSintesi, param: 1});
		if (CONTEXT.MENU_GP_SCAD)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Scadenzario',				panel: getScadenzarioDBT});	// getScadenzarioDBT è una funzione in common.js
		if (CONTEXT.MENU_SCAD_STR)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Scadenzario STR/LEG',		panel:  getScadenzarioSTR, param:'STRLEG'}); // getScadenzarioSTR è una funzione in common.js	
		if (CONTEXT.MENU_GP_COM)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Comunicazioni',			panel: DCS.Comunicazioni.createComm, id: 'voceMenuComunicazioni'});	
		if (CONTEXT.MENU_GP_SVA)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Svalutazioni',			panel: DCS.PraticheSvalutate.create});
		if (CONTEXT.MENU_GP_SIT)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Situazione debitoria',	panel: DCS.PraticheSituazione.create});
		if (CONTEXT.MENU_GP_RIN)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Rinegoziazioni',	panel: DCS.PraticheRinegoziate.create});	
		if (CONTEXT.MENU_GP_GRAF)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Grafici',			panel: DCS.Charts.Tabs.create_TFSI});
		if (CONTEXT.MENU_GP_GRAF_STRLEG)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Grafici STR',		panel: DCS.Charts.Tabs.create_TFSI_STR});
		if (CONTEXT.MENU_AZI_SPEC_I)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Azioni con convalida',panel: DCS.PraticheAzioniSpeciali});
		if (CONTEXT.MENU_GP_EXPERIAN)
			menu_insoluti.add({xtype: 'btnsubmenu',text: 'Experian',panel: DCS.Experian.create});
		
    	//----------------SOTTOMENU AFFIDAMENTI------------------
    	var menu_affidamenti = new DCS.Menu ({title: 'Affidamenti',items: []});
    	if (CONTEXT.MENU_AFF_CORR)
	    	menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Correnti (Pre-DBT)',	panel: DCS.PraticheAffidate.create,param:1});
    	if (CONTEXT.MENU_AFF_STR)
			menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Stragiudiziali',		panel: DCS.PraticheAffidate.create,param:2});
    	if (CONTEXT.MENU_AFF_LEG)
			menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Legali',				panel: DCS.PraticheAffidate.create,param:3});
		if (CONTEXT.MENU_AFF_SINT)
			menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Sintesi affidamenti',	panel: DCS.PraticheSintesi,param:2});	
		if (CONTEXT.MENU_AFF_PROV)
		{
			if (CONTEXT.InternoEsterno=='E') {
				menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Provvigioni',		panel: DCS.Provvigioni.create,param:''});
				if (CONTEXT.MENU_GPA_RIN) 
					menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Provvigioni rinegoziazione',	panel: DCS.Provvigioni.create,param:4});
			} else {
				menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Provvigioni pre-DBT',panel: DCS.Provvigioni.create,param:1});
				menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Provvigioni STR',	panel: DCS.Provvigioni.create,param:2});
				menu_affidamenti.add({xtype: 'btnsubmenu',text: 'Provvigioni rinegoziazione',	panel: DCS.Provvigioni.create,param:4});
			} 
		}

    	//----------------SOTTOMENU PRATICHE AGENZIA------------------
    	var menu_gestPratiche_Agenzia = new DCS.Menu ({title: 'Gestione pratiche',items: []});
    	if (CONTEXT.InternoEsterno=='E')
	    	DCS.menu_insoluti = menu_insoluti; // usato in common.js (funzione showMessaggiNonLetti)
    	
    	if (CONTEXT.MENU_GPA_CORR)
	    	menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Correnti',		panel: DCS.PraticheAgenzia.create});
    	if (CONTEXT.MENU_GPA_SINT) 
			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Sintesi',			panel: DCS.PraticheAgenziaSintesi.create});
    	if (CONTEXT.MENU_GPA_SCAD)
			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Scadenzario',		panel: DCS.Comunicazioni.createScadenzario});
    	if (CONTEXT.MENU_GPA_COM)
        	menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Comunicazioni',	panel: DCS.Comunicazioni.createComm, id: 'voceMenuComunicazioni'});
//		if (CONTEXT.MENU_GPA_RIN)
//			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Rinegoziazioni',			    panel: DCS.PraticheRinegoziate.create, param:1});
		if (CONTEXT.MENU_GPA_GRAF)
			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Grafici',			panel: DCS.Charts.Tabs.create,param:1});
		if (CONTEXT.MENU_GPA_GRAF_STRLEG)
			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Grafici DBT',		panel: DCS.Charts.Tabs.create,param:2});
		if (CONTEXT.MENU_AZI_SPEC_E)
			menu_gestPratiche_Agenzia.add({xtype: 'btnsubmenu',text: 'Azioni con convalida',panel: DCS.PraticheAzioniSpeciali});
    	
    	//----------------SOTTOMENU GESTIONE INCASSI PER OPERATORE INTERNO ------------------
    	var menu_incassi = new DCS.Menu ({title: 'Gestione incassi',items: []});
    	if (CONTEXT.MENU_INC_VAL_CORR)
			menu_incassi.add({xtype: 'btnsubmenu',text: 'Incassi valori',				panel: DCS.IncassiValori.create});
		// Voci di menù che puntano solo a files
		menu_incassi.add({xtype: 'button',text: 'Bonifici sospesi',		cls: 'btn-submenu',handler: DCS.Incassi.showBonificiSospesi});
		menu_incassi.add({xtype: 'button',text: 'Bollettini smarriti',	cls: 'btn-submenu',	handler: DCS.Incassi.showBollettiniSmarriti});

    	//----------------SOTTOMENU STORICO PER OPERATORE INTERNO ------------------
    	var menu_storico = new DCS.Menu ({title: 'Storico',items: []});
     	if (CONTEXT.MMENU_STORICO) {
     		var  funcNO = function() { // usato temporaneamente mentre si sviluppano funzioni
     			return new Ext.BoxComponent({autoEl:{html:"Funzione non ancora disponibile"},width:500});
     			};
			menu_storico.add({xtype: 'btnsubmenu',text: 'Elenco',	panel: DCS.PraticheStorico.create});
		}

		//----------------SOTTOMENU CONFIGURAZIONI A MODULI------------------
    	var menu_configurazioni_modulari = new DCS.Menu ({title: 'Configurazione',items: []});
    	if	(CONTEXT.MMENU_CONF)
			menu_configurazioni_modulari.add({xtype: 'btnsubmenu',text: 'Quadro generale',	panel: DCS.moduliMain.create});
	
		//-----SOTTOMENU FUNZIONI CONTROLLO OP. BATCH-------   	
    	var menu_controllo = new DCS.Menu ({title: 'Controllo del sistema',items: []});
    	
		if (CONTEXT.MENU_CONT_ACQ)
    		menu_controllo.add({xtype: 'btnsubmenu',text: 'Acquisizione dati',			panel: DCS.visFile.create});
		if (CONTEXT.MENU_CONT_MSGD)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Messaggi differiti',			panel: DCS.MessaggiDifferiti.create});
		if (CONTEXT.MENU_CONT_GB)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Giornale di bordo',			panel: DCS.LogList.create});
		if (CONTEXT.MENU_CONT_VIS)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Visibilit&agrave; affidi',	panel: DCS.Parametro,param:'DATA_ULT_VIS,DATA_ULT_VIS_STR'});
		if (CONTEXT.MENU_AVVISI_AGE)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Avviso agenzia',				panel: DCS.avvisoAge.create});
		if (CONTEXT.MENU_PROC_AUTO)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Processi automatici',		panel: DCS.GridProcessiAuto.create});
		if (CONTEXT.MENU_CONT_ACQ)
			menu_controllo.add({xtype: 'btnsubmenu',text: 'Parametri di sistema',		panel: fn_ParametriSistema});
				
		//----------------SOTTOMENU LINKS------------------
    	var menu_links = new Ext.Panel ({title: 'Links utili',headerStyle: 'font-weight: bold',
        	flex: 1,frame: true,autoScroll: true,	autoLoad: 'server/documenti.php'});
		
    	//----------------MAINVIEWPORT------------------
    	var cmp1 = new DCS.MainViewport({
			url_avvisi:		'server/avvisi.php',
			url_scadenze:	'server/scadenze.php',
			user_cal:  CONTEXT.MMENU_CAL,
			user_avv:  CONTEXT.MMENU_AVV,
			user_insa: CONTEXT.MMENU_CAL_AV,
			user_inss: CONTEXT.MMENU_CAL_SC,
			dateAvvisi: <?php include("server/dateAvvisi.php"); ?>,

			sottomenu: [<?php 	$v = '';
				if(userCanDo('MMENU_GP'))   { echo 'menu_insoluti'; $v=',';}
				if(userCanDo('MMENU_GPAG') && !userCanDo('MMENU_GP')) { echo $v.'menu_gestPratiche_Agenzia'; $v=',';}
				if(userCanDo('MMENU_AFF'))  { echo $v.'menu_affidamenti'; $v=',';}
				if(userCanDo('MMENU_INC'))  { echo $v.'menu_incassi'; $v=',';}
				if(userCanDo('MMENU_STORICO')) { echo $v.'menu_storico'; $v=',';}
				if(userCanDo('MMENU_CONF')) { echo $v.'menu_configurazioni_modulari'; $v=',';}
				if(userCanDo('MMENU_CONT')) { echo $v.'menu_controllo'; $v=',';}
				if(userCanDo('MMENU_LK'))     echo $v.'menu_links';?>
			],
				
			renderTo: Ext.getBody()
		});
		cmp1.initMenu();
		cmp1.show();
		
