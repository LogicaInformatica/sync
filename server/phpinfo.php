<?php
echo phpinfo();
/***
echo strtotime("15-12-2010 12:00:00");
echo "<br>";
echo strftime('%d/%m/%Y %H:%M:%s',1292281200);


//database parameters
$user='logica';   //user
$pw='logica';     //user password
$db='logica_GU';     //name of database

//make database connection
$connection = mysql_connect('localhost:3306',$user, $pw) or
   die("Could not connect: " . mysql_error());
mysql_select_db($db) or die("Could not select database $db");

$result = mysql_query('SELECT count(*) FROM ATTO');
$counter = mysql_fetch_row($result);

echo $counter;
*****/
?>