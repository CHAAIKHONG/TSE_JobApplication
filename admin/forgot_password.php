<?php
session_start();

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        
        $message = "If an account exists for $email, you will receive a password reset link shortly.";
        $messageType = "success";
    } else {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HR System</title>
    <style>
        :root {
            --color-primary: #e85d26;
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

        .forgot-container {
            background: var(--color-white);
            padding: 3rem;
            border-radius: 2rem;
            box-shadow: 0 2rem 3rem var(--color-light);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .logo .danger {
            color: var(--color-danger);
        }

        .logo p {
            color: var(--color-info-dark);
            margin-bottom: 2rem;
            font-size: 0.9rem;
            line-height: 1.5;
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
            margin-top: 0.5rem;
        }

        button:hover {
            background: #ffa683;
        }

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
            border: 1px solid #2e8b57;
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

    <div class="forgot-container">
        <div class="logo">
            <h2>Reset <span class="danger">Password</span></h2>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <input type="email" name="email" placeholder="Enter your Email Address" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <a href="admin_login.php" class="back-link">← Back to Login</a>
    </div>

</body>
</html>