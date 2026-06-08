<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once '../database/db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']     ?? '');
    $email       = trim($_POST['email']    ?? '');
    $phoneNo     = trim($_POST['phoneNo']  ?? '');
    $password    = $_POST['password']      ?? '';
    $confirm     = $_POST['confirm']       ?? '';
    $resume_path = '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'This email address is already registered.';
        } else {
            if (!empty($_FILES['resume']['name'])) {
                $upload_dir = '../uploads/resumes/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $allowed_types = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $file_type = $_FILES['resume']['type'];
                $file_size = $_FILES['resume']['size'];

                if (!in_array($file_type, $allowed_types)) {
                    $error = 'Resume must be a PDF or Word document.';
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $error = 'Resume file size must not exceed 5MB.';
                } else {
                    $ext      = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('resume_') . '.' . $ext;
                    move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $filename);
                    $resume_path = 'uploads/resumes/' . $filename;
                }
            }

            if (empty($error)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("INSERT INTO users (name, email, phoneNo, password, resume, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssss", $name, $email, $phoneNo, $hashed, $resume_path);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
        }
        $check->close();
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

    .bg-dots {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    .auth-wrap {
        width: 100%;
        max-width: 900px;
        position: relative;
        z-index: 1;
    }

    .auth-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #e8e6e0;
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        overflow: hidden;
    }

    /* 左栏 */
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
        top: -80px; right: -80px;
        width: 260px; height: 260px;
        border-radius: 50%;
        background: rgba(224,90,30,0.15);
        pointer-events: none;
    }

    .auth-left::after {
        content: '';
        position: absolute;
        bottom: -60px; left: -60px;
        width: 200px; height: 200px;
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
        width: 32px; height: 32px;
        background: #E05A1E;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
    }

    .auth-brand-dot svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; }
    .auth-brand-name { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: #fff; }

    .auth-left-body { position: relative; z-index: 1; }

    .auth-left-title {
        font-family: 'Syne', sans-serif;
        font-size: 26px; font-weight: 700;
        color: #fff; line-height: 1.3;
        margin: 0 0 1rem;
        letter-spacing: -0.5px;
    }

    .auth-left-title span { color: #E05A1E; }
    .auth-left-desc { font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.7; margin: 0 0 2rem; }

    /* steps list */
    .steps-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px; }

    .steps-list li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 13px;
        color: rgba(255,255,255,0.65);
        line-height: 1.5;
    }

    .step-num {
        width: 22px; height: 22px;
        background: rgba(224,90,30,0.25);
        border: 1px solid rgba(224,90,30,0.4);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700;
        color: #E05A1E;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* 右栏 */
    .auth-right {
        padding: 2.5rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-heading {
        font-family: 'Syne', sans-serif;
        font-size: 22px; font-weight: 700;
        color: #1a1a1a; margin: 0 0 4px;
        letter-spacing: -0.5px;
    }

    .auth-sub { font-size: 13px; color: #888; margin: 0 0 1.25rem; }
    .auth-sub strong { color: #1a1a1a; }

    .field-group { margin-bottom: 0.85rem; }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .field-label {
        display: block;
        font-size: 11px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.6px;
        color: #555; margin-bottom: 5px;
    }

    .field-label .opt { font-size: 11px; color: #bbb; font-weight: 400; text-transform: none; letter-spacing: 0; }

    .field-input {
        width: 100%;
        padding: 9px 13px;
        border: 1.5px solid #e8e6e0;
        border-radius: 10px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px; color: #1a1a1a;
        background: #fff; outline: none;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .field-input:focus { border-color: #E05A1E; }
    .field-input::placeholder { color: #bbb; }

    .resume-upload {
        border: 1.5px dashed #d0cdc5;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
    }

    .resume-upload:hover { border-color: #E05A1E; background: #fdf8f5; }
    .resume-text { font-size: 12px; color: #888; margin: 0; }
    .resume-text strong { color: #E05A1E; }

    .auth-btn {
        width: 100%; padding: 11px;
        background: #E05A1E; color: #fff;
        border: none; border-radius: 12px;
        font-family: 'Syne', sans-serif;
        font-size: 15px; font-weight: 600;
        cursor: pointer; margin-top: 1rem;
        letter-spacing: -0.2px; transition: background 0.2s;
    }

    .auth-btn:hover { background: #c74d16; }

    .auth-switch { text-align: center; font-size: 13px; color: #888; margin-top: 1rem; }
    .auth-switch a { color: #E05A1E; font-weight: 500; text-decoration: none; }

    .alert-error {
        background: #fdf0f0; border: 1px solid #f7c1c1;
        color: #a32d2d; border-radius: 10px;
        padding: 10px 14px; font-size: 13px; margin-bottom: 1rem;
    }

    /* success state */
    .success-box { text-align: center; padding: 2rem 0; }
    .success-icon {
        width: 60px; height: 60px;
        background: #eaf3de; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem; font-size: 28px;
    }
    .success-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 700; color: #1a1a1a; margin: 0 0 6px; }
    .success-sub   { font-size: 13px; color: #888; margin: 0 0 1.5rem; }

    .btn-dark {
        width: 100%; padding: 12px;
        background: #1a1a1a; color: #fff;
        border: none; border-radius: 12px;
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 600;
        cursor: pointer; text-decoration: none; display: block;
        text-align: center; transition: background 0.2s;
    }
    .btn-dark:hover { background: #333; }

    #file-name { font-size: 12px; color: #E05A1E; margin: 4px 0 0; display: none; }
</style>

<div class="auth-center">
    <div class="bg-dots"></div>
    <div class="auth-wrap">
        <div class="auth-card">

            <!-- 左栏 -->
            <div class="auth-left">
                <div class="auth-brand">
                    <div class="auth-brand-dot">
                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span class="auth-brand-name">ApplyGo</span>
                </div>

                <div class="auth-left-body">
                    <p class="auth-left-title">Start your <span>career journey</span> today</p>
                    <p class="auth-left-desc">Join thousands of job seekers who found their dream role through ApplyGo.</p>

                    <ul class="steps-list">
                        <li>
                            <span class="step-num">1</span>
                            <span>Create your free account in under a minute</span>
                        </li>
                        <li>
                            <span class="step-num">2</span>
                            <span>Upload your resume and complete your profile</span>
                        </li>
                        <li>
                            <span class="step-num">3</span>
                            <span>Browse 8,000+ companies and apply instantly</span>
                        </li>
                        <li>
                            <span class="step-num">4</span>
                            <span>Track all your applications in one dashboard</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 右栏 -->
            <div class="auth-right">

                <?php if ($success): ?>
                <div class="success-box">
                    <div class="success-icon">✓</div>
                    <p class="success-title">You're in!</p>
                    <p class="success-sub">Your account has been created successfully.</p>
                    <a href="login.php" class="btn-dark">Sign in now →</a>
                </div>

                <?php else: ?>
                <p class="auth-heading">Create account</p>
                <p class="auth-sub">Join thousands finding their <strong>dream job</strong></p>

                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php" enctype="multipart/form-data">
                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">Full Name</label>
                            <input class="field-input" type="text" name="name" placeholder="Jane Doe"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
                        </div>
                        <div class="field-group">
                            <label class="field-label">Phone <span class="opt">(optional)</span></label>
                            <input class="field-input" type="tel" name="phoneNo" placeholder="+60 12-345 6789"
                                   value="<?= htmlspecialchars($_POST['phoneNo'] ?? '') ?>" />
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <input class="field-input" type="email" name="email" placeholder="jane@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">Password</label>
                            <input class="field-input" type="password" name="password" placeholder="Min. 8 chars" required />
                        </div>
                        <div class="field-group">
                            <label class="field-label">Confirm</label>
                            <input class="field-input" type="password" name="confirm" placeholder="Re-enter" required />
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Resume <span class="opt">(optional)</span></label>
                        <div class="resume-upload" onclick="document.getElementById('resumeFile').click()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#bbb"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                                 style="display:block;margin:0 auto 4px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p class="resume-text"><strong>Click to upload</strong> or drag & drop</p>
                            <p class="resume-text" style="margin-top:2px;">PDF, DOC up to 5MB</p>
                            <input type="file" id="resumeFile" name="resume" style="display:none;"
                                   accept=".pdf,.doc,.docx" onchange="showFileName(this)" />
                        </div>
                        <p id="file-name"></p>
                    </div>

                    <button class="auth-btn" type="submit">Create account →</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function showFileName(input) {
    const label = document.getElementById('file-name');
    if (input.files.length > 0) {
        label.textContent = '✓ ' + input.files[0].name;
        label.style.display = 'block';
    }
}
</script>

<?php include '../assets/include/user_footer.php'; ?>   