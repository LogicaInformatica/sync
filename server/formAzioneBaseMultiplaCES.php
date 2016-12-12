<?php 

   require_once("workflowFunc.php");
   $dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo
   
   if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
	{
		$dataLimite = getScalar("SELECT MIN(DataFineAffido) FROM v_pratiche WHERE IdContratto in (".join(",",$idContratti).")");
		if ($dataLimite==NULL)
			$dataLimite = '9999-12-31';
		else
			$dataLimite = ISODate($dataLimite);
	}	
	else
		$dataLimite = '9999-12-31';
	
?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	id: 'frmPan',
	labelWidth: 130, 
	frame: true, 
	autoScroll: true,
	title: "<?php echo $titolo?>",
    width: 530,
    autoHeight: true,
    //height: <?php echo $hw?>,
    /*defaults: {
            width: 400, 
			height: 100
        },*/
        defaultType: 'textfield',
        items: [{xtype:'container', id:'frmCont',
        		items:[
        		{
        		 xtype:'container', layout:'column',
		         items:[{  
		            xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield', columnWidth: .20,
					defaults: {anchor:'98%'},
					items: [{
			        	xtype: 'displayfield',
					    fieldLabel: 'N. Selezionati',
		                value: "<b><?php echo count($idContratti)?></b>",
		                //width: 100,
		                style: 'text-align:left',
		                name: 'numContratti'
					}]
				   },
				   {   
				    xtype:'panel', layout:'form', labelWidth:120, defaultType:'textfield', columnWidth: .40,
					defaults: {anchor:'98%'},
					items: [{
			        	xtype: 'displayfield',
					    fieldLabel: 'Tot. Debiti residui',
		                value: "<b><?php echo $totCapitale?></b>",
		                //width: 100,
		                style: 'text-align:left',
		                name: 'totDebResiduo'
					}]
				   }
				 ] 		
				},
				{
		        	xtype:'panel', layout:'form', labelWidth:85, defaultType:'textarea',
					defaults: {readOnly:false, anchor:'99%'},
					items: [{
						xtype:'textarea',
						height: 60,
			            fieldLabel: 'Nota',
			            id: 'nota'
			            //name: 'nota'
		            }]
		        },
		        { 
		         xtype:'container', layout:'column',
		         items:[
				   {   
				    xtype:'panel', layout:'form', labelWidth:85, defaultType:'textfield', columnWidth: .47,
					defaults: {anchor:'80%'},
					items: [{
			        	xtype: 'datefield',
						format: 'd/m/Y',
						//width: 100,
						fieldLabel: 'Data verifica',
						value: '<?php echo $dataDefault?>',
						minValue: new Date(),
						maxValue:'<?php echo italianDate($dataLimite) ?>',
						name: 'dataVerifica',
						id: 'dataVerifica'
					}]
				   }
				 ] 		
				}
		        ]
		 }]
});