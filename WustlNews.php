<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News</title>
</head>
<body>

<h1>WUSTL News</h1>
<hr>
<br>

<?php

// Connect to the Database
require 'database.php';

session_start();

// Registered Users
if(isset($_SESSION['username']))
{
   // Display Welcome Message
   $currentUser = $_SESSION['username'];
   printf("Welcome %s!", $currentUser);
   
   // Display View Profile, Upload Story, and Log Out Buttons
   ?>
   <br>
   <table>
   <tr>
      <td>
      <form method="GET" action="ViewProfile.php">
         <input type="hidden" name="user" value="<?php echo $currentUser ?>">
         <input type="submit" value="View Profile"/>
      </form>
      </td>
      <td>
      <form method="POST" action="UploadStory.php">
         <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>"/>
         <input type="submit" value="Upload Story"/>
      </form>
      </td>
      <td>
      <form method="POST" action="WustlNewsLogin.php">
         <input type="submit" name="logout" value="Log Out"/>
      </form>
      </td>
   </tr>
   </table>
   <br><br>
   <?php
}
else // Un-registered users get Login Button
{
   ?>
   <form method="POST" action="WustlNewsLogin.php">
      <input type="submit" value="Login"/>
   </form>
   <br>
   <?php
}

// Allow filtering by category
if(isset($_GET['category']))
{
   $filter_category = $_GET['category'];
}
else
{
   $filter_category = 'All';
}
?>

<table>
<tr>
   <th>Filter by Category</th>
</tr>
<tr>
   <td>
   <form method="GET">
      <input type="radio" name="category" value="Politics" <?php if($filter_category=='Politics') echo 'checked' ?>>Politics 
      <input type="radio" name="category" value="Sports" <?php if($filter_category=='Sports') echo 'checked' ?>>Sports 
      <input type="radio" name="category" value="Entertainment" <?php if($filter_category=='Entertainment') echo 'checked' ?>>Entertainment
      <input type="radio" name="category" value="World" <?php if($filter_category=='World') echo 'checked' ?>>World
      <input type="radio" name="category" value="Technology" <?php if($filter_category=='Technology') echo 'checked' ?>>Technology 
      <input type="radio" name="category" value="All" <?php if($filter_category=='All') echo 'checked' ?>>All
      <input type="submit" value="Filter">
   </form>
   </td>
</tr>
</table>
<br>

<?php

/****** Display Stories *******/

// Figure out sql query based on filter
if($filter_category == 'All')
{
   $story_stmt = $mysqli->prepare("SELECT story_id, title, category, uploaded_by_user, date_uploaded, content, url FROM stories ORDER BY date_uploaded DESC");
   if(!$story_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
}
else
{
   $story_stmt = $mysqli->prepare("SELECT story_id, title, category, uploaded_by_user, date_uploaded, content, url FROM stories WHERE category=? ORDER BY date_uploaded DESC");
   if(!$story_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $story_stmt->bind_param('s', $filter_category);
}

// Execute the sql query
$story_stmt->execute();
$story_result = $story_stmt->get_result();
$story_stmt->close();

// For each story returned by the query
while($row = $story_result->fetch_assoc())
{
   ?>
   <form action="Story.php" method="GET">
      <input type="hidden" name="story" value="<?php echo $row['story_id'] ?>"/>
      <input class="storytitle<?php echo htmlspecialchars($row['category']) ?>" type="submit" value="<?php  echo htmlspecialchars($row['title']) ?>"/>
   </form>
   <?php
   printf("<p class=\"infoLine\">%s -- Uploaded by %s %s</p>", $row['category'], htmlspecialchars($row['uploaded_by_user']), $row['date_uploaded']);
   printf("%s", htmlspecialchars($row['content']));
   if($row['url'] != NULL)
   {
      $escaped_url = htmlentities($row['url']);
      printf("<br><br>URL: <a href=\"%s\">%s</a>", $row['url'], $escaped_url);
   }
   
   // Find out how many comments there are for each story
   $comment_stmt = $mysqli->prepare("select COUNT(comment_id) from comments where story = ?");
   if(!$comment_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $comment_stmt->bind_param('d', $row['story_id']);
   $comment_stmt->execute();
   $comment_stmt->bind_result($db_cmt_count);
   if($comment_stmt->fetch())
   {
      printf("<p>%d Comment(s)</p>", $db_cmt_count);
   }
   $comment_stmt->close();
}


?>

</body>
</html>