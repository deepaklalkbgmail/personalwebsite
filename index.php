<?php
// --- minimal PHP mail handler (same file) ---
$contact_success = null; $contact_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_token']) && $_POST['contact_token'] === 'dlkb1') {
  $honeypot = trim($_POST['website'] ?? ''); // bots fill this
  if ($honeypot === '') {
    $name = trim(strip_tags($_POST['name'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $message = trim(strip_tags($_POST['message'] ?? ''));
    if ($name && $email && $message) {
      $to = 'deepaklalkb@gmail.com';
      $subject = 'Portfolio contact from ' . $name;
      $body = "Name: $name\nEmail: $email\n\nMessage:\n$message\n\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\nUser-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '');
      $headers = 'From: noreply@' . ($_SERVER['SERVER_NAME'] ?? 'site.local') . "\r\n" . 'Reply-To: ' . $email . "\r\n";
      if (@mail($to, $subject, $body, $headers)) { $contact_success = true; }
      else { $contact_error = 'Sorry, your message could not be sent. Please try again later.'; }
    } else { $contact_error = 'Please complete all fields with a valid email.'; }
  } else { $contact_success = true; } // silently accept bots
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Deepaklal KB — Infrastructure Engineer | DevOps | Kubernetes</title>
  <meta name="description" content="Portfolio of Deepaklal KB — Infrastructure Engineer specializing in DevOps, Kubernetes, performance engineering, cloud and middleware." />
  <link rel="icon" href="/favicon.ico" />
  <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
  <meta property="og:title" content="Deepaklal KB — Infrastructure Engineer" />
  <meta property="og:description" content="DevOps • Kubernetes • Performance Engineering • Cloud" />
  <meta property="og:type" content="website" />
  <style>
  /* === base + tokens (partly minified) === */
  :root{--bg:#0b0f14;--bg-soft:#10151d;--card:#111827;--muted:#8b9bb4;--txt:#e6edf3;--accent:#6ee7ff;--accent-2:#b16fff;--ring:0 0 0 2px rgba(110,231,255,.35);--shadow:0 10px 30px rgba(0,0,0,.35);--radius:16px;--max:1200px}
  [data-theme="light"]{--bg:#f7f9fc;--bg-soft:#ffffff;--card:#ffffff;--txt:#0c1422;--muted:#506078;--accent:#005eff;--accent-2:#7a3cff;--shadow:0 10px 30px rgba(0,0,0,.08)}
  @media (prefers-color-scheme: dark){:root{--bg:#0b0f14;--bg-soft:#0e141d;--card:#111827;--txt:#e6edf3;--muted:#8b9bb4;--accent:#6ee7ff;--accent-2:#b16fff}}
  *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font:16px/1.6 ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;letter-spacing:.2px;background:var(--bg);color:var(--txt)}a{color:var(--accent);text-decoration:none}a:hover{text-decoration:underline}
  img{max-width:100%;height:auto;display:block}
  .wrap{max-width:var(--max);margin:auto;padding:0 1rem}
  .sr-only{position:absolute;width:1px;height:1px;clip:rect(0 0 0 0);overflow:hidden;white-space:nowrap}
  .chip{display:inline-block;padding:.35rem .6rem;border:1px solid rgba(255,255,255,.12);border-radius:999px;font-size:.8rem;margin:.25rem .35rem .25rem 0;color:var(--txt);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(0,0,0,.06));position:relative;cursor:help;transition:all .2s ease}
  .chip:hover{transform:scale(1.05);border-color:var(--accent);box-shadow:0 0 15px rgba(110,231,255,.3)}
  .tooltip{position:absolute;bottom:120%;left:50%;transform:translateX(-50%) scale(0);background:var(--card);border:1px solid var(--accent);border-radius:12px;padding:.7rem 1rem;font-size:.75rem;white-space:nowrap;z-index:1000;box-shadow:0 8px 25px rgba(0,0,0,.25);opacity:0;transition:all .25s cubic-bezier(0.68,-0.55,0.265,1.55);pointer-events:none}
  .tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:var(--accent)}
  .chip:hover .tooltip{opacity:1;transform:translateX(-50%) scale(1);animation:tooltipBounce .3s ease}
  @keyframes tooltipBounce{0%{transform:translateX(-50%) scale(0)}50%{transform:translateX(-50%) scale(1.1)}100%{transform:translateX(-50%) scale(1)}}
  .btn{display:inline-flex;align-items:center;gap:.55rem;padding:.8rem 1.2rem;border-radius:999px;background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;font-weight:600;border:none;box-shadow:var(--shadow);transition:all .25s ease;position:relative;overflow:hidden}
  .btn:hover{transform:translateY(-2px);text-decoration:none;box-shadow:0 8px 25px rgba(110,231,255,.4)}
  .btn.outline{background:transparent;color:var(--txt);border:2px solid rgba(110,231,255,.3);box-shadow:none}
  .btn.outline:hover{background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;border-color:transparent;transform:translateY(-2px);box-shadow:0 8px 25px rgba(110,231,255,.4)}
  .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid rgba(255,255,255,.06)}
  .pill{padding:.2rem .6rem;border-radius:999px;border:1px solid rgba(255,255,255,.15);font-size:.75rem;color:var(--muted)}
  .glow{position:relative}
  .glow:after{content:"";position:absolute;inset:-2px;border-radius:inherit;background:linear-gradient(90deg,var(--accent),var(--accent-2));filter:blur(12px);opacity:.3;z-index:-1}
  .headline-badge{display:inline-block;padding:.8rem 1.5rem;border-radius:50px;background:linear-gradient(135deg,var(--accent),var(--accent-2));margin-bottom:1rem;box-shadow:var(--shadow);transform:scale(1);transition:transform .2s ease}
  .headline-badge:hover{transform:scale(1.05)}
  .headline-title{font-size:1.1rem;font-weight:700;color:#ffffff;text-shadow:0 1px 2px rgba(0,0,0,.2);letter-spacing:.5px}
  .grid{display:grid;gap:1rem}
  .hero{padding:5rem 0 3rem;background:radial-gradient(1200px 300px at 50% -10%,rgba(110,231,255,.15),transparent 60%),radial-gradient(1000px 300px at 70% -5%,rgba(177,111,255,.15),transparent 60%)}
  /* header */
  .skip{position:absolute;left:-9999px;top:auto}.skip:focus{left:1rem;top:1rem;background:#000;color:#fff;padding:.5rem 1rem;z-index:999}
  header{position:sticky;top:0;z-index:900;background:rgba(16,21,30,.6);backdrop-filter:saturate(120%) blur(12px);border-bottom:1px solid rgba(255,255,255,.06)}
  .nav{display:flex;align-items:center;justify-content:space-between;padding:.6rem 0}
  .nav a{color:var(--txt)}
  .brand{display:flex;align-items:center;gap:.7rem}
  .brand .logo{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:var(--shadow);overflow:visible;border:2px solid rgba(110,231,255,.4)}
  .brand .logo img{width:100%;height:100%;object-fit:cover;border-radius:8px;transition:transform .3s ease}
  .brand .logo:hover img{transform:scale(3)}
  .menu{display:flex;gap:1rem;align-items:center}
  .menu a{padding:.45rem .6rem;border-radius:8px}
  .menu a:hover{background:rgba(255,255,255,.06)}
  .burger{display:inline-flex;border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:.45rem}
  .only-mobile{display:inline-flex}
  .only-desktop{display:none}
  @media(min-width:860px){.only-mobile{display:none}.only-desktop{display:inline-flex}.menu{gap:.35rem}}
  .drawer{position:fixed;inset:auto 0 0 0;background:var(--bg-soft);border-top:1px solid rgba(255,255,255,.08);padding:1rem;transform:translateY(100%);transition:transform .25s ease;z-index:950;visibility:hidden;box-shadow:0 -4px 20px rgba(0,0,0,.15)}
  body.nav-open .drawer{transform:translateY(0);visibility:visible}
  body.nav-open{overflow:hidden}
  .drawer a{display:block;padding:.8rem 0;color:var(--txt)}
  /* hero */
  .hero h1{font-size:2rem;line-height:1.2;margin:0 0 .6rem}
  .subtitle{color:var(--muted);font-size:1rem}
  .hero-cta{margin-top:1.5rem;display:flex;gap:.8rem;flex-wrap:wrap;align-items:center}
  @media(max-width:640px){.hero-cta{flex-direction:column;align-items:flex-start}.hero-cta .btn{width:100%;justify-content:center}}
  @media(min-width:860px){.hero h1{font-size:3rem}}
  .badges{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:1rem}
  .stats{grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-top:1rem}
  .stat{padding:1rem}
  .stat h3{margin:.2rem 0 0;font-size:1.1rem}

  /* skills */
  .skills{grid-template-columns:repeat(2,1fr)}
  @media(min-width:720px){.skills{grid-template-columns:repeat(2,1fr)}}
  @media(min-width:1024px){.skills{grid-template-columns:repeat(4,1fr)}}
  .skills .card{padding:1.2rem;height:100%}
  .skills h3{margin:.2rem 0 .8rem;font-size:1rem}
  .skills .card > div{display:flex;flex-wrap:wrap;gap:.3rem}
  /* timeline */
  .timeline{position:relative;padding:1rem 0}
  .timeline:before{content:"";position:absolute;left:20px;top:0;bottom:0;width:2px;background:linear-gradient(var(--accent),var(--accent-2));opacity:.4}
  .t-item{display:grid;grid-template-columns:40px 1fr;gap:1rem;align-items:start;margin:1rem 0;opacity:0;transform:translateY(10px);transition:.5s ease}
  .t-item.show{opacity:1;transform:none}
  .dot{width:14px;height:14px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-2));margin-top:.45rem;box-shadow:0 0 0 4px rgba(110,231,255,.12)}
  .t-card{background:var(--card);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:1rem;box-shadow:var(--shadow)}
  .t-title{display:flex;flex-wrap:wrap;gap:.5rem;align-items:baseline}
  .t-title h3{margin:.1rem 0;font-size:1rem}
  .t-title .where{color:var(--muted)}
  .t-title .when{margin-left:auto;color:var(--muted);font-size:.9rem}
  .t-card ul{padding-left:1.1rem;margin:.4rem 0}
  /* contact */
  form{display:grid;gap:.6rem}
  input,textarea{width:100%;padding:.8rem;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:transparent;color:var(--txt)}
  input:focus,textarea:focus{outline:none;box-shadow:var(--ring)}
  .flash{padding:.8rem 1rem;border-radius:12px;border:1px solid rgba(255,255,255,.18)}
  .flash.ok{background:rgba(25,135,84,.1)}.flash.err{background:rgba(220,53,69,.1)}
  /* footer */
  footer{margin-top:2rem;padding:2rem 0;color:var(--muted)}
  .foot{display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center}
  /* sections */
  section{padding:2.2rem 0}
  h2{font-size:1.5rem;margin:0 0 1rem}
  .section-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}
  .filters{display:flex;gap:.4rem}
  </style>
</head>
<body>
  <a class="skip" href="#content">Skip to content</a>
  <header>
    <div class="wrap nav">
      <div class="brand"><div class="logo" aria-hidden="true"><img src="DSC_4751.JPG" alt="Deepaklal KB"></div>
        <a href="#home" class="only-desktop"><strong>Deepaklal KB</strong></a>
      </div>
      <nav class="menu only-desktop" aria-label="Primary">
        <a href="#about">About</a>
        <a href="#skills">Skills</a>
        <a href="#timeline">Experience</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="menu only-mobile">
        <button id="menuToggle" class="burger" aria-label="Toggle menu">☰</button>
      </div>
      <div class="menu">
        <button id="themeToggle" class="pill" aria-label="Toggle dark mode">🌗 Theme</button>
      </div>
    </div>
    <div class="drawer" id="drawer" aria-hidden="true">
      <div class="wrap">
        <a href="#about" onclick="document.body.classList.remove('nav-open')">About</a>
        <a href="#skills" onclick="document.body.classList.remove('nav-open')">Skills</a>
        <a href="#timeline" onclick="document.body.classList.remove('nav-open')">Experience</a>
        <a href="#contact" onclick="document.body.classList.remove('nav-open')">Contact</a>
      </div>
    </div>
  </header>

  <main id="content">
    <section class="hero" id="home">
      <div class="wrap grid" style="grid-template-columns:1.1fr .9fr;align-items:center">
        <div>
          <div class="headline-badge glow">
            <span class="headline-title">IT Infrastructure Engineer • AI Generalist</span>
          </div>
          <h1>DevOps • Kubernetes • Performance Engineering</h1>
          <p class="subtitle">I design, run, and harden resilient platforms on cloud and on‑prem—shipping secure, observable, and high‑performing systems.</p>
          <div class="hero-cta">
            <a href="#contact" class="btn outline">Contact Me</a>
            <a href="DeepaklalCV.pdf" class="btn outline" download>Download CV</a>
            <a href="https://www.linkedin.com/in/deepaklalkb" class="btn outline" target="_blank" rel="noopener">LinkedIn</a>
          </div>
          <div class="badges">
            <span class="chip">Azure Admin & Security (AZ‑104/500)</span>
            <span class="chip">Led 11‑person platform team</span>
            <span class="chip">DR: On‑prem ↔ Azure</span>
          </div>
          <div class="stats grid">
            <div class="card stat">
              <h3>Project &amp; Team Management</h3>
              <ul>
                <li>Project Planning</li>
                <li>Site Inspection</li>
                <li>DR Drill Planning</li>
                <li>Communication Skills</li>
                <li>Team Leadership</li>
                <li>Latex documentation</li>
              </ul>
            </div>
            <div class="card stat">
              <h3>System &amp; Infrastructure Administration</h3>
              <ul>
                <li>System Integration</li>
                <li>K8s Administration</li>
                <li>Middleware Integration</li>
                <li>Application Servers: JBoss, WebSphere, WebLogic, Tomcat</li>
                <li>Webserver: Apache HTTPD, IHS, OHS, JBCS</li>
                <li>Load balancer: mod-jk, proxy, proxy balancer, mod cluster</li>
                <li>Database: MySQL 14.14, Oracle 12c, Oracle 19c, MSSQL, postgresql 16</li>
                <li>Cloud Solutions</li>
                <li>Cloud technologies: Azure (IaaS, PaaS, Sentinel, ASR), WHM, cPanel</li>
                <li>OS: Ubuntu (18.04, 22.04), RHEL-Opopta, Kali Linux</li>
              </ul>
            </div>
            <div class="card stat">
              <h3>Monitoring, Performance &amp; Security</h3>
              <ul>
                <li>Performance management</li>
                <li>Performance testing: JMeter, Apache benchmarking</li>
                <li>Monitoring: Elastic search, Kibana, FluentD, Grafana, Wireshark, NMON, Glowroot, Apache extended status, AppDynamics, IBM Instana, Dynatrace</li>
                <li>Java heap/thread dump analysis</li>
                <li>Security: Burp Suite, Azure Monitor, Kali Linux tools</li>
              </ul>
            </div>
            <div class="card stat">
              <h3>DevOps, Automation &amp; Development</h3>
              <ul>
                <li>Microservice Tools: Docker, Kubernetes, OpenShift</li>
                <li>Scripting Languages: Powershell, Shell</li>
                <li>DevOps: Jenkins, Gitlab, SonarQube, Trivy, Dependency Track</li>
              </ul>
            </div>
          </div>
        </div>
        <div>
          <div class="card glow"><img src="DSC_4748.jpg" alt="Portrait of Deepaklal KB"/></div>
        </div>
      </div>
    </section>

    <section id="about">
      <div class="wrap grid" style="grid-template-columns:1.1fr .9fr;align-items:start">
        <div class="card" style="padding:1rem">
          <h2>About</h2>
          <p>Infrastructure engineer with end‑to‑end ownership across middleware, cloud, and DevOps. I’ve led banking platform deployments, built Jenkins‑based DevSecOps pipelines, and implemented disaster recovery across Azure and on‑prem environments. Previously hands‑on in performance engineering and application servers; I love clean automation and measurable outcomes.</p>
          <div class="badges">
            <span class="chip">Middleware → Cloud integration</span>
            <span class="chip">Platform SRE mindset</span>
            <span class="chip">Security by default</span>
          </div>
        </div>
        <aside class="card" style="padding:1rem">
          <h2>At a glance</h2>
          <ul>
            <li>Project Leader @ i‑exceed (2019→)</li>
            <li>Azure administration, migration, DR setup</li>
            <li>OpenShift/K8s microservices pioneer</li>
            <li>Performance testing & tuning</li>
          </ul>
        </aside>
      </div>
    </section>

    <section id="skills">
      <div class="wrap">
        <div class="section-head">
          <h2>Skills</h2>
        </div>
        <div class="grid skills">
          <div class="card">
            <h3>Cloud &amp; Infrastructure</h3>
            <div>
              <span class="chip">Azure (IaaS, PaaS, Sentinel, ASR)<span class="tooltip">Microsoft cloud platform providing infrastructure and platform services</span></span>
              <span class="chip">Disaster Recovery<span class="tooltip">Business continuity strategy to restore systems after disasters</span></span>
              <span class="chip">Ubuntu • RHEL<span class="tooltip">Popular Linux distributions for enterprise server deployments</span></span>
              <span class="chip">WHM • cPanel<span class="tooltip">Web hosting management tools for server administration</span></span>
              <span class="chip">Docker<span class="tooltip">Containerization platform for application deployment and scaling</span></span>
              <span class="chip">Kubernetes • OpenShift<span class="tooltip">Container orchestration platforms for managing containerized applications</span></span>
            </div>
          </div>
          <div class="card">
            <h3>DevSecOps</h3>
            <div>
              <span class="chip">Jenkins<span class="tooltip">Self-contained Java-based program for continuous integration automation</span></span>
              <span class="chip">GitLab<span class="tooltip">DevOps platform providing Git repository and CI/CD capabilities</span></span>
              <span class="chip">SonarQube<span class="tooltip">Code quality analysis tool detecting bugs and vulnerabilities</span></span>
              <span class="chip">Trivy<span class="tooltip">Comprehensive security scanner for containers and filesystems</span></span>
              <span class="chip">Dependency Track<span class="tooltip">Component analysis platform for identifying vulnerable dependencies</span></span>
            </div>
          </div>
          <div class="card">
            <h3>Perf &amp; Observability</h3>
            <div>
              <span class="chip">JMeter<span class="tooltip">Java application for load testing and performance measurement</span></span>
              <span class="chip">Apache Benchmarking<span class="tooltip">Command-line tool for benchmarking HTTP web servers</span></span>
              <span class="chip">Elasticsearch<span class="tooltip">Distributed search and analytics engine for data storage</span></span>
              <span class="chip">Kibana<span class="tooltip">Data visualization dashboard for Elasticsearch search and analytics</span></span>
              <span class="chip">FluentD<span class="tooltip">Log collection and forwarding tool for unified logging</span></span>
              <span class="chip">Grafana<span class="tooltip">Multi-platform analytics and monitoring solution with rich dashboards</span></span>
              <span class="chip">Wireshark<span class="tooltip">Network protocol analyzer for troubleshooting and network analysis</span></span>
              <span class="chip">NMON<span class="tooltip">System performance monitoring tool for AIX and Linux</span></span>
              <span class="chip">Glowroot<span class="tooltip">Java application performance monitoring with low overhead design</span></span>
              <span class="chip">Apache Extended Status<span class="tooltip">Built-in Apache module providing detailed server status information</span></span>
              <span class="chip">AppDynamics<span class="tooltip">Application performance monitoring platform for business applications</span></span>
              <span class="chip">IBM Instana<span class="tooltip">Automated application performance monitoring with AI-powered insights</span></span>
              <span class="chip">Dynatrace<span class="tooltip">AI-powered observability platform for cloud-native environments</span></span>
            </div>
          </div>
          <div class="card">
            <h3>Middleware</h3>
            <div>
              <span class="chip">JBoss<span class="tooltip">Open-source Java EE application server for enterprise applications</span></span>
              <span class="chip">WebSphere<span class="tooltip">IBM enterprise Java application server for mission-critical workloads</span></span>
              <span class="chip">WebLogic<span class="tooltip">Oracle Java EE application server for scalable applications</span></span>
              <span class="chip">Tomcat<span class="tooltip">Lightweight Java servlet container for web application deployment</span></span>
              <span class="chip">Apache HTTPD<span class="tooltip">Open-source HTTP server for serving web content worldwide</span></span>
              <span class="chip">IHS<span class="tooltip">IBM HTTP Server based on Apache for enterprise environments</span></span>
              <span class="chip">OHS<span class="tooltip">Oracle HTTP Server optimized for Oracle application stack</span></span>
              <span class="chip">JBCS<span class="tooltip">JBoss Core Services providing enterprise web server capabilities</span></span>
              <span class="chip">mod_jk<span class="tooltip">Apache connector module for integrating with Tomcat servers</span></span>
              <span class="chip">proxy<span class="tooltip">Apache module for proxying HTTP requests to backends</span></span>
              <span class="chip">proxy balancer<span class="tooltip">Apache load balancing module for distributing traffic efficiently</span></span>
              <span class="chip">mod_cluster<span class="tooltip">JBoss load balancing solution with dynamic server discovery</span></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="experience">
      <div class="wrap">
        <div class="section-head">
          <h2>Experience & Education</h2>
          <div class="filters" role="tablist" aria-label="Timeline filter">
            <button class="pill" data-tfilter="experience" aria-selected="true">Experience</button>
            <button class="pill" data-tfilter="education" aria-selected="false">Education</button>
          </div>
        </div>
        <div class="timeline" id="timeline" aria-live="polite"></div>
      </div>
    </section>

    <section id="contact">
      <div class="wrap grid" style="grid-template-columns:1fr 1fr;align-items:start">
        <div class="card" style="padding:1rem">
          <h2>Contact</h2>
          <?php if ($contact_success): ?>
            <div class="flash ok">Thanks! Your message was sent. I’ll get back to you shortly.</div>
          <?php elseif ($contact_error): ?>
            <div class="flash err"><?php echo htmlspecialchars($contact_error); ?></div>
          <?php endif; ?>
          <form method="post" action="#contact" autocomplete="on" novalidate>
            <input type="hidden" name="contact_token" value="dlkb1" />
            <label class="sr-only" for="name">Name</label>
            <input id="name" name="name" type="text" placeholder="Your name" required>
            <label class="sr-only" for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="you@example.com" required>
            <label class="sr-only" for="message">Message</label>
            <textarea id="message" name="message" rows="6" placeholder="How can I help?" required></textarea>
            <input type="text" name="website" tabindex="-1" aria-hidden="true" class="sr-only" />
            <button class="btn" type="submit">Send message</button>
            <p class="subtitle">Or email me directly: <a href="mailto:iam@deepaklal.online">iam@deepaklal.online</a></p>
          </form>
        </div>
        <aside class="card" style="padding:1rem">
          <h2>Certifications</h2>
          <ul>
            <li>Azure Administrator Certification (AZ-104)</li>
            <li>Azure Security Engineer Certification (AZ-500)</li>
            <li>CompTIA Linux Plus Certification</li>
          </ul>
          <h2>Find me</h2>
          <p><a href="https://www.linkedin.com/in/deepaklalkb" target="_blank" rel="noopener">linkedin.com/in/deepaklalkb</a></p>
        </aside>
      </div>
    </section>
  </main>

  <footer>
    <div class="wrap foot">
      <p>© <span id="y"></span> Deepaklal KB • Built with a tiny sprinkle of jQuery‑like magic and modern CSS ✨</p>
      <p><a href="#home">Back to top ↑</a></p>
    </div>
  </footer>

  <script>
  // tiny jQuery‑like helpers (no external libs)
  const $=s=>document.querySelector(s), $$=s=>Array.from(document.querySelectorAll(s));
  // theme
  const key='theme', prefersDark=window.matchMedia('(prefers-color-scheme: dark)').matches;
  function setTheme(t){document.documentElement.dataset.theme=t;localStorage.setItem(key,t)}
  setTheme(localStorage.getItem(key)||'dark');
  $('#themeToggle').addEventListener('click',()=>setTheme(document.documentElement.dataset.theme==='dark'?'light':'dark'));
  // mobile menu
  $('#menuToggle').addEventListener('click',()=>document.body.classList.toggle('nav-open'));
  // close menu when clicking outside
  document.addEventListener('click',e=>{if(!e.target.closest('header')&&document.body.classList.contains('nav-open')){document.body.classList.remove('nav-open')}});
  // close menu with escape key
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.body.classList.contains('nav-open')){document.body.classList.remove('nav-open')}});
  // footer year
  $('#y').textContent=new Date().getFullYear();
  // dynamic timeline
  const data={
    experience:[
      {role:'Project Leader',where:'i‑exceed Technology Solutions',when:'Oct 2019 – Present',high:['Led middleware/cloud/devops/appsec team of 11','Azure administration, migration & security','Pioneered microservices with OpenShift & Kubernetes','Built Jenkins Master/Master‑Slave DevSecOps pipelines','DR setup for on‑prem and Azure']},
      {role:'Senior Analyst',where:'Mashreq Global Services',when:'Apr 2019 – Oct 2019',high:['Maintained Appzillon apps (JS/Java/Oracle)','Managed WebLogic & Oracle integration','Supported RM Mobility for bank staff']},
      {role:'Technology Consultant',where:'DXC Technologies',when:'Jul 2018 – Apr 2019',high:['Managed IPPB (JBoss/WebLogic/Linux)','System performance testing with JMeter','Led PGB project (Oracle/JavaScript/Finale/iReport)']},
      {role:'Technical Representative',where:'Saggezza India',when:'Apr 2017 – Mar 2018',high:['Finacle support & technical operations','Designed, tested and maintained apps','Troubleshot, deployed & monitored software']},
      {role:'Assistant Professor',where:'KMCT College of Engineering',when:'Sep 2014 – Nov 2015',high:['Taught computer science; mentored students','Industry collaboration & placements','Documentation and code in JS/Java/Oracle']},
      {role:'DB Admin & Programmer',where:'Quadra Software Systems',when:'Dec 2011 – Aug 2012',high:['ASP.NET application development & DB admin','NEON construction ERP project','Unit & integration testing for reliability']}
    ],
    education:[
      {role:'M.Tech — Computer Science',where:'KMCT College of Engineering (Calicut University)',when:'Jan 2014',high:['Masters focused on systems & engineering']},
      {role:'B.E. — Computer Science',where:'Dr. Pauls Engineering College (Anna University)',when:'Jan 2011',high:['Foundations in CS & engineering']}
    ]
  };
  function renderTimeline(type){const el=$('#timeline');el.innerHTML='';data[type].forEach((item,i)=>{const a=document.createElement('article');a.className='t-item';a.innerHTML=`<div class="dot" aria-hidden="true"></div><div class="t-card"><div class="t-title"><h3>${item.role}</h3><span class="where">@ ${item.where}</span><span class="when">${item.when}</span></div><ul>`+item.high.map(h=>`<li>${h}</li>`).join('')+`</ul></div>`;el.appendChild(a);});watch();}
  // reveal on scroll
  const io=new IntersectionObserver(es=>es.forEach(e=>e.target.classList.toggle('show',e.isIntersecting)),{threshold:.15});
  function watch(){ $$('.t-item').forEach(x=>io.observe(x)); }
  renderTimeline('experience');
  $$('.filters [data-tfilter]').forEach(btn=>btn.addEventListener('click',e=>{ $$('.filters [data-tfilter]').forEach(b=>b.classList.remove('active')); e.target.classList.add('active'); renderTimeline(e.target.dataset.tfilter);}));
  </script>
</body>
</html>
