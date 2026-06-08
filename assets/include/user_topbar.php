<?php
/**
 * user_topbar.php — Reusable Top Navigation Bar
 */

// ── Auth: fetch user from DB if logged in ────────────────────────────────────
if (!isset($conn)) {
    require_once '../database/db.php';
}

// Protected pages — redirect to login if not logged in
$protectedPages = ['profile.php', 'applications.php', 'settings.php', 'dashboard.php'];
$currentPage    = basename($_SERVER['PHP_SELF']);

if (in_array($currentPage, $protectedPages) && empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user from DB only if not already fetched by the host page
if (!empty($_SESSION['user_id']) && empty($currentUser)) {
    $stmt = $conn->prepare('SELECT user_id, name, email FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $dbUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($dbUser) {
        $nameParts = explode(' ', trim($dbUser['name']));
        $initials  = strtoupper(
            (isset($nameParts[0]) ? $nameParts[0][0] : '') .
            (isset($nameParts[1]) ? $nameParts[1][0] : '')
        );
        $currentUser = [
            'name'        => $dbUser['name'],
            'initials'    => $initials,
            'notif_count' => 0,
        ];
    }
}

// 如果 host page 已经查询并设置了 $currentUser（如 dashboard.php），
// 确保它包含 topbar 需要的 initials 和 notif_count 字段
if (!empty($currentUser) && !isset($currentUser['initials'])) {
    $nameParts = explode(' ', trim($currentUser['name']));
    $currentUser['initials'] = strtoupper(
        (isset($nameParts[0]) ? $nameParts[0][0] : '') .
        (isset($nameParts[1]) ? $nameParts[1][0] : '')
    );
}

if (!empty($currentUser) && !isset($currentUser['notif_count'])) {
    $currentUser['notif_count'] = 0;
}

// Defaults if not logged in
$activePage  = $activePage  ?? 'dashboard';
$currentUser = $currentUser ?? ['name' => 'Guest', 'initials' => '?', 'notif_count' => 0];

$navLinks = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'companies' => ['label' => 'Companies', 'href' => 'companies.php'],
    'positions' => ['label' => 'Positions', 'href' => 'positions.php'],
    'about'     => ['label' => 'About Us',  'href' => 'about.php'],
];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
  :root {
    --ink:    #0f0f0f;
    --paper:  #faf9f7;
    --accent: #e85d26;
    --mid:    #6b6560;
    --border: #e2ddd8;
    --nav-h:  64px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  .ap-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: var(--paper);
    border-bottom: 1.5px solid var(--border);
    height: var(--nav-h);
    font-family: 'DM Sans', sans-serif;
  }

  .ap-nav__inner {
    max-width: 1280px;
    margin: 0 auto;
    height: 100%;
    display: flex;
    align-items: center;
    padding: 0 32px;
    gap: 40px;
  }

  .ap-nav__logo {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .ap-nav__logo-mark {
    width: 32px; height: 32px;
    background: var(--ink);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
  }

  .ap-nav__logo-mark::after {
    content: '';
    position: absolute;
    bottom: 0; right: 0;
    width: 14px; height: 14px;
    background: var(--accent);
    border-radius: 6px 0 0 0;
  }

  .ap-nav__logo-mark svg { width: 16px; height: 16px; position: relative; z-index: 1; }

  .ap-nav__logo-text {
    font-family: 'Syne', sans-serif;
    font-weight: 800; font-size: 18px;
    color: var(--ink); letter-spacing: -0.5px;
  }

  .ap-nav__logo-text span { color: var(--accent); }

  .ap-nav__links {
    display: flex; align-items: center; gap: 4px; list-style: none;
  }

  .ap-nav__links a {
    text-decoration: none; color: var(--mid);
    font-size: 14px; font-weight: 500;
    padding: 6px 12px; border-radius: 6px;
    transition: color 0.2s, background 0.2s;
  }

  .ap-nav__links a:hover,
  .ap-nav__links a.active { color: var(--ink); background: rgba(0,0,0,0.05); }

  .ap-nav__spacer { flex: 1; }

  .ap-nav__actions { display: flex; align-items: center; gap: 8px; }

  .ap-nav__notif {
    position: relative;
    width: 38px; height: 38px;
    border: 1.5px solid var(--border); border-radius: 8px;
    background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s, background 0.2s;
  }

  .ap-nav__notif:hover { border-color: var(--ink); background: var(--ink); }
  .ap-nav__notif:hover svg { stroke: #fff; }

  .ap-nav__notif svg {
    width: 16px; height: 16px;
    stroke: var(--mid); fill: none; stroke-width: 1.8;
    transition: stroke 0.2s;
  }

  .ap-nav__notif-dot {
    position: absolute;
    top: 7px; right: 7px;
    width: 7px; height: 7px;
    background: var(--accent);
    border-radius: 50%;
    border: 1.5px solid var(--paper);
  }

  .ap-nav__profile { position: relative; }

  .ap-nav__profile-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 5px 10px 5px 5px;
    border: 1.5px solid var(--border); border-radius: 8px;
    background: transparent; cursor: pointer;
    transition: border-color 0.2s;
    font-family: 'DM Sans', sans-serif;
  }

  .ap-nav__profile-btn:hover { border-color: var(--ink); }

  .ap-nav__avatar {
    width: 28px; height: 28px;
    background: var(--ink); border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 11px;
    color: var(--paper); letter-spacing: 0.5px;
  }

  .ap-nav__profile-name { font-size: 13px; font-weight: 500; color: var(--ink); }

  .ap-nav__chevron {
    width: 14px; height: 14px;
    stroke: var(--mid); fill: none; stroke-width: 2;
    transition: transform 0.2s;
  }

  .ap-nav__profile.open .ap-nav__chevron { transform: rotate(180deg); }

  .ap-nav__dropdown {
    position: absolute;
    top: calc(100% + 8px); right: 0;
    min-width: 180px;
    background: var(--paper);
    border: 1.5px solid var(--border);
    border-radius: 10px; padding: 6px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    display: none; z-index: 200;
  }

  .ap-nav__profile.open .ap-nav__dropdown { display: block; }

  .ap-nav__dropdown a {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px;
    font-size: 13px; color: var(--mid);
    text-decoration: none; border-radius: 6px;
    transition: background 0.15s, color 0.15s;
    font-family: 'DM Sans', sans-serif;
  }

  .ap-nav__dropdown a:hover { background: rgba(0,0,0,0.05); color: var(--ink); }
  .ap-nav__dropdown-divider { height: 1px; background: var(--border); margin: 4px 0; }
  .ap-nav__dropdown .logout { color: #c0392b; }
  .ap-nav__dropdown .logout:hover { background: #fef2f2; color: #c0392b; }

  /* Disabled link style for guests */
  .ap-nav__dropdown a.disabled {
    opacity: 0.4; pointer-events: none; cursor: default;
  }

  @media (max-width: 768px) {
    .ap-nav__links { display: none; }
    .ap-nav__inner { padding: 0 16px; }
  }
</style>

<nav class="ap-nav" role="navigation" aria-label="Main navigation">
  <div class="ap-nav__inner">

    <a href="dashboard.php" class="ap-nav__logo" aria-label="ApplyGo — go to dashboard">
      <div class="ap-nav__logo-mark">
        <svg viewBox="0 0 16 16"><path d="M2 12 L8 4 L14 12" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <span class="ap-nav__logo-text">Apply<span>Go</span></span>
    </a>

    <ul class="ap-nav__links">
      <?php foreach ($navLinks as $key => $link): ?>
        <li>
          <a href="<?= htmlspecialchars($link['href']) ?>"
             <?= ($activePage === $key) ? 'class="active" aria-current="page"' : '' ?>>
            <?= htmlspecialchars($link['label']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="ap-nav__spacer"></div>

    <div class="ap-nav__actions">

      <!-- Notification Bell -->
      <button class="ap-nav__notif" aria-label="View notifications" onclick="toggleNotifications()">
        <svg viewBox="0 0 24 24">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <?php if (!empty($currentUser['notif_count']) && $currentUser['notif_count'] > 0): ?>
          <span class="ap-nav__notif-dot" id="notif-dot"
                aria-label="<?= (int)$currentUser['notif_count'] ?> unread notifications">
          </span>
        <?php endif; ?>
      </button>

      <!-- Profile Dropdown -->
      <div class="ap-nav__profile" id="profileMenu">
        <button class="ap-nav__profile-btn"
                onclick="toggleProfile()"
                aria-haspopup="true"
                aria-expanded="false">
          <div class="ap-nav__avatar">
            <?= htmlspecialchars($currentUser['initials']) ?>
          </div>
          <span class="ap-nav__profile-name">
            <?= htmlspecialchars($currentUser['name']) ?>
          </span>
          <svg class="ap-nav__chevron" viewBox="0 0 24 24">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>

        <div class="ap-nav__dropdown" role="menu">

          <?php $loggedIn = !empty($_SESSION['user_id']); ?>

          <a href="<?= $loggedIn ? 'profile.php' : 'login.php' ?>"
             <?= !$loggedIn ? 'class="disabled"' : '' ?>
             role="menuitem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="4"/>
              <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            My Profile
          </a>

          <a href="<?= $loggedIn ? 'applications.php' : 'login.php' ?>"
             <?= !$loggedIn ? 'class="disabled"' : '' ?>
             role="menuitem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 11l3 3L22 4"/>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            My Applications
          </a>

          <a href="<?= $loggedIn ? 'settings.php' : 'login.php' ?>"
             <?= !$loggedIn ? 'class="disabled"' : '' ?>
             role="menuitem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/>
              <path d="M12 2v2m0 16v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M2 12h2m16 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            Settings
          </a>

          <div class="ap-nav__dropdown-divider"></div>

          <?php if ($loggedIn): ?>
            <a href="logout.php" class="logout" role="menuitem">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
              Sign Out
            </a>
          <?php else: ?>
            <a href="login.php" role="menuitem">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
              </svg>
              Sign In
            </a>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</nav>

<script>
  function toggleProfile() {
    const menu = document.getElementById('profileMenu');
    const btn  = menu.querySelector('.ap-nav__profile-btn');
    const isOpen = menu.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen);
  }

  function toggleNotifications() {
    const dot = document.getElementById('notif-dot');
    if (dot) dot.remove();
    alert('Notification panel coming soon!');
  }

  document.addEventListener('click', function(e) {
    const menu = document.getElementById('profileMenu');
    if (menu && !menu.contains(e.target)) {
      menu.classList.remove('open');
      menu.querySelector('.ap-nav__profile-btn')?.setAttribute('aria-expanded', 'false');
    }
  });
</script>