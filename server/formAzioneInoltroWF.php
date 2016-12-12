<?php 
// formAzioneInoltro: inoltra un a richiesta ad un approvatore
$row_pratiche = getRow("SELECT Stato, Insoluti, Importo FROM v_pratiche WHERE IdContratto=".$idsArray[0]);

// Determina lo stato di workflow successivo a cui porta il passo di workflow, in modo da individuare
// le persone / profili destinatari del presente inoltro.
$IdStatoSuccessivo = getScalar("SELECT IdStatoRecuperoSuccessivo FROM statoazione WHERE IdStatoAzione=$idstatoazione");
?>

var dsUtente = new Ext.data.Store({
	proxy: new Ext.data.HttpProxy({
		//where to retrieve data
		url: 'server/AjaxRequest.php',
		method: 'POST'
	}),   
	/* cerca le persone che possono fare il passo successivo del workflow */
	baseParams:{task: 'getApprovers', from:<?php echo $IdStatoSuccessivo?>, idcontratti: "<?php echo addslashes($idcontratti) ?>" },
	/*2. specify the reader*/
	reader:  new Ext.data.JsonReader(
			{
				root: 'results',//name of the property that is container for an Array of row objects
				id: 'IdUtente'//the property within each row object that provides an ID for the record (optional)
			},
			[
				{name: 'IdUtente'},
				{name: 'NomeUtente'}
			]
            ),
		sortInfo:{field: 'NomeUtente', direction: "ASC"}
	}
); 

var contenitoreChk = new Ext.Container({
	fieldLabel: 'Approvatori',
	layout: 'fit',
	height:150,
	autoScroll:true,
	items:[]
})     
//CheckGroup e array di configurazione
var checkboxconfigs = []; //array of about to be checkboxes.   
var CheckProfGroup = new Ext.form.CheckboxGroup({
    id:'CPGroup',
    xtype: 'checkboxgroup',
    fieldLabel: 'Approvatori',
    itemCls: 'x-check-group-alt',
    columns: 1,
    height:60,
	autoScroll:true,
    items: [checkboxconfigs],
    listeners:{
		change:function(CheckProfGroup,arr){
			//segna le aggiunte di check
			flag = false;
			arr = CheckProfGroup.getValue();
			for(k=0;k < arr.length;k++)
            {
				for(j=0;j < checkboxconfigs.length;j++)
            	{
					if(arr[k].id==checkboxconfigs[j].id)
					{
						checkboxconfigs[j].checked = arr[k].checked;
						break;
					}
            	}
            }
			//segna le detrazioni di check
			for(j=0;j < checkboxconfigs.length;j++)
            {
				flag = false;
				if(checkboxconfigs[j].checked){
					for(k=0;k < arr.length;k++)
	            	{
						if(arr[k].id==checkboxconfigs[j].id)
						{
							flag=true;
							break;
						}
					}
					if(!flag){checkboxconfigs[j].checked = false;}
				}
            }
		}
	}
});       

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	labelWidth: 105, frame: true, title: "<?php echo $titolo?>",
    width: 430,height: 360,
    defaults: {width: 300},
	defaultType: 'displayfield',
        items: [{
			xtype: 'textarea',
           	fieldLabel: 'Nota',
           	height: 100,
           	name: 'nota'
        }
       ],
    buttons: [{
			text: 'Inoltra',
			handler: function() {
					arr = CheckProfGroup.getValue();
					if(arr.length > 0)
					{
						var vectAtt = [];
						for(j=0;j < checkboxconfigs.length;j++)
		            	{
			            	if(checkboxconfigs[j].checked == true)
			            	{
			            		vectAtt.push(checkboxconfigs[j].id);
			            	}
		            	}
						// qualche campo modificato
						DCS.showMask();
						formPanel.getForm().submit({
							url: 'server/edit_azione.php', method: 'POST',
							params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>", idAttuatori: Ext.encode(vectAtt)},
							success: function (frm,action) {saveSuccess(win,frm,action);},
							failure: saveFailure
						});
					}else{
						Ext.MessageBox.alert('Errore', "Selezionare almeno un destinatario (approvatore)");
					}
			}
		},
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});	

dsUtente.load({
	callback: function(r,options,success){
		//preparazione approvatori  
		range = dsUtente.getRange();
		for (i=0; i < range.length; i++)
		{
			var rec = range[i];
		    checkboxconfigs.push({ 
		        id:rec.data.IdUtente,
		        boxLabel:rec.data.NomeUtente,
		        checked: false,
		        disabled: (rec.data.IdUtente==0)
		      });
		}
		contenitoreChk.add(CheckProfGroup);
		formPanel.add(contenitoreChk);
		formPanel.doLayout();
	},
	scope: this
});
