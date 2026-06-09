<?php
require_once __DIR__ . '/config.php';

$topicId = $_GET['topic'] ?? null;
$article = $topicId ? find_article($topicId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? h($article['title']) . ' - Pollution Forum' : 'Article - Pollution Forum'; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="new-post.php" class="nav-link">New Post</a>
                <a href="index.php#quiz" class="nav-link">Quiz Game</a>
                <a href="index.php#learning" class="nav-link">Learning Corner</a>
                <div class="nav-auth">
                    <?php if (is_logged_in()): ?>
                        <?php $user = current_user(); ?>
                        <div class="user-info">
                            <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" class="username-link">
                                <?php echo h($user['username']); ?><?php echo is_admin() ? ' (Admin)' : ''; ?>
                            </a>
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'article.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'article.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'article.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="back-button-section">
                <a href="index.php#learning" class="btn-back">← Back to Learning Corner</a>
                <?php if (is_admin() && $article): ?>
                    <a href="article-admin.php?mode=edit&id=<?php echo urlencode($article['id']); ?>" class="btn btn-primary" style="margin-left: auto;">
                        Edit Article
                    </a>
                <?php endif; ?>
            </div>

            <section class="article-section">
                <div class="card">
                    <?php if ($article): ?>
                        <div class="article-header">
                            <h1 class="article-title"><?php echo h($article['title']); ?></h1>
                        </div>
                        <?php if (!empty($article['image'])): ?>
                            <div class="article-image-container" style="margin-bottom: 1rem; display: flex; justify-content: center;">
                                <img
                                    src="<?php echo h($article['image']); ?>"
                                    alt="Article image"
                                    style="max-width: 230px; width: 100%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"
                                >
                            </div>
                        <?php endif; ?>
                        <div class="article-content-display">
                            <div class="article-content">
                                <?php echo $article['content']; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <h3>Article not found</h3>
                            <p>Please select an article from the <a href="index.php#learning">Learning Corner</a>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Pollution Forum. Join the conversation to make a difference.</p>
        </div>
    </footer>
</body>
</html>


