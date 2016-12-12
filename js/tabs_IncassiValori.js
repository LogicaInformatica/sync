// Grid incassi
Ext.namespace('DCS');

DCS.GridListaIncassiValori = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	id:'',
	pagesize: 100,
	titlePanel: '',
	btnMenuAzioni: null,
	task: '',
	filters: null,
	GroupFlag: '',
	GroupFlagLot: '',
	hdn:'',
	groupOn:'',
	groupDir:'',
	selmVis:'',
	repId:'',
	
	initComponent : function() {
		//var summary = new Ext.ux.grid.GroupSummary();
		var fields = [
						{name: 'CodContratto',type: 'string'},
						{name: 'NomeCliente', type: 'string'},
						{name: 'IdIncasso',   type: 'int'},
						{name: 'IncCapitale',   type: 'int'},
						{name: 'IncInteressi',   type: 'int'},
						{name: 'IncSpese',   type: 'int'},
						{name: 'IncAltriAddebiti',   type: 'int'},
						{name: 'NumDocumento'},
						{name: 'TitoloTipoIncasso',        type: 'string'},
						{name: 'Data'},
						{name: 'ImpPagato',   type: 'float'},
						{name: 'UtenteInc',   type: 'string'},
						{name: 'RepartoInc',  type: 'string'},
						{name: 'UrlAllegato',  type: 'string'},
						{name: 'IdDistinta'},
						{name: 'IdRepartoInc'},
						{name: 'Lotto'},{name: 'DataFineAffido'}];
			
		if(this.selmVis == true){
			var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
			var columns = [selM,
			               	{dataIndex:'UrlAllegato',width:80,   header:'UrlAllegato',   filterable:false, align:'left',   groupable:false,          sortable:false , hidden:true},
			               	{dataIndex:'CodContratto',width:80,  header:'Contratto',     filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
					        {dataIndex:'NomeCliente', width:100, header:'Cliente',       filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
					        {dataIndex:'IdIncasso',   width:80,  header:'Incasso n.',     filterable:false, align:'left',   groupable:false,          sortable:false, hidden:true },
				        	{dataIndex:'TitoloTipoIncasso',	      width:60,  header:'Tipo inc.',     filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
				        	{dataIndex:'Data',	      width:80,  header:'Data',          filterable:true , align:'center', groupable:false,          sortable:true , hidden:false , xtype:'datecolumn', format:'d/m/y' },
				        	{dataIndex:'ImpPagato',	  width:80,  header:'Imp. Pagato',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncCapitale',	  width:80,  header:'Capitale',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncAltriAddebiti',	  width:80,  header:'Altri Addebiti',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncInteressi',	  width:80,  header:'Interessi',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncSpese',	  width:80,  header:'Spese',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'UtenteInc',   width:80,  header:'Utente',        filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
				        	{dataIndex:'RepartoInc',  width:80,  header:'Reparto',       filterable:true , align:'left',   groupable:false, sortable:true , hidden:this.GroupFlag},
				        	{dataIndex:'Lotto', 	  width:80,  header:'Lotto',      filterable:true , align:'center', groupable:this.GroupFlagLot,   sortable:true , hidden:false},
				        	{dataIndex:'IdDistinta',   width:50,  header:'Distinta n.',     filterable:false, align:'left',   groupable:false,          sortable:false, hidden:false },
				        	{dataIndex:'NumDocumento',	  width:80,  header:'Num. Documento',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false },
				        	{
	    		                xtype: 'actioncolumn',
	    		                printable: false,
	    		                header:'Azioni',
	    		                sortable:false, 
	    		                align:'center',
	    		                resizable: false,
	    		                filterable:false,
	    		                width: 42,
	    		                menuDisabled: false,
	    		                items: [
		    		                      {
											   icon   : 'images/delete.gif', 
											   tooltip: 'Cancella',
											   handler : function(grid, rowIndex, colIndex) {
											       var rec = grid.gstore.getAt(rowIndex);
											       grid.deleteIncasso(rec);
											   }
		    		                      }
		    		                   ]
	    		            }
				        ];
		}else{
			var columns = [
			               	{dataIndex:'UrlAllegato',width:80,   header:'UrlAllegato',   filterable:false, align:'left',   groupable:false,          sortable:false , hidden:true},
					        {dataIndex:'CodContratto',width:80,  header:'Contratto',     filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
					        {dataIndex:'NomeCliente', width:100, header:'Cliente',       filterable:true , align:'left',   groupable:false,          sortable:true , hidden:false},
					        {dataIndex:'IdIncasso',   width:80,  header:'IdIncasso',     filterable:false, align:'left',   groupable:false,          sortable:false, hidden:true },
				        	{dataIndex:'TitoloTipoIncasso',	      width:60,  header:'Tipo inc.',     filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
				        	{dataIndex:'Data',	      width:80,  header:'Data',          filterable:true , align:'center', groupable:false,          sortable:true , hidden:false , xtype:'datecolumn', format:'d/m/y' },
				        	{dataIndex:'ImpPagato',	  width:80,  header:'Imp. Pagato',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , /*summaryType:'sum',*/ xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncCapitale',	  width:80,  header:'Capitale',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncAltriAddebiti',	  width:80,  header:'Altri Addebiti',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncInteressi',	  width:80,  header:'Interessi',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'IncSpese',	  width:80,  header:'Spese',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false , xtype:'numbercolumn',format:'0.000,00/i'},
				        	{dataIndex:'UtenteInc',   width:80,  header:'Utente',        filterable:true , align:'center', groupable:false,          sortable:true , hidden:false},
				        	{dataIndex:'RepartoInc',  width:80,  header:'Reparto',       filterable:true , align:'left',   groupable:this.GroupFlag, sortable:true , hidden:true},
				        	{dataIndex:'Lotto', 	  width:80,  header:'Lotto',      filterable:true , align:'center', groupable:this.GroupFlagLot,   sortable:true , hidden:false},
				        	{dataIndex:'IdDistinta',   width:50,  header:'Distinta n.',     filterable:false, align:'left',   groupable:false,          sortable:false, hidden:false },
				        	{dataIndex:'NumDocumento',	  width:80,  header:'Num. Documento',   filterable:true , align:'right',  groupable:false,          sortable:true , hidden:false},
				        	{
	    		                xtype: 'actioncolumn',
	    		                printable: false,
	    		                header:'Azioni',
	    		                sortable:false, 
	    		                align:'center',
	    		                resizable: false,
	    		                filterable:false,
	    		                width: 42,
	    		                menuDisabled: false,
	    		                items: [
		    		                      {
											   icon   : 'images/delete.gif', 
											   tooltip: 'Cancella',
											   handler : function(grid, rowIndex, colIndex) {
											       var rec = grid.gstore.getAt(rowIndex);
											       grid.deleteIncasso(rec,this.id);
											   }
		    		                      }
		    		                   ]
	    		            }
				        ];
		}
		
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/gestioneIncassi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: this.task, repId: this.repId},
			remoteSort: true,
			groupField: this.groupOn,
			groupOnSort: false,
			id: 'tabStore',
			groupDir: this.groupDir,
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
			//plugins: [summary],
			view: new Ext.grid.GroupingView({
				//startCollapsed : true,
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			columns: columns,
			listeners: {
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
				
				rowdblclick: function(grid,rowIndex,event) {
					var rec = this.gstore.getAt(rowIndex);
					this.showDettaglioIncasso(rec,this.id); 
				},
				scope: this
			}
	    });
		
		/*var sGrid = this;
		var sStore = this.gstore;
		var sVis= this.selmVis;*/
		var tbarItems = [
					
		                {xtype:'tbtext', text:this.titlePanel, style:"color:#15428B;font:bold 11px tahoma,arial,verdana,sans-serif"},
		                '->',//chkElem,
		                {xtype:'button',
						icon:'images/stampa.gif',
						hidden:false, 
						id: 'bSd',
						pressed: false,
						enableToggle:false,
						text: 'Stampa distinta',
						/*listeners:{
		                	click : function( Button, e ){
			                	if(sVis === true){
			                		var array = selM.getSelections();
			                		//var j = this.gstore.getCount();//elementi nello store della griglia
			                		var j = sStore.getCount();//elementi nello store della griglia
			                		//console.log("elementi iniziali "+j);
			                		var deleteArr=new Array();
			                		var ind=0;
			                		//scarnifica lo store della griglia di ogni elemento non selezionato
			                		for(var h=0;h<j;h++)//per ogni elemento nello store
			                		{
			                			//console.log("elemento h-esimo "+this.gstore.getAt(h).get('CodContratto'));
			                			//if(!selM.isSelected(this.gstore.getAt(h)))
			                			if(!selM.isSelected(sStore.getAt(h)))
			                			{
			                				//tiene conto degli indici da eliminare
			                				deleteArr[ind]=h;
			                				ind++;
			                			}
			                		}
			                		for(var h=0;h<ind;h++)
			                		{
			                			//rimozione
			                			//console.log("Rimuove indice: "+deleteArr[h]);
		                				//this.gstore.remove(this.gstore.getAt(deleteArr[h]-h));
			                			sStore.remove(sStore.getAt(deleteArr[h]-h));
		                				//se nn è nella selezione viene rimosso con 
		                				//riallineamento dell'indice interno a deleteArr
			                		}
			                		//ora lo store della griglia corrisponde a quello da stampare
			                	}
			                	//var l = this.gstore.getCount();
			                	var l = sStore.getCount();
			                	//console.log("elementi finali "+l);
			                	if(l!=0){
			                		var totalImp=0;
			                		for(var h=0;h<l;h++)
			                		{
			                			//totalImp=totalImp+this.gstore.getAt(h).get('ImpPagato');
			                			totalImp=totalImp+sStore.getAt(h).get('ImpPagato');
			                		}
			                		//inserisci la riga della somma
			                		var defaultData = {
			                				CodContratto:'',
			        						NomeCliente:'Tot. Vaglia',
			        						IdIncasso:null,
			        						TitoloTipoIncasso:'',
			        						Data:'',
			        						ImpPagato:totalImp,
			        						UtenteInc:'',
			        						RepartoInc:'',
			        						UrlAllegato:'',
			        						IdDistinta:'',
			        						Lotto:'',
			        						IdRepartoInc:null,
			        						DataFineAffido:''
			                            };
		                            var recId = (l); // provide unique id
		                            //var p = new this.gstore.recordType(defaultData, recId); // create new record
		                            var p = new sStore.recordType(defaultData, recId); // create new record
		                            //this.gstore.insert(l, p); // insert a new record into the store (also see add)
		                            sStore.insert(l, p); // insert a new record into the store (also see add)
		                            
		                            //creazione della distinta nella tabella apposita
		                            Ext.Ajax.request({
								        url: 'server/gestioneIncassi.php', method:'POST',
								        params :{task:"addDistinta",ImpPagato:sStore.getAt(l).get('ImpPagato'),idCompagnia:sStore.getAt(0).get('IdRepartoInc')},
								        success: function (obj) { 
								        	//stampa
						                	Ext.ux.Printer.print(sGrid,true);},
								        failure: function (obj) {
											Ext.MessageBox.alert("Registrazione distinta fallito");
				                    		},
										scope: this
								     }); // fine request
		                            
		                            console.log("da sgrid totale "+sGrid.gstore.getAt(l).get('ImpPagato'));
		                            console.log("da sgrid n elem store "+sGrid.gstore.getCount());
		                            		                            
		                            //stampa
				                	//Ext.ux.Printer.print(sGrid);
		                           
			                	}
			                	//console.log("elementi dopo aggiunta "+this.gstore.getCount());
			                	//console.log("elemento ultimo "+sStore.getAt(l).get('ImpPagato'));
			                	if(sVis === true){
			                		//this.gstore.reload();
			                		sGrid.getStore().reload();
			                		//ripristina lo store della griglia
			                	}
		                	}
		                },*/
						handler: function(){
		                	if(this.selmVis === true){
		                		var array = selM.getSelections();
		                		var j = this.gstore.getCount();//elementi nello store della griglia
		                		//console.log("elementi iniziali "+j);
		                		var deleteArr=new Array();
		                		var ind=0;
		                		//scarnifica lo store della griglia di ogni elemento non selezionato
		                		for(var h=0;h<j;h++)//per ogni elemento nello store
		                		{
		                			//console.log("elemento h-esimo "+this.gstore.getAt(h).get('CodContratto'));
		                			if(!selM.isSelected(this.gstore.getAt(h)))
		                			{
		                				//tiene conto degli indici da eliminare
		                				deleteArr[ind]=h;
		                				ind++;
		                			}
		                		}
		                		for(var h=0;h<ind;h++)
		                		{
		                			//rimozione
		                			//console.log("Rimuove indice: "+deleteArr[h]);
	                				this.gstore.remove(this.gstore.getAt(deleteArr[h]-h));
	                				//se nn è nella selezione viene rimosso con 
	                				//riallineamento dell'indice interno a deleteArr
		                		}
		                		//ora lo store della griglia corrisponde a quello da stampare
		                	}
		                	var l = this.gstore.getCount();
		                	//console.log("elementi finali "+l);
		                	if(l!=0){
		                		var totalImp=0;
		                		var totalCap=0;
		                		var totalAA=0;
		                		var totalInt=0;
		                		var totalSpese=0;
		                		for(var h=0;h<l;h++)
		                		{
		                			totalImp=totalImp+this.gstore.getAt(h).get('ImpPagato');
		                			totalCap=totalCap+this.gstore.getAt(h).get('IncCapitale');
		                			totalAA=totalAA+this.gstore.getAt(h).get('IncAltriAddebiti');
		                			totalInt=totalInt+this.gstore.getAt(h).get('IncInteressi');
		                			totalSpese=totalSpese+this.gstore.getAt(h).get('IncSpese');
		                		}
		                		//inserisci la riga della somma
		                		var defaultData = {
		                				CodContratto:'',
		        						NomeCliente:'TOTALI:',
		        						IdIncasso:null,
		        						TitoloTipoIncasso:'',
		        						Data:'',
		        						ImpPagato:totalImp,
		        						IncCapitale:totalCap,
		        						IncInteressi:totalInt,
		        						IncSpese:totalSpese,
		        						IncAltriAddebiti:totalAA,
		        						UtenteInc:'',
		        						RepartoInc:'',
		        						UrlAllegato:'',
		        						IdDistinta:'',
		        						Lotto:'',
		        						IdRepartoInc:null,
		        						DataFineAffido:''
		                            };
	                            var recId = (l); // provide unique id
	                            var p = new this.gstore.recordType(defaultData, recId); // create new record
	                            this.gstore.insert(l, p); // insert a new record into the store (also see add)
		                		
	                            var ArrInc = '';
		                		for(var j=0; j<l; j++){
		                			ArrInc = ArrInc + '|' + this.gstore.getAt(j).get('IdIncasso');
		                		}
	                            //creazione della distinta nella tabella apposita
	                            Ext.Ajax.request({
							        url: 'server/gestioneIncassi.php', method:'POST',
							        params :{task:"addDistinta",ArrI:ArrInc,ImpPagato:this.gstore.getAt(l).get('ImpPagato'),idCompagnia:this.gstore.getAt(0).get('IdRepartoInc')},
							        success: function (obj) {
							        		//stampa
						                	Ext.ux.Printer.print(this,true);
						               	},
							        failure: function (obj) {
										Ext.MessageBox.alert("Registrazione distinta fallito");
			                    		},
									scope: this
							     }); // fine request
	                            
	                            //stampa
			                	//Ext.ux.Printer.print(this);
		                	}
		                	//console.log("elementi dopo aggiunta "+this.gstore.getCount());
		                	
		                	if(this.selmVis === true){
		                		this.gstore.reload();
		                		//ripristina lo store della griglia
		                	}
		                }, 
						scope: this
						},
						//'-', {type:'button', text:'Stampa elenco', icon:'images/stampa.gif', handler:function(){Ext.ux.Printer.print(this);}, scope: this},
		                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text:'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
		                '-', helpButton("StatiContratto"),' '
				];

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
					chkElem.reset();
					this.gstore.load();
				}, scope: this},'-');
		}
		
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});
		
		if(this.selmVis===true){
			Ext.apply(this, {
				sm: selM
			});
		}

		DCS.GridListaIncassiValori.superclass.initComponent.call(this, arguments);
	},
	
	showDettaglioIncasso: function(rec,idcaller)
	{
		var risp = DCS.showIncassoDetail.create(idcaller,rec.get('IdIncasso'),rec.get('UrlAllegato'),rec.get('CodContratto'),rec.get('NomeCliente'));
	},
	
	deleteIncasso: function(rec,ID)
	{
		Ext.Msg.confirm("Cancella incasso", "Si  vuole procedere con l'operazione?", 
				function(btn, text) {
										if (btn == 'yes')
										{	
											Ext.Ajax.request({
										        url: 'server/gestioneIncassi.php', method:'POST',
										        params :{task:"deleteIncasso",idIncasso:rec.get('IdIncasso')},
										        success: function (obj) {
													var grid = Ext.getCmp("'"+ID+"'").getStore().reload(); 
													eval('var resp = '+obj.responseText);
										        	Ext.MessageBox.alert("Cancellazione incasso", resp.msg);
										        	},
										        failure: function (obj) {
													eval('var resp = '+obj.responseText);
													Ext.MessageBox.alert("Cancellazione incasso", resp.msg);
						                    		},
												scope: this
										     }); // fine request
											//this.store.load();
											this.store.load({
												params: { 
													start: 0, 
													limit: this.pagesize
												}
											});
										}	
		                    		 }, this); 
	}
});

DCS.IncassiValori = function(){

	return {
		create: function(){
			DCS.showMask();
			var user = CONTEXT.InternoEsterno;
			var TabIncassi = new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				id: 'tabIncVal',
				items: []
			});
			
			if (user=='I')
			{
				Ext.Ajax.request({
					url : 'server/gestioneIncassi.php' , 
					params : {task: 'AgenzieIncassiTabs'},
					method: 'POST',
					autoload:true,
					success: function ( result, request ) {
						eval('var resp = '+result.responseText);
						var arr = resp.results;
						var grid = new Array();
						var nomeG='';
						var listG = new Array();
						for(i=0;i<resp.total;i++){
							nomeG = "gridN"+i; 
							grid[nomeG] = new DCS.GridListaIncassiValori({
								id:'ListaIncassiValori'+arr[i]['IdRepartoInc'],
								titlePanel: 'Incassi dell\'agenzia '+arr[i]['RepartoInc'],
								title: arr[i]['RepartoInc'],
								task: "readALotMain",
								GroupFlag:false,
								GroupFlagLot:true,
								selmVis: false,
								repId: arr[i]['IdRepartoInc'],
								stateId: 'ListaIncassiValori',
								stateful: true,
								groupOn : 'Lotto',
								groupDir: 'DESC'
							});
							listG.push(grid[nomeG]);
						}
						Ext.getCmp('tabIncVal').add(listG);
						Ext.getCmp('tabIncVal').setActiveTab(0);
					},
					failure: function ( result, request) { 
						DCS.hideMask();
						//eval('var resp = '+result.responseText);
						Ext.MessageBox.alert('Errore', result.statusText);  
					},
					scope:this
				});
			}
			
			if (user=='E')
			{
				var ListaIncassi = new DCS.GridListaIncassiValori({
					id:'ListaIncassiValori',
					titlePanel: 'Incassi agenzia',
					title: 'Recenti',
					task: "readALot",
					stateId: 'ListaIncassiValori',
					stateful: true,
					GroupFlag:true,
					GroupFlagLot:true,
					selmVis: true,
					groupOn : 'Lotto',
					groupDir: 'DESC'
				});
				
				Ext.getCmp('tabIncVal').add(ListaIncassi);
			}
			
			Ext.getCmp('tabIncVal').setActiveTab(0);
			DCS.hideMask();
				
			return TabIncassi;
		}
	};
	
}();
