<?php
// 引入数据库连接
require_once '../database/db.php';

// 初始化数量
$total_jobs = 0; 
$total_applications = 0;

try {
    // 1. 查询总职位数（假设表名叫 jobs）
    $sql_jobs = "SELECT COUNT(*) as total FROM jobs";
    $result_jobs = mysqli_query($conn, $sql_jobs); // 如果使用的是 PDO，请改为相应的 PDO 语法
    if ($result_jobs) {
        $row = mysqli_fetch_assoc($result_jobs);
        $total_jobs = $row['total'];
    }

    // 2. 查询总申请数（假设表名叫 applications）
    $sql_apps = "SELECT COUNT(*) as total FROM applications";
    $result_apps = mysqli_query($conn, $sql_apps);
    if ($result_apps) {
        $row = mysqli_fetch_assoc($result_apps);
        $total_applications = $row['total'];
    }
} catch (Exception $e) {
    // 如果报错，可以临时取消注释查看原因
    // echo "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin Dashboard</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; display: flex; background-color: #f4f7f6; }
        .sidebar { width: 250px; background-color: #2c3e50; color: white; height: 100vh; padding-top: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; }
        .sidebar a { display: block; color: white; padding: 15px 20px; text-decoration: none; }
        .sidebar a:hover { background-color: #34495e; }
        .main-content { flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .cards { display: flex; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .card h3 { margin: 0; color: #7f8c8d; font-size: 16px; }
        .card .number { font-size: 32px; font-weight: bold; color: #2980b9; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>HR System</h2>
        <a href="home.php">🏠 Dashboard</a>
        <a href="jobs.php">💼 Job Management</a>
        <a href="applications.php">📄 Applications</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <span>Welcome, HR Admin</span>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Total Jobs</h3>
                <div class="number"><?php echo $total_jobs; ?></div>
            </div>
            <div class="card">
                <h3>Total Applications</h3>
                <div class="number"><?php echo $total_applications; ?></div>
            </div>
        </div>
    </div>

</body>
</html>