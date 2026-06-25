<?php
session_start();
require_once '../database/db.php';

$message = '';
$messageType = '';

if (isset($_GET['action']) && isset($_GET['app_id'])) {
    $action = $_GET['action'];
    $app_id = intval($_GET['app_id']);
    
    $new_status = '';
    if ($action === 'accept') {
        $new_status = 'Accepted';
    } elseif ($action === 'reject') {
        $new_status = 'Rejected';
    }

    if ($new_status !== '') {
        $info_sql = "SELECT a.user_id, j.jobtitle FROM applications a 
                     JOIN jobs j ON a.job_id = j.job_id 
                     WHERE a.application_id = $app_id";
        $info_res = mysqli_query($conn, $info_sql);
        
        if ($info_row = mysqli_fetch_assoc($info_res)) {
            $u_id = $info_row['user_id'];
            $j_title = $info_row['jobtitle'];

            $update_sql = "UPDATE applications SET status = '$new_status' WHERE application_id = $app_id";
            if (mysqli_query($conn, $update_sql)) {
                $notif_msg = "Your application for the position of '$j_title' has been $new_status.";
                
$safe_msg = mysqli_real_escape_string($conn, $notif_msg);
$insert_notif = "INSERT INTO notifications (user_id, message) VALUES ($u_id, '$safe_msg')";
                mysqli_query($conn, $insert_notif);

                $message = "Application has been $new_status and notification sent!";
                $messageType = "success";
            }
        }
    }
}

// --- Filter ---
$filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$whereClause = "";
if ($filter !== 'All') {
    $safe_filter = mysqli_real_escape_string($conn, $filter);
    $whereClause = "WHERE a.status = '$safe_filter'";
}

$sql = "SELECT a.application_id, a.status, a.applied_at, 
               u.name, u.email, u.resume, 
               j.jobtitle 
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN jobs j ON a.job_id = j.job_id
        $whereClause
        ORDER BY a.applied_at DESC";
$applications_list = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Management - HR System</title>
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
        h1 { font-weight: 800; font-size: 1.8rem; margin-bottom: 1rem; }
        
        h1.page-title {
            display: inline-block;
            border: 2px solid var(--color-dark);
            border-radius: 50px;
            padding: 0.6rem 1.8rem;
            color: var(--color-white);
            background: var(--color-primary);
            box-shadow: 0 4px 12px rgba(54, 57, 73, 0.15);
            margin-bottom: 1.5rem;
        }

        .danger { color: var(--color-danger); }

        aside { height: 100vh; }
        aside .top { background: white; display: flex; align-items: center; justify-content: center; margin-top: 1.4rem; border-radius: 0.8rem; padding: 1.5rem; border: 1px solid var(--color-light); }
        
        aside .logo { display: flex; gap: 12px; align-items: center; justify-content: center; }
        .logo-mark {
            width: 38px; height: 38px; background: #111; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden; flex-shrink: 0; box-shadow: 0 6px 12px rgba(232, 93, 38, 0.25);
        }
        .logo-mark::after {
            content: ''; position: absolute; bottom: 0; right: 0; width: 16px; height: 16px; background: var(--color-primary); border-radius: 8px 0 0 0;
        }
        .logo-mark svg { width: 18px; height: 18px; position: relative; z-index: 1; stroke: #fff;}
        aside .logo h2 { font-size: 1.6rem; font-weight: 800; margin: 0; letter-spacing: -0.5px; display: flex; gap: 4px; align-items: baseline; }
        aside .logo h2 span.primary { color: var(--color-primary); }
        /* ------------------------ */

        aside .sidebar { 
            background: rgb(255, 255, 255); 
            display: flex; 
            flex-direction: column; 
            height: 86vh; 
            position: relative; 
            top: 1rem; 
            border-radius: 1.2rem; 
            padding-top: 1.5rem; 
            border: 1px solid var(--color-info-light); 
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.02); 
        }
        aside .sidebar a { 
            display: flex; 
            color: var(--color-info-dark); 
            margin: 0.4rem 1.2rem; 
            padding: 0.8rem 1.2rem; 
            gap: 1.2rem; 
            align-items: center; 
            position: relative; 
            border-radius: 0.8rem; 
            border: 1px solid transparent; 
            transition: all 0.3s ease; 
        }
        aside .sidebar a span { font-size: 1.6rem; transition: all 0.3s ease; }
        
        aside .sidebar a.active { 
            background: rgba(232, 93, 38, 0.08); 
            color: var(--color-primary); 
            border: 1px solid rgba(232, 93, 38, 0.4); 
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5); 
        }
        aside .sidebar a:hover { 
            color: var(--color-primary); 
            background: var(--color-light); 
            border-color: var(--color-info-light);
            transform: translateX(4px); 
        }
        aside .sidebar a.active:hover { transform: none; border-color: var(--color-primary); }

        aside .sidebar a.logout-btn {
            margin-top: auto; 
            margin-bottom: 1.5rem;
            border: 2px solid var(--color-info-light);
            border-radius: 50px; 
            justify-content: center;
            color: var(--color-info-dark);
            background: transparent;
        }
        aside .sidebar a.logout-btn:hover {
            background: #ffeaea; 
            border-color: var(--color-danger);
            color: var(--color-danger);
            transform: none; 
        }

        main { margin-top: 1.4rem; padding-bottom: 3rem; }

        /* 提示块 */
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.4rem; font-weight: 500; }
        .alert.success { background: rgba(65, 241, 182, 0.2); color: #2e8b57; border: 1px solid #41f1b6; }
        .alert.danger { background: rgba(255, 119, 130, 0.1); color: var(--color-danger); border: 1px solid var(--color-danger); }

        /* Filter Section */
        .filter-box { margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; }
        .filter-box span { font-size: 0.9rem; font-weight: 600; color: var(--color-info-dark); }
        .filter-btn { padding: 0.6rem 1.5rem; border-radius: 2rem; border: 1px solid var(--color-light); background: white; color: var(--color-dark); cursor: pointer; transition: all 0.3s ease; font-weight: 600; font-size: 0.85rem; }
        .filter-btn:hover { border-color: var(--color-primary); color: var(--color-primary); transform: translateY(-2px); }
        .filter-btn.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }

        /* Table Section */
        .table-section { background: white; padding: 1.8rem; border-radius: 2rem; box-shadow: 0 2rem 3rem var(--color-light); border: 1px solid var(--color-info-light); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; text-align: left; }
        th, td { padding: 1.2rem 1rem; border-bottom: 1px solid var(--color-light); }
        th { color: var(--color-info-dark); font-weight: 600; }
        tr:last-child td { border: none; }
        
        /*  Badges */
        .badge { padding: 0.4rem 0.8rem; border-radius: 0.4rem; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background: #fff8ee; color: #f59e0b; }
        .badge-accepted { background: #edf7ee; color: #2e7d32; }
        .badge-rejected { background: #f5f5f5; color: #616161; }

        /*  Buttons */
        .btn { padding: 0.5rem 1rem; border-radius: 0.4rem; font-weight: bold; cursor: pointer; color: white; transition: all 300ms ease; font-size: 0.8rem; text-align: center; border: none; }
        .btn-success { background: #2e7d32; }
        .btn-success:hover { background: #1b5e20; transform: translateY(-1px); }
        .btn-danger { background: var(--color-danger); }
        .btn-danger:hover { background: #e0606b; transform: translateY(-1px); }
        .actions { display: flex; gap: 0.5rem; align-items: center; }
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
                <a href="manage_application.php" class="active"> 
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
            <h1 class="page-title">Application Management</h1>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="filter-box">
                <span>Filter by:</span>
                <a href="manage_application.php?status=All" class="filter-btn <?php echo $filter === 'All' ? 'active' : ''; ?>">All</a>
                <a href="manage_application.php?status=Pending" class="filter-btn <?php echo $filter === 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="manage_application.php?status=Accepted" class="filter-btn <?php echo $filter === 'Accepted' ? 'active' : ''; ?>">Accepted</a>
                <a href="manage_application.php?status=Rejected" class="filter-btn <?php echo $filter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
            </div>

            <div class="table-section">
                <table>
                    <thead>
                        <tr>
                            <th>Applicant Name</th>
                            <th>Email</th>
                            <th>Applied Job</th>
                            <th>Resume</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($applications_list) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($applications_list)): ?>
                                <tr>
                                    <td><b style="color: var(--color-dark);"><?php echo htmlspecialchars($row['name']); ?></b></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><span style="color: var(--color-primary); font-weight:600;"><?php echo htmlspecialchars($row['jobtitle']); ?></span></td>
                                    <td>
    <?php if (!empty($row['resume'])): ?>
        <a href="/TSE_JobApplication/uploads/resumes/<?php echo htmlspecialchars(basename($row['resume'])); ?>" target="_blank" style="color: #1565c0; text-decoration: underline;">View Resume</a>
    <?php else: ?>
        <span style="color: var(--color-info-dark);">No Resume</span>
    <?php endif; ?>
</td>
                                    <td><?php echo date('d M Y', strtotime($row['applied_at'])); ?></td>
                                    
                                    <td>
                                        <?php 
                                            $status_class = 'badge-pending';
                                            if ($row['status'] == 'Accepted') $status_class = 'badge-accepted';
                                            if ($row['status'] == 'Rejected') $status_class = 'badge-rejected';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                    
                                    <td class="actions">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="manage_application.php?action=accept&app_id=<?php echo $row['application_id']; ?>" class="btn btn-success" onclick="return confirm('Accept this applicant?')">Accept</a>
                                            <a href="manage_application.php?action=reject&app_id=<?php echo $row['application_id']; ?>" class="btn btn-danger" onclick="return confirm('Reject this applicant?')">Reject</a>
                                        <?php else: ?>
                                            <span style="color: var(--color-info-dark); font-size: 0.8rem; font-weight: 600;">Decision Made</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--color-info-dark); padding: 2rem;">No applications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>