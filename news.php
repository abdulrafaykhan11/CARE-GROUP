<?php
session_start();
require_once __DIR__ . '/config/db.php';
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';

// Fetch only Published news
$q = "SELECT * FROM medical_news WHERE status = 'Published' ORDER BY published_at DESC, created_at DESC";
$newsResult = $conn->query($q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical News & Insights | CARE Nexus</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .news-hero {
            padding: 120px 5% 60px;
            background: linear-gradient(135deg, var(--bg-main) 0%, rgba(6,182,212,0.05) 100%);
            text-align: center;
        }
        .news-hero h1 {
            font-size: 42px;
            margin-bottom: 20px;
            color: var(--text-main);
            font-weight: 700;
        }
        .news-hero p {
            color: var(--text-muted);
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            padding: 60px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .news-card {
            background: var(--bg-card);
            border: 1px solid var(--border-cyber);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(12px);
        }
        
        .news-card:hover {
            transform: translateY(-5px);
            border-color: var(--cyan-neon);
            box-shadow: 0 10px 30px rgba(6,182,212,0.1);
        }
        
        .news-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-cyber);
            display: block;
            background: linear-gradient(135deg, rgba(6,182,212,0.15) 0%, rgba(139,92,246,0.15) 100%);
        }
        .news-img-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(6,182,212,0.12) 0%, rgba(139,92,246,0.12) 100%);
            border-bottom: 1px solid var(--border-cyber);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--cyan-neon);
            opacity: 0.5;
        }
        
        .news-content {
            padding: 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .news-meta {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--cyan-neon);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .news-title {
            font-size: 20px;
            color: var(--text-main);
            margin-bottom: 12px;
            line-height: 1.4;
            font-weight: 600;
        }
        
        .news-excerpt {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
            flex-grow: 1;
        }
        
        .news-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border-cyber);
        }
        
        .read-more {
            color: var(--cyan-neon);
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s;
        }
        
        .read-more:hover {
            color: var(--violet-quantum);
        }
        
        .feedback-stats {
            display: flex;
            gap: 12px;
            font-size: 13px;
            color: var(--text-dim);
        }
        
        .feedback-stats span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="news-hero">
        <h1>Medical News & Insights</h1>
        <p>Stay informed with the latest breakthroughs, deep-dive analyses, and verified health updates from the global medical community.</p>
    </section>

    <div class="news-grid">
        <?php if($newsResult && $newsResult->num_rows > 0): ?>
            <?php while($row = $newsResult->fetch_assoc()): ?>
                <article class="news-card">
                    <?php if(!empty($row['image'])): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>" 
                             alt="<?= htmlspecialchars($row['title']) ?>" 
                             class="news-img"
                             onerror="this.onerror=null; this.src=''; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="news-img-placeholder" style="display:none;">🏥</div>
                    <?php else: ?>
                        <div class="news-img-placeholder">🏥</div>
                    <?php endif; ?>
                    <div class="news-content">
                        <div class="news-meta">
                            <span>📅 <?= date('M d, Y', strtotime($row['published_at'] ?? $row['created_at'])) ?></span>
                            <span>• Verified by Admin</span>
                        </div>
                        <h2 class="news-title"><?= htmlspecialchars($row['title']) ?></h2>
                        <div class="news-excerpt">
                            <?= htmlspecialchars(substr(strip_tags($row['description']), 0, 150)) ?>...
                        </div>
                        <div class="news-footer">
                            <a href="news_detail.php?id=<?= $row['news_id'] ?>" class="read-more">Read Full Article &rarr;</a>
                            <div class="feedback-stats">
                                <span title="Helpful" style="color: var(--emerald-bio);">👍 <?= $row['likes'] ?></span>
                                <span title="Not Helpful" style="color: var(--rose-danger); margin-left: 8px;">👎 <?= $row['dislikes'] ?></span>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding: 60px; color: var(--text-muted);">
                <p>No medical news published yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
