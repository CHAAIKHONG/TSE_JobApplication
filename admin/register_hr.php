<?php
session_start();
// require_once '../database/db.php'; // 实际使用时取消注释

$message = '';
$messageType = ''; // 用于区分是 'success' 还是 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 基础验证
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } else {
        // --- 真实的数据库操作逻辑应该写在这里 ---
        // 建议使用 password_hash() 来加密密码，例如：
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // $sql = "INSERT INTO hr_users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', 'HR')";
        // mysqli_query($conn, $sql);
        
        // 模拟注册成功
        $message = "HR Account '$name' created successfully!";
        $messageType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register HR Account</title>
    <style>
        :root {
            --color-primary: #7380ec;
            --color-danger: #ff7782;
            --color-success: #41f1b6;
            --color-white: #fff;
            --color-dark: #363949;
            --color-light: rgba(132, 139, 200, 0.18);
            --color-background: #f6f6f9;
            --color-info-dark: #7d8da1;
        }

        * {
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: poppins, sans-serif;
            text-decoration: none;
        }

        body {
            background: var(--color-background);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-dark);
        }

        .register-container {
            background: var(--color-white);
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 2rem 3rem var(--color-light);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }
        
        .logo p {
            color: var(--color-info-dark);
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .logo .danger {
            color: var(--color-danger);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        input {
            padding: 1.2rem;
            border-radius: 0.4rem;
            border: 1px solid var(--color-light);
            background: var(--color-background);
            color: var(--color-dark);
            font-size: 1rem;
            outline: none;
            transition: all 300ms ease;
        }

        input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px var(--color-light);
        }

        button {
            background: var(--color-primary);
            color: var(--color-white);
            padding: 1.2rem;
            border-radius: 0.4rem;
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 300ms ease;
            margin-top: 1rem;
        }

        button:hover {
            background: #111e88;
        }

        /* 提示信息样式 */
        .alert {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            padding: 0.8rem;
            border-radius: 0.4rem;
            font-weight: 500;
        }
        .alert.error {
            color: var(--color-danger);
            background: rgba(255, 119, 130, 0.1);
            border: 1px solid var(--color-danger);
        }
        .alert.success {
            color: #2e8b57;
            background: rgba(65, 241, 182, 0.2);
            border: 1px solid var(--color-success);
        }

        .back-link {
            display: block;
            margin-top: 1.5rem;
            color: var(--color-info-dark);
            font-size: 0.9rem;
            transition: all 300ms ease;
        }

        .back-link:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="logo">
            <h2>Super Admin <span class="danger">Portal</span></h2>
            <p>Register a new HR Account</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="register_hr.php" method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            
            <button type="submit">Create Account</button>
        </form>

        <a href="admin_login.php" class="back-link">← Back to Login</a>
    </div>

</body>
</html>