<?php
// Database Connection
require_once '../database/db.php';

$total_jobs = 0; 
$total_applications = 0;

try {
    // Count total jobs
    $sql_jobs = "SELECT COUNT(*) as total FROM jobs";
    $result_jobs = mysqli_query($conn, $sql_jobs);
    if ($result_jobs) {
        $row = mysqli_fetch_assoc($result_jobs);
        $total_jobs = $row['total'];
    }

    // Count total applications
    $sql_apps = "SELECT COUNT(*) as total FROM applications";
    $result_apps = mysqli_query($conn, $sql_apps);
    if ($result_apps) {
        $row = mysqli_fetch_assoc($result_apps);
        $total_applications = $row['total'];
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
            --primary: #7380ec;
            --danger: #ff7782;
            --success: #41f1b6;
            --warning: #ffbb55;
            --white: #fff;
            --info-dark: #7d8da1;
            --info-light: #dce1eb;
            --dark: #363949;
            --light: rgba(132, 139, 200, 0.18);
            --primary-variant: #111e88;
            --dark-variant: #677483;
            --color-background: #f6f6f9;
        }
        
        * {
            margin: 0; padding: 0; outline: 0; appearance: none; border: 0;
            text-decoration: none; list-style: none; box-sizing: border-box;
        }
        
        html { font-size: 14px; }
        body { margin: 0; padding: 0; background-color: var(--color-background); }
        
        a { color: #363949; }
        img { display: block; width: 100%; }
        h1 { font-weight: 800; font-size: 1.8rem; color: #333; }
        h2 { font-size: 1.4rem; }
        h3 { font-size: 0.87rem; font-weight: 500; }
        small { font-size: 0.75rem; }
        .text-muted { color: #dce1eb; }
        .danger { color: #ff7782; }

        .container {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }
        
        aside {
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .logo { display: flex; align-items: center; padding: 20px; }
        .logo h2 { margin-left: 10px; color: #333; }

        .sidebar { padding: 20px 0; }
        .sidebar a {
            display: flex; color: #7d8da1; margin-left: 2rem; gap: 1rem;
            align-items: center; position: relative; height: 3.7rem;
            transition: all 300ms ease;
        }
        .sidebar a span { font-size: 1.6rem; transition: all 300ms ease; }
        .sidebar a.active { background: var(--light); color: var(--primary); margin-left: 0; padding-left: 2rem;}
        .sidebar a.active:before {
            content: ""; width: 6px; height: 100%;
            background: var(--primary); position: absolute; left: 0;
        }
        .sidebar a.active span { color: var(--primary); }
        .sidebar a:hover { color: var(--primary); }
        .sidebar a:hover span { margin-left: 1rem; }
        
        main { padding: 20px 30px; }
        .date { margin: 20px 0; }
        
        .insights {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        
        .card-box {
            background: #fff; padding: 20px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-box span { font-size: 2rem; padding: 0.5rem; border-radius: 50%; color: #fff; margin-bottom: 1rem; display: inline-block; }
        .card-box:nth-child(1) span { background: var(--primary); }
        .card-box:nth-child(2) span { background: var(--danger); }
        .card-box h1 { margin-top: 10px; font-size: 2.5rem;}
        
        .recent-activities {
            background: #fff; padding: 20px; border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-top: 30px;
        }
        table.centered-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        table.centered-table td { padding: 15px 10px; text-align: left; border-bottom: 1px solid var(--light); }
    </style>
</head>

<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <h2>HR<span class="danger">SYSTEM</span></h2>
                </div>
            </div>

            <div class="sidebar">
                <a href="home.php" class="active">
                    <span class="material-symbols-sharp">grid_view</span>
                    <h3>Dashboard</h3>
                </a>
                <a href="jobs.php">
                    <span class="material-symbols-sharp">work</span>
                    <h3>Job Management</h3>
                </a>
                <a href="applications.php">
                    <span class="material-symbols-sharp">description</span>
                    <h3>Applications</h3>
                </a>
                <a href="logout.php">
                    <span class="material-symbols-sharp">logout</span>
                    <h3>Logout</h3>
                </a>
            </div>
        </aside>

        <main>
            <h1>Dashboard</h1>

            <div class="date">
                <span id="current-date"></span>
            </div>

            <div class="insights">
                <div class="card-box">
                    <span class="material-symbols-sharp">work</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Jobs</h3>
                            <h1><?php echo $total_jobs; ?></h1>
                        </div>
                    </div>
                </div>

                <div class="card-box">
                    <span class="material-symbols-sharp">description</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Applications</h3>
                            <h1><?php echo $total_applications; ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-activities">
                <h2>Activities Overview</h2>
                <table class="centered-table">
                    <tbody>
                        <tr>
                            <td colspan="2"><h2>Welcome to HR Admin Dashboard</h2></td>
                        </tr>
                        <tr>
                            <td colspan="2">Here are the main functions you can perform:</td>
                        </tr>
                        <tr>
                            <td colspan="2"><b>1. Job Management:</b> Add, edit, or delete job titles, positions, salaries, and details.</td>
                        </tr>
                        <tr>
                            <td colspan="2"><b>2. Application Management:</b> View all applications, filter lists, see details, and make Accept/Reject decisions.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        document.getElementById('current-date').textContent = `${day} / ${month} / ${year}`;
    </script>
</body>
</html>