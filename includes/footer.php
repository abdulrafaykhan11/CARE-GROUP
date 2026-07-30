  <footer style="background: rgba(4, 7, 17, 0.95); border-top: 1px solid var(--border-cyber); padding: 60px 6% 40px; margin-top: 80px; position: relative; z-index: 10;">
    <div style="max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 40px;">
      <div>
        <a class="brand" href="index.php" style="margin-bottom: 16px; display: inline-flex;">
          CARE <span>NEXUS</span>
        </a>
        <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; margin-top: 12px;">
          A trusted medical care platform connecting patients with verified specialists, clinics, appointments, FAQs, and health updates.
        </p>
      </div>
      
      <div>
        <h4 style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 18px;">
          Clinical Portals
        </h4>
        <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; font-size: 14px; color: var(--text-muted);">
          <li><a href="find_doctor.php">Doctor Search</a></li>
          <li><a href="register.php">Patient Registration</a></li>
          <li><a href="register.php">Doctor Portal Access</a></li>
          <li><a href="admin/dashboard.php">Admin Oversight</a></li>
        </ul>
      </div>

      <div>
        <h4 style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 18px;">
          Network Telemetry
        </h4>
        <div style="display: grid; gap: 10px; font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
          <div>STATUS: <span style="color: var(--emerald-bio);">100% OPERATIONAL</span></div>
          <div>ENCRYPTION: <span style="color: var(--cyan-neon);">SECURE PORTAL</span></div>
          <div>COVERAGE: <span style="color: var(--violet-quantum);">8 METROPOLITAN CITIES</span></div>
        </div>
      </div>
    </div>
    
    <div style="max-width: 1280px; margin: 40px auto 0; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.06); text-align: center; color: var(--text-dim); font-size: 13px;">
      &copy; <?= date('Y') ?> CARE Group Medical Services Platform. All rights reserved.
    </div>
  </footer>

  <!-- 3D WebGL & GSAP Engine Script -->
  <script src="assets/js/cyber_3d.js"></script>
</body>
</html>
