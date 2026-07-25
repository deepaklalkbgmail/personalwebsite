<?php
$SLUG = 'apache-httpd-worker-mpm-performance';
$TOC = [
    ['id' => 'mpm',        'label' => 'What an MPM actually is'],
    ['id' => 'worker',     'label' => 'How worker allocates threads'],
    ['id' => 'directives', 'label' => 'The directives that matter'],
    ['id' => 'sizing',     'label' => 'Sizing from measurement'],
    ['id' => 'keepalive',  'label' => 'Keep-alive: the hidden multiplier'],
    ['id' => 'scoreboard', 'label' => 'Reading the scoreboard'],
    ['id' => 'backend',    'label' => 'Aligning with the backend'],
    ['id' => 'os',         'label' => 'Operating system limits'],
    ['id' => 'symptoms',   'label' => 'Symptoms and their causes'],
    ['id' => 'method',     'label' => 'A tuning method that works'],
];
require __DIR__ . '/../partials/article-open.php';
?>

<h2 id="mpm">What an MPM actually is</h2>

<p>
  Apache HTTPD does not have one concurrency model — it has a pluggable one. The Multi-Processing
  Module decides how the server accepts connections and how it maps them onto operating system
  processes and threads. Everything else in an Apache performance conversation depends on which MPM
  is loaded, which is why the first question is always <code>httpd -V</code>.
</p>

<pre><code>$ httpd -V | grep -i mpm
Server MPM:     worker
  threaded:     yes (fixed thread count)
    forked:     yes (variable process count)

<span class="c"># On Debian/Ubuntu the module is switched, not compiled:</span>
$ a2dismod mpm_prefork &amp;&amp; a2enmod mpm_worker &amp;&amp; systemctl restart apache2</code></pre>

<div class="table-scroll">
  <table>
    <thead>
      <tr><th>MPM</th><th>Model</th><th>Memory per connection</th><th>Constraint</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>prefork</td>
        <td>One process per connection, no threads</td>
        <td>High — a full process</td>
        <td>Needed for non-thread-safe modules such as classic mod_php</td>
      </tr>
      <tr>
        <td>worker</td>
        <td>Processes × fixed threads; one thread per connection</td>
        <td>Moderate — a thread stack</td>
        <td>Idle keep-alive connections still hold a thread</td>
      </tr>
      <tr>
        <td>event</td>
        <td>worker, plus a listener thread that parks idle keep-alive sockets</td>
        <td>Low for idle connections</td>
        <td>Falls back to worker behaviour for some workloads</td>
      </tr>
    </tbody>
  </table>
</div>

<p>
  worker is the right default for a reverse proxy or static-content tier in front of an application
  server, which is the shape most Java estates take: HTTPD or IHS terminating TLS and proxying to
  Tomcat, JBoss or WebLogic over <code>mod_proxy</code>, <code>mod_jk</code> or
  <code>mod_cluster</code>. That is the deployment this article assumes.
</p>

<h2 id="worker">How worker allocates threads</h2>

<p>
  A worker-MPM Apache is a small hierarchy. One parent process, running as root, does nothing but
  bind the listening sockets and supervise children. It forks child processes, each of which creates
  a fixed number of worker threads plus one listener thread. The listener accepts a connection and
  hands it to an idle worker thread in the same process; that thread owns the connection until it
  closes.
</p>

<pre><code>httpd (parent, root)
 |
 +-- child 1 ── listener thread
 |               ├── worker thread 1 ─── connection ─── request ─── response
 |               ├── worker thread 2 ─── connection (keep-alive, idle)
 |               └── ... ThreadsPerChild threads
 |
 +-- child 2 ── listener thread
 |               └── ... ThreadsPerChild threads
 |
 +-- child N   (N varies between StartServers and ServerLimit)</code></pre>

<p>
  Two consequences follow directly from that picture, and both are the source of most worker-MPM
  misconfiguration:
</p>

<ul>
  <li>
    <strong>The thread count per child is fixed at startup.</strong> Apache scales concurrency by
    forking or reaping whole <em>processes</em>, never by adding threads to an existing one. So
    capacity moves in units of <code>ThreadsPerChild</code>.
  </li>
  <li>
    <strong>A thread is occupied for the entire connection, not the request.</strong> Under worker, a
    client sitting on an idle keep-alive connection consumes a worker thread that could be serving
    someone else. This is precisely the problem the event MPM was built to solve.
  </li>
</ul>

<h2 id="directives">The directives that matter</h2>

<pre><code>&lt;IfModule mpm_worker_module&gt;
    ServerLimit              16     <span class="c"># hard ceiling on child processes</span>
    ThreadLimit              64     <span class="c"># hard ceiling on ThreadsPerChild</span>
    ThreadsPerChild          64     <span class="c"># threads created in every child</span>
    StartServers              4     <span class="c"># children forked at startup</span>
    MinSpareThreads         128     <span class="c"># fork if idle threads fall below this</span>
    MaxSpareThreads         384     <span class="c"># reap if idle threads exceed this</span>
    MaxRequestWorkers      1024     <span class="c"># total concurrent connections served</span>
    MaxConnectionsPerChild    0     <span class="c"># 0 = never recycle a child</span>
&lt;/IfModule&gt;</code></pre>

<p>
  The relationship that governs everything is a single identity, and Apache will silently clamp your
  configuration if you break it:
</p>

<div class="callout">
  <p>
    <b>MaxRequestWorkers = ServerLimit × ThreadsPerChild</b><br>
    Set <code>MaxRequestWorkers</code> higher than that product and Apache reduces it to the product
    at startup, logging a warning most people never read. Set <code>ThreadsPerChild</code> above
    <code>ThreadLimit</code>, or <code>ServerLimit</code> above what the binary was built for, and it
    is clamped the same way.
  </p>
</div>

<p>
  The remaining directives are worth understanding individually:
</p>

<ul>
  <li>
    <strong><code>ThreadLimit</code> and <code>ServerLimit</code></strong> are ceilings evaluated once
    at startup and are not changeable on a graceful restart. Raising them requires a full stop and
    start, which is a genuine operational fact to plan around. Do not set them absurdly high "just in
    case" — the scoreboard is allocated from them and costs shared memory.
  </li>
  <li>
    <strong><code>ThreadsPerChild</code></strong> controls the granularity of scaling. Too low (say 8)
    and Apache forks a large number of processes, each with its own memory overhead and its own
    connection pool to the backend. Too high (say 250) and a single child crash takes a large slice
    of your capacity with it. 25 to 64 is the practical range for a proxy tier.
  </li>
  <li>
    <strong><code>MinSpareThreads</code> / <code>MaxSpareThreads</code></strong> drive the fork and
    reap decisions. Because a fork creates <code>ThreadsPerChild</code> threads at once, a
    <code>MinSpareThreads</code> smaller than <code>ThreadsPerChild</code> causes the server to
    oscillate — fork, overshoot the max spare, reap, dip below the min spare, fork again. Keep
    <code>MinSpareThreads</code> at roughly two children's worth and
    <code>MaxSpareThreads</code> comfortably above it.
  </li>
  <li>
    <strong><code>MaxConnectionsPerChild</code></strong> recycles a child after N connections. Zero is
    correct for a clean stack; a modest value (10000–50000) is a pragmatic mitigation if a third-party
    module leaks. It is a workaround, not a tuning parameter — recycling costs you a fork and cold
    backend connections.
  </li>
  <li>
    <strong><code>ListenBacklog</code></strong> sets the accept queue depth. It is capped by the
    kernel's <code>net.core.somaxconn</code>, so raising one without the other achieves nothing.
  </li>
</ul>

<h2 id="sizing">Sizing from measurement</h2>

<p>
  <code>MaxRequestWorkers</code> is the single most consequential number in the file, and the two
  common ways of choosing it are both wrong. Copying a value from a blog ignores your workload;
  setting it very high "so we never queue" converts a manageable queue into a memory exhaustion event
  and an unresponsive server.
</p>

<p>
  Derive it instead. Little's Law gives the concurrency a system needs:
</p>

<pre><code>concurrency = arrival rate × average residence time

<span class="c"># Example: 800 requests/sec, average 120 ms served end to end</span>
concurrency = 800 × 0.120 = 96 concurrent requests in flight</code></pre>

<p>
  Ninety-six is the steady-state requirement. Now apply headroom for burst and for backend slowdown —
  the case you actually care about, because that is when residence time balloons. A factor of two to
  three is normal, so roughly 200–300 workers. Then check it against memory, which is the hard limit:
</p>

<pre><code><span class="c"># Resident memory per httpd child, in MB</span>
$ ps -ylC httpd --sort:rss | awk '/httpd/ {sum+=$8; n++} END {print sum/n/1024" MB avg"}'

<span class="c"># Budget: (RAM available to httpd) / (memory per child) = affordable ServerLimit</span>
<span class="c"># 8 GB for httpd, 90 MB per child  ->  ~88 children</span>
<span class="c"># With ThreadsPerChild 64 that is far more than 300 workers, so memory is not</span>
<span class="c"># the binding constraint here — the backend is.</span></code></pre>

<p>
  For a proxy tier the binding constraint is almost never Apache itself. It is the application server
  behind it, and that leads to the most important sizing rule in this article:
</p>

<div class="callout">
  <p>
    <b>Apache should not be able to send the backend more concurrent work than the backend can
    handle.</b> If Tomcat has 200 request-processing threads and Apache has 1024 workers, then under
    load Apache accepts 1024 connections, forwards them, and 824 of them queue inside Tomcat where
    you have no visibility and no ability to shed them. Apache is meant to be the place where excess
    load is visible and controllable.
  </p>
</div>

<h2 id="keepalive">Keep-alive: the hidden multiplier</h2>

<p>
  Under worker, an idle keep-alive connection holds a worker thread. That makes
  <code>KeepAliveTimeout</code> a direct multiplier on your capacity requirement.
</p>

<pre><code>KeepAlive              On
MaxKeepAliveRequests   100
KeepAliveTimeout       3      <span class="c"># seconds — not the 15 in many default files</span></code></pre>

<p>
  The arithmetic is unforgiving. At 500 new connections per second with a 15-second timeout, up to
  7500 connections can be sitting idle but attached. No realistic <code>MaxRequestWorkers</code>
  covers that, so the server stops accepting new work while most of its threads are doing nothing.
</p>

<p>
  Two to five seconds is the right range for a public-facing worker-MPM tier: long enough to reuse a
  connection across the assets of a single page view, short enough that abandoned connections release
  quickly. If your traffic genuinely benefits from long keep-alive — many small requests from a small
  number of clients — that is the argument for switching to the event MPM, where idle connections are
  handed back to a listener thread instead of pinning a worker.
</p>

<h2 id="scoreboard">Reading the scoreboard</h2>

<p>
  <code>mod_status</code> with <code>ExtendedStatus</code> is the instrument panel. Without it you are
  tuning blind.
</p>

<pre><code>LoadModule status_module modules/mod_status.so
ExtendedStatus On

&lt;Location "/server-status"&gt;
    SetHandler server-status
    Require ip 10.0.0.0/8            <span class="c"># never expose this publicly</span>
&lt;/Location&gt;</code></pre>

<pre><code>$ curl -s http://localhost/server-status?auto
BusyWorkers: 187
IdleWorkers: 13
Scoreboard: WWWWWKKKWW_WRWWWCWWWW.........</code></pre>

<p>
  The scoreboard string is one character per worker slot, and the distribution tells you where time is
  going:
</p>

<div class="table-scroll">
  <table>
    <thead><tr><th>Char</th><th>State</th><th>What a high count means</th></tr></thead>
    <tbody>
      <tr><td><code>_</code></td><td>Waiting for connection</td><td>Healthy idle capacity</td></tr>
      <tr><td><code>W</code></td><td>Sending reply</td><td>Normal — unless persistently near the cap, which means the backend is slow</td></tr>
      <tr><td><code>K</code></td><td>Keep-alive (idle)</td><td>KeepAliveTimeout is too long for your connection rate</td></tr>
      <tr><td><code>R</code></td><td>Reading request</td><td>Slow clients or a slowloris-style attack; see mod_reqtimeout</td></tr>
      <tr><td><code>C</code></td><td>Closing connection</td><td>Normal in small numbers</td></tr>
      <tr><td><code>G</code></td><td>Gracefully finishing</td><td>A reload or MaxConnectionsPerChild recycling is in progress</td></tr>
      <tr><td><code>.</code></td><td>Open slot</td><td>Room to fork more children</td></tr>
    </tbody>
  </table>
</div>

<p>
  The decisive signal is <code>BusyWorkers</code> pinned at <code>MaxRequestWorkers</code> with the
  scoreboard full of <code>W</code>. That is not an Apache capacity problem — it is Apache waiting on
  something downstream, and raising <code>MaxRequestWorkers</code> will make it worse by pushing more
  concurrent work into an already saturated backend. Scrape this endpoint into Prometheus or your APM
  and alert on the ratio, not the absolute.
</p>

<h2 id="backend">Aligning with the backend</h2>

<p>
  In a proxied architecture, the numbers on both sides of the connector have to be chosen together.
  With <code>mod_proxy</code>, the pool is per child process, which is the detail that catches people
  out:
</p>

<pre><code>&lt;Proxy "balancer://app"&gt;
    BalancerMember "http://10.0.2.11:8080" \
        min=4 max=64 smax=32 ttl=60 retry=30 \
        connectiontimeout=3 timeout=30
    BalancerMember "http://10.0.2.12:8080" \
        min=4 max=64 smax=32 ttl=60 retry=30 \
        connectiontimeout=3 timeout=30
&lt;/Proxy&gt;

ProxyPass        "/app" "balancer://app/app" stickysession=JSESSIONID
ProxyPassReverse "/app" "balancer://app/app"</code></pre>

<div class="callout">
  <p>
    <b>Total backend connections = number of child processes × <code>max</code>.</b> With
    <code>ServerLimit 16</code> and <code>max=64</code>, Apache can open 1024 connections to each
    backend member. Set <code>max</code> to <code>ThreadsPerChild</code> — a child can never need more
    backend connections than it has worker threads — and let <code>ServerLimit</code> do the
    multiplication.
  </p>
</div>

<p>
  <code>connectiontimeout</code> and <code>timeout</code> deserve explicit values. Defaults inherited
  from <code>Timeout</code> are usually far too long, so a backend that has stopped responding holds
  Apache threads for the full duration instead of failing fast and letting the balancer retry
  elsewhere. A short <code>connectiontimeout</code> (2–3 s) with a <code>timeout</code> matched to
  your slowest legitimate request is the shape you want.
</p>

<p>
  The same alignment applies to <code>mod_jk</code>, where <code>connection_pool_size</code> is
  likewise per child process and should be set to <code>ThreadsPerChild</code>, with
  <code>connection_pool_timeout</code> matched to Tomcat's <code>connectionTimeout</code> so neither
  side is holding half-dead sockets the other has already discarded.
</p>

<h2 id="os">Operating system limits</h2>

<p>
  A well-tuned Apache still fails if the kernel and the process limits are not raised alongside it.
  Each connection is a file descriptor, and each proxied connection is two.
</p>

<pre><code><span class="c"># File descriptors — the most common hard stop</span>
$ cat /proc/$(pgrep -o httpd)/limits | grep 'open files'
<span class="c"># systemd unit override:</span>
[Service]
LimitNOFILE=65535

<span class="c"># Accept queue: must be raised in the kernel AND in httpd.conf</span>
$ sysctl -w net.core.somaxconn=4096
$ sysctl -w net.ipv4.tcp_max_syn_backlog=8192
ListenBacklog 4096

<span class="c"># Ephemeral ports for outbound proxy connections</span>
$ sysctl -w net.ipv4.ip_local_port_range="10240 65535"
$ sysctl -w net.ipv4.tcp_fin_timeout=15</code></pre>

<p>
  Two quick wins that are not thread tuning but usually matter more than it does: enable
  <code>mod_deflate</code> for text responses, and configure the TLS session cache. A stateless TLS
  handshake on every connection is measurable CPU, and it lands on exactly the tier you are trying to
  keep responsive.
</p>

<pre><code>SSLSessionCache        "shmcb:/var/run/httpd/sslcache(512000)"
SSLSessionCacheTimeout 300

AddOutputFilterByType DEFLATE text/html text/plain text/css \
                              application/javascript application/json</code></pre>

<h2 id="symptoms">Symptoms and their causes</h2>

<div class="table-scroll">
  <table>
    <thead><tr><th>Symptom</th><th>Usual cause</th><th>Action</th></tr></thead>
    <tbody>
      <tr>
        <td>"server reached MaxRequestWorkers" in the error log</td>
        <td>Backend latency, not Apache capacity, nine times out of ten</td>
        <td>Check backend response time first; only then raise the limit</td>
      </tr>
      <tr>
        <td>Scoreboard dominated by <code>K</code></td>
        <td><code>KeepAliveTimeout</code> too high for the connection rate</td>
        <td>Reduce to 2–5 s, or move to the event MPM</td>
      </tr>
      <tr>
        <td>Child count oscillating, CPU spent forking</td>
        <td><code>MinSpareThreads</code> below <code>ThreadsPerChild</code></td>
        <td>Raise spare thresholds to at least two children's worth</td>
      </tr>
      <tr>
        <td>Memory grows steadily until OOM</td>
        <td>Leak in a module, or too many children permitted</td>
        <td>Set <code>MaxConnectionsPerChild</code>; recheck the memory budget</td>
      </tr>
      <tr>
        <td>Intermittent 502 / 503 from the proxy</td>
        <td>Backend pool exhausted or <code>retry</code> window still open</td>
        <td>Align <code>max</code> with <code>ThreadsPerChild</code>; lower <code>retry</code></td>
      </tr>
      <tr>
        <td>Connection refused under burst, Apache otherwise idle</td>
        <td>Accept queue overflow</td>
        <td>Raise <code>somaxconn</code> and <code>ListenBacklog</code> together</td>
      </tr>
    </tbody>
  </table>
</div>

<h2 id="method">A tuning method that works</h2>

<ol>
  <li>
    <strong>Establish the baseline.</strong> Measure requests per second and the latency distribution
    at the current configuration. Without a before, you cannot claim an after.
  </li>
  <li>
    <strong>Find the actual bottleneck.</strong> Correlate <code>BusyWorkers</code> against backend
    response time. If workers rise while backend latency rises, Apache is a victim, not a cause.
  </li>
  <li>
    <strong>Compute, do not copy.</strong> Derive <code>MaxRequestWorkers</code> from Little's Law,
    constrain it by the memory budget, and cap it at what the backend can absorb.
  </li>
  <li>
    <strong>Change one variable per test.</strong> Load test with a realistic mix — JMeter or
    <code>ab</code> against your actual endpoints, not just <code>/</code> — and hold everything else
    constant.
  </li>
  <li>
    <strong>Push to failure deliberately.</strong> You need to know where the knee is and how the
    server behaves past it. A configuration that degrades gracefully beats one with a marginally
    higher peak.
  </li>
  <li>
    <strong>Instrument permanently.</strong> Ship <code>mod_status</code> metrics into your monitoring
    stack and alert on <code>BusyWorkers / MaxRequestWorkers</code>. Tuning is not a project you
    finish; traffic changes and the numbers age.
  </li>
</ol>

<div class="callout">
  <p>
    <b>The one-line summary.</b> Under the worker MPM, capacity is
    <code>ServerLimit × ThreadsPerChild</code>, a connection holds a thread for its whole life, and
    the right value for <code>MaxRequestWorkers</code> is the smallest one that keeps the backend busy
    without letting queues form where you cannot see them.
  </p>
</div>

<?php require __DIR__ . '/../partials/article-close.php'; ?>
