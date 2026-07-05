<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News</title>
</head>
<body>

<h1>WUSTL News - User Profile</h1>
<hr>
<br>

<form method="POST" action="WustlNews.php">
   <input type="submit" value="Back to Main Page"/>
</form>

<?php

// Connect to the Database
require 'database.php';
require 'src/NewsHelpers.php';

session_start();

if(isset($_GET['user']))
{
   $chosenUser = $_GET['user'];
   printf("<h2>%s</h2>", \WustlNews\escape_html($chosenUser));

   if($mysqli === null)
   {
      $stories = array_values(array_filter(
         \WustlNews\demo_stories(),
         static fn (array $story): bool => $story['uploaded_by_user'] === $chosenUser
      ));
      if(count($stories) === 0)
      {
         $stories = \WustlNews\demo_stories();
      }

      printf("<p class=\"notice\">Demo mode profile. Configure MySQL for live user history.</p>");
      printf("<p><i>User since 2026-06-01 09:00:00-05:00</i></p>");
      printf("<p><b>%d %s Uploaded</b></p><br>", count($stories), count($stories) === 1 ? 'Story' : 'Stories');

      foreach($stories as $row)
      {
         ?>
         <form action="Story.php" method="GET">
            <input type="hidden" name="story" value="<?php echo \WustlNews\escape_html((string) $row['story_id']) ?>"/>
            <input class="storytitle<?php echo \WustlNews\escape_html((string) $row['category']) ?>" type="submit" value="<?php echo \WustlNews\escape_html((string) $row['title']) ?>"/>
         </form>
         <?php
         printf("<p class=\"infoLine\">%s -- Uploaded by %s %s</p>", \WustlNews\escape_html((string) $row['category']), \WustlNews\escape_html($chosenUser), \WustlNews\escape_html((string) $row['date_uploaded']));
         printf("%s", \WustlNews\escape_html(\WustlNews\excerpt((string) $row['content'])));
         printf("<p>%d Comments</p>", (int) ($row['comment_count'] ?? 0));
      }
      exit;
   }

   // SQL Query to find when the user joined
   $stmt = $mysqli->prepare("SELECT date_joined FROM users WHERE username=?");
   if(!$stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $stmt->bind_param('s', $chosenUser);
   
   // Execute the sql query
   $stmt->execute();
   $stmt->bind_result($joinDate);
   if($stmt->fetch())
   {
      printf("<p><i>User since %s</i></p>", \WustlNews\escape_html($joinDate));
   }
   $stmt->close();
   
   // Figure out how many stories the user has uploaded
   $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) FROM stories WHERE uploaded_by_user=?");
   if(!$cnt_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $cnt_stmt->bind_param('s', $chosenUser);
   
   // Execute the sql query
   $cnt_stmt->execute();
   $cnt_stmt->bind_result($cnt);
   if($cnt_stmt->fetch())
   {
      if($cnt == 1)
      {
         printf("<p><b>1 Story Uploaded</b></p><br>");
      }
      else
      {
         printf("<p><b>%d Stories Uploaded</b></p><br>", $cnt);
      }
   }
   $cnt_stmt->close();


   /****** Display Stories for Chosen User *******/
   
   // Prepare sql query
   $story_stmt = $mysqli->prepare("SELECT story_id, title, category, date_uploaded, content, url FROM stories WHERE uploaded_by_user=? ORDER BY date_uploaded DESC");
   if(!$story_stmt)
   {
      printf("Query Prep Failed: %s\n", $mysqli->error);
      exit;
   }
   $story_stmt->bind_param('s', $chosenUser);
   
   // Execute the sql query
   $story_stmt->execute();
   $story_result = $story_stmt->get_result();
   $story_stmt->close();
   
   // For each story returned by the query
   while($row = $story_result->fetch_assoc())
   {
      ?>
      <form action="Story.php" method="GET">
         <input type="hidden" name="story" value="<?php echo htmlspecialchars($row['story_id']) ?>"/>
         <input class="storytitle<?php echo htmlspecialchars($row['category']) ?>" type="submit" value="<?php  echo htmlspecialchars($row['title']) ?>"/>
      </form>
      <?php
      printf("<p class=\"infoLine\">%s -- Uploaded by %s %s</p>", \WustlNews\escape_html($row['category']), \WustlNews\escape_html($chosenUser), \WustlNews\escape_html($row['date_uploaded']));
      printf("%s", \WustlNews\escape_html($row['content']));
      if($row['url'] != NULL)
      {
         $escaped_url = \WustlNews\escape_html($row['url']);
         printf("<br><br>URL: <a href=\"%s\">%s</a>", $escaped_url, $escaped_url);
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
         printf("<p>%d Comments</p>", $db_cmt_count);
      }
      $comment_stmt->close();
   }
}
else
{
   printf("<p class=\"error\">No User chosen!</p>");
}

?>

</body>
</html>
