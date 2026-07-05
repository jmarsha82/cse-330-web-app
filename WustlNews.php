<?php
require 'database.php';
require 'src/NewsHelpers.php';

session_start();

$currentUser = $_SESSION['username'] ?? null;
$filter_category = \WustlNews\normalize_category($_GET['category'] ?? null);
$stories = [];
$usingDemoData = $mysqli === null;

if(!$usingDemoData)
{
   if($filter_category === 'All')
   {
      $story_stmt = $mysqli->prepare("SELECT story_id, title, category, uploaded_by_user, date_uploaded, content, url FROM stories ORDER BY date_uploaded DESC");
   }
   else
   {
      $story_stmt = $mysqli->prepare("SELECT story_id, title, category, uploaded_by_user, date_uploaded, content, url FROM stories WHERE category=? ORDER BY date_uploaded DESC");
      if($story_stmt)
      {
         $story_stmt->bind_param('s', $filter_category);
      }
   }

   if(!$story_stmt)
   {
      $usingDemoData = true;
   }
   else
   {
      $story_stmt->execute();
      $story_result = $story_stmt->get_result();
      while($row = $story_result->fetch_assoc())
      {
         $comment_stmt = $mysqli->prepare("SELECT COUNT(comment_id) FROM comments WHERE story = ?");
         $row['comment_count'] = 0;
         if($comment_stmt)
         {
            $comment_stmt->bind_param('d', $row['story_id']);
            $comment_stmt->execute();
            $comment_stmt->bind_result($db_cmt_count);
            if($comment_stmt->fetch())
            {
               $row['comment_count'] = (int) $db_cmt_count;
            }
            $comment_stmt->close();
         }
         $stories[] = $row;
      }
      $story_stmt->close();

      if(count($stories) === 0)
      {
         $usingDemoData = true;
      }
   }
}

if($usingDemoData)
{
   $stories = \WustlNews\filter_stories_by_category(\WustlNews\demo_stories(), $filter_category);
}

$featuredStory = $stories[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="stylesheet" type="text/css" href="WustlNewsFormat.css">
   <title>WUSTL News</title>
</head>
<body>
<header class="app-header">
   <a class="brand" href="WustlNews.php" aria-label="WUSTL News home">
      <span class="brand-mark">W</span>
      <span>
         <strong>WUSTL News</strong>
         <small>Campus reporting desk</small>
      </span>
   </a>
   <nav class="header-actions" aria-label="Account actions">
      <?php if($currentUser !== null): ?>
         <span class="welcome">Welcome, <?php echo \WustlNews\escape_html($currentUser); ?></span>
         <form method="GET" action="ViewProfile.php">
            <input type="hidden" name="user" value="<?php echo \WustlNews\escape_html($currentUser); ?>">
            <button type="submit" class="button button-secondary">Profile</button>
         </form>
         <form method="POST" action="UploadStory.php">
            <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
            <button type="submit" class="button">New Story</button>
         </form>
         <form method="POST" action="WustlNewsLogin.php">
            <button type="submit" name="logout" class="button button-ghost">Log Out</button>
         </form>
      <?php else: ?>
         <form method="POST" action="WustlNewsLogin.php">
            <button type="submit" class="button">Login</button>
         </form>
      <?php endif; ?>
   </nav>
</header>

<main class="app-shell">
   <section class="news-main" aria-label="Campus news feed">
      <div class="page-intro">
         <div>
            <h1>Today on campus</h1>
            <p>Student stories, policy updates, arts coverage, and local notes in one fast feed.</p>
         </div>
         <?php if($usingDemoData): ?>
            <p class="notice">Demo mode is active. Configure MySQL to publish live stories.</p>
         <?php endif; ?>
      </div>

      <form class="category-tabs" method="GET" aria-label="Filter by category">
         <?php foreach(array_merge(['All'], \WustlNews\CATEGORIES) as $category): ?>
            <?php $selected = $filter_category === $category; ?>
            <button
               type="submit"
               name="category"
               value="<?php echo \WustlNews\escape_html($category); ?>"
               class="category-tab <?php echo $selected ? 'is-active ' : ''; ?><?php echo \WustlNews\category_css_class($category); ?>"
            >
               <?php echo \WustlNews\escape_html(\WustlNews\category_display_label($category)); ?>
            </button>
         <?php endforeach; ?>
      </form>

      <?php if($featuredStory !== null): ?>
         <article class="featured-story">
            <div class="story-meta">
               <span class="category-chip <?php echo \WustlNews\category_css_class((string) $featuredStory['category']); ?>">
                  <?php echo \WustlNews\escape_html(\WustlNews\category_display_label((string) $featuredStory['category'])); ?>
               </span>
               <span><?php echo \WustlNews\reading_minutes((string) $featuredStory['content']); ?> min read</span>
            </div>
            <h2><?php echo \WustlNews\escape_html((string) $featuredStory['title']); ?></h2>
            <p><?php echo \WustlNews\escape_html(\WustlNews\excerpt((string) $featuredStory['content'], 280)); ?></p>
            <div class="story-footer">
               <span>By <?php echo \WustlNews\escape_html((string) $featuredStory['uploaded_by_user']); ?> · <?php echo \WustlNews\escape_html((string) $featuredStory['date_uploaded']); ?></span>
               <a class="text-link" href="Story.php?story=<?php echo \WustlNews\escape_html((string) $featuredStory['story_id']); ?>">Read story</a>
            </div>
         </article>
      <?php endif; ?>

      <section class="story-list" aria-label="Latest stories">
         <?php foreach(array_slice($stories, $featuredStory === null ? 0 : 1) as $row): ?>
            <article class="story-row">
               <div class="story-row-accent <?php echo \WustlNews\category_css_class((string) $row['category']); ?>"></div>
               <div>
                  <div class="story-meta">
                     <span><?php echo \WustlNews\escape_html(\WustlNews\category_display_label((string) $row['category'])); ?></span>
                     <span><?php echo \WustlNews\reading_minutes((string) $row['content']); ?> min read</span>
                     <span><?php echo (int) ($row['comment_count'] ?? 0); ?> comments</span>
                  </div>
                  <h3><a href="Story.php?story=<?php echo \WustlNews\escape_html((string) $row['story_id']); ?>"><?php echo \WustlNews\escape_html((string) $row['title']); ?></a></h3>
                  <p><?php echo \WustlNews\escape_html(\WustlNews\excerpt((string) $row['content'])); ?></p>
                  <div class="story-footer">
                     <span>By <?php echo \WustlNews\escape_html((string) $row['uploaded_by_user']); ?> · <?php echo \WustlNews\escape_html((string) $row['date_uploaded']); ?></span>
                     <?php if($row['url'] !== null && trim((string) $row['url']) !== ''): ?>
                        <a class="text-link" href="<?php echo \WustlNews\escape_html((string) $row['url']); ?>">Source</a>
                     <?php endif; ?>
                  </div>
               </div>
            </article>
         <?php endforeach; ?>

         <?php if(count($stories) === 0): ?>
            <div class="empty-state">
               <h2>No stories found</h2>
               <p>Try the All filter or connect a database with stories for this category.</p>
            </div>
         <?php endif; ?>
      </section>
   </section>

   <aside class="sidebar" aria-label="News sidebar">
      <section>
         <h2>Desk Briefing</h2>
         <p class="sidebar-stat">5 desks · <?php echo count($stories); ?> visible stories</p>
         <p>Coverage is organized for quick scans: policy, sports, arts, world notes, and campus tech.</p>
      </section>
      <section>
         <h2>Trending Topics</h2>
         <ul class="topic-list">
            <li>Study spaces</li>
            <li>Transit funding</li>
            <li>Fall arts calendar</li>
            <li>International orientation</li>
         </ul>
      </section>
      <section>
         <h2>Contributor Tools</h2>
         <p>Log in to post stories, comment, and manage your own reporting.</p>
         <form method="POST" action="<?php echo $currentUser === null ? 'WustlNewsLogin.php' : 'UploadStory.php'; ?>">
            <?php if($currentUser !== null): ?>
               <input type="hidden" name="token" value="<?php echo \WustlNews\escape_html($_SESSION['token'] ?? ''); ?>">
            <?php endif; ?>
            <button type="submit" class="button button-full"><?php echo $currentUser === null ? 'Login to contribute' : 'Upload a story'; ?></button>
         </form>
      </section>
   </aside>
</main>
</body>
</html>
