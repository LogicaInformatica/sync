<?php 
	{
		// Blocco di istruzioni usato in funzioniStorico.php al posto della " or die()" per gestire la differente uscita 
		// tra il caso chiamato da funzione online e quello batch
		$lastDbErr = getlastError();
		if ($context["process_name"]>'')  {
			writeProcessLog($context["process_name"],$lastDbErr,3);
			sendDeferMail();
		}
		die($lastDbErr);
	}
?>
