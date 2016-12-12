// Crea namespace DCS
Ext.namespace('DCS');

DCS.GridUtenti = Ext.extend(Ext.grid.GridPanel, {
	//pagesize: PAGESIZE,
	pagesize: 0,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	id: 'gridUtenti',

	initComponent : function() { 
		
		/**---------------------------	
		Gestione tasto Nuovo Utente
		----------------------------*/
		var newRecord = function(btn, pressed)
		{
	   		
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showUserDetail(0,'',Ext.getCmp('gridUtenti').getStore(),'');
			myMask.hide();
	    };      
		//Fine Gestione Tasto Nuovo Utente
		
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:true});
		
		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColUt',
	            width: 84,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:true, fixed:true, groupable:false,
	            items: [{
	            	icon   : 'images/delete.gif',               
                    tooltip: 'Cancella',
	                handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var IdUtente = rec.get('IdUtente');
							Ext.Msg.show({
								title:'Conferma cancellazione',
								msg: 'Vuoi cancellare completamente l\' utente '+ rec.get('NomeUtente')+' o solo disattivarlo ?',
								buttons: {
									yes: 'Cancella',
									no: 'Disattiva',
									cancel : 'Esci'
								},
								fn: function(btn, text){
									if(btn == 'yes'){
										var type = 'cancel';
									}else if(btn == 'no' ){
										var type = 'disable';
									}else{
										return;
									}
									//si sta cancellando la selezione: ok
									Ext.Ajax.request({
								        url: 'server/utentiProfili.php',
								        method: 'POST',
										        params: {task: 'deleteU',id: IdUtente, type: type},
								        success: function(obj) {
								        	eval('var resp = '+obj.responseText);
								        	Ext.MessageBox.alert('Esito', resp.error);
								        	grid.getStore().reload();
										},
										failure: function (obj) {
											eval('var resp = '+obj.responseText);
			                    			Ext.MessageBox.alert('Errore', resp.error); 
			                    		},
										scope: this,
										waitMsg: 'Cancellazione in corso...'
								    });
								},
							})
					},
					scope: this
	            },'-',{
					icon:"images/space.png"
				},{
					iconCls: 'invioMail',               
                    tooltip: 'Invia Email',
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var utente = rec.get('NomeUtente');
						var mail = rec.get('Email');
						if (mail == ''){mail='Mail assente'}
						showMailForm(mail, utente);
					},
					scope: this
	            },'-',
	            {
					iconCls: 'invioSms',               
                    tooltip: 'Invia Sms',
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var numero = rec.get('Cellulare');
						if (numero == ''){numero='Numero di cellulare assente'}
						showSmsForm(rec.get('NomeUtente'), numero);
					},
					scope: this
	            },'-',
	            {
	            	//iconCls: 'impersonaUser',               
                    tooltip: 'Impersona',
                    getClass: function(v,meta,rec) {
					 	if (CONTEXT.IMPERSONA) {
					 		return 'impersonaUser';
                        } else {
                        	return '';
                        }
					},
					handler: function(grid, rowIndex, colIndex) {
	                    var rec = this.store.getAt(rowIndex);
						var utente = rec.get('NomeUtente');
						var idU = rec.get('IdUtente');
						var userid = rec.get('Userid');
						Ext.Ajax.request({
							url : 'server/AjaxRequest.php' , 
							params : {task: 'read',sql: "SELECT DISTINCT count(*)as presente FROM profiloutente pu, profilo p, profilofunzione pf, funzione f WHERE pu.idUtente="+idU+" and f.codfunzione='IMPERSONA' AND pu.idProfilo=p.idProfilo AND CURDATE() BETWEEN p.DataIni AND p.DataFin AND CURDATE() BETWEEN pf.DataIni AND pf.DataFin AND CURDATE() BETWEEN pu.DataIni AND pu.DataFin AND p.idProfilo=pf.idProfilo AND pf.idFunzione=f.idFunzione"},
							method: 'POST',
							autoload:true,
							success: function ( result, request ) {
								var jsonData = Ext.util.JSON.decode(result.responseText);
								var slave=jsonData.results[0] ['presente'];
								if(slave==0){
									showAnswForm(userid, utente);
								}else{
									Ext.MessageBox.alert('Non consentito', "Questo utente puo\' impersonare a sua volta.");
								}								
							},
							failure: function ( result, request) { 
								Ext.MessageBox.alert('Errore', result.responseText); 
							},
							scope:this
						});
					},
					scope: this
	            }]
			};

		var fields = [{name: 'IdUtente',type: 'int'},
    	              {name: 'NomeUtente',type: 'string'},
    	              {name: 'Userid',type: 'string'},
    	              {name: 'TitoloUfficio',type: 'string'},
    	              {name: 'Cellulare',type: 'string'},
    	              {name: 'Telefono',type: 'string'},
    	              {name: 'Email',type: 'string'},
    	              {name: 'TitoloStatoUtente',type: 'string'},
	    	      	  {name: 'DataIni', type: 'date', dateFormat: 'Y-m-d'},
	    	    	  {name: 'DataFin', type: 'date', dateFormat: 'Y-m-d'},
	    	    	  {name: 'profiliUt',type: 'string'}
	    	      	  ];

    	var columns = [selM,
    	               	{dataIndex:'IdUtente',width:80,hidden: true, hideable: false, header:'Id',filterable:true,groupable:false,sortable:false},
    	               	{dataIndex:'NomeUtente',width:105, header:'Nome', filterable:true,groupable:false,sortable:true},
    	               	{dataIndex:'profiliUt',width:105, header:'Profili', filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'Userid',	width:45,	header:'Alias', hideable: false,filterable:true,groupable:false,sortable:true},
    		        	{dataIndex:'TitoloStatoUtente',	width:45,	header:'Stato', hideable: true,filterable:true,groupable:false,sortable:false},
    		        	{dataIndex:'TitoloUfficio',	width:50,	header:'Reparto', hideable: false,filterable:true,sortable:false,groupable:true},
    		        	{dataIndex:'Cellulare',	width:45,	header:'Cellulare',filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'Telefono',	width:45,	header:'Telefono',filterable:true,sortable:false,groupable:false},
    		        	{dataIndex:'Email',	width:105,	header:'Email',sortable:false,groupable:false},
    		        	actionColumn,
    		        	{dataIndex:'DataIni',	width:40,xtype:'datecolumn', format:'d/m/y',header:'Valido dal',filterable:true,sortable:true,groupable:false},
    		        	{dataIndex:'DataFin',	width:40,xtype:'datecolumn', format:'d/m/y',header:'al',filterable:true,sortable:true,groupable:false}
    		          ];
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/utentiProfili.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, group: this.groupOn},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			}),
			listeners: {load: DCS.hideMask}
  		});
		
		Ext.apply(this,{
			store: gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
				//enableNoGroups: false,
				hideGroupedColumn: false,
				getRowClass : function(record, rowIndex, p, store){
					if(rowIndex%2)
					{
						return 'grid-row-azzurrochiaro';
					}
					return 'grid-row-azzurroscuro';
				}
			}),
			columns: columns,
			sm: selM,
			listeners: {
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.store.getAt(rowIndex);
					showUserDetail(rec.get('IdUtente'),rec.get('NomeUtente'),this.store,rowIndex);
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->', {xtype:'button',
							icon:'ext/examples/shared/icons/fam/add.png',
							hidden:false, 
							id: 'bNu',
							pressed: false,
							enableToggle:false,
							text: 'Nuova utenza',
							handler: newRecord
							},
	                '-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton("Utenti"),' '
				];

		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					displayMsg: 'Righe {0} - {1} di {2}',
					emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
			
		} else {
			tbarItems.splice(2,0,
				{type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
					this.store.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.GridPratiche.superclass.initComponent.call(this, arguments);
		this.activation();
		//this.store.load();
		selM.on('selectionchange', function(selm) {
			this.btnMenuAzioni.setDisabled(selm.getCount() < 1);
		}, this);

	},

	activation: function() {
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
	}
});

DCS.GridGestUtenti = function(){

	return {
		create: function(){
			var gridGestUtenti = new DCS.GridUtenti({
				titlePanel: 'Gestione degli utenti'
					+'<span class="subtit">'
					+'<br>Nota 1: per definire un nuovo utente deve prima essere stato definito il reparto (o agenzia) a cui appartiene.'
					+'<br>Nota 2: gli utenti indicati come non operativi non hanno accesso al sistema, ma possono essere comunque impersonati dall\' amministratore.'
					+'<br>Nota 3: se un utente viene reso non operativo mentre &egrave; online, NON viene disabilitato fino al prossimo tentativo di login.</span>',
				groupOn: "TitoloUfficio",
				flex: 1,
				task: "readU"
			});

			return gridGestUtenti;
		}
	};
	
}();

