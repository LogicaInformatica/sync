// Crea namespace DCS
Ext.namespace('DCS');

DCS.recordPratica = Ext.data.Record.create([
		{name: 'area'},
		{name: 'Prodotto'},
		{name: 'Venditore'},
		{name: 'TipoPagamento'},
		{name: 'CodCliente'},
		{name: 'NomeCliente'},
		{name: 'DataNCli', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'LuogoNCli'},
		{name: 'CodStato'},
		{name: 'Stato'},
		{name: 'CodClasse'},
		{name: 'Classificazione'},
		{name: 'CodStatoRecupero'},
		{name: 'StatoRecupero'},
		{name: 'CodUtente'},
		{name: 'NomeUtente'},
		{name: 'NomeAgenzia'},
		{name: 'Rata', type: 'int'},
		{name: 'Insoluti', type: 'int'},
		{name: 'Giorni', type: 'int'},
		{name: 'Importo', convert:numdec_it, type: 'float'},
		{name: 'DataScadenza', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'IdFamiglia', type: 'int'},
		{name: 'IdContratto', type: 'int'},
		{name: 'IdContrattoDerivato', type: 'int', useNull: true},
		{name: 'IdCliente', type: 'int'},
		{name: 'CodContratto'},										// Num.Pratica
		{name: 'IdProdotto', type: 'int'},							// Prodotto
		{name: 'IdStatoContratto', type: 'int'},					// stato contratto
		{name: 'IdTipoPagamento', type: 'int'},						// tipo pagamento
		{name: 'CodiceFiscale'},
		{name: 'PartitaIVA'},
		{name: 'ImpValoreBene', convert:numdec_it,type: 'float', useNull: true},
		{name: 'ImpFinanziato', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpAnticipo', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpErogato', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpRata', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpRataFinale', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpRiscatto', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpInteressi', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpSpeseIncasso', convert:numdec_it, type: 'float', useNull: true},
		{name: 'ImpBollo', convert:numdec_it, type: 'float', useNull: true},
		{name: 'PercTasso', convert:numdec_it, type: 'float', useNull: true},
		{name: 'PercTaeg', convert:numdec_it, type: 'float', useNull: true},
		{name: 'PercTassoReale', convert:numdec_it, type: 'float', useNull: true},
		{name: 'NumRate', type: 'int', useNull: true},
		{name: 'ImpInteressiDilazione', convert:numdec_it, type: 'float', useNull: true},
		{name: 'NumMesiDilazione', type: 'int', useNull: true},
		{name: 'DescrBene'},
		{name: 'CodBene'},
		{name: 'CodTabFinanziaria'},
		{name: 'DataContratto', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'DataDecorrenza', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'DataPrimaScadenza', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'DataUltimaScadenza', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'DataChiusura', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'ABI', type: 'int', useNull: true},
		{name: 'CAB', type: 'int', useNull: true},
		{name: 'IBAN'},
		{name: 'TitoloBanca'},// ok
		{name: 'IdFiliale', type: 'int', useNull: true},				// 
		{name: 'IdDealer', type: 'int', useNull: true},					// venditore
		{name: 'IdTipoSpeciale', type: 'int', useNull: true},
		{name: 'IdClasse', type: 'int', useNull: true},
		{name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'LastUser'},
		{name: 'IdAgenzia', type: 'int', useNull: true},
		{name: 'IdOperatore', type: 'int', useNull: true},
		{name: 'IdCompagnia', type: 'int', useNull: true},
		{name: 'DataCambioStato', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'DataCambioClasse', type: 'date', dateFormat: 'Y-m-d'},
		{name: 'DataInizioAffido', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'DataFineAffido', type: 'date', dateFormat: 'Y-m-d', convert:date_it},
		{name: 'NomePuntoVendita'},
		{name: 'NomeVenditore'},
		{name: 'TitTipoSpec'}, //motivo override
		{name: 'AutoreOverride'},
		{name: 'Attributo'},
		{name: 'Telefono'},
		{name: 'sesso'},
		{name: 'TitoloCategoria'},
		{name: 'IntestatarioConto'},
		{name: 'IdPianoRientro'},
		{name: 'TitoloAzione'},
		{name: 'Garanzie'},
		{name: 'IdExperian'},
        {name:'D4CScoreIndex', type:'int'},
        {name:'StatoPagamenti', type:'int'},
        {name:'NumProtesti', type:'int'},
        {name:'ImportoTotaleProtesti', type:'float'},
        {name:'NumDatiPregiudizievoli', type:'int'},
        {name:'ImportoTotaleDatiPregiudizievoli', type:'float'},
        {name:'NumRichiesteCredito6mesi', type:'int'},
        {name:'ImpRichiesteCredito6mesi', type:'float'},
        {name:'TotaleImpScadutoNonPagato', type:'float'},
        {name:'TotaleImpegnoMensile', type:'float'},
        {name:'NumPrestitiFinalizzati', type:'int'},
        {name:'NumPrestitiPersonali', type:'int'}
      ]);

DCS.recordRecapito = Ext.data.Record.create([
		{name: 'IdRecapito'},
		{name: 'IdCliente'},
		{name: 'IdContratto'},
		{name: 'modificabile'},//, convert:bool_db},
		{name: 'IdTipoRecapito'},
		{name: 'TitoloTipoRecapito'},
		{name: 'ProgrRecapito'},
		{name: 'Nome'},
		{name: 'Indirizzo'},
		{name: 'Localita'},
		{name: 'CAP'},
		{name: 'SiglaProvincia'},
		{name: 'SiglaNazione'},
		{name: 'Telefono'},
		{name: 'Cellulare'},
		{name: 'Fax'},
		{name: 'Email'},
		{name: 'Controparte'}
	]);

DCS.FormRecapito = function(){
	var win;
	var keyIdCliente;
	var keyIdContratto;
	var callGrid;
	var datiPianoRecupero;
	var lastAzioneSpeciale;
	
	
	var formRecapito = new Ext.form.FormPanel({
//		autoHeight: true,
		frame: true,
		bodyStyle: 'padding:5px 5px 0',
		border: false,
		trackResetOnLoad: true,
		reader: new Ext.data.ArrayReader({}, DCS.recordRecapito),
		items: [{
			xtype: 'fieldset',
			autoHeight: true,
			layout: 'column',
			items: [{
				xtype: 'panel',
				layout: 'form',
				labelWidth: 90,
				columnWidth: 1,
				defaults: {xtype: 'textfield', anchor: '97%'},
				items: [{
					xtype: 'combo',
					fieldLabel: 'Tipo recapito',
					allowBlank: false,
					hiddenName: 'IdTipoRecapito',
					typeAhead: false, 
					triggerAction: 'all',
					forceSelection: true,
					lazyRender: true,	//should always be true for editor
					store: DCS.Store.dsTipoRecapito,
					displayField: 'TitoloTipoRecapito',
					valueField: 'IdTipoRecapito'
				}, {
					hidden: true,
					name: 'IdRecapito'
				}, {
					hidden: true,
					name: 'IdCliente'
				}, {
					hidden: true,
					name: 'IdContratto'
				}, {
					fieldLabel: 'Nominativo',
					name: 'Nome'
				}, {
					fieldLabel: 'Indirizzo',
					name: 'Indirizzo'
				}, {
					xtype: 'compositefield',
					fieldLabel: '',
					items: [{
						xtype: 'textfield',
						width: 50,
						name: 'CAP'
					}, {
						xtype: 'textfield',
						flex: 1,
						name: 'Localita'
					}, {
						xtype: 'combo',
						width: 45,
						name: 'SiglaProvincia',
						forceSelection: true,
						editable: true,
						mode: 'local',
						displayField: 'sigla',
						valueField: 'sigla',
						lazyInit: false,
						store: DCS.Store.dsProvince,
						triggerAction: 'all',
						autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '2'},
						style : {textTransform: "uppercase"}
					}, {
						xtype: 'textfield',
						width: 30,
						name: 'SiglaNazione'
					}]
					
			}, {
					fieldLabel: 'E-mail',
					name: 'Email',
					vtype: 'email'
				}, {
					//xtype:'numberfield',
					//allowNegative: false,
					//allowDecimals:false,
					fieldLabel: 'Telefono',
					name: 'Telefono'						
				}, {
					fieldLabel: 'Cellulare',
					name: 'Cellulare'
					//vtype:'cell_list'
				}, {
					//xtype:'numberfield',
					//allowNegative: false,
					//allowDecimals:false,
					fieldLabel: 'Fax',
					name: 'Fax'
				},{
					fieldLabel: 'modificabile',
					name: 'modificabile',
					hidden: true
				}]
			}]
		}],
		buttons: [{
			text: 'Salva',
			handler: function() {
				if (formRecapito.getForm().isDirty()) {	// qualche campo modificato
					formRecapito.getForm().submit({
						url: 'server/edit_recapiti.php',
						method: 'POST',
						params: {task: 'save'},
						success: function(){
							if (callGrid != undefined) {
								callGrid.getStore().reload();
							}
							win.hide();
							var campiModificati = formRecapito.getForm().getFieldValues(true);
							if ((campiModificati ['Telefono']!==undefined) || (campiModificati ['Cellulare']!==undefined))
								formRecapito.fireEvent('clickEditRecapito');
						},
						failure: function(frm, action){
							Ext.Msg.alert('Errore', action.result.error);
						},
						scope: this,
						waitMsg: 'Salvataggio in corso...'
					});
				} else
					win.hide();
			},
			scope: this
		}, {
			text: 'Annulla',
			handler: function(){
				if (formRecapito.getForm().isDirty()) {
					Ext.Msg.confirm('', 'I valori sono stati modificati, uscire senza salvare?', function(btn, text){
    					if (btn == 'yes'){
				        	win.hide();
					    }
					});
				} else
					win.hide();
			},
			scope: this
		}]
	});
	
	return {
		getForm: function (){return formRecapito;},
		
		show: function(rec, grid){
			callGrid = grid;
			formRecapito.getForm().loadRecord(rec);

			if (!win) {
				win = new Ext.Window({
					modal: true,
					width: 600,
					height: 340,
					minWidth: 560,
					minHeight: 340,
					layout: 'fit',
					plain: true,
					constrain: true,
					bodyStyle: 'padding:5px;',
					title: 'Recapito',
					items: formRecapito,
					closable: false,
					tools: [helpTool("Recapito")]
				});
			}
			win.show();
		}
	};

};

// Griglia usata sia per i recapiti (pagina principale) sia per la lista altriSoggetti
DCS.GridRecapiti = Ext.extend(Ext.grid.GridPanel, {
	key: 0,
	altriSoggetti: false,
	frmRecapito: undefined,
	isStorico: false,
	
	initComponent: function() {
		var schema = MYSQL_SCHEMA+(this.isStorico?'_storico':'');
		var dsRecapiti = new Ext.data.GroupingStore({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader(
				{root: 'results'}, DCS.recordRecapito
	        ),
			groupField: 'Controparte'
		});        

	    function renderRecap(value, p, r){
	        return String.format('{0}{1}<br/>{2} {3} {4} {5}', 
				r.data['Nome']!=null?r.data['Nome']+'<br/>':'', 
				r.data['Indirizzo']!=null?r.data['Indirizzo']:'',
				r.data['CAP']!=null?r.data['CAP']:'',
				r.data['Localita']!=null?r.data['Localita']:'',
				r.data['SiglaProvincia']!=''&&r.data['SiglaProvincia']!=null?'('+r.data['SiglaProvincia']+')':'',
				r.data['SiglaNazione']!='IT'&&r.data['SiglaNazione']!=null?' - '+r.data['SiglaNazione']:'');
	    }

	    function renderTel(value, p, r){
	        return String.format('<font size="2"> {0} {1}<br/>{2}<br/>{3} </font>', 
	        	r.data['Telefono']!=null && r.data['Telefono']!=''?'<b>tel:</b> '+r.data['Telefono']:'', 
				r.data['Cellulare']!=null && r.data['Cellulare']!=''?'<b>cell:</b> '+r.data['Cellulare']:'',
				r.data['Fax']!=null && r.data['Fax']!=''?'<b>fax:</b> '+r.data['Fax']:'',
				r.data['Email']!=null && r.data['Email']!=''?'<b>email:</b> '+r.data['Email']:'');
	    }

	    function renderRec(value, p, r){
	        return String.format('{0}','<font size="2">'+r.data['TitoloTipoRecapito']+'</font>');
	    }

		var cols = [{
				dataIndex: 'TitoloTipoRecapito',
				header: this.altriSoggetti?'Tipo recapito':'Tipo recapito',
				width: 135,
				fixed: true, 
				groupable: false,
				renderer: renderRec,
				css: 'cursor:pointer'
			}, {
				dataIndex: 'Indirizzo',
				header: this.altriSoggetti?'Indirizzo':'Indirizzo',
				groupable: false,
				renderer: renderRecap
			}, {
				dataIndex: 'Telefono',
				header: this.altriSoggetti?'Telefoni e indirizzo di posta':'Telefoni e indirizzo di posta',
				groupable: false,
				renderer: renderTel
			}, {
				xtype: 'actioncolumn',
	            width: 40,
				fixed: true,
				header: this.altriSoggetti?'':'Mod.',
	            sortable:false, filterable:false, resizable:false, fixed:true,
	            items: (!CONTEXT.MOD_PRATICA || this.isStorico)?[]:
	            	[{
						tooltip: 'Modifica',
						iconCls: 'mod-recapito',
						handler: function(grid, rowIndex) {
	                        var rec = grid.store.getAt(rowIndex);
	                        this.frmRecapito.show(rec, grid);
	                    },
						scope: this
					},{
						getClass: function(v,meta,rec) {
						 	if (rec.get('modificabile')=='N') {
								this.items[1].tooltip = 'Annulla';
								return 'disp-row';
	                        } else {
								this.items[1].tooltip = 'Elimina';
								return 'del-row';
	                        }
					 	},
					 	handler: function(grid, rowIndex) {
					 		var rec = grid.store.getAt(rowIndex);
                        	Ext.Ajax.request({
                        		url : 'server/edit_recapiti.php' , 
                        		params : {task: 'delete',IdRecapito: rec.get('IdRecapito'),IdCliente: rec.get('IdCliente'),modificabile: rec.get('modificabile')},
                        		method: 'POST',
                        		success: function ( result, request ) {
                        			grid.store.reload();
        							grid.fireEvent('clickEditRecapito');
                        		},
                        		failure: function ( result, request) { 
                        			Ext.MessageBox.alert('Errore', result.responseText); 
                        		} 
                        	});
					 	}
					}]
			}];

		if (this.altriSoggetti) {
			var sogg = [{
				dataIndex: 'Controparte',
				header: 'Controparte',
				fixed: true,
				groupable: true,
				hidden: true
			}];
			cols = sogg.concat(cols);
		}
		
		var colModel = new Ext.grid.ColumnModel({
        	columns: cols,
        	defaults: {
            	sortable: false,
            	menuDisabled: true
        	}
    	});

    	var vw,dblclick,groupdblclick;
		if (this.altriSoggetti) {
			vw = new Ext.grid.GroupingView({
				headersDisabled: false,
        		autoFill: true,
				forceFit: false,
        		showGroupName : false
    		});
			// al doppio click su una riga di indirizzo va al dettaglio pratiche del coobbligato
			dblclick = function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					this.showListaPraticheSoggetto(rec.json.IdCliente,rec.json.Soggetto);
			};
			// idem al doppio click su una riga di gruppo
			groupdblclick = function( grid, groupField, groupValue, e ) {
					var rowIndex = this.store.find('Controparte',groupValue);
					if (rowIndex>=0) {
						var rec = this.store.getAt(rowIndex);
						this.showListaPraticheSoggetto(rec.json.IdCliente,rec.json.Soggetto);
					}
			};
		} else {
			vw = new Ext.grid.GridView({
				headersDisabled: true,
        		autoFill: true,
				forceFit: false
    		});
			dblclick = function () {};
			groupdblclick = function () {};
		}

		//--------------------------------------------------------
		// Visualizza lista pratiche collegato ad un soggetto
		//--------------------------------------------------------
		this.showListaPraticheSoggetto = function(IdCliente,Nome,isStorico) {
			var pnl = new DCS.pnlSearch({IdC: 'PraticheSoggetto',
				                         titolo:"Lista pratiche collegate a "+Nome, 
				                         searchFields:{IdCliente:IdCliente}});
			var win = new Ext.Window({
	    		width: 1100, height:700, 
	    		autoHeight:true,modal: true,
	    	    layout: 'fit', plain:true, bodyStyle:'padding:5px;',
	    	    title: 'Ricerca pratiche',
	    		constrain: true,
				items: [pnl]
	        });
	    	win.show();
			pnl.activation.call(pnl);
	    };

		
		Ext.apply(this, {
			title: this.altriSoggetti?'Coobbligati e altri soggetti (fare <font color="#B00000">doppio click</font> su una riga per vedere la lista delle pratiche collegate a ciascuno)':'',
			loadMask: true,
			store: dsRecapiti,
			border: false,
			colModel: colModel,
			view: vw,
			listeners: {rowdblclick: dblclick, groupdblclick: groupdblclick} 
		});

		DCS.GridRecapiti.superclass.initComponent.call(this);
		
		var sql;
		if (this.altriSoggetti)
			sql = "SELECT * FROM "+schema+".v_altri_soggetti WHERE IdContratto=" + this.key;
		else
			sql = "SELECT * FROM "+schema+".v_recapito v WHERE v.IdCliente=" + this.key + "  and v.FlagAnnullato = 'N' ORDER BY v.ProgrRecapito";

		dsRecapiti.load({
			params:{
				sql: sql
			}
		});
		this.addEvents('clickEditRecapito');
	}
	
});

DCS.DettaglioPratica = Ext.extend(Ext.TabPanel, {
	idContratto: 0,
	numPratica: '',
	cliente: 0,
	listStore: null,
	rowIndex: -1,
	isStorico: false,
	
	initComponent: function() {

		var frmRecapito = new DCS.FormRecapito();

		var recordPartite;
		var sqlPartite;
		var colPartite;
		var schema = MYSQL_SCHEMA+(this.isStorico?'_storico':'');
// Dal 20/10/2011 anche le agenzie vedono lo stesso partitario degli interni		
//		if (CONTEXT.InternoEsterno=='I') {
			recordPartite = [
				    {name: 'IdInsoluto', type: 'int',id:'Ins'},
					{name: 'IdContratto', type: 'int'},
					{name: 'NumRata', type: 'int'},
					{name: 'DataRegistrazione', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'DataCompetenza', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'DataScadenza', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'DataValuta', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'TitoloTipoMovimento'},
					{name: 'TitoloTipoInsoluto'},
					{name: 'Debito', type: 'float', useNull: true},
					{name: 'Credito', type: 'float', useNull: true}];
					
			vistaPartite = schema+".v_partite";
						 
			colPartite = [{dataIndex:'NumRata',width:40,header:'Rata',sortable:true},
						{dataIndex:'DataScadenza',width:100,header:'Data scadenza',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataRegistrazione',width:100,header: 'Data reg.',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataCompetenza',width:100,header:'Data comp.',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataValuta',width:100,header:'Data valuta',sortable:true,renderer:DCS.render.date},
					{dataIndex:'TitoloTipoMovimento',width:180,header:'Tipo movimento',sortable:false},
					{dataIndex:'TitoloTipoInsoluto',width:100,header:'Causale insol.',sortable:false},
					{dataIndex:'Debito',width:70,header:'Debito',align:'right',sortable:false,xtype:'numbercolumn',format:'0.000,00/i'},
					{dataIndex:'Credito',width:70,header:'Credito',align:'right',sortable:false,xtype:'numbercolumn',format:'0.000,00/i'}];

		var dsPartite = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/partiteDettaglioPratica.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read',idc:this.idContratto,version:'new',schema: schema},
			reader:  new Ext.data.JsonReader(
				{root: 'results'}, recordPartite
	        ),
	        autoLoad:true,
			sortInfo:{field: 'NumRata', direction: "ASC"}
		});
		        
		var dsAltriContratti = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader(
				{root: 'results'},
				   [{name: 'flagAssegnato',type:'int'},
				    {name: 'IdCliente'},
				  	{name: 'Ruolo'},
					{name: 'cliente'},
					{name: 'IdContratto'},
					{name: 'numPratica'},
					{name: 'Prodotto'},
					{name: 'Stato'},
					{name: 'StatoRecupero'},
					{name: 'ImpFinanziato',type:'float'},
					{name: 'Importo',type:'float'},
					{name: 'Agenzia'}] 
		    ),
			sortInfo:{field: 'numPratica', direction: "ASC"}
		});
		        
		// True se l'altro contratto pu� essere aperto perch� affidato alla stessa agenzia o perche l'operatore � interno
		var flgAssegnato = '1';
		if (CONTEXT.InternoEsterno == 'E') {
			flgAssegnato = "ifnull((pc.IdAgenzia="+CONTEXT.IdReparto+" OR " + CONTEXT.IdReparto + 
					" IN (SELECT IdAgenzia FROM assegnazione ass WHERE ass.IdContratto=pc.IdContratto AND ass.DataFin>=CURDATE())),0)";
		}

		dsAltriContratti.load({
			params:{
				sql: "SELECT pc.*, " + flgAssegnato + " as flagAssegnato FROM "+schema+".v_pratiche_collegate pc WHERE IdCliente=" + this.cliente + 
					 " AND IdContratto!=" + this.idContratto + " ORDER BY numPratica"
			}
		});

		keyIdCliente=this.cliente;
		keyIdContratto=this.idContratto;
		
		var datiGenerali = new DCS.PraticaDatiGenerali();
		
		datiPianoRientro = new Ext.form.FormPanel({
			title:'Piano rientro',		//il titolo � usato per testare il tab
			id: 'panelPianoRientro',
			layout: 'fit',
			items: [DCS.PianoRientro(keyIdContratto)]
		});

		//----------------------------------------------------------------------------------------------------------------
				
		var datiGeneraliSecondari = new Ext.form.FormPanel({
			title:'Dettagli pratica',		//il titolo � usato per testare il tab
//			autoHeight: true,
			frame: true,
			bodyStyle:'margin: 0; padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordPratica
			}),
			items: [{
				xtype:'fieldset', title:'Dati finanziari', autoHeight:true,
				items:[{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .53, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:70, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Valore bene',	name:'ImpValoreBene',	style:'text-align:right', width:90}]//1st column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:57, columnWidth:.47,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Finanziato',	name:'ImpFinanziato',	style:'text-align:right', width:90}]//2nd column 1st row
						}]//end sub fieldset left column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .47, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:50, columnWidth: .52,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Anticipo',	name:'ImpAnticipo',	style:'text-align:right', width:90}]//3rd column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:65, columnWidth:.48,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'94%'},
							items: [{fieldLabel:'Erogato',	name:'ImpErogato',	style:'text-align:right', width:90}]//4th column 1st row
						}]//end sub fieldset right column
					}]
				},{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .53, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:70, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Rata finale',	name:'ImpRataFinale',	style:'text-align:right', width:90}]//1st column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:57, columnWidth:.47,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Riscatto', cls: 'txt_evid', name:'ImpRiscatto', style:'text-align:right; background:#ffff60', width:90}]//2nd column 1st row
						}]//end sub fieldset left column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .47, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:50, columnWidth: .52,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Interessi',	name:'ImpInteressi',	style:'text-align:right', width:90}]//3rd column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:65, columnWidth:.48,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'94%'},
							items: [{fieldLabel:'Spese inc. ',	name:'ImpSpeseIncasso',	style:'text-align:right', width:80}]//4th column 1st row
						}]//end sub fieldset right column
					}]
				},{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .53, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:70, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Tasso',	name:'PercTasso',	style:'text-align:right', width:90}]//1st column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:57, columnWidth:.47,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'TAEG',		name:'PercTaeg',	style:'text-align:right', width:90}]//2nd column 1st row
						}]//end sub fieldset left column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .47, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:65, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'88%'},
							items: [{fieldLabel:'Tasso reale',	name:'PercTassoReale',	style:'text-align:right', width:90}]//3rd column 1st row
						}]//end sub fieldset right column
					}]
				},{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .53, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:70, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Num rate',	name:'NumRate',	style:'text-align:right', width:90}]//3rd column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:57, columnWidth:.47,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Inter. dil.',  name:'ImpInteressiDilazione',	style:'text-align:right', width:90}]//4th column 1st row
						}]//end sub fieldset right column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .47, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:65, columnWidth: .53,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'88%'},
							items: [{fieldLabel:'Mesi dilaz.',	    name:'NumMesiDilazione',	style:'text-align:right', width:90}]//3rd column 1st row
						}]//end sub fieldset right column
					}]
				}]
			},{
				xtype:'fieldset', title:'Pratica', autoHeight:true,
				items:[
				  {
					xtype:'compositefield', fieldLabel:'Descrizione bene',
					defaults: {readOnly:true},
					items:[
						{xtype:'textfield',	name:'CodBene',	width:100},
						{xtype:'textfield', name:'DescrBene', width:450}]
				  },
				  {xtype:'textfield', fieldLabel:'Garanzie', name:'Garanzie', width:555, readOnly:true}
				]	   
			},{
				xtype:'fieldset', title:'Date', autoHeight:true,
				items:[{
					xtype:'container', layout:'column',
					items:[{
						xtype:'panel', layout:'form', labelWidth:100, columnWidth:.33, defaultType:'textfield',
						defaults: {anchor:'95%', readOnly:true},
						items: [
								{fieldLabel:'Contratto',	name:'DataContratto'},
								{fieldLabel:'Decorrenza',	name:'DataDecorrenza'}]
					},{
						xtype:'panel', layout:'form', labelWidth:100, columnWidth:.33, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true},
						items: [
								{fieldLabel:'Prima scad.',		name:'DataPrimaScadenza'},
								{fieldLabel:'Ultima scad.',	    name:'DataUltimaScadenza'}]
					},{
						xtype:'panel', layout:'form', labelWidth:100, columnWidth:.33, defaultType:'textfield',
						defaults: {anchor:'99%', readOnly:true},
						items: [
								{fieldLabel:'Chiusura',	name:'DataChiusura'}]
					}]
				}]
			},{
				xtype:'fieldset', title:'Banca', autoHeight:true,
				bodyStyle:'margin: 0; padding:0',
				items:[{
					xtype:'container', layout:'column',
					items:[{
						xtype:'panel', layout:'form', columnWidth:.60, labelWidth:75, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true},
						items: [{fieldLabel:'Intestatario',	name:'IntestatarioConto'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.40, labelWidth:35, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true},
						items: [{fieldLabel:'IBAN',	name:'IBAN'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.60, labelWidth:75, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true},
						items: [{fieldLabel:'Banca',	name:'TitoloBanca'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.20, labelWidth:35,defaultType:'textfield',
						defaults: {anchor:'90%', readOnly:true},
						items: [{fieldLabel:'ABI',id:'ABI',	name:'ABI'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.20, labelWidth:35, defaultType:'textfield',
						defaults: {anchor:'94%', readOnly:true},
						items: [{fieldLabel:'CAB', name:'CAB'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.30, labelWidth:75, defaultType:'textfield',
						defaults: {anchor:'95%', readOnly:true},
						items: [{fieldLabel:'Tel. banca',	name:'Telefono'}]
					},{
/* SCA							xtype:'panel', layout:'form', columnWidth:.35, defaultType:'textfield',
						defaults: {anchor:'95%', readOnly:true},
						items: [{fieldLabel:'Importo finanz.',	name:'ImpFinanziato', style:'text-align:right'}]
					},{	*/
						xtype:'panel', layout:'form', columnWidth:.30, labelWidth:75, defaultType:'textfield',
						defaults: {anchor:'94%', readOnly:true},
						items: [{fieldLabel:'Importo rata', name:'ImpRata', style:'text-align:right'}]
					},{
						xtype:'panel', layout:'form', columnWidth:.40, labelWidth:115, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true},
						items: [{fieldLabel:'Tipo pagamento', name:'TipoPagamento'}]
					}]
				}]
			}]
		});
		
		//---------------- pagina dei dati Experian ------------------------
		var datiExperian = new Ext.form.FormPanel({
			title:'Info Experian',	
			id: 'panelExperian',
			frame: true,
			bodyStyle:'margin: 0; padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.JsonReader({
				root: 'results',
				fields: DCS.recordPratica
			}),
			items: [{
				xtype:'fieldset', title:'Informazioni ricevute da Experian ', autoHeight:true,
				bodyStyle:'margin: 0; padding:0',
				items:[{
					xtype:'container', layout:'column',
					defaults:{labelWidth:230, style:'padding:5px 0'},
					items:[{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Score',	name:'D4CScoreIndex', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Stato Pagamenti',	name:'StatoPagamenti', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Numero protesti',	name:'NumProtesti', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Importo totale protesti',	name:'ImportoTotaleProtesti', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Num. dati pregiudizievoli',	name:'NumDatiPregiudizievoli', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Importo dati pregiudizievoli',	name:'ImportoTotaleDatiPregiudizievoli', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Num. richieste di credito ultimi 6 mesi',	name:'NumRichiesteCredito6mesi', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Imp. richieste di credito ultimi 6 mesi',	name:'ImpRichiesteCredito6mesi', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Totale scaduto non pagato',	name:'TotaleImpScadutoNonPagato', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Totale impegno mensile',	name:'TotaleImpegnoMensile', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Numero prestiti finalizzati',	name:'NumPrestitiFinalizzati', style:'text-align:right', width: 70}]
					},{
						xtype:'panel', layout:'form', columnWidth:.50, defaultType:'textfield',
						defaults: {readOnly:true},
						items: [{fieldLabel:'Numero prestiti personali',	name:'NumPrestitiPersonali', style:'text-align:right', width: 70}]
					}]
				}]
			}]
		});
        //---- fine pagina dei dati experian
		//----------------------------------------------------------------------------------------------------------------
		
		var gridAltriSoggetti = new DCS.GridRecapiti({
				key: this.idContratto,
				altriSoggetti: true,
				frmRecapito: frmRecapito,
				isStorico: this.isStorico});
				
		// Indice del record nello store della lista
		var indexStore = this.rowIndex;
		if (this.listStore!=null && (this.listStore.lastOptions.params||{}).start != undefined)
			indexStore += this.listStore.lastOptions.params.start;
		// Indice dell'ultimo record nello store della lista
		var lastRec = (this.listStore!=null?this.listStore.getTotalCount()-1:indexStore);
//		var si = this.listStore.getSortState();
		
		// Funzione che gestisace la pressione dei bottoni precedente/successivo
		var dettaglio_nextprev = function(btn) {
			var p = this.listStore.lastOptions.params || {};		// parametri di lettura dello store
			var newIndex = this.rowIndex + (btn.getItemId()=='btnPrev'?-1:+1);	// nuovo indice del record nella pagina
			var flg_reload = false;				// flag per eventuale caricamento pagina 
			if (p != undefined && p.start != undefined) {	// paginata
				if (newIndex < 0) {					// precedente da inizio pagina?
					p.start -= p.limit;
					flg_reload = true;
				} else
					if (newIndex >= p.limit) {		// successivo da fine pagina?
						p.start += p.limit;
						flg_reload = true;
					}
			}
			if (flg_reload) {					// richiesto record fuori pagina: deve caricarla 
				this.listStore.load({
					params:p, 
					callback : function(rows,options,success) {
						if (success) {			// mostra dettaglio record richiesto
							var newIndex = this.rowIndex==0?options.params.limit-1:0;
							var rec = rows[newIndex]; //this.listStore.getAt(newIndex);
							showPraticaDetail(rec.get('IdContratto'),rec.get('numPratica'),rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.listStore,newIndex);
						}
					},
					scope:this
				});
			} else {			// nella pagina: mostra dettaglio record richiesto
				var rec = this.listStore.getAt(newIndex);
				showPraticaDetail(rec.get('IdContratto'),rec.get('numPratica'),rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.listStore,newIndex);
			}
		};
		
		Ext.apply(this, {
			activeTab:0,
			items: [
  				datiGenerali.create(this.idContratto,this.cliente, frmRecapito, datiGeneraliSecondari, datiExperian,this.isStorico)
			,{
				xtype:'form', title:'Altri soggetti',	//il titolo � usato per testare il tab
				layout:'fit',
				items:[gridAltriSoggetti]
			},
				datiGeneraliSecondari
			,
    			/*Ext.apply(DCS.PraticaServizi(this.idContratto),{
				title:'Servizi'})
			,*/{
				xtype:'grid', title:'Rate e insoluti',
				id: 'gridRateInsoluti',
         		store: dsPartite,
		 		disableSelection : true,
				border: false,
				viewConfig: {
					autoFill: true,
					forceFit: false,
			        getRowClass : function(record, rowIndex, p, store){
			                if(record.get('IdInsoluto') > 0){
			                    return 'grid-row-giallochiaro';
			                }
//			                if(record.get('IdInsoluto') < 0){
//			                    return 'grid-row-verdechiaro';
//			                }
			                if(record.get('NumRata')%2)
			                {
						        return 'grid-row-azzurrochiaro';
			                }
					        return 'grid-row-azzurroscuro';
			        }
				},
				columnLines:true,
				columns: colPartite
			},
        		Ext.apply(DCS.StoriaRecupero(this.idContratto,this.numPratica,this.isStorico),{
					title:'Storico recupero'})
			,{
				xtype:'grid', title:'Altri contratti',
				store: dsAltriContratti,
				border: false,
				viewConfig: {
					autoFill: true,
					forceFit: false
				},
				disableSelection : false,
				columns: [{dataIndex:'Ruolo',width:70,header: 'Ruolo',sortable:true},
					{dataIndex:'numPratica',width:60,header: 'N.Pratica',sortable:true},
					{dataIndex:'Prodotto',width:110,header:'Prodotto',sortable:true},
					{dataIndex:'Stato',width:90,header:'Stato contratto',sortable:true},
					{dataIndex:'StatoRecupero',width:90,header:'Stato recupero',sortable:true},
					{dataIndex:'Agenzia',width:90,header:'Agenzia',sortable:true},
					{dataIndex:'ImpFinanziato',width:60,header:'Finanziato',sortable:true,align:'right',xtype:'numbercolumn',format:'0.000,00/i'},
					{dataIndex:'Importo',width:60,header:'Impagato',sortable:true,align:'right',xtype:'numbercolumn',format:'0.000,00/i'}],
				listeners: {
					rowdblclick: function(grid,rowIndex,event) {
						var rec = this.store.getAt(rowIndex);
						if (rec.get('flagAssegnato')==1)
							showPraticaDetail(rec.get('IdContratto'),rec.get('numPratica'),rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.store,rowIndex,this.isStorico);
						else
							Ext.Msg.alert("Informazione","Il contratto non � assegnato all'Agenzia");
					}
				}
			},
				//Ext.apply(DCS.FormNote.griglia(this.idContratto),{title:'Note'})
				//Ext.apply(DCS.FormVistaNote.showDetailVistaNote(this.idContratto,this.numPratica,'N',0,this.listStore),{title:'Note'})
				Ext.apply(DCS.GridRami(this.idContratto,this.numPratica,this.listStore,this.isStorico),{
					title:'Note'})
			,
				Ext.apply(DCS.Allegato(this.idContratto, this.numPratica,this.isStorico),{
					title:'Allegati'})
			,	datiPianoRientro
			,   datiExperian
			],

	        tbar: new Ext.Toolbar({
				items:[
					'->',new DCS.Azioni({hidden:!CONTEXT.AZIONI,disabled:false, idContratto: this.idContratto,
										 numPratica: this.numPratica, isStorico:this.isStorico}),
					    
						{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSepar'},
						{type:'button', text:'Stampa', iconCls:'grid-print',hidden: true, id:'btnPrintDettPraticaRate',
							handler:function() {			
							Ext.ux.Printer.print(Ext.getCmp('gridRateInsoluti'));}, 
							scope: this},
						{xtype:'tbseparator', hidden: true, id:'btnPrintDettPraticaRateSeparExp'},
						{type: 'button', hidden:!CONTEXT.EXPORT, id:'btnExpDettPraticaRate', text: 'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(Ext.getCmp('gridRateInsoluti'));}, scope:this},
					'-',{type:'button', text:'Precedente',
							itemId:'btnPrev',
							iconCls:'icon-prev',
							disabled: (indexStore<=0),
							disabledClass: 'x-item-disabled',
							handler:dettaglio_nextprev,
							scope:this},
					'-',{type:'button', text:'Seguente',
							itemId:'btnNext',
							iconCls:'icon-next',
							disabled: (indexStore >= lastRec),
							disabledClass: 'x-item-disabled',
							handler:dettaglio_nextprev,
							scope:this},
					'-', helpButton("DettaglioPratica")]
									
	        }),
	        id: 'pnlDettPratica',
	        listeners: {
				tabchange: function(panel, tab) {
					var myIdx = panel.items.indexOf(panel.getActiveTab());
					var showButtons = ((myIdx==3) && (panel.id=='pnlDettPratica'));
					
					if (((myIdx==6) && (panel.id=='pnlDettPratica'))) {
						//DCS.FormNote.caricaDati();
						//aggiorna le note ogni volta che le si guarda per vedere se qualcuno
						//ha scritto nel mentre
						var idP=this.idContratto;
						Ext.Ajax.request({
							url: 'server/edit_ramiNote.php', method:'POST',
							params :{task:'readTree',IdPratica:idP, schema: MYSQL_SCHEMA+(this.isStorico?'_storico':'')},
							callback : 	function(r,options,success) 
										{
							 				var idRamoSelezionato = 0;
											var myMask = new Ext.LoadMask(Ext.getBody(), {
												msg: "Ricerca aggiornamenti note..."
											});											
											myMask.show();
											var arrayStr = '';
										 	arrayStr =  success.responseText;
										 	var child = Ext.util.JSON.decode(arrayStr); 
										 	
										 	var nroot=new Ext.tree.AsyncTreeNode({
									            expanded: true,
									            children: child
									        });
										 	Ext.getCmp('treeNotePratica').setRootNode(nroot);
										 	myMask.hide();
										},
							failure:	function (obj)
										{
											Ext.MessageBox.alert('Errore', 'Errore durante la lettura dei rami.');
										},
							scope: this
						});
					}

					this.toolbars[0].get('btnPrintDettPraticaRate').setVisible(showButtons);
					this.toolbars[0].get('btnExpDettPraticaRate').setVisible(showButtons && CONTEXT.EXPORT);
					this.toolbars[0].get('btnPrintDettPraticaRateSepar').setVisible(showButtons);
					this.toolbars[0].get('btnPrintDettPraticaRateSeparExp').setVisible(showButtons);
	            }
	        }
        });
		
		DCS.DettaglioPratica.superclass.initComponent.call(this);
        
		this.relayEvents(datiGenerali.gridRecapiti, ['clickEditRecapito']);
		this.relayEvents(frmRecapito.getForm(), ['clickEditRecapito']);
		this.on( {
			'clickEditRecapito' : function () {
				var sql="select ifnull(Nominativo,RagioneSociale) as nominativo, telefono from cliente where idcliente="+this.cliente;
				var strCliente='';	
				Ext.Ajax.request({
					url : 'server/AjaxRequest.php' , 
					params : {task: 'read',sql: sql},
					method: 'POST',
					autoload:true,
					success: function ( result, request ) {
						  var jsonData = Ext.util.JSON.decode(result.responseText);
						  strCliente=jsonData.results[0] ['nominativo'] +' '+ jsonData.results[0] ['telefono'];
						  var titolo='Dettaglio pratica - ' + this.numPratica +
							' &nbsp;&nbsp;&nbsp;<img src="images/telefono.png" ' +
							'align="absbottom"><b>&nbsp;'+strCliente;
						  this.ownerCt.setTitle(titolo);					
						  },
					failure: function ( result, request) { 
						Ext.MessageBox.alert('Errore', result.responseText); 
					},
					scope:this
				});
			},
			scope : this
		});
	}
	
});

// register xtype
Ext.reg('DCS_dettagliopratica', DCS.DettaglioPratica);

DCS.PraticaDatiGenerali = function(){
		var dsPratica = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader({root: 'results'},DCS.recordPratica)
		});
		
	return {
		gridRecapiti: undefined,

		create: function(idContratto, idCliente, frmRec, datiSec,datiExp, isStorico) {
			var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
			this.gridRecapiti = new DCS.GridRecapiti({
						key: idCliente,
						height: 137,
						width: '100%',
						frmRecapito: frmRec,
						isStorico: isStorico
					});

			var formPratica = new Ext.form.FormPanel({
				title:'Dati generali',		//il titolo � usato per testare il tab
//				autoHeight: true,
				frame: true,
				bodyStyle: 'padding:5px 5px 0',
				border: false,
				trackResetOnLoad: true,
				reader: new Ext.data.JsonReader({
					root: 'results',
					fields: DCS.recordPratica
				}),
				items: [{
					xtype:'fieldset', title:'Pratica', autoHeight:true, layout:'column',
					items:[{
						xtype:'container', layout:'form', columnWidth:.55, 
						items: [{
							xtype:'panel', layout:'form', labelWidth:45,
							items: [{
								xtype:'compositefield', fieldLabel:'Cliente', hideLabel: false, anchor:'97%',
								defaults: {readOnly:true},
								items:[
								   //{xtype:'label', 	text:'Cliente:',	width:50},
								   {xtype:'textfield',	name:'CodCliente',	width:50},
								   {xtype:'textfield', name:'NomeCliente',  width:338}]
							}]//chiuso pannello 1 superiore del container principale colonna sinistra
						},{//fine primo elemento container principale colonna sinistra del fieldset pratica->inizio secondo elemento inferiore
							xtype:'panel', layout:'form', labelWidth:100, //columnWidth:.55,
							items: [{
								xtype:'container', layout:'column',
								items:[{
									xtype:'panel', layout:'form', columnWidth:.75, defaultType:'textfield',
									defaults: {anchor:'92%', readOnly:true},
									items: [{fieldLabel:'Data di nascita',	name:'DataNCli', style:'text-align:left'}]
								},{
									xtype:'panel', layout:'form', labelWidth:40, columnWidth:.25, defaultType:'textfield',
									defaults: {anchor:'87%', readOnly:true},
									items: [{fieldLabel:'Sesso', name:'sesso', style:'text-align:center'}]
								}]
							},{
								xtype:'panel', layout:'form', labelWidth:100, defaultType:'textfield',
								defaults: {anchor:'97%', readOnly:true, width:80},
								items: [{fieldLabel:'Codice Fiscale',   name:'CodiceFiscale'},
								        {fieldLabel:'Num.pratica',		name:'CodContratto',	id:'Npratica',	hidden:true,	style:'text-align:right'},
								        {fieldLabel:'Attributo',		name:'Attributo',	id:'Attrib',	hidden:true,	style:'text-align:right'},
								        {fieldLabel:'Punto vendita',	name:'NomePuntoVendita'},
								        {fieldLabel:'Autore override',		name:'AutoreOverride'},
								        {fieldLabel:'Area',				name:'area'}
								]
							}]//chiuso pannello 2 inferiore del container principale colonna sinistra
						}]//chiuso container principale colonna sinistra
					},{//fine primo elemento fieldset pratica (prima colonna)-> inizio seconda colonna
						xtype:'panel', layout:'form', labelWidth:90, columnWidth:.45, defaultType:'textfield',
						defaults: {anchor:'97%', readOnly:true, width:90},
						items: [{fieldLabel:'Prodotto',	name:'Prodotto'},
						        {fieldLabel:'Luogo nascita',	name:'LuogoNCli'},
						        {fieldLabel:'Partita IVA',	name:'PartitaIVA'},
						        {fieldLabel:'Rivenditore',	name:'NomeVenditore'},
						        {fieldLabel:'Motivo override',	name:'TitTipoSpec'},
						        {fieldLabel:'Dealer',	name:'Venditore'}
						        ] //end panel 1
					}]//end field 1
				},{// end item 1 - start item 2
				xtype: 'panel',
				//title: 'Recapiti',
				autoHeight: true,
				items: [this.gridRecapiti],
				buttons: [{
					text: 'Nuovo recapito',
					iconCls:'grid-add',
					hidden: !CONTEXT.BOTT_NEW_REC || isStorico,
					handler: function() {
						var griglia;
						var frmRecapito = new DCS.FormRecapito();
						griglia = this.gridRecapiti;
						
						var rec = new DCS.recordRecapito({
							IdRecapito: 0,
							IdCliente:keyIdCliente,
							IdContratto:keyIdContratto,
							modificabile:'S',
							IdTipoRecapito:'',
							TitoloTipoRecapito:'',
							ProgrRecapito:0,
							Nome:'',
							Indirizzo:'',
							Localita:'',
							CAP:'',
							SiglaProvincia:'',
							SiglaNazione:'IT',
							Telefono:'',
							Cellulare:'',
							Fax:'',
							Email:'',
							Controparte:''});
							
						frmRecapito.show(rec, griglia);
		        	},
					scope: this
				}]
			},{ //end item 2 - start item 3			
				xtype: 'fieldset',
				title: 'Stato recupero',
				id: 'fsStato',
				autoHeight: true,
				layout: 'column',
				items: [
				  {
					xtype: 'fieldset', autoHeight: true, layout: 'column', columnWidth: .99, border: false,
					items: [
					  {
						xtype: 'panel', layout: 'form', labelWidth: 60, columnWidth: .29,
						items: [{
							xtype: 'compositefield', fieldLabel: 'Insoluto',
							defaults: {readOnly: true},
							items: [
							  {
							  	xtype: 'textfield', name: 'Importo', cls: 'txt_evid', style: 'text-align:right; font-weight:bold; background:#ffff60', width: 83
							  },{
							  	xtype: 'button', name: 'espandi', iconCls: 'in_dettaglio', tooltip: 'Informazioni aggiuntive insoluto',
								handler: function(){
									showInsolutoDetail(idContratto);
								},
								scope: this
							}]
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 57, columnWidth: .23, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							fieldLabel: 'Scadenza', name: 'DataScadenza', width: 74
						}]//3nd column
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 95, columnWidth: .28, defaultType: 'textfield',
						items: [{
							xtype: 'textfield', fieldLabel: 'Rata impagata', name: 'Rata', style: 'text-align:right', width: 68
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 73, columnWidth: .20, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							fieldLabel: 'Giorni ritardo', name: 'Giorni', style: 'text-align:right', width: 51
						}]//4th column
					}]//end top sub-fieldset
				  },{
					xtype: 'fieldset', autoHeight: true, layout: 'column', columnWidth: .99, border: false,
					items: [
					  {
						xtype: 'panel', layout: 'form', labelWidth: 60, columnWidth: .52, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							xtype: 'textfield', fieldLabel:'Stato', name: 'StatoRecupero', anchor: '95%'
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 95, columnWidth: .47, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							xtype: 'textfield', fieldLabel:'Classificazione', name: 'Classificazione', anchor: '99%'
						}]
					}]//end center sub-fieldset
				  },{
					xtype: 'fieldset', autoHeight: true, layout: 'column', columnWidth: .99, border: false,
					items: [
					  {
						xtype: 'panel', layout: 'form', labelWidth: 60, columnWidth: .27, defaultType: 'textfield',
						defaults: {readOnly: true},
						items:[{
							xtype: 'textfield', fieldLabel: 'Operatore', name: 'NomeUtente', anchor: '95%'
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 45, columnWidth: .30, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							xtype: 'textfield', fieldLabel: 'Agenzia', name: 'NomeAgenzia', anchor: '97%'
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 55, columnWidth: .21, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							xtype: 'textfield', fieldLabel: 'In. Affido', name: 'DataInizioAffido', style: 'text-align:left', anchor: '98%'
						}]
					  },{
						xtype: 'panel', layout: 'form', labelWidth: 59, columnWidth: .21, defaultType: 'textfield',
						defaults: {readOnly: true},
						items: [{
							xtype: 'textfield', fieldLabel: 'Fin. Affido', name: 'DataFineAffido', style: 'text-align:left', anchor: '100%'
						}]
					}]//end bottom sub-fieldset
				}]//end field
			  }]//end
			});

			dsPratica.load({
				params:{
					sql: "SELECT v.*,a.TitoloAzione AS TitoloAzione,b.TitoloBanca,b.Telefono,pr.IdPianoRientro,ex.IdExperian, "
						 +" D4CScoreIndex,StatoPagamenti,NumProtesti,ImportoTotaleProtesti,NumDatiPregiudizievoli,ImportoTotaleDatiPregiudizievoli,"
						 +"NumRichiesteCredito6mesi,ImpRichiesteCredito6mesi,TotaleImpScadutoNonPagato,TotaleImpegnoMensile,NumPrestitiFinalizzati"
						 +",NumPrestitiPersonali,"
					     +" if(v.IBAN is null AND v.CAB is null AND v.ABI is null,\'\',ifnull(os.Soggetto,v.NomeCliente)) AS IntestatarioConto "
                         +" FROM "+schema+".v_pratiche as v left join banca b on v.abi=b.abi and v.cab=b.cab "
                         +" left join "+schema+".v_altri_soggetti os on v.IdContratto=os.IdContratto AND os.CodTipoControparte=\'ICC\' "
                         +" left join "+schema+".pianorientro pr on pr.IdContratto=v.IdContratto "
                         +" left join "+schema+".v_experian_client ex on ex.IdCliente=v.IdCliente "
                         +" left join azione a on a.TitoloAzione = (select a.TitoloAzione "
                         +"                                         from "+schema+".storiarecupero sr " 
                         +"                                         left join azionetipoazione ata on ata.IdAzione = sr.IdAzione and ata.IdTipoAzione in (9,14,15) "
                         +"                                         left join azioneprocedura ap on sr.IdAzione = ap.IdAzione "
                         +"                                         left join azione a on a.IdAzione=ata.IdAzione or a.IdAzione = ap.IdAzione "
                         +"                                         where sr.IdContratto="+idContratto+" having a.TitoloAzione IS NOT NULL order by sr.IdStoriaRecupero desc limit 1) "
                         +" WHERE v.IdContratto="+idContratto+" limit 1"
				},
				callback : function(r,options,success) {
					if (success && r.length>0) {
						formPratica.getForm().loadRecord(r[0]);
						datiSec.getForm().loadRecord(r[0]);
						datiExp.getForm().loadRecord(r[0]);
						
						var app = r[0].get('TitoloCategoria');
						var pianoR = r[0].get('IdPianoRientro');
						var appAzione = r[0].get('TitoloAzione');
						var IdExperian = r[0].get('IdExperian');
						
						// Se si chiude molto rapidamente la finestra, può capitare che la callback entri con la finestra ormai chiusa: controlla questo caso
						if (!Ext.getCmp('fsStato')) return;
						
						if(!(app==null) && !(appAzione==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Categoria: ' +  app +') (Ultima azione: '+ appAzione +')');
					    else {
							if (!(app==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Categoria: ' +  app +')');
							else {
							  if(!(appAzione==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Ultima azione: '+ appAzione +')');
						    }
						} 
											
						if (!pianoR) { // stranamente, per far scomparire il tab prefabbricato ci mette hide e lo aggiunge al form (Boh)
							Ext.getCmp('panelPianoRientro').add({hidden:true});
							Ext.getCmp('panelPianoRientro').doLayout();
							formPratica.add(Ext.getCmp('panelPianoRientro'));
						}

						if (!IdExperian) {
							Ext.getCmp('panelExperian').add({hidden:true});
							Ext.getCmp('panelExperian').doLayout();
							formPratica.add(Ext.getCmp('panelExperian'));
						}

					}
				},
				scope: this
			});
			
			return formPratica;
		}
	};
};
