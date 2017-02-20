/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.ns('DCS');

DCS.pnlFuncList = Ext.extend(Ext.grid.GridPanel, {
	lista: '',
	nome_gruppo:'',
	profilo:'',
	innerColumns: null,
	pagesize: 0,
	winList:'',
	winFather:'',

	initComponent : function() {
		var selM = new Ext.grid.CheckboxSelectionModel();
		var arrayGroup=[];
		var firstSelFlag = false;
		
		var locFields = [{name: 'IdFunzione', type: 'int'},
						{name: 'IdGruppo', type: 'int'},
						{name: 'CodFunzione'},
						{name: 'TitoloFunzione'}];
		
		var columns = new Ext.grid.ColumnModel({
				columns: [selM,
			{dataIndex:'IdFunzione',width:45, hidden:true,header:'Id',align:'left', filterable: false},
			{dataIndex:'IdGruppo',	width:45, hidden:false,header:'Gruppo',filterable:false,sortable:false},
			{dataIndex:'CodFunzione',	width:70, hidden:true,header:'Codice',filterable:false,sortable:false,groupable:false},
			{dataIndex:'TitoloFunzione',	width:280,	header:'Funzione',align:'right',filterable:false,sortable:false}
			]});
		
		var gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/funzioniGruppi.php',
				method: 'POST'
			}),   
			baseParams:{attiva:'N', task: 'read', lista: this.lista},
			remoteSort: true,
			groupField: 'IdGruppo',
			groupOnSort: false,
			remoteGroup: true,
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				idProperty: 'IdFunzione', //the property within each row object that provides an ID for the record (optional)
				fields: locFields
			}),
			listeners:{
				load:function(s,r,o){
					console.log("n "+s.getCount());
					var h=0;
					for(var k=0;k<s.getCount();k++){
						if(s.getAt(k).get('IdFunzione')==s.getAt(k).get('IdGruppo')){
							arrayGroup[h] = s.getAt(k).get('IdFunzione');
							h++;
							//console.log("array kepsimo "+arrayGroup[k]);
						}
					}
					var arrSel = new Array();
					for (var i=0; i<Ext.getCmp(fath).listFunc.length; i++) {
						//console.log("i "+i);
						//console.log("idsC "+gstore.getCount());
						//console.log("ids "+gstore.getAt(i).get('IdFunzione'));
						//console.log("idret "+Ext.getCmp(fath).listFunc[i]);
						//j = this.store.findExact('IdFunzione',retfun[i]);
						j = s.find('IdFunzione',Ext.getCmp(fath).listFunc[i]);
						console.log("j "+j);
						if (j != -1) {
							arrSel.push(j);
						}
					}
					firstSelFlag=true;
		        	selM.selectRows(arrSel);
		        	firstSelFlag=false;
					/*for(i=0; i<s.getCount();i++){
						if(r[i].get('IdFunzione')==r[i].get('IdGruppo'))
						{
							console.log("in ");
							console.log("in "+r[i].get('IdGruppo'));
							arr['gruppo'].push(r[i].get('IdGruppo'));
							arr['titolo'].push(r[i].get('TitoloFunzione'));
							arr['indice'].push(s.find('TitoloFunzione',r[i].get('TitoloFunzione')));
						}
						console.log("ELE "+r[i].get('TitoloFunzione'));
					}*/
					/*console.log("array "+arr['gruppo'].length);
					for (j=0;j<arr['gruppo'].length;j++){
						console.log("ELE "+arr['gruppo'][j]);
						console.log(arr['titolo'][j]);
						console.log(arr['indice'][j]);
					}*/
				}
			}
  		});

		//var titolo = 'Funzioni da assegnare';
		var fath=this.winFather;
		var Pro=this.profilo;
		var List=this.lista;
		var save = new Ext.Button({
			sm:selM,
			store:this.store,
			text: 'Salva',
			handler: function(grid,rowIndex,colIndex) {
				var array = selM.getSelections();
				var i = selM.getCount();
				var vect = [];
				if (i>0){
					for (j=0;j<i;j++)
					{
						//vect = vect + '|' + array[j].get('IdFunzione');
						vect.push(array[j].get('IdFunzione'));
					}
				}else{
					console.log("no record");
				}
				console.log("fat "+fath);
				Ext.getCmp(fath).listFunc=vect;
				this.winList.close();
				/*Ext.Ajax.request({
			        url: 'server/funzioniGruppi.php',
			        method: 'POST',
			        params: {task: 'save',vect: vect, profilo: Pro},
			        success: function(obj) {
			            var resp = obj.responseText;
			            if (resp != 0) {
			                Ext.MessageBox.alert('Esito', resp + ' funzione/i assegnata/e');
			            } else {
			            	Ext.MessageBox.alert('Esito', 'Gruppo deselezionato');	
			            }
			            this.winList.close();
					},
					scope: this,
					waitMsg: 'Salvataggio in corso...'
			    });*/
			},
			scope: this
		});
		
		Ext.apply(this,{
			height: 600,
			store: gstore,
			//titlePanel: titolo,
			fields: locFields,
			colModel: columns,
			sm: selM,
			buttons: [save],
			view: new Ext.grid.GroupingView({
				//startCollapsed : true,
				//autoFill: (Ext.state.Manager.get(this.stateId,'')==''),
				forceFit: false,
				groupTextTpl: '{[values.rs[0].data["TitoloFunzione"]]} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
			}),
			listeners: {
				viewready: function() {
					/*Ext.Ajax.request({
				        url: 'server/funzioniGruppi.php',
				        method: 'POST',
				        params: {task: 'checkLoad', lista:List,profilo:Pro},
				        success: function(obj) {
				        	console.log("loadcheck");
				            if (obj.responseText != '') {
								eval("var retfun = "+obj.responseText);
								//console.log("res "+obj.responseText);
				                //check del selM
				            	if(selM.isLocked)
				            	{
				            		selM.unlock();
				            	}
				            	//console.log("dim St "+this.store.getCount());
				            	var arrSel = new Array();
								for (var i=0; i<retfun.length; i++) {
									//console.log("i "+i);
									//console.log("ids "+this.store.getAt(i).get('IdFunzione'))
									//console.log("idret "+retfun[i]);
									//j = this.store.findExact('IdFunzione',retfun[i]);
									j = this.store.find('IdFunzione',retfun[i],0,true);
									//console.log("j "+j);
									if (j != -1) {
										arrSel.push(j);
									}
								}
								firstSelFlag=true;
				            	selM.selectRows(arrSel);
				            	firstSelFlag=false;
				            } else {
				                Ext.MessageBox.alert('Fallito', 'Nessuna voce processata');
				            }
						},
						scope: this
				    });*/
				},
				scope: this
			}
		});
		
		if (this.pagesize > 0) {
			Ext.apply(this, {
				// paging bar on the bottom
				bbar: new Ext.PagingToolbar({
					pageSize: this.pagesize,
					store: this.store,
					displayInfo: true,
					//displayMsg: 'Righe {0} - {1} di {2}',
					//emptyMsg: "Nessun elemento da mostrare",
					items: []
				})
			});
		}
		
		DCS.pnlFuncList.superclass.initComponent.call(this, arguments);
		
		selM.on('selectionchange', function(selm) {
			//console.log("nChange "+arrayGroup.length);
			//console.log("flag "+firstSelFlag);
			if(!firstSelFlag)
			{
				if(selM.isLocked)
	        	{
	        		selM.unlock();
	        	}
				for(var j=0;j<arrayGroup.length;j++){
					//console.log("selezionato: "+selM.isIdSelected(arrayGroup[j])+" | "+arrayGroup[j]);
					if (!selM.isIdSelected(arrayGroup[j])){
						//console.log("in");
						selM.each(function(r){
							//console.log("r.id "+r.get('IdFunzione')+" | r.idG "+r.get('IdGruppo'));
							if(r.get('IdGruppo')==arrayGroup[j])
							{
								//rimuovi selezione dai record figli se il gruppo è selezionato
								var st = r.store;
								var index = st.find('IdFunzione',r.get('IdFunzione'));
								selm.deselectRow(index);
							}
						});
					}
				}
			}
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
