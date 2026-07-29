<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$clinics = mysqli_query($conn, "SELECT cl.clinic_id,cl.clinic_name,cl.address,cl.status,c.city_name,(SELECT COUNT(*) FROM doctor_clinic dc WHERE dc.clinic_id=cl.clinic_id) doctors_count FROM clinics cl JOIN cities c ON c.city_id=cl.city_id ORDER BY cl.status ASC, cl.clinic_name ASC");
$specializations = mysqli_query($conn, "SELECT s.specialization_id,s.specialization_name,s.description,s.status,(SELECT COUNT(*) FROM doctors d WHERE d.specialization_id=s.specialization_id) doctors_count FROM specializations s ORDER BY s.status ASC, s.specialization_name ASC");
$cities = mysqli_query($conn, "SELECT city_id,city_name,state,status FROM cities ORDER BY status ASC, city_name ASC");
$cityOptions = mysqli_query($conn, "SELECT city_id,city_name FROM cities WHERE status='Active' ORDER BY city_name ASC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Directory | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('directory'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">DIRECTORY CONTROL</p><h1>Clinics, specialties, cities</h1><p>Control what appears in public search and doctor registration forms.</p></div>
            <a class="btn btn-outline" href="dashboard.php">Overview</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <section class="admin-split three">
            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">ADD CLINIC</p><h2>Clinic</h2></div></div>
                <form method="post" class="booking-form">
                    <input type="hidden" name="action" value="add_clinic">
                    <div class="field"><label>CLINIC NAME</label><input name="clinic_name" required></div>
                    <div class="field"><label>CITY</label><select name="city_id" required><option value="">Select city</option><?php while($city = mysqli_fetch_assoc($cityOptions)): ?><option value="<?=$city['city_id']?>"><?=h($city['city_name'])?></option><?php endwhile; ?></select></div>
                    <div class="field"><label>ADDRESS</label><input name="address" required></div>
                    <div class="form-row"><div class="field"><label>PHONE</label><input name="phone"></div><div class="field"><label>EMAIL</label><input type="email" name="email"></div></div>
                    <button class="btn btn-primary">Add clinic</button>
                </form>
            </article>
            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">ADD SPECIALTY</p><h2>Specialization</h2></div></div>
                <form method="post" class="booking-form">
                    <input type="hidden" name="action" value="add_specialization">
                    <div class="field"><label>NAME</label><input name="specialization_name" required></div>
                    <div class="field"><label>DESCRIPTION</label><textarea name="description" rows="3" required></textarea></div>
                    <div class="field"><label>GUIDE OVERVIEW</label><textarea name="overview" rows="3"></textarea></div>
                    <div class="field"><label>WHEN TO BOOK (ONE PER LINE)</label><textarea name="when_to_book" rows="4"></textarea></div>
                    <div class="field"><label>CARE TIPS (ONE PER LINE)</label><textarea name="care_points" rows="4"></textarea></div>
                    <button class="btn btn-primary">Add specialization</button>
                </form>
            </article>
            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">ADD CITY</p><h2>City</h2></div></div>
                <form method="post" class="booking-form">
                    <input type="hidden" name="action" value="add_city">
                    <div class="field"><label>CITY</label><input name="city_name" required></div>
                    <div class="field"><label>STATE / PROVINCE</label><input name="state"></div>
                    <button class="btn btn-primary">Add city</button>
                </form>
            </article>
        </section>

        <section class="admin-split">
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">CLINICS</p><h2>Website locations</h2></div></div>
                <div class="admin-table compact">
                    <?php while($c = mysqli_fetch_assoc($clinics)): ?>
                        <article>
                            <div><strong>#<?=$c['clinic_id']?> - <?=h($c['clinic_name'])?></strong><span><?=h($c['city_name'])?> - <?=$c['doctors_count']?> doctors</span><small><?=h($c['address'])?></small></div>
                            <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_clinic_status"><input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>"><select name="status"><?php foreach(['Active','Inactive'] as $s): ?><option <?=$c['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                            <form method="post" onsubmit="return confirm('Remove this clinic from website?');"><input type="hidden" name="action" value="delete_clinic"><input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>"><button class="btn btn-outline danger-btn">Remove</button></form>
                        </article>
                    <?php endwhile; ?>
                </div>
            </article>
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">SPECIALIZATIONS</p><h2>Public categories</h2></div></div>
                <div class="admin-table compact">
                    <?php while($s = mysqli_fetch_assoc($specializations)): ?>
                        <article>
                            <div><strong><?=h($s['specialization_name'])?></strong><span><?=$s['doctors_count']?> doctors</span><small><?=h($s['description'])?></small></div>
                            <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_specialization_status"><input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>"><select name="status"><?php foreach(['Active','Inactive'] as $st): ?><option <?=$s['status']===$st?'selected':''?>><?=$st?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                            <form method="post" onsubmit="return confirm('Remove this specialization from search?');"><input type="hidden" name="action" value="delete_specialization"><input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>"><button class="btn btn-outline danger-btn">Remove</button></form>
                        </article>
                    <?php endwhile; ?>
                </div>
            </article>
        </section>

        <section class="panel admin-table-panel">
            <div class="panel-head"><div><p class="eyebrow">CITIES</p><h2>City visibility</h2></div></div>
            <div class="admin-table relaxed">
                <?php while($city = mysqli_fetch_assoc($cities)): ?>
                    <article>
                        <div><strong><?=h($city['city_name'])?></strong><span><?=h($city['state'])?></span></div>
                        <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_city_status"><input type="hidden" name="city_id" value="<?=$city['city_id']?>"><select name="status"><?php foreach(['Active','Inactive'] as $s): ?><option <?=$city['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    </main>
</body>
</html>
