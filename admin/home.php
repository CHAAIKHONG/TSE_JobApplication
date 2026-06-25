<?php
// Database Connection
require_once '../database/db.php';

$total_jobs = 0; 
$total_applications = 0;
$total_pending = 0;
$total_accepted = 0;
$total_rejected = 0;
$recent_applications = [];

try {
    $sql_jobs = "SELECT COUNT(*) as total FROM jobs";
    $result_jobs = mysqli_query($conn, $sql_jobs);
    if ($result_jobs) {
        $row = mysqli_fetch_assoc($result_jobs);
        $total_jobs = $row['total'];
    }

    $sql_apps = "SELECT COUNT(*) as total FROM applications";
    $result_apps = mysqli_query($conn, $sql_apps);
    if ($result_apps) {
        $row = mysqli_fetch_assoc($result_apps);
        $total_applications = $row['total'];
    }

    $sql_pending = "SELECT COUNT(*) as total FROM applications WHERE status = 'pending'";
    $result_pending = mysqli_query($conn, $sql_pending);
    if ($result_pending) {
        $row = mysqli_fetch_assoc($result_pending);
        $total_pending = $row['total'];
    }

    $sql_accepted = "SELECT COUNT(*) as total FROM applications WHERE status = 'accepted'";
    $result_accepted = mysqli_query($conn, $sql_accepted);
    if ($result_accepted) {
        $row = mysqli_fetch_assoc($result_accepted);
        $total_accepted = $row['total'];
    }

    $sql_rejected = "SELECT COUNT(*) as total FROM applications WHERE status = 'rejected'";
    $result_rejected = mysqli_query($conn, $sql_rejected);
    if ($result_rejected) {
        $row = mysqli_fetch_assoc($result_rejected);
        $total_rejected = $row['total'];
    }

    $sql_recent = "SELECT a.id, a.applicant_name, a.status, a.created_at, j.title AS job_title
                   FROM applications a
                   LEFT JOIN jobs j ON a.job_id = j.id
                   ORDER BY a.created_at DESC
                   LIMIT 6";
    $result_recent = mysqli_query($conn, $sql_recent);
    if ($result_recent) {
        while ($row = mysqli_fetch_assoc($result_recent)) {
            $recent_applications[] = $row;
        }
    }
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    
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
        body { width: 100vw; height: 100vh; font-family: poppins, sans-serif; font-size: 0.88rem; background: var(--color-background); user-select: none; overflow-x: hidden; color: var(--color-dark); }
        .container { display: grid; width: 96%; margin: 0 auto; gap: 1.8rem; grid-template-columns: 15rem auto; }
        
        a { color: var(--color-dark); }
        
        h1.page-title {
            display: inline-block;
            border: 2px solid var(--color-dark);
            border-radius: 50px;
            padding: 0.6rem 1.8rem;
            color: var(--color-white);
            background: var(--color-primary);
            box-shadow: 0 4px 12px rgba(54, 57, 73, 0.15);
        }

        .date-badge {
            display: inline-block;
            margin-top: 10px;
            margin-bottom: 20px;
            background: #ebebeb;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--color-dark-variant);
            border: 1px solid var(--color-info-light);
        }

        /* --- Logo -- */
        aside { height: 100vh; }
        aside .top { background: white; display: flex; align-items: center; justify-content: center; margin-top: 1.4rem; border-radius: 0.8rem; padding: 1.5rem; border: 1px solid var(--color-light); }
        aside .logo { display: flex; gap: 12px; align-items: center; justify-content: center; }
        .logo-mark {
            width: 38px; height: 38px; background: #111; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden; flex-shrink: 0; box-shadow: 0 6px 12px rgba(232, 93, 38, 0.25);
        }
        .logo-mark::after { content: ''; position: absolute; bottom: 0; right: 0; width: 16px; height: 16px; background: var(--color-primary); border-radius: 8px 0 0 0; }
        .logo-mark svg { width: 18px; height: 18px; position: relative; z-index: 1; stroke: #fff;}
        aside .logo h2 { font-size: 1.6rem; font-weight: 800; margin: 0; letter-spacing: -0.5px; display: flex; gap: 4px; align-items: baseline; }
        aside .logo h2 span.primary { color: var(--color-primary); }

        /* --- Sidebar --- */
        aside .sidebar { background: rgb(255, 255, 255); display: flex; flex-direction: column; height: 86vh; position: relative; top: 1rem; border-radius: 1.2rem; padding-top: 1.5rem; border: 1px solid var(--color-info-light); box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.02); }
        aside .sidebar a { display: flex; color: var(--color-info-dark); margin: 0.4rem 1.2rem; padding: 0.8rem 1.2rem; gap: 1.2rem; align-items: center; position: relative; border-radius: 0.8rem; border: 1px solid transparent; transition: all 0.3s ease; }
        aside .sidebar a span { font-size: 1.6rem; transition: all 0.3s ease; }
        aside .sidebar a.active { background: rgba(232, 93, 38, 0.08); color: var(--color-primary); border: 1px solid rgba(232, 93, 38, 0.4); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5); }
        aside .sidebar a:hover { color: var(--color-primary); background: var(--color-light); border-color: var(--color-info-light); transform: translateX(4px); }
        aside .sidebar a.active:hover { transform: none; border-color: var(--color-primary); }

        /* --- Logout  --- */
        aside .sidebar a.logout-btn {
            margin-top: auto; margin-bottom: 1.5rem; border: 2px solid var(--color-info-light); border-radius: 50px; justify-content: center; color: var(--color-info-dark); background: transparent; transition: all 0.3s ease;
        }
        aside .sidebar a.logout-btn:hover { background: #ffeaea; border-color: var(--color-danger); color: var(--color-danger); transform: none; }

        /* --- Main Content --- */
        main { margin-top: 1.4rem; padding-bottom: 3rem; }
        main .insights { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.6rem; }
        main .insights > div { background: white; padding: 1.8rem; border-radius: 2rem; margin-top: 1rem; box-shadow: 0 2rem 3rem var(--color-light); transition: all 300ms ease; border: 1px solid var(--color-info-light); }
        main .insights > div:hover { box-shadow: 0 1rem 2rem rgba(0,0,0,0.05); border-color: var(--color-info-light); transform: translateY(-3px);}
        main .insights > div span { background: var(--color-primary); padding: 0.5rem; border-radius: 50%; color: #fff; font-size: 2rem; display: inline-block; }
        main .insights > div.total_apps span { background: var(--color-danger); }
        main .insights > div .middle { display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; }
        main .insights h3 { margin: 1rem 0 0.6rem; font-size: 1rem; }
        main .insights h1 { font-size: 2.5rem; }

        /* ================================================
           NEW: Quick Actions
        ================================================ */
        .quick-actions {
            margin-top: 2rem;
        }
        .quick-actions h2 {
            font-size: 1.1rem;
            color: var(--color-dark);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: #fff;
            border: 1.5px solid var(--color-info-light);
            border-radius: 1rem;
            padding: 1rem 1.2rem;
            cursor: pointer;
            transition: all 0.25s ease;
            color: var(--color-dark);
            font-family: poppins, sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
        }
        .quick-action-btn:hover {
            border-color: var(--color-primary);
            background: rgba(232, 93, 38, 0.05);
            transform: translateY(-2px);
            color: var(--color-primary);
        }
        .quick-action-btn .qa-icon {
            width: 36px;
            height: 36px;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .qa-icon.orange { background: rgba(232, 93, 38, 0.12); color: var(--color-primary); }
        .qa-icon.blue   { background: rgba(55, 138, 221, 0.12); color: #185fa5; }
        .qa-icon.green  { background: rgba(65, 241, 182, 0.18); color: #0f6e56; }

        /* ================================================
           NEW: Application Status Summary
        ================================================ */
        .status-section {
            margin-top: 2rem;
        }
        .status-section h2 {
            font-size: 1.1rem;
            color: var(--color-dark);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .status-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .status-card {
            background: #fff;
            border: 1px solid var(--color-info-light);
            border-radius: 1.2rem;
            padding: 1.4rem 1.6rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.25s ease;
        }
        .status-card:hover { transform: translateY(-3px); box-shadow: 0 1rem 2rem rgba(0,0,0,0.05); }
        .status-card .status-icon {
            width: 46px; height: 46px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .status-icon.pending  { background: rgba(255, 187, 85, 0.2);  color: #ba7517; }
        .status-icon.accepted { background: rgba(65, 241, 182, 0.2);  color: #0f6e56; }
        .status-icon.rejected { background: rgba(255, 119, 130, 0.2); color: #993556; }
        .status-card .status-info h3 { font-size: 0.8rem; color: var(--color-dark-variant); font-weight: 500; margin-bottom: 0.2rem; }
        .status-card .status-info h2 { font-size: 1.8rem; font-weight: 700; color: var(--color-dark); }
        .status-progress {
            margin-top: 0.6rem;
            height: 4px;
            border-radius: 99px;
            background: var(--color-info-light);
            overflow: hidden;
        }
        .status-progress-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 0.8s ease;
        }
        .bar-pending  { background: #ffbb55; }
        .bar-accepted { background: #41f1b6; }
        .bar-rejected { background: #ff7782; }

        /* ================================================
           NEW: Recent Applications Table
        ================================================ */
        .recent-applications {
            margin-top: 2rem;
        }
        .recent-applications-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .recent-applications-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-dark);
        }
        .view-all-link {
            font-size: 0.8rem;
            color: var(--color-primary);
            font-weight: 600;
            border: 1px solid rgba(232, 93, 38, 0.4);
            border-radius: 50px;
            padding: 0.3rem 0.9rem;
            transition: all 0.2s ease;
        }
        .view-all-link:hover { background: var(--color-primary); color: #fff; }

        .applications-table {
            background: #fff;
            width: 100%;
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 1px solid var(--color-info-light);
            box-shadow: 0 2rem 3rem var(--color-light);
            border-collapse: collapse;
            overflow: hidden;
        }
        .applications-table thead th {
            padding: 0.6rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-info-dark);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--color-info-light);
        }
        .applications-table tbody td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--color-light);
            color: var(--color-dark-variant);
            font-size: 0.85rem;
            vertical-align: middle;
        }
        .applications-table tbody tr:last-child td { border-bottom: none; }
        .applications-table tbody tr:hover td { background: rgba(132, 139, 200, 0.05); }

        .applicant-name { font-weight: 600; color: var(--color-dark); }

        /* Applicant avatar initials */
        .applicant-cell { display: flex; align-items: center; gap: 0.7rem; }
        .applicant-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(232, 93, 38, 0.12);
            color: var(--color-primary);
            font-size: 0.75rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .badge-pending  { background: rgba(255, 187, 85, 0.18);  color: #ba7517; }
        .badge-accepted { background: rgba(65, 241, 182, 0.18);  color: #0f6e56; }
        .badge-rejected { background: rgba(255, 119, 130, 0.18); color: #993556; }
        .badge-default  { background: var(--color-light); color: var(--color-dark-variant); }

        .no-data-row td {
            text-align: center;
            color: var(--color-info-dark);
            padding: 2rem 1rem !important;
            font-style: italic;
        }
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
                <a href="home.php" class="active">
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
                <a href="manage_users.php">
                    <span class="material-symbols-sharp">people</span>
                    <h3>User Management</h3>
                </a>
                <a href="report.php">
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
            <h1 class="page-title">Dashboard</h1>
            <div class="date-badge" id="current-date"></div>

            <div class="insights">
                <div class="total_jobs">
                    <span class="material-symbols-sharp">work</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Jobs</h3>
                            <h1><?php echo $total_jobs; ?></h1>
                        </div>
                    </div>
                </div>

                <div class="total_apps">
                    <span class="material-symbols-sharp">description</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Applications</h3>
                            <h1><?php echo $total_applications; ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================
                 NEW: Quick Actions
            ================================================ -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="quick-actions-grid">
                    <a href="manage_jobs.php?action=add" class="quick-action-btn">
                        <div class="qa-icon orange">
                            <span class="material-symbols-sharp">add_circle</span>
                        </div>
                        Post New Job
                    </a>
                    <a href="manage_application.php" class="quick-action-btn">
                        <div class="qa-icon blue">
                            <span class="material-symbols-sharp">people</span>
                        </div>
                        View Applications
                    </a>
                    <a href="report.php" class="quick-action-btn">
                        <div class="qa-icon green">
                            <span class="material-symbols-sharp">analytics</span>
                        </div>
                        View Reports
                    </a>
                </div>
            </div>

            <!-- ================================================
                 NEW: Application Status Summary
            ================================================ -->
            <div class="status-section">
                <h2>Application Status Summary</h2>
                <div class="status-cards">
                    <?php
                        $pct_pending  = $total_applications > 0 ? round(($total_pending  / $total_applications) * 100) : 0;
                        $pct_accepted = $total_applications > 0 ? round(($total_accepted / $total_applications) * 100) : 0;
                        $pct_rejected = $total_applications > 0 ? round(($total_rejected / $total_applications) * 100) : 0;
                    ?>
                    <div class="status-card">
                        <div class="status-icon pending">
                            <span class="material-symbols-sharp">hourglass_empty</span>
                        </div>
                        <div class="status-info" style="flex:1">
                            <h3>Pending</h3>
                            <h2><?php echo $total_pending; ?></h2>
                            <div class="status-progress">
                                <div class="status-progress-bar bar-pending" style="width: <?php echo $pct_pending; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="status-card">
                        <div class="status-icon accepted">
                            <span class="material-symbols-sharp">check_circle</span>
                        </div>
                        <div class="status-info" style="flex:1">
                            <h3>Accepted</h3>
                            <h2><?php echo $total_accepted; ?></h2>
                            <div class="status-progress">
                                <div class="status-progress-bar bar-accepted" style="width: <?php echo $pct_accepted; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="status-card">
                        <div class="status-icon rejected">
                            <span class="material-symbols-sharp">cancel</span>
                        </div>
                        <div class="status-info" style="flex:1">
                            <h3>Rejected</h3>
                            <h2><?php echo $total_rejected; ?></h2>
                            <div class="status-progress">
                                <div class="status-progress-bar bar-rejected" style="width: <?php echo $pct_rejected; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================
                 NEW: Recent Applications
            ================================================ -->
            <div class="recent-applications">
                <div class="recent-applications-header">
                    <h2>Recent Applications</h2>
                    <a href="manage_application.php" class="view-all-link">View All</a>
                </div>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Applicant</th>
                            <th>Position Applied</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_applications)): ?>
                            <?php foreach ($recent_applications as $index => $app): ?>
                                <?php
                                    $name   = htmlspecialchars($app['applicant_name'] ?? 'N/A');
                                    $title  = htmlspecialchars($app['job_title'] ?? 'N/A');
                                    $status = strtolower($app['status'] ?? 'pending');
                                    $date   = isset($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A';
                                    $initials = '';
                                    $parts = explode(' ', $name);
                                    foreach (array_slice($parts, 0, 2) as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                                    $badge_class = in_array($status, ['pending','accepted','rejected']) ? 'badge-'.$status : 'badge-default';
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="applicant-cell">
                                            <div class="applicant-avatar"><?php echo $initials; ?></div>
                                            <span class="applicant-name"><?php echo $name; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $title; ?></td>
                                    <td><?php echo $date; ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="no-data-row">
                                <td colspan="5">No applications found yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script>
        const now = new Date();
        const options = { year: 'numeric', month: 'short', day: '2-digit' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-GB', options).replace(/ /g, ' / ');
    </script>
</body>
</html>