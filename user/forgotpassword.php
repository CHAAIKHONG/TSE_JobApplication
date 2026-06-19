<?php
session_start();

// ── If already logged in, redirect ──────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../database/db.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
// ── Load PHPMailer (via Composer) ────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

// ════════════════════════════════════════════════════════════════════════════
//  Gmail SMTP Configuration — replace with your credentials
// ════════════════════════════════════════════════════════════════════════════
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'moonbees5431@gmail.com');   // ← your Gmail address
define('SMTP_PASS',     'dgjz zxiz hfwn cabp'); // ← Gmail App Password (16-char)
define('SMTP_FROM',     'moonbees5431@gmail.com');   // ← same as SMTP_USER
define('SMTP_FROM_NAME','ApplyGo');

// ════════════════════════════════════════════════════════════════════════════
//  Step tracking via session
//  step 1 = enter email
//  step 2 = enter OTP
//  step 3 = set new password
// ════════════════════════════════════════════════════════════════════════════
if (!isset($_SESSION['fp_step'])) {
    $_SESSION['fp_step'] = 1;
}

$step    = (int)$_SESSION['fp_step'];
$error   = '';
$success = '';

// ────────────────────────────────────────────────────────────────────────────
//  Helper: send OTP email
// ────────────────────────────────────────────────────────────────────────────
function sendOtpEmail(string $toEmail, string $otp): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your ApplyGo Password Reset Code';
        $mail->Body    = "
        <div style='font-family:DM Sans,Arial,sans-serif;max-width:480px;margin:auto;padding:32px;background:#faf9f7;border-radius:16px;border:1px solid #e2ddd8;'>
            <div style='text-align:center;margin-bottom:24px;'>
                <div style='display:inline-flex;align-items:center;gap:8px;'>
                    <div style='background:#E05A1E;border-radius:8px;width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;'>
                        <span style='color:#fff;font-weight:700;font-size:14px;'>A</span>
                    </div>
                    <span style='font-family:Syne,Arial,sans-serif;font-size:20px;font-weight:700;color:#1a1a1a;'>ApplyGo</span>
                </div>
            </div>
            <h2 style='font-size:22px;font-weight:700;color:#1a1a1a;margin:0 0 8px;text-align:center;'>Password Reset</h2>
            <p style='font-size:14px;color:#6b6560;text-align:center;margin:0 0 28px;'>Use the code below to reset your password. It expires in <strong>10 minutes</strong>.</p>
            <div style='background:#fff;border:2px dashed #e2ddd8;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;'>
                <span style='font-family:monospace;font-size:40px;font-weight:700;letter-spacing:10px;color:#E05A1E;'>{$otp}</span>
            </div>
            <p style='font-size:12px;color:#aaa;text-align:center;margin:0;'>If you didn't request this, you can safely ignore this email.</p>
        </div>";
        $mail->AltBody = "Your ApplyGo password reset code is: {$otp}\nThis code expires in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  POST handlers
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ── STEP 1: Validate email & send OTP ───────────────────────────────────
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check user exists
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();

            if (!$exists) {
                // Vague message for security — don't reveal whether email exists
                $error = 'If this email is registered, a reset code will be sent.';
            } else {
                // Rate-limit: max 3 OTPs per email in last 10 minutes
                $rateStmt = $conn->prepare(
                    "SELECT COUNT(*) FROM password_resets
                     WHERE email = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
                );
                $rateStmt->bind_param('s', $email);
                $rateStmt->execute();
                $rateStmt->bind_result($recentCount);
                $rateStmt->fetch();
                $rateStmt->close();

                if ($recentCount >= 3) {
                    $error = 'Too many requests. Please wait 10 minutes before trying again.';
                } else {
                    // Generate 6-digit OTP
                    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    // Invalidate old OTPs for this email
                    $delStmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE email = ?');
                    $delStmt->bind_param('s', $email);
                    $delStmt->execute();
                    $delStmt->close();

                    // Insert new OTP
                    $insStmt = $conn->prepare(
                        'INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)'
                    );
                    $insStmt->bind_param('sss', $email, $otp, $expires);
                    $insStmt->execute();
                    $insStmt->close();

                    if (sendOtpEmail($email, $otp)) {
                        $_SESSION['fp_step']  = 2;
                        $_SESSION['fp_email'] = $email;
                        $step = 2;
                        
                    } else {
                        $error = 'Failed to send email. Please try again later.';
                    }
                }
            }
        }
    }

    // ── STEP 2: Verify OTP ───────────────────────────────────────────────────
    elseif ($action === 'verify_otp') {
        $digits = array_map(fn($k) => trim($_POST[$k] ?? ''), ['d1','d2','d3','d4','d5','d6']);
        $otp    = implode('', $digits);
        $email  = $_SESSION['fp_email'] ?? '';

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = 'Please enter the complete 6-digit code.';
        } elseif (empty($email)) {
            $error = 'Session expired. Please start again.';
            $_SESSION['fp_step'] = 1; $step = 1;
        } else {
            $stmt = $conn->prepare(
                "SELECT id FROM password_resets
                 WHERE email = ? AND otp = ? AND used = 0 AND expires_at > NOW()
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->bind_param('ss', $email, $otp);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $error = 'Invalid or expired code. Please try again.';
            } else {
                $stmt->bind_result($resetId);
                $stmt->fetch();
                $_SESSION['fp_step']     = 3;
                $_SESSION['fp_reset_id'] = $resetId;
                $step = 3;
            }
            $stmt->close();
        }
    }

    // ── STEP 3: Reset password ───────────────────────────────────────────────
    elseif ($action === 'reset_password') {
        $newPass  = $_POST['new_password']     ?? '';
        $confPass = $_POST['confirm_password'] ?? '';
        $email    = $_SESSION['fp_email']      ?? '';
        $resetId  = $_SESSION['fp_reset_id']   ?? 0;

        if (empty($email) || empty($resetId)) {
            $error = 'Session expired. Please start again.';
            $_SESSION['fp_step'] = 1; $step = 1;
        } elseif (strlen($newPass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPass)) {
            $error = 'Password must contain at least one uppercase letter (A-Z).';
        } elseif (!preg_match('/[a-z]/', $newPass)) {
            $error = 'Password must contain at least one lowercase letter (a-z).';
        } elseif (!preg_match('/[0-9]/', $newPass)) {
            $error = 'Password must contain at least one number (0-9).';
        } elseif (!preg_match('/[\W_]/', $newPass)) {
            $error = 'Password must contain at least one special character (e.g. !@#$%).';
        } elseif ($newPass !== $confPass) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);

            // Update user password
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
            $stmt->bind_param('ss', $hashed, $email);
            $stmt->execute();
            $stmt->close();

            // Mark OTP as used
            $stmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
            $stmt->bind_param('i', $resetId);
            $stmt->execute();
            $stmt->close();

            // Clear session
            unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_reset_id']);

            $success = 'done';
        }
    }

    elseif ($action === 'resend_otp') {
    $email = $_SESSION['fp_email'] ?? '';
    if (empty($email)) {
        $_SESSION['fp_step'] = 1; $step = 1;
    } else {
        $step = 2; // 👈 加这行

        $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $delStmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE email = ?');
        $delStmt->bind_param('s', $email);
        $delStmt->execute();
        $delStmt->close();

        $insStmt = $conn->prepare('INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)');
        $insStmt->bind_param('sss', $email, $otp, $expires);
        $insStmt->execute();
        $insStmt->close();

        if (sendOtpEmail($email, $otp)) {
            $success = 'resent';
        } else {
            $error = 'Failed to resend code. Please try again.';
        }
    }
}

    // ── Go back to step 1 ────────────────────────────────────────────────────
    elseif ($action === 'restart') {
        unset($_SESSION['fp_step'], $_SESSION['fp_email'], $_SESSION['fp_reset_id']);
        header('Location: forgotpassword.php');
        exit;
    }
}

// Mask email for display e.g. ja***@example.com
$maskedEmail = '';
if (!empty($_SESSION['fp_email'])) {
    [$local, $domain] = explode('@', $_SESSION['fp_email']);
    $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;
}
?>
<?php include '../assets/include/user_topbar.php'; ?>

<style>
  :root {
    --ink:    #0f0f0f;
    --paper:  #faf9f7;
    --accent: #E05A1E;
    --mid:    #6b6560;
    --border: #e2ddd8;
    --green:  #2e7d32;
    --red:    #c0392b;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  /* ── Page centering ── */
  .fp-center {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 64px - 220px);
    padding: 2rem 1rem;
    background: var(--paper);
    position: relative;
    overflow: hidden;
  }

  .fp-center::before {
    content: '';
    position: absolute;
    top: -120px; right: -120px;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(224,90,30,0.08) 0%, transparent 70%);
    pointer-events: none;
  }

  .fp-center::after {
    content: '';
    position: absolute;
    bottom: -100px; left: -100px;
    width: 350px; height: 350px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(224,90,30,0.06) 0%, transparent 70%);
    pointer-events: none;
  }

  .bg-dots {
    position: absolute; inset: 0;
    pointer-events: none; z-index: 0;
    background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
    background-size: 28px 28px;
  }

  /* ── Card ── */
  .fp-wrap { width: 100%; max-width: 420px; position: relative; z-index: 1; }

  .fp-card {
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem 2.25rem;
  }

  /* ── Brand ── */
  .fp-brand {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 2rem;
  }

  .fp-brand-dot {
    width: 30px; height: 30px;
    background: var(--accent); border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
  }

  .fp-brand-dot svg { width: 16px; height: 16px; fill: none; stroke: #fff; stroke-width: 2; }
  .fp-brand-name { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; color: var(--ink); }

  /* ── Step icon ── */
  .fp-icon {
    width: 52px; height: 52px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1.25rem;
  }

  .fp-icon svg { width: 24px; height: 24px; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .fp-icon--orange { background: #fdf0ea; }
  .fp-icon--orange svg { stroke: var(--accent); }
  .fp-icon--green  { background: #e8f5e9; }
  .fp-icon--green  svg { stroke: var(--green); }

  /* ── Typography ── */
  .fp-heading {
    font-family: 'Syne', sans-serif;
    font-size: 22px; font-weight: 700;
    color: var(--ink); letter-spacing: -0.5px;
    margin-bottom: 6px;
  }

  .fp-sub { font-size: 13px; color: var(--mid); line-height: 1.6; margin-bottom: 1.75rem; }
  .fp-sub strong { color: var(--ink); }

  /* ── Progress steps ── */
  .fp-progress {
    display: flex; align-items: center; gap: 0;
    margin-bottom: 2rem;
  }

  .fp-step-dot {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    border: 2px solid var(--border);
    color: var(--mid);
    background: #fff;
    flex-shrink: 0;
    transition: all 0.3s;
  }

  .fp-step-dot.active  { background: var(--accent); border-color: var(--accent); color: #fff; }
  .fp-step-dot.done    { background: var(--green);  border-color: var(--green);  color: #fff; }

  .fp-step-line {
    flex: 1; height: 2px;
    background: var(--border);
    transition: background 0.3s;
  }

  .fp-step-line.done { background: var(--green); }

  /* ── Alerts ── */
  .alert {
    padding: 11px 14px;
    border-radius: 10px;
    font-size: 13px; font-weight: 500;
    margin-bottom: 1.25rem;
    display: flex; align-items: flex-start; gap: 9px;
  }

  .alert svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }
  .alert--error   { background: #fef2f2; color: var(--red);   border: 1px solid #fecaca; }
  .alert--success { background: #e8f5e9; color: var(--green); border: 1px solid #c8e6c9; }

  /* ── Form fields ── */
  .field { margin-bottom: 1rem; }

  .field label {
    display: block;
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: var(--mid); margin-bottom: 6px;
  }

  .field input {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid var(--border); border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px; color: var(--ink);
    background: var(--paper); outline: none;
    transition: border-color 0.2s;
  }

  .field input:focus { border-color: var(--ink); background: #fff; }
  .field input::placeholder { color: #bbb; }
  .field input.input-ok    { border-color: #4caf50 !important; }
  .field input.input-error { border-color: var(--red) !important; }

  .field-hint { font-size: 11px; margin-top: 4px; display: none; }
  .field-hint.ok    { color: var(--green); display: block; }
  .field-hint.error { color: var(--red);   display: block; }

  /* ── OTP input row ── */
  .otp-row {
    display: flex; gap: 10px;
    justify-content: center;
    margin-bottom: 1.5rem;
  }

  .otp-digit {
    width: 48px; height: 56px;
    border: 2px solid var(--border); border-radius: 12px;
    text-align: center;
    font-family: 'Syne', sans-serif;
    font-size: 22px; font-weight: 700;
    color: var(--ink); background: var(--paper);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    caret-color: var(--accent);
  }

  .otp-digit:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(224,90,30,0.12);
    background: #fff;
  }

  .otp-digit.filled { border-color: var(--accent); }

  /* ── Password strength ── */
  .pw-bar-track {
    height: 4px; background: var(--border);
    border-radius: 4px; overflow: hidden;
    margin-top: 8px;
  }

  .pw-bar-fill {
    height: 100%; width: 0%;
    border-radius: 4px;
    transition: width 0.3s, background 0.3s;
  }

  .pw-rules {
    display: flex; flex-wrap: wrap; gap: 5px;
    margin-top: 8px;
  }

  .pw-rule {
    font-size: 10px; padding: 2px 7px;
    border-radius: 20px;
    border: 1px solid var(--border);
    color: #aaa; background: var(--paper);
    transition: all 0.2s;
  }

  .pw-rule.ok { color: var(--green); background: #eaf3de; border-color: #b8dfa0; }

  /* ── Buttons ── */
  .btn-primary {
    width: 100%; padding: 12px;
    background: var(--ink); color: #fff;
    border: none; border-radius: 12px;
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700;
    cursor: pointer; letter-spacing: -0.2px;
    transition: background 0.2s;
    margin-top: 0.25rem;
  }

  .btn-primary:hover { background: var(--accent); }

  .btn-link {
    background: none; border: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; color: var(--accent);
    cursor: pointer; font-weight: 600;
    padding: 0; text-decoration: underline;
  }

  .btn-link:hover { color: #c74d16; }

  .fp-footer {
    text-align: center;
    font-size: 13px; color: var(--mid);
    margin-top: 1.25rem;
  }

  .fp-footer a { color: var(--accent); font-weight: 600; text-decoration: none; }
  .fp-footer a:hover { text-decoration: underline; }

  /* ── Timer ── */
  .otp-timer {
    text-align: center;
    font-size: 12px; color: var(--mid);
    margin-top: 1rem;
  }

  .otp-timer #timer-count { font-weight: 700; color: var(--ink); }
  .otp-timer #timer-count.expired { color: var(--red); }

  /* ── Success final screen ── */
  .success-screen { text-align: center; padding: 1rem 0; }

  .success-circle {
    width: 72px; height: 72px;
    background: #e8f5e9; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.25rem; font-size: 32px;
  }
</style>

<div class="fp-center">
  <div class="bg-dots"></div>
  <div class="fp-wrap">
    <div class="fp-card">

      <!-- Brand -->
      <div class="fp-brand">
        <div class="fp-brand-dot">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
        </div>
        <span class="fp-brand-name">ApplyGo</span>
      </div>

      <?php if ($success === 'done'): ?>
      <!-- ════ Final success screen ════ -->
      <div class="success-screen">
        <div class="success-circle">✓</div>
        <p class="fp-heading">Password reset!</p>
        <p class="fp-sub" style="margin-bottom:1.75rem;">Your password has been updated successfully. You can now sign in with your new password.</p>
        <a href="login.php" style="display:block;width:100%;padding:12px;background:var(--ink);color:#fff;border-radius:12px;font-family:'Syne',sans-serif;font-size:15px;font-weight:700;text-decoration:none;text-align:center;transition:background 0.2s;"
           onmouseover="this.style.background='var(--accent)'" onmouseout="this.style.background='var(--ink)'">
          Sign in now →
        </a>
      </div>

      <?php else: ?>
      <!-- ════ Progress bar ════ -->
      <div class="fp-progress">
        <div class="fp-step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">
          <?= $step > 1 ? '✓' : '1' ?>
        </div>
        <div class="fp-step-line <?= $step > 1 ? 'done' : '' ?>"></div>
        <div class="fp-step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">
          <?= $step > 2 ? '✓' : '2' ?>
        </div>
        <div class="fp-step-line <?= $step > 2 ? 'done' : '' ?>"></div>
        <div class="fp-step-dot <?= $step >= 3 ? 'active' : '' ?>">3</div>
      </div>

      <!-- ── Alerts ── -->
      <?php if ($error): ?>
        <div class="alert alert--error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success === 'resent'): ?>
        <div class="alert alert--success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          A new code has been sent to your email.
        </div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
      <!-- ════ STEP 1: Enter email ════ -->
      <div class="fp-icon fp-icon--orange">
        <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 6 10-6"/></svg>
      </div>
      <p class="fp-heading">Forgot password?</p>
      <p class="fp-sub">Enter your registered email and we'll send you a 6-digit reset code.</p>

      <form method="POST" id="emailForm" novalidate>
        <input type="hidden" name="action" value="send_otp">
        <div class="field">
          <label>Email Address</label>
          <input type="email" name="email" id="emailInput"
                 placeholder="jane@example.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 oninput="liveEmailCheck(this)">
          <span class="field-hint" id="email-hint"></span>
        </div>
        <button class="btn-primary" type="submit">Send reset code →</button>
      </form>

      <?php elseif ($step === 2): ?>
      <!-- ════ STEP 2: Enter OTP ════ -->
      <div class="fp-icon fp-icon--orange">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <p class="fp-heading">Enter reset code</p>
      <p class="fp-sub">We sent a 6-digit code to <strong><?= htmlspecialchars($maskedEmail) ?></strong>. It's valid for 10 minutes.</p>

      <form method="POST" id="otpForm" novalidate>
        <input type="hidden" name="action" value="verify_otp">
        <div class="otp-row">
          <?php foreach (['d1','d2','d3','d4','d5','d6'] as $i => $n): ?>
            <input type="text" name="<?= $n ?>" id="otp<?= $i+1 ?>"
                   class="otp-digit" maxlength="1"
                   inputmode="numeric" pattern="[0-9]"
                   autocomplete="one-time-code"
                   value="<?= htmlspecialchars($_POST[$n] ?? '') ?>">
          <?php endforeach; ?>
        </div>
        <button class="btn-primary" type="submit">Verify code →</button>
      </form>

      <div class="otp-timer">
        Code expires in <span id="timer-count">10:00</span>
      </div>

      <div class="fp-footer" style="margin-top:1rem;">
        Didn't receive it?
        <form method="POST" style="display:inline;">
          <input type="hidden" name="action" value="resend_otp">
          <button type="submit" class="btn-link" id="resendBtn" disabled>Resend code</button>
        </form>
      </div>

      <div class="fp-footer" style="margin-top:0.5rem;">
        <form method="POST" style="display:inline;">
          <input type="hidden" name="action" value="restart">
          <button type="submit" class="btn-link" style="color:var(--mid);text-decoration:none;">← Use a different email</button>
        </form>
      </div>

      <?php elseif ($step === 3): ?>
      <!-- ════ STEP 3: New password ════ -->
      <div class="fp-icon fp-icon--green">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <p class="fp-heading">Set new password</p>
      <p class="fp-sub">Almost there! Choose a strong password for your account.</p>

      <form method="POST" id="pwForm" novalidate>
        <input type="hidden" name="action" value="reset_password">

        <div class="field">
          <label>New Password</label>
          <input type="password" name="new_password" id="newPw"
                 placeholder="Min. 8 chars" required
                 oninput="checkPw(this.value)">
          <div class="pw-bar-track"><div class="pw-bar-fill" id="pw-bar"></div></div>
          <div class="pw-rules">
            <span class="pw-rule" id="r-len">8+ chars</span>
            <span class="pw-rule" id="r-upper">A–Z</span>
            <span class="pw-rule" id="r-lower">a–z</span>
            <span class="pw-rule" id="r-num">0–9</span>
            <span class="pw-rule" id="r-sym">!@#$</span>
          </div>
        </div>

        <div class="field" style="margin-top:1rem;">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" id="confPw"
                 placeholder="Re-enter password" required
                 oninput="checkConf(this.value)">
          <span class="field-hint" id="conf-hint"></span>
        </div>

        <button class="btn-primary" type="submit" style="margin-top:0.5rem;">Reset password →</button>
      </form>
      <?php endif; ?>

      <?php if ($success !== 'done'): ?>
      <div class="fp-footer">
        Remember your password? <a href="login.php">Sign in</a>
      </div>
      <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>
</div>

<script>
/* ── Step 1: live email format check ── */
function liveEmailCheck(input) {
  const hint  = document.getElementById('email-hint');
  const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
  if (!input.value) { input.classList.remove('input-ok','input-error'); hint.className='field-hint'; return; }
  if (valid) {
    input.classList.add('input-ok'); input.classList.remove('input-error');
    hint.className = 'field-hint';
  } else {
    input.classList.add('input-error'); input.classList.remove('input-ok');
    hint.textContent = '✗ Please enter a valid email address.';
    hint.className   = 'field-hint error';
  }
}

document.getElementById('emailForm')?.addEventListener('submit', function(e) {
  const em = document.getElementById('emailInput').value.trim();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
    e.preventDefault();
    alert('Please enter a valid email address.');
  }
});

/* ── Step 2: OTP digit auto-advance ── */
(function() {
  const digits = document.querySelectorAll('.otp-digit');
  if (!digits.length) return;

  digits.forEach((el, i) => {
    el.addEventListener('input', function() {
      // Allow only numeric
      this.value = this.value.replace(/\D/g, '').slice(-1);
      if (this.value) {
        this.classList.add('filled');
        if (i < digits.length - 1) digits[i + 1].focus();
      } else {
        this.classList.remove('filled');
      }
    });

    el.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && !this.value && i > 0) {
        digits[i - 1].focus();
        digits[i - 1].value = '';
        digits[i - 1].classList.remove('filled');
      }
    });

    // Handle paste on first digit
    el.addEventListener('paste', function(e) {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      text.split('').slice(0, 6).forEach((ch, j) => {
        if (digits[j]) {
          digits[j].value = ch;
          digits[j].classList.add('filled');
        }
      });
      const nextEmpty = [...digits].findIndex(d => !d.value);
      if (nextEmpty !== -1) digits[nextEmpty].focus();
      else digits[5].focus();
    });
  });
})();

/* ── Step 2: 10-minute countdown timer ── */
(function() {
  const timerEl  = document.getElementById('timer-count');
  const resendBtn = document.getElementById('resendBtn');
  if (!timerEl) return;

  let seconds = 10 * 60; // 600s

  const tick = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
      clearInterval(tick);
      timerEl.textContent = 'Expired';
      timerEl.classList.add('expired');
      if (resendBtn) resendBtn.disabled = false;
      return;
    }
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    timerEl.textContent = m + ':' + String(s).padStart(2, '0');

    // Enable resend after 60s
    if (seconds <= (9 * 60) && resendBtn) resendBtn.disabled = false;
  }, 1000);
})();

/* ── Step 3: password strength ── */
const pwRules = {
  'r-len':   v => v.length >= 8,
  'r-upper': v => /[A-Z]/.test(v),
  'r-lower': v => /[a-z]/.test(v),
  'r-num':   v => /[0-9]/.test(v),
  'r-sym':   v => /[\W_]/.test(v),
};
const barColors = ['#e05252','#e07d1e','#e0c01e','#7db83a','#3a8a3a'];

function checkPw(val) {
  let passed = 0;
  for (const [id, fn] of Object.entries(pwRules)) {
    const el = document.getElementById(id);
    if (!el) continue;
    fn(val) ? (el.classList.add('ok'), passed++) : el.classList.remove('ok');
  }
  const bar = document.getElementById('pw-bar');
  if (bar) { bar.style.width = (passed / 5 * 100) + '%'; bar.style.background = passed > 0 ? barColors[passed-1] : '#e2ddd8'; }
  const c = document.getElementById('confPw');
  if (c?.value) checkConf(c.value);
}

function checkConf(val) {
  const pw   = document.getElementById('newPw')?.value;
  const hint = document.getElementById('conf-hint');
  const inp  = document.getElementById('confPw');
  if (!hint || !inp) return;
  if (!val) { inp.classList.remove('input-ok','input-error'); hint.className='field-hint'; return; }
  if (val === pw) {
    inp.classList.add('input-ok'); inp.classList.remove('input-error');
    hint.textContent = '✓ Passwords match'; hint.className = 'field-hint ok';
  } else {
    inp.classList.add('input-error'); inp.classList.remove('input-ok');
    hint.textContent = '✗ Passwords do not match'; hint.className = 'field-hint error';
  }
}

document.getElementById('pwForm')?.addEventListener('submit', function(e) {
  const pw  = document.getElementById('newPw').value;
  const cfg = document.getElementById('confPw').value;
  const allPassed = Object.values(pwRules).every(fn => fn(pw));
  if (!allPassed) {
    e.preventDefault();
    alert('Please meet all password requirements before submitting.');
    return;
  }
  if (pw !== cfg) {
    e.preventDefault();
    alert('Passwords do not match.');
  }
});
</script>

<?php include '../assets/include/user_footer.php'; ?>