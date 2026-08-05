<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$role = $_GET['role'] ?? '';
$where = in_array($role, ['Admin','Doctor','Patient'], true) ? "WHERE role='".mysqli_real_escape_string($conn, $role)."'" : '';
$users = mysqli_query($conn, "SELECT user_id,full_name,email,phone,role,status,created_at FROM users $where ORDER BY FIELD(role,'Admin','Doctor','Patient'), created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Accounts Telemetry | Admin Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('users'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">USER TELEMETRY</p>
                    <h2>Account Oversight & Roles</h2>
                </div>
                <a class="btn btn-outline" href="dashboard.php">Overview HUD</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px;">
                <a class="btn <?=$role===''?'btn-primary':'btn-outline'?>" href="users.php">All Accounts</a>
                <?php foreach(['Admin','Doctor','Patient'] as $r): ?>
                    <a class="btn <?=$role===$r?'btn-primary':'btn-outline'?>" href="users.php?role=<?=$r?>"><?=$r?>s</a>
                <?php endforeach; ?>
            </div>

            <section class="cyber-table-wrap">
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>FULL NAME</th>
                            <th>ROLE</th>
                            <th>CONTACT DETAILS</th>
                            <th>REGISTRATION DATE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><strong style="color: var(--text-main);"><?=h($u['full_name'])?></strong></td>
                                <td><span style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon); font-weight: 700;"><?=h($u['role'])?></span></td>
                                <td style="color: var(--text-muted); font-size: 13px;">
                                    <?=h($u['email'])?><br>
                                    <small style="font-family: var(--font-mono);"><?=h($u['phone'])?></small>
                                </td>
                                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
                                    <?=date('d M Y', strtotime($u['created_at']))?>
                                </td>
                                <td>
                                    <span class="status-pill status-<?=strtolower($u['status'])?>">
                                        <?=h($u['status'])?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <form method="post" style="display: flex; gap: 6px;">
                                            <input type="hidden" name="action" value="set_user_status">
                                            <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                                            <select name="status" style="padding: 6px 8px; font-size: 11px; border-radius: var(--radius-sm);">
                                                <?php foreach(['Active','Inactive','Suspended'] as $s): ?>
                                                    <option <?=$u['status']===$s?'selected':''?>><?=$s?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;">Save</button>
                                        </form>

                                        <?php if($u['role'] !== 'Admin'): ?>
                                            <form method="post" onsubmit="return confirm('Remove account? Accounts with clinical history will be suspended.');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                                                <button class="btn btn-outline" style="padding: 6px 12px; font-size: 11px; border-color: var(--rose-danger); color: var(--rose-danger);">Remove</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
