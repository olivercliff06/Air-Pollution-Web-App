<?php
require_once __DIR__ . '/config.php';

$labels   = category_labels();
$category = $_GET['category'] ?? null;
$valid    = $category && isset($labels[$category]);
$posts    = load_posts();

// Filter posts by category if valid
$filteredPosts = [];
if ($valid) {
    foreach ($posts as $post) {
        if (($post['category'] ?? '') === $category) {
            $filteredPosts[] = $post;
        }
    }
    // Sort newest first
    usort($filteredPosts, function ($a, $b) {
        return strtotime($b['date'] ?? 'now') <=> strtotime($a['date'] ?? 'now');
    });
}

// Simple helper to count comments
function comment_count(array $post): int {
    return isset($post['comments']) && is_array($post['comments']) ? count($post['comments']) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Discussions - Pollution Forum</title>
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
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'forum-posts.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'forum-posts.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'forum-posts.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="category-header-section">
                <div class="card">
                    <?php if ($valid): ?>
                        <h2 class="section-title">
                            <?php
                            $icons = [
                                'air'     => '💨',
                                'water'   => '💧',
                                'land'    => '🌱',
                                'noise'   => '🔊',
                                'plastic' => '♻️',
                                'general' => '💬',
                            ];
                            $icon = $icons[$category] ?? '';
                            echo h(trim($icon . ' ' . $labels[$category]));
                            ?>
                        </h2>
                        <p class="category-description">
                            <?php
                            $descs = [
                                'air'     => 'Discuss issues related to air quality, emissions, and atmospheric pollution.',
                                'water'   => 'Share concerns about water contamination, marine life, and water quality.',
                                'land'    => 'Talk about soil contamination, deforestation, and land degradation.',
                                'noise'   => 'Discuss noise levels, urban soundscapes, and their environmental impact.',
                                'plastic' => 'Address plastic pollution, recycling, and sustainable alternatives.',
                                'general' => 'Open forum for general pollution-related topics and conversations.',
                            ];
                            echo h($descs[$category] ?? '');
                            ?>
                        </p>
                    <?php else: ?>
                        <h2 class="section-title">Category Not Found</h2>
                        <p class="category-description">The requested category does not exist.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="posts-list-section">
                <div id="postsListContainer" class="posts-list-container">
                    <?php if (!$valid): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <h3>Category not found</h3>
                            <p>Please select a valid category from the <a href="index.php#categories">Discussion Categories</a> page.</p>
                        </div>
                    <?php elseif (empty($filteredPosts)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <h3>No posts in this category yet</h3>
                            <p>Be the first to start a discussion about <?php echo h($labels[$category]); ?>!</p>
                            <a href="new-post.php?category=<?php echo urlencode($category); ?>" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Create New Post</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filteredPosts as $post): ?>
                            <?php
                            $date      = new DateTime($post['date'] ?? 'now');
                            $timeLabel = time_ago($date);
                            $comments  = comment_count($post);
                            $thumbPath = null;
                            if (!empty($post['images']) && is_array($post['images'])) {
                                $thumbPath = $post['images'][0];
                            } elseif (!empty($post['image'])) {
                                $thumbPath = $post['image'];
                            }
                            ?>
                            <a href="post-detail.php?id=<?php echo (int)$post['id']; ?>&category=<?php echo urlencode($category); ?>" class="post-list-item">
                                <div class="post-list-content">
                                    <div class="post-list-category">
                                        <span class="post-category-badge">
                                            <?php echo h($labels[$post['category']] ?? $post['category']); ?>
                                        </span>
                                    </div>
                                    <h3 class="post-list-title"><?php echo h($post['title']); ?></h3>
                                    <div class="post-list-meta">
                                        <span class="post-list-author">👤 <?php echo h($post['author']); ?></span>
                                        <span class="post-list-date"><?php echo h($timeLabel); ?></span>
                                        <span class="post-list-comments">💬 <?php echo $comments; ?> <?php echo $comments === 1 ? 'comment' : 'comments'; ?></span>
                                    </div>
                                </div>
                                <?php if ($thumbPath): ?>
                                    <div class="post-list-thumbnail">
                                        <img src="<?php echo h($thumbPath); ?>" alt="Post thumbnail" class="post-thumbnail-image">
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
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


