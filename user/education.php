<?php
session_start();

$activePage = 'education';
$pageTitle  = 'My Education — ApplyGo';

require_once '../database/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$success = '';
$errors  = [];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add ──────────────────────────────────────────────────────────────────
    if ($action === 'add') {
        $institution   = trim($_POST['institution_name'] ?? '');
        $qualification = trim($_POST['qualification']    ?? '');
        $field         = trim($_POST['field_of_study']   ?? '');
        $description   = trim($_POST['description']      ?? '') ?: null;
        $cgpa          = trim($_POST['cgpa']             ?? '') ?: null;
        $startDate     = trim($_POST['start_date']       ?? '');
        $endDate       = trim($_POST['end_date']         ?? '') ?: null;

        if ($institution   === '') $errors[] = 'Institution name is required.';
        if ($qualification === '') $errors[] = 'Qualification is required.';
        if ($startDate     === '') $errors[] = 'Start date is required.';
        if ($cgpa !== null && (!is_numeric($cgpa) || $cgpa < 0 || $cgpa > 4)) {
            $errors[] = 'CGPA must be a number between 0.00 and 4.00.';
        }

        if (empty($errors)) {
            $stmt = $conn->prepare(
                'INSERT INTO education
                    (user_id, institution_name, qualification, field_of_study, description, cgpa, start_date, end_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('issssdss', $userId, $institution, $qualification, $field, $description, $cgpa, $startDate, $endDate);
            $stmt->execute();
            $stmt->close();
            $success = 'Education record added.';
        }
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $eduId = (int)($_POST['education_id'] ?? 0);
        if ($eduId > 0) {
            $stmt = $conn->prepare('DELETE FROM education WHERE education_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $eduId, $userId);
            $stmt->execute();
            $stmt->close();
            $success = 'Record deleted.';
        }
    }

    // ── Edit ─────────────────────────────────────────────────────────────────
    if ($action === 'edit') {
        $eduId         = (int)($_POST['education_id']    ?? 0);
        $institution   = trim($_POST['institution_name'] ?? '');
        $qualification = trim($_POST['qualification']    ?? '');
        $field         = trim($_POST['field_of_study']   ?? '');
        $description   = trim($_POST['description']      ?? '') ?: null;
        $cgpa          = trim($_POST['cgpa']             ?? '') ?: null;
        $startDate     = trim($_POST['start_date']       ?? '');
        $endDate       = trim($_POST['end_date']         ?? '') ?: null;

        if ($institution   === '') $errors[] = 'Institution name is required.';
        if ($qualification === '') $errors[] = 'Qualification is required.';
        if ($startDate     === '') $errors[] = 'Start date is required.';
        if ($cgpa !== null && (!is_numeric($cgpa) || $cgpa < 0 || $cgpa > 4)) {
            $errors[] = 'CGPA must be a number between 0.00 and 4.00.';
        }

        if (empty($errors) && $eduId > 0) {
            $stmt = $conn->prepare(
                'UPDATE education
                 SET institution_name=?, qualification=?, field_of_study=?, description=?, cgpa=?, start_date=?, end_date=?
                 WHERE education_id=? AND user_id=?'
            );
            $stmt->bind_param('ssssdssii', $institution, $qualification, $field, $description, $cgpa, $startDate, $endDate, $eduId, $userId);
            $stmt->execute();
            $stmt->close();
            $success = 'Record updated.';
        }
    }
}

// ── Fetch education records ───────────────────────────────────────────────────
$result = $conn->query(
    "SELECT education_id, institution_name, qualification, field_of_study, description, cgpa, start_date, end_date
     FROM education
     WHERE user_id = $userId
     ORDER BY start_date DESC"
);
$records = $result->fetch_all(MYSQLI_ASSOC);

// ── For topbar ────────────────────────────────────────────────────────────────
$stmt = $conn->prepare('SELECT user_id, name, email FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbUser) { session_destroy(); header('Location: login.php'); exit; }

$nameParts   = explode(' ', trim($dbUser['name']));
$initials    = strtoupper(
    (isset($nameParts[0]) ? $nameParts[0][0] : '') .
    (isset($nameParts[1]) ? $nameParts[1][0] : '')
);
$currentUser = ['name' => $dbUser['name'], 'initials' => $initials, 'notif_count' => 0];

// Helper: format date range
function formatDateRange($start, $end): string {
    $s = $start ? date('M Y', strtotime($start)) : '';
    $e = $end   ? date('M Y', strtotime($end))   : 'Present';
    return $s && $e ? "$s — $e" : $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
      background: var(--paper); color: var(--ink);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh; display: flex; flex-direction: column;
    }

    .page-main {
      flex: 1; max-width: 860px; width: 100%;
      margin: 0 auto; padding: 40px 32px 64px;
    }

    /* ── Page header ── */
    .page-header {
      margin-bottom: 36px; padding-bottom: 28px;
      border-bottom: 1.5px solid var(--border);
    }

    .page-header__eyebrow {
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 2px;
      color: var(--accent); margin-bottom: 8px;
    }

    .page-header__title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(26px, 4vw, 38px);
      font-weight: 800; line-height: 1.1; letter-spacing: -1.5px;
    }

    .page-header__title em { font-style: italic; font-weight: 400; color: var(--mid); }

    /* ── Alert ── */
    .alert {
      padding: 12px 16px; border-radius: 8px;
      font-size: 13px; font-weight: 500;
      margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
    }

    .alert--success { background: #e8f5e9; color: var(--green); border: 1px solid #c8e6c9; }
    .alert--error   { background: #fef2f2; color: var(--red);   border: 1px solid #fecaca; }

    /* ── Add form card ── */
    .form-card {
      background: #fff; border: 1.5px solid var(--border);
      border-radius: var(--radius); margin-bottom: 28px; overflow: hidden;
    }

    .form-card__header {
      padding: 16px 24px; border-bottom: 1.5px solid var(--border);
      display: flex; align-items: center; gap: 10px;
      cursor: pointer; user-select: none;
      transition: background 0.15s;
    }

    .form-card__header:hover { background: var(--surface); }

    .form-card__icon {
      width: 32px; height: 32px; background: var(--surface); border-radius: 8px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    .form-card__icon svg { width: 15px; height: 15px; stroke: var(--ink); fill: none; stroke-width: 2; }

    .form-card__title {
      font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
      letter-spacing: -0.3px; flex: 1;
    }

    .form-card__chevron {
      width: 16px; height: 16px; stroke: var(--mid); fill: none; stroke-width: 2;
      transition: transform 0.25s;
    }

    .form-card.open .form-card__chevron { transform: rotate(180deg); }

    .form-card__body {
      padding: 24px; display: none;
    }

    .form-card.open .form-card__body { display: block; }

    /* ── Form grid ── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .field { display: flex; flex-direction: column; gap: 6px; }

    .field--full { grid-column: 1 / -1; }

    .field label {
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 1px; color: var(--mid);
    }

    .field input, .field select, .field textarea {
      padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 14px; font-family: 'DM Sans', sans-serif;
      color: var(--ink); background: var(--paper);
      outline: none; transition: border-color 0.2s;
    }

    .field textarea {
      resize: vertical; min-height: 80px; line-height: 1.5;
    }

    .field input:focus, .field select:focus, .field textarea:focus {
      border-color: var(--ink); background: #fff;
    }
    .field input::placeholder, .field textarea::placeholder { color: var(--mid); }

    .field__hint { font-size: 11px; color: var(--mid); margin-top: 2px; }

    .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; }

    /* ── Buttons ── */
    .btn {
      padding: 10px 22px; border: none; border-radius: 8px;
      font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
      cursor: pointer; transition: background 0.2s, transform 0.15s;
      display: inline-flex; align-items: center; gap: 7px;
    }

    .btn:hover { transform: translateY(-1px); }
    .btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; }

    .btn--primary { background: var(--ink); color: #fff; }
    .btn--primary:hover { background: var(--accent); }

    .btn--danger { background: #fef2f2; color: var(--red); border: 1.5px solid #fecaca; }
    .btn--danger:hover { background: #fee2e2; }

    .btn--ghost {
      background: transparent; color: var(--mid);
      border: 1.5px solid var(--border); padding: 7px 14px; font-size: 12px;
    }

    .btn--ghost:hover { border-color: var(--ink); color: var(--ink); }

    /* ── Timeline ── */
    .timeline { position: relative; }

    .timeline::before {
      content: ''; position: absolute;
      left: 19px; top: 0; bottom: 0;
      width: 2px; background: var(--border);
    }

    .timeline-item {
      position: relative; padding-left: 52px; margin-bottom: 16px;
      animation: fadeUp 0.35s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .timeline-item__dot {
      position: absolute; left: 11px; top: 20px;
      width: 18px; height: 18px; border-radius: 50%;
      background: var(--accent); border: 3px solid var(--paper);
      box-shadow: 0 0 0 2px var(--accent);
    }

    .timeline-item__card {
      background: #fff; border: 1.5px solid var(--border);
      border-radius: var(--radius); padding: 20px 22px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .timeline-item__card:hover {
      border-color: var(--ink);
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .timeline-item__top {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 12px; margin-bottom: 6px;
    }

    .timeline-item__institution {
      font-family: 'Syne', sans-serif;
      font-size: 16px; font-weight: 700; letter-spacing: -0.4px;
    }

    .timeline-item__actions { display: flex; gap: 6px; flex-shrink: 0; }

    .timeline-item__degree {
      font-size: 14px; font-weight: 500; color: var(--ink); margin-bottom: 4px;
    }

    .timeline-item__field {
      font-size: 13px; color: var(--mid); margin-bottom: 6px;
    }

    .timeline-item__description {
      font-size: 13px; color: var(--mid); line-height: 1.55;
      margin-bottom: 8px;
    }

    .timeline-item__meta {
      display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
      margin-top: 8px;
    }

    .timeline-item__date {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 12px; color: var(--mid);
      background: var(--surface); padding: 3px 10px; border-radius: 100px;
    }

    .timeline-item__date svg {
      width: 11px; height: 11px; stroke: var(--mid); fill: none; stroke-width: 2;
    }

    .badge-cgpa {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 12px; font-weight: 600;
      background: #fff8e1; color: #b45309;
      border: 1px solid #fde68a;
      padding: 3px 10px; border-radius: 100px;
    }

    .badge-cgpa svg {
      width: 11px; height: 11px; stroke: #b45309; fill: none; stroke-width: 2;
    }

    /* Present badge */
    .badge-present {
      display: inline-block;
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.5px; padding: 2px 8px; border-radius: 100px;
      background: #e8f5e9; color: var(--green); margin-left: 6px;
    }

    /* ── Edit modal ── */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.4);
      z-index: 500; display: none;
      align-items: center; justify-content: center; padding: 20px;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: #fff; border-radius: var(--radius);
      width: 100%; max-width: 600px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      animation: modalIn 0.25s ease;
      max-height: 90vh; overflow-y: auto;
    }

    @keyframes modalIn {
      from { opacity: 0; transform: scale(0.96) translateY(10px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal__header {
      padding: 18px 24px; border-bottom: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; background: #fff; z-index: 1;
    }

    .modal__title {
      font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700;
    }

    .modal__close {
      width: 32px; height: 32px; border: none; background: var(--surface);
      border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.15s;
    }

    .modal__close:hover { background: var(--border); }
    .modal__close svg { width: 14px; height: 14px; stroke: var(--ink); fill: none; stroke-width: 2; }

    .modal__body { padding: 24px; }
    .modal__footer {
      padding: 16px 24px; border-top: 1.5px solid var(--border);
      display: flex; justify-content: flex-end; gap: 10px;
      position: sticky; bottom: 0; background: #fff;
    }

    /* ── Empty state ── */
    .empty-state {
      text-align: center; padding: 64px 32px; color: var(--mid);
    }

    .empty-state__icon {
      width: 60px; height: 60px; background: var(--surface); border-radius: 50%;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;
    }

    .empty-state__icon svg { width: 26px; height: 26px; stroke: var(--mid); fill: none; stroke-width: 1.5; }

    .empty-state h3 {
      font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700;
      color: var(--ink); margin-bottom: 6px;
    }

    @media (max-width: 640px) {
      .page-main { padding: 24px 16px 48px; }
      .form-grid { grid-template-columns: 1fr; }
      .timeline::before { left: 15px; }
      .timeline-item { padding-left: 42px; }
      .timeline-item__dot { left: 7px; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <!-- Header -->
    <div class="page-header">
      <p class="page-header__eyebrow">Academic background</p>
      <h1 class="page-header__title">My <em>Education</em></h1>
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

    <!-- ── Add Education Form ── -->
    <div class="form-card" id="addCard">
      <div class="form-card__header" onclick="toggleAddForm()">
        <div class="form-card__icon">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <span class="form-card__title">Add Education</span>
        <svg class="form-card__chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="form-card__body">
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-grid">

            <div class="field field--full">
              <label for="institution_name">Institution Name *</label>
              <input type="text" id="institution_name" name="institution_name"
                     placeholder="e.g. University of Malaya" required>
            </div>

            <div class="field">
              <label for="qualification">Qualification *</label>
              <input type="text" id="qualification" name="qualification"
                     placeholder="e.g. Bachelor's Degree / Diploma" required>
            </div>

            <div class="field">
              <label for="field_of_study">Field of Study</label>
              <input type="text" id="field_of_study" name="field_of_study"
                     placeholder="e.g. Computer Science">
            </div>

            <div class="field">
              <label for="start_date">Start Date *</label>
              <input type="date" id="start_date" name="start_date" required>
            </div>

            <div class="field">
              <label for="end_date">End Date</label>
              <input type="date" id="end_date" name="end_date">
              <span class="field__hint">Leave blank if currently studying</span>
            </div>

            <div class="field">
              <label for="cgpa">CGPA</label>
              <input type="number" id="cgpa" name="cgpa"
                     placeholder="e.g. 3.75" min="0" max="4" step="0.01">
              <span class="field__hint">Out of 4.00 — leave blank if not applicable</span>
            </div>

            <div class="field field--full">
              <label for="description">Description</label>
              <textarea id="description" name="description"
                        placeholder="e.g. Relevant coursework, achievements, activities…"></textarea>
              <span class="field__hint">Optional — briefly describe your studies or highlights</span>
            </div>

          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn--primary">
              <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Record
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Timeline ── -->
    <?php if (empty($records)): ?>
      <div class="empty-state">
        <div class="empty-state__icon">
          <svg viewBox="0 0 24 24">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <h3>No education records yet</h3>
        <p>Add your academic background to strengthen your profile.</p>
      </div>

    <?php else: ?>
      <div class="timeline">
        <?php foreach ($records as $i => $rec):
          $isPresent = empty($rec['end_date']);
        ?>
          <div class="timeline-item" style="animation-delay: <?= $i * 0.07 ?>s">
            <div class="timeline-item__dot"></div>
            <div class="timeline-item__card">

              <div class="timeline-item__top">
                <div class="timeline-item__institution">
                  <?= htmlspecialchars($rec['institution_name']) ?>
                  <?php if ($isPresent): ?>
                    <span class="badge-present">Current</span>
                  <?php endif; ?>
                </div>
                <div class="timeline-item__actions">
                  <!-- Edit btn -->
                  <button class="btn btn--ghost"
                    onclick="openEdit(
                      <?= (int)$rec['education_id'] ?>,
                      <?= htmlspecialchars(json_encode($rec['institution_name'])) ?>,
                      <?= htmlspecialchars(json_encode($rec['qualification'])) ?>,
                      <?= htmlspecialchars(json_encode($rec['field_of_study'] ?? '')) ?>,
                      <?= htmlspecialchars(json_encode($rec['description'] ?? '')) ?>,
                      <?= htmlspecialchars(json_encode($rec['cgpa'] ?? '')) ?>,
                      <?= htmlspecialchars(json_encode($rec['start_date'] ?? '')) ?>,
                      <?= htmlspecialchars(json_encode($rec['end_date'] ?? '')) ?>
                    )">
                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" fill="none" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    Edit
                  </button>
                  <!-- Delete btn -->
                  <form method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this record?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="education_id" value="<?= (int)$rec['education_id'] ?>">
                    <button type="submit" class="btn btn--danger">
                      <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" fill="none" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4h6v2"/>
                      </svg>
                      Delete
                    </button>
                  </form>
                </div>
              </div>

              <p class="timeline-item__degree"><?= htmlspecialchars($rec['qualification']) ?></p>

              <?php if (!empty($rec['field_of_study'])): ?>
                <p class="timeline-item__field"><?= htmlspecialchars($rec['field_of_study']) ?></p>
              <?php endif; ?>

              <?php if (!empty($rec['description'])): ?>
                <p class="timeline-item__description"><?= nl2br(htmlspecialchars($rec['description'])) ?></p>
              <?php endif; ?>

              <div class="timeline-item__meta">
                <span class="timeline-item__date">
                  <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <?= formatDateRange($rec['start_date'], $rec['end_date']) ?>
                </span>

                <?php if (!empty($rec['cgpa'])): ?>
                  <span class="badge-cgpa">
                    <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    CGPA <?= htmlspecialchars(number_format((float)$rec['cgpa'], 2)) ?>
                  </span>
                <?php endif; ?>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

  <?php include '../assets/include/user_footer.php'; ?>

  <!-- ── Edit Modal ── -->
  <div class="modal-overlay" id="editModal" onclick="closeEditOnOverlay(event)">
    <div class="modal">
      <div class="modal__header">
        <span class="modal__title">Edit Education</span>
        <button class="modal__close" onclick="closeEdit()">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="education_id" id="edit_id">
        <div class="modal__body">
          <div class="form-grid">
            <div class="field field--full">
              <label for="edit_institution">Institution Name *</label>
              <input type="text" id="edit_institution" name="institution_name" required>
            </div>
            <div class="field">
              <label for="edit_qualification">Qualification *</label>
              <input type="text" id="edit_qualification" name="qualification" required>
            </div>
            <div class="field">
              <label for="edit_field">Field of Study</label>
              <input type="text" id="edit_field" name="field_of_study">
            </div>
            <div class="field">
              <label for="edit_start">Start Date *</label>
              <input type="date" id="edit_start" name="start_date" required>
            </div>
            <div class="field">
              <label for="edit_end">End Date</label>
              <input type="date" id="edit_end" name="end_date">
              <span class="field__hint">Leave blank if currently studying</span>
            </div>
            <div class="field">
              <label for="edit_cgpa">CGPA</label>
              <input type="number" id="edit_cgpa" name="cgpa"
                     placeholder="e.g. 3.75" min="0" max="4" step="0.01">
              <span class="field__hint">Out of 4.00 — leave blank if not applicable</span>
            </div>
            <div class="field field--full">
              <label for="edit_description">Description</label>
              <textarea id="edit_description" name="description"
                        placeholder="e.g. Relevant coursework, achievements, activities…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal__footer">
          <button type="button" class="btn btn--ghost" onclick="closeEdit()">Cancel</button>
          <button type="submit" class="btn btn--primary">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Toggle add form
    function toggleAddForm() {
      const card = document.getElementById('addCard');
      card.classList.toggle('open');
    }

    // Auto-open if there are errors (form was submitted)
    <?php if (!empty($errors)): ?>
    document.getElementById('addCard').classList.add('open');
    <?php endif; ?>

    // Edit modal — now accepts description and cgpa too
    function openEdit(id, institution, qualification, field, description, cgpa, start, end) {
      document.getElementById('edit_id').value            = id;
      document.getElementById('edit_institution').value   = institution;
      document.getElementById('edit_qualification').value = qualification;
      document.getElementById('edit_field').value         = field;
      document.getElementById('edit_description').value   = description;
      document.getElementById('edit_cgpa').value          = cgpa;
      document.getElementById('edit_start').value         = start;
      document.getElementById('edit_end').value           = end;
      document.getElementById('editModal').classList.add('open');
    }

    function closeEdit() {
      document.getElementById('editModal').classList.remove('open');
    }

    function closeEditOnOverlay(e) {
      if (e.target === document.getElementById('editModal')) closeEdit();
    }

    // ESC key closes modal
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeEdit();
    });
  </script>

</body>
</html>