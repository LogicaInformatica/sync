// Crea namespace DCS
Ext.namespace('DCS');
//AFFIDAMENTIGridRegGenTab
var winS;

DCS.GridRegGenTab = Ext.extend(Ext.grid.GridPanel, {
	pagesize: PAGESIZE,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	hideStato: false,
	groupOn: undefined,
	idRep:'',
	campo:'',
	titoloR:'',
	
	initComponent : function() { 
		
		var NotVisibleOpe=true;
		var buttAddName='';
		var buttDelName='';
		var repAss=this.idRep||'';
		var campo=this.campo||'';
		var titoloRep=this.titoloR||'';
		var IdMain = this.getId();
		var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});

		this.btnMenuAzioni = new DCS.Azioni({
			gstore: this.store,
			sm: selM
		});
		
		var newRecord = function(btn, pressed)
		{
			var myMask = new Ext.LoadMask(Ext.getBody(), {msg: "Caricamento in corso ..."});	
			myMask.show();
			showRegAssOpDetail(repAss,'',null,'','',titoloRep,campo);
			myMask.hide();
	    };
	    
	    var delRecord = function(btn, pressed)
	    {
	    	var Arr = selM.getSelections();
	    	var confString='';
	    	var vectString='';
	    	var field='';
	    	var indexf='';
	    	var word='';
	    	var arrContr=[];
	    	switch(campo){
				case 'NumTipAff':
					word='i tipi di regole:';
					field='TitoloRegolaProvvigione';
			    	indexf='IdRegolaProvvigione';
					break;
				case 'NumRegAff':
					word='le regole di affidamento selezionate di classe:';
					field='TitoloClasse';
			    	indexf='IdRegolaAssegnazione';
					break;
				case 'NumRegAffOpe':
					word='le regole di assegnazione selezionate per gli utenti:';
					field='nomeutente';
			    	indexf='IdRegolaAssegnazione';
					break;
			}
	    	
	    	if(Arr.length>0)
	    	{
	    		var flagExist=false;
		    	for(var k=0;k<Arr.length;k++)
		    	{
		    		flagExist=false;
		    		for(var h=0;h<arrContr.length;h++)
			    	{
		    			if(arrContr[h]==Arr[k].get(field))
		    			{
		    				//esiste già
		    				flagExist=true;
		    			}
			    	}
		    		if(!flagExist)
		    		{
		    			arrContr.push(Arr[k].get(field));
		    			confString += '<br />	-'+Arr[k].get(field);
		    		}
		    		vectString = vectString + '|' + Arr[k].get(indexf);
		    		
		    	}
		    	Ext.MessageBox.alert('Conferma', "Si desidera eliminare "+word+" "+confString+" ?",function(btn, text){
		    	    if (btn == 'ok'){
		    	    	Ext.Ajax.request({
					        url: 'server/gestioneAssegnazioni.php',
					        method: 'POST',
					        params: {task: 'deleteRules',scelta:campo,vect: vectString},
					        success: function(obj) {
					            var resp = obj.responseText;
					            //console.log("res "+resp);
					            if (resp == '' && vectString!='') {
					                Ext.MessageBox.alert('Esito', 'Le azioni selezionate sono state eliminate.');
					                gstore.reload();
					            } else {
					            	if(resp!=''){
						                Ext.MessageBox.alert('Esito', resp);
						                gstore.reload();			            		
					            	}
					            }
							},
							scope: this,
							waitMsg: 'Salvataggio in corso...'
					    });
		    	    }
		    	});
	    	}else{
	    		Ext.MessageBox.alert('Conferma', "Non si è selezionata alcuna voce.");
	    	}
	    };
	    
		var actionColumn = {
				xtype: 'actioncolumn',
				id: 'actionColGen',
	            width: 50,
	            header:'Azioni',
	            printable:false, hideable: false, sortable:false,  filterable:false, resizable:false, fixed:true, groupable:false,
	            items: []
			};
		buttAddName='Nuova regola';
		buttDelName='Cancella regola';
		switch(campo)
		{
			case 'NumTipAff':
					var fields = [	{name: 'IdRegolaProvvigione', type: 'int'},
					              	{name: 'IdReparto', type: 'int'},
									{name: 'CodRegolaProvvigione'},
									{name: 'TitoloRegolaProvvigione'},
									{name: 'DataIni', type:'date'},
									{name: 'DataFin', type:'date'},
									{name: 'LastUser'},
									{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];
					
					var columns = [selM,
									{dataIndex:'IdRegolaProvvigione',width:10, header:'IdRP',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
									{dataIndex:'IdReparto',width:10, header:'IdR',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
									{dataIndex:'TitoloRegolaProvvigione',	width:130,	header:'Nome regola', hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'CodRegolaProvvigione',	width:70,	header:'Codice', hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'DataIni',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Inizio affidamento',align:'left', filterable: true, groupable:true, sortable:true},
									{dataIndex:'DataFin',width:40,xtype:'datecolumn', format:'d/m/y',	header:'Fine affidamento',align:'left', filterable: true, groupable:true, sortable:true},
									{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
									{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}];
				break;
			default:
					if(campo=='NumRegAffOpe')
					{
						NotVisibleOpe=false;
					}
					var fields = [	{name: 'IdRegolaAssegnazione', type: 'int'},
									{name: 'DurataAssegnazione', type: 'int'},
									{name: 'IdReparto',type: 'int'},
									{name: 'TipoDistribuzione'},
									{name: 'tipodistribuzioneConv'},
									{name: 'nomeutente'},
									{name: 'TitoloFamiglia'},
									{name: 'TitoloClasse'},
									{name: 'TitoloArea'},
									{name: 'GiorniFissiInizio'},
									{name: 'GiorniFissiFine'},
									{name: 'Condizione'},
									{name: 'LastUser'},
									{name: 'LastUpd', type:'date', dateFormat: 'Y-m-d H:i:s'}];
					
					var columns = [selM,
									{dataIndex:'IdRegolaAssegnazione',width:10, header:'Idra',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
									{dataIndex:'IdReparto',width:10, header:'IdR',hidden: true, hideable: false,filterable:true,groupable:false,sortable:false},
									{dataIndex:'nomeutente',	width:130,	header:'Nome operatore', hidden:NotVisibleOpe, hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'TitoloFamiglia',	width:130,	header:'Famiglia', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'TitoloClasse',	width:130,	header:'Classe di affidamento', hidden:false, hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'TitoloArea',	width:130,	header:'Area', hidden:!NotVisibleOpe, hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'DurataAssegnazione',width:60, header:'Durata', align:'center',hidden:!NotVisibleOpe, hideable: false,filterable:true,groupable:false,sortable:false},
									{dataIndex:'TipoDistribuzione',	width:80,	header:'Tipo distrib.', hidden:true,hideable: false,align:'right',filterable:true,groupable:false,sortable:true},
									{dataIndex:'tipodistribuzioneConv',	width:80,	header:'Distribuzione', hideable: false,align:'right',filterable:true,groupable:false,sortable:true},
									{dataIndex:'GiorniFissiInizio',	width:110,	header:'Giorni inizio fissato', hidden:!NotVisibleOpe, align:'right',hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'GiorniFissiFine',	width:110,	header:'Giorni fine fissata', hidden:!NotVisibleOpe, align:'right',hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'Condizione',	width:110,	header:'Condizione', hideable: false,filterable:true,groupable:false,sortable:true},
									{dataIndex:'LastUpd',	width:70,xtype:'datecolumn',header:'Last update',hidden:true, filterable:true,sortable:true,groupable:false},
									{dataIndex:'LastUser',	width:70,header:'Last user',hidden:true, filterable:true,sortable:true,groupable:false}];
				break;
		}
		
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneAssegnazioni.php',
				method: 'POST'
			}),   
			baseParams:{task: this.task, group: this.groupOn, idRep:this.idRep, scelta:this.campo},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});
		
		Ext.apply(this,{
			store: gstore,
			autoHeight: false,
			border: false,
			layout: 'fit',
			loadMask: true,
			view: new Ext.grid.GroupingView({
				autoFill: true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
				//enableNoGroups: false,
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
			sm: selM,
			listeners: {
				celldblclick : function(grid,rowIndex,columnIndex,event){
					var rec = this.store.getAt(rowIndex);
					var titoloRegola='';
					switch(campo){
						case 'NumTipAff':
							if(rec.get('IdRegolaProvvigione')!='' || rec.get('IdRegolaProvvigione')!=null)
							{
								titoloRegola = "provvigionale \'"+rec.get('TitoloRegolaProvvigione')+'\'';
							}else{
								titoloRegola = "";
							}
							showRegAssOpDetail(repAss,rec.get('IdRegolaProvvigione'),gstore,rowIndex,titoloRegola,titoloRep,campo);
							break;
						case 'NumRegAff':
							titoloRegola = "per l\'agenzia "+titoloRep+" <br />per la classe di affidamento \'"+rec.get('TitoloClasse')+"\'";
							showRegAssOpDetail(repAss,rec.get('IdRegolaAssegnazione'),gstore,rowIndex,titoloRegola,titoloRep,campo);
							break;
						case 'NumRegAffOpe':
							titoloRegola = "per l\'operatore "+rec.get('nomeutente')+" <br />per la classe di affidamento \'"+rec.get('TitoloClasse')+"\'";
							showRegAssOpDetail(repAss,rec.get('IdRegolaAssegnazione'),gstore,rowIndex,titoloRegola,titoloRep,campo);
							break;
						default:titleWin='';
							break;
					}
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
					'->',{xtype:'button',
						icon:'ext/examples/shared/icons/fam/add.png',
						hidden:false, 
						id: 'bNRA',
						pressed: false,
						enableToggle:false,
						text: buttAddName,
						handler: newRecord
						},
					'-', {xtype:'button',
						icon:'ext/examples/shared/icons/fam/delete.gif',
						hidden:false, 
						id: 'bDRA',
						pressed: false,
						enableToggle:false,
						text: buttDelName,
						handler: delRecord
						},
					'-', {type: 'button', text: 'Stampa elenco', icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}},
	                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png', handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
	                '-', helpButton(campo=='NumTipAff'?"RegoleProvvigionali":(campo=='NumRegAff'?'RegoleAffidamento':'RegoleAssegnazione')),' '
				];
		
		var bbarItems = [
					'->', {type:'button', tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', handler: function(){
								this.store.load();
							}, scope: this}
				];
				
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		
		Ext.apply(this, {
	        bbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:bbarItems
	        })		
		});

		DCS.GridRegGenTab.superclass.initComponent.call(this, arguments);
		this.activation();
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

DCS.AssGenerica = function(){

	return {
		create: function(idR,campo,titoloR){
			var subtit;
			switch(campo){
			case 'NumTipAff':
				subtitle = 'Le regole provvigionali definiscono sia le formule di calcolo delle commissioni, sia le eventuali condizioni'
					  +'<br>di applicabilit&agrave;. Sono collegate alle regole di affidamento, che stabiliscono quali pratiche devono essere'
					  +'<br>assegnate a ciascuna agenzia.';
				break;
			case 'NumRegAff':
				subtitle = 'Le regole di affidamento stabiliscono quali pratiche devono essere assegnate a ciascuna agenzia.'
					+'<br>Sono collegate alle regole provvigionali, che differenziano le diverse modalit&agrave; di calcolo delle provvigioni.';
				break;
			case 'NumRegAffOpe':
				subtitle = 'Le regole di assegnazione agli operatori stabiliscono quali pratiche devono essere assegnate'
					+'<br>a ciascun operatore di un\' agenzia. Sono opzionali: le pratiche non assegnate rimangono'
					+'<br>comunque visibili e gestibili da parte di chi ha il potere di supervisione nell\' agenzia';
				break;
			}
			var gridGestAss = new DCS.GridRegGenTab({
				titlePanel: '<span class="subtit">'+subtitle+'</span>',
				flex: 1,
				task: "readGenAssGrid",
				idRep:idR,
				campo:campo,
				titoloR:titoloR
			});

			return gridGestAss;
		}
	};
	
}();