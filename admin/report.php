<?php
session_start();
require_once '../database/db.php';

// --- Total Stats ---
$total_jobs      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM jobs"))['c'];
$total_users     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_apps      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM applications"))['c'];
$total_pending   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM applications WHERE status='Pending'"))['c'];
$total_accepted  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM applications WHERE status='Accepted'"))['c'];
$total_rejected  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM applications WHERE status='Rejected'"))['c'];

$accept_rate = $total_apps > 0 ? round(($total_accepted / $total_apps) * 100, 1) : 0;
$reject_rate = $total_apps > 0 ? round(($total_rejected / $total_apps) * 100, 1) : 0;

// --- Applications per Job ---
$apps_per_job_res = mysqli_query($conn, "
    SELECT j.jobtitle, 
           COUNT(a.application_id) as total,
           SUM(a.status='Accepted') as accepted,
           SUM(a.status='Rejected') as rejected,
           SUM(a.status='Pending') as pending
    FROM jobs j
    LEFT JOIN applications a ON j.job_id = a.job_id
    GROUP BY j.job_id
    ORDER BY total DESC
    LIMIT 8
");
$apps_per_job = [];
while ($r = mysqli_fetch_assoc($apps_per_job_res)) $apps_per_job[] = $r;

// --- Monthly application trend (last 6 months) ---
$monthly_res = mysqli_query($conn, "
    SELECT DATE_FORMAT(applied_at, '%b %Y') as month_label,
           DATE_FORMAT(applied_at, '%Y-%m') as month_key,
           COUNT(*) as total
    FROM applications
    WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_key
    ORDER BY month_key ASC
");
$monthly_labels = [];
$monthly_data = [];
while ($r = mysqli_fetch_assoc($monthly_res)) {
    $monthly_labels[] = $r['month_label'];
    $monthly_data[]   = (int)$r['total'];
}

// --- Top applied jobs ---
$top_jobs_res = mysqli_query($conn, "
    SELECT j.jobtitle, j.position, COUNT(a.application_id) as total
    FROM jobs j
    LEFT JOIN applications a ON j.job_id = a.job_id
    GROUP BY j.job_id
    ORDER BY total DESC
    LIMIT 5
");
$top_jobs = [];
while ($r = mysqli_fetch_assoc($top_jobs_res)) $top_jobs[] = $r;

// --- Badge distribution ---
$badge_res = mysqli_query($conn, "SELECT badge, COUNT(*) as c FROM users GROUP BY badge");
$badge_data = [];
while ($r = mysqli_fetch_assoc($badge_res)) $badge_data[$r['badge']] = (int)$r['c'];

// --- Recent activity log (last 10 notifications as proxy) ---
$activity_res = mysqli_query($conn, "
    SELECT n.message, n.created_at, u.name
    FROM notifications n
    JOIN users u ON n.user_id = u.user_id
    ORDER BY n.created_at DESC
    LIMIT 8
");
$activities = [];
while ($r = mysqli_fetch_assoc($activity_res)) $activities[] = $r;

$max_apps = count($apps_per_job) > 0 ? max(array_column($apps_per_job, 'total')) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - HR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        :root {
            --color-primary: #e85d26;
            --color-danger: #ff7782;
            --color-success: #41f1b6;
            --color-warning: #ffbb55;
            --color-white: #fff;
            --color-info-dark: #7d8da1;
            --color-info-light: #dce1eb;
            --color-dark: #363949;
            --color-light: rgba(132, 139, 200, 0.18);
            --color-primary-variant: #b44519;
            --color-dark-variant: #677483;
            --color-background: #f6f6f9;
        }
        * { margin: 0; padding: 0; outline: 0; appearance: none; border: 0; text-decoration: none; list-style: none; box-sizing: border-box; }
        html { font-size: 14px; }
        body { width: 100vw; min-height: 100vh; font-family: poppins, sans-serif; font-size: 0.88rem; background: var(--color-background); overflow-x: hidden; color: var(--color-dark); }
        .container { display: grid; width: 96%; margin: 0 auto; gap: 1.8rem; grid-template-columns: 15rem auto; }
        a { color: var(--color-dark); }

        h1.page-title { display: inline-block; border: 2px solid var(--color-dark); border-radius: 50px; padding: 0.6rem 1.8rem; color: var(--color-white); background: var(--color-primary); box-shadow: 0 4px 12px rgba(54,57,73,0.15); margin-bottom: 1.5rem; }

        aside { height: 100vh; }
        aside .top { background: white; display: flex; align-items: center; justify-content: center; margin-top: 1.4rem; border-radius: 0.8rem; padding: 1.5rem; border: 1px solid var(--color-light); }
        aside .logo { display: flex; gap: 12px; align-items: center; }
        .logo-mark { width: 38px; height: 38px; background: #111; border-radius: 10px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; flex-shrink: 0; box-shadow: 0 6px 12px rgba(232,93,38,0.25); }
        .logo-mark::after { content: ''; position: absolute; bottom: 0; right: 0; width: 16px; height: 16px; background: var(--color-primary); border-radius: 8px 0 0 0; }
        .logo-mark svg { width: 18px; height: 18px; position: relative; z-index: 1; stroke: #fff; }
        aside .logo h2 { font-size: 1.6rem; font-weight: 800; margin: 0; letter-spacing: -0.5px; display: flex; gap: 4px; align-items: baseline; }
        aside .logo h2 span.primary { color: var(--color-primary); }
        aside .sidebar { background: white; display: flex; flex-direction: column; height: 86vh; position: relative; top: 1rem; border-radius: 1.2rem; padding-top: 1.5rem; border: 1px solid var(--color-info-light); box-shadow: 0 1rem 3rem rgba(0,0,0,0.02); }
        aside .sidebar a { display: flex; color: var(--color-info-dark); margin: 0.4rem 1.2rem; padding: 0.8rem 1.2rem; gap: 1.2rem; align-items: center; border-radius: 0.8rem; border: 1px solid transparent; transition: all 0.3s ease; }
        aside .sidebar a span { font-size: 1.6rem; }
        aside .sidebar a.active { background: rgba(232,93,38,0.08); color: var(--color-primary); border: 1px solid rgba(232,93,38,0.4); }
        aside .sidebar a:hover { color: var(--color-primary); background: var(--color-light); border-color: var(--color-info-light); transform: translateX(4px); }
        aside .sidebar a.active:hover { transform: none; border-color: var(--color-primary); }
        aside .sidebar a.logout-btn { margin-top: auto; margin-bottom: 1.5rem; border: 2px solid var(--color-info-light); border-radius: 50px; justify-content: center; color: var(--color-info-dark); background: transparent; }
        aside .sidebar a.logout-btn:hover { background: #ffeaea; border-color: var(--color-danger); color: var(--color-danger); transform: none; }

        main { margin-top: 1.4rem; padding-bottom: 3rem; }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.8rem; }
        .kpi-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.2rem; padding: 1.4rem 1.6rem; transition: all 0.25s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 1rem 2rem rgba(0,0,0,0.05); }
        .kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.8rem; }
        .kpi-icon { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .kpi-icon.orange  { background: rgba(232,93,38,0.12); color: var(--color-primary); }
        .kpi-icon.blue    { background: rgba(55,138,221,0.12); color: #185fa5; }
        .kpi-icon.green   { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .kpi-icon.yellow  { background: rgba(255,187,85,0.2);  color: #ba7517; }
        .kpi-label { font-size: 0.78rem; color: var(--color-info-dark); font-weight: 500; }
        .kpi-value { font-size: 2rem; font-weight: 800; color: var(--color-dark); line-height: 1.1; }
        .kpi-sub { font-size: 0.75rem; color: var(--color-dark-variant); margin-top: 0.2rem; }

        /* Charts Row */
        .charts-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.5rem; margin-bottom: 1.8rem; }
        .chart-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.5rem; padding: 1.6rem; }
        .chart-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1.2rem; }
        .chart-wrap { position: relative; height: 220px; }

        /* Donut legend */
        .donut-legend { display: flex; flex-direction: column; gap: 0.6rem; margin-top: 1rem; }
        .legend-item { display: flex; align-items: center; gap: 0.6rem; font-size: 0.82rem; color: var(--color-dark-variant); }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        /* Top Jobs Table */
        .bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.8rem; }
        .table-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.5rem; padding: 1.6rem; }
        .table-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1.2rem; }
        .rank-table { width: 100%; border-collapse: collapse; }
        .rank-table th { font-size: 0.72rem; color: var(--color-info-dark); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.5rem 0.8rem; border-bottom: 1px solid var(--color-info-light); text-align: left; }
        .rank-table td { padding: 0.8rem; border-bottom: 1px solid var(--color-light); font-size: 0.83rem; color: var(--color-dark-variant); vertical-align: middle; }
        .rank-table tr:last-child td { border-bottom: none; }
        .rank-table tr:hover td { background: rgba(132,139,200,0.04); }
        .job-title-cell { font-weight: 600; color: var(--color-dark); }
        .job-position { font-size: 0.75rem; color: var(--color-info-dark); }

        /* Bar chart in table */
        .mini-bar-wrap { display: flex; align-items: center; gap: 0.6rem; }
        .mini-bar-bg { flex: 1; height: 6px; background: var(--color-light); border-radius: 99px; overflow: hidden; }
        .mini-bar-fill { height: 100%; background: var(--color-primary); border-radius: 99px; }
        .mini-bar-count { font-size: 0.8rem; font-weight: 700; color: var(--color-dark); min-width: 20px; text-align: right; }

        /* Activity Log */
        .activity-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.5rem; padding: 1.6rem; margin-bottom: 1.8rem; }
        .activity-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1.2rem; }
        .activity-item { display: flex; align-items: flex-start; gap: 0.9rem; padding: 0.8rem 0; border-bottom: 1px solid var(--color-light); }
        .activity-item:last-child { border-bottom: none; }
        .activity-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary); margin-top: 0.35rem; flex-shrink: 0; }
        .activity-msg { font-size: 0.83rem; color: var(--color-dark-variant); flex: 1; }
        .activity-msg strong { color: var(--color-dark); }
        .activity-time { font-size: 0.75rem; color: var(--color-info-dark); white-space: nowrap; }
        .no-activity { text-align: center; color: var(--color-info-dark); font-style: italic; font-size: 0.85rem; padding: 1rem 0; }
    </style>
</head>
<body>
<div class="container">
    <aside>
        <div class="top">
            <div class="logo">
                <div class="logo-mark">
                    <svg viewBox="0 0 16 16"><path d="M2 12 L8 4 L14 12" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h2>HR<span class="primary">SYSTEM</span></h2>
            </div>
        </div>
        <div class="sidebar">
            <a href="home.php">
                    <span class="material-symbols-sharp">grid_view</span>
                    <h3>Dashboard</h3>
                </a>
                <a href="manage_jobs.php">
                    <span class="material-symbols-sharp">work</span>
                    <h3>Job Management</h3>
                </a>
                <a href="manage_application.php">
                    <span class="material-symbols-sharp">description</span>
                    <h3>Application Management</h3>
                </a>
                <a href="manage_company.php">
                    <span class="material-symbols-sharp">business</span>
                    <h3>Company Management</h3>
                </a>
                <a href="manage_users.php">
                    <span class="material-symbols-sharp">people</span>
                    <h3>User Management</h3>
                </a>
                <a href="report.php" class="active">
                    <span class="material-symbols-sharp">analytics</span>
                    <h3>Reports & Analytics</h3>
                </a>
                <a href="interviews.php">
                    <span class="material-symbols-sharp">schedule</span>
                    <h3>Interviews</h3>
                </a>
                <a href="admin_login.php" class="logout-btn">
                    <span class="material-symbols-sharp">logout</span>
                    <h3>Logout</h3>
                </a>
            </div>
        </aside>

    <main>
        <h1 class="page-title">Reports & Analytics</h1>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-top">
                    <div class="kpi-label">Total Jobs Posted</div>
                    <div class="kpi-icon orange"><span class="material-symbols-sharp">work</span></div>
                </div>
                <div class="kpi-value"><?php echo $total_jobs; ?></div>
                <div class="kpi-sub">Active vacancies</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-top">
                    <div class="kpi-label">Total Applicants</div>
                    <div class="kpi-icon blue"><span class="material-symbols-sharp">group</span></div>
                </div>
                <div class="kpi-value"><?php echo $total_users; ?></div>
                <div class="kpi-sub">Registered users</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-top">
                    <div class="kpi-label">Acceptance Rate</div>
                    <div class="kpi-icon green"><span class="material-symbols-sharp">check_circle</span></div>
                </div>
                <div class="kpi-value"><?php echo $accept_rate; ?>%</div>
                <div class="kpi-sub"><?php echo $total_accepted; ?> of <?php echo $total_apps; ?> applications</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-top">
                    <div class="kpi-label">Pending Review</div>
                    <div class="kpi-icon yellow"><span class="material-symbols-sharp">hourglass_empty</span></div>
                </div>
                <div class="kpi-value"><?php echo $total_pending; ?></div>
                <div class="kpi-sub">Awaiting decision</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>Application Trend (Last 6 Months)</h3>
                <div class="chart-wrap">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Application Status Breakdown</h3>
                <div class="chart-wrap" style="height:160px">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="donut-legend">
                    <div class="legend-item"><div class="legend-dot" style="background:#ffbb55"></div> Pending — <?php echo $total_pending; ?></div>
                    <div class="legend-item"><div class="legend-dot" style="background:#41f1b6"></div> Accepted — <?php echo $total_accepted; ?></div>
                    <div class="legend-item"><div class="legend-dot" style="background:#ff7782"></div> Rejected — <?php echo $total_rejected; ?></div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="bottom-row">
            <!-- Top Applied Jobs -->
            <div class="table-card">
                <h3>Top Applied Positions</h3>
                <table class="rank-table">
                    <thead>
                        <tr><th>#</th><th>Job Title</th><th>Applications</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_jobs)): ?>
                            <?php foreach ($top_jobs as $i => $j): ?>
                            <tr>
                                <td style="color:var(--color-info-dark);font-weight:700"><?php echo $i+1; ?></td>
                                <td>
                                    <div class="job-title-cell"><?php echo htmlspecialchars($j['jobtitle']); ?></div>
                                    <div class="job-position"><?php echo htmlspecialchars($j['position']); ?></div>
                                </td>
                                <td>
                                    <div class="mini-bar-wrap">
                                        <div class="mini-bar-bg">
                                            <div class="mini-bar-fill" style="width:<?php echo $max_apps > 0 ? round(($j['total']/$max_apps)*100) : 0; ?>%"></div>
                                        </div>
                                        <span class="mini-bar-count"><?php echo $j['total']; ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;color:var(--color-info-dark);padding:1.5rem;font-style:italic">No data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Badge Distribution -->
            <div class="table-card">
                <h3>Applicant Work Preference</h3>
                <div class="chart-wrap" style="height:200px">
                    <canvas id="badgeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="activity-card">
            <h3>Recent Activity Log</h3>
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $act): ?>
                <div class="activity-item">
                    <div class="activity-dot"></div>
                    <div class="activity-msg">
                        <strong><?php echo htmlspecialchars($act['name']); ?></strong> — <?php echo htmlspecialchars($act['message']); ?>
                    </div>
                    <div class="activity-time"><?php echo date('d M, H:i', strtotime($act['created_at'])); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-activity">No recent activity found.</p>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
const primaryColor = '#e85d26';

// --- Trend Chart ---
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_labels ?: ['No data']); ?>,
        datasets: [{
            label: 'Applications',
            data: <?php echo json_encode($monthly_data ?: [0]); ?>,
            borderColor: primaryColor,
            backgroundColor: 'rgba(232,93,38,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: primaryColor,
            pointRadius: 4,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0, color: '#7d8da1', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#7d8da1', font: { size: 11 } } }
        }
    }
});

// --- Status Donut ---
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Accepted', 'Rejected'],
        datasets: [{
            data: [<?php echo $total_pending; ?>, <?php echo $total_accepted; ?>, <?php echo $total_rejected; ?>],
            backgroundColor: ['#ffbb55', '#41f1b6', '#ff7782'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});

// --- Badge Bar Chart ---
const badgeCtx = document.getElementById('badgeChart').getContext('2d');
new Chart(badgeCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($badge_data) ?: ['N/A']); ?>,
        datasets: [{
            label: 'Users',
            data: <?php echo json_encode(array_values($badge_data) ?: [0]); ?>,
            backgroundColor: ['#e85d26', '#41f1b6', '#ffbb55'],
            borderRadius: 6,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0, color: '#7d8da1', font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#7d8da1', font: { size: 11 } } }
        }
    }
});
</script>
</body>
</html>