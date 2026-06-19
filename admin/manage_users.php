<?php
session_start();
require_once '../database/db.php';

$message = '';
$messageType = '';

// --- 1. Delete User ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user_id'])) {
    $uid = intval($_GET['user_id']);
    // Delete related records first
    mysqli_query($conn, "DELETE FROM notifications WHERE user_id = $uid");
    mysqli_query($conn, "DELETE FROM career_history WHERE user_id = $uid");
    mysqli_query($conn, "DELETE FROM education WHERE user_id = $uid");
    mysqli_query($conn, "DELETE FROM applications WHERE user_id = $uid");
    $del = mysqli_query($conn, "DELETE FROM users WHERE user_id = $uid");
    if ($del) {
        $message = "User deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete user.";
        $messageType = "danger";
    }
}

// --- 2. Search & Filter ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$badge_filter = isset($_GET['badge']) ? mysqli_real_escape_string($conn, $_GET['badge']) : 'All';

$where = "WHERE 1=1";
if ($search !== '') {
    $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if ($badge_filter !== 'All') {
    $where .= " AND u.badge = '$badge_filter'";
}

// --- 3. Fetch Users with application count ---
$sql = "SELECT u.user_id, u.name, u.email, u.phoneNo, u.badge, u.resume, u.created_at,
               COUNT(a.application_id) AS total_applications
        FROM users u
        LEFT JOIN applications a ON u.user_id = a.user_id
        $where
        GROUP BY u.user_id
        ORDER BY u.created_at DESC";
$users_result = mysqli_query($conn, $sql);

// --- 4. View single user detail ---
$view_user = null;
$user_edu = [];
$user_career = [];
$user_apps = [];

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $vid = intval($_GET['view']);
    $vres = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $vid");
    $view_user = mysqli_fetch_assoc($vres);

    $edu_res = mysqli_query($conn, "SELECT * FROM education WHERE user_id = $vid ORDER BY start_date DESC");
    while ($r = mysqli_fetch_assoc($edu_res)) $user_edu[] = $r;

    $career_res = mysqli_query($conn, "SELECT * FROM career_history WHERE user_id = $vid ORDER BY start_date DESC");
    while ($r = mysqli_fetch_assoc($career_res)) $user_career[] = $r;

    $app_res = mysqli_query($conn, "SELECT a.application_id, a.status, a.applied_at, j.jobtitle 
                                    FROM applications a 
                                    JOIN jobs j ON a.job_id = j.job_id 
                                    WHERE a.user_id = $vid 
                                    ORDER BY a.applied_at DESC");
    while ($r = mysqli_fetch_assoc($app_res)) $user_apps[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HR System</title>
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
        body { width: 100vw; min-height: 100vh; font-family: poppins, sans-serif; font-size: 0.88rem; background: var(--color-background); overflow-x: hidden; color: var(--color-dark); }
        .container { display: grid; width: 96%; margin: 0 auto; gap: 1.8rem; grid-template-columns: 15rem auto; }
        a { color: var(--color-dark); }

        h1.page-title { display: inline-block; border: 2px solid var(--color-dark); border-radius: 50px; padding: 0.6rem 1.8rem; color: var(--color-white); background: var(--color-primary); box-shadow: 0 4px 12px rgba(54,57,73,0.15); margin-bottom: 1.5rem; }

        /* Sidebar */
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

        /* Main */
        main { margin-top: 1.4rem; padding-bottom: 3rem; }

        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.8rem; font-weight: 500; }
        .alert.success { background: rgba(65,241,182,0.15); color: #0f6e56; border: 1px solid #41f1b6; }
        .alert.danger { background: rgba(255,119,130,0.1); color: var(--color-danger); border: 1px solid var(--color-danger); }

        /* Toolbar */
        .toolbar { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap span { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--color-info-dark); font-size: 1.2rem; }
        .search-wrap input { width: 100%; padding: 0.65rem 1rem 0.65rem 2.6rem; border-radius: 50px; border: 1.5px solid var(--color-info-light); background: #fff; font-size: 0.85rem; color: var(--color-dark); font-family: poppins, sans-serif; transition: border 0.2s; }
        .search-wrap input:focus { border-color: var(--color-primary); outline: none; }
        .filter-btn { padding: 0.6rem 1.4rem; border-radius: 50px; border: 1.5px solid var(--color-info-light); background: white; color: var(--color-dark-variant); cursor: pointer; font-weight: 600; font-size: 0.82rem; font-family: poppins, sans-serif; transition: all 0.2s; }
        .filter-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .filter-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }

        /* Stats row */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1rem; padding: 1.2rem 1.4rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .stat-icon.blue { background: rgba(55,138,221,0.12); color: #185fa5; }
        .stat-icon.orange { background: rgba(232,93,38,0.12); color: var(--color-primary); }
        .stat-icon.green { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .stat-info p { font-size: 0.75rem; color: var(--color-info-dark); margin-bottom: 0.1rem; }
        .stat-info h2 { font-size: 1.6rem; font-weight: 700; color: var(--color-dark); }

        /* Table */
        .table-section { background: white; padding: 1.8rem; border-radius: 1.5rem; box-shadow: 0 2rem 3rem var(--color-light); border: 1px solid var(--color-info-light); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 0.7rem 1rem; color: var(--color-info-dark); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid var(--color-info-light); }
        td { padding: 1rem; border-bottom: 1px solid var(--color-light); color: var(--color-dark-variant); font-size: 0.85rem; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(132,139,200,0.04); }

        .user-cell { display: flex; align-items: center; gap: 0.8rem; }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: rgba(232,93,38,0.1); color: var(--color-primary); font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-transform: uppercase; }
        .user-name { font-weight: 600; color: var(--color-dark); }
        .user-email { font-size: 0.78rem; color: var(--color-info-dark); }

        .badge-pill { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.72rem; font-weight: 700; }
        .badge-onsite { background: rgba(55,138,221,0.12); color: #185fa5; }
        .badge-remote { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .badge-hybrid { background: rgba(255,187,85,0.2); color: #ba7517; }

        .app-count { display: inline-block; background: rgba(232,93,38,0.1); color: var(--color-primary); font-weight: 700; padding: 0.2rem 0.7rem; border-radius: 50px; font-size: 0.8rem; }

        .btn { padding: 0.45rem 1rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; color: white; transition: all 0.2s; font-size: 0.78rem; border: none; display: inline-block; }
        .btn-view { background: #185fa5; }
        .btn-view:hover { background: #0c447c; }
        .btn-delete { background: var(--color-danger); }
        .btn-delete:hover { background: #e0606b; }
        .actions { display: flex; gap: 0.5rem; }

        /* Detail Panel */
        .detail-panel { background: white; border: 1px solid var(--color-info-light); border-radius: 1.5rem; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 2rem 3rem var(--color-light); }
        .detail-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--color-info-light); }
        .detail-header-left { display: flex; align-items: center; gap: 1rem; }
        .detail-avatar { width: 52px; height: 52px; border-radius: 50%; background: rgba(232,93,38,0.12); color: var(--color-primary); font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; justify-content: center; text-transform: uppercase; }
        .detail-name { font-size: 1.1rem; font-weight: 700; color: var(--color-dark); }
        .detail-email { font-size: 0.82rem; color: var(--color-info-dark); margin-top: 0.1rem; }
        .back-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1.2rem; border-radius: 50px; border: 1.5px solid var(--color-info-light); color: var(--color-dark-variant); font-weight: 600; font-size: 0.82rem; transition: all 0.2s; }
        .back-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }

        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .detail-section h3 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-info-dark); margin-bottom: 0.8rem; font-weight: 700; }
        .detail-row { display: flex; gap: 0.6rem; padding: 0.5rem 0; border-bottom: 1px solid var(--color-light); font-size: 0.85rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: var(--color-info-dark); min-width: 90px; flex-shrink: 0; }
        .detail-value { color: var(--color-dark); font-weight: 500; }

        .history-card { border: 1px solid var(--color-info-light); border-radius: 0.8rem; padding: 0.9rem 1.1rem; margin-bottom: 0.7rem; }
        .history-card h4 { font-size: 0.88rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.2rem; }
        .history-card p { font-size: 0.78rem; color: var(--color-info-dark); }

        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.72rem; font-weight: 700; }
        .status-pending { background: rgba(255,187,85,0.18); color: #ba7517; }
        .status-accepted { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .status-rejected { background: rgba(255,119,130,0.18); color: #993556; }

        .empty-state { text-align: center; padding: 1.5rem; color: var(--color-info-dark); font-size: 0.82rem; font-style: italic; }
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
                <a href="manage_users.php" class="active">
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
        <h1 class="page-title">User Management</h1>

        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($view_user): ?>
        <!-- ===== DETAIL VIEW ===== -->
        <?php
            $vname = htmlspecialchars($view_user['name']);
            $vinit = '';
            foreach (array_slice(explode(' ', $vname), 0, 2) as $p) $vinit .= strtoupper(substr($p,0,1));
            $vbadge = $view_user['badge'] ?? 'Onsite';
            $badge_cls = 'badge-onsite';
            if ($vbadge === 'Remote') $badge_cls = 'badge-remote';
            elseif ($vbadge === 'Hybrid') $badge_cls = 'badge-hybrid';
        ?>
        <a href="manage_users.php" class="back-btn"><span class="material-symbols-sharp" style="font-size:1rem">arrow_back</span> Back to Users</a>
        <br><br>
        <div class="detail-panel">
            <div class="detail-header">
                <div class="detail-header-left">
                    <div class="detail-avatar"><?php echo $vinit; ?></div>
                    <div>
                        <div class="detail-name"><?php echo $vname; ?></div>
                        <div class="detail-email"><?php echo htmlspecialchars($view_user['email']); ?></div>
                    </div>
                    <span class="badge-pill <?php echo $badge_cls; ?>" style="margin-left:0.5rem"><?php echo $vbadge; ?></span>
                </div>
                <?php if (!empty($view_user['resume'])): ?>
                    <a href="../uploads/<?php echo htmlspecialchars($view_user['resume']); ?>" target="_blank" class="btn btn-view">View Resume</a>
                <?php endif; ?>
            </div>

            <div class="detail-grid">
                <!-- Basic Info -->
                <div class="detail-section">
                    <h3>Basic Info</h3>
                    <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value"><?php echo htmlspecialchars($view_user['phoneNo'] ?? 'N/A'); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Joined</span><span class="detail-value"><?php echo date('d M Y', strtotime($view_user['created_at'])); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Badge</span><span class="detail-value"><span class="badge-pill <?php echo $badge_cls; ?>"><?php echo $vbadge; ?></span></span></div>
                </div>

                <!-- Applications -->
                <div class="detail-section">
                    <h3>Applications (<?php echo count($user_apps); ?>)</h3>
                    <?php if (!empty($user_apps)): foreach ($user_apps as $ap): ?>
                        <div class="history-card">
                            <h4><?php echo htmlspecialchars($ap['jobtitle']); ?></h4>
                            <p><?php echo date('d M Y', strtotime($ap['applied_at'])); ?> &nbsp;
                                <?php
                                    $sc = 'status-pending';
                                    if ($ap['status']==='Accepted') $sc='status-accepted';
                                    elseif ($ap['status']==='Rejected') $sc='status-rejected';
                                ?>
                                <span class="status-badge <?php echo $sc; ?>"><?php echo $ap['status']; ?></span>
                            </p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="empty-state">No applications yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Education -->
                <div class="detail-section">
                    <h3>Education</h3>
                    <?php if (!empty($user_edu)): foreach ($user_edu as $ed): ?>
                        <div class="history-card">
                            <h4><?php echo htmlspecialchars($ed['institution_name']); ?></h4>
                            <p><?php echo htmlspecialchars($ed['degree']); ?> · <?php echo htmlspecialchars($ed['field_of_study']); ?></p>
                            <p><?php echo $ed['start_date'] ? date('Y', strtotime($ed['start_date'])) : ''; ?> – <?php echo $ed['end_date'] ? date('Y', strtotime($ed['end_date'])) : 'Present'; ?></p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="empty-state">No education records.</p>
                    <?php endif; ?>
                </div>

                <!-- Career History -->
                <div class="detail-section">
                    <h3>Career History</h3>
                    <?php if (!empty($user_career)): foreach ($user_career as $ch): ?>
                        <div class="history-card">
                            <h4><?php echo htmlspecialchars($ch['company_name']); ?></h4>
                            <p><?php echo htmlspecialchars($ch['position']); ?></p>
                            <p><?php echo $ch['start_date'] ? date('M Y', strtotime($ch['start_date'])) : ''; ?> – <?php echo $ch['end_date'] ? date('M Y', strtotime($ch['end_date'])) : 'Present'; ?></p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="empty-state">No career history.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== LIST VIEW ===== -->

        <?php
            $total_users = mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users"));
            $onsite_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE badge='Onsite'"))['c'];
            $remote_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE badge='Remote'"))['c'];
        ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue"><span class="material-symbols-sharp">group</span></div>
                <div class="stat-info"><p>Total Users</p><h2><?php echo $total_users; ?></h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><span class="material-symbols-sharp">location_on</span></div>
                <div class="stat-info"><p>Onsite</p><h2><?php echo $onsite_count; ?></h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><span class="material-symbols-sharp">wifi</span></div>
                <div class="stat-info"><p>Remote</p><h2><?php echo $remote_count; ?></h2></div>
            </div>
        </div>

        <form method="GET" action="manage_users.php">
            <div class="toolbar">
                <div class="search-wrap">
                    <span class="material-symbols-sharp">search</span>
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" name="badge" value="All" class="filter-btn <?php echo $badge_filter==='All'?'active':''; ?>">All</button>
                <button type="submit" name="badge" value="Onsite" class="filter-btn <?php echo $badge_filter==='Onsite'?'active':''; ?>">Onsite</button>
                <button type="submit" name="badge" value="Remote" class="filter-btn <?php echo $badge_filter==='Remote'?'active':''; ?>">Remote</button>
                <button type="submit" name="badge" value="Hybrid" class="filter-btn <?php echo $badge_filter==='Hybrid'?'active':''; ?>">Hybrid</button>
            </div>
        </form>

        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Badge</th>
                        <th>Applications</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_result && mysqli_num_rows($users_result) > 0):
                        $i = 1;
                        while ($u = mysqli_fetch_assoc($users_result)):
                            $uname = htmlspecialchars($u['name']);
                            $init = '';
                            foreach (array_slice(explode(' ', $uname), 0, 2) as $p) $init .= strtoupper(substr($p,0,1));
                            $ub = $u['badge'] ?? 'Onsite';
                            $ubcls = $ub==='Remote'?'badge-remote':($ub==='Hybrid'?'badge-hybrid':'badge-onsite');
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?php echo $init; ?></div>
                                <div>
                                    <div class="user-name"><?php echo $uname; ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['phoneNo'] ?? 'N/A'); ?></td>
                        <td><span class="badge-pill <?php echo $ubcls; ?>"><?php echo $ub; ?></span></td>
                        <td><span class="app-count"><?php echo $u['total_applications']; ?></span></td>
                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <div class="actions">
                                <a href="manage_users.php?view=<?php echo $u['user_id']; ?>" class="btn btn-view">View</a>
                                <a href="manage_users.php?action=delete&user_id=<?php echo $u['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this user and all their data?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--color-info-dark);padding:2rem">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>