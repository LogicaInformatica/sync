<?php


// Make the connection:

$host = "localhost";
$username = "root";
$password = "usbw";
$db_name = "tst";

$conn = mysqli_connect($host, $username, $password, $db_name, 3307);
if (!$conn) {
	die('Could not connect: ' . mysqli_error());
}
?>