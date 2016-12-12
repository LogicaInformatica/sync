// USATO PER VISUALIZZARE LE NOTE NEL DETTAGLIO DI UNA PRATICA

//Crea namespace DCS
Ext.namespace('DCS');

DCS.GridRami = function(idContratto,numPratica,listStore,isStorico) {
	
	var schema = MYSQL_SCHEMA+(isStorico?'_storico':'');
	var children =Ext.util.JSON.decode('[{"id":"1","text":"","iconCls":"empty_ico","cls":"file","leaf":true,"children":null}]');
	/*console.log("lung first  "+children.length);
	for (var i=0; i<children.length; i++){
 		console.log("childs first ["+i+"] "+children[i].id);
 	}*/
		
	var tree = new Ext.tree.TreePanel({
		id:'treeNotePratica',
	    useArrows: true,
	    autoScroll: true,
	    animate: true,
	    enableDD: true,
//	    collapsible: true,
	    containerScroll: true,
	    border: false,
	    title: 'Comunicazioni e note per la pratica ' + numPratica,
	    loader: new Ext.tree.TreeLoader(),
	    // 2 parametri usati solo dall'export
		url: 'server/edit_ramiNote.php', 
		params :{task:'export',IdPratica:idContratto,schema:schema},

		root:new Ext.tree.AsyncTreeNode({
            expanded: true,
            children: children
        }),
		autoScroll: true,
	    split: true,
	    rootVisible: false,
	    buttonAlign: 'left',
		buttons: [		
		   '->',
		   {type: 'button', 
			  hidden:!CONTEXT.EXPORT, 
			  id:'btnExpNote', 
			  text: 'Esporta', 
			  icon:'images/export.png',  
			  tooltip:'Esporta la lista note in un file Excel',
			  handler: function(){Ext.ux.Printer.exportXLS(Ext.getCmp('treeNotePratica'));}, 
			  scope:this
		},{
			text: 'Nuova nota',
			id: 'btnNNP',
			icon:'images/icon_postit.gif',  
			hidden: (CONTEXT.BOTT_NEW_NOTE != true || isStorico),
			handler: function() {
	            DCS.FormNotaMex.showDetailNoteMex(idContratto,numPratica,'N',0,0,'',null,null,listStore);
			},
			scope: this
		},{
			text: 'Nuovo messaggio',
			id: 'btnNMP',
			icon:'images/comment.png', 
			hidden: (CONTEXT.BOTT_NEW_COMM != true || isStorico),
			handler: function() {
				DCS.FormNotaMex.showDetailNoteMex(idContratto,numPratica,'C',0,0,'',null,null,listStore);
			},
			scope: this
		}],
	    listeners:
	    {
		       render: 	function() {
	                        this.getRootNode().expand();
	                    },
	           dblclick:	function(Node, e){
	                    	var idNodo = Node.attributes.id;
	                    	if(!isNaN(idNodo))
	                    	{
	                    		var IsResp = idNodo.substring(0,3);
	                    		Ext.Ajax.request({
	                    			url: 'server/AjaxRequest.php', 
	                        		params : {	task: 'read',
	                    						sql: "SELECT * FROM "+schema+".nota where IdNota="+idNodo
	                    					},
	                    			method: 'POST',
	                    			reader:  new Ext.data.JsonReader(
	                        					{
	                        						root: 'results',//name of the property that is container for an Array of row objects
	                        						id: 'IdNota'//the property within each row object that provides an ID for the record (optional)
	                        					},
	                        					[{name: 'IdNota'},
	                        					{name: 'TipoNota'},
	                        					{name: 'IdUtente'},
	                        					{name: 'IdNotaPrecedente'}]
	                        				),
	                    			success: function ( result, request ) {
	                    				eval('var resp = ('+result.responseText+').results[0]');
	                    				//console.log("idcontrsucc "+idContratto);
	                    				if (resp != undefined)
	                    				{
	                    					if(resp.NomeUtente!='')
	                    					{
	                    						if(IsResp=='000'){
					                    			DCS.FormNotaMex.showDetailNoteMex(idContratto,numPratica,resp.TipoNota,0,0,'',Node.parentNode.id,null,listStore,isStorico);
					                    		}else{
					                    			if(resp.IdUtente==CONTEXT.IdUtente)
					                    			{
					                    				DCS.FormNotaMex.showDetailNoteMex(idContratto,numPratica,resp.TipoNota,idNodo,0,'',Node.parentNode.id,null,listStore,isStorico);
					                    			}else{
				                    					Ext.MessageBox.alert('Accesso negato','Non hai l\'autorit� o non sei l\'autore di questo messaggio.');
					                    			}
					                    		}
	                    					}
	                    				}
	                    			},
	                        		failure: function ( result, request) { 
	                        			Ext.MessageBox.alert('Errore', result.responseText); 
	                        		},
	                        		autoLoad: true
	                        	});
	                    	}	
    	
	                   }
		}
	});
		
	Ext.Ajax.request({
		url: 'server/edit_ramiNote.php', method:'POST',
		params :{task:'readTree',IdPratica:idContratto,schema:schema},
		callback : 	function(r,options,success) 
					{
		 				var idRamoSelezionato = 0;
		 		
						var myMask = new Ext.LoadMask(Ext.getBody(), {
							msg: "Lettura note..."
						});
				
						//myMask.show();
				
						var arrayStr = '';
						
		
					 	arrayStr =  success.responseText;
					 	child = Ext.util.JSON.decode(arrayStr); 
					 	
					 	/*console.log("lung "+child.length);
					 	for (var i=0; i<child.length; i++){
					 		console.log("childs["+i+"] "+child[i].id);
					 	}*/
					 	var nroot=new Ext.tree.AsyncTreeNode({
				            expanded: true,
				            children: child
				        });
					 	
					 	// Se si è chiusa la finestra molto rapidamente, questa callback può entrare quando il container non c'è più: evita che resti sospeso per l'errore
					 	try {
						 	tree.setRootNode(nroot);
					 	} catch(e) {
					 	}
					 	myMask.hide();
					},
		failure:	function (obj)
					{
						Ext.MessageBox.alert('Errore', 'Errore durante la lettura delle note.');
					},
		scope: this
	});
	
	
	
	return tree;
};