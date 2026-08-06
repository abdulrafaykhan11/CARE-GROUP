<?php
require_once __DIR__ . '/_admin.php';

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $url     = trim($_POST['action_url'] ?? '#');
        $type    = in_array($_POST['type']??'',['General','Alert','Announcement','Promotion','News']) ? $_POST['type'] : 'General';
        $icon    = trim($_POST['icon'] ?? '🔔');
        $target  = in_array($_POST['target_role']??'',['All','Patient','Doctor']) ? $_POST['target_role'] : 'All';
        $status  = in_array($_POST['status']??'',['Pending','Approved','Rejected']) ? $_POST['status'] : 'Pending';
        if ($title && $message) {
            $st = $conn->prepare("INSERT INTO notifications (user_id,title,message,action_url,type,icon,target_role,status) VALUES (NULL,?,?,?,?,?,?,?)");
            $st->bind_param("sssssss",$title,$message,$url,$type,$icon,$target,$status);
            $msg = $st->execute() ? '✅ Notification broadcast created.' : '❌ '.$conn->error;
            $msgType = $st->execute() ? 'success' : 'error';
        } else { $msg='⚠️ Title and Message are required.'; $msgType='warning'; }
    }

    if ($action === 'update_status') {
        $id = (int)($_POST['notif_id']??0);
        $ns = in_array($_POST['new_status']??'',['Pending','Approved','Rejected']) ? $_POST['new_status'] : '';
        if ($id && $ns) { $conn->query("UPDATE notifications SET status='$ns' WHERE notification_id=$id"); $msg='✅ Status updated.'; $msgType='success'; }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['notif_id']??0);
        if ($id) { $conn->query("DELETE FROM notifications WHERE notification_id=$id"); $msg='✅ Notification deleted.'; $msgType='success'; }
    }
}

$notifications = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
$counts = []; $cr = $conn->query("SELECT status,COUNT(*) t FROM notifications GROUP BY status");
while($r=$cr->fetch_assoc()) $counts[$r['status']]=(int)$r['t'];
$total = array_sum($counts);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notification Manager | CARE Admin Nexus</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* ── Page-level overrides ───────────────────────────── */
.notif-stats{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:32px}
.stat-chip{display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:999px;font-size:13px;font-weight:700;font-family:var(--font-mono);border:1px solid var(--border-cyber);background:var(--bg-card);color:var(--text-muted);transition:all .2s}
.stat-chip.approved{border-color:rgba(16,185,129,.4);color:#059669;background:rgba(16,185,129,.07)}
.stat-chip.pending {border-color:rgba(245,158,11,.4);color:#d97706;background:rgba(245,158,11,.07)}
.stat-chip.rejected{border-color:rgba(239,68,68,.4); color:#dc2626;background:rgba(239,68,68,.07)}
.stat-chip .stat-num{font-size:20px;color:inherit}

/* ── Form grid ──────────────────────────────────────── */
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:680px){.form-grid-2{grid-template-columns:1fr}}

/* ── Live Preview ───────────────────────────────────── */
.notif-preview{
  display:flex;align-items:flex-start;gap:14px;
  padding:16px 18px;border-radius:14px;margin-top:8px;
  background:var(--bg-card);
  border:1px solid var(--border-cyber);
  border-left:4px solid var(--cyan-neon);
  transition:border-color .3s;
  box-shadow:0 4px 20px rgba(6,182,212,.06);
}
.prev-icon{font-size:30px;flex-shrink:0;line-height:1}
.prev-eyebrow{font-family:var(--font-mono);font-size:10px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--cyan-neon);display:flex;align-items:center;gap:6px;margin-bottom:5px}
.prev-dot{width:6px;height:6px;border-radius:50%;background:var(--cyan-neon);animation:pdot 1.6s infinite}
@keyframes pdot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.6)}}
.prev-title{font-size:14px;font-weight:700;color:var(--text-main);margin-bottom:4px;line-height:1.3}
.prev-msg{font-size:12.5px;color:var(--text-muted);line-height:1.55}
.prev-actions{margin-top:10px;display:flex;gap:8px}
.prev-btn{font-size:11px;font-weight:600;padding:4px 14px;border-radius:999px;border:none;cursor:default;background:linear-gradient(135deg,var(--cyan-neon),#3b82f6);color:#fff}
.prev-dismiss{font-size:11px;padding:4px 12px;border-radius:999px;background:transparent;border:1px solid var(--border-cyber);color:var(--text-muted)}

/* type colors for preview */
.prev-alert   {border-left-color:#ef4444!important}.prev-alert    .prev-eyebrow,.prev-alert    .prev-dot{color:#ef4444!important;background:#ef4444!important}
.prev-announcement{border-left-color:#8b5cf6!important}.prev-announcement .prev-eyebrow,.prev-announcement .prev-dot{color:#8b5cf6!important;background:#8b5cf6!important}
.prev-promotion{border-left-color:#f59e0b!important}.prev-promotion .prev-eyebrow,.prev-promotion .prev-dot{color:#d97706!important;background:#f59e0b!important}
.prev-news    {border-left-color:#10b981!important}.prev-news     .prev-eyebrow,.prev-news     .prev-dot{color:#059669!important;background:#10b981!important}

/* ── Table type badges ──────────────────────────────── */
.type-badge{display:inline-block;font-size:10px;font-weight:800;letter-spacing:.8px;padding:3px 10px;border-radius:999px;text-transform:uppercase}
.tb-general     {background:rgba(100,116,139,.1);color:#64748b}
.tb-alert       {background:rgba(239,68,68,.1);color:#dc2626}
.tb-announcement{background:rgba(139,92,246,.1);color:#7c3aed}
.tb-promotion   {background:rgba(245,158,11,.1);color:#b45309}
.tb-news        {background:rgba(16,185,129,.1);color:#059669}

/* status select inside table */
.status-select{font-size:12px;padding:4px 8px;min-width:118px;border-radius:var(--radius-sm);border:1px solid var(--border-cyber);background:var(--bg-card);color:var(--text-main);cursor:pointer}

/* char counter */
.char-counter{font-size:11px;font-family:var(--font-mono);color:var(--text-dim);text-align:right;margin-top:3px}
.char-counter.warn{color:#f59e0b}
.char-counter.danger{color:#ef4444}

/* icon emoji picker strip */
.emoji-strip{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.emoji-btn{width:34px;height:34px;font-size:18px;border:1px solid var(--border-cyber);border-radius:8px;background:var(--bg-card);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}
.emoji-btn:hover,.emoji-btn.active{border-color:var(--cyan-neon);background:rgba(6,182,212,.1);transform:scale(1.1)}

/* ── Notification row card ──────────────────────────── */
.notif-card{
  background:var(--bg-card);
  border:1px solid var(--border-cyber);
  border-radius:var(--radius-lg);
  padding:20px 22px;
  display:grid;
  grid-template-columns:auto 1fr auto;
  gap:16px;
  align-items:flex-start;
  transition:all .2s;
  border-left:4px solid var(--border-cyber);
}
.notif-card:hover{border-color:var(--cyan-neon);box-shadow:0 6px 24px rgba(6,182,212,.08)}
.notif-card.status-approved{border-left-color:#10b981}
.notif-card.status-pending {border-left-color:#f59e0b}
.notif-card.status-rejected{border-left-color:#ef4444}
.nc-icon{font-size:32px;line-height:1;padding-top:2px}
.nc-title{font-size:14px;font-weight:700;color:var(--text-main);margin-bottom:4px;line-height:1.35}
.nc-msg{font-size:12.5px;color:var(--text-muted);line-height:1.55;margin-bottom:8px}
.nc-meta{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.nc-url{font-size:11px;font-family:var(--font-mono);color:var(--cyan-neon);margin-top:4px}
.nc-actions{display:flex;flex-direction:column;gap:8px;align-items:flex-end;flex-shrink:0}
.nc-date{font-size:11px;font-family:var(--font-mono);color:var(--text-dim);white-space:nowrap}
@media(max-width:600px){
  .notif-card{grid-template-columns:auto 1fr;}.nc-actions{grid-column:1/-1;flex-direction:row;justify-content:flex-end}}
</style>
</head>
<body>
<div class="dash-container">
<?php adminSidebar('notifications'); ?>
<main class="dash-content">

  <header class="section-heading">
    <div>
      <p class="eyebrow">BROADCAST CONTROL MATRIX</p>
      <h2>Notification Manager</h2>
    </div>
    <a class="btn btn-outline" href="dashboard.php">← Control HUD</a>
  </header>

  <?php if($msg): ?>
  <div class="alert alert-<?=$msgType?>" style="margin-bottom:24px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:18px"><?=$msgType==='success'?'✅':($msgType==='warning'?'⚠️':'❌')?></span>
    <?=h($msg)?>
  </div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="notif-stats">
    <div class="stat-chip">
      <span style="font-size:20px">🔔</span>
      <div><div style="font-size:10px;color:var(--text-dim)">TOTAL</div><div class="stat-num"><?=$total?></div></div>
    </div>
    <div class="stat-chip approved">
      <span style="font-size:20px">✅</span>
      <div><div style="font-size:10px">LIVE</div><div class="stat-num"><?=$counts['Approved']??0?></div></div>
    </div>
    <div class="stat-chip pending">
      <span style="font-size:20px">⏳</span>
      <div><div style="font-size:10px">PENDING</div><div class="stat-num"><?=$counts['Pending']??0?></div></div>
    </div>
    <div class="stat-chip rejected">
      <span style="font-size:20px">❌</span>
      <div><div style="font-size:10px">REJECTED</div><div class="stat-num"><?=$counts['Rejected']??0?></div></div>
    </div>
  </div>

  <!-- ── Create Form ──────────────────────────────────── -->
  <article class="cyber-table-wrap" style="margin-bottom:32px">
    <div class="section-heading" style="margin-bottom:24px">
      <div>
        <p class="eyebrow">NEW BROADCAST</p>
        <h3 style="margin:0;font-size:20px;color:var(--text-main)">Create Notification</h3>
      </div>
      <span id="formStatus" style="font-size:12px;font-family:var(--font-mono);color:var(--text-dim)">Fill form to preview →</span>
    </div>

    <form method="POST" id="createForm" novalidate>
      <input type="hidden" name="action" value="create">

      <div class="form-grid-2" style="margin-bottom:16px">
        <!-- Icon -->
        <div class="field">
          <label class="form-label">Icon <span style="color:var(--rose-danger)">*</span></label>
          <input type="text" name="icon" id="iconInput" class="form-control" value="🔔" maxlength="8"
                 style="font-size:20px;width:70px" required>
          <div class="emoji-strip" id="emojiStrip">
            <?php foreach(['🔔','🩺','💊','👨‍⚕️','👁️','🏥','💉','📰','⚠️','🎁','📣','🤖','❤️','🧬'] as $em): ?>
            <button type="button" class="emoji-btn" data-emoji="<?=$em?>"><?=$em?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Type -->
        <div class="field">
          <label class="form-label">Type <span style="color:var(--rose-danger)">*</span></label>
          <select name="type" id="typeInput" class="form-control" required>
            <option value="General">🔔 General</option>
            <option value="Alert">🚨 Alert</option>
            <option value="Announcement">📣 Announcement</option>
            <option value="Promotion">🎁 Promotion</option>
            <option value="News">📰 News</option>
          </select>
        </div>

        <!-- Target -->
        <div class="field">
          <label class="form-label">Target Audience <span style="color:var(--rose-danger)">*</span></label>
          <select name="target_role" class="form-control" required>
            <option value="All">🌐 All Users</option>
            <option value="Patient">🧑‍🤝‍🧑 Patients Only</option>
            <option value="Doctor">👨‍⚕️ Doctors Only</option>
          </select>
        </div>

        <!-- Status -->
        <div class="field">
          <label class="form-label">Initial Status <span style="color:var(--rose-danger)">*</span></label>
          <select name="status" class="form-control" required>
            <option value="Approved">✅ Approved — Goes Live</option>
            <option value="Pending">⏳ Pending — Draft</option>
            <option value="Rejected">❌ Rejected</option>
          </select>
        </div>
      </div>

      <!-- Title -->
      <div class="field" style="margin-bottom:16px">
        <label class="form-label">Title <span style="color:var(--rose-danger)">*</span></label>
        <input type="text" name="title" id="titleInput" class="form-control"
               placeholder="e.g. 🩺 Free Community Health Checkup Drive"
               required minlength="8" maxlength="120">
        <div class="char-counter" id="titleCounter">0 / 120</div>
      </div>

      <!-- Message -->
      <div class="field" style="margin-bottom:16px">
        <label class="form-label">Message <span style="color:var(--rose-danger)">*</span></label>
        <textarea name="message" id="msgInput" class="form-control" rows="3"
                  placeholder="Write a clear, concise patient-facing message..."
                  required minlength="20" maxlength="300"></textarea>
        <div class="char-counter" id="msgCounter">0 / 300</div>
      </div>

      <!-- URL -->
      <div class="field" style="margin-bottom:20px">
        <label class="form-label">Action URL <small style="color:var(--text-dim)">(optional)</small></label>
        <input type="text" name="action_url" class="form-control"
               placeholder="e.g. find_doctor.php  or  news.php  or  https://..." value="#">
      </div>

      <!-- Live Preview -->
      <div style="margin-bottom:24px">
        <p class="eyebrow" style="margin-bottom:8px;color:var(--cyan-neon)">LIVE PREVIEW</p>
        <div class="notif-preview" id="previewBox">
          <div class="prev-icon" id="prevIcon">🔔</div>
          <div style="flex:1;min-width:0">
            <div class="prev-eyebrow">
              <span class="prev-dot"></span>
              <span id="prevType">CARE NEXUS — General</span>
            </div>
            <div class="prev-title" id="prevTitle">Your notification title…</div>
            <div class="prev-msg"   id="prevMsg">Your message will appear here.</div>
            <div class="prev-actions">
              <span class="prev-btn">Learn More →</span>
              <span class="prev-dismiss">Don't show again</span>
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary" id="submitBtn">
          📤 Publish Notification
        </button>
        <button type="reset" class="btn btn-outline" onclick="resetPreview()">↺ Clear Form</button>
      </div>
    </form>
  </article>

  <!-- ── Notification List ─────────────────────────────── -->
  <article class="cyber-table-wrap">
    <div class="section-heading" style="margin-bottom:24px">
      <div>
        <p class="eyebrow">BROADCAST LOG</p>
        <h3 style="margin:0;font-size:20px;color:var(--text-main)">All Notifications</h3>
      </div>
      <span style="font-family:var(--font-mono);font-size:12px;color:var(--text-dim)"><?=$total?> TOTAL</span>
    </div>

    <?php if($notifications && $notifications->num_rows > 0): ?>
    <div style="display:grid;gap:14px">
      <?php while($n=$notifications->fetch_assoc()): ?>
      <div class="notif-card status-<?=strtolower($n['status'])?>">

        <div class="nc-icon"><?=htmlspecialchars($n['icon']?:'🔔')?></div>

        <div>
          <div class="nc-title"><?=h($n['title'])?></div>
          <div class="nc-msg"><?=h(mb_substr($n['message'],0,110)).(mb_strlen($n['message'])>110?'…':'')?></div>
          <div class="nc-meta">
            <span class="type-badge tb-<?=strtolower($n['type']?:'general')?>"><?=h($n['type']?:'General')?></span>
            <span style="font-size:12px;color:var(--text-muted);font-family:var(--font-mono)">→ <?=h($n['target_role']?:'All')?></span>
            <span class="nc-date"><?=date('d M Y',strtotime($n['created_at']))?></span>
          </div>
          <?php if($n['action_url'] && $n['action_url']!=='#'): ?>
          <div class="nc-url">🔗 <?=h($n['action_url'])?></div>
          <?php endif; ?>
        </div>

        <div class="nc-actions">
          <!-- Quick status toggle -->
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="notif_id" value="<?=$n['notification_id']?>">
            <select name="new_status" class="status-select" onchange="this.form.submit()">
              <option <?=$n['status']==='Approved'?'selected':''?> value="Approved">✅ Live</option>
              <option <?=$n['status']==='Pending' ?'selected':''?> value="Pending">⏳ Pending</option>
              <option <?=$n['status']==='Rejected'?'selected':''?> value="Rejected">❌ Rejected</option>
            </select>
          </form>
          <!-- Delete -->
          <form method="POST" style="margin:0" onsubmit="return confirm('Delete this notification permanently?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="notif_id" value="<?=$n['notification_id']?>">
            <button type="submit" class="btn"
              style="padding:5px 14px;font-size:12px;color:var(--rose-danger);border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.06)">
              🗑 Delete
            </button>
          </form>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div style="text-align:center;padding:60px 20px">
      <div style="font-size:56px;margin-bottom:16px">🔕</div>
      <h4 style="margin:0;color:var(--text-main)">No notifications yet</h4>
      <p style="color:var(--text-muted);font-size:14px;margin-top:8px">
        Create your first broadcast notification using the form above.
      </p>
    </div>
    <?php endif; ?>
  </article>

</main>
</div>

<script src="../assets/js/live_validation.js"></script>
<script>
(function(){
  const iconInput  = document.getElementById('iconInput');
  const titleInput = document.getElementById('titleInput');
  const msgInput   = document.getElementById('msgInput');
  const typeInput  = document.getElementById('typeInput');
  const prevIcon   = document.getElementById('prevIcon');
  const prevTitle  = document.getElementById('prevTitle');
  const prevMsg    = document.getElementById('prevMsg');
  const prevType   = document.getElementById('prevType');
  const previewBox = document.getElementById('previewBox');
  const titleCtr   = document.getElementById('titleCounter');
  const msgCtr     = document.getElementById('msgCounter');

  const typeClasses = ['prev-alert','prev-announcement','prev-promotion','prev-news'];

  function updateCounter(input, counter, max){
    const len = input.value.length;
    counter.textContent = len + ' / ' + max;
    counter.className = 'char-counter' + (len > max*.9 ? (len >= max ? ' danger' : ' warn') : '');
  }

  function updatePreview(){
    prevIcon.textContent  = iconInput.value || '🔔';
    prevTitle.textContent = titleInput.value || 'Your notification title…';
    prevMsg.textContent   = msgInput.value   || 'Your message will appear here.';
    const t = typeInput.value;
    prevType.textContent  = 'CARE NEXUS — ' + t;
    typeClasses.forEach(c => previewBox.classList.remove(c));
    if(t !== 'General') previewBox.classList.add('prev-' + t.toLowerCase());
    updateCounter(titleInput, titleCtr, 120);
    updateCounter(msgInput, msgCtr, 300);
  }

  // Emoji picker
  document.querySelectorAll('.emoji-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      iconInput.value = btn.dataset.emoji;
      document.querySelectorAll('.emoji-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      updatePreview();
    });
  });

  [iconInput, titleInput, msgInput, typeInput].forEach(el => el.addEventListener('input', updatePreview));
  typeInput.addEventListener('change', updatePreview);

  window.resetPreview = function(){
    setTimeout(updatePreview, 50);
  };

  updatePreview();
})();
</script>
</body>
</html>
