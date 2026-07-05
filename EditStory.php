<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News Story Edit</title>
</head>
<body>

<h1>WUSTL News - Edit Story</h1>
<hr>
<br>

<?php
require 'database.php';
require 'src/NewsHelpers.php';

session_start();
date_default_timezone_set('America/Chicago');

$registeredUser = false;
if(isset($_SESSION['username']))
{
   $registeredUser = true;
   $currentUser = $_SESSION['username'];
}

$story_id = $_POST['story'];

// Check who the owner of the story is
$story_stmt = $mysqli->prepare("SELECT uploaded_by_user FROM stories WHERE story_id=?");
if(!$story_stmt)
{
   printf("Query Prep Failed: %s\n", $mysqli->error);
   exit;
}
$story_stmt->bind_param('d', $story_id);

$story_stmt->execute();
$story_stmt->bind_result($story_owner);
$story_stmt->fetch();
$story_stmt->close();

// Only registered users can edit their own story
if($registeredUser and $currentUser == $story_owner)
{
   // Check token
   \WustlNews\require_valid_csrf_token($_SESSION['token'] ?? null, $_POST['token'] ?? null);

   // Get current story info
   $story_stmt = $mysqli->prepare("SELECT title, category, content, url FROM stories WHERE story_id=?");
   if(!$story_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $story_stmt->bind_param('d', $story_id);
   
   $story_stmt->execute();
   $story_result = $story_stmt->get_result();
   $story_stmt->close();
   
   if($row = $story_result->fetch_assoc())
   {
      ?>
      <form class="uploadStory" name="editStory" method="POST">
         <input type="hidden" name="story" value="<?php echo \WustlNews\escape_html($story_id); ?>">
         <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token']); ?>">
         <div>
            <label>Title</label><br>
            <input class="uploadStoryinput" type="text" name="title" value="<?php echo \WustlNews\escape_html($row['title']) ?>" required>
         </div><br>
         <div>
            <label>Category</label><br>
            <select name="category">
               <option <?php if($row['category']=="Politics") echo "selected" ?> value="Politics">Politics</option>
               <option <?php if($row['category']=="Sports") echo "selected" ?> value="Sports">Sports</option>
               <option <?php if($row['category']=="Entertainment") echo "selected" ?> value="Entertainment">Entertainment</option>
               <option <?php if($row['category']=="World") echo "selected" ?> value="World">World</option>
               <option <?php if($row['category']=="Technology") echo "selected" ?> value="Technology">Technology</option>
            </select>
         </div><br>
         <div>
            <label>URL (optional)</label><br>
            <input class="uploadStoryinput" type="url" name="url" value="<?php echo \WustlNews\escape_html($row['url']) ?>">
         </div><br>
         <div>
            <label>Content</label><br>
            <textarea id="content" name="content" required><?php echo \WustlNews\escape_html($row['content']) ?></textarea>
         </div><br>
         <input type="submit" value="Update">
      </form>
      
      <form class="uploadStory" method="GET" action="Story.php">
         <input type="hidden" name="story" value="<?php echo \WustlNews\escape_html($story_id) ?>">
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
            echo "<p class=\"error\">Invalid characters in content</p>";
            exit;
         }

         if(!\WustlNews\is_valid_optional_url($_POST['url'] ?? null))
         {
            echo "<p class=\"error\">Invalid URL</p>";
            exit;
         }

         $title = trim($_POST['title']);
         $user = $_SESSION['username'];
         $category = \WustlNews\normalize_category($_POST['category']);
         $content = trim($_POST['content']);
         //$dateTime = new DateTime();
         //$dateUploaded = date_format($dateTime, 'Y-m-d H:i:sP');
         $url = trim($_POST['url'] ?? '');
         
         $stmt = $mysqli->prepare("UPDATE stories SET title=?, category=?, content=?, url=? WHERE story_id=?");
         if(!$story_stmt)
         {
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
         }
         $stmt->bind_param('sssss', $title, $category, $content, $url, $story_id);
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
       printf("<p class=\"error\">Did not find story id!</p>");
   }
}
else
{
    ?>
    <p class="error">You can not edit a story unless you are the story owner!</p>
    <form class="uploadStory" method="POST" action="WustlNews.php">
       <input type="submit" value="Return to Wustl News"/>
    </form>
    <?php
}
?>

</body>
</html>
