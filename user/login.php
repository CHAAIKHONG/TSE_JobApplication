<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../database/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['user_id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}
?>
<?php include '../assets/include/user_topbar.php'; ?>

<style>
    .auth-center {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 64px - 220px);
        padding: 2rem 1rem;
        background: #faf9f7;
        position: relative;
        overflow: hidden;
    }

    /* 背景装饰圆圈 */
    .auth-center::before {
        content: '';
        position: absolute;
        top: -120px;
        right: -120px;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(224,90,30,0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .auth-center::after {
        content: '';
        position: absolute;
        bottom: -100px;
        left: -100px;
        width: 350px;
        height: 350px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(224,90,30,0.06) 0%, transparent 70%);
        pointer-events: none;
    }

    .auth-wrap {
        width: 100%;
        max-width: 880px;
        position: relative;
        z-index: 1;
    }

    /* 横向两栏 card */
    .auth-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #e8e6e0;
        display: grid;
        grid-template-columns: 1fr 1fr;
        overflow: hidden;
    }

    /* 左栏：品牌/插图区域 */
    .auth-left {
        background: #1a1a1a;
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .auth-left::before {
        content: '';
        position: absolute;
        top: -80px;
        right: -80px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(224,90,30,0.15);
        pointer-events: none;
    }

    .auth-left::after {
        content: '';
        position: absolute;
        bottom: -60px;
        left: -60px;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(224,90,30,0.1);
        pointer-events: none;
    }

    .auth-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        z-index: 1;
    }

    .auth-brand-dot {
        width: 32px;
        height: 32px;
        background: #E05A1E;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .auth-brand-dot svg {
        width: 18px;
        height: 18px;
        fill: #fff;
    }

    .auth-brand-name {
        font-family: 'Syne', sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #fff;
    }

    .auth-left-body {
        position: relative;
        z-index: 1;
    }

    .auth-left-title {
        font-family: 'Syne', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #fff;
        line-height: 1.3;
        margin: 0 0 1rem;
        letter-spacing: -0.5px;
    }

    .auth-left-title span {
        color: #E05A1E;
    }

    .auth-left-desc {
        font-size: 14px;
        color: rgba(255,255,255,0.55);
        line-height: 1.7;
        margin: 0 0 2rem;
    }

    .stat-row {
        display: flex;
        gap: 10px;
    }

    .stat-chip {
        flex: 1;
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 12px 8px;
        text-align: center;
    }

    .stat-num {
        font-family: 'Syne', sans-serif;
        font-size: 20px;
        font-weight: 700;
        display: block;
        color: #fff;
    }

    .stat-num.orange { color: #E05A1E; }

    .stat-lbl {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255,255,255,0.4);
        margin-top: 3px;
        display: block;
    }

    /* 右栏：表单区域 */
    .auth-right {
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-heading {
        font-family: 'Syne', sans-serif;
        font-size: 24px;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 4px;
        letter-spacing: -0.5px;
    }

    .auth-sub {
        font-size: 13px;
        color: #888;
        margin: 0 0 1.75rem;
    }

    .auth-sub strong { color: #1a1a1a; }

    .field-group { margin-bottom: 1rem; }

    .field-label {
        display: block;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #555;
        margin-bottom: 6px;
    }

    .field-input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e8e6e0;
        border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        color: #1a1a1a;
        background: #fff;
        outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .field-input:focus { border-color: #E05A1E; }
    .field-input::placeholder { color: #bbb; }

    .auth-forgot {
        text-align: right;
        margin-bottom: 0;
        margin-top: -6px;
    }

    .auth-forgot a {
        font-size: 12px;
        color: #E05A1E;
        text-decoration: none;
        font-weight: 500;
    }

    .auth-btn {
        width: 100%;
        padding: 12px;
        background: #1a1a1a;
        color: #fff;
        border: none;
        border-radius: 12px;
        font-family: 'Syne', sans-serif;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1.25rem;
        letter-spacing: -0.2px;
        transition: background 0.2s;
    }

    .auth-btn:hover { background: #333; }

    .auth-divider {
        text-align: center;
        font-size: 12px;
        color: #bbb;
        margin: 1.25rem 0;
        position: relative;
    }

    .auth-divider::before, .auth-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 38%;
        height: 1px;
        background: #e8e6e0;
    }

    .auth-divider::before { left: 0; }
    .auth-divider::after { right: 0; }

    .auth-switch {
        text-align: center;
        font-size: 13px;
        color: #888;
        margin-top: 1.25rem;
    }

    .auth-switch a {
        color: #E05A1E;
        font-weight: 500;
        text-decoration: none;
    }

    .alert-error {
        background: #fdf0f0;
        border: 1px solid #f7c1c1;
        color: #a32d2d;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        margin-bottom: 1rem;
    }

    /* 背景装饰点点 */
    .bg-dots {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
        background-size: 28px 28px;
    }
</style>

<!-- auth-center replaces .page-content — centers the card vertically & horizontally -->
<div class="auth-center">
    <div class="bg-dots"></div>
    <div class="auth-wrap">
        <div class="auth-card">

            <!-- 左栏 -->
            <div class="auth-left">
                <div class="auth-brand">
                    <div class="auth-brand-dot">
                        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <span class="auth-brand-name">ApplyGo</span>
                </div>

                <div class="auth-left-body">
                    <p class="auth-left-title">Land your next <span>dream job</span> faster</p>
                    <p class="auth-left-desc">Thousands of companies are actively hiring. Your next opportunity is just one application away.</p>
                    <div class="stat-row">
                        <div class="stat-chip">
                            <span class="stat-num orange">247</span>
                            <span class="stat-lbl">new this week</span>
                        </div>
                        <div class="stat-chip">
                            <span class="stat-num">8K+</span>
                            <span class="stat-lbl">companies</span>
                        </div>
                        <div class="stat-chip">
                            <span class="stat-num">50K</span>
                            <span class="stat-lbl">hired</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右栏 -->
            <div class="auth-right">
                <p class="auth-heading">Welcome back 👋</p>
                <p class="auth-sub">Find your next <strong>great opportunity</strong></p>

                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <input class="field-input" type="email" name="email" placeholder="jane@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
                    </div>

                    <div class="field-group">
                        <label class="field-label">Password</label>
                        <input class="field-input" type="password" name="password" placeholder="••••••••" required />
                    </div>

                    <p class="auth-forgot"><a href="forgotpassword.php">Forgot password?</a></p>

                    <button class="auth-btn" type="submit">Sign in →</button>
                </form>

                <div class="auth-divider">or</div>

                <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
            </div>

        </div>
    </div>
</div>

<?php include '../assets/include/user_footer.php'; ?>
