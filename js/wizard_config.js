/* Definizione delle colonne del database che sono possibile destinazione del wizard di importazione
   Questa definizione javascript viene convertita in json per l'uso da parte dei programmi
   
   ATTENZIONE: il valore del campo "name" non deve presentare duplicati, perché quella è chiave univoca della combo
   ed è usata per identificare i campi nell'importProcessor.php
   
*/
{
	/**
	 * columns: VETTORE DELLE COLONNE DISPONIBILI COME TARGET DELL'IMPORTAZIONE
	 * Spiegazione delle proprieta' di ciascuna "column":
	 * 		title: nome con cui si vede nel wizard
	 * 		name:  nome della colonna sul database
	 * 		table: suffisso della tabella wizard_import corrispondente (ad es se table:'cliente') il dato viene importato in wizard_import_cliente
	 * 		type:  tipo di dato accettato (string, number, date, datetime)
	 * 		length: lunghezza massima del campo target
	 * 		check_re: espressione regolare per il controllo di formato del campo di input
	 *      match_re: espressione regolare usata per suggerire la corrispondenza tra una colonna in input e il campo in esame, sulla base della
	 *                similitudine dell'etichetta
	 *      lookup: nome della tabella o view usata per controllare il valore di input
	 */
	columns: [
	   /*** RIGA VUOTA PER LA COMBOBOX ***/
	   {title:'[da ignorare]', name:' ', type:'string'},
	   /*** DATI RELATIVI AL DEBITORE ***/
	   {title:'Debitore: Codice', name:'CodCliente', 	   type:'string', length:30,  table:'cliente', match_re:'/cod.*cli/i'},
	   {title:'Debitore: Nominativo', 	 name:'Nominativo', 	   type:'string', length:200, table:'cliente', match_re:'/(cogn.*nome|nome.*cogn|nominativo)/i'},
	   {title:'Debitore: Cognome', 	 	 name:'CognomeDebitore', 		   type:'string', length:100, table:'cliente', match_re:'/^cognome$/i'},
	   {title:'Debitore: Nome', 	 		 name:'NomeDebitore', 			   type:'string', length:100, table:'cliente', match_re:'/^nome$/i'},
	   {title:'Debitore: Ragione Sociale', name:'RagioneSociale',    type:'string', length:200, table:'cliente', match_re:'/rag.*soc/i'},
	   {title:'Debitore: Forma Giuridica', name:'CodFormaGiuridica', type:'string', lookup:'formagiuridica', length:30, table:'cliente',
		   match_re:'/(tipo.*soc|forma.*giurid|forma.*soc)/i' , lookupField:'titoloFormaGiuridica'},
	   {title:'Debitore: Cod.Fisc. o P.IVA', name:'CodiceFiscale', type:'string', check_re:'/^([a-z]{6}[0-9]{2}[abcdehlmprst]{1}[0-9]{2}[a-z][0-9]{3}[a-z]|([a-z]{2})?([0-9]{0,5})?[0-9]{11})$/i', 
		   match_re: '/(cod.*fis|^p.*iva|c\.f\.)/i', table:'cliente'},
	   {title:'Debitore: Partita IVA',  	 name:'PartitaIva',  	   type:'string', check_re:'/^([a-z]{2})?([0-9]{0,5})?[0-9]{11}$/i', match_re: '/^p.+iva/', table:'cliente'},
	   {title:'Debitore: Data di nascita', name:'DataNascita', 	   type:'date', table:'cliente', match_re:'/data.*nasc/i'},
	   {title:'Debitore: Luogo di nascita',name:'LocalitaNascita',   type:'string', length:100, table:'cliente', match_re:'/(loc|luog).*nasc/i'},
	   {title:'Debitore: Provincia di nascita',name:'SiglaProvinciaNascita',   type:'string', lookup:'provincia', table:'cliente', match_re:'/prov.*nasc/i',
		   lookupField: 'SiglaProvincia'},
	   {title:'Debitore: Nazione di nascita',name:'SiglaNazioneNascita',  type:'string', lookup:'nazione', table:'cliente', match_re:'/naz.*nasc/i',
			   lookupField: 'SiglaNazione'},
	   {title:'Debitore: Sesso',			 name:'Sesso',   		   type:'string', length:1, table:'cliente', check_re:'/[mf ]/i', match_re:'/^sesso$/i'},
	   {title:'Debitore: Area geografica', name:'Area',   		   type:'string', length:50, table:'cliente', match_re:'/^area/i'},
	   {title:'Debitore: Nota', name:'NotaCliente',  type:'string', length:20000, table:'cliente', match_re:'/^nota/i'},
	   
	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI ALLA PRATICA ***/
	   {title:'Pratica: Numero Pratica',  name:'CodContratto', type:'string', length:20,  	table:'contratto', match_re:'/(cod.*contr|num.*prat|cod.*cli)/i'},
	   {title:'Pratica: Garante/Garanzie',name:'Garante', 	  type:'string', length:100, 	table:'contratto', match_re:'/garan/i'},
	   {title:'Pratica: Classificazione della pratica', name:'IdClasse',   type:'string', lookup:'classificazione',	table:'contratto', match_re:'/class/i',
		   lookupField:'TitoloClasse'},
	   {title:'Pratica: Stato del contratto', name:'IdStatoContratto',type:'string', lookup:'statocontratto',table:'contratto', match_re:'/stato/i',
			   lookupField:'TitoloStatoContratto'},
	   {title:'Pratica: Stato del recupero', name:'IdStatoRecupero',type:'string', lookup:'statorecupero',table:'contratto', match_re:'/stato.*recu/i',
				   lookupField:'TitoloStatoRecupero'},
	   {title:'Pratica: Imp. finanziato/Debito originario',name:'ImpFinanziato', type:'number', currency:true, table:'contratto', match_re:'/(finanz|debito.+origin)/i'},
	   {title:'Pratica: Importo erogato',name:'ImpErogato', type:'number', currency:true, table:'contratto', match_re:'/eroga/i'},
	   {title:'Pratica: Numero rate',     name:'NumRate', type:'number', table:'contratto', match_re:'/num.+rate/i'},
	   {title:'Pratica: Numero effetti scaduti',     name:'NumEffettiScaduti', type:'string',length:100, table:'contratto', match_re:'/num.+scad/i'},
	   {title:'Pratica: Importo rata',	  name:'ImpRata', type:'number', currency:true, table:'contratto', match_re:'/imp.+rata/i'},
	   {title:'Pratica: Valore reale',	  name:'ImpValoreBene', type:'number', currency:true, table:'contratto', match_re:'/valore/i'},
	   {title:'Pratica: Codice bene/fin.',name:'CodBene',  type:'string', length:20, 	table:'contratto', match_re:'/codic/i'},
	   {title:'Pratica: Descrizione',	  name:'DescrBene', 	   type:'string', length:500, 	table:'contratto', match_re:'/descriz/i'},
	   {title:'Pratica: Prima Scadenza', 	  name:'DataPrimaScadenza', type:'date', table:'contratto', match_re:'/data.*(ini|prim)/i'},
	   {title:'Pratica: Ultima Scadenza', 	  name:'DataUltimaScadenza', type:'date', table:'contratto', match_re:'/data.*(fin|ultim)/i'},
	   {title:'Pratica: Nota n.1',name:'Nota1', 	   type:'string', length:2000, 	table:'contratto', match_re:'/nota/i'},
	   {title:'Pratica: Nota n.2',name:'Nota2', 	   type:'string', length:2000, 	table:'contratto'},
	   {title:'Pratica: Nota n.3',name:'Nota3', 	   type:'string', length:2000, 	table:'contratto'},
	   {title:'Pratica: Nota n.4',name:'Nota4', 	   type:'string', length:2000, 	table:'contratto'},

	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI AI RECAPITI E CONTATTI ***/
	   {title:'Recapito: Tipo Recapito', name:'IdTipoRecapito', type:'string', lookup:'tiporecapito', table:'recapito',
		   lookupField:'TitoloTipoRecapito,CodTipoRecapitoLegacy'},
	   {title:'Recapito: Indirizzo', name:'Indirizzo', type:'string', length: 250, table:'recapito', match_re:'/indiriz/i'},
	   {title:'Recapito: Localit&agrave;', name:'Localita', type:'string', length: 250, table:'recapito', match_re:'/(local|citt|comune)/i'},
	   {title:'Recapito: CAP', name:'Cap', type:'string', table:'recapito', check_re:'/[0-9]{5}/i', match_re:'/^CAP$/i'},
	   {title:'Recapito: Sigla Provincia', name:'SiglaProvincia', type:'string',lookup:'provincia', table:'recapito', match_re:'/provin/i',
		   lookupField:'SiglaProvincia'},
	   {title:'Recapito: Nazione', name:'SiglaNazione', type:'string',lookup:'nazione', table:'recapito', match_re:'/nazio/i',
			   lookupField:'TitoloNazione'},
	   {title:'Recapito: Telefono', name:'Telefono', type:'string', length: 400, table:'recapito', match_re:'/telefon/i'},
	   {title:'Recapito: Cellulare', name:'Cellulare', type:'string', length: 70, table:'recapito', match_re:'/cellula/i'},
	   {title:'Recapito: Fax', name:'Fax', type:'string', length: 20, table:'recapito', match_re:'/fax/i'},
	   {title:'Recapito: E-mail', name:'Email', type:'string', length: 250, table:'recapito', match_re:'/e.*mail/i', check_re:'/.+@.+/'},
	   {title:'Recapito: Denominazione', name:'Nome', type:'string', length: 200, table:'recapito'},

	   {title:'Recapito: Tipo Recapito n.2', name:'IdTipoRecapito2', type:'string', lookup:'tiporecapito', table:'recapito',
		   lookupField:'TitoloTipoRecapito,CodTipoRecapitoLegacy'},
	   {title:'Recapito: Indirizzo n.2', name:'Indirizzo2', type:'string', length: 250, table:'recapito', match_re:'/indiriz/i'},
	   {title:'Recapito: Localit&agrave; n.2', name:'Localita2', type:'string', length: 250, table:'recapito', match_re:'/(local|citt|comune)/i'},
	   {title:'Recapito: CAP n.2', name:'Cap2', type:'string', table:'recapito', check_re:'/[0-9]{5}/i', match_re:'/^CAP$/i'},
	   {title:'Recapito: Sigla Provincia n.2', name:'SiglaProvincia2', type:'string',lookup:'provincia', table:'recapito', match_re:'/provin/i',
		   lookupField:'SiglaProvincia'},
	   {title:'Recapito: Nazione n.2', name:'SiglaNazione2', type:'string',lookup:'nazione', table:'recapito', match_re:'/nazio/i',
			   lookupField:'TitoloNazione'},
	   {title:'Recapito: Telefono n.2', name:'Telefono2', type:'string', length: 400, table:'recapito', match_re:'/telefon/i'},
	   {title:'Recapito: Cellulare n.2', name:'Cellulare2', type:'string', length: 70, table:'recapito', match_re:'/cellula/i'},
	   {title:'Recapito: Fax n.2', name:'Fax2', type:'string', length: 20, table:'recapito', match_re:'/fax/i'},
	   {title:'Recapito: E-mail n.2', name:'Email2', type:'string', length: 250, table:'recapito', match_re:'/e.*mail/i', check_re:'/.+@.+/'},
	   {title:'Recapito: Denominazione n.2', name:'Nome2', type:'string', length: 200, table:'recapito'},

	   {title:'Recapito: Tipo Recapito n.3', name:'IdTipoRecapito3', type:'string', lookup:'tiporecapito', table:'recapito',
		   lookupField:'TitoloTipoRecapito,CodTipoRecapitoLegacy'},
	   {title:'Recapito: Indirizzo n.3', name:'Indirizzo3', type:'string', length: 250, table:'recapito', match_re:'/indiriz/i'},
	   {title:'Recapito: Localit&agrave; n.3', name:'Localita3', type:'string', length: 250, table:'recapito', match_re:'/(local|citt|comune)/i'},
	   {title:'Recapito: CAP n.3', name:'Cap3', type:'string', table:'recapito', check_re:'/[0-9]{5}/i', match_re:'/^CAP$/i'},
	   {title:'Recapito: Sigla Provincia n.3', name:'SiglaProvincia3', type:'string',lookup:'provincia', table:'recapito', match_re:'/provin/i',
		   lookupField:'SiglaProvincia'},
	   {title:'Recapito: Nazione n.3', name:'SiglaNazione3', type:'string',lookup:'nazione', table:'recapito', match_re:'/nazio/i',
			   lookupField:'TitoloNazione'},
	   {title:'Recapito: Telefono n.3', name:'Telefono3', type:'string', length: 400, table:'recapito', match_re:'/telefon/i'},
	   {title:'Recapito: Cellulare n.3', name:'Cellulare3', type:'string', length: 70, table:'recapito', match_re:'/cellula/i'},
	   {title:'Recapito: Fax n.3', name:'Fax3', type:'string', length: 20, table:'recapito', match_re:'/fax/i'},
	   {title:'Recapito: E-mail n.3', name:'Email3', type:'string', length: 250, table:'recapito', match_re:'/e.*mail/i', check_re:'/.+@.+/'},
	   {title:'Recapito: Denominazione n.3', name:'Nome3', type:'string', length: 200, table:'recapito'},

	   {title:'Recapito: Tipo Recapito n.4', name:'IdTipoRecapito4', type:'string', lookup:'tiporecapito', table:'recapito',
		   lookupField:'TitoloTipoRecapito,CodTipoRecapitoLegacy'},
	   {title:'Recapito: Indirizzo n.4', name:'Indirizzo4', type:'string', length: 250, table:'recapito', match_re:'/indiriz/i'},
	   {title:'Recapito: Localit&agrave; n.4', name:'Localita4', type:'string', length: 250, table:'recapito', match_re:'/(local|citt|comune)/i'},
	   {title:'Recapito: CAP n.4', name:'Cap4', type:'string', table:'recapito', check_re:'/[0-9]{5}/i', match_re:'/^CAP$/i'},
	   {title:'Recapito: Sigla Provincia n.4', name:'SiglaProvincia4', type:'string',lookup:'provincia', table:'recapito', match_re:'/provin/i',
		   lookupField:'SiglaProvincia'},
	   {title:'Recapito: Nazione n.4', name:'SiglaNazione4', type:'string',lookup:'nazione', table:'recapito', match_re:'/nazio/i',
			   lookupField:'TitoloNazione'},
	   {title:'Recapito: Telefono n.4', name:'Telefono4', type:'string', length: 400, table:'recapito', match_re:'/telefon/i'},
	   {title:'Recapito: Cellulare n.4', name:'Cellulare4', type:'string', length: 70, table:'recapito', match_re:'/cellula/i'},
	   {title:'Recapito: Fax n.4', name:'Fax4', type:'string', length: 20, table:'recapito', match_re:'/fax/i'},
	   {title:'Recapito: E-mail n.4', name:'Email4', type:'string', length: 250, table:'recapito', match_re:'/e.*mail/i', check_re:'/.+@.+/'},
	   {title:'Recapito: Denominazione n.4', name:'Nome4', type:'string', length: 200, table:'recapito'},

	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI AL GARANTE ***/
	   {title:'Garante: Codice', name: 'CodGarante',  type: 'string', length: 30, table:'garante', match_re: '/cod.*gar/i'},
	   {title:'Garante: Nominativo', 	name:'NominativoGarante', type:'string', length: 200, table:'garante', match_re:'/(cogn.*nome|nome.*cogn|nominativo)/i'},
	   {title:'Garante: Cognome', 	 	name:'CognomeGarante', 	  type:'string', length:100, table:'garante', match_re:'/^cognome$/i'},
	   {title:'Garante: Nome', 	 		name:'NomeGarante', 	  type:'string', length:100, table:'garante', match_re:'/^nome$/i'},
	   {title:'Garante: Cod.Fisc. o P.IVA', 	name:'CodFiscaleGarante', type:'string', check_re:'/^([a-z]{6}[0-9]{2}[abcdehlmprst]{1}[0-9]{2}[a-z][0-9]{3}[a-z]|([a-z]{2})?([0-9]{0,5})?[0-9]{11})$/i', 
		   match_re: '/(cod.*fis|^p.*iva|c\.f\.)/i', table:'garante'},
	   {title:'Garante: Partita IVA',  	 name:'PartitaIvaGarante',  	   type:'string', check_re:'/^([a-z]{2})?([0-9]{0,5})?[0-9]{11}$/i', match_re: '/^p.+iva/', table:'garante'},
	   {title:'Garante: Tipo coobbligato', name:'TipoGarante', 	  type:'string', length:50, table:'garante', match_re:'/tipo.+(coob|gara)/i'},
	   {title:'Garante: Indirizzo', name:'IndirizzoGarante', type:'string', length: 250, table:'garante', match_re:'/indiriz/i'},
	   {title:'Garante: Localit&agrave;', name:'LocalitaGarante', type:'string', length: 250, table:'garante', match_re:'/(local|citt|comune)/i'},
	   {title:'Garante: CAP', name:'CapGarante', type:'string', table:'garante', check_re:'/[0-9]{5}/i', match_re:'/^CAP$/i'},
	   {title:'Garante: Sigla Provincia', name:'SiglaProvinciaGarante', type:'string',lookup:'provincia', table:'garante', match_re:'/provin/i',
		   lookupField:'SiglaProvincia'},
	   {title:'Garante: Nazione', name:'SiglaNazioneGarante', type:'string',lookup:'nazione', table:'garante', match_re:'/nazio/i',
			   lookupField:'TitoloNazione'},
	   {title:'Garante: Telefono', name:'TelefonoGarante', type:'string', length: 400, table:'garante', match_re:'/telefon/i'},
	   {title:'Garante: Cellulare', name:'CellulareGarante', type:'string', length: 70, table:'garante', match_re:'/cellula/i'},
	   {title:'Garante: Fax', name:'FaxGarante', type:'string', length: 20, table:'garante', match_re:'/fax/i'},
	   {title:'Garante: E-mail', name:'EmailGarante', type:'string', length: 250, table:'garante', match_re:'/e.*mail/i', check_re:'/.+@.+/'},
	   {title:'Garante: Nota', name:'NotaGarante',  type:'string', length:20000, table:'garante', match_re:'/^nota/i'},
	   
	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI ALLA POSIZIONE DEBITORIA (ES. SINGOLA FATTURA SCADUTA) ***/
	   {title:'Posizione: Numero Documento', 	name:'NumDocumentoPos', 	type:'string', 	length:20, table:'posizione', match_re: '/num.*doc/i'},
	   {title:'Posizione: Data Documento', 		name:'DataDocumentoPos', 	type:'date', 	table:'posizione', match_re:'/data.*nasc/i'},
	   {title:'Posizione: Data Scadenza', 		name:'DataScadenzaPos', 	type:'date', 	table:'posizione', match_re:'/data.*scad/i'},
	   {title:'Posizione: Data Registrazione',	name:'DataRegistrazionePos',type:'date', 	table:'posizione', match_re:'/data.*scad/i'},
	   {title:'Posizione: Numero Rata', 		name:'NumRataPos', 		type:'number', 	table:'posizione', match_re: '/num.*rata/i'},
	   {title:'Posizione: Importo (cap.)',		name:'ImpCapitale', 	type:'number', currency: true, table:'posizione', match_re: '/(debito$|scaduto|importo)/i'},
	   {title:'Posizione: Importo Spese Recupero', name:'ImpSpeseRecupero', type:'number', currency: true, table:'posizione', match_re: '/spese/i'},
	   {title:'Posizione: Importo Spese Legali', name:'ImpSpeseLegali', type:'number', currency: true, table:'posizione', match_re: '/spese.*legal/i'},
	   {title:'Posizione: Importo Interessi',  name:'ImpInteressi', 	type:'number', currency: true, table:'posizione', match_re: '/interes/i'},
	   {title:'Posizione: Altri Addebiti',  name:'ImpAltriAddebiti', 	type:'number', currency: true, table:'posizione', match_re: '/addeb/i'},
	   {title:'Posizione: Importo Pagato',  name:'ImpPagato', 	type:'number', currency: true, table:'posizione', match_re: '/pagat/i'},
	   
	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI AD UN SINGOLO MOVIMENTO CONTABILE ***/
	   {title:'Movimento: Numero Documento', 	name:'NumDocumento', 	type:'string', 	length:20, table:'movimento', match_re: '/num.*doc/i'},
	   {title:'Movimento: Data Documento', 		name:'DataDocumento', 	type:'date', 	table:'movimento', match_re:'/data.*nasc/i'},
	   {title:'Movimento: Data Scadenza', 		name:'DataScadenza', 	type:'date', 	table:'movimento', match_re:'/data.*scad/i'},
	   {title:'Movimento: Data Registrazione',	name:'DataRegistrazione',type:'date', 	table:'movimento', match_re:'/data.*scad/i'},
	   {title:'Movimento: Data Competenza',		name:'DataCompetenza',  type:'date', 	table:'movimento', match_re:'/data.*scad/i'},
	   {title:'Movimento: Numero Rata', 		name:'NumRata', 		type:'number', 	table:'movimento', match_re: '/num.*rata/i'},
	   {title:'Movimento: Importo a credito',	name:'ImpCapitaleCredito', 	type:'number', currency: true, 	table:'movimento', match_re: '/(credito|incass|paga)/i'},
	   {title:'Movimento: Importo a debito',	name:'ImpCapitaleDebito', 	type:'number', currency: true, 	table:'movimento', match_re: '/(debito|scaduto|importo)/i'},
	   {title:'Movimento: Interessi a credito',	name:'ImpInteressiCredito', type:'number', currency: true, 	table:'movimento'},
	   {title:'Movimento: Interessi a debito',	name:'ImpInteressiDebito', 	type:'number', currency: true, 	table:'movimento', match_re: '/interes/i'},
	   {title:'Movimento: Spese a credito',		name:'ImpInteressiCredito', type:'number', currency: true, 	table:'movimento'},
	   {title:'Movimento: Spese a debito',		name:'ImpInteressiDebito', 	type:'number', currency: true, 	table:'movimento', match_re: '/interes/i'},
	   {title:'Movimento: Causale Movimento', 	name:'IdTipoMovimento', type:'string', lookup:'tipomovimento',table:'movimento', 
		   match_re:'/tipo/i', lookupField:'TitoloTipoMovimento'},

	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
	   
	   /*** DATI RELATIVI ALLA STORIA RECUPERO ***/
	   
	   {title: 'Storia Rec.: Data Evento', name:'DataEvento', type:'date', table:'storiarecupero', match_re:'/data/i'},
	   {title: 'Storia Rec.: Operatore', name:'Operatore', type:'string', length:30, table:'storiarecupero', match_re:'/(operat|uten)/i'},
	   {title: 'Storia Rec.: Descrizione Evento', name:'DescrEvento', type:'string', length:500, table:'storiarecupero', match_re:'/(descr.*event|descriz)/i'},
	   {title: 'Storia Rec.: Nota', name:'NotaEvento',  type:'string', length:20000, table:'storiarecupero', match_re:'/^nota/i'},
	   
	   {title:'-----------------------------', name:' ', type:'string'}, /* separatore visuale */
		   
	   /*** CAMPI AUSILIARI ***/
	   {title:'Campi ausiliari: Campo n.1', 	name:'Campo1', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.2', 	name:'Campo2', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.3', 	name:'Campo3', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.4', 	name:'Campo4', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.5', 	name:'Campo5', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.6', 	name:'Campo6', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.7', 	name:'Campo7', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.8', 	name:'Campo8', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.9', 	name:'Campo9', 	type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.10', 	name:'Campo10', type:'string', 	length:20000, table:'workarea'},
	   {title:'Campi ausiliari: Campo n.11', 	name:'Campo11', type:'string', 	length:20000, table:'workarea'}
	 ],
	/**
	 * importTypes: DEFINIZIONE DEI PARAMETRI NECESSARI PER OGNI TIPO DI IMPORT SCELTO 
	 * Ad es. se il file contiene solo "recapiti" è obbligatorio che contenga anche dati identificativi dell'utente
	 * Spiegazione degli attributi:
	 * baseTable: indica qual'è la tabella di cui includere tutte le colonne
	 * addedColumns: indica quali altre proprietà devono essere rese visibili in quanto necessarie all'identificazione
	 * required: indica quali proprietà devono essere non vuote (se ce ne sono più in alternativa le esprime come array)
	 */
	importTypes: {
	    "cliente": 		{baseTable: 'cliente', addedColumns:[],
	    	required: [
	    	    'CodCliente',['Nominativo','CognomeDebitore','RagioneSociale'],['CodiceFiscale','PartitaIva']    
	    	]
	    },
	    "garante": { baseTable: 'garante', addedColumns:['CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva','CodContratto'],
	    	required:[
	    	          // campi necessari ad identificare il cliente o il contratto
		   	    	  ['CodContratto','CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
		   	    	  'CodGarante',['NominativoGarante','CognomeGarante'],['CodFiscaleGarante','PartitaIvaGarante'] 
	    	]
	    },
	    "contratto": 	{baseTable: 'contratto', addedColumns:['CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
	    	required: [
	    	        // campi necessari ad identificare il cliente
	    	        ['CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
	   	    	    'CodContratto'   
	   	    	]},
	    "recapito": 	{baseTable: 'recapito', addedColumns:['CodContratto','CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
	    	required: [
		    	        // campi necessari ad identificare il cliente o il contratto
		   	    	    ['CodContratto','CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
		   	    	    ['Indirizzo','Telefono','Cellulare','Email','Nome']    
		   	    	]
	   	 },
	    "posizione": 	{baseTable: 'posizione', addedColumns:['CodContratto','CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
	    	required: [
		    	        // campi necessari ad identificare il cliente o il contratto
		   	    	    ['CodContratto','CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
		   	    	    ['ImpCapitale','ImpSpeseRecupero','ImpSpeseLegali','ImpInteressi']    
		   	    	]
    	},
	    "movimento": 	{baseTable: 'movimento', addedColumns:['CodContratto','CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
	    	required: [
		    	        // campi necessari ad identificare il cliente o il contratto
		   	    	    ['CodContratto','CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
		   	    	    'DataRegistrazione',
		   	    	    ['ImpCapitaleoDebito','ImpCapitaleCredito','ImpInteressiDebito','ImpInteressiCredito','ImpSpeseDebito','ImpSpeseCredito']
		   	    	]
    	},
    	"storiarecupero": {baseTable: 'storiarecupero', addedColumns:['CodContratto','CodCliente','Nominativo','CognomeDebitore','NomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
    		required: [
    		           ['CodContratto','CodCliente','Nominativo','CognomeDebitore','RagioneSociale','CodiceFiscale','PartitaIva'],
    		           ['DataEvento','DescrEvento']
    		          ]
    	}
	}
}	 