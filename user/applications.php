<?php
session_start();

$activePage = 'applications';
$pageTitle  = 'My Applications — ApplyGo';

require_once '../database/db.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// ── Fetch current user ───────────────────────────────────────────────────────
$stmt = $conn->prepare('SELECT user_id, name, email FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ── Fetch applications JOIN jobs ─────────────────────────────────────────────
// applications: application_id, user_id, job_id, admin_id, status, applied_at
// jobs:         job_id, admin_id, jobtitle, position, salary, details, created_at, badge
$appStmt = $conn->prepare("
    SELECT
        a.application_id,
        a.job_id,
        a.status,
        a.applied_at,
        j.jobtitle,
        j.position,
        j.salary,
        j.badge
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
");
$appStmt->bind_param('i', $userId);
$appStmt->execute();
$applications = $appStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appStmt->close();

// ── Stats ────────────────────────────────────────────────────────────────────
$total    = count($applications);
$pending  = count(array_filter($applications, fn($a) => strtolower($a['status']) === 'pending'));
$accepted = count(array_filter($applications, fn($a) => strtolower($a['status']) === 'accepted'));
$rejected = count(array_filter($applications, fn($a) => strtolower($a['status']) === 'rejected'));

// ── Status config ─────────────────────────────────────────────────────────────
$statusConfig = [
    'pending'  => ['label' => 'Pending',  'class' => 'status--pending'],
    'accepted' => ['label' => 'Accepted', 'class' => 'status--accepted'],
    'rejected' => ['label' => 'Rejected', 'class' => 'status--rejected'],
];

// ── Badge config ──────────────────────────────────────────────────────────────
$badgeConfig = [
    'new'    => ['label' => 'New',     'class' => 'badge--new'],
    'remote' => ['label' => 'Remote',  'class' => 'badge--remote'],
    'urgent' => ['label' => 'Urgent',  'class' => 'badge--urgent'],
    'onsite' => ['label' => 'On-site', 'class' => 'badge--onsite'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink:       #0f0f0f;
      --paper:     #faf9f7;
      --surface:   #f2efea;
      --accent:    #e85d26;
      --accent-lt: #fdf0ea;
      --mid:       #6b6560;
      --border:    #e2ddd8;
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

    .page-main {
      flex: 1; max-width: 1280px; width: 100%;
      margin: 0 auto; padding: 40px 32px 80px;
    }

    /* ── Page Header ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 24px; margin-bottom: 36px;
      padding-bottom: 32px; border-bottom: 1.5px solid var(--border);
    }

    .page-header__eyebrow {
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 2px;
      color: var(--accent); margin-bottom: 8px;
    }

    .page-header__title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(26px, 4vw, 40px);
      font-weight: 800; line-height: 1.1; letter-spacing: -1.5px;
    }

    .page-header__title em { font-style: italic; font-weight: 400; color: var(--mid); }

    .page-header__meta {
      margin-top: 10px; font-size: 14px; color: var(--mid);
    }

    .page-header__meta strong { color: var(--ink); font-weight: 600; }

    /* ── Stats Row ── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 36px;
    }

    .stat-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 22px 24px;
      position: relative; overflow: hidden;
      transition: border-color 0.2s, transform 0.2s;
    }

    .stat-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0;
      height: 3px; background: var(--border);
    }

    .stat-card:hover { border-color: var(--ink); transform: translateY(-2px); }

    .stat-card--total::before    { background: var(--accent); }
    .stat-card--pending::before  { background: #f59e0b; }
    .stat-card--accepted::before { background: #2e7d32; }
    .stat-card--rejected::before { background: #9e9e9e; }

    .stat-card__label {
      font-size: 11px; font-weight: 600; color: var(--mid);
      text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;
    }

    .stat-card__number {
      font-family: 'Syne', sans-serif;
      font-size: 32px; font-weight: 800; letter-spacing: -1.5px; line-height: 1;
    }

    .stat-card--total    .stat-card__number { color: var(--accent); }
    .stat-card--pending  .stat-card__number { color: #b45309; }
    .stat-card--accepted .stat-card__number { color: #2e7d32; }
    .stat-card--rejected .stat-card__number { color: #757575; }

    /* ── Toolbar ── */
    .table-toolbar {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 18px; flex-wrap: wrap;
    }

    .filter-label {
      font-size: 12px; font-weight: 600; color: var(--mid);
      text-transform: uppercase; letter-spacing: 1px; margin-right: 4px;
    }

    .filter-pill {
      padding: 6px 14px; border: 1.5px solid var(--border); border-radius: 100px;
      font-size: 13px; font-weight: 500; color: var(--mid);
      background: transparent; cursor: pointer; transition: all 0.2s;
      font-family: 'DM Sans', sans-serif;
    }

    .filter-pill:hover { border-color: var(--ink); color: var(--ink); }
    .filter-pill.active { background: var(--ink); border-color: var(--ink); color: #fff; }

    .toolbar-search { margin-left: auto; position: relative; }

    .toolbar-search input {
      padding: 7px 14px 7px 36px; border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 13px; font-family: 'DM Sans', sans-serif;
      background: var(--paper); color: var(--ink);
      width: 220px; outline: none; transition: border-color 0.2s;
    }

    .toolbar-search input:focus { border-color: var(--ink); }
    .toolbar-search input::placeholder { color: var(--mid); }

    .toolbar-search svg {
      position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
      width: 14px; height: 14px; stroke: var(--mid); fill: none; stroke-width: 2;
      pointer-events: none;
    }

    /* ── Table ── */
    .table-wrap {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    table {
      width: 100%; border-collapse: collapse;
    }

    thead {
      background: var(--surface);
      border-bottom: 1.5px solid var(--border);
    }

    th {
      padding: 13px 20px;
      font-size: 11px; font-weight: 700; color: var(--mid);
      text-transform: uppercase; letter-spacing: 1px;
      text-align: left; white-space: nowrap;
    }

    th.th-center { text-align: center; }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
      animation: rowIn 0.35s ease both;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--surface); }

    @keyframes rowIn {
      from { opacity: 0; transform: translateX(-8px); }
      to   { opacity: 1; transform: translateX(0); }
    }

    td {
      padding: 16px 20px;
      font-size: 14px; vertical-align: middle;
    }

    /* Job cell */
    .job-cell { display: flex; align-items: center; gap: 14px; }

    .job-abbr {
      width: 40px; height: 40px; border-radius: 8px;
      background: var(--surface); border: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 12px;
      color: var(--ink); flex-shrink: 0; letter-spacing: -0.5px;
    }

    .job-cell__title {
      font-family: 'Syne', sans-serif;
      font-size: 14px; font-weight: 700; letter-spacing: -0.3px;
      line-height: 1.2; color: var(--ink);
    }

    .job-cell__type {
      font-size: 12px; color: var(--mid); font-weight: 500;
      text-transform: uppercase; letter-spacing: 0.6px; margin-top: 2px;
    }

    /* Badge */
    .job-badge {
      padding: 3px 9px; border-radius: 100px;
      font-size: 10px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.5px;
      display: inline-block;
    }

    .badge--new    { background: #e8f5e9; color: #2e7d32; }
    .badge--remote { background: #e3f2fd; color: #1565c0; }
    .badge--urgent { background: #fff3e0; color: #e65100; }
    .badge--onsite { background: var(--surface); color: var(--mid); }
    .badge--none   { color: var(--mid); font-size: 13px; }

    /* Salary */
    .salary-val {
      font-family: 'Syne', sans-serif;
      font-size: 15px; font-weight: 800; letter-spacing: -0.5px;
    }

    .salary-na { color: var(--mid); font-size: 13px; }

    /* Date */
    .date-main { font-size: 14px; font-weight: 600; color: var(--ink); }
    .date-sub  { font-size: 12px; color: var(--mid); margin-top: 1px; }

    /* Status badge */
    .td-center { text-align: center; }

    .status-badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 13px; border-radius: 100px;
      font-size: 12px; font-weight: 600;
    }

    .status-badge::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%;
      background: currentColor; opacity: 0.7;
    }

    .status--pending  { background: #fff8ee; color: #b45309; }
    .status--accepted { background: #edf7ee; color: #2e7d32; }
    .status--rejected { background: #f5f5f5; color: #757575; }

    /* Empty state */
    .empty-row td {
      text-align: center; padding: 72px 32px; color: var(--mid);
    }

    .empty-row h3 {
      font-family: 'Syne', sans-serif;
      font-size: 18px; font-weight: 700; color: var(--ink);
      margin-bottom: 8px;
    }

    .empty-row p { font-size: 14px; }

    .empty-row a {
      display: inline-block; margin-top: 18px;
      padding: 10px 22px; background: var(--ink); color: #fff;
      border-radius: 8px; text-decoration: none;
      font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
      transition: background 0.2s;
    }

    .empty-row a:hover { background: var(--accent); }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
      .stats-row { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
      th.hide-mobile, td.hide-mobile { display: none; }
    }

    @media (max-width: 640px) {
      .page-main { padding: 24px 16px 56px; }
      .stats-row { grid-template-columns: repeat(2, 1fr); }
      .page-header { flex-direction: column; align-items: flex-start; }
      .toolbar-search { margin-left: 0; width: 100%; }
      .toolbar-search input { width: 100%; }
      th.hide-sm, td.hide-sm { display: none; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <!-- ── Page Header ── -->
    <div class="page-header">
      <div>
        <p class="page-header__eyebrow">Track your progress</p>
        <h1 class="page-header__title">My <em>Applications</em></h1>
        <p class="page-header__meta">
          <strong><?= $total ?> application<?= $total !== 1 ? 's' : '' ?></strong> submitted so far
        </p>
      </div>
    </div>

    <!-- ── Stats Row ── -->
    <div class="stats-row">
      <div class="stat-card stat-card--total">
        <div class="stat-card__label">Total Applied</div>
        <div class="stat-card__number"><?= $total ?></div>
      </div>
      <div class="stat-card stat-card--pending">
        <div class="stat-card__label">Pending</div>
        <div class="stat-card__number"><?= $pending ?></div>
      </div>
      <div class="stat-card stat-card--accepted">
        <div class="stat-card__label">Accepted</div>
        <div class="stat-card__number"><?= $accepted ?></div>
      </div>
      <div class="stat-card stat-card--rejected">
        <div class="stat-card__label">Rejected</div>
        <div class="stat-card__number"><?= $rejected ?></div>
      </div>
    </div>

    <!-- ── Toolbar ── -->
    <div class="table-toolbar">
      <span class="filter-label">Filter:</span>
      <button class="filter-pill active" onclick="filterRows(this,'all')">All</button>
      <button class="filter-pill" onclick="filterRows(this,'pending')">Pending</button>
      <button class="filter-pill" onclick="filterRows(this,'accepted')">Accepted</button>
      <button class="filter-pill" onclick="filterRows(this,'rejected')">Rejected</button>
      <div class="toolbar-search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" placeholder="Search jobs…" oninput="searchRows(this.value)" aria-label="Search applications" />
      </div>
    </div>

    <!-- ── Table ── -->
    <div class="table-wrap">
      <table id="appTable">
        <thead>
          <tr>
            <th>Job</th>
            <th class="hide-mobile">Badge</th>
            <th class="hide-sm">Salary</th>
            <th class="hide-mobile">Applied On</th>
            <th class="th-center">Status</th>
          </tr>
        </thead>
        <tbody id="appBody">

          <?php if (empty($applications)): ?>
            <tr class="empty-row">
              <td colspan="5">
                <h3>No applications yet</h3>
                <p>Start applying to jobs and track your progress here.</p>
                <a href="dashboard.php">Browse Jobs →</a>
              </td>
            </tr>

          <?php else: ?>

            <?php foreach ($applications as $i => $app):
              $status    = strtolower(trim($app['status'] ?? 'pending'));
              $statusCfg = $statusConfig[$status] ?? ['label' => ucfirst($status), 'class' => 'status--pending'];
              $abbr      = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $app['jobtitle']), 0, 3));
              $date      = new DateTime($app['applied_at']);

              // Parse badge from jobs table
              $badgeKey  = strtolower(trim($app['badge'] ?? ''));
              $badgeCfg  = $badgeConfig[$badgeKey] ?? null;

              $delay = ($i + 1) * 0.04;
            ?>
              <tr data-status="<?= htmlspecialchars($status) ?>" style="animation-delay:<?= $delay ?>s">

                <!-- Job -->
                <td>
                  <div class="job-cell">
                    <div class="job-abbr"><?= htmlspecialchars($abbr ?: '?') ?></div>
                    <div>
                      <div class="job-cell__title"><?= htmlspecialchars($app['jobtitle']) ?></div>
                      <div class="job-cell__type"><?= htmlspecialchars($app['position']) ?></div>
                    </div>
                  </div>
                </td>

                <!-- Badge -->
                <td class="hide-mobile">
                  <?php if ($badgeCfg): ?>
                    <span class="job-badge <?= $badgeCfg['class'] ?>"><?= $badgeCfg['label'] ?></span>
                  <?php else: ?>
                    <span class="badge--none">—</span>
                  <?php endif; ?>
                </td>

                <!-- Salary -->
                <td class="hide-sm">
                  <?php if (!empty($app['salary'])): ?>
                    <span class="salary-val"><?= htmlspecialchars($app['salary']) ?></span>
                  <?php else: ?>
                    <span class="salary-na">—</span>
                  <?php endif; ?>
                </td>

                <!-- Applied On -->
                <td class="hide-mobile">
                  <div class="date-main"><?= $date->format('d M Y') ?></div>
                  <div class="date-sub"><?= $date->format('h:i A') ?></div>
                </td>

                <!-- Status -->
                <td class="td-center">
                  <span class="status-badge <?= $statusCfg['class'] ?>">
                    <?= htmlspecialchars($statusCfg['label']) ?>
                  </span>
                </td>

              </tr>
            <?php endforeach; ?>

          <?php endif; ?>

        </tbody>
      </table>
    </div>

  </main>

  <script>
    function filterRows(btn, status) {
      document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('#appBody tr[data-status]').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
      });
    }

    function searchRows(query) {
      const q = query.toLowerCase().trim();
      document.querySelectorAll('#appBody tr[data-status]').forEach(row => {
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
      });
    }
  </script>

</body>
</html>