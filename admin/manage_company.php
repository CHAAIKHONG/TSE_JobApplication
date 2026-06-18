<?php
session_start();
require_once '../database/db.php';

$message = '';
$messageType = '';

// --- 1. 自动建表：支持多公司管理的全新 companies 表 ---
$createTableQuery = "CREATE TABLE IF NOT EXISTS companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    industry VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTableQuery);

// 如果表里没数据，自动插入 3 家公司作为测试账号
$checkEmpty = mysqli_query($conn, "SELECT COUNT(*) as total FROM companies");
$rowCount = mysqli_fetch_assoc($checkEmpty)['total'];
if ($rowCount == 0) {
    mysqli_query($conn, "INSERT INTO companies (company_name, industry, email, phone, address, description) VALUES 
    ('Google Malaysia', 'Technology', 'contact@google.com.my', '03-22010000', 'Axiata Tower, Kuala Lumpur', 'Global tech giant specializing in search, AI, and cloud infrastructure.'),
    ('Intel Penang', 'Manufacturing', 'hr@intel.com', '04-6400000', 'Bayan Lepas Free Industrial Zone, Penang', 'Leading worldwide semiconductor chip manufacturer.'),
    ('ApplyGo Tech', 'Technology', 'hr@applygo.com', '012-3456789', '123 Tech Park, Melaka', 'An innovative talent recruitment platform matching employers with candidates.')");
}

// --- 2. 处理：添加新公司 ---
if (isset($_POST['add_company'])) {
    $c_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $c_industry = mysqli_real_escape_string($conn, $_POST['industry']);
    $c_email = mysqli_real_escape_string($conn, $_POST['email']);
    $c_phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $c_address = mysqli_real_escape_string($conn, $_POST['address']);
    $c_desc = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "INSERT INTO companies (company_name, industry, email, phone, address, description) 
            VALUES ('$c_name', '$c_industry', '$c_email', '$c_phone', '$c_address', '$c_desc')";
    if (mysqli_query($conn, $sql)) {
        $message = "Company added successfully!";
        $messageType = "success";
    } else {
        $message = "Error adding company: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

// --- 3. 处理：删除公司 ---
if (isset($_GET['delete'])) {
    $company_id = intval($_GET['delete']);
    $sql = "DELETE FROM companies WHERE company_id = $company_id";
    if (mysqli_query($conn, $sql)) {
        $message = "Company profile deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting company: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

// --- 4. 处理：更新公司资料 ---
if (isset($_POST['update_company'])) {
    $company_id = intval($_POST['company_id']);
    $c_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $c_industry = mysqli_real_escape_string($conn, $_POST['industry']);
    $c_email = mysqli_real_escape_string($conn, $_POST['email']);
    $c_phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $c_address = mysqli_real_escape_string($conn, $_POST['address']);
    $c_desc = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "UPDATE companies SET 
            company_name='$c_name', industry='$c_industry', 
            email='$c_email', phone='$c_phone', 
            address='$c_address', description='$c_desc' 
            WHERE company_id=$company_id";
            
    if (mysqli_query($conn, $sql)) {
        header("Location: manage_company.php?msg=updated");
        exit;
    } else {
        $message = "Error updating company: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $message = "Company updated successfully!";
    $messageType = "success";
}

// --- 5. 处理：点击 Edit 时加载单家公司数据 ---
$edit_company = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_res = mysqli_query($conn, "SELECT * FROM companies WHERE company_id = $edit_id");
    if (mysqli_num_rows($edit_res) > 0) {
        $edit_company = mysqli_fetch_assoc($edit_res);
    }
}

// 获取完整的公司列表展现到表格
$companies_list = mysqli_query($conn, "SELECT * FROM companies ORDER BY company_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Management - HR System</title>
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
        .container { display: grid; width: 96%; margin: 0 auto; gap: 1.8rem; grid-template-columns: 14rem auto; }
        a { color: var(--color-dark); }
        h1 { font-weight: 800; font-size: 1.8rem; margin-bottom: 1rem; }
        .danger { color: var(--color-danger); }
        
        aside { height: 100vh; }
        aside .top { background: white; display: flex; align-items: center; justify-content: center; margin-top: 1.4rem; border-radius: 0.4rem; padding: 1.5rem; }
        
        /* --- Logo 设计 --- */
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
        aside .sidebar { background: rgb(255, 255, 255); display: flex; flex-direction: column; height: 86vh; position: relative; top: 1rem; border-radius: 0.4rem; padding-top: 2rem; }
        aside .sidebar a { display: flex; color: var(--color-info-dark); margin-left: 2rem; gap: 1rem; align-items: center; position: relative; height: 3.7rem; transition: all 300ms ease; }
        aside .sidebar a span { font-size: 1.6rem; transition: all 300ms ease; }
        aside .sidebar a.active { background: var(--color-light); color: var(--color-primary); margin-left: 0; }
        aside .sidebar a.active:before { content: ""; width: 6px; height: 100%; background: var(--color-primary); }
        aside .sidebar a.active span { color: var(--color-primary); margin-left: calc(1rem - 3px); }
        aside .sidebar a:hover { color: var(--color-primary); }
        aside .sidebar a:hover span { margin-left: 1rem; }

        /* --- 独立的 Logout 按钮设计 --- */
        aside .sidebar a.logout-btn {
            margin-top: auto; 
            margin-bottom: 2rem;
            margin-left: 1.5rem;
            margin-right: 1.5rem;
            border: 2px solid var(--color-info-light);
            border-radius: 50px;
            justify-content: center;
            color: var(--color-info-dark);
            transition: all 0.3s ease;
        }
        aside .sidebar a.logout-btn:hover {
            background: #ffeaea; 
            border-color: var(--color-danger);
            color: var(--color-danger);
        }
        aside .sidebar a.logout-btn:hover span {
            margin-left: 0; 
        }

        main { margin-top: 1.4rem; padding-bottom: 3rem; }
        .form-section, .table-section { background: white; padding: 1.8rem; border-radius: 2rem; margin-top: 1.5rem; box-shadow: 0 2rem 3rem var(--color-light); }
        
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
                <a href="manage_jobs.php">
                    <span class="material-symbols-sharp">work</span>
                    <h3>Job Management</h3>
                </a>
                <a href="manage_user.php">
                    <span class="material-symbols-sharp">description</span>
                    <h3>User Management</h3>
                </a>
                <a href="manage_company.php" class="active">
                    <span class="material-symbols-sharp">business</span>
                    <h3>Company Management</h3>
                </a>
                
                <a href="admin_login.php" class="logout-btn">
                    <span class="material-symbols-sharp">logout</span>
                    <h3>Logout</h3>
                </a>
            </div>
        </aside>

        <main>
            <h1>Company Management</h1>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-section">
                <?php if ($edit_company): ?>
                    <h2>Edit Company Profile</h2>
                    <form action="manage_company.php" method="POST">
                        <input type="hidden" name="company_id" value="<?php echo $edit_company['company_id']; ?>">
                        <div class="form-group">
                            <input type="text" name="company_name" placeholder="Company Name" value="<?php echo htmlspecialchars($edit_company['company_name']); ?>" required>
                            <input type="text" name="industry" placeholder="Industry (e.g. Technology, Finance)" value="<?php echo htmlspecialchars($edit_company['industry']); ?>" required>
                            <input type="email" name="email" placeholder="Contact Email" value="<?php echo htmlspecialchars($edit_company['email']); ?>" required>
                            <input type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($edit_company['phone']); ?>" required>
                            <input type="text" name="address" placeholder="Headquarters Address" value="<?php echo htmlspecialchars($edit_company['address']); ?>" style="grid-column: span 2;" required>
                            <textarea name="description" placeholder="Company Profile Description / About Us..." required><?php echo htmlspecialchars($edit_company['description']); ?></textarea>
                        </div>
                        <button type="submit" name="update_company" class="btn btn-primary">Update Profile</button>
                        <a href="manage_company.php" style="margin-left: 1rem; color: var(--color-info-dark); text-decoration: underline;">Cancel</a>
                    </form>
                <?php else: ?>
                    <h2>Register New Client Company</h2>
                    <form action="manage_company.php" method="POST">
                        <div class="form-group">
                            <input type="text" name="company_name" placeholder="Company Name (e.g. Shopee)" required>
                            <input type="text" name="industry" placeholder="Industry Type (e.g. E-Commerce)" required>
                            <input type="email" name="email" placeholder="Corporate HR Email" required>
                            <input type="text" name="phone" placeholder="Office Phone" required>
                            <input type="text" name="address" placeholder="HQ Address Location" style="grid-column: span 2;" required>
                            <textarea name="description" placeholder="Company Background & Operations overview..." required></textarea>
                        </div>
                        <button type="submit" name="add_company" class="btn btn-primary">Add Company</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="table-section">
                <h2>Active Enterprise Partners</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Industry</th>
                            <th>Contact Info</th>
                            <th>HQ Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($companies_list) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($companies_list)): ?>
                                <tr>
                                    <td><?php echo $row['company_id']; ?></td>
                                    <td><b style="color: var(--color-dark);"><?php echo htmlspecialchars($row['company_name']); ?></b></td>
                                    <td><span style="background: var(--color-light); padding: 0.3rem 0.6rem; border-radius: 0.3rem; font-weight: 500; font-size: 0.8rem; color: var(--color-primary);"><?php echo htmlspecialchars($row['industry']); ?></span></td>
                                    <td>
                                        <div style="font-size: 0.85rem;">📧 <?php echo htmlspecialchars($row['email']); ?></div>
                                        <div style="font-size: 0.85rem; color: var(--color-info-dark);">📞 <?php echo htmlspecialchars($row['phone']); ?></div>
                                    </td>
                                    <td style="max-width: 220px; font-size: 0.85rem;"><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td class="actions">
                                        <a href="manage_company.php?edit=<?php echo $row['company_id']; ?>" class="btn btn-warning">Edit</a>
                                        <a href="manage_company.php?delete=<?php echo $row['company_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this corporate partner?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--color-info-dark);">No client companies registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>