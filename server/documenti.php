<?php 
require_once("userFunc.php");

if ($context["InternoEsterno"] == 'I')
{
   	echo <<<EOT
   	<li>
   	<a href="http://itros024p.it.toyota-fs.com/fincrawler/fincrawler.asp" target="_blank">TFSI FinCrawler</a>
    </li>
 	<br>
EOT;
}
?>
    <li>
    <a href="http://www.poste.it/online/cercaup/" target="_blank">Ricerca ufficio postale</a>
    </li>
   	<br>
    <li>
    <a href="https://servizi.aci.it/VisureInternet/welcome.do" target="_blank">Visure PRA online</a>
    </li>
   	<br>
    <li>
    <a href="http://trovaconcessionario.toyota.it/dealers/index.aspx?ui=blankpage" target="_blank">Trova concessionario</a>
    </li>
   	<br>
    <li>
    <a href="http://www.abiecab.it/" target="_blank">Ricerca ABI e CAB</a>
    </li>
   	<br>
    <li>
    <a href="http://www.infoimprese.it/impr/index.jsp" target="_blank">Infoimprese.it</a>
    </li>
   	<br>
    <li>
    <a href="http://www.paginebianche.it/index.html" target="_blank">Pagine bianche</a>
    </li>
   	<br>
    <li id="linkToChrome" style="display:none">
 	<a href="http://www.google.com/chrome?hl=it" target="_blank">Scarica Google Chrome</a>
    </li>
 
<?php 
if (userCanDo("MENU_UT_PROF")) // utente molto potente
{
  	echo <<<EOT
   	<li>
   	<a href="../links/Modello Dati CNC/Default.html" target="_blank">Modello Dati CNC</a>
    </li>
 	<br>
EOT;
}
?> 