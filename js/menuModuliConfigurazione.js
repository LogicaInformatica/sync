//Crea namespace DCS
Ext.namespace('DCS');

//--------------------------------------------------------
//Avviso in sovrapposizione alla pagina
//--------------------------------------------------------
DCS.moduliMain = function(){
	return {	
		create: function() {

		
/* STRUTTURA LAYOUT
------------------------------------------------------------------
 FormPanel
	Panel n.1 (container a 2 colonne)
		container
 			fieldset (colonna 1: tabelle relative all'organizzazione)
				panel 1:n (righe con 2 pulsanti)
					container
						box1 pulsante box2 pulsante box3 
		container
			fieldset (colonna 2: tabelle relative alle regole)
				panel 1:n (righe con 1 pulsante)
					container
						box1 pulsante box2 
	Panel n.2 (container a 1 colonna)
 		container
 			fieldset (colonna 1: tabelle di decodifica)
				panel 1:n (righe con 3 pulsanti)
					container
						box1 pulsante box2 pulsante box3 pulsante box4 
------------------------------------------------------------------*/
		var space1 = {xtype: 'box', flex: 0.1};
		var space2 = {xtype: 'box', flex: 0.2};
		
		// spaziatore tra le righe di pulsanti
		var vspace = {xtype:'tbspacer', height: 4};
		   
		// Riga modello della sezione Organizzazione
		var orgrow = {xtype:'panel',
			   		  layout: {type: 	'hbox', align: 'middle'}, 
			   		  defaults: {xtype: 'btnsubmain', flex: 0.3},
			   		  items: [space1, {xtype:	'btnsubmain'}, space2,	{xtype:	'btnsubmain', style: {visibility: 'hidden'}},space1]
		   			 };
			
		var orgrow1 = Ext.clone(orgrow);
		var orgrow2 = Ext.clone(orgrow);
		var orgrow3 = Ext.clone(orgrow);
		var orgrow4 = Ext.clone(orgrow);
		var orgrow5 = Ext.clone(orgrow);
		var orgrow6 = Ext.clone(orgrow);
		var orgrow7 = Ext.clone(orgrow);
		var orgrow8 = Ext.clone(orgrow);

		var setButton = function(row,numButton,text,panel,authorization) {
			row.items[numButton*2-1].text = text;
			row.items[numButton*2-1].panel = panel;
			row.items[numButton*2-1].disabled = !authorization;
			row.items[numButton*2-1].style = {visibility: 'visible'};
		};

		setButton(orgrow1,1,'Utenti',					DCS.GridGestUtenti.create,		CONTEXT.MENU_UT_UT);
		setButton(orgrow1,2,'Profili',					DCS.GridUtentiProfili.create,	CONTEXT.MENU_UT_PROF);
		setButton(orgrow2,1,'Societ&agrave mandatarie',	DCS.CompanyOrg.create(1),		CONTEXT.MENU_CONF_SMAN);
		setButton(orgrow2,2,'Societ&agrave di recupero',DCS.CompanyOrg.create(2),		CONTEXT.MENU_CONF_SREC);
		setButton(orgrow3,1,'Societ&agrave dealer',		DCS.CompanyOrg.create(3),		CONTEXT.MENU_CONF_SDEAL);
		setButton(orgrow3,2,'Agenzie e Reparti',		DCS.RepartiOrg.create,			CONTEXT.MENU_CONF_AREC);
		setButton(orgrow4,1,'Filiali',					DCS.FilialiOrg.create,			CONTEXT.MENU_CONF_FILIA);
		setButton(orgrow4,2,'Aree geografiche',			DCS.AreaGeoOrg.create('R'),		CONTEXT.MENU_ANA_AREE);
		setButton(orgrow5,1,'Aree commerciali',			DCS.AreaGeoOrg.create('C'),		CONTEXT.MENU_ANA_AREE);
		setButton(orgrow5,2,'Tipi di cliente',			DCS.TClienteOrg.create,			CONTEXT.MENU_CONF_TCLI);
		setButton(orgrow6,1,'Tipi di controparte',		DCS.TControparteOrg.create,		CONTEXT.MENU_ANA_TCONT);
		setButton(orgrow6,2,'Tipi di relazione',		DCS.TRelazioneOrg.create,		CONTEXT.MENU_CONF_TREL);
		setButton(orgrow7,1,'Tipi di recapito',			DCS.TRecapitoOrg.create,		CONTEXT.MENU_ANA_TREC);
		setButton(orgrow7,2,'Tipo di reparto',			DCS.TRepartoOrg.create,			CONTEXT.MENU_CONF_TREP);
		setButton(orgrow8,1,'Tipi di societ&agrave;',	DCS.TipoCompagnia.create,		CONTEXT.MENU_CONF_TSOC);
		// NON SVILUPPATO: setButton(orgrow8,2,'Banche',DCS.RepartiOrg.create,CONTEXT.MENU_CONF_BANK);

		// Riga modello della sezione Regole
		var rulrow = {xtype:'panel',
			   		  layout: {type: 	'hbox', align: 'middle'}, 
			   		  defaults: {xtype: 'btnsubmain', flex: 0.6},
			   		  items: [space2, {xtype:	'btnsubmain'}, space2]
		   			 };
			
		var rulrow1 = Ext.clone(rulrow);
		var rulrow2 = Ext.clone(rulrow);
		var rulrow3 = Ext.clone(rulrow);
		var rulrow4 = Ext.clone(rulrow);
		var rulrow5 = Ext.clone(rulrow);
		var rulrow6 = Ext.clone(rulrow);
		var rulrow7 = Ext.clone(rulrow);
		var rulrow8 = Ext.clone(rulrow);

		setButton(rulrow1,1,'Affidamenti',			DCS.AffidamentiRegole.create,		CONTEXT.MENU_AFF);
		setButton(rulrow2,1,'Assegnazioni',			DCS.AssegnazioniRegole.create,		CONTEXT.MENU_ASS);
		setButton(rulrow3,1,'Classificazioni',		DCS.ClassificazioniRegole.create,	CONTEXT.MENU_CLASS);
		setButton(rulrow4,1,'Workflow',				DCS.ProceduraWrkF.create,			CONTEXT.MENU_CONF_WORK);
		setButton(rulrow5,1,'Automatismi',			DCS.GridGestAutomatismi.create,		CONTEXT.MENU_CONF_AUT);
		setButton(rulrow6,1,'Azioni',				DCS.GridGestAzioni.create,			CONTEXT.MENU_CONF_AZ);
		setButton(rulrow7,1,'Ripartizioni',			DCS.GridRegRipartizioni.create,		CONTEXT.MENU_REGRIP);
		setButton(rulrow8,1,'Fasce di recupero',	DCS.FasciaRecuperoReg.create,		CONTEXT.MENU_CONF_FREC);

		// Riga modello della sezione Tabelle di decodifica
		var tabrow = {xtype:'panel',
		   		  layout: {type: 	'hbox', align: 'middle'}, 
		   		  defaults: {xtype: 'btnsubmain', flex: 0.2},
		   		  items: [space1, {xtype:	'btnsubmain'}, 
		   		          space1, {xtype:	'btnsubmain', style: {visibility: 'hidden'}},
		   		          space1, {xtype:	'btnsubmain', style: {visibility: 'hidden'}},space1]
	   			 };
		
		var tabrow1 = Ext.clone(tabrow);
		var tabrow2 = Ext.clone(tabrow);
		var tabrow3 = Ext.clone(tabrow);
		var tabrow4 = Ext.clone(tabrow);
		var tabrow5 = Ext.clone(tabrow);
		var tabrow6 = Ext.clone(tabrow);
		var tabrow7 = Ext.clone(tabrow);
		var tabrow8 = Ext.clone(tabrow);
		var tabrow9 = Ext.clone(tabrow);
		
		setButton(tabrow1,1,'Attributi del contratto',DCS.Attributo.create,			CONTEXT.MENU_CONF_ATTC);
		setButton(tabrow2,1,'Categorie',			DCS.CategoriaConf.create,		CONTEXT.MENU_CONF_CAT);
		setButton(tabrow3,1,'Categorie maxirata',	DCS.CategoriaMaxirata.create,	CONTEXT.MENU_CONF_MR);
		setButton(tabrow4,1,'Categorie riscatto leasing',	DCS.CategoriaRiscattoLeasing.create,	CONTEXT.MENU_CONF_RL);
		setButton(tabrow5,1,'Causali movimento',	DCS.TipoMovimento.create,		CONTEXT.MENU_ANA_CMOV);
		setButton(tabrow6,1,'Famiglie di prodotti',	DCS.FamigliaProdotto.create,	CONTEXT.MENU_ANA_FMP);
		setButton(tabrow7,1,'Modelli',				DCS.Modelli.create,				CONTEXT.MENU_CONF_MOD);
		setButton(tabrow8,1,'Nazioni',				DCS.NazioniDec.create,			CONTEXT.MENU_CONF_NAZ);
		setButton(tabrow9,1,'Prodotti',				DCS.Prodotto.create,			CONTEXT.MENU_ANA_PRO);
		setButton(tabrow1,2,'Province',				DCS.ProvinceDec.create,			CONTEXT.MENU_CONF_PROV);
		setButton(tabrow2,2,'Regioni',				DCS.RegioniDec.create,			CONTEXT.MENU_CONF_REG);
		setButton(tabrow3,2,'Stati del contratto',	DCS.StatoContrattoDec.create,	CONTEXT.MENU_ANA_SC);
		setButton(tabrow4,2,'Stati dell\'utente',	DCS.StatoUtenza.create,			CONTEXT.MENU_CONF_STAU);
		setButton(tabrow5,2,'Stati di recupero',	DCS.StatoRecuperoConf.create,	CONTEXT.MENU_CONF_SRE);
		setButton(tabrow6,2,'Stati di rinegoziazione',	null/*DCS.StatoRinegoziazione.create*/,	CONTEXT.MENU_CONF_RINE);
		setButton(tabrow7,2,'Stati legali',			DCS.StatoLegaleConf.create,	CONTEXT.MENU_CONF_SLEG);
		setButton(tabrow8,2,'Stati piano di rientro',	null/*DCS.StatoPiano.create*/,	CONTEXT.MENU_CONF_PIANO);
		setButton(tabrow9,2,'Stati stragiudiziali',	DCS.StatoStragiudizialeConf.create,	CONTEXT.MENU_GP_SSTG);
		setButton(tabrow1,3,'Tipi di allegato',		DCS.TipoAllegato.create,		CONTEXT.MENU_CONF_TALL);
		setButton(tabrow2,3,'Tipi di azione',		DCS.TipoAzioni.create,			CONTEXT.MENU_CONF_TAZ);
		setButton(tabrow3,3,'Tipi di esito',		DCS.TEsitoConf.create,			CONTEXT.MENU_CONF_TES);
		setButton(tabrow4,3,'Tipi di forzatura',	DCS.TipoSpeciale.create,		CONTEXT.MENU_CONF_TFOR);
		setButton(tabrow5,3,'Tipi di incasso',		DCS.TIncassoConf.create,		CONTEXT.MENU_CONF_TINC);
		setButton(tabrow6,3,'Tipi di insoluto',		DCS.TipoInsoluto.create,		CONTEXT.MENU_CONF_TINS);
		setButton(tabrow7,3,'Tipi di pagamento',	DCS.TipoPagamento.create,		CONTEXT.MENU_CONF_TPAG);
		setButton(tabrow8,3,'Tipi di partita',		DCS.TipoPartita.create,			CONTEXT.MENU_CONF_TPAR);
		setButton(tabrow9,3,'Tipi di richiesta',	DCS.TRichiestaConf.create,		CONTEXT.MENU_CONF_TRIC);

		// i fieldset sono contenuti in un container perch� altrimenti appaiono male
		var fieldset1 = {xtype:'container',layout: 'anchor', columnWidth:.65,
				items: [{xtype:'fieldset', 
						title:'Definizioni relative all\'Organizzazione', 
						border: true, 
						anchor:'95%',
						items: [orgrow1,vspace,orgrow2,vspace,orgrow3,vspace,orgrow4,vspace,orgrow5,vspace,orgrow6,vspace,orgrow7,vspace,orgrow8,vspace]
				}]
		};
		var fieldset2 = {xtype:'container',layout: 'anchor', columnWidth:.35,
				items: [{xtype:'fieldset', 
				 title:'Configurazione delle Regole', 
				 border: true, 
				 anchor:'95%',
				 items: [rulrow1,vspace,rulrow2,vspace,rulrow3,vspace,rulrow4,vspace,rulrow5,vspace,rulrow6,vspace,rulrow7,vspace,rulrow8,vspace]
				}]
		};
		var fieldset3 = {xtype:'container',layout: 'anchor', columnWidth:1,
				items: [{xtype:'fieldset', 
				 title:'Tabelle di decodifica', 
				 border: true, 
				 anchor:'98%',
				 items: [tabrow1,vspace,tabrow2,vspace,tabrow3,vspace,tabrow4,vspace,tabrow5,vspace,tabrow6,vspace,tabrow7,vspace,tabrow8,vspace,tabrow9]
				}]
		};
		
		var panel1 = {xtype: 'container', layout: 'column', items: [fieldset1,fieldset2] };
		var panel2 = {xtype: 'container', layout: 'column', items: [fieldset3] };
		
		var formModuli = new Ext.form.FormPanel({
			frame: true,
			header: false,
			bodyStyle: 'padding:5px 0px 0',
			layoutConfig: {flex: 1},
			anchor:'95%',
			border: false,
			items: [panel1,panel2]
		});

		return formModuli;
	} // end of "create" method
	}; // end of returned struture

}(); // fine funzione principale DCS.moduliMain