<?php 
// formAzioneNoteMex
// Genera la struttura del form di tipo "NoteMex" per inserire note e messaggi su una pratica
// Contenuto: 

$impDebito = getScalar("SELECT ImpInsoluto FROM contratto where IdContratto = ".$ids);


?>
/*!
 * Ext JS Library 3.3.1
 * Copyright(c) 2006-2010 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

// Crea namespace DCS
Ext.namespace('DCS');
var id='';
DCS.RegPianoRientro = function(){
	return {
		registra: function(idPratica,impDebito){
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Caricamento in corso"
			});
			myMask.show();
			
		var gridFormRientro= new Ext.FormPanel(
			{
				id: 'form_piano_rientro',
				frame: true,
				title : "Registrazione piano di rientro",
				trackResetOnLoad: true,
				border: false,
				items:[
							{   
				        		xtype:'container', 
				        		layout:'column',
				        		items:[
				        			   {
									     xtype:'panel', 
									     layout:'form', 
									     columnWidth:.5, 
									     labelWidth:120, 
										 items: 
										 [
										 	 {
										          fieldLabel:'Importo debito',	
										          name:'ImportoDebito',
										          xtype:'numberfield', 	
										          cls : 'red-title',
										          id:'ImportoDebito',
										          anchor:"95%",
										          format: '0,00',
										          style: 'text-align:right',
										          value : impDebito,
										          readOnly: true
										     }
										     ,
										     {
										          fieldLabel:'Importo',	
										          name:'ImportoPag',
										          xtype:'numberfield',
										          allowBlank : false,
										          anchor:"95%",
										          style: 'text-align:right',
										          blankText : "Indicare l'importo delle rate",
										          allowNegative: false,
										          decimalPrecision: 2,
												  allowDecimals : true,
										          minValue :0.00, 
										          id:'ImportoPag'
										     }
										   
										   
										 ]
									   }
									   ,
									   {
									     xtype:'panel', 
									     layout:'form', 
									     columnWidth:.5, 
									     labelWidth:120, 
										 items: 
										 [
									        
											  {
										          fieldLabel:'Numero rate',	
										          name:'NumeroRate',
										          xtype:'numberfield', 
										          allowNegative: false,	
										          style: 'text-align:right',
										          anchor:"95%",
										          blankText : "Indicare il numero delle rate",
										          decimalPrecision: 0,
										          allowBlank : false,
										          id:'NumeroRate'
										     }
										     ,											        
										     {
											          xtype: 'datefield',
													  format: 'd/m/Y',
													  fieldLabel: 'Data inizio pagam.',
													  allowBlank: false,
													  anchor:"95%",
													  style: 'text-align:right',
													  blankText : "Indicare la data di inizio pagamento",
													  //vtype: 'daterange',
													  name: 'DataInizioPagamento',
													  id: 'DataInizioPagamento',
													  minValue: new Date()
											 }
										 ]
									   }
									  ]
							}
						    ,
						    {
						     xtype:'panel', 
						     layout:'form', 
						     labelWidth:120, 
							 items: 
							 [
							 	{
									xtype:'htmleditor',
						            fieldLabel: 'Note',
						            anchor:"100%",
						            enableLinks: false,
						            enableSourceEdit :false,
						         	enableFormat : false,
						            id: 'NotePianoRientro',
						            name: 'NotePianoRientro',
						            allowBlank: true
				    	        }
							 ]
						    }
				      ]
				      ,
				       buttons: [
						          {
									text: 'Salva',
									id: 'btnSalvaDett',
									handler: function() 
											{
												if (gridFormRientro.getForm().isValid()) 
												{
													gridFormRientro.getForm().submit(
													{
														url: 'server/edit_azione.php', method: 'POST',
														params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>", InvioMultiplo: 'TRUE'},
														success: function (frm,action) {saveSuccess(win,frm,action);},
														failure: saveFailure
													});
													
												
												}
											}
									
								  }
								  , 
								  {
									text: 'Annulla',
									id: 'btnAnnullaDett',
									handler: function() 
											{
												win.close();
											}
								  }
								  
						   		]	
			});	

		var win = new Ext.Window(
		{
				cls: 'left-right-buttons',
				modal: true,
				width: 600,
				height: 300,
				minWidth: 600,
				minHeight: 300,
				layout: 'fit',
				plain: true,
				constrain: true,
				title: titolo,
				items: [gridFormRientro]
		});
			
			win.show();
			myMask.hide();
		}
	}
}();


id = '<?php echo $idGrid?>';
DCS.RegPianoRientro.registra(<?php echo $ids?>,<?php echo $impDebito?>);
