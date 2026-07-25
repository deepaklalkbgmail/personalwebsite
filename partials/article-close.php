<?php
/** Closes a blog article opened by article-open.php. */
?>
      <nav class="article-nav" aria-label="More articles">
        <?php if ($PREV): ?>
          <a class="prev" href="<?= htmlspecialchars($PREV['slug']) ?>.php">
            <span class="dir">&larr; Previous</span>
            <?= htmlspecialchars($PREV['title']) ?>
          </a>
        <?php else: ?>
          <a class="prev" href="<?= $BASE ?>blog/">
            <span class="dir">&larr; Index</span>
            All articles
          </a>
        <?php endif; ?>

        <?php if ($NEXT): ?>
          <a class="next" href="<?= htmlspecialchars($NEXT['slug']) ?>.php">
            <span class="dir">Next &rarr;</span>
            <?= htmlspecialchars($NEXT['title']) ?>
          </a>
        <?php else: ?>
          <a class="next" href="<?= $BASE ?>#contact">
            <span class="dir">Next &rarr;</span>
            Get in touch
          </a>
        <?php endif; ?>
      </nav>

      <p><a class="back-home" href="<?= $BASE ?>"><span aria-hidden="true">&larr;</span> Back to home</a></p>
    </article>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
