<?php
session_start();
// require_once '../database/db.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === 'admin@gmail.com' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin Login</title>
    <style>
        :root {
            --color-primary: #e85d26;
            --color-danger: #ff7782;
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

        .login-container {
            background: var(--color-white);
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 2rem 3rem var(--color-light);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            font-weight: 800;
        }

        .logo .danger {
            color: var(--color-danger);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
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
            margin-top: 0.5rem;
        }

        button:hover {
            background: #ffa683;
        }

        .error {
            color: var(--color-danger);
            font-size: 0.87rem;
            margin-bottom: 1rem;
            background: rgba(255, 119, 130, 0.1);
            padding: 0.5rem;
            border-radius: 0.4rem;
        }

        .extra-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .extra-links a {
            color: var(--color-info-dark);
            transition: all 300ms ease;
        }

        .extra-links a:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo">
            <h2>HR <span class="danger">SYSTEM</span></h2>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            
            <div class="extra-links">
            </div>
        </form>
    </div>

</body>
</html>