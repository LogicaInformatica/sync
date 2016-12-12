<?php 
// formAzioneInoltro
// Genera la struttura del form di tipo "azione inoltra per approvazione"
// Contenuto: listbox approvatori, allega documento, campo note e pulsanti Approva / Annulla

$comboModel = generaCombo("Tipo Documento","IdTipoAllegato","TitoloTipoAllegato",
			"FROM tipoallegato WHERE NOW() BETWEEN DataIni AND DataFin ORDER BY TitoloTipoAllegato");
?>

// visibilità flag riservato
	var RiservatoHidden=true;
	var ValueRiservato=false;
	if (CONTEXT.InternoEsterno == 'I'){
		RiservatoHidden=false;
		ValueRiservato=false;
	}

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	labelWidth: 105, 
	frame: true, 
	fileUpload: true, 
	title: "<?php echo $titolo?>",
    width: 430,height: 320,
         defaults: {
            width: 300
        },
        defaultType: 'textfield',
        items: [   
        <?php echo $comboModel?>  
        ,{
            xtype: 'fileuploadfield',
            fieldLabel: 'Allega Documento',
            name: 'docPath',
            id: 'docPath',
            allowBlank: false,
            buttonText: 'Cerca',
            listeners: {
	            'fileselected': function(){
	                var valueTitolo=Ext.getCmp('titolo').getValue();
//	                if (valueTitolo=="")
//	                {
	                	valueTitolo=Ext.getCmp('docPath').getValue();
	                	// Ri-trasforma i caratteri URLEncoded in caratteri normali
	                	valueTitolo=unescape(String(valueTitolo).replace("/\+/g", " ")); 
	                	
	                	// Toglie l'estensione del nome file
	                	if (valueTitolo.lastIndexOf(".")>0)
		    				valueTitolo=valueTitolo.substring(0,(valueTitolo.lastIndexOf(".")));
		    			// Toglie il path
	                	if (valueTitolo.lastIndexOf("\\")>0) 
		    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("\\"));
	                	if (valueTitolo.lastIndexOf("/")>0) 
		    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("/"));
	                	Ext.getCmp('titolo').setValue(valueTitolo);
//	                }
	            }
   	        }
        },{
           	fieldLabel: 'Titolo Documento',
           	allowBlank: false,
           	id: 'titolo',
           	name: 'titolo'
        },{
			xtype: 'textarea',
           	fieldLabel: 'Nota',
           	height: 100,
           	name: 'nota'
        },{
			xtype: 'checkbox',
			boxLabel: '<span style="color:red;"><b>Visibile solo agli interni</b></span>',
			name: 'FlagRiservato',
			hidden: RiservatoHidden,
			checked: ValueRiservato
		}],
    	buttons: [{
			text: 'Allega',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					formPanel.getForm().submit({
						url: 'server/edit_azione.php', method: 'POST',
						params: {idstatoazione: <?php echo $idstatoazione?>, idcontratti: "<?php echo addslashes($idcontratti)?>"},
						success: function (frm,action) {saveSuccess(win,frm,action);},
						failure: saveFailure
					});
				}
			}
		}, 
		{text: 'Annulla',handler: function () {quitForm(formPanel,win);} 

		}]  // fine array buttons
});