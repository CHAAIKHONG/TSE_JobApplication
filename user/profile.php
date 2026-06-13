<?php
session_start();

$activePage = 'profile';
$pageTitle  = 'My Profile — ApplyGo';

require_once '../database/db.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$success  = '';
$errors   = [];

// ── Handle form submissions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ── Update basic info ────────────────────────────────────────────────────
    if ($action === 'update_info') {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $phoneNo = trim($_POST['phoneNo'] ?? '');

        if ($name === '')  $errors[] = 'Name cannot be empty.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

        if (empty($errors)) {
            $stmt = $conn->prepare('UPDATE users SET name=?, email=?, phoneNo=? WHERE user_id=?');
            $stmt->bind_param('sssi', $name, $email, $phoneNo, $userId);
            $stmt->execute();
            $stmt->close();
            $success = 'Profile updated successfully.';
        }
    }

    // ── Change password ──────────────────────────────────────────────────────
    if ($action === 'change_password') {
        $oldPass  = $_POST['old_password']      ?? '';
        $newPass  = $_POST['new_password']      ?? '';
        $confPass = $_POST['confirm_password']  ?? '';

        if ($oldPass === '')  $errors[] = 'Please enter your current password.';
        if (strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';
        if ($newPass !== $confPass) $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            // Verify old password
            $stmt = $conn->prepare('SELECT password FROM users WHERE user_id=? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || !password_verify($oldPass, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('UPDATE users SET password=? WHERE user_id=?');
                $stmt->bind_param('si', $hashed, $userId);
                $stmt->execute();
                $stmt->close();
                $success = 'Password changed successfully.';
            }
        }
    }

    // ── Upload resume ────────────────────────────────────────────────────────
    if ($action === 'upload_resume') {
        $file = $_FILES['resume'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'No file uploaded or upload error.';
        } elseif ($file['type'] !== 'application/pdf' && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            $errors[] = 'Only PDF files are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'File size must be under 5MB.';
        } else {
            $uploadDir = '../uploads/resumes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = 'resume_' . bin2hex(random_bytes(8)) . '.pdf';
            $dest     = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $dbPath = 'uploads/resumes/' . $filename;
                $stmt = $conn->prepare('UPDATE users SET resume=? WHERE user_id=?');
                $stmt->bind_param('si', $dbPath, $userId);
                $stmt->execute();
                $stmt->close();
                $success = 'Resume uploaded successfully.';
            } else {
                $errors[] = 'Failed to save file. Check folder permissions.';
            }
        }
    }
}

// ── Fetch current user ───────────────────────────────────────────────────────
$stmt = $conn->prepare('SELECT user_id, name, email, phoneNo, resume, created_at FROM users WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// For user_topbar.php
$nameParts   = explode(' ', trim($user['name']));
$initials    = strtoupper(
    (isset($nameParts[0]) ? $nameParts[0][0] : '') .
    (isset($nameParts[1]) ? $nameParts[1][0] : '')
);
$currentUser = [
    'name'        => $user['name'],
    'initials'    => $initials,
    'notif_count' => 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink:     #0f0f0f;
      --paper:   #faf9f7;
      --surface: #f2efea;
      --accent:  #e85d26;
      --mid:     #6b6560;
      --border:  #e2ddd8;
      --radius:  12px;
      --green:   #2e7d32;
      --red:     #c0392b;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      background: var(--paper);
      color: var(--ink);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Page layout ── */
    .page-main {
      flex: 1;
      max-width: 860px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 32px 64px;
    }

    /* ── Page header ── */
    .profile-header {
      margin-bottom: 36px;
      padding-bottom: 28px;
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .profile-header__avatar {
      width: 64px; height: 64px;
      border-radius: 50%;
      background: var(--accent);
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-size: 22px; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      letter-spacing: -0.5px;
    }

    .profile-header__text h1 {
      font-family: 'Syne', sans-serif;
      font-size: 26px; font-weight: 800;
      letter-spacing: -1px; line-height: 1.1;
    }

    .profile-header__text p {
      font-size: 13px; color: var(--mid); margin-top: 4px;
    }

    /* ── Alert banners ── */
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13px; font-weight: 500;
      margin-bottom: 24px;
      display: flex; align-items: center; gap: 10px;
    }

    .alert--success { background: #e8f5e9; color: var(--green); border: 1px solid #c8e6c9; }
    .alert--error   { background: #fef2f2; color: var(--red);   border: 1px solid #fecaca; }

    /* ── Cards ── */
    .profile-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 20px;
      overflow: hidden;
    }

    .profile-card__header {
      padding: 18px 24px;
      border-bottom: 1.5px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }

    .profile-card__icon {
      width: 32px; height: 32px;
      background: var(--surface);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .profile-card__icon svg {
      width: 15px; height: 15px;
      stroke: var(--ink); fill: none; stroke-width: 2;
    }

    .profile-card__title {
      font-family: 'Syne', sans-serif;
      font-size: 15px; font-weight: 700;
      letter-spacing: -0.3px;
    }

    .profile-card__body { padding: 24px; }

    /* ── Form fields ── */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-grid--full { grid-template-columns: 1fr; }

    .field { display: flex; flex-direction: column; gap: 6px; }

    .field label {
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 1px;
      color: var(--mid);
    }

    .field input {
      padding: 10px 14px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 14px; font-family: 'DM Sans', sans-serif;
      color: var(--ink); background: var(--paper);
      outline: none; transition: border-color 0.2s;
    }

    .field input:focus { border-color: var(--ink); background: #fff; }
    .field input::placeholder { color: var(--mid); }

    .form-actions {
      margin-top: 20px;
      display: flex; justify-content: flex-end;
    }

    /* ── Buttons ── */
    .btn {
      padding: 10px 22px;
      border: none; border-radius: 8px;
      font-family: 'Syne', sans-serif;
      font-size: 13px; font-weight: 700; letter-spacing: 0.3px;
      cursor: pointer; transition: background 0.2s, transform 0.15s;
      display: inline-flex; align-items: center; gap: 7px;
    }

    .btn:hover { transform: translateY(-1px); }
    .btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; }

    .btn--primary { background: var(--ink); color: #fff; }
    .btn--primary:hover { background: var(--accent); }

    .btn--outline {
      background: transparent;
      border: 1.5px solid var(--border);
      color: var(--mid);
    }
    .btn--outline:hover { border-color: var(--ink); color: var(--ink); }

    /* ── Resume section ── */
    .resume-current {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 16px;
      background: var(--surface); border-radius: 8px;
      margin-bottom: 16px;
    }

    .resume-current svg {
      width: 20px; height: 20px;
      stroke: var(--accent); fill: none; stroke-width: 1.8;
      flex-shrink: 0;
    }

    .resume-current__name {
      font-size: 13px; font-weight: 500; color: var(--ink); flex: 1;
      word-break: break-all;
    }

    .resume-current__link {
      font-size: 12px; font-weight: 600;
      color: var(--accent); text-decoration: none;
      white-space: nowrap;
    }

    .resume-current__link:hover { text-decoration: underline; }

    .resume-none {
      font-size: 13px; color: var(--mid);
      padding: 12px 0; margin-bottom: 12px;
    }

    /* File upload drop zone */
    .upload-zone {
      border: 2px dashed var(--border);
      border-radius: 10px;
      padding: 28px 24px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      position: relative;
    }

    .upload-zone:hover,
    .upload-zone.drag-over { border-color: var(--accent); background: var(--accent-lt, #fdf0ea); }

    .upload-zone input[type="file"] {
      position: absolute; inset: 0;
      opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }

    .upload-zone__icon { margin-bottom: 10px; }
    .upload-zone__icon svg {
      width: 32px; height: 32px;
      stroke: var(--mid); fill: none; stroke-width: 1.5;
    }

    .upload-zone__label {
      font-size: 14px; font-weight: 600; color: var(--ink);
      margin-bottom: 4px;
    }

    .upload-zone__hint { font-size: 12px; color: var(--mid); }

    #file-chosen {
      margin-top: 10px; font-size: 13px;
      color: var(--accent); font-weight: 500;
      min-height: 18px;
    }

    /* ── Member since ── */
    .member-since {
      font-size: 12px; color: var(--mid);
      text-align: center; margin-top: 32px;
    }

    /* ── Responsive ── */
    @media (max-width: 640px) {
      .page-main { padding: 24px 16px 48px; }
      .form-grid { grid-template-columns: 1fr; }
      .profile-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <!-- Page header -->
    <div class="profile-header">
      <div class="profile-header__avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="profile-header__text">
        <h1><?= htmlspecialchars($user['name']) ?></h1>
        <p><?= htmlspecialchars($user['email']) ?> &nbsp;·&nbsp; Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
      </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
      <div class="alert alert--success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars(implode(' ', $errors)) ?>
      </div>
    <?php endif; ?>

    <!-- ── Card 1: Basic Info ── -->
    <div class="profile-card">
      <div class="profile-card__header">
        <div class="profile-card__icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        </div>
        <span class="profile-card__title">Personal Information</span>
      </div>
      <div class="profile-card__body">
        <form method="POST">
          <input type="hidden" name="action" value="update_info">
          <div class="form-grid">
            <div class="field">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name"
                     value="<?= htmlspecialchars($user['name']) ?>"
                     placeholder="Your full name" required>
            </div>
            <div class="field">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email"
                     value="<?= htmlspecialchars($user['email']) ?>"
                     placeholder="you@example.com" required>
            </div>
            <div class="field">
              <label for="phoneNo">Phone Number</label>
              <input type="tel" id="phoneNo" name="phoneNo"
                     value="<?= htmlspecialchars($user['phoneNo'] ?? '') ?>"
                     placeholder="e.g. 0123456789">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Card 2: Change Password ── -->
    <div class="profile-card">
      <div class="profile-card__header">
        <div class="profile-card__icon">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <span class="profile-card__title">Change Password</span>
      </div>
      <div class="profile-card__body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-grid">
            <div class="field">
              <label for="old_password">Current Password</label>
              <input type="password" id="old_password" name="old_password"
                     placeholder="Enter current password" required>
            </div>
            <div class="field">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password"
                     placeholder="Min. 6 characters" required>
            </div>
            <div class="field">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password"
                     placeholder="Repeat new password" required>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Update Password
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Card 3: Resume ── -->
    <div class="profile-card">
      <div class="profile-card__header">
        <div class="profile-card__icon">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <span class="profile-card__title">Resume / CV</span>
      </div>
      <div class="profile-card__body">

        <!-- Current resume -->
        <?php if (!empty($user['resume'])): ?>
          <div class="resume-current">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="resume-current__name"><?= htmlspecialchars(basename($user['resume'])) ?></span>
            <a href="../<?= htmlspecialchars($user['resume']) ?>" target="_blank" class="resume-current__link">View PDF ↗</a>
          </div>
        <?php else: ?>
          <p class="resume-none">No resume uploaded yet.</p>
        <?php endif; ?>

        <!-- Upload form -->
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_resume">
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="resume" id="resumeFile" accept=".pdf,application/pdf"
                   onchange="updateFileName(this)">
            <div class="upload-zone__icon">
              <svg viewBox="0 0 24 24"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            </div>
            <p class="upload-zone__label">Click or drag & drop your resume</p>
            <p class="upload-zone__hint">PDF only · Max 5MB</p>
            <p id="file-chosen"></p>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
              Upload Resume
            </button>
          </div>
        </form>
      </div>
    </div>

    <p class="member-since">Member since <?= date('F j, Y', strtotime($user['created_at'])) ?></p>

  </main>

  <?php include '../assets/include/user_footer.php'; ?>

  <script>
    // Show chosen filename
    function updateFileName(input) {
      const label = document.getElementById('file-chosen');
      label.textContent = input.files.length ? '📎 ' + input.files[0].name : '';
    }

    // Drag & drop highlight
    const zone = document.getElementById('uploadZone');
    ['dragenter', 'dragover'].forEach(e => zone.addEventListener(e, () => zone.classList.add('drag-over')));
    ['dragleave', 'drop'].forEach(e => zone.addEventListener(e, () => zone.classList.remove('drag-over')));
  </script>

</body>
</html>