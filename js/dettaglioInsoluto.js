// Crea namespace DCS
Ext.namespace('DCS');

//window di dettaglio
var win;

DCS.recordInsoluto = Ext.data.Record.create([
	 		{name: 'Capitale',		convert:numdec_it, type: 'float', useNull: true},
	 		{name: 'InteressiMora',	convert:numdec_it, type: 'float', useNull: true},
	 		{name: 'AltriAddebiti',	convert:numdec_it, type: 'float', useNull: true},
	 		{name: 'SpeseRecupero',	convert:numdec_it, type: 'float', useNull: true},
	 		{name: 'Riscatto',	    convert:numdec_it, type: 'float', useNull: true}
	 ]);

DCS.DettaglioInsoluto = Ext.extend(Ext.Panel, {
	idContratto: '',
	
	initComponent: function() {
		
		var dsInsoluti = new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'server/AjaxRequest.php',
				method: 'POST'
			}),   
			baseParams:{task: 'read'},
			reader:  new Ext.data.JsonReader(
				{root: 'results'}, DCS.recordInsoluto
	        )
		});
		
		//Form su cui montare gli elementi
		var formInsoluto = new Ext.form.FormPanel({
//			autoHeight: true,
			frame: true,
			bodyStyle: 'padding:5px 5px 0',
			border: false,
			trackResetOnLoad: true,
			reader: new Ext.data.ArrayReader({
				root: 'results',
				fields: DCS.recordInsoluto}),
			items: [{
				xtype: 'fieldset',
				autoHeight: true,
				layout: 'column',
				items: [{
					xtype: 'panel',
					layout: 'form',
					labelWidth: 110,
					columnWidth: 1,
					defaults: {xtype: 'textfield', anchor: '97%'},
					items: [{
						fieldLabel: 'idContratto',
						readOnly:true,
						hidden:true,
						style:'text-align:right',
						name: 'idcontratto'
					},{
						fieldLabel: 'Capitale',
						readOnly:true,
						style:'text-align:right',
						name: 'Capitale'
					}, {
						fieldLabel: 'Interessi di mora',
						readOnly:true,
						style:'text-align:right',
						name: 'InteressiMora'
					}, {
						fieldLabel: 'Altri addebiti',
						readOnly:true,
						style:'text-align:right',
						name: 'AltriAddebiti'
					}, {
						fieldLabel: 'Spese di recupero',
						readOnly:true,
						style:'text-align:right',
						name: 'SpeseRecupero'
					}, {
						fieldLabel: 'Riscatto scaduto',
						readOnly:true,
						style:'text-align:right',
						name: 'Riscatto'
					}]
				}]
			}],
			buttons: [{
				text: 'Annulla',
				handler: function(){
					if (formInsoluto.getForm().isDirty()) {
						Ext.Msg.confirm('', 'I valori sono stati modificati, uscire senza salvare?', function(btn, text){
	    					if (btn == 'yes'){
					        	win.close();
						    }
						});
					} else
						win.close();
				},
				scope: this
			}]
		});

		Ext.apply(this, {
			items: [formInsoluto]
			//store: dsInsoluti
		});
		
		DCS.DettaglioInsoluto.superclass.initComponent.call(this);
		//caricamento dello store
		dsInsoluti.load({
			params:{
				sql: 'select * from v_dettaglio_insoluto where idcontratto='+this.idContratto
			},callback : function(rows,options,success) {
				formInsoluto.getForm().loadRecord(rows[0]);},
			scope:this
		});
	}	
});

// register xtype
Ext.reg('DCS_DettaglioInsoluto', DCS.DettaglioInsoluto);

//--------------------------------------------------------
// Visualizza dettaglio insoluto
//--------------------------------------------------------
function showInsolutoDetail(idContratto) {
	
	var winTitle = 'Dettaglio insoluto';
	win = new Ext.Window({
		width: 360,
		height: 260,
		minWidth: 360,
		minHeight: 260,
		layout: 'fit',
		id:'dettaglioInsoluto'+idContratto,
		stateful:false,
		plain: true,
		bodyStyle: 'padding:5px;',
		modal: true,
		title: winTitle,
		tools: [helpTool("DettaglioInsoluto")],
		constrain: true,
		items: [{
			xtype: 'DCS_DettaglioInsoluto',
			idContratto: idContratto
			}]
	});

	//Ext.apply(win.getComponent(0),{winList:win});
	win.show();
	
}; // fine funzione showUserDetail