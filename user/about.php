<?php
$activePage = 'about';
$pageTitle  = 'About Us — ApplyGo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink:       #0f0f0f;
      --paper:     #faf9f7;
      --surface:   #f2efea;
      --accent:    #e85d26;
      --accent-lt: #fdf0ea;
      --mid:       #6b6560;
      --border:    #e2ddd8;
      --radius:    12px;
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
      max-width: 1280px;
      width: 100%;
      margin: 0 auto;
      padding: 40px 32px 80px;
    }

    /* ── Page Header ── */
    .about-header {
      padding-bottom: 48px;
      border-bottom: 1.5px solid var(--border);
      margin-bottom: 64px;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 32px;
    }

    .about-header__eyebrow {
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 2px;
      color: var(--accent); margin-bottom: 10px;
    }

    .about-header__title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(36px, 5vw, 58px);
      font-weight: 800; line-height: 1.05; letter-spacing: -2px;
    }

    .about-header__title em {
      font-style: italic; font-weight: 400; color: var(--mid);
    }

    .about-header__desc {
      margin-top: 18px;
      font-size: 16px; line-height: 1.7; color: var(--mid);
      max-width: 560px;
    }

    .about-header__stat-row {
      display: flex; gap: 0; flex-shrink: 0;
      border: 1.5px solid var(--border); border-radius: var(--radius);
      overflow: hidden;
    }

    .about-stat {
      padding: 20px 28px; text-align: center;
      border-right: 1.5px solid var(--border);
    }

    .about-stat:last-child { border-right: none; }

    .about-stat__number {
      font-family: 'Syne', sans-serif;
      font-size: 28px; font-weight: 800; letter-spacing: -1px;
      color: var(--ink);
    }

    .about-stat__label {
      font-size: 11px; color: var(--mid);
      text-transform: uppercase; letter-spacing: 0.8px;
      margin-top: 3px;
    }

    /* ── Section Label ── */
    .section-eyebrow {
      font-family: 'Syne', sans-serif;
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 2.5px;
      color: var(--accent); margin-bottom: 14px;
    }

    .section-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(22px, 3vw, 30px);
      font-weight: 800; letter-spacing: -1px; line-height: 1.15;
      margin-bottom: 14px;
    }

    .section-body {
      font-size: 15px; line-height: 1.75; color: var(--mid);
    }

    /* ── Mission & Vision ── */
    .mv-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 80px;
    }

    .mv-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 36px;
      position: relative;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    }

    .mv-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 3px; background: var(--accent);
    }

    .mv-card:hover {
      border-color: var(--ink);
      box-shadow: 0 8px 32px rgba(0,0,0,0.07);
      transform: translateY(-2px);
    }

    .mv-card__icon {
      width: 44px; height: 44px; border-radius: 10px;
      background: var(--accent-lt); border: 1.5px solid #f5d0be;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; margin-bottom: 20px;
    }

    .mv-card__title {
      font-family: 'Syne', sans-serif;
      font-size: 20px; font-weight: 800; letter-spacing: -0.5px;
      margin-bottom: 10px;
    }

    .mv-card__body {
      font-size: 14px; line-height: 1.75; color: var(--mid);
    }

    /* ── Story Section ── */
    .story-section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 64px;
      align-items: center;
      margin-bottom: 80px;
      padding-bottom: 80px;
      border-bottom: 1.5px solid var(--border);
    }

    .story-visual {
      background: var(--surface);
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 40px;
      display: flex; flex-direction: column; gap: 12px;
    }

    .story-timeline-item {
      display: flex; gap: 16px; align-items: flex-start;
    }

    .story-timeline-dot {
      width: 10px; height: 10px; border-radius: 50%;
      background: var(--accent); flex-shrink: 0; margin-top: 5px;
    }

    .story-timeline-dot--mid {
      background: var(--border);
      border: 2px solid var(--accent);
    }

    .story-timeline-dot--empty {
      background: var(--border);
    }

    .story-timeline-line {
      width: 1.5px; height: 24px; background: var(--border);
      margin-left: 4.25px;
    }

    .story-timeline-year {
      font-family: 'Syne', sans-serif;
      font-size: 12px; font-weight: 700; color: var(--accent);
      letter-spacing: 0.5px; margin-bottom: 2px;
    }

    .story-timeline-text {
      font-size: 13px; line-height: 1.6; color: var(--mid);
    }

    /* ── Values ── */
    .values-section { margin-bottom: 80px; }

    .values-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-top: 36px;
    }

    .value-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 28px 24px;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
      animation: cardIn 0.4s ease both;
    }

    .value-card:hover {
      border-color: var(--ink);
      box-shadow: 0 8px 32px rgba(0,0,0,0.06);
      transform: translateY(-2px);
    }

    .value-card:nth-child(1) { animation-delay: 0.05s; }
    .value-card:nth-child(2) { animation-delay: 0.10s; }
    .value-card:nth-child(3) { animation-delay: 0.15s; }
    .value-card:nth-child(4) { animation-delay: 0.20s; }
    .value-card:nth-child(5) { animation-delay: 0.25s; }
    .value-card:nth-child(6) { animation-delay: 0.30s; }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .value-card__num {
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 800;
      color: var(--accent); letter-spacing: 1px;
      margin-bottom: 12px;
    }

    .value-card__title {
      font-family: 'Syne', sans-serif;
      font-size: 16px; font-weight: 700; letter-spacing: -0.3px;
      margin-bottom: 8px;
    }

    .value-card__body {
      font-size: 13px; line-height: 1.65; color: var(--mid);
    }

    /* ── Team ── */
    .team-section { margin-bottom: 80px; }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-top: 36px;
    }

    .team-card {
      background: #fff;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 28px 20px;
      text-align: center;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
      animation: cardIn 0.4s ease both;
    }

    .team-card:hover {
      border-color: var(--ink);
      box-shadow: 0 8px 32px rgba(0,0,0,0.06);
      transform: translateY(-2px);
    }

    .team-card:nth-child(1) { animation-delay: 0.05s; }
    .team-card:nth-child(2) { animation-delay: 0.10s; }
    .team-card:nth-child(3) { animation-delay: 0.15s; }
    .team-card:nth-child(4) { animation-delay: 0.20s; }

    .team-avatar {
      width: 60px; height: 60px; border-radius: 50%;
      background: var(--surface); border: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 16px;
      color: var(--ink); margin: 0 auto 16px;
      letter-spacing: -0.5px;
    }

    .team-avatar--accent { background: var(--accent); border-color: var(--accent); color: #fff; }
    .team-avatar--warm   { background: #fff3ee; border-color: #f5c4a0; color: #c04b10; }
    .team-avatar--cool   { background: #eef3ff; border-color: #b3c6f5; color: #2c4faf; }
    .team-avatar--green  { background: #edf7ee; border-color: #9fd4a3; color: #2e7d32; }

    .team-card__name {
      font-family: 'Syne', sans-serif;
      font-size: 15px; font-weight: 700; letter-spacing: -0.3px;
      margin-bottom: 4px;
    }

    .team-card__role {
      font-size: 12px; color: var(--accent); font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.8px;
      margin-bottom: 10px;
    }

    .team-card__bio {
      font-size: 12px; line-height: 1.6; color: var(--mid);
    }

    /* ── CTA Banner ── */
    .cta-banner {
      background: var(--ink);
      border-radius: var(--radius);
      padding: 52px 56px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 32px;
    }

    .cta-banner__title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(22px, 3vw, 32px);
      font-weight: 800; letter-spacing: -1px; color: #fff; line-height: 1.2;
    }

    .cta-banner__title em {
      font-style: italic; font-weight: 400; color: rgba(255,255,255,0.5);
    }

    .cta-banner__sub {
      margin-top: 8px; font-size: 14px; color: rgba(255,255,255,0.5); line-height: 1.6;
    }

    .cta-banner__actions { display: flex; gap: 12px; flex-shrink: 0; }

    .btn-primary {
      padding: 12px 28px;
      background: var(--accent); color: #fff;
      border: none; border-radius: 8px;
      font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
      cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
      transition: opacity 0.2s, transform 0.15s;
    }

    .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }

    .btn-ghost {
      padding: 12px 28px;
      background: transparent; color: rgba(255,255,255,0.7);
      border: 1.5px solid rgba(255,255,255,0.2); border-radius: 8px;
      font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
      cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
      transition: border-color 0.2s, color 0.2s, transform 0.15s;
    }

    .btn-ghost:hover {
      border-color: rgba(255,255,255,0.5);
      color: #fff;
      transform: translateY(-1px);
    }

    /* ── Responsive ── */
    @media (max-width: 1024px) {
      .team-grid { grid-template-columns: repeat(2, 1fr); }
      .values-grid { grid-template-columns: repeat(2, 1fr); }
      .about-header { flex-direction: column; align-items: flex-start; }
    }

    @media (max-width: 768px) {
      .story-section { grid-template-columns: 1fr; gap: 32px; }
      .mv-grid { grid-template-columns: 1fr; }
      .cta-banner { flex-direction: column; align-items: flex-start; padding: 36px 28px; }
    }

    @media (max-width: 640px) {
      .page-main { padding: 24px 16px 56px; }
      .team-grid { grid-template-columns: 1fr 1fr; }
      .values-grid { grid-template-columns: 1fr; }
      .about-header__stat-row { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

  <?php include '../assets/include/user_topbar.php'; ?>

  <main class="page-main">

    <!-- ── Page Header ── -->
    <div class="about-header">
      <div>
        <p class="about-header__eyebrow">About ApplyGo</p>
        <h1 class="about-header__title">
          We connect talent<br>
          <em>with opportunity</em>
        </h1>
        <p class="about-header__desc">
          ApplyGo is a modern job platform built for both job seekers and employers.
          We believe finding the right job — or the right person — should be simple,
          transparent, and human.
        </p>
      </div>
      <div class="about-header__stat-row">
        <div class="about-stat">
          <div class="about-stat__number">10K+</div>
          <div class="about-stat__label">Job Seekers</div>
        </div>
        <div class="about-stat">
          <div class="about-stat__number">500+</div>
          <div class="about-stat__label">Companies</div>
        </div>
        <div class="about-stat">
          <div class="about-stat__number">3K+</div>
          <div class="about-stat__label">Placements</div>
        </div>
      </div>
    </div>

    <!-- ── Mission & Vision ── -->
    <div style="margin-bottom: 80px;">
      <p class="section-eyebrow">What drives us</p>
      <div class="mv-grid">
        <div class="mv-card">
          <div class="mv-card__icon">🎯</div>
          <h2 class="mv-card__title">Our Mission</h2>
          <p class="mv-card__body">
            To make the job search process faster, fairer, and more human — giving every
            candidate a real shot at their dream role and every employer the tools to find
            talent that truly fits.
          </p>
        </div>
        <div class="mv-card">
          <div class="mv-card__icon">🔭</div>
          <h2 class="mv-card__title">Our Vision</h2>
          <p class="mv-card__body">
            A world where career opportunities are accessible to everyone, regardless of
            background — where merit and potential matter more than who you know or
            where you studied.
          </p>
        </div>
      </div>
    </div>

    <!-- ── Story ── -->
    <div class="story-section">
      <div>
        <p class="section-eyebrow">Our story</p>
        <h2 class="section-title">Built from<br>frustration.</h2>
        <p class="section-body">
          ApplyGo was founded after our team experienced firsthand how broken the
          traditional hiring process was — endless forms, zero feedback, and months
          of silence after interviews.
        </p>
        <p class="section-body" style="margin-top: 14px;">
          We set out to build something better: a platform that respects candidates'
          time and gives employers honest, clear tools to hire with confidence.
        </p>
      </div>
      <div class="story-visual">
        <div class="story-timeline-item">
          <div>
            <div class="story-timeline-dot"></div>
            <div class="story-timeline-line"></div>
          </div>
          <div>
            <div class="story-timeline-year">2021</div>
            <div class="story-timeline-text">ApplyGo founded — first version launched as a local jobs board.</div>
          </div>
        </div>
        <div class="story-timeline-item">
          <div>
            <div class="story-timeline-dot story-timeline-dot--mid"></div>
            <div class="story-timeline-line"></div>
          </div>
          <div>
            <div class="story-timeline-year">2022</div>
            <div class="story-timeline-text">Expanded to 50+ employer partners. Application tracking launched.</div>
          </div>
        </div>
        <div class="story-timeline-item">
          <div>
            <div class="story-timeline-dot story-timeline-dot--mid"></div>
            <div class="story-timeline-line"></div>
          </div>
          <div>
            <div class="story-timeline-year">2023</div>
            <div class="story-timeline-text">Crossed 5,000 active users. Real-time status updates introduced.</div>
          </div>
        </div>
        <div class="story-timeline-item">
          <div>
            <div class="story-timeline-dot"></div>
          </div>
          <div>
            <div class="story-timeline-year">2024 →</div>
            <div class="story-timeline-text">10K+ job seekers. Growing every day.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Values ── -->
    <div class="values-section">
      <p class="section-eyebrow">What we stand for</p>
      <h2 class="section-title">Our core values</h2>
      <div class="values-grid">
        <div class="value-card">
          <div class="value-card__num">01</div>
          <h3 class="value-card__title">Transparency</h3>
          <p class="value-card__body">
            Candidates always know where they stand. No ghosting, no ambiguity —
            clear status updates at every step.
          </p>
        </div>
        <div class="value-card">
          <div class="value-card__num">02</div>
          <h3 class="value-card__title">Fairness</h3>
          <p class="value-card__body">
            Every application gets a fair review. We build tools that reduce bias
            and open doors for underrepresented talent.
          </p>
        </div>
        <div class="value-card">
          <div class="value-card__num">03</div>
          <h3 class="value-card__title">Simplicity</h3>
          <p class="value-card__body">
            Hiring is already stressful. Our platform is designed to be intuitive,
            fast, and friction-free for everyone.
          </p>
        </div>
        <div class="value-card">
          <div class="value-card__num">04</div>
          <h3 class="value-card__title">Respect</h3>
          <p class="value-card__body">
            We treat job seekers as people, not applicant numbers. Every interaction
            on ApplyGo is built on mutual respect.
          </p>
        </div>
        <div class="value-card">
          <div class="value-card__num">05</div>
          <h3 class="value-card__title">Impact</h3>
          <p class="value-card__body">
            A job changes a life. We take that seriously and measure our success
            by the careers we help launch.
          </p>
        </div>
        <div class="value-card">
          <div class="value-card__num">06</div>
          <h3 class="value-card__title">Innovation</h3>
          <p class="value-card__body">
            We keep improving. From real-time notifications to smarter matching,
            we're always building what's next.
          </p>
        </div>
      </div>
    </div>

    <!-- ── Team ── -->
    <div class="team-section">
      <p class="section-eyebrow">The people behind it</p>
      <h2 class="section-title">Meet the team</h2>
      <div class="team-grid">
        <div class="team-card">
          <div class="team-avatar team-avatar--accent">AK</div>
          <div class="team-card__name">Ahmad Karim</div>
          <div class="team-card__role">CEO & Co-founder</div>
          <p class="team-card__bio">
            Former recruiter turned builder. Ahmad started ApplyGo after watching
            great candidates slip through broken systems.
          </p>
        </div>
        <div class="team-card">
          <div class="team-avatar team-avatar--warm">SR</div>
          <div class="team-card__name">Sarah Razali</div>
          <div class="team-card__role">CTO & Co-founder</div>
          <p class="team-card__bio">
            Full-stack engineer with a passion for product. Sarah leads everything
            from infrastructure to user experience.
          </p>
        </div>
        <div class="team-card">
          <div class="team-avatar team-avatar--cool">DL</div>
          <div class="team-card__name">Daniel Lim</div>
          <div class="team-card__role">Head of Product</div>
          <p class="team-card__bio">
            Obsessed with simplicity. Daniel ensures every feature on ApplyGo
            earns its place on the screen.
          </p>
        </div>
        <div class="team-card">
          <div class="team-avatar team-avatar--green">NJ</div>
          <div class="team-card__name">Nurul Jannah</div>
          <div class="team-card__role">Head of Growth</div>
          <p class="team-card__bio">
            Connects employers and talent. Nurul drives partnerships that bring
            real opportunities to the platform.
          </p>
        </div>
      </div>
    </div>

    <!-- ── CTA Banner ── -->
    <div class="cta-banner">
      <div>
        <h2 class="cta-banner__title">
          Ready to find your<br><em>next great opportunity?</em>
        </h2>
        <p class="cta-banner__sub">
          Join thousands of job seekers already using ApplyGo to land roles they love.
        </p>
      </div>
      <div class="cta-banner__actions">
        <a href="dashboard.php" class="btn-primary">Browse Jobs ↗</a>
        <a href="register.php" class="btn-ghost">Create Account</a>
      </div>
    </div>

  </main>

</body>
</html>