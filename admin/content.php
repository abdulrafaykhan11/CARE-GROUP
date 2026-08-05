<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$newsRows = mysqli_query($conn, "SELECT news_id,title,status,created_at,published_at FROM medical_news ORDER BY updated_at DESC, news_id DESC");
$faqRows = mysqli_query($conn, "SELECT f.faq_id,f.question,f.answer,f.status,s.specialization_name FROM specialization_faqs f JOIN specializations s ON s.specialization_id=f.specialization_id ORDER BY f.status ASC, s.specialization_name ASC, f.sort_order ASC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Content Shards | Admin Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('content'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">CONTENT SHARDS & FAQS</p>
                    <h2>Medical News & Specialty FAQs</h2>
                </div>
                <a class="btn btn-outline" href="dashboard.php">Overview HUD</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <section style="display: grid; gap: 28px;">
                <!-- FAQ Shards -->
                <article class="cyber-table-wrap" style="margin: 0; width: 100%;">
                    <p class="eyebrow">SPECIALTY FAQ ARCHIVE</p>
                    <h3 style="margin: 0 0 16px; color: var(--text-main);">Specialty FAQs</h3>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>QUESTION & ANSWER</th>
                                <th>SPECIALTY</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($faqRows)): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-main); display: block; margin-bottom: 4px;"><?=h($row['question'])?></strong>
                                        <small style="color: var(--text-muted); line-height: 1.5; display: block; max-width: 760px;"><?=h($row['answer'])?></small>
                                    </td>
                                    <td style="font-family: var(--font-mono); color: var(--cyan-neon); font-size: 12px;"><?=h($row['specialization_name'])?></td>
                                    <td>
                                        <span class="status-pill status-<?=strtolower($row['status'])?>">
                                            <?=h($row['status'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" style="display: flex; gap: 6px;">
                                            <input type="hidden" name="action" value="set_faq_status">
                                            <input type="hidden" name="faq_id" value="<?=$row['faq_id']?>">
                                            <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                                <?php foreach(['Active','Inactive'] as $s): ?>
                                                    <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </article>

                <!-- News Shards -->
                <article class="cyber-table-wrap" style="margin: 0; width: 100%;">
                    <p class="eyebrow">MEDICAL NEWS SHARDS</p>
                    <h3 style="margin: 0 0 16px; color: var(--text-main);">Public Medical News</h3>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>ARTICLE TITLE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($newsRows)): ?>
                                <?php while($row = mysqli_fetch_assoc($newsRows)): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-main);"><?=h($row['title'])?></strong><br>
                                            <small style="color: var(--text-muted); font-family: var(--font-mono);">
                                                Created <?=date('d M Y', strtotime($row['created_at']))?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="status-pill status-<?=strtolower($row['status'])?>">
                                                <?=h($row['status'])?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" style="display: flex; gap: 6px;">
                                                <input type="hidden" name="action" value="set_news_status">
                                                <input type="hidden" name="news_id" value="<?=$row['news_id']?>">
                                                <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                                    <?php foreach(['Draft','Published','Archived'] as $s): ?>
                                                        <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="color: var(--text-muted); text-align: center;">No news articles registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
