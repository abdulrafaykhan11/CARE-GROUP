<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$newsRows = mysqli_query($conn, "SELECT news_id,title,status,created_at,published_at FROM medical_news ORDER BY updated_at DESC, news_id DESC");
$faqRows = mysqli_query($conn, "SELECT f.faq_id,f.question,f.answer,f.status,s.specialization_name FROM specialization_faqs f JOIN specializations s ON s.specialization_id=f.specialization_id ORDER BY f.status ASC, s.specialization_name ASC, f.sort_order ASC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Content | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('content'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">CONTENT STATUS</p><h1>News and FAQs</h1><p>Control public content visibility for medical news and specialization FAQ entries.</p></div>
            <a class="btn btn-outline" href="dashboard.php">Overview</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <section class="admin-split">
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">NEWS</p><h2>Medical news</h2></div></div>
                <div class="admin-table compact">
                    <?php if(mysqli_num_rows($newsRows)): ?>
                        <?php while($row = mysqli_fetch_assoc($newsRows)): ?>
                            <article>
                                <div><strong><?=h($row['title'])?></strong><span>Created <?=date('d M Y', strtotime($row['created_at']))?></span><small><?=h($row['published_at'] ?: 'Not published')?></small></div>
                                <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_news_status"><input type="hidden" name="news_id" value="<?=$row['news_id']?>"><select name="status"><?php foreach(['Draft','Published','Archived'] as $s): ?><option <?=$row['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <article><div><strong>No news rows yet</strong><span>Medical news entries will appear here.</span></div></article>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">FAQS</p><h2>Specialty FAQs</h2></div></div>
                <div class="admin-table compact">
                    <?php while($row = mysqli_fetch_assoc($faqRows)): ?>
                        <article>
                            <div><strong><?=h($row['question'])?></strong><span><?=h($row['specialization_name'])?></span><small><?=h($row['answer'])?></small></div>
                            <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_faq_status"><input type="hidden" name="faq_id" value="<?=$row['faq_id']?>"><select name="status"><?php foreach(['Active','Inactive'] as $s): ?><option <?=$row['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                        </article>
                    <?php endwhile; ?>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
