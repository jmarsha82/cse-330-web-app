<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News Comment Edit</title>
</head>
<body>

<h1>WUSTL News - Edit Comment</h1>
<hr>
<br>

<?php
require 'database.php';
session_start();
date_default_timezone_set('America/Chicago');

$registeredUser = false;
if(isset($_SESSION['username']))
{
   $registeredUser = true;
   $currentUser = $_SESSION['username'];
}

$story_id = $_POST['story'];
$comment_id = $_POST['comment'];

// Check who the owner of the comment is
$comment_stmt = $mysqli->prepare("SELECT user FROM comments WHERE comment_id=?");
if(!$comment_stmt)
{
   printf("Query Prep Failed: %s\n", $mysqli->error);
   exit;
}
$comment_stmt->bind_param('d', $comment_id);

$comment_stmt->execute();
$comment_stmt->bind_result($comment_owner);
if(!$comment_stmt->fetch())
{
   printf("Error on fetching comment owner: %s", $mysqli->error);
   exit;
}
$comment_stmt->close();

// Only registered users can edit their own comment
if($registeredUser and $currentUser == $comment_owner)
{
   // Check token
   if(!hash_equals($_SESSION['token'], $_POST['token']))
   {
      die("<p class=\"error\">Request forgery detected!</p>");
   }
   
   // Get current comment info
   $comment_stmt = $mysqli->prepare("SELECT comment_text FROM comments WHERE comment_id=?");
   if(!$comment_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $comment_stmt->bind_param('d', $comment_id);
   
   $comment_stmt->execute();
   $comment_result = $comment_stmt->get_result();
   $comment_stmt->close();
   
   if($row = $comment_result->fetch_assoc())
   {
      ?>
      <form class="uploadStory" name="editComment" method="POST">
          <div>
            <label>Comment Content</label><br>
            <textarea id="content" name="content" required><?php echo htmlspecialchars($row['comment_text']) ?></textarea>
          </div><br>
         <input type="submit" value="Update">
      </form>

      <form class="uploadStory" method="GET" action="Story.php">
         <input type="hidden" name="story" value="<?php echo $story_id ?>">
         <input type="submit" value="Cancel"/>
      </form>
       
      <?php 
      if(isset($_POST['content']))
      {
         // Make sure comment input is valid
         if( !preg_match('/^[\w_\-]+$/', $_POST['comment']) )
         {
            echo "<p class=\"error\">Invalid characters in comment</p>";
            exit;
         }
       
         $content = $_POST['content'];
         //$dateTime = new DateTime();
         //$dateUploaded = date_format($dateTime, 'Y-m-d H:i:sP');
         
         $stmt = $mysqli->prepare("UPDATE comments SET comment_text=? WHERE comment_id=?");
         if(!$stmt)
         {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
         }
         $stmt->bind_param('ss', $content, $comment_id);
         $success = $stmt->execute();
         if(!$success)
         {
             printf("Error on the UPDATE: %s", $mysqli->error);
             exit;
         }
         $stmt->close();
         
         $returnString = sprintf("Location: Story.php?story=%s", $story_id);
         header($returnString);
         exit;
      }
   }
   else
   {
       printf("<p class=\"error\">Did not find comment id!</p>");
   }
}
else
{
    ?>
    <p class="error">You can not edit a comment unless you are the comment owner!</p>
    <form class="uploadStory" method="POST" action="WustlNews.php">
       <input type="submit" value="Return to Wustl News"/>
    </form>
    <?php
}
?>

</body>
</html>