<?php
require __DIR__ . '/../partials/data.php';

$BASE       = '../';
$IS_HOME    = false;
$PAGE_TITLE = 'Writing — Deepaklal KB';
$PAGE_DESC  = 'Technical articles by Deepaklal KB on Kubernetes progressive delivery, Apache HTTPD worker MPM performance management and Tomcat internals.';

require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
?>

<main id="content">
  <div class="article-head">
    <div class="wrap">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= $BASE ?>">Home</a>
        <span class="sep" aria-hidden="true">/</span>
        <span aria-current="page">Writing</span>
      </nav>
      <h1>Writing</h1>
      <p class="lede">
        Longer-form notes on the systems I run in production — orchestration, web tiers and Java
        middleware. Written from incidents, load tests and the configuration files that caused them.
      </p>
    </div>
  </div>

  <section>
    <div class="wrap">
      <div class="col-3">
        <?php foreach ($POSTS as $p): ?>
          <article class="post-card">
            <div class="post-meta">
              <span class="tag"><?= htmlspecialchars($p['tags'][0]) ?></span>
              <span aria-hidden="true">·</span>
              <time datetime="<?= htmlspecialchars($p['date']) ?>"><?= date('M Y', strtotime($p['date'])) ?></time>
              <span aria-hidden="true">·</span>
              <span><?= htmlspecialchars($p['read']) ?> read</span>
            </div>
            <h3><a href="<?= htmlspecialchars($p['slug']) ?>.php"><?= htmlspecialchars($p['title']) ?></a></h3>
            <p><?= htmlspecialchars($p['summary']) ?></p>
            <a class="read-more" href="<?= htmlspecialchars($p['slug']) ?>.php">
              Read the article <span class="arw" aria-hidden="true">&rarr;</span>
            </a>
          </article>
        <?php endforeach; ?>
      </div>

      <p><a class="back-home" href="<?= $BASE ?>"><span aria-hidden="true">&larr;</span> Back to home</a></p>
    </div>
  </section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
