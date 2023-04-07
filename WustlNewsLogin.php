<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News Login</title>
</head>
<body>

<h1>WUSTL News - Login</h1>
<hr>
<br>
<form method="POST" action="WustlNews.php">
   <input type="submit" value="Back to Main Page"/>
</form>
<br><br>

<?php
// Connect to the Database
require 'database.php';

session_start();

// If user requested to log out, then destroy the session and display a message
if(isset($_POST['logout']))
{
   session_destroy();
   printf("<p class=\"notice\">You have been logged out</p>");
}
?>

<!-- Login Form -->
<form name="user_login" method="POST">
   User Name: <input type="text" name="username">
   Password: <input type="password" name="password">
   <input type="submit" value="Login">
</form>
	
<?php
// Username and password were submitted
if(isset($_POST['username']) && isset($_POST['password']))
{
   // Make sure username input is valid
   if( !preg_match('/^[\w_\-]+$/', $_POST['username']) )
   {
      echo "<p class=\"error\">Invalid username</p>";
      exit;
   }
	if( !preg_match('/^[\w_\-]+$/', $_POST['password']) )
   {
      echo "<p class=\"error\">Invalid password</p>";
      exit;
   }
	
   // Find username in database
   $user = $_POST['username'];
   $stmt = $mysqli->prepare("SELECT COUNT(*), password FROM users WHERE username=?");
	if(!$stmt)
	{
		printf("Query Prep Failed: %s\n", $mysqli->error);
		exit;
	}
	$stmt->bind_param('s', $user);

	$stmt->execute();
	$stmt->bind_result($cnt, $db_password);

	if ($stmt->fetch())
	{
		$stmt->close();
		$pswd_entered = $_POST['password'];
		if($cnt == 1 && password_verify($pswd_entered, $db_password))
		{
			// Set variables
			$_SESSION['username'] = $user;
			
			// Generate random string for session token
			$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
			
			header("Location: WustlNews.php");
			exit;
		}
		else
		{
			?>
			<p class="error">Incorrect Password!</p>
			<?php
		}
   }
	else
	{
		$stmt->close();
		printf("User not in database!");
	}
 
}

?>

<br><br>
<h4>Don't Have an Account?</h4>
<form action="RegisterNewUser.php" method="POST">
   <input type="submit" value="Register for an New Account">
</form>

</body>
</html>