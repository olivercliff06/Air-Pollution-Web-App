<?php
require_once __DIR__ . '/config.php';

$articles = load_articles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollution Forum - Discuss Environmental Issues</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link active">Home</a>
                <a href="new-post.php" class="nav-link">New Post</a>
                <a href="index.php#quiz" class="nav-link">Quiz Game</a>
                <a href="index.php#learning" class="nav-link">Learning Corner</a>
                <div class="nav-auth">
                    <?php if (is_logged_in()): ?>
                        <?php $user = current_user(); ?>
                        <div class="user-info" id="userInfo">
                            <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" id="usernameDisplay" class="username-link">
                                <?php echo h($user['username']); ?><?php echo is_admin() ? ' (Admin)' : ''; ?>
                            </a>
                            <?php if (is_admin()): ?>
                                <a href="admin.php" class="btn btn-outline" style="margin-left: 0.5rem;">Admin</a>
                            <?php endif; ?>
                            <a href="logout.php?redirect=index.php" class="btn btn-logout" id="logoutButton">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode('index.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" id="loginButton" href="login.php?redirect=<?php echo urlencode('index.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <!-- Website Introduction/Slogan -->
            <section class="intro-banner">
                <div class="intro-banner-content">
                    <h1 class="intro-slogan">🌍 Together for a Cleaner Planet</h1>
                    <p class="intro-text">
                        Join our community to discuss environmental pollution, share knowledge, and take action for a sustainable future.
                    </p>
                </div>
            </section>

            <!-- Discussion Categories -->
            <section id="categories" class="categories-section">
                <div class="card">
                    <h2 class="section-title">Discussion Categories</h2>
                    <div class="categories-grid">
                        <a href="forum-posts.php?category=air" class="category-card category-link">
                            <div class="category-icon">💨</div>
                            <h3>Air Pollution</h3>
                            <p>Discuss issues related to air quality, emissions, and atmospheric pollution.</p>
                        </a>
                        <a href="forum-posts.php?category=water" class="category-card category-link">
                            <div class="category-icon">💧</div>
                            <h3>Water Pollution</h3>
                            <p>Share concerns about water contamination, marine life, and water quality.</p>
                        </a>
                        <a href="forum-posts.php?category=land" class="category-card category-link">
                            <div class="category-icon">🌱</div>
                            <h3>Land Pollution</h3>
                            <p>Talk about soil contamination, deforestation, and land degradation.</p>
                        </a>
                        <a href="forum-posts.php?category=noise" class="category-card category-link">
                            <div class="category-icon">🔊</div>
                            <h3>Noise Pollution</h3>
                            <p>Discuss noise levels, urban soundscapes, and their environmental impact.</p>
                        </a>
                        <a href="forum-posts.php?category=plastic" class="category-card category-link">
                            <div class="category-icon">♻️</div>
                            <h3>Plastic Waste</h3>
                            <p>Address plastic pollution, recycling, and sustainable alternatives.</p>
                        </a>
                        <a href="forum-posts.php?category=general" class="category-card category-link">
                            <div class="category-icon">💬</div>
                            <h3>General Discussion</h3>
                            <p>Open forum for general pollution-related topics and conversations.</p>
                        </a>
                    </div>
                </div>
            </section>
            
            <!-- Quiz Game -->
            <section id="quiz" class="categories-section">
                <div class="card">
                    <h2 class="section-title">Quiz Game</h2>
                    <p style="margin-bottom: 0.75rem;">
                        Challenge yourself with quizzes about pollution, environmental issues, and sustainability.<br><br>
                    </p>
                    <a href="quiz.php" class="btn btn-primary">Play Quiz</a>
                    <?php if (is_admin()): ?>
                        <a href="quiz-admin.php" class="btn btn-outline" style="margin-left: 0.5rem;">Manage Quizzes</a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Learning Corner -->
            <section id="learning" class="learning-section">
                <div class="card">
                    <div class="learning-header">
                        <h2 class="section-title">Learning Corner</h2>
                        <?php if (is_admin()): ?>
                            <div class="admin-controls">
                                <a href="article-admin.php" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                                    Manage Articles
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Learning Content Display -->
                    <div class="learning-content-display">
                        <div class="articles-grid">
                            <?php foreach ($articles as $article): ?>
                                <a href="article.php?topic=<?php echo urlencode($article['id']); ?>" class="article-card">
                                    <div class="article-card-icon">
                                        <?php
                                        // Use first token (emoji) from title if present
                                        $parts = explode(' ', $article['title']);
                                        echo h($parts[0]);
                                        ?>
                                    </div>
                                    <h3><?php echo h($article['title']); ?></h3>
                                    <p><?php echo h($article['description']); ?></p>
                                    <span class="article-link-text">Read more →</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
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


