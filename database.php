<?php
$db_host = getenv('WUSTL_NEWS_DB_HOST') ?: 'localhost';
$db_user = getenv('WUSTL_NEWS_DB_USER') ?: '';
$db_password = getenv('WUSTL_NEWS_DB_PASSWORD') ?: '';
$db_name = getenv('WUSTL_NEWS_DB_NAME') ?: '';

$mysqli = null;
$database_error = null;

if($db_user === '' || $db_name === '')
{
	$database_error = 'Database configuration missing. Running with demo news data.';
	return;
}

if(!class_exists('mysqli'))
{
	$database_error = 'The mysqli extension is not enabled. Running with demo news data.';
	return;
}

$mysqli = @new mysqli($db_host, $db_user, $db_password, $db_name);
 
if($mysqli->connect_errno)
{
	$database_error = sprintf('Database connection failed: %s. Running with demo news data.', $mysqli->connect_error);
	$mysqli = null;
}
?>
