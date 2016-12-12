	var fn_pivotGrid = function(){
	    // (funziona solo con dati non locali, cio? su file separato caricato da URL)
		var rec = Ext.data.Record.create([{name: 'classe'}, {name: 'prodotto'},{name: 'agenzia'}, {name:'count',type:'int'}]);

		var myStore = new Ext.data.Store({
    	    url: 'sintesi.json',
        	autoLoad: true,
         	reader: new Ext.data.JsonReader({
            	root: 'rows',
           	idProperty: 'id'
        	}, rec)
    	});

		return new Ext.grid.PivotGrid({
			title: 'Sintesi insoluti per classe, stato e prodotto',
			forceLayout: true,
			store: myStore,
			columnLines: true,
			autoScroll: true,
			autoHeight: true,
			viewConfig: { autoScroll: true, autoFill: true},
			aggregator: 'sum',
			measure: 'count',
			leftAxis: [{dataIndex: 'classe'}, {dataIndex: 'agenzia'}],
			topAxis: [{dataIndex: 'prodotto'}],
			renderer: function(value){
				if (value == 0) 
					return "";
				else 
					return value;
			},
			// paging bar on the bottom
			bbar: new Ext.Toolbar({
				items: ['-', {
					type: 'button',
					text: '&nbsp;&nbsp;Stampa',
					icon: 'images/stampa.gif'
				}, '-', {
					type: 'button',
					text: '&nbsp;&nbsp;Esporta',
					icon: 'ext/examples/shared/icons/fam/application_go.png'
				}, '-', {
					type: 'button',
					text: '&nbsp;&nbsp;Cambia vista',
					icon: 'ext/examples/shared/icons/fam/table_refresh.png'
				}]
			})
		});
	};

