<?php
session_start();
require_once __DIR__ . '/config/db.php';
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: news.php");
    exit;
}

// Fetch the article (only Published)
$stmt = $conn->prepare("SELECT * FROM medical_news WHERE news_id = ? AND status = 'Published'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h2 style='text-align:center; padding: 100px; color: var(--text-main); font-family: sans-serif;'>Article not found or not yet published.</h2>";
    exit;
}

$article = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> | CARE Nexus</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-container {
            max-width: 900px;
            margin: 120px auto 60px;
            padding: 0 5%;
        }
        
        .article-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .article-meta {
            font-family: var(--font-mono);
            font-size: 14px;
            color: var(--cyan-neon);
            margin-bottom: 20px;
            display: inline-flex;
            gap: 16px;
            align-items: center;
            background: rgba(6,182,212,0.1);
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid rgba(6,182,212,0.2);
        }
        
        .article-title {
            font-size: 42px;
            color: var(--text-main);
            line-height: 1.2;
            margin-bottom: 30px;
            font-weight: 800;
        }
        
        .article-img {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: var(--radius-xl);
            margin-bottom: 40px;
            border: 1px solid var(--border-cyber);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .article-body {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-muted);
        }
        
        .article-body h3 {
            color: var(--text-main);
            font-size: 24px;
            margin: 40px 0 20px;
        }
        
        .article-body p {
            margin-bottom: 20px;
        }
        
        .feedback-section {
            margin-top: 60px;
            padding: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-cyber);
            border-radius: var(--radius-lg);
            text-align: center;
            backdrop-filter: blur(12px);
        }
        
        .feedback-title {
            font-size: 20px;
            color: var(--text-main);
            margin-bottom: 24px;
            font-weight: 600;
        }
        
        .feedback-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .btn-feedback {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-cyber);
            border-radius: 999px;
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .btn-feedback:hover {
            background: rgba(6,182,212,0.1);
            border-color: var(--cyan-neon);
            transform: translateY(-2px);
        }
        
        .btn-feedback.active-yes {
            background: rgba(16,185,129,0.15);
            border-color: var(--emerald-bio);
            color: var(--emerald-bio);
        }
        
        .btn-feedback.active-no {
            background: rgba(244,63,94,0.15);
            border-color: var(--rose-danger);
            color: var(--rose-danger);
        }
        
        #feedback-msg {
            margin-top: 16px;
            font-size: 14px;
            color: var(--cyan-neon);
            min-height: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="article-container">
        <div class="article-header">
            <div class="article-meta">
                <span>📅 Published: <?= date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])) ?></span>
                <span>🛡️ Admin Verified</span>
            </div>
            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
        </div>

        <?php if(!empty($article['image'])): ?>
            <img src="<?= htmlspecialchars($article['image']) ?>" alt="Article Cover" class="article-img">
        <?php endif; ?>

        <div class="article-body">
            <?= $article['description'] // Trusting DB content since it's admin verified ?>
        </div>

        <!-- Like / Dislike Feedback -->
        <div class="feedback-section">
            <div class="feedback-title">Did you find this medical insight useful?</div>
            <div class="feedback-buttons">
                <button class="btn-feedback" id="btn-like" onclick="submitFeedback('like')">
                    👍 Yes (<span id="count-like"><?= $article['likes'] ?></span>)
                </button>
                <button class="btn-feedback" id="btn-dislike" onclick="submitFeedback('dislike')">
                    👎 No (<span id="count-dislike"><?= $article['dislikes'] ?></span>)
                </button>
            </div>
            <div id="feedback-msg"></div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const newsId = <?= $id ?>;
        const storageKey = 'care_news_vote_' + newsId;
        let currentVote = localStorage.getItem(storageKey) || null; // 'like', 'dislike', or null

        function updateVoteUI(userVote) {
            const btnLike = document.getElementById('btn-like');
            const btnDislike = document.getElementById('btn-dislike');
            const msgEl = document.getElementById('feedback-msg');

            btnLike.classList.remove('active-yes');
            btnDislike.classList.remove('active-no');

            if (userVote === 'like') {
                btnLike.classList.add('active-yes');
                msgEl.style.color = 'var(--emerald-bio)';
                msgEl.textContent = '👍 You marked this medical insight as helpful.';
            } else if (userVote === 'dislike') {
                btnDislike.classList.add('active-no');
                msgEl.style.color = 'var(--rose-danger)';
                msgEl.textContent = '👎 You marked this medical insight as not helpful.';
            } else {
                msgEl.style.color = 'var(--cyan-neon)';
                msgEl.textContent = '';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateVoteUI(currentVote);
        });

        function submitFeedback(targetVote) {
            const previousVote = currentVote;
            const newVote = (currentVote === targetVote) ? null : targetVote; // Toggle off if clicked again

            fetch('api/news_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: newsId,
                    previous_vote: previousVote,
                    new_vote: newVote
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    currentVote = data.user_vote;
                    if (currentVote) {
                        localStorage.setItem(storageKey, currentVote);
                    } else {
                        localStorage.removeItem(storageKey);
                    }

                    document.getElementById('count-like').textContent = data.new_likes;
                    document.getElementById('count-dislike').textContent = data.new_dislikes;
                    updateVoteUI(currentVote);
                } else {
                    document.getElementById('feedback-msg').style.color = 'var(--rose-danger)';
                    document.getElementById('feedback-msg').textContent = data.message || 'An error occurred.';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('feedback-msg').style.color = 'var(--rose-danger)';
                document.getElementById('feedback-msg').textContent = 'Failed to submit feedback.';
            });
        }
    </script>
</body>
</html>
