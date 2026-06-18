<?php
session_start();
require_once '../database/db.php';

// Create interviews table if not exists
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS interviews (
        interview_id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT,
        interview_date DATETIME,
        location VARCHAR(255),
        notes TEXT,
        status ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
    )
");

$message = '';
$messageType = '';

// --- 1. Delete Interview ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['interview_id'])) {
    $iid = intval($_GET['interview_id']);
    if (mysqli_query($conn, "DELETE FROM interviews WHERE interview_id = $iid")) {
        $message = "Interview deleted successfully.";
        $messageType = "success";
    }
}

// --- 2. Update Status ---
if (isset($_GET['action']) && isset($_GET['interview_id']) && in_array($_GET['action'], ['complete','cancel'])) {
    $iid = intval($_GET['interview_id']);
    $new_status = $_GET['action'] === 'complete' ? 'Completed' : 'Cancelled';
    $safe_status = mysqli_real_escape_string($conn, $new_status);
    mysqli_query($conn, "UPDATE interviews SET status='$safe_status' WHERE interview_id=$iid");
    $message = "Interview marked as $new_status.";
    $messageType = "success";
}

// --- 3. Schedule new interview (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_interview'])) {
    $app_id        = intval($_POST['application_id']);
    $interview_date = mysqli_real_escape_string($conn, $_POST['interview_date']);
    $location      = mysqli_real_escape_string($conn, trim($_POST['location']));
    $notes         = mysqli_real_escape_string($conn, trim($_POST['notes']));

    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT interview_id FROM interviews WHERE application_id=$app_id AND status='Scheduled'"));
    if ($check) {
        $message = "This application already has a scheduled interview.";
        $messageType = "danger";
    } else {
        $ins = mysqli_query($conn, "INSERT INTO interviews (application_id, interview_date, location, notes) VALUES ($app_id, '$interview_date', '$location', '$notes')");
        if ($ins) {
            // Notify applicant
            $app_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.user_id, j.jobtitle FROM applications a JOIN jobs j ON a.job_id=j.job_id WHERE a.application_id=$app_id"));
            if ($app_info) {
                $uid = $app_info['user_id'];
                $jtitle = mysqli_real_escape_string($conn, $app_info['jobtitle']);
                $notif = "You have been scheduled for an interview for the position of '$jtitle' on " . date('d M Y, H:i', strtotime($_POST['interview_date'])) . ". Location: $location";
                $notif = mysqli_real_escape_string($conn, $notif);
                mysqli_query($conn, "INSERT INTO notifications (user_id, message) VALUES ($uid, '$notif')");
            }
            $message = "Interview scheduled and applicant notified!";
            $messageType = "success";
        }
    }
}

// --- 4. Fetch accepted applications without a scheduled interview ---
$eligible_res = mysqli_query($conn, "
    SELECT a.application_id, u.name, u.email, j.jobtitle, j.position, a.applied_at
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.status = 'Accepted'
      AND a.application_id NOT IN (
          SELECT application_id FROM interviews WHERE status = 'Scheduled'
      )
    ORDER BY a.applied_at DESC
");
$eligible = [];
while ($r = mysqli_fetch_assoc($eligible_res)) $eligible[] = $r;

// --- 5. Filter interviews list ---
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$where = $status_filter !== 'All' ? "WHERE i.status='" . mysqli_real_escape_string($conn, $status_filter) . "'" : "";

$interviews_res = mysqli_query($conn, "
    SELECT i.interview_id, i.interview_date, i.location, i.notes, i.status, i.created_at,
           u.name, u.email, j.jobtitle, j.position, a.application_id
    FROM interviews i
    JOIN applications a ON i.application_id = a.application_id
    JOIN users u ON a.user_id = u.user_id
    JOIN jobs j ON a.job_id = j.job_id
    $where
    ORDER BY i.interview_date ASC
");
$interviews = [];
while ($r = mysqli_fetch_assoc($interviews_res)) $interviews[] = $r;

// Stats
$total_scheduled  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM interviews WHERE status='Scheduled'"))['c'];
$total_completed  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM interviews WHERE status='Completed'"))['c'];
$total_cancelled  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM interviews WHERE status='Cancelled'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Management - HR System</title>
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

        .alert { padding: 1rem 1.2rem; margin-bottom: 1.5rem; border-radius: 0.8rem; font-weight: 500; }
        .alert.success { background: rgba(65,241,182,0.15); color: #0f6e56; border: 1px solid #41f1b6; }
        .alert.danger { background: rgba(255,119,130,0.1); color: var(--color-danger); border: 1px solid var(--color-danger); }

        /* Stats */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.8rem; }
        .stat-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.2rem; padding: 1.3rem 1.5rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .stat-icon.blue   { background: rgba(55,138,221,0.12); color: #185fa5; }
        .stat-icon.green  { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .stat-icon.red    { background: rgba(255,119,130,0.15); color: #993556; }
        .stat-info p { font-size: 0.75rem; color: var(--color-info-dark); }
        .stat-info h2 { font-size: 1.7rem; font-weight: 700; color: var(--color-dark); }

        /* Schedule Form */
        .schedule-section { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.5rem; padding: 1.8rem; margin-bottom: 1.8rem; }
        .schedule-section h2 { font-size: 1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.78rem; font-weight: 600; color: var(--color-info-dark); text-transform: uppercase; letter-spacing: 0.04em; }
        .form-group select,
        .form-group input,
        .form-group textarea {
            padding: 0.7rem 1rem; border-radius: 0.7rem; border: 1.5px solid var(--color-info-light);
            background: var(--color-background); font-size: 0.85rem; font-family: poppins, sans-serif;
            color: var(--color-dark); transition: border 0.2s;
        }
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus { border-color: var(--color-primary); outline: none; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .submit-btn { display: inline-flex; align-items: center; gap: 0.5rem; background: var(--color-primary); color: white; padding: 0.7rem 1.8rem; border-radius: 50px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; font-family: poppins, sans-serif; border: none; }
        .submit-btn:hover { background: var(--color-primary-variant); transform: translateY(-1px); }
        .no-eligible { background: rgba(255,187,85,0.12); border: 1px dashed #ffbb55; border-radius: 0.8rem; padding: 1rem 1.2rem; color: #ba7517; font-size: 0.85rem; }

        /* Filter */
        .filter-bar { display: flex; gap: 0.8rem; align-items: center; margin-bottom: 1.2rem; }
        .filter-btn { padding: 0.55rem 1.3rem; border-radius: 50px; border: 1.5px solid var(--color-info-light); background: white; color: var(--color-dark-variant); cursor: pointer; font-weight: 600; font-size: 0.82rem; font-family: poppins, sans-serif; transition: all 0.2s; text-decoration: none; }
        .filter-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .filter-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }

        /* Interview Cards */
        .interviews-section h2 { font-size: 1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1.2rem; }
        .interview-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .interview-card { background: #fff; border: 1px solid var(--color-info-light); border-radius: 1.2rem; padding: 1.4rem; transition: all 0.25s; position: relative; overflow: hidden; }
        .interview-card:hover { transform: translateY(-2px); box-shadow: 0 1rem 2rem rgba(0,0,0,0.06); }
        .interview-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 4px 0 0 4px; }
        .interview-card.scheduled::before  { background: #185fa5; }
        .interview-card.completed::before  { background: #0f6e56; }
        .interview-card.cancelled::before  { background: #993556; }

        .card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
        .card-header-left { display: flex; align-items: center; gap: 0.8rem; }
        .card-avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(232,93,38,0.1); color: var(--color-primary); font-size: 0.78rem; font-weight: 700; display: flex; align-items: center; justify-content: center; text-transform: uppercase; flex-shrink: 0; }
        .card-name { font-weight: 700; color: var(--color-dark); font-size: 0.9rem; }
        .card-email { font-size: 0.75rem; color: var(--color-info-dark); }

        .status-badge { display: inline-block; padding: 0.25rem 0.8rem; border-radius: 50px; font-size: 0.72rem; font-weight: 700; }
        .badge-scheduled  { background: rgba(55,138,221,0.12); color: #185fa5; }
        .badge-completed  { background: rgba(65,241,182,0.18); color: #0f6e56; }
        .badge-cancelled  { background: rgba(255,119,130,0.15); color: #993556; }

        .card-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1rem; margin-bottom: 1rem; }
        .info-item { display: flex; align-items: flex-start; gap: 0.4rem; }
        .info-item span.material-symbols-sharp { font-size: 1rem; color: var(--color-info-dark); margin-top: 1px; }
        .info-text { font-size: 0.8rem; color: var(--color-dark-variant); }
        .info-text strong { display: block; font-size: 0.72rem; color: var(--color-info-dark); font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }

        .card-job { background: rgba(232,93,38,0.06); border-radius: 0.5rem; padding: 0.5rem 0.8rem; margin-bottom: 1rem; font-size: 0.82rem; }
        .card-job span { color: var(--color-primary); font-weight: 700; }
        .card-job small { color: var(--color-dark-variant); }

        .card-notes { background: var(--color-background); border-radius: 0.5rem; padding: 0.6rem 0.8rem; font-size: 0.78rem; color: var(--color-dark-variant); margin-bottom: 1rem; font-style: italic; }

        .card-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn { padding: 0.4rem 0.9rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; color: white; transition: all 0.2s; font-size: 0.78rem; border: none; display: inline-block; }
        .btn-complete { background: #0f6e56; }
        .btn-complete:hover { background: #085041; }
        .btn-cancel  { background: #ba7517; color: white; }
        .btn-cancel:hover { background: #854f0b; }
        .btn-delete  { background: var(--color-danger); }
        .btn-delete:hover { background: #e0606b; }
        .btn-outline { background: transparent; color: var(--color-info-dark); border: 1px solid var(--color-info-light); }
        .btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--color-info-dark); }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.88rem; }
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
                <a href="report.php">
                    <span class="material-symbols-sharp">analytics</span>
                    <h3>Reports & Analytics</h3>
                </a>
                <a href="interviews.php" class="active">
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
        <h1 class="page-title">Interview Management</h1>

        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon blue"><span class="material-symbols-sharp">event</span></div>
                <div class="stat-info"><p>Scheduled</p><h2><?php echo $total_scheduled; ?></h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><span class="material-symbols-sharp">task_alt</span></div>
                <div class="stat-info"><p>Completed</p><h2><?php echo $total_completed; ?></h2></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><span class="material-symbols-sharp">event_busy</span></div>
                <div class="stat-info"><p>Cancelled</p><h2><?php echo $total_cancelled; ?></h2></div>
            </div>
        </div>

        <!-- Schedule Form -->
        <div class="schedule-section">
            <h2><span class="material-symbols-sharp" style="font-size:1.2rem;color:var(--color-primary)">add_circle</span> Schedule New Interview</h2>

            <?php if (!empty($eligible)): ?>
            <form method="POST" action="interview.php">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Select Accepted Applicant</label>
                        <select name="application_id" required>
                            <option value="">— Choose an accepted applicant —</option>
                            <?php foreach ($eligible as $e): ?>
                                <option value="<?php echo $e['application_id']; ?>">
                                    <?php echo htmlspecialchars($e['name']); ?> — <?php echo htmlspecialchars($e['jobtitle']); ?> (<?php echo htmlspecialchars($e['position']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Interview Date & Time</label>
                        <input type="datetime-local" name="interview_date" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Location / Meeting Link</label>
                        <input type="text" name="location" placeholder="e.g. Room 3A or https://meet.google.com/..." required>
                    </div>
                    <div class="form-group full">
                        <label>Notes (optional)</label>
                        <textarea name="notes" placeholder="Any preparation notes, topics to cover..."></textarea>
                    </div>
                </div>
                <button type="submit" name="schedule_interview" class="submit-btn">
                    <span class="material-symbols-sharp" style="font-size:1.1rem">event_available</span>
                    Schedule Interview
                </button>
            </form>
            <?php else: ?>
                <div class="no-eligible">
                    <span class="material-symbols-sharp" style="font-size:1.1rem;vertical-align:-3px">info</span>
                    No accepted applicants available to schedule. Accept applications first from <a href="manage_application.php" style="color:var(--color-primary);font-weight:700">Application Management</a>.
                </div>
            <?php endif; ?>
        </div>

        <!-- Interviews List -->
        <div class="interviews-section">
            <h2>All Interviews</h2>
            <div class="filter-bar">
                <a href="interview.php?status=All"       class="filter-btn <?php echo $status_filter==='All'?'active':''; ?>">All</a>
                <a href="interview.php?status=Scheduled" class="filter-btn <?php echo $status_filter==='Scheduled'?'active':''; ?>">Scheduled</a>
                <a href="interview.php?status=Completed" class="filter-btn <?php echo $status_filter==='Completed'?'active':''; ?>">Completed</a>
                <a href="interview.php?status=Cancelled" class="filter-btn <?php echo $status_filter==='Cancelled'?'active':''; ?>">Cancelled</a>
            </div>

            <?php if (!empty($interviews)): ?>
            <div class="interview-grid">
                <?php foreach ($interviews as $iv):
                    $ivname = htmlspecialchars($iv['name']);
                    $init = '';
                    foreach (array_slice(explode(' ', $ivname), 0, 2) as $p) $init .= strtoupper(substr($p,0,1));
                    $ivstatus = strtolower($iv['status']);
                    $badge_cls = 'badge-scheduled';
                    if ($ivstatus==='completed') $badge_cls='badge-completed';
                    elseif ($ivstatus==='cancelled') $badge_cls='badge-cancelled';
                ?>
                <div class="interview-card <?php echo $ivstatus; ?>">
                    <div class="card-header">
                        <div class="card-header-left">
                            <div class="card-avatar"><?php echo $init; ?></div>
                            <div>
                                <div class="card-name"><?php echo $ivname; ?></div>
                                <div class="card-email"><?php echo htmlspecialchars($iv['email']); ?></div>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $badge_cls; ?>"><?php echo $iv['status']; ?></span>
                    </div>

                    <div class="card-job">
                        <span><?php echo htmlspecialchars($iv['jobtitle']); ?></span>
                        <small> · <?php echo htmlspecialchars($iv['position']); ?></small>
                    </div>

                    <div class="card-info">
                        <div class="info-item">
                            <span class="material-symbols-sharp">calendar_today</span>
                            <div class="info-text">
                                <strong>Date</strong>
                                <?php echo date('d M Y', strtotime($iv['interview_date'])); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="material-symbols-sharp">schedule</span>
                            <div class="info-text">
                                <strong>Time</strong>
                                <?php echo date('H:i', strtotime($iv['interview_date'])); ?>
                            </div>
                        </div>
                        <div class="info-item" style="grid-column:1/-1">
                            <span class="material-symbols-sharp">location_on</span>
                            <div class="info-text">
                                <strong>Location</strong>
                                <?php echo htmlspecialchars($iv['location']); ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($iv['notes'])): ?>
                    <div class="card-notes">"<?php echo htmlspecialchars($iv['notes']); ?>"</div>
                    <?php endif; ?>

                    <div class="card-actions">
                        <?php if ($iv['status'] === 'Scheduled'): ?>
                            <a href="interview.php?action=complete&interview_id=<?php echo $iv['interview_id']; ?>" class="btn btn-complete" onclick="return confirm('Mark as completed?')">Mark Complete</a>
                            <a href="interview.php?action=cancel&interview_id=<?php echo $iv['interview_id']; ?>" class="btn btn-cancel" onclick="return confirm('Cancel this interview?')">Cancel</a>
                        <?php endif; ?>
                        <a href="interview.php?action=delete&interview_id=<?php echo $iv['interview_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this interview record?')">Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-symbols-sharp">event_note</span>
                <p>No interviews found<?php echo $status_filter !== 'All' ? " with status \"$status_filter\"" : ''; ?>.</p>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>
</body>
</html>