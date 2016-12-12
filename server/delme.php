<?php
$pippo=fetchValuesArray('SELECT datascadenza FROM nota where idutentedest=1 and datascadenza is not null');
echo $pippo;
?>