// Sintesi delle pratiche viste da operatore interno
Ext.namespace('DCS.Charts');

//var tipi = ['Column2D', 'Column3D', 'Bar2D', 'Line', 'Area2D', 'Pie2D', 'Pie3D', 'Doughnut2D', 'Doughnut3D', 'Pareto2D', 'Pareto3D', 'SSGrid'];
DCS.Charts.tipi = ['MSColumn2D', 'MSColumn3D', 'MSLine', 'MSBar2D', 'MSBar3D', 'MSArea', 'Marimekko', 'ZoomLine',
'StackedColumn3D', 'StackedColumn2D', 'StackedBar2D', 'StackedBar3D', 'StackedArea2D'];

DCS.Charts.Sintesi = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	itipo: 1,
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	comboData: null,
		
	initComponent : function() {
	/* da usare con FusionChart vers. 3, omesso per la vers. 2 
		var myChart = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
		myChart.configure("XMLLoadingText","Caricamento in corso...");
		myChart.configureLink({
			overlayButton: { message : 'Indietro' }
			},0
		);
		var myChart2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
		myChart2.configure("XMLLoadingText","Caricamento in corso...");
		myChart2.configureLink({
			overlayButton: { message : 'Indietro' }
			},0
		);
		
		var myChart3 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId3", "100%", "90%", "0", "1" );
		myChart3.configure("XMLLoadingText","Caricamento in corso...");
		*/
		this.dsMese = DCS.Store.ChartMesi.getInstance();

		this.dsAnni = DCS.Store.ChartAnni;//.getInstance();

		this.comboTipo = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['tipo'],
	        	data: [['Mensile'],['Storico']]
	    	}),
		    valueField: 'tipo',
		    displayField: 'tipo',
			width: 100,
			listeners: {
				select: function(combo, record, index) {
					this.comboMese.setVisible(index==0);
					Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
					Ext.getCmp(this.task+'_story').setVisible(index==1);
					if (!this.comboAnni.hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}

					this.comboData.setVisible(index==1);
					if (!this.comboData.hidden) {
//						this.selectYear(this.comboData.getValue());
					}

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboData = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['dataType'],
	        	data: [['IPM'],['IPR']]
	    	}),
		    valueField: 'dataType',
		    displayField: 'dataType',
			value: 'IPM',
			width: 60,
			listeners: {
				select: function(combo, record, index) {
					this.selectYear(this.comboAnni.getValue());
				},
				scope: this
			}
		});

		this.comboMese = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsMese,
		    valueField: 'num',
		    displayField: 'abbr',
			width: 80,
			listeners: {
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
							'<br><font size=2>'+ this.titlePanel+'</font></h1>');
		
					// rilascia quelli già allocati
					var g = FusionCharts(this.task+"_chartId"); /* sufficiente per FusionChart vers.3, non per la 2 */
					if (g) g.dispose();
					var g2 = FusionCharts(this.task+"_chartId2");
					if (g2) g2.dispose();

					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
					// non va: è asincrono anche se la guida non lo dice
					//g.setXMLUrl("server/charts/sintesi.php?type=stack&mese="+record.data.num+"&task="+this.task);

					Ext.Ajax.request({
				        url: 'server/charts/sintesi.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
							g.setXMLData(obj.responseText);
							g.render(this.task+"_cc");
				        },	scope: this});	

					g2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
//                    g2.setXMLUrl("server/charts/sintesiPerc.php?type=stack&mese="+record.data.num+"&task="+this.task);
//                    g2.render(this.task+"_cc2");
					Ext.Ajax.request({
				        url: 'server/charts/sintesiPerc.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, task: this.task},
				        success: function(obj) {
							g2.setXMLData(obj.responseText);
							g2.render(this.task+"_cc2");
				        },	scope: this});	

				},
				scope: this
			}
		});

		this.comboAnni = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsAnni,
		    valueField: 'num',
		    displayField: 'num',
			width: 60,
			listeners: {
				select: function(combo, record, index) {
					var dataType = this.comboData.getValue();
					Ext.getCmp(this.task+'_title').update('<h1>'+dataType+' - Fiscal Year '+record.data.num +'</h1>');
		
					var g = FusionCharts(this.task+"_chartId3"); 
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId3", "100%", "90%", "0", "1" );
                  //g.setXMLUrl("server/charts/sintesiStory.php?type=stack&anno="+record.data.num+"&task="+this.task+"&data="+dataType); //pie");
                    //g.render(this.task+"_story");
					Ext.Ajax.request({
				        url: 'server/charts/sintesiStory.php',
				        method: 'GET',
				        params: {type: 'stack', id:this.id, anno: record.data.num, task: this.task, data: dataType},
				        success: function(obj) {
							g.setXMLData(obj.responseText);
							g.render(this.task+"_story");
				        },	scope: this});	

				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboTipo,this.comboData,this.comboAnni,this.comboMese]
			}, {
				xtype:'panel',
				layout: 'column',
				id:this.task+'_pnl',
				items: [{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_cc'
				},{
					xtype:'panel',
					columnWidth: .5,
					id:this.task+'_cc2'
			}]}, {
				xtype:'panel',
				hidden: true,
				id:this.task+'_story'
			}],
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Mensile');
					}
					var idx = this.comboTipo.store.find('tipo',this.comboTipo.getValue());
					this.comboTipo.fireEvent('select',this.comboTipo,null,idx); 
				},
				scope: this
			}
	    });
		
		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
//	                '->', {type:'button', text:'Grafico', icon:'images/3dgraph.png', handler:this.changeType, scope:this},
					' '
				];

		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.Sintesi.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	changeType: function() {
		var g = FusionCharts(this.task+"_chartId");
		this.itipo++; 
		if (this.itipo==DCS.Charts.tipi.length) 
			this.itipo = 0;
		g = g.clone( { swfUrl : 'FusionCharts/'+DCS.Charts.tipi[this.itipo]+'.swf' } );
		g.render(this.task+"_cc");
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectMonth: function(mese) {
		this.comboMese.setValue(mese);
		if (this.dsMese) // se inizializzato
		{
			var idx = this.dsMese.find('num',mese);
			var rec = this.dsMese.getAt(idx);
			this.comboMese.fireEvent('select',this.comboMese,rec,idx); 
		}
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectYear: function(anno) {
		this.comboAnni.setValue(anno);
		if (this.dsAnni) // se inizializzato
		{
			var idx = this.dsAnni.find('num',anno);
			var rec = this.dsAnni.getAt(idx);
			this.comboAnni.fireEvent('select',this.comboAnni,rec,idx); 
		}
    }

});

//--------------------------------------------------------------------
// Funzione richiamata da sintesiStory.php per il drill down
//--------------------------------------------------------------------
DCS.Charts.presentaMese = function(idGrafico,numMese)
{
	var pannello = Ext.getCmp(idGrafico);
	pannello.comboTipo.setValue('Mensile');
	pannello.comboTipo.fireEvent('select',pannello.comboTipo,null,0); // Imposta la combo su mensile
	pannello.selectMonth(numMese);
};

//--------------------------------------------------------------------
// Tabella dei target, nella pagina del cruscotto=piramide
//--------------------------------------------------------------------
DCS.Charts.TargetTable = Ext.extend(Ext.grid.GridPanel, {
//	width:380,
	gstore: null,
	pagesize: 0,
	titlePanel: '',
		
	initComponent : function() {
		var fields = [{name: 'FasciaRecupero'},
		              {name: 'TitoloRegolaProvvigione'},
		              {name: 'Agenzia'},
		              {name: 'TargetIPR',type: 'int'},
		              {name: 'IPR',type: 'float'},
		              {name: 'IPF',type: 'float'},
		              {name: 'Mese',type: 'int'}];

		var	columns = [{dataIndex:'FasciaRecupero',width:70, header:'Fascia',sortable:false},
		   	           {dataIndex:'TitoloRegolaProvvigione',width:100, header:'Definizione',sortable:false,hidden:true},
		   	           {dataIndex:'Agenzia',width:120, header:'Agenzia',sortable:false},
		   	           {dataIndex:'TargetIPR',width:70, header:'Target IPR',sortable:false,align:'center'},
		   	           {dataIndex:'IPR',width:60, header:'IPR',sortable:false,align:'right',xtype:'numbercolumn',format:'000,00/i'},
		   	           {dataIndex:'IPF',width:60, header:'IPF',sortable:false,align:'right',xtype:'numbercolumn',format:'000,00/i'}];
		
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/charts/pyramid.php',
				method: 'GET'
			}),   
			/* specificati al momento della reload: baseParams:{task:'table', anno:this.anno, mese:this.mese},*/
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
  		});

		Ext.apply(this,{
			store: this.gstore,
			autoHeight: false,
//			autoExpandColumn: true,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''), //false, //true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			border: true,
			loadMask: true,
			columns: columns,
			valign:'center'
	    });

		var tbarItems = [
							{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
							'->', {type: 'button', text: 'Stampa elenco',  icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}, scope:this},
			                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
			                '-', helpButton("Grafici"),' '
						];
	
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.TargetTable.superclass.initComponent.call(this, arguments);
	}
});
//-----------------------------------------------------------
// Griglia con i dati per area geografica
//-----------------------------------------------------------
DCS.Charts.GeoTable = Ext.extend(Ext.grid.GridPanel, {
	gstore: null,
	width: 2000,
	pagesize: 0,
	titlePanel: '',
	task:null,
		
	initComponent : function() {
		var fields,columns;
		if (this.task=="GEO")
		{
			fields = [{name: 'Area'},
		              {name: 'Totale',type:'float'},
		              {name: 'TotaleNum',type:'int'},
		              {name: 'Ats'},{name: 'AtsNum',type:'int'},
		              {name: 'City1'},{name: 'City1Num',type:'int'},
		              {name: 'City2'},{name: 'City2Num',type:'int'},
		              {name: 'Eurocollection'},{name: 'EurocollectionNum',type:'int'},
		              {name: 'Fides'},{name: 'FidesNum',type:'int'},
		              {name: 'GeaServices'},{name: 'GeaServicesNum',type:'int'},
		              {name: 'Kreos'},{name: 'KreosNum',type:'int'},
		              {name: 'Ncp31'},{name: 'Ncp31Num',type:'int'},
		              {name: 'Nicol35'},{name: 'Nicol35Num',type:'int'}, 
		              {name: 'Osirc'},{name: 'OsircNum',type:'int'},
		              {name: 'Sogec1'},{name: 'Sogec1Num',type:'int'},
		              {name: 'Sogec2'},{name: 'Sogec2Num',type:'int'},
		              {name: 'Starcredit'},{name: 'StarcreditNum',type:'int'}
//		              {name: 'Sting'},{name: 'StingNum',type:'int'}
		              ];

		 	columns = [{dataIndex:'Area',width:87, header:'Regione',sortable:false},
		   	           {dataIndex:'Totale',width:100, header:'TOTALE<br>IPR %',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'000,00 %/i'},
		   	           {dataIndex:'TotaleNum',width:50, header:'TOTALE<br>N.',sortable:true,align:'right'},
		   	           {dataIndex:'City1',width:100, header:'City (24)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'City1Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'City2',width:100, header:'City (P4)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'City2Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Eurocollection',width:100, header:'Eurocoll.<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'EurocollectionNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fides',width:100, header:'Fides<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FidesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'GeaServices',width:100, header:'Gea Services<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'GeaServicesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Kreos',width:100, header:'Kreos<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'KreosNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Ncp31',width:100, header:'NCP<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Ncp31Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Nicol35',width:100, header:'Nicol (35)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Nicol35Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Osirc',width:100, header:'Osirc (2A)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'OsircNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Sogec1',width:100, header:'Sogec (S2)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Sogec1Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Sogec2',width:100, header:'Sogec (P2)<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'Sogec2Num',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Starcredit',width:100, header:'Starcredit<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'StarcreditNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           //{dataIndex:'Sting',width:100, header:'Sting<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	        //{dataIndex:'StingNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'dummy',width:2}];
		}
		else // tabella per stragiudiziale
		{
			fields = [{name: 'Area'},
		              {name: 'Totale',type:'float'},
		              {name: 'TotaleNum',type:'int'},
		              {name: 'Css'},{name: 'CssNum',type:'int'},
		              {name: 'EY'},{name: 'EYNum',type:'int'},
		              {name: 'Fides'},{name: 'FidesNum',type:'int'},
		              {name: 'Fire'},{name: 'FireNum',type:'int'},
		              {name: 'Nicol'},{name: 'NicolNum',type:'int'},
		              {name: 'City'},{name: 'CityNum',type:'int'},
		              {name: 'Irc'},{name: 'IrcNum',type:'int'},
		              {name: 'Luzzi'},{name: 'LuzziNum',type:'int'}
		              ];

		 	columns = [{dataIndex:'Area',width:87, header:'Regione',sortable:false},
		   	           {dataIndex:'Totale',width:100, header:'TOTALE<br>IPR %',sortable:true,align:'right',css:'background-color:aquamarine;font-weight:bold;',xtype:'numbercolumn',format:'000,00 %/i'},
		   	           {dataIndex:'TotaleNum',width:50, header:'TOTALE<br>N.',sortable:true,align:'right'},
		   	           {dataIndex:'Css',width:100, header:'CSS<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'CssNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'EY',width:100, header:'ERNST & YOUNG<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'EYNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fides',width:100, header:'FIDES<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FidesNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Fire',width:100, header:'FIRE<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'FireNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Nicol',width:100, header:'NICOL<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'NicolNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'City',width:100, header:'CITY<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'CityNum',width:50, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},
		   	           {dataIndex:'Irc',width:100, header:'IRC<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'IrcNum',width:51, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},		   	         
		   	           {dataIndex:'Luzzi',width:100, header:'Studio Luzzi<br>IPR',sortable:true,align:'right',css:'background-color:lavender;',renderer:DCS.render.floatV},
		   	           {dataIndex:'LuzziNum',width:51, header:'N.',sortable:true,align:'right',renderer:DCS.render.intV},		   	         
					   {dataIndex:'dummy',width:2}];
		}
		this.gstore = new Ext.data.GroupingStore({
			autoDestroy: true,
			proxy: new Ext.data.HttpProxy({
				url: 'server/charts/geography.php',
				method: 'GET'
			}),   
			/* specificati al momento della reload: baseParams: task:,anno:this.anno, mese:this.mese},*/
			reader: new Ext.data.JsonReader({
				root: 'results', //name of the property that is container for an Array of row objects
				totalProperty: 'total',
				fields: fields
			})
		});

		Ext.apply(this,{
			store: this.gstore,
			view: new Ext.grid.GroupingView({
				autoFill: (Ext.state.Manager.get(this.stateId,'')==''), //false, //true,
				forceFit: false,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "elementi" : "elemento"]})',
		        //enableNoGroups: false,
	            hideGroupedColumn: true
           }),
			border: true,
			loadMask: true,
			columns: columns,
			valign:'center'
	    });

		var tbarItems = [
							{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
							'->', {type: 'button', text: 'Stampa elenco',  icon: 'images/stampa.gif', handler: function(){Ext.ux.Printer.print(this);}, scope:this},
			                '-', {type: 'button', hidden:!CONTEXT.EXPORT, text: 'Esporta elenco', icon:'images/export.png',  handler: function(){Ext.ux.Printer.exportXLS(this);}, scope:this},
			                '-', helpButton("TabellaGeo"),' '
						];
	
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.GeoTable.superclass.initComponent.call(this, arguments);
	}
});
//-----------------------------------------------------------
// Pagina con il grafico "cruscotto" e la tabella dei target
//-----------------------------------------------------------
DCS.Charts.Pyramid = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	itipo: 4,
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	grid: null,
		
	initComponent : function() {
	/*
		var myChart = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
		myChart.configure("XMLLoadingText","Caricamento in corso...");

		var myChart2 = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
		myChart2.configure("XMLLoadingText","Caricamento in corso...");
	 */	
		
		this.dsMese = DCS.Store.ChartMesi.getInstance();

		this.dsAnni = DCS.Store.ChartAnni; //.getInstance();

		this.comboTipo = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['tipo'],
	        	data: [['Mese'],['Anno fiscale']]
	    	}),
		    valueField: 'tipo',
		    displayField: 'tipo',
			width: 100,
			listeners: {
				select: function(combo, record, index) {
					this.comboMese.setVisible(index==0);
					Ext.getCmp(this.task+'_pnl').setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
					Ext.getCmp(this.task+'_story').setVisible(index==1);
					if (!this.comboAnni.hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboMese = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsMese,
		    valueField: 'num',
		    displayField: 'abbr',
			width: 80,
			listeners: {
				// alla selezione di anno o mese nella combobox, riempie grafico e tabella
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + ' </h1>');
		
					// Costruisce il grafico
					var g = FusionCharts(this.task+"_chartId");
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId", "100%", "90%", "0", "1" );
                  //  g.setXMLUrl("server/charts/pyramid.php?type=stack&mese="+record.data.num);
                  //  g.render(this.task+"_pnl");
					Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', mese: record.data.num, gruppo: this.gruppo},
				        success: function(obj) {
				        	var result = obj.responseText;
				        	var parti  = result.split("\n");
							Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + 
									'<br>('+ parti[0]+' pratiche affidate in totale)</h1>');
							
							g.setXMLData(parti[1]);
							g.render(this.task+"_pnl");
			        },	scope: this});	
					
					// Aggiorna la griglia dei target
					this.grid.titlePanel = "Target "+record.data.mese;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:'table', mese:record.data.num, gruppo:this.gruppo};
					gstore.reload();
				},
				scope: this
			}
		});

		this.comboAnni = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsAnni,
		    valueField: 'num',
		    displayField: 'num',
			width: 60,
			listeners: {
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num +'</h1>');
		
					var g = FusionCharts(this.task+"_chartId2");
					if (g) g.dispose();
					g = new FusionCharts("FusionCharts/"+DCS.Charts.tipi[this.itipo]+".swf", this.task+"_chartId2", "100%", "90%", "0", "1" );
					//g.setXMLUrl("server/charts/pyramid.php?type=stack&anno="+record.data.num);
                    //g.render(this.task+"_story");
					Ext.Ajax.request({
				        url: 'server/charts/pyramid.php',
				        method: 'GET',
				        params: {type: 'stack', anno: record.data.num, gruppo: this.gruppo},
				        success: function(obj) {
				        	var result = obj.responseText;
				        	var parti  = result.split("\n");
							Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num  + 
									'<br>('+ parti[0]+' pratiche affidate in totale)</h1>');
							
							g.setXMLData(parti[1]);
							g.render(this.task+"_story");
				        },	scope: this});	
					// Aggiorna la griglia dei target
					this.grid.titlePanel = "Target Fiscal Year "+record.data.num;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:'table', anno:record.data.num, gruppo:this.gruppo};
					gstore.reload();
				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboTipo,this.comboAnni,this.comboMese]
			},{
				xtype:'panel',
				layout: 'column',
				id:this.task+'_page',
				items: [{
					xtype:'panel',
					columnWidth: .6,
					layout: 'column',
					id:this.task+'_pnl'
				},{
					xtype:'panel',
					hidden: true,
					columnWidth: .6,
					layout: 'column',
					id:this.task+'_story'
				},{
					xtype:'panel',
					columnWidth: .4,
					padding:'20 0 0 0',
					id:this.task+'_table',
height:450,
					layout:'fit',
					items: [this.grid]
			}] // fine array dei tre items grafico+tabella
			}],// fine array dei due item che soctituiscono la pagina (titolo+combos , grafici+tabella)
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Mese');
					}
					var idx = this.comboTipo.store.find('tipo',this.comboTipo.getValue());
					this.comboTipo.fireEvent('select',this.comboTipo,null,idx); 
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
//	                '->', {type:'button', text:'Grafico', icon:'images/3dgraph.png', handler:this.changeType, scope:this},
					' '
				];
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.Pyramid.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // Visualizza dettaglio
    //--------------------------------------------------------
	changeType: function() {
		var g = FusionCharts(this.task+"_chartId");
		this.itipo++; 
		if (this.itipo==DCS.Charts.tipi.length) 
			this.itipo = 0;
		g = g.clone( { swfUrl : 'FusionCharts/'+DCS.Charts.tipi[this.itipo]+'.swf' } );
		g.render(this.task+"_pnl");
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectMonth: function(mese) {
		this.comboMese.setValue(mese);
		var idx = this.dsMese.find('num',mese);
		var rec = this.dsMese.getAt(idx);
		this.comboMese.fireEvent('select',this.comboMese,rec,idx); 
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectYear: function(anno) {
		this.comboAnni.setValue(anno);
		var idx = this.dsAnni.find('num',anno);
		var rec = this.dsAnni.getAt(idx);
		this.comboAnni.fireEvent('select',this.comboAnni,rec,idx); 
    }

});
//------------------------------------------------------------
// Pagina con la tabella per area geografica
//------------------------------------------------------------
DCS.Charts.Geography = Ext.extend(Ext.Panel, {
	titlePanel: '',
	task: '',
	dsMese: null,
	dsAnni: null,
	comboTipo: null,
	comboMese: null,
	comboAnni: null,
	grid: null,
		
	initComponent : function() {
		this.dsMese = DCS.Store.ChartMesi.getInstance();
		this.dsAnni = DCS.Store.ChartAnni; //.getInstance();
		this.comboTipo = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: new Ext.data.ArrayStore({
	        	fields: ['tipo'],
	        	data: [['Mese'],['Anno fiscale']]
	    	}),
		    valueField: 'tipo',
		    displayField: 'tipo',
			width: 100,
			listeners: {
				select: function(combo, record, index) {
					this.comboMese.setVisible(index==0);
					if (!this.comboMese.hidden) {
						if (this.comboMese.getValue() == '') {
							var oggi = new Date();
							this.selectMonth(oggi.dateFormat('Ym'));
						} else {
							this.selectMonth(this.comboMese.getValue());
						}
					}

					this.comboAnni.setVisible(index==1);
					if (!this.comboAnni.hidden) {
						if (this.comboAnni.getValue() == '') {
							var oggi = new Date();
							var fy = oggi.dateFormat('Y');
							if (oggi.dateFormat('m')>LAST_FY_MONTH) fy++;
							this.selectYear(fy);
						} else {
							this.selectYear(this.comboAnni.getValue());
						}
					}

					this.doLayout();
				},
				scope: this
			}
		});


		this.comboMese = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsMese,
		    valueField: 'num',
		    displayField: 'abbr',
			width: 80,
			listeners: {
				// alla selezione di anno o mese nella combobox, riempie tabella
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>' + record.data.FY + ' - ' + record.data.mese  + ' </h1>'
							+'<br><font size=2>Recupero esattoriale per Regione</font></h1>');
					this.grid.titlePanel = "Risultati per regione - " + record.data.FY + ' - ' + record.data.mese;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:this.task,mese:record.data.num, gruppo:this.gruppo};
					gstore.reload();
				},
				scope: this
			}
		});

		this.comboAnni = new Ext.form.ComboBox({
		    triggerAction: 'all',
		    mode: 'local',
			lazyInit: false, 
			forceSelection: true,
			autoSelect: true,
		    store: this.dsAnni,
		    valueField: 'num',
		    displayField: 'num',
			width: 60,
			listeners: {
				select: function(combo, record, index) {
					Ext.getCmp(this.task+'_title').update('<h1>Fiscal Year '+record.data.num +'</h1>');
		
					this.grid.titlePanel = "Risultati per regione - Fiscal Year "+record.data.num;
					var gstore = this.grid.getStore();
					gstore.baseParams = {task:this.task,anno:record.data.num, gruppo:this.gruppo};
					gstore.reload();
				},
				scope: this
			}
		});

		Ext.apply(this,{
			header: true,
			border: false,
			layout: 'vbox',
			layoutConfig: {align: 'stretch'},
			items: [{
				xtype:'panel',
				layout: 'hbox',
				items:[{
					id: this.task+'_title',
					xtype: 'box',
					style: 'text-align:center;',
					flex: 1
				},this.comboTipo,this.comboAnni,this.comboMese]
			},Ext.apply(this.grid,{flex:1})],// fine array dei due item che costituiscono la pagina (titolo+combos , tabella)
			listeners: {
				activate: function(pnl) {
					if (this.comboTipo.getValue() == '') {
						this.comboTipo.setValue('Mese');
					}
					var idx = this.comboTipo.store.find('tipo',this.comboTipo.getValue());
					this.comboTipo.fireEvent('select',this.comboTipo,null,idx); 
				},
				scope: this
			}
	    });

		var tbarItems = [
					{xtype:'tbtext', text:this.titlePanel, cls:'panel-title'},
//	                '->', {type:'button', text:'Grafico', icon:'images/3dgraph.png', handler:this.changeType, scope:this},
					' '
				];
		Ext.apply(this, {
	        tbar: new Ext.Toolbar({
				cls: "x-panel-header",
	            items:tbarItems
	        })		
		});

		DCS.Charts.Geography.superclass.initComponent.call(this, arguments);

	},

	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectMonth: function(mese) {
		this.comboMese.setValue(mese);
		var idx = this.dsMese.find('num',mese);
		var rec = this.dsMese.getAt(idx);
		this.comboMese.fireEvent('select',this.comboMese,rec,idx); 
    },
	
	//--------------------------------------------------------
    // 
    //--------------------------------------------------------
	selectYear: function(anno) {
		this.comboAnni.setValue(anno);
		var idx = this.dsAnni.find('num',anno);
		var rec = this.dsAnni.getAt(idx);
		this.comboAnni.fireEvent('select',this.comboAnni,rec,idx); 
    }

});

DCS.Charts.Tabs = function(){
	tabPanelAzienda = null;

	return {
		create: function(gruppo){		
			this.tabPanelAzienda = new Ext.TabPanel({
				enableTabScroll: true,
				flex: 1,
				items: []
			});
			Ext.Ajax.request({
				url: 'server/AjaxRequest.php', method:'POST',
				params: { 
					task: 'read',
					sql: "SELECT DISTINCT FasciaRecupero FROM v_graph_provvigione WHERE IdReparto="+CONTEXT.IdReparto+" and gruppo="+gruppo+" AND FasciaVecchia='N'"
				},
				scope: this,
	        	success: function(xhr) {
                	eval('var resp = '+xhr.responseText);
					var arr = resp.results;
					for (var i=0; i<resp.total; i++) {
						var fascia = arr[i].FasciaRecupero;
						this.tabPanelAzienda.add(new DCS.Charts.Sintesi({
							titlePanel: 'Sintesi',
							title: fascia,
							task: fascia
						}));
					}
					this.tabPanelAzienda.doLayout();
					this.tabPanelAzienda.setActiveTab(0);
				}
			});
			return tabPanelAzienda;
		},
		
		create_TFSI: function(){
			items = Array();
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_INS || CONTEXT.CAN_GRAPH_INS_TEK)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Prerecupero: < 30 gg',
					title: 'INS+TEK',
					task: 'INS,INS+TEK',id: 'graphINS'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_INS_TEK_REC)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'INS+TEK Recidivo 0-30 gg',
					title: 'INS+TEK Rec',
					task: 'INS+TEK REC',id: 'graphINSREC'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_PHONE || CONTEXT.CAN_GRAPH_PHONE_COLL
					|| CONTEXT.CAN_GRAPH_I_ESA || CONTEXT.CAN_GRAPH_1o_HOME || CONTEXT.CAN_GRAPH_1o_HOME_COLL || CONTEXT.CAN_GRAPH_1o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Home collection 1 Livello: 31-60 gg',
				title: '1&deg; HOME COLL',
				task: '1&deg; HOME,1&deg; HOME COLL,1&deg; HOME LOAN,I ESA',	id: 'graph1ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_II_ESA_LOAN || CONTEXT.CAN_GRAPH_2o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 2&deg; livello: 61-90 gg',
				title: '2&deg; HOME LOAN',
				task: '2&deg; HOME LOAN,II ESA,II ESA LOAN',	id: 'graph2ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_ESA_LEASING || CONTEXT.CAN_GRAPH_2o_HOME_LEASING)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Leasing Home 2&deg; livello: >60 gg',
				title: '2&deg; HOME LEASING',
				task: '2&deg; HOME LEASING,ESA LEASING',	id: 'graphLEA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_III_ESA_LOAN || CONTEXT.CAN_GRAPH_3o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 3&deg; Livello: 91-150 gg',
				title: '3&deg; HOME LOAN',
				task: '3&deg; HOME LOAN,III ESA LOAN',	id: 'graph3ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_IV_ESA_LOAN || CONTEXT.CAN_GRAPH_4o_HOME_LOAN)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Loan Home coll. 4&deg; Livello: > 150gg',
				title: '4&deg; HOME LOAN',
				task: '4&deg; HOME LOAN,IV ESA LOAN',	id: 'graph4ESA'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_1o_FLOTTE)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Flotte 1&deg; livello: 31-90 gg',
				title: '1&deg; FLOTTE',
				task: '1&deg; FLOTTE',	id: 'graphFLO1'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_2o_FLOTTE || CONTEXT.CAN_GRAPH_FLOTTE)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Flotte 2&deg; livello: >90 gg',
				title: '2&deg; FLOTTE',
				task: '2&deg; FLOTTE,FLOTTE',	id: 'graphFLO2'
				}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_SALDI_POS)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Saldi positivi',
				title: 'SALDI POS.',
				task: 'SALDI POS',	id: 'graphSP'
				}));


			var targetGrid = new DCS.Charts.TargetTable({
				titlePanel: 'Target del periodo',
				stateId: 'TargetTable1',
				stateful: true, gruppo: 1
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.Charts.Pyramid({
				titlePanel: 'Cruscotto TFSI',
				title: 'Cruscotto TFSI',
				task: 'PYRAMID',
				grid: targetGrid, gruppo: 1
				}));

			var geoGrid = new DCS.Charts.GeoTable({
				stateId: 'GeoTable1',
				stateful: true,task: 'GEO',
				titlePanel: 'Risultati recupero esattoriale per regione'
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
				items.push(new DCS.Charts.Geography({
				titlePanel: 'Aree geografiche',
				title: 'Aree geografiche',
				task: 'GEO', grid:geoGrid
				}));

			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		},
		create_TFSI_STR: function(){
			items = Array();
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_REPO)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: REPO',
					title: 'REPO',
					task: 'REPO',id: 'graphREPO'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_SOFT)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: DBT Soft',
					title: 'DBT Soft',
					task: 'DBT SOFT',id: 'graphDBTsoft'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_HARD)
				items.push(new DCS.Charts.Sintesi({
					titlePanel: 'Stragiudiziale: DBT Hard',
					title: 'DBT Hard',
					task: 'DBT HARD',id: 'graphDBThard'
					}));
			if (CONTEXT.CAN_GRAPH_ALL || CONTEXT.CAN_GRAPH_DBT_STRONG)
				items.push(new DCS.Charts.Sintesi({
				titlePanel: 'Stragiudiziale: DBT Strong',
				title: 'DBT Strong',
				task: 'DBT STRONG',	id: 'graphDBTstrong'
				}));

			var targetGrid = new DCS.Charts.TargetTable({
				titlePanel: 'Target del periodo',
				stateId: 'TargetTable2',
				stateful: true, gruppo: 2,
				hidden: false
				});
			
			if (CONTEXT.CAN_GRAPH_ALL)
			{
				items.push(new DCS.Charts.Pyramid({
				titlePanel: 'Cruscotto TFSI',
				title: 'Cruscotto TFSI',
				task: 'PYRAMID',
				grid: targetGrid, gruppo: 2
				}));
				
				var geoGrid = new DCS.Charts.GeoTable({
					stateId: 'GeoTable2',
					stateful: true,task: 'GEO2',
					titlePanel: 'Risultati recupero stragiudiziale per regione'
					});

				items.push(new DCS.Charts.Geography({
				titlePanel: 'Aree geografiche',
				title: 'Aree geografiche',
				task: 'GEO2', grid:geoGrid
				}));
			}
			return new Ext.TabPanel({
    			activeTab: 0,
				enableTabScroll: true,
				flex: 1,
				items: items
			});
		}		
	};
	
}();
