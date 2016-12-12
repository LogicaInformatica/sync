<?php
    if (function_exists("mail"))
    {
    	$ret = mail("giorgio.difalco@gmail.com","Prova invio mail", "body vuoto", "From: Toyota Financial Services <noreply@tfsi.it>");
    	echo "Risultato invio mail = $ret";
    }
    else
    	echo "Funzione mail non disponibile";
?>