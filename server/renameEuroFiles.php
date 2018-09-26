<?php
require_once("common.php");
$EIPATH = ATT_PATH.'/euroInvestigation';

// Mette il nome standard ai file vecchi EuroInvestigation
	$files = scandir($EIPATH);
	foreach ($files as $file) {
		if (preg_match('/((?:LO|LE)\d+(?:[-_]\d)?\.pdf)/i',$file,$arr)) {
			$fileName = $arr[1];
			$data = date('Ymd',filemtime($EIPATH."/$file"));
			//echo "\n".$EIPATH."/$file ".filemtime($EIPATH."/$file");
    		$newName = $data."_".$fileName;
			//echo "\n $file $fileName $newName";
			if (!rename($EIPATH."/$file",$EIPATH."/$newName")) {
				die("rename fallito ".$EIPATH."/$file ==> ".$EIPATH."/$newName");
			}
		}
	}