// Crea namespace DCS
Ext.namespace('DCS'); 

DCS.Allegato = function(idContratto, codContratto, isStorico) {
	
     // create the Data Store
	var addedQueryPiece = "and IFNULL(FlagRiservato,'N')!='Y'";
	if (CONTEXT.InternoEsterno == 'I')
		addedQueryPiece = "";
	var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
	var select = "SELECT a.*, date(a.lastupd) as Data, ta.TitoloTipoAllegato,CASE WHEN FlagRiservato='Y' THEN 'Riservato' ELSE '' END AS Riservato," 
			    +" '"+CONTEXT.IdUtente+"' as idUserCorrente,"
			    +"CONCAT(a.IdAllegato,'-',md5(IdContratto)) AS CodedKey" // chiave per creare link non riconducibile alla cartella vera
				+" FROM "+schema+".allegato a, tipoallegato ta"
				+" WHERE a.IdTipoallegato=ta.IdTipoAllegato"
				+" "+addedQueryPiece
				+" AND IdContratto="+idContratto+" ORDER BY Data";

	
	var dsAllegato = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			//where to retrieve data
			url: 'server/AjaxRequest.php',
			method: 'POST'
		}),   
		baseParams:{task: 'readAllegati', IdContratto: idContratto, sql: select}, // task cambiato da read a readAllegati il 17/10/2016 per inserimento accesso al nuovo documentale
		/*2. specify the reader*/
		reader:  new Ext.data.JsonReader(
				{
					root: 'results',//name of the property that is container for an Array of row objects
					id: 'IdAllegato'//the property within each row object that provides an ID for the record (optional)
				},
				[
					{name: 'TitoloTipoAllegato'},
					{name: 'IdUtente'},
					{name: 'TitoloAllegato'},
					{name: 'UrlAllegato'},
					{name: 'Riservato'},
					{name: 'LastUser'},
					{name: 'lastSuper'},
					{name: 'Data'},
					{name: 'CodedKey'}
				]
        ),
		sortInfo:{field: 'Data', direction: "ASC"},
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

 
     // pluggable renders
    var renderTopic = function (value, p, record){
    	var url = record.data.UrlAllegato;
    	if (!/^http/.test(url)) url = escape(url);
        return String.format(
                '<b><a href="{1}" target="_blank">{0}</a></b>', value,url);
    };
  	
 // visibilità flag riservato
	var RiservatoHidden=true;
	if (CONTEXT.InternoEsterno == 'I')
		RiservatoHidden=false;
	
	var selM = new Ext.grid.CheckboxSelectionModel({printable:false,groupable:false,singleSelect:false});
    var grid = new Ext.grid.GridPanel({
    	pagesize: 0,
    	id: 'gridAllegato',
        width:500,
        height:300,
        title:'',
        store: dsAllegato,
        trackMouseOver:false,
        disableSelection:true,
        loadMask: true,
        sm: selM,
		viewConfig: {
			autoFill: true,
			forceFit: false
		},

        // grid columns
        columns:[selM,
        {
            header: "Data",
            dataIndex: 'Data',
            width: 120,
            align: 'Left'
        },{
            header: "Utente",
            dataIndex: 'LastUser',
            width: 70,
            align: 'Left'
        },{
            header: "Tipo Documento",
            dataIndex: 'TitoloTipoAllegato',
            width: 150,
            align: 'Left'
        },{
 			id: 'topic',
			header: "Titolo",
            dataIndex: 'TitoloAllegato',
	        width: 350,
			renderer: renderTopic,
            align: 'left',
			sortable: true
        },{
 			header: "Riservato",
            dataIndex: 'Riservato',
            hidden: RiservatoHidden,
	        width: 80,
            align: 'left'
        },
		{xtype: 'actioncolumn',
            width: 70,
            header:'Azioni',
            sortable:false,  filterable:false,
            items: [{icon:"images/space.png"},{icon:"images/space.png"},
                    {tooltip: 'Cancella', hidden: isStorico,
						getClass: function(v,meta,rec) {
            				// ï¿½ possibile eliminare solo l'ultima nota e solo da colui che l'ha emessa 
			 				//if ((rec.get('rowNum')=='1') && (rec.get('idUserCorrente')==rec.get('IdUtente'))) {
				 			if (CONTEXT['IdUtente']==rec.get('IdUtente')) {
			 					return 'del-row';
			 				} else {
			 					return '';
			 				}
			 			},                    	 
            			handler: function(grid, rowIndex, colIndex) {
			 				
	                        //var rec = grid.store.getAt(rowIndex);
			 				var rec = dsAllegato.getAt(rowIndex);
                        	Ext.Ajax.request({
                        		url : 'server/edit_allegati.php' , 
								params: {task: 'delete',IdAllegato: rec['id'],UrlAllegato: rec.get('UrlAllegato'), CodContratto: codContratto, IdCompagnia: CONTEXT['idCompagnia'], TitoloTipoAllegato: rec.get('TitoloTipoAllegato')},
                        		method: 'POST',
                        		success: function ( result, request ) {
									eval("res = "+result.responseText);
									if (res.success)
										Ext.getCmp('gridAllegato').getStore().reload();
									else
	                        			Ext.MessageBox.alert('Operazione non eseguita', 'Impossibile cancellare l\'allegato'); 										
                        		},
                        		failure: function ( result, request) { 
                        			Ext.MessageBox.alert('Cancellazione non eseguita', result.responseText); 
                        		} 
                        	});
						}
                    }
            ]   // fine icone di azione su riga
        }// fine colonna action
     ],        

        // paging bar on the bottom
     /*
    	   bbar: new Ext.PagingToolbar({
            pageSize: 10,
            id: 'bbAll',
            store: dsAllegato,
            displayInfo: true,
            displayMsg: 'Righe {0} - {1} di {2}',
            emptyMsg: "Nessun elemento da mostrare",
            items:['-',{ref: '../addBtn',
					  id: 'bAll',
					  text: 'Nuovo Allegato',
					  hidden: true,
					  //handler: eseguiAzioneBase('37',[idContratto],'Allega nuovo documento'),
					  tooltip: 'Crea un nuovo allegato',
					  iconCls:'grid-add'
               }]
        })
         */
     bbar: new Ext.Toolbar({
            id: 'bbAll',
			cls: "x-panel-header",
			items:[{type:'button', 
					text:'Aggiorna', 
					tooltip:'Aggiorna', icon:'ext/resources/images/default/grid/refresh.gif', 
					handler: function(){
						dsAllegato.load();
					}, scope: this},
			       '->',{
						 text: 'Invia Selezionati per Email',
						 tooltip: 'Invia per Email gli allegati selezionati',
						 iconCls : 'invioMail',
						 handler : function(){
							 var arr = selM.getSelections();
							 if(arr.length == 0){
								 Ext.Msg.alert('', 'Devi selezionare almeno un allegato');
							 }else{
								 // Nota: idModello=20 ï¿½ il modello per inviare documenti allegati
								 Ext.Ajax.request({url: 'server/generaTestoEmail.php',method: 'POST',
			                  			params :{IdModello:20, IdContratto:idContratto, defaultSubst:""},
			                  			success: function (result, request) {
			                  				var resp = JSON.parse(result.responseText);
			                  				/*prepara il corpo del messaggio per generare la lista dei link ai documenti*/
			                  				var lista = [];
			                  				for(var i = 0; i<arr.length; i++){
			                  					lista.push('<br/>- <a href="' + CONTEXT.LinkUrl + 'download.php?id=' 
			                  							+ arr[i].get('CodedKey')
			                  							+ '">'
			                  							+ arr[i].get('TitoloAllegato')
			                  							+ '</a>');
			                  				}
			                  				lista.push('</ol>');
			                  				eseguiAzioneBase('EMA',[idContratto],'Invia documenti per email','',
													 {
												 		subject: resp.subject,
												 		body: resp.body.replace("_listalink_", lista.join(''))
													 }); 
			               					},
			               				failure: function (result,request) {
											Ext.Msg.alert ("Invio email fallito",result.responseText);
			               				}
			       					});
								
							 }
						 }
					  },
				      {ref: '../addBtn',
				  text: 'Nuovo Allegato',
					  hidden: !CONTEXT.AZIONE_ALL || isStorico, 
				  tooltip: 'Crea un nuovo allegato',
					  iconCls:'grid-add',
					  handler: function() {
							eseguiAzioneBase('ALL',[idContratto],'Allega nuovo documento');
						}
				      }
				  ]
     	   })
    });

     // trigger the data store load
    DCS.showMask("Lettura lista documenti allegati...");
    dsAllegato.load();
    DCS.hideMask();
    
	return grid;
};
