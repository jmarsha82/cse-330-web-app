<?php
//$mysqli = new mysqli('localhost', 'username', 'password', 'databasename');
$mysqli = new mysqli('localhost', 'newsie', 'news330', 'newsSite');
 
if($mysqli->connect_errno)
{
	printf("Connection Failed: %s\n", $mysqli->connect_error);
	exit;
}
?>