<?php
/**
 * Expects: $BASE, $SITE and (optionally) $IS_HOME.
 * On sub-pages every in-page anchor is prefixed with $BASE so the link walks
 * back to the home page first — there is always a route home from anywhere.
 */
$BASE = $BASE ?? '';
$IS_HOME = $IS_HOME ?? false;
$H = $IS_HOME ? '' : $BASE;   // anchor prefix

$NAV = [
    ['href' => $H . '#about',      'label' => 'About'],
    ['href' => $H . '#expertise',  'label' => 'Expertise'],
    ['href' => $H . '#experience', 'label' => 'Experience'],
    ['href' => $BASE . 'blog/',    'label' => 'Writing'],
    ['href' => $H . '#contact',    'label' => 'Contact'],
];
?>
<header class="site-header">
  <div class="wrap nav">
    <a class="brand" href="<?= $IS_HOME ? '#home' : $BASE ?>" aria-label="<?= htmlspecialchars($SITE['name']) ?> — home">
      <img src="<?= $BASE ?>assets/img/avatar-96.jpg" width="34" height="34" alt="" loading="eager" decoding="async">
      <span>
        <span class="brand-name"><?= htmlspecialchars($SITE['name']) ?></span>
        <span class="brand-role"><?= htmlspecialchars($SITE['role']) ?></span>
      </span>
    </a>

    <nav class="nav-links" aria-label="Primary">
      <?php foreach ($NAV as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"><?= htmlspecialchars($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-actions">
      <button id="themeToggle" class="icon-btn" type="button" aria-label="Toggle colour theme" title="Toggle colour theme">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
          <circle cx="12" cy="12" r="4"></circle>
          <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
        </svg>
      </button>
      <button id="menuToggle" class="icon-btn" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="drawer">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
          <path d="M3 6h18M3 12h18M3 18h18"></path>
        </svg>
      </button>
    </div>
  </div>
</header>

<div class="drawer" id="drawer" aria-hidden="true">
  <div class="drawer-head">
    <a class="brand" href="<?= $IS_HOME ? '#home' : $BASE ?>">
      <img src="<?= $BASE ?>assets/img/avatar-96.jpg" width="34" height="34" alt="" decoding="async">
      <span class="brand-name"><?= htmlspecialchars($SITE['name']) ?></span>
    </a>
    <button id="menuClose" class="icon-btn" type="button" aria-label="Close menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
        <path d="M6 6l12 12M18 6L6 18"></path>
      </svg>
    </button>
  </div>
  <nav aria-label="Mobile">
    <a href="<?= $IS_HOME ? '#home' : $BASE ?>">Home</a>
    <?php foreach ($NAV as $item): ?>
      <a href="<?= htmlspecialchars($item['href']) ?>"><?= htmlspecialchars($item['label']) ?></a>
    <?php endforeach; ?>
    <a href="<?= $BASE ?>DeepaklalCV.pdf" download>Download CV</a>
  </nav>
</div>
