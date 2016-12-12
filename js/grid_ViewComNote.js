/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');

DCS.FormVistaNote = function(){
	
	return {
		showDetailVistaNote: function(idPratica, numPratica, tipoNota, idNota,store,isStorico){
			Ext.Ajax.request({
				url: 'server/edit_ramiNote.php', method:'POST',
				params :{task:'readTree',IdPratica:idPratica, schema:MYSQL_SCHEMA+(isStorico?'_storico':'')},
				callback : 	function(r,options,success) 
							{
				 				var idRamoSelezionato = 0;
				 		
								var myMask = new Ext.LoadMask(Ext.getBody(), {
									msg: "Lettura note..."
								});
						
								//myMask.show();
						
								var arrayStr = '';
								var children ='' ;
				
							 	arrayStr =  success.responseText;
							 	children = Ext.util.JSON.decode(arrayStr); 
						
							 	var win = new Ext.Window(
								{
									width: 700,
									height: 520,
									minWidth: 700,
									minHeight: 520,
									//autoHeight: true,
									layout: 'fit',
									plain: true,
									bodyStyle: 'padding:5px;',
									modal: true,
									title: 'Note',
									tools: [helpTool("ComNote")],
									constrain: true,
									items: [{   
									//			collapsible: true,
											    title: 'Comunicazioni e note per la pratica ' + numPratica,
											    xtype: 'treepanel',
											    id:'winRami',
											    // 2 parametri usati solo dall'export
												url: 'server/edit_ramiNote.php', 
												params :{task:'export',IdPratica:idPratica,schema:MYSQL_SCHEMA+(isStorico?'_storico':'')},
												
											    width: 680,
											    height:470,
											    autoScroll: true,
											    split: true,
											    loader: new Ext.tree.TreeLoader(),
											    root: new Ext.tree.AsyncTreeNode({
											        expanded: true,
											        children: children
											    }),
											    rootVisible: false,
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
											                    						sql: "SELECT * FROM nota where IdNota="+idNodo
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
											                    				if (resp != undefined)
											                    				{
											                    					if(resp.NomeUtente!='')
											                    					{
											                    						
											                    							if(IsResp=='000'){
																                    			DCS.FormNotaMex.showDetailNoteMex(idPratica,numPratica,resp.TipoNota,0,0,'',Node.parentNode.id,win,store);
																                    		}else{
																                    			if(resp.IdUtente==CONTEXT.IdUtente)
																                    			{
																                    				DCS.FormNotaMex.showDetailNoteMex(idPratica,numPratica,resp.TipoNota,idNodo,0,'',Node.parentNode.id,win,store);
																                    			}else{
																                    				Ext.MessageBox.alert('Accesso negato','Non puoi modificare questo messaggio, per vedere la pratica premi il tasto "Vedi pratica", in basso a sinistra');
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
									}],
									buttonAlign: 'left',
									buttons: [{
												text: 'Vedi pratica',
												handler: function() {
													win.close();
													showPraticaDetail(idPratica,numPratica,null,'','',null,-1);
												},
												scope: this
											  }, '->',
									          {type: 'button', 
												  hidden:!CONTEXT.EXPORT, 
												  id:'btnExpNote', 
												  text: 'Esporta', 
												  icon:'images/export.png',  
												  tooltip:'Esporta la lista note in un file Excel',
												  handler: function(){Ext.ux.Printer.exportXLS(Ext.getCmp('winRami'));}, 
												  scope:this}
									          ,{  text: 'Nuova nota',
									        	  id: 'btnNN',
												  icon:'images/icon_postit.gif',  
									        	  hidden: (CONTEXT.BOTT_NEW_NOTE != true || isStorico),
									        	  handler: function() {
									        	  	DCS.FormNotaMex.showDetailNoteMex(idPratica,numPratica,'N',0,0,'',null,win,store);
									          		},
									          		scope: this
									          },{
													text: 'Nuovo messaggio',
												    icon:'images/comment.png',  
													id: 'btnNM',
													hidden: (CONTEXT.BOTT_NEW_COMM != true || isStorico),
													handler: function() {
														DCS.FormNotaMex.showDetailNoteMex(idPratica,numPratica,'C',0,0,'',null,win,store);
													},
													scope: this
												}],
									listeners: 
									{
											close:	function(p){
														p.hide();
													}
									}
								});	        
							 	win.show();
								myMask.hide();
							},
				failure:	function (obj)
							{
								Ext.MessageBox.alert('Errore', 'Errore durante la lettura delle note.');
							},
				scope: this
			}); // fine request
		} 
	};
}();

