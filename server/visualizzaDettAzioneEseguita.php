<?php 
    // visualizzaDettaglioAzioneEseguita
    // Visualizza l'html (fotografia) dell'azione eseguita in precedenza
    require_once("common.php");
    
	$idContratto = $_REQUEST['IdContratto'];
	$titoloForm = getRow("SELECT NomeCliente, CodContratto FROM v_pratiche WHERE IdContratto in (".$idContratto.")");
    //$titolo = "Proponi passaggio in $isOp"; //(".italianDate($dataForm['DataUltimaScadenza']).")";
    $titolo = "Pratica n. ".$titoloForm['CodContratto']." - ".$titoloForm['NomeCliente'];
    $txtHTML = $_REQUEST['htmlAzione'];
	$result = $_REQUEST['valoreHtmlAzione'];
	
	$formWidth  = $_REQUEST['formWidth'];
	$formHeight = $_REQUEST['formHeight'];
	
	$valuej = json_decode(stripslashes($result),true);
	
	for($j=0; $j<=sizeOf($valuej)-1; $j++){
	  $prova = $valuej[$j];
	  foreach ($prova as $k => $v){
	  	 $scriptImpostaValoriSalvati=$scriptImpostaValoriSalvati."myObj=document.getElementById('".$k."'); myObj.value='".$v."';";
	  }	
	}
?>

var formPanel = new Ext.form.FormPanel({
	xtype: "form",
	id: 'formVisualizzazione',
	labelWidth: 130, 
	frame: true, 
	autoScroll: true,
	title: 'Immagine dell\'operazione eseguita',
    width: <?php echo $formWidth?>+20,
    height: <?php echo $formHeight?>+30,
    defaultType: 'textfield',
    items: [
       {
        xtype:'panel', 
        layout:'form', 
        labelWidth:85, 
        defaultType:'textfield',
        html:'<?php echo addslashes($txtHTML);?>'
       }
    ],
    buttons: [
       {
        text: 'Chiudi',
        handler: function () 
          {
           quitForm(formPanel,win);
          }
       }
    ]       
});



Ext.onReady( 
      function() 
      {
         win = new Ext.Window({
						layout: 'fit',
					    width: <?php echo $formWidth?>+20,
					    height: <?php echo $formHeight?>+30,
						modal: true,
						flex: 1,
						frame: true,
						closable: false,
						items:[formPanel]
					});
					
         win.show();
         
         var myObj;
         <?php echo $scriptImpostaValoriSalvati;?>
         
      }
);       


