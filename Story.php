<?php
require 'database.php';
require 'src/NewsHelpers.php';

session_start();
date_default_timezone_set('America/Chicago');

$registeredUser = isset($_SESSION['username']);
$currentUser = $_SESSION['username'] ?? null;
$story_id = (int) ($_GET['story'] ?? 101);
$usingDemoData = $mysqli === null;
$story = null;
$comments = [];

if(!$usingDemoData)
{
   if(isset($_POST['comment']) && $registeredUser)
   {
      \WustlNews\require_valid_csrf_token($_SESSION['token'] ?? null, $_POST['token'] ?? null);
      if(!\WustlNews\is_valid_story_text($_POST['comment'], 2000))
      {
         $form_error = 'Invalid characters in comment.';
      }
      else
      {
         $commentDate = date_format(new DateTime(), 'Y-m-d H:i:sP');
         $comment_stmt = $mysqli->prepare("INSERT INTO comments (user, time, story, comment_text) VALUES (?,?,?,?)");
         if($comment_stmt)
         {
            $comment_stmt->bind_param('ssds', $currentUser, $commentDate, $story_id, $_POST['comment']);
            $comment_stmt->execute();
            $comment_stmt->close();
            header("Location: Story.php?story=" . $story_id);
            exit;
         }
      }
   }

   if(isset($_POST['deleteStory']) && $registeredUser)
   {
      \WustlNews\require_valid_csrf_token($_SESSION['token'] ?? null, $_POST['token'] ?? null);
      $delete_comments = $mysqli->prepare("DELETE FROM comments WHERE story=?");
      $delete_story = $mysqli->prepare("DELETE FROM stories WHERE story_id=? AND uploaded_by_user=?");
      if($delete_comments && $delete_story)
      {
         $delete_comments->bind_param('d', $story_id);
         $delete_comments->execute();
         $delete_comments->close();

         $delete_story->bind_param('ds', $story_id, $currentUser);
         $delete_story->execute();
         $delete_story->close();
         header("Location: WustlNews.php");
         exit;
      }
   }

   if(isset($_POST['commentToDelete']) && $registeredUser)
   {
      \WustlNews\require_valid_csrf_token($_SESSION['token'] ?? null, $_POST['token'] ?? null);
      $commentId = (int) $_POST['commentToDelete'];
      $delete_comment = $mysqli->prepare("DELETE FROM comments WHERE comment_id=? AND user=?");
      if($delete_comment)
      {
         $delete_comment->bind_param('ds', $commentId, $currentUser);
         $delete_comment->execute();
         $delete_comment->close();
         header("Location: Story.php?story=" . $story_id);
         exit;
      }
   }

   $story_stmt = $mysqli->prepare("SELECT story_id, title, category, uploaded_by_user, date_uploaded, content, url FROM stories WHERE story_id=?");
   if($story_stmt)
   {
      $story_stmt->bind_param('d', $story_id);
      $story_stmt->execute();
      $story_result = $story_stmt->get_result();
      $story = $story_result->fetch_assoc() ?: null;
      $story_stmt->close();
   }

   $comment_stmt = $mysqli->prepare("SELECT comment_id, user, time, comment_text FROM comments WHERE story = ? ORDER BY time DESC");
   if($comment_stmt)
   {
      $comment_stmt->bind_param('d', $story_id);
      $comment_stmt->execute();
      $comment_result = $comment_stmt->get_result();
      while($comment_row = $comment_result->fetch_assoc())
      {
         $comments[] = $comment_row;
      }
      $comment_stmt->close();
   }

   if($story === null)
   {
      $usingDemoData = true;
   }
}

if($usingDemoData)
{
   $story = \WustlNews\find_story(\WustlNews\demo_stories(), $story_id) ?? \WustlNews\demo_stories()[0];
   $comments = \WustlNews\demo_comments((int) $story['story_id']);
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title><?php echo \WustlNews\escape_html((string) $story['title']); ?> - WUSTL News</title>
</head>
<body>
<header class="app-header">
   <a class="brand" href="WustlNews.php">
      <span class="brand-mark">W</span>
      <span>
         <strong>WUSTL News</strong>
         <small>Story desk</small>
      </span>
   </a>
   <nav class="header-actions" aria-label="Story actions">
      <a class="button button-secondary" href="WustlNews.php">Back to Feed</a>
      <?php if($registeredUser && $currentUser === $story['uploaded_by_user'] && !$usingDemoData): ?>
         <form action="EditStory.php" method="POST">
            <input type="hidden" name="story" value="<?php echo \WustlNews\escape_html((string) $story_id); ?>">
            <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
            <button type="submit" class="button">Edit</button>
         </form>
         <form method="POST">
            <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
            <button type="submit" name="deleteStory" class="button button-danger">Delete</button>
         </form>
      <?php endif; ?>
   </nav>
</header>

<main class="article-shell">
   <article class="article-card">
      <?php if($usingDemoData): ?>
         <p class="notice">Demo mode is active. Comments and edit actions are sample-only until MySQL is configured.</p>
      <?php endif; ?>
      <?php if(isset($form_error)): ?>
         <p class="error"><?php echo \WustlNews\escape_html($form_error); ?></p>
      <?php endif; ?>

      <div class="story-meta">
         <span class="category-chip <?php echo \WustlNews\category_css_class((string) $story['category']); ?>">
            <?php echo \WustlNews\escape_html(\WustlNews\category_display_label((string) $story['category'])); ?>
         </span>
         <span><?php echo \WustlNews\reading_minutes((string) $story['content']); ?> min read</span>
      </div>
      <h1><?php echo \WustlNews\escape_html((string) $story['title']); ?></h1>
      <p class="article-byline">By <?php echo \WustlNews\escape_html((string) $story['uploaded_by_user']); ?> · <?php echo \WustlNews\escape_html((string) $story['date_uploaded']); ?></p>
      <div class="article-body">
         <?php echo nl2br(\WustlNews\escape_html((string) $story['content'])); ?>
      </div>
      <?php if($story['url'] !== null && trim((string) $story['url']) !== ''): ?>
         <p><a class="text-link" href="<?php echo \WustlNews\escape_html((string) $story['url']); ?>">Read the source</a></p>
      <?php endif; ?>
   </article>

   <section class="comments-panel" aria-label="Comments">
      <div class="section-heading">
         <h2><?php echo count($comments); ?> Comment<?php echo count($comments) === 1 ? '' : 's'; ?></h2>
      </div>

      <?php foreach($comments as $comment_row): ?>
         <article class="comment">
            <div class="comment-header">
               <strong><?php echo \WustlNews\escape_html((string) $comment_row['user']); ?></strong>
               <span><?php echo \WustlNews\escape_html((string) $comment_row['time']); ?></span>
            </div>
            <p><?php echo \WustlNews\escape_html((string) $comment_row['comment_text']); ?></p>
            <?php if($registeredUser && $currentUser === $comment_row['user'] && !$usingDemoData): ?>
               <div class="inline-actions">
                  <form action="EditComment.php" method="POST">
                     <input type="hidden" name="story" value="<?php echo \WustlNews\escape_html((string) $story_id); ?>">
                     <input type="hidden" name="comment" value="<?php echo \WustlNews\escape_html((string) $comment_row['comment_id']); ?>">
                     <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
                     <button type="submit" class="button button-secondary">Edit</button>
                  </form>
                  <form method="POST">
                     <input type="hidden" name="commentToDelete" value="<?php echo \WustlNews\escape_html((string) $comment_row['comment_id']); ?>">
                     <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
                     <button type="submit" class="button button-danger">Delete</button>
                  </form>
               </div>
            <?php endif; ?>
         </article>
      <?php endforeach; ?>

      <?php if($registeredUser && !$usingDemoData): ?>
         <form class="comment-form" method="POST">
            <label for="comment">Add a comment</label>
            <textarea id="comment" rows="5" name="comment" required></textarea>
            <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
            <button type="submit" class="button">Post Comment</button>
         </form>
      <?php else: ?>
         <div class="empty-state compact">
            <h2><?php echo $usingDemoData ? 'Demo comments are read-only' : 'Log in to join the discussion'; ?></h2>
            <p><?php echo $usingDemoData ? 'Connect MySQL to enable posting, editing, and deleting.' : 'Authenticated users can add comments to live stories.'; ?></p>
         </div>
      <?php endif; ?>
   </section>
</main>
</body>
</html>
