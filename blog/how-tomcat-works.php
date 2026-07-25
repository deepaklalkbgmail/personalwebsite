<?php
$SLUG = 'how-tomcat-works';
$TOC = [
    ['id' => 'overview',    'label' => 'Tomcat in one sentence'],
    ['id' => 'hierarchy',   'label' => 'The component hierarchy'],
    ['id' => 'startup',     'label' => 'What happens at startup'],
    ['id' => 'connector',   'label' => 'The connector: sockets to requests'],
    ['id' => 'threads',     'label' => 'Thread pools and their limits'],
    ['id' => 'pipeline',    'label' => 'The pipeline and valves'],
    ['id' => 'mapping',     'label' => 'Finding the right servlet'],
    ['id' => 'classloader', 'label' => 'The classloader hierarchy'],
    ['id' => 'sessions',    'label' => 'Sessions and clustering'],
    ['id' => 'tuning',      'label' => 'Tuning what you now understand'],
];
require __DIR__ . '/../partials/article-open.php';
?>

<h2 id="overview">Tomcat in one sentence</h2>

<p>
  Tomcat is a servlet container: it accepts TCP connections, parses HTTP off them, turns each request
  into an <code>HttpServletRequest</code>, finds the one servlet responsible for that URL, calls it,
  and writes the resulting <code>HttpServletResponse</code> back onto the socket. Everything else —
  the XML, the directory layout, the classloaders, the valves — exists to make that path configurable,
  isolated between applications, and manageable at runtime.
</p>

<p>
  Knowing the path in detail is what turns Tomcat tuning from cargo-culting into engineering. Almost
  every production question — why does <code>maxThreads</code> not fix this, why does the app see a
  different class than I deployed, why do sessions vanish on redeploy — is answered somewhere along
  that path.
</p>

<h2 id="hierarchy">The component hierarchy</h2>

<p>
  Tomcat's runtime is a tree of nested components, and <code>server.xml</code> is a literal
  description of it. Each level has one job.
</p>

<pre><code>Server                      <span class="c">// the JVM instance; owns the shutdown port</span>
 └── Service                <span class="c">// binds connectors to one engine</span>
      ├── Connector :8080   <span class="c">// HTTP/1.1 — protocol handling + thread pool</span>
      ├── Connector :8009   <span class="c">// AJP — for mod_jk / mod_proxy_ajp</span>
      └── Engine            <span class="c">// the request-processing entry point</span>
           └── Host         <span class="c">// a virtual host, e.g. localhost</span>
                └── Context <span class="c">// one web application (one WAR)</span>
                     └── Wrapper  <span class="c">// one servlet</span></code></pre>

<pre><code>&lt;Server port="8005" shutdown="SHUTDOWN"&gt;
  &lt;Service name="Catalina"&gt;

    &lt;Connector port="8080" protocol="HTTP/1.1"
               connectionTimeout="20000"
               maxThreads="200" minSpareThreads="25"
               acceptCount="100" maxConnections="8192"
               redirectPort="8443" /&gt;

    &lt;Engine name="Catalina" defaultHost="localhost"&gt;
      &lt;Host name="localhost" appBase="webapps"
            unpackWARs="true" autoDeploy="true"&gt;
        &lt;!-- Contexts are usually discovered from appBase --&gt;
      &lt;/Host&gt;
    &lt;/Engine&gt;

  &lt;/Service&gt;
&lt;/Server&gt;</code></pre>

<p>
  A few properties of this tree matter operationally:
</p>

<ul>
  <li>
    <strong>Connectors belong to a Service, not to an application.</strong> The 200 threads on port
    8080 are shared by every web application deployed under that Engine. One slow application can
    starve every other one — which is the argument for separate Tomcat instances per critical
    application rather than one instance hosting ten.
  </li>
  <li>
    <strong>Everything implements <code>Lifecycle</code>.</strong> Every component moves through
    <code>init → start → stop → destroy</code> and fires events at each transition. That uniformity is
    what makes hot redeploy of a single Context possible while the rest of the server keeps serving.
  </li>
  <li>
    <strong>A Context is the unit of isolation.</strong> Its own classloader, its own session manager,
    its own <code>ServletContext</code>. Two applications on one Tomcat share a JVM and a thread pool
    but almost nothing else.
  </li>
</ul>

<h2 id="startup">What happens at startup</h2>

<p>
  <code>catalina.sh start</code> launches <code>org.apache.catalina.startup.Bootstrap</code>, and the
  sequence from there is worth knowing because most startup failures are a specific step in it.
</p>

<ol>
  <li>
    <strong>Bootstrap builds the server classloaders</strong> — common, catalina and shared — from the
    paths in <code>conf/catalina.properties</code>, then loads Catalina reflectively through them.
    This is why Tomcat's own classes are invisible to your application.
  </li>
  <li>
    <strong>Digester parses <code>server.xml</code></strong> and instantiates the component tree
    described above, applying attributes as bean properties. A typo in an attribute name is silently
    ignored here — a genuine source of "my setting had no effect".
  </li>
  <li>
    <strong><code>init()</code> propagates down the tree.</strong> Connectors bind their sockets at
    this point, which is where a port conflict surfaces.
  </li>
  <li>
    <strong><code>start()</code> propagates down.</strong> The Host deploys applications from
    <code>appBase</code>: it expands WARs, creates a Context and a classloader for each, and merges
    the global <code>conf/web.xml</code> with the application's own descriptor and its annotations.
  </li>
  <li>
    <strong>Each Context starts its filters and listeners</strong>, then loads servlets — eagerly if
    <code>&lt;load-on-startup&gt;</code> is set, lazily on first request otherwise.
  </li>
  <li>
    <strong>Connectors begin accepting.</strong> Only now does the port actually serve traffic.
  </li>
</ol>

<div class="callout">
  <p>
    <b>Startup ordering detail.</b> Connectors bind during <code>init()</code> but do not accept until
    the very end of <code>start()</code>. Between those points the port is open and the kernel is
    queueing connections while nothing is answering — which is why a health check that only tests TCP
    connectivity reports a Tomcat as up several seconds before it can serve a request. Health checks
    must issue a real HTTP request against a real path.
  </p>
</div>

<h2 id="connector">The connector: sockets to requests</h2>

<p>
  The connector is where nearly all Tomcat performance lives. Since Tomcat 8.5 the default is the NIO
  protocol handler, and its internal division of labour is the thing to understand.
</p>

<pre><code>            ┌──────────────┐
 TCP  ──────▶│   Acceptor   │  1 thread. Calls accept(), sets the socket
             └──────┬───────┘  non-blocking, hands it to the poller.
                    ▼
             ┌──────────────┐
             │    Poller    │  1–2 threads on a Selector. Watches many
             └──────┬───────┘  sockets; wakes only when data is readable.
                    ▼
             ┌──────────────┐
             │ Worker pool  │  maxThreads threads. Parses HTTP, runs the
             └──────┬───────┘  filter chain and the servlet, writes out.
                    ▼
              Response ──▶ socket</code></pre>

<p>
  The separation is what makes NIO scale. A connection that is open but idle — keep-alive between
  requests — is registered with the poller's selector and costs no thread at all. A worker thread is
  taken only while a request is actually being processed. That is the fundamental difference from the
  old BIO connector, where one thread was pinned per connection for its entire life.
</p>

<div class="table-scroll">
  <table>
    <thead><tr><th>Protocol</th><th>Implementation</th><th>Notes</th></tr></thead>
    <tbody>
      <tr>
        <td><code>HTTP/1.1</code></td>
        <td>Auto-selects NIO</td>
        <td>The sensible default</td>
      </tr>
      <tr>
        <td><code>org.apache.coyote.http11.Http11NioProtocol</code></td>
        <td>Java NIO selector</td>
        <td>Explicit form of the above</td>
      </tr>
      <tr>
        <td><code>Http11Nio2Protocol</code></td>
        <td>Java NIO.2 asynchronous channels</td>
        <td>Completion-handler based; rarely a measurable win</td>
      </tr>
      <tr>
        <td><code>Http11AprProtocol</code></td>
        <td>Native APR / OpenSSL</td>
        <td>Removed in Tomcat 10.1+; OpenSSL now reachable from NIO</td>
      </tr>
      <tr>
        <td><code>AJP/1.3</code></td>
        <td>Binary protocol from HTTPD</td>
        <td>Bind to localhost and set a secret — never expose it</td>
      </tr>
    </tbody>
  </table>
</div>

<h3>Following one request through</h3>

<ol>
  <li>The acceptor accepts the socket and registers it with a poller.</li>
  <li>The poller's selector reports the socket readable and dispatches it to the worker pool.</li>
  <li>A worker thread reads bytes and Coyote parses the request line and headers into an internal
      <code>org.apache.coyote.Request</code> — a lightweight, recyclable object.</li>
  <li>CoyoteAdapter wraps it as the <code>HttpServletRequest</code> the Servlet API defines, and asks
      the Mapper which Host, Context and Wrapper should handle the URI.</li>
  <li>The request enters the Engine pipeline and descends through Host, Context and Wrapper
      pipelines.</li>
  <li>The Wrapper builds the filter chain and finally invokes <code>service()</code> on your
      servlet.</li>
  <li>The response is written back through the same socket; if keep-alive applies, the socket returns
      to the poller and the worker thread is released.</li>
</ol>

<h2 id="threads">Thread pools and their limits</h2>

<p>
  Four connector attributes govern concurrency, and confusing them is the most common Tomcat
  misconfiguration there is.
</p>

<div class="table-scroll">
  <table>
    <thead><tr><th>Attribute</th><th>Default</th><th>Governs</th></tr></thead>
    <tbody>
      <tr><td><code>maxThreads</code></td><td>200</td><td>Requests processed <em>simultaneously</em></td></tr>
      <tr><td><code>minSpareThreads</code></td><td>10</td><td>Threads kept alive when idle</td></tr>
      <tr><td><code>maxConnections</code></td><td>8192 (NIO)</td><td>Sockets <em>accepted and held</em>, processing or idle</td></tr>
      <tr><td><code>acceptCount</code></td><td>100</td><td>OS accept-queue depth once maxConnections is reached</td></tr>
    </tbody>
  </table>
</div>

<p>
  They form a funnel, and the behaviour at each stage is different:
</p>

<pre><code>incoming connections
   │
   ├─▶ up to maxConnections   ── accepted and tracked by the poller
   │      │
   │      └─▶ up to maxThreads ── actively processing a request
   │             (the rest are idle keep-alive, or waiting for a worker)
   │
   ├─▶ beyond maxConnections  ── queued in the OS backlog, up to acceptCount
   │
   └─▶ beyond acceptCount     ── connection refused</code></pre>

<div class="callout">
  <p>
    <b>The distinction that matters.</b> <code>maxConnections</code> is about sockets;
    <code>maxThreads</code> is about concurrent work. Raising <code>maxThreads</code> when the
    application is waiting on a database does not increase throughput — it increases the number of
    threads waiting on the same database, adds context-switching overhead, and consumes heap through
    per-thread state. Throughput is governed by the slowest dependency, and Tomcat cannot make it
    faster by trying harder.
  </p>
</div>

<p>
  The right way to size <code>maxThreads</code> is from measurement. Little's Law again: for 300
  requests per second at 150 ms average service time you need about 45 concurrent workers, so
  something in the region of 100 gives you comfortable burst headroom. Then verify against the
  downstream pool — if the JDBC pool has 50 connections, 400 Tomcat threads simply create a queue
  inside the connection pool, invisible in Tomcat's own metrics.
</p>

<p>
  <code>acceptCount</code> deserves a moment of thought too. A large queue converts a load spike into
  a latency spike: requests sit in the backlog, the client times out, and Tomcat then processes work
  nobody is waiting for any more. A small queue fails fast and lets a load balancer route elsewhere.
  Fast rejection is usually the better behaviour.
</p>

<h2 id="pipeline">The pipeline and valves</h2>

<p>
  Each container in the tree — Engine, Host, Context, Wrapper — owns a <em>pipeline</em>, an ordered
  chain of <code>Valve</code> objects ending in a mandatory basic valve that passes the request down
  to the next level. This is Tomcat's interception mechanism, and it sits below the Servlet API, so a
  valve sees requests that never reach any application.
</p>

<pre><code>Engine pipeline   → [ ErrorReportValve ] [ StandardEngineValve ]
  Host pipeline   → [ AccessLogValve ] [ RemoteIpValve ] [ StandardHostValve ]
    Context pipe. → [ AuthenticatorValve ] [ StandardContextValve ]
      Wrapper pipe→ [ StandardWrapperValve ] → filter chain → servlet</code></pre>

<p>
  Valves you are likely to configure in production:
</p>

<ul>
  <li>
    <strong><code>RemoteIpValve</code></strong> — reads <code>X-Forwarded-For</code> and
    <code>X-Forwarded-Proto</code> so that behind Apache or a load balancer,
    <code>request.getRemoteAddr()</code> returns the real client and
    <code>request.isSecure()</code> reflects the original TLS. Without it, access logs record the
    proxy's IP and redirects can downgrade HTTPS to HTTP.
  </li>
  <li>
    <strong><code>AccessLogValve</code></strong> — the access log. Add <code>%D</code> to record
    request duration; a log without timings is far less useful during an incident.
  </li>
  <li>
    <strong><code>RemoteAddrValve</code></strong> — network-level allow/deny, typically to fence off
    the manager application.
  </li>
  <li>
    <strong><code>StuckThreadDetectionValve</code></strong> — logs a stack trace for any request
    exceeding a threshold. Effectively free, and it turns "the app hung last night" into an actual
    stack trace.
  </li>
</ul>

<pre><code>&lt;Valve className="org.apache.catalina.valves.RemoteIpValve"
       internalProxies="10\.\d+\.\d+\.\d+"
       protocolHeader="X-Forwarded-Proto" /&gt;

&lt;Valve className="org.apache.catalina.valves.AccessLogValve"
       directory="logs" prefix="access." suffix=".log"
       pattern="%h %l %u %t &amp;quot;%r&amp;quot; %s %b %D" /&gt;

&lt;Valve className="org.apache.catalina.valves.StuckThreadDetectionValve"
       threshold="60" /&gt;</code></pre>

<h2 id="mapping">Finding the right servlet</h2>

<p>
  The Mapper resolves a URI to a Wrapper in one pass, using rules fixed by the Servlet specification —
  not by declaration order in <code>web.xml</code>. The precedence is:
</p>

<ol>
  <li><strong>Exact match</strong> — <code>/api/status</code></li>
  <li><strong>Longest path prefix</strong> — <code>/api/*</code> beats <code>/*</code></li>
  <li><strong>Extension match</strong> — <code>*.jsp</code></li>
  <li><strong>Default servlet</strong> — <code>/</code>, which serves static files</li>
</ol>

<p>
  Two consequences worth internalising. First, mapping a servlet or framework dispatcher to
  <code>/</code> replaces the default servlet, so static resources stop being served unless the
  framework handles them. Second, <code>/*</code> and <code>/</code> are different mappings with
  different precedence — a mistake that produces "my filter runs but my servlet 404s" with impressive
  regularity.
</p>

<h2 id="classloader">The classloader hierarchy</h2>

<p>
  Tomcat deliberately breaks the standard Java delegation model, and knowing where it breaks resolves
  a large class of <code>ClassCastException</code> and <code>NoSuchMethodError</code> mysteries.
</p>

<pre><code>Bootstrap (JVM)
   └── System  (CLASSPATH: bootstrap.jar, tomcat-juli.jar)
        └── Common  (${catalina.base}/lib — servlet-api.jar etc.)
             ├── WebappClassLoader  /app-one
             └── WebappClassLoader  /app-two</code></pre>

<p>
  A normal Java classloader asks its parent first. Tomcat's <code>WebappClassLoader</code> instead
  looks in this order:
</p>

<ol>
  <li>JVM bootstrap classes (always first — you cannot override <code>java.lang.String</code>)</li>
  <li><code>/WEB-INF/classes</code> of the application</li>
  <li><code>/WEB-INF/lib/*.jar</code> of the application</li>
  <li>The Common classloader — Tomcat's <code>lib/</code></li>
</ol>

<p>
  So your application's own copy of a library wins over the container's. That is what makes two
  applications with incompatible versions of the same framework coexist in one JVM. The exception is
  the Servlet API itself: it must come from the container, which is why
  <code>servlet-api.jar</code> is marked <code>provided</code> in Maven builds. Bundling it in
  <code>WEB-INF/lib</code> gives you two incompatible copies of the same interface and a
  <code>ClassCastException</code> between types that look identical in the stack trace.
</p>

<div class="callout">
  <p>
    <b>Why redeploys leak memory.</b> Each redeploy creates a fresh <code>WebappClassLoader</code> and
    discards the old one. If anything outside the application still holds a reference — a JDBC driver
    registered in the shared <code>DriverManager</code>, a thread-local left set on a pooled worker
    thread, a timer thread the application started and never stopped — the old classloader and every
    class it loaded stay reachable. Enough redeploys and you exhaust metaspace. Tomcat's
    <code>JreMemoryLeakPreventionListener</code> mitigates some known cases, but applications that
    start threads must stop them in a <code>ServletContextListener</code>.
  </p>
</div>

<h2 id="sessions">Sessions and clustering</h2>

<p>
  By default each Context has a <code>StandardManager</code> holding sessions in the JVM heap, keyed
  by a <code>JSESSIONID</code> cookie. Two behaviours follow. Sessions do not survive a crash, and in a
  cluster a request that lands on a different node sees no session at all — hence sticky sessions in
  the load balancer, using <code>jvmRoute</code> to append a node identifier to the session ID.
</p>

<pre><code>&lt;Engine name="Catalina" defaultHost="localhost" jvmRoute="node01"&gt;

<span class="c">// resulting cookie: JSESSIONID=A1B2C3D4E5F6.node01
// mod_jk / mod_proxy_balancer route on the suffix</span></code></pre>

<p>
  Stickiness alone still loses sessions when a node dies. The options, in increasing order of
  robustness:
</p>

<ul>
  <li>
    <strong><code>PersistentManager</code></strong> — swaps idle sessions to disk or a database.
    Survives a graceful restart; cheap, and often enough.
  </li>
  <li>
    <strong><code>DeltaManager</code></strong> — Tomcat's built-in clustering, replicating session
    deltas to every node over multicast. Simple, but all-to-all replication limits it to small
    clusters (roughly four to six nodes).
  </li>
  <li>
    <strong><code>BackupManager</code></strong> — each session has one designated backup node rather
    than being copied everywhere. Scales considerably further.
  </li>
  <li>
    <strong>External store</strong> — Redis, Hazelcast or similar behind a custom manager. The right
    answer for containerised deployments, where pods are disposable and a canary or rolling update
    will move users between versions mid-session.
  </li>
</ul>

<p>
  Whatever you choose, everything placed in a replicated session must be <code>Serializable</code>,
  and sessions should stay small. Replication cost is proportional to session size, and a session
  carrying a large object graph turns every request into a network event.
</p>

<h2 id="tuning">Tuning what you now understand</h2>

<pre><code>&lt;Connector port="8080" protocol="HTTP/1.1"
           maxThreads="150"            <span class="c">// from measured concurrency, not folklore</span>
           minSpareThreads="25"
           maxConnections="4096"       <span class="c">// sockets held, not requests processed</span>
           acceptCount="100"           <span class="c">// fail fast rather than queue deeply</span>
           connectionTimeout="20000"   <span class="c">// time allowed to send the request line</span>
           keepAliveTimeout="15000"
           maxKeepAliveRequests="100"
           compression="on"
           compressibleMimeType="text/html,text/css,application/json"
           maxHttpHeaderSize="16384"
           server="Server"             <span class="c">// suppress the version banner</span>
           URIEncoding="UTF-8" /&gt;</code></pre>

<p>
  Alongside the connector, the JVM settings matter as much as anything in <code>server.xml</code>:
</p>

<pre><code><span class="c"># setenv.sh — keep JVM options out of catalina.sh</span>
CATALINA_OPTS="-Xms2g -Xmx2g \
  -XX:+UseG1GC -XX:MaxGCPauseMillis=200 \
  -XX:+HeapDumpOnOutOfMemoryError \
  -XX:HeapDumpPath=/var/log/tomcat/ \
  -Djava.security.egd=file:/dev/urandom"</code></pre>

<ul>
  <li>
    <strong>Set <code>-Xms</code> equal to <code>-Xmx</code></strong> on a dedicated server. Heap
    resizing costs full collections at exactly the wrong moment.
  </li>
  <li>
    <strong>Always enable <code>HeapDumpOnOutOfMemoryError</code>.</strong> An OOM without a heap dump
    is an incident you will investigate twice.
  </li>
  <li>
    <strong>Account for thread stacks outside the heap.</strong> 200 threads at the default 1 MB stack
    is 200 MB of native memory the heap setting does not cover — relevant when Tomcat runs in a
    container with a hard memory limit.
  </li>
  <li>
    <strong>Match the front tier.</strong> If Apache HTTPD proxies to this Tomcat, its worker count and
    per-child connection pool must be sized against <code>maxThreads</code>, or excess load queues
    invisibly inside Tomcat instead of visibly in Apache.
  </li>
</ul>

<div class="callout">
  <p>
    <b>Where to look when it is slow.</b> Take three thread dumps thirty seconds apart with
    <code>jstack</code> and compare. Threads named <code>http-nio-8080-exec-*</code> stuck in the same
    stack across all three are your bottleneck, and the frame they are stuck in — a JDBC read, a
    synchronised block, an HTTP call to another service — names the actual problem. That is almost
    always more informative than another round of guessing at <code>maxThreads</code>.
  </p>
</div>

<?php require __DIR__ . '/../partials/article-close.php'; ?>
