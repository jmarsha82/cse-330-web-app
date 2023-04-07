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
date_default_timezone_set('America/Chicago');

$registeredUser = false;
if(isset($_SESSION['username']))
{
   $registeredUser = true;
   $currentUser = $_SESSION['username'];
}

?>
<form method="POST" action="WustlNews.php">
   <input type="submit" value="Back to Main Page"/>
</form>
<?php

// Display the Story
$story_id = $_GET['story'];
$story_stmt = $mysqli->prepare("SELECT title, category, uploaded_by_user, date_uploaded, content, url FROM stories WHERE story_id=?");
if(!$story_stmt)
{
   printf("Query Prep Failed: %s\n", $mysqli->error);
   exit;
}
$story_stmt->bind_param('d', $story_id);

$story_stmt->execute();
$story_result = $story_stmt->get_result();
$story_stmt->close();

while($row = $story_result->fetch_assoc())
{
   printf("<h3 class=\"story\" id=\"%s\">%s</h3>", $row['category'], $row['title']);
   printf("<p class=\"infoLine\">%s -- Uploaded by %s %s</p>", $row['category'], $row['uploaded_by_user'], $row['date_uploaded']);
   printf("<p>%s</p>", $row['content']);
   if($row['url'] != NULL)
   {
      $escaped_url = htmlentities($row['url']);
      printf("<p>URL: <a href=\"%s\">%s</a></p>", $row['url'], $escaped_url);
   }
   
   // If the logged in user is the owner of the story, show Edit and Delete buttons
   if($registeredUser and $currentUser == $row['uploaded_by_user'])
   {
      ?>
      <table>
      <tr>
         <td>
         <form action="EditStory.php" method="POST">
            <input type="hidden" name="story" value="<?php echo $story_id ?>"/>
            <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>"/>
            <input type="submit" value="Edit Story"/>
         </form>
         </td>
         <td>
         <form method="POST">
            <input type="submit" name="deleteStory" value="Delete Story"/>
         </form>
         </td>
      </tr>
      </table>
      <?php
   }
   
   // Page break
   printf("<br><hr>");
   
   // Get number of comments
   $count_stmt = $mysqli->prepare("SELECT COUNT(comment_id) as count FROM comments WHERE story = ?");
   if(!$count_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $count_stmt->bind_param('d', $story_id);

   $count_stmt->execute();
   $count_stmt->bind_result($comment_count);
   while($count_stmt->fetch())
   {
      printf("<p>%d Comment(s)</p>", $comment_count);
   }
   $count_stmt->close();
   
   // Get all the comments and display
   $comment_stmt = $mysqli->prepare("SELECT comment_id, user, time, comment_text FROM comments WHERE story = ?");
   if(!$comment_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $comment_stmt->bind_param('d', $story_id);

   $comment_stmt->execute();
   $comment_result = $comment_stmt->get_result();
   $comment_stmt->close();

   while($comment_row = $comment_result->fetch_assoc())
   {
      ?>
      <div class="comment">
      <b><?php echo htmlspecialchars($comment_row['user']) ?></b>
      <p class="infoLineinComment"><?php echo htmlspecialchars($comment_row['time']) ?></p>
      <p class="commentIndent"> <?php echo htmlspecialchars($comment_row['comment_text']) ?></p>
      <?php if($registeredUser and $currentUser == $comment_row['user'])
      {
         ?>
         <table>
         <tr>
            <td>
            <form action="EditComment.php" method="POST">
               <input type="hidden" name="story" value="<?php echo $story_id ?>">
               <input type="hidden" name="comment" value="<?php echo $comment_row['comment_id'] ?>">
               <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>"/>
               <input type="submit" value="Edit">
            </form>
            </td>
            <td>
            <form method="POST">
               <input type="hidden" name="commentToDelete" value="<?php echo $comment_row['comment_id'] ?>">
               <input type="submit" value="Delete">
            </form>
            </td>
         </tr>
         </table>
         <?php
      }
      printf("</div>");
   }
}


// Registered Users can Add Comments - Display text box and button
if($registeredUser)
{
   ?>
   <br>
   <form method="POST">
      <textarea rows="5" cols="50" name="comment" required></textarea>
      <input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>"/>
      <input type="submit" value="Add Comment"/>
   </form>
   <?php
}

if(isset($_POST['comment']))
{
   // Check token
   if(!hash_equals($_SESSION['token'], $_POST['token']))
   {
      die("<p class=\"error\">Request forgery detected!</p>");
   }
   
   if( !preg_match('/^[\w_\-]+$/', $_POST['comment']) )
   {
      echo "<p class=\"error\">Invalid characters in comment</p>";
      exit;
   }
   
   $dateTime = new DateTime();
   $commentDate = date_format($dateTime, 'Y-m-d H:i:sP');
   
   $comment_stmt = $mysqli->prepare("INSERT INTO comments (user, time, story, comment_text) VALUES (?,?,?,?)");
   if(!$comment_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $comment_stmt->bind_param('ssds', $currentUser, $commentDate, $story_id, $_POST['comment']);
   $comment_stmt->execute();
   $comment_stmt->close();
   
   header("Refresh:0");
}

// User wants to delete their story
if(isset($_POST['deleteStory']))
{
   $safe_story_id = $mysqli->real_escape_string($story_id);

   // First delete all the comments for the story
   $delete_comments = sprintf("DELETE FROM comments WHERE story=%s", $safe_story_id);
   $delete_success = $mysqli->query($delete_comments);
   if(!$delete_success)
   {
      printf("DELETE Comments Failed: %s\n", $mysqli->error);
      exit;
   }

   // Next delete the story
   $delete_story = sprintf("DELETE FROM stories WHERE story_id=%s", $safe_story_id);
   $delete_success = $mysqli->query($delete_story);
   if(!$delete_success)
   {
      printf("DELETE Story Failed: %s\n", $mysqli->error);
      exit;
   }

   header("Location:WustlNews.php");
}

// User wants to delete their comment
if(isset($_POST['commentToDelete']))
{
   $safe_comment_id = $mysqli->real_escape_string($_POST['commentToDelete']);

   $delete_comment = sprintf("DELETE FROM comments WHERE comment_id=%s", $safe_comment_id);
   $delete_success = $mysqli->query($delete_comment);
   if(!$delete_success)
   {
      printf("DELETE Comment Failed: %s\n", $mysqli->error);
      exit;
   }

   header("Refresh:0");
}

?>

</body>
</html>