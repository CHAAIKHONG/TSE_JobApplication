<?php
/**
 * footer.php — Reusable Footer
 * Usage on any page: <?php include 'includes/footer.php'; ?>
 */
$currentYear = date('Y');
?>
<style>
  .ap-footer {
    background: var(--ink, #0f0f0f);
    color: rgba(255,255,255,0.5);
    font-family: 'DM Sans', sans-serif;
    padding: 48px 0 24px;
    margin-top: auto;
  }

  .ap-footer__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 32px;
  }

  .ap-footer__top {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 48px;
    padding-bottom: 40px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }

  .ap-footer__brand-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    text-decoration: none;
  }

  .ap-footer__brand-mark {
    width: 28px; height: 28px;
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
  }

  .ap-footer__brand-mark::after {
    content: '';
    position: absolute;
    bottom: 0; right: 0;
    width: 12px; height: 12px;
    background: var(--accent, #e85d26);
    border-radius: 6px 0 0 0;
  }

  .ap-footer__brand-mark svg {
    width: 14px; height: 14px;
    fill: none; position: relative; z-index: 1;
  }

  .ap-footer__brand-name {
    font-family: 'Syne', sans-serif;
    font-weight: 800; font-size: 16px;
    color: #fff; letter-spacing: -0.5px;
  }

  .ap-footer__brand-name span { color: var(--accent, #e85d26); }

  .ap-footer__tagline {
    font-size: 13px; line-height: 1.6;
    color: rgba(255,255,255,0.4);
    max-width: 220px;
  }

  .ap-footer__col-title {
    font-family: 'Syne', sans-serif;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: rgba(255,255,255,0.3);
    margin-bottom: 16px;
  }

  .ap-footer__links {
    list-style: none;
    display: flex; flex-direction: column; gap: 10px;
  }

  .ap-footer__links a {
    text-decoration: none;
    font-size: 13px; color: rgba(255,255,255,0.5);
    transition: color 0.2s;
  }

  .ap-footer__links a:hover { color: #fff; }

  .ap-footer__bottom {
    padding-top: 24px;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 12px;
  }

  .ap-footer__copy { color: rgba(255,255,255,0.25); }
  .ap-footer__copy span { color: var(--accent, #e85d26); }

  .ap-footer__socials { display: flex; gap: 8px; }

  .ap-footer__social-btn {
    width: 32px; height: 32px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none;
    transition: border-color 0.2s, background 0.2s;
  }

  .ap-footer__social-btn:hover {
    border-color: rgba(255,255,255,0.4);
    background: rgba(255,255,255,0.05);
  }

  .ap-footer__social-btn svg {
    width: 14px; height: 14px;
    stroke: rgba(255,255,255,0.4); fill: none; stroke-width: 1.8;
    transition: stroke 0.2s;
  }

  .ap-footer__social-btn:hover svg { stroke: #fff; }

  @media (max-width: 768px) {
    .ap-footer__top { grid-template-columns: 1fr 1fr; gap: 32px; }
    .ap-footer__bottom { flex-direction: column; gap: 16px; text-align: center; }
    .ap-footer__inner { padding: 0 16px; }
  }
</style>

<footer class="ap-footer" role="contentinfo">
  <div class="ap-footer__inner">
    <div class="ap-footer__top">

      <!-- Brand -->
      <div>
        <a href="dashboard.php" class="ap-footer__brand-logo" aria-label="ApplyGo home">
          <div class="ap-footer__brand-mark">
            <svg viewBox="0 0 16 16">
              <path d="M2 12 L8 4 L14 12" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="ap-footer__brand-name">Apply<span>Go</span></span>
        </a>
        <p class="ap-footer__tagline">Your career, mapped. Discover roles that align with your ambitions and apply with confidence.</p>
      </div>

      <!-- Job Seekers -->
      <div>
        <p class="ap-footer__col-title">Job Seekers</p>
        <ul class="ap-footer__links">
          <li><a href="dashboard.php">Browse Jobs</a></li>
          <li><a href="applications.php">My Applications</a></li>
          <li><a href="profile.php">My Profile</a></li>
          <li><a href="saved.php">Saved Jobs</a></li>
        </ul>
      </div>

      <!-- Company -->
      <div>
        <p class="ap-footer__col-title">Company</p>
        <ul class="ap-footer__links">
          <li><a href="about.php">About Us</a></li>
          <li><a href="companies.php">For Employers</a></li>
          <li><a href="blog.php">Blog</a></li>
          <li><a href="careers.php">Careers</a></li>
        </ul>
      </div>

      <!-- Support -->
      <div>
        <p class="ap-footer__col-title">Support</p>
        <ul class="ap-footer__links">
          <li><a href="help.php">Help Center</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
          <li><a href="terms.php">Terms of Use</a></li>
          <li><a href="contact.php">Contact Us</a></li>
        </ul>
      </div>

    </div>

    <div class="ap-footer__bottom">
      <p class="ap-footer__copy">
        &copy; <?= $currentYear ?> <span>ApplyGo</span>. All rights reserved.
      </p>
      <div class="ap-footer__socials">
        <a href="#" class="ap-footer__social-btn" aria-label="LinkedIn">
          <svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        </a>
        <a href="#" class="ap-footer__social-btn" aria-label="Twitter / X">
          <svg viewBox="0 0 24 24"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
        </a>
        <a href="#" class="ap-footer__social-btn" aria-label="Instagram">
          <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
      </div>
    </div>

  </div>
</footer>