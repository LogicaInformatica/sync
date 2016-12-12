// Pagina delle liste relative a Experian (servizio di valutazione dello stato del credito di un cliente)
Ext.namespace('DCS');

DCS = DCS || {};

// variabili globali usate anche in altri punti (tabsRicerca);
DCS.ExperianModel = {
	fields: [
	          {name:'IdCliente', type:'int'},
	          {name:'IdExperian', type:'int'},
	          {name:'CodCliente'},
	          {name:'OwnData'},
	          {name:'DataAnalisi', type:'date', dateFormat:'Y-m-d'},
	          {name:'Nominativo'},
	          {name:'CodiceFiscale'},
	          {name:'D4CScore', type:'int'},
	          {name:'D4CScoreIndex', type:'int'},
	          {name:'TipoFinanziamento', type:'int'},
	          {name:'MotivoFinanziamento', type:'int'},
	          {name:'StatoPagamenti', type:'int'},
	          {name:'ScadutoNonPagato', type:'float'},
	          {name:'MesiDaUltimoProtesto', type:'int'},
	          {name:'NumProtesti', type:'int'},
	          {name:'ImportoTotaleProtesti', type:'float'},
	          {name:'MesiDaUltimoDatoPubblico', type:'int'},
	          {name:'MesiDaUltimoDataPregiudizievole', type:'int'},
	          {name:'NumDatiPregiudizievoli', type:'int'},
	          {name:'ImportoTotaleDatiPregiudizievoli', type:'float'},
	          {name:'NumRichiesteCredito6mesi', type:'int'},
	          {name:'ImpRichiesteCredito6mesi', type:'float'},
	          {name:'NumRichiesteCredito3mesi', type:'int'},
	          {name:'NumRichiesteAccettate6mesi', type:'int'},
	          {name:'ImpRichiesteAccettate6mesi', type:'float'},
	          {name:'NumRichiesteAccettate3mesi', type:'int'},
	          {name:'ImpUltimaRichiestaFinanziata', type:'float'},
	          {name:'PeggiorStatusSpeciale'},
	          {name:'NumContratti12mesi', type:'int'},
	          {name:'NumContrattiAttivi', type:'int'},
	          {name:'NumContrattiStatus0', type:'int'},
	          {name:'NumContrattiStatus1_6', type:'int'},
	          {name:'NumContrattiStatus1_3', type:'int'},
	          {name:'NumContrattiStatus4_5', type:'int'},
	          {name:'NumContrattiStatus6', type:'int'},
	          {name:'NumContrattiPeggiorStatus0_2_12mesi', type:'int'},
	          {name:'NumContrattiPeggiorStatus1_2_12mesi', type:'int'},
	          {name:'NumContrattiPeggiorStatus3_5_12mesi', type:'int'},
	          {name:'NumContrattiPeggiorStatus6_12mesi', type:'int'},
	          {name:'NumContrattiDefault_12mesi', type:'int'},
	          {name:'PeggiorStatus_1_12mesi'},
	          {name:'PeggiorStatus_6mesi'},
	          {name:'PeggiorStatus_7_12mesi'},
	          {name:'PeggiorStatusCorrente'},
	          {name:'NumContrattiEstinti', type:'int'},
	          {name:'NumContrattiEstinti_6mesi', type:'int'},
	          {name:'NumContrattiDefault', type:'int'},
	          {name:'NumContrattiPerditaCessione', type:'int'},
	          {name:'NumContrattiPerditaCessione_12mesi', type:'int'},
	          {name:'MesiDaUltimoContrattoStatus0_12mesi', type:'int'},
	          {name:'MesiDaUltimoContrattoStatus3__6_12mesi', type:'int'},
	          {name:'MesiDaUltimoContrattoDefault', type:'int'},
	          {name:'TotaleImpScadutoNonPagato', type:'float'},
	          {name:'TotaleImpScadutoNonPagato_Status1_2', type:'float'},
	          {name:'TotaleImpScadutoNonPagato_Status3_5', type:'float'},
	          {name:'TotaleImpScadutoNonPagato_Status6_8', type:'float'},
	          {name:'TotaleSaldoInEssere', type:'float'},
	          {name:'TotaleImpegnoMensile', type:'float'},
	          {name:'NumContiRevolvingSaldoMinore75percento', type:'int'},
	          {name:'RapportoMaxSaldoLimiteCredito', type:'int'},
	          {name:'RapportoSaldoAutoSaldoTotale', type:'int'},
	          {name:'RapportoMaxScadutoSaldo', type:'int'},
	          {name:'NumPrestitiFinalizzati', type:'int'},
	          {name:'NumPrestitiPersonali', type:'int'},
	          {name:'NumContiRevolving', type:'int'}
		     ],
	columns: [
		      {dataIndex:'IdCliente',header:'Id Cliente', hidden:true,hideable:false,exportable:false,stateful:false},
		      {dataIndex:'IdExperian',header:'Id invio', hidden:true,hideable:false,exportable:false,stateful:false},
/*visibile*/  {dataIndex:'CodCliente',width:62, header:'Codice<br>Cliente',align:'left', resizable:true, sortable:true},
		      {dataIndex:'OwnData',header:'OwnData', hidden:true,hideable:false,exportable:false,stateful:false},
/*visibile*/  {dataIndex:'DataAnalisi',width:66,xtype:'datecolumn', format:'d/m/y', header:'Data<br>Analisi',align:'left', resizable:true, sortable:true},
/*visibile*/  {dataIndex:'Nominativo',width:160, header:'Nominativo',align:'left', resizable:true, sortable:true},
		      {dataIndex:'CodiceFiscale',width:50, header:'Cod.Fiscale',align:'left', resizable:true, sortable:true, hidden:true},
		      {dataIndex:'D4CScore',header:'D4C Score',hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/  {dataIndex:'D4CScoreIndex',width:50, header:'Score<br>Index',align:'center', resizable:true, sortable:true},
	          {dataIndex:'TipoFinanziamento', header:'Tipo<br>Finanziamento',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MotivoFinanziamento', header:'Motivo<br>Finanziamento', hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/  {dataIndex:'StatoPagamenti',width:50, header:'Stato<br>Pagam.',align:'center', resizable:true, sortable:true},
	          {dataIndex:'ScadutoNonPagato', header:'Scaduto<br>non pagato',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MesiDaUltimoProtesto', header:'Medi da ult.<br>Protesto', hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/  {dataIndex:'NumProtesti',width:60, header:'Num.<br>Protesti',align:'center', resizable:true, sortable:true},
/*visibile*/  {dataIndex:'ImportoTotaleProtesti',width:70, header:'Importo<br>Protesti', xtype:'numbercolumn',format:'0.000/i',align:'right', resizable:true, sortable:true},
	          {dataIndex:'MesiDaUltimoDatoPubblico', header:'Mesi da ult.<br>dato pubbl.',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MesiDaUltimoDataPregiudizievole', header:'Medi da ult.<br>Pregiudiz.',hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/  {dataIndex:'NumDatiPregiudizievoli',width:80, header:'Num. Dati<br>Pregiudizievoli',align:'center', resizable:true, sortable:true},
/*visibile*/  {dataIndex:'ImportoTotaleDatiPregiudizievoli',width:80, header:'Imp. Dati<br>Pregiudiz.', xtype:'numbercolumn',format:'0.000/i',align:'right', resizable:true, sortable:true},
/*visibile*/  {dataIndex:'NumRichiesteCredito6mesi',width:90, header:'Rich.Cred.<br>Ultimi 6 mesi',align:'center', resizable:true, sortable:true},
	          {dataIndex:'ImpRichiesteCredito6mesi', header:'Imp.rich. cred.<br>ultimi 6 mesi', hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumRichiesteCredito3mesi',header:'Num.rich. cred.<br>ultimi 3 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumRichiesteAccettate6mesi',header:'Num.rich.accett.<br>ultimi 6 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'ImpRichiesteAccettate6mesi',header:'Imp.rich.accett.<br>ultimi 6 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumRichiesteAccettate3mesi',header:'Num.rich.accett.<br>ultimi 3 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'ImpUltimaRichiestaFinanziata',header:'Imp.ult.rich.<br>finanziata',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'PeggiorStatusSpeciale',header:'Peggior<br>status spec.',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContratti12mesi',header:'Num. contratti<br>12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiAttivi',header:'Num. contratti<br>Attivi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiStatus0',header:'Num. contratti<br>Status 0',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiStatus1_6',header:'Num. contratti<br>Status 1-6',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiStatus1_3',header:'Num. contratti<br>Status 1-3',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiStatus4_5',header:'Num. contratti<br>Status 4-5',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiStatus6',header:'Num. contratti<br>Status 6',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPeggiorStatus0_2_12mesi',header:'Num. contratti peg.<br>status 0-2 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPeggiorStatus1_2_12mesi',header:'Num. contratti peg.<br>status 1-2 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPeggiorStatus3_5_12mesi',header:'Num. contratti peg.<br>status 3-5 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPeggiorStatus6_12mesi',header:'Num. contratti peg.<br>status 6 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiDefault_12mesi',header:'Num. contratti<br>default 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'PeggiorStatus_1_12mesi', header:'Peggior status<br>status 1-12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'PeggiorStatus_6mesi', header:'Peggior status<br>6 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'PeggiorStatus_7_12mesi',header:'Peggior status<br>7-12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'PeggiorStatusCorrente',header:'Peggior status<br>corrente',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiEstinti',header:'Num. contratti<br>estinti',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiEstinti_6mesi',header:'Num. contratti<br>estinti 6 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiDefault',header:'Num. contratti<br>default',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPerditaCessione',header:'Num. contratti<br>perdita/cess.',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContrattiPerditaCessione_12mesi',header:'Num. contratti<br>perdita/cess. 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MesiDaUltimoContrattoStatus0_12mesi',header:'Num. contratti<br>status 0 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MesiDaUltimoContrattoStatus3__6_12mesi',header:'Num. contratti<br>status 3-6 12 mesi',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'MesiDaUltimoContrattoDefault',header:'Num. mesi ultimo<br>contratto default',hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/      {dataIndex:'TotaleImpScadutoNonPagato',width:80, header:'Tot. Scad.<br>non pagato', xtype:'numbercolumn',format:'0.000/i',align:'right', resizable:true, sortable:true},
	          {dataIndex:'TotaleImpScadutoNonPagato_Status1_2',header:'Imp.scaduto<br>non pagato status 1-2',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'TotaleImpScadutoNonPagato_Status3_5',header:'Imp.scaduto<br>non pagato status 3-5',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'TotaleImpScadutoNonPagato_Status6_8',header:'Imp.scaduto<br>non pagato status 6-8',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'TotaleSaldoInEssere',header:'Saldo in essere',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'TotaleImpegnoMensile',header:'Impegno mensile',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'NumContiRevolvingSaldoMinore75percento',header:'Num.Conti<br>Revolv. saldo 75%',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'RapportoMaxSaldoLimiteCredito',header:'Rapporto<br>saldo/max limite',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'RapportoSaldoAutoSaldoTotale',header:'Rapporto<br>saldo auto/totale',hidden:true,hideable:false,exportable:true,stateful:false},
	          {dataIndex:'RapportoMaxScadutoSaldo',header:'Rapporto<br>max scaduto/saldo',hidden:true,hideable:false,exportable:true,stateful:false},
/*visibile*/      {dataIndex:'NumPrestitiFinalizzati',width:80, header:'Num.Prestiti<br>Finalizz.',align:'center', resizable:true, sortable:true},
/*visibile*/      {dataIndex:'NumPrestitiPersonali',width:80, header:'Num.Prestiti<br>Personali',align:'center', resizable:true, sortable:true},
	          {dataIndex:'NumContiRevolving',header:'Num. conti<br>Revolving',hidden:true,hideable:false,exportable:true,stateful:false}
	    ]
}; 

DCS.GridExperian = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	pagesize: PAGESIZE,
	titlePanel: '',
	task: '',
	sqlExtraCondition : '',
	filters: null,
	initComponent : function() {
 
		var fields = [];
		var columns = [];
		switch (this.task) {
		case 'sintesi':
			fields = [
			    {name:'IdExperian', type:'int'},
			    {name:'DataInvio', type:'date', dateFormat:'Y-m-d H:i:s'},
			    {name:'DataRisposta', type:'date', dateFormat:'Y-m-d H:i:s'},
			    {name:'NumClienti', type:'int'},
			    {name:'Score_1', type:'int'},
			    {name:'Score_2', type:'int'},
			    {name:'Score_3', type:'int'},
			    {name:'Score_4', type:'int'},
			    {name:'Score_5', type:'int'},
			    {name:'Score_6', type:'int'},
			    {name:'Score_7', type:'int'},
			    {name:'Score_8', type:'int'},
			    {name:'Score_9', type:'int'},
			    {name:'Score_10', type:'int'}
			];
			columns = [
		        {dataIndex:'IdExperian',width:45, header:'Id',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'DataInvio',width:90,xtype:'datecolumn', format:'d/m/y H:i:s', header:'Data Invio',align:'left', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'DataRisposta',width:90,xtype:'datecolumn', format:'d/m/y H:i:s', header:'Data Risposta',align:'left', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'NumClienti',width:70, header:'Num. pos.',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_1',width:50, header:'Score 1',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_2',width:50, header:'Score 2',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_3',width:50, header:'Score 3',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_4',width:50, header:'Score 4',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_5',width:50, header:'Score 5',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_6',width:50, header:'Score 6',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_7',width:50, header:'Score 7',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_8',width:50, header:'Score 8',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_9',width:50, header:'Score 9',align:'center', resizable:true, sortable:true, hidden:false},
		        {dataIndex:'Score_10',width:55, header:'Score 10',align:'center', resizable:true, sortable:true, hidden:false},
		        {width:5, header:' ', resizable:true} // some space
            ];
			break;
		case 'generale':
			fields = DCS.ExperianModel.fields;
			columns = DCS.ExperianModel.columns;
			columns.push({width:5, header:' ',resizable:true}); // aggiungi una piccola colonna vuota.
			break;
		case 'coda':
			var selectionCheckbox = new Ext.grid.CheckboxSelectionModel({printable:false});
			fields = [
						{name: 'IdCliente', type: 'int'},
				        {name: 'CodCliente'},
				        {name: 'Nominativo'},
						{name: 'AbbrClasse'},
						{name: 'Agenzie'},
						{name: 'NumPratiche', type: 'int'},
						{name: 'ListaPratiche'},
						{name: 'DataFineAffido', type:'date', dateFormat:'Y-m-d'},
						{name: 'TotaleImpScadutoNonPagato', type:'float'}
				];
				columns = [selectionCheckbox,
				      {dataIndex:'IdCliente',hidden:true,hideable:false,exportable:true,stateful:false},
				      {dataIndex:'CodCliente',width:62, header:'Codice<br>Cliente',align:'left', resizable:true, sortable:true},
				      {dataIndex:'Nominativo',width:130, header:'Nominativo',align:'left', resizable:true, sortable:true},
				      {dataIndex:'NumPratiche',width:60, header:'Num.Pratiche<br>a recupero',align:'center', resizable:true, sortable:true},
				      {dataIndex:'ListaPratiche',width:100, header:'Pratiche',align:'left', resizable:true, sortable:true},
				      {dataIndex:'AbbrClasse',width:100, header:'Classificazione',align:'left', resizable:true, sortable:true},
				      {dataIndex:'Agenzie',width:100, header:'Agenzia',align:'left', resizable:true, sortable:true},
				      {dataIndex:'DataFineAffido',width:70,xtype:'datecolumn', format:'d/m/y', header:'Data<br>fine affido',align:'center', resizable:true, sortable:true},
				      {dataIndex:'TotaleImpScadutoNonPagato', width:90, header:'Tot. Scaduto<br>non pagato', xtype:'numbercolumn',format:'0.000/i',align:'right', resizable:true, sortable:true},
			          {width:5, header:' ',resizable:true} // some space
				];

			break;
		case 'candidati':
			fields = [
					{name: 'IdCliente', type: 'int'},
			        {name: 'CodCliente'},
			        {name: 'Nominativo'},
					{name: 'AbbrClasse'},
					{name: 'Agenzie'},
					{name: 'NumPratiche', type: 'int'},
					{name: 'ListaPratiche'},
					{name: 'DataFineAffido', type:'date', dateFormat:'Y-m-d'},
					{name: 'TotaleImpScadutoNonPagato', type:'float'}
			];
			columns = [
			      {dataIndex:'IdCliente',hidden:true,hideable:false,exportable:true,stateful:false},
			      {dataIndex:'CodCliente',width:62, header:'Codice<br>Cliente',align:'left', resizable:true, sortable:true},
			      {dataIndex:'Nominativo',width:130, header:'Nominativo',align:'left', resizable:true, sortable:true},
			      {dataIndex:'NumPratiche',width:60, header:'Num.Pratiche<br>a recupero',align:'center', resizable:true, sortable:true},
			      {dataIndex:'ListaPratiche',width:100, header:'Pratiche',align:'left', resizable:true, sortable:true},
			      {dataIndex:'AbbrClasse',width:100, header:'Classificazione',align:'left', resizable:true, sortable:true},
			      {dataIndex:'Agenzie',width:100, header:'Agenzia',align:'left', resizable:true, sortable:true},
			      {dataIndex:'DataFineAffido',width:70,xtype:'datecolumn', format:'d/m/y', header:'Data<br>fine affido',align:'center', resizable:true, sortable:true},
			      {dataIndex:'TotaleImpScadutoNonPagato', width:90, header:'Tot. Scaduto<br>non pagato', xtype:'numbercolumn',format:'0.000/i',align:'right', resizable:true, sortable:true},
		          {width:5, header:' ',resizable:true} // some space
			];
			break;
		}

		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/experian.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task},
			remoteSort: true,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});
		
 		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				forceFit: true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
	            hideGroupedColumn: true,
	            getRowClass : function(record, rowIndex, p, store){
	                if(rowIndex%2)
	                {
				        return 'grid-row-azzurrochiaro';
	                }
			        return 'grid-row-azzurroscuro';
				}
			}),
		    columns: columns,
		    listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					var parametri,titolo;
					switch (this.task) {
						case 'coda':
						case 'candidati':
							// Al doppio click, mostra la lista pratiche del cliente cliccato
							parametri = {IdC: 'PraticheSoggetto', 
									     searchFields: {IdCliente: rec.get('IdCliente')},
									     titolo: 'Lista pratiche di '+rec.get('Nominativo')};
							titolo    =  'Lista pratiche di '+rec.get('Nominativo')+ ' (come intestatario o coobbligato)';
							break;
						case 'sintesi':
							// Al doppio click, mostra la lista delle posizioni (=clienti) incluse nell'invio a Experian
							parametri = {IdC: 'DettaglioExperianInvio', 
								 searchFields: {IdExperian: rec.get('IdExperian')},
							     titolo: 'Lista posizioni Experian'};
							titolo    =  'Lista posizioni Experian lotto '+rec.get('IdExperian');
							break;
						case 'generale':
							// Al doppio click, mostra la lista dei singoli risultati ricevuti da Experian per lo stesso cliente
							parametri = {IdC: 'DettaglioExperianCliente', 
							     searchFields: {IdCliente: rec.get('IdCliente')},
							     titolo: 'Lista interrogazioni Experian'};
							titolo    =  'Lista interrogazioni Experian cliente '+rec.get('Nominativo');
							break;
					}
					var pnl = new DCS.pnlSearch(parametri);
					var win = new Ext.Window({
						width: 1100, height:700, minWidth: 900, minHeight: 700,
						autoHeight:true,modal: true,
					    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
					    title: titolo,
						constrain: true,
						items: [pnl]
					});
					win.show();
					pnl.activation.call(pnl);							
				},
				activate: function(pnl) {
					this.store.setBaseParam('attiva','Y'); 
					var lastOpt = this.store.lastOptions;
					if (!lastOpt || lastOpt.params==undefined) {
						if (this.pagesize>0) {
							this.store.load({
								params: { //this is only parameters for the FIRST page load, use baseParams above for ALL pages.
									start: 0, //pass start/limit parameters for paging
									limit: this.pagesize
								}
							}); 
						} else {
							this.store.load(); 
						}
					}
				},
				scope: this
			}
	    });
		var tbarItems = [
							{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
							'->', {type: 'button', text: 'Stampa elenco',  icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}, scope:this},
			                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png',  
									handler: function()
									{
										Ext.ux.Printer.exportXLS(this);
									}, scope:this},
			                '-', helpButton("Listaexperian"),' '
						];
		if (this.task=='coda') { // per il terzo tab, aggiunge il bottone "rimuovi"
			this.selModel = selectionCheckbox; // permette di usare col.1 come selezionatore di riga
			tbarItems.splice(1,0,{
				   id:'remove_selected',
				   xtype: 'button',
				   style: 'width:15; height:15',
				   icon: 'ext/examples/shared/icons/fam/delete.gif',
				   text: 'Rimuovi selezionate',
				   tooltip: 'Rimuove dalla lista le pratiche selezionate',
				   handler: rimuovePratiche,
				   sm: selectionCheckbox, // aggiunge proprietà custom per passare la colonna di selezione 
				   gstore: this.store // aggiunge proprietà custom per passare lo store
				});
		}


		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.gstore,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridExperian.superclass.initComponent.call(this, arguments);
		
	}

});

DCS.Experian = function(){
	return {
		create: function(){
			
			// TabPanel che contiene le varie liste
			var tabPanel = new Ext.TabPanel({
				activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: []
			});		
			
			var grid1 = new DCS.GridExperian({
				titlePanel: 'Lista dei lotti inviati a Experian (doppio click per vedere il dettaglio del lotto)',
				title: 'Lista sintetica',
				task: "sintesi"
			});
			tabPanel.add(grid1);

			var grid2 = new DCS.GridExperian({
				titlePanel: 'Lista generale delle posizioni inviate (doppio click per vedere la cronologia di tutte le analisi)',
				title: 'Lista generale',
				task: "generale"
			});
			tabPanel.add(grid2);

			var grid3 = new DCS.GridExperian({
				titlePanel: 'Lista delle posizioni accodate manualmente (doppio click per vedere tutte le pratiche associate)',
				title: 'Posizioni accodate',
				task: "coda"
			});
			tabPanel.add(grid3);

			var grid4 = new DCS.GridExperian({
				titlePanel: 'Lista delle posizioni candidate al prossimo invio (doppio click per vedere tutte le pratiche associate)',
				title: 'Candidati all\'invio',
				task: "candidati"
			});
			tabPanel.add(grid4);
			return tabPanel;
		}
	};
	
}();

//----------------------------------------------------------------
// rimuovePratiche
// Rimuove dalla lista dei candidati le pratiche selezionate
//----------------------------------------------------------------
function rimuovePratiche(btn,pressed) 
{
	// costruice array degli IdCliente selezionati
	var sel = btn.sm.getSelections();
	var ids = new Array();
	for (i=0; i<sel.length; i++) 
		ids.push(sel[i].get('IdCliente'));

	if (ids.length==0)
		Ext.Msg.alert("","Nessuna pratica selezionata");
	else 
	{
		var myMask = new Ext.LoadMask(Ext.getBody(), {
			msg: "Rimozione in corso..."
		});
		myMask.show();
		Ext.Ajax.request({
			url: 'server/AjaxRequest.php', method:'POST',
			params: {task: 'exec', sql: 'DELETE FROM experianqueue WHERE IdCliente IN (0' + ids.join(',')+ ')'},
			scope: this,
			failure: function() 
					{	myMask.hide();
						Ext.Msg.alert("Operazione non riuscita", "Errore di comunicazione");
					}, 
			success: function(result, request)
					{
						myMask.hide();
						eval('var result = ' + result.responseText);
						if (result.error)
							Ext.Msg.alert("Operazione non riuscita", result.error);
						else
						{
							btn.gstore.reload();
						}
					}
		});
	}
}