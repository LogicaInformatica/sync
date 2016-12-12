// Crea namespace DCS
Ext.namespace('DCS'); 

DCS.StoriaRecupero = function(idContratto, codContratto, isStorico) {
	
	   
	 // create the Data Store
	/*var select = "SELECT * FROM v_storiarecupero"
				+" WHERE idContratto="+idContratto;*/

	var select =  "SELECT * FROM v_storiarecupero"+(isStorico?'_storico':'')+" WHERE idContratto="+idContratto;
	
	var dsStoriaRecupero = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'read', sql: select},//this parameter is passed for any HTTP request
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results'//name of the property that is container for an Array of row objects
					//,id: 'IdStoriaRecupero'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'IdStoriaRecupero'},
					{name: 'CodAzione'},
					{name: 'titoloAzione'},
					{name: 'DataEvento'},
					{name: 'DescrEvento'},
					{name: 'NotaEvento'},
					{name: 'IdAzioneAutomatica'},
					{name: 'IdAzioneSpeciale'},
					{name: 'UserId'},
					{name: 'UserSuper'},
					{name: 'FlagSpeciale'},
					{name: 'HtmlAzioneEseguita'},
					{name: 'ValoriAzioneEseguita'},
					{name: 'FormWidth'},
					{name: 'FormHeight'}
				]
        ),
		sortInfo:{field: 'IdStoriaRecupero', direction: "DESC"},
		remoteSort:true,
		listeners:{
			load : function(Store, records, options)
			{
				//controllo di chi sta aprendo la griglia allegati
				var appoggio;
				for(var j=0; j<Store.getCount(); j++)
				{
					appoggio = Store.getAt(j);
					// se è una registrazione fatta impersonando, fa vedere i due userid all'amministratore
					// e solo quello dell'impersonatore all'utente ordinario
					if ( Store.getAt(j).get('lastSuper')!='' && Store.getAt(j).get('lastSuper') != null)
					{
						if (CONTEXT.IMPERSONA) // è un utente amministratore
							appoggio.set('LastUser',Store.getAt(j).get('lastSuper')+"/"+Store.getAt(j).get('LastUser'));
						else
							appoggio.set('LastUser',Store.getAt(j).get('lastSuper'));
					}
					appoggio.commit();
				}
			}
		}			
	});//end dsIndustry   

    var grid = new Ext.grid.EditorGridPanel({
       // width:1000,
        height:450,
        titlePanel:'Storia Recupero - Pratica: '+codContratto,
        store: dsStoriaRecupero,
        trackMouseOver:false,
        disableSelection:true,
        loadMask: true,

        // grid columns
        columns:[{
            header: "Data",
            dataIndex: 'DataEvento',
            width: 120,
            align: 'center',
            sortable: true
        },{
            header: "Utente",
            dataIndex: 'UserId',
            width: 50,
            align: 'center'
        },{
            header: "Descrizione evento",
            dataIndex: 'DescrEvento',
            width: 250,
            align: 'left',editor: new Ext.form.TextArea({readOnly:true}),
			renderer: DCS.render.word_wrap 
        },{
            header: "Nota",
            dataIndex: 'NotaEvento',
            width: 300,
            align: 'left',editor:new Ext.form.TextArea({readOnly:true}),
			renderer: DCS.render.word_wrap 
        },
        {
           xtype: 'actioncolumn', width: 40, align: 'center', header:'Azioni', exportable: false,
     	   fixed:true,
     	   items: [
     	           {
 	        	    tooltip: 'Visualizza dettaglio azione',
 		            getClass: function(v,meta,rec) 
 		            {
     	        	   if(rec.get('IdAzioneSpeciale')>'0' || rec.get('HtmlAzioneEseguita')>'')
     	        		   return 'see_dett';
     	        	   else 
						  return '';
     		       },                    	 
     		       handler: function(grid, rowIndex, colIndex) 
     		       {
            		    var rec = grid.getStore().getAt(rowIndex);
						if (rec.get('IdAzioneSpeciale')>0 && rec.get('HtmlAzioneEseguita')>'') {
						  Ext.Ajax.request({
							url: 'server/AjaxRequest.php',
							params: {task: 'read', 
							         sql: "SELECT COUNT(*) AS allegati FROM "
							        	 +MYSQL_SCHEMA+(isStorico?'_storico.':'')
							        	 +"allegatoazionespeciale WHERE IdAzioneSpeciale = " + rec.get('IdAzioneSpeciale')},
							method: 'POST',
							reader: new Ext.data.JsonReader({
								root: 'results',//name of the property that is container for an Array of row objects
								id: 'Allegati'//the property within each row object that provides an ID for the record (optional)
							}, 
							[{name: 'Allegati'}]),
							success: function(result, request){
								eval('var resp = (' + result.responseText + ').results[0]');
								//Controllo se è una azione speciale con allegati
								if(resp.Allegati>0){
								    DCS.showAzioneSpecialeDetail.create(rec.get('IdAzioneSpeciale'),idContratto, rec.get('UserId'),true,isStorico);	
								} else {
									DCS.showAzioneSpecialeDetail.create(rec.get('IdAzioneSpeciale'),idContratto, rec.get('UserId'),false,isStorico);
								}
							},
							failure: function(result, request){
									 Ext.MessageBox.alert('Errore', result.responseText);
							}
						  });	 	
						} else {
							Ext.Ajax.request({
							   url: 'server/visualizzaDettAzioneEseguita.php', 
							   params : {IdContratto: idContratto, htmlAzione: rec.get('HtmlAzioneEseguita'), 
								   valoreHtmlAzione: rec.get('ValoriAzioneEseguita'), formWidth: rec.get('FormWidth'),
								   formHeight: rec.get('FormHeight')},
							   method: 'POST',
							   success: function(result, request){
								   eval(result.responseText);
							   },
							   failure: function ( result, request) { 
								  Ext.MessageBox.alert('Errore', result.responseText); 
							   }
							});	
						}
				   }
     	         }
     	        ]
     	  }
        ],

        // customize view config
       viewConfig: {
			autoFill: true,
			forceFit: false
        },

        // paging bar on the bottom
        bbar: new Ext.PagingToolbar({
            pageSize: 20,
            store: dsStoriaRecupero,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare"
        })
    });

    // Aggiungi pulsante per export
	grid.getBottomToolbar().insert(12 ,
			{type: 'button', hidden:!CONTEXT.EXPORT, 
			 text: 'Esporta elenco', 
			 icon:'images/export.png',  
			 handler: function(){Ext.ux.Printer.exportXLS(grid);}
	        });
	grid.getBottomToolbar().insert(13,'-');
	grid.getBottomToolbar().doLayout();

    // trigger the data store load
    dsStoriaRecupero.load({params:{start:0, limit:20}});
	
	return grid;
};
