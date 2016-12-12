/*
 * File: MainViewport.js
 * 
 * Viewport principale.
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.MainViewport = Ext.extend(Ext.Viewport, {
	url_avvisi: 'server/avvisi.php',
	url_scadenze: 'server/scadenze.php',
	url_pUser: 'server/pUtente.php',
	user_cal: false,
	user_avv: false,
	user_insa: false,
	user_inss: false,
	user_name: '',
	dateAvvisi: null,

	sottomenu: [], 

    initComponent: function() {
		var main_panel = new Ext.Panel({
			id: 'mainPanel',
			region: 'center',
			layout: 'vbox',			// nel nostro caso 'fit' non sembra funzionare come dovrebbe
			layoutConfig: {align: 'stretch'},
			frame: true,
			activeChild: null,
			items: [] 
		});
		DCS.mainPanel = main_panel;
		//---------------------------------------------------------
		// east_container
		// Pannello a destra con calendario, scadenze e avvisi
		// Visualizzazione condizionata al profilo
		//---------------------------------------------------------
		if (this.user_cal || this.user_avv) {
			var toolsMiniMaxi = [{
				id: 'minimize',
				qtip: 'Ripristina a lato',
				hidden: true,
				handler: this.min_east_panel,
				scope: this
			}, {
				id: 'maximize',
				qtip: 'Ingrandisci',
				handler: this.max_east_panel,
				scope: this
			}];
			
			if (this.user_inss == true) {
				toolsMiniMaxi.unshift({
					id: 'plus',
					qtip: 'Inserimento',
					handler: this.ins_avv_scad,
					scope: this
				});
			}
			
			var toolsMiniMaxiAvv = [{
				id: 'minimize',
				qtip: 'Ripristina a lato',
				hidden: true,
				handler: this.min_east_panel,
				scope: this
			}, {
				id: 'maximize',
				qtip: 'Ingrandisci',
				handler: this.max_east_panel,
				scope: this
			}];
			
			if (this.user_insa == true) {
				toolsMiniMaxiAvv.unshift({
					id: 'plus',
					qtip: 'Inserimento',
					handler: this.ins_avv_scad,
					scope: this
				});
			}
			
			var toolsRefresh = {
				id: 'refresh',
				qtip: 'Refresh',
				handler: this.refresh_All,
				scope: this
			};
	
			var scadenze = new Ext.Panel({
				id: 'scadenze_panel',
				title: 'Scadenze',
				flex: 1,
				tools: toolsMiniMaxi,
				layout: 'fit',
				itemId: 1,
				tipoVisualizzazione: 'Min',
				autoScroll: true,
				autoLoad: this.url_scadenze,
				frame: true
			});
			
			var avvisi = new Ext.Panel({
				id: 'avvisi_panel',
				title: 'Avvisi',
				flex: 1,
				tools: toolsMiniMaxiAvv,
				itemId: 2,
				autoScroll: true,
				autoLoad: this.url_avvisi,
				frame: true
			});
			
			var calendario = new Ext.Panel({
				frame: true,
				height: 210,
				itemId: 0,
				items: [{
					xtype: 'calendar',
					id: 'app-nav-picker',
					cls: 'ext-cal-nav-picker',
					noticeDays: this.dateAvvisi,
					listeners: {
						'selectNotice': {
							fn: this.cal_day_selected,
							scope: this
						}
					}
				}]
			});
			
			var east_container = new Ext.Panel({
				title: (this.user_cal)?'Calendario':'',
				tools: (this.user_cal)?[toolsRefresh,helpTool('Strutturadellapaginaprincipale#calendario')]:undefined,
				id: 'east_container',
				region: 'east',
				width: 192,
				layout: 'vbox',
				layoutConfig: {
					align: 'stretch'
				},
				collapsible: true,
				items: []
			});
			
			if (this.user_cal == true) {
				east_container.add(calendario);
				east_container.add(scadenze);
			}
			if (this.user_avv == true) {
				east_container.add(avvisi);
			}
		} else {
			var east_container = new Ext.Component({hidden:true});
		}
		
		//---------------------------------------------------------
		// west_container
		// Navigatore a sinistra
		//---------------------------------------------------------
		var menu = new DCS.Navigator({
			flex: 1,
			id: 'navigatore',
			//autoScroll:true,
			items: this.sottomenu
		});
		
		var west_container = new Ext.Panel({
			title: 'Menu',
			collapsible: true,
			region: 'west',
			width: 160,
			layout: 'vbox',
			layoutConfig: {align: 'stretch'},
			//items: [menu, links]
			items: [menu]
		});

		//---------------------------------------------------------
		// status_panel
		// Pannello in alto al centro con titolo e search field
		//---------------------------------------------------------
		var status_panel = new Ext.Panel({
			region: 'north',
			id: 'status',
			height: 0,
			
			tbar: new Ext.Toolbar({
				//cls: "x-panel-header",
				items: [
				    {xtype:'tbtext', text:'', id:'testo', cls:'panel-title'},
					'->',
					[{xtype: 'tbtext', text: 'Ricerca: ',id: 'labelCampoRicerca'},
					 new DCS.tabs_Ricerca({}),' ',
					  {
						id:'search_more',
						xtype: 'button',
						style: 'width:15; height:15',
						icon: 'images/lente.png',
						tooltip: 'Ricerca avanzata',
						handler: avviaRicercaAvanzata
					  }	 
					      ,' ',
					  {
						id:'max_main',
						xtype: 'button',
						style: 'width:15; height:15',
						icon: 'images/max_tool.png',
						tooltip: 'Ingrandisci',
						handler: function(){
							Ext.getCmp('top_area').hide();
							Ext.getCmp('max_main').setVisible(false);
							Ext.getCmp('min_main').setVisible(true);
							west_container.collapse(false);
							if (east_container.isXType('panel')) {
								east_container.collapse(false);
							}
						}
					},{
						id: 'min_main',
						hidden: true,
						xtype: 'button',
						width: 15, height:15,
						icon: 'images/min_tool.png',
						tooltip: 'Ripristina',
						handler: function(){
							Ext.getCmp('top_area').show();
							Ext.getCmp('min_main').setVisible(false);
							Ext.getCmp('max_main').setVisible(true);
							west_container.expand(false);
							if (east_container.isXType('panel')) {
								east_container.expand(false);
							}
						}
					},"-",helpButton("Funzionediricercadiretta")]
				]})
		});
		status_panel.relayEvents(menu, ['clickNavElem']);
		status_panel.on( {
			'clickNavElem' : function (panel, btn, status,t) {
				if (panel && status) {
					var testo = this.getTopToolbar().items.first();
					testo.setText( (btn.ownerCt.title?btn.ownerCt.title+' - ':'')+btn.text);
				}
	      	},
			scope : status_panel
	    });

		//---------------------------------------------------------
		// main_panel
		// Pannello centrale
		//---------------------------------------------------------
		main_panel.relayEvents(menu, ['clickNavElem']);
		main_panel.on( {
			'clickNavElem' : function (panel, btn, status) {
				if (panel) {
					if (status) {
						var mainp = Ext.getCmp('mainPanel'); 
						if (mainp.activeChild!=null && mainp.activeChild.hidden) {	// E' presente il pannello scadenze o avvisi
							var pnl = mainp.findById('scadenze_panel');
							if (pnl==null) {
								pnl = mainp.findById('avvisi_panel');
							}
							if (pnl) {
								this.minimizza(pnl);	
							}
						}
						
 						mainp.add(panel);
						mainp.activeChild = panel;
       	            	panel.show();
	                    mainp.doLayout();
	                    // quando si mostra lo storico, scrive un hint nel campo ricerca
	                    if (panel.id=='tabStorico') {
	                    	Ext.getCmp('labelCampoRicerca').setText('Ricerca nello Storico: ');
	                    } else {
	                    	Ext.getCmp('labelCampoRicerca').setText('Ricerca: ');
	                    }
					} else {
						panel.hide();
					}
				}
	      	},
			scope : this
	    });
		
		//---------------------------------------------------------
		// north_container
		// Contenitore in testa
		//---------------------------------------------------------
		var north_container = new Ext.Panel({
			id: 'Usr_log',
//			title: 'Utente',
//			flex: 1,
			//height: 56,
			//region: 'center',
			height: 72,
			border: false,
			bodyStyle: 'background-color:white;',
			margins: '0 0 0 0',
 			autoLoad: {
    			url: this.url_pUser,
				text: '&nbsp;'
			},
			frame: false
		});

		//---------------------------------------------------------
		// Applicazione della configurazione
		//---------------------------------------------------------
		Ext.apply(this, {
			layout: 'border',
			items: [{
				id:'top_area',
				xtype: 'container',
				region: 'north',
				layout: 'border',
				height: 80,
				items: [{				    
						id:'top_logo',
						xtype: 'container',
						region: 'west',
						width: 400,
						html: CONTEXT.LogoProdotto,
						style: 'background-color:white;'
					},{
					    id:'top_pUser',
						xtype: 'container',
						region: 'center',
						layout: 'vbox',
						//layout: 'border',
						style: 'background-color:white;',
						layoutConfig: {
							align: 'stretch'
						},
						items: [{xtype: 'container',flex:1},
						/*{
							id:'top_linkCr',
							xtype: 'container',
							width: 100,
							region: 'west',
							layout: 'border',
							style: 'background-color:white;',
							items:[{xtype: 'container',region: 'center',flex:1},{
								id:'top_linkCrSouth',
								xtype: 'container',
								width: 100,
								region: 'south',
								hidden: (navigator.userAgent.toLowerCase().indexOf('chrome') > -1),
								height: 20,
								html: '<a href="http://www.google.com/chrome?hl=it" target="_blank">scarica Google Chrome</a>'
							}]
						},*/north_container]
					},{
					    id:'topr_logo',
						xtype: 'container',
						region: 'east',
						width: 90,
						html: CONTEXT.LogoSocieta,
						style: 'background-color:white;text-align:center'						
					}]
				},
					west_container
				 ,{
					xtype: 'container',
					region: 'center',
					layout: 'border',
					items: [
						status_panel,
						main_panel,
						{
							xtype: 'container',
							region: 'south',
							height: 15,
							html: CONTEXT.Footer, 
							style: 'background-color: #fff;'
						}]
				},
				east_container
			],

			minimizza : function(panel){
				// imposto il tipo di visualizzazione del panel scadenze
				if (panel.id=='scadenze_panel'){
					panel.removeAll(true);
					panel.tipoVisualizzazione='Min';
					Ext.getCmp('scadenze_panel').load({url:this.url_scadenze,params:{data:Ext.getCmp('app-nav-picker').value.dateFormat('Y-m-d'),flagnota:true,tipoVisualizzazione:'Min'},nocache:true});
				}
				panel.getTool('minimize').hide();
				panel.getTool('maximize').show();
				var east = Ext.getCmp('east_container');
				east.insert(panel.itemId,panel);
				east.doLayout();
		    },

		    initMenu : function() {
				try {
					var firstM = this.sottomenu[0];
					firstM.toggleFirstSubmenu(firstM, false);
				} catch (e) {}
		    }

		});

        // call parent
        DCS.MainViewport.superclass.initComponent.apply(this, arguments);
		
        if((this.user_avv == true)||(this.user_cal == true)){this.add(east_container);}
        
        // fa comparire  il link "scarica chrome"
        if (navigator.userAgent.toLowerCase().indexOf('chrome') == -1)
        {
            elem = document.getElementById("linkToChrome");
            if (elem)
            	elem.style.display = "";
        }
	},
		
    ins_avv_scad : function(e, target, panel) {
		DCS.FormNota.showDetailNote(0,'',(panel.itemId==1)?'S':'A',0,0,'');
	},
	
	refresh_All: function(e, target, panel) {
		Ext.Ajax.request({
			url: 'server/dateAvvisi.php',
            success: function(xhr) {
                eval('var days = '+xhr.responseText);
                Ext.getCmp('app-nav-picker').setNoticeDays(days);
            }
		});
	},

    min_east_panel : function(e, target, panel) {
		this.minimizza(panel);
		var mainp = Ext.getCmp('mainPanel');
		mainp.activeChild.show();
		mainp.doLayout();
	},

    max_east_panel : function(e, target, panel) {
		var mainp = Ext.getCmp('mainPanel');
		var east = Ext.getCmp('east_container');
		var srcId = panel.id=='scadenze_panel'?'avvisi_panel':'scadenze_panel';
		var pnl = mainp.findById(srcId);
		if (pnl) {
			this.minimizza(pnl);
		} else {
			mainp.activeChild.hide();
		}
		// imposto il tipo di visualizzazione del panel scadenze
		if (panel.id=='scadenze_panel'){
			panel.tipoVisualizzazione='Max';
			panel.update('');
			panel.add(DCS.Comunicazioni.createScadenzarioMax(Ext.getCmp('app-nav-picker').value.dateFormat('Y-m-d')));
		}
		panel.getTool('maximize').hide();
		panel.getTool('minimize').show();
		mainp.add(panel);
		mainp.doLayout();
		east.doLayout();
	},
		
    cal_day_selected : function(dp, dt, notaPresente){
		var panel=Ext.getCmp('scadenze_panel');
		if ((panel.tipoVisualizzazione=='Min') || (panel.tipoVisualizzazione==''))
			panel.load({url:this.url_scadenze,params:{data:dt,flagnota:notaPresente,tipoVisualizzazione:'Min'},nocache:true});
		else
		{
			panel.removeAll(true);
			panel.add(DCS.Comunicazioni.createScadenzarioMax(dt));
			panel.doLayout();
		}
	}
	
});

//----------------------------------------------------------------
// avviaRicercaAvanzata
// Apre il form per la ricerca avanzata
//----------------------------------------------------------------
function avviaRicercaAvanzata() 
{
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"Qualche istante, prego..."});
	myMask.show();
	Ext.Ajax.request({
     url: 'server/formRicercaAvanzata.php', method:'POST',
		params: {},
		failure: function() {Ext.Msg.alert("Impossibile aprire la pagina per la ricerca avanzata", "Errore Ajax");},
        success: function(req)
        {
			var formPanel;
            eval(req.responseText);
			if (formPanel!=undefined) {	// se costruito un form
	            var win = new Ext.Window({
	                width: formPanel.width+30, height:formPanel.height+30, 
	                minWidth: formPanel.width+30, minHeight: formPanel.height+30,
	                layout: 'fit', plain:true, bodyStyle:'padding:5px;',modal: true,
	                title:  "Ricerca avanzata",
					constrain: true,
					modal: true,
					closable: true,
	                items: formPanel,
	                tools: [helpTool("Funzionediricercadiretta")]
	                });
	            win.show();
			}
			myMask.hide();
       } // fine corpo funzione Ajax.success
	} // fine corpo richiesta Ajax
  ) // fine parametri Ajax.request
}
