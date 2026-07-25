<?php
/**
 * Expects:
 *   $BASE        relative path back to the site root ('' or '../')
 *   $PAGE_TITLE  full <title>
 *   $PAGE_DESC   meta description
 * Optional:
 *   $PAGE_TYPE   Open Graph type, defaults to "website"
 */
$BASE = $BASE ?? '';
$PAGE_TYPE = $PAGE_TYPE ?? 'website';
$ASSET_V = '2026070101'; // cache-buster; bump when css/js change
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars($PAGE_TITLE, ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($PAGE_DESC, ENT_QUOTES) ?>">
  <meta name="author" content="Deepaklal KB">
  <meta name="theme-color" content="#0a0e14" media="(prefers-color-scheme: dark)">
  <meta name="theme-color" content="#f6f8fb" media="(prefers-color-scheme: light)">

  <meta property="og:title" content="<?= htmlspecialchars($PAGE_TITLE, ENT_QUOTES) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($PAGE_DESC, ENT_QUOTES) ?>">
  <meta property="og:type" content="<?= htmlspecialchars($PAGE_TYPE, ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= $BASE ?>assets/img/portrait-512.jpg">
  <meta name="twitter:card" content="summary">

  <link rel="icon" href="<?= $BASE ?>assets/img/avatar-96.jpg" type="image/jpeg">
  <link rel="apple-touch-icon" href="<?= $BASE ?>assets/img/portrait-256.jpg">

  <link rel="preload" as="image" href="<?= $BASE ?>assets/img/portrait-512.webp" fetchpriority="high">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/site.css?v=<?= $ASSET_V ?>">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/loader.css?v=<?= $ASSET_V ?>">

  <script>
    /* Applied before first paint so the chosen theme never flashes. The `js`
       class gates every effect that JavaScript is responsible for undoing. */
    (function () {
      document.documentElement.classList.add('js');
      try {
        var saved = localStorage.getItem('theme');
        var sysLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        document.documentElement.setAttribute('data-theme', saved || (sysLight ? 'light' : 'dark'));
      } catch (e) { /* storage blocked — the dark default in the markup stands */ }
    }());
  </script>
</head>
<body>

<div id="loader" role="status" aria-live="polite" aria-label="Loading">
  <div class="boot">
    <div class="boot-frame">
      <div class="boot-bar">
        <i></i><i></i><i></i>
        <span>deepaklal.online — rollout</span>
      </div>
      <ul class="boot-log">
        <li><span class="ok">&#10003;</span><span class="txt">resolving manifest &hellip; ok</span></li>
        <li><span class="ok">&#10003;</span><span class="txt">assets pulled &nbsp;&mdash;&nbsp; cache hit</span></li>
        <li><span class="ok">&#10003;</span><span class="txt">stylesheet applied</span></li>
        <li><span class="arrow">&rarr;</span><span class="txt">rolling out ui/1.0 &hellip;</span></li>
        <li><span class="ok">&#10003;</span><span class="txt">deployment available</span></li>
      </ul>
      <div class="boot-progress"><i></i></div>
    </div>
    <div class="boot-foot">
      <span>booting portfolio<span class="cursor"></span></span>
      <span>k8s · httpd · tomcat</span>
    </div>
  </div>
</div>

<a class="skip" href="#content">Skip to content</a>
