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
require 'src/NewsHelpers.php';

session_start();
date_default_timezone_set('America/Chicago');

if($mysqli === null)
{
   ?>
   <p class="notice">Demo mode is active. Configure MySQL before uploading live stories.</p>
   <form class="uploadStory" method="POST" action="WustlNews.php">
      <input type="submit" value="Return to WUSTL News"/>
   </form>
   <?php
   exit;
}

// Only registered users can upload a story
if(isset($_SESSION['username']))
{
   // Check token
   \WustlNews\require_valid_csrf_token($_SESSION['token'] ?? null, $_POST['token'] ?? null);
    
   ?>
   <form class="uploadStory" name="story_upload" method="POST">
       <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token']); ?>">
       <div>
           <label>Title</label><br>
           <input class="uploadStoryinput" type="text" name="title" required>
       </div><br>
       <div>
           <label>Category</label><br>
           <select name="category">
               <option value="Politics">Politics</option>
               <option value="Sports">Sports</option>
               <option value="Entertainment">Entertainment</option>
               <option value="World">World</option>
               <option value="Technology">Technology</option>
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
      if(!\WustlNews\is_valid_story_text($_POST['title'], 150))
      {
         echo "<p class=\"error\">Invalid title</p>";
         exit;
      }
      if(!\WustlNews\is_valid_story_text($_POST['content']))
      {
         echo "<p class=\"error\">Invalid characters in story content</p>";
         exit;
      }
      
      if(!\WustlNews\is_valid_optional_url($_POST['url'] ?? null))
      {
         echo "<p class=\"error\">Invalid URL</p>";
         exit;
      }

      // Set the variables
      $title = trim($_POST['title']);
      $user = $_SESSION['username'];
      $category = \WustlNews\normalize_category($_POST['category']);
      $content = trim($_POST['content']);
      $dateTime = new DateTime(); // current time
      $dateUploaded = date_format($dateTime, 'Y-m-d H:i:sP');
      $url = trim($_POST['url'] ?? '');
      
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
