// Crea namespace DCS
Ext.namespace('DCS');

DCS.DettaglioPraticaTax = Ext.extend(Ext.TabPanel, {
	idContratto: 0,
	numPratica: '',
	cliente: 0,
	listStore: null,
	rowIndex: -1,
	
	initComponent: function() {

		var frmRecapito = new DCS.FormRecapito();

		var recordPartite;
		var sqlPartite;
		var colPartite;
		
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
					
			vistaPartite = "v_partite";
						 
			colPartite = [{dataIndex:'NumRata',width:40,header:'Rata',sortable:true},
						{dataIndex:'DataScadenza',width:100,header:'Data scadenza',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataRegistrazione',width:100,header: 'Data reg.',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataCompetenza',width:100,header:'Data comp.',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataValuta',width:100,header:'Data valuta',sortable:true,renderer:DCS.render.date},
					{dataIndex:'TitoloTipoMovimento',width:180,header:'Tipo movimento',sortable:false},
					{dataIndex:'TitoloTipoInsoluto',width:100,header:'Causale insol.',sortable:false},
					{dataIndex:'Debito',width:70,header:'Debito',align:'right',sortable:false,xtype:'numbercolumn',format:'0.000,00/i'},
					{dataIndex:'Credito',width:70,header:'Credito',align:'right',sortable:false,xtype:'numbercolumn',format:'0.000,00/i'}];
/*			
		} else {
			recordPartite = [
				    {name: 'IdInsoluto', type: 'int',id:'Ins'},
					{name: 'idContratto', type: 'int'},
					{name: 'NumRata', type: 'int'},
					{name: 'DataScadenza', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'DataPagamento', type: 'date', dateFormat: 'Y-m-d'},
					{name: 'CausalePagamento'},
					{name: 'Rata', type: 'float', useNull: true},
					{name: 'TitoloTipoInsoluto'},
					{name: 'Debito', type: 'float', useNull: true}];
					
			vistaPartite = "v_partite_semplici";
						 
			colPartite = [{dataIndex:'NumRata',width:40,header:'Rata',sortable:true},
					{dataIndex:'DataScadenza',width:80,header: 'Data scadenza',sortable:true,renderer:DCS.render.date},
					{dataIndex:'DataPagamento',width:80,header:'Data pagamento',sortable:true,renderer:DCS.render.date},
					{dataIndex:'CausalePagamento',width:100,header:'Tipo pagamento'},
					{dataIndex:'Rata',width:80,header:'Importo rata',align:'right',sortable:true,xtype:'numbercolumn',format:'0.000,00/i'},
					{dataIndex:'TitoloTipoInsoluto',width:130,header:'Causale insoluto',sortable:false},
					{dataIndex:'Debito',width:80,header:'Debito residuo',align:'right',sortable:true,xtype:'numbercolumn',format:'0.000,00/i'}];
		}
*/		 
		var dsPartite = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/partiteDettaglioPratica.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read',idc:this.idContratto,version:'new'},
			reader:  new Ext.data.JsonReader(
				{root: 'results'}, recordPartite
	        ),
	        autoLoad:true,
			sortInfo:{field: 'NumRata', direction: "ASC"}
		});
		        
		/*dsPartite.load({
			params:{
				sql: "SELECT v.*, i.IdInsoluto FROM "+vistaPartite+" v left join insoluto i on v.IdContratto=i.IdContratto AND " +
					 "v.NumRata=i.NumRata where v.idcontratto=" + this.idContratto
			}
		});*/
		        
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
		        
		// True se l'altro contratto può essere aperto perché affidato alla stessa agenzia o perche l'operatore è interno
		var flgAssegnato = '1';
		if (CONTEXT.InternoEsterno == 'E') {
			flgAssegnato = "ifnull((pc.IdAgenzia="+CONTEXT.IdReparto+" OR " + CONTEXT.IdReparto + 
					" IN (SELECT IdAgenzia FROM assegnazione ass WHERE ass.IdContratto=pc.IdContratto AND ass.DataFin>=CURDATE())),0)";
		}

		dsAltriContratti.load({
			params:{
				sql: "SELECT pc.*, " + flgAssegnato + " as flagAssegnato FROM v_pratiche_collegate pc WHERE IdCliente=" + this.cliente + 
					 " AND IdContratto!=" + this.idContratto + " ORDER BY numPratica"
			}
		});

		keyIdCliente=this.cliente;
		keyIdContratto=this.idContratto;
		
		var datiGenerali = new DCS.PraticaDatiGeneraliTax();

		//----------------------------------------------------------------------------------------------------------------
				
		var datiGeneraliSecondari = new Ext.form.FormPanel({
			title:'Dettagli pratica',		//il titolo è usato per testare il tab
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
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Imp. annuale',	name:'ImpValoreBene',	style:'text-align:right', width:90}]//1st column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:80, columnWidth:.50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Imp. Dilaz.',	name:'ImpFinanziato',	style:'text-align:right', width:90}]//2nd column 1st row
						}]//end sub fieldset left column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Anticipo',	name:'ImpAnticipo',	style:'text-align:right', width:90}]//3rd column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:80, columnWidth:.50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Rimanente',	name:'ImpErogato',	style:'text-align:right', width:90}]//4th column 1st row
						}]//end sub fieldset right column
					}]
				},{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Rata finale',	name:'ImpRataFinale',	style:'text-align:right', width:90}]//1st column 1st row
						},{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Interessi',	name:'ImpInteressi',	style:'text-align:right', width:90}]//3rd column 1st row
						}]//end sub fieldset left column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{        
							xtype:'panel', layout:'form', labelWidth:80, columnWidth:.50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Spese inc. ',	name:'ImpSpeseIncasso',	style:'text-align:right', width:90}]//4th column 1st row
						}]//end sub fieldset right column
					}]
				},{
					xtype:'container', layout:'column',
					items:[{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Num rate',	name:'NumRate',	style:'text-align:right', width:90}]//3rd column 1st row
						},{        
							xtype:'panel', layout:'form', labelWidth:80, columnWidth:.50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Inter. Dilaz.',  name:'ImpInteressiDilazione',	style:'text-align:right', width:90}]//4th column 1st row
						}]//end sub fieldset right column
					},{
						xtype:'fieldset', autoHeight:true, layout:'column', columnWidth: .50, border: false,
						items:[{
							xtype:'panel', layout:'form', labelWidth:80, columnWidth: .50,defaultType:'textfield',
							defaults: {readOnly:true, anchor:'90%'},
							items: [{fieldLabel:'Mesi dilaz.',	    name:'NumMesiDilazione',	style:'text-align:right', width:90}]//3rd column 1st row
						}]//end sub fieldset right column
					}]
				},{        
					xtype:'panel', layout:'form', labelWidth:80,defaultType:'textfield',
					defaults: {readOnly:true, anchor:'100%'},
					items: [{fieldLabel:'Motivo dilaz.',  name:'MotivoDilazione',	style:'text-align:left', width:450}]
				}]
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
		
		//----------------------------------------------------------------------------------------------------------------
		
		var gridAltriSoggetti = new DCS.GridRecapiti({
				key: this.idContratto,
				altriSoggetti: true,
				frmRecapito: frmRecapito});
				
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
  				datiGenerali.create(this.idContratto,this.cliente, frmRecapito, datiGeneraliSecondari)
			,
				datiGeneraliSecondari
			,{
				xtype:'form', title:'Altri soggetti',	//il titolo è usato per testare il tab
				layout:'fit',
				items:[gridAltriSoggetti]
			}
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
        		Ext.apply(DCS.StoriaRecupero(this.idContratto,this.numPratica),{
					title:'Storico recupero'})
			,{
				xtype:'grid', title:'Altre pratiche',
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
							showPraticaDetail(rec.get('IdContratto'),rec.get('numPratica'),rec.get('IdCliente'),rec.get('cliente'),rec.get('Telefono'),this.store,rowIndex);
						else
							Ext.Msg.alert("Informazione","Il contratto non è assegnato all'Agenzia");
					}
				}
			},
				//Ext.apply(DCS.FormNote.griglia(this.idContratto),{title:'Note'})
				//Ext.apply(DCS.FormVistaNote.showDetailVistaNote(this.idContratto,this.numPratica,'N',0,this.listStore),{title:'Note'})
				Ext.apply(DCS.GridRami(this.idContratto,this.numPratica,this.listStore),{
					title:'Note'})
			,
				Ext.apply(DCS.Allegato(this.idContratto, this.numPratica),{
					title:'Allegati'}),
					Ext.apply(DCS.PianoRateazione(this.idContratto, this.numPratica),{
						title:'Rateazione'})
			],

	        tbar: new Ext.Toolbar({
				items:[
					'->',new DCS.Azioni({hidden:!CONTEXT.AZIONI,disabled:false, idContratto: this.idContratto, numPratica: this.numPratica}),
					    
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
							params :{task:'readTree',IdPratica:idP},
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
		
		DCS.DettaglioPraticaTax.superclass.initComponent.call(this);
        
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
Ext.reg('DCS_dettagliopraticaTax', DCS.DettaglioPraticaTax);

DCS.PraticaDatiGeneraliTax = function(){
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

		create: function(idContratto, idCliente, frmRec, datiSec) {
			this.gridRecapiti = new DCS.GridRecapiti({
						key: idCliente,
						height: 137,
						frmRecapito: frmRec
					});

			var formPratica = new Ext.form.FormPanel({
				title:'Dati generali',		//il titolo è usato per testare il tab
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
					xtype:'fieldset', title:'Pratica', autoHeight:true, layout:'form',
					items:[
							{
							   	xtype:'container', layout:'form', 
							   	items: [{
											xtype:'compositefield', fieldLabel:'Debitore', hideLabel: false, anchor:'100%',
											defaults: {readOnly:true},
											items:[
											   {xtype:'textfield',	name:'CodCliente',	width:50},
											   {xtype:'textfield', name:'NomeCliente',	width:566}]
										}
							   		  ]
							}
							,
					        {
					    	   	xtype:'container', layout:'column', 
					    	   	items: [
					    	 	       {
											xtype:'container', layout:'form', columnWidth:.55, 
											items: [{//fine primo elemento container principale colonna sinistra del fieldset pratica->inizio secondo elemento inferiore
												xtype:'panel', layout:'form', labelWidth:100, //columnWidth:.55,
												items: [{
													xtype:'container', layout:'column',
													items:[{
														xtype:'panel', layout:'form', columnWidth:.75, defaultType:'textfield',
														defaults: {anchor:'92%', readOnly:true},
														items: [{fieldLabel:'Data di nascita',	name:'DataNCli', style:'text-align:left'}]
													},{
														xtype:'panel', layout:'form', labelWidth:40, columnWidth:.25, defaultType:'textfield',
														defaults: {anchor:'90%', readOnly:true},
														items: [{fieldLabel:'Sesso', name:'sesso', style:'text-align:center'}]
													}]
												},{
													xtype:'panel', layout:'form', labelWidth:100, defaultType:'textfield',
													defaults: {anchor:'97%', readOnly:true, width:80},
													items: [{fieldLabel:'Num.pratica',		name:'CodContratto',	id:'Npratica',	hidden:true,	style:'text-align:right'},
													        {fieldLabel:'Attributo',		name:'Attributo',	id:'Attrib',	hidden:true,	style:'text-align:right'}
													]
												}]//chiuso pannello 2 inferiore del container principale colonna sinistra
											}]//chiuso container principale colonna sinistra
										},{//fine primo elemento fieldset pratica (prima colonna)-> inizio seconda colonna
											xtype:'panel', layout:'form', labelWidth:100, columnWidth:.45, defaultType:'textfield',
											defaults: {anchor:'100%', readOnly:true},
											items:[
											        {fieldLabel:'Luogo nascita',	name:'LuogoNCli'}
											        ] //end panel 1
										}
					    	   	       ]
					       },{
					    	   	xtype:'container', layout:'form', 
					    	   	items: [{//fine primo elemento fieldset pratica (prima colonna)-> inizio seconda colonna
												xtype:'panel', layout:'form', labelWidth:100, defaultType:'textfield',
												defaults: {anchor:'100%', readOnly:true},
												items: [{fieldLabel:'Prodotto',	name:'Prodotto'}
												        ] //end panel 1
					    	   			}
					    	   			,
										{
											xtype:'compositefield', fieldLabel:'Descrizione bene',
											defaults: {readOnly:true},
											items:[
												{xtype:'textfield',	name:'CodBene',	width:100},
												{xtype:'textfield', name:'DescrBene', width:517}]
										}
					    	   	       ]
					       }
					]//end field 1
				},{// end item 1 - start item 2
				xtype: 'panel',
				//title: 'Recapiti',
				autoHeight: true,
				items: [this.gridRecapiti],
				buttons: [{
					text: 'Nuovo recapito',
					iconCls:'grid-add',
					hidden: !CONTEXT.BOTT_NEW_REC,
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
					sql: "SELECT v.*,a.TitoloAzione AS TitoloAzione,b.TitoloBanca,b.Telefono,pr.IdPianoRientro, "
					     +" if(v.IBAN is null AND v.CAB is null AND v.ABI is null,\'\',ifnull(os.Soggetto,v.NomeCliente)) AS IntestatarioConto "
                         +" FROM v_pratiche as v left join banca b on v.abi=b.abi and v.cab=b.cab "
                         +" left join v_altri_soggetti os on v.IdContratto=os.IdContratto AND os.CodTipoControparte=\'ICC\' "
                         +" left join pianorientro pr on pr.IdContratto=v.IdContratto "
                         +" left join azione a on a.TitoloAzione = (select a.TitoloAzione "
                         +"                                         from storiarecupero sr " 
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
						
						var app = r[0].get('TitoloCategoria');
						var pianoR = r[0].get('IdPianoRientro');
						var appAzione = r[0].get('TitoloAzione');
						
						if(!(app==null) && !(appAzione==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Categoria: ' +  app +') (Ultima azione: '+ appAzione +')');
					    else {
							if (!(app==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Categoria: ' +  app +')');
							else {
							  if(!(appAzione==null)) Ext.getCmp('fsStato').setTitle('Stato recupero (Ultima azione: '+ appAzione +')');
						    }
						} 
												
/*						if(pianoR==null){
							Ext.getCmp('panelPianoRateazione').add({hidden:true});
							Ext.getCmp('panelPianoRateazione').doLayout();
							formPratica.add(Ext.getCmp('panelPianoRateazione'));
						}*/
					}
				},
				scope: this
			});
			
			return formPratica;
		}
	};
};
