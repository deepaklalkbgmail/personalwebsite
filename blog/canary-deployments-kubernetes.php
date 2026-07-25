<?php
$SLUG = 'canary-deployments-kubernetes';
$TOC = [
    ['id' => 'why',        'label' => 'Why rolling updates are not enough'],
    ['id' => 'shapes',     'label' => 'Blue-green, rolling and canary'],
    ['id' => 'replica',    'label' => 'Canary with replica ratios'],
    ['id' => 'ingress',    'label' => 'Weighted routing at the ingress'],
    ['id' => 'mesh',       'label' => 'Service mesh and Gateway API'],
    ['id' => 'analysis',   'label' => 'Automated analysis and promotion'],
    ['id' => 'signals',    'label' => 'Choosing the right signals'],
    ['id' => 'pitfalls',   'label' => 'Pitfalls that bite in production'],
    ['id' => 'checklist',  'label' => 'A practical checklist'],
];
require __DIR__ . '/../partials/article-open.php';
?>

<h2 id="why">Why rolling updates are not enough</h2>

<p>
  Kubernetes gives you a rolling update for free. Change the image in a Deployment and the
  controller creates a new ReplicaSet, scales it up, scales the old one down, and honours
  <code>maxSurge</code> and <code>maxUnavailable</code> along the way. It is safe in a narrow sense:
  it will not take all your pods down at once, and it will pause if new pods never become ready.
</p>

<p>
  The catch is what "ready" means. A readiness probe answers one question — <em>can this container
  serve traffic?</em> It does not know whether the new build has doubled p95 latency, started
  returning HTTP 500 on one endpoint, quietly broken a downstream contract, or introduced a memory
  leak that will bite in forty minutes. Those defects pass every probe you have, and a rolling
  update will happily march them to one hundred percent of your traffic.
</p>

<div class="callout">
  <p>
    <b>The core idea.</b> A canary release separates <em>deploying</em> code from <em>releasing</em>
    it to users. The new version runs in production, takes a small, controlled slice of real traffic,
    and is judged on real signals before it is allowed any more.
  </p>
</div>

<h2 id="shapes">Blue-green, rolling and canary</h2>

<p>
  All three shapes are used on Kubernetes and they solve different problems. Picking the wrong one
  is a common source of "we have progressive delivery" that does not actually reduce risk.
</p>

<div class="table-scroll">
  <table>
    <thead>
      <tr><th>Strategy</th><th>Traffic during rollout</th><th>Rollback speed</th><th>Extra capacity</th><th>Best for</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>Rolling</td>
        <td>Mixed, uncontrolled ratio</td>
        <td>Minutes — another rollout</td>
        <td>maxSurge only</td>
        <td>Low-risk, backwards-compatible changes</td>
      </tr>
      <tr>
        <td>Blue-green</td>
        <td>All-or-nothing switch</td>
        <td>Seconds — flip the selector</td>
        <td>2× for the window</td>
        <td>Changes needing an atomic cutover</td>
      </tr>
      <tr>
        <td>Canary</td>
        <td>Deliberate small percentage, ramped</td>
        <td>Seconds — drop the weight to zero</td>
        <td>One canary replica upwards</td>
        <td>User-facing changes with measurable impact</td>
      </tr>
    </tbody>
  </table>
</div>

<p>
  Blue-green gives you a fast rollback but tells you nothing before the switch, because no real user
  touched the new version. Canary gives you evidence, at the cost of running two versions
  simultaneously — which is an application design constraint, not just an infrastructure one.
</p>

<h2 id="replica">Canary with replica ratios</h2>

<p>
  The simplest canary on plain Kubernetes needs no extra components. Run two Deployments whose pods
  share a label that a single Service selects. Traffic then splits roughly in proportion to the
  replica counts, because kube-proxy load-balances across all matching endpoints.
</p>

<pre><code><span class="c"># Stable: 9 replicas of v1</span>
apiVersion: apps/v1
kind: Deployment
metadata:
  name: payments-stable
spec:
  replicas: 9
  selector:
    matchLabels: { app: payments, track: stable }
  template:
    metadata:
      labels: { app: payments, track: stable, version: v1 }
    spec:
      containers:
        - name: api
          image: registry.internal/payments:1.8.3
---
<span class="c"># Canary: 1 replica of v2 — same `app` label, different `track`</span>
apiVersion: apps/v1
kind: Deployment
metadata:
  name: payments-canary
spec:
  replicas: 1
  selector:
    matchLabels: { app: payments, track: canary }
  template:
    metadata:
      labels: { app: payments, track: canary, version: v2 }
    spec:
      containers:
        - name: api
          image: registry.internal/payments:1.9.0
---
<span class="c"># One Service selects only `app` — so it fronts both tracks</span>
apiVersion: v1
kind: Service
metadata:
  name: payments
spec:
  selector: { app: payments }
  ports:
    - port: 80
      targetPort: 8080</code></pre>

<p>
  Ten pods, one of them canary, gives about ten percent of requests to v2. Ramping means scaling the
  canary up and the stable set down in step. It works, it is transparent, and it requires nothing you
  do not already run.
</p>

<p>
  The limitations are worth stating plainly, because teams hit all of them:
</p>

<ul>
  <li>
    <strong>Granularity is tied to pod count.</strong> One percent of traffic needs at least one
    hundred pods. For most services, five to ten percent is the realistic floor.
  </li>
  <li>
    <strong>The split is statistical, not enforced.</strong> kube-proxy balances connections, not
    requests. With HTTP keep-alive, a client that opens one long-lived connection to a canary pod
    sends <em>all</em> its requests there. With gRPC — multiplexed over a single HTTP/2 connection —
    the skew is far worse.
  </li>
  <li>
    <strong>You cannot target a cohort.</strong> There is no way to say "internal users first" or
    "only requests carrying this header".
  </li>
  <li>
    <strong>Scaling fights you.</strong> A HorizontalPodAutoscaler on either Deployment will move
    your traffic ratio without telling you.
  </li>
</ul>

<h2 id="ingress">Weighted routing at the ingress</h2>

<p>
  Moving the split up to the ingress controller decouples the traffic percentage from the replica
  count. With the NGINX ingress controller, you deploy a second Ingress object annotated as a canary
  for the same host and path.
</p>

<pre><code>apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: payments-canary
  annotations:
    nginx.ingress.kubernetes.io/canary: "true"
    nginx.ingress.kubernetes.io/canary-weight: "5"        <span class="c"># 5% of requests</span>
spec:
  ingressClassName: nginx
  rules:
    - host: api.example.com
      http:
        paths:
          - path: /payments
            pathType: Prefix
            backend:
              service:
                name: payments-canary
                port: { number: 80 }</code></pre>

<p>
  Now the canary can run a single replica while taking exactly five percent, and ramping is a one-line
  patch. More usefully, NGINX evaluates canary rules in a fixed precedence — header, then cookie, then
  weight — which lets you build a genuine progression:
</p>

<pre><code><span class="c"># Stage 1 — opt-in only. Nobody reaches v2 without asking for it.</span>
nginx.ingress.kubernetes.io/canary-by-header: "x-canary"
nginx.ingress.kubernetes.io/canary-by-header-value: "always"

<span class="c"># Stage 2 — sticky cohort. A user who lands on the canary stays on it.</span>
nginx.ingress.kubernetes.io/canary-by-cookie: "canary_payments"

<span class="c"># Stage 3 — percentage ramp: 5 -&gt; 20 -&gt; 50 -&gt; 100</span>
nginx.ingress.kubernetes.io/canary-weight: "20"</code></pre>

<div class="callout">
  <p>
    <b>Start with the header stage.</b> Routing your own QA team and synthetic monitors to the canary
    with an explicit header costs nothing and catches the embarrassing failures — wrong config map,
    missing secret, bad database credential — before a single customer is exposed.
  </p>
</div>

<h2 id="mesh">Service mesh and Gateway API</h2>

<p>
  Ingress-level canaries only cover north-south traffic. If the change is in a service that is called
  by other services inside the cluster, the ingress never sees those requests. That is where a mesh —
  Istio, Linkerd — or the Gateway API earns its keep, because the split happens at every sidecar or
  proxy, east-west included.
</p>

<pre><code><span class="c"># Istio: subsets defined once, weights adjusted per rollout step</span>
apiVersion: networking.istio.io/v1
kind: DestinationRule
metadata:
  name: payments
spec:
  host: payments
  subsets:
    - name: stable
      labels: { version: v1 }
    - name: canary
      labels: { version: v2 }
---
apiVersion: networking.istio.io/v1
kind: VirtualService
metadata:
  name: payments
spec:
  hosts: [ payments ]
  http:
    - route:
        - destination: { host: payments, subset: stable }
          weight: 90
        - destination: { host: payments, subset: canary }
          weight: 10</code></pre>

<p>
  The vendor-neutral equivalent is Gateway API's <code>HTTPRoute</code>, which expresses weighted
  backends natively and is now the direction most controllers are converging on:
</p>

<pre><code>apiVersion: gateway.networking.k8s.io/v1
kind: HTTPRoute
metadata:
  name: payments
spec:
  parentRefs: [ { name: public-gateway } ]
  rules:
    - backendRefs:
        - name: payments-stable
          port: 80
          weight: 90
        - name: payments-canary
          port: 80
          weight: 10</code></pre>

<p>
  A mesh also hands you the metrics for free. Because every request passes through a proxy that emits
  consistent request-count, latency-histogram and response-code series labelled by destination
  version, you get an apples-to-apples comparison between canary and stable without instrumenting the
  application at all. That matters enormously for the next step.
</p>

<h2 id="analysis">Automated analysis and promotion</h2>

<p>
  A canary that a human watches on a dashboard is better than nothing, but it degrades the moment
  someone is busy. The mature form is a controller that ramps traffic, queries a metrics provider at
  each step, and promotes or aborts on the result. Argo Rollouts and Flagger both do this; here is the
  Argo Rollouts shape.
</p>

<pre><code>apiVersion: argoproj.io/v1alpha1
kind: Rollout
metadata:
  name: payments
spec:
  replicas: 10
  strategy:
    canary:
      canaryService: payments-canary
      stableService: payments-stable
      trafficRouting:
        nginx:
          stableIngress: payments
      analysis:
        templates: [ { templateName: success-rate } ]
        startingStep: 2                 <span class="c"># analyse from the 20% step onwards</span>
        args:
          - name: service
            value: payments-canary
      steps:
        - setWeight: 5
        - pause: { duration: 5m }
        - setWeight: 20
        - pause: { duration: 10m }
        - setWeight: 50
        - pause: { duration: 10m }
        - setWeight: 100
  selector:
    matchLabels: { app: payments }
  template:
    metadata:
      labels: { app: payments }
    spec:
      containers:
        - name: api
          image: registry.internal/payments:1.9.0</code></pre>

<pre><code>apiVersion: argoproj.io/v1alpha1
kind: AnalysisTemplate
metadata:
  name: success-rate
spec:
  args: [ { name: service } ]
  metrics:
    - name: success-rate
      interval: 1m
      count: 5
      successCondition: result[0] &gt;= 0.99
      failureLimit: 1                   <span class="c"># one bad reading aborts the rollout</span>
      provider:
        prometheus:
          address: http://prometheus.monitoring:9090
          query: |
            sum(rate(http_requests_total{
              service="{{args.service}}", code!~"5.."}[2m]))
            /
            sum(rate(http_requests_total{service="{{args.service}}"}[2m]))</code></pre>

<p>
  When a measurement fails, the controller sets the canary weight back to zero and scales the canary
  ReplicaSet down. No page, no human, no partial outage that ran for twenty minutes because the
  on-call engineer was in a meeting.
</p>

<h2 id="signals">Choosing the right signals</h2>

<p>
  The analysis is only as good as the queries behind it. A few rules that hold up:
</p>

<ul>
  <li>
    <strong>Compare canary to stable, not to a fixed threshold.</strong> A five percent error rate may
    be normal for a service that proxies a flaky third party. What matters is whether the canary is
    <em>worse than the stable version right now</em>, under the same conditions.
  </li>
  <li>
    <strong>Use rate windows longer than your scrape interval.</strong> A two-minute rate over a
    thirty-second scrape gives stable readings; anything shorter will fire on noise.
  </li>
  <li>
    <strong>Watch percentiles, not averages.</strong> Mean latency hides the tail that users feel.
    p95 and p99 against the stable baseline are the useful comparison.
  </li>
  <li>
    <strong>Require a minimum request volume.</strong> At five percent weight on a quiet service, a
    single 500 can be a one hundred percent error rate. Gate the analysis on a request-count floor so
    it does not abort on a sample size of two.
  </li>
  <li>
    <strong>Include at least one business metric.</strong> Technically healthy releases that stop
    users completing a payment are exactly the class of failure infrastructure metrics miss.
  </li>
</ul>

<h2 id="pitfalls">Pitfalls that bite in production</h2>

<h3>Database schema changes</h3>
<p>
  This is the constraint that decides whether you can canary at all. During the rollout, v1 and v2 are
  both live against the same database. Any migration must therefore be backwards compatible: add
  nullable columns, never rename or drop in the same release, and split destructive changes across
  deployments — expand, migrate, contract. If your change cannot satisfy that, you need blue-green
  with a maintenance window, and pretending otherwise just moves the outage.
</p>

<h3>Stateful sessions</h3>
<p>
  If a user's session lives in a pod's memory, a request that lands on the canary and the next that
  lands on stable will look like a random logout. Externalise session state — Redis, a database, a
  signed token — or pin the cohort with the cookie-based canary so a user stays on one version for
  the whole rollout.
</p>

<h3>Autoscalers moving your weights</h3>
<p>
  With the replica-ratio method, an HPA on the stable Deployment changes the traffic split as a side
  effect of load. Either move the split to the ingress or mesh, or let the rollout controller own the
  replica counts during the rollout window.
</p>

<h3>Connection-level stickiness</h3>
<p>
  Keep-alive and HTTP/2 mean that connection-level balancing is not request-level balancing. For gRPC
  in particular, a Service-based canary can send a wildly different percentage than the replica ratio
  implies. Use a proxy that balances per request.
</p>

<h3>Asynchronous work</h3>
<p>
  Traffic weights govern inbound HTTP. They do not govern a canary pod that is also consuming from a
  Kafka topic or running a scheduled job — that pod will process whatever it picks up, at full effect.
  Keep queue consumers and cron workloads out of the canary Deployment, or scale them to zero on the
  canary track.
</p>

<h3>Observability that cannot tell the versions apart</h3>
<p>
  If your dashboards aggregate every pod behind one service name, you cannot compare canary to stable
  and the whole exercise collapses. Propagate a <code>version</code> label from pod metadata into
  every metric, log line and trace before you run your first canary, not after.
</p>

<h2 id="checklist">A practical checklist</h2>

<ul>
  <li>Readiness and liveness probes reflect real dependency health, not just process liveness.</li>
  <li>Metrics, logs and traces carry a version label that distinguishes canary from stable.</li>
  <li>Schema changes are backwards compatible across the two versions that will run concurrently.</li>
  <li>Session state lives outside the pod, or the cohort is pinned by cookie.</li>
  <li>An explicit abort path exists and has been tested — deliberately deploy a broken build and confirm the rollback fires.</li>
  <li>Analysis compares canary against stable, over a window long enough to be stable, with a request-count floor.</li>
  <li>Async consumers and scheduled jobs are excluded from the canary track.</li>
  <li>Every rollout step has a maximum duration, so a stuck canary does not sit at fifty percent overnight.</li>
</ul>

<div class="callout">
  <p>
    <b>Start small.</b> Header-based routing to an internal cohort, one canary replica and a manual
    promotion gate already removes most of the risk of a bad release. Automated analysis is worth
    building — but it is the second step, not the first.
  </p>
</div>

<?php require __DIR__ . '/../partials/article-close.php'; ?>
