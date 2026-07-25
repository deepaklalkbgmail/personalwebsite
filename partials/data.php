<?php
/**
 * Shared site content. Keeping it in one place means the home page, the blog
 * index and each article all stay in sync automatically.
 */

$SITE = [
    'name'     => 'Deepaklal KB',
    'role'     => 'IT Infrastructure Engineer',
    'tagline'  => 'DevOps · Kubernetes · Performance Engineering',
    'email'    => 'iam@deepaklal.online',
    'linkedin' => 'https://www.linkedin.com/in/deepaklalkb',
    'location' => 'India',
];

/**
 * Blog posts, newest first. `slug` maps to blog/<slug>.php.
 */
$POSTS = [
    [
        'slug'    => 'canary-deployments-kubernetes',
        'title'   => 'Canary Deployments in Kubernetes',
        'summary' => 'Rolling updates tell you a pod is healthy, not that a release is good. This walks through progressive delivery on Kubernetes — replica-ratio canaries, ingress weighting, service-mesh traffic splitting and automated analysis with Argo Rollouts — plus the failure modes that catch teams out.',
        'tags'    => ['Kubernetes', 'Progressive Delivery'],
        'date'    => '2026-06-18',
        'read'    => '14 min',
    ],
    [
        'slug'    => 'apache-httpd-worker-mpm-performance',
        'title'   => 'Performance Management in Apache HTTPD (Worker MPM)',
        'summary' => 'How the worker MPM actually allocates processes and threads, how to size MaxRequestWorkers from measured throughput instead of guesswork, how to read the scoreboard, and how to align the web tier with the backend pool so a slow application never becomes a queue collapse.',
        'tags'    => ['Apache HTTPD', 'Performance'],
        'date'    => '2026-05-22',
        'read'    => '16 min',
    ],
    [
        'slug'    => 'how-tomcat-works',
        'title'   => 'How Tomcat Works: Architecture and Request Lifecycle',
        'summary' => 'A walk down the Catalina stack — Server, Service, Connector, Engine, Host, Context, Wrapper — following a single HTTP request from the acceptor socket through the poller, the pipeline of valves, the classloader hierarchy and into your servlet, then back out.',
        'tags'    => ['Tomcat', 'Java Middleware'],
        'date'    => '2026-04-30',
        'read'    => '15 min',
    ],
];

/**
 * Look up a post by slug and return [post, previous, next].
 */
function post_context(array $posts, string $slug): array
{
    foreach ($posts as $i => $p) {
        if ($p['slug'] === $slug) {
            return [$p, $posts[$i - 1] ?? null, $posts[$i + 1] ?? null];
        }
    }
    return [null, null, null];
}

$EXPERIENCE = [
    [
        'role'  => 'Project Leader',
        'where' => 'i-exceed Technology Solutions',
        'when'  => 'Oct 2019 – Present',
        'high'  => [
            'Led Appzillon banking framework implementations for IPPB, Bandhan, UCBL, Canara, Equitas, Bank Islam, BOA, Al-Mashraf, NBF and CBoI',
            'Managed an 11-person team spanning middleware, cloud, DevOps and application security',
            'Owned Azure administration, migration strategy, performance tuning and platform security',
            'Introduced microservices on OpenShift and Kubernetes across delivery teams',
            'Built DevSecOps pipelines on Jenkins in master and master–slave topologies',
            'Designed and ran disaster recovery for on-premises and Azure environments',
        ],
    ],
    [
        'role'  => 'Senior Analyst',
        'where' => 'Mashreq Global Services',
        'when'  => 'Apr 2019 – Oct 2019',
        'high'  => [
            'Maintained Appzillon applications across JavaScript, Java and Oracle',
            'Managed WebLogic and Oracle integration for core banking workloads',
            'Supported RM Mobility for bank staff',
        ],
    ],
    [
        'role'  => 'Technology Consultant',
        'where' => 'DXC Technologies',
        'when'  => 'Jul 2018 – Apr 2019',
        'high'  => [
            'Managed the IPPB platform on JBoss, WebLogic and Linux',
            'Ran system performance testing with JMeter and tuned the resulting bottlenecks',
            'Led the PGB project across Oracle, JavaScript, Finale and iReport',
        ],
    ],
    [
        'role'  => 'Technical Representative',
        'where' => 'Saggezza India',
        'when'  => 'Apr 2017 – Mar 2018',
        'high'  => [
            'Finacle support and day-to-day technical operations',
            'Designed, tested and maintained banking applications',
            'Troubleshot, deployed and monitored software releases',
        ],
    ],
    [
        'role'  => 'Assistant Professor',
        'where' => 'KMCT College of Engineering',
        'when'  => 'Sep 2014 – Nov 2015',
        'high'  => [
            'Taught computer science and mentored final-year projects',
            'Drove industry collaboration and student placements',
            'Produced documentation and reference code in JavaScript, Java and Oracle',
        ],
    ],
    [
        'role'  => 'DB Admin & Programmer',
        'where' => 'Quadra Software Systems',
        'when'  => 'Dec 2011 – Aug 2012',
        'high'  => [
            'ASP.NET application development alongside database administration',
            'Delivered the NEON construction ERP project',
            'Unit and integration testing to harden release reliability',
        ],
    ],
];

$EDUCATION = [
    [
        'role'  => 'M.Tech — Computer Science',
        'where' => 'KMCT College of Engineering, Calicut University',
        'when'  => 'Jan 2014',
        'high'  => ['Postgraduate specialisation in systems and software engineering'],
    ],
    [
        'role'  => 'B.E. — Computer Science',
        'where' => 'Dr. Pauls Engineering College, Anna University',
        'when'  => 'Jan 2011',
        'high'  => ['Foundations in computer science and engineering'],
    ],
];

$CERTIFICATIONS = [
    'Microsoft Certified: Azure Administrator Associate (AZ-104)',
    'Microsoft Certified: Azure Security Engineer Associate (AZ-500)',
    'CompTIA Linux+',
];

$DOMAINS = [
    [
        'num'   => '01',
        'title' => 'Platform & Middleware',
        'items' => [
            'JBoss, WebSphere, WebLogic, Tomcat',
            'Apache HTTPD, IHS, OHS, JBCS',
            'mod_jk, mod_proxy, proxy balancer, mod_cluster',
            'MySQL, Oracle 12c/19c, MSSQL, PostgreSQL 16',
        ],
    ],
    [
        'num'   => '02',
        'title' => 'Cloud & Containers',
        'items' => [
            'Azure IaaS, PaaS, Sentinel, Site Recovery',
            'Kubernetes and OpenShift in production',
            'Docker image build and hardening',
            'On-premises ↔ Azure disaster recovery',
        ],
    ],
    [
        'num'   => '03',
        'title' => 'Performance & Observability',
        'items' => [
            'JMeter and ab load modelling',
            'Elasticsearch, Kibana, FluentD, Grafana',
            'AppDynamics, Instana, Dynatrace, Glowroot',
            'JVM heap and thread dump analysis',
        ],
    ],
    [
        'num'   => '04',
        'title' => 'DevSecOps & Delivery',
        'items' => [
            'Jenkins and GitLab CI pipelines',
            'SonarQube, Trivy, Dependency-Track gates',
            'Shell and PowerShell automation',
            'Release planning and DR drill execution',
        ],
    ],
];

$METRICS = [
    ['value' => '13+',  'label' => 'Years across infrastructure and middleware'],
    ['value' => '11',   'label' => 'Engineers led on a platform team'],
    ['value' => '10+',  'label' => 'Banking platform implementations'],
    ['value' => 'AZ-104 / 500', 'label' => 'Azure administration and security certified'],
];
