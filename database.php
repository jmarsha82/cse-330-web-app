<?php
$db_host = getenv('WUSTL_NEWS_DB_HOST') ?: 'localhost';
$db_user = getenv('WUSTL_NEWS_DB_USER') ?: '';
$db_password = getenv('WUSTL_NEWS_DB_PASSWORD') ?: '';
$db_name = getenv('WUSTL_NEWS_DB_NAME') ?: '';

if($db_user === '' || $db_name === '')
{
	printf("Database configuration missing. Set WUSTL_NEWS_DB_USER, WUSTL_NEWS_DB_PASSWORD, and WUSTL_NEWS_DB_NAME.\n");
	exit;
}

$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name);
 
if($mysqli->connect_errno)
{
	printf("Connection Failed: %s\n", $mysqli->connect_error);
	exit;
}
?>
