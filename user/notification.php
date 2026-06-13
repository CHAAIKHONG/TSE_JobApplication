<?php
session_start();

$activePage = 'notifications';
$pageTitle  = 'Notifications — ApplyGo';

require_once '../database/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// ── Mark all as read when page is opened ─────────────────────────────────────
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $userId AND is_read = 0");

// ── Fetch all notifications for this user ────────────────────────────────────
$result = $conn->query(
    "SELECT notification_id, message, is_read, created_at
     FROM notifications
     WHERE user_id = $userId
     ORDER BY created_at DESC"
);
$notifications = $result->fetch_all(MYSQLI_ASSOC);

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
      --blue:    #1565c0;
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

    .page-main {
      flex: 1;
      max-width: 720px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 32px 64px;
    }

    /* ── Header ── */
    .notif-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
    }

    .notif-header h1 {
      font-family: 'Syne', sans-serif;
      font-size: 26px; font-weight: 800;
      letter-spacing: -1px;
    }

    .notif-header__count {
      font-size: 13px; color: var(--mid);
    }

    /* ── Empty state ── */
    .notif-empty {
      text-align: center;
      padding: 80px 32px;
      color: var(--mid);
    }

    .notif-empty__icon {
      width: 56px; height: 56px;
      background: var(--surface);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }

    .notif-empty__icon svg {
      width: 24px; height: 24px;
      stroke: var(--mid); fill: none; stroke-width: 1.8;
    }

    .notif-empty h3 {
      font-family: 'Syne', sans-serif;
      font-size: 18px; font-weight: 700;
      color: var(--ink); margin-bottom: 6px;
    }

    .notif-empty p { font-size: 14px; }

    /* ── Notification list ── */
    .notif-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .notif-item {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 20px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
      transition: border-color 0.2s;
      position: relative;
    }

    /* Unread left bar */
    .notif-item.unread {
      border-left: 3px solid var(--accent);
    }

    .notif-item.unread::after {
      content: '';
      position: absolute;
      top: 16px; right: 16px;
      width: 7px; height: 7px;
      background: var(--accent);
      border-radius: 50%;
    }

    /* Icon dot */
    .notif-item__icon {
      width: 36px; height: 36px;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }

    .notif-item__icon svg {
      width: 16px; height: 16px;
      fill: none; stroke-width: 2;
    }

    /* Icon colours by keyword */
    .notif-item__icon--accepted { background: #e8f5e9; }
    .notif-item__icon--accepted svg { stroke: var(--green); }

    .notif-item__icon--rejected { background: #fef2f2; }
    .notif-item__icon--rejected svg { stroke: var(--red); }

    .notif-item__icon--pending  { background: #e3f2fd; }
    .notif-item__icon--pending  svg { stroke: var(--blue); }

    .notif-item__icon--default  { background: var(--surface); }
    .notif-item__icon--default  svg { stroke: var(--mid); }

    .notif-item__body { flex: 1; min-width: 0; }

    .notif-item__message {
      font-size: 14px;
      line-height: 1.5;
      color: var(--ink);
      margin-bottom: 5px;
    }

    .notif-item.unread .notif-item__message { font-weight: 600; }

    .notif-item__time {
      font-size: 12px;
      color: var(--mid);
    }

    @media (max-width: 640px) {
      .page-main { padding: 24px 16px 48px; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <div class="notif-header">
      <h1>Notifications</h1>
      <span class="notif-header__count"><?= count($notifications) ?> total</span>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="notif-empty">
        <div class="notif-empty__icon">
          <svg viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
        </div>
        <h3>No notifications yet</h3>
        <p>You'll be notified when your application status changes.</p>
      </div>

    <?php else: ?>
      <div class="notif-list">
        <?php foreach ($notifications as $notif):
          $msg     = $notif['message'];
          $msgLow  = strtolower($msg);
          $isRead  = (bool)$notif['is_read'];

          // Determine icon style from message content
          if (str_contains($msgLow, 'accepted')) {
            $iconClass = 'notif-item__icon--accepted';
            $iconSvg   = '<polyline points="20 6 9 17 4 12"/>';
          } elseif (str_contains($msgLow, 'rejected')) {
            $iconClass = 'notif-item__icon--rejected';
            $iconSvg   = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
          } elseif (str_contains($msgLow, 'pending') || str_contains($msgLow, 'review')) {
            $iconClass = 'notif-item__icon--pending';
            $iconSvg   = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
          } else {
            $iconClass = 'notif-item__icon--default';
            $iconSvg   = '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>';
          }

          // Human-readable time
          $ts   = strtotime($notif['created_at']);
          $diff = time() - $ts;
          if ($diff < 60)          $timeStr = 'Just now';
          elseif ($diff < 3600)    $timeStr = floor($diff / 60) . ' min ago';
          elseif ($diff < 86400)   $timeStr = floor($diff / 3600) . ' hr ago';
          elseif ($diff < 604800)  $timeStr = floor($diff / 86400) . ' days ago';
          else                     $timeStr = date('M j, Y', $ts);
        ?>
          <div class="notif-item <?= !$isRead ? 'unread' : '' ?>">
            <div class="notif-item__icon <?= $iconClass ?>">
              <svg viewBox="0 0 24 24"><?= $iconSvg ?></svg>
            </div>
            <div class="notif-item__body">
              <p class="notif-item__message"><?= htmlspecialchars($msg) ?></p>
              <span class="notif-item__time"><?= $timeStr ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

  <?php include '../assets/include/user_footer.php'; ?>

</body>
</html>