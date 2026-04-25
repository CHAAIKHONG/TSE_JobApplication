<?php
$activePage  = 'dashboard';
$pageTitle   = 'Dashboard — ApplyGo';

// ── Current user (replace with session/DB data) ──
$currentUser = [
    'name'        => 'Jane Doe',
    'initials'    => 'JD',
    'notif_count' => 3,
];

// ── Sample job listings (replace with DB query) ──
$jobs = [
    [
        'id'          => 1,
        'position'    => 'Senior Frontend Engineer',
        'company'     => 'Stripe',
        'company_abbr'=> 'Str',
        'description' => 'Build and scale world-class payment interfaces used by millions globally. Work on complex UI challenges, collaborate with world-class designers, and ship features with real impact.',
        'salary'      => '$140K – $175K',
        'salary_period'=> 'per year',
        'tags'        => ['remote', 'new'],
    ],
    [
        'id'          => 2,
        'position'    => 'Product Manager, Growth',
        'company'     => 'LinkedIn',
        'company_abbr'=> 'Lin',
        'description' => 'Drive growth strategy across LinkedIn\'s core professional network. Define the roadmap, partner with engineering and data science, and influence product direction at scale.',
        'salary'      => '$160K – $200K',
        'salary_period'=> 'per year',
        'tags'        => ['onsite'],
    ],
    [
        'id'          => 3,
        'position'    => 'UX / Product Designer',
        'company'     => 'Figma',
        'company_abbr'=> 'Fig',
        'description' => 'Shape the future of design tooling by creating intuitive, beautiful experiences for Figma\'s professional user base. Own end-to-end design for a core product area.',
        'salary'      => '$130K – $165K',
        'salary_period'=> 'per year',
        'tags'        => ['remote', 'new'],
    ],
    [
        'id'          => 4,
        'position'    => 'DevRel Engineer',
        'company'     => 'Vercel',
        'company_abbr'=> 'Ver',
        'description' => 'Advocate for Vercel\'s platform and empower developers worldwide. Create content, give talks, build demos, and serve as the bridge between product and the developer community.',
        'salary'      => '$120K – $150K',
        'salary_period'=> 'per year',
        'tags'        => ['remote'],
    ],
    [
        'id'          => 5,
        'position'    => 'ML Research Engineer',
        'company'     => 'Archetype AI',
        'company_abbr'=> 'Arc',
        'description' => 'Work on cutting-edge physical AI research — training large multimodal models on sensor data. Ideal for someone who loves the intersection of research and high-performance systems.',
        'salary'      => '$180K – $240K',
        'salary_period'=> 'per year',
        'tags'        => ['onsite', 'urgent'],
    ],
    [
        'id'          => 6,
        'position'    => 'Backend Engineer, Platform',
        'company'     => 'Notion',
        'company_abbr'=> 'Not',
        'description' => 'Scale Notion\'s infrastructure to support 100M+ users. Work on distributed systems, performance optimisation, and platform reliability. Strong CS fundamentals required.',
        'salary'      => '$150K – $190K',
        'salary_period'=> 'per year',
        'tags'        => ['remote'],
    ],
    [
        'id'          => 7,
        'position'    => 'Data Scientist, Trust',
        'company'     => 'Airbnb',
        'company_abbr'=> 'Air',
        'description' => 'Use data science and ML to keep Airbnb\'s marketplace safe and trusted. Build fraud detection models, run experiments, and derive insights that protect hosts and guests at scale.',
        'salary'      => '$145K – $180K',
        'salary_period'=> 'per year',
        'tags'        => ['onsite'],
    ],
    [
        'id'          => 8,
        'position'    => 'Full-Stack Engineer',
        'company'     => 'Linear',
        'company_abbr'=> 'Lin',
        'description' => 'Join a small, elite team building the project management tool top engineering teams rely on. Contribute across the full stack in a fast-paced, high-ownership environment.',
        'salary'      => '$150K – $200K',
        'salary_period'=> 'per year',
        'tags'        => ['remote', 'new'],
    ],
    [
        'id'          => 9,
        'position'    => 'Engineering Manager',
        'company'     => 'Shopify',
        'company_abbr'=> 'Sho',
        'description' => 'Lead a high-performing team of engineers working on Shopify\'s Checkout — the highest-traffic area of the platform. Drive technical excellence, team growth, and delivery at scale.',
        'salary'      => '$190K – $230K',
        'salary_period'=> 'per year',
        'tags'        => ['onsite', 'urgent'],
    ],
];

// ── Badge config ──
$badgeConfig = [
    'new'    => ['label' => 'New',     'class' => 'badge--new'],
    'remote' => ['label' => 'Remote',  'class' => 'badge--remote'],
    'urgent' => ['label' => 'Urgent',  'class' => 'badge--urgent'],
    'onsite' => ['label' => 'On-site', 'class' => 'badge--onsite'],
];

// ── Stats (replace with real DB counts) ──
$stats = [
    ['value' => 3,   'label' => 'Applied',   'accent' => true],
    ['value' => 8,   'label' => 'Saved',     'accent' => false],
    ['value' => 1,   'label' => 'Interview', 'accent' => false],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <!-- Fonts loaded inside navbar.php, but safe to also declare here -->
  <style>
    /* ── Design Tokens ── */
    :root {
      --ink:       #0f0f0f;
      --paper:     #faf9f7;
      --surface:   #f2efea;
      --accent:    #e85d26;
      --accent-lt: #fdf0ea;
      --mid:       #6b6560;
      --border:    #e2ddd8;
      --nav-h:     64px;
      --radius:    12px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      background: var(--paper);
      color: var(--ink);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Main wrapper ── */
    .page-main {
      flex: 1;
      max-width: 1280px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 32px 64px;
    }

    /* ── Hero ── */
    .dash-hero {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 24px;
      margin-bottom: 40px;
      padding-bottom: 32px;
      border-bottom: 1.5px solid var(--border);
    }

    .dash-hero__eyebrow {
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 2px;
      color: var(--accent);
      margin-bottom: 8px;
    }

    .dash-hero__title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(28px, 4vw, 42px);
      font-weight: 800;
      line-height: 1.1; letter-spacing: -1.5px;
    }

    .dash-hero__title em {
      font-style: italic; font-weight: 400; color: var(--mid);
    }

    .dash-hero__meta {
      margin-top: 12px; font-size: 14px; color: var(--mid);
    }

    .dash-hero__meta strong { color: var(--ink); font-weight: 600; }

    .dash-hero__stats { display: flex; gap: 24px; flex-shrink: 0; }

    .stat-chip {
      display: flex; flex-direction: column; align-items: center;
      background: var(--surface);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 14px 20px; min-width: 80px;
    }

    .stat-chip__number {
      font-family: 'Syne', sans-serif;
      font-size: 24px; font-weight: 800;
      letter-spacing: -1px;
    }

    .stat-chip__label {
      font-size: 11px; color: var(--mid);
      text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px;
    }

    .stat-chip--accent { background: var(--accent); border-color: var(--accent); }
    .stat-chip--accent .stat-chip__number,
    .stat-chip--accent .stat-chip__label { color: #fff; }

    /* ── Filters ── */
    .dash-filters {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 28px; flex-wrap: wrap;
    }

    .filter-label {
      font-size: 12px; font-weight: 600; color: var(--mid);
      text-transform: uppercase; letter-spacing: 1px; margin-right: 4px;
    }

    .filter-pill {
      padding: 6px 14px;
      border: 1.5px solid var(--border);
      border-radius: 100px;
      font-size: 13px; font-weight: 500; color: var(--mid);
      background: transparent; cursor: pointer;
      transition: all 0.2s;
      font-family: 'DM Sans', sans-serif;
    }

    .filter-pill:hover { border-color: var(--ink); color: var(--ink); }
    .filter-pill.active { background: var(--ink); border-color: var(--ink); color: #fff; }

    .dash-filters__search { margin-left: auto; position: relative; }

    .dash-filters__search input {
      padding: 7px 14px 7px 36px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 13px; font-family: 'DM Sans', sans-serif;
      background: var(--paper); color: var(--ink);
      width: 220px; outline: none;
      transition: border-color 0.2s;
    }

    .dash-filters__search input:focus { border-color: var(--ink); }
    .dash-filters__search input::placeholder { color: var(--mid); }

    .dash-filters__search svg {
      position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
      width: 14px; height: 14px;
      stroke: var(--mid); fill: none; stroke-width: 2;
      pointer-events: none;
    }

    /* ── Job Grid ── */
    .job-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }

    /* ── Job Card ── */
    .job-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      display: flex; flex-direction: column;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
      animation: cardIn 0.4s ease both;
      position: relative; overflow: hidden;
    }

    .job-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--border);
      transition: background 0.2s;
    }

    .job-card:hover { border-color: var(--ink); box-shadow: 0 8px 32px rgba(0,0,0,0.07); transform: translateY(-2px); }
    .job-card:hover::before { background: var(--accent); }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    <?php for ($i = 1; $i <= count($jobs); $i++): ?>
    .job-card:nth-child(<?= $i ?>) { animation-delay: <?= $i * 0.05 ?>s; }
    <?php endfor; ?>

    .job-card__top {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 14px;
    }

    .job-card__co-logo {
      width: 40px; height: 40px;
      border-radius: 8px;
      background: var(--surface); border: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 13px;
      color: var(--ink); letter-spacing: -0.5px; flex-shrink: 0;
    }

    .job-card__badges { display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end; }

    .badge {
      padding: 3px 8px; border-radius: 100px;
      font-size: 10px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.5px;
    }

    .badge--new    { background: #e8f5e9; color: #2e7d32; }
    .badge--remote { background: #e3f2fd; color: #1565c0; }
    .badge--urgent { background: #fff3e0; color: #e65100; }
    .badge--onsite { background: var(--surface); color: var(--mid); }

    .job-card__position {
      font-family: 'Syne', sans-serif;
      font-size: 17px; font-weight: 700;
      letter-spacing: -0.4px; line-height: 1.2;
      margin-bottom: 6px;
    }

    .job-card__company {
      font-size: 12px; font-weight: 600; color: var(--mid);
      text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;
    }

    .job-card__divider { height: 1px; background: var(--border); margin: 12px 0; }

    .job-card__desc {
      font-size: 13px; line-height: 1.6; color: var(--mid); flex: 1;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .job-card__salary-row {
      display: flex; align-items: center; justify-content: space-between;
      margin-top: 14px; padding-top: 14px;
      border-top: 1px solid var(--border);
    }

    .job-card__salary-label {
      font-size: 10px; text-transform: uppercase;
      letter-spacing: 1px; color: var(--mid); font-weight: 600;
    }

    .job-card__salary-amount {
      font-family: 'Syne', sans-serif;
      font-size: 18px; font-weight: 800;
      letter-spacing: -0.5px; line-height: 1.1;
    }

    .job-card__salary-period { font-size: 11px; color: var(--mid); }

    /* Apply button */
    .btn-apply {
      padding: 9px 18px;
      background: var(--ink); color: #fff;
      border: none; border-radius: 8px;
      font-family: 'Syne', sans-serif;
      font-size: 13px; font-weight: 700; letter-spacing: 0.3px;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      display: inline-flex; align-items: center; gap: 6px;
      text-decoration: none;
    }

    .btn-apply:hover { background: var(--accent); transform: translateY(-1px); }
    .btn-apply svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; transition: transform 0.2s; }
    .btn-apply:hover svg { transform: translate(2px, -2px); }

    /* ── Pagination ── */
    .dash-pagination {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      margin-top: 40px; padding-top: 32px;
      border-top: 1.5px solid var(--border);
    }

    .page-btn {
      width: 36px; height: 36px;
      border: 1.5px solid var(--border); border-radius: 8px;
      background: transparent;
      font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
      color: var(--mid); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s;
    }

    .page-btn:hover { border-color: var(--ink); color: var(--ink); }
    .page-btn.active { background: var(--ink); border-color: var(--ink); color: #fff; }
    .page-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }

    /* ── Toast ── */
    #toast {
      position: fixed; bottom: 24px; right: 24px;
      background: var(--ink); color: #fff;
      padding: 12px 20px; border-radius: 10px;
      font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      transform: translateY(80px); opacity: 0;
      transition: transform 0.3s ease, opacity 0.3s ease;
      z-index: 999; display: flex; align-items: center; gap: 10px;
      pointer-events: none;
    }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
      .job-grid { grid-template-columns: repeat(2, 1fr); }
      .dash-hero__stats { display: none; }
    }

    @media (max-width: 640px) {
      .job-grid { grid-template-columns: 1fr; }
      .page-main { padding: 24px 16px 48px; }
      .dash-hero { flex-direction: column; align-items: flex-start; }
      .dash-filters__search { margin-left: 0; width: 100%; }
      .dash-filters__search input { width: 100%; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <!-- Hero -->
    <div class="dash-hero">
      <div class="dash-hero__text">
        <p class="dash-hero__eyebrow">
          Good <?php
            $h = (int)date('H');
            echo $h < 12 ? 'morning' : ($h < 18 ? 'afternoon' : 'evening');
          ?>, <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?> 👋
        </p>
        <h1 class="dash-hero__title">Find your next<br><em>great opportunity</em></h1>
        <p class="dash-hero__meta"><strong>247 new listings</strong> added this week</p>
      </div>
      <div class="dash-hero__stats">
        <?php foreach ($stats as $stat): ?>
          <div class="stat-chip <?= $stat['accent'] ? 'stat-chip--accent' : '' ?>">
            <span class="stat-chip__number"><?= $stat['value'] ?></span>
            <span class="stat-chip__label"><?= htmlspecialchars($stat['label']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Filters -->
    <div class="dash-filters">
      <span class="filter-label">Filter:</span>
      <button class="filter-pill active" onclick="filterJobs(this,'all')">All Jobs</button>
      <button class="filter-pill" onclick="filterJobs(this,'remote')">Remote</button>
      <button class="filter-pill" onclick="filterJobs(this,'onsite')">On-site</button>
      <button class="filter-pill" onclick="filterJobs(this,'new')">New</button>
      <div class="dash-filters__search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" placeholder="Search jobs…" oninput="searchJobs(this.value)" aria-label="Search jobs" />
      </div>
    </div>

    <!-- Job Grid (PHP-rendered) -->
    <div class="job-grid" id="jobGrid">
      <?php foreach ($jobs as $job): ?>
        <div class="job-card" data-tags="<?= htmlspecialchars(implode(' ', $job['tags'])) ?>">

          <!-- Top: logo + badges -->
          <div class="job-card__top">
            <div class="job-card__co-logo" aria-hidden="true">
              <?= htmlspecialchars($job['company_abbr']) ?>
            </div>
            <div class="job-card__badges">
              <?php foreach ($job['tags'] as $tag):
                if (isset($badgeConfig[$tag])):
              ?>
                <span class="badge <?= $badgeConfig[$tag]['class'] ?>">
                  <?= $badgeConfig[$tag]['label'] ?>
                </span>
              <?php
                endif;
              endforeach; ?>
            </div>
          </div>

          <!-- Cell 1: Position -->
          <p class="job-card__company"><?= htmlspecialchars($job['company']) ?></p>
          <h2 class="job-card__position"><?= htmlspecialchars($job['position']) ?></h2>

          <div class="job-card__divider"></div>

          <!-- Cell 2: Description -->
          <p class="job-card__desc"><?= htmlspecialchars($job['description']) ?></p>

          <!-- Cell 3: Salary + Apply -->
          <div class="job-card__salary-row">
            <div class="job-card__salary">
              <span class="job-card__salary-label">Salary</span>
              <span class="job-card__salary-amount"><?= htmlspecialchars($job['salary']) ?></span>
              <span class="job-card__salary-period"><?= htmlspecialchars($job['salary_period']) ?></span>
            </div>
            <button
              class="btn-apply"
              onclick="applyJob(this, <?= htmlspecialchars(json_encode($job['position'])) ?>, <?= (int)$job['id'] ?>)"
              data-job-id="<?= (int)$job['id'] ?>"
              aria-label="Apply for <?= htmlspecialchars($job['position']) ?> at <?= htmlspecialchars($job['company']) ?>">
              Apply
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <line x1="7" y1="17" x2="17" y2="7"/>
                <polyline points="7 7 17 7 17 17"/>
              </svg>
            </button>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <nav class="dash-pagination" aria-label="Job listing pages">
      <button class="page-btn" aria-label="Previous page">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="page-btn active" aria-current="page">1</button>
      <button class="page-btn">2</button>
      <button class="page-btn">3</button>
      <span style="color:var(--mid);font-size:13px;padding:0 4px" aria-hidden="true">…</span>
      <button class="page-btn">12</button>
      <button class="page-btn" aria-label="Next page">
        <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </nav>

  </main>

  <?php include '../assets/include/user_footer.php'; ?>


  <!-- Toast notification -->
  <div id="toast" role="status" aria-live="polite">
    <span style="color:var(--accent);font-size:16px" aria-hidden="true">✓</span>
    <span id="toast-msg"></span>
  </div>

  <script>
    // ── Filter pills ──
    function filterJobs(btn, tag) {
      document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.job-card').forEach(card => {
        if (tag === 'all') {
          card.style.display = '';
        } else {
          card.style.display = (card.dataset.tags || '').includes(tag) ? '' : 'none';
        }
      });
    }

    // ── Search ──
    function searchJobs(query) {
      const q = query.toLowerCase().trim();
      document.querySelectorAll('.job-card').forEach(card => {
        card.style.display = (!q || card.textContent.toLowerCase().includes(q)) ? '' : 'none';
      });
    }

    // ── Apply ──
    function applyJob(btn, title, jobId) {
      // TODO: send real AJAX request to apply.php
      // fetch('apply.php', { method: 'POST', body: new URLSearchParams({ job_id: jobId }) })

      btn.innerHTML = 'Applied ✓';
      btn.style.background = '#2e7d32';
      btn.style.pointerEvents = 'none';
      showToast('Applied for "' + title + '"');
    }

    // ── Toast ──
    function showToast(msg) {
      const toast = document.getElementById('toast');
      document.getElementById('toast-msg').textContent = msg;
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
      setTimeout(() => {
        toast.style.transform = 'translateY(80px)';
        toast.style.opacity = '0';
      }, 3000);
    }

    // ── Pagination ──
    document.querySelectorAll('.page-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        if (this.querySelector('svg')) return;
        document.querySelectorAll('.page-btn').forEach(b => {
          b.classList.remove('active');
          b.removeAttribute('aria-current');
        });
        this.classList.add('active');
        this.setAttribute('aria-current', 'page');
      });
    });
  </script>

</body>
</html>