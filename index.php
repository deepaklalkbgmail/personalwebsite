<?php
require __DIR__ . '/partials/data.php';

/* ---------------------------------------------------------------------------
   Contact form handler (unchanged behaviour, tightened validation).
   --------------------------------------------------------------------------- */
$contact_success = null;
$contact_error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['contact_token'] ?? '') === 'dlkb1') {
    $honeypot = trim($_POST['website'] ?? '');           // bots fill this in
    if ($honeypot === '') {
        $name    = trim(strip_tags($_POST['name'] ?? ''));
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $message = trim(strip_tags($_POST['message'] ?? ''));

        if ($name !== '' && $email && $message !== '') {
            $to      = 'deepaklalkb@gmail.com';
            $subject = 'Portfolio contact from ' . $name;
            $body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}\n\n"
                     . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n"
                     . 'User-Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $headers = 'From: noreply@' . ($_SERVER['SERVER_NAME'] ?? 'site.local') . "\r\n"
                     . 'Reply-To: ' . $email . "\r\n";

            if (@mail($to, $subject, $body, $headers)) {
                $contact_success = true;
            } else {
                $contact_error = 'Sorry, your message could not be sent. Please email me directly instead.';
            }
        } else {
            $contact_error = 'Please complete every field with a valid email address.';
        }
    } else {
        $contact_success = true;                          // silently accept bots
    }
}

$BASE       = '';
$IS_HOME    = true;
$PAGE_TITLE = 'Deepaklal KB — IT Infrastructure Engineer | DevOps, Kubernetes, Performance';
$PAGE_DESC  = 'Deepaklal KB is an IT infrastructure engineer working across Kubernetes, Azure, Apache HTTPD and Java middleware — building resilient, observable, high-performance platforms for banking workloads.';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>

<main id="content">

  <!-- ===================== hero ===================== -->
  <section class="hero" id="home">
    <div class="wrap hero-inner">
      <div>
        <p class="eyebrow">Infrastructure &amp; Platform Engineering</p>
        <h1>Resilient platforms for <span class="accent">production banking</span> workloads.</h1>
        <p class="hero-lede">
          I design, run and harden infrastructure across Kubernetes, Azure and Java middleware —
          from disaster-recovery topology and DevSecOps pipelines down to the thread pools that
          decide whether a release holds under load.
        </p>

        <div class="hero-cta">
          <a class="btn btn-primary" href="#contact">Get in touch</a>
          <a class="btn btn-ghost" href="DeepaklalCV.pdf" download>Download CV</a>
          <a class="btn btn-ghost" href="<?= htmlspecialchars($SITE['linkedin']) ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        </div>

        <div class="chips">
          <span class="chip chip--accent">Kubernetes / OpenShift</span>
          <span class="chip">Azure AZ-104 · AZ-500</span>
          <span class="chip">Apache HTTPD &amp; Tomcat</span>
          <span class="chip">DR: on-prem &harr; Azure</span>
        </div>
      </div>

      <figure class="portrait">
        <picture>
          <source srcset="assets/img/portrait-512.webp" type="image/webp">
          <img src="assets/img/portrait-512.jpg" width="512" height="512"
               alt="Portrait of Deepaklal KB" fetchpriority="high" decoding="async">
        </picture>
        <figcaption class="portrait-badge">
          <span class="status-dot" aria-hidden="true"></span> Open to platform roles
        </figcaption>
      </figure>
    </div>

    <div class="wrap" style="margin-top:2.5rem">
      <div class="metrics">
        <?php foreach ($METRICS as $m): ?>
          <div class="metric">
            <b><?= htmlspecialchars($m['value']) ?></b>
            <span><?= htmlspecialchars($m['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===================== about ===================== -->
  <section id="about">
    <div class="wrap">
      <div class="section-head">
        <p class="eyebrow">About</p>
        <h2>Middleware roots, platform scope</h2>
      </div>

      <div class="two-col">
        <div class="card">
          <p>
            I started in application servers and performance engineering — JBoss, WebLogic, WebSphere,
            Tomcat and Apache HTTPD — where a badly sized thread pool is the difference between a quiet
            release night and a war room. That grounding still shapes how I approach platforms: measure
            first, model the queue, then change one thing.
          </p>
          <p>
            Today I lead infrastructure delivery for core banking implementations, covering Azure
            administration and migration, Kubernetes and OpenShift adoption, Jenkins-based DevSecOps
            pipelines with quality and vulnerability gates, and disaster recovery across on-premises and
            cloud estates. I have led an eleven-person platform team through implementations for banks
            across India and the Middle East.
          </p>
          <p style="margin-bottom:0">
            The work I enjoy most sits where automation meets evidence: pipelines that fail for the right
            reasons, dashboards that answer a question, and rollouts that can be reversed in seconds.
          </p>
        </div>

        <aside class="card">
          <h3 style="font-size:1rem">Certifications</h3>
          <ul style="color:var(--txt-dim);font-size:.9rem">
            <?php foreach ($CERTIFICATIONS as $c): ?>
              <li><?= htmlspecialchars($c) ?></li>
            <?php endforeach; ?>
          </ul>

          <h3 style="font-size:1rem;margin-top:1.25rem">Languages</h3>
          <div class="chips">
            <span class="chip">English</span>
            <span class="chip">Malayalam</span>
            <span class="chip">Hindi</span>
            <span class="chip">Tamil — spoken</span>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <!-- ===================== expertise ===================== -->
  <section id="expertise" style="background:var(--bg-elev);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
    <div class="wrap">
      <div class="section-head">
        <p class="eyebrow">Expertise</p>
        <h2>Four areas I own end to end</h2>
        <p>From the operating system up to the delivery pipeline.</p>
      </div>

      <div class="col-4">
        <?php foreach ($DOMAINS as $d): ?>
          <article class="card domain-card">
            <h3><span class="num"><?= htmlspecialchars($d['num']) ?></span><?= htmlspecialchars($d['title']) ?></h3>
            <ul>
              <?php foreach ($d['items'] as $i): ?>
                <li><?= htmlspecialchars($i) ?></li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===================== writing ===================== -->
  <section id="writing">
    <div class="wrap">
      <div class="section-head">
        <p class="eyebrow">Writing</p>
        <h2>Notes from production</h2>
        <p>Longer pieces on the systems I work with day to day.</p>
      </div>

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
            <h3><a href="blog/<?= htmlspecialchars($p['slug']) ?>.php"><?= htmlspecialchars($p['title']) ?></a></h3>
            <p><?= htmlspecialchars($p['summary']) ?></p>
            <a class="read-more" href="blog/<?= htmlspecialchars($p['slug']) ?>.php">
              Read the article <span class="arw" aria-hidden="true">&rarr;</span>
            </a>
          </article>
        <?php endforeach; ?>
      </div>

      <p style="margin-top:1.5rem"><a class="read-more" href="blog/">All writing <span class="arw" aria-hidden="true">&rarr;</span></a></p>
    </div>
  </section>

  <!-- ===================== experience ===================== -->
  <section id="experience" style="background:var(--bg-elev);border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
    <div class="wrap">
      <div class="section-head">
        <p class="eyebrow">Track record</p>
        <h2>Experience &amp; education</h2>
      </div>

      <div class="tl-filters" role="tablist" aria-label="Timeline filter">
        <button type="button" data-tl-filter="experience" role="tab" aria-selected="true">Experience</button>
        <button type="button" data-tl-filter="education" role="tab" aria-selected="false">Education</button>
      </div>

      <?php
      $panels = ['experience' => $EXPERIENCE, 'education' => $EDUCATION];
      foreach ($panels as $key => $rows): ?>
        <div class="timeline" data-tl-panel="<?= $key ?>">
          <?php foreach ($rows as $r): ?>
            <article class="tl-item">
              <div class="tl-card">
                <h3><?= htmlspecialchars($r['role']) ?></h3>
                <div class="tl-where"><?= htmlspecialchars($r['where']) ?></div>
                <span class="tl-when"><?= htmlspecialchars($r['when']) ?></span>
                <ul>
                  <?php foreach ($r['high'] as $h): ?>
                    <li><?= htmlspecialchars($h) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===================== contact ===================== -->
  <section id="contact">
    <div class="wrap">
      <div class="section-head">
        <p class="eyebrow">Contact</p>
        <h2>Let's talk about your platform</h2>
        <p>Consulting, platform reviews, performance investigations or a full-time role.</p>
      </div>

      <div class="two-col">
        <div class="card">
          <?php if ($contact_success): ?>
            <div class="notice ok">Thanks — your message was sent. I'll get back to you shortly.</div>
          <?php elseif ($contact_error): ?>
            <div class="notice err"><?= htmlspecialchars($contact_error) ?></div>
          <?php endif; ?>

          <form method="post" action="#contact" autocomplete="on">
            <input type="hidden" name="contact_token" value="dlkb1">

            <div class="field">
              <label for="name">Name</label>
              <input id="name" name="name" type="text" autocomplete="name" placeholder="Your name" required>
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="you@company.com" required>
            </div>
            <div class="field">
              <label for="message">Message</label>
              <textarea id="message" name="message" rows="6" placeholder="What are you working on?" required></textarea>
            </div>

            <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="sr-only">
            <button class="btn btn-primary" type="submit">Send message</button>
          </form>
        </div>

        <aside class="card">
          <h3 style="font-size:1rem">Direct</h3>
          <ul class="contact-list">
            <li><span class="k">Email</span><span class="v"><a href="mailto:<?= htmlspecialchars($SITE['email']) ?>"><?= htmlspecialchars($SITE['email']) ?></a></span></li>
            <li><span class="k">LinkedIn</span><span class="v"><a href="<?= htmlspecialchars($SITE['linkedin']) ?>" target="_blank" rel="noopener noreferrer">deepaklalkb</a></span></li>
            <li><span class="k">CV</span><span class="v"><a href="DeepaklalCV.pdf" download>Download PDF</a></span></li>
            <li><span class="k">Based in</span><span class="v"><?= htmlspecialchars($SITE['location']) ?></span></li>
          </ul>
        </aside>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
