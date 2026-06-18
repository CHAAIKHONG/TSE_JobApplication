<?php
session_start();
require_once '../database/db.php';

// 假设 admin_id 存储在 session 中，如果没有则默认设为 1 (测试用)
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 1;

$message = '';
$messageType = '';

if (isset($_POST['add_job'])) {
    $jobtitle = mysqli_real_escape_string($conn, $_POST['jobtitle']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $salary = floatval($_POST['salary']); // 对应 DECIMAL(10,2)
    $details = mysqli_real_escape_string($conn, $_POST['details']);

    $sql = "INSERT INTO jobs (admin_id, jobtitle, position, salary, details) VALUES ('$admin_id', '$jobtitle', '$position', '$salary', '$details')";
    if (mysqli_query($conn, $sql)) {
        $message = "Job added successfully!";
        $messageType = "success";
    } else {
        $message = "Error adding job: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

if (isset($_GET['delete'])) {
    $job_id = intval($_GET['delete']);
    $sql = "DELETE FROM jobs WHERE job_id = $job_id";
    if (mysqli_query($conn, $sql)) {
        $message = "Job deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting job: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

if (isset($_POST['update_job'])) {
    $job_id = intval($_POST['job_id']);
    $jobtitle = mysqli_real_escape_string($conn, $_POST['jobtitle']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $salary = floatval($_POST['salary']);
    $details = mysqli_real_escape_string($conn, $_POST['details']);

    $sql = "UPDATE jobs SET jobtitle='$jobtitle', position='$position', salary='$salary', details='$details' WHERE job_id=$job_id";
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_jobs.php?msg=updated");
        exit;
    } else {
        $message = "Error updating job: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $message = "Job updated successfully!";
    $messageType = "success";
}

$edit_job = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_res = mysqli_query($conn, "SELECT * FROM jobs WHERE job_id = $edit_id");
    if (mysqli_num_rows($edit_res) > 0) {
        $edit_job = mysqli_fetch_assoc($edit_res);
    }
}

$jobs_list = mysqli_query($conn, "SELECT * FROM jobs ORDER BY job_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - HR System</title>
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
        h1 { font-weight: 800; font-size: 1.8rem; }
        h2 { font-size: 1.4rem; margin-bottom: 1rem; }
        .danger { color: var(--color-danger); }
        
        /* 页面标题胶囊边框设计 */
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

        /* --- Logo 设计 --- */
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

        /* --- Sidebar 设计 --- */
        aside .sidebar { background: rgb(255, 255, 255); display: flex; flex-direction: column; height: 86vh; position: relative; top: 1rem; border-radius: 1.2rem; padding-top: 1.5rem; border: 1px solid var(--color-info-light); box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.02); }
        aside .sidebar a { display: flex; color: var(--color-info-dark); margin: 0.4rem 1.2rem; padding: 0.8rem 1.2rem; gap: 1.2rem; align-items: center; position: relative; border-radius: 0.8rem; border: 1px solid transparent; transition: all 0.3s ease; }
        aside .sidebar a span { font-size: 1.6rem; transition: all 0.3s ease; }
        aside .sidebar a.active { background: rgba(232, 93, 38, 0.08); color: var(--color-primary); border: 1px solid rgba(232, 93, 38, 0.4); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5); }
        aside .sidebar a:hover { color: var(--color-primary); background: var(--color-light); border-color: var(--color-info-light); transform: translateX(4px); }
        aside .sidebar a.active:hover { transform: none; border-color: var(--color-primary); }
        
        /* --- 独立的 Logout 按钮设计 --- */
        aside .sidebar a.logout-btn {
            margin-top: auto; margin-bottom: 1.5rem; border: 2px solid var(--color-info-light); border-radius: 50px; justify-content: center; color: var(--color-info-dark); background: transparent; transition: all 0.3s ease;
        }
        aside .sidebar a.logout-btn:hover { background: #ffeaea; border-color: var(--color-danger); color: var(--color-danger); transform: none; }

        main { margin-top: 1.4rem; padding-bottom: 3rem; }
        .form-section, .table-section { background: white; padding: 1.8rem; border-radius: 2rem; margin-top: 1.5rem; box-shadow: 0 2rem 3rem var(--color-light); border: 1px solid var(--color-info-light); }
        .form-group { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1rem; }
        input, textarea { padding: 1rem; border-radius: 0.4rem; border: 1px solid var(--color-light); background: var(--color-background); color: var(--color-dark); font-size: 0.9rem; width: 100%; transition: all 0.3s ease; }
        textarea { grid-column: span 3; height: 80px; resize: none; }
        input:focus, textarea:focus { border-color: var(--color-primary); box-shadow: 0 0 0 2px rgba(232, 93, 38, 0.1); }
        
        .btn { padding: 0.8rem 1.5rem; border-radius: 0.4rem; font-weight: bold; cursor: pointer; color: white; transition: all 300ms ease; text-align: center; border: none;}
        .btn-primary { background: var(--color-primary); }
        .btn-primary:hover { background: var(--color-primary-variant); transform: translateY(-2px); }
        .btn-danger { background: var(--color-danger); padding: 0.4rem 0.8rem; font-size: 0.8rem; }
        .btn-danger:hover { background: #e0606b; transform: translateY(-1px); }
        .btn-warning { background: var(--color-warning); padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #333; }
        .btn-warning:hover { background: #e6a84d; transform: translateY(-1px); }
        
        .alert { padding: 1rem; margin-top: 1rem; border-radius: 0.4rem; font-weight: 500; }
        .alert.success { background: rgba(65, 241, 182, 0.2); color: #2e8b57; border: 1px solid #41f1b6; }
        .alert.danger { background: rgba(255, 119, 130, 0.1); color: var(--color-danger); border: 1px solid var(--color-danger); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; text-align: left; }
        th, td { padding: 1.2rem; border-bottom: 1px solid var(--color-light); }
        th { color: var(--color-info-dark); }
        tr:last-child td { border: none; }
        .actions { display: flex; gap: 0.5rem; align-items: center;}
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
                <a href="manage_jobs.php" class="active">
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
            <h1 class="page-title">Job Management</h1>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-section">
                <?php if ($edit_job): ?>
                    <h2>Edit Job Position</h2>
                    <form action="manage_jobs.php" method="POST">
                        <input type="hidden" name="job_id" value="<?php echo $edit_job['job_id']; ?>">
                        <div class="form-group">
                            <input type="text" name="jobtitle" placeholder="Job Title" value="<?php echo htmlspecialchars($edit_job['jobtitle']); ?>" required>
                            <input type="text" name="position" placeholder="Position (e.g. Full-time, Remote)" value="<?php echo htmlspecialchars($edit_job['position']); ?>" required>
                            <input type="number" step="0.01" name="salary" placeholder="Salary (e.g. 4000.00)" value="<?php echo htmlspecialchars($edit_job['salary']); ?>" required>
                            <textarea name="details" placeholder="Job Details / Requirements" required><?php echo htmlspecialchars($edit_job['details']); ?></textarea>
                        </div>
                        <button type="submit" name="update_job" class="btn btn-primary">Update Job</button>
                        <a href="manage_jobs.php" style="margin-left: 1rem; color: var(--color-info-dark); text-decoration: underline;">Cancel</a>
                    </form>
                <?php else: ?>
                    <h2>Create New Job Post</h2>
                    <form action="manage_jobs.php" method="POST">
                        <div class="form-group">
                            <input type="text" name="jobtitle" placeholder="Job Title (e.g. Software Engineer)" required>
                            <input type="text" name="position" placeholder="Position Type (e.g. Full-time)" required>
                            <input type="number" step="0.01" name="salary" placeholder="Salary (e.g. 4000.00)" required>
                            <textarea name="details" placeholder="Job Details & Requirements..." required></textarea>
                        </div>
                        <button type="submit" name="add_job" class="btn btn-primary">Post Job</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="table-section">
                <h2>Active Job Lists</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Position</th>
                            <th>Salary</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($jobs_list) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($jobs_list)): ?>
                                <tr>
                                    <td><?php echo $row['job_id']; ?></td>
                                    <td><b style="color: var(--color-dark);"><?php echo htmlspecialchars($row['jobtitle']); ?></b></td>
                                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                                    <td style="color: var(--color-primary); font-weight: 600;">RM <?php echo htmlspecialchars($row['salary']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['details'], 0, 50)) . (strlen($row['details']) > 50 ? '...' : ''); ?></td>
                                    <td class="actions">
                                        <a href="manage_jobs.php?edit=<?php echo $row['job_id']; ?>" class="btn btn-warning">Edit</a>
                                        <a href="manage_jobs.php?delete=<?php echo $row['job_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this job?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--color-info-dark);">No jobs posted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>