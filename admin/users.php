<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$role = $_GET['role'] ?? '';
$where = in_array($role, ['Admin','Doctor','Patient'], true) ? "WHERE role='".mysqli_real_escape_string($conn, $role)."'" : '';
$users = mysqli_query($conn, "SELECT user_id,full_name,email,phone,role,status,created_at FROM users $where ORDER BY FIELD(role,'Admin','Doctor','Patient'), created_at DESC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Users | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('users'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">ACCOUNTS</p><h1>Users</h1><p>Activate, suspend, or remove accounts. Accounts with appointment history are suspended instead of hard-deleted.</p></div>
            <a class="btn btn-outline" href="dashboard.php">Overview</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <nav class="admin-tabs">
            <a class="<?=!$role?'active':''?>" href="users.php">All</a>
            <?php foreach(['Admin','Doctor','Patient'] as $r): ?><a class="<?=$role===$r?'active':''?>" href="users.php?role=<?=$r?>"><?=$r?></a><?php endforeach; ?>
        </nav>

        <section class="panel admin-table-panel">
            <div class="admin-table relaxed">
                <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <article>
                        <div><strong><?=h($u['full_name'])?></strong><span><?=h($u['role'])?> - <?=h($u['email'])?> - <?=h($u['phone'])?></span><small>Joined <?=date('d M Y', strtotime($u['created_at']))?></small></div>
                        <form method="post" class="inline-admin-form">
                            <input type="hidden" name="action" value="set_user_status">
                            <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                            <select name="status"><?php foreach(['Active','Inactive','Suspended'] as $s): ?><option <?=$u['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select>
                            <button class="btn btn-primary">Save</button>
                        </form>
                        <?php if($u['role'] !== 'Admin'): ?>
                            <form method="post" onsubmit="return confirm('Remove this account? Accounts with history will be suspended.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                                <button class="btn btn-outline danger-btn">Remove</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    </main>
</body>
</html>
