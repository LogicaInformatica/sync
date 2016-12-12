<?php 
// formAzioneInvioEmail
// Genera la struttura del form di tipo "azione invio email"
// Contenuto: campo/listbox email destinatario, listbox modello di email, campo oggetto email, campo note (testo email) e pulsanti Conferma / Annulla
//if(!preg_match( '/^[\w\.\-]+@\w+[\w\.\-]*?\.\w{1,4}$/', $indirizzoEmail))
//{
//	$indirizzoEmail = "sbagliato";
//}
require_once("userFunc.php");
require_once("workflowFunc.php");
/*preleva soggetto e body eventualmente passati dal chiamante*/
$subject = $arrDatiExtra->subject;
$body = $arrDatiExtra->body;
//$row è definito nella GeneraFormAzione.php solo per l'invio x un singolo contratto
$IdCliente=$row["IdCliente"];

//$indirizzoEmail = trim(getScalar("SELECT Email FROM recapito r,contratto c WHERE r.IdCliente=c.IdCliente AND c.IdContratto=$ids AND idTipoRecapito=1"));
$indirizzoEmail = trim(getScalar("SELECT Email FROM v_email WHERE IdCliente=$IdCliente"));
// funzione chiamata alla select sulla combobox
$listener  = <<<EOT
	function(combo, record, index) {
		Ext.Ajax.request({url: 'server/generaTestoEmail.php',method: 'POST',
                  			params :{IdModello:combo.getValue(), IdContratto:$ids, defaultSubst:""},
                  			success: function (result, request) {
                  				var resp = JSON.parse(result.responseText);
                  				Ext.getCmp('oggetto').setValue(resp.subject);
                  				Ext.getCmp('nota').setValue(resp.body);
               				},
                  			failure: function (result,request) {
								Ext.Msg.alert ("Invio email fallito",result.responseText);
               				}
       					});
	}
EOT;
$add = (userCanDo("READ_RISERVATO"))?"":" AND IFNULL(FlagRiservato,'N')='N'";
$comboModel = generaCombo("Scegli modello","IdModello","TitoloModello",
    "FROM modello WHERE TipoModello='E' $add AND CURDATE() BETWEEN DataIni AND DataFin ORDER BY TitoloModello",$listener,"true",false, true);
$dataDefault = getDefaultDate($azione["IdAzione"]); // data di default da Automatismo

$contratti=json_decode($idcontratti);
$contrattiStr = join(",",$contratti );
$chkHidden = false;
if(rowExistsInTable("nota","IdContratto in (".$contrattiStr.") and TipoNota='S' and DATE_FORMAT(DataScadenza,'%Y-%m-%d')>= curdate()")==false)
	$chkHidden = true;

if ($context["InternoEsterno"]=="E") // se utente di agenzia, non può mettere scadenze oltre il periodo di affido
{
	$dataLimite = getScalar("SELECT MIN(DataFineAffido) FROM contratto WHERE IdContratto in ($contrattiStr)");
	if ($dataLimite==NULL)
		$dataLimite = '9999-12-31';
	else
		$dataLimite = ISODate($dataLimite);
}	
else
	$dataLimite = '9999-12-31';
	?>
var sql = "";

var readOnly = (CONTEXT.InternoEsterno=='E');

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	fileUpload: true,
	frame: true, title: "<?php echo $titolo?>",
    width: 640,height: 450,labelWidth:100,
        defaultType: 'textfield',
        items: [     
        <?php echo $comboModel?>,
		{
         	fieldLabel: 'Oggetto',
 			xtype:'textarea',
         	id: 'oggetto',
        	name: 'oggetto',
        	value : '<?php echo addslashes($subject)?>',
            readOnly: readOnly,
            anchor: '97%',
            height: 36
        },{
         	fieldLabel: 'Indirizzo e-mail',
            anchor: '97%',
          	height: 20,
         	value: "<?php echo $indirizzoEmail?>",
         	allowBlank: false,
        	name: 'email',
        	vtype: 'email_list'
        },{
			xtype:'htmleditor',
            fieldLabel: 'Nota/testo',
            id: 'nota',
            name: 'nota',
            enableAlignments : !readOnly,
			enableColors : !readOnly,
			enableFont : !readOnly,
			enableFontSize : !readOnly,
			enableFormat : !readOnly,
			enableLinks : !readOnly,
			enableLists : !readOnly,
			enableSourceEdit : !readOnly,
            readOnly: readOnly,
            value : '<?php echo addslashes($body)?>',
            anchor: '97%',
            height: 180
        },
        {
			xtype: 'compositefield',
			items:[{
		        	xtype:'fileuploadfield',
		        	fieldLabel: 'Allegato',
					id: 'docPath',
					name: 'docPath',
					buttonText: 'Carica file',
					buttonOnly: true,
				    listeners: {
			            'fileselected': function(){
				            	var valueTitolo=Ext.getCmp('docPath').getValue();
			                	// Ri-trasforma i caratteri URLEncoded in caratteri normali
			                	valueTitolo=unescape(String(valueTitolo).replace("/\+/g", " ")); 
				    			// Toglie il path
			                	if (valueTitolo.lastIndexOf("\\")>0) 
				    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("\\"));
			                	if (valueTitolo.lastIndexOf("/")>0) 
				    				valueTitolo=valueTitolo.substring(1+valueTitolo.lastIndexOf("/"));
			                		Ext.getCmp('newFile').setValue(valueTitolo);
					      }
				   		}
        	},{
					xtype:'displayfield',
					width: 300,
					height: 20,
					id: 'newFile'
			}]
        },{	
        	xtype: 'datefield',
			format: 'd/m/Y',
			width: 100,
			fieldLabel: 'Data verifica',
			value: '<?php echo $dataDefault?>',
			name: 'data',
			minValue: new Date(),
					maxValue:'<?php echo italianDate($dataLimite) ?>',
			id: 'data'
       		},{
		    xtype: 'checkbox',
		    height: 30,
		    boxLabel: '<span style="color:blue;"><b>Elimina scadenze gi&agrave; in calendario</b></span>',
			name: 'chkHidden',
			id: 'chkHidden',
			hidden: '<?php echo $chkHidden?>',
			disabled:'<?php echo $chkHidden?>',
			checked: false,

			listeners:{
	 			check: function(r,v)
	 			{
 	 			  if(v==true)
	 			  {
					Ext.Msg.show({
					   title:'Attenzione...',
					   msg: '<span style="color:red;"><b align="justify">Selezionando questa voce saranno sostituite tutte le scadenze gi&agrave; inserite per questa pratica.</b></span>',
					   buttons: Ext.Msg.OK,
					   icon: Ext.MessageBox.WARNING
					});	
	 		    }
	 		}    
		}
			}],
    buttons: [{
			text: 'Conferma',
			handler: function() {
				if (formPanel.getForm().isValid()){
					DCS.showMask();
					// qualche campo modificato
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


