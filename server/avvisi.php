<?php
require_once("userFunc.php");
$context = $_SESSION['userContext'];
$idUtente = $context['IdUtente'];
$sql = "SELECT nota.* , cliente.idcliente as idcliente,cliente.nominativo as nominativo, contratto.codcontratto as numpratica FROM nota left join contratto on nota.idcontratto=contratto.idcontratto left join cliente on contratto.idcliente=cliente.idcliente WHERE TipoNota='A' AND CURDATE() BETWEEN nota.DataIni AND nota.DataFin AND ".userCondition()
	." ORDER BY nota.lastupd DESC";
$arr = getFetchArray($sql);
if (is_array($arr))
{
	foreach($arr as $element)
	{
		$idContratto=$element['IdContratto'];
   		$numpratica=$element['numpratica'];
   		$tipoNota=$element['TipoNota'];
   		$idNota=$element['IdNota'];
   		$idcliente=$element['idcliente'];
   		$nominativo=$element['nominativo'];
   		$testo=$element['TestoNota'];
   		
   		if (($idContratto==null)) 
   			$idContratto=0;
   		if (($idNota==null)) 
   			$idNota=0;
   		if (($idcliente==null)) 
   			$idcliente=0;
//		if (strlen($testo)>150)
//			$testo = substr($testo,0,130)."...";

   		$ArrSplit = split('<a',$testo);	
		$ArrSubSplit=array();
		for($j=0;$j<count($ArrSplit);$j++){
			$mom = split('</a>',$ArrSplit[$j]);	
			$ArrSubSplit[]=$mom;
		}
		
		//trace("ARRTOT ".print_r($ArrSubSplit,true));
		
		$ArrImplode=array();
		$ArrImplode[0]=$ArrSubSplit[0][0];
		for($k=1;$k<count($ArrSubSplit);$k++){
			for($h=0;$h<count($ArrSubSplit[$k]);$h++){
				if($h==0)
				{
					$ArrSubSplit[$k][$h]="</a><a".$ArrSubSplit[$k][$h];
				}else{
					$ArrSubSplit[$k][$h]="</a><a style=\"text-decoration:none\" href=\"javascript:DCS.FormNota.showDetailNote(".$idContratto.",'".$numpratica."','".$tipoNota."',".$idNota.",".$idcliente.",'".str_replace("'","\'",$nominativo)."');\">".$ArrSubSplit[$k][$h];
				}
			}
			$ArrImplode[$k] = implode('', $ArrSubSplit[$k]);
		}
		$testo = implode('', $ArrImplode);
		
		//trace("ARRTOTAFTER ".print_r($ArrSubSplit,true));
		
		$ArrImplode=array();
		$ArrSubSplit=array();
		//trace("testo $testo");
		
   		echo "<li><a  style=\"text-decoration:none\" href=\"javascript:DCS.FormNota.showDetailNote(".$idContratto.",'".$numpratica."','".$tipoNota."',".$idNota.",".$idcliente.",'".str_replace("'","\'",$nominativo)."');\">".$testo.'</a></li>';
	}
}
?>