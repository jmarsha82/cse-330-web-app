<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News Story Upload</title>
</head>
<body>

<h1>WUSTL News - Story Upload</h1>
<hr>
<br>

<?php
require 'database.php';
session_start();
date_default_timezone_set('America/Chicago');

// Only registered users can upload a story
if(isset($_SESSION['username']))
{
   // Check token
   if(!hash_equals($_SESSION['token'], $_POST['token']))
   {
      die("<p class=\"error\">Request forgery detected!</p>");
   }
    
   ?>
   <form class="uploadStory" name="story_upload" method="POST">
       <div>
           <label>Title</label><br>
           <input class="uploadStoryinput" type="text" name="title" required>
       </div><br>
       <div>
           <label>Category</label><br>
           <select name="category">
               <option value="politics">Politics</option>
               <option value="sports">Sports</option>
               <option value="entertainment">Entertainment</option>
               <option value="world">World</option>
               <option value="technology">Technology</option>
           </select>
       </div><br>
       <div>
           <label>URL (optional)</label><br>
           <input class="uploadStoryinput" type="url" name="url">
       </div><br>
       <div>
           <label>Content</label><br>
           <textarea id="content" name="content" required></textarea>
       </div><br>
       <input type="submit" value="Post Story">
   </form>
    
   <form class="uploadStory" method="POST" action="WustlNews.php">
      <input type="submit" value="Cancel"/>
   </form>
    
   <?php 
   if(isset($_POST['title']) and isset($_POST['category']) and isset($_POST['content']))
   {
      // Make sure story input is valid
      if( !preg_match('/^[\w_\-]+$/', $_POST['title']) )
      {
         echo "<p class=\"error\">Invalid title</p>";
         exit;
      }
      if( !preg_match('/^[\w_\-]+$/', $_POST['content']) )
      {
         echo "<p class=\"error\">Invalid characters in story content</p>";
         exit;
      }
      
      // Set the variables
      $title = $_POST['title'];
      $user = $_SESSION['username'];
      $category = $_POST['category'];
      $content = $_POST['content'];
      $dateTime = new DateTime(); // current time
      $dateUploaded = date_format($dateTime, 'Y-m-d H:i:sP');
      $url = $_POST['url'];  
      
      // Prepare and execute the insert to the database
      $stmt = $mysqli->prepare("INSERT INTO stories (title, uploaded_by_user, category, content, date_uploaded, url) VALUES (?,?,?,?,?,?)");
      if(!$stmt)
      {
         printf("Query Prep Failed: %s\n", $mysqli->error);
         exit;
      }
      $stmt->bind_param('ssssss', $title, $user, $category, $content, $dateUploaded, $url);
      $stmt->execute();
      $stmt->close();
      
      // Do a query to find the story id
      $stmt = $mysqli->prepare("SELECT COUNT(*), story_id FROM stories WHERE title=? AND uploaded_by_user=? AND date_uploaded=?");
      if(!$stmt)
      {
         printf("Query Prep Failed: %s\n", $mysqli->error);
         exit;
      }
      $stmt->bind_param('sss', $title, $user, $dateUploaded);
      $stmt->execute();
      $stmt->bind_result($cnt, $story_id);
      if($stmt->fetch())
      {
         $stmt->close();
         if($cnt == 1) // Make sure there was only one story returned from the query
         {
            // Redirect to the story page
            $returnString = sprintf("Location: Story.php?story=%s", $story_id);
            header($returnString);
            exit;
         }
      }
   }
}
else
{
    ?>
    <p class="error">You can not upload a story unless you are logged in!</p>
    <form class="uploadStory" method="POST" action="WustlNewsLogin.php">
       <input type="submit" value="Login"/>
    </form>
    <form class="uploadStory" method="POST" action="WustlNews.php">
       <input type="submit" value="Return to Wustl News"/>
    </form>
    <?php
}
?>

</body>
</html>