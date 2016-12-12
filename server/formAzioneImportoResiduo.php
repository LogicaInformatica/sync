<?php
require_once("workflowFunc.php"); 
// formAzioneImportoResiduo
// Form per la registrazione della richiesta di saldo e stralcio
// Contenuto: 

$impDebito = getScalar("SELECT ImpInsoluto FROM contratto where IdContratto = ".$ids);
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo


?>
// Crea namespace DCS
Ext.namespace('DCS');
var id='';
DCS.RegImportoResiduo = function(){
	return {
		registra: function(idPratica,impDebito){
			var myMask = new Ext.LoadMask(Ext.getBody(), {
				msg: "Caricamento in corso"
			});
			myMask.show();
			
		var gridFormSaldoStralcio= new Ext.FormPanel(
			{
				id: 'form_saldo_stralcio',
				frame: true,
				title : "Registrazione saldo e stralcio",
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
										          xtype: 'datefield',
												  format: 'd/m/Y',
												  fieldLabel: 'Data verifica',
												  allowBlank: false,
												  anchor:"95%",
												  style: 'text-align:right',
												  blankText : "Indicare la data di verifica",
												  name: 'DataVerifica',
												  id: 'DataVerifica',
												  value: '<?php echo $dataDefault?>',
												  minValue: new Date()
											 }
										     ,
										     {
										          fieldLabel:'Imp. disp. a pagare',	
										          name:'ImportoDispostoAPagare',
										          xtype:'numberfield',
										          allowBlank : false,
										          anchor:"95%",
										          style: 'text-align:right',
										          blankText : "Indicare l'importo disposto a pagare",
										          allowNegative: false,
										          decimalPrecision: 2,
												  allowDecimals : true,
										          minValue :0.00, 
										          id:'ImportoDispostoAPagare'
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
										     },
										     {
										          xtype: 'datefield',
												  format: 'd/m/Y',
												  fieldLabel: 'Data pagamamento',
												  allowBlank: false,
												  anchor:"95%",
												  style: 'text-align:right',
												  blankText : "Indicare la data di pagamento",
												  name: 'DataInizioPagamento',
												  id: 'DataInizioPagamento',
												  value: '<?php echo $dataDefault?>',
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
						            id: 'NoteSaldoStralcio',
						            name: 'NoteSaldoStralcio',
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
												if (Ext.getCmp('ImportoDispostoAPagare').getValue() > Ext.getCmp('ImportoDebito').getValue())
												{
													Ext.Msg.alert("Attenzione","L'importo che il cliente vuole pagare non pu&ograve; essere maggiore dell'importo dovuto.");
													Ext.getCmp('ImportoDispostoAPagare').markInvalid();
												}
												else
												{
													if (gridFormSaldoStralcio.getForm().isValid()) 
													{
														gridFormSaldoStralcio.getForm().submit(
														{
															url: 'server/edit_azione.php', method: 'POST',
															params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>", InvioMultiplo: 'TRUE'},
															success: function (frm,action) {saveSuccess(win,frm,action);},
															failure: saveFailure
														});
														
													
													}
												
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
				items: [gridFormSaldoStralcio]
		});
			
			win.show();
			myMask.hide();
		}
	}
}();


id = '<?php echo $idGrid?>';
DCS.RegImportoResiduo.registra(<?php echo $ids?>,<?php echo $impDebito?>);
