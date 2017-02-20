Ext.namespace('DCS');

DCS.Azioni = Ext.extend(Ext.Button, {
	gstore: null,
	sm: null,
	idContratto : undefined,
	numPratica : undefined,
	disabled: true,
	employ:false,
	isStorico: false,
	
	initComponent: function() {

		Ext.apply(this, {
			text: 'Azioni',
			icon: 'ext/examples/shared/icons/fam/table_refresh.png',
			menu: {
				xtype: 'menu',
				items: [{icon: 'ext/resources/images/access/grid/loading.gif', text:'Attendere caricamento...'}]
			},
			handler: this.caricaMenu,
			scope: this
		});

		DCS.Azioni.superclass.initComponent.call(this);

	},

	caricaMenu: function(grid, rowIndex, colIndex, btn, evt) {
		if (this.menu.items.getCount() != 1) {
   			this.menu.removeAll();
			this.menu.add({icon: 'ext/resources/images/access/grid/loading.gif', text:'Attendere caricamento...'});
			this.menu.doLayout();
			if (evt) {
				this.menu.showAt(evt.getXY());
			}
		}
		
		var encoded_keys = [];
		var codes = [];
		if (this.idContratto!=undefined && this.numPratica!=undefined) {
				encoded_keys.push(this.idContratto);
				codes.push(this.numPratica);			
		} else {
			if (evt) {
				this.sm.selectRow(rowIndex, true);
			}
			var sel = this.sm.selections.items;
			for (i=0; i<sel.length; i++) {
				encoded_keys.push(sel[i].get('IdContratto'));
				codes.push(sel[i].get('numPratica'));
			}
		}
		
		Ext.Ajax.request({
			url: 'server/menuAzioni.php', method:'POST',
			params: { 
				employ:this.employ,
				isStorico: this.isStorico,
				contracts: Ext.encode(encoded_keys),
				codContracts: Ext.encode(codes)
			},
			scope: this,
        	failure: function() {}, //Ext.Msg.alert("Impossibile aprire la pagina di dettaglio", "Errore Ajax");},
        	success: function(xhr) {
    			this.menu.removeAll();
    			if(xhr.responseText != '')
    			{
					eval('this.menu.add('+xhr.responseText+');'); //replace(/\'/g,'\\\'')
					this.menu.doLayout();
					
					if (evt) {
						this.menu.showAt(evt.getXY());
					}
    			}
			}
		});
	}

});