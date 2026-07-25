<?php
$BASE = $BASE ?? '';
$IS_HOME = $IS_HOME ?? false;
$H = $IS_HOME ? '' : $BASE;
$ASSET_V = $ASSET_V ?? '2026070101';
?>
<footer class="site-footer">
  <div class="wrap foot-inner">
    <p style="margin:0">&copy; <span data-year>2026</span> <?= htmlspecialchars($SITE['name']) ?> &middot; <?= htmlspecialchars($SITE['tagline']) ?></p>
    <nav class="foot-links" aria-label="Footer">
      <a href="<?= $IS_HOME ? '#home' : $BASE ?>">Home</a>
      <a href="<?= $BASE ?>blog/">Writing</a>
      <a href="<?= $H ?>#contact">Contact</a>
      <a href="<?= htmlspecialchars($SITE['linkedin']) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
    </nav>
  </div>
</footer>

<script src="<?= $BASE ?>assets/js/site.js?v=<?= $ASSET_V ?>" defer></script>
</body>
</html>
