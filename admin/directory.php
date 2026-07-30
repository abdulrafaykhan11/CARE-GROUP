<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$clinics = mysqli_query($conn, "SELECT cl.clinic_id,cl.clinic_name,cl.address,cl.status,c.city_name,(SELECT COUNT(*) FROM doctor_clinic dc WHERE dc.clinic_id=cl.clinic_id) doctors_count FROM clinics cl JOIN cities c ON c.city_id=cl.city_id ORDER BY cl.status ASC, cl.clinic_name ASC");
$specializations = mysqli_query($conn, "SELECT s.specialization_id,s.specialization_name,s.description,s.status,(SELECT COUNT(*) FROM doctors d WHERE d.specialization_id=s.specialization_id) doctors_count FROM specializations s ORDER BY s.status ASC, s.specialization_name ASC");
$cities = mysqli_query($conn, "SELECT city_id,city_name,state,status FROM cities ORDER BY status ASC, city_name ASC");
$cityOptions = mysqli_query($conn, "SELECT city_id,city_name FROM cities WHERE status='Active' ORDER BY city_name ASC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Directory Network Nodes | Admin Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('directory'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">DIRECTORY & NETWORK NODES</p>
                    <h2>Clinics, Specializations & City Nodes</h2>
                </div>
                <a class="btn btn-outline" href="dashboard.php">Overview HUD</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <!-- Forms Grid -->
            <section class="admin-nodes-grid">
                <article class="profile-shard" style="padding: 24px;">
                    <p class="eyebrow" style="color: var(--cyan-neon);">ADD CLINIC NODE</p>
                    <h3 style="margin-top: 0; color: #FFF;">Register Clinic</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_clinic">
                        <div class="field"><label>CLINIC NAME</label><input name="clinic_name" required placeholder="e.g. Care Nexus Central"></div>
                        <div class="field">
                            <label>CITY NODE</label>
                            <select name="city_id" required>
                                <option value="">Select City</option>
                                <?php while($city = mysqli_fetch_assoc($cityOptions)): ?>
                                    <option value="<?=$city['city_id']?>"><?=h($city['city_name'])?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="field"><label>ADDRESS</label><input name="address" required placeholder="Full street address"></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="field"><label>PHONE</label><input name="phone" placeholder="0300-0000000"></div>
                            <div class="field"><label>EMAIL</label><input type="email" name="email" placeholder="clinic@domain.com"></div>
                        </div>
                        <button class="btn btn-primary"><span>❖ Register Clinic Node</span></button>
                    </form>
                </article>

                <article class="profile-shard" style="padding: 24px;">
                    <p class="eyebrow" style="color: var(--emerald-bio);">ADD SPECIALTY</p>
                    <h3 style="margin-top: 0; color: #FFF;">Medical Specialization</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_specialization">
                        <div class="field"><label>SPECIALTY NAME</label><input name="specialization_name" required placeholder="e.g. Cardiology"></div>
                        <div class="field"><label>SHORT DESCRIPTION</label><textarea name="description" rows="2" required placeholder="Brief clinical scope"></textarea></div>
                        <div class="field"><label>GUIDE OVERVIEW</label><textarea name="overview" rows="2" placeholder="In-depth specialty introduction"></textarea></div>
                        <button class="btn btn-primary"><span>❖ Register Specialty Shard</span></button>
                    </form>
                </article>

                <article class="profile-shard" style="padding: 24px;">
                    <p class="eyebrow" style="color: var(--violet-quantum);">ADD CITY NODE</p>
                    <h3 style="margin-top: 0; color: #FFF;">City Network Node</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_city">
                        <div class="field"><label>CITY NAME</label><input name="city_name" required placeholder="e.g. Multan"></div>
                        <div class="field"><label>STATE / PROVINCE</label><input name="state" placeholder="e.g. Punjab"></div>
                        <button class="btn btn-primary"><span>❖ Register City Node</span></button>
                    </form>
                </article>
            </section>

            <!-- Tables Stack -->
            <section style="display: grid; gap: 28px;">
                <!-- Clinics Table -->
                <article class="cyber-table-wrap">
                    <p class="eyebrow">REGISTERED CLINIC NODES</p>
                    <h3 style="margin:0 0 16px; color:#FFF;">Active Clinic Network</h3>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CLINIC NAME</th>
                                <th>CITY NODE</th>
                                <th>ATTACHED DOCTORS</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($c = mysqli_fetch_assoc($clinics)): ?>
                                <tr>
                                    <td style="font-family: var(--font-mono); color: var(--cyan-neon);">#<?=$c['clinic_id']?></td>
                                    <td>
                                        <strong style="color: #FFF;"><?=h($c['clinic_name'])?></strong><br>
                                        <small style="color: var(--text-muted);"><?=h($c['address'])?></small>
                                    </td>
                                    <td><?=h($c['city_name'])?></td>
                                    <td style="font-family: var(--font-mono);"><?=$c['doctors_count']?> Doctors</td>
                                    <td><span class="status-pill status-<?=strtolower($c['status'])?>"><?=$c['status']?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <form method="post" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="action" value="set_clinic_status">
                                                <input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>">
                                                <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                                    <?php foreach(['Active','Inactive'] as $s): ?><option <?=$c['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Remove clinic?');">
                                                <input type="hidden" name="action" value="delete_clinic">
                                                <input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>">
                                                <button class="btn btn-outline" style="padding: 4px 10px; font-size: 11px; border-color: var(--rose-danger); color: var(--rose-danger);">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </article>

                <!-- Specializations Table -->
                <article class="cyber-table-wrap">
                    <p class="eyebrow">MEDICAL SPECIALIZATIONS</p>
                    <h3 style="margin:0 0 16px; color:#FFF;">Specialty Archive</h3>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>SPECIALTY NAME</th>
                                <th>DESCRIPTION</th>
                                <th>ATTACHED DOCTORS</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($s = mysqli_fetch_assoc($specializations)): ?>
                                <tr>
                                    <td><strong style="color: #FFF;"><?=h($s['specialization_name'])?></strong></td>
                                    <td style="color: var(--text-muted); font-size: 13px; max-width: 300px;"><?=h($s['description'])?></td>
                                    <td style="font-family: var(--font-mono); color: var(--cyan-neon);"><?=$s['doctors_count']?> Doctors</td>
                                    <td><span class="status-pill status-<?=strtolower($s['status'])?>"><?=$s['status']?></span></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <form method="post" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="action" value="set_specialization_status">
                                                <input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>">
                                                <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                                    <?php foreach(['Active','Inactive'] as $st): ?><option <?=$s['status']===$st?'selected':''?>><?=$st?></option><?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Remove specialization?');">
                                                <input type="hidden" name="action" value="delete_specialization">
                                                <input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>">
                                                <button class="btn btn-outline" style="padding: 4px 10px; font-size: 11px; border-color: var(--rose-danger); color: var(--rose-danger);">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
