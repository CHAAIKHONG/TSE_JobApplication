<?php
// Database Connection
require_once '../database/db.php';

$total_jobs = 0; 
$total_applications = 0;

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
            --color-primary: #7380ec;
            --color-danger: #ff7782;
            --color-success: #41f1b6;
            --color-warning: #ffbb55;
            --color-white: #fff;
            --color-info-dark: #7d8da1;
            --color-info-light: #dce1eb;
            --color-dark: #363949;
            --color-light: rgba(132, 139, 200, 0.18);
            --color-primary-variant: #111e88;
            --color-dark-variant: #677483;
            --color-background: #f6f6f9;
        }

        * {
            margin: 0; padding: 0; outline: 0; appearance: none; border: 0;
            text-decoration: none; list-style: none; box-sizing: border-box;
        }

        html { font-size: 14px; }

        body {
            width: 100vw; height: 100vh;
            font-family: poppins, sans-serif;
            font-size: 0.88rem;
            background: var(--color-background);
            user-select: none; overflow-x: hidden;
            color: var(--color-dark);
        }

        .container {
            display: grid; width: 96%; margin: 0 auto; gap: 1.8rem;
            grid-template-columns: 14rem auto; 
        }

        a { color: var(--color-dark); }
        h1 { font-weight: 800; font-size: 1.8rem; }
        h2 { font-size: 1.4rem; }
        h3 { font-size: 0.87rem; }
        .danger { color: var(--color-danger); }

        aside { height: 100vh; }
        aside .top {
            background: white; display: flex; align-items: center;
            justify-content: center; margin-top: 1.4rem;
            border-radius: 0.4rem; padding: 1.5rem;
        }
        aside .logo { display: flex; gap: 0.8rem; }
        aside .logo h2 { font-size: 1.5rem; }
        
        aside .sidebar {
            background: rgb(255, 255, 255); display: flex; flex-direction: column;
            height: 86vh; position: relative; top: 1rem; border-radius: 0.4rem;
            padding-top: 2rem;
        }
        aside .sidebar a {
            display: flex; color: var(--color-info-dark); margin-left: 2rem;
            gap: 1rem; align-items: center; position: relative; height: 3.7rem;
            transition: all 300ms ease;
        }
        aside .sidebar a span { font-size: 1.6rem; transition: all 300ms ease; }
        aside .sidebar a.active { background: var(--color-light); color: var(--color-primary); margin-left: 0; }
        aside .sidebar a.active:before {
            content: ""; width: 6px; height: 100%; background: var(--color-primary);
        }
        aside .sidebar a.active span { color: var(--color-primary); margin-left: calc(1rem - 3px); }
        aside .sidebar a:hover { color: var(--color-primary); }
        aside .sidebar a:hover span { margin-left: 1rem; }

        main { margin-top: 1.4rem; }
        main .date {
            display: inline-block; background: var(--color-light);
            border-radius: 0.4rem; margin-top: 1rem; padding: 0.5rem 1.6rem;
        }

        main .insights {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.6rem;
        }
        main .insights > div {
            background: white; padding: 1.8rem; border-radius: 2rem;
            margin-top: 1rem; box-shadow: 0 2rem 3rem var(--color-light);
            transition: all 300ms ease;
        }
        main .insights > div:hover { box-shadow: none; }
        main .insights > div span {
            background: cornflowerblue; padding: 0.5rem; border-radius: 50%;
            color: #fff; font-size: 2rem; display: inline-block;
        }
        main .insights > div.total_apps span { background: var(--color-danger); }
        main .insights > div .middle {
            display: flex; align-items: center; justify-content: space-between; margin-top: 1rem;
        }
        main .insights h3 { margin: 1rem 0 0.6rem; font-size: 1rem; }
        main .insights h1 { font-size: 2.5rem; }

        main .recent-activities { margin-top: 2rem; }
        main .recent-activities h2 { margin-bottom: 0.8rem; }
        main .recent-activities table {
            background: #fff; width: 100%; border-radius: 2rem; padding: 1.8rem;
            text-align: left; box-shadow: 0 2rem 3rem var(--color-light);
            transition: all 300ms ease; border-collapse: collapse;
        }
        main .recent-activities table:hover { box-shadow: none; }
        main table tbody td {
            height: 2.8rem; border-bottom: 1px solid var(--color-light);
            color: var(--color-dark-variant); padding: 1rem;
        }
        main table tbody tr:last-child td { border: none; }
    </style>
</head>

<body>
    <div class="container">
        <aside>
            <div class="top">
                <div class="logo">
                    <h2>HR <span class="danger">SYSTEM</span></h2>
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
                <a href="manage_user.php">
                    <span class="material-symbols-sharp">description</span>
                    <h3>User Management</h3>
                </a>
                <a href="admin_login.php">
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

            <div class="recent-activities">
                <h2>Activities Overview</h2>
                <table>
                    <tbody>
                        <tr>
                            <td><b>1. Job Management:</b> Add, edit, or delete job titles, positions, salaries, and details.</td>
                        </tr>
                        <tr>
                            <td><b>2. Application Management:</b> View all applications, filter lists, see details, and make Accept/Reject decisions.</td>
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