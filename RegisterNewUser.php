<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News</title>
</head>
<body>

<h1>WUSTL News - Register</h1>
<hr>
<br>
<form method="POST" action="WustlNews.php">
   <input type="submit" value="Back to Main Page"/>
</form>
<br><br>
<form name="register_new_user" method="POST">
   User Name: <input type="text" name="username">
   Password: <input type="password" name="password">
   <input type="submit" value="Register">
</form>

<?php
// Connect to the Database
require 'database.php';

session_start();
date_default_timezone_set('America/Chicago');

if(isset($_POST['username']) and isset($_POST['password']))
{
   // Make sure username input is valid
   if( !preg_match('/^[\w_\-]+$/', $_POST['username']) )
   {
      echo "Invalid username";
      exit;
   }
	if( !preg_match('/^[\w_\-]+$/', $_POST['password']) )
   {
      echo "<p class=\"error\">Invalid password</p>";
      exit;
   }
   
   // See if the username already exists
   $user = $_POST['username'];
   $query_stmt = $mysqli->prepare("select username from users where username=?");
	if(!$query_stmt)
	{
		printf("Query Prep Failed: %s\n", $mysqli->error);
		exit;
	}
	$query_stmt->bind_param('s', $user);
   
   $query_stmt->execute();
	$query_stmt->bind_result($db_username);
   if($query_stmt->fetch())
   {
		$query_stmt->close();
      printf ("<p class=\"error\">Sorry that username is taken. Try again</p>");
   }
   else // Insert this username
   {
		$query_stmt->close();

      // Create the hashed password
      $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
		
		// Set the date joined field
		$dateTime = new DateTime(); // Current time
      $dateJoined = date_format($dateTime, 'Y-m-d H:i:sP');
      
      // Insert into our database
      $insert_stmt = $mysqli->prepare("insert into users (username, password, date_joined) values (?,?,?)");
		if(!$insert_stmt)
		{
			printf("Query Prep Failed: %s\n", $mysqli->error);
			exit;
		}
      $insert_stmt->bind_param('sss', $user, $password, $dateJoined);
      $insert_stmt->execute();
		$insert_stmt->close();
		
		// Set session user and redirect to main page
      $_SESSION['username'] = $user;
		header("Location: WustlNews.php");
		exit;
   }
}

?>

<br>
<form action="WustlNewsLogin.php">
	<input type="submit" value="Cancel"/>
</form>

</body>
</html>