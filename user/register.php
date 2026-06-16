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
    } elseif (!empty($phoneNo) && !preg_match('/^\d{1,11}$/', $phoneNo)) {
        $error = 'Phone number must be digits only and no more than 11 digits.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter (A-Z).';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter (a-z).';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number (0-9).';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = 'Password must contain at least one special character (e.g. !@#$%).';
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
    .field-input.input-error { border-color: #e05252 !important; }
    .field-input.input-ok    { border-color: #4caf50 !important; }

    /* 密码强度条 */
    .pw-strength { margin-top: 6px; }

    .pw-bar-track {
        height: 4px;
        background: #e8e6e0;
        border-radius: 4px;
        overflow: hidden;
    }

    .pw-bar-fill {
        height: 100%;
        width: 0%;
        border-radius: 4px;
        transition: width 0.3s, background 0.3s;
    }

    .pw-rules {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 7px;
    }

    .pw-rule {
        font-size: 10px;
        padding: 2px 7px;
        border-radius: 20px;
        border: 1px solid #e8e6e0;
        color: #aaa;
        background: #fafafa;
        transition: all 0.2s;
    }

    .pw-rule.ok {
        color: #3a8a3a;
        background: #eaf3de;
        border-color: #b8dfa0;
    }

    /* 提示文字 */
    .field-hint {
        font-size: 11px;
        margin-top: 4px;
        display: none;
    }

    .field-hint.error { color: #c0392b; display: block; }
    .field-hint.ok    { color: #3a8a3a;  display: block; }

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
        background: #fdf0f0;
        border: 1px solid #f7c1c1;
        color: #a32d2d;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        margin-bottom: 1rem;
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

                <form method="POST" action="register.php" enctype="multipart/form-data" id="regForm" novalidate>

                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">Full Name</label>
                            <input class="field-input" type="text" name="name" placeholder="Jane Doe"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />
                        </div>
                        <div class="field-group">
                            <label class="field-label">Phone <span class="opt">(optional)</span></label>
                            <input class="field-input" type="tel" name="phoneNo" id="phoneNo"
                                   placeholder="e.g. 60123456789" maxlength="11"
                                   value="<?= htmlspecialchars($_POST['phoneNo'] ?? '') ?>"
                                   oninput="checkPhone(this)" />
                            <span class="field-hint" id="phone-hint"></span>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Email</label>
                        <input class="field-input" type="email" name="email" id="emailInput"
                               placeholder="jane@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               oninput="checkEmail(this)" required />
                        <span class="field-hint" id="email-hint"></span>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">Password</label>
                            <input class="field-input" type="password" name="password"
                                   id="pw" placeholder="Min. 8 chars" required
                                   oninput="checkPassword(this.value)" />
                            <div class="pw-strength">
                                <div class="pw-bar-track">
                                    <div class="pw-bar-fill" id="pw-bar"></div>
                                </div>
                                <div class="pw-rules">
                                    <span class="pw-rule" id="r-len">8+ chars</span>
                                    <span class="pw-rule" id="r-upper">A–Z</span>
                                    <span class="pw-rule" id="r-lower">a–z</span>
                                    <span class="pw-rule" id="r-num">0–9</span>
                                    <span class="pw-rule" id="r-sym">!@#$</span>
                                </div>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Confirm</label>
                            <input class="field-input" type="password" name="confirm"
                                   id="confirm" placeholder="Re-enter" required
                                   oninput="checkConfirm(this.value)" />
                            <span class="field-hint" id="confirm-hint"></span>
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
/* ── 显示上传文件名 ── */
function showFileName(input) {
    const label = document.getElementById('file-name');
    if (input.files.length > 0) {
        label.textContent = '✓ ' + input.files[0].name;
        label.style.display = 'block';
    }
}

/* ── 电话号码验证：只允许数字，最多 11 位 ── */
function checkPhone(input) {
    // 自动过滤非数字字符
    input.value = input.value.replace(/\D/g, '');

    const hint = document.getElementById('phone-hint');

    if (input.value.length === 0) {
        input.classList.remove('input-error', 'input-ok');
        hint.className = 'field-hint';
        return;
    }

    if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
    }

    if (input.value.length <= 11 && input.value.length > 0) {
        input.classList.add('input-ok');
        input.classList.remove('input-error');
        hint.textContent = '✓ Valid phone number';
        hint.className = 'field-hint ok';
    } else {
        input.classList.add('input-error');
        input.classList.remove('input-ok');
        hint.textContent = '✗ Max 11 digits allowed.';
        hint.className = 'field-hint error';
    }
}

/* ── Email 格式验证 ── */
function checkEmail(input) {
    const hint = document.getElementById('email-hint');
    const val  = input.value.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

    if (!val) {
        input.classList.remove('input-error', 'input-ok');
        hint.className = 'field-hint';
        return;
    }

    if (valid) {
        input.classList.add('input-ok');
        input.classList.remove('input-error');
        hint.className = 'field-hint';
    } else {
        input.classList.add('input-error');
        input.classList.remove('input-ok');
        hint.textContent = '✗ Please enter a valid email address.';
        hint.className = 'field-hint error';
    }
}

/* ── 密码规则定义 ── */
const pwRules = {
    'r-len':   v => v.length >= 8,
    'r-upper': v => /[A-Z]/.test(v),
    'r-lower': v => /[a-z]/.test(v),
    'r-num':   v => /[0-9]/.test(v),
    'r-sym':   v => /[\W_]/.test(v),
};

const barColors = ['#e05252', '#e07d1e', '#e0c01e', '#7db83a', '#3a8a3a'];

/* ── 密码强度实时检查 ── */
function checkPassword(val) {
    let passed = 0;

    for (const [id, fn] of Object.entries(pwRules)) {
        const el = document.getElementById(id);
        if (fn(val)) {
            el.classList.add('ok');
            passed++;
        } else {
            el.classList.remove('ok');
        }
    }

    const bar = document.getElementById('pw-bar');
    bar.style.width      = (passed / 5 * 100) + '%';
    bar.style.background = passed > 0 ? barColors[passed - 1] : '#e8e6e0';

    // 同步刷新 confirm 提示
    const c = document.getElementById('confirm');
    if (c.value) checkConfirm(c.value);
}

/* ── 确认密码实时比对 ── */
function checkConfirm(val) {
    const pw   = document.getElementById('pw').value;
    const hint = document.getElementById('confirm-hint');
    const inp  = document.getElementById('confirm');

    if (!val) {
        inp.classList.remove('input-error', 'input-ok');
        hint.className = 'field-hint';
        return;
    }

    if (val === pw) {
        inp.classList.add('input-ok');
        inp.classList.remove('input-error');
        hint.textContent = '✓ Passwords match';
        hint.className   = 'field-hint ok';
    } else {
        inp.classList.add('input-error');
        inp.classList.remove('input-ok');
        hint.textContent = '✗ Passwords do not match';
        hint.className   = 'field-hint error';
    }
}

/* ── 提交前最终拦截 ── */
document.getElementById('regForm').addEventListener('submit', function(e) {
    const pw  = document.getElementById('pw').value;
    const cfg = document.getElementById('confirm').value;
    const ph  = document.getElementById('phoneNo').value;

    // 检查全部密码规则
    const allPassed = Object.values(pwRules).every(fn => fn(pw));
    if (!allPassed) {
        e.preventDefault();
        alert('Your password does not meet the requirements. Please check all the rules below the password field.');
        return;
    }

    // 密码一致性
    if (pw !== cfg) {
        e.preventDefault();
        alert('Passwords do not match. Please re-enter your confirm password.');
        return;
    }

    // 电话号码（填写时才验证）
    if (ph && !/^\d{1,11}$/.test(ph)) {
        e.preventDefault();
        alert('Phone number must be digits only, maximum 11 digits.');
    }
});
</script>

<?php include '../assets/include/user_footer.php'; ?>