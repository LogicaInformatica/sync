<?php
//
// Composizione del menù delle azioni
//
	try 
	{ 
		require_once("workflowFunc.php");
		//trace("menuazioni");
		$contracts = json_decode(stripslashes($_REQUEST['contracts']));
		$isNote = $_REQUEST['isNote']=='true';
		$isStorico = $_REQUEST['isStorico']=='true';
		$idGrid=isset($_REQUEST['idGrid'])?$_REQUEST['idGrid']:'';
		$isEmployGrid=isset($_REQUEST['employ'])?$_REQUEST['employ']:false;
		
		// Crea l'array degli ID delle azioni permesse sull'insieme dato di contratti
		$azioniAbilitate = array();
	    if (is_array($contracts))
	    	$azioniAbilitate = getActions($contracts,true,true,$isNote); 
	 	
	    //trace("az ab ".print_r($azioniAbilitate,true));
		// Se il menù ha un numero limitato di voci, genera un solo livello
	    // Legge la lista di tutte le azioni permesse all'utente, ordinate per tipo azione
	    // oppure per azione
	    
	    // 27/6/2012: per evitare che si vedano i gruppi di azioni "stragiudiziali" e "legali"
	    // quando non devono (avendo singole azioni in comune, un agente vedrebbe entrambi i gruppi
	    // sia quando guarda pratiche STR sia quando guarda pratiche LEG) crea una condizione
	    // aggiuntiva di filtro
	    // 4/9/12: per quelle non affidate (in attesa affido STR/LEG), che sono visibili solo agli interni, fa vedere le azioni strag.,
	    // in modo che l'operatore interno possa fare cose come la rich. piano di rientro)
	    $praticheSTR = rowExistsInTable("contratto","IdStatoRecupero IN (6,25,26) AND IdContratto IN (". join($contracts,",").")");
	    $praticheLEG = rowExistsInTable("contratto","IdStatoRecupero=5 AND IdContratto IN (". join($contracts,",").")");
		    	
	    $azioni = readAllActions($flat,$isNote,$isEmployGrid,$praticheSTR,$praticheLEG,$isStorico);
	    $idTipoAzione="";
		$menuAction="";
		$menu="";
		
		// Menù ad un solo livello
		if ($flat)
		{
			foreach ($azioni as $elemento)
			{
				if ($menu!="")
					$menu .= ", "; // se non è il primo elemento dell'array, mette la virgola
				$idStatoAzione = $azioniAbilitate[$elemento['IdAzione']];
				
				if($isNote){
					$testoMenu=addslashes(htmlstr($elemento['TitoloAzione']));
				}else{
					$testoMenu=addslashes(htmlstr($elemento['CodAzione']))." - ".addslashes(htmlstr($elemento['TitoloAzione']));
				}
				
				if ($idStatoAzione)  
					$menu .= " {text: '".$testoMenu."'"
								.", data: ".json_encode_plus(array($idStatoAzione,$contracts,$idGrid))
								.", handler: eseguiAzione }";  	
				else
					$menu .= " {text: '".addslashes(htmlstr($elemento['TitoloAzione']))."'"
								.", disabled : true}"; 	
			}
		}
		else // Menù a 2 e 3 livelli
		{
			// Loop di creazione delle voci del menù, ad ogni break del valore del tipoAzione compone il secondo livello di menù
			// Per le voci la cui sigla comincia per WF, crea un terzo livello che contiene le azioni di workflow
			//trace("Azioni generali: ".print_r($azioni,true));
			foreach ($azioni as $elemento)
			{
				if ($idTipoAzione!=$elemento['idTipoAzione']) // break sul tipo azione
				{			
					$idTipoAzione=$elemento['idTipoAzione'];	
					if ($menu!="")
						$menu .= "$menuAction ] } },";  // chiude il sottomenù di secondo livello
					$menu .= " { text: '".addslashes(htmlstr($elemento['TitoloTipoAzione']))."', menu: { items: ["; // riapre la voce di primo livello successiva
					$menuAction="";				
				}
					
				if ($menuAction!="")
					$menuAction .= ", "; // se non è il primo elemento dell'array, mette la virgola
					
				if (substr($elemento['CodAzione'],0,2)=="WF")  // azione che indica una procedura di workflow
				{	// Costruisce il menù di terzo livello per le azioni di procedura di workflow
					//trace("cod ".$elemento['CodAzione']);
					$azioniInProc = readActionsInProc($elemento['CodAzione']);
					//trace("proc Az: ".print_r($azioniInProc,true));
					$menu3 = "";
					$urlManuale = "";
					foreach ($azioniInProc as $azioneInProc)
					{
						if ($menu3!="")
							$menu3 .= ", ";
						// Segna se la procedura prevede un manuale d'uso o norma interna
						if ($urlManuale=="" && $azioneInProc["UrlDocProcedura"]!=NULL)
							$urlManuale = $azioneInProc["UrlDocProcedura"];
						// Compone riga del menù di terzo livello
						$idStatoAzione = $azioniAbilitate[$azioneInProc['IdAzione']];
						if ($idStatoAzione){  
							$menu3 .= " { text: '".addslashes(htmlstr($azioneInProc['TitoloAzione']))."'"
									.", data : ".json_encode_plus(array($idStatoAzione,$contracts,$idGrid))
									.", handler : eseguiAzione} "; 	
							//trace("menuVoce $menu3");
						}else
							$menu3 .= " { text: '".addslashes(htmlstr($azioneInProc['TitoloAzione']))."'"
									.", disabled : true}"; 	
					}
					// Se la procedura prevede un manuale d'uso o norma interna, include una riga apposita
					if ($urlManuale>"")
						$menu3 .= ",'-',{text:'Norme interne', data:'$urlManuale', handler:vediManuale}";	
					$menuAction .= " { text: '".addslashes(htmlstr($elemento['TitoloAzione']))."', menu: { items: [$menu3] } } ";
				}
				else // azione normale: genera la chiamata alla funzione di esecuzione
				{
					$idStatoAzione = $azioniAbilitate[$elemento['IdAzione']];
					if ($idStatoAzione)  
						$menuAction .= " { text: '".addslashes(htmlstr($elemento['CodAzione']))." - ".addslashes(htmlstr($elemento['TitoloAzione']))."'"
									.", data : ".json_encode_plus(array($idStatoAzione,$contracts,$idGrid))
									.", handler : eseguiAzione }";  	
					else
						$menuAction .= " { text: '".addslashes(htmlstr($elemento['CodAzione']))." - ".addslashes(htmlstr($elemento['TitoloAzione']))."'"
									.", disabled : true}"; 	
				}
			}
			if ($menu!="")
				$menu .= $menuAction." ] } }";
		}
		echo $menu;
	}
	catch (Exception $e)
	{
		 return " { text: '".$e->getMessage()."'}";
	}
?>
