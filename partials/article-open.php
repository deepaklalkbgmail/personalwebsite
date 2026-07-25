<?php
/**
 * Opens a blog article. The including file must define, before requiring this:
 *   $SLUG   the post slug (must match an entry in $POSTS)
 *   $TOC    list of ['id' => ..., 'label' => ...] for the table of contents
 */
require __DIR__ . '/data.php';

$BASE    = '../';
$IS_HOME = false;

[$POST, $PREV, $NEXT] = post_context($POSTS, $SLUG);
if ($POST === null) {
    http_response_code(404);
    exit('Post not found');
}

$PAGE_TITLE = $POST['title'] . ' — Deepaklal KB';
$PAGE_DESC  = $POST['summary'];
$PAGE_TYPE  = 'article';

require __DIR__ . '/head.php';
require __DIR__ . '/header.php';
?>

<main id="content">
  <div class="article-head">
    <div class="wrap">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= $BASE ?>">Home</a>
        <span class="sep" aria-hidden="true">/</span>
        <a href="<?= $BASE ?>blog/">Writing</a>
        <span class="sep" aria-hidden="true">/</span>
        <span aria-current="page"><?= htmlspecialchars($POST['tags'][0]) ?></span>
      </nav>

      <div class="post-meta">
        <?php foreach ($POST['tags'] as $t): ?>
          <span class="tag"><?= htmlspecialchars($t) ?></span>
        <?php endforeach; ?>
        <span aria-hidden="true">·</span>
        <time datetime="<?= htmlspecialchars($POST['date']) ?>"><?= date('j F Y', strtotime($POST['date'])) ?></time>
        <span aria-hidden="true">·</span>
        <span><?= htmlspecialchars($POST['read']) ?> read</span>
      </div>

      <h1><?= htmlspecialchars($POST['title']) ?></h1>
      <p class="lede"><?= htmlspecialchars($POST['summary']) ?></p>
    </div>
  </div>

  <div class="wrap article-layout">
    <nav class="toc" aria-label="On this page">
      <div class="toc-inner">
        <h2>On this page</h2>
        <ol>
          <?php foreach ($TOC as $t): ?>
            <li><a href="#<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['label']) ?></a></li>
          <?php endforeach; ?>
        </ol>
      </div>
    </nav>

    <article class="article-body">
