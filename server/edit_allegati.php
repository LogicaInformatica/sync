<?php
require_once("userFunc.php");
require_once("workflowFunc.php");
$task = ($_POST['task']) ? ($_POST['task']) : null;

switch($task){
	case "delete":
		if (deleteAllegato($_POST['IdAllegato']))
			echo "{'success':true}";
		else
			echo "{success:false, error:\"".getLastError()."\"}";
		break;
	default:
		echo "{failure:true}";
		break;
}
?>
